<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2014 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */
return array(
	'service_manager' => array(
		'invokables' => array(
			'interceptorService' => 'Admin\Service\Interceptor',
		),
		'factories' => array(
		    'navigation' => 'Zend\Navigation\Service\DefaultNavigationFactory',
		),
	),

    'controllers' => array(
        'invokables' => array(
        	'Admin\Controller\Index'	   => 'Admin\Controller\IndexController',
            'Admin\Controller\Auth'	       => 'Admin\Controller\AuthController',
        	'Admin\Controller\Acl'         => 'Admin\Controller\AclController',
            'Admin\Controller\Article'     => 'Admin\Controller\ArticleController',
            'Admin\Controller\Translation' => 'Admin\Controller\TranslationController' ,
            'Admin\Controller\Memcache'    => 'Admin\Controller\MemcacheController' ,
            'Admin\Controller\Uploadimage' => 'Admin\Controller\UploadimageController',
            'Admin\Controller\Site'        => 'Admin\Controller\SiteController',
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
            'layout/udferror'         => __DIR__ . '/../view/layout/udferror.phtml',
        ),
        'template_path_stack' => array(
            'admin'    => __DIR__ . '/../view',
        ),
    ),

    'view_helpers' => array(
        'factories'    => array(
            'flashMessages' => function($sm) {
                $flashmessenger = $sm->getServiceLocator()
                ->get('ControllerPluginManager')
                ->get('flashmessenger');

                $messages = new \Admin\View\Helper\FlashMessages();
                $messages->setFlashMessenger($flashmessenger);

                return $messages;
            },
            'navigation' => function(Zend\View\HelperPluginManager $pm) {
                $currentUser = Admin\Model\Auth::getIdentity();
                $cache = Application\Service\Cache::get('dynamicCache');
                $batchId = $cache->getItem('RESOURCES_BATCHID');
                if(null === $batchId){
                    $batchId = md5(time());
                    $cache->setItem('RESOURCES_BATCHID', $batchId);
                }
                $key = 'ACL_OBJ_' . $currentUser['id'] . '_' .$batchId;
                $cacheKey = Application\Util\Util::makeCacheKey($key);
                $acl = $cache->getItem($cacheKey);
                // Setup ACL:
                if (null === $acl) {
                    $acl = new \Admin\Model\Acl();
                } else {
                    $acl = unserialize($acl);
                }
                
                // Get an instance of the proxy helper
                $navigation = $pm->get('Zend\View\Helper\Navigation');
                // Store ACL and role in the proxy helper:
                $navigation->setAcl($acl)->setRole($currentUser['id']);
            
                // Return the new navigation helper instance
                return $navigation;
            }
        ),
        'invokables'   => array(
            'GetLang' 			        	=> 'Application\View\Helper\GetLang',
            'GetUrl'                        => 'Application\View\Helper\GetUrl',
            'GetTranslation'                => 'Admin\View\Helper\GetTranslation',
            'CurrentAdminUser'              => 'Admin\View\Helper\CurrentAdminUser',
            'RenderAdminMenu'               => 'Admin\View\Helper\RenderAdminMenu',
            'RenderOptions'                 => 'Admin\View\Helper\RenderOptions',
            'RenderLinks'                   => 'Admin\View\Helper\RenderLinks',
            'RenderAlert'                   => 'Admin\View\Helper\RenderAlert',
        	'CleanUri'                   	=> 'Admin\View\Helper\CleanUri',

    	)
    ),
);
