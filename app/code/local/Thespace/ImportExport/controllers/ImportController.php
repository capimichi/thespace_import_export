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
        $imageReplace = !empty($_POST['image_replace']);
        
        $dataItems = $productParserHelper->getDataFromRows($csvHelper->getRows($filePath));
        $dataItems = $productParserHelper->applyParentCells($dataItems);
        $dataItems = $productParserHelper->applyImagesCells($dataItems, [
            'advanced' => 0,
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
    
    public function ajaximportclearimagesAction()
    {
        header('Content-Type: application/json');
        $response = [
            'status' => 'OK',
            'errors' => [],
        ];
        
        $csvHelper = Mage::helper('thespaceimportexport/Csv');
        $productParserHelper = Mage::helper('thespaceimportexport/ProductParser');
        
        $filePath = $_POST['file'];
        $imageReplace = !empty($_POST['image_replace']);
        
        $dataItems = $productParserHelper->getDataFromRows($csvHelper->getRows($filePath));
        
        if ($imageReplace) {
            // Eliminazione immagini
            
            $replaceSkus = [];
            foreach ($dataItems as $dataItem) {
                if (isset($dataItem['sku'])) {
                    $replaceSkus[] = $dataItem['sku'];
                }
            }
            $replaceSkus = array_unique($replaceSkus);
            $products = Mage::getModel('catalog/product')
                ->getCollection()
                ->addAttributeToFilter('sku', [
                    'in' => $replaceSkus,
                ])
                ->addAttributeToSelect('id');
            
            foreach ($products as $product) {
                $mediaGallery = Mage::getModel('catalog/product_attribute_media_api')->items($product->getId());
                foreach ($mediaGallery as $mediaImage) {
                    $mediaDir = Mage::getConfig()->getOptions()->getMediaDir();
                    $mediaCatalogDir = $mediaDir . DS . 'catalog' . DS . 'product';
                    $dirImagePath = str_replace("/", DS, $mediaImage['file']);
                    $io = new Varien_Io_File();
                    $io->rm($mediaCatalogDir . $dirImagePath);
                    $remove = Mage::getModel('catalog/product_attribute_media_api')->remove($product->getId(), $mediaImage['file']);
                }
            }
        }
        
        echo json_encode($response);
        die();
    }
    
}