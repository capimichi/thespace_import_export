<?php

class Thespace_ImportExport_ExportController extends Mage_Adminhtml_Controller_Action
{
    
    public function indexAction()
    {
        // "Fetch" display
        $this->loadLayout();
        
        // "Inject" into display
        // THe below example will not actualy show anything since the core/template is empty
        $this->_addContent($this->getLayout()->createBlock('core/template')->setTemplate('thespace/import_export/export/export_products.phtml'));
        
        // echo "Hello developer...";
        
        // "Output" display
        $this->renderLayout();
    }
    
    public function exportAction()
    {
        header('Content-Type: application/json');
        
        $response = [
            'status' => 'OK',
            'errors' => [],
        ];
        
        $csvHelper = Mage::helper('thespaceimportexport/Csv');
        $productParserHelper = Mage::helper('thespaceimportexport/ProductParser');
        
        $file = isset($_POST['file']) ? $_POST['file'] : null;
        $storeView = isset($_POST['store_view']) ? $_POST['store_view'] : null;
        if ($storeView) {
            $storeView = Mage::getModel('core/store')->load($storeView); //a store object
        }
        $page = isset($_POST['page']) ? $_POST['page'] : 1;
        $stepRows = isset($_POST['step_rows']) ? $_POST['step_rows'] : 50;
        
        $collection = Mage::getModel('catalog/product')
            ->getCollection();
        
        if ($storeView) {
            $collection
                ->addStoreFilter($storeView->getId())
                ->setStoreId($storeView->getId());
        }
        
        $collection
            ->addAttributeToSelect('*')
            ->setPageSize($stepRows)
            ->setCurPage($page);
//            ->load();
        
        $response['pages'] = ceil($collection->getSize() / $stepRows);
        
        $f = fopen($file, 'a');
        if ($page == 1) {
            
            $productIndex = 0;
            foreach ($collection as $product) {
                if (!$productIndex) {
                    $row = $productParserHelper->getRowFromProduct($product, $storeView);
                    fputcsv($f, array_keys($row));
                }
                $productIndex++;
            }
        }
        fclose($f);
        
        // Prima ciclo i configurabili così se hanno figli
        // già li cavo di torno
        
        $f = fopen($file, 'a');
        foreach ($collection as $product) {
            if ($product->getTypeId() == "configurable") {
                $row = $productParserHelper->getRowFromProduct($product, $storeView);
                fputcsv($f, $row);
                
                $childIds = Mage::getModel('catalog/product_type_configurable')
                    ->getChildrenIds($product->getId());
                foreach ($childIds as $childId) {
                    $child = Mage::getModel('catalog/product')->load($childId);
                    $row = $productParserHelper->getRowFromProduct($child, $storeView);
                    fputcsv($f, $row);
                }
            }
        }
        fclose($f);
        
        // Poi raccolgo gli sku già aggiunti
        
        $alreadyExistingSkus = [];
        foreach ($csvHelper->getRows($file) as $existingRow) {
            $alreadyExistingSkus[] = $existingRow['sku'];
        }
        $alreadyExistingSkus = array_unique($alreadyExistingSkus);
        
        // Poi ciclo i semplici, controllando che non
        // siano già stati inseriti perchè erano dei figli
        
        $f = fopen($file, 'a');
        foreach ($collection as $product) {
            if ($product->getTypeId() == "simple") {
                if (!in_array($product->getSku(), $alreadyExistingSkus)) {
                    $row = $productParserHelper->getRowFromProduct($product, $storeView);
                    fputcsv($f, $row);
                }
            }
        }
        fclose($f);
        
        echo json_encode($response);
        exit();
    }
    
    public function prepareAction()
    {
        header('Content-Type: application/json');
        $response = [
            'status' => 'OK',
            'errors' => [],
        ];
        
        $now = new DateTime();
        
        $exportDirectory = implode(DIRECTORY_SEPARATOR, [
                \Mage::getBaseDir('media'),
                "thespace-import-export",
                "csv",
                $now->format('Y'),
                $now->format('m'),
                $now->format('d'),
            ]) . DIRECTORY_SEPARATOR;
        
        $canCreateExportDirectory = true;
        if (!file_exists($exportDirectory)) {
            $canCreateExportDirectory = mkdir($exportDirectory, 0777, true);
        }
        
        if ($canCreateExportDirectory) {
            
            $exportFile = $exportDirectory . implode("-", [
                    $now->format("Y-m-d-H-i-s"),
                    sprintf("export-product.csv"),
                ]);
            
            if (is_writable($exportDirectory)) {
                
                touch($exportFile);
                
                $response['file'] = $exportFile;
            } else {
                $response['status'] = 'ERROR';
                $response['errors'][] = sprintf("Cannot write export file '%s'", $exportFile);
            }
        } else {
            $response['status'] = 'ERROR';
            $response['errors'][] = sprintf("Cannot create export directory '%s'", $exportDirectory);
        }
        
        echo json_encode($response);
        die();
    }
    
    public function downAction()
    {
//        $manufacturer = isset($_POST['manufacturer']) ? $_POST['manufacturer'] : null;
        $file = isset($_POST['file']) ? $_POST['file'] : null;
        
        if ($file) {
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . basename($file) . '"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($file));
            readfile($file);
        }
        exit();
    }
}