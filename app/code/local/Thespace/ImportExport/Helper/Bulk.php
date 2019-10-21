<?php

class Thespace_ImportExport_Helper_Bulk extends Mage_Core_Helper_Abstract
{
    
    public function getSkuCategories()
    {
        $resource = \Mage::getSingleton('core/resource');
        $readConnection = $resource->getConnection('core_read');
        
        $ccp = $resource->getTableName('catalog_category_product');
        $cpe = $resource->getTableName('catalog_product_entity');
        
        $results = $readConnection->fetchAll("SELECT sku, category_id FROM $ccp INNER JOIN $cpe ON product_id = entity_id");
        
        $skuCategories = [];
        foreach ($results as $result) {
            $sku = $result['sku'];
            $categoryId = $result['category_id'];
            if (!isset($skuCategories[$sku])) {
                $skuCategories[$sku] = [];
            }
            $skuCategories[$sku][] = $categoryId;
        }
        
        
        return $skuCategories;
    }
    
}