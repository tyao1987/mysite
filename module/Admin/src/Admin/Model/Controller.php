<?php
namespace Admin\Model;

use Admin\Model\Action;

use Application\Model\DbTable;

use Zend\Form\Form;
use Zend\InputFilter\Factory;
use Zend\InputFilter\InputFilter;

use Zend\Db\Sql\Expression;
use Zend\Db\Sql\Sql;
use Zend\Paginator\Adapter\DbSelect;
use Zend\Paginator\Paginator;

class Controller extends DbTable
{
	protected $_name = 'acl_controllers';
	protected $_primary = 'id';

	function __construct(){
		$this->setTableGateway("cmsdb", $this->_name);
		$this->_select = $this->tableGateway->getSql()->select();
	}

	public function paginator($conditions = array()) {

		$dbAdapter = $this->tableGateway->getAdapter ();
		$sql = new Sql ( $dbAdapter );

		$this->_select->order(array('module_id', 'name'));

		$adapter = new DbSelect ( $this->_select, $sql );
		$paginator = new Paginator ( $adapter );

		return $paginator;

	}

	public function deleteControllerByModuleId($module_id) {
		$controllers = $this->getControllers(array('module_id' => $module_id));
		foreach ($controllers as $controller) {
			$this->deleteControllerHandler($controller->id);
		}
	}

	public function getControllers($clause = array()) {
		$where = array();
		if ($clause) {
			foreach ($clause as $key => $val) {
				$where[] = $this->quoteInto("{$key} = ?", $val);
			}
		}
		$order = array('module_id', 'name');
		$this->_select->order($order);
		$this->_select->where($where);
		return $this->tableGateway->selectWith($this->_select);
	}

	public function deleteControllerHandler($id) {
		// delete actions
		$action = new Action();
		$action->deleteActionByControllerId($id);

		$where[] = $this->quoteInto('id = ?', $id);
		return $this->tableGateway->delete($where);
	}

	public function getControllerById($controller_id){
		$where[] = $this->quoteInto("id = ?", $controller_id);
		return $this->fetchRow($where);
	}

	public function updateController($id,$data){
		$where[] = $this->quoteInto('id = ?', $id);
		return $this->tableGateway->update($data, $where);
	}

	public function insertController($data){
		return $this->insert($data);
	}

	public function deleteController($id){

		// delete actions
		$action = new Action();
		$action->deleteActionByControllerId($id);

		$where[] = $this->quoteInto('id = ?', $id);
		$this->tableGateway->delete($where);
	}

	public function getControllersPairs(){

		$dbAdapter = $this->tableGateway->getAdapter();
		$sql = new Sql($dbAdapter);
		$select = $sql->select();
		$select->from(array("c" => $this->_name))
		->columns(array("id", "name" => new Expression('CONCAT(`m`.`name`, "_", `c`.`name`)')))
		->join(array("m" => "acl_modules"), "c.module_id=m.id", array(),"left");

		$statement = $sql->prepareStatementForSqlObject($select);
		$results = $statement->execute();
		$returnArray = array();
		foreach ($results as $result) {
			$returnArray[$result['id']] = $result['name'];
		}
		return $returnArray;
	}

	public function getAclControllerForm($data = array()){
		$form = new Form ( );
		$form->setAttribute('action', '/acl/controller-edit')
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

		$module = new Module();
		$modules = $module->getModulesPairs();
		natsort($modules);

		$form->add(array(
				'name' => 'module_id',
				'type' => 'Select',
				'options' => array(
						'label' => 'Module',
				),
				'attributes' => array(
						'id'    => 'module_id',
						'class' => 'form-control',
						'required'=>'required',
						'options' => $modules,
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
						'value' => 'Add Controller',
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
						'onclick' => 'window.location=\'/acl/controller-list\';',
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