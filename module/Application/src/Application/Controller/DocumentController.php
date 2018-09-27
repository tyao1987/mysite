<?php
namespace Application\Controller;

use Zend\View\Model\ViewModel;

use Test\Data;
use Test\Util\Timer;

use Application\Model\ArticleCms;
use Application\Service\Cache;
use Application\Util\Util;
use Application\Exception;

class DocumentController extends AbstractController
{
	protected $pageName = null;

	public function indexAction() {
		Timer::start(__METHOD__);
		
		$this->pageName = Data::getInstance()->get('route');
        switch($this->pageName){
			case 'document':
                return $this->document();
                break;
            default:;
        }
        
		Timer::end(__METHOD__);
	}

    /**
     * The BASIC method to show static content by it's title.
     * For example:
     *  if URL:  /user-guide/find.html
     *  then Title(keyword) is: '/user-guide/find'
     *
     *  then we qeury the table 'articles' for a record:
     *      title   : '/user-guide/find'
     *      site_id : current_site_id, GBR is 17. 
     *      type    : 10 (see the definition in model Article.php)
     */
    public function document() {
        Timer::start(__METHOD__);
        
        $title = $this->params['title'];
        $type = $this->params['type'];
        $type = strtolower($type);
        
        $cache = Cache::get('dynamicCache');
        $cacheKey = Util::makeCacheKey($this->pageName . '_' . $title);
        if (!$row = $cache->getItem($cacheKey)) {
        	//在原先文档转换过程中，将空格转换为下划线
        	$title = str_replace(" ","_",$title);	
    		$articleModel = new ArticleCms();
    		$params = array();
    		$row = array();
    		$params['title'] = $title;
			$row['description'] = $articleModel->fetchContent('document',$params);
			
        	if ($row['description']) {
    			$cache->setItem($cacheKey, $row);
    			$item = $cache->getItem($cacheKey);
        	}
        }
        
        if(!$row['description']){
			$this->_redirectToUrl('/');
        }
        $document = $row['description'];
        	
        $data = Data::getInstance();

       	if ((false !== stripos($document, '@meta.begin')) && (false !== stripos($document, '@meta.end'))) {
        		
	       	$parseHelper = Util::getViewHelper('ParseContent');
		 		
	        preg_match('/\@meta\.title="([^"]+)"/i', $document, $matches);
	        if ($matches && !empty($matches[1])) {
	        	$pageTitle = Util::formatString($parseHelper($matches[1]));
	        	$tplParams['meta_title'] = $pageTitle;
	        	if ($data->has('tplParams')) {
	        		$tplParams = array_merge($tplParams, $data->get('tplParams'));
	        	}
	        	$data->set('tplParams', $tplParams);
	        }
        		
        	preg_match('/\@meta\.description="([^"]+)"/i', $document, $matches);
        	if ($matches && !empty($matches[1])) {
        		$metaDescription = Util::formatString($parseHelper($matches[1]));
        		$tplParams['meta_description'] = $metaDescription;
        		if ($data->has('tplParams')) {
        			$tplParams = array_merge($tplParams, $data->get('tplParams'));
        		}
        		$data->set('tplParams', $tplParams);
        	}
        		
//         	preg_match('/\@meta\.keywords="([^"]+)"/i', $document, $matches);
//         	if ($matches && !empty($matches[1])) {
//         		$metaKeywords = Util::formatString($parseHelper($matches[1]));
//         		$tplParams['meta_keywords'] = $metaKeywords;
//         		if ($data->has('tplParams')) {
//         			$tplParams = array_merge($tplParams, $data->get('tplParams'));
//         		}
//         		$data->set('tplParams', $tplParams);
//         	} 
        }
        	
        $pageVariables['description'] = $document;
        $pageVariables['params'] = $this->params;
        $data = Data::getInstance();
        $result = array_merge($data->getData(), $pageVariables);
        $viewModel = new ViewModel($result);
        $viewModel->setTemplate('application/document/document.phtml');
        
        Timer::end(__METHOD__);
        
        return $viewModel;
		
    }

}
