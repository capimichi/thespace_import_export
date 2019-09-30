<?php
require_once 'abstract.php';

class Thespace_Import_Export_Cron_Import extends Mage_Shell_Abstract
{
    protected $_argname = [];
    
    public function __construct()
    {
        parent::__construct();
        
        // Time limit to infinity
        set_time_limit(0);
        
        // Get command line argument named "argname"
        // Accepts multiple values (comma separated)
        if ($this->getArg('argname')) {
            $this->_argname = array_merge(
                $this->_argname,
                array_map(
                    'trim',
                    explode(',', $this->getArg('argname'))
                )
            );
        }
    }
    
    // Shell script point of entry
    public function run()
    {
        $todoDirectory = implode(DIRECTORY_SEPARATOR, [
                \Mage::getBaseDir('media'),
                "thespace-import-export",
                "cron",
                "todo",
                "",
            ]) . DIRECTORY_SEPARATOR;
        if (!file_exists($todoDirectory)) {
            mkdir($todoDirectory, 0777, true);
        }
        
        $doneDirectory = implode(DIRECTORY_SEPARATOR, [
                \Mage::getBaseDir('media'),
                "thespace-import-export",
                "cron",
                "done",
                "",
            ]) . DIRECTORY_SEPARATOR;
        if (!file_exists($doneDirectory)) {
            mkdir($doneDirectory, 0777, true);
        }
        
        if (file_exists($todoDirectory)) {
            $todoFiles = array_diff(scandir($todoDirectory), ['..', '.']);
            
            foreach ($todoFiles as $todoFile) {
                
                $todoFilePath = $todoDirectory . $todoFile;
                
                if (file_exists($todoFilePath)) {
                
                }
            }
        }
    }
    
    // Usage instructions
    public function usageHelp()
    {
        return <<<USAGE
Usage:  php -f scriptname.php -- [options]
 
  --argname <argvalue>       Argument description
 
  help                   This help
 
USAGE;
    }
}

// Instantiate
$shell = new Thespace_Import_Export_Cron_Import();

// Initiate script
$shell->run();
