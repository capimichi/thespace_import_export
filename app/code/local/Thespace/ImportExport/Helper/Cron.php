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
            'year'   => $parts[0],
            'month'  => $parts[1],
            'day'    => $parts[2],
            'hour'   => $parts[3],
            'minute' => $parts[4],
        ];
    }
}