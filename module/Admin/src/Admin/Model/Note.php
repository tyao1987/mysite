<?php
namespace Admin\Model;

use Admin\Model\Auth;

use Application\Model\DbTable;

use Zend\Authentication\Storage\Session as sessionStorage;

class Note extends ContentNode 
{
    
	protected $_type = 'note';
	protected $_namespace = "user";
	
	public function getUsersNotes($userId = null) {
		$identity = Auth::getIdentity();
		$userId = $identity['id'];
		$cmsCountry = new sessionStorage('cmscountry','cms_site_id');
		$siteId = $cmsCountry->read();
		
		if($userId > 0) {
			$where[] = $this->quoteInto("site_id = ?", $siteId);
			$where[] = $this->quoteInto("parent_id = ?", $this->_namespace . '_' . $userId);
			$where[] = $this->quoteInto("node=?", $this->_type);
			$row = $this->fetchRow($where);
			if($row) {
				return $row;
			}else{
				//the row does not exist.  create it
				$data = array(
						'content'       => 'You have no notes to view',
						'node'          => $this->_type,
						'parent_id'     => $this->_namespace . '_' . $userId,
						'site_id'     => $siteId
				);
				$this->insert($data);
				$where[] = $this->quoteInto("id = ?", $this->tableGateway->lastInsertValue);
				$result = $this->fetchRow($where);
				return $result;
			}
		}
	}
	
	public function saveUsersNotes($notes, $userId = null) {
		$identity = Auth::getIdentity();
		$userId = $identity['id'];
		$cmsCountry = new sessionStorage('cmscountry','cms_site_id');
		$siteId = $cmsCountry->read();
		if($userId > 0) {
			$where[] = $this->quoteInto("site_id = ?", $siteId);
			$where[] = $this->quoteInto("parent_id = ?", $this->_namespace . '_' . $userId);
			$where[] = $this->quoteInto("node=?", $this->_type);
			$row = $this->fetchRow($where);
			if($row) {
				$this->tableGateway->update(array('content'=>addslashes($notes)),$where);
			}else{
				//the row does not exist.  create it
				$data = array(
						'content'       => $notes,
						'node'          => $this->_type,
						'parent_id'     => $this->_namespace . '_' . $userId,
						'site_id'     => $siteId
				);
				$this->insert($data);
			}
		}
	}
    
    
}
