<?php 

namespace Admin\Model;

use Application\Model\DbTable;

use Zend\Db\Sql\Sql;
use Zend\Paginator\Adapter\DbSelect;
use Zend\Paginator\Paginator;

class AdminLog extends DbTable
{
	protected $_name = 'admin_log';
	protected $_primary = 'id';

	function __construct(){
		$this->setTableGateway("cmsdb", $this->_name);
	}
	
	/**
	* Paginator
	*
	* @param Array $conditions
	* @return \Zend\Paginator\Paginator
	*/
	public function paginator($conditions = array()) {
		$dbAdapter = $this->tableGateway->getAdapter ();
		$sql = new Sql ( $dbAdapter );
		$select = $sql->select ();
		
		$select->from ( array (
				'l' => $this->_name
		))->columns( array (
				'id',
				'user_name',
				'url',
				'params',
				'ip',
				'date',
				'site_id'
		) )->order("id desc");
		
		if (trim ( $conditions ['user_id'] )) {
			$select->where ( $this->quoteInto ( 'l.user_id = ?', $conditions ['user_id'] ) );
		}
		
		if (trim ( $conditions ['site'] )) {
			$select->where ( $this->quoteInto ( 'l.site_id = ?', $conditions ['site'] ) );
		}
		
		if (trim ( $conditions ['url'] )) {
			$select->where->like('l.url', "%" . trim($conditions ['url']) . "%");
		}
		$adapter = new DbSelect ( $select, $sql );
		$paginator = new Paginator ( $adapter );
		
		return $paginator;
	}
	
	public function getLogs($offset=0, $limit=20, $clause = array()) {
		
		$dbAdapter = $this->tableGateway->getAdapter();
		$sql = new Sql($dbAdapter);
		$select = $sql->select();
		
		$where = array();
		if ($clause) {
			foreach ($clause as $c) {
				$select->where($c);
			}
		}
		
		$select->order("date DESC")->limit($limit)->offset($offset);

		$statement = $sql->prepareStatementForSqlObject($select);
		$results = $statement->execute();
		$returnArray = array();
		foreach ($results as $result) {
			$returnArray[] = $result;
		}
		return $returnArray;
	}

	public function getLogCount($clause = array()) {

		$select = $this->tableGateway->getSql()->select();
		$select->from($this->_name, 'count(*) as totalNum');
		if ($clause) {
			foreach ($clause as $c) {
				$select->where($c) ;
			}
		}
		$row = $this->fetchRow($select);
		return $row->totalNum;
	}
}

?>