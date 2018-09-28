<?php
/**
 * This makes our life easier when dealing with paths. Everything is relative
 * to the application root now.
 */
define('ROOT_PATH', dirname(dirname(__FILE__)));

chdir(ROOT_PATH);

$env = file_get_contents(ROOT_PATH . "/config/env.txt");
if($env === false){
	echo "APPLICATION_ENV NOT FOUND";
	exit;
}
define('APPLICATION_ENV', $env);
define('ACTIVE_MODULE', 'cron');

// 不是产品环境则允许显示错误，以方便调试
if (APPLICATION_ENV != 'production') {
	error_reporting(E_ALL ^ E_NOTICE ^ E_WARNING);
	ini_set('display_startup_errors', 1);
	ini_set('display_errors', 1);
} else {
	// 是产品环境则不允许显示错误
	ini_set('display_startup_errors', 0);
	ini_set('display_errors', 0);
}

// Setup autoloading
require 'init_autoloader.php';

try {
    // Run the application!
    $application = Zend\Mvc\Application::init(require 'config/module/' . ACTIVE_MODULE . '.config.php');
    $application->run();
} catch (\Exception $e) {
	echo $e->getMessage();exit;
//	$loader->add('Application', ROOT_PATH . '/module/Application/src/');
// 	if(APPLICATION_ENV == "production") {
		
// 		$data = Test\Data::getInstance();
// 		if (!$data->has('config')) {
// 			$config = include ROOT_PATH . "/module/Application/config/config." . APPLICATION_ENV . ".php";
// 			$data->set('config', $config, true);
// 		}
// 		$error = '/error';

// 		$urlPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// 		if ($urlPath == '/error') {
// 			//$errorForm = '/error.php';
// 			\Application\Exception::mailError($e);
// 		}

// 		\Application\Exception::log($e);
		 
// 		// send mail
// 		if (!empty($_SERVER['HTTP_REFERER'])) {
// 			$parts = parse_url($_SERVER['HTTP_REFERER']);
// 			if (0 == strcasecmp($parts['host'], $_SERVER['SERVER_NAME'])) {
// 				\Application\Exception::mailError($e);
// 			}
// 		}

// 		header("Location: ".$error."?referer=" . $_SERVER['REQUEST_URI'], true, 302);
// 		exit;
// 	} else {
//         echo \Application\Exception::log($e, true);
//         die;
//     }
}


