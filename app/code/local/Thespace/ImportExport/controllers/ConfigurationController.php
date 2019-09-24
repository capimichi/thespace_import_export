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
        
        $configurationHelper = Mage::helper('thespaceimportexport/Configuration');
        
        foreach (Thespace_ImportExport_Helper_Configuration::DEFAULT_CONFIGURATION_OPTIONS as $default) {
            $configurationHelper->set($default, isset($_GET[$default]) ? $_GET[$default] : '');
        }
        
        
        $tags = [
            'CONFIG',
            'LAYOUT_GENERAL_CACHE_TAG',
//            'BLOCK_HTML',
//            'TRANSLATE',
//            'COLLECTION_DATA',
//            'EAV',
//            'CONFIG_API',
//            'CONFIG_API2',
        ];
        
        foreach ($tags as $tag) {
            Mage::app()->getCacheInstance()->cleanType($tag);
        }
        
        
        echo json_encode($response);
        die();
    }
    
}