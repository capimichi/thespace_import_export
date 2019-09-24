<?php

class Thespace_ImportExport_ConfigurationController extends Mage_Adminhtml_Controller_Action
{
    
    public function indexAction()
    {
        // "Fetch" display
        $this->loadLayout();
        
        // "Inject" into display
        // THe below example will not actualy show anything since the core/template is empty
        $this->_addContent($this->getLayout()->createBlock('core/template')->setTemplate('thespace/import_export/configuration.phtml'));
        
        // "Output" display
        $this->renderLayout();
    }
    
    public function updateAction()
    {
        header('Content-Type: application/json');
        $response = [
            'status' => 'OK',
            'errors' => [],
        ];
        
        $importHelper = Mage::helper('thespaceimportexport/Import');
        $csvHelper = Mage::helper('thespaceimportexport/Csv');
        $productParserHelper = Mage::helper('thespaceimportexport/ProductParser');
        
        
        echo json_encode($response);
        die();
    }
    
}