<?php

namespace Admin\Service;

use Admin\Model\Acl;
use Admin\Model\AdminLog;
use Admin\Model\Auth;
use Admin\Model\Sites;

use Application\Service\Cache;
use Application\Util\Util;

use Test\Data;
use Test\Util\Common;

use Zend\Mvc\MvcEvent;

class Interceptor {
	
	/**
	 * the current user's identity
	 *
	 * @var zend_db_row
	 */
	private $_identity;
	
	/**
	 * the acl object
	 *
	 * @var zend_acl
	 */
	private $_acl;
	
	
	public function check(MvcEvent $e)
	{
		$data = Data::getInstance();
		$cache = Cache::get('dynamicCache');
		$batchId = $cache->getItem('RESOURCES_BATCHID');
		if(null === $batchId){
			$batchId = md5(time());
			$cache->setItem('RESOURCES_BATCHID', $batchId);
		}
		$this->_identity = Auth::getIdentity();
		$key = 'ACL_OBJ';
		if ($this->_identity) {
			$key .= '_' . $this->_identity['id'] . '_' .$batchId;
		}
		
		$cacheKey = Util::makeCacheKey($key);
		$acl = $cache->getItem($cacheKey);
		if (null === $acl) {
			$acl = new Acl();
			$cache->addItem($cacheKey, serialize($acl));
		} else {
			$acl = unserialize($acl);
		}
		 
		$this->_acl = $acl;

		if(!empty($this->_identity)){
			// use id instead of role
			$role = $this->_identity['id'];
			//$role = $this->_identity->role;
		}else{
			$role = null;
		}
		
		$module = ACTIVE_MODULE;
		$controller = $data->get('controller'); 
		$action = $data->get('action'); 
		
		//go from more specific to less specific
		$moduleLevel = $module;
		$controllerLevel = $moduleLevel . '_' . $controller;
		$actionLevel = $controllerLevel . '_' . $action;
		
		if ($this->_acl->hasResource($actionLevel)) {
			$resource = $actionLevel;
		}elseif ($this->_acl->hasResource($controllerLevel)){
			$resource = $controllerLevel;
		}else{
			$resource = $moduleLevel;
		}
		/**
		 * @todo make sure this works
		 */
		if($module != 'public' && $controller != 'public'
				&& !($module=="mod_akamai" && $controller=="admin" && $action=='clear') && 
				!($module=="mod_memcache" && $controller=="admin" && ($action=='clear-cache' || $action=='clear-memcache'  || $action=='country'))){
			 
			if (!$this->_checkIp()) {
				throw new \Exception('Access denied');
			}
			
			if (!$this->_acl->isAllowed($role, $resource)) {
				if (!$this->_identity) {
					$url = '/auth/login';
					$response = $e->getResponse();
					$response->getHeaders()->addHeaderLine('Location', $url);
					$response->setStatusCode(302);
					$response->sendHeaders();
					exit;
					
				}else{
					
					$url = '/auth/no-auth';
					$response = $e->getResponse();
					$response->getHeaders()->addHeaderLine('Location', $url);
					$response->setStatusCode(302);
					$response->sendHeaders();
					exit;

				}
			} else {
		
				$site = $data->get('site');
				
				$data = array();
				$userId = ($this->_identity['id'])?$this->_identity['id']:0;
				$data['user_id'] = $userId;
				$data['user_name'] = $this->_identity['first_name'] . ' ' . $this->_identity['last_name'];
				$data['url'] = $_SERVER['REQUEST_URI'];
		
				$postParams = $e->getRequest()->getPost()->toArray();
				$getParams = $e->getRequest()->getQuery()->toArray();
				$params = array_merge($postParams, $getParams);
				
				$data['params'] = ($params)?serialize($params):'';
		
				$data['ip'] = Common::getRemoteIp();
				$data['date'] = date("Y-m-d H:i:s");
		
				$data['site_id'] = $site['site_id'];
				
				$adminLog = new AdminLog();
				$adminLog->insert($data);
			}
			 
		}
		
	}
	
 	/**
 	 * Check Ip  
 	 * 
 	 * @return boolean
 	 */
	protected function _checkIp()
	{
		$currentIp = Common::getRemoteIp();
	
		$allow = false;
		$ipList = $this->_loadList(ROOT_PATH . '/module/Admin/data/adminIp.txt');
		foreach ($ipList as $search) {
			if (false === strpos($search, '*')) {
				if ($currentIp === $search) {
					return true;
				}
			} else {
				 
				if ($this->_checkRange($currentIp, $search)) {
					return true;
				}
			}
		}
		return $allow;
	}
	
	private function _checkRange($ipAddr, $range)
	{
		$long = ip2long($ipAddr);
		$first = str_replace('*', '0', $range);
		$last = str_replace('*', '255', $range);
		if ($long >= ip2long($first) && $long <= ip2long($last)) {
			return TRUE;
		} else {
			return FALSE;
		}
	}
	
	private function _loadList($filename)
	{
		$result = array();
	
		if (is_readable($filename) && ($data = file($filename, TRUE))) {
			foreach ($data as $line) {
				$line = trim($line);
				if ($line == '' || $line{0} == '#') {continue;}
				$result[] = $line;
			}
		}
	
		return $result;
	}
}

?>