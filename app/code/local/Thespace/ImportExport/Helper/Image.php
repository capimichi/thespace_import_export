<?php

/**
 * Created by PhpStorm.
 * User: michelecapicchioni
 * Date: 11/12/18
 * Time: 16:39
 */
class Thespace_ImportExport_Helper_Image extends Mage_Core_Helper_Abstract
{
    
    public function isImageUrl($path)
    {
        
        return !empty(parse_url($path, PHP_URL_SCHEME));
    }
    
    public function storeImage($path)
    {
        $mediaImportPath = null;
        $imageContent = null;
        $baseName = null;
        
        $mediaImportDir = Mage::getBaseDir('media') . DIRECTORY_SEPARATOR . 'import' . DIRECTORY_SEPARATOR;
        
        if (self::isImageUrl($path)) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $path);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/60.0.3112.113 Safari/537.36	');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
            $imageContent = curl_exec($ch);
            curl_close($ch);
            $baseName = basename(parse_url($path, PHP_URL_PATH));
        } else {
            
            if (file_exists($path)) {
                $baseName = basename($path);
                $imageContent = file_get_contents($path);
            }
            
            if (file_exists($mediaImportDir . $path)) {
                $mediaImportPath = $mediaImportDir . $path;
                $imageContent = null;
            }
        }
        
        if ($imageContent) {
            
            $tempImagePath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . md5(rand(0, 99999999999) . rand(0, 99999999999));
            file_put_contents($tempImagePath, $imageContent);
            
            $ext = pathinfo($tempImagePath, PATHINFO_EXTENSION);
            
            if (empty($ext)) {
                switch (true) {
                    case preg_match("/\.png/is", $baseName):
                        $ext = 'png';
                        break;
                    case preg_match("/\.jpg/is", $baseName):
                    case preg_match("/\.jpeg/is", $baseName):
                        $ext = 'jpg';
                        break;
                    case preg_match("/\.gif/is", $baseName):
                        $ext = 'gif';
                        break;
                    case preg_match("/\.bmp/is", $baseName):
                        $ext = 'gif';
                        break;
                    default:
                        $ext = 'png';
                        break;
                }
            }
            
            $imageName = implode("-", [
                $baseName,
                substr(md5($imageContent), 0, 5),
                sprintf(".%s", $ext),
            ]);
            
            if (!file_exists($mediaImportDir)) {
                mkdir($mediaImportDir, 0777, true);
            }
            $mediaImportPath = implode(DIRECTORY_SEPARATOR, [
                $mediaImportDir,
                $imageName,
            ]);
            
            copy($tempImagePath, $mediaImportPath);
            unlink($tempImagePath);
        }
        
        return $mediaImportPath;
    }
    
}