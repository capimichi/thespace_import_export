<?php

class Thespace_ImportExport_ImportController extends Mage_Adminhtml_Controller_Action
{
    
    public function indexAction()
    {
        // "Fetch" display
        $this->loadLayout();
        
        // "Inject" into display
        // THe below example will not actualy show anything since the core/template is empty
        $this->_addContent($this->getLayout()->createBlock('core/template')->setTemplate('thespace/import_export/import/import.phtml'));
        
        // "Output" display
        $this->renderLayout();
    }
    
    public function ajaximportparseAction()
    {
        header('Content-Type: application/json');
        $response = [
            'status' => 'OK',
            'errors' => [],
        ];
        
        if (isset($_FILES['file'])) {
            $filePath = $_FILES['file']['tmp_name'];
            
            $importDirectory = implode(DIRECTORY_SEPARATOR, [
                    \Mage::getBaseDir('media'),
                    "thespace-import-export",
                ]) . DIRECTORY_SEPARATOR;
            
            $canCreateImportDirectory = true;
            if (!file_exists($importDirectory)) {
                $canCreateImportDirectory = mkdir($importDirectory, 0777, true);
            }
            
            if ($canCreateImportDirectory) {
                
                $importFile = $importDirectory . implode("-", [
                        date("Y-m-d-H-i-s"),
                        str_replace(" ", "", $_FILES['file']['name']),
                    ]);
                
                if (is_writable($importDirectory)) {
                    
                    copy($filePath, $importFile);
                    
                    $response['file'] = $importFile;
                    $response['rows_count'] = count(file($importFile)) - 1;
                } else {
                    $response['status'] = 'ERROR';
                    $response['errors'][] = sprintf("Cannot write import file '%s'", $importFile);
                }
            } else {
                $response['status'] = 'ERROR';
                $response['errors'][] = sprintf("Cannot create import directory '%s'", $importDirectory);
            }
        } else {
            $response['status'] = 'ERROR';
            $response['errors'][] = '$_FILES is not set';
        }
        
        echo json_encode($response);
        die();
    }
    
    public function ajaximportcheckAction()
    {
        header('Content-Type: application/json');
        $response = [
            'status' => 'OK',
            'errors' => [],
        ];
        
        $csvHelper = Mage::helper('thespaceimportexport/Csv');
        $productParserHelper = Mage::helper('thespaceimportexport/ProductParser');
        
        $filePath = $_POST['file'];
        
        $index = 0;
        $rowIndex = 1;
        
        $missingHeaderRows = $productParserHelper->getMissingHeadersInRows($csvHelper->getRows($filePath));
        
        foreach ($csvHelper->getRows($filePath) as $row) {
            
            $missingHeaders = $missingHeaderRows[$index];
            
            if (count($missingHeaders)) {
                $error = [
                    'row'     => $rowIndex,
                    'columns' => implode(", ", $missingHeaders),
                ];
                
                $response['errors'][] = $error;
            }
            
            $index++;
            $rowIndex++;
        }
        
        if (count($response['errors'])) {
            $response['status'] = 'ERROR';
        }
        
        echo json_encode($response);
        die();
    }
    
    public function ajaximportrunAction()
    {
        header('Content-Type: application/json');
        $response = [
            'status' => 'OK',
            'errors' => [],
        ];
        
        $csvHelper = Mage::helper('thespaceimportexport/Csv');
        $productParserHelper = Mage::helper('thespaceimportexport/ProductParser');
        
        $filePath = $_POST['file'];
        
        $dataItems = $productParserHelper->getDataFromRows($csvHelper->getRows($filePath));
        $dataItems = $productParserHelper->applyParentCells($dataItems);
        $dataItems = $productParserHelper->applyImagesCells($dataItems, [
            'advanced' => 0
        ]);
        
        $confItems = [];
        $simpleItems = [];
        $dataGroups = [];
        
        foreach ($dataItems as $dataItem) {
            if (isset($dataItem['_type']) && $dataItem['_type'] == 'configurable') {
                $confItems[] = $dataItem;
            } else {
                $simpleItems[] = $dataItem;
            }
        }
        
        foreach (array_chunk($simpleItems, 500) as $simpleItemGroup) {
            $dataGroups[] = $simpleItemGroup;
        }
        
        foreach (array_chunk($confItems, 500) as $confItemGroup) {
            $dataGroups[] = $confItemGroup;
        }
        
        $import = Mage::getModel('fastsimpleimport/import');
        
        $index = 0;
        foreach ($dataGroups as $dataGroup) {
            
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