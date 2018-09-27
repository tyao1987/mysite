<?php
namespace Admin\Model;

use Admin\Model\Auth;

use Application\Model\DbTable;

use Zend\Authentication\Storage\Session as sessionStorage;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Sql;

class Bookmark extends ContentNode 
{
    
	protected $_type = 'bookmark';
	
	public function getUsersBookmarks($userId = null) {
		$identity = Auth::getIdentity();
		$userId = $identity['id'];
		$cmsCountry = new sessionStorage('cmscountry','cms_site_id');
		$siteId = $cmsCountry->read();
	
		if($userId > 0) {
			
			$where[] = $this->quoteInto("site_id = ?", $siteId);
			$where[] = $this->quoteInto("parent_id = ?", $userId);
			$where[] = $this->quoteInto("content_type=?", $this->_type);
			
			$select = $this->tableGateway->getSql()->select();
			$select->where($where);
			$select->order("node DESC");
			$results = $this->tableGateway->selectWith($select);		
			$returnArray = array();
			foreach ($results as $result) {
				$returnArray[] = $result;
			}
			return $returnArray;
		}
	}
	
	public function addUsersBookmark($label, $url, $userId = null) {
		$identity = Auth::getIdentity();
		$userId = $identity['id'];
		$cmsCountry = new sessionStorage('cmscountry','cms_site_id');
		$siteId = $cmsCountry->read();
	
		if($userId > 0) {
			$where[] = $this->quoteInto("site_id = ?", $siteId);
			$where[] = $this->quoteInto("parent_id = ?", $userId);
			$where[] = $this->quoteInto("node=?", $label);
			$where[] = $this->quoteInto("content_type=?", $this->_type);
			$row = $this->fetchRow($where);
			if(!$row) {
				//the row does not exist.  create it
				$data = array(
						'content'       => $url,
						'node'          => $label,
						'content_type'  => $this->_type,
						'parent_id'     => $userId,
						'site_id'     => $siteId,
				);
				$this->insert($data);
			}
		}
	}
	
	public function deleteBookmark($id) {
		$identity = Auth::getIdentity();
		$userId = $identity['id'];
		$cmsCountry = new sessionStorage('cmscountry','cms_site_id');
		$siteId = $cmsCountry->read();
	
		if($userId > 0) {
			$where[] = $this->quoteInto("site_id = ?", $siteId);
			$where[] = $this->quoteInto("parent_id = ?", $userId);
			$where[] = $this->quoteInto("id=?", $id);
			$where[] = $this->quoteInto("content_type=?", $this->_type);
			return $this->tableGateway->delete($where);
		}
	}
    
    
}
