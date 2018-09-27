<?php 
namespace Admin\Service;

use Admin\Model\Auth;
use Admin\Model\Sites;

use Test\Data;

use Zend\Authentication\Storage\Session as sessionStorage;

class Resource {
    /**
     * 加载站点信息
     *
     * @param string $hostname
     * @return array
     */
    static public function loadSite() {
        $siteConfig = array();
        
        $s = new sessionStorage('cmscountry','cms_site_id');
        $user = Auth::getIdentity();
        
        $sites = $user['sites'];
        
        $country = $_GET['site_id'];
        if(!empty($country)){
        	$siteArray = array();
        	foreach($sites as $r){
        		$siteArray[] = $r['site_id'];
        	}
        
        	if(in_array($country,$siteArray)){
        		$s->write($country);
        	}else{
        		throw new \Exception('you have no access on Site id:'.$country);
        	}
        }
        
        $currentSite = $s->read();
        
        $config = Data::getInstance()->get('config');
        
        $defaultSiteId = $config['defaultSiteId'];
        
        if(empty($currentSite)){
        	$currentSite = (!empty($sites[0]->site_id)) ? $sites[0]->site_id : $defaultSiteId;
        	$s->write($currentSite);
        }
        
        $sites = new Sites();
        if(!empty($currentSite)){
        	$siteConfig = $sites->getSiteByID($currentSite);
        }
        return $siteConfig;
        
    }
}