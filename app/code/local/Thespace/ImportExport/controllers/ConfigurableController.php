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
        
        $csvHelper = Mage::helper('thespaceimportexport/Csv');
        $productParserHelper = Mage::helper('thespaceimportexport/ProductParser');
        
        $filePath = $_POST['file'];
        
        $dataItems = [];
        
        foreach ($csvHelper->getRows($filePath) as $row) {
            
            $dataItems = $productParserHelper->getConfigurableItemsFromRow($row);
        }
    
        $dataItems = $productParserHelper->applyParentCells($dataItems);
        
        $dataGroups = array_chunk($dataItems, 500);
        
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