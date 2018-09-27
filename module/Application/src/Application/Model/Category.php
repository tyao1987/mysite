<?php
namespace Application\Model;

use Zend\Session\Container;

use Test\Data;
use Test\Util\Common;
use Test\Util\Timer;
use Test\Util\Uri;

use Application\Exception;
use Application\Model\ArticleCms;
use Application\Service\Cache;
use Application\Service\Resource;
use Application\View\Helper\GetUrl;
use Application\Util\Util;

use DateTime;

class Category {
    
    /**
     * url 和 api 参数中允许的值
     *
     * @var array
     */
    protected $urlParamsAllowed = array(
		'sort'	=>	array(
		    	0	=>	'merchantcount',
		    	1 	=>  'name',
		    	2	=>  'pop',
		),
        // value means default counts per page in such show mode
        'viewType'	=>	array(
            'grid'        => 20,
            'standard'    => 20
		),
        'numberOfProducts'	=>  array(20, 40, 60),
    );
    
    /**
     * @var array url 参数的默认值
     */
    protected $urlParamsDefault = array(
    		'viewType'		=> 'standard',
    		'sort'			=> 2
    );
    
    /**
     * @var 排序设置和 url 参数对照表
     */
    protected $sortSettingToUrlMapping = array(
    	'RETAILER_COUNT'		=> 0,
        'NAME'					=> 1,
        'POPULARITY'			=> 2,
    );
    
    /**
     * @var array 路由参数
     */
    protected $routeParams = array('cat', 'categoryName');
    
    /**
     * 当前产品数量，存在 cookie 中
     *
     * @var integer
     */
    static $currentNumberOfProducts;
        
    public function __construct() {}
    
    /**
     * @array siteSetting
     */
    static $siteSetting = null;
    
    /**
     * instance GetUrl
     * @var Object
     */
    static $urlHelper = null;
    
    public function getCategory($params = array()) {
        Timer::start(__METHOD__);
        
        $data = Data::getInstance();
        // 合并 default 参数
        $params = array_merge($this->apiParamsDefault, (array)$params);
         
        // 如果需要，设置全局级别的 api 参数
        
        //Issue 310578 - Exclude retailer completely in the API requests
        $categoryTypeKey = 'site_remove_retailers_in_'.self::$categoryType;
        $siteSetting = $data->get('siteSetting');
        if(trim($siteSetting[$categoryTypeKey]) != ''){
        	$params['offer_exclude'] = $siteSetting[$categoryTypeKey];
        }
     
        $cache = Cache::get('cache');
        $cacheKey = Util::makeCacheKey(array_merge(array('func'=>__METHOD__), $params));
        if(null === ($result = $cache->getItem($cacheKey))){
            $result = Api::get()->getCategory($params);
            $cache->setItem($cacheKey, $result);
        }

        if($data->get('route') == 'product_list' || $data->get('route') == 'product_list_ajax'){
            // 格式化操作
            $result = $this->_formatProductsList($result);
        }
        
        Timer::end(__METHOD__);
        
        return $result;
    }
    
    /**
     * 解析参数
     *
     * @param array $params
     * @return array
     */
    public function parseParams($params = array()) {
        Timer::start(__METHOD__);
        $urlParams = $this->_parseUrlParams($params);
        $cookieParams = $this->_parseCookieParams($urlParams);
        
        Timer::end(__METHOD__);
        
        return array(
            'urlParams'    => $urlParams,
            'cookieParams' => $cookieParams,
        );
    }
    
    /**
     * 解析 url 参数
     *
     * @param array $params
     * @return array
     */
    protected function _parseUrlParams($params = array()) {
    	
    	unset($params['controller'], $params['action']);
        $urlParams = array(
            array(), // 所有 route 参数
            array(), // 所有 get 参数
        );
        foreach ($params as $key => $value) {
            if (in_array($key, $this->routeParams)) { // route 参数
                $urlParams[0][$key] = $value;
            } else if ($key == 'numberOfProducts') {
                if(in_array($value, $this->urlParamsAllowed['numberOfProducts'])){
                    $urlParams[1][$key] = $value;
                }
            } else if ($key == 'q' || $key == 'search') {
                $urlParams[1][$key] = Common::queryFilter($value);
            } else if ($key == 'page') {
                $urlParams[1][$key] = intval($value) < 1 ? 1 : intval($value);
            } else if (isset($this->urlParamsAllowed[$key]) // 定义的允许值的参数
                && in_array($value, array_keys($this->urlParamsAllowed[$key]))) {
                $urlParams[1][$key] = $value;
            }elseif ($key == 'viewType'){
                if(in_array($value, array_keys($this->urlParamsAllowed[$key]))){
                    $urlParams[1][$key] = $value;
                }
            } else if(preg_match("/^[_0-9a-zA-Z-]+$/", $key)) { // 其他 key 合法的参数
                $urlParams[1][$key] = $value;
            }
        }
        return $urlParams;
    }
    
    /**
     * 解析 api 参数
     *
     * @param array $params
     * @return array
     */
    protected function _parseApiParams($params = array()) {
        $apiParams = array();
        
        $data = Data::getInstance();
        $siteSetting = $data->get('siteSetting');
        $locale = $siteSetting['site_locale'];
        $fmt = new \NumberFormatter($locale,\NumberFormatter::DECIMAL);
        
        foreach ($params as $key => $value) {
            if (in_array($key, $this->routeParams)) { // route 参数
                $apiParams[$key] = $value;
            } else if ($key == 'man_id') {
                $apiParams['brand'] = (int)$value;
            } else if ($key == 'retailer') {
                $apiParams['merchant'] = (int)$value;
            } else if ($key == 'price_min') {
                if($value == '' || (float)$value == 0) {
                    $value = 0;
                }else {
                    $value = (float) $value;
                    
                    $value = $fmt->parse($value);
                    $value = floor((float) $value * 100);
                    $value = number_format($value, 0, '', ''); // convert 1.2E+6 to 1200000
                }
                if(array_key_exists('price', $apiParams)) {
                    $apiParams['price'] = $value . $apiParams['price'];
                }else {
                    $apiParams['price'] = $value;
                }
                if(!array_key_exists('price_max', $params)) {
                    $apiParams['price'] = $apiParams['price'] . '_' . 99999999;
                }
            } else if ($key == 'price_max') {
                if($value == '' || (float)$value == 0 || ($value > 99999999)) {
                    $value = 99999999;
                }else {
                    $value = (float) $value;
                    
                    $value = $fmt->parse($value);
                    $value = floor((float) $value * 100);
                    $value = number_format($value, 0, '', ''); // convert 1.2E+6 to 1200000
                }
                if(array_key_exists('price', $apiParams)) {
                    $apiParams['price'] .= "_" . $value;
                }else {
                    $apiParams['price'] = "_" . $value;
                }
                if(!array_key_exists('price_min', $params)) {
                    $apiParams['price'] = 0 . $apiParams['price'];
                }
            } else if (substr($key, 0, 5) == 'attr_' && (int)$value != 0) {
                if(!array_key_exists('attr', $apiParams)) {
                    $apiParams['attr'] = array();
                }
                $apiParams['attr'][] = (int)$value;
            } else if ($key == 'search' && !empty($value)) {
                $apiParams['keyword'] = Common::queryFilter($value);
            } else if ($key == 'page') {
                $apiParams['pstart'] = ((int)$value - 1) * $params['numberOfProducts'] + 1;
            } else if ($key == 'numberOfProducts') {
                if(in_array($value, $this->urlParamsAllowed['numberOfProducts'])){
                	$apiParams['pcount'] = (int)$value;
                }
            } else if ($key == 'sort') {
                $apiParams['sort'] = isset($this->urlParamsAllowed['sort'][$value]) ? $this->urlParamsAllowed['sort'][$value] : NULL;
            }
        }

        // append default value if no specify or wrong specify
        $apiParams['attrstart'] = $this->apiParamsDefault['attrstart'];
        $apiParams['attrcount'] = $this->apiParamsDefault['attrcount'];
        
        $apiParams['offerstart'] = $this->apiParamsDefault['offerstart'];
        $apiParams['offercount'] = $this->apiParamsDefault['offercount'];

        if(!array_key_exists('price', $apiParams) || !preg_match('/^\d+_\d+$/', $apiParams['price'])) {
            $apiParams['price'] = $this->apiParamsDefault['price'];
        }
        if(!array_key_exists('pstart', $apiParams)) {
            $apiParams['pstart'] = 0;
        }
        if(!array_key_exists('pcount', $apiParams) && !empty($params['numberOfProducts'])) {
            $apiParams['pcount'] = self::$currentNumberOfProducts;
        }
        
        //TODO:临时添加，待重构项目上线后移除.
        if(array_key_exists('keyword', $apiParams)) {
        	if(!$apiParams['sort']) {
        		// add sort param
    			$defaultSort = $siteSetting['productlist_default_sort_order'];
    			$apiParams['sort'] = Util::getProductSorting($defaultSort);
        	}
        }
     
        //Issue 310578 - Exclude retailer completely in the API requests
        $categoryTypeKey = 'site_remove_retailers_in_'.self::$categoryType;      
        if(trim($siteSetting[$categoryTypeKey]) != ''){
        	$apiParams['offer_exclude'] = $siteSetting[$categoryTypeKey];
        }

        // 根据 route 增加一些页面级别的参数
        //暂时注释掉hotkeyword，单独请求一次getRelatedSearches,等api改后好后才能直接使用keyword
        $route = $data->get('route');
        switch ($route) {
            case 'product_list':
                $apiParams['attrstart'] = 0;
                $apiParams['attrcount'] = 10000;
                //$apiParams['hotkeyword'] = 20;
                $apiParams['revcount'] = 0;
                $apiParams = $this->_parseListParams($apiParams);
                break;
            case 'category_voucher':
                $page  = ($params['page']) ? (int)$params['page'] : 1;
                $apiParams['coupon_count'] = ((int)$params['numberOfVouchers']) ? (int)$params['numberOfVouchers'] : 20;
                $apiParams['coupon_start'] = ($page-1) * $apiParams['coupon_count'] + 1;
                //$apiParams['hotkeyword'] = 20;
                $apiParams['show_voucher'] = 1;
                break;
            case 'splash_page':
                //$apiParams['hotkeyword'] = 20;
                break;
            case 'buying_guide':
                $apiParams['attrstart'] = 0;
                $apiParams['attrcount'] = 10000;
                break;
            case 'product_list_ajax':
                $apiParams['attrstart'] = 0;
                $apiParams['attrcount'] = 10000;
                $apiParams = $this->_parseListParams($apiParams);
                break; 
            default:
                break;
        }
        return $apiParams;
    }
    
    protected function _parseCookieParams($urlParams = array()) {
        
        $cookieParams = array();
        
        $oldPrefs = Util::getPrefs();
        $newPrefs = array();
        
        $categoryId = $urlParams[0]['cat'];
        
        // category level view type
        $oldViewTypes = array_key_exists('viewType', $oldPrefs) ? unserialize($oldPrefs['viewType']) : array();
        
        if (array_key_exists('viewType', $urlParams[1])) {
            $cookieParams['viewType'] = $urlParams[1]['viewType'];
            if(!array_key_exists('viewType', $oldPrefs) || ($oldViewTypes[$categoryId] != $urlParams[1]['viewType'])) {
                $oldViewTypes[$categoryId] = $urlParams[1]['viewType'];
                $newPrefs['viewType'] = serialize($oldViewTypes);
            }
            
        // from old cookie
        } else if (array_key_exists('viewType', $oldPrefs) && isset($oldViewTypes[$categoryId]) ) {
			$cookieParams['viewType'] = $oldViewTypes[$categoryId];

		// from default, but default value maybe from siteSetting by
		} else {
			$cookieParams['viewType'] = $this->urlParamsDefault['viewType'];
		}
        
        if(array_key_exists('numberOfProducts', $urlParams[1])) {
            $cookieParams['numberOfProducts'] = $urlParams[1]['numberOfProducts'];
            if(!array_key_exists('numberOfProducts', $oldPrefs) || $oldPrefs['numberOfProducts'] != $urlParams[1]['numberOfProducts']) {
                $newPrefs['numberOfProducts'] = $urlParams[1]['numberOfProducts'];
            }
        } else if (array_key_exists('numberOfProducts', $oldPrefs)) {
			$cookieParams['numberOfProducts'] = $oldPrefs['numberOfProducts'];
			
		} else {
			$cookieParams['numberOfProducts'] = $this->urlParamsAllowed['viewType'][$cookieParams['viewType']];
		}
        self::$currentNumberOfProducts = $cookieParams['numberOfProducts'];
        
        if(!empty($newPrefs)) {
             Util::setPrefs($newPrefs);
        }
        
        return $cookieParams;
    }
    
    public function getUrlParams($params) {
    	return $this->_parseUrlParams($params);
    }
    
    /**
     * 获取 category info 
     * 
     * @param integer $cat Category ID
     * @param boolean $isLeaf 是否是 leaf category
     * @return array ("name"=>"TV","type"=>"freetext")
     */
    public static function getCategoryInfo($cat, $isLeaf = true) {
        //get category mappings.
        $categoryMaps = Util::loadCategories();
        
        $mapping = ($isLeaf)?$categoryMaps['leafCategories']:$categoryMaps['subCategories'];
        
        return empty($mapping[$cat])?'':$mapping[$cat];
    }
    
    /**
     * 获取 category 级别的热门关键词
     *
     * @return \Pr\ApiWrapper\Entity\Category
     */
    public function getRelatedSearches($apiParams,$urlParams) {
        
        $cache = Cache::get('cache');
        
    	$params = array();
    	$params['cat'] = $urlParams['cat'];
    	$params['hotkeyword'] = 20;
    	
    	$cacheKey = Util::makeCacheKey($params);
    	if(null === ($ret=$cache->getItem($cacheKey))){
    	    $ret = Api::get()->getCategoryRelatedSearches($params);
    	    $cache->setItem($cacheKey,$ret);
    	}
    	return $ret;
    }
    
    
    /**
     * 获取 match more 的信息，解析 other hits
     *
     * @param array $urlParams
     * @return object
     */
    public static function getMatchMore($urlParams) {

        $api = Api::get();
        $data = Data::getInstance();
        
    	$siteSetting = $data->get('siteSetting');
    	$activeSharpLogic = $siteSetting['product_activate_sharp_logic'];
    
    
        $urlProcess = Util::getViewHelper("GetUrl");
    	$otherHits = $urlParams['other_hits'];
    
    	$otherHits = explode(';', $otherHits);
    	$tmp_otherHits = array_filter($otherHits);
    	if(empty($tmp_otherHits)) return null;
    	if(count($otherHits) == 4) {
    	}else {
    		return null;
    	}
    
    	$ret = new \stdClass();
    	$ret->q = '';
    	if(array_key_exists('q', $urlParams)){
    		$ret->q = urldecode($urlParams['q']);
    	}else{
    		if($activeSharpLogic=="1" && $data->has("plac")){
    			$productListAnchor = $data->get("plac");
    			parse_str($productListAnchor,$anchorArray);
    			foreach ($anchorArray as $key => $value){
    				if($key=="q"){
    					$ret->q = urldecode($value);
    				}
    			}
    		}
    	}
    
    	$searchUrlParams = new Container('searchUrlParams');
    	if(!empty($searchUrlParams->q)){
    		$ret->q = urldecode($searchUrlParams->q);
    	}
    
    	$arr = array('category', 'retailer', 'manufacturer', 'channel');
    	$i = 0;
    	foreach ($otherHits as $item) {
    		if($i >= 4) {
    			break;
    		}
    		$ret->{$arr[$i]} = $item;
    		$i++;
    	}
    
    	//handle category
    	try{
        	$tmp = $ret->category;
        	$tmp = explode('|', $tmp);
        	$ret->category = array();
        	if(count($tmp) > 1 || $tmp[0] != '') {
        
        		$i = 0;
        		$more = false;
        		foreach ($tmp as $item) {
        			if($item == 'x') {
        				$more = true;
        			}else {
        				$matches = null;
        				if(!preg_match('/^(\d+):(.*)$/', $item, $matches)) {
        					continue;
        				}
        				$ret->category[$i]['search'] = $matches[2];
        				$ret->category[$i]['cat'] = $matches[1];
        			}
        			$i++;
        		}
        		$sort = self::getProductListDefaultSort();
        		if(count($ret->category) > 0){
            		foreach ($ret->category as $i=>$item) {
            		        $info = self::getCategoryInfo($item['cat']);
            		        $item['categoryName'] = $info['name'];
            		        $params = array();
            		        $params['ch'] = $item['ch'];
            		        $params['cat'] = $item['cat'];
            		        $params['categoryName'] = $item['categoryName'];
            		        $query = array();
            		        $query['search'] = $item['search'];
            		        $query['q'] = $ret->q;
            		        	
            		        if ($sort) {
            		        	$query['sort'] = $sort;
            		        }
            		        if($activeSharpLogic=='1'){
            		        	$item['url'] = self::constructCleanUrl(array($params,$query));
            		        }else{
            		        	$item['url'] = $urlProcess('product_list', $params, $query);
            		        }
            		        $ret->category[$i] = $item;
            		}
            		if($more) {
            			$ret->category['more'] = $urlProcess('search', array(), array('q' => $ret->q, 'search_splash' => 'true'));
            		}
        		}else {
        			$ret->category = array();
        		}
        	}
    	} catch (\Exception $e){
    	    
    	}
    	
    	try{
        	//handle retailer
        	$tmp = $ret->retailer;
        	$tmp = explode('|', $tmp);
        	$ret->retailer = array();
        	if(count($tmp) > 1 || $tmp[0] != '') {
        		$more = false;
        		foreach ($tmp as $item) {
        			if($item == 'x') {
        				$more = true;
        			}else if (0 !== (int)$item){
        				$ret->retailer[] = array('id' => $item);
        			}
        		}
        		$apiParams = array();
        		foreach ($ret->retailer as $item) {
        			array_push($apiParams, array('merchant' => $item['id']));
        		}
        		$tmp2 = $api->getRetailersById($apiParams);
        		if($more) {
        			$sameCount = count($tmp2) == count($tmp) -1;
        		}else {
        			$sameCount = count($tmp2) == count($tmp);
        		}
        		if(!is_null($tmp2) && $sameCount) {
        			for($i = 0; $i< count($tmp2); $i++) {
        				$retailerName = $tmp2[$i]->name;
        				$ret->retailer[$i]['name'] = $retailerName;
        					
        				$params = array();
        				$params['merchant'] = $ret->retailer[$i]['id'];
        				$params['retailerName'] = $ret->retailer[$i]['name'];
        				$ret->retailer[$i]['url'] = $urlProcess('retailer_info', $params, null, 'tab-fragment-retailer-products');
        			}
        			if(count($ret->retailer) > 0 && $more) {
        				$ret->retailer['more'] = $urlProcess('search', array(), array('q' => $ret->q, 'search_splash' => 'true'));
        			}
        		}else{
        			$ret->retailer = array();
        		}
        		unset($tmp); unset($tmp2);
        	}
    	}catch(\Exception $e){
    	    
    	}
    
    
        try{
        	//handle manufacturer
        	$tmp = $ret->manufacturer;
        	$tmp = explode('|', $tmp);
        	$ret->manufacturer = array();
        	if(count($tmp) > 1 || $tmp[0] != '') {
        		$more = false;
        		foreach ($tmp as $item) {
        			if($item == 'x') {
        				$more = true;
        			}else if (0 !== (int)$item){
        				$ret->manufacturer[] = array('id' => $item);
        			}
        		}
        		$apiParams = array();
        		foreach ($ret->manufacturer as $item) {
        			array_push($apiParams, array('brand' => $item['id']));
        		}
        		$tmp2 = $api->getManufacturersById($apiParams);
        		if($more) {
        			$sameCount = count($tmp2) == count($tmp) -1;
        		}else {
        			$sameCount = count($tmp2) == count($tmp);
        		}
        		if(!is_null($tmp2) && $sameCount) {
        			for($i = 0; $i< count($tmp2); $i++) {
        				$manufacturerName = $tmp2[$i]->name;
        				$ret->manufacturer[$i]['name'] = $manufacturerName;
        					
        				$params = array();
        				$params['brand'] = $ret->manufacturer[$i]['id'];
        				$params['manufacturerName'] = $ret->manufacturer[$i]['name'];
        				$ret->manufacturer[$i]['url'] = $urlProcess('manufacturer_info', $params);
        			}
        			if(count($ret->retailer) > 0 && $more) {
        				$ret->manufacturer['more'] = $urlProcess('search', array(), array('q' => $ret->q, 'search_splash' => 'true'));
        			}
        		}else{
        			$ret->manufacturer = array();
        		}
        		unset($tmp); unset($tmp2);
        	}
        }catch(\Exception $e){
            
        }
    
        try{
        	//handle channel
        	$tmp = $ret->channel;
        	$tmp = explode('|', $tmp);
        	$ret->channel = array();
        	if(count($tmp) > 1 || $tmp[0] != '') {
        
        		$i = 0;
        		$more = false;
        		foreach ($tmp as $item) {
        			if($item == 'x') {
        				$more = true;
        			}else {
        				$matches = null;
        				if(!preg_match('/^(\d+):(.*)$/', $item, $matches)) {
        					continue;
        				}
        				$ret->channel[$i]['search'] = $matches[2];
        				$ret->channel[$i]['cat'] = $matches[1];
        			}
        			$i++;
        		}
        		if(count($ret->channel)>0){
            		foreach ($ret->channel as $i=>$item) {
            		    $info = self::getCategoryInfo($item['cat'],false);
            		    $item['categoryName'] = $info['name'];
            		    $params = array();
            		    $params['ch'] = $item['cat'];
            		    $params['channelName'] = $item['categoryName'];
            		    $query = array();
            		    $query['q'] = $ret->q;
            		    $item['url'] = $urlProcess('tree_page', $params, $query);
            		    $ret->channel[$i] = $item;
            		}
            		if(count($ret->channel) > 0 && $more) {
            			$ret->channel['more'] = $urlProcess('search', array(), array('q' => $ret->q, 'search_splash' => 'true'));
            		}
        		}else{
        		    $ret->channel = array();
        		}
        	}
        }catch(\Exception $e){
            
        }
    	$ret->category = array_merge($ret->channel, $ret->category);
    
    	return $ret;
    }
    
    /**
     * 获取产品列表的默认排序
     *
     * @return integer
     */
    static function getProductListDefaultSort() {
    
    	// add sort param
    	$siteConfig = Data::getInstance()->get("siteSetting");
    	$sort = '';
    	if ($siteConfig) {
    		$defaultSort = $siteConfig['productlist_default_sort_order'];
    		
    		$mapping = array(
    				'RETAILER_COUNT'		=> 0
    				,'NAME'					=> 1
    				,'PRODUCT_RATING'		=> 2
    				,'PRICE'				=> 3
    				,'POPULARITY'			=> 4
    				,'NEW_PRODUCTS'			=> 5
    		);
    		
    		$sort = $mapping[$defaultSort];
    	}
    
    	return $sort;
    }
    
    public static function constructCleanUrl($urlParams,$categoryType='',$keepOtherParams=false){
        Timer::start(__METHOD__);
        
    	if($categoryType==''){
    		$categoryInfo = self::getCategoryInfo($urlParams[0]['cat']);
    		$categoryType = $categoryInfo['type'];
    	}
    
    	$man_id = self::MAN_ID;
    
    	$urlProcess = self::getUrlHelper();
    
    	$setting = self::getSiteSetting();
    
    	$importantFiltersCount = -1; //all at left side of #
    	if(isset($setting['product_how_many_important_filter_combine']) && is_numeric($setting['product_how_many_important_filter_combine'])){
    		$importantFiltersCount = $setting['product_how_many_important_filter_combine'];
    	}
    
    	$qSearchLeft = 0;
    	if($categoryType=='Structured'){
    		if(!empty($setting['product_structured_q_search_left_sharp'])){
    			$qSearchLeft = $setting['product_structured_q_search_left_sharp'];
    		}
    	}elseif($categoryType=='FreeText'){
    		if(!empty($setting['product_freetext_q_search_left_sharp'])){
    			$qSearchLeft = $setting['product_freetext_q_search_left_sharp'];
    		}
    	}
    
    	$importantFiltersArray = self::getImportantFilters($urlParams[0]['cat']);
    
    	$anchorStr = "";
    	$newParams = array();
    	$anchorArray = array();
    	$otherParams = array("numberOfProducts","price_max","price_min","retailer","sort","viewType");
    
    	$searchUrlParams = new Container('searchUrlParams');
    
    	if($qSearchLeft && $importantFiltersCount>0 && (!empty($urlParams[1]["q"]) || !empty($urlParams[1]["search"]))){
    		$importantFiltersCount--;
    	}
    
    	foreach($urlParams[1] as $key=>$value){
    		$key = trim($key);
    			
    		if($key=="ref" || $key=="sp" || $key=="other_hits" || $key=="curl"){
    			$searchUrlParams->$key = trim($value);
    		}elseif($key=="q" || $key=="search"){
    			if($qSearchLeft){
    				$newParams[$key] = $value;
    			}else{
    				$anchorArray[$key] = $value;
    			}
    		}elseif($key=="man_id" || substr($key,0,5)=="attr_"){
    			if((substr($key,0,5)=="attr_" && in_array(str_replace("attr_","",$key),$importantFiltersArray)) || ($key=="man_id" && in_array($man_id,$importantFiltersArray))){
    				if($importantFiltersCount > 0 || $importantFiltersCount == -1){
    					$newParams[$key] = $value;
    					if($importantFiltersCount > 0){
    						$importantFiltersCount--;
    					}
    				}else{
    					$anchorArray[$key] = $value;
    				}
    			}else{
    				$anchorArray[$key] = $value;
    			}
    		}elseif($key=="page"){
    			$newParams[$key] = $value;
    		}elseif(in_array($key,$otherParams)){
    			$anchorArray[$key] = $value;
    		}else{
    			if($keepOtherParams){
    				$newParams[$key] = $value;
    			}
    		}
    	}
    	$anchorStr = http_build_query($anchorArray);
    	$cleanUrl = $urlProcess('product_list', $urlParams[0],$newParams,$anchorStr,false);
    	
    	Timer::end(__METHOD__);
    	
    	return $cleanUrl;
    }
    
    static public function getSiteSetting(){
        if (null == self::$siteSetting){
            self::$siteSetting = Data::getInstance()->get('siteSetting');
        }
        return self::$siteSetting;
    }
    
    static public function getUrlHelper(){
    	if (null == self::$urlHelper){
    		self::$urlHelper = Util::getViewHelper("GetUrl");
    	}
    	return self::$urlHelper;
    }
    
    static public function getImportantFilters($cat) {
        Timer::start(__METHOD__);
    	if (empty(self::$importantFilters[$cat])) {
    		$articleModel = new ArticleCms();
    		$params['cid'] = $cat;
    		$filterSettings = $articleModel->fetchContent('important-filter',$params);
    			
    		$importantFilters = (string)$filterSettings['filters'];
    			
    		$selectedFilters = array();
    		if ($importantFilters) {
    			$importantFilters = explode("\n", $importantFilters);
    			foreach ($importantFilters as $filter) {
    				$parts = explode('|||', $filter);
    				if ($parts) {
    					$selectedFilters[] = trim($parts[0]);
    				}
    			}
    		}
    		self::$importantFilters[$cat] = $selectedFilters;
    	}
    	Timer::end(__METHOD__);
    	return self::$importantFilters[$cat];
    }
    
    protected function _setDefaultParams($params){
        $siteSetting = Data::getInstance()->get('siteSetting');
        $categoryID = $params['cat'];
        $categoryViewTypeKey = "category_{$categoryID}_list_mode";
        $defaultViewTypeKey = 'category_0_'.self::$categoryType.'_list_mode';
        $defaultViewType = isset($siteSetting[$defaultViewTypeKey]) ? $siteSetting[$defaultViewTypeKey] : $siteSetting['productlist_default_view_type'];
        $ar['viewType'] = isset($siteSetting[$categoryViewTypeKey])? $siteSetting[$categoryViewTypeKey] : $defaultViewType;
        
        $categoryListSortModeKey = "category_{$categoryID}_list_sort_mode";
        $defaultSortKey = 'category_0_'.self::$categoryType.'_list_sort_mode';
        $defaultSort = isset($siteSetting[$defaultSortKey]) ? $siteSetting[$defaultSortKey] : $siteSetting['productlist_default_sort_order'];
        $categorySort = isset($siteSetting[$categoryListSortModeKey]) ? $siteSetting[$categoryListSortModeKey] : $defaultSort;
        $ar['sort'] = $this->sortSettingToUrlMapping[$categorySort];
        
        $this->urlParamsDefault = $ar;
        
        return $ar['sort'];
    }
    
    /**
     *处理cl页面的api参数并返回 
     */
    protected function _parseListParams($apiParams){
        $siteSetting = Data::getInstance()->get('siteSetting');
        $categoryID = $apiParams['cat'];
        if(!isset($apiParams['sort'])){
            $apiParams['sort'] = $this->urlParamsAllowed['sort'][$this->urlParamsDefault['sort']];
        }
        
        if(array_key_exists('keyword', $apiParams)) {
        	if(!$apiParams['sort']){
        		$defaultSort = $siteSetting['productlist_default_sort_order'];
        		$apiParams['sort'] = Util::getProductSorting($defaultSort);
        	}
        }elseif (!array_key_exists('sort', $apiParams)) {
        	$apiParams['sort'] = $this->urlParamsAllowed['sort'][$this->urlParamsDefault['sort']];
        }
        // 如果不显示非付费商家，并且category 是 freetext，则增加 only_pay 参数
        $showNoneCustomPrices = (int)$siteSetting['productlist_show_freetext_none_custom_prices'];
        //Issue 339771 - PR Prod - 5 different retailers first in freetext
        $categoryInfo = self::getCategoryInfo($categoryID);
        
        if (($categoryInfo['type'] == 'FreeText') && ($apiParams['sort'] == 'pop')){
        	$apiParams['remixnum'] = 5;
        }
        if ($categoryInfo['type']=='FreeText' && $showNoneCustomPrices == 0 && $categoryInfo['isHybrid']!="1") {
        	$apiParams['only_pay'] = 1;
        
        	// 如果 category 是 freetext 并且需要显示免费商家且排序为 pop，则将 pop 改为 pricestatus
        } else if ($categoryInfo['type']=='FreeText' && $showNoneCustomPrices == 1
        		&& $apiParams['sort'] == 'pop'){
        	$apiParams['sort'] = 'pricestatus';
        }

        if($categoryInfo['type']=="FreeText" && $categoryInfo['isHybrid']=="1" ){
        	$apiParams['offercount'] = '3';
        	$apiParams['offer_sort'] = $siteSetting['productlist_hybrid_offer_sort_order'];
        	if((int)$siteSetting['productlist_hybrid_show_sponsored_link']==1){
        		$apiParams['featured_offer_count'] = '3';
        		$apiParams['featured_offer_sort'] = 'cpc';
        	}
        }elseif($categoryInfo['type']=="FreeText"){
            $apiParams ['offercount'] = 1;
        }
        return $apiParams;
    }
	    
    protected static function _getProductPriceUrl($productsObj = null,$urlParams = array()){
        
        if (is_null($productsObj)){
            return '';
        }
        
        $product = $productsObj->products[0];
        $GetUrl = Util::getViewHelper("GetUrl");
        $redirectUrl = $GetUrl(
        		'product_price'
        		,array(
        				'ch'	=> $product->channelId
        				,'cat' 	=> $product->categoryId
        				,'pid' 	=> $product->id
        				,'categoryName' => $product->categoryName
        				,'productName' 	=> $product->name
        		)
        		,$urlParams[1]
        		,''
        		,false
        );
        return $redirectUrl;
    }
    
    public function getTopThreeLinks($productsObj){
        $topLoop = 1;
        foreach ($productsObj->products as $topProduct){
        	$topParams = array(
        			'ch'	=>  $topProduct->channelId
        			,'cat'	=> $topProduct->categoryId
        			,'categoryName' => $topProduct->categoryName
        	);
        	$topCompareParam["c_" . $topProduct->id] = $topProduct->id;
        	if($topLoop==3){
        		break;
        	}
        	$topLoop++;
        }
        $urlProcess = Util::getViewHelper("GetUrl");
        return $urlProcess('comparing_products', $topParams, $topCompareParam);
    }
    
    /**
     * 获取排序选项
     *
     * @return string
     */
    public function getSortMode($apiParams) {    
    	if(isset($apiParams['sort'])) {
    		$sort = $apiParams['sort'];
    		return array_search($sort, $this->urlParamsAllowed['sort']);
    	}
    	return (empty($sort) && $sort!='0')?$this->urlParamsDefault['sort']:$sort;
    }
    
    /**
     * 是否显示相关度排序选项
     *
     * @return boolean
     */
    public static function getRelevanceShow($apiParams) {
    	if(isset($apiParams['keyword']) && (empty($apiParams['sort']))) {
    		return true;
    	}else {
    		return false;
    	}
    }
    
    public static function getProductlistNoindex($categoryType){
        $data = Data::getInstance();
        //Issue 300932 - Trigger metatag Noindex when CL containing ONE or less Products
        $siteSetting = $data->get('siteSetting');
        if($categoryType == 'Structured'){
            $param = "productlist_noindex_structured_cl";
        }else{
            $param = 'productlist_noindex_freetext_cl';
        }
        $$param = isset($siteSetting[$param]) ?  $siteSetting[$param] : -1;
         
        //Issue 300931 - Fix Canonical inheritence from whitelabel
        $site = $data->get('site');
        $siteCountry = $site['country'];
        $siteType = $site['site_type'];
         
        if($siteType!="MainSite"){
            $$param= isset($siteSetting[$param]) ?  $siteSetting[$param] : $$param;
        }
         
        if(!is_numeric($$param)){
        	$$param = -1;
        }
        //就是两个$$
        return $$param;
    }
    /**
     * list页面格式化product
     */
    protected  function _formatProductsList($products){
        //如果没有matchAttributes或者matchAttributes不需要跳转
        if(empty($products->matchAttributes) || self::$matchAttributesNeedRedirect == false){
        	//Issue 309985 - Remove links from selected retailers, category level
        	$siteSetting = Data::getInstance()->get('siteSetting');
        	if (trim($siteSetting['product_list_remove_retailers']) != ''){
        		$removeRetailers = explode(',', $siteSetting['product_list_remove_retailers']);
        		$removeRetailers = array_map('trim', $removeRetailers);
        	}else{
        		$removeRetailers = '';
        	}
        	$validator = new Digits();
        	foreach ((array)$products->products as $key => $product) {
        		//Issue 309985 - Remove links from selected retailers, category level
        		if ($removeRetailers){
        			$offers = array();
        			foreach ((array)$product->offers as $offer) {
        				if(in_array($offer->retailerId, $removeRetailers)){
        					$offer->tag = 'WOLINK';
        					//$offer->relatedOffers = array();
        				}
        				$offers[] = $offer;
        			}
        			$product->offers = $offers;
        		}
        			
        		//handle product time, used in is "New" product
        		$timestamp =  strtotime($product->createTime);
        
        		$currentTimeStamp = time();
        		if($currentTimeStamp <= $timestamp + $this->newProductTimeDistance) {
        			$products->products[$key]->isNew = true;
        		}else {
        			$products->products[$key]->isNew = false;
        		}
        
        
        		//handle round price  留到view中处理
        		$oPrice = new Price();
        		$products->products[$key]->listPrice = $oPrice->getPriceFormat($product);
        		        		 
        		$product = Util::formatProduct($product,false);
        		
        		$products->products[$key]->name = $product->name;
        		// use long description for freetext product
        	    $products->products[$key]->shortDescription = $product->shortDescription;     	
        		
        		//handle sales package
        		if(	!is_object($product->manufacturer)
        				|| !property_exists($product->manufacturer, 'id')
        				|| !property_exists($product->manufacturer, 'name')
        				|| !$validator->isValid($product->manufacturer->id)
        		) {
        			$products->products[$key]->salesPackage = null;
        		}else {
        
        			$params = array(
        					'productId' 		=> $product->id
        					,'categoryId'		=> $product->categoryId
        					,'manufacturerId'	=> $product->manufacturer->id
        					,'manufacturerName'	=> $product->manufacturer->name
        			);
        			$products->products[$key]->salesPackage = $this->_getSalesPackageEngine($params);
        		}
        		 
        		//calculate money saving
        		$moneySaving = Util::calMoneySaving($products->products[$key]->localMinPrice->value, $products->products[$key]->avgPrice->value);
        		$products->products[$key]->moneySaving = $moneySaving;
        		if(property_exists($product, "productType")){
        		    $products->products[$key]->productType = strtolower($product->productType);
        		}
        	}
        }
        
        return $products;
    }
    
    /**
     * 根据 category/product/manufacturer id 获取可用的 sales package 的信息
     *
     * @param array $params
     * @return array
     */
    protected  function _getSalesPackageEngine($params) {
        Timer::start(__METHOD__);
        
    	$ret = array(
    			'logo'		=> NULL
    			,'message'	=> NULL
    	);
    	 
    	$urlProcess = Util::getViewHelper("GetUrl");
    	 
    	$productId = $params['productId'];
    	$categoryId = $params['categoryId'];
    	$manufacturerId = $params['manufacturerId'];
    	$manufacturerName = $params['manufacturerName'];
    	 
    	if($productId <= 0 || $categoryId <= 0 || $manufacturerId <= 0) {
    		return null;
    	}
    
    	$siteObj = Data::getInstance()->get("site");
    	$country = $siteObj['country'];
    	 
    	if (null === self::$salesMessage) {
    		//messageData
    		$dataFile = ROOT_PATH . '/module/Shopping/data/salespackage/data/' . $country . '_SALESMESSAGE.php';
    		if(file_exists($dataFile)) {
    			self::$salesMessage = require_once $dataFile;
    		}else {
    			self::$salesMessage = array();
    		}
    	}

    	$data = self::$salesMessage;
    
    	if(array_key_exists($manufacturerId, (array)$data)) {
    
    		$data = $data[$manufacturerId];
    
    		//handle useful data
    		$scopeData = array();
    		$siteId = $siteObj['site_id'];
    		foreach ($data['scopes'] as $item) {
    			if($item['siteId'] == $siteId || $item['siteId'] == '0') {
    				array_push($scopeData, $item);
    			}
    		}
    
    		do {
    			//handle Product level
    			foreach ($scopeData as $item) {
    				if($item['level'] == 'Product') {
    					if($item['siteId'] == $siteId) {
    						if(in_array($productId, $item['scopeId'])) {
    							$ret['message'] = $item['message'];
    							break 2;
    						}
    					}
    				}
    			}
    			foreach($scopeData as $item) {
    				if($item['level'] == 'Product') {
    					if($item['siteId'] == '0') {
    						if(in_array($productId, $item['scopeId'])) {
    							$ret['message'] = $item['message'];
    							break 2;
    						}
    					}
    				}
    			}
    		  
    			//handle Products level
    			foreach ($scopeData as $item) {
    				if($item['level'] == 'Products') {
    					if($item['siteId'] == $siteId) {
    						if(in_array($productId, $item['scopeId'])) {
    							$ret['message'] = $item['message'];
    							break 2;
    						}
    					}
    				}
    			}
    			foreach($scopeData as $item) {
    				if($item['level'] == 'Products') {
    					if($item['siteId'] == '0') {
    						if(in_array($productId, $item['scopeId'])) {
    							$ret['message'] = $item['message'];
    							break 2;
    						}
    					}
    				}
    			}
    		  
    			//handle Category level
    			foreach ($scopeData as $item) {
    				if($item['level'] == 'Category') {
    					if($item['siteId'] == $siteId) {
    						if(in_array($categoryId, $item['scopeId'])) {
    							$ret['message'] = $item['message'];
    							break 2;
    						}
    					}
    				}
    			}
    			foreach ($scopeData as $item) {
    				if($item['level'] == 'Category') {
    					if($item['siteId'] == '0') {
    						if(in_array($categoryId, $item['scopeId'])) {
    							$ret['message'] = $item['message'];
    							break 2;
    						}
    					}
    				}
    			}
    		  
    			//handle Categories level
    			foreach ($scopeData as $item) {
    				if($item['level'] == 'Categories') {
    					if($item['siteId'] == $siteId) {
    						if(in_array($categoryId, $item['scopeId'])) {
    							$ret['message'] = $item['message'];
    							break 2;
    						}
    					}
    				}
    			}
    			foreach ($scopeData as $item) {
    				if($item['level'] == 'Categories') {
    					if($item['siteId'] == '0') {
    						if(in_array($categoryId, $item['scopeId'])) {
    							$ret['message'] = $item['message'];
    							break 2;
    						}
    					}
    				}
    			}
    		  
    			//handle ALL level
    			foreach ($scopeData as $item) {
    				if($item['level'] == 'ALL') {
    					if($item['siteId'] == $siteId) {
    						if (empty($item['scopeId'])) {
    							$ret['message'] = $item['message'];
    							break 2;
    						} else if(in_array($siteId, $item['scopeId'])) {
    							$ret['message'] = $item['message'];
    							break 2;
    						}
    					}
    				}
    			}
    			foreach ($scopeData as $item) {
    				if($item['level'] == 'ALL') {
    					if($item['siteId'] == '0') {
    						if (empty($item['scopeId'])) {
    							$ret['message'] = $item['message'];
    							break 2;
    						} else if(in_array($siteId, $item['scopeId'])) {
    							$ret['message'] = $item['message'];
    							break 2;
    						}
    					}
    				}
    			}
    		}while(false);
    
    		//if still null then use default
    		if(empty($ret['message'])) {
    			$ret['message'] = $data['message'];
    		}
    		$ret['message'] = stripcslashes($ret['message']);
    	}
    	 
    	unset($data);
    	 
    	if (null === self::$brandLogo) {
    		//logoData
    		$dataFile = ROOT_PATH . '/module/Shopping/data/salespackage/data/' . $country . '_LOGO.php';
    		if(file_exists($dataFile)) {
    			self::$brandLogo = require_once $dataFile;
    		}else {
    			self::$brandLogo = array();
    		}
    	}
    	 
    	$data = self::$brandLogo;
    	 
    	if(array_key_exists($manufacturerId, (array)$data)) {
    
    		$data = $data[$manufacturerId];
    
    		$spec = array(
    				'src'		=> $data['logo']
    				,'width' 	=> $data['width']
    				,'height' 	=> $data['height']
    				,'name'		=> $manufacturerName
    				,'url'		=> $urlProcess('manufacturer_info', array('brand' => $manufacturerId, 'manufacturerName' => $manufacturerName))
    		);
    
    
    
    		$normalText = '<a href="' . $spec['url'] . '" title="' . htmlentities($spec['name']) . '">' . htmlentities($spec['name']) . '</a>';
    		$boldText = '<a href="' . $spec['url'] . '" title="' . htmlentities($spec['name']) . '"><strong>' . htmlentities($spec['name']) . '</strong></a>';
    
    
    		$logo = '<a href="' . $spec['url'] . '" title="' . htmlentities($spec['name']) . '"><img src="' . $spec['src'] . '" />{{$textUnderLogo}}</a>';
    
    		$tmp = array(
    				'NORMAL TEXT' 	=> $normalText
    				,'LOGO'			=> $logo
    				,'BOLD TEXT'	=> $boldText
    		);
    
    
    		//handle useful data
    		$scopeData = array();
    		$siteId = $siteObj['site_id'];
    		foreach ($data['scopes'] as $item) {
    			if($item['siteId'] == $siteId || $item['siteId'] == '0') {
    				array_push($scopeData, $item);
    			}
    		}
    		unset($data);
    
    		do {
    			//handle Product level
    			foreach ($scopeData as $item) {
    				if($item['level'] == 'Product') {
    					if($item['siteId'] == $siteId) {
    						if(in_array($productId, $item['scopeId'])) {
    							$ret['logo'] = $this->_parseForSalesPackageLogo($tmp[$item['visuality']], $item['underLogo']);
    							break 2;
    						}
    					}
    				}
    			}
    			foreach($scopeData as $item) {
    				if($item['level'] == 'Product') {
    					if($item['siteId'] == '0') {
    						if(in_array($productId, $item['scopeId'])) {
    							$ret['logo'] = $this->_parseForSalesPackageLogo($tmp[$item['visuality']], $item['underLogo']);
    							break 2;
    						}
    					}
    				}
    			}
    		  
    			//handle Products level
    			foreach ($scopeData as $item) {
    				if($item['level'] == 'Products') {
    					if($item['siteId'] == $siteId) {
    						if(in_array($productId, $item['scopeId'])) {
    							$ret['logo'] = $this->_parseForSalesPackageLogo($tmp[$item['visuality']], $item['underLogo']);
    							break 2;
    						}
    					}
    				}
    			}
    			foreach($scopeData as $item) {
    				if($item['level'] == 'Products') {
    					if($item['siteId'] == '0') {
    						if(in_array($productId, $item['scopeId'])) {
    							$ret['logo'] = $this->_parseForSalesPackageLogo($tmp[$item['visuality']], $item['underLogo']);
    							break 2;
    						}
    					}
    				}
    			}
    		  
    			//handle Category level
    			foreach ($scopeData as $item) {
    				if($item['level'] == 'Category') {
    					if($item['siteId'] == $siteId) {
    						if(in_array($categoryId, $item['scopeId'])) {
    							$ret['logo'] = $this->_parseForSalesPackageLogo($tmp[$item['visuality']], $item['underLogo']);
    							break 2;
    						}
    					}
    				}
    			}
    			foreach ($scopeData as $item) {
    				if($item['level'] == 'Category') {
    					if($item['siteId'] == '0') {
    						if(in_array($categoryId, $item['scopeId'])) {
    							$ret['logo'] = $this->_parseForSalesPackageLogo($tmp[$item['visuality']], $item['underLogo']);
    							break 2;
    						}
    					}
    				}
    			}
    		  
    			//handle Categories level
    			foreach ($scopeData as $item) {
    				if($item['level'] == 'Categories') {
    					if($item['siteId'] == $siteId) {
    						if(in_array($categoryId, $item['scopeId'])) {
    							$ret['logo'] = $this->_parseForSalesPackageLogo($tmp[$item['visuality']], $item['underLogo']);
    							break 2;
    						}
    					}
    				}
    			}
    			foreach ($scopeData as $item) {
    				if($item['level'] == 'Categories') {
    					if($item['siteId'] == '0') {
    						if(in_array($categoryId, $item['scopeId'])) {
    							$ret['logo'] = $this->_parseForSalesPackageLogo($tmp[$item['visuality']], $item['underLogo']);
    							break 2;
    						}
    					}
    				}
    			}
    		  
    			//handle ALL level
    			foreach ($scopeData as $item) {
    				if($item['level'] == 'ALL') {
    					if($item['siteId'] == $siteId) {
    						if (empty($item['scopeId'])) {
    							$ret['logo'] = $this->_parseForSalesPackageLogo($tmp[$item['visuality']], $item['underLogo']);
    							break 2;
    						} else if(in_array($siteId, $item['scopeId'])) {
    							$ret['logo'] = $this->_parseForSalesPackageLogo($tmp[$item['visuality']], $item['underLogo']);
    							break 2;
    						}
    					}
    				}
    			}
    			foreach ($scopeData as $item) {
    				if($item['level'] == 'ALL') {
    					if($item['siteId'] == '0') {
    						if (empty($item['scopeId'])) {
    							$ret['logo'] = $this->_parseForSalesPackageLogo($tmp[$item['visuality']], $item['underLogo']);
    							break 2;
    						} else if(in_array($siteId, $item['scopeId'])) {
    							$ret['logo'] = $this->_parseForSalesPackageLogo($tmp[$item['visuality']], $item['underLogo']);
    							break 2;
    						}
    					}
    				}
    			}
    		}while(false);
    
    		//if still null then use default
    		if(empty($ret['logo'])) {
    			$ret['logo'] = $tmp[$data['visuality']];
    		}
    		$ret['logo'] = $this->_parseForSalesPackageLogo($ret['logo'], $data['underLogo'], true);
    	}
    	 
    	unset($data);

    	Timer::end(__METHOD__);
    	
    	return $ret;
    }
    
    /**
     * 格式化 message under logo
     *
     * @param string $string
     * @param string $replace
     * @param boolean $enforce
     * @return string
     */
    protected function _parseForSalesPackageLogo($string, $replace, $enforce = false) {
    	 
    	if(!$enforce && empty($replace)) {
    		return $string;
    	}
    	return str_replace('{{$textUnderLogo}}', "<br />" . htmlentities(stripslashes($replace)), $string);
    	 
    }
    
    /**
     * 
     * @param array $urlParams
     * @param array $categoryAttributes $this->category->attributes
     * @param string $categoryType  $this->category->type [Structed|Freetext]
     */
    public function getFilter($urlParams, $categoryAttributes, $categoryType, $apiParams){
        Timer::start(__METHOD__);
        
        $data = Data::getInstance();
        $siteSetting = $data->get('siteSetting');
        
        $activateSharpLogic = $siteSetting['product_activate_sharp_logic'];
        
        $expandCount = 3;
        if( array_key_exists('productlist_filter_expand_count', $siteSetting) ){
        $expandCount = $siteSetting['productlist_filter_expand_count'];
        }
        
        // 处理 site setting 中设置的需要隐藏 manufacturer 的 category
        $filters = $this->_formatFilter($urlParams, $categoryAttributes, $apiParams);
//         self::$formatedFilter = $filters;
        //@20091021 yiying edited for hiding manufacture filter block for cl/xx page, filter IDs can be set by siteSetting.
        if( array_key_exists('productlist_manufacture_filter', $siteSetting) ){
        	$filterManufactureIdArray = explode(",", (string)$siteSetting['productlist_manufacture_filter']);
        	if( in_array( $urlParams[0]['cat'], $filterManufactureIdArray ) ){
        		// categoryId in siteSetting manufacutre filter ids, ready to delete $filter[0].
        		if( isset($filters[0]) && $filters[0]->labelId == self::MAN_ID){
        			array_shift($filters);
        		}
        	}
        }
        
        // get selected filters
        $selectedFiltersList = $this->_getSelectedFilters($filters);
        $retailerCount = $selectedFiltersList['retailerCount'];
        $selectedFilters = $selectedFiltersList['selectedFilters'];

        $refineObj = $this->getRefine($urlParams);
        
        $getLang = Util::getViewHelper('GetLang');
        $escape = Util::getViewHelper('escapeHtml');
        if(array_key_exists('search', $urlParams[1]) || ($activateSharpLogic=='1' && !empty($refineObj->search)) ){
        	$selectedFilters[] = array("url"   => $refineObj->removeUrl,
        			"label" => $getLang('PR.TextFilters.SEARCH'),
        			"value" => $escape($refineObj->search));
        }
         
        
        //**** Other matching categories,manufacturers,retailers *** start **//
        
        $searchUrlParams = new Container('searchUrlParams');
        $matchMore = null;
        
        $siteType = $data->get('siteType');
        $pricewatch = array();
        if ('mobile' != $siteType){
        	$pricewatch = $this->_getPriceWatch($urlParams);
        }
        
        try {
            if(isset($searchUrlParams->other_hits) && !empty($searchUrlParams->other_hits)) {
                $urlParams[1]['other_hits'] = $searchUrlParams->other_hits;
            	$matchMore = self::getMatchMore($urlParams[1]);
            	unset($urlParams[1]['other_hits']);
            	$searchUrlParams->other_hits = "";
            }elseif(array_key_exists('other_hits', $urlParams[1])) {
            	$matchMore = self::getMatchMore($urlParams[1]);
            }
        } catch (\Exception $e) {}
        
        $relatedCategories = '';
       
        if ('mobile' != $siteType){
            //*** relared categories  start **//
            if(empty($urlParams[1]['q']) && empty($matchMore) && empty($selectedFilters)){
            	$relatedCategories = $this->_getArticleContent('related-categories',array("cid" => $urlParams[0]['cat']));
            }
            //*** end **//
        }
        
        $detail = new \stdClass();
        $detail->filter = $filters;
        $detail->refine = $refineObj;
        $detail->selectedFilters = $selectedFilters;
        $detail->moreFilters = $this->getMoreFilters($filters, $urlParams);
        $detailObj = $detail;
        
        $formatMapping = Price::parseFormat($siteSetting['priceformat_structured_price']);
        $decimalSeparator = $formatMapping['dec_point'];
        
        // 如果 filter 选中超过 1 个，则需要在给所有的 filter 链接加上 nofollow 属性
        $alwaysnofollow = $this->_addNoFollow($urlParams);
        
        $urlParamsMerged = array_merge($urlParams[0],$urlParams[1]);
        $qSearchLeft = self::checkSearchIsLeft($urlParamsMerged,$categoryType);
        
        $result = array(
            'urlParams' => $urlParams,
            'matchMore' => $matchMore,
            'expandCount' => $expandCount,
            'relatedCategories' => $relatedCategories,
            'pricewatch' => $pricewatch,
            'detailFilter' => $detailObj,
            'decimalSeparator' => $decimalSeparator,
            'alwaysnofollow' => $alwaysnofollow,
            'qSearchLeft' => $qSearchLeft,
            'retailerCount' => $retailerCount,
            'activateSharpLogic' => $activateSharpLogic,
            'cat' => $urlParams[0]['cat'],
        );
        
        Timer::end(__METHOD__);
        
        return $result;
    }
    
    public function addFilterToGaData($category,$urlParams,$googleAnalyticsData, $apiParams){
        
        Timer::start(__METHOD__);
        
        // Manufacturer and Filters
        $selectedFilters = $priceRange = array();
        
        $filters = $this->_formatFilter($urlParams,$category->attributes, $apiParams);
        
        foreach ($filters as $item) {
        	if (false === $item->selected) {
        		continue;
        	}
        	if ($item->labelId == self::MAN_ID) {
        		$item2 = $item->popular[$item->selected];
        		$googleAnalyticsData['Manufacturer'] = "{$item2->name}({$item2->id})";
        	} else if ($item->labelId == self::PRI_ID) {
        		if ($item->minPrice !== '') {
        			$priceRange[] = "MinPrice={$item->minPrice}";
        		}
        		if ($item->maxPrice !== '') {
        			$priceRange[] = "MaxPrice={$item->maxPrice}";
        		}
        	} else if (!empty($item->popular)) {
        		foreach ($item->popular as $item2) {
        			if($item2->selected){
        				$selectedFilters[] = "{$item->labelName}={$item2->name}";
        			}
        		}
        	}
        }
        
        if ($selectedFilters) {
        	$googleAnalyticsData['Filters'] = implode(':', $selectedFilters);
        }
         
        if (!empty($priceRange)) {
        	$googleAnalyticsData['PriceFilter'] = implode(' - ', $priceRange);
        }
        
        Timer::end(__METHOD__);
        
        return $googleAnalyticsData;
    }
    
    /**
     * 获取 filter 信息，包含有产品和没有产品的以及已经被选中的
     *
     * @return array
     */
    protected function _formatFilter($urlParams = array(), $categoryAttributes = array(), $apiParams = array()) {
        
        Timer::start(__METHOD__);
        if (NULL !== self::$formatedFilter) {
            Timer::end(__METHOD__);
        	return self::$formatedFilter;
        }
        unset($urlParams[1]['timeStamp']);
    	$needNewFilters = 	array_key_exists('brand', $apiParams)
                            	|| array_key_exists('merchant', $apiParams)
                            	    //?
                            	|| array_key_exists('attr', $apiParams)
                            	|| array_key_exists('keyword', $apiParams)
                            	    //?
                            	|| (array_key_exists('price', $apiParams) && $apiParams['price'] != $this->apiParamsDefault['price']);
    	
    	$nullReturn = false;

    	//如果 $needNewFilters 存在，才去获取base filters,否则，base filters 就是 category filters.
    	if($needNewFilters) {
    	    $baseFilters = $this->_getBaseFilter($urlParams, $apiParams);
    	    	
    	    //start of handle selected we here havnt handle pricerange,
    	    //coz pricerange use removeUrl to descide whether it is seleted.
    	    $selectedIds = $this->_getSelectedIds($apiParams);
    	    
    	    //handle selected
    	    $baseFilters = $this->_addBaseFiltersSelected($baseFilters, $selectedIds);
    	    
    		do {
    			if(!$categoryAttributes) {
    				$nullReturn = true;
    				
    				//只有在这一种情况下才执行此段逻辑 
					if(!empty($baseFilters)) {
						foreach ($baseFilters as $key => $filter) {
							foreach ($filter->options as $key2 => $option) {
								if($nullReturn) {
									$baseFilters[$key]->options[$key2]->prodcount = 0;
								}
							}
						}
					}
    				break;
    			}
    			$baseFilters = $this->_addBaseFiltersProdCount($baseFilters, $categoryAttributes);
    		}while (false);
    	}else{
    	    $baseFilters = $categoryAttributes;
    	}
  		    
    	$baseFilters = $this->_handleNoProductItem($baseFilters);
    	
    	// Issue 249680 - URL - URL rewrite 1.0 : Keep the filters param in order
//     	$sortUrlParams = $this->_urlParamsSort($urlParams);
    	
    	//handle selected.
    	$attributes = $this->_handleSelected($baseFilters, $urlParams);

    	//start of handle price range
    	$tmp = $this->_handlePriceRange($urlParams);
    	array_unshift($attributes, $tmp);
   
        //start of handle attribute order, manufacturer, store, price, anyother attributes
        // move Manufacturer to the first and move Store to the second/end
        $attributes = $this->_handleAttributeOrder($attributes);
    
        //Issue 309992 - Improve Boolean filters
        $attributes = $this->_improveBoolFilters($attributes, $urlParams[0]['cat']);
        
        self::$formatedFilter = $attributes;
    
        Timer::end(__METHOD__);
    
        return $attributes;
    }
    
    /**
     * urlParams[1] sort
     * @param array $params
     */
    protected function _urlParamsSort($urlParams = array()){
    
    	if (!empty($urlParams[1]) && count($urlParams[1])>1){
    		ksort($urlParams[1]);
    		
    		if (!empty($urlParams[1]['man_id'])) { // 将 man_id 放到第一位
    			$manId = $urlParams[1]['man_id'];
    			unset($urlParams[1]['man_id']);
    			$urlParams[1] = array_merge(array('man_id'=>$manId), $urlParams[1]);
    		}
    	}
    
    	return $urlParams;
    }
    
    protected function _getBaseFilter($urlParams, $apiParams){
        $requestParams = array(
    		'cat' => $urlParams[0]['cat'],
    		'attrstart' => 0,
    		'attrcount' => 10000,
        );        
        $baseFilterParams = array_merge($this->apiParamsDefault, $requestParams);
        unset($requestParams);
        	
        //Issue 310578 - Exclude retailer completely in the API requests
        if ($apiParams['offer_exclude']){
            $baseFilterParams['offer_exclude'] = $apiParams['offer_exclude'];
        }
        	
        // get base filter from cache
        $cache = Cache::get('cache');
        $cacheKey = Util::makeCacheKey(array_merge(array('func'=>__METHOD__), $baseFilterParams));
        $baseFilters = $cache->getItem($cacheKey);
        if (null === $baseFilters) {
        	$baseFilters = $this->getAttributes($baseFilterParams);
        	$cache->addItem($cacheKey, $baseFilters);
        }
        
        return $baseFilters;
    }
    
    protected function _getSelectedFilters($filters = array()){
        Timer::start(__METHOD__);
        
        $retailerCount = 0;
        $selectedFilters = array();
        
        if ($filters){
            $getLangViewHelper = Util::getViewHelper('GetLang');
           
            $site = Data::getInstance()->get('site');
            $symbolCountry = $site['currency'];
            
            if (self::$currencyMapping == null) {
            	$currencyMapping = include (ROOT_PATH . '/module/Shopping/config/currency.php');
            	self::$currencyMapping = $currencyMapping;
            } else {
            	$currencyMapping = self::$currencyMapping;
            }
            
            $currencyMapping = include (ROOT_PATH . '/module/Shopping/config/currency.php');
            $symbol = $currencyMapping[$symbolCountry];
            
            foreach ($filters as $key => $filter) {
            	if($filter->labelId == self::PRI_ID/*'Price'*/ && property_exists($filter, 'removeUrl')) {
            
            		if(empty($filter->minPrice)) {
            			$priceWithSymbol = Price::combineSymbolWithPrice($filter->maxPrice, $symbol);
            			$value = $getLangViewHelper('PR.TextFilters.PRICE_MAX') . ' ' . $priceWithSymbol;
            		} else if(empty($filter->maxPrice)) {
            			$priceWithSymbol = Price::combineSymbolWithPrice($filter->minPrice, $symbol);
            			$value = $getLangViewHelper('PR.TextFilters.PRICE_MIN') . ' ' . $priceWithSymbol;
            		} else {
            			$priceWithSymbol = Price::combineSymbolWithPrice($filter->minPrice . ' - ' . $filter->maxPrice, $symbol);
            			$value = $priceWithSymbol;
            		}
            
            		$filters[$key]->priceWithSymbol = $value;
            
            		$selectedFilters[] = array(
            				'url' => $filter->removeUrl,
            				'label' => $getLangViewHelper('PR.TextFilters.PRICE'),
            				'value' => $value,
            		);
            	} else {
            		if($filter->labelId == self::MAN_ID){
            			$filter->labelName = $getLangViewHelper('PR.ProductList.MANUFACTURER');
            		}elseif($filter->labelId == self::STO_ID){
            			$filter->labelName = $getLangViewHelper('PR.ProductList.STORE');
            
            			// 如果被选中，则为 1
            			if (false !== $filter->selected) {
            				$retailerCount = 1;
            			} else {
            				// 如果 <= 5 个，则拿 popular 里面的做统计
            				if (empty($filter->upper) && !empty($filter->popular)) {
            					foreach ((array)$filter->popular as $option) {
            						if ($option->prodcount > 0) {
            							$retailerCount += 1;
            						}
            					}
            					// 否则，拿 upper 和 lower 做统计
            				} else {
            					foreach ((array)$filter->upper as $option) {
            						if ($option->prodcount > 0) {
            							$retailerCount += 1;
            						}
            					}
            					foreach ((array)$filter->lower as $option) {
            						if ($option->prodcount > 0) {
            							$retailerCount += 1;
            						}
            					}
            				}
            			}
            		}
            
            		$options = (empty($filter->upper))?$filter->popular:array_merge($filter->upper, $filter->lower);
            		foreach ((array)$options as $item) {
            
            			if ($item->selected){
            				$selectedFilters[] = array(
        						'url' => $item->url,
        						'label' => $filter->labelName,
        						'value' => $item->name,
            				);
            			}
            		}
            	}
            }
        }
        
        Timer::end(__METHOD__);
        
        return array('selectedFilters' => $selectedFilters,'retailerCount' => $retailerCount);
    }
    
    /**
     * 获取 refine keyword
     *
     * @return object
     */
    public function getRefine($urlParams = array()) {
    	$tmp = new \stdClass();
    	$tmp->search = '';
    
    	$urlProcess = Util::getViewHelper('GetUrl');

    	if(array_key_exists('search', $urlParams[1])) {
    		$tmp->search = urldecode($urlParams[1]['search']);
    		unset($urlParams[1]['search']);
    	}
    	if(array_key_exists('page', $urlParams[1])) {
    		unset($urlParams[1]['page']);
    	}
    
    	$tmp->action = $urlProcess('product_list', $urlParams[0]);
    
    	$siteSetting = Data::getInstance()->get('siteSetting');
    	$activeSharpLogic = $siteSetting['product_activate_sharp_logic'];
    
    	$tmpUrlParams = $urlParams;
    	if(array_key_exists('q', $tmpUrlParams[1])) {
    		unset($tmpUrlParams[1]['q']);
    	}
    	if(array_key_exists('other_hits', $tmpUrlParams[1])) {
    		unset($tmpUrlParams[1]['other_hits']);
    	}
    	if($activeSharpLogic=='1'){
    		$tmp->removeUrl = self::constructCleanUrl($tmpUrlParams);
    	}else{
    		$tmp->removeUrl = $urlProcess('product_list', $tmpUrlParams[0],$tmpUrlParams[1]);
    	}
    
    	$tmp->params = $urlParams[1];
    
    	return $tmp;
    }
    
    /**
     * 通过URL组合成 filter 参数
     */
    public function getFilterParams($urlParams = array()) {
    		
    	$priceWatchFilters = array();
    	foreach ($urlParams as $key => $value) {
    
    		if(	$key == "man_id"
    				|| $key == "retailer"
    				|| $key == "search"
    				|| substr($key, 0, 5) == "attr_") {
    			$priceWatchFilters[$key] = $value;
    		}
    	}
    	$string = '';
    	if($priceWatchFilters) $string = http_build_query($priceWatchFilters);
    
    	return $string;
    }
    
    /**
     * 获取 category 页面上面隐藏的 filter 选项
     *
     * @param array $filters
     * @return object
     */
    public function getMoreFilters($filters, $urlParams) {
    
    	$ret = new \stdClass();
    	$ret->moreLabel = array();
    
    	$i = 0;
    	foreach ($filters as $filter) {
    		if($i++ < 4) {
    
    			if($filter->labelId == self::PRI_ID) {
    				if(property_exists($filter, 'removeUrl')) {
    					$ret->removeUrl = null;
    				}
    			}else {
    				foreach ($filter->popular as $option) {
    					if($option->selected) {
    						$ret->removeUrl = null;
    					}
    				}
    			}
    
    			continue;
    		}
    			
    		$selectedTrue = false;
    		foreach ($filter->popular as $option) {
    			if($option->selected) {
    				$ret->removeUrl = null;
    				$selectedTrue = true;
    				break;
    			}
    		}
    		$tmp = array(
				'name' => $filter->labelName,
    		    'selected' => $selectedTrue
    		);
    		array_push($ret->moreLabel, $tmp);
    	}
    
    	if(isset($urlParams[1]['search']) && !empty($urlParams[1]['search'])) {
    		$ret->removeUrl = null;
    	}
    
    	if(property_exists($ret, 'removeUrl') || false) {
    		$urlProcess = Util::getViewHelper('GetUrl');
    			
    		$ret->removeUrl = $urlProcess('product_list', $urlParams[0]);
    	}
    
    	return $ret;
    }
    
    static public function checkSearchIsLeft($urlParams,$categoryType='') {
    	$siteSetting = Data::getInstance()->get('siteSetting');
    	$qSearchLeft = 0;
    
    	$importantFiltersCount = -1; //all at left side of #
    	if(isset($siteSetting['product_how_many_important_filter_combine']) && is_numeric($siteSetting['product_how_many_important_filter_combine'])){
    		$importantFiltersCount = $siteSetting['product_how_many_important_filter_combine'];
    	}
    
    	if($importantFiltersCount != -1){
    		if($categoryType=='Structured'){
    			if(!empty($siteSetting['product_structured_q_search_left_sharp'])){
    				$qSearchLeft = $siteSetting['product_structured_q_search_left_sharp'];
    			}
    		}elseif($categoryType=='FreeText'){
    			if(!empty($siteSetting['product_freetext_q_search_left_sharp'])){
    				$qSearchLeft = $siteSetting['product_freetext_q_search_left_sharp'];
    			}
    		}
    		if($qSearchLeft!=0){
    			$haveImportantCount = 0;
    			$man_id = self::MAN_ID;
    			$importantFiltersArray = self::getImportantFilters($urlParams['cat']);
    			foreach($urlParams as $key=>$value){
    				$key = trim($key);
    				if($key=="q" || $key=="search" || $key=="page"){
    					$qSearchLeft = 1;
    					break;
    				}elseif($key=="man_id" || substr($key,0,5)=="attr_"){
    					if((substr($key,0,5)=="attr_" && in_array(str_replace("attr_","",$key),$importantFiltersArray)) || ($key=="man_id" && in_array($man_id,$importantFiltersArray))){
    						$haveImportantCount++;
    					}
    				}
    			}
    			if($haveImportantCount<$importantFiltersCount ){
    				$qSearchLeft = 1;
    			}else{
    				$qSearchLeft = 0;
    			}
    		}
    	}else{
    		$qSearchLeft = 1;
    	}
    	return $qSearchLeft;
    }
  
    /**
     * @desc 获取 base attributes
     * @return array
     */
    public function getAttributes($params) {    
    	return Api::get()->getAttributes($params);
    }
    
    /**
     * 获取 manufacturer 名称
     *
     * @return string
     */
    public function getManufacturerName($attributes,$params) {
    	$brandName = '';
    	if(array_key_exists('brand', $params)) {
    		if(count($attributes) > 0){
    			$matchedKey = false;
    			foreach ($attributes as $key => $attribute) {
    				if($attribute->labelId == self::MAN_ID) {
    					$matchedKey = $key;
    					break;
    				}
    			}
    			if(array_key_exists('brand', $params) && $matchedKey !== false) {
    				foreach ($attributes[$matchedKey]->options as $option) {
    					if($option->id == $params['brand']) {
    						$brandName = $option->name;
    					}
    				}
    			}
    		}
    	}
    	return $brandName;
    }
    
    public static function getCleanUrl($params,$type){
        
        unset($params[1][$type]);
        if(isset($params[1]["page"])){
        	unset($params[1]["page"]);
        }
        
        if ('viewCleanUrl' == $type){
            unset($params[1]['viewType']);
        }
        
        $url = self::constructCleanUrl($params);
        if(false === strpos($url,"#")){
        	$url .= "#";
        }else{
        	$url .= "&";
        }
        return $url;
    }
    /**
     * 生成列表上方 splash , filters , buying advice
     *
     */
    public function getDetail($category,$urlParams){
        Timer::start(__METHOD__);
        
        //check the vouche tab should display or not
        $viewData = array();
        $data = Data::getInstance();
        $siteSetting = $data->get('siteSetting');
        $site = $data->get("site");
        if($siteSetting['voucher_display']){
        	$voucher_count = $category->voucherCount;
        	if($voucher_count>0){
        		$viewData['showVoucherTab'] = $voucher_count;
        	}
        }
        $detail = new \stdClass();
        $cache = Cache::get('dynamicCache');
        
        // 如果是主站，则获取对应 category 的 splash 信息
        $detail->splash = null;
        if($site['site_type'] === 'MainSite'){

            $uri = Util::getCleanUri($_GET);
            $cacheKey = 'SPLASH_' . $category->id . '_' . $uri;
            if ($_SERVER['HTTP_USER_AGENT'] == 'hot-keyword-curl' && !empty($_COOKIE['sk'])) {
                $cacheKey .= '_' . Common::queryFilter(urldecode($_COOKIE['sk']));
            }
            $cacheKey = Util::makeCacheKey($cacheKey);
            $splash = $cache->getItem($cacheKey);

            if (null === $splash) {
                $tmp = "";
                $splashIsFromSeo = 0;

                // 如果不是 product_list，需要先检查 product_list 页面的 seo-template 中的 id 级别是否有内容
                if ($data->get("route") != 'product_list') {
                    $articleModel = new ArticleCms();
                    $tmp = $articleModel->fetchContent('splash-check',array("cid" => $category->id));
                    if ($tmp) {
                        $seoData = array();
                        $seoData['seoTpl']['splash_text_cat'] = $tmp;
                        $seoData['rules']['main-id'] = array('splash_text_cat');
                        $tmp = '';
                    }
                } else {
                    // 先检查 seo-template 中是否有内容
                    $seoData = Resource::loadSeoTemplates(array_merge($urlParams[0],$urlParams[1]));
                }

                if (isset($seoData['seoTpl']['splash_text_cat'])
                    && !empty($seoData['seoTpl']['splash_text_cat'])
                    && '' != ($tmp = trim($seoData['seoTpl']['splash_text_cat']))){
                    // 设置标识
                    $splashIsFromSeo = 1;
                }

                if(!$splashIsFromSeo){
                    $articleModel = new ArticleCms();
                    $tmp = $articleModel->fetchContent($type='splash', array("title" => "description", "cid" => $category->id));
                }

                $splash = array('content' => $tmp );

                $cache->addItem($cacheKey,$splash);
            }

            $detail->splash = $splash['content'];
        }
        // 如果是主站，则获取对应 category 的 buying advice 信息
        $detail->buyingAdvice = null;
        $detail->buyingAdviceFromSeo = null;
        
        if($site['site_type'] === 'MainSite'){
        
        	//For redirected url like sp page have been encoded, so we need decode it first.
        	$uri = Util::getCleanUri($_GET);
        	$key = 'BUYING_ADVICE_SHORT_' . $category->id . '_' . $uri;
        	if ($_SERVER['HTTP_USER_AGENT'] == 'hot-keyword-curl' && !empty($_COOKIE['sk'])) {
        	    $key .= '_' . Common::queryFilter(urldecode($_COOKIE['sk']));
        	}
        	$cacheKey = Util::makeCacheKey($key);
        	$buyingAdvice = $cache->getItem($cacheKey);
        	if (null === $buyingAdvice) {
        		$content = "";
        		$isFromSeo = 0;
        		$mainCategoryPageOnly = 0;
        		$tabLabel = '';
        		$adviceExtended = '';
        		$tabUrl = '';
        
        		// 如果不是 product_list，需要先检查 product_list 页面的 seo-template 中的 id 级别是否有内容
        		if ($data->get("route") != 'product_list') {
        			$articleModel = new ArticleCms();
        			$content = $articleModel->fetchContent('buying-advice-check',array("cid" => $category->id));
        			if ($content) {
        				$seoData = array();
        				$seoData['seoTpl']['intro_text_cat'] = $content;
        				$seoData['rules']['main-id'] = array('intro_text_cat');
        				$content = '';
        			}
        		} else {
        			// 先检查 seo-template 中是否有内容
        		    $seoData = Resource::loadSeoTemplates(array_merge($urlParams[0],$urlParams[1]));
        		}
        
        		// 获取 BuyingAdviceIntro
        		$pattern = '/(?P<adviceIntro>\<div\s+[^>]*class=([\'"])buyingAdviceIntro\2[^>]*\>[\s\S]+?\<\/div\>)\s*(?:\<div\s+[^>]*class=([\'"])buyingAdviceExtended\2[^>]*\>|$)/';
        
        		// 必须有 intro_text_cat 并且有 adviceIntro
        		if (!empty($seoData['seoTpl']['intro_text_cat']) // 有 intro
        				&& '' != ($introText = trim($seoData['seoTpl']['intro_text_cat'])) // 获取 intro
        				&& preg_match($pattern, $introText, $matches) // 必须有内容
        				&& '' != ($content = trim($matches['adviceIntro'])) // 内容不为空
        				&& !preg_match('/^<!--.*-->$/s', $content)) { // 内容不只是注释，for matching char <CR>, preg_match need modifier 's'.
        
        			// 设置标识
        			$isFromSeo = 1;
        
        			// 如果是 category id 级别的，需要将 advice-tab 的链接设置为 category 的首页
        			if (!empty($seoData['rules']['main-id'])
        					&& in_array('intro_text_cat', $seoData['rules']['main-id'])) {
        				$mainCategoryPageOnly = 1;
        			}
        				
        			// 如果只在 category 首页显示，又有参数的话，就把内容置空
        			if ($isFromSeo && $mainCategoryPageOnly && $urlParams[1]) {
        				$content = '';
        			}
        				
        			// 获取 BuyingAdviceTab
        			$pattern = '/\<div\s+[^>]*class=([\'"])buyingAdviceTab\1[^>]*\>(?P<adviceTab>[\s\S]+?)\<\/div\>\s*\<div\s+[^>]*class=([\'"])buyingAdviceIntro\3[^>]*\>/';
        			if (preg_match($pattern, $introText, $matches)) {
        				$tabLabel = trim($matches['adviceTab']);
        			}
        				
        			// 获取 buyingAdviceExtended
        			$pattern = '/(?P<adviceExtended>\<div\s+[^>]*class=([\'"])buyingAdviceExtended\2[^>]*\>[\s\S]+?\<\/div\>)\s*$/';
        			if ($content != ''
        					&& preg_match($pattern, $introText, $matches)) {
        				$adviceExtended = trim($matches['adviceExtended']);
        			}
        		}
        
        		if(!$isFromSeo){
        			$articleModel = new ArticleCms();
        			$content = $articleModel->fetchContent('buying-advice',array("title" => "description", "cid" => $category->id));
        			if ($content) {
        				$content = '';
        				// 如果有结果，则不在 CL 页面显示内容，只将 tab 链接到 BA 页面
        				$urlProcess = Util::getViewHelper("GetUrl");
        				$tabUrl = $urlProcess('buying_advice', array('cat' => $category->id, 'categoryName' => $category->name));
        			}
        			$isFromSeo = 0;
        			$mainCategoryPageOnly = 0;
        			$tabLabel = '';
        			$adviceExtended = '';
        		}
        
        		$buyingAdvice = array(
        				'content'		=> $content,
        				'isFromSeo'		=> $isFromSeo,
        				'mainCategoryPageOnly'	=> $mainCategoryPageOnly,
        				'tabLabel'		=> $tabLabel,
        				'adviceExtended'=> $adviceExtended,
        				'tabUrl'		=> $tabUrl,
        		);
        		$cache->addItem($cacheKey,$buyingAdvice);
        	}
        	$detail->buyingAdvice 			= $buyingAdvice['content'];
        	$detail->buyingAdviceTabLabel 	= $buyingAdvice['tabLabel'];
        	$detail->buyingAdviceTabUrl 	= $buyingAdvice['tabUrl'];
        	$detail->buyingAdviceFromSeo 	= $buyingAdvice['isFromSeo'];
        	$detail->buyingAdviceMainCategoryPageOnly = $buyingAdvice['mainCategoryPageOnly'];
        	$detail->buyingAdviceExtended = $buyingAdvice['adviceExtended'];
        	// 如果有扩展内容，并且 intro 不为空

        	//end of checking if buying-advice from seo-template
        	
        	// 如果是主站，则获取对应 category 的 guide 信息
        	$detail->guide = null;
        	if($site['site_type'] === 'MainSite'){
        			
        		$guideStats = self::getGuideStatsFromCookies();
        		$cacheKey = Util::makeCacheKey('GUIDE_' . $category->id);
        		$tmp = $cache->getItem($cacheKey);
        		if (null === $tmp) {
        			$articleModel = new ArticleCms();
        			$tmp = $articleModel->fetchContent($type='buying-guide',array("cid" => $category->id));
        			if ($tmp) {
        				$keys = array_keys($tmp);
        				// sort by key
        				natsort($keys);
        				$result = array();
        				foreach ($keys as $key) {
        					$result[$key] = $tmp[$key];
        				}
        				$tmp = $result;
        			}
        			$cache->addItem($cacheKey, $tmp );
        		}
        		// 			libxml_use_internal_errors(true);
        		$showGuideType = "checkbox";
        		if (!empty($tmp)) {
        		    foreach ($tmp as $key => $guideString) {
        		        if(false !== strpos($guideString,"<graphical-data>")){
        		            $showGuideType = "image";
        		            break;
        		        }
        		    }
        		}
        		
        		$detail->guide = $tmp;
        		$detail->showGuideType = $showGuideType; //用来区分是 以checkbox展现形式  还是以图片的形式
        	}
        	
        	$detail->switch = '';
        	
        	//检查cookie中是否有prefer的nowTab信息
        	$prefs = Util::getNowTab();
        	$nowTab = "";
        	if(array_key_exists('nt', $prefs)) {
        		$nowTab = $prefs['nt'];
        	}
        	if($nowTab!=""){
        		if($nowTab == "filter-link"){
        			$detail->switch = 'filters';
        		} else if ($nowTab == 'guide-link' && !empty($detail->guide)) {
        			$detail->switch = 'guide';
        		} else if ($nowTab == 'advice-link' && !empty($detail->buyingAdvice)) {
        			$detail->switch = 'advice';
        		} else if ($nowTab == 'splash-link' && !empty($detail->splash)) {
        			$detail->switch = 'splash';
        		}
        	}
        	if($detail->switch == ''){
        		$tabSort = array(
        				"graphical_guide" =>"1",
        				"filter" =>"2",
        				"splash" =>"3",
        				"non_graphical_guide" =>"4",
        		);
        		if(is_numeric($siteSetting['productlist_tab_graphical_buying_guide'])){
        			$tabSort['graphical_guide'] = $siteSetting['productlist_tab_graphical_buying_guide'];
        		}else{
        			$tabSort['graphical_guide'] = $tabSort["graphical_guide"];
        		}
        			
        		if(is_numeric($siteSetting['productlist_tab_filter'])){
        			$tabSort['filter'] = $siteSetting['productlist_tab_filter'];
        		}else{
        			$tabSort['filter'] = $tabSort["filter"];
        		}
        			
        		if(is_numeric($siteSetting['productlist_tab_splash'])){
        			$tabSort['splash'] = $siteSetting['productlist_tab_splash'];
        		}else{
        			$tabSort['splash'] = $tabSort["splash"];
        		}
        			
        		if(is_numeric($siteSetting['productlist_tab_non_graphical_buying_guide'])){
        			$tabSort['non_graphical_guide'] = $siteSetting['productlist_tab_non_graphical_buying_guide'];
        		}else{
        			$tabSort['non_graphical_guide'] = $tabSort["non_graphical_guide"];
        		}
        			
        		asort($tabSort);
        		foreach($tabSort as $tabName => $priorityLevel){
        			if($tabName == "graphical_guide" && $detail->guide && $showGuideType == "image"){
        				$detail->switch = 'guide';
        				break;
        			}else if($tabName == "filter"){
        				$detail->switch = 'filters';
        				break;
        			}else if($tabName == "splash" && $detail->splash){
        				$detail->switch = 'splash';
        				break;
        			}else if($tabName == "non_graphical_guide" && $detail->guide && $showGuideType != "image"){
        				$detail->switch = 'guide';
        				break;
        			}
        	
        		}
        			
        	}
        	
        	// 是否需要展开此部分内容
        	$detail->collapse = false;
        	if($detail->splash && ($detail->switch != 'guide') && array_key_exists('ref', $urlParams[1]) && $urlParams[1]['ref'] == 'redirect') {
        		$detail->collapse = true;
        	} else if ($detail->switch == 'advice') {
        		$detail->collapse = true;
        	}
        	
        	$detail->switchButton = true;
        	if ($detail->switch == 'filters') {
        		$detail->collapse = true;
        		$detail->switchButton = false;
        	}
        	
        	$viewData['detail'] = $detail;
        }
        
        Timer::end(__METHOD__);
        
        return $viewData;
    }
    
    /**
     * 从 Cookie 中获取 guide 状态
     *
     * @return array
     */
    public static function getGuideStatsFromCookies() {
    	 
    	if(Data::getInstance()->has('cookiesGuideStats')) {
    		$ret = Data::getInstance()->get('cookiesGuideStats');
    	}else {
    		$ret = array();
    		if(array_key_exists('guideStats', $_COOKIE)) {
    			$guideStats = $_COOKIE['guideStats'];
    			$ret = array();
    		  
    			$arr = explode('|', $guideStats);
    			foreach ($arr as $item) {
    				$tmp = explode(':', $item);
    				$key = $tmp[0];
    				$value = $tmp[1];
    				$ret[$key] = $value;
    			}
    		}
    		Data::getInstance()->set('cookiesGuideStats', $ret);
    	}
    	return $ret;
    }
    /**
     * 获取buying Guide
     */
    public function getBuyingGuide($urlParams,$category,$activateSharpLogic,$apiParams){
        Timer::start(__METHOD__);
        
        $data = Data::getInstance();
    	$siteType = $data->get("siteType");
    	$site = $data->get("site");
    	
        $detail = new \stdClass();
        $detail->activateSharpLogic = $activateSharpLogic;
        $detail->filter = null;
        $detail->selectedFilters = null;
        
        $filters = $this->_formatFilter($urlParams ,$category->attributes ,$apiParams);  // 获取 Filter info 供 buying guide 使用  
        $filterInfo = array();
        $GetLang = Util::getViewHelper("GetLang");
        foreach ($filters as $filter) {
        	if($filter->labelId != self::PRI_ID/*'Price'*/) {
        		$key = $filter->labelId;
        		if($filter->labelId == self::MAN_ID){
        			$filter->labelName = $GetLang('PR.ProductList.MANUFACTURER');
        			$key = 'man_id';
        		}elseif($filter->labelId == self::STO_ID){
        			$filter->labelName = $GetLang('PR.ProductList.STORE');
        			$key = 'merchant';
        		}
        		$filtersLabel[$key] = $filter->labelName;
        		$filterInfo[$key] 	= array();
        		$options = (empty($filter->upper))? $filter->popular:array_merge($filter->upper, $filter->lower);
        		foreach ((array)$options as $item) {
        			$filterInfo[$key][$item->id]['prodcount'] = $item->prodcount;
        			$filterInfo[$key][$item->id]['selected']  = ($item->selected)? 1:0;
        			$filterInfo[$key][$item->id]['url'] 	  = $item->url;
        			$filterInfo[$key][$item->id]['name'] 	  = $item->name;
        		}
        	}
        }
        $detail->refine =  $this->getRefine($urlParams);
        // 如果是主站，则获取对应 category 的 guide 信息
        $detail->guide = null;
        $cache = Cache::get('dynamicCache');
        if($siteType == 'main' || $siteType == 'mobile'){
        	$cacheKey = Util::makeCacheKey('GUIDE_' . $category->id);
        	$guideTmp = $cache->getItem($cacheKey);
        	if (null === $guideTmp) {
        		$articleModel = new ArticleCms();
        		$guideTmp = $articleModel->fetchContent($type='buying-guide',array("cid" => $category->id));
        		if ($guideTmp) {
        			$keys = array_keys($guideTmp);
        			natsort($keys); // sort by key
        			$result = array();
        			foreach ($keys as $key) {
        				$result[$key] = $guideTmp[$key];
        			}
        			$guideTmp = $result;
        		}
        		$cache->addItem($cacheKey ,$guideTmp );
        	}
        	if(empty($guideTmp)){
        	    if($data->get("route") == 'product_list_ajax'){
        	        return array("buyingGuide"=>"","buyingGuideTab"=>"");
        	    }
        		echo json_encode(array("buyingGuide"=>"","buyingGuideTab"=>""));
        		exit();
        	}
        	libxml_use_internal_errors(true);
        	$guideStats = self::getGuideStatsFromCookies();//获取cookie中的 保存的guide相关的值
        	$guides = array();
        	$curNum = 1;//当前在第几个 question 上 或者第几个filters
        	
        	$selectedTab = "";
        	
        	foreach ($guideTmp as $key => $guide) {
        		// process if-else-statement
        		if (preg_match_all('/\<if\s([^>]+)\>(.*?)\<\/if\>/is', $guide, $matches)) {
        			$matchesLen = count($matches[0]);
        			for ($i = 0; $i < $matchesLen; $i++) {
        				// process conditions
        				$parameters = array();
        				$conditions = $matches[1][$i];
        				$pattern = '/(attr_\d+|man_id|retailer|price_min|price_max|operator)\s*=\s*([\'"])([a-z0-9\.]*|\*)\2/is';
        				if (preg_match_all($pattern, $conditions, $parameterPairs)) {
        					$parameterPairsLen = count($parameterPairs[0]);
        					for ($j=0; $j<$parameterPairsLen; $j++) {
        						$tmpKey = strtolower($parameterPairs[1][$j]);
        						if(array_key_exists($tmpKey,$parameters)){
        							if(is_array($parameters[$tmpKey])){
        								array_push($parameters[$tmpKey],strtolower($parameterPairs[3][$j]));
        							}else{
        								$parameters[$tmpKey] = array($parameters[$tmpKey],strtolower($parameterPairs[3][$j]));
        							}
        						}else{
        							$parameters[$tmpKey] = strtolower($parameterPairs[3][$j]);
        						}
        					}
        					if (count($parameters) == 1 || (count($parameters) > 1 && empty($parameters['operator']))) {
        						$parameters['operator'] = 'and';
        					}
        				} else { // invalid parameters , remove this part
        					$guide = str_replace($matches[0][$i], '', $guide);
        					continue;
        				}
        				// process content
        				$contentIfTrue = $contentIfFalse = '';
        				$contents 	   = explode('<else/>', $matches[2][$i]);
        				if (count($contents) == 2) {
        					$contentIfTrue = $contents[0];
        					$contentIfFalse = $contents[1];
        				} else {
        					$contentIfTrue = $contents[0];
        				}
        
        				// check parameters
        				$result 	= '';
        				$condition 	= false;
        				$params 	= $urlParams[1];
        				$operator 	= $parameters['operator'];
        				unset($parameters['operator']);
        				if ($operator == 'and') {
        					$condition = true;
        					foreach ($parameters as $key1 => $value) {
        						if($value!==""){
        							if (empty($params) || $params[$key1] !== $value) {
        								$condition = false;
        								break;
        							}
        						}else{
        							if (!empty($params[$key1]) && $params[$key1] !== $value) {
        								$condition = false;
        								break;
        							}
        						}
        					}
        				} else {
        					foreach ($parameters as $key2 => $value) {
        						if(is_array($value)){
        							if (!empty($params) && in_array($params[$key2],$value)) {
        								$condition = true;
        								break;
        							}
        						}else{
        							if($value!==""){
        								if (!empty($params) && ($params[$key2] === $value || ($value == "*" && !empty($params[$key2])))) {
        									$condition = true;
        									break;
        								}
        							}else{
        								if (empty($params[$key2]) || ($params[$key2] === $value || ($value == "*" && !empty($params[$key2])))) {
        									$condition = true;
        									break;
        								}
        							}
        						}
        					}
        				}
        				if ($condition) {
        					$result = $contentIfTrue;
        				} else {
        					$result = $contentIfFalse;
        				}
        				$guide = str_replace($matches[0][$i], $result, $guide);
        			}
        		}
        
        		$xml 	= simplexml_load_string($guide);
        		if ($xml && $xml->headline && $xml->question && $xml->answers->answer) {
        			$guides[$key] = array();
        			$guides[$key]['headline'] = (string)$xml->headline;
        			$guides[$key]['headline_short'] = ((string)$xml->headline_short)?(string)$xml->headline_short:$guides[$key]['headline'];
        			$guides[$key]['description'] = ((string)$xml->description)?(string)$xml->description:'';
        			$guides[$key]['question'] 	 = ((string)$xml->question)?(string)$xml->question:'';
        			$guides[$key]['answers'] 	 = array();
        			$guides[$key]['answered']    = 0;
        			$guides[$key]['active']      = 0;
        			$guides[$key]['showCount']   = 1;
        				
        			foreach ($xml->answers->answer as $answer) {
        				if ($answer->text && $answer->attr && $answer->value) {
        						
        					$value = ((string)$answer->value)?(string)$answer->value:-1;
        					$tmp2 = array(
        							'text' => (string)$answer->text,
        							'attr' => (string)$answer->attr,
        							'value' => $value,
        							'selected' => 0,
        							'prodcount' => 0,
        					);
        						
        					if ($tmp2['attr'] != 'price') {
        						$infoKey = substr($tmp2['attr'], 5);
        						if (!empty($filterInfo[$infoKey][$value])) {
        							$tmp2['prodcount'] = $filterInfo[$infoKey][$value]['prodcount'];
        						} else if ($value != -1) {
        							$guides[$key]['showCount'] = 0;
        						}
        					} else {
        						$guides[$key]['showCount'] = 0;
        					}
        					// 普通filter只需要检查 guide 中指定的值等于当前答案，就认为当前答案为选中状态
        					if((!empty($urlParams[1][$tmp2['attr']]) && $urlParams[1][$tmp2['attr']] == $tmp2['value'])
        							// 如果值为 -1 ，就是说当前答案的值为空，哪我们需要验证当前问题已回答，并且值为 -1
        							|| (!empty($guideStats[$category->id .'_'. $curNum]) && $guideStats[$category->id .'_'. $curNum] == $tmp2['value'] && $tmp2['value'] == -1)
        							// 如果问题为 price，则当前问题已回答，并且答案不为 -1 ， 则url中必须存在 price_min 和 price_max 参数
        							|| ($tmp2['attr'] == 'price'
        									&& ($tmp2['value'] == (int)$urlParams[1]['price_min'] . '_' . $urlParams[1]['price_max']))) {
        						$guides[$key]['answered'] = 1;
        						$tmp2['selected'] = 1;
        					}
        					$guides[$key]['answers'][] = $tmp2;
        				}
        			}
        			if (!empty($guideStats[$category->id]) && $curNum == $guideStats[$category->id]) {
        				$guides[$key]['active'] = 1;
        			}
        				
        			if (empty($guides[$key]['answers'])) {
        				unset($guides[$key]);
        			}
        			$curNum++;
        			$showGuideType = "checkbox";
        				
        		}else if($xml && $xml->filters){
        
        			if($siteType == "mobile"){
        				$imageSize = "104x104";
        			}else{
        				$imageSize = "80x80";
        			}
        				
        			$filtersObj = $xml->filters[0];
        			$guides[$key] = array();
        			foreach($filtersObj->attributes() as $k => $v) {
        				$guides[$key][$k] = (string)$v;
        			}
        			if(!empty($guides[$key]['attr'])){
        				$infoKey = $guides[$key]['attr'];
        				if(strpos($guides[$key]['attr'], "attr") !== false){
        					$infoKey = substr($guides[$key]['attr'], 5);
        				}
        				if(empty($guides[$key]['display'])) $guides[$key]['display'] = $filtersLabel[$infoKey];
        			}
        				
        			$guides[$key]['active']  = 0;
        			$guides[$key]['choosed'] = 0;
        				
        			$guides[$key]['filters'] = array();
        			if(empty($guides[$key]['override']) && ($filtersObj->filter || $filtersObj->link)){
        
        				if($filtersObj->filter){
        					foreach($filtersObj->filter as $k => $row){
        						$childArr = array();
        						foreach($row->attributes() as $name => $v){
        							$childArr[$name] = (string)$v;
        						}
        						if(empty($childArr['attr'])) $childArr['attr'] = $guides[$key]['attr'];
        						if(empty($childArr['category']) && ('price' !== $childArr['attr']) && $childArr['value']){
        							$infoKey = $childArr['attr'];
        							if(strpos($guides[$key]['attr'], "attr") !== false){
        								$infoKey = substr($childArr['attr'], 5);
        							}
        							if(empty($childArr['url']))  	$childArr['url']     = $filterInfo[$infoKey][$childArr['value']]['url'];
        							if(empty($childArr['display'])) $childArr['display'] = $filterInfo[$infoKey][$childArr['value']]['name'];
        							if(empty($childArr['image'])){
        								$childArr['image']  =  Util::getImg("/images/site-gui/category-icons/".$imageSize."/".$category->id."/".$category->id."_".$childArr['value'].".jpg");
        							}
        							$childArr['showCount'] = $filterInfo[$infoKey][$childArr['value']]['prodcount'];
        							$childArr['selected']  = 0;
        							// 普通filter只需要检查 guide 中指定的值等于当前答案，就认为当前答案为选中状态
        							if((!empty($urlParams[1][$childArr['attr']]) && $urlParams[1][$childArr['attr']] == $childArr['value'])
        									// 如果值为 -1 ，就是说当前答案的值为空，哪我们需要验证当前问题已回答，并且值为 -1
        									|| (!empty($guideStats[$category->id .'_'. $curNum]) && $guideStats[$category->id .'_'. $curNum] == $childArr['value'] && $childArr['value'] == -1)
        									// 如果问题为 price，则当前问题已回答，并且答案不为 -1 ， 则url中必须存在 price_min 和 price_max 参数
        									|| ($childArr['attr'] == 'price'
        											&& ($childArr['value'] == (int)$urlParams[1]['price_min'] . '_' . $urlParams[1]['price_max']))) {
        								$guides[$key]['choosed'] = 1;
        								$childArr['selected'] = 1;
        								$guides[$key]['choosed_display'] = $childArr['display'];
        							}
        							if(($childArr['showCount'] > 0) && !empty($childArr['url'])
        									&& !empty($childArr['image']) && !empty($childArr['display'])){
        								$guides[$key]['filters'][] = $childArr;
        							}
        						}else if (!empty($childArr['category']) && !empty($childArr['display']) && $childArr['value']){
        							$guides[$key]['filters'] = $this->_getBuyGuideOfCategory($childArr, $guides[$key]['filters'],$imageSize);
        						}
        					}
        				}
        				if($filtersObj->link){  //解析自定义的link
        					$guides[$key]['filters'] = $this->_getBuyGuideOfLink($filtersObj->link, $guides[$key]['filters']);
        				}
        			}else{
        				//取得该filter type下所有的filter
        				if(strpos($guides[$key]['attr'], "attr") !== false){
        					$tmpAll = $filterInfo[substr($guides[$key]['attr'],5)];
        				}else{
        					$tmpAll = $filterInfo[$guides[$key]['attr']];
        				}
        				$overRideFilters = array();
        				if(isset($guides[$key]['override']) && ($guides[$key]['override'] == 'yes')
        						&& ($filtersObj->filter || $filtersObj->link)){      //解析 override自定义的filter
        					if($filtersObj->filter){
        						foreach($filtersObj->filter as $k => $row){
        							$childArr  = array();
        							foreach($row->attributes() as $name => $v){
        								$childArr[$name] = (string)$v;
        							}
        							if(empty($childArr['category']) && $childArr['value'] ){ //普通的filter
        								$overRideFilters[] = $childArr;
        							} else if(!empty($childArr['category']) && !empty($childArr['display'])){ //category filter
        								$guides[$key]['filters'] = $this->_getBuyGuideOfCategory($childArr, $guides[$key]['filters'],$imageSize);
        							}
        						}
        					}
        					if($filtersObj->link){  //解析自定义的link
        						$guides[$key]['filters'] = $this->_getBuyGuideOfLink($filtersObj->link, $guides[$key]['filters']);
        					}
        				}
        				if(!empty($tmpAll)){ //解析 all filters
        					foreach($tmpAll as $filter => &$val){
        						if($val['prodcount'] > 0){
        							if(!empty($overRideFilters)){
        								foreach($overRideFilters as $row){
        									if(isset($row['value']) && ($row['value'] == $filter) ){
        										if(!empty($row['display']))  $val['name']  = $row['display'];
        										if(!empty($row['url']))      $val['url']   = $row['url'];
        										if(!empty($row['image']))    $val['image'] = $row['image'];
        									}
        								}
        							}
        							if($val['selected']) {
        								$guides[$key]['choosed'] = 1;
        								$guides[$key]['choosed_display'] = $val['name'];
        							}
        							if(empty($val['image'])){
        								$val['image'] = Util::getImg("/images/site-gui/category-icons/".$imageSize."/".$category->id."/".$category->id."_".$filter.".jpg");
        								if(isset($guides[$key]['override-image']) && ($guides[$key]['override-image'] == 'yes') ){
        									$val['image'] = Util::getImg("/images/site-gui/category-icons/".$imageSize."/".$category->id."/{$site['short_name']}/".$category->id."_".$filter."_".$site['short_name'].".jpg");
        								}
        							}
        							$guides[$key]['filters'][] = array( "display" 	=> $val['name'],
        									"url" 		=> $val['url'],
        									"showCount" => $val['prodcount'],
        									"selected" 	=> $val['selected'],
        									"image" 	=> $val['image'] );
        						}
        					}
        					unset($val);
        				}
        			}
        			if(empty($guides[$key]['filters'])) unset($guides[$key]);
        			if(!empty($guides[$key]['filters'])) {
        				$guides[$key]['filters'] = $this->_getSortData($guides[$key]['filters'], $guides[$key]['sort']);
        				if (!empty($guideStats[$category->id]) && $curNum == $guideStats[$category->id]) {
        					$guides[$key]['active'] = 1;
        				}
        				$curNum++;
        				
        				foreach($guides[$key]['filters'] as $tmpFilters){
        					if((int)$tmpFilters['selected']==1){
        						$selectedTab = $key;
        						break;
        					}
        				}
        			}
        			$showGuideType = "image";
        		}
        
        	}
        	if((empty($guideStats[$category->id]) && count($guides) > 1) || (count($guides) < (int)$guideStats[$category->id])) {
        		$keys = array_keys($guides);
        		$guides[$keys[0]]['active'] = 1;
        	}
        	$tmp = $guides;
        	$tmpKeyArr = array_keys($tmp);
        	$detail->guideActiveTab = $tmpKeyArr[0];
        	$tmpValueKeyArr = array_flip($tmpKeyArr);
        	
        	/*dev_349110_GraphicalFiltersActivateNextQuestion*/
        	if($showGuideType == "image"){
	        	$selectedNameArray = array();
	        	foreach($guides as $question){
	        		if(!empty($question['filters']) && is_array($question['filters'])){
	        			foreach($question['filters'] as $filter){
	        				if((int)$filter['selected'] == 1){
	        					$selectedNameArray[] = $filter['display'];
	        				}
	        			}
	        		}
	        	}
	        	$setSelectedTab = false;
	        	if(!empty($selectedNameArray) && count($selectedNameArray)==1){
	        		$selectedName = $selectedNameArray[0];
	        		foreach ($tmpKeyArr as $tab){
	        			if($guides[$tab]['display']==$selectedName){
	        				$detail->guideActiveTab = $tab;
	        				$guideStats = array();
	        				$setSelectedTab = true;
	        			}
	        		}
	        	}
	        	
	        	if(empty($guideStats[$category->id]) && $selectedTab!="" && !$setSelectedTab){
	        		$detail->guideActiveTab = $selectedTab;
	        	}
        	}
        	/*dev_349110_GraphicalFiltersActivateNextQuestion*/
        	
        	if(!empty($guideStats[$category->id])){
        		
        		$QuestionArray = explode("$",$guideStats[$category->id]);
        		if(count($QuestionArray)>1){
        			$fromQuestion =  $QuestionArray[0];
        			$toQuestion =  $QuestionArray[1];
        			if(in_array($fromQuestion,$tmpKeyArr) && in_array($toQuestion,$tmpKeyArr)){
        
        				if((int)$tmpValueKeyArr[$fromQuestion] < (int)$tmpValueKeyArr[$toQuestion]){
        					$detail->guideActiveTab = $tmpKeyArr[((int)$tmpValueKeyArr[$fromQuestion]+1)];
        				}
        			}elseif(in_array($fromQuestion,$tmpKeyArr)){
        
        				if(((int)$tmpValueKeyArr[$fromQuestion] + 1) < count($tmpValueKeyArr)){
        					$detail->guideActiveTab = $tmpKeyArr[((int)$tmpValueKeyArr[$fromQuestion]+1)];
        				}else{
        					$detail->guideActiveTab = $fromQuestion;
        				}
        			}elseif(in_array($toQuestion,$tmpKeyArr)){
        
        				$detail->guideActiveTab = $toQuestion;
        			}
        			$guides[$detail->guideActiveTab]['active'] = 1;
        
        		}else{
        			if(in_array($guideStats[$category->id],$tmpKeyArr)){
        				$detail->guideActiveTab = $guideStats[$category->id];
        			}
        		}
        	}
	        
        	
        	$detail->tabPosition = ($tmpValueKeyArr[$detail->guideActiveTab]+1);
        	$detail->guide = $tmp;
        	$detail->showGuideType = $showGuideType; //用来区分是 以checkbox展现形式  还是以图片的形式
        }
        
        Timer::end(__METHOD__);
        
        return $detail;
    }
    
    /**
     *  buying Guide 解析 category filter
     * @param  $category
     * @param  $display
     */
    protected function _getBuyGuideOfCategory($childArr, $resultArr ,$size = "80x80"){
    
    	$urlProcess = Util::getViewHelper("GetUrl");
    	$childArr['url'] = $urlProcess('product_list', array("cat"=> $childArr['category']), array($childArr['attr'] => $childArr['value']));
    	if(empty($childArr['image'])){
    		$childArr['image']  =  Util::getImg("/images/site-gui/category-icons/".$size."/".$childArr['category']."/".$childArr['category']."_".$childArr['value'].".jpg");
    	}
    	if(!empty($childArr['url']) && !empty($childArr['image']) && !empty($childArr['display'])){
    		$resultArr[] = $childArr;
    	}
    	return $resultArr;
    }
    
    /**
     * buying guide 解析 Link
     */
    protected function _getBuyGuideOfLink($filterObj, $resultArr){
    
    	foreach($filterObj as $k => $row){
    		$childArr = array();
    		foreach($row->attributes() as $name => $v){
    			$childArr[$name] = (string)$v;
    		}
    		$childArr['type'] = "link";
    		if(!empty($childArr['url']) && !empty($childArr['image']) && !empty($childArr['display'])){
    			$resultArr[] = $childArr;
    		}
    	}
    	return $resultArr;
    }
    
    /**
     * 获得 guide 两种排序方式后的数据
     */
    protected function _getSortData($dataArray, $type){
    
    	$newArray = array();
    	if(empty($dataArray)) return array();
    	$sortKeys = array();
    	foreach($dataArray as $val){
    		if($type == "alphabetical"){
    			$sortKeys[] = $val['display'];
    		}else if($type == "productCount"){
    			$sortKeys[] = $val['showCount'];
    		}
    	}
    	//排序
    	if($type == "alphabetical"){
    		array_multisort($sortKeys, SORT_ASC, $dataArray);
    	}else if($type == "productCount"){
    		array_multisort($sortKeys, SORT_DESC, $dataArray);
    	}
    
    	//moving selected filters first of all the filters.
    	foreach($dataArray as $key=>$item){
    		if ($item['selected']){
    			array_unshift($dataArray, $item);
    			unset($dataArray[$key+1]);
    		}
    	}
    
    	$newArray = $dataArray;
    	if(count($dataArray) > 8){
    		$selected = array();
    		foreach($dataArray as $key => $val){
    			if($val['selected'] && ($key >= 8)){
    				$selected = $val;
    				unset($dataArray[$key]);
    			}
    		}
    		if(!empty($selected)){
    			$tempArr1 = array_slice($dataArray, 0,7);
    			$tempArr2 = array_slice($dataArray, 7);
    			$newArray = array_merge($tempArr1, array($selected), $tempArr2);
    		}
    	}
    	return $newArray;
    }
    
    /**
     * 获取热门的 filter 选项
     *
     * @param object $label
     * @param object $options
     * @return array
     */
//     protected function _getPopular($label, $options, $urlParams) {
    protected function _getPopular($options) {
    
    	$tmp = array();
    	foreach ($options as $item) {
    		$tmp[$item->id] = $item->prodcount;
    	}
    	arsort($tmp);
    
    	$tmp2 = array_count_values($tmp);
    	krsort($tmp2);
    
    	$sum = 0;
    	$nums = 5;
    	$tmp3 = array();
    	foreach ($tmp2 as $key => $item) {
    		if($sum < $nums) {
    			$distance = $nums - $sum;
    			$tmp3[$key] = $item > $distance ? $distance : $item;
    			$sum += $tmp3[$key];
    		}
    	}
    	
    	$ret = array();
    	$tmp4 = $tmp3;
    	foreach ($tmp4 as $key => $value) {
    		$tmp4[$key] = 0;
    	}
    
    	$selected = false;
    	foreach ($options as $key => $option) {
    		if($option->selected) {
    			$selected = true;
    			break;
    		}
    	}
    
    	$selectedpushed = false;
    	foreach ($options as $option) {
    		if(count($ret) >= $nums) {
    			break;
    		}
    		if(array_key_exists($option->prodcount, $tmp3)){
    			if($tmp4[$option->prodcount] >= $tmp3[$option->prodcount]) {
    				continue;
    			}
    			array_push($ret, $option);
    			if($option->selected) {
    				$selectedpushed = true;
    			}
    			$tmp4[$option->prodcount]++;
    		}else {
    			if($option->selected) {
    				array_push($ret, $option);
    				$selectedpushed = true;
    			}
    		}
    	}
    
    	if($selected && !$selectedpushed) {
    		array_pop($ret);
    		foreach ($options as $key => $option) {
    			if($option->selected) {
    				array_push($ret, $option);
    			}
    		}
    	}
    
//     	foreach ($ret as $key => $option) {
//     		$ret[$key] = $this->_parseOptions($label, $option, $urlParams);
//     	}
    	
    	return $ret;
    }
    
    /**
     * 解析 filter 的选项
     *
     * @param object $label
     * @param object $option
     * @return object
     */
    protected function _parseOptions($label, $option, $urlParams) {
        Timer::start(__METHOD__);
        
    	if($label->labelId == self::MAN_ID) {
    		if($option->selected) {
    			$option->url = $this->_makeUrl('man_id', null, $urlParams);
    		}else {
    			$option->url = $this->_makeUrl('man_id', $option->id, $urlParams);
    		}
    	}else if($label->labelId == self::STO_ID){
    		if($option->selected) {
    			$option->url = $this->_makeUrl('retailer', null, $urlParams);
    		}else {
    			$option->url = $this->_makeUrl('retailer', $option->id, $urlParams);
    		}
    	}else {
    		if($option->selected) {
    			$option->url = $this->_makeUrl("attr_{$label->labelId}", null, $urlParams);
    		}else {
    			$option->url = $this->_makeUrl("attr_{$label->labelId}", $option->id, $urlParams);
    		}
    	}
    	
    	Timer::end(__METHOD__);
    	
    	return $option;
    }
    
    /**
     * 获取分页信息
     * object {
     * 		 'steps' => array() // used for
     * 		,'countPerPage'		// display how many nums per page
     * 		,'offset'			// current offset in the all nums
     * 		,'allCounts'		// all nums
     * }
     *
     * @return object
     */
    public function getPaginationInfo($apiParams,$urlParams,$cookieParams,$products) {
    
    	$tmp = new \stdClass();
    	$tmp->steps = $this->urlParamsAllowed['numberOfProducts'];
    	$tmp->countPerPage = $cookieParams['numberOfProducts'];
    	
    	$tmp->offset = $this->apiParamsDefault['pstart']+1;
    	if(array_key_exists('page', $urlParams[1])){
    		$tmp->offset = $urlParams[1]['page'];
    	}
    
    	$tmp->allCounts = $products->prodcount;
 
    	// u'd better specify the showNums as odd number
    	$showNums = 5;
    	if($showNums % 2 == 0) {
    		$showNums += 1;
    	}
    	$tmp->pageCounts = ceil($tmp->allCounts / $tmp->countPerPage);
    	 
    	$tmp->first = true;
    	$tmp->prev = true;
    	$tmp->next = true;
    	$tmp->last = true;
    	$tmp->data = array();
    	 
    	$tmp->data[$tmp->offset] = 1;
    	$tmp2 = floor($showNums / 2);
    	for($i = 1; $i <= $tmp2; $i++) {
    		if($tmp->offset - $i > 0) {
    			$tmp->data[$tmp->offset -$i] = 0;
    		}
    	}
    	$tmp3 = count($tmp->data);
    	for($i = 1; $i <= ($showNums - $tmp3); $i++) {
    		if($tmp->offset + $i <= $tmp->pageCounts) {
    			$tmp->data[$tmp->offset +$i] = 0;
    		}
    	}
    	ksort($tmp->data);
    	$tmp4 = $showNums - count($tmp->data);
    	for($i = 1; $i <= $tmp4; $i++) {
    		if(key($tmp->data) - $i > 0) {
    			$tmp->data[key($tmp->data) - $i] = 0;
    		}
    	}
    	ksort($tmp->data);
    	 
    	if(key($tmp->data) == 1) {
    		$tmp->first = false;
    		$tmp->prev = false;
    	}
    	 
    	end($tmp->data);
    	if(key($tmp->data) == $tmp->pageCounts || $tmp->pageCounts == 0) {
    		$tmp->last = false;
    		$tmp->next = false;
    	}
    	reset($tmp->data);
    	return $tmp;
    }
    
    /**
     * 获取 filter 的 url
     *
     * @param string $remove
     * @param integer|null $newValue
     * @return string
     */
    protected function _makeUrl($remove = '', $newValue = null, $params = array()) {
        Timer::start(__METHOD__);
        
        $urlProcess = self::getUrlHelper();
        
        //     	$params = self::$urlParams;
        
        $siteSetting = self::getSiteSetting();
        $activeSharpLogic = $siteSetting['product_activate_sharp_logic'];
        
        if ($newValue !== null) {
        	$params[1][$remove] = $newValue;
        } else {
        	unset($params[1][$remove]);
        }
        
        if(array_key_exists('page', $params[1])) {
        	unset($params[1]['page']);
        }
        // Issue 249680 - URL - URL rewrite 1.0 : Keep the filters param in order
        if (!empty($params[1]) && count($params[1]) > 1) {
        	ksort($params[1]);
        	if (!empty($params[1]['man_id'])) {
        		$manId = $params[1]['man_id'];
        		unset($params[1]['man_id']);
        		$params[1] = array_merge(array('man_id'=>$manId), $params[1]);
        	}
        }
        if($activeSharpLogic=='1'){
        	$url = self::constructCleanUrl($params);
        }else{
        	$url = $urlProcess('product_list', $params[0], $params[1]);
        }
        
        Timer::end(__METHOD__);
        
        return $url;
    }
    
    protected function _getSelectedIds($apiParams = array()){
        $selectedIds = array(
    		'attr' 		=> array(),
            'brand' 	=> null,
    		'merchant'	=> null
        );
        if(array_key_exists('attr', $apiParams)) {
        	$selectedIds['attr'] = $apiParams['attr'];
        }
        if(array_key_exists('brand', $apiParams)){
        	$selectedIds['brand'] = $apiParams['brand'];
        }
        if(array_key_exists('merchant', $apiParams)) {
        	$selectedIds['merchant'] = $apiParams['merchant'];
        }
        
        return $selectedIds;
    }
    
    /**
     * @desc set base filters selected value
     * @param array $baseFilters
     * @param array $selectedIds
     */
    protected function _addBaseFiltersSelected($baseFilters = array(), $selectedIds = array()){
        
        if(!empty($baseFilters)) {
        	foreach ($baseFilters as $key => $filter) {
        		foreach ($filter->options as $key2 => $option) {
        				
        			// remove filters with 0 product and empty manufacturer
        			if ((int)$option->prodcount == 0 || $option->id == self::EMPTY_MAN) {
        				unset($baseFilters[$key]->options[$key2]);
        				$baseFilters[$key]->optionCount -= 1;
        				continue;
        			}
        
        			$selected = ($filter->labelId == self::MAN_ID && $option->id == $selectedIds['brand'])
        			                ||($filter->labelId == self::STO_ID && $option->id == $selectedIds['merchant'])
        			                ||in_array($option->id, $selectedIds['attr']);
        			
        			$baseFilters[$key]->options[$key2]->selected = $selected;
        		}
        	}
        }
        
        return $baseFilters;
    }
    
    /**
     * @desc set base filters prodCount value
     * @param array $baseFilters
     * @param array $newFilters
     */
    protected function _addBaseFiltersProdCount($baseFilters = array(), $newFilters = array()){
        if (empty($baseFilters)) {
            return $baseFilters;
        }
        $newLabelStructure = array();
        
        foreach ($newFilters as $key => $filter){
        	$tmp = array();
        	$tmp['offset'] = $key;
        	$tmp['optionIds'] = array();
        	foreach ($filter->options as $key2 => $option) {
        		$tmp[$option->id] = $key2;
        		array_push($tmp['optionIds'], $option->id);
        	}
        	$newLabelStructure[$filter->labelId] = $tmp;
        }
        $newLabelIds = array_keys($newLabelStructure);
        
        foreach ($baseFilters as $key => $filter) {
        	if(in_array($filter->labelId, $newLabelIds)) {
        		$labelId = $filter->labelId;
        		foreach ($filter->options as $key2 => $option) {
        			if(in_array($option->id, $newLabelStructure[$labelId]['optionIds'])){
        				$baseFilters[$key]->options[$key2]->prodcount = $newFilters[$newLabelStructure[$labelId]['offset']]->options[$newLabelStructure[$labelId][$option->id]]->prodcount;
        			}else {
        				$baseFilters[$key]->options[$key2]->prodcount = 0;
        			}
        		}
        	}else {
        		foreach ($filter->options as $key2 => $option) {
        			$baseFilters[$key]->options[$key2]->prodcount = 0;
        		}
        	}
        }
        
        return $baseFilters;
    }
    
    
    protected function _handleNoProductItem($baseFilters = array()){
        $showNoProductItem = 1;
        $siteSetting = Data::getInstance()->get('siteSetting');
        if( array_key_exists('productlist_filter_show_no_product', $siteSetting) ){
        	$showNoProductItem = $siteSetting['productlist_filter_show_no_product'];
        }
        if(!$showNoProductItem) {
        	if(!empty($baseFilters)) {
        		foreach ($baseFilters as $key => $filter) {
        			foreach ($filter->options as $key2 => $option) {
        				if($option->prodcount == 0) {
        					unset($baseFilters[$key]->options[$key2]);
        				}
        			}
        			if(empty($filter->options)) {
        				unset($baseFilters[$key]);
        			}
        		}
        	}
        }
        
        return $baseFilters;
    }
    
    protected function _handleSelected($attributes = array(), $urlParams = array()){
        $ret = array();
        
        if(!empty($attributes)) {
        	foreach ($attributes as $label) {
        		$tmp = new \stdClass();
        		$tmp->labelId = $label->labelId;
        		$tmp->labelName = $label->labelName;
        		$tmp->description = $label->description;
        		$tmp->popular = array();
        
        		if($label->optionCount > 6) {
        			$tmp->upper = array();
        			$tmp->lower = array();
        			$i = 0;
        			$parseOptions = array();//临时存储已被解析处理的options,以免在_getPopular里重复调用_parseOptions方法。
        			foreach ($label->options as $option) {
        				if($i < 6) {
        					$option = $this->_parseOptions($label, $option, $urlParams);
        					array_push($tmp->upper, $option);
        				}else {
        					$option = $this->_parseOptions($label, $option, $urlParams);
        					array_push($tmp->lower, $option);
        				}
        				array_push($parseOptions, $option);
        				$i++;
        			}
//         			$tmp->popular = $this->_getPopular($label, $label->options, $urlParams);
                    $tmp->popular = $this->_getPopular($parseOptions);
        		}else {
        			foreach ($label->options as $option) {
        				$option = $this->_parseOptions($label, $option, $urlParams);
        				array_push($tmp->popular, $option);
        			}
        		}
        
        		// 标识 filter 是否选中
        		$tmp->selected = false;
        		$i = 0;
        		foreach ($tmp->popular as $option) {
        			if ($option->selected) {
        				$tmp->selected = $i;
        				break;
        			}
        			$i++;
        		}
        
        		array_push($ret, $tmp);
        		unset($tmp);
        	}
        }
        
        return $ret;
    }
    
    /**
     * @desc handle price range
     */
    protected function _handlePriceRange($urlParams=array()){
        $tmp = new \stdClass();
        $tmp->labelId = self::PRI_ID;
        $tmp->labelName = 'Price';
        
        $siteSetting = Data::getInstance()->get('siteSetting');
        $urlProcess = Util::getViewHelper("GetUrl");        
        $tmp->maxPrice = '';
        $tmp->minPrice = '';
        $urlParams[0] = $urlParams[0] ? $urlParams[0] : array();
        $urlParams[1] = $urlParams[1] ? $urlParams[1] : array();
        if(array_key_exists('price_min', $urlParams[1])) {
        	$tmp->minPrice = (float)$urlParams[1]['price_min'];
        	unset($urlParams[1]['price_min']);
        }
        if(array_key_exists('price_max', $urlParams[1])) {
        	$tmp->maxPrice = (float)$urlParams[1]['price_max'];
        	unset($urlParams[1]['price_max']);
        }
        $tmp->action = $urlProcess('product_list', $urlParams[0]);
        $tmp->params = $urlParams[1];
        
        if($tmp->maxPrice || $tmp->minPrice) {
        	if(array_key_exists('page', $urlParams[1])) {
        		unset($urlParams[1]['page']);
        	}
        
        	$activeSharpLogic = $siteSetting['product_activate_sharp_logic'];
        	if($activeSharpLogic=='1'){
        		$tmp->removeUrl = self::constructCleanUrl($urlParams);
        	}else{
        		$tmp->removeUrl = $urlProcess('product_list', $urlParams[0], $urlParams[1]);
        	}
        }
        
        return $tmp;
    }
    
    /**
     *  @desc start of handle attribute order, manufacturer, store, price, anyother attributes
     *   move Manufacturer to the first and move Store to the second/end
     * @param array $attributes
     */
    protected function _handleAttributeOrder($attributes = array()){
        
        if ($attributes){
            foreach ($attributes as $key => $attribute) {
            	if($attribute->labelId == self::STO_ID) {
            		unset($attributes[$key]);
            		//array_unshift($attributes, $attribute); // move Store to the second
            		array_push($attributes, $attribute); // move Store to the end
            		break;
            	}elseif ($attribute->labelId == self::MAN_ID){
            	    unset($attributes[$key]);
            	    array_unshift($attributes, $attribute);
            	    break;
            	}
            }
        }
        
        return $attributes;
    }
    
    /**
     * @desc Issue 309992 - Improve Boolean filters
     * @param array $attributes
     * @param integer $cat
     */
    protected function _improveBoolFilters($attributes, $cat){
        Timer::start(__METHOD__);
        
        $articleParams = array(
            'cid' => $cat,
            'title' => 'filter'    
        );
        $customFilter = $this->_getArticleContent('custom-filter',$articleParams);
        unset($articleParams);
        
        if (trim($customFilter) != ''){
        	try {
        		libxml_use_internal_errors(true);
        		$customFilterXML = simplexml_load_string($customFilter);
        		//合并filter
        		$mergeFilters = $customFilterXML->merge_filters;
        		//删除filter
        		$removeFilters = $customFilterXML->remove_filters;
        		$mergeData = array();
        		if ($mergeFilters){
        			foreach ($mergeFilters as $filters){
        				foreach ($filters as $filter){
        					$display = $filter->attributes()->display;
        					//没有name 直接跳过
        					if(trim($display) == ''){
        						continue;
        					}
        					$tmp = new \stdClass();
        					$tmp->description = '';
        					$tmp->labelId = self::LABEL_ID;
        					$tmp->labelName = (string)$display;
        					//显示序列位置,如果没设置则设为0 在最后显示
        					$tmp->position = ((int)$filter->attributes()->position != 0) ? (int)$filter->attributes()->position : 0;
        					$tmp->options = array();
        					foreach ($filter as $option){
        						$labelId = substr($option->attr, 5);
        						$tmpOption = array();
        						$text = (string)$option->text ? (string)$option->text : '';
        						if (isset($option->attr_text)){
        							$text = 'attr_text';
        						}elseif (isset($option->value_text)){
        							$text = 'value_text';
        						}
        						$tmpOption['text'] = $text;
        						$value = (int)$option->value ? (int)$option->value : '';
        						$tmpOption['value'] = $value;
        						$tmpOption['labelId'] = $labelId;
        						$tmp->options[] = $tmpOption;
        						$tmp->all = array();
        					}
        					$mergeData[] = $tmp;
        					unset($tmp);
        				}
        			}
        		}
        		$removeData = array();
        		if ($removeFilters){
        			foreach ($removeFilters as $filters){
        				foreach ($filters as $filter){
        					$labelId = substr($filter->attributes()->attr, 5);
        					$removeData[] = $labelId;
        				}
        			}
        		}
        		$removeFilterKey = array();
        		//循环遍历,将要删除的filter 的位置和需要合并的filter信息存在数组中
        		foreach ($attributes as $key => $attribute){
        			if ($attribute->labelId == self::PRI_ID){
        				$attributes[$key] = $attributes[0];
        				$attributes[0] = $attribute;
        			}
        			$currentAttribute = null;
        			if ($mergeData){
        				foreach ($mergeData as $mergeKey => $data){
        					$options = $data->options;
        					foreach ($options as $option){
        						if ($currentAttribute == null) {
        							$currentAttribute = $this->_getCurrentAttribute($attribute);
        						}
        						foreach ((array)$currentAttribute as $attr){
        							if ($attr->id == $option['value'] && $option['labelId'] == $attribute->labelId){
        								if ($option['text'] == 'attr_text'){
        									$option['text'] = $attribute->labelName;
        								}elseif ($option['text'] == 'value_text'){
        									$option['text'] = $attr->name;
        								}
        								$attr->name = $option['text'];
        								$data->all[] = $attr;
        								$mergeData[$mergeKey] = $data;
        							}
        						}
        					}
        				}
        			}
        			if (in_array($attribute->labelId, $removeData)){
        				$removeFilterKey[] = $key;
        			}
        		}
        		if ($removeFilterKey){
        			arsort($removeFilterKey);
        			foreach ($removeFilterKey as $key){
        				unset($attributes[$key]);
        			}
        		}
        		if ($mergeData){
        			foreach ($mergeData as $key => $data){
        				unset($data->options);
        				$position = $data->position;
        				unset($data->position);
        				$data = $this->_improveFilterGroup($data);
        				if($position != 0){
        					array_splice($attributes, $position-1, 0, array($data));
        				}else{
        					array_push($attributes, $data);
        				}
        			}
        		}
        
        	} catch (\Exception $e) {
        
        	}
        }
        
        Timer::end(__METHOD__);
        
        return $attributes;
    }
    
    protected function _getCurrentAttribute($attribute){
    	if(isset($attribute->lower)){
    		return array_merge($attribute->upper,$attribute->lower);
    	}else{
    		return $attribute->popular;
    	}
    }
    
    protected function _improveFilterGroup($attribute){
    	$filter = $attribute->all;
    	$count = count($filter);
    	unset($attribute->all);
    	if ($count > 6){
    		$attribute->upper = array_slice($filter, 0, 6);
    		$attribute->lower = array_slice($filter, 6);
    		$attribute->popular = array_slice($filter, 0,5);
    	}else{
    		$attribute->popular = $filter;
    	}
    	return $attribute;
    }
    
    /**
     * @desc 如果 filter 选中超过 1 个，则需要在给所有的 filter 链接加上 nofollow 属性
     * @param array $urlParams
     */
    protected function _addNoFollow($urlParams=array()){
        $siteSetting = Data::getInstance()->get('siteSetting');
        $attrcount = 0;
        $alwaysnofollow = false;
        
        if ($urlParams[1]){
            foreach($urlParams[1] as $k=>$v){
            	if(strtolower(substr($k, 0, 5)) == 'attr_' || strtolower(substr($k, 0, 8)) == 'retailer' || strtolower(substr($k, 0, 5)) == 'price'){
            		$attrcount++;
            	}
            }
            
            if((int)$siteSetting['product_enabled_filter_nofollow'] && $attrcount > 0){
            	$alwaysnofollow = true;
            }
        }

        return $alwaysnofollow;
    }
    
    /**
     * @desc get price watch 
     * @param array $urlParams
     */
    protected function _getPriceWatch($urlParams = array()){
    	$siteSetting = Data::getInstance()->get('siteSetting');
    	
        $pricewatch = array();
        $tmpFilter = $this->getFilterParams($urlParams[1]);
        $pricewatch['filterParam'] = $tmpFilter ? urlencode($tmpFilter) : '';
        $pricewatch['filter_attribute'] = "c_".$urlParams[0]['cat'];
        
        if((int)$siteSetting['product_activate_sharp_logic']){
        	$pricewatch['ajax_direct_url'] = self::constructCleanUrl($urlParams);
        }
        
        return $pricewatch;
    }
    
    /**
     * @desc get article content.
     */
    protected function _getArticleContent($name,$params = array()){
        
        $returnStr = '';
        if ($name){
            $articleModel = new ArticleCms();
            $returnStr = $articleModel->fetchContent($name,$params);
        }
        
        return $returnStr;
        
    }
    
    /**
     * 获取产品列表
     *
     * @return APIWrapper_Entity_ProductSet
     */
    public function getProducts($params) {

    	Timer::start(__METHOD__);
    	
    	$products = Api::get()->getProducts($params);

    	//如果没有matchAttributes或者matchAttributes不需要跳转
    	if(empty($products->matchAttributes) || self::$matchAttributesNeedRedirect == false){
    		//Issue 309985 - Remove links from selected retailers, category level
    		$siteSetting = Data::getInstance()->get('siteSetting');
    		if (trim($siteSetting['product_list_remove_retailers']) != ''){
    			$removeRetailers = explode(',', $siteSetting['product_list_remove_retailers']);
    			$removeRetailers = array_map('trim', $removeRetailers);
    		}else{
    			$removeRetailers = '';
    		}
    			
    		$date = new DateTime();

    		foreach ($products->products as $key => $product) {
    			//Issue 309985 - Remove links from selected retailers, category level
    			if ($removeRetailers){
    				$offers = array();
    				foreach ((array)$product->offers as $offer) {
    					if(in_array($offer->retailerId, $removeRetailers)){
    						$offer->tag = 'WOLINK';
    						//$offer->relatedOffers = array();
    					}
    					$offers[] = $offer;
    				}
    				$product->offers = $offers;
    			}	
    					
    			//handle product time, used in is "New" product
    			$timestamp =  strtotime($product->createTime);
    			$currentTimeStamp = $date->getTimestamp();
    			if($currentTimeStamp <= $timestamp + $this->newProductTimeDistance) {
    				$products->products[$key]->isNew = true;
    			}else {
    				$products->products[$key]->isNew = false;
    			}
    
    
    			//handle round price
    			$products->products[$key]->listPrice = Price::getPriceFormat($product);
    			//$products->products[$key]->strutPrice = $oPrice->getPriceFormat($product->localMinPrice->value,$product->localMaxPrice->value,"AUT","default",$product->retailerCount);
    				 
    			$product  = Util::formatProduct($product,false);
    			$products->products[$key]->name = $product->name;
    			// use long description for freetext product
    		    $products->products[$key]->shortDescription = $product->shortDescription;
    		    
    			$digitsValidator = new \Zend\Validator\Digits();
    			//handle sales package
    			if(	!is_object($product->manufacturer)
    					|| !property_exists($product->manufacturer, 'id')
    					|| !property_exists($product->manufacturer, 'name')
    					|| !$digitsValidator->isValid($product->manufacturer->id)
    			) {
    				$products->products[$key]->salesPackage = null;
    			}else {
    					$params = array(
    						'productId' 		=> $product->id
    						,'categoryId'		=> $product->categoryId
    						,'manufacturerId'	=> $product->manufacturer->id
    						,'manufacturerName'	=> $product->manufacturer->name
    				);
    				$products->products[$key]->salesPackage = $this->_getSalesPackageEngine($params);
    			}
    				 
    			//calculate money saving
    			$moneySaving = Util::calMoneySaving($products->products[$key]->localMinPrice->value, $products->products[$key]->avgPrice->value);
    			$products->products[$key]->moneySaving = $moneySaving;
    		}
    	}
    	
    	Timer::end(__METHOD__);
    	
    	return $products;
    }
    
    /**
     * 获取当前 sub category 下面热门的 brands和当前 sub category 下面热门的 leaf categories
     *
     * @return array|null
     */
    public function getHotBrandsAndCategories($cat =null) {
    
    	$params = array();
		$params['cat'] = $cat;
    	$params['isleaf'] = 1;
    
    	$rows = Api::get()->getHierarchies($params);
    
    	return $rows;
    }
    
	    
}