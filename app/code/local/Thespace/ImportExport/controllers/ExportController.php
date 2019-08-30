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
        
        $file = isset($_POST['file']) ? $_POST['file'] : null;
        $page = isset($_POST['page']) ? $_POST['page'] : 1;
        $stepRows = isset($_POST['step_rows']) ? $_POST['step_rows'] : 50;
        
        $collection = Mage::getModel('catalog/product')
            ->getCollection()
            ->addAttributeToSelect('*')
            ->setPageSize($stepRows)
            ->setCurPage($page);
//            ->load();
        
        $response['pages'] = ceil($collection->getSize() / $stepRows);
        
        $f = fopen($file, 'a');
        if ($page == 1) {
            fputcsv($f, [
                'sku',
            ]);
        }
        
        foreach ($collection as $product) {
            fputcsv($f, [
                $product->getSku(),
            ]);
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
    
    public function downloadAction()
    {
        header('Content-Type: application/json');
//        $manufacturer = isset($_POST['manufacturer']) ? $_POST['manufacturer'] : null;
        $file = isset($_POST['file']) ? $_POST['file'] : null;
        
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($file) . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($file));
        readfile($file);
        exit();
    }
}