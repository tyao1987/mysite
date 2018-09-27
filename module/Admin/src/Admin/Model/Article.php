<?php
namespace Admin\Model;

use Application\Model\DbTable;
use Zend\Db\Sql\Sql;
use Zend\Db\Sql\Expression;
use Application\Model\Utilities;
use Test\Data;
use Zend\Paginator\Adapter\DbSelect;
use Application\Service\DbAdapterCluster;
use Admin\Util\Util;
use Zend\Authentication\Storage\Session as sessionStorage;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Where;
use Zend\Db\Sql\Insert;

class Article extends DbTable {
	protected $_name = 'article';
	protected $_name_content = 'article_content';
	protected $_primary = 'id';
	protected $_name_content_version = "article_content_version";
	protected $_dbAdapter = null;
	function __construct() {
		$this->setTableGateway("cmsdb", $this->_name);
		$this->_dbAdapter = $this->tableGateway->getAdapter();
	}

	public function getListToPaginator($params, $exact = false) {

	    $noderow = $this->getNode($params['aid']);
	    $leftkey = $noderow->leftkey;
	    $rightkey = $noderow->rightkey;
	    $level = $noderow->level;

	    $select = $this->tableGateway->getSql()->select();
	    $select->join(array('b' => $this->_name_content), "b.aid={$this->_name}.id", array('version'=>'version'), 'left');
	    $select->where($this->quoteInto(" `{$this->_name}`.`leftkey` > ?", $leftkey));
	    $select->where($this->quoteInto(" `{$this->_name}`.`rightkey` < ?", $rightkey));
	    $select->where($this->quoteInto(" `{$this->_name}`.`level` = ?", $level+1));
	    $select->order("{$this->_name}.type DESC");
	    if($params['sort']=='name'){
	        $select->order("{$this->_name}.name ASC");
	    }else{
	        $select->order("{$this->_name}.id DESC");
	    }

	    if(!empty($params['name'])){
	        if ($exact) {
	            $select->where(str_ireplace('?', '', ($this->quoteInto("{$this->_name}.name = ?", $params['name']))));
	        } else {
	            $select->where(str_ireplace('?', '', ($this->quoteInto("{$this->_name}.name like ?", "%".$params['name']."%"))));
	        }
	    }


	    if($params['ishide'] === 1){
	        $select->where($this->quoteInto("{$this->_name}.ishide=?",1));
	    }elseif($params['ishide'] === 0){
	        $select->where($this->quoteInto("{$this->_name}.ishide=?",0));
	    }//echo str_replace("\"", "", $select->getSqlString());
	    return new DbSelect($select, $this->_dbAdapter);
	}
	/**
	 * get node row by aid
	 * @param int aid aid
	 * @return obj node row
	 */
	public function getNode($aid){
		$select = $this->tableGateway->getSql()->select();
	    $select->where($this->quoteInto(' `id` = ? ', $aid));

	    return $this->fetchRow($select);

	}
	/**
	 * 获取当前site，对应的aid
	 */
	public function getAidBySite(){
	    $data = Data::getInstance();
	    $sites = $data->get('site');
	    if($sites['site_type'] == "MainSite"){
	        $level = 2;
	    }elseif($sites['site_type'] == "DistributionSite"){
	        $level = 3;
	    }
		
	    $select = $this->tableGateway->getSql()->select();;
	    $select->where($this->quoteInto(' `name` = ? ', $sites['short_name']));
	    $select->where($this->quoteInto(' `level` = ? ', $level));
	    
	    return $this->fetchRow($select);
	}
	public function checkPemission($aid,$base_aid){
	    $thisrow = self::getNode($aid);
	    $baserow = self::getNode($base_aid);
			
	    if($thisrow->leftkey >= $baserow->leftkey && $thisrow->rightkey <= $baserow->rightkey){
	        return true;
	    }else{
	        return false;
	    }
	}
	public function getContentByVersion($aid){
		
	    $sql = new Sql($this->_dbAdapter);
	    $select = $sql->select();
	    $select->from(array('a' => $this->_name_content_version), '*')
	    ->where($this->quoteInto("a.versionid = ?", $aid));
	    $selectString = $sql->getSqlStringForSqlObject($select);
	    $result = $this->fetchRow($selectString);
	    return $result;
	}
	/**
	 * get content by aid
	 * @param int aid aid
	 * @return obj article row
	 */
	public function getContentByAid($aid) {
	    $select = $this->tableGateway->getSql()->select();

	    $select->join(array('b' => $this->_name_content), "{$this->_name}.id=b.aid", "*", "left");
	    $select->where($this->quoteInto("{$this->_name}.id = ?", $aid));

	    return $this->fetchRow($select);
	}
	/**
	 * get history version by aid
	 * @param int aid
	 * @return obj aid history
	 */
	public function getContentHistoryByAid($aid){
	    //$dbAdapter = $this->tableGateway->getAdapter();
	    $sql = new Sql($this->_dbAdapter);
	    $select = $sql->select();
	    $select->from(array('a' => $this->_name_content_version), '*')
	    ->where($this->quoteInto("a.aid = ?", $aid))
	    ->order("a.versionid DESC");
	    $selectString = $sql->getSqlStringForSqlObject($select);
		$result = $this->fetchAll($selectString);
	    return $result;
	}
	/**
	 * 在aid 下增加一个节点
	 */
	public function add($name, $aid, $type = 'DIRECTORY', $description='', $ishide=0) {
	    if(!preg_match('/^([-_0-9a-zA-Z.%])+$/i', $name)) return false;
		
	    //check exist when add new node
	    $isexist = $this->checkName($aid, $name, '');
	    if($isexist) return false;
	    
		$typeTmp = $type;
		$nameTmp = $name;
	    $this->beginTransaction();
	    try {
	        $name = $this->quote($name);
	        $type = $this->quote($type);
	        $description = $this->quote($description);

	        $this->query("SELECT @myRight := rightkey,@level :=level FROM ".$this->_name." WHERE id = ".(int)$aid);
	        $this->query("UPDATE ".$this->_name." SET rightkey = rightkey + 2 WHERE rightkey >= @myRight");
	        $this->query("UPDATE ".$this->_name." SET leftkey = leftkey + 2 WHERE leftkey >= @myRight");
	        $this->query("INSERT INTO ".$this->_name." (leftkey, rightkey, level ,name,type,description,ishide) VALUES (@myRight, @myRight + 1 ,@level+1,$name,$type,$description,'$ishide')");
	        $this->commit();


	    } catch (\Exception $e) {
	        $this->rollBack();
	        //echo $e->getMessage();
	        return false;
	    }
	    $lastinsertid = $this->fetchOne("SELECT last_insert_id()");
	    
	    if($lastinsertid){
	        $this->updateFileContent($nameTmp, $aid, $typeTmp, $description);
	    }

	    return $lastinsertid;
	}
	/**
	 * 根据aid这个节点，获取这个节点的相对path,最高节点对应权限位置
	 * @param int aid article id
	 * @return object $path
	 */
	public function getPathByAid($aid){

	    $path = $this->getPath($aid);
	    $data = Data::getInstance();
	    $sites = $data->get('site');
	    if($sites['site_type'] == "MainSite"){
	        $level = 2;
	    }elseif($sites['site_type']=="DistributionSite"){
	        $level = 3;
	    }

	    $newpath = array();
	    foreach($path as $key=>$value){
	        if($key>$level-2){
	        	$newpath[] = $value;
	        }
	    }

	    return $newpath;

	}
	public function getBreadCrumbPathByAid($aid){
	
	    $path = $this->getPath($aid);
        $level = 2;
	
	    $newpath = array();
	    foreach($path as $key=>$value){
	        if($key>$level-2)	$newpath[] = $value;
	    }
	
	    return $newpath;
	
	}
	/**
	 * update the file or directory
	 * @param varchar name node name
	 * @param int aid father aid
	 * @param varchar type file or directory
	 * @param varchar description nodedescription
	 * @param text content filecontent
	 * @param int ishide 1:hide   0:show
	 * @return boolean true or false
	 */
	private function updateFileContent($name,$aid,$type = 'DIRECTORY',$description='',$content='',$ishide=0){
	    $path = $this->getPath($aid);

	    $newpath = \Application\Util\Util::getWritableDir("articles");
	    foreach($path as $key=>$value){
	        if($key>0){
	            $newpath .= $value->name."/";
	        }
	    }
	    if($type == 'DIRECTORY'){
	        @mkdir($newpath.$name."/",0777,true);
	    }elseif($type == 'FILE'){
	        @mkdir($newpath,0777,true);
	    }

	    if($type == 'FILE'){
	        if($ishide){
	            $thisfile = $newpath.$name;
	            @unlink($thisfile);
	        }else{
	            $thisfile = $newpath.$name;
	            @file_put_contents($thisfile, $content);
	        }
	    }

	    return true;
	}
	/**
	 * get aid path
	 * @param int aid aid
	 * @return obj path result
	 */
	private function getPath($aid){
	    $select = $this->tableGateway->getSql()->select();

	    $select->join(array('O2' => $this->_name), "{$this->_name}.leftkey BETWEEN O2.leftkey AND O2.rightkey", '*');
	    $select->where($this->quoteInto("{$this->_name}.id=?",$aid));
	    $select->order("O2.level ASC");
	    $rs = $this->tableGateway->selectWith($select);
	    $data = array();
	    if ($rs) {
	        foreach ($rs as $item) {
	            $data[] = $item;
	        }
	    }
	    return $data;
	}
	/**
	 * get father node
	 * @param int aid article id
	 * @return obj father node row
	 */
	private function getFatherNode($aid){
	    $path = $this->getPath($aid);

	    array_pop($path);

	    return end($path);
	}
	private function getDirectoryPath($aid){
	    $path = $this->getPath($aid);
	    $newpath = \Application\Util\Util::getWritableDir("articles");
	    foreach($path as $key=>$value){
	        if($key>0){
	            $newpath .= $value->name."/";
	        }
	    }
	    return $newpath;
	}
	private function getLastFilePath($aid){
	    $path = $this->getPath($aid);
	    $newpath = \Application\Util\Util::getWritableDir("articles");

	    $path_count = count($path);
	    foreach($path as $key=>$value){
	        if($key>0 && $key<$path_count-1){
	            $newpath .= $value->name."/";
	        }
	    }
	    return $newpath;
	}
	private function getFilePath($aid){
	    $path = $this->getPath($aid);
	    $newpath = \Application\Util\Util::getWritableDir("articles");
	    foreach($path as $key=>$value){
	        if($key>0){
	            $newpath .= $value->name."/";
	        }
	    }
	    $newpath = substr($newpath,0,-1);
	    return $newpath;
	}
	/**
	 * update node
	 */
	public function updateNode($aid, $array, $type){
	    if(!preg_match('/^([-_0-9a-zA-Z.%])+$/i', $array['name'])) return false;

	    $fathernode = $this->getFatherNode($aid);
	    //check exist when add new node
	    $isexist = $this->checkName($fathernode->id,$array['name'],$aid);
	    if($isexist) return false;


	    $get_this_name = $this->getNode($aid);
	    $get_this_name = $get_this_name->name;
	    
	    if($this->tableGateway->update($array, $this->quoteInto('id = ?',$aid))){

	        //rename the directory
	        if($type == 'DIRECTORY'){
	            $thispatch = $this->getDirectoryPath($aid);
	            $path = $this->getLastFilePath($aid);

	            if(!file_exists($path.$get_this_name."/")){
	                @mkdir($path.$get_this_name."/",0777,true);
	            }
	            rename($path.$get_this_name."/",$path.$array['name']."/");
	        }
	        if($type == 'FILE'){
	            $thispatch = $this->getFilePath($aid);
	            $path = $this->getLastFilePath($aid);
	            if(!file_exists($path)){
	                @mkdir($path,0777,true);
	            }
	            rename($path.$get_this_name,$path.$array['name']);
	        }
	    }else{
	        if($type == 'DIRECTORY'){
	            $thispatch = $this->getDirectoryPath($aid);
	            $path = $this->getLastFilePath($aid);

	            if(!file_exists($path.$get_this_name."/")){
	                @mkdir($path.$get_this_name."/",0777,true);
	            }

	        }
	        if($type == 'FILE'){
	            $thispatch = $this->getFilePath($aid);
	            $path = $this->getLastFilePath($aid);
	            if(!file_exists($path)){
	                @mkdir($path,0777,true);
	            }
	        }
	    }
	    return $aid;
	}
	/**
	 * update content by aid
	 * add version when updated
	 * @param int aid article id
	 * @param text content article content
	 * @return bool true or false
	 */
	public function updateContent($aid, $content, $ishide=0, $name) {

		//$dbAdapter = $this->tableGateway->getAdapter();
	    $this->beginTransaction();
	    try {
	        $storage = Auth::getBaseInfoStorageInstance();
	        $user = $storage->read();
	        $date = date("Y-m-d H:i:s");
	        
	        $sql = new Sql($this->_dbAdapter);
	        $insert = $sql->insert($this->_name_content_version);
	        
	        $values = array('aid'=>$aid,'name'=>$name,'content'=>$content,'createtime'=>$date,'createuser'=>$user['email']);
	        $insert->values($values);
	        $insertSql = $sql->getSqlStringForSqlObject($insert);
	        $re = $this->query($insertSql);
			
	        $versionid = $this->fetchRow("SELECT last_insert_id() AS versionid");
	        $versionid = $versionid['versionid'];
	        $contentTmp = $this->quote($content);
	       	$sqlStr = "REPLACE INTO ".$this->_name_content." (`aid`,`content`,`version`) ";
	       	$sqlStr .= "VALUES ({$aid},{$contentTmp},{$versionid})";
	       	$re = $this->query($sqlStr);

	        $this->commit();
	    } catch (\Exception $e) {
	        $this->rollBack();
	        return false;
	    }
	    $path = $this->getPath($aid);

	    $name = $path;
	    $name = array_pop($name);
	    array_pop($path);
	    $thisaid = array_pop($path);


	    $this->updateFileContent($name->name,$thisaid->id,$type='FILE','',$content,$ishide);

	    return $re;
	}
	/**
	 * delete content by aid in article_content
	 * delete name by id in article
	 * do not delete content in article_content_version
	 * @param int aid article_id
	 * @return bool true or false
	 */
	public function deleteContent($aid){

	    $noderow = $this->getNode($aid);
	    $fatherrow = $this->getFatherNode($aid);

	    $this->beginTransaction();
	    try {
	        //delete node in DB
	        $this->query("SELECT @myRight := rightkey FROM ".$this->_name." WHERE id = ".(int)$aid);
	        $this->query("UPDATE ".$this->_name." SET rightkey = rightkey - 2 WHERE rightkey >= @myRight");
	        $this->query("UPDATE ".$this->_name." SET leftkey = leftkey - 2 WHERE leftkey >= @myRight");

	        $this->query("DELETE FROM ".$this->_name_content." WHERE aid=".(int)$aid);
	        $this->query("DELETE FROM ".$this->_name." WHERE id=".(int)$aid);

	        $this->commit();
	    } catch (\Exception $e) {
	        $this->rollBack();
	        return false;
	    }

	    //delete node in file

	    $this->updateFileContent($noderow->name,$fatherrow->id,$type='FILE','','',1);

	    return true;
	}
	/**
	 * hide or show the file
	 * @param int aid articleid
	 * @param int ishide hide:1,show:0
	 * @return int father aid
	 */
	public function trunActive($aid, $ishide, $type){

	    $array = array("ishide" => $ishide);

	    if($this->tableGateway->update($array, array('id' => $aid))){
	        if($type == "FILE"){
	            $content = $this->getContentByAid($aid);
	            $father = $this->getFatherNode($aid);
	            $this->updateFileContent($content->name,$father->id,$type = 'FILE','',$content->content,$ishide);
	        }

	        if($type == "DIRECTORY"){
	            if($ishide == 1){
	                $this->delTree($this->getDirectoryPath($aid));
	            }else{
	                $this->createAllLeafNode($aid);
	            }

	        }
	    }


	    $fathernode = $this->getFatherNode($aid);
	    return $fathernode->id;

	}
	private function delTree($dir) {
	    $files = glob($dir . '*', GLOB_MARK);
	    foreach($files as $file){
	        if(substr($file, -1) == DIRECTORY_SEPARATOR){
	            $this->delTree($file);
	        }
	        else{
	            unlink($file);
	        }
	    }
	    rmdir($dir);
	}
	private function createAllLeafNode($aid){

	    $noderow = $this->getNode($aid);

	    $leftkey = $noderow->leftkey;
	    $rightkey = $noderow->rightkey;
	    $level = $noderow->level;

	    $select = $this->tableGateway->getSql()->select();


	    $select->join(array('b' => $this->_name_content), "b.aid={$this->_name}.id",array('version'=>'version'), 'left');
	    $select->where($this->quoteInto("{$this->_name}.leftkey > ?", $leftkey));
	    $select->where($this->quoteInto("{$this->_name}.rightkey < ?", $rightkey));
	    $select->where($this->quoteInto("{$this->_name}.leftkey+1 = {$this->_name}.rightkey"));
	    $select->where($this->quoteInto("{$this->_name}.leftkey+1 = {$this->_name}.rightkey"));
	    $select->where($this->quoteInto("{$this->_name}.ishide = ?",0));
	    $select->order("{$this->_name}.type DESC");

	    $rs = $this->tableGateway->selectWith($select);
	    $dir = \Application\Util\Util::getWritableDir("articles");
	    foreach($rs as $key=>$value){
	        $thisdir = $dir;
	        $rs = $this->getPath($value->id);
	        $type = $value->type;

	        $path = "";
	        foreach($rs as $k=>$v){
	            if($k==0) continue;

	            if($v->type == 'DIRECTORY'){
	                $path .= $v->name."/";
	            }else{
	                $path .= $v->name;
	            }
	        }

	        if($type == 'DIRECTORY'){
	            $thisdir = $dir.$path."/";
	            @mkdir($thisdir,0777,true);
	        }

	        if($type == 'FILE'){
	            $sql = "SELECT * FROM `".$this->_name_content."` WHERE aid={$value->id}";
	            
	            $content = $this->fetchRow($sql);

	            $file_path = $dir.$path;

	            preg_match("/(.*)\//i",$file_path,$file_directory);

	            @mkdir($file_directory[1]."/",0777,true);
	            
	            @file_put_contents($file_path, $content['content']);
	        }
	    }

	    return true;

	}
	/**
	 * check the name is exist in aid
	 * @param int aid aid
	 * @param varchar name name
	 * @return boolean true exist or false not exist
	 */
	public function checkName($aid, $name, $nowaid=0){

// 	    $this->query("LOCK TABLES {$this->_name} AS a WRITE,{$this->_name} AS O1 WRITE");

	    $noderow = $this->getNode($aid);

	    $leftkey = $noderow->leftkey;
	    $rightkey = $noderow->rightkey;
	    $level = $noderow->level;

	    $select = $this->tableGateway->getSql()->select();

	    $select->where($this->quoteInto("{$this->_name}.leftkey > ?", $leftkey));
	    $select->where($this->quoteInto("{$this->_name}.rightkey < ?", $rightkey));
	    $select->where($this->quoteInto("{$this->_name}.level = ?", $level+1));
	    $select->where($this->quoteInto("{$this->_name}.name = ?", $name));

	    if($nowaid){
	        $select->where($this->quoteInto("{$this->_name}.id != ?", $nowaid));
	    }
		
        $rs = $this->tableGateway->selectWith($select);

// 	    $this->query("UNLOCK TABLES");

	    return $rs->count();
	}
	/**
	 * 通过version_id 回滚
	 */
	public function makeCurrent($version_id){
        //delete node in DB
        $history_row = self::getContentByVersion($version_id);

        $array['name'] = $history_row['name'];
        $array['ishide'] = 0;
        self::updateNode($history_row['aid'],$array,'FILE');

        self::updateContent($history_row['aid'],$history_row['content'],$ishide=0,$history_row['name']);

        return $history_row['aid'];
	}
}