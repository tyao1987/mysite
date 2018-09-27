<?php

return array (
        'home'    => array(
	        'type' => 'Zend\Mvc\Router\Http\Literal',
            'options' => array(
        	    'route' => '/',
                'defaults' => array(
                    '__NAMESPACE__' => 'Admin\Controller',
                    'controller' => 'Index',
                    'action'     => 'index',
                ),
            ),
        ),
		'default' => array(
                'type' => 'Zend\Mvc\Router\Http\Segment',
                'options' => array(
                    'route'    => '/[:controller[/:action]][/id/:id][/url/:url][/label/:label]',
                    'constraints' => array(
                        'controller' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'action'     => '[a-zA-Z][a-zA-Z0-9_-]*',
                    ),
                    'defaults' => array(
                    	'__NAMESPACE__' => 'Admin\Controller',
                        'controller' => 'Index',
						'action'     => 'index',
                    ),
                ),
        ),
);