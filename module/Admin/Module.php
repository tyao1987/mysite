<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2014 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Admin;

use Zend\Mvc\ModuleRouteListener;
use Zend\Mvc\MvcEvent;
use Zend\ModuleManager\ModuleManager;

use Test\Data;
use Test\Util\Timer;
use Admin\Service\Resource;


class Module
{
    /**
     * 初始化，这里要放置尽可能少的操作
     *
     * @param ModuleManager $moduleManager
     */
    public function init(ModuleManager $moduleManager) {
        //Timer::start(__METHOD__);



        //Timer::end(__METHOD__);
    }

    public function onBootstrap(MvcEvent $e) {

        //Timer::start(__METHOD__);

        // 获取 Data 实例
        $data = Data::getInstance();

        $eventManager = $e->getApplication()->getEventManager();
        $moduleRouteListener = new ModuleRouteListener();
        $moduleRouteListener->attach($eventManager);

        $appConfig = $e->getApplication()->getServiceManager()->get('config');

        // 注册事件
        $eventManager = $e->getApplication()->getEventManager();
        $eventManager->attach('route', function($event) use ($data) {

        	$matches = $event->getRouteMatch();
        	$action = '';
        	$controller = '';
        	if ($matches) {
        	    $action     = $matches->getParam('action');
        	    $controller = $matches->getParam('controller');
        	    $controller = substr($controller, strrpos($controller, '\\') + 1);
        	}
        	$data->set('controller', strtolower($controller), true);
        	$data->set('action', $action, true);

        	$interceptorService = $event->getTarget()->getServiceManager()->get("interceptorService");
        	$interceptorService->check($event);


        });

        $this->_initConfig();

        // 注册 site
        $appConfig['site'] = Resource::loadSite();
        
        $data->set('site', $appConfig['site'], true);
        
        $config = $data->get("config");
       
        //设置时区
        date_default_timezone_set($config['cmsDefaultTimezone']);

        //Timer::end(__METHOD__);

    }

    public function getConfig() {
        //Timer::start(__METHOD__);

        $siteRouteConfig = require __DIR__ . '/config/router/' . strtolower(__NAMESPACE__) . '.config.php';
        $routerConfig = array("routes"=>$siteRouteConfig);
        $moduleConfig = require __DIR__ . '/config/module.config.php';
        $page = require __DIR__ . '/config/page.config.php';
        $moduleConfig = array_merge(array('router' => $routerConfig, 'navigation' => $page), $moduleConfig);
        
    	//Timer::end(__METHOD__);
    	return $moduleConfig;

    }

    public function getAutoloaderConfig() {
        // 执行顺序1
        return array(
            'Zend\Loader\ClassMapAutoloader' => array(
                __DIR__ . '/autoload_classmap.php',
            ),
            'Zend\Loader\StandardAutoloader' => array(
                'namespaces' => array(
                    __NAMESPACE__ => __DIR__ . '/src/' . __NAMESPACE__,
                	'Application' => ROOT_PATH . '/module/Application/src/Application',
                ),
            ),
        );
    }

    protected function _initConfig(){
    	//Timer::start(__METHOD__);

    	$config = include (ROOT_PATH . "/module/Application/config/config." . APPLICATION_ENV . ".php");
    	$adminConfig = include (ROOT_PATH . "/module/Admin/config/config." . APPLICATION_ENV . ".php");
    	Data::getInstance()->set("config", array_merge($config,$adminConfig), true);
    	
    	//Timer::end(__METHOD__);
    }

}
