<?php
namespace Application\Service;

use Test\Util\Timer;
use Test\Data;
use Test\Util\Common;

use Application\Model\ArticleCms;
use Application\Service\Cache;
use Application\Model\SiteSettingTable;
use Application\Model\TranslationTable;
use Application\Util\Util;
use Zend\Config\Reader\Xml;
use Zend\Session\Container;
use Zend\Session\SessionManager;
use Zend\Cache\StorageFactory;
use Zend\Session\SaveHandler\Cache as SessionCache;
use Admin\Model\Sites;
class Resource {
    
    /**
     * @var array
     */
    protected static $sites = null;
    
    /**
     * 加载站点信息
     * 
     * @param string $hostname
     * @return array
     */
    static public function loadSite($domain) {
		
        Timer::start(__METHOD__);
        
        if (null === self::$sites) {
        	$siteCms = new Sites();
        	$site = $siteCms->getSite($domain);
        	if($site){
        		$siteConfig = self::$sites = $site;
        	}else{
        		throw new \Exception('Site not found:'.$domain);
        	}
        }
        
        Timer::end(__METHOD__);
        
        return $siteConfig;
    }
    
    /**
     * 加载 Site settings
     *
     * 可以根据 site domain 加载，同时 white label 站点有些设置需要用对应国家的主站设置覆盖
     */
    static public function loadSiteSetting($domain = null) {
    
    	Timer::start(__METHOD__);
    
    	$cache = Cache::get("dynamicCache");
    	$config = Data::getInstance()->get("config");
    	if(!$domain){
    		$site = Data::getInstance()->get('site');
    		$cacheKey = Util::makeCacheKey('siteSetting_').$site['hostname'];
    	}else{
    		$site = self::loadSite($domain);
    		$cacheKey = Util::makeCacheKey('siteSetting_'.$domain);
    	}
    	$settings = $cache->getItem($cacheKey);
    	if (!$settings) {
    		$dataCacheFile = Util::getWritableDir('dataCache') . 'siteSettings.serialized';
    		if (file_exists($dataCacheFile)) {
    			$siteSettings = unserialize(Common::readFile($dataCacheFile));
    			$settings = (!empty($siteSettings[$site['hostname']]))?$siteSettings[$site['hostname']]:null;
    		} else {
    			$siteSetting = new SiteSettingTable();
    			$siteSettingXml = $siteSetting->getSiteSetting($site['site_id']);
    			$reader = new Xml();
    			$settings = $reader->fromString($siteSettingXml);
    			foreach ($settings as $key=>$value){
    				$settings [$key] = ($value === array())?"":$value;
    			}
    		}
    		if (!$settings) {
    			$settings = array();
    		}
    
    		$defaultSettings = require ROOT_PATH . '/module/Application/data/site-settings/mainsite-default-settings.php';
    		//if ($site['site_type'] == 'DistributionSite') {
    			//$tmp = require ROOT_PATH . '/module/Shopping/data/site-settings/whitelabel-default-settings.php';
    			//$defaultSettings = array_merge($defaultSettings, $tmp);
    		//}
    		
    		$settings = array_merge($defaultSettings, (array)$settings); // merge with default settings
    
    		$cache->addItem($cacheKey, $settings);
    	}
    	Timer::end(__METHOD__);
    	return $settings;
    }
	
    static public function loadSession() {
    	
        Timer::start(__METHOD__);
        
    	$manager = new SessionManager();
    	
    	$config = require ROOT_PATH.'/module/Application/config/session.'.APPLICATION_ENV.'.php';
    	$handler = $config['handler'];
    	$cacheStorage = StorageFactory::factory(array(
    		'adapter' => $config[$handler]['config'],
    		'plugins' => array(
    			'exception_handler' => array('throw_exceptions' => false),
    		),
    		'use_cookies' => true,
    	));
    	if(!$cacheStorage->setItem('testSession', 'testSession')){
    		throw new \Exception('Memcache not avaliable');
    	}
    	$cacheSaveHandler = new SessionCache($cacheStorage);
    	$manager->setSaveHandler($cacheSaveHandler);
    	$manager->start();
    	\Zend\Session\Container::setDefaultManager($manager);
    	
    	Timer::end(__METHOD__);
    }
    
    /**
     * 加载 translation keys
     *
     * @param string $locale
     * @return array
     */
    static function loadTranslations($locale) {
    
    	Timer::start(__METHOD__);
    
    	$config = Data::getInstance()->get('config');
    	$mapping = $config['languageMapping'];
    
    	$cacheFile = Util::getWritableDir('dataCache') . '/language/' . $mapping[$locale] . '.php';
    	$translate = new TranslationTable();
    	$translations = $translate->getLang($mapping[$locale]);
    	if (file_exists($cacheFile)) {
    		$translations = require $cacheFile;
    	} else {
    		$cache = Cache::get("dynamicCache");
    		 
    		$cacheKey = Util::makeCacheKey('translations-' . $locale, false);
    		$translations = $cache->getItem($cacheKey);
    		if (!$translations) {
    
    			$translate = new TranslationTable();
    			$translations = $translate->getLang($mapping[$locale]);
    			
    			$cache->setItem($cacheKey, $translations);
    		}
    	}
    	
    	Timer::end(__METHOD__);
    
    	return $translations;
    }
    
    static function loadSeoTemplates($params) {
    	
    	Timer::start(__METHOD__);
    
    	$data = Data::getInstance();
    
    	$site = $data->get('site');
    
    	// 部分页面是需要判断 id 的，即页面有继承关系，没有针对某个 id 的特殊设置时，会取默认设置
    	$specialPages = array(
    
    		// category related
    		'product_list' => 'cat',        
    	);
    	 
    	$currentRouteName = $data->get('route');
    
    
    	$seoTplKey = 'SEO_TPL_' . $site['short_name'] . "_" . $currentRouteName;
    	
    	$requiredParam = $params[$specialPages[$currentRouteName]];
    	$seoTplKey .= '_' . $requiredParam;
    	$cache = Cache::get('dynamicCache');
    
    	$seoTplKey = Util::makeCacheKey($seoTplKey);
    	$seoData = $cache->getItem($seoTplKey);
    	if (!$seoData) {
    		/**
    		 * 由于 SEO template 有非常灵活的继承功能，假如一个页面有 3 个 SEO template，
    		 * 有可能第一个使用的是默认的，第二个使用的是页面级的，第三个使用的是有 ID 特殊设置的，
    		 * 所以这里 3 层都要取。
    		 */
    		$params['pagetype'] = $currentRouteName;
    		$params['id'] = $requiredParam;
    		// 添加 URL 级别的变量
    		//if(in_array($currentRouteName, $pageLevelPages)){
    			//$params['uri'] = $uri;
    		//}
    
    		// 添加 keyword 级别的变量
    		//if(in_array($currentRouteName, $keywordLevelPages) && !empty($keyword)){
    			//$params['keyword'] = $keyword;
    		//}
    
    		//添加category级别的变量
    		//if(in_array( $currentRouteName, $categoryLevelPages)){
    			//$params['categoryId'] = $params['cat'];
    		//}
    
    		$matchedRules = array();
    		
    		$tpl = ArticleCms::fetchContent('seo-template', $params,$matchedRules);
			
    		$siteSetting = $data->get('siteSetting');
    		
    		$metaLang = substr($siteSetting['site_locale'], 0, 2);
    		
    		$tpl['meta_language'] = $metaLang;
    
    		$tpl['meta_title'] = isset($tpl['meta_title']) ? $tpl['meta_title'] : '';
    
    		//$tpl['meta_keywords'] = isset($tpl['meta_keywords']) ? $tpl['meta_keywords'] : '';
    
    		$tpl['meta_description'] = isset($tpl['meta_description']) ? $tpl['meta_description'] : '';
    		
    		$seoData = array('seoTpl' => $tpl, 'rules' => $matchedRules);
    
    		$cache->setItem($seoTplKey,$seoData);
    	}
    
    	Timer::end(__METHOD__);
    
    	return $seoData;
    }
}