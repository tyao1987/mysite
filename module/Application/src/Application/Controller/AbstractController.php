<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2014 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Application\Controller;

use Zend\Paginator\Adapter\ArrayAdapter;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\Mvc\MvcEvent;
use Zend\Session\Container;
use Zend\View\Model\ViewModel;
use Zend\Form\Element\Csrf;
use Zend\Form\Element;
use Zend\Form\Form;

use Test\Data;
use Test\Util\Common;
use Test\Util\Timer;

use Application\Service\Resource;
use Application\Util\Util;


abstract class AbstractController extends AbstractActionController {
    
    /**
     * @var 请求参数，包含  get 和 route 参数
     */
    protected $params = array();
    
    /**
     * @var 判断是否是ajax请求
     */
    
    protected $isAjax = null;
    
    /**
     * @var 页面名称 
     */
    protected $routerName = null;
    
    /**
     * @var 站点配置
     */
    protected $siteSetting = null;
    
    /**
     * (non-PHPdoc)
     * @see Zend\Mvc\Controller\AbstractController::attachDefaultListeners()
     */
    protected function attachDefaultListeners() {
    	
        Timer::start(__METHOD__);
        
		parent::attachDefaultListeners();
		$events = $this->getEventManager();
		$this->events->attach(MvcEvent::EVENT_DISPATCH, array($this, 'preDispatch'), 1000);
		$this->events->attach(MvcEvent::EVENT_DISPATCH, array($this, 'postDispatch'), -1000);
		
		Timer::end(__METHOD__);
	}
	
	/**
	 * Dispatch 前，将需要的数据获取到，附加到 protected 属性中
	 * 
	 * @param MvcEvent $e
	 */
	public function preDispatch(MvcEvent $e) {
		
	    Timer::start(__METHOD__);
	    
	    // 注册 get + route 参数
	    $this->params = array_merge($this->params()->fromQuery(), $this->params()->fromRoute());
	    Data::getInstance()->set('pageParams', $this->params, true);
	    
	    //设置viewhelperManager到Util
	    $viewHelper = $this->getServiceLocator()->get('viewhelpermanager');
	    Util::setViewhelperManager($viewHelper);
	    
	    $data = Data::getInstance();
	    $this->isAjax = $data->get('isAjax');
	    $this->routerName = $data->get('route');
	    
	    $this->siteSetting = $data->get('siteSetting');;
	    
	    Timer::end(__METHOD__);
	}
	
	/**
	 * Dispatch 后，将需要的数据附加到 viewModel 中，比如 seo-template， google Analytics
	 * 
	 * @param MvcEvent $e
	 */
	public function postDispatch(MvcEvent $e) {
		
	    Timer::start(__METHOD__);
	    
	    $layout = $e->getViewModel();
	    if (!$layout instanceof ViewModel) {
	        Timer::end(__METHOD__);
	        return;
	    }
	    
	    $view = $e->getResult();
	    
	    $data = Data::getInstance();
	    
	    // 设置 layout
		$layout->setTemplate('layout/layout.phtml');
	    
	    // 获取 seo-templates
	    $seoTemplates = Resource::loadSeoTemplates($this->params);
	    $seoTpl = $seoTemplates['seoTpl'];
	    if ($data->has('seoTpl')) {
	    	$seoTpl = array_merge($seoTpl, $data->get('seoTpl'));
	    }
	    $data->set('seoTpl', $seoTpl);
	    $layout->setVariable('seoTpl', $seoTpl);
	    unset($seoTemplates);
	    
 	    // 获取 tplParams
 	    $tplParams = $this->_getTplParams();
 	    if ($data->has('tplParams')) {
 	        $tplParams = array_merge($tplParams, $data->get('tplParams'));
 	    }
 	    $data->set('tplParams', $tplParams);
 	    $layout->setVariable('tplParams', $tplParams);
 	    $view->setVariable('tplParams', $tplParams);

 	    
// 	    $layout->setVariable('device', Util::checkDevice());
			    
	    Timer::end(__METHOD__);
	}
	
	/**
	 * 渲染 viewModel 并直接返回 response
	 * 
	 * @param ViewModel $viewModel
	 * @return \Zend\Http\PhpEnvironment\Response
	 */
	protected function _renderViewModel(ViewModel $viewModel,$statusCode = 200) {
		
	    Timer::start(__METHOD__);
	    
	    $viewRender = $this->getServiceLocator()->get('ViewRenderer');
	    $html = $viewRender->render($viewModel);
	     
	    $response = $this->getResponse();
	    $response->setStatusCode($statusCode);
	    $response->setContent($html);
	    
	    Timer::end(__METHOD__);
	    
	    return $response;
	}
	
	public function notFoundAction() {
	    return $this->_notFound();
	}
	
	/**
	 * 返回 404 错误页面
	 * 
	 * @return \Zend\Http\PhpEnvironment\Response
	 */
	protected function _notFound() {
		
	    Timer::start(__METHOD__);
	    
	    $event = $this->getEvent();
	    
 	    $data = Data::getInstance();
	    
 	    $viewRender = $this->getServiceLocator()->get('ViewRenderer');
	    
 	    $viewModel = new ViewModel($data->getData());

 	    $viewModel->setTemplate('error/404.phtml');
 	    $event->setResult($viewModel);
 	    self::postDispatch($event);
	    
 	    $layout = $event->getViewModel();
 	    $layout->content = $viewRender->render($viewModel);
	    
 	    $html = $viewRender->render($layout);
	    
	    $response = $this->getResponse();
	    $response->setContent($html);
	    $response->setStatusCode(404);
	    Timer::end(__METHOD__);
	    
	    return $response;
	}
	
	
	protected function _getTplParams(){
		
	    Timer::start(__METHOD__);
	    
	    $tplParams = include_once ROOT_PATH . '/module/Application/config/tplParams.config.php';
	    $siteSetting = Data::getInstance()->get('siteSetting');
	    $params = array();
	    foreach ($tplParams as $param){
	        $params[$param] = $siteSetting[$param];
	    }
	    
	    Timer::end(__METHOD__);
	    
	    return $params;
	}
	
	/**
	 *
	 * @param string $url 要跳转的 URL
	 * @param integer $code HTTP code，默认 301
	 */
	protected function _redirectToUrl($url, $code = 301) {
		return $this->redirect()->toUrl($url)->setStatusCode($code);
	}
	
	
	protected function _createFormCsrf(){
		 
		$form = new \Zend\Form\Form();
		$form->add(array(
				'type' => 'Csrf',
				'name' => 'csrf',
				'options' => array(
						'csrf_options' => array(
							'salt' => 'unique'
						)
				)
		));
		return $form;
	}
	
}
