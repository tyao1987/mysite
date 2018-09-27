<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2014 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Application\Controller;

use Zend\Mvc\MvcEvent;
use Zend\View\Model\ViewModel;
use Zend\Session\Container;
use Test\Data;
use Test\Util\Common;
use Test\Util\Timer;

use Application\Model\ArticleCms;
use Application\Service\Cache;
use Application\Util\Util;
use Application\Model\Category;

class CategoryController extends AbstractController {
    
    protected $cookieParams;

    /**
     * @var array Url 参数
     */
    protected $urlParams = array();
    
    public function preDispatch(MvcEvent $e) {
        
        parent::preDispatch($e);
        
        Timer::start(__METHOD__);
        
        $data = Data::getInstance();
        
        $categoryModel = new Category();
        // 解析请求参数
        $params = $categoryModel->parseParams($this->params);
        
        //if(!$categoryInfo){
        	// 返回 404
        	//return $this->_notFound();
        //}
        
        $urlParams = $params['urlParams'];
        
        //if(empty($urlParams[0]['categoryName'])){
        	//$urlParams[0]['categoryName'] = $categoryInfo['name'];
        //}
		        
        // 获取 category 信息
        try{
           //$category = $categoryModel->getCategory($params['apiParams']);
           //可能是由于参数错误导致的category返回为空
           //if($category->id == null){
               //$category = $categoryModel->getCategoryCache($params['urlParams'][0]['cat']);
           //}
       }catch (\Exception $e){
           return $this->_notFound();
       }
        // 将 categoryName 添加到 urlParams 中
        // $urlParams[0]['categoryName'] = $category->name;
        $this->urlParams = $urlParams;
        
        $this->_checkUrl();

        $this->cookieParams = $params['cookieParams'];
        
        Timer::end(__METHOD__);
    }
    
    /**
     *CategoryList CL页面 
     */
    public function productAction() {
    	
        Timer::start(__METHOD__);
        
        $data = Data::getInstance();
        
        $urlProcess = Util::getViewHelper('GetUrl');

        $viewData = array(
        	'urlParmas'	=>  $this->urlParams,
        	'viewType'	=>	$this->cookieParams['viewType'],
        	'numberOfProducts'	=>	$this->cookieParams['numberOfProducts'],
        	'isAjax'	=>	$this->isAjax
        );
        
        $viewModel = new ViewModel(array_merge($data->getData(), $viewData));
        
        Timer::end(__METHOD__);
        
        return $viewModel;
        
    }
    
	    
    public function postDispatch(MvcEvent $e) {
    
        Timer::start(__METHOD__);
        
        $layout = $e->getViewModel();
        if (!$layout instanceof ViewModel) {
            Timer::end(__METHOD__);
            return false;
        }

        $view = $e->getResult();
        
        $data = Data::getInstance();
        
        //tpl params
		$tplParams = $data->has('tplParams') ? $data->get('tplParams') : array();
        $tplParams = array_merge($tplParams,$this->_getCategoryTplParams());
        $data->set('tplParams', $tplParams);
        $layout->setVariable('tplParams', $tplParams);
        $view->setVariable('tplParams', $tplParams);
        
        
        parent::postDispatch($e);
        
        Timer::end(__METHOD__);
    }
    
    /**
     * 将变量设置增加到模板变量列表中
     *
     */
    protected function _getCategoryTplParams() {
        
    	$data = Data::getInstance();
        	
        $tplParams = array(
        	'category_id'      => $this->urlParams[0]['cat'],
        	'category_name'    => $this->urlParams[0]['categoryName'],
        );
        
    	$categoryModel = new Category();
    	
    	return $tplParams;
    }
    
    
    protected function _checkUrl(){
        // 检查 url
        $urlProcess = Util::getViewHelper('GetUrl');
        $correctUrl = urldecode($urlProcess($this->routerName, $this->urlParams[0]));
        if(!$this->isAjax) {
            $currentUrl = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
        	if($currentUrl != $correctUrl) {
        	    $correctUrl = $urlProcess($this->routerName, $this->urlParams[0],$this->urlParams[1], '', false);
        		$this->_redirectToUrl($correctUrl,301);
        	}
        }
    }
    
}
