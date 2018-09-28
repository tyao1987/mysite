<?php
/**
 * This makes our life easier when dealing with paths. Everything is relative
 * to the application root now.
 */
define('ROOT_PATH', dirname(dirname(__FILE__)));

chdir(ROOT_PATH);
// 定义应用运行环境，可以在 .htaccess 中设置 SetEnv APPLICATION_ENV development
defined('APPLICATION_ENV')
|| define('APPLICATION_ENV', (getenv('APPLICATION_ENV')?getenv('APPLICATION_ENV'):'production'));

//为定时任务注入环境变量
$env = file_get_contents(ROOT_PATH . "/config/cron.env.txt");
if($env === false || $env != APPLICATION_ENV){
	file_put_contents(ROOT_PATH . "/config/cron.env.txt", APPLICATION_ENV);
}
defined('ACTIVE_MODULE')
|| define('ACTIVE_MODULE', (getenv('ACTIVE_MODULE')?getenv('ACTIVE_MODULE'):'application'));

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

Test\Util\Timer::start('ALL');

try {
    // Run the application!
    $application = Zend\Mvc\Application::init(require 'config/module/' . ACTIVE_MODULE . '.php');
    $application->run();
} catch (\Exception $e) {
	$loader->add('Application', ROOT_PATH . '/module/Application/src/');
	if(APPLICATION_ENV == "production") {
		
		$data = Test\Data::getInstance();
		if (!$data->has('config')) {
			$config = include ROOT_PATH . "/module/Application/config/config." . APPLICATION_ENV . ".php";
			$data->set('config', $config, true);
		}
		$error = '/error';

		$urlPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

		if ($urlPath == '/error') {
			//$errorForm = '/error.php';
			\Application\Exception::mailError($e);
		}

		\Application\Exception::log($e);
		 
		// send mail
		if (!empty($_SERVER['HTTP_REFERER'])) {
			$parts = parse_url($_SERVER['HTTP_REFERER']);
			if (0 == strcasecmp($parts['host'], $_SERVER['SERVER_NAME'])) {
				\Application\Exception::mailError($e);
			}
		}

		header("Location: ".$error."?referer=" . $_SERVER['REQUEST_URI'], true, 302);
		exit;
	} else {
        echo \Application\Exception::log($e, true);
        die;
    }
}

Test\Util\Timer::end('ALL');

if (!$application->getRequest()->isXmlHttpRequest()
        && (APPLICATION_ENV != "production" || (boolean)$_GET['debug_time'])) {
    echo Test\Util\Timer::show();
}
