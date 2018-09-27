<?php 
namespace Admin\Model;

use Application\Model\DbTable;
use Zend\Db\Sql\Sql;
use Zend\Db\Sql\Expression;

class Translation extends DbTable{
	
	protected $_name = 'translation';
	protected $_primary = 'id';
	
	public function __construct(){
		$this->setTableGateway("cmsdb", $this->_name);
		$this->_select = $this->tableGateway->getSql()->select();
	}
	
	/**
	 * Paginator
	 *
	 * @param Array $conditions
	 * @return \Zend\Paginator\Paginator
	 */
	public function paginator($conditions = array()) {
		
		$dbAdapter = $this->tableGateway->getAdapter();
		$sql = new Sql($dbAdapter);
		$select = $sql->select ();
		$select->from(array('c'=>$this->_name));
		
		
		if(trim($conditions['key'])){
			$select->where(str_ireplace('?', '', ($this->quoteInto('lang like ?', '%'.$conditions['key'].'%'))));
		}
		
		if(trim($conditions['language'])  && trim($conditions['text'])){
			$select->where(str_ireplace('?', '', ($this->quoteInto($conditions['language'].' like ?', '%'.trim($conditions['text'] ) . '%' ))));
		}
		
		$select->order("id desc");
	
		$adapter = new \Zend\Paginator\Adapter\DbSelect($select, $sql);
		$paginator = new \Zend\Paginator\Paginator($adapter);
	
		return $paginator;
	}
	
	/**
	 *  Get translation by id
	 * @param int $id
	 * @return array
	 */
	public function getTranslationById($id){
		
		$select = $this->tableGateway->getSql()->select();
		
		$select->where($this->quoteInto("id=?", $id));
		
		return (array)$this->fetchRow($select);
	}
	
	/**
	 *  Get translation by id
	 * @param int $id
	 * @return array
	 */
	public function getTranslationByLang($lang){
	
		$select = $this->tableGateway->getSql()->select();
	
		$select->where($this->quoteInto("lang=?", $lang));
	
		return (array)$this->fetchRow($select);
	}
	
	/**
	 * 
	 * @param string $lang
	 * @return array
	 */
	public function getLang($lang){
		
		$dbAdapter = $this->tableGateway->getAdapter();
		
		$sql = new Sql($dbAdapter);
		$select = $sql->select();
		
		$select->from($this->_name)
		->columns(array("lang",$lang));
		
		$statement = $sql->prepareStatementForSqlObject($select);
		$results = $statement->execute();
		
		$return = array();
		foreach ($results as $result) {
			$return[] = $result;
		}
		return $return;
	}
	
	/**
	 * 
	 * @param int $id
	 * @param array $data
	 * @return \Zend\Db\Adapter\Driver\ResultInterface
	 */
	public function updateTranslationById($id,$data){
		
		$dbAdapter = $this->tableGateway->getAdapter ();
		$sql = new Sql($dbAdapter);
		$update  = $sql->update();
		
		$update->table($this->_name)->set($data)->where($this->quoteInto("id=?", $id));
		
		$rs = $sql->prepareStatementForSqlObject($update)->execute();
		
		return $rs->getAffectedRows();
	}
	
	/**
	 * 
	 * @param int $id
	 * @return number
	 */
	public function deleteTranslationById($id){
		
		$dbAdapter = $this->tableGateway->getAdapter ();
		$sql = new Sql($dbAdapter);
		$delete  = $sql->delete();
		
		$delete->from($this->_name)->where($this->quoteInto("id=?", $id));
		
		$rs = $sql->prepareStatementForSqlObject($delete)->execute();
		
		return  $rs->getAffectedRows();
	}
	
}




?>