<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2013 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

return array(
    
    'service_manager' => array(
        'factories' => array(
            //'translator' => 'Zend\I18n\Translator\TranslatorServiceFactory',
        ),
    ),


	'console' => array(
			'router' => array(
					'routes' => array(
							//CRON RESULTS SCRAPER
							'hello' => array(
									'type'    => 'simple',       // <- simple route is created by default, we can skip that
									'options' => array(
											'route'    => 'hello',
											'defaults' => array(
													'controller' => 'Cron\Controller\Index',
													'action'     => 'index'
											)
									)
							)
	
					),
			),
	),
		
    'controllers' => array(
        'invokables' => array(
            'Cron\Controller\Index' 	=> 'Cron\Controller\IndexController',
        	'Cron\Controller\Error' 	=> 'Cron\Controller\ErrorController',
        ),
    ),

    
);
