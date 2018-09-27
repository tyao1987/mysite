<?php
namespace Application\Util;

use Test\Data;
use Test\Util\Timer;
use Application\Exception;
class Util {
    
    protected static $writableDir;
    
    protected static $viewhelpermanager;
    
    protected static $imageServer = null;
    
    static function getWritableDir($name) {
    
    	if (isset(self::$writableDir[$name])) {
    		return self::$writableDir[$name];
    	}
    
    	$config = Data::getInstance()->get('config');
    	
    	if (!isset($config['writableDir'][$name])) {
    		throw new Exception('Writable Dir ' . $name . ' not found');
    	}
    
    	self::$writableDir[$name] = $config['writableDir']['base'] . $config['writableDir'][$name];
    	return self::$writableDir[$name];
    }
    
    
    
    public static function makeCacheKey($key, $addSiteId = true) {
    	
    	if (is_array($key)) {
    		natsort($key);
    	}
    
    	$key = serialize($key);
    	$key = preg_replace('/[^a-zA-Z0-9_]/', '', $key);
    	
    	$site = Data::getInstance()->get('site');
   		
    	// add site id
    	if ($addSiteId) {
    		$key .= $site['site_id'];
    	}
    
    	
    	if (strlen($key) > 32) {
    		$key = md5($key);
    	}
    
    	return $key;
    }
    
    public static function setViewhelperManager($manager){
    	self::$viewhelpermanager = $manager;
    }
    
    public static function getViewHelper($viewhelperName){
    	if(self::$viewhelpermanager == null){
    		throw new Exception('view helper manager has not been added to Util');
    	}else{
    		return self::$viewhelpermanager->get($viewhelperName);
    	}
    }
    
    public static function getImg($string) {
    	
    	Timer::start(__METHOD__);
    	if(substr($string, 0, 1) !== '/') {
    		$string = '/' . $string;
    	}
    
    	if (null == self::$imageServer) {
    		$config = Data::getInstance()->get('config');
    		self::$imageServer = $config['imageServer'];
    	}
    	Timer::end(__METHOD__);
    
    	return self::$imageServer . $string;
    }
    
    /**
     * 替换内容中的变量
     *
     * @param string $str
     * @param array $variables
     * @return string
     */
    public static function replaceVariables($str, $variables = array()) {
    
    	// get all variables
    	preg_match_all('/\${(?P<key>[_\w]+)}/i', $str, $matches);
    
    	if (isset($matches['key'])) {
    		$keys = $matches['key'];
    
    		$len = count($keys);
    		for ($i=0; $i<$len; $i++) {
    			// clear unhandled variables
    			if (!isset($variables[$keys[$i]])) {
    				$variables[$keys[$i]] = '';
    			}
    			// replace variables
    			$str = str_ireplace('${' . $keys[$i] . '}', $variables[$keys[$i]], $str);
    		}
    	}
    
    	return $str;
    }
    
    
    static function formatString($string) {
    	return str_replace('"', '', $string);
    }
    
    
    /**
     * @return array 获取所有偏好设置
     */
    public static function getPrefs() {
    	if(Data::getInstance()->has('cookiesPrefs')) {
    		$ret = Data::getInstance()->get('cookiesPrefs');
    	}else {
    
    		$ret = array();
    		if(array_key_exists('prefs', $_COOKIE)) {
    			$prefs = $_COOKIE['prefs'];
    			$ret = array();
    
    			$arr = explode('|||', $prefs);
    			foreach ($arr as $item) {
    				$tmp = explode('#', $item);
    				$key = $tmp[0];
    				$value = $tmp[1];
    					
    				$ret[$key] = $value;
    			}
    		}
    		Data::getInstance()->set('cookiesPrefs', $ret);
    	}
    	return $ret;
    }
    
    /**
     * 批量设置偏好信息
     *
     * @param array $params 偏好设置
     * @return boolean
     */
    public static function setPrefs(array $params = array()) {
    
    	$prefs = self::getPrefs();
    	foreach ($params as $key => $value) {
    		$prefs[$key] = $value;
    	}
    	$data = '';
    	foreach ($prefs as $key => $value) {
    		$delimiter = $data === '' ? '' : '|||';
    		$data .= $delimiter . $key . '#' . $value;
    	}
    	
    	$dateTime = new \DateTime();
    	$currentTimeStamp = $dateTime->getTimestamp();
    	
    	return setcookie('prefs', $data, $currentTimeStamp + 60 * 60 * 24 * 365, '/');
    }
}