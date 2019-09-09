<?php

class Thespace_ImportExport_Helper_Sku extends Mage_Core_Helper_Abstract
{
    
    /**
     * @author Michele Capicchioni <capimichi@gmail.com>
     *
     * @return array
     */
    public static function getExistingSkus()
    {
        
        $resource = \Mage::getSingleton('core/resource');
        $readConnection = $resource->getConnection('core_read');
        $table = $resource->getTableName('catalog/product');
        $existingSkus = $readConnection->fetchCol("SELECT sku FROM " . $table);
        
        return $existingSkus;
    }
    
}