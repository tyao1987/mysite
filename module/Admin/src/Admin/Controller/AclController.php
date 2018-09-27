<?php
namespace Admin\Controller;

use Admin\Model\Action;
use Admin\Model\Controller;
use Admin\Model\Module;
use Admin\Model\Role;
use Admin\Model\RoleAction;
use Admin\Model\Sites;
use Admin\Model\SiteGroup;
use Admin\Model\User;
use Admin\Model\DealCache ;

use Application\Service\Cache;
use Application\Util\Util;

use Zend\Form\Form;
use Zend\InputFilter\Factory;
use Zend\InputFilter\InputFilter;

use Zend\View\Model\ViewModel;
use Admin\Util\Post;


class AclController extends AbstractController {

	public function indexAction(){
		return $this->redirect()->toRoute('default', array(
						'controller'=> 'acl',
						'action'    => 'module-list',
				));
	}

	public function moduleListAction(){
		$param = $this->params()->fromQuery();

		$module = new Module();
		$paginator = $module->paginator($param);
		$paginator->setCurrentPageNumber((int)$param['page']);
		if(empty($param['perpage'])){
			$param['perpage'] = 20; 
		}
		$paginator->setItemCountPerPage ( $param['perpage'] );

		$viewData ['paginator'] = $paginator;
		$viewData = array_merge ( $viewData, $param);

		return new ViewModel ($viewData);

	}

	public function controllerListAction(){
		$param = $this->params()->fromQuery();

		$controller = new Controller();
		$paginator = $controller->paginator ( $param );
		$paginator->setCurrentPageNumber (( int )$param ['page']);
		if(empty($param['perpage'])){
			$param['perpage'] = 20; 
		}
		$paginator->setItemCountPerPage ($param['perpage']);
		

		$module = new Module();
		$modules = $module->getModulesPairs();

		$viewData['modules'] = $modules;
		$viewData ['paginator'] = $paginator;
		$viewData = array_merge ($viewData, $param);

		return new ViewModel ($viewData);

	}

	public function actionListAction(){
		$param = $this->params()->fromQuery();

		$clause = array();

		$controllerId = (int)$this->params()->fromQuery('controller_id', 0);
		if ($controllerId) {
			$clause['controller_id'] = $controllerId;
		}

		$actionName = (string)$this->params()->fromQuery('action_name', '');
		if ($actionName) {
			$clause['name'] = $actionName;
		}

		$action = new Action();
		$paginator = $action->paginator($clause);
		$paginator->setCurrentPageNumber ((int)$param['page']);
		if(empty($param['perpage'])){
			$param['perpage'] = 20; 
		}
		$paginator->setItemCountPerPage ( $param['perpage'] );
		

		$Controller = new Controller();
		$controllers = $Controller->getControllersPairs();
		natsort($controllers);
		$viewData['controllers'] = $controllers;
		$viewData ['paginator'] = $paginator;
		$viewData = array_merge ( $viewData, $param);

		return new ViewModel ( $viewData );

	}


	public function roleListAction(){
		$param = $this->params ()->fromQuery ();

		$role = new Role();
		$paginator = $role->paginator ();
		$paginator->setCurrentPageNumber ( ( int ) $param ['page'] );
		if(empty($param['perpage'])){
			$param['perpage'] = 20; 
		}
		$paginator->setItemCountPerPage ( $param['perpage'] );
		

		$viewData ['paginator'] = $paginator;
		$viewData = array_merge ( $viewData, $param);

		return new ViewModel ( $viewData );

	}

	public function siteGroupListAction(){
		$param = $this->params ()->fromQuery ();

		$siteGroup = new SiteGroup();
		$paginator = $siteGroup->paginator ( array('name'=>$param['name']) );
		$paginator->setCurrentPageNumber ( ( int ) $param ['page'] );
		if(empty($param['perpage'])){
			$param['perpage'] = 20; 
		}
		$paginator->setItemCountPerPage ( $param['perpage'] );
		

		$viewData ['paginator'] = $paginator;
		$viewData = array_merge ( $viewData, $param);

		return new ViewModel ( $viewData );

	}

	public function userListAction(){
		$param = $this->params ()->fromQuery ();

		$user = new User();
		$paginator = $user->paginator ();
		$paginator->setCurrentPageNumber ( ( int ) $param ['page'] );
		if(empty($param['perpage'])){
			$param['perpage'] = 20; 
		}
		$paginator->setItemCountPerPage ( $param['perpage'] );
		

		$viewData ['paginator'] = $paginator;
		$viewData = array_merge ( $viewData, $param);

		return new ViewModel ( $viewData );

	}

	public function moduleEditAction() {

		$module = new Module();
		$form = $module->getAclModuleForm($_POST);

		if ($this->request->isPost() && $form->isValid()) {
			$data = $form->getData();

			unset($data['submit']);
			unset($data['cancel']);

			$id = (int)$data['id'];
			if ($id) {
				$module->updateModule($id, $data);
			} else {
				$module->insertModule($data);
			}

			$this->_clearResources();
			return $this->redirect()->toRoute('default', array(
						'controller'=> 'acl',
						'action'    => 'module-list',
				));
		}


		$id = ( int ) $this->params()->fromRoute ( "id", 0 );

		// edit, then get old data
		if ($id) {
			$data = $module->getModuleById($id);
			if (!$data) {
				return $this->redirect()->toRoute('default', array(
						'controller'=> 'acl',
						'action'    => 'module-list',
				));
			}
			$form->setData( $data);
			$form->get('submit')->setValue('Edit Module');
		}
		$viewData = array ();
		$viewData['form'] = $form;
		return new ViewModel ( $viewData );

	}

	public function controllerEditAction() {

		$controller = new Controller();
		$form = $controller->getAclControllerForm($_POST);

		if ($this->request->isPost() && $form->isValid()) {
			$data = $form->getData();

			unset($data['submit']);
			unset($data['cancel']);

			$id = (int)$data['id'];
			if ($id) {
				$controller->updateController($id, $data);
			} else {
				$controller->insertController($data);
			}

			$this->_clearResources();

			return $this->redirect()->toRoute('default', array(
					'controller'=> 'acl',
					'action'    => 'controller-list',
			));
		}


		$id = ( int ) $this->params ()->fromRoute ( "id", 0 );

		// edit, then get old data
		if ($id) {
			$data = $controller->getControllerById($id);
			if (!$data) {
				return $this->redirect()->toRoute('default', array(
						'controller'=> 'acl',
						'action'    => 'controller-list',
				));
			}
			$form->setData( $data);
			$form->get('submit')->setValue('Edit Controller');
		}
		$viewData = array ();
		$viewData['form'] = $form;
		return new ViewModel ( $viewData );

	}

	public function actionEditAction() {

		$action = new Action();

		$form = $action->getAclActionForm($_POST);

		if ($this->request->isPost() && $form->isValid()) {
			$data = $form->getData();

			unset($data['submit']);
			unset($data['cancel']);

			$id = (int)$data['id'];
			if ($data['controller_id']) {
			    $controller = new Controller();
				$module = $controller->getControllerById($data['controller_id']);
				$data['module_id'] = $module->module_id;
			}
			if ($id) {
				$action->updateAction($id, $data);
			} else {
				$action->insertAction($data);
			}

			$this->_clearResources();

			return $this->redirect()->toRoute('default', array(
					'controller'=> 'acl',
					'action'    => 'action-list',
			));
		}


		$id = ( int ) $this->params ()->fromRoute ( "id", 0 );

		// edit, then get old data
		if ($id) {
			$data = $action->getActionById($id);
			if (!$data) {
				return $this->redirect()->toRoute('default', array(
						'controller'=> 'acl',
						'action'    => 'action-list',
				));
			}
			$form->setData( $data);
			$form->get('submit')->setValue('Edit Action');
		}
		$viewData = array ();
		$viewData['form'] = $form;
		return new ViewModel ( $viewData );

	}

	public function roleEditAction() {

		$role = new Role();
		$form = $role->getAclRoleForm($_POST);

		if ($this->request->isPost() && $form->isValid()) {
			$data = $form->getData();

			unset($data['submit']);
			unset($data['cancel']);


			$id = (int)$data['id'];
			if ($id) {
				$role->updateRole($id, $data);
			} else {
				$role->insertRole($data);
			}

			$this->_clearResources();

			return $this->redirect()->toRoute('default', array(
					'controller'=> 'acl',
					'action'    => 'role-list',
			));
		}


		$id = ( int ) $this->params ()->fromRoute ( "id", 0 );

		// edit, then get old data
		if ($id) {
			$data = $role->getRoleById($id);
			if (!$data) {
				return $this->redirect()->toRoute('default', array(
						'controller'=> 'acl',
						'action'    => 'role-list',
				));
			}
			$form->get('submit')->setValue('Edit Role');
			$form->setData( $data);
		}
		$viewData = array ();
		$viewData['form'] = $form;
		return new ViewModel ( $viewData );

	}

	public function siteGroupEditAction() {
		$siteGroup = new SiteGroup();
		$form = $siteGroup->getAclSiteGroupForm($_POST);

		if ($this->request->isPost() && $form->isValid()) {
			$data = $form->getData();

			unset($data['submit']);
			unset($data['cancel']);


			$id = (int)$data['id'];
			if ($id) {
				$siteGroup->updateSiteGroup($id, $data);
			} else {
				$siteGroup->insertSiteGroup($data);
			}

			$this->_clearResources();

			return $this->redirect()->toRoute('default', array(
					'controller'=> 'acl',
					'action'    => 'site-group-list',
			));
		}


		$id = ( int ) $this->params ()->fromRoute ( "id", 0 );

		// edit, then get old data
		if ($id) {
			$data = $siteGroup->getSiteGroupById($id);
			if (!$data) {
				return $this->redirect()->toRoute('default', array(
						'controller'=> 'acl',
						'action'    => 'site-group-list',
				));
			}
			$form->setData( $data);
			$form->get('submit')->setValue('Edit Site Group');
		}
		$viewData = array ();
		$viewData['form'] = $form;
		return new ViewModel ( $viewData );

	}

	public function userAddAction() {

		$user = new User();

		$form = $user->getAclUserForm($_POST);

		if ($this->request->isPost() && $form->isValid()) {
			$data = $form->getData();

			$id = (int)$data['id'];
			if ($id) {
				$user->updateUser($id, $data);
			} else {
				$id = $user->insertUser($data);
			}

			$this->_clearResources();

			return $this->redirect()->toRoute('default', array(
					'controller'=> 'acl',
					'action'    => 'user-manage',
					'id'		=> $id,
			));
		}


		$id = ( int ) $this->params ()->fromRoute ( "id", 0 );

		// edit, then get old data
		if ($id) {
			$data = $user->getUserById($id);
			if (!$data) {
				return $this->redirect()->toRoute('default', array(
						'controller'=> 'acl',
						'action'    => 'user-list',
				));
			}
			$form->setData( $data);
		}
		$viewData = array ();
		$viewData['form'] = $form;
		$viewData['error'] = $form->getMessages();
		return new ViewModel ( $viewData );

	}

	public function moduleDeleteAction() {
		$id = ( int ) $this->params ()->fromRoute ( "id", 0 );
		$module = new Module();
		$module->deleteModule($id);

		$refer = $_SERVER['HTTP_REFERER'];
		if ($refer) {
			return $this->redirect()->toUrl($refer);
		} else {
			return $this->redirect()->toRoute('default', array(
						'controller'=> 'acl',
						'action'    => 'module-list',
				));
		}
	}

	public function controllerDeleteAction() {
		$id = ( int ) $this->params ()->fromRoute ( "id", 0 );
		$controller = new Controller();
		$controller->deleteController($id);

		$refer = $_SERVER['HTTP_REFERER'];
		if ($refer) {
			return $this->redirect()->toUrl($refer);
		} else {
			return $this->redirect()->toRoute('default', array(
					'controller'=> 'acl',
					'action'    => 'controller-list',
			));
		}
	}

	public function actionDeleteAction() {
		$id = ( int ) $this->params ()->fromRoute ( "id", 0 );
		$action = new Action();
		$action->deleteAction($id);

		$refer = $_SERVER['HTTP_REFERER'];
		if ($refer) {
			return $this->redirect()->toUrl($refer);
		} else {
			return $this->redirect()->toRoute('default', array(
					'controller'=> 'acl',
					'action'    => 'action-list',
			));
		}
	}

	public function roleDeleteAction() {
		$id = ( int ) $this->params ()->fromRoute ( "id", 0 );
		$role = new Role();
		$role->deleteRole($id);

		$refer = $_SERVER['HTTP_REFERER'];
		if ($refer) {
			return $this->redirect()->toUrl($refer);
		} else {
			return $this->redirect()->toRoute('default', array(
					'controller'=> 'acl',
					'action'    => 'role-list',
			));
		}
	}


	public function siteGroupDeleteAction() {
		$id = ( int ) $this->params ()->fromRoute ( "id", 0 );
		$siteGroup = new SiteGroup();
		$siteGroup->deleteSiteGroup($id);

		$refer = $_SERVER['HTTP_REFERER'];
		if ($refer) {
			return $this->redirect()->toUrl($refer);
		} else {
			return $this->redirect()->toRoute('default', array(
					'controller'=> 'acl',
					'action'    => 'site-group-list',
			));
		}
	}

	public function roleManageAction() {

		$role = new Role();
		$form = $role->getAclRoleManageForm($_POST);

		if ($this->request->isPost() && $form->isValid()) {

			$data = $form->getData();

			unset($data['submit']);

// 			$selectedData = $data['selectedData'];
            $selectedData = Post::get('selectedData');
			$actions = explode(',', $selectedData);


			$roleAction = new RoleAction();

			$id = (int)$data['id'];

			$role->updateRole($id, array('name'=>$data['name'],'description'=>$data['description']));
			$roleAction->updateRoleByActions($id, $actions);

			$this->_clearResources();

			return $this->redirect()->toRoute('default', array(
					'controller'=> 'acl',
					'action'    => 'role-list',
			));
		}

		$id = ( int ) $this->params ()->fromRoute ( "id", 0 );
		if(!$id){
			$id = ( int ) $this->params ()->fromPost ( "id", 0 ); 
		}
		// edit, then get old data
		if ($id) {
			$data = $role->getRoleById($id);
			if (!$data) {
				return $this->redirect()->toRoute('default', array(
						'controller'=> 'acl',
						'action'    => 'role-list',
				));
			}
			$form->setData( $data);

			$roleAction = new RoleAction();
			$selectedRoles = $roleAction->getSelectedActions($id);
			$unselectedRoles = $roleAction->getUnselectedActions($id);

			$form->get('leftSelector')->setValueOptions($unselectedRoles);
			$form->get('selected')->setValueOptions($selectedRoles);

		}

		$viewData = array ();
		$viewData['form'] = $form;
		return new ViewModel ( $viewData );
	}


	public function siteGroupManageAction() {


		$siteGroup= new SiteGroup();
		$form = $siteGroup->getAclSiteGroupManageForm($_POST);
		
		$id = ( int ) $this->params ()->fromRoute ( "id", ( int ) $this->params ()->fromPost ( "id", 0 ) );
		
		if ($this->request->isPost() && $form->isValid()) {

			$data = $form->getData();

			unset($data['submit']);

			$selectedData = $data['selectedData'];
			$selected = explode(',', $selectedData);
			$selected = array_filter($selected);

			$id = (int)$data['id'];

			$siteGroup = new SiteGroup();

			$siteGroup->updateSiteGroup($id, array('name'=>$data['name'],'description'=>$data['description']));

			$siteGroup->updateRelationById($selected, $id);

			$this->_clearResources();

			return $this->redirect()->toRoute('default', array(
					'controller'=> 'acl',
					'action'    => 'site-group-list',
			));
		}


		// edit, then get old data
		if ($id) {

			$data = $siteGroup->getSiteGroupById($id);
			if (!$data) {
				return $this->redirect()->toRoute('default', array(
						'controller'=> 'acl',
						'action'    => 'site-group-list',
				));
			}
			$form->setData( $data);

			$site = new Sites();
			$sites = $site->getSitesPairs();

			$siteGroup = new SiteGroup();
			$selected = $siteGroup->getSelectedSitesBySiteGroupId($id);

			foreach($selected as $item => $svalue) {
				foreach($sites as $key => $value) {
					if($item == $key) {
						unset($sites[$key]);
					}
				}
			}

			$form->get('site')->setValueOptions($sites);
			$form->get('selected')->setValueOptions($selected);

		}

		$viewData = array();
		$viewData['form'] = $form;
		return new ViewModel($viewData);
	}

	public function userManageAction() {
		$user = new User();
		$viewData = array();

		$id = (int) $this->params()->fromRoute("id", (int)$this->params()->fromPost("id", 0));
		if(empty($id)){
			return $this->redirect()->toRoute('default', array(
					'controller'=> 'acl',
					'action'    => 'user-list',
			));
		}



		$aclUserForm = $user->getAclUserForm($_POST,$id);
		$aclUserForm->setAttribute('action', '/acl/user-manage')
					->setAttribute('name', 'form_general');
		$aclUserForm->get('submit')->setAttribute('value','Update User Profile');

		$aclUserRolesForm = $user->getAclUserRolesForm($id);
		$aclUserSiteGroupsForm = $user->getAclUserSiteGroupsForm($id);
		$aclUserSitesForm = $user->getAclUserSitesForm($id);

		if ($this->request->isPost()) {
			$redirect = true;
			if(key_exists('selectedRolesData', $_POST)){
				$selected = explode(',', $this->params ()->fromPost('selectedRolesData'));
				$selected = array_filter($selected);
				$user->updateSelectedRoles($selected, $id);
			}elseif(key_exists('selectedSitesData', $_POST)){
				$selected = explode(',', $this->params ()->fromPost('selectedSitesData'));
				$selected = array_filter($selected);
				$user->updateSelectedSites($selected, $id);
			}elseif(key_exists('selectedSiteGroupsData', $_POST)){
				$selected = explode(',', $this->params ()->fromPost('selectedSiteGroupsData'));
				$selected = array_filter($selected);
				$user->updateSelectedSiteGroups($selected, $id);
			}else{
				$redirect = false;
				if($aclUserForm->isValid()){
					$data = $aclUserForm->getData();
					if($data['update_password']=='1'){
						if(empty($data['newPassword']) || empty($data['newConfirmPassword']) || $data['newPassword']!=$data['newConfirmPassword']){
							$viewData['error'] = array('newConfirmPassword'=>array('notSame'=>'The two given tokens do not match'));
						}
					}
					if(empty($viewData['error'])){
						$id = (int)$data['id'];
						$user->updateUser($id, $data);
					}
				}else{
					$viewData['error'] = $aclUserForm->getMessages();
				}
			}
			if(empty($viewData['error'])){
				$this->_clearResources();
			}

			if($redirect){
				$url = '/acl/user-manage/id/' . $id . '?scope=' . $this->params ()->fromPost('scope');
				return $this->redirect()->toUrl($url);
			}

		}else{

			$scope = $this->params()->fromQuery('scope', 'general');
			if ($id) {
				$data = $user->getUserById($id);
				if (!$data) {
					return $this->redirect()->toRoute('default', array(
							'controller'=> 'acl',
							'action'    => 'user-list',
					));
				}
				$aclUserForm->setData( $data);
			}
		}

		$viewData['aclUserForm'] = $aclUserForm;
		$viewData['aclUserRolesForm'] = $aclUserRolesForm;
		$viewData['aclUserSiteGroupsForm'] = $aclUserSiteGroupsForm;
		$viewData['aclUserSitesForm'] = $aclUserSitesForm;
		$viewData['scope'] = $scope;
		return new ViewModel($viewData);
	}

	public function userDeleteAction() {
		$id = (int) $this->params()->fromRoute( "id", 0 );
	   	if($id == 1) {
	   		throw new \Exception("Can't delete the system default user!");
	   	}
	   	$user = new User();
	   	$user->deleteById($id);

	   	$url = "/acl/user-list";
       	return $this->redirect()->toUrl($url);
	}

	public function updateMyPasswordAction()
	{
		$user = new User();
		$data = $this->params()->fromPost();
		$user->updateMyPassword($data);
		$url = '/';
		return $this->redirect()->toUrl($url);
	}
	
	protected function _clearResources(){
		$cache = Cache::get('dynamicCache');
		$cacheKey = Util::makeCacheKey('ALL_ACL_RESOURCES', false);
		$cache->removeItem($cacheKey);
		$cache->removeItem('ACL_OBJ');
		$cache->setItem('RESOURCES_BATCHID', md5(time()));
		//更新缓存文件
		$dealCache = new DealCache() ;
		$dealCache->dealAclResources() ;
	}

}