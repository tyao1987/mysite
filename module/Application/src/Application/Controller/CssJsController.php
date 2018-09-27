<?php
namespace Application\Controller;

use Application\Model\ArticleCms;
use Application\Model\JsCssCompressor;
use Application\Util\Util;

use Test\Data;

use Zend\Mvc\MvcEvent;
use Zend\View\Model\ViewModel;



class CssJsController extends AbstractController {
    
    protected $_dateObj = null;
    
    public function preDispatch(MvcEvent $e) {
    	//ensure that use the en_US language/type
    	parent::preDispatch($e);
    	$this->_dateObj = new \DateTime();
    	
    }
    
    
    protected function getZendDate() {
    	return clone $this->_dateObj;
    }
    
    public function StylesheetAction(){
    	
        $date = $this->getZendDate();
        
        $response = $this->getResponse();
        $response->getHeaders()->addHeaderLine("Content-Type: text/css; charset=utf-8");
        
        $data = Data::getInstance();
        
        $siteSetting = $data->get('siteSetting');
        $skin = $siteSetting['site_skin'];
        
        $siteObj = $data->get('site');

        $params = $this->params;
        
        if(strtolower($params['type']) == 'core' || strtolower($params['type']) == 'core_distribution' ) {
        	
        	$siteType = $siteObj['site_type']; //MainSite | DistributionSite
        	
        	$articleModel = new ArticleCms();
        	$params['skin'] = $skin;
        	$css = $articleModel->fetchContent($type='css',$params);
        	$tmp = array();
        	$stylePathes = array();
        	foreach ($css as $key=>$value) {
        		if (substr($key, -4, 4) == '.css') {
        			$tmp[$key] = $value;
        		}
        	}
        	foreach ($tmp as $file => $filecss) {
        		if(preg_match('~login\.css$~i', $file)) {
        			//if ($siteType == 'MainSite' && strtolower($params['type']) == 'core') {
        				//array_push($stylePathes, $filecss);
        			//}
        		}else if(preg_match('~distribution\.css$~i', $file)) {
        			//if($siteType == 'DistributionSite' && strtolower($params['type']) == 'core_distribution') {
        				//array_push($stylePathes, $filecss);
        			//}
        		}else {
        			array_push($stylePathes, $filecss);
        		}
        	}
        		
        	$tmp = new jsCssCompressor();
        		
        	$output = $tmp->makeCss($stylePathes);
        		
        	$timestamp = $date->getTimestamp();
        		
        	$coreCss = array(
        			'data'	=> $output
        			,'time'	=> $timestamp
        	);
        		
        	$output = $coreCss['data'];
        	    
        	$lastModifiedTime = $coreCss['time'];
        		
        }else if(strtolower($params['type']) == 'override_' . $siteObj['short_name']
        		|| strtolower($params['type']) == 'partner_' . $siteObj['short_name']) {
        		
        	$articleModel = new ArticleCms();
        	$css = $articleModel->fetchContent($type = 'css-override');
        
        	$stylePathes = array();
        	$tmp = array();
        
        	foreach ($css as $file=>$value) {
        		if (substr($file, -4, 4) == '.css' && (strtolower($file) != 'partner.css')) {
        			$stylePathes[$file] = $value;
        		}
        	}
        
        	// partner.css
        	//if (strtolower($params['type']) == 'partner_' . $siteObj['short_name']) {
        		//foreach ($css as $file=>$value) {
        			//if (strtolower($file) == 'partner.css') {
        				//$stylePathes[$file] = $value;
        			//}
        		//}
        	//}
        			
        	$tmp = new jsCssCompressor();
        	$output = $tmp->makeCss($stylePathes);
        
        	$timestamp = $date->getTimestamp();
        
        	$overridingCss = array(
        		'data'	=> $output
        		,'time'	=> $timestamp
        	);
        
        	//$output = $overridingCss['data'];
        	$lastModifiedTime = $overridingCss['time'];
        
        }else {
        	throw new \Exception('No such type for Stylesheet! Only support[core|override|noscript] currently!');
        }
        
        $howLong = 259200; // means seconds 3 * 24 * 60 * 60;
        
        $dateInterval = new \DateInterval('PT'.$howLong.'S');
        $date->add($dateInterval);
        $expires = $date->format("D, d M Y H:i:s");
        
        $response->getHeaders()->addHeaderLine("Expires: $expires GMT");
        //$response->setHeader('Content-Type: text/css; charset=utf-8');
        $timestamp = $howLong;
        $response->getHeaders()->addHeaderLine("Cache-Control: max-age=$timestamp");
        $response->getHeaders()->addHeaderLine("Pragma: public");
        
        
        $date = $this->getZendDate();
        $date->setTimestamp($lastModifiedTime);
        $response->getHeaders()->addHeaderLine("Last-Modified: " . $date->format("D, d M Y H:i:s") . " GMT");
        
        if(isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])) {
        		
        	//strtotime support GMT, good news;
        	$oldLastModifiedTime = strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']);
        	
        	if($oldLastModifiedTime == $lastModifiedTime) {
        		header( 'HTTP/1.0 304 Not Modified' );
        		exit;
        	}
        }
        
        $filename = Util::getWritableDir('styles') . '/' . $params['type'].'.css';
        file_put_contents($filename, $output);
        
        $response->setStatusCode(200);
        $response->setContent($output);

        return $response;
        
        
    }
    
}
?>