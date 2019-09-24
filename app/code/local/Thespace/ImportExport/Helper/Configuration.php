<?php

class Thespace_ImportExport_Helper_Configuration extends Mage_Core_Helper_Abstract
{
    
    const OPTION_DEFAULT_ATTRIBUTE_SET    = 'thespace_import_export/config/default_attribute_set';
    const OPTION_DEFAULT_PRODUCT_WEBSITES = 'thespace_import_export/config/default_product_websites';
    const OPTION_DEFAULT_TAX_CLASS_ID     = 'thespace_import_export/config/default_tax_class_id';
    
    const DEFAULT_CONFIGURATION_OPTIONS = [
        self::OPTION_DEFAULT_ATTRIBUTE_SET,
        self::OPTION_DEFAULT_PRODUCT_WEBSITES,
        self::OPTION_DEFAULT_TAX_CLASS_ID,
    ];
    
    public function get($key)
    {
        return Mage::getStoreConfig($key);
    }
    
    public function set($key, $value)
    {
        Mage::getConfig()->saveConfig($key, $value);
    }
}