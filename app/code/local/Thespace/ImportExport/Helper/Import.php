<?php

/**
 * Created by PhpStorm.
 * User: michelecapicchioni
 * Date: 11/12/18
 * Time: 16:39
 */
class Thespace_ImportExport_Helper_Import extends Mage_Core_Helper_Abstract
{
    public function groupImportItems($dataItems, $size = 500)
    {
        $dataGroups = [];
        $confItems = [];
        $simpleItems = [];
        
        foreach ($dataItems as $dataItem) {
            if (isset($dataItem['_type']) && $dataItem['_type'] == 'configurable') {
                $confItems[] = $dataItem;
            } else {
                $simpleItems[] = $dataItem;
            }
        }
        
        foreach (array_chunk($simpleItems, $size) as $simpleItemGroup) {
            $dataGroups[] = $simpleItemGroup;
        }
        
        foreach (array_chunk($confItems, $size) as $confItemGroup) {
            $dataGroups[] = $confItemGroup;
        }
        
        return $dataGroups;
    }
}