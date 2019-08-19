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
    
    const HEADER_ASSOCIATIONS = [
        'name'              => [
            'name',
            'nome',
        ],
        'sku'               => [
            'sku',
            'riferimento',
        ],
        '_type'             => [
            'tipo',
            'tipologia',
            'type',
            '_type',
        ],
        '_attribute_set'    => [
            'set',
            'set_attributi',
            'attributi_set',
            'attribute_set',
            'set_attribute',
            '_attribute_set',
        ],
        '_product_websites' => [
            'website',
            '_product_websites',
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
     *
     * @return array
     */
    public function getDataFromRow($row)
    {
        $data = [];
        foreach (self::HEADER_ASSOCIATIONS as $magentoKey => $headerNames) {
            if (!is_array($headerNames)) {
                $headerNames = [
                    $headerNames,
                ];
            }
            
            foreach ($headerNames as $headerName) {
                if (isset($row[$headerName])) {
                    $data[$magentoKey] = $row[$headerName];
                }
            }
        }
        
        return $data;
    }
    
    /**
     * @author Michele Capicchioni <capimichi@gmail.com>
     *
     * @param $row
     *
     * @return array
     */
    public function getMissingHeadersInRow($row)
    {
        $data = $this->getDataFromRow($row);
        
        $missingHeaders = [];
        
        foreach (self::REQUIRED_HEADERS as $requiredHeader) {
            if (!isset($data[$requiredHeader])) {
                $missingHeaders[] = $requiredHeader;
            }
        }
        
        if (isset($data['sku'])) {
            
            $sku = $data['sku'];
            
            $product = \Mage::getModel('catalog/product')->loadByAttribute('sku', $sku);
            
            if (!$product) {
                
                foreach (self::REQUIRED_NEW_PRODUCT_HEADERS as $requiredHeader) {
                    if (!isset($data[$requiredHeader])) {
                        $missingHeaders[] = $requiredHeader;
                    }
                }
                
                $attributes = Mage::getResourceModel('catalog/product_attribute_collection')
                    ->getItems();
                
                foreach ($attributes as $attribute) {
                    $isRequired = intval($attribute->getData('is_required'));
//                    $isUserDefined = intval($attribute->getData('is_user_defined'));
                    
                    if ($isRequired) {
                        
                        $attributeCode = $attribute->getData('attribute_code');
                        if (!isset($data[$attributeCode])) {
                            $missingHeaders[] = $attributeCode;
                        }
                    }
                }
                
            }
        }
        
        return $missingHeaders;
    }
    
    /**
     * @author Michele Capicchioni <capimichi@gmail.com>
     *
     * @param $rows
     *
     * @return array
     */
    public function getMissingHeadersInRows($rows)
    {
        $missingHeadersRows = [];
        
        $attributes = Mage::getResourceModel('catalog/product_attribute_collection')
            ->getItems();
        
        foreach ($rows as $row) {
            $data = $this->getDataFromRow($row);
            
            $missingHeaders = [];
            
            foreach (self::REQUIRED_HEADERS as $requiredHeader) {
                if (!isset($data[$requiredHeader])) {
                    $missingHeaders[] = $requiredHeader;
                }
            }
            
            if (isset($data['sku'])) {
                
                $sku = $data['sku'];
                
                $product = \Mage::getModel('catalog/product')->loadByAttribute('sku', $sku);
                
                if (!$product) {
                    
                    foreach (self::REQUIRED_NEW_PRODUCT_HEADERS as $requiredHeader) {
                        if (!isset($data[$requiredHeader])) {
                            $missingHeaders[] = $requiredHeader;
                        }
                    }
                    
                    foreach ($attributes as $attribute) {
                        $isRequired = intval($attribute->getData('is_required'));
                        
                        if ($isRequired) {
                            
                            $attributeCode = $attribute->getData('attribute_code');
                            if (!isset($data[$attributeCode])) {
                                $missingHeaders[] = $attributeCode;
                            }
                        }
                    }
                    
                }
            }
            
            $missingHeadersRows[] = $missingHeaders;
        }
        
        return $missingHeadersRows;
    }
    
    /**
     * @param $row
     *
     * @return mixed
     */
    public function getRowNewSku($row)
    {
        return isset($row[self::NEW_SKU_KEY]) ? $row[self::NEW_SKU_KEY] : null;
    }
    
    /**
     * @param $row
     *
     * @return mixed
     */
    public function getSku($row)
    {
        return isset($row[self::SKU_KEY]) ? $row[self::SKU_KEY] : null;
    }
    
    /**
     * @param $row
     *
     * @return mixed
     */
    public function getRowProductType($row)
    {
        return isset($row[self::TYPE_KEY]) ? $row[self::TYPE_KEY] : 'simple';
    }
    
    /**
     * @param $row
     *
     * @return mixed
     */
    public function getRowProductParentSku($row)
    {
        return isset($row[self::PARENT_SKU_KEY]) ? $row[self::PARENT_SKU_KEY] : null;
    }
    
    /**
     * @param $row
     *
     * @return mixed
     */
    public function getRowProductSku($row)
    {
        return isset($row[self::SKU_KEY]) ? $row[self::SKU_KEY] : null;
    }
    
    /**
     * @param $row
     *
     * @return array
     */
    public function getConfigurableProductUsedAttributeCodes($row)
    {
        return isset($row[self::CONFIGURABLE_ATTRIBUTES_KEY]) ? explode("|", $row[self::CONFIGURABLE_ATTRIBUTES_KEY]) : [];
    }
    
    /**
     * @param $products
     *
     * @return array
     */
    public function getProductsUsedAttributeCodes($products)
    {
        $attributeCodes = [];
        foreach ($products as $product) {
            if ($product->type_id == 'configurable') {
                $usedProductAttributes = $product->getTypeInstance()->getUsedProductAttributes($product);
                foreach ($usedProductAttributes as $attribute) {
                    $attributeCodes[] = $attribute->getAttributeCode();
                }
            }
        }
        $attributeCodes = array_unique($attributeCodes);
        
        return $attributeCodes;
    }
    
    /**
     * @param $row
     *
     * @return array
     * @throws Exception
     */
    public function getImportAttributeCodes($row)
    {
        $codes = [];
        
        foreach ($row as $key => $value) {
            
            if (preg_match("/^att_/is", $key)) {
                
                $attributeName = preg_replace("/^att_/is", '', $key);
                
                $attr = Mage::getResourceModel('catalog/eav_attribute')
                    ->loadByCode('catalog_product', $attributeName);
                
                if (!$attr->getId()) {
                    throw new Exception(sprintf("Missing attribute %s", $attributeName));
                }
                
                $codes[] = $attributeName;
            }
        }
        
        return $codes;
    }
    
    /**
     * @param $row
     *
     * @return mixed
     *
     * @throws \Exception
     */
    public function changeSku($row)
    {
        $product = \Mage::getModel('catalog/product')->loadByAttribute('sku', $this->getRowProductSku($row));
        if ($product) {
            $newSku = $this->getRowNewSku($row);
            
            $productWithnewSku = \Mage::getModel('catalog/product')->loadByAttribute('sku', $newSku);
            
            if ($productWithnewSku) {
                throw  new \Exception("Product with same sku already exists");
            }
            
            $product->setSku($newSku);
            return $product;
        } else {
            return null;
        }
    }
    
    /**
     * @param $product
     * @param $attributeCodes
     *
     * @return Mage_Core_Model_Abstract
     */
    public function setConfigurableProductUsedAttributes($product, $attributeCodes)
    {
        $product = \Mage::getModel('catalog/product')->load($product->getId());
        
        $product->setCanSaveConfigurableAttributes(true);
        $product->setCanSaveCustomOptions(true);
        
        $attributeIds = [];
        
        foreach ($attributeCodes as $attributeCode) {
            $attributeIds[] = \Mage::getModel('eav/entity_attribute')->getIdByCode('catalog_product', $attributeCode);
        }
        
        $product->getTypeInstance()->setUsedProductAttributeIds($attributeIds);
        $product->setConfigurableAttributesData($product->getTypeInstance()->getConfigurableAttributesAsArray());
        
        return $product;
    }
    
    public function setConfigurableData($product, $rows, $attributeCodes)
    {
        $product = \Mage::getModel('catalog/product')->load($product->getId());
        
        $configurableProductsData = $product->getConfigurableProductsData();
        
        foreach ($rows as $row) {
            
            $variationProduct = \Mage::getModel('catalog/product')->loadByAttribute('sku', $this->getRowProductSku($row));
            $simpleProductsData = [];
            
            foreach ($attributeCodes as $attributeCode) {
                
                $attributeId = \Mage::getModel('eav/entity_attribute')->getIdByCode('catalog_product', $attributeCode);
                
                $attributeValue = $row['att_' . $attributeCode];
                
                $simpleProductsData[] = [
                    'label'         => rand(0, 999999),
                    'attribute_id'  => intval($attributeId),
                    'value_index'   => $variationProduct->getResource()->getAttribute($attributeCode)->getSource()->getOptionId($attributeValue),
                    'is_percent'    => 0,
                    'pricing_value' => floatval($variationProduct->getPrice()),
                ];
            }
            
            $configurableProductsData[$variationProduct->getId()] = $simpleProductsData;
            
            //            $configurableAttributesData[0]['values'][] = $simpleProductsData;
            
            $variations[] = $variationProduct;
        }
        
        $product->setConfigurableProductsData($configurableProductsData);
        
        return $product;
    }
    
    public function translateproduct($product, $row)
    {
        Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);
        
        $langCodes = [];
        $stores = Mage::app()->getStores();
        foreach ($stores as $store) {
            $langCodes[] = $store->getCode();
        }
        
        $tProduct = \Mage::getModel('catalog/product')->load($product->getId());
        
        foreach ($langCodes as $langCode) {
            $titleKey = str_replace("{langkey}", $langCode, self::TRANSLATE_TITLE_KEY);
            $descriptionKey = str_replace("{langkey}", $langCode, self::TRANSLATE_DESCRIPTION_KEY);
            
            if (
                !empty($row[$titleKey]) ||
                !empty($row[$descriptionKey])
            ) {
                $store = \Mage::getModel('core/store')->load($langCode, 'code');
                if ($store) {
                    $storeId = $store->getId();
                    
                    if (!empty($row[$titleKey])) {
                        $title = $row[$titleKey];
                        $tProduct->setStoreId($storeId)->setName($title);
                    }
                    
                    if (!empty($row[$descriptionKey])) {
                        $description = $row[$descriptionKey];
                        $tProduct->setStoreId($storeId)->setDescription($description);
                    }
                    
                    $tProduct->save();
                }
            }
        }
    }
    
    /**
     * @param $row
     *
     * @return mixed
     */
    public function rowToProduct($row)
    {
        $type = empty($row[self::TYPE_KEY]) ? "simple" : $row[self::TYPE_KEY];
        $sku = empty($row[self::SKU_KEY]) ? "" : $row[self::SKU_KEY];
        
        $attributes = Mage::getSingleton('eav/config')
            ->getEntityType(Mage_Catalog_Model_Product::ENTITY)
            ->getAttributeCollection()
            ->addSetInfo();
        
        $product = Mage::getModel('catalog/product')->loadByAttribute('sku', $sku);
        if (!$product) {
            $product = Mage::getModel('catalog/product');
            $product->setSku($sku);
            $product->setWebsiteIds([1]);
            $product->setTypeId($type);
            $product->setCreatedAt(strtotime('now'));
            $product->setStoreId(\Mage::app()->getStore()->getId());
            $product->setAttributeSetId(Mage::getModel('catalog/product')->getDefaultAttributeSetId());
        }
        
        foreach ($row as $key => $value) {
            
            if (preg_match("/^att_/is", $key)) {
                
                $attributeName = preg_replace("/^att_/is", '', $key);
                
                $type = null;
                /** @var Mage_Catalog_Model_Resource_Eav_Attribute $attribute */
                foreach ($attributes as $attribute) {
                    if ($attributeName == $attribute->getName()) {
                        $type = $attribute->getData('frontend_input');
                    }
                }
                
                switch ($type) {
                    case "select":
                        $product->setData($attributeName, $product->getResource()->getAttribute($attributeName)->getSource()->getOptionId($value));
                        break;
                    case "text":
                        $product->setData($attributeName, $value);
                        break;
                    default:
                        $product->setData($attributeName, $value);
                        break;
                }
            }
        }
        
        
        if (isset($row[self::TITLE_KEY])) {
            $product->setName(empty($row[self::TITLE_KEY]) ? "." : $row[self::TITLE_KEY]);
        }
        if (isset($row[self::STATUS_KEY])) {
            if ($row[self::STATUS_KEY] == 0) {
                $row[self::STATUS_KEY] = 2;
            }
            $product->setStatus($row[self::STATUS_KEY]);
        }
        if (isset($row[self::WEIGHT_KEY])) {
            $product->setWeight($row[self::WEIGHT_KEY]);
        }
        if (isset($row[self::HEIGHT_KEY])) {
            $product->setHeight($row[self::HEIGHT_KEY]);
        }
        if (isset($row[self::TAX_CLASS_KEY])) {
            $product->setTaxClassId($row[self::TAX_CLASS_KEY]);
        }
        if (isset($row[self::VISIBILITY_KEY])) {
            $product->setVisibility($row[self::VISIBILITY_KEY]);
        }
        if (isset($row[self::DESCRIPTION_KEY])) {
            $product->setDescription(empty($row[self::DESCRIPTION_KEY]) ? "." : $row[self::DESCRIPTION_KEY]);
        }
        if (isset($row[self::PRICE_KEY])) {
            $price = str_replace(",", ".", $row[self::PRICE_KEY]);
            $product->setPrice($price);
        }
        if (isset($row[self::SPECIAL_PRICE_KEY])) {
            $specialPrice = str_replace(",", ".", $row[self::SPECIAL_PRICE_KEY]);
            $product->setSpecialPrice($specialPrice);
        }
        if (isset($row[self::CATEGORY_KEY])) {
            $categories = explode("|", $row[self::CATEGORY_KEY]);
            $product->setCategoryIds($categories);
        }
        if (isset($row[self::SHORT_DESCRIPTION_KEY])) {
            $product->setShortDescription($row[self::SHORT_DESCRIPTION_KEY]);
        }
        if (isset($row[self::ATTRIBUTE_SET_KEY])) {
            $product->setAttributeSetId($row[self::ATTRIBUTE_SET_KEY]);
        }
        if (isset($row[self::META_TITLE_KEY])) {
            $product->setMetaTitle($row[self::META_TITLE_KEY]);
        }
        if (isset($row[self::META_DESCRIPTION_KEY])) {
            $product->setMetaDescription($row[self::META_DESCRIPTION_KEY]);
        }
        
        return $product;
    }
    
    public function getRowHeader($attributeCodes, $storeViews)
    {
        $headers = [
            self::SKU_KEY,
            self::TYPE_KEY,
            self::CATEGORY_KEY,
            self::DESCRIPTION_KEY,
            self::TITLE_KEY,
            self::PRICE_KEY,
            self::SPECIAL_PRICE_KEY,
            self::STATUS_KEY,
            self::WEIGHT_KEY,
            self::VISIBILITY_KEY,
            Thespace_ImportExport_Helper_StockRow::MANAGE_QUANTITY_KEY,
            Thespace_ImportExport_Helper_StockRow::QUANTITY_KEY,
            Thespace_ImportExport_Helper_StockRow::AVAILABLE_KEY,
            Thespace_ImportExport_Helper_ImageRow::IMAGES_KEY,
            self::CONFIGURABLE_ATTRIBUTES_KEY,
            self::PARENT_SKU_KEY,
        ];
        
        foreach ($attributeCodes as $attributeCode) {
            $headers[] = "att_" . $attributeCode;
        }
        
        foreach ($storeViews as $storeView) {
            $headers[] = 'titolo_' . $storeView->getCode();
            $headers[] = 'descrizione_' . $storeView->getCode();
        }
        
        return $headers;
    }
    
    public function productToRow($product, $attributeCodes, $storeViews, $includeImages = true)
    {
        $product = \Mage::getModel('catalog/product')->load($product->getId());
        
        $attributes = Mage::getSingleton('eav/config')
            ->getEntityType(Mage_Catalog_Model_Product::ENTITY)
            ->getAttributeCollection()
            ->addSetInfo();
        
        $imageUrls = [];
        if (count($product->getMediaGalleryImages()) && $includeImages) {
            foreach ($product->getMediaGalleryImages() as $image) {
                $imageUrls[] = $image->getUrl();
            }
        }
        
        $usedProductAttributeCodes = [];
        if ($product->getTypeId() == "configurable") {
            $usedProductAttributes = $product->getTypeInstance()->getUsedProductAttributes($product);
            foreach ($usedProductAttributes as $attribute) {
                $usedProductAttributeCodes[] = $attribute->getAttributeCode();
            }
        }
        
        
        $parentSku = "";
        if ($product->getTypeId() == "simple") {
            $parentIds = Mage::getModel('catalog/product_type_grouped')->getParentIdsByChild($product->getId());
            if (!$parentIds) {
                $parentIds = Mage::getModel('catalog/product_type_configurable')->getParentIdsByChild($product->getId());
            }
            if (isset($parentIds[0])) {
                $parent = Mage::getModel('catalog/product')->load($parentIds[0]);
                $parentSku = $parent->getSku();
            }
        }
        
        $row = [
            $product->getSku(),
            $product->getTypeId(),
            implode("|", $product->getCategoryIds()),
            $product->getDescription(),
            $product->getName(),
            $product->getPrice(),
            $product->getSpecialPrice(),
            $product->getStatus() == 2 ? 0 : $product->getStatus(),
            $product->getWeight(),
            $product->getVisibility(),
            $product->getStockItem()->getManageStock() ? 1 : 0,
            $product->getStockItem()->getQty(),
            $product->getStockItem()->getData('is_in_stock'),
            implode("|", $imageUrls),
            implode("|", $usedProductAttributeCodes),
            $parentSku,
        ];
        
        
        foreach ($attributeCodes as $attributeCode) {
            
            /** @var Mage_Catalog_Model_Resource_Eav_Attribute $attribute */
            foreach ($attributes as $attribute) {
                if ($attributeCode == $attribute->getName()) {
                    
                    $type = $attribute->getData('frontend_input');
                    
                    switch ($type) {
                        case "select":
                            $row[] = $product->getAttributeText($attributeCode);
                            break;
                        case "text":
                            $row[] = $product->getData($attributeCode);
                            break;
                        default:
                            $row[] = $product->getData($attributeCode);
                            break;
                    }
                }
            }
        }
        
        foreach ($storeViews as $storeView) {
            
            $storeViewProduct = $product = Mage::getModel('catalog/product')->setStoreId($storeView->getId())->load($product->getId());
            
            $row[] = $storeViewProduct->getName();
            $row[] = $storeViewProduct->getDescription();
        }
        
        return $row;
    }
}