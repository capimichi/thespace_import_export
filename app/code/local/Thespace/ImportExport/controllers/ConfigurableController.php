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
        
        $skuHelper = Mage::helper('thespaceimportexport/Sku');
        $importHelper = Mage::helper('thespaceimportexport/Import');
        $csvHelper = Mage::helper('thespaceimportexport/Csv');
        $productParserHelper = Mage::helper('thespaceimportexport/ProductParser');
        
        $existingSkus = $skuHelper->getExistingSkus();
        
        $filePath = $_POST['file'];
        
        $dataItems = [];
        
        foreach ($csvHelper->getRows($filePath) as $row) {
            
            $dataItems = array_merge($dataItems, $productParserHelper->getConfigurableItemsFromRow($row));
        }
        
        $dataItems = $productParserHelper->applyParentCells($dataItems);
        
        $mergedItems = [];
        foreach ($dataItems as $dataItem) {
            $sku = $dataItem['sku'];
            if (empty($mergedItems[$sku])) {
                $mergedItems[$sku] = $dataItem;
            } else {
                foreach ($dataItem as $key => $value) {
                    if (is_array($value)) {
                        $mergedItems[$sku][$key] = array_merge($mergedItems[$sku][$key], array_values($value));
//                        $mergedItems[$sku][$key] = array_unique($mergedItems[$sku][$key]);
                        $mergedItems[$sku][$key] = array_values($mergedItems[$sku][$key]);
                    } else {
                        $mergedItems[$sku][$key] = $value;
                    }
                }
            }
        }
        $dataItems = array_values($mergedItems);
        
        $dataGroups = $importHelper->groupImportItems($dataItems, 500);
        
        $import = Mage::getModel('fastsimpleimport/import');
        
        $index = 0;
        foreach ($dataGroups as $dataGroup) {
            
            $clearImagesGroup = [];
            foreach ($dataGroup as $dataItem) {
                if ($dataItems['type'] == 'simple') {
                    $clearImagesGroup[] = $dataItem;
                }
            }
            
            $productParserHelper->clearImages($clearImagesGroup, $existingSkus);
            $dataGroup = $productParserHelper->parseImages($dataGroup);
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