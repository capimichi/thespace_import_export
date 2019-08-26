<?php

class Thespace_ImportExport_ImagesController extends Mage_Adminhtml_Controller_Action
{
    
    public function indexAction()
    {
        // "Fetch" display
        $this->loadLayout();
        
        // "Inject" into display
        // THe below example will not actualy show anything since the core/template is empty
        $this->_addContent($this->getLayout()->createBlock('core/template')->setTemplate('thespace/import_export/import/upload_images.phtml'));
        
        // "Output" display
        $this->renderLayout();
    }
    
    public function uploadimageAction()
    {
//        header('Content-Type: application/json');
        $response = [
            'status' => 'OK',
            'errors' => [],
        ];
        
        if (isset($_FILES['file'])) {
            
            $file = $_FILES['file'];
            $uploadDir = Mage::getBaseDir('media') . DIRECTORY_SEPARATOR . 'import' . DIRECTORY_SEPARATOR;
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            move_uploaded_file($file['tmp_name'], $uploadDir . $file['name']);
        } else {
            $response = array_merge([
                'status' => 'ERROR',
                'errors' => [
                    'Nessun file selezionato',
                ],
            ]);
        }
        
        echo json_encode($response);
        die();
    }
    
    
}