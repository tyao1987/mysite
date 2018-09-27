<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2014 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Admin\Controller;

use Zend\View\Model\ViewModel;
use Test\Data;
use Test\Util\Common ;
use Application\Util\Util;
use Application\Service\Cache;
use Application\Model\JavaScriptPacker;

use Admin\Model\Sites ;
use Admin\Model\DealCache ;

class MemcacheController extends AbstractController
{
	private $config = null ;
	
	private $site   = null ;

	public function __construct(){

		$data = Data::getInstance() ;

		if($this->config === null)
			$this->config = $data->get('config') ;

		if($this->site === null)
			$this->site = $data->get('site') ;
	}
    /**
     *index
     */
    public function indexAction()
    {
        if($this->getRequest()->isPost()){

        	$type = $this->getRequest()->getPost('flush');
			
        	switch ($type) {
	            case 'default':
			        Cache::get("cache")->flush();
	                $defaultCacheFlushed = true;
	                break;
	            case 'constant';
			        Cache::get("constantCache")->flush();
	                $constantCacheFlushed = true;
	                break;
	            case 'dynamic';
	                Cache::get("dynamicCache")->flush();
	                $dynamicCacheFlushed = true;
	                break;
	            case 'all';
	                Cache::get("cache")->flush();
	                Cache::get("constantCache")->flush();
	                Cache::get("dynamicCache")->flush();
	                $allCacheFlushed = true;
	                break;
	            case 'css_js';

	                $result = $this->clearAllHostsCache();
	                
	                $jsResult = $this->clearJsCache();
	                
	                $cssResult = $this->clearCssFile();
	                
                    $cssAndJsResultInfo = $jsResult.$cssResult ;

	                $jsAndCssCacheFlushed = $cssAndJsResultInfo === '' ? true:false ;
	                
	                break;
	            case 'all_hosts';
	            
	                $allHostsCacheFlushed = false;
	                $result = $this->clearAllHostsCache();
	                self::clearCmsdbCacheFile();
	                
	                if($result){
	                    $allHostsCacheResultInfo =  "<BR>";
	                    if( $result['success'] ){
	                        $allHostsCacheResultInfo .= 'Successful hosts: [ '. join(', ',$result['success']).' ] '. '<BR>';
	                    }
	                    if( $result['failure'] ){
	                        $allHostsCacheResultInfo .= 'Fail hosts: [ '. join(', ',$result['failure']).' ] ' ; 
	                    }
	                    $allHostsCacheFlushed = true;
	                }else{
	                    $allHostsCacheResultInfo =  "Error: cannot get cache hosts from config file.";
	                }
	                
	                break;
	        }
        }

        if(strtoupper($this->site['country']) == 'USA'){
            $this->site['country'] = '';
        }

        $result['data'] = array(
            array(
                 'name'     => 'Default Cache' ,
                 'type'     => 'default' ,
                 'info'     => 'Data' , //$config->default->info ,
                 'flushed'  => $defaultCacheFlushed
            ) ,
            array(
                 'name'     => 'Constant Cache' ,
                 'type'     => 'constant' ,
                 'info'     => 'Router, Translation, ZendDbTableMetadata, TemplateVariableDescription, Currency' ,//$config->constant->info ,
                 'flushed'  => $constantCacheFlushed
            ) ,
            array(
                 'name'     => 'Dynamic Cache' ,
                 'type'     => 'dynamic' ,
                 'info'     => 'Stylesheet, Site, SiteSetting, PageMetaData, PageProperties' ,//$config->dynamic->info ,
                 'flushed'  => $dynamicCacheFlushed
            ) ,
            array(
                 'name'     => 'All Cache' ,
                 'type'     => 'all' ,
                 'info'     => 'All Cache' ,
                 'flushed'  => $allCacheFlushed
            ) ,
            array(
                 'name'     => 'Css and JS Cache' ,
                 'type'     => 'css_js' ,
                 'info'     => 'Css and JS Cache(memcache)' ,
                 'flushed'  => $jsAndCssCacheFlushed ,
                 'resultInfo'=> $cssAndJsResultInfo
            ) ,
            array(
                 'name'     => 'All Hosts Cache' ,
                 'type'     => 'all_hosts' ,
                 'info'     => 'All Hosts Cache(memcache)' ,
                 'flushed'  => $allHostsCacheFlushed ,
                 'resultInfo'=> $allHostsCacheResultInfo
            )
        );

        return new ViewModel($result) ;
    }


    public function clearAllHostsCache()
    {
        //clear all cache
        $this->clearAllCache();

        $cacheHosts = $this->config['cacheHosts'];
        if( !isset($cacheHosts[0]) ){ 
            $ip = $cacheHosts['ip'];
            $domain = $cacheHosts['domain'];
            $cacheHosts = array();
            $cacheHosts[0] = array("ip"=>$ip, "domain"=>$domain);
        }
        $successHost = array();
        $failureHost = array();
        foreach( (array) $cacheHosts as $key=>$host )
        {
            $ip = $host['ip'];
            $domain = $host['domain'];
            $httpHeader = array( "HOST: $domain" );
            $url = "http://$ip/mod_memcache/admin/clear-cache";
		    $curlHandler = curl_init();
		    curl_setopt($curlHandler, CURLOPT_URL, $url);
		    curl_setopt($curlHandler, CURLOPT_RETURNTRANSFER, 1);
		    curl_setopt($curlHandler, CURLOPT_HTTPHEADER, $httpHeader);
            $content = curl_exec($curlHandler);
            $error = curl_error($curlHandler);
            curl_close($curlHandler);
            if(!empty($error)) {
                $failureHost[] = "$ip/$domain($error)";
            }else{
                $successHost[] = "$ip/$domain";
            }
        }
        return array("success"=>$successHost, "failure"=>$failureHost);
    }

    public function clearAllCache() {
        //$this->clearApcCache();
        $this->clearAllMemcache();
        return true;
    }

    public function clearAllMemcache() {
        
        Cache::get('cache')->flush();
        Cache::get('constantCache')->flush();
        Cache::get('dynamicCache')->flush();
        
        return true;
    
    }

//     public function clearApcCache() {
//         if (function_exists('apc_clear_cache')) {
//             apc_clear_cache();
//             apc_clear_cache('user');
//         }
//     }

    public function clearJsCache(){
    	
    	
		$thisFile = ROOT_PATH . '/public/scripts/core.js';
		$localContent = file_get_contents($thisFile);	

		$tmp = new JavaScriptPacker($localContent, 'None', false, false);
		if(APPLICATION_ENV != 'production'){
			$output = $localContent;
		}else{
			$output = $tmp->pack();
		}

		$jsroot = $this->config['cmsWritableDir']['javascript'] ;
		$localFile = $jsroot . 'core.js';
		$file = fopen($localFile,"w+");
		fwrite($file,$output);
		fclose($file);

        $jsStr = '' ;
		if(!file_exists($localFile) || !filesize($localFile)){
            $jsStr = 'Clear js cache: ' . $localFile . ' failed! ' ;
        }

		$cacheFile = Util::getWritableDir('dataCache')."jsVersion";
		Common::writeFile($cacheFile, md5_file($localFile));
        return $jsStr ;
    }
    
    private function clearCssCache($name, $localHost, &$cssVersion) {

        //Bug 350727 - PR Prod - Improve CSS/JS cache implementation.
        $cssFile = Util::getWritableDir('styles') . '/' . $name . '.css';
        $cssBackFile = Util::getWritableDir('styles') . '/old_' . $name . '.css'; 
        /*if(file_exists($cssBackFile)){
            $flagUnlink = unlink($cssBackFile);
            if(!$flagUnlink){
                return  array(
                             'info'     => 'Delete the last backup css file: old_' . $name . ' failed !' ,
                             'flushed'  => false
                        ) ;
            }
        }*/

        if (file_exists($cssFile)) {
            $flagRename = rename($cssFile, $cssBackFile);
            if(!$flagRename){
                return array(
                             'info'     => 'rename css file ' . $name . ' failed !' ,
                             'flushed'  => false
                        ) ;
            }
        }
    	
    	$localCss = 'http://127.0.0.1/styles/' . $name . '.css';
    	
    	$result = self::curlContent($localCss, $localHost);
    	unset($cssVersion[$name]);
    	if (file_exists($cssFile)) {
            if(file_exists($cssBackFile)){
                $flagUnlink = unlink($cssBackFile);
                if(!$flagUnlink){
                    return array(
                                 'info'     => 'Delete backup css file: old_' . $name . ' failed !' ,
                                 'flushed'  => false
                            ) ;
                }
            }
    	}elseif(file_exists($cssBackFile)){
    		$cssVersion[$name] = md5_file($cssFile);
            $flagRename = rename($cssBackFile, $cssFile) ;
            if(!$flagRename){
                return array(
                             'info'     => 'rename css file name from ' . $cssBackFile . ' to ' . $name . ' failed !' ,
                             'flushed'  => false
                        ) ;
            }
        }
    	
    	return $result;
    }


    private function clearCssFile() {
    	
    	$cmsHost = $this->config['cmsHost'];
    	//$styleHost = $this->config['styleServer'];
    	
    	$cacheFile = Util::getWritableDir('dataCache')."cssVersion";
    	
    	$cssVersion = array();
    	if (file_exists($cacheFile)) {
	    	$result = Common::readFile($cacheFile);
	    	$cssVersion = (array)unserialize($result);
    	}
    	
        //Intialization return .
    	$returnArray = array() ;
		// noscript.css
    	//$returnArray[] = $this->clearCssCache('noscript', $cmsHost, $styleHost, $cssVersion);
        
		$coreMainSite = false;
		$coreDistributionSite = false;
		
		// override
		$siteModel = new Sites();
		$sites = $siteModel->getSitesColumn(array('site_id' , 'short_name' , 'hostname' ,'site_type'));
		
		foreach ($sites as $site) {
			$returnArray[] = $this->clearCssCache('override_' . $site['short_name'], $site['hostname'], $cssVersion);
			
			// core for main site
			$coreCss = Util::getWritableDir('styles') . '/core.css';
			
			if (($coreMainSite == false || !file_exists($coreCss)) && strtolower($site['site_type']) == 'mainsite') {	
				$returnArray[] = $result = $this->clearCssCache('core', $site['hostname'], $cssVersion);
				if ($result != false && !isset($result['flushed'])) {
					$coreMainSite = true;
				}
			}
			
			// core for distribution site
			$coreDistributionCss = Util::getWritableDir('styles') . '/core_distribution.css';
			if (($coreDistributionSite == false || !file_exists($coreDistributionCss)) && strtolower($site['site_type']) == 'distributionsite') {
				$returnArray[] = $result = $this->clearCssCache('core_distribution', $site['hostname'], $cssVersion);
				if ($result != false && !isset($result['flushed'])) {
					$coreDistributionSite = true;
				}
			}
		}
		Common::writeFile($cacheFile, serialize($cssVersion));
        //set return result
        $returnString = '' ;
        if(!empty($returnArray)){
            foreach ($returnArray as $key => $value) {
                if(isset($value['flushed']) && !$value['flushed']){
                    $returnString .= $value['info'] ;
                }
            }
        }
        return $returnString ;
    }

    private static function curlContent($url, $host) {
    	//echo $url . '===' . $host . '#####<br>';
		$httpHeader = array( "HOST: $host" );
	    $curlHandler = curl_init();
	    curl_setopt($curlHandler, CURLOPT_URL, $url);
	    curl_setopt($curlHandler, CURLOPT_RETURNTRANSFER, 1);
	    curl_setopt($curlHandler, CURLOPT_HTTPHEADER, $httpHeader);
	    curl_setopt($curlHandler, CURLOPT_TIMEOUT, 30);
		curl_exec($curlHandler);
		$error = curl_error($curlHandler);
		curl_close($curlHandler);
		unset($curlHandler);
		return empty($error);
    }

    private static function clearCmsdbCacheFile() {

    	//include(__ROOT_PATH . '/application/data/scripts/cron/dataCache.php');
    	$cache = new DealCache();
    	$cache->dataCache();
    }

}