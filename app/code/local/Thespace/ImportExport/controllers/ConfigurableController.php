<?php

class Thespace_ImportExport_ConfigurableController extends Mage_Adminhtml_Controller_Action
{
    
    public function indexAction()
    {
        // "Fetch" display
        $this->loadLayout();
        
        // "Inject" into display
        // THe below example will not actualy show anything since the core/template is empty
        $this->_addContent($this->getLayout()->createBlock('core/template')->setTemplate('thespace/import_export/import/configurable.phtml'));
        
        // "Output" display
        $this->renderLayout();
    }
    
    public function ajaximportrunAction()
    {
        header('Content-Type: application/json');
        $response = [
            'status' => 'OK',
            'errors' => [],
        ];
        
        $importHelper = Mage::helper('thespaceimportexport/Import');
        $csvHelper = Mage::helper('thespaceimportexport/Csv');
        $productParserHelper = Mage::helper('thespaceimportexport/ProductParser');
        
        $filePath = $_POST['file'];
        
        $dataItems = [];
        
        foreach ($csvHelper->getRows($filePath) as $row) {
            
            $dataItems = $productParserHelper->getConfigurableItemsFromRow($row);
        }
        
        $dataItems = $productParserHelper->applyParentCells($dataItems);
        
        $now = new DateTime();
        
        $importDir = implode(DIRECTORY_SEPARATOR, [
                \Mage::getBaseDir('media'),
                "thespace-import-export",
                "bulk-configurable",
                $now->format('Y'),
                $now->format('m'),
                $now->format('d'),
            ]) . DIRECTORY_SEPARATOR;
        if (!file_exists($importDir)) {
            mkdir($importDir, 0777, true);
        }
        $importFile = implode(DIRECTORY_SEPARATOR, [
            $importDir,
            $now->format('Y-m-d-h-i-s') . ".json",
        ]);
        
        file_put_contents($importFile, json_encode($dataItems));
        
        $dataGroups = $importHelper->groupImportItems($dataItems, 500);
        
        $import = Mage::getModel('fastsimpleimport/import');
        
        $index = 0;
        foreach ($dataGroups as $dataGroup) {
            
            $univokeDataGroup = [];
            foreach ($dataGroup as $dataGroupItem) {
                $sku = $dataGroupItem['sku'];
                $univokeDataGroup[$sku] = $dataGroupItem;
            }
            $dataGroup = array_values($univokeDataGroup);
            
            $dataGroup = $productParserHelper->parseArrayCells($dataGroup);
            
            try {
                $import->processProductImport($dataGroup);
            } catch (Exception $e) {
                $error = [
                    'group'  => $index,
                    'errors' => $import->getErrorMessages(),
                ];
                $response['errors'][] = $error;
            }
            $index++;
        }
        
        echo json_encode($response);
        die();
    }
    
}