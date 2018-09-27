<?php

namespace Admin\Model;

use Zend\Db\Sql\Sql;

use Zend\Db\Sql\Expression;
use Application\Model\DbTable;

class Sites extends DbTable {
	
	protected $_name = 'sites';
	protected $_primary = 'site_id';
	
	function __construct(){
		$this->setTableGateway("cmsdb", $this->_name);
		$this->_select = $this->tableGateway->getSql()->select();
	}
	
	/**
	 *  get site by domain
	 * 
	 * @param String $domain
	 * @return array | null
	 */
	public function getSite($domain) {
		
		$select = $this->_select;
		$select->where($this->quoteInto(' `hostname` = ? ', $domain));
		$result = $this->fetchRow($select);
		
		return $result;
	}
	
	/**
	 *  get active  site by domain
	 * 
	 * @param string $domain
	 * @return array | null
	 */
	public function getActiveSite($domain) {
		
		$select = $this->_select;
		$select->where($this->quoteInto(' `hostname` = ? ', $domain));
		$select->where($this->quoteInto(' `isactive` = ? ',  "YES" ));
		$result = $this->fetchRow($select);
		
		return $result;
	}
	
	/**
	 * get all active sites
	 * 
	 * @return array | null
	 */
	public function fetchActiveAll() {

		$dbAdapter = $this->tableGateway->getAdapter();
		$sql = new Sql ($dbAdapter);

		$select = $this->_select;
		$select->where($this->quoteInto( "isactive = ?", "YES" ));
		$selectString = $sql->getSqlStringForSqlObject($select);
		$result = $this->fetchAll($selectString);
		
		return $result;
	}
	
	/**
	 * get site by id 
	 * 
	 * @param int $siteid
	 * @return array | null
	 */
	public function getSiteByID($siteid) {
		
		$select = $this->_select;
		$select->where($this->quoteInto( "site_id = ?", $siteid ));
		$result = $this->fetchRow($select);
		
		return $result;
	}
	
	/**
	 *  get site list 
	 * 
	 * @return array | null
	 */
	public function getSiteList() {
		
		$select = $this->_select;
		$select->from ( $this->_name, array (
				'site_id',
				'display_name' 
		) );
		$select->group ( 'site_id' );
		
		return $this->fetchPairs ( $select );
	}
	
	/**
	 * get Site ShortName  List
	 * 
	 * @return array | null
	 */
	public function getSiteShortNameList() {
		
		$select = $this->_select;
		$select->columns(array (
				'site_id',
				'short_name' 
		));
		$select->group ( 'site_id' );
	    
		$result = $this->tableGateway->selectWith($select);
		foreach ($result as $row) {
			$return[$row['site_id']] = $row['short_name'];
		}
		return $return;
		
	}
	
	/**
	 *  get sites count
	 * 
	 * @return array | null
	 */
	public function getSiteCounts() {
		
		$select = $this->_select;
// 		$select->from ( $this->_name, array (
// 				'count(*) as counts' 
// 		) );
        $select->columns(array('counts' => new Expression('COUNT(*)')));
		$result = $this->fetchRow($select);
		
		return $result;
	}
    
    public function updateCmsDb($id, $data) {
    	if(is_array($data)){
            return $this->tableGateway->update($data , array('site_id' => $id));
        }else{
            return false;
        }
    }
	
	public function getSitesPairs(){
		$results = $this->fetchAll('select site_id,hostname from sites order by site_id');
		$returnArray = array();
		foreach ($results as $result) {
			$returnArray[$result['site_id']] = $result['hostname'];
		}
		return $returnArray;
	}

	public function getSitesColumn($columns = array('*') , $returnColumn = array('*')){
		
		if(!is_array($columns)) return false ;

		$results = $this->fetchAll('select '.implode(',', $columns).' from sites order by site_id');

		$return = array();
		foreach ($results as $result) {
			if(isset($result['site_id'])){
				if(is_string($returnColumn) && in_array($returnColumn, $columns) && $returnColumn != '*')
					$return[$result['site_id']] = $result[$returnColumn];
				else{
					$return[$result['site_id']] = $result ;
				}
			}else{
				$return[] = $result ;
			}
		}
		return $return;
	}
}