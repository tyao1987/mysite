<?php

namespace Application\Model;

class TranslationTable extends DbTable {
    
	function __construct(){
		$this->setTableGateway("cmsdb", "translation");
	}
	
	public function getLang($locale) {
	    $sql = $this->tableGateway->getSql();
	    $select = $sql->select();
	    $select->columns(array('lang', $locale));
		$rows = $this->tableGateway->select($select)->toArray();
		$translations = array();
		foreach ($rows as $row) {
		    $translations[$row['lang']] = $row[$locale];
		}
		return $translations;
	}
}