<?php
namespace Application;

use Test\Data;
use Test\Util\Common;

class Exception extends \Exception {
    
    protected static $config = null;
    
    public static function getException($exception) {
        $msg = "EXCEPTION '" . __CLASS__ . "' WITH MESSAGE '" . $exception->getMessage() . "'"
            . " HTTP_REFERER:'" . $_SERVER['HTTP_REFERER'] . "'"
            . " REQUEST_URI:'" . $_SERVER['REQUEST_URI'] . "'"
            . " IP:'" . Common::getRemoteIp() . "'"
            . " USER_AGENT:'" . $_SERVER['HTTP_USER_AGENT'] . "'"
            . " IN " . $exception->getFile() . ":" . $exception->getLine() . "\n"
            . " STACK TRACE:\n" . $exception->getTraceAsString()
            . "";
        	
        return $msg;
    }
    
    static function log($exception, $display = false) {
    
        $msg = self::getException($exception);
        if ($display) {
            return $msg;
        } else {
            Service\Logger::msg($msg);
        }
    }
    
    static function loadConfig() {
        if (null === self::$config) {
            $data = Data::getInstance();
            if ($data->has('config')) {
                $config = $data->get('config');
            } else {
                $config = include ROOT_PATH . "/module/Application/config/config." . APPLICATION_ENV . ".php";
            }
            self::$config = $config;
        }
    
        return self::$config;
    }
    
    static function getReportEmail() {
    
        $config = self::loadConfig();
    
        $email = $config['errorReport'];
        $emails = explode('|', $email);
    
        return $emails;
    }
    
    static function mailError($exception) {
    	
    	$config = self::loadConfig();

    	$emails = self::getReportEmail();
    	
    	$emailTimeZone = $config['log']['emailTimeZone'];
        $date = new \DateTime($emailTimeZone);
        $curTime = $date->format('Y-m-d H:i:s');
        
        $server = $_SERVER['SERVER_NAME'];
        
        $errorMsg = substr($exception->getMessage(), 0, 100);
       	
        if ($config['log']['email']) {
    
            $key = md5($errorMsg);
            $logFile = Util\Util::getWritableDir('log') . 'mail';
        	
            // old than 1 day
            if (file_exists($logFile) && (filemtime($logFile) + 1) < time()) {
                @unlink($logFile);
            }
            	
            if (!file_exists($logFile)) {
                @touch($logFile);
            }
            	
            // check log
            $log = file($logFile);
            	
            if (in_array($key, $log)) {
                return;
            } else {
                $log[] = $key;
                // add to log
                $dh = fopen($logFile, 'w');
                fwrite($dh, implode("\n",$log));
                fclose($dh);
            }
    
        }
    
        $subject = '[URGENT ISSUE] ' . $curTime . ' | ' . $server .' | '. $errorMsg;
        $body = self::getException($exception);
        
        Service\Mail::mail($emails, $subject, $body);
    }
}