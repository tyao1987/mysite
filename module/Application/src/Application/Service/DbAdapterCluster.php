<?php
namespace Application\Service;

use Zend\Db\Adapter\Adapter;

class DbAdapterCluster{
    
	protected static $config;
	protected static $adapters = array();
	
	static function getAdapter($name){
		if(!isset(self::$adapters[$name])){
		    if(self::$config === null){
			    self::$config = self::getConfig();
		    }
		    $options = self::$config[$name];
		    $options['port'] = empty($options['port'])?3306:$options['port'];
		    $db = array(
		        'driver'         => 'Pdo',
		        'dsn'            => "mysql:dbname={$options['dbname']};host={$options['host']};port={$options['port']}",
		        'driver_options' => array(
		            \PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$options['charset']}",
		        ),
		        'username' => $options['username'],
		        'password' => $options['password'],
		    );
		    self::$adapters[$name] = new Adapter($db);
	    }
		return self::$adapters[$name];
	}
	
	protected static function getConfig(){
		return require ROOT_PATH . '/config/db/db.' . APPLICATION_ENV . '.php';
	}
}