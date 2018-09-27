<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2014 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Admin\Controller;


use Zend\Mvc\Controller\AbstractActionController;
use Zend\Mvc\MvcEvent;
use Test\Data;

abstract class AbstractController extends AbstractActionController {
    const MSG_SUCCESS  = 'success';
    const MSG_INFO     = 'info';
    const MSG_DEFAULT  = 'default';
    const MSG_ERROR    = 'error';

    /**
     * @var 请求参数，包含  get 和 route 参数
     */
    protected $params = array();
    /**
     * (non-PHPdoc)
     * @see Zend\Mvc\Controller\AbstractController::attachDefaultListeners()
     */
    protected function attachDefaultListeners() {
        parent::attachDefaultListeners();
        $events = $this->getEventManager();
        $this->events->attach(MvcEvent::EVENT_DISPATCH, array($this, 'preDispatch'), 1000);
        $this->events->attach(MvcEvent::EVENT_DISPATCH, array($this, 'postDispatch'), -1000);
    }
    /**
     * Dispatch 前，将需要的数据获取到，附加到 protected 属性中
     *
     * @param MvcEvent $e
     */
    public function preDispatch(MvcEvent $e) {
        // 注册 get + route 参数
        $this->params = array_merge($this->params()->fromQuery(), $this->params()->fromRoute());
        Data::getInstance()->set('pageParams', $this->params, true);
    }
    public function postDispatch(MvcEvent $e) {
        $data = Data::getInstance();
        $controller = $data->get('controller');
        $action = $data->get('action');
//         $this->layout()->setVariable('breadcrumbs', array($action => '/'.$controller.'/'.$action));

        if($controller=="auth" && $action=="search" || $controller=="index" && $action=="servers"){
        	$this->layout()->setTemplate('layout/empty.phtml');
        }elseif($controller == 'site'){
        	$this->layout('layout/site.phtml');
        }else{
        	$this->layout()->setTemplate('layout/layout.phtml');
        }

    }
    /**
     * 发送信息
     * @param string $message
     * @param string $type
     * @return \Zend\Mvc\Controller
     */
    protected function _message($message , $type = self::MSG_SUCCESS) {
        $this->flashMessenger()->setNamespace($type);
        $this->flashMessenger()->addMessage($message);
        return $this;
    }

    /**
    * url 跳转
    * @param  string $url
    * @param  int $status
    */
    public function _redirect($url , $status)
    {
        header("Location: " . $url , true, $status);
        exit;
    }
}
