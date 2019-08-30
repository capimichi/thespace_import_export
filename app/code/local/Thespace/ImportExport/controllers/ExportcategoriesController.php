<?php

class Thespace_ImportExport_ExportcategoriesController extends Mage_Adminhtml_Controller_Action
{
    
    public function indexAction()
    {
        // "Fetch" display
        $this->loadLayout();
        
        // "Inject" into display
        // THe below example will not actualy show anything since the core/template is empty
        $this->_addContent($this->getLayout()->createBlock('core/template')->setTemplate('thespace/import_export/export/export_categories.phtml'));
        
        // echo "Hello developer...";
        
        // "Output" display
        $this->renderLayout();
    }
    
    public function exportAction()
    {
        $categoryParserHelper = Mage::helper('thespaceimportexport/CategoryParser');
        
        $now = new DateTime();
        
        $importDirectory = implode(DIRECTORY_SEPARATOR, [
                \Mage::getBaseDir('media'),
                "thespace-import-export",
                "csv",
                $now->format('Y'),
                $now->format('m'),
                $now->format('d'),
            ]) . DIRECTORY_SEPARATOR;
        
        $canCreateImportDirectory = true;
        if (!file_exists($importDirectory)) {
            $canCreateImportDirectory = mkdir($importDirectory, 0777, true);
        }
        
        if ($canCreateImportDirectory) {
            
            $filePath = $importDirectory . implode("-", [
                    date("Y-m-d-H-i-s"),
                    'categories.csv',
                ]);
            
            if (is_writable($importDirectory)) {
                
                $f = fopen($filePath, "w");
                
                fputcsv($f, $categoryParserHelper->getRowHeaders());
                
                $categories = Mage::getModel('catalog/category')->getCollection()
                    ->addAttributeToSelect('*');
                
                $categoryNames = $categoryParserHelper->categoryNames();
                
                foreach ($categories as $category) {
                    $row = $categoryParserHelper->categoryToRow($category, $categoryNames);
                    fputcsv($f, $row);
                }
                
                fclose($f);
                
            } else {
                die(sprintf("Cannot write import file '%s'", $filePath));
            }
        } else {
            die(sprintf("Cannot create import directory '%s'", $importDirectory));
        }
        
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($filePath) . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($filePath));
        readfile($filePath);
        exit();
    }
    
}