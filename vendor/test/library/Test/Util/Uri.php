<?php

namespace Test\Util;

use Test\Util\Timer;

class Uri {
	
	static $formated = array();
	
	static function makeUriFromArray(array $array, $encode = true) {
		$seperator = '&';
		if ($encode) {
			$seperator = '&amp;';
		}

		$array = array_map('trim', $array);
		$array = array_filter($array, array(self,'_array_filter'));
		
		return http_build_query($array, '', $seperator);
	}
	
	static function _array_filter($var) {
		return ($var !== '');
	}
	
	static function formatString($string, $url = false) {

        if (is_int($string)){
            return $string;
        }
		if (empty($string) || trim($string) == '') {
			return '';
		}
		
		Timer::start(__METHOD__);
		
		$key = $string . (int)$url;
		
		if (isset(self::$formated[$key])) {
			return self::$formated[$key];
		}
		
		$string = trim(strip_tags($string));
		$string = preg_replace('/&#[\d\w]{1,8};/', '', $string);
		
		if (!$url) {
			// % is a special char for url
			$string = str_replace('%', '-', $string);
		}
		
		$string = preg_replace('/[\-]{2,}/', '-', $string);
		
		$string = trim($string, '.');
		
		self::$formated[$key] = $string;
		
		Timer::end(__METHOD__);
		
		return $string;
	}
	
// 	static function addQuery($url, $params) {
//    		$query = array();
   		
// 		foreach ($params as $key => $value) {
// 			$query[] = $key . '=' . urlencode($value);
// 		}
			
// 		if ($query) {
				
// 			if (false !== strpos($url, '?')) {
// 				$url .= '&';
// 			} else {
// 				$url .= '?';
// 			}
				
// 			$url .= implode('&', $query);
// 		}
		
// 		return $url;
//     }
}
