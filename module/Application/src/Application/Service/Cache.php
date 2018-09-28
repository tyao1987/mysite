<?php

namespace Application\Service;

use Zend\Cache\StorageFactory;

class Cache{
    
    /**
     * @var Cache
     */
	protected static $instance;
	
	/**
	 * @var array Cache 配置信息
	 */
	protected $config;
	
	/**
	 * @var array Cache 存储实例系列
	 */
	protected $storages = array();
	
	function __construct(){
		$this->config = require ROOT_PATH . '/config/cache/' . APPLICATION_ENV . '.php';
	}
	
	/**
	 * 获取 cache 实例
	 * 
	 * @param string $cacheName
	 * @return \Zend\Cache\Storage\StorageInterface
	 */
	static public function get($cacheName){
		if(!(self::$instance instanceof self)){
			self::$instance = new self;
		}
		return self::$instance->_getCacheStorage($cacheName);
	}
	
	protected function _getCacheStorage($cacheName){
		// 保证 cacheStorage 对象不重复构造
		if(!isset($this->storages[$cacheName])) {
    		$cacheConfig = $this->config[$cacheName];
    		$cache = StorageFactory::factory(array(
    		    'adapter' => array(
    		        'name'    => $cacheConfig['name'],
    		        'options' => $cacheConfig['options'],
    			),
    		    'plugins' => array(
    		        'exception_handler' => array('throw_exceptions' => false),
    		        'Serializer',
    		    ),
    		));
    		$this->storages[$cacheName] = $cache;
		}
		return $this->storages[$cacheName];
	}
}