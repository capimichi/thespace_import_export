<?php

class Thespace_ImportExport_Helper_ProductParser extends Mage_Core_Helper_Abstract
{
    const REQUIRED_HEADERS = [
        'sku',
    ];
    
    const REQUIRED_NEW_PRODUCT_HEADERS = [
        '_type',
        '_attribute_set',
        '_product_websites',
    ];
    
    const NOT_REQUIRED_HEADERS = [
        'created_at',
        'links_purchased_separately',
        'links_title',
        'price_type',
        'price_view',
        'samples_title',
        'shipment_type',
        'sku_type',
        'updated_at',
        'weight_type',
    ];
    
    const CONFIGURABLE_HEADERS_PARENT = [
        'parent',
        'genitore',
    ];
    
    const CONFIGURABLE_HEADERS_VARIATION_ATTRIBUTES = [
        'variation_attributes',
        'attributi_variazioni',
    ];
    
    const HEADER_IMAGE_ASSOCIATIONS = [
        '_media_image'        => '_media_image',
        '_media_attribute_id' => '_media_attribute_id',
        '_media_is_disabled'  => '_media_is_disabled',
        '_media_position'     => '_media_position',
        '_media_lable'        => '_media_lable',
        'image'               => [
            'image',
            'images',
        ],
        'small_image'         => 'small_image',
        'thumbnail'           => 'thumbnail',
    ];
    
    const HEADER_HELPER_ASSOCIATIONS = [
        'parent'               => self::CONFIGURABLE_HEADERS_PARENT,
        'variation_attributes' => self::CONFIGURABLE_HEADERS_VARIATION_ATTRIBUTES,
    ];
    
    const HEADER_ASSOCIATIONS = [
        'name'                    => [
            'name',
            'nome',
        ],
        'sku'                     => [
            'sku',
            'riferimento',
        ],
        '_type'                   => [
            'tipo',
            'tipologia',
            'type',
            '_type',
        ],
        '_attribute_set'          => [
            'set',
            'set_attributi',
            'attributi_set',
            'attribute_set',
            'set_attribute',
            '_attribute_set',
        ],
        '_product_websites'       => [
            'website',
            '_product_websites',
        ],
        'description'             => [
            'description',
            'descrizione',
        ],
        'short_description'       => [
            'short_description',
            'descrizione_breve',
        ],
        'price'                   => [
            'price',
            'prezzo',
        ],
        'status'                  => [
            'status',
            'stato',
            'abilitato',
            'enabled',
        ],
        'qty'                     => [
            'qty',
            'quantita',
            'quantità',
        ],
        'is_in_stock'             => [
            'is_in_stock',
            'disponibile',
            'disponibilità',
        ],
        'manage_stock'            => [
            'manage_stock',
            'gestisci_scorte',
            'gestisci_quantita',
        ],
        'use_config_manage_stock' => 'use_config_manage_stock',
        'tax_class_id'            => [
            'tax_class_id',
            'classe_tassa',
            'tax_class',
        ],
        'visibility'              => [
            'visibility',
            'visibilita',
        ],
        '{attribute_code}'        => [
            'att_{attribute_code}',
        ],
    ];

//    const TYPE_KEY                    = "tipo";
//    const TITLE_KEY                   = "titolo";
//    const SKU_KEY                     = "riferimento";
//    const NEW_SKU_KEY                 = "nuovo riferimento";
//    const STATUS_KEY                  = "attivo";
//    const WEIGHT_KEY                  = "peso";
//    const HEIGHT_KEY                  = "altezza";
//    const TAX_CLASS_KEY               = "tassa";
//    const VISIBILITY_KEY              = "visibilita";
//    const DESCRIPTION_KEY             = "descrizione";
//    const SHORT_DESCRIPTION_KEY       = "descrizione_breve";
//    const PRICE_KEY                   = "prezzo";
//    const SPECIAL_PRICE_KEY           = "prezzo_speciale";
//    const CATEGORY_KEY                = "categoria";
//    const ATTRIBUTE_SET_KEY           = "set_attributi";
//    const META_TITLE_KEY              = "meta_titolo";
//    const META_DESCRIPTION_KEY        = "meta_descrizione";
//    const PARENT_SKU_KEY              = "genitore";
//    const CONFIGURABLE_ATTRIBUTES_KEY = "attributi_variazioni";
//    const TRANSLATE_TITLE_KEY         = "titolo_{langkey}";
//    const TRANSLATE_DESCRIPTION_KEY   = "descrizione_{langkey}";
    
    
    /**
     * @author Michele Capicchioni <capimichi@gmail.com>
     *
     * @param $row
     * @param $attributes
     *
     * @return array
     */
    public function getDataFromRow($row, $attributes = null)
    {
        if (is_null($attributes)) {
            $attributes = Mage::getResourceModel('catalog/product_attribute_collection')
                ->getItems();
        }
        
        $data = [];
        
        $associations = array_merge(
            self::HEADER_ASSOCIATIONS,
            self::HEADER_IMAGE_ASSOCIATIONS,
            self::HEADER_HELPER_ASSOCIATIONS
        );
        
        foreach ($associations as $magentoKey => $headerNames) {
            if (!is_array($headerNames)) {
                $headerNames = [
                    $headerNames,
                ];
            }
            
            foreach ($headerNames as $headerName) {
                if (isset($row[$headerName])) {
                    $data[$magentoKey] = $row[$headerName];
                }
                
                if (preg_match("/{attribute_code}/is", $magentoKey)) {
                    
                    foreach ($attributes as $attribute) {
                        $attributeCode = $attribute->getData('attribute_code');
                        if (!empty($attributeCode)) {
                            $parsedMagentoKey = str_replace("{attribute_code}", $attributeCode, $magentoKey);
                            $parsedHeaderName = str_replace("{attribute_code}", $attributeCode, $headerName);
                            
                            if (isset($row[$parsedHeaderName])) {
                                $data[$parsedMagentoKey] = $row[$parsedHeaderName];
                            }
                        }
                    }
                }
                
            }
        }
        
        return $data;
    }
    
    /**
     * @author Michele Capicchioni <capimichi@gmail.com>
     *
     * @param $rows
     *
     * @return array
     */
    public function getDataFromRows($rows)
    {
        $attributes = Mage::getResourceModel('catalog/product_attribute_collection')
            ->getItems();
        
        $datas = [];
        
        foreach ($rows as $row) {
            $data = $this->getDataFromRow($row, $attributes);
            $datas[] = $data;
        }
        
        return $datas;
    }
    
    /**
     * @author Michele Capicchioni <capimichi@gmail.com>
     *
     * @param $dataItems
     *
     * @return array
     */
    public function applyParentCells($dataItems)
    {
        $parentChildren = [];
        
        foreach ($dataItems as $dataItem) {
            
            $parentSku = !empty($dataItem['parent']) ? $dataItem['parent'] : null;
            
            if ($parentSku) {
                if (!isset($parentChildren[$parentSku])) {
                    $parentChildren[$parentSku] = [];
                }
                
                $parentChildren[$parentSku][] = $dataItem;
            }
        }
        
        foreach ($parentChildren as $parentSku => $children) {
            
            $parentHasRow = false;
            
            foreach ($dataItems as $dataItem) {
                $sku = $dataItem['sku'];
                
                if ($sku == $parentSku) {
                    $parentHasRow = true;
                }
            }
            
            if (!$parentHasRow) {
                $dataItems[] = [
                    'sku' => $parentSku,
                ];
            }
        }
        
        for ($i = 0; $i < count($dataItems); $i++) {
            $dataItem = $dataItems[$i];
            $sku = $dataItem['sku'];
            
            $variationAttributeCodes = explode('|', $dataItem['variation_attributes']);
            
            if (isset($parentChildren[$sku])) {
                $dataItem['_super_products_sku'] = [];
                $dataItem['_super_attribute_code'] = [];
                $dataItem['_super_attribute_option'] = [];
                
                foreach ($parentChildren[$sku] as $child) {
                    $childSku = $child['sku'];
                    
                    foreach ($variationAttributeCodes as $variationAttributeCode) {
                        
                        $dataItem['_super_products_sku'][] = $childSku;
                        $dataItem['_super_attribute_code'][] = $variationAttributeCode;
                        $dataItem['_super_attribute_option'][] = isset($child[$variationAttributeCode]) ? $child[$variationAttributeCode] : null;
                    }
                }
            }
            
            $dataItems[$i] = $dataItem;
        }
        
        return $dataItems;
    }
    
    /**
     * @author Michele Capicchioni <capimichi@gmail.com>
     *
     * @param       $dataItems
     * @param array $options
     *
     * @return mixed
     */
    public function applyImagesCells($dataItems, $options = [])
    {
        $options = array_merge([
            'media_attribute_id' => Mage::getSingleton('catalog/product')->getResource()->getAttribute('media_gallery')->getAttributeId(),
            'advanced'           => 0,
        ], $options);
        
        $imageHelper = Mage::helper('thespaceimportexport/Image');
        
        for ($i = 0; $i < count($dataItems); $i++) {
            $dataItem = $dataItems[$i];
            
            $hasImage = false;
            foreach (self::HEADER_IMAGE_ASSOCIATIONS as $key => $value) {
                if (!empty($dataItem[$key])) {
                    $hasImage = true;
                }
            }
            
            if ($hasImage) {
                
                if ($options['advanced']) {
                    
                    $hasImage = false;
                    $maxCount = 0;
                    foreach ($dataItem as $key => $value) {
                        if (
                            in_array($key, [
                                '_media_image', '_media_attribute_id', '_media_is_disabled', '_media_position', '_media_lable', 'image', 'small_image', 'thumbnail',
                            ])
                            && !empty($value)
//                            && preg_match("/|/is", $value)
                        ) {
                            $dataItem[$key] = explode('|', $value);
                            $maxCount = max($maxCount, count($dataItem[$key]));
                            $hasImage = true;
                        }
                    }
                    if ($hasImage) {
                        
                        if (isset($dataItem['_media_image'])) {
                            $dataItem['_media_image'] = array_map(function ($imagePath) use ($imageHelper) {
                                $mediaPath = $imageHelper->storeImage($imagePath);
                                if ($mediaPath) {
                                    return $mediaPath;
                                } else {
                                    return $imagePath;
                                }
                            }, $dataItem['_media_image']);
                        }
                        
                        $attributeIds = [];
                        for ($i = 0; $i < $maxCount; $i++) {
                            $attributeIds[] = $options['media_attribute_id'];
                        }
                        $dataItem['_media_attribute_id'] = $attributeIds;
                    }
                    
                } else {
                    
                    $imagePaths = explode("|", $dataItem['image']);
                    
                    $parsedSimpleData = [
                        '_media_image'        => [],
                        '_media_attribute_id' => [],
                        '_media_is_disabled'  => [],
                        '_media_position'     => [],
                        '_media_lable'        => [],
                        'image'               => '',
                        'small_image'         => '',
                        'thumbnail'           => '',
                    ];
                    
                    $index = 0;
                    foreach ($imagePaths as $imagePath) {
                        
                        $mediaPath = $imageHelper->storeImage($imagePath);
                        if ($mediaPath) {
                            $imageName = basename($mediaPath);
                            
                            $parsedSimpleData['_media_image'][] = $imageName;
                            $parsedSimpleData['_media_attribute_id'][] = $options['media_attribute_id'];
                            $parsedSimpleData['_media_is_disabled'][] = 0;
                            $parsedSimpleData['_media_position'][] = $index;
                            $parsedSimpleData['_media_lable'][] = $imageName;
                            $parsedSimpleData['image'] = $imageName;
                            $parsedSimpleData['small_image'] = $imageName;
                            $parsedSimpleData['thumbnail'] = $imageName;
                            
                            $index++;
                        }
                    }
                    
                    $dataItem = array_merge($dataItem, $parsedSimpleData);
                }
            }
            
            $dataItems[$i] = $dataItem;
        }
        
        return $dataItems;
    }
    
    /**
     * @author Michele Capicchioni <capimichi@gmail.com>
     *
     * @param $row
     * @param $attributes
     *
     * @return array
     */
    public function getConfigurableItemsFromRow($row, $attributes = null)
    {
        if (is_null($attributes)) {
            $attributes = Mage::getResourceModel('catalog/product_attribute_collection')
                ->getItems();
        }
        
        $combinationHelper = Mage::helper('thespaceimportexport/Combination');
        
        $items = [];
        
        $row = self::getDataFromRow($row, $attributes);
        
        $rowAttributeCodes = explode("|", $row['variation_attributes']);
        
        $attributeValueGroups = [];
        
        foreach ($rowAttributeCodes as $rowAttributeCode) {
            
            if (isset($row[$rowAttributeCode])) {
                
                $values = explode("|", $row[$rowAttributeCode]);
                
                $attributeValueGroups[] = array_unique($values);
            }
        }
        
        $combinations = $combinationHelper->getCombinations($attributeValueGroups);
        
        $sku = $row['parent'];
        
        $parentProduct = Mage::getModel('catalog/product')->loadByAttribute('sku', $sku);
        
        $name = $parentProduct->getName();
        
        $index = 0;
        foreach ($combinations as $combination) {
            $index++;
            $item = array_merge($row, [
                'sku'   => sprintf("%s-%s", $sku, $index),
                'name'  => sprintf("%s-%s", $name, implode("-", $combination)),
                '_type' => 'simple',
            ]);
            
            foreach ($combination as $key => $value) {
                $item[$rowAttributeCodes[$key]] = $value;
            }
            
            if (empty($item['_attribute_set'])) {
                $item['_attribute_set'] = Mage::getModel('eav/entity_attribute_set')->load($parentProduct->getAttributeSetId())->getAttributeSetName();
            }
            
            if (empty($item['_product_websites'])) {
                $item['_product_websites'] = [];
                
                foreach ($parentProduct->getWebsiteIds() as $websiteId) {
                    $websiteCode = Mage::getModel('core/website')->load($websiteId)->getData("code");
                    $item['_product_websites'][] = $websiteCode;
                }
                
            }
            
            foreach ($attributes as $attribute) {
                $attributeCode = $attribute->getData('attribute_code');
                if (
                    !empty($attributeCode)
                    && intval($attribute->getData('is_required'))
                ) {
                    if (!isset($item[$attributeCode])) {
                        $item[$attributeCode] = $parentProduct->getData($attributeCode);
                    }
                }
            }
            
            if (empty($item['price'])) {
                $item['price'] = $parentProduct->getPrice();
            }
            
            $items[] = $item;
        }
        
        return $items;
    }
    
    /**
     * @author Michele Capicchioni <capimichi@gmail.com>
     *
     * @param $dataItems
     *
     * @return array
     */
    public function parseArrayCells($dataItems)
    {
        $parsedDataItems = [];
        
        foreach ($dataItems as $dataItem) {
            $arrayCells = [];
            
            foreach ($dataItem as $key => $value) {
                if (is_array($value)) {
                    $arrayCells[$key] = $value;
                }
            }
            
            $i = 0;
            do {
                $parsedDataItem = [
                    'sku' => null,
                ];
                
                if (!$i) {
                    $parsedDataItem = array_merge($parsedDataItem, $dataItem);
                }
                
                foreach ($arrayCells as $key => $arrayCell) {
                    $parsedDataItem[$key] = array_shift($arrayCells[$key]);
                }
                
                $parsedDataItems[] = $parsedDataItem;
                
                $hasMoreItems = false;
                foreach ($arrayCells as $arrayCell) {
                    if (count($arrayCell)) {
                        $hasMoreItems = true;
                    }
                }
                
                $i++;
            } while ($hasMoreItems);
        }
        
        return $parsedDataItems;
    }

//    /**
//     * @author Michele Capicchioni <capimichi@gmail.com>
//     *
//     * @param      $row
//     * @param null $attributes
//     *
//     * @return array
//     */
//    public function getMissingHeadersInRow($row, $attributes = null)
//    {
//        if (is_null($attributes)) {
//            $attributes = Mage::getResourceModel('catalog/product_attribute_collection')
//                ->getItems();
//        }
//
//        $data = $this->getDataFromRow($row);
//
//        $missingHeaders = [];
//
//        foreach (self::REQUIRED_HEADERS as $requiredHeader) {
//            if (!isset($data[$requiredHeader])) {
//                $missingHeaders[] = $requiredHeader;
//            }
//        }
//
//        if (isset($data['sku'])) {
//
//            $sku = $data['sku'];
//
//            $product = \Mage::getModel('catalog/product')->loadByAttribute('sku', $sku);
//
//            if (!$product) {
//
//                foreach (self::REQUIRED_NEW_PRODUCT_HEADERS as $requiredHeader) {
//                    if (!isset($data[$requiredHeader])) {
//                        $missingHeaders[] = $requiredHeader;
//                    }
//                }
//
//                foreach ($attributes as $attribute) {
//                    $isRequired = intval($attribute->getData('is_required'));
//                    $attributeCode = $attribute->getData('attribute_code');
////                    $isUserDefined = intval($attribute->getData('is_user_defined'));
//
//                    if ($isRequired && !in_array($attributeCode, self::NOT_REQUIRED_HEADERS)) {
//                        if (!isset($data[$attributeCode])) {
//                            $missingHeaders[] = $attributeCode;
//                        }
//                    }
//                }
//
//            }
//        }
//
//        return $missingHeaders;
//    }
//
//    /**
//     * @author Michele Capicchioni <capimichi@gmail.com>
//     *
//     * @param $rows
//     *
//     * @return array
//     */
//    public function getMissingHeadersInRows($rows)
//    {
//        $missingHeadersRows = [];
//
//        $attributes = Mage::getResourceModel('catalog/product_attribute_collection')
//            ->getItems();
//
//        foreach ($rows as $row) {
//            $missingHeaders = $this->getMissingHeadersInRow($row, $attributes);
//
//            $missingHeadersRows[] = $missingHeaders;
//        }
//
//        return $missingHeadersRows;
//    }
//
//    /**
//     * @param $row
//     *
//     * @return mixed
//     */
//    public function getRowNewSku($row)
//    {
//        return isset($row[self::NEW_SKU_KEY]) ? $row[self::NEW_SKU_KEY] : null;
//    }
//
//    /**
//     * @param $row
//     *
//     * @return mixed
//     */
//    public function getSku($row)
//    {
//        return isset($row[self::SKU_KEY]) ? $row[self::SKU_KEY] : null;
//    }
//
//    /**
//     * @param $row
//     *
//     * @return mixed
//     */
//    public function getRowProductType($row)
//    {
//        return isset($row[self::TYPE_KEY]) ? $row[self::TYPE_KEY] : 'simple';
//    }
//
//    /**
//     * @param $row
//     *
//     * @return mixed
//     */
//    public function getRowProductParentSku($row)
//    {
//        return isset($row[self::PARENT_SKU_KEY]) ? $row[self::PARENT_SKU_KEY] : null;
//    }
//
//    /**
//     * @param $row
//     *
//     * @return mixed
//     */
//    public function getRowProductSku($row)
//    {
//        return isset($row[self::SKU_KEY]) ? $row[self::SKU_KEY] : null;
//    }
//
//    /**
//     * @param $row
//     *
//     * @return array
//     */
//    public function getConfigurableProductUsedAttributeCodes($row)
//    {
//        return isset($row[self::CONFIGURABLE_ATTRIBUTES_KEY]) ? explode("|", $row[self::CONFIGURABLE_ATTRIBUTES_KEY]) : [];
//    }
//
//    /**
//     * @param $products
//     *
//     * @return array
//     */
//    public function getProductsUsedAttributeCodes($products)
//    {
//        $attributeCodes = [];
//        foreach ($products as $product) {
//            if ($product->type_id == 'configurable') {
//                $usedProductAttributes = $product->getTypeInstance()->getUsedProductAttributes($product);
//                foreach ($usedProductAttributes as $attribute) {
//                    $attributeCodes[] = $attribute->getAttributeCode();
//                }
//            }
//        }
//        $attributeCodes = array_unique($attributeCodes);
//
//        return $attributeCodes;
//    }
//
//    /**
//     * @param $row
//     *
//     * @return array
//     * @throws Exception
//     */
//    public function getImportAttributeCodes($row)
//    {
//        $codes = [];
//
//        foreach ($row as $key => $value) {
//
//            if (preg_match("/^att_/is", $key)) {
//
//                $attributeName = preg_replace("/^att_/is", '', $key);
//
//                $attr = Mage::getResourceModel('catalog/eav_attribute')
//                    ->loadByCode('catalog_product', $attributeName);
//
//                if (!$attr->getId()) {
//                    throw new Exception(sprintf("Missing attribute %s", $attributeName));
//                }
//
//                $codes[] = $attributeName;
//            }
//        }
//
//        return $codes;
//    }
//
//    /**
//     * @param $row
//     *
//     * @return mixed
//     *
//     * @throws \Exception
//     */
//    public function changeSku($row)
//    {
//        $product = \Mage::getModel('catalog/product')->loadByAttribute('sku', $this->getRowProductSku($row));
//        if ($product) {
//            $newSku = $this->getRowNewSku($row);
//
//            $productWithnewSku = \Mage::getModel('catalog/product')->loadByAttribute('sku', $newSku);
//
//            if ($productWithnewSku) {
//                throw  new \Exception("Product with same sku already exists");
//            }
//
//            $product->setSku($newSku);
//            return $product;
//        } else {
//            return null;
//        }
//    }
//
//    /**
//     * @param $product
//     * @param $attributeCodes
//     *
//     * @return Mage_Core_Model_Abstract
//     */
//    public function setConfigurableProductUsedAttributes($product, $attributeCodes)
//    {
//        $product = \Mage::getModel('catalog/product')->load($product->getId());
//
//        $product->setCanSaveConfigurableAttributes(true);
//        $product->setCanSaveCustomOptions(true);
//
//        $attributeIds = [];
//
//        foreach ($attributeCodes as $attributeCode) {
//            $attributeIds[] = \Mage::getModel('eav/entity_attribute')->getIdByCode('catalog_product', $attributeCode);
//        }
//
//        $product->getTypeInstance()->setUsedProductAttributeIds($attributeIds);
//        $product->setConfigurableAttributesData($product->getTypeInstance()->getConfigurableAttributesAsArray());
//
//        return $product;
//    }
//
//    public function setConfigurableData($product, $rows, $attributeCodes)
//    {
//        $product = \Mage::getModel('catalog/product')->load($product->getId());
//
//        $configurableProductsData = $product->getConfigurableProductsData();
//
//        foreach ($rows as $row) {
//
//            $variationProduct = \Mage::getModel('catalog/product')->loadByAttribute('sku', $this->getRowProductSku($row));
//            $simpleProductsData = [];
//
//            foreach ($attributeCodes as $attributeCode) {
//
//                $attributeId = \Mage::getModel('eav/entity_attribute')->getIdByCode('catalog_product', $attributeCode);
//
//                $attributeValue = $row['att_' . $attributeCode];
//
//                $simpleProductsData[] = [
//                    'label'         => rand(0, 999999),
//                    'attribute_id'  => intval($attributeId),
//                    'value_index'   => $variationProduct->getResource()->getAttribute($attributeCode)->getSource()->getOptionId($attributeValue),
//                    'is_percent'    => 0,
//                    'pricing_value' => floatval($variationProduct->getPrice()),
//                ];
//            }
//
//            $configurableProductsData[$variationProduct->getId()] = $simpleProductsData;
//
//            //            $configurableAttributesData[0]['values'][] = $simpleProductsData;
//
//            $variations[] = $variationProduct;
//        }
//
//        $product->setConfigurableProductsData($configurableProductsData);
//
//        return $product;
//    }
//
//    public function translateproduct($product, $row)
//    {
//        Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);
//
//        $langCodes = [];
//        $stores = Mage::app()->getStores();
//        foreach ($stores as $store) {
//            $langCodes[] = $store->getCode();
//        }
//
//        $tProduct = \Mage::getModel('catalog/product')->load($product->getId());
//
//        foreach ($langCodes as $langCode) {
//            $titleKey = str_replace("{langkey}", $langCode, self::TRANSLATE_TITLE_KEY);
//            $descriptionKey = str_replace("{langkey}", $langCode, self::TRANSLATE_DESCRIPTION_KEY);
//
//            if (
//                !empty($row[$titleKey]) ||
//                !empty($row[$descriptionKey])
//            ) {
//                $store = \Mage::getModel('core/store')->load($langCode, 'code');
//                if ($store) {
//                    $storeId = $store->getId();
//
//                    if (!empty($row[$titleKey])) {
//                        $title = $row[$titleKey];
//                        $tProduct->setStoreId($storeId)->setName($title);
//                    }
//
//                    if (!empty($row[$descriptionKey])) {
//                        $description = $row[$descriptionKey];
//                        $tProduct->setStoreId($storeId)->setDescription($description);
//                    }
//
//                    $tProduct->save();
//                }
//            }
//        }
//    }
//
//    /**
//     * @param $row
//     *
//     * @return mixed
//     */
//    public function rowToProduct($row)
//    {
//        $type = empty($row[self::TYPE_KEY]) ? "simple" : $row[self::TYPE_KEY];
//        $sku = empty($row[self::SKU_KEY]) ? "" : $row[self::SKU_KEY];
//
//        $attributes = Mage::getSingleton('eav/config')
//            ->getEntityType(Mage_Catalog_Model_Product::ENTITY)
//            ->getAttributeCollection()
//            ->addSetInfo();
//
//        $product = Mage::getModel('catalog/product')->loadByAttribute('sku', $sku);
//        if (!$product) {
//            $product = Mage::getModel('catalog/product');
//            $product->setSku($sku);
//            $product->setWebsiteIds([1]);
//            $product->setTypeId($type);
//            $product->setCreatedAt(strtotime('now'));
//            $product->setStoreId(\Mage::app()->getStore()->getId());
//            $product->setAttributeSetId(Mage::getModel('catalog/product')->getDefaultAttributeSetId());
//        }
//
//        foreach ($row as $key => $value) {
//
//            if (preg_match("/^att_/is", $key)) {
//
//                $attributeName = preg_replace("/^att_/is", '', $key);
//
//                $type = null;
//                /** @var Mage_Catalog_Model_Resource_Eav_Attribute $attribute */
//                foreach ($attributes as $attribute) {
//                    if ($attributeName == $attribute->getName()) {
//                        $type = $attribute->getData('frontend_input');
//                    }
//                }
//
//                switch ($type) {
//                    case "select":
//                        $product->setData($attributeName, $product->getResource()->getAttribute($attributeName)->getSource()->getOptionId($value));
//                        break;
//                    case "text":
//                        $product->setData($attributeName, $value);
//                        break;
//                    default:
//                        $product->setData($attributeName, $value);
//                        break;
//                }
//            }
//        }
//
//
//        if (isset($row[self::TITLE_KEY])) {
//            $product->setName(empty($row[self::TITLE_KEY]) ? "." : $row[self::TITLE_KEY]);
//        }
//        if (isset($row[self::STATUS_KEY])) {
//            if ($row[self::STATUS_KEY] == 0) {
//                $row[self::STATUS_KEY] = 2;
//            }
//            $product->setStatus($row[self::STATUS_KEY]);
//        }
//        if (isset($row[self::WEIGHT_KEY])) {
//            $product->setWeight($row[self::WEIGHT_KEY]);
//        }
//        if (isset($row[self::HEIGHT_KEY])) {
//            $product->setHeight($row[self::HEIGHT_KEY]);
//        }
//        if (isset($row[self::TAX_CLASS_KEY])) {
//            $product->setTaxClassId($row[self::TAX_CLASS_KEY]);
//        }
//        if (isset($row[self::VISIBILITY_KEY])) {
//            $product->setVisibility($row[self::VISIBILITY_KEY]);
//        }
//        if (isset($row[self::DESCRIPTION_KEY])) {
//            $product->setDescription(empty($row[self::DESCRIPTION_KEY]) ? "." : $row[self::DESCRIPTION_KEY]);
//        }
//        if (isset($row[self::PRICE_KEY])) {
//            $price = str_replace(",", ".", $row[self::PRICE_KEY]);
//            $product->setPrice($price);
//        }
//        if (isset($row[self::SPECIAL_PRICE_KEY])) {
//            $specialPrice = str_replace(",", ".", $row[self::SPECIAL_PRICE_KEY]);
//            $product->setSpecialPrice($specialPrice);
//        }
//        if (isset($row[self::CATEGORY_KEY])) {
//            $categories = explode("|", $row[self::CATEGORY_KEY]);
//            $product->setCategoryIds($categories);
//        }
//        if (isset($row[self::SHORT_DESCRIPTION_KEY])) {
//            $product->setShortDescription($row[self::SHORT_DESCRIPTION_KEY]);
//        }
//        if (isset($row[self::ATTRIBUTE_SET_KEY])) {
//            $product->setAttributeSetId($row[self::ATTRIBUTE_SET_KEY]);
//        }
//        if (isset($row[self::META_TITLE_KEY])) {
//            $product->setMetaTitle($row[self::META_TITLE_KEY]);
//        }
//        if (isset($row[self::META_DESCRIPTION_KEY])) {
//            $product->setMetaDescription($row[self::META_DESCRIPTION_KEY]);
//        }
//
//        return $product;
//    }
//
//    public function getRowHeader($attributeCodes, $storeViews)
//    {
//        $headers = [
//            self::SKU_KEY,
//            self::TYPE_KEY,
//            self::CATEGORY_KEY,
//            self::DESCRIPTION_KEY,
//            self::TITLE_KEY,
//            self::PRICE_KEY,
//            self::SPECIAL_PRICE_KEY,
//            self::STATUS_KEY,
//            self::WEIGHT_KEY,
//            self::VISIBILITY_KEY,
//            Thespace_ImportExport_Helper_StockRow::MANAGE_QUANTITY_KEY,
//            Thespace_ImportExport_Helper_StockRow::QUANTITY_KEY,
//            Thespace_ImportExport_Helper_StockRow::AVAILABLE_KEY,
//            Thespace_ImportExport_Helper_ImageRow::IMAGES_KEY,
//            self::CONFIGURABLE_ATTRIBUTES_KEY,
//            self::PARENT_SKU_KEY,
//        ];
//
//        foreach ($attributeCodes as $attributeCode) {
//            $headers[] = "att_" . $attributeCode;
//        }
//
//        foreach ($storeViews as $storeView) {
//            $headers[] = 'titolo_' . $storeView->getCode();
//            $headers[] = 'descrizione_' . $storeView->getCode();
//        }
//
//        return $headers;
//    }
//
//    public function productToRow($product, $attributeCodes, $storeViews, $includeImages = true)
//    {
//        $product = \Mage::getModel('catalog/product')->load($product->getId());
//
//        $attributes = Mage::getSingleton('eav/config')
//            ->getEntityType(Mage_Catalog_Model_Product::ENTITY)
//            ->getAttributeCollection()
//            ->addSetInfo();
//
//        $imageUrls = [];
//        if (count($product->getMediaGalleryImages()) && $includeImages) {
//            foreach ($product->getMediaGalleryImages() as $image) {
//                $imageUrls[] = $image->getUrl();
//            }
//        }
//
//        $usedProductAttributeCodes = [];
//        if ($product->getTypeId() == "configurable") {
//            $usedProductAttributes = $product->getTypeInstance()->getUsedProductAttributes($product);
//            foreach ($usedProductAttributes as $attribute) {
//                $usedProductAttributeCodes[] = $attribute->getAttributeCode();
//            }
//        }
//
//
//        $parentSku = "";
//        if ($product->getTypeId() == "simple") {
//            $parentIds = Mage::getModel('catalog/product_type_grouped')->getParentIdsByChild($product->getId());
//            if (!$parentIds) {
//                $parentIds = Mage::getModel('catalog/product_type_configurable')->getParentIdsByChild($product->getId());
//            }
//            if (isset($parentIds[0])) {
//                $parent = Mage::getModel('catalog/product')->load($parentIds[0]);
//                $parentSku = $parent->getSku();
//            }
//        }
//
//        $row = [
//            $product->getSku(),
//            $product->getTypeId(),
//            implode("|", $product->getCategoryIds()),
//            $product->getDescription(),
//            $product->getName(),
//            $product->getPrice(),
//            $product->getSpecialPrice(),
//            $product->getStatus() == 2 ? 0 : $product->getStatus(),
//            $product->getWeight(),
//            $product->getVisibility(),
//            $product->getStockItem()->getManageStock() ? 1 : 0,
//            $product->getStockItem()->getQty(),
//            $product->getStockItem()->getData('is_in_stock'),
//            implode("|", $imageUrls),
//            implode("|", $usedProductAttributeCodes),
//            $parentSku,
//        ];
//
//
//        foreach ($attributeCodes as $attributeCode) {
//
//            /** @var Mage_Catalog_Model_Resource_Eav_Attribute $attribute */
//            foreach ($attributes as $attribute) {
//                if ($attributeCode == $attribute->getName()) {
//
//                    $type = $attribute->getData('frontend_input');
//
//                    switch ($type) {
//                        case "select":
//                            $row[] = $product->getAttributeText($attributeCode);
//                            break;
//                        case "text":
//                            $row[] = $product->getData($attributeCode);
//                            break;
//                        default:
//                            $row[] = $product->getData($attributeCode);
//                            break;
//                    }
//                }
//            }
//        }
//
//        foreach ($storeViews as $storeView) {
//
//            $storeViewProduct = $product = Mage::getModel('catalog/product')->setStoreId($storeView->getId())->load($product->getId());
//
//            $row[] = $storeViewProduct->getName();
//            $row[] = $storeViewProduct->getDescription();
//        }
//
//        return $row;
//    }
}