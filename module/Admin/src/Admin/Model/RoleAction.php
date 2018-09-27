<?php
namespace Admin\Model;

use Application\Model\DbTable;

use Zend\Db\Sql\Sql;
use Zend\Paginator\Adapter\DbSelect;
use Zend\Paginator\Paginator;

class RoleAction extends DbTable
{
	protected $_name = 'acl_role_action';
	protected $_primary = 'role_id';
	
	function __construct(){
		$this->setTableGateway("cmsdb", $this->_name);
		$this->_select = $this->tableGateway->getSql()->select();
	}
	
	public function getSelectedActions($id){
		$id = (int)$id;
		$sql = "
		SELECT DISTINCT
		A.id as id, concat(M.name,'_',C.name,'_',A.name) as name
		FROM
		acl_modules as M,
		acl_controllers as C,
		acl_actions as A,
		acl_roles as R
		WHERE
		R.id = $id
		and
		A.id IN (select action_id from acl_role_action where role_id = $id)
		and
		A.module_id = M.id
		and
		A.controller_id = C.id
		ORDER BY
		name
		";
		$rows = $this->fetchAll($sql);
		if($rows) {
			$returnArray = array();
			foreach ($rows as $row) {
				$returnArray[$row['id']] = $row['name'];
			}
			return $returnArray;
		}
		return array();
	}
	
	public function getUnselectedActions($id){
		$id = (int)$id;
		$sql = "
		SELECT DISTINCT
		A.id as id, concat(M.name,'_',C.name,'_',A.name) as name
		FROM
		acl_modules as M,
		acl_controllers as C,
		acl_actions as A,
		acl_roles as R
		WHERE
		R.id = $id
		and
		A.id NOT IN (select action_id from acl_role_action where role_id = $id)
		and
		A.module_id = M.id
		and
		A.controller_id = C.id
		ORDER BY
		name
		";
	
		$rows = $this->fetchAll($sql);
		if($rows) {
			$returnArray = array();
			foreach ($rows as $row) {
				$returnArray[$row['id']] = $row['name'];
			}
			return $returnArray;
		}
		return array();
	}
	
	public function updateRoleByActions($role_id, $actions)
	{
		// begin transaction
		$dbAdapter = $this->tableGateway->getAdapter();
		$dbAdapter->getDriver()->getConnection()->beginTransaction();

		try{
		$this->deleteAllRecord($role_id);
		foreach( $actions as $action )
		{
		$this->createRecord($role_id,$action);
		}
		$dbAdapter->getDriver()->getConnection()->commit();
		}catch( \Exception $e ){
		$dbAdapter->getDriver()->getConnection()->rollback();
		throw new \Exception("Fail to update role info: ". $e->getMessage() );
		}
			// end transaction
	}

	public function deleteAllRecord($role_id)
	{
		$where[] = $this->quoteInto('role_id = ?', $role_id);
		return $this->tableGateway->delete($where);
	}

	public function createRecord($role_id, $action_id)
	{
		$data = array('role_id'=>$role_id,'action_id'=>$action_id);
		$this->tableGateway->insert($data);
		$id = $this->tableGateway->lastInsertValue;
		return $id;
	}
}