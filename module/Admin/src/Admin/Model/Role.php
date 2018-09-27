<?php
namespace Admin\Model;

use Application\Model\DbTable;

use Zend\Form\Form;
use Zend\InputFilter\Factory;
use Zend\InputFilter\InputFilter;

use Zend\Db\Sql\Sql;
use Zend\Paginator\Adapter\DbSelect;
use Zend\Paginator\Paginator;

class Role extends DbTable
{
	protected $_name = 'acl_roles';
	protected $_primary = 'id';
	
	function __construct(){
		$this->setTableGateway("cmsdb", $this->_name);
		$this->_select = $this->tableGateway->getSql()->select();
	}
	
	public function paginator($conditions = array()) {
	
		$dbAdapter = $this->tableGateway->getAdapter ();
		$sql = new Sql ( $dbAdapter );
	
		if ($conditions) {
			foreach ($conditions as $key => $val) {
				$this->_select->where($this->quoteInto("{$key} = ?", $val));
			}
		}
	
		$this->_select->order(array('id desc'));
	
		$adapter = new DbSelect ( $this->_select, $sql );
		$paginator = new Paginator ( $adapter );
	
		return $paginator;
	
	}
	
	public function updateRole($id,$data){
		$where[] = $this->quoteInto('id = ?', $id);
		return $this->tableGateway->update($data, $where);
	}
	
	public function insertRole($data){
		return $this->insert($data);
	}
	
	public function getRoleById($role_id){
		$where[] = $this->quoteInto("id = ?", $role_id);
		return $this->fetchRow($where);
	}
	public function deleteRole($id){
		$where[] = $this->quoteInto('id = ?', $id);
		$this->tableGateway->delete($where);
	}
	
	public function getAclRoleManageForm($data = array()){
		$form = new Form ( );
		$form->setAttribute('action', '/acl/role-manage')
		->setAttribute('class', 'form-horizontal')
		->setAttribute('method', 'post');
	
			
		$form->add(array(
				'name' => 'name',
				'type' => 'Text',
				'options' => array(
						'label' => 'Name',
				),
				'attributes' => array(
						'id'    => 'name',
						'class' => 'form-control',
						'required'=>'required',
				),
		));
	
	
		$form->add(array(
				'name' => 'leftSelector',
				'type' => 'Select',
				'attributes' => array(
						'id'    => 'leftSelector',
						'class' => 'form-control',
						'multiple' => 'true',
						'size' => "25",
						'style'=>'width: 100%',
						//'options' => $unselectedRoles,
				),
		));
	
		$form->add(array(
				'name' => 'selected',
				'type' => 'Select',
				'attributes' => array(
						'id'    => 'selected',
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
	
		$form->add(array(
				'name' => 'description',
				'type' => 'Textarea',
				'options' => array(
						'label' => 'Description',
				),
				'attributes' => array(
						'cols'  => 50,
						'rows'  => 4,
						'class' => 'form-control',
						'id'    => 'description',
				),
		));
	
		$form->add(array('name' => 'id', 'type' => 'Hidden'));
	
		$form->add(array('name' => 'selectedData', 'type' => 'Hidden'));
	
		$form->add(array(
				'name' => 'submit',
				'type' => 'Submit',
				'attributes' => array(
						'value' => 'Update Role',
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
						'onclick' => 'window.location=\'/acl/role-list\';',
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
				'name'     => 'name',
				'required' => true,
				'allowEmpty' => false,
				'filters'  => array(
						array('name' => 'StripTags'),
						array('name' => 'StringTrim'),
				),
		)));
	
		$inputFilter->add($factory->createInput(array(
				'name'     => 'description',
				'required' => false,
				'filters'  => array(
						array('name' => 'StripTags'),
	
				),
		)));
	
		$inputFilter->add($factory->createInput(array(
				'name'     => 'id',
				'filters'  => array(
						array('name' => 'int'),
				),
		)));
	
		$inputFilter->add($factory->createInput(array(
				'name'     => 'selected',
				'required' => false,
				'allowEmpty' => true,
		)));
	
		$inputFilter->add($factory->createInput(array(
				'name'     => 'leftSelector',
				'required' => false,
				'allowEmpty' => true,
		)));
	
		$form->setInputFilter($inputFilter);
	
		//set data
		if (is_array ( $data )) {
			$form->setData( $data );
		}
	
		return $form;
	}
	
	public function getAclRoleForm($data = array()){
		$form = new Form ( );
		$form->setAttribute('action', '/acl/role-edit')
		->setAttribute('class', 'form-horizontal')
		->setAttribute('method', 'post');
	
			
		$form->add(array(
				'name' => 'name',
				'type' => 'Text',
				'options' => array(
						'label' => 'Name',
				),
				'attributes' => array(
						'id'    => 'name',
						'class' => 'form-control',
						'required'=>'required',
				),
		));
	
		$form->add(array(
				'name' => 'description',
				'type' => 'Textarea',
				'options' => array(
						'label' => 'Description',
				),
				'attributes' => array(
						'cols'  => 50,
						'rows'  => 4,
						'class' => 'form-control',
						'id'    => 'description',
				),
		));
	
		$form->add(array('name' => 'id', 'type' => 'Hidden'));
	
		$form->add(array(
				'name' => 'submit',
				'type' => 'Submit',
				'attributes' => array(
						'value' => 'Add Role',
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
						'onclick' => 'window.location=\'/acl/role-list\';',
				),
		));
		
		$inputFilter = new InputFilter();
		$factory     = new Factory();
	
		$inputFilter->add($factory->createInput(array(
				'name'     => 'name',
				'required' => true,
				'allowEmpty' => false,
				'filters'  => array(
						array('name' => 'StripTags'),
						array('name' => 'StringTrim'),
				),
		)));
	
		$inputFilter->add($factory->createInput(array(
				'name'     => 'description',
				'required' => false,
				'filters'  => array(
						array('name' => 'StripTags'),
	
				),
		)));
	
		$inputFilter->add($factory->createInput(array(
				'name'     => 'id',
				'filters'  => array(
						array('name' => 'int'),
				),
		)));
	
		$form->setInputFilter($inputFilter);
	
		//set data
		if (is_array ( $data )) {
			$form->setData( $data );
		}
	
		return $form;
	}
	
	public function getRolesPairs(){
		$results = $this->fetchAll('select id,name from acl_roles order by id');
		$returnArray = array();
		foreach ($results as $result) {
			$returnArray[$result['id']] = $result['name'];
		}
		return $returnArray;
	}
}