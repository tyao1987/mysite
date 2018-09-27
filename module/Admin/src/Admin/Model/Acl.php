<?php
namespace Admin\Model;

use Admin\Model\Auth;
use Admin\Model\User;

use Test\Util\Common;

use Application\Service\Cache;
use Application\Util\Util;

use Zend\Permissions\Acl\Acl as ZendAcl;
use Zend\Permissions\Acl\Resource\GenericResource;
use Zend\Permissions\Acl\Role\GenericRole;

class Acl extends ZendAcl
{
	protected $resourceList;
	
	public function __construct()
	{
		// add current user as role
		$currentUser = Auth::getIdentity();

		if ($currentUser) {
			$this->addRole(new GenericRole($currentUser['id']));
		}
		
		$this->loadResources();
		
		$this->loadCurrentUsersPermissions();
		
		//load common resources
		$this->addResource(new GenericResource('admin_auth'));
		
		//everybody
		$this->allow(null, 'admin_auth');
		
		//load common resources
		$this->addResource(new GenericResource('cmsFront'));
		
		//everyone can run the page builders
		$this->allow(null, 'cmsFront');
		
		// grant the super admin access to everything
		if ($currentUser && $currentUser['id'] == User::SUPERUSER_ROLE) {
			$this->allow(User::SUPERUSER_ROLE);
		}
		
	}
	
	public function loadResources() {
		$cache = Cache::get('dynamicCache');
		$cacheKey = Util::makeCacheKey('ALL_ACL_RESOURCES', false);
		$resources = $cache->getItem($cacheKey);
		if (null === $resources){
		
			$aclResourcesCacheFile = Util::getWritableDir('dataCache') . 'aclResources.serialized';
			if (file_exists($aclResourcesCacheFile)) {
				$resources = unserialize(Common::readFile($aclResourcesCacheFile));
			} else {
				$user = new User();
				$resources = $user->getAllAclResources();
			}
			$cache->addItem($cacheKey, $resources);
		}
		
		
		$resourceList = array();
		$emptyController = array();
		 
		// setup resources
		foreach ($resources as $resource) {
			if($resource['module_name'] != 'public' && $resource['module_name'] != 'front') {
				// set module
				if (!isset($resourceList[$resource['module_name']])) {
					$resourceList[$resource['module_name']] = null;
					 
					//load the module resource
					$this->addResource(new GenericResource($resource['module_name']));
				}
		
				// if controller
				if ($resource['controller_name'] != null) {
					 
					// add controller to module
					if (!isset($resourceList[$resource['module_name']][$resource['controller_name']])) {
						$resourceList[$resource['module_name']][$resource['controller_name']] = null;
		
						
					}
					 
					// add action
					if ($resource['action_name'] != null && $resource['action_name'] != '*') {
						$resourceList[$resource['module_name']][$resource['controller_name']][] = $resource['action_name'];
		
						//load the action resource
						$key = $resource['module_name'] . '_' . $resource['controller_name'] . '_' . $resource['action_name'];
						$this->addResource(new GenericResource($key), $resource['module_name']);
						 
						
					}elseif($resource['action_name'] == '*'){
						//load the action resource
						$key = $resource['module_name'] . '_' . $resource['controller_name'];
						if(!$this->hasResource($key)){
							$this->addResource(new GenericResource($key), $resource['module_name']);
						}
					}
				}
			}
		}
		 
		$this->resourceList = $resourceList;
		 
		
	}
	
	public function loadCurrentUsersPermissions(){
		 
		$currentUser = Auth::getIdentity();

		if($currentUser) {
			$user = new User();
			$permissions = $user->getCurrentUsersAclResources();
			 
			// use user id for role
			$userInfo = $user->getCurrentUser();
			if($permissions) {
				foreach ($permissions as $key => $value) {
					if($value == 1){
						$this->allow($userInfo['id'],$key);
					}else{
						$this->deny($userInfo['id'],$key);
					}
				}
			}
		}
	}
	
	public function getResourceList()
	{
		return $this->resourceList;
	}
	
}