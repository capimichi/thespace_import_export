<?php

class Thespace_ImportExport_Helper_Slug extends Mage_Core_Helper_Abstract
{
    
    /**
     * @author Michele Capicchioni <capimichi@gmail.com>
     *
     * @param $string
     *
     * @return null|string|string[]
     */
    public function getSlug($string)
    {
        // replace non letter or digits by -
        $string = preg_replace('~[^\pL\d]+~u', '-', $string);
        
        // transliterate
        $string = iconv('utf-8', 'us-ascii//TRANSLIT', $string);
        
        // remove unwanted characters
        $string = preg_replace('~[^-\w]+~', '', $string);
        
        // trim
        $string = trim($string, '-');
        
        // remove duplicate -
        $string = preg_replace('~-+~', '-', $string);
        
        // lowercase
        $string = strtolower($string);
        
        if (empty($string)) {
            return 'n-a';
        }
        
        return $string;
    }
}