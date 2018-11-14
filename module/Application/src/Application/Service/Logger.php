<?php
namespace Application\Service;

use Zend\Log;

use Test\Data;

use Application\Util\Util;

class Logger {
    
    protected static $enabled = false;
    
    protected static $logger = null;
    
    public static function getInstance() {
        if (null == self::$logger) {
            $config = Data::getInstance()->get('config');
            self::$enabled = $config['log']['enabled'];
            if (!self::$enabled) {
                return null;
            }
            $file = Util::getWritableDir('log') .date('Ymd')."-". $config['log']['file'];
            $backup = $file . date('YmdHi') . '_bak';
            if((file_exists($file) && !is_writable($file))
                || (20480000 < filesize($file))){
                rename($file, $backup);
            }
            
            $logger = new Log\Logger;
            $writer = new Log\Writer\Stream($file);
            
            $logger->addWriter($writer);
            self::$logger = $logger;
        }
    
        return self::$logger;
    }
    
    public static function msg($msg, $level = 'info') {
    
        if (null == self::$logger) {
            self::$logger = self::getInstance();
        }
    
        if (!self::$logger || !self::$enabled) {
            return null;
        }
    
        $msg .= "\n================================\n\n";
    
        self::$logger->{$level}($msg);
    }
}