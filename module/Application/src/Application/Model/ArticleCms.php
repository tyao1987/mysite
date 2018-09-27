<?php
namespace Application\Model;

use Test\Data;
use Test\Util\Common;
use Test\Util\Timer;

use Application\Util\Util;

class ArticleCms {
	
	protected static $mainSite = null;
	protected static $distributionSite = null;
	
	protected static $templates = null;

    /**
     * 替换参数
     * 
     * @param string $template 模板
     * @param array $params 参数列表
     * @return boolean|mixed
     */
    protected static function _replaceParams($template, $params){

    	if(self::$mainSite){
    		$params['mainsite_shortname'] = self::$mainSite;
    	}
    	
    	if(self::$distributionSite){
    		$params['distributionsite_shortname'] = self::$distributionSite;
    	}

    	if(preg_match_all("/{(.*?)}/i", $template, $matches)){
    		foreach($matches[1] as $variable){
	    		if(isset($params[$variable])){
	    			$value = str_replace(" ", "_", $params[$variable]);
	    			$template = str_replace("{".$variable."}", $value, $template);		
	    		}else{ // 如果有一个参数找不到，就返回false
	    			return false;
	    		}
	    	}
    	}
    	return $template;
    }
    
    /**
     * Get content by template
     * 
     * @param string template template dir + name
     * @return if the file is not exist ,return false
     */
    protected static function _getContentByTemplate($template){
        $file = Util::getWritableDir("articles") . $template;
    	if(file_exists($file)){
    		return Common::readFile($file);
    	}else{
    		return false;
    	}
    }
    
    /**
     * Get all content by template dir
     * 
     * @param string templatedir 
     * @return array name=>content
     */
    protected static function _getContentByDir($template){
    	$template = Util::getWritableDir("articles") . $template;

		if(!is_dir($template)){
			return false;
		}
	
		$result = array();
    	foreach(glob($template . '*') as $item){
    		$result[basename($item)] = Common::readFile($item);
	    }
	    
	   	return $result;
    }
    
    /**
     * 验证目录
     * 
     * @param string $template
     * @return boolean
     */
    protected static function _checkDir($template) {
    	
    	$allowedDir = Util::getWritableDir("articles");
    	$requestDir = Util::getWritableDir("articles") . $template;
    	if (!file_exists($requestDir) || !file_exists($allowedDir)
    	    || (is_file($requestDir) && is_executable($requestDir))) {
    	    	return false;
    	}
    	$requestDir = realpath($requestDir);
    	$allowedDir = realpath($allowedDir);
    	 
    	return (strpos($requestDir, $allowedDir) === 0);
    }
    
    protected static function _loadTemplates() {
        if (null === self::$templates) {
            self::$templates = include ROOT_PATH . '/module/Application/data/article-template.php';
            
            $data = Data::getInstance();
            $site = $data->get('site');
            if ($site['site_type'] == 'MainSite') {
                self::$mainSite = $site['short_name'];
            } else if ($site['site_type'] == 'DistributionSite') {
                self::$distributionSite = $site['short_name'];
            
                $config = $data->get('config');
                $countryShortname = $config['countryShortname'];
                self::$mainSite = $countryShortname[$site['country']];
                unset($config, $countryShortname);
            }
        }
        return self::$templates;
    }
    
    /**
     * 获取 article 内容
     * 
     * @param string $type article分类
     * @param array $params 其他相关参数
     */
    public static function fetchContent($type, $params = array(), &$matchedTemplates = null){
        Timer::start(__METHOD__);
        
    	$templates = self::_loadTemplates();
    	
    	// 判断是否存在该模板,如果模板不存在则返回空
    	if (!isset($templates[$type])) { return null; }
    	
    	$contentType = $templates[$type]['type'];
    	$loadMethod = $templates[$type]['method'];
    	unset($templates[$type]['type']);
    	unset($templates[$type]['method']);
    	
    	$result = ($contentType == "directory")?array():'';

    	// 按照模板中设置的先后顺序，循环遍历模板
    	foreach($templates[$type] as $name => $template){
    	    
    		// 调用私有方法，进行变量的替换
    		$formatTemplate = self::_replaceParams($template,$params);
    		
    		if ($formatTemplate === false  || !self::_checkDir($formatTemplate)) {
    			continue;
    		}
    		
    		if ($contentType == "directory"){
    			$contents = self::_getContentByDir($formatTemplate);
    			if ($contents === false){
	    			continue;
	    		} else {
	    			if (is_array($matchedTemplates)) {
	    				$matchedTemplates[$name] = array_keys($contents);
	    			}
	    			foreach($contents as $key => $value){
	    				$result[$key] = $value;
	    			}
	    		}
    		} else {
	    		$result = self::_getContentByTemplate($formatTemplate);
	    		if ($result === false){
	    			continue;
	    		} else {
	    			if (is_array($matchedTemplates)) {
	    				$matchedTemplates[$name] = substr($formatTemplate, strripos($formatTemplate, '/') + 1);
	    			}	
	    		}	
    		}
    		
    		if($loadMethod == 'priority'){
    		    break;
    		}
    	}
    	
    	if (is_array($matchedTemplates)) {
    		// 去重，以后加载的规则为准
    		$keys = array_reverse(array_keys($matchedTemplates), true);
    		$first = array_shift($keys);
    		while (!empty($keys)) {
    			foreach ($keys as $key) {
    				$matchedTemplates[$key] = array_diff($matchedTemplates[$key], $matchedTemplates[$first]);
    			}
    			$first = array_shift($keys);
    		}
    	}
    	
    	Timer::end(__METHOD__);
    	
    	return $result;
    }
}
