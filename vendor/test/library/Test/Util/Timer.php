<?php

namespace Test\Util;

class Timer {
	
	static $timeList = array();
	
	static function start($name) {
		if (!isset(self::$timeList[$name])) {
			self::$timeList[$name] = array();
		}
		self::$timeList[$name][count(self::$timeList[$name])]['S'] = array(
			'TIME'	=> microtime(true),
			'MEM'	=> memory_get_usage(),
		);
	}
	
	static function end($name) {
		if (!isset(self::$timeList[$name])) {
			self::$timeList[$name] = array();
		}
		self::$timeList[$name][count(self::$timeList[$name]) - 1]['E'] = array(
			'TIME'	=> microtime(true),
			'MEM'	=> memory_get_usage(),
		);
	}
	
	static function total() {
		if (!isset(self::$timeList['ALL'])) {
			return 0;
		}
		
		$total = self::$timeList['ALL'][0];
		$cost = $total['E']['TIME'] - $total['S']['TIME'];
		return round($cost, 0);
	}
	
	static function show($format = true) {
		
		if ($format) {
		
			$css = '
			<style type="text/css">
			ul.timer, ul.timer ol, ul.timer li {margin-top:10px;font:12px,sans-serif;font-weight:bold;list-style:none;padding-left:10px;margin-left:10px;line-height:150%;}
			ul.timer ol li {font-weight:normal;margin-top:3px;}
			</style>
			';
			$result = $css . '<ul class="timer">';
			foreach (self::$timeList as $name => $times) {
				$count = count($times);
				$timeSpend = 0;
				$memorySpend = 0;
				$child = '';
				foreach ($times as $time) {
					if (isset($time['S']['TIME']) && isset($time['E']['TIME'])) {
						$spend = $time['E']['TIME'] - $time['S']['TIME'];
						$mem = ($time['E']['MEM'] - $time['S']['MEM']) / 1024;
						//$child .= "<li>TIME : {$spend} ||| MEM : {$mem} KB</li>";
						$timeSpend += $spend;
						$memorySpend += $mem;
					}
				}
				$result .= "<li>{$name} ({$count}) : {$timeSpend} - {$memorySpend} KB<ol>";
				$result .= $child;
				$result .= "</ol></li>";
			}
			$result .= '</ul>';
			
			return $result;
		
		} else {
			
			$result = '';
			
			foreach (self::$timeList as $name => $times) {
				$count = count($times);
				$timeSpend = 0;
				$memorySpend = 0;
				$child = '';
				foreach ($times as $time) {
					if (isset($time['S']['TIME']) && isset($time['E']['TIME'])) {
						$spend = $time['E']['TIME'] - $time['S']['TIME'];
						$mem = ($time['E']['MEM'] - $time['S']['MEM']) / 1024;
						//$child .= "\t{$spend}\n";
						$timeSpend += $spend;
						$memorySpend += $mem;
					}
				}
				$result .= "{$name} ({$count}) : {$timeSpend} - {$memorySpend} KB\n";
				$result .= $child;
				$result .= "";
			}
			
			return $result;
			
		}
	}
}

		