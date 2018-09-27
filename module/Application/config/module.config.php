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
//     'translator' => array(
//         'locale' => 'en_US',
//         'translation_file_patterns' => array(
//             array(
//                 'type'     => 'gettext',
//                 'base_dir' => __DIR__ . '/../language',
//                 'pattern'  => '%s.mo',
//             ),
//         ),
//     ),
    'controllers' => array(
        'invokables' => array(
            'Application\Controller\Index' 	=> 'Application\Controller\IndexController',
        	'Application\Controller\Error' 	=> 'Application\Controller\ErrorController',
        	'Application\Controller\User' 	=> 'Application\Controller\UserController',
        	'Application\Controller\Memcache' 	=> 'Application\Controller\MemcacheController',
        	'Application\Controller\Document' 	=> 'Application\Controller\DocumentController',
        	'Application\Controller\CssJs'      => 'Application\Controller\CssJsController',
        	'Application\Controller\Category'      => 'Application\Controller\CategoryController',
        ),
    ),
    'view_manager' => array(
        'display_not_found_reason' => false,
        'display_exceptions'       => false,
        'doctype'                  => 'HTML5',
        'not_found_template'       => 'error/404',
        'exception_template'       => 'error/index',
        'template_map' => array(
            'layout/layout'           => __DIR__ . '/../view/layout/layout.phtml',
            'error/404'               => __DIR__ . '/../view/error/404.phtml',
            'error/index'             => __DIR__ . '/../view/error/index.phtml',
        ),
        'template_path_stack' => array(
            __DIR__ . '/../view',
        ),
    ),
    
    'view_helpers' => array(
    	'factories'    => array(),
    	'invokables'   => array(
    		'GetUrl' 		=> 'Application\View\Helper\GetUrl',
    		'GetCaptcha' 	=> 'Application\View\Helper\GetCaptcha',
    		'GetLang' 		=> 'Application\View\Helper\GetLang',
    		'CheckLogin' 	=> 'Application\View\Helper\CheckLogin',
    		'GetImg' 		=> 'Application\View\Helper\GetImg',
    		'GetScript' 	=> 'Application\View\Helper\GetScript',
    		'GetStyle' 		=> 'Application\View\Helper\GetStyle',
    		'GetArticle' 	=> 'Application\View\Helper\GetArticle',
    		'ParseContent'  => 'Application\View\Helper\ParseContent',
    		'GetStaticContent'	=> 'Application\View\Helper\GetStaticContent'
    	)
    ),
);
