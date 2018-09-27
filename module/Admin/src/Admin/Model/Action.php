<?php
namespace Admin\Model;

use Application\Model\DbTable;

use Zend\Db\Sql\Sql;

use Zend\Form\Form;
use Zend\InputFilter\Factory;
use Zend\InputFilter\InputFilter;

use Zend\Paginator\Adapter\DbSelect;
use Zend\Paginator\Paginator;

class Action extends DbTable
{
	protected $_name = 'acl_actions';
	protected $_primary = 'id';

	function __construct(){
		$this->setTableGateway("cmsdb", $this->_name);
		$this->_select = $this->tableGateway->getSql()->select();
	}

	public function deleteActionByControllerId($controller_id) {
		$where = $this->quoteInto('controller_id = ?', $controller_id);
		return $this->tableGateway->delete($where);
	}

	public function paginator($conditions = array()) {

		$dbAdapter = $this->tableGateway->getAdapter ();
		$sql = new Sql ( $dbAdapter );

		if ($conditions) {
			$this->_select->where($conditions);
		}

		$this->_select->order(array('module_id','controller_id','name'));

		$adapter = new DbSelect ( $this->_select, $sql );
		$paginator = new Paginator ( $adapter );

		return $paginator;

	}

	public function updateAction($id,$data){
		$where[] = $this->quoteInto('id = ?', $id);
		return $this->tableGateway->update($data, $where);
	}

	public function insertAction($data){
		return $this->insert($data);
	}

	public function getActionById($action_id){
		$where[] = $this->quoteInto("id = ?", $action_id);
		return $this->fetchRow($where);
	}

	public function deleteAction($id){
		$where[] = $this->quoteInto('id = ?', $id);
		$this->tableGateway->delete($where);
	}

	public function getAclActionForm($data = array()){
		$form = new Form ( );
		$form->setAttribute('action', '/acl/action-edit')
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

		$controller = new Controller();
		$controllers = $controller->getControllersPairs();
		natsort($controllers);

		$form->add(array(
				'name' => 'controller_id',
				'type' => 'Select',
				'options' => array(
						'label' => 'Controller',
				),
				'attributes' => array(
						'id'    => 'controller_id',
						'class' => 'form-control',
						'required'=>'required',
						'options' => $controllers,
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
						'value' => 'Add Action',
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
						'onclick' => 'window.location=\'/acl/action-list\';',
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
}