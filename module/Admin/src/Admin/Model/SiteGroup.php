<?php
namespace Admin\Model;

use Application\Model\DbTable;

use Zend\Form\Form;
use Zend\InputFilter\Factory;
use Zend\InputFilter\InputFilter;

use Zend\Db\Sql\Sql;
use Zend\Db\Sql\Expression;
use Zend\Paginator\Adapter\DbSelect;
use Zend\Paginator\Paginator;

class SiteGroup extends DbTable
{
	protected $_name = 'acl_sitegroup';
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
				$this->_select->where->like($key, "%" . trim($val) . "%");
			}
		}
	
		$this->_select->order(array('name'));
	
		$adapter = new DbSelect ( $this->_select, $sql );
		$paginator = new Paginator ( $adapter );
	
		return $paginator;
	
	}
	
	public function updateSiteGroup($id,$data){
		$where[] = $this->quoteInto('id = ?', $id);
		return $this->tableGateway->update($data, $where);
	}
	
	public function insertSiteGroup($data){
		return $this->insert($data);
	}
	
	public function getSiteGroupById($id){
		$where[] = $this->quoteInto("id = ?", $id);
		return $this->fetchRow($where);
	}
	
	public function deleteSiteGroup($id){
		
		// begin transaction
		$dbAdapter = $this->tableGateway->getAdapter();
		$dbAdapter->getDriver()->getConnection()->beginTransaction();
		
		try{
			$this->removeRelationById($id);
			$where[] = $this->quoteInto('id = ?', $id);
			$this->tableGateway->delete($where);
			
			$dbAdapter->getDriver()->getConnection()->commit();
		}catch( \Exception $e ){
			$dbAdapter->getDriver()->getConnection()->rollback();
			throw new \Exception("Db error for SiteGroup->deleteById:". $e->getMessage() );
		}
		
	}
	
	public function removeRelationById($id) {
		$where = array();
		$where[] = $this->quoteInto('sitegroup_id=?', $id);
		
		$dbAdapter = $this->tableGateway->getAdapter ();
		$sql = new Sql ( $dbAdapter );
		$statement = $sql->prepareStatementForSqlObject($sql->delete('acl_sitegroup_site')->where($where));
		$results = $statement->execute();
		
	}
	
	public function insertRelationById($data) {
			
		$dbAdapter = $this->tableGateway->getAdapter ();
		$sql = new Sql ( $dbAdapter );
		$statement = $sql->prepareStatementForSqlObject($sql->insert('acl_sitegroup_site')->values($data));
		$results = $statement->execute();
	}
	
	public function getSelectedSitesBySiteGroupId($id){
	
		$dbAdapter = $this->tableGateway->getAdapter();
		$sql = new Sql($dbAdapter);
		$select = $sql->select();
		$select->from(array('a'=>'acl_sitegroup_site'))
		->join(array("s" => "sites"), "s.site_id=a.site_id", array('hostname'),"left")
		->where($this->quoteInto('sitegroup_id=?', $id));
		$statement = $sql->prepareStatementForSqlObject($select);
		$results = $statement->execute();
		
		$returnArray = array();
		foreach ($results as $result) {
			$returnArray[$result['site_id']] = $result['hostname'];
		}
		return $returnArray;
		
	}
	
	public function updateRelationById($data, $id) {
		// begin transaction
		$dbAdapter = $this->tableGateway->getAdapter();
		$dbAdapter->getDriver()->getConnection()->beginTransaction();
		
		try {
			$this->removeRelationById($id);
			foreach($data as $value) {
				
				$ar = array(
						'sitegroup_id' => $id
						,'site_id' => $value
				);
				$this->insertRelationById($ar);
			}
			$dbAdapter->getDriver()->getConnection()->commit();
		}catch (\Exception $e) {
			$dbAdapter->getDriver()->getConnection()->rollback();
			throw new \Exception($e->getMessage());
		}
	}
	
	public function getAclSiteGroupManageForm($data = array()){
		$form = new Form ( );
		$form->setAttribute('action', '/acl/site-group-manage')
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
				'name' => 'site',
				'type' => 'Select',
				'attributes' => array(
						'id'    => 'site',
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
						'value' => 'Update Site Group',
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
						'onclick' => 'window.location=\'/acl/site-group-list\';',
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
				'name'     => 'site',
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
	
	public function getAclSiteGroupForm($data = array()){
		$form = new Form ( );
		$form->setAttribute('action', '/acl/site-group-edit')
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
						'value' => 'Add Site Group',
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
						'onclick' => 'window.location=\'/acl/site-group-list\';',
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
	
	public function getSiteGroupsPairs(){
		$results = $this->fetchAll('select id,name from acl_sitegroup order by id');
		$returnArray = array();
		foreach ($results as $result) {
			$returnArray[$result['id']] = $result['name'];
		}
		return $returnArray;
	}
}