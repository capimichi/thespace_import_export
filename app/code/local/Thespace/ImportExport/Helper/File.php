<?php

class Thespace_ImportExport_Helper_File extends Mage_Core_Helper_Abstract
{
    const NAME_KEY = "nome_categorie";
    const SKU_KEY  = "riferimento";
    
    /**
     * @author Michele Capicchioni <capimichi@gmail.com>
     *
     * @param string $name
     * @param string $format
     *
     * @return string
     */
    public function getImportFile($name = "", $format = "csv")
    {
        $now = new DateTime();
        $importDirectory = implode(DIRECTORY_SEPARATOR, [
                \Mage::getBaseDir('media'),
                "thespace-import-export",
                "csv",
                $now->format('Y'),
                $now->format('m'),
                $now->format('d'),
            ]) . DIRECTORY_SEPARATOR;
        
        $importFile = $importDirectory . implode("-", [
                $now->format("Y-m-d-H-i-s"),
                sprintf("%s.%s", $name, $format),
            ]);
        
        return $importFile;
    }
}