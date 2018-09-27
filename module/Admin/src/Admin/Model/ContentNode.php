<?php
namespace Admin\Model;

use Application\Model\DbTable;
use Zend\Db\Sql\Sql;
class ContentNode extends DbTable {
	
	protected $_name = "content_nodes";

	protected $_primary = 'id';

	protected $_namespace = "page";

	function __construct() {

        $this->setTableGateway("cmsdb", $this->_name);
    }
    
	/**
	 * returns the content object for the selected page
	 * if nodes is set then it will only return the specified nodes
	 * otherwise it returns all
	 *
	 * @param int $pageId
	 * @param array $nodes
	 * @return object
	 */
	public function fetchContentObject($pageId, $nodes = null, $namespace = null, $version = null) {
		if (null == $namespace) {
			$namespace = $this->_namespace;
		}
		
		// we don't need site_id, because pageid is primary key
		//$site = $this->_getSite();
		//$where [] = $this->_db->quoteInto ( "site_id = ?", $site['site_id'] );
		
		$select = $this->tableGateway->getSql()->select();
		$select->where($this->quoteInto( "parent_id = ?", $namespace . '_' . $pageId ));

		if ($version != null) {
			$select->where($this->quoteInto( "version = ?", $version ));
		} else {
			$select->where('version IS NULL');
		}
		$dbAdapter = $this->tableGateway->getAdapter ();
		$sql = new Sql ( $dbAdapter );
		$selectString = $sql->getSqlStringForSqlObject($select);

		$rowset = $this->fetchAll( $selectString );

		if (count($rowset) > 0) {
			foreach ( $rowset as $row ) {
				$node = $row['node'];
				$data[$node] = stripslashes ( $row['content'] );
				$data[$node . '_content'] = $row;
			}
		}
		if (is_array ( $nodes )) {
			foreach ( $nodes as $node ) {
				if (isset($data[$node]) && !empty ( $data[$node] )) {
					$return[$node] = $data[$node];
				} else {
					$return[$node] = null;
				}
			}
			return $return;
		} else {
			return $data;
		}
	}
}