<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2013 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Cron;

class Module
{
    public function getConfig()
    {
    	return require __DIR__ . '/config/module.config.php';
        
    }

    public function getAutoloaderConfig()
    {
    	
        return array(
            'Zend\Loader\StandardAutoloader' => array(
                'namespaces' => array(
                    __NAMESPACE__ => __DIR__ . '/src/' . __NAMESPACE__,
                	'Admin' => ROOT_PATH . '/module/Admin/src/Admin',
                	'Application' => ROOT_PATH . '/module/Application/src/Application',
                ),
            ),
        );
    }
    
}

