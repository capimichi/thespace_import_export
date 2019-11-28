<?php

class Thespace_ImportExport_Helper_ProductParser extends Mage_Core_Helper_Abstract
{
    const REQUIRED_HEADERS = [
        'sku',
    ];
    
    const REQUIRED_NEW_PRODUCT_HEADERS = [
        'name',
        'status',
        'tax_class_id',
        '_type',
        '_attribute_set',
        '_product_websites',
    ];
    
    const ARRAY_SEPARATOR = '|';
    
    const NOT_REQUIRED_HEADERS = [
        'name',
        'status',
        'tax_class_id',
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
            'immagine',
            'immagini',
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
            'titolo',
            'title',
        ],
        '_category'               => [
            'category',
            'categories',
            'categoria',
            'categorie',
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
            'attivo',
            'active',
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
        'backorders'              => [
            'backorders',
        ],
        'use_config_backorders'   => [
            'use_config_backorders',
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
    
    const SPECIAL_CSV_HEADERS = [
        '_category',
        '_type',
        '_attribute_set',
        '_product_websites',
        '_store',
        'parent',
        'variation_attributes',
    ];
    
    const STOCK_CSV_HEADERS = [
        'qty',
        'is_in_stock',
        'manage_stock',
        'use_config_manage_stock',
    ];
    
    const DEFAULT_CSV_HEADERS = [
        'sku',
        'name',
        'description',
        'short_description',
        'price',
        'status',
        'tax_class_id',
        'visibility',
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
     * @param      $product
     * @param null $storeView
     *
     * @return array
     */
    public function getRowFromProduct($product, $storeView = null)
    {
        $row = [];
        
        foreach (self::SPECIAL_CSV_HEADERS as $header) {
            switch ($header) {
                case "_category":
                    $value = $product->getCategoryIds();
                    if (is_array($value)) {
                        $value = implode("|", $value);
                    }
                    break;
                case "_type":
                    $value = $product->getTypeId();
                    break;
                case "_attribute_set":
                    $attributeSetModel = Mage::getModel("eav/entity_attribute_set");
                    $attributeSetModel->load($product->getAttributeSetId());
                    $value = $attributeSetModel->getAttributeSetName();
                    break;
                case "_product_websites":
                    $value = "";
                    break;
                case "_store":
                    $value = "";
                    if ($storeView) {
                        $value = $storeView->getCode();
                    }
                    break;
                case "parent":
                    $value = "";
                    if ($product->getTypeId() == "simple") {
                        $parentIds = Mage::getModel('catalog/product_type_grouped')->getParentIdsByChild($product->getId());
                        if (!$parentIds) {
                            $parentIds = Mage::getModel('catalog/product_type_configurable')->getParentIdsByChild($product->getId());
                        }
                        if (isset($parentIds[0])) {
                            $parent = Mage::getModel('catalog/product')->load($parentIds[0]);
                            $value = $parent->getSku();
                        }
                    }
                    break;
                case "variation_attributes":
                    $usedProductAttributeCodes = [];
                    if ($product->getTypeId() == "configurable") {
                        $usedProductAttributes = $product->getTypeInstance()->getUsedProductAttributes($product);
                        foreach ($usedProductAttributes as $attribute) {
                            $usedProductAttributeCodes[] = $attribute->getAttributeCode();
                        }
                    }
                    $value = implode('|', $usedProductAttributeCodes);
                    break;
            }
            $row[$header] = $value;
        }
        
        foreach (self::DEFAULT_CSV_HEADERS as $header) {
//            if ($storeView) {
//                $value = $product->setStoreId($storeView->getId())->getData($header);
//            } else {
            $value = $product->getData($header);
//            }
            $row[$header] = $value;
        }
        
        
        return $row;
    }
    
    /**
     * @author Michele Capicchioni <capimichi@gmail.com>
     *
     * @param $row
     * @param $attributes
     * @param $categoryPathNames
     *
     * @return array
     */
    public function getDataFromRow($row, $attributes = null, $categoryPathNames = null)
    {
        $configurationHelper = Mage::helper('thespaceimportexport/Configuration');
        
        if (is_null($attributes)) {
            $attributes = Mage::getResourceModel('catalog/product_attribute_collection')
                ->getItems();
        }

//        $defaultRow = [
//            '_attribute_set'    => $configurationHelper->get(Thespace_ImportExport_Helper_Configuration::OPTION_DEFAULT_ATTRIBUTE_SET),
//            '_product_websites' => $configurationHelper->get(Thespace_ImportExport_Helper_Configuration::OPTION_DEFAULT_PRODUCT_WEBSITES),
//            'tax_class_id'      => $configurationHelper->get(Thespace_ImportExport_Helper_Configuration::OPTION_DEFAULT_TAX_CLASS_ID),
//        ];
//        $row = array_merge($defaultRow, $row);
        
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
        
        foreach ($data as $key => $datum) {
            $regexPattern = sprintf("/%s/is", preg_quote(self::ARRAY_SEPARATOR));
            if (preg_match($regexPattern, $datum)) {
                $datum = explode(self::ARRAY_SEPARATOR, $datum);
                $data[$key] = $datum;
            }
        }
        
        if (!empty($data['_category'])) {
            if (!is_array($data['_category'])) {
                $data['_category'] = [$data['_category']];
            }
            foreach ($data['_category'] as $key => $category) {
                $data['_category'][$key] = $categoryPathNames[$category];
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
        $categoryParserHelper = Mage::helper('thespaceimportexport/CategoryParser');
        $attributes = Mage::getResourceModel('catalog/product_attribute_collection')
            ->getItems();
        
        $categories = Mage::getModel('catalog/category')->getCollection()
            ->addAttributeToSelect('*');
        
        $categoryNames = $categoryParserHelper->categoryNames();
        
        $categoryPathNames = [];
        foreach ($categories as $category) {
            $categoryPathNames[$category->getId()] = $categoryParserHelper->getCategoryPathName($category, $categoryNames);
        }
        
        $datas = [];
        
        foreach ($rows as $row) {
            $data = $this->getDataFromRow($row, $attributes, $categoryPathNames);
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
        
        for ($i = 0; $i < count($dataItems); $i++) {
            $dataItem = $dataItems[$i];
            $sku = $dataItem['sku'];
            
            $variationAttributeCodes = $dataItem['variation_attributes'];
            if (!is_array($variationAttributeCodes)) {
                $variationAttributeCodes = [$variationAttributeCodes];
            }
            
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
                            $dataItem[$key] = $value;
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
                    
                    $imagePaths = $dataItem['image'];
                    if (!is_array($imagePaths)) {
                        $imagePaths = [$imagePaths];
                    }
                    
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
     * @param       $dataItems
     * @param array $options
     *
     * @return mixed
     */
    public function parseImages($dataItems, $options = [])
    {
        $options = array_merge([
            'media_attribute_id' => Mage::getSingleton('catalog/product')->getResource()->getAttribute('media_gallery')->getAttributeId(),
            'advanced'           => 0,
        ], $options);
        
        $imageHelper = Mage::helper('thespaceimportexport/Image');
        
        if (!$options['advanced']) {
            foreach ($dataItems as $key => $dataItem) {
                
                $images = $dataItem['image'];
                
                if (!empty($images)) {
                    if (!is_array($images)) {
                        $images = [$images];
                    }
                    
                    $_mediaImages = [];
                    $_mediaLabels = [];
                    $_mediaIsDisableds = [];
                    $_mediaAttributeIds = [];
                    $_mediaPositions = [];
                    $index = 0;
                    foreach ($images as $image) {
                        $image = $imageHelper->storeImage($image);
                        $_mediaImages[] = basename($image);;
                        $_mediaLabels[] = basename($image);;
                        $_mediaIsDisableds[] = 0;
                        $_mediaAttributeIds[] = $options['media_attribute_id'];
                        $_mediaPositions[] = $index;
                        $dataItems[$key]['image'] = basename($image);
                        $dataItems[$key]['small_image'] = basename($image);;
                        $dataItems[$key]['thumbnail'] = basename($image);;
                        $index++;
                    }
                    
                    $dataItems[$key]['_media_image'] = $_mediaImages;
                    $dataItems[$key]['_media_attribute_id'] = $_mediaAttributeIds;
                    $dataItems[$key]['_media_is_disabled'] = $_mediaIsDisableds;
                    $dataItems[$key]['_media_position'] = $_mediaPositions;
                    $dataItems[$key]['_media_lable'] = $_mediaLabels;
                } else {
                    unset($dataItems[$key]['image']);
                }
            }
        }
        
        return $dataItems;
    }
    
    /**
     * @author Michele Capicchioni <capimichi@gmail.com>
     *
     * @param $dataItems
     * @param $existingSkus
     */
    public function clearImages($dataItems, $existingSkus)
    {
        foreach ($dataItems as $dataItem) {
            
            $sku = $dataItem['sku'];
            if (
                in_array($sku, $existingSkus)
                && !empty($dataItem['_media_image'])
            ) {
                $product = Mage::getModel('catalog/product')->loadByAttribute('sku', $sku);
                $mediaApi = Mage::getModel("catalog/product_attribute_media_api");
                $items = $mediaApi->items($product->getId());
                foreach ($items as $item) {
                    $mediaApi->remove($product->getId(), $item['file']);
                }
                
            }
        }
    }
    
    
    public function applyCategories($dataItems, $skuCategories, $categoryNames, $categoryPaths)
    {
        $categoryParserHelper = Mage::helper('thespaceimportexport/CategoryParser');
        foreach ($dataItems as $k => $dataItem) {
            
            $sku = $dataItem['sku'];
            
            if (
                isset($skuCategories[$sku])
                && isset($dataItem['_category'])
            ) {
                $names = [];
                foreach ($skuCategories[$sku] as $categoryId) {
                    $name = $categoryParserHelper->getCategoryPathNameById($categoryId, $categoryNames, $categoryPaths);
                    $names[] = $name;
                }
                
                if (!is_array($dataItem['_category'])) {
                    $dataItem['_category'] = [$dataItem['_category']];
                }
                $dataItem['_category'] = array_merge($dataItem['_category'], $names);
                $dataItem['_category'] = array_unique($dataItem['_category']);
                $dataItems[$k] = $dataItem;
            }
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
        
        $configurationHelper = Mage::helper('thespaceimportexport/Configuration');
        $productParserHelper = Mage::helper('thespaceimportexport/ProductParser');
        $combinationHelper = Mage::helper('thespaceimportexport/Combination');
        $slugHelper = Mage::helper('thespaceimportexport/Slug');
        $skuHelper = Mage::helper('thespaceimportexport/Sku');
        
        $items = [];
        
        $row = self::getDataFromRow($row, $attributes);
        
        $rowAttributeCodes = $row['variation_attributes'];
        if (!is_array($rowAttributeCodes)) {
            $rowAttributeCodes = [$rowAttributeCodes];
        }
        $rowAttributeIds = [];
        foreach ($rowAttributeCodes as $rowAttributeCode) {
            $rowAttributeIds[] = Mage::getResourceModel('eav/entity_attribute')
                ->getIdByCode('catalog_product', $rowAttributeCode);
        }
        
        $attributeValueGroups = [];
        
        foreach ($rowAttributeCodes as $rowAttributeCode) {
            
            if (isset($row[$rowAttributeCode])) {
                
                $values = $row[$rowAttributeCode];
                if (!is_array($values)) {
                    $values = [$values];
                }
                
                $attributeValueGroups[] = array_unique($values);
            }
        }

//        $existingSkus = $skuHelper->getExistingSkus();
        
        $combinations = $combinationHelper->getCombinations($attributeValueGroups);
        
        $sku = $row['parent'];
        
        $parentProduct = Mage::getModel('catalog/product')->loadByAttribute('sku', $sku);
        
        $defaultRow = [
            '_attribute_set'    => $configurationHelper->get(Thespace_ImportExport_Helper_Configuration::OPTION_DEFAULT_ATTRIBUTE_SET),
            '_product_websites' => $configurationHelper->get(Thespace_ImportExport_Helper_Configuration::OPTION_DEFAULT_PRODUCT_WEBSITES),
            'tax_class_id'      => $configurationHelper->get(Thespace_ImportExport_Helper_Configuration::OPTION_DEFAULT_TAX_CLASS_ID),
        ];
        
        if (!$parentProduct) {
            
            $rowData = $productParserHelper->getDataFromRow($row, $attributes);
            $rowData = array_merge($defaultRow, $rowData);
            
            $parentItem = $rowData;
            $parentItem['sku'] = $sku;
            
            $name = $parentItem['name'];
        } else {
            
            $parentItem = [
                'sku' => $sku,
            ];
            
            $name = $parentProduct->getName();
        }
        
        $parentItem['variation_attributes'] = $row['variation_attributes'];
        $parentItem['_type'] = 'configurable';
        unset($parentItem['parent']);
        
        $parentItem = array_merge($defaultRow, $parentItem);
        
        $items[] = $parentItem;
        
        $index = 0;
        foreach ($combinations as $combination) {
            $index++;
            
            if (!is_array($combination)) {
                $combination = [$combination];
            }
            
            $combinationName = [];
            for ($i = 0; $i < count($combination); $i++) {
                $combinationName[] = ucwords(str_replace("_", " ", $rowAttributeIds[$i]));
//                $combinationName[] = $combination[$i];
                $combinationAttribute = Mage::getModel('eav/config')->getAttribute('catalog_product', $rowAttributeCodes[$i]);
                $combinationId = $combinationAttribute->getSource()->getOptionId($combination[$i]);
                $combinationName[] = $combinationId;
            }
            
            $combinationSku = strtolower(sprintf("%s-%s", $sku, $slugHelper->getSlug(implode("-", $combinationName))));
            
            $item = array_merge($row, [
                'sku'        => $combinationSku,
                'name'       => $name,
                '_type'      => 'simple',
                'visibility' => 1,
            ]);
            
            unset($item['variation_attributes']);
            foreach ($rowAttributeCodes as $code) {
                unset($item[$code]);
            }
            
            foreach ($combination as $key => $value) {
                $item[$rowAttributeCodes[$key]] = $value;
            }
            
            if (empty($item['_attribute_set'])) {
                if ($parentProduct) {
                    $item['_attribute_set'] = Mage::getModel('eav/entity_attribute_set')->load($parentProduct->getAttributeSetId())->getAttributeSetName();
                } else {
                    $item['_attribute_set'] = $parentItem['_attribute_set'];
                }
            }

            if (empty($item['_product_websites'])) {
                $item['_product_websites'] = [];

                if ($parentProduct) {
                    foreach ($parentProduct->getWebsiteIds() as $websiteId) {
                        $websiteCode = Mage::getModel('core/website')->load($websiteId)->getData("code");
                        $item['_product_websites'][] = $websiteCode;
                    }
                } else {
                    $item['_product_websites'][] = $parentItem['_product_websites'];
                }
            }
            
            if (empty($item['tax_class_id'])) {
                
                if ($parentProduct) {
                    $item['tax_class_id'] = $parentProduct->getTaxClassId();
                } else {
                    $item['tax_class_id'] = $parentItem['tax_class_id'];
                }
            }
            
            foreach ($attributes as $attribute) {
                $attributeCode = $attribute->getData('attribute_code');
                if (
                    !empty($attributeCode)
                    && intval($attribute->getData('is_required'))
                    && !in_array($attributeCode, self::NOT_REQUIRED_HEADERS)
                ) {
                    if (!isset($item[$attributeCode])) {
                        if ($parentProduct) {
                            $item[$attributeCode] = $parentProduct->getData($attributeCode);
                        } else {
                            $item[$attributeCode] = $parentItem[$attributeCode];
                        }
                    }
                }
            }
            
            if (empty($item['price'])) {
                if ($parentProduct) {
                    $item['price'] = $parentProduct->getPrice();
                } else {
                    $item['price'] = $parentItem['price'];
                }
            }
            
            $items[$combinationSku] = $item;
            
            
            $items = array_values($items);
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
    
    /**
     * @author Michele Capicchioni <capimichi@gmail.com>
     *
     * @param      $row
     * @param null $attributes
     * @param null $existingSkus
     *
     * @return array
     */
    public function getMissingHeadersInRow($row, $attributes = null, $existingSkus = null)
    {
        if (is_null($attributes)) {
            $attributes = Mage::getResourceModel('catalog/product_attribute_collection')
                ->getItems();
        }
        
        $data = $this->getDataFromRow($row);
        
        $missingHeaders = [];
        
        foreach (self::REQUIRED_HEADERS as $requiredHeader) {
            if (!isset($data[$requiredHeader])) {
                $missingHeaders[] = $requiredHeader;
            }
        }
        
        if (isset($data['sku'])) {
            
            $sku = $data['sku'];
            
            if (is_array($existingSkus)) {
                $productExists = in_array($sku, $existingSkus);
            } else {
                $productExists = \Mage::getModel('catalog/product')->loadByAttribute('sku', $sku) ? true : false;
            }

//            $product = \Mage::getModel('catalog/product')->loadByAttribute('sku', $sku);
            
            if (!$productExists) {
                
                foreach (self::REQUIRED_NEW_PRODUCT_HEADERS as $requiredHeader) {
                    if (!isset($data[$requiredHeader])) {
                        $missingHeaders[] = $requiredHeader;
                    }
                }
                
                foreach ($attributes as $attribute) {
                    $isRequired = intval($attribute->getData('is_required'));
                    $attributeCode = $attribute->getData('attribute_code');
//                    $isUserDefined = intval($attribute->getData('is_user_defined'));
                    
                    if ($isRequired && !in_array($attributeCode, self::NOT_REQUIRED_HEADERS)) {
                        if (!isset($data[$attributeCode])) {
                            $missingHeaders[] = sprintf("att_%s", $attributeCode);
                        }
                    }
                }
                
            }
        } else {
            $missingHeaders[] = 'sku';
        }
        
        return $missingHeaders;
    }
    
    /**
     * @author Michele Capicchioni <capimichi@gmail.com>
     *
     * @param      $rows
     * @param null $existingSkus
     *
     * @return array
     */
    public function getMissingHeadersInRows($rows, $existingSkus = null)
    {
        $missingHeadersRows = [];
        
        $attributes = Mage::getResourceModel('catalog/product_attribute_collection')
            ->getItems();
        
        foreach ($rows as $row) {
            $missingHeaders = $this->getMissingHeadersInRow($row, $attributes, $existingSkus);
            
            $missingHeadersRows[] = $missingHeaders;
        }
        
        return $missingHeadersRows;
    }
    
}
