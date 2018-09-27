<?php

namespace Test\Util;

class Common {
    
    
    public static function readFile($filename) {
        $fp = @fopen($filename, 'rb');
        if ($fp) {
            $data = '';
            flock($fp, LOCK_SH);
            clearstatcache();
            $filesize = filesize($filename);
            if ($filesize > 0) {
                $data = fread($fp, $filesize);
            }
            flock($fp, LOCK_UN);
            fclose($fp);
            return $data;
        } else {
            return false;
        }
    }
    
    public static function writeFile($filename, $content, $mode = 'wb') {
        $fp = @fopen($filename, $mode);
        if ($fp) {
            flock($fp, LOCK_EX);
            fwrite($fp, $content);
            flock($fp, LOCK_UN);
            fclose($fp);
            return true;
        } else {
            return false;
        }
    }
    
    public static function getRemoteIp() {
    	$ip = '';
    	if (isset($_SERVER["HTTP_RLNCLIENTIPADDR"])) {
    		$ip = $_SERVER["HTTP_RLNCLIENTIPADDR"];
    	} else if(!empty($_SERVER['HTTP_CLIENT_IP'])){
    		$ip = $_SERVER['HTTP_CLIENT_IP'];
    	} else if(!empty($_SERVER['HTTP_X_FORWARDED_FOR'])){
    		foreach ((strstr($_SERVER['HTTP_X_FORWARDED_FOR'],',') ? split(',',$_SERVER['HTTP_X_FORWARDED_FOR']) : array($_SERVER['HTTP_X_FORWARDED_FOR'])) as $remote_ip){
    			if(strtolower($remote_ip) == 'unknown') {
    				continue;
    			}
    			if(preg_match('/^((25[0-5]|2[0-4][0-9]|1[0-9][0-9]|[1-9][0-9]|[0-9])\.){3}(25[0-5]|2[0-4][0-9]|1[0-9][0-9]|[1-9][0-9]|[0-9])$/', $remote_ip) ||
    					preg_match('/^([0-9a-fA-F]{4}|0)(\:([0-9a-fA-F]{4}|0)){7}$/', $remote_ip)
    			){
    				$ip = $remote_ip;
    				break;
    			}
    		}
    	}
    
    	if (empty($ip) && !empty($_SERVER['REMOTE_ADDR'])) {
    		$ip = $_SERVER['REMOTE_ADDR'];
    	}
    	return $ip;
    }
    
    public static function mb_ucfirst($str){
    	mb_internal_encoding('UTF-8');
    	$c = mb_substr($str,0,1);
    	$end = mb_substr($str,1);
    	
    	return strtoupper($c) . $end ;
    }

    public static function queryFilter($query) {
    	$query = mb_substr(trim(strtolower(str_replace(
    			array('|', '_', '-', '<', '>')
    			,array('',  ' ', ' ', ' ', ' ')
    			,strip_tags($query))
    	)),0,299,"UTF-8");
    	$query = preg_replace('/\s{2,}/',' ',$query);
    	return $query;
    }
}