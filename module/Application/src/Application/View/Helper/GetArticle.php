<?php
namespace Application\View\Helper;

use Zend\View\Helper\AbstractHelper;

use Test\Data;
use Test\Util\Timer;

use Application\Model\ArticleCms;
use Application\Service\Cache;
use Application\Util\Util;

class GetArticle extends AbstractHelper {
	
	static $site = null;
	
	/**
	 * 获取CMS内容
	 * 
	 * @param string $type 类型
	 * @param array $params 参数
	 * @param boolean $cacheEnabled 是否启用 cache
	 * @return string
	 */
	public function __invoke($type, $params = array(), $cacheEnabled = true)
	{
	    Timer::start(__METHOD__);
	    
		if (self::$site == null) {
			self::$site = Data::getInstance()->get("site");
		}
		
		if (!$cacheEnabled) {
			
			$article = new ArticleCms();
			$data = $article->fetchContent($type, $params);
			$data = $this->view->ParseContent($data);
			
		} else {
			
			$cache = Cache::get('dynamicCache');
			$cacheKey = Util::makeCacheKey(array('type'=>$type,'params'=>$params));
			$data = $cache->getItem($cacheKey);
			if (null === $data) {
				$article = new ArticleCms();
				$data = $article->fetchContent($type, $params);
				$data = $this->view->ParseContent($data);				
				$cache->setItem($cacheKey,$data);
			}
			
		}
		
		Timer::end(__METHOD__);
		
		return $data;
	}
	
   
}
