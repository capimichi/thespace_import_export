<?php

class Thespace_ImportExport_CronImportController extends Mage_Adminhtml_Controller_Action
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
    
    public function ajaximportsplitAction()
    {
        header('Content-Type: application/json');
        $response = [
            'status' => 'OK',
            'errors' => [],
        ];
        
        $filePath = $_POST['file'];
        $stepRows = $_POST['step_rows'];
        
        $configurationHelper = Mage::helper('thespaceimportexport/Configuration');
        $skuHelper = Mage::helper('thespaceimportexport/Sku');
        $importHelper = Mage::helper('thespaceimportexport/Import');
        $csvHelper = Mage::helper('thespaceimportexport/Csv');
        $productParserHelper = Mage::helper('thespaceimportexport/ProductParser');
        
        $existingSkus = $skuHelper->getExistingSkus();
        $attributes = Mage::getResourceModel('catalog/product_attribute_collection')
            ->getItems();
        
        $defaultRow = [
            '_attribute_set'    => $configurationHelper->get(Thespace_ImportExport_Helper_Configuration::OPTION_DEFAULT_ATTRIBUTE_SET),
            '_product_websites' => $configurationHelper->get(Thespace_ImportExport_Helper_Configuration::OPTION_DEFAULT_PRODUCT_WEBSITES),
            'tax_class_id'      => $configurationHelper->get(Thespace_ImportExport_Helper_Configuration::OPTION_DEFAULT_TAX_CLASS_ID),
        ];
        
        $now = new DateTime('now');
        
        $importDirectory = implode(DIRECTORY_SEPARATOR, [
                \Mage::getBaseDir('media'),
                "thespace-import-export",
                "json",
                $now->format('Y'),
                $now->format('m'),
                $now->format('d'),
                $now->format('Y-m-d-h-i-s'),
            ]) . DIRECTORY_SEPARATOR;
        
        $canCreateImportDirectory = true;
        if (!file_exists($importDirectory)) {
            $canCreateImportDirectory = mkdir($importDirectory, 0777, true);
        }
        
        if ($canCreateImportDirectory) {
            
            if (is_writable($importDirectory)) {
                
                $rows = [];
                foreach ($csvHelper->getRows($filePath) as $row) {
                    $rowData = $productParserHelper->getDataFromRow($row, $attributes);
                    
                    if (!in_array($rowData['sku'], $existingSkus)) {
                        $row = array_merge($defaultRow, $row);
                    }
                    
                    $rows[] = $row;
                }
                
                $dataItems = $productParserHelper->getDataFromRows($rows);
                $dataItems = $productParserHelper->applyParentCells($dataItems);
//                $dataItems = $productParserHelper->applyImagesCells($dataItems, [
//                    'advanced' => 0,
//                ]);
                
                $dataGroups = $importHelper->groupImportItems($dataItems, $stepRows);
                
                $groupFiles = [];
                
                $groupIndex = 1;
                foreach ($dataGroups as $dataGroup) {
                    $importFile = $importDirectory . implode("-", [
                            sprintf("%08d.json", $groupIndex),
                        ]);
                    $groupIndex++;
                    
                    file_put_contents($importFile, json_encode($dataGroup, JSON_UNESCAPED_UNICODE));
                    
                    $groupFiles[] = $importFile;
                }
                
                $response['files'] = $groupFiles;
            } else {
                $response['status'] = 'ERROR';
                $response['errors'][] = sprintf("Cannot write import files in '%s'", $importDirectory);
            }
        } else {
            $response['status'] = 'ERROR';
            $response['errors'][] = sprintf("Cannot create import directory '%s'", $importDirectory);
        }
        
        echo json_encode($response);
        die();
    }
    
    public function ajaximportrunAction()
    {
        header('Content-Type: application/json');
        header("Cache-Control: no-cache, must-revalidate"); //HTTP 1.1
        
        $response = [
            'status' => 'OK',
            'errors' => [],
        ];
        
        $importHelper = Mage::helper('thespaceimportexport/Import');
        $csvHelper = Mage::helper('thespaceimportexport/Csv');
        $skuHelper = Mage::helper('thespaceimportexport/Sku');
        $productParserHelper = Mage::helper('thespaceimportexport/ProductParser');
        
        $groupIndex = $_POST['group'];
        $response['group'] = $groupIndex;
        $filePath = $_POST['file'];
        $response['file'] = $filePath;
        $dataGroup = json_decode(file_get_contents($filePath), true);
        $imageReplace = !empty($_POST['image_replace']);

//        $dataItems = $productParserHelper->getDataFromRows($csvHelper->getRows($filePath));
//        $dataItems = $productParserHelper->applyParentCells($dataItems);
//        $dataItems = $productParserHelper->applyImagesCells($dataItems, [
//            'advanced' => 0,
//        ]);
//        $dataGroups = $importHelper->groupImportItems($dataItems, 500);
        
        $import = Mage::getModel('fastsimpleimport/import');
        
        try {
            $dataGroup = $productParserHelper->parseImages($dataGroup);
            
            if ($imageReplace) {
                $existingSkus = $skuHelper->getExistingSkus();
                $productParserHelper->clearImages($dataGroup, $existingSkus);
            }
            
            $dataGroup = $productParserHelper->parseArrayCells($dataGroup);
            
            $import->processProductImport($dataGroup);
        } catch (Exception $e) {
            $response['status'] = 'ERROR';
            $error = [
                'errors' => $import->getErrorMessages(),
            ];
            $response['errors'][] = $error;
        }

//        $index = 0;
//        foreach ($dataGroups as $dataGroup) {
//
//            $dataGroup = $productParserHelper->parseArrayCells($dataGroup);
//
//            try {
//                $import->processProductImport($dataGroup);
//            } catch (Exception $e) {
//                $error = [
//                    'group'  => $index,
//                    'errors' => $import->getErrorMessages(),
//                ];
//                $response['errors'][] = $error;
//            }
//            $index++;
//        }
        
        
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
    
    public function showimportjsonAction()
    {
        $file = $_GET['file'];
        
        header('Content-Type: application/json');
        header('Content-disposition: attachment; filename=' . basename($file));
        
        
        if (
            file_exists($file)
            && preg_match("/\.json$/is", $file)
        ) {
            echo file_get_contents($file);
        }
        
        die();
    }
    
}