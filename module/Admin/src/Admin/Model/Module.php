<?php
namespace Admin\Model;

use Admin\Model\Controller;

use Application\Model\DbTable;

use Zend\Form\Form;
use Zend\InputFilter\Factory;
use Zend\InputFilter\InputFilter;

use Zend\Db\Sql\Sql;
use Zend\Paginator\Adapter\DbSelect;
use Zend\Paginator\Paginator;


class Module extends DbTable
{
	protected $_name = 'acl_modules';
	protected $_primary = 'id';

	function __construct(){
		$this->setTableGateway("cmsdb", $this->_name);
		$this->_select = $this->tableGateway->getSql()->select();
	}

	public function paginator($conditions = array()) {

		$dbAdapter = $this->tableGateway->getAdapter();
		$sql = new Sql ($dbAdapter);

		$this->_select->order("name");

		$adapter = new DbSelect ($this->_select, $sql);
		$paginator = new Paginator ($adapter);

		return $paginator;

	}

	public function insertModule($data) {
		return $this->insert($data);
	}

	public function updateModule($id, $data) {
		$where[] = $this->quoteInto('id = ?', $id);
		return $this->tableGateway->update($data, $where);
	}

	public function getModuleById($module_id) {
		$where[] = $this->quoteInto("id = ?", $module_id);
		return $this->fetchRow($where);
	}

	public function deleteModule($id) {
		$dbAdapter = $this->tableGateway->getAdapter();
		$dbAdapter->getDriver()->getConnection()->beginTransaction();

		try {
			// delete controllers
			$controller = new Controller();
			$controller->deleteControllerByModuleId($id);

			$where[] = $this->quoteInto('id = ?', $id);
			$this->tableGateway->delete($where);

			$dbAdapter->getDriver()->getConnection()->commit();

		} catch (\Exception $e) {
			$dbAdapter->getDriver()->getConnection()->rollback();
			throw new \Exception($e->getMessage());
		}
	}

	public function getModulesPairs() {

		$select = $this->_select;
		$select->columns(array (
				'id',
				'name'
		));

		$result = $this->tableGateway->selectWith($select);
		foreach ($result as $row) {
			$return[$row['id']] = $row['name'];
		}
		return $return;

	}

	public function getAclModuleForm($data = array()) {
		$form = new Form ( );
		$form->setAttribute('action', '/acl/module-edit')
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
						'value' => 'Add Module',
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
						'onclick' => 'window.location=\'/acl/module-list\';',
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