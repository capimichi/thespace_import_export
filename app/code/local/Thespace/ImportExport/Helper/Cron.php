<?php

class Thespace_ImportExport_Helper_Cron extends Mage_Core_Helper_Abstract
{
    
    /**
     * @author Michele Capicchioni <capimichi@gmail.com>
     *
     * @param $file
     *
     * @return array
     */
    public function getFileExecution($file)
    {
        $name = basename($file);
        $parts = explode("-", $name);
        
        return [
            'Y' => $parts[0],
            'M' => $parts[1],
            'd' => $parts[2],
            'H' => $parts[3],
            'i' => $parts[4],
        ];
    }
    
    /**
     * @author Michele Capicchioni <capimichi@gmail.com>
     *
     * @param $file
     *
     * @return bool
     */
    public function isExecutable($file)
    {
        $fileExecutionDate = $this->getFileExecution($file);
        $now = new DateTime();
        
        $executable = true;
        foreach ($fileExecutionDate as $key => $value) {
            if (intval($now->format($key)) != intval($value)) {
                $executable = false;
            }
        }
        
        return $executable;
        
        
    }
}