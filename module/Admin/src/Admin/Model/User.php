<?php
namespace Admin\Model;

use Admin\Model\Role;

use Application\Model\DbTable;

use Zend\Db\Sql\Expression;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Sql;

use Zend\Form\Form;
use Zend\InputFilter\Factory;
use Zend\InputFilter\InputFilter;

use Zend\Paginator\Adapter\DbSelect;
use Zend\Paginator\Paginator;

use Zend\Validator\Db\NoRecordExists;

class User extends DbTable
{
	// use id for role
	const SUPERUSER_ROLE = 1;

	protected $_name = 'users';
	protected $_primary = 'id';


	function __construct(){
		$this->setTableGateway("cmsdb", $this->_name);
		$this->_select = $this->tableGateway->getSql()->select();
	}
	function getUserByUsername($userName)
	{
	    $select = $this->tableGateway->getSql()->select();
	    $select->where($this->quoteInto("email = ?", $userName));
	    return $this->fetchRow($select);
	}
	/**
	 * get all acl resources
	 *
	 * @return array
	 */
	public function getAllAclResources() {
		$dbAdapter = $this->tableGateway->getAdapter();
		$sql = new Sql($dbAdapter);
		$select = $sql->select();
		$select->from(array("m" => "acl_modules"))
     		->columns(array("module_id" => "id", "module_name" => "name"))
			->join(array("c" => "acl_controllers"), "m.id=c.module_id", array("controller_id"=>"id", "controller_name"=>"name"),"left")
			->join(array("a" => "acl_actions"), "c.id=a.controller_id", array("action_id"=>"id", "action_name"=>"name"),"left");
		$statement = $sql->prepareStatementForSqlObject($select);
		$results = $statement->execute();
		$returnArray = array();
		foreach ($results as $result) {
			$returnArray[] = $result;
		}
		return $returnArray;
	}
	/**
	 * returns the complete user row for the currently logged in user
	 * @return zend_db_row
	 */
	function getCurrentUser()
	{
		$currentUser = Auth::getIdentity();

		if($currentUser) {
			$select = $this->tableGateway->getSql()->select();
			$select->where($this->quoteInto('id = ?', $currentUser['id']));
			$res = $this->fetchRow($select);
			return $res;
		}
	}

	public function getCurrentUsersAclResources()
	{
		$currentUser = $this->getCurrentUser();
		if($currentUser) {
			return $this->getAclResources($currentUser);
		}
	}

	public function getAclResources($userRowset) {
		$roles = $this->getUserActions($userRowset['id']);

		if (!$roles) {
			return null;
		}

		$resources = array();
		foreach ($roles as $role) {
			if ($role['controller_name'] == '*') {
				$key = $role['module_name'];
				$resources[$key] = 1;
			} else {
				if ($role['action_name'] == '*') {
					$key = $role['module_name'] . '_' . $role['controller_name'];
					$resources[$key] = 1;
				} else {
					$key = $role['module_name'] . '_' . $role['controller_name'] . '_' . $role['action_name'];
					$resources[$key] = 1;
				}
			}
		}

		return $resources;

	}

	/**
	 * get user actions
	 *
	 * @param int $userId
	 * @return array
	 */
	public function getUserActions($userId) {
		$roleAction = $this->getActionIdByRole($userId);
		$userAction = $this->getActionIdByUser($userId);
		$actionIds = array_merge($roleAction, $userAction);
		$actionIds = array_unique($actionIds);

		$actions = $this->getActionsById($actionIds);

		return $actions;
	}

	/**
	 * get action ids by role (acl_role_action)
	 *
	 * @param int $userId
	 * @return array
	 */
	public function getActionIdByRole($userId) {

		$dbAdapter = $this->tableGateway->getAdapter();
		$sql = new Sql($dbAdapter);
		$select = $sql->select();
		$select->from(array('ra' => 'acl_role_action'))
		->columns(array("action_id"))
		->join(array('ur'=>'acl_user_role'), new Expression($this->quoteInto('ur.user_id = ? and ra.role_id = ur.role_id ', (int)$userId)), array());
		$statement = $sql->prepareStatementForSqlObject($select);
		$results = $statement->execute();

		$return = array();
		foreach ($results as $result) {
			$return[] = $result['action_id'];
		}
		return $return;
	}

	/**
	 * get action ids by user (acl_user_action)
	 *
	 * @param int $userId
	 * @return array
	 */
	public function getActionIdByUser($userId) {

		$dbAdapter = $this->tableGateway->getAdapter();
		$sql = new Sql($dbAdapter);
		$select = $sql->select();
		$select->from(array('ua' => 'acl_user_action'))
		->columns(array("action_id"))
		->where($this->quoteInto('ua.user_id = ? ', (int)$userId));
		$statement = $sql->prepareStatementForSqlObject($select);
		$results = $statement->execute();

		$return = array();
		foreach ($results as $result) {
			$return[] = $result['action_id'];
		}
		return $return;

	}

	/**
	 * get actions info by id
	 *
	 * @param array $actions
	 * @return array
	 */
	public function getActionsById(array $actions) {

		if (count($actions) < 1) {
			return null;
		}

		$dbAdapter = $this->tableGateway->getAdapter();
		$sql = new Sql($dbAdapter);
		$select = $sql->select();

		$select->from(array('a' => 'acl_actions'));
		$select->columns(array(
						'action_id'=>'id',
						'controller_id',
						'module_id',
						'action_name'=>'name'
				));

		$where = array();
		foreach ($actions as $actionId) {
			$where[] = $this->quoteInto('a.id = ?', (int)$actionId);
		}

		if ($where) {
			$select->where(implode(' OR ', $where));
		}

		$select->join(array('m'=>'acl_modules'), 'a.module_id = m.id', array('module_name' => 'name'))
		->join(array('c'=>'acl_controllers'), 'a.controller_id = c.id', array('controller_name' => 'name'));

		$statement = $sql->prepareStatementForSqlObject($select);
		$results = $statement->execute();
		$returnArray = array();
		foreach ($results as $result) {
			$returnArray[] = $result;
		}
		return $returnArray;

	}

	/**
	 *  get selected  site  by uid
	 *
	 * @param int $id
	 */
	public function getSelectedSitesByUserId($id) {

		$dbAdapter = $this->tableGateway->getAdapter();
		$sql = new Sql($dbAdapter);
		$select = $sql->select();

		$select ->from(array('a'=>'acl_user_site'), '*')
		->join(array('u'=> 'sites'), 'u.site_id=a.site_id', array('*'),"left")
		->where($this->quoteInto('user_id = ? ', (int)$id));

		$statement = $sql->prepareStatementForSqlObject($select);
		$results = $statement->execute();

		$returnArray = array();

		foreach ($results as $result) {
			$returnArray[] = $result;
		}

		return $returnArray;
	}

	public function getSelectedSitesPairsByUserId($id) {

		$dbAdapter = $this->tableGateway->getAdapter();
		$sql = new Sql($dbAdapter);
		$select = $sql->select();

		$select ->from(array('a'=>'acl_user_site'), '*')
		->join(array('u'=> 'sites'), 'u.site_id=a.site_id', array('*'),"left")
		->where($this->quoteInto('user_id = ? ', (int)$id));

		$statement = $sql->prepareStatementForSqlObject($select);
		$results = $statement->execute();

		$returnArray = array();

		foreach ($results as $result) {
			$returnArray[$result['site_id']] = $result['hostname'];
		}

		return $returnArray;
	}

	/**
	 * get selectd site group by uid
	 *
	 * @param int $id
	 */
	public function getSelectedSiteGroupsByUserId($id) {

		$dbAdapter = $this->tableGateway->getAdapter();
		$sql = new Sql($dbAdapter);
		$select = $sql->select();

		$select ->from(array('a'=>'acl_user_sitegroup'), '*')
		->join(array('u'=> 'acl_sitegroup'), 'u.id=a.sitegroup_id', array('name'),"left")
		->where($this->quoteInto('user_id = ? ', (int)$id));

		$statement = $sql->prepareStatementForSqlObject($select);
		$results = $statement->execute();
		$returnArray = array();
		foreach ($results as $result) {
			$returnArray[] = $result;
		}
		return $returnArray;
	}

	public function getSelectedSiteGroupsPairsByUserId($id) {

		$dbAdapter = $this->tableGateway->getAdapter();
		$sql = new Sql($dbAdapter);
		$select = $sql->select();

		$select ->from(array('a'=>'acl_user_sitegroup'), '*')
		->join(array('u'=> 'acl_sitegroup'), 'u.id=a.sitegroup_id', array('name'),"left")
		->where($this->quoteInto('user_id = ? ', (int)$id));

		$statement = $sql->prepareStatementForSqlObject($select);
		$results = $statement->execute();
		$returnArray = array();
		foreach ($results as $result) {
			$returnArray[$result['sitegroup_id']] = $result['name'];
		}
		return $returnArray;
	}

	/**
	 * get user sites by group
	 *
	 * @param int $userId
	 * @return array
	 */
	public function getSelectedSitesBySiteGroup($userId) {

		$siteGroupList = $this->getSelectedSiteGroupsByUserId($userId);

		if (count($siteGroupList) < 1) {
			return array();
		}

		$dbAdapter = $this->tableGateway->getAdapter();
		$sql = new Sql($dbAdapter);
		$select = $sql->select();

		$select ->from(array('g'=>'acl_sitegroup_site'), '*')
		->join(array('s'=> 'sites'), 's.site_id=g.site_id', array('*'),'left');

		$where = array();
		foreach ($siteGroupList as $sitegroup) {
			$where[] = $this->quoteInto('g.sitegroup_id = ?', (int)$sitegroup['sitegroup_id']);
		}

		if ($where) {
			$select->where(implode(' OR ', $where));
			$select->where("s.isactive = 'YES'");
		}

		$statement = $sql->prepareStatementForSqlObject($select);
		$results = $statement->execute();
		$returnArray = array();
		foreach ($results as $result) {
			$returnArray[] = $result;
		}
		return $returnArray;
	}

	/**
	 * get user sites
	 *
	 * @param int $userId
	 * @return array
	 */
	public function getUserSites($userId) {
		$userSites = $this->getSelectedSitesByUserId($userId);
		$groupSites = $this->getSelectedSitesBySiteGroup($userId);
		$sites = array_merge($userSites, $groupSites);

		//remove duplicate sites
		$siteList = array();
		$len = count($sites);

		for($i=0; $i<$len; $i++) {
			if (in_array($sites[$i]['site_id'], $siteList)) {
				unset($sites[$i]);
				continue;
			}
			$siteList[] = $sites[$i]['site_id'];
		}
		return $sites;
	}

	/**
	 * get user info by identity
	 *
	 * @param string $identity
	 */
	public  function getUserByLdapIdentity($identity)
	{
		$select = $this->tableGateway->getSql()->select();
		$select->where($this->quoteInto(' `ldap_identity` = ? ', $identity));
		$result = $this->fetchRow($select);
		return $result;
	}


	public  function getUserById($userId)
	{
		$result = $this->fetchRow(array('id'=> $userId));
		return $result;
	}

	/**
	 * this function queries a users permissions
	 *
	 * the resource should be in the module_controller_action format
	 *
	 * if strict = true then this requires an exact match
	 * example: news_article != news_article_edit
	 *
	 * if strict = false then it will add wildcards
	 * example: news_article == news_article_edit
	 *
	 * if user is not set then the query will be run on the current user
	 *
	 * @param string $resource
	 * @param boolean $strict
	 * @param integer $user
	 * @return boolean
	 */
	public function queryPermissions($resource, $strict = false, $userId = null)
	{
		if($userId !== null) {
			$user = $this->getUserById($userId);
			if(!$user){
				return false;
			}
			$resources = $this->getAclResources($user);
		}else{
			$resources = $this->getCurrentUsersAclResources();
		}
		if(is_array($resources)) {
			if($strict) {
				if(array_key_exists($resource, $resources) && 1 == $resources[$resource]) {
					return true;
				}
			}else{
				$len = strlen($resource);
				foreach ($resources as $r => $v) {
					if(1 == $v && $resource == substr($r, 0, $len)) {
						return true;
					}
				}
			}
		}
		return false;
	}

	/**
	 * return boolean indicate whether current user id == 1.
	 * @return boolean
	 */
	function isCurrentRootUser()
	{
		$currentUser = Auth::getIdentity();
		if($currentUser) {
			$currentUserData =  $this->getUserById($currentUser['id']);
			return ($currentUserData['id'] == 1);
		}
	}

	public function getUsersPairs() {
		$select = $this->tableGateway->getSql()->select()
		->columns(array('id' => 'id', 'name' => new Expression('concat(first_name, " ", last_name)')))
		->order('name');
		$result = $this->tableGateway->selectWith($select);
	 	foreach ($result as $row) {
            $return[$row['id']] = $row['name'];
        }
		return $return;
	}

	public function paginator($conditions = array()) {

		$dbAdapter = $this->tableGateway->getAdapter ();
		$sql = new Sql ( $dbAdapter );
		if ($conditions) {
			foreach ($conditions as $key => $val) {
				$this->_select->where($this->quoteInto("{$key} like ?", '%' .$val. '%'));
			}
		}

		$this->_select->order(array('first_name'));

		$adapter = new DbSelect ( $this->_select, $sql );
		$paginator = new Paginator ( $adapter );

		return $paginator;

	}

	public function updateUser($id,$data){

		unset($data['submit']);
		unset($data['cancel']);
		unset($data['search_ldap']);
		unset($data['newPassword']);
		if($data['update_password']=='1'){
			$data['password'] = md5($data['newConfirmPassword']);
		}
		unset($data['update_password']);
		unset($data['newConfirmPassword']);

		$where[] = $this->quoteInto('id = ?', $id);
		return $this->tableGateway->update($data, $where);
	}

	public function insertUser($data){

		unset($data['submit']);
		unset($data['cancel']);
		unset($data['search_ldap']);
		unset($data['newPassword']);

		$data['password'] = md5($data['newConfirmPassword']);
		unset($data['newConfirmPassword']);

		$this->insert($data);
		return $this->tableGateway->lastInsertValue;
	}

	public function getDbAdapter(){
		return  $this->tableGateway->getAdapter();
	}

	public function getAclUserForm($data = array(),$userId=null){
		$form = new Form ( );
		$form->setAttribute('action', '/acl/user-add')
		->setAttribute('class', 'form-horizontal')
		->setAttribute('method', 'post');

		$form->add(array(
				'name' => 'first_name',
				'type' => 'Text',
				'options' => array(
						'label' => 'First Name',
				),
				'attributes' => array(
						'id'    => 'first_name',
						'class' => 'form-control',
						'required'=>'required',
				),
		));

		$form->add(array(
				'name' => 'last_name',
				'type' => 'Text',
				'options' => array(
						'label' => 'Last Name',
				),
				'attributes' => array(
						'id'    => 'last_name',
						'class' => 'form-control',
						'required'=>'required',
				),
		));

		$form->add(array(
				'name' => 'email',
				'type' => 'Email',
				'options' => array(
						'label' => 'Email Address',
				),
				'attributes' => array(
						'id'    => 'email',
						'class' => 'form-control',
						'required'=>'required',
				),
		));

		$form->add(array(
				'name' => 'ldap_identity',
				'type' => 'Text',
				'options' => array(
						'label' => 'Ldap identity',
				),
				'attributes' => array(
						'id'    => 'ldap_identity',
						'class' => 'form-control',
						'style' => 'display:inline;',
				),
		));

		$form->add(array(
				'name' => 'search_ldap',
				'type' => 'Button',
				'options' => array(
						'label' => 'Search Ldap',

				),
				'attributes' => array(
						'id' => 'search_ldap',
						'value' => 'Search Ldap',
						'class' => 'btn btn-primary btn-lg',
						'style'=>'width: 20%',
				),
		));

		$form->add(array(
				'name' => 'submit',
				'type' => 'Submit',
				'attributes' => array(
						'value' => 'Create User',
						'class' => 'btn btn-primary btn-lg',
						'style'=>'width: 20%',
				),
		));
		
		$form->add(array(
				'name' => 'cancel',
				'type' => 'Button',
				'options' => array(
						'label' => 'Cancel',
		
				),
				'attributes' => array(
						'value' => 'Cancel',
						'class' => 'btn btn-primary btn-lg',
						'style'=>'width: 20%',
						'onclick' => 'window.location=\'/acl/user-list\';',
				),
		));

		$form->add(array('name' => 'id', 'type' => 'Hidden'));

		$inputFilter = new InputFilter();
		$factory     = new Factory();

		$inputFilter->add($factory->createInput(array(
				'name'     => 'first_name',
				'required' => true,
				'allowEmpty' => false,
				'filters'  => array(
						array('name' => 'StripTags'),
						array('name' => 'StringTrim'),
				),
		)));

		$inputFilter->add($factory->createInput(array(
				'name'     => 'last_name',
				'required' => true,
				'allowEmpty' => false,
				'filters'  => array(
						array('name' => 'StripTags'),
						array('name' => 'StringTrim'),
				),
		)));

		$inputFilter->add($factory->createInput(array(
				'name'     => 'id',
				'filters'  => array(
						array('name' => 'int'),
				),
		)));

		$user = new User();
		if(!empty($userId)){

			$form->add(array(
					'name' => 'newPassword',
					'type' => 'Password',
					'options' => array(
							'label' => 'Password',
					),
					'attributes' => array(
							'id'    => 'newPassword',
							'class' => 'form-control',
					),
			));

			$form->add(array(
					'name' => 'newConfirmPassword',
					'type' => 'Password',
					'options' => array(
							'label' => 'onfirm Password',
					),
					'attributes' => array(
							'id'    => 'newConfirmPassword',
							'class' => 'form-control',
					),
			));

			$inputFilter->add($factory->createInput(array(
					'name'     => 'newPassword',
					'required' => false,
					'filters'  => array(
							array('name' => 'StringTrim'),
					),
			)));

			$inputFilter->add($factory->createInput(array(
					'name'     => 'newConfirmPassword',
					'required' => false,
					'filters'  => array(
							array('name' => 'StringTrim'),
					),
					'validators' => array(
							array(
									'name' => 'identical',
									'options' => array('token' => 'newPassword' )
							),
					),
			)));


			$form->add(array(
					'name' => 'update_password',
					'type' => 'Checkbox',
					'options' => array(
							'label' => 'Update password?',
					),
					'attributes' => array(
							'id'    => 'update_password',
							'value' => '1',
					),
			));

			$inputFilter->add($factory->createInput(array(
					'name'     => 'update_password',
					'required' => false,
					'allowEmpty' => true,
					'filters'  => array(
							array('name' => 'StringTrim'),
					),
			)));

			$norecord_exists = new NoRecordExists(
					array(
							'table'     => 'users',
							'field'     => 'email',
							'adapter'   => $user->getDbAdapter(),
							'exclude' => array(
									'field' => 'id',
									'value' => $userId
							)

					)
			);
			$norecord_exists->setMessage('Email already exists !', 'recordFound');


		}else{

			$form->add(array(
					'name' => 'newPassword',
					'type' => 'Password',
					'options' => array(
							'label' => 'Password',
					),
					'attributes' => array(
							'id'    => 'newPassword',
							'class' => 'form-control',
							'required'=>'required',
					),
			));

			$form->add(array(
					'name' => 'newConfirmPassword',
					'type' => 'Password',
					'options' => array(
							'label' => 'onfirm Password',
					),
					'attributes' => array(
							'id'    => 'newConfirmPassword',
							'class' => 'form-control',
							'required'=>'required',
					),
			));

			$inputFilter->add($factory->createInput(array(
					'name'     => 'newPassword',
					'required' => true,
					'filters'  => array(
							array('name' => 'StringTrim'),
					),
			)));

			$inputFilter->add($factory->createInput(array(
					'name'     => 'newConfirmPassword',
					'required' => true,
					'filters'  => array(
							array('name' => 'StringTrim'),
					),
					'validators' => array(
							array(
									'name' => 'identical',
									'options' => array('token' => 'newPassword' )
							),
					),
			)));


			$norecord_exists = new NoRecordExists(
					array(
							'table'     => 'users',
							'field'     => 'email',
							'adapter'   => $user->getDbAdapter(),
					)
			);
			$norecord_exists->setMessage('Email already exists !', 'recordFound');
		}

		$inputFilter->add($factory->createInput(array(
				'name'     => 'email',
				'required' => true,
				'allowEmpty' => false,
				'filters'  => array(
						array('name' => 'StripTags'),
						array('name' => 'StringTrim'),
				),
				'validators' => array(
						$norecord_exists ,
						array(
								'name' => 'email_address',
						),
				),
		)));

		$form->setInputFilter($inputFilter);

		//set data
		if (is_array ( $data )) {
			$form->setData( $data );
		}

		return $form;

	}

	public function getAclUserRolesForm($id){
		$form = new Form ( );
		$form->setAttribute('action', '/acl/user-manage')
		->setAttribute('class', 'form-horizontal')
		->setAttribute('name', 'form_roles')
		->setAttribute('method', 'post');



		$form->add(array(
				'name' => 'roles',
				'type' => 'Select',
				'attributes' => array(
						'id'    => 'roles',
						'class' => 'form-control',
						'multiple' => 'true',
						'size' => "25",
						'style'=>'width: 100%',
						//'options' => $unselectedRoles,
				),
		));

		$form->add(array(
				'name' => 'selectedRoles',
				'type' => 'Select',
				'attributes' => array(
						'id'    => 'selectedRoles',
						'class' => 'form-control',
						'multiple' => 'true',
						'size' => "25",
						'style'=>'width: 100%',
						//'options' => $selectedRoles,
				),
				'options' => array(
						'disable_inarray_validator' => true,
				),
		));


		$form->add(array('name' => 'id', 'type' => 'Hidden'));
		$form->add(array('name' => 'scope', 'type' => 'Hidden'));
		$form->add(array('name' => 'selectedRolesData', 'type' => 'Hidden'));

		$form->add(array(
				'name' => 'submit',
				'type' => 'Submit',
				'attributes' => array(
						'value' => 'Update User Roles',
						'class' => 'btn btn-primary btn-lg',
						'style'=>'width: 20%',
				),
		));


		$form->add(array(
				'name' => 'moveRight',
				'type' => 'Button',
				'options' => array(
						'label' => 'Move Right ->',

				),
				'attributes' => array(
						'value' => 'Move Right ->',
						'class' => 'btn btn-primary btn-lg btn-block',
						'style'=>'width: 100%',
				),
		));

		$form->add(array(
				'name' => 'moveLeft',
				'type' => 'Button',
				'options' => array(
						'label' => 'Move Left <-',

				),
				'attributes' => array(
						'value' => 'Move Left <-',
						'class' => 'btn btn-primary btn-lg btn-block',
						'style'=>'width: 100%',
				),
		));

		$inputFilter = new InputFilter();
		$factory     = new Factory();

		$inputFilter->add($factory->createInput(array(
				'name'     => 'id',
				'filters'  => array(
						array('name' => 'int'),
				),
		)));

		$inputFilter->add($factory->createInput(array(
				'name'     => 'roles',
				'required' => false,
				'allowEmpty' => true,
		)));

		$inputFilter->add($factory->createInput(array(
				'name'     => 'selectedRoles',
				'required' => false,
				'allowEmpty' => true,
		)));

		$form->setInputFilter($inputFilter);

		$role = new Role();
		$roles = $role->getRolesPairs();

		$selected = $this->getSelectedRolesPairsByUserId($id);

		foreach($selected as $item => $svalue) {
			foreach($roles as $key => $value) {
				if($item == $key) {
					unset($roles[$key]);
				}
			}
		}

		$form->get('id')->setValue($id);
		$form->get('scope')->setValue('roles');

		$form->get('roles')->setValueOptions($roles);
		$form->get('selectedRoles')->setValueOptions($selected);

		return $form;

	}

	public function getSelectedRolesByUserId($id){

		$dbAdapter = $this->tableGateway->getAdapter();
		$sql = new Sql($dbAdapter);
		$select = $sql->select();

		$select ->from(array('a'=>'acl_user_role'), '*')
		->join(array('u'=> 'acl_roles'), 'u.id=a.role_id', array('name'),"left")
		->where($this->quoteInto('user_id = ? ', (int)$id));

		$statement = $sql->prepareStatementForSqlObject($select);
		$results = $statement->execute();
		$returnArray = array();
		foreach ($results as $result) {
			$returnArray[] = $result;
		}
		return $returnArray;

	}

	public function getSelectedRolesPairsByUserId($id){
		$dbAdapter = $this->tableGateway->getAdapter();
		$sql = new Sql($dbAdapter);
		$select = $sql->select();

		$select ->from(array('a'=>'acl_user_role'), '*')
		->join(array('u'=> 'acl_roles'), 'u.id=a.role_id', array('name'),"left")
		->where($this->quoteInto('user_id = ? ', (int)$id));

		$statement = $sql->prepareStatementForSqlObject($select);
		$results = $statement->execute();
		$returnArray = array();
		foreach ($results as $result) {
			$returnArray[$result['role_id']] = $result['name'];
		}
		return $returnArray;
	}

	public function getAclUsersiteGroupsForm($id){
		$form = new Form ( );
		$form->setAttribute('action', '/acl/user-manage')
		->setAttribute('class', 'form-horizontal')
		->setAttribute('name', 'form_siteGroups')
		->setAttribute('method', 'post');



		$form->add(array(
				'name' => 'siteGroups',
				'type' => 'Select',
				'attributes' => array(
						'id'    => 'siteGroups',
						'class' => 'form-control',
						'multiple' => 'true',
						'size' => "25",
						'style'=>'width: 100%',
						//'options' => $unselectedRoles,
				),
		));

		$form->add(array(
				'name' => 'selectedSiteGroups',
				'type' => 'Select',
				'attributes' => array(
						'id'    => 'selectedSiteGroups',
						'class' => 'form-control',
						'multiple' => 'true',
						'size' => "25",
						'style'=>'width: 100%',
						//'options' => $selectedRoles,
				),
				'options' => array(
						'disable_inarray_validator' => true,
				),
		));


		$form->add(array('name' => 'id', 'type' => 'Hidden'));
		$form->add(array('name' => 'scope', 'type' => 'Hidden'));
		$form->add(array('name' => 'selectedSiteGroupsData', 'type' => 'Hidden'));

		$form->add(array(
				'name' => 'submit',
				'type' => 'Submit',
				'attributes' => array(
						'value' => 'Update User SiteGroups',
						'class' => 'btn btn-primary btn-lg',
						'style'=>'width: 20%',
				),
		));


		$form->add(array(
				'name' => 'moveRight',
				'type' => 'Button',
				'options' => array(
						'label' => 'Move Right ->',

				),
				'attributes' => array(
						'value' => 'Move Right ->',
						'class' => 'btn btn-primary btn-lg btn-block',
						'style'=>'width: 100%',
				),
		));

		$form->add(array(
				'name' => 'moveLeft',
				'type' => 'Button',
				'options' => array(
						'label' => 'Move Left <-',

				),
				'attributes' => array(
						'value' => 'Move Left <-',
						'class' => 'btn btn-primary btn-lg btn-block',
						'style'=>'width: 100%',
				),
		));

		$inputFilter = new InputFilter();
		$factory     = new Factory();

		$inputFilter->add($factory->createInput(array(
				'name'     => 'id',
				'filters'  => array(
						array('name' => 'int'),
				),
		)));

		$inputFilter->add($factory->createInput(array(
				'name'     => 'siteGroups',
				'required' => false,
				'allowEmpty' => true,
		)));

		$inputFilter->add($factory->createInput(array(
				'name'     => 'selectedSiteGroups',
				'required' => false,
				'allowEmpty' => true,
		)));

		$form->setInputFilter($inputFilter);

		$siteGroup = new SiteGroup();
		$siteGroups = $siteGroup->getSiteGroupsPairs();

		$selected = $this->getSelectedSiteGroupsPairsByUserId($id);

		foreach($selected as $item => $svalue) {
			foreach($siteGroups as $key => $value) {
				if($item == $key) {
					unset($siteGroups[$key]);
				}
			}
		}

		$form->get('id')->setValue($id);
		$form->get('scope')->setValue('siteGroups');
		$form->get('siteGroups')->setValueOptions($siteGroups);
		$form->get('selectedSiteGroups')->setValueOptions($selected);

		return $form;

	}

	public function getAclUsersitesForm($id){
		$form = new Form ( );
		$form->setAttribute('action', '/acl/user-manage')
		->setAttribute('class', 'form-horizontal')
		->setAttribute('name', 'form_sites')
		->setAttribute('method', 'post');



		$form->add(array(
				'name' => 'sites',
				'type' => 'Select',
				'attributes' => array(
						'id'    => 'sites',
						'class' => 'form-control',
						'multiple' => 'true',
						'size' => "25",
						'style'=>'width: 100%',
						//'options' => $unselectedRoles,
				),
		));

		$form->add(array(
				'name' => 'selectedSites',
				'type' => 'Select',
				'attributes' => array(
						'id'    => 'selectedSites',
						'class' => 'form-control',
						'multiple' => 'true',
						'size' => "25",
						'style'=>'width: 100%',
						//'options' => $selectedRoles,
				),
				'options' => array(
						'disable_inarray_validator' => true,
				),
		));


		$form->add(array('name' => 'id', 'type' => 'Hidden'));
		$form->add(array('name' => 'scope', 'type' => 'Hidden'));
		$form->add(array('name' => 'selectedSitesData', 'type' => 'Hidden'));

		$form->add(array(
				'name' => 'submit',
				'type' => 'Submit',
				'attributes' => array(
						'value' => 'Update User Sites',
						'class' => 'btn btn-primary btn-lg',
						'style'=>'width: 20%',
				),
		));


		$form->add(array(
				'name' => 'moveRight',
				'type' => 'Button',
				'options' => array(
						'label' => 'Move Right ->',

				),
				'attributes' => array(
						'value' => 'Move Right ->',
						'class' => 'btn btn-primary btn-lg btn-block',
						'style'=>'width: 100%',
				),
		));

		$form->add(array(
				'name' => 'moveLeft',
				'type' => 'Button',
				'options' => array(
						'label' => 'Move Left <-',

				),
				'attributes' => array(
						'value' => 'Move Left <-',
						'class' => 'btn btn-primary btn-lg btn-block',
						'style'=>'width: 100%',
				),
		));

		$inputFilter = new InputFilter();
		$factory     = new Factory();

		$inputFilter->add($factory->createInput(array(
				'name'     => 'id',
				'filters'  => array(
						array('name' => 'int'),
				),
		)));

		$inputFilter->add($factory->createInput(array(
				'name'     => 'sites',
				'required' => false,
				'allowEmpty' => true,
		)));

		$inputFilter->add($factory->createInput(array(
				'name'     => 'selectedSites',
				'required' => false,
				'allowEmpty' => true,
		)));

		$form->setInputFilter($inputFilter);

		$site = new Sites();
		$sites = $site->getSitesPairs();

		$selected = $this->getSelectedSitesPairsByUserId($id);

		foreach($selected as $item => $svalue) {
			foreach($sites as $key => $value) {
				if($item == $key) {
					unset($sites[$key]);
				}
			}
		}

		$form->get('id')->setValue($id);
		$form->get('scope')->setValue('sites');
		$form->get('sites')->setValueOptions($sites);
		$form->get('selectedSites')->setValueOptions($selected);

		return $form;

	}

	public function updateSelectedRoles($data, $id) {
		// begin transaction
		$dbAdapter = $this->tableGateway->getAdapter();
		$dbAdapter->getDriver()->getConnection()->beginTransaction();
		try {

			$sql = new Sql ( $dbAdapter );

			$where = array();
			$where[] = $this->quoteInto('user_id=?', $id);

			$sql->prepareStatementForSqlObject($sql->delete('acl_user_role')->where($where))->execute();

			foreach($data as $value) {
				$ar = array(
						'user_id' => $id
						,'role_id' => $value
				);
				$sql->prepareStatementForSqlObject($sql->insert('acl_user_role')->values($ar))->execute();
			}
			$dbAdapter->getDriver()->getConnection()->commit();
		}catch (\Exception $e) {
			$dbAdapter->getDriver()->getConnection()->rollback();
			throw new \Exception($e->getMessage());
		}
	}

	public function updateSelectedSites($data, $id) {
		// begin transaction
		$dbAdapter = $this->tableGateway->getAdapter();
		$dbAdapter->getDriver()->getConnection()->beginTransaction();
		try {
			$sql = new Sql ( $dbAdapter );

			$where = array();
			$where[] = $this->quoteInto('user_id=?', $id);
			$sql->prepareStatementForSqlObject($sql->delete('acl_user_site')->where($where))->execute();
			foreach($data as $value) {
				$ar = array(
						'user_id' => $id
						,'site_id' => $value
				);
				$sql->prepareStatementForSqlObject($sql->insert('acl_user_site')->values($ar))->execute();
			}
			$dbAdapter->getDriver()->getConnection()->commit();
		}catch (\Exception $e) {
			$dbAdapter->getDriver()->getConnection()->rollback();
			throw new \Exception($e->getMessage());
		}
	}

	public function updateSelectedSiteGroups($data, $id) {
		// begin transaction
		$dbAdapter = $this->tableGateway->getAdapter();
		$dbAdapter->getDriver()->getConnection()->beginTransaction();

		try {
			$sql = new Sql ( $dbAdapter );

			$where = array();
			$where[] = $this->quoteInto('user_id=?', $id);
			$sql->prepareStatementForSqlObject($sql->delete('acl_user_sitegroup')->where($where))->execute();
			foreach($data as $value) {
				$ar = array(
						'user_id' => $id
						,'sitegroup_id' => $value
				);
				$sql->prepareStatementForSqlObject($sql->insert('acl_user_sitegroup')->values($ar))->execute();
			}
			$dbAdapter->getDriver()->getConnection()->commit();
		}catch (\Exception $e) {
			$dbAdapter->getDriver()->getConnection()->rollback();
			throw new \Exception($e->getMessage());
		}
	}

	public function deleteById($id){
		// begin transaction
		$dbAdapter = $this->tableGateway->getAdapter();
		$dbAdapter->getDriver()->getConnection()->beginTransaction();

		try {
			$sql = new Sql ( $dbAdapter );
			$ret = $this->tableGateway->delete($this->quoteInto('id=?', $id));
			$where = array();
			$where[] = $this->quoteInto('user_id=?', $id);
			$sql->prepareStatementForSqlObject($sql->delete('acl_user_role')->where($where))->execute();
			$sql->prepareStatementForSqlObject($sql->delete('acl_user_site')->where($where))->execute();
			$sql->prepareStatementForSqlObject($sql->delete('acl_user_sitegroup')->where($where))->execute();

			$dbAdapter->getDriver()->getConnection()->commit();
		}catch (\Exception $e) {
			$dbAdapter->getDriver()->getConnection()->rollback();
			throw new \Exception($e->getMessage());
		}
		return $ret;
	}

	public function updateMyPassword($postData) {
		$error = false;
		$newPwd = trim(strip_tags($postData['password']));
		$confirm = trim(strip_tags($postData['confirmation']));
		if($newPwd == '') {
            $error = true;
        }
		if($newPwd == $confirm) {
			$data['password'] = md5($newPwd);

		}else{
			$error = true;
		}

		$userModle = new User();
		$user = $userModle->getCurrentUser();
        $id = $user['id'];

        if(!$error){
            $this->tableGateway->update($data, "id=" . $id);
        }
        return $userModle->getCurrentUser();
	}

}