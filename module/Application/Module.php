<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2013 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Application;

use Zend\Mvc\ModuleRouteListener;
use Zend\Mvc\MvcEvent;
use Zend\ModuleManager\ModuleManager;
use Zend\Mvc\Application;

use Test\Util\Timer;
use Application\Util\Util;
use Test\Data;
use Application\Service\Resource;

class Module
{
    /**
     * 初始化，这里要放置尽可能少的操作
     *
     * @param ModuleManager $moduleManager
     */
    public function init(ModuleManager $moduleManager)
    {
    	Timer::start(__METHOD__);
    	
    	// 设置访问域名
    	//@todo 将所有的$_SERVER['HTTP_HOST']改为从data中取
    	$domain = (substr($_SERVER['SERVER_NAME'], -1) == '.') ? substr($_SERVER['SERVER_NAME'], 0, -1) : $_SERVER['SERVER_NAME'];
    	Data::getInstance()->set('domain', strtolower($domain), true);
    	
    	//$domain = $_SERVER['HTTP_HOST'];
    	$cookieDomain = substr($domain, strpos($domain, '.'));
    	Data::getInstance()->set('cookieDomain', strtolower($cookieDomain), true);
		
    	Timer::end(__METHOD__);
    }
    
    public function onBootstrap(MvcEvent $e)
    {
        Timer::start(__METHOD__);
        
        // 获取 Data 实例
        $data = Data::getInstance();
        $applicationConfig = $e->getApplication()->getServiceManager()->get('config');
        
        $eventManager = $e->getApplication()->getEventManager();
        $eventManager->attach('route', function($event) use ($data)
        {          
        	// 是否 ajax 请求
        	$data->set('isAjax', $event->getRequest()->isXmlHttpRequest(), true);
        	// 设置当前路由名称
        	$route = $event->getRouteMatch()->getMatchedRouteName();
        	$data->set('route', $route, true);
            
        });
        
        // 处理 404 和 500 错误
        $eventManager->attach('dispatch.error', function($event) use ($data) {
        
        	$error = $event->getError();
        	if (empty($error)) {
        		return;
        	}
        	switch ($error) {
        		case Application::ERROR_CONTROLLER_NOT_FOUND:
        		case Application::ERROR_CONTROLLER_INVALID:
        		case Application::ERROR_ROUTER_NO_MATCH:
        			$route = 'not_found';
        			$controller = 'Application\Controller\Index';
        			$action = 'notFoundAction';
        			break;
        		default:
        			$exception = $event->getParam('exception');
        			if(!empty($exception)){
        				if(APPLICATION_ENV == "production") {
        					$urlPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        					if ($urlPath == '/error') {
        						throw $exception;
        					}
        					
        					\Application\Exception::log($exception);
        					 
        					// send mail
        					if (!empty($_SERVER['HTTP_REFERER'])) {
        						$parts = parse_url($_SERVER['HTTP_REFERER']);
        						if (0 == strcasecmp($parts['host'], $_SERVER['SERVER_NAME'])) {
        							\Application\Exception::mailError($exception);
        						}
        					}
        					 
        				} else {
        					echo \Application\Exception::log($exception, true);
        					die;
        				}
        			}
        
        			$route = 'error';
        			$urlHelper = Util::getViewHelper("GetUrl");
        			$redirectUrl = $urlHelper($route);
        			header("Location: " . $redirectUrl , true, 301);
        			exit;
        	}
        	if(!$data->has('route')){
        		$data->set('route', $route, true);
        	}
        	
        	$data->set('pageParams', array(), true);
        	
        	$controllerLoader = $event->getApplication()->getServiceManager()->get('ControllerLoader');
        	$controller = $controllerLoader->get($controller);
        	$controller->setEvent($event);
        	
        	return $controller->{$action}();
        }, 100);
        $this->_initConfig();
        
        $data->set('site', $applicationConfig['site'], true);
        
        // 注册 siteSetting 回调函数，第一次加载用回调，随后置成获取到的数据
        $siteSetting = Resource::loadSiteSetting();
        $data->set('siteSetting', $siteSetting, true);
        
        //设置时区
        date_default_timezone_set($siteSetting['site_timezone']);
        
        //设置正则配置
        $this->_initRegex();
        
        Resource::loadSession();
        
        $moduleRouteListener = new ModuleRouteListener();
        $moduleRouteListener->attach($eventManager);
        
        Timer::end(__METHOD__);
    }

    // 将 config.xml 转化为数组存进 \Test\Data 中
    protected function _initConfig()
    {
    	Timer::start(__METHOD__);
    	
    	$config = include (__DIR__ . "/config/config." . APPLICATION_ENV . ".php");
    	
    	Data::getInstance()->set('config', $config, true);
    
    	Timer::end(__METHOD__);
    }
    
    protected function _initRegex()
    {
    	Timer::start(__METHOD__);
    	$regex = include (__DIR__ . "/config/regex.php");
    	Data::getInstance()->set("regex", $regex, true);
    
    	Timer::end(__METHOD__);
    }
    
    public function getConfig()
    {
        Timer::start(__METHOD__);
        
        // 获取 domain
        $domain = Data::getInstance()->get('domain');
        // 加载 sites 信息
        $siteConfig = Resource::loadSite($domain);
        $country = $siteConfig["country"];
        // 加载 route 配置
        $siteRouteConfig = __DIR__ . '/config/router/' . $siteConfig["short_name"] . '.php';
        if (file_exists($siteRouteConfig)) {
        	$routerConfig = require $siteRouteConfig;
        } else {
        	$routerConfig = require __DIR__ . '/config/router/'.strtolower($country).'.php';
        }
        //加载公共路由
        $publicRouteConfig = require __DIR__ . '/config/router/public.php';
        $routerConfig = array("routes"=>array_merge($routerConfig['routes'],$publicRouteConfig));
        
        // 加载 module 配置
        $moduleConfig = require __DIR__ . '/config/module.config.php';
        
        // 合并加载的配置信息
        $moduleConfig = array_merge(array('site' => $siteConfig, 'router' => $routerConfig), $moduleConfig);
        
        Timer::end(__METHOD__);
        
        return $moduleConfig;
        
    }

    public function getAutoloaderConfig()
    {
    	
        return array(
            'Zend\Loader\StandardAutoloader' => array(
                'namespaces' => array(
                    __NAMESPACE__ => __DIR__ . '/src/' . __NAMESPACE__,
                	'Admin' => ROOT_PATH . '/module/Admin/src/Admin',
                ),
            ),
        );
    }
    
}

