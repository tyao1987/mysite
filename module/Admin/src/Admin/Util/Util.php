<?php

namespace Admin\Util;

use Test\Data;
use Admin\Model\Auth;
use Admin\Model\User;
class Util {

    protected static $cmsWritableDir;
	/**
	 * returns a randomly generated string
	 * commonly used for password generation
	 *
	 * @param int $length
	 * @return string
	 */
	static function random($length = 8) {
		// start with a blank string
		$string = "";

		// define possible characters
		$possible = "0123456789abcdfghjkmnpqrstvwxyz";

		// set up a counter
		$i = 0;

		// add random characters to $string until $length is reached
		while ( $i < $length ) {

			// pick a random character from the possible ones
			$char = substr ( $possible, mt_rand ( 0, strlen ( $possible ) - 1 ), 1 );

			// we don't want this character if it's already in the string
			if (! strstr ( $string, $char )) {
				$string .= $char;
				$i ++;
			}
		}

		return $string;
	}
	static function getCmsWritableDir($name) {

	    if (isset(self::$cmsWritableDir[$name])) {
	        return self::$cmsWritableDir[$name];
	    }

	    $config = Data::getInstance()->get('config');

	    if (!isset($config['cmsWritableDir'][$name])) {
	        throw new \Exception('Writable Dir ' . $name . ' not found');
	    }

	    self::$cmsWritableDir[$name] = $config['cmsWritableDir']['base'] . $config['cmsWritableDir'][$name];
	    return self::$cmsWritableDir[$name];
	}
	static function clearAkamai($path){

		if (APPLICATION_ENV != 'production') {
			return;
		}

		$config = Data::getInstance()->get('config');
		$host = $config['cmsHost'];

		$path = base64_encode($path);

		$cmsHost = $config['cmsHost'];
		if(strpos($cmsHost, "http") === false){
			$cmsHost ="http://".$cmsHost;
		}
		$cmsHost = rtrim($cmsHost,"/");

		$url = "{$cmsHost}/akamai/clear?path=".$path;

		$domain = $_SERVER['SERVER_NAME'];
		$httpHeader = array( "HOST: $domain" );

		$curlHandler = curl_init();
		curl_setopt($curlHandler, CURLOPT_URL, $url);
		curl_setopt($curlHandler, CURLOPT_HEADER, false);
		curl_setopt($curlHandler, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curlHandler, CURLOPT_HTTPHEADER, $httpHeader);
		//curl_setopt($curlHandler, CURLOPT_TIMEOUT, $this->_timeout);
		curl_setopt($curlHandler, CURLOPT_FOLLOWLOCATION,true);
		$xml = curl_exec($curlHandler);
		$error = curl_error($curlHandler);
		curl_close($curlHandler);
	}
	
	static function stripUnderscores($string, $relative = false)
	{
		$string = str_replace('_', '/', trim($string));
		if($relative)
		{
			$string = self::stripLeading('/', $string);
		}
		return $string;
	}
	
	/**
	 * strips the leading $replace from the $string
	 *
	 * @param string $replace
	 * @param string $string
	 * @return string
	 */
	static function stripLeading($replace, $string)
	{
		if(substr($string, 0, strlen($replace)) == $replace)
		{
			return substr($string, strlen($replace));
		}else{
			return $string;
		}
	}
	
	static function addHyphens($string)
	{
		return str_replace(' ', '-', trim($string));
	}
	
	static function safe_file_put_contents($filename, $content, $mode = 'wb')
	{
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
	static function checkActionPermission($resource, $userAclResources) {
	    $user = Auth::getIdentity();
	    if ($user['id'] == User::SUPERUSER_ROLE) return true;
	    if ($user['id'] != User::SUPERUSER_ROLE && $resource == 'admin_index_log') {
	    	return false;
	    }
	    if(array_key_exists($resource, $userAclResources) && 1 == $userAclResources[$resource]) {
	        return true;
	    }
	    foreach ($userAclResources as $k=>$v) {
	        if ($v != 1) continue;
	        if ($k == substr($resource, 0, strlen($k))) {
	            return true;
	        }
	    }
	    return false;
	}
	static function getBreadcrumbs($current, $nav) {
	    $bread = array();
	    if (!$current instanceof \Zend\Navigation\Page\AbstractPage) {
	        return ;
	    }
	    $bread[] = $current;
	    if ($current->getParent() instanceof \Zend\Navigation\Page\AbstractPage) {
	        return array_merge(self::getBreadcrumbs($current->getParent(), $nav), $bread);
	    }
	    return $bread;
	}
// 	static function filterSideBar($items, $resources) {
// 	    $user = Auth::getIdentity();
// 	    if ($user['id'] == User::SUPERUSER_ROLE) return $items;
// 	    $keys = array_keys($items);
// 	    foreach ($keys as $resource) {
// 	        if (!self::checkActionPermission($resource, $resources)) {
// 	            unset($items[$resource]);
// 	        }
// 	    }
// 	    return $items;
// 	}
}

?>