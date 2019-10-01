<?php
require_once 'abstract.php';

class Thespace_Import_Export_Cron_Import extends Mage_Shell_Abstract
{
    protected $_argname = [];
    
    public function __construct()
    {
        parent::__construct();
        
        // Time limit to infinity
        set_time_limit(0);
        
        // Get command line argument named "argname"
        // Accepts multiple values (comma separated)
        if ($this->getArg('argname')) {
            $this->_argname = array_merge(
                $this->_argname,
                array_map(
                    'trim',
                    explode(',', $this->getArg('argname'))
                )
            );
        }
    }
    
    // Shell script point of entry
    public function run()
    {
        $cronHelper = Mage::helper('thespaceimportexport/Cron');
        
        $stepRows = 1000;
        
        $todoDirectory = implode(DIRECTORY_SEPARATOR, [
                \Mage::getBaseDir('media'),
                "thespace-import-export",
                "cron",
                "todo",
                "",
            ]) . DIRECTORY_SEPARATOR;
        if (!file_exists($todoDirectory)) {
            mkdir($todoDirectory, 0777, true);
        }
        
        $importDirectory = implode(DIRECTORY_SEPARATOR, [
                \Mage::getBaseDir('media'),
                "thespace-import-export",
                "cron",
                "done",
                "",
            ]) . DIRECTORY_SEPARATOR;
        if (!file_exists($importDirectory)) {
            mkdir($importDirectory, 0777, true);
        }
        
        if (file_exists($todoDirectory)) {
            $todoFiles = array_diff(scandir($todoDirectory), ['..', '.']);
            
            foreach ($todoFiles as $todoFile) {
                
                $todoFilePath = $todoDirectory . $todoFile;
                
                if (file_exists($todoFilePath)) {
                    
                    $isExecutable = $cronHelper->isExecutable();
                    
                    if ($isExecutable) {
                        
                        $importFile = $importDirectory . basename($todoFile);
                        copy($todoFilePath, $importFile);
                        unlink($todoFilePath);
                        
                        $configurationHelper = Mage::helper('thespaceimportexport/Configuration');
                        $skuHelper = Mage::helper('thespaceimportexport/Sku');
                        $importHelper = Mage::helper('thespaceimportexport/Import');
                        $csvHelper = Mage::helper('thespaceimportexport/Csv');
                        $productParserHelper = Mage::helper('thespaceimportexport/ProductParser');
                        
                        $existingSkus = $skuHelper->getExistingSkus();
                        $attributes = Mage::getResourceModel('catalog/product_attribute_collection')
                            ->getItems();
                        
                        $defaultRow = [
                            '_attribute_set'    => $configurationHelper->get(Thespace_ImportExport_Helper_Configuration::OPTION_DEFAULT_ATTRIBUTE_SET),
                            '_product_websites' => $configurationHelper->get(Thespace_ImportExport_Helper_Configuration::OPTION_DEFAULT_PRODUCT_WEBSITES),
                            'tax_class_id'      => $configurationHelper->get(Thespace_ImportExport_Helper_Configuration::OPTION_DEFAULT_TAX_CLASS_ID),
                        ];
                        
                        $rows = [];
                        foreach ($csvHelper->getRows($importFile) as $row) {
                            $rowData = $productParserHelper->getDataFromRow($row, $attributes);
                            
                            if (!in_array($rowData['sku'], $existingSkus)) {
                                $row = array_merge($defaultRow, $row);
                            }
                            
                            $rows[] = $row;
                        }
                        
                        $dataItems = $productParserHelper->getDataFromRows($rows);
                        $dataItems = $productParserHelper->applyParentCells($dataItems);
                        
                        $dataGroups = $importHelper->groupImportItems($dataItems, $stepRows);
                        
                        $productParserHelper = Mage::helper('thespaceimportexport/ProductParser');
                        
                        $import = Mage::getModel('fastsimpleimport/import');
                        
                        foreach ($dataGroups as $dataGroup) {
                            try {
                                $dataGroup = $productParserHelper->parseImages($dataGroup);
                                $dataGroup = $productParserHelper->parseArrayCells($dataGroup);
                                
                                $import->processProductImport($dataGroup);
                            } catch (Exception $e) {
                            }
                        }
                    }
                }
            }
        }
    }
    
    // Usage instructions
    public function usageHelp()
    {
        return <<<USAGE
Usage:  php -f scriptname.php -- [options]
 
  --argname <argvalue>       Argument description
 
  help                   This help
 
USAGE;
    }
}

// Instantiate
$shell = new Thespace_Import_Export_Cron_Import();

// Initiate script
$shell->run();
