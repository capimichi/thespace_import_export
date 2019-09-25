<?php

class Thespace_ImportExport_Helper_CategoryParser extends Mage_Core_Helper_Abstract
{
    const NAME_KEY = "nome_categorie";
    const SKU_KEY  = "riferimento";
    
    
    public function getRowHeader()
    {
        $headers = [
            Thespace_ImportExport_Helper_ProductRow::SKU_KEY,
            Thespace_ImportExport_Helper_ProductRow::CATEGORY_KEY,
        ];
        
        return $headers;
    }
    
    public function getRowHeaders()
    {
        $row = [
            'id',
            'path',
            'name',
        ];
        
        return $row;
    }
    
    public function categoryToRow($category, $categoryNames = null)
    {
        $row = [
            $category->getId(),
            $this->getCategoryPathName($category, $categoryNames),
            $category->getName(),
        ];
        
        return $row;
    }
    
    /**
     * @author Michele Capicchioni <capimichi@gmail.com>
     *
     * @return array
     */
    public function categoryNames()
    {
        $categories = Mage::getModel('catalog/category')->getCollection()
            ->addAttributeToSelect('*');
        
        $categoryNames = [];
        foreach ($categories as $category) {
//            $categoryNames[$category->getId()] = str_replace("/", "\/", $category->getName());
            $categoryNames[$category->getId()] = $category->getName();
        }
        
        return $categoryNames;
    }
    
    public function getCategoryPathName($category, $categoryNames = null)
    {
        $path = $category->getPath();
        $path = explode('/', $path);
        
        $path = array_map(function ($item) use ($categoryNames) {
            if (!is_array($categoryNames) || !isset($categoryNames[$item])) {
                $category = Mage::getModel('catalog/category')->load($item);
                $item = str_replace("/", "\/", $category->getName());
            } else {
                $item = $categoryNames[$item];
            }
            return $item;
        }, $path);
        array_shift($path);
        $path = implode("/", $path);
        
        return $path;
    }
}