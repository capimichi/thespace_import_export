<?php

class Thespace_ImportExport_CronController extends Mage_Adminhtml_Controller_Action
{
    
    public function indexAction()
    {
        // "Fetch" display
        $this->loadLayout();
        
        // "Inject" into display
        // THe below example will not actualy show anything since the core/template is empty
        $this->_addContent($this->getLayout()->createBlock('core/template')->setTemplate('thespace/import_export/import/cron_import_products.phtml'));
        
        // "Output" display
        $this->renderLayout();
    }
    
    public function ajaximportaddAction()
    {
        header('Content-Type: application/json');
        $response = [
            'status' => 'OK',
            'errors' => [],
        ];
        
        $year = $_GET['day'];
        $month = $_GET['day'];
        $day = $_GET['day'];
        $hour = $_GET['hour'];
        $minute = $_GET['minute'];
        
        if (isset($_FILES['file'])) {
            $filePath = $_FILES['file']['tmp_name'];
            
            $importDirectory = implode(DIRECTORY_SEPARATOR, [
                    \Mage::getBaseDir('media'),
                    "thespace-import-export",
                    "cron",
                    "todo",
                ]) . DIRECTORY_SEPARATOR;
            
            $canCreateImportDirectory = true;
            if (!file_exists($importDirectory)) {
                $canCreateImportDirectory = mkdir($importDirectory, 0777, true);
            }
            
            if ($canCreateImportDirectory) {
                
                $importFile = $importDirectory . implode("-", [
                        sprintf('%s-%s-%s-%s-%s', $year, $month, $day, $hour, $minute),
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
    
    public function ajaximportlistAction()
    {
        header('Content-Type: application/json');
        $response = [
            'status' => 'OK',
            'errors' => [],
        ];
        
        $importDirectory = implode(DIRECTORY_SEPARATOR, [
                \Mage::getBaseDir('media'),
                "thespace-import-export",
                "cron",
                "todo",
                "",
            ]) . DIRECTORY_SEPARATOR;
        
        $files = [];
        if (file_exists($importDirectory)) {
            $todoFiles = array_diff(scandir($importDirectory), ['..', '.']);
            
            foreach ($todoFiles as $todoFile) {
                $files[] = $importDirectory . $todoFile;
            }
        }
        
        $response['files'] = $files;
        
        echo json_encode($response);
        die();
    }
    
    public function ajaximportremoveAction()
    {
        header('Content-Type: application/json');
        $response = [
            'status' => 'OK',
            'errors' => [],
        ];
        
        $importDirectory = implode(DIRECTORY_SEPARATOR, [
                \Mage::getBaseDir('media'),
                "thespace-import-export",
                "cron",
                "todo",
                "",
            ]) . DIRECTORY_SEPARATOR;
        
        if (isset($_POST['file'])) {
            $file = $_POST['file'];
            $file = $importDirectory . basename($file);
            if (file_exists($file)) {
                unlink($file);
            }
        }
        echo json_encode($response);
        die();
    }
    
}