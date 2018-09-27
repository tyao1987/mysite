<?php
namespace Application\Model;

use Application\Service\DbAdapterCluster;
use Zend\Db\TableGateway\TableGateway;
use Zend\Db\ResultSet\ResultSet;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Where;

class DbTable {

    /**
     *
     * @var TableGateway
     */
    public $tableGateway;

    /**
     * 设置 TableGateway
     *
     * @param $dbName string
     *            DB 配置文件中的键名
     * @param $tableName string
     *            表名
     * @param $prototypeModelName string
     *            Prototype 类名
     */
    public function setTableGateway($dbName, $tableName, $prototypeModelName = null) {
        $dbAdapter = DbAdapterCluster::getAdapter($dbName);
        $resultSetPrototype = new ResultSet();
        if ($prototypeModelName) {
            $resultSetPrototype->setArrayObjectPrototype(new $prototypeModelName());
        }
        $this->tableGateway = new TableGateway($tableName, $dbAdapter);
    }

    /**
     * 获取一行记录
     *
     * @param $where Where|\Closure|string|array           
     * @return array \ArrayObject null
     */
    public function fetchRow($where) {
        if($where instanceof select){
            $rowset = $this->tableGateway->selectWith($where);
        }elseif($where instanceof where || is_array($where)){
            $rowset = $this->tableGateway->select($where);
        }elseif(is_string($where)){
            $result = $this->query($where);
            foreach ($result as $row) {
                $result->getResource()->closeCursor();
                return $row;
            }
            return null;
        }
        $row = $rowset->current();
        return $row;
    }

    /**
     * 执行 SQL 语句
     *
     * @param $where Where|\Closure|string|array           
     * @return ResultSet
     */
    public function query($sql) {
        $adapter = $this->tableGateway->getAdapter();
        return $adapter->query($sql)->execute();
    }

    /**
     * 取回一个相关数组,第一个字段值为鍵，第二个字段为值
     *
     * @param
     *            $sql
     * @return array
     */
    public function fetchPairs($sql) {
        $result = $this->query($sql);
        $return = array();
        foreach ($result as $row) {
            $tmp = array_values($row);
            $return[$tmp[0]] = $tmp[1];
        }
        $result->getResource()->closeCursor();
        return $return;
    }

    /**
     * 取回结果集中所有字段的值,作为连续数组返回
     *
     * @param
     *            $sql
     * @return array
     */
    public function fetchAll($sql) {
        $result = $this->query($sql);
        $return = array();
        foreach ($result as $row) {
            $return[] = $row;
        }
        $result->getResource()->closeCursor();
        return $return;
    }

    /**
     * Fetches the first column of the first row of the SQL result.
     *
     * @param
     *            $sql || $select
     * @return array
     */
    public function fetchOne($sql) {
        if($sql instanceof select){
            $row = $this->fetchRow($sql);
            if($row){
                foreach ($row as $value) {
                    return $value;
                }
            }
        }else{
            $result = $this->query($sql);
            if ($result) {
            	foreach ($result as $row) {
            	    $result->getResource()->closeCursor();
            	    $row = array_values($row);           	    
            	    return $row[0];
            	}
            }
        }
    }

    /**
     * Fetches the first column of all SQL result rows as an array.
     *
     * @param
     *            $sql
     * @return array
     */
    public function fetchCol($sql) {
        $result = $this->query($sql);
        $return = array();
        if ($result) {
            foreach ($result as $row) {
                $row = array_values($row);
                $return[] = $row[0];
            }
            $result->getResource()->closeCursor();
            return $return;
        }
    }

    public function quoteInto($text, $value) {
        $paltform = $this->tableGateway->getAdapter()->getPlatform();
        return str_replace('?', $paltform->quoteValue($value), $text);
    }
    
    public function quote($value) {
    	$paltform = $this->tableGateway->getAdapter()->getPlatform();
    	return $paltform->quoteValue($value);
    }
    
    public function beginTransaction(){
        $connection = $this->tableGateway->getAdapter()->getDriver()->getConnection();
        $connection->beginTransaction();
    }
    
    
    public function commit(){
    	$connection = $this->tableGateway->getAdapter()->getDriver()->getConnection();
    	$connection->commit();
    }
    
    
    public function rollback(){
    	$connection = $this->tableGateway->getAdapter()->getDriver()->getConnection();
    	$connection->rollback();
    }
    
    public function insert($data){
        if(is_array($data)){
            $this->tableGateway->insert($data);
            return $this->tableGateway->lastInsertValue;
        }else{
            return false;
        }
    }
    
}