<?php

return array(
	'document' => array(
			'type' => 'Zend\Mvc\Router\Http\Regex',
			'options' => array(
					'regex' => '/(?<title>.+)\.(?<type>html|xml|txt)([^\/]+)?',
					'defaults' => array(
							'controller' => 'Application\Controller\Document',
							'action' => 'index'
					),
					'spec' => '/%title%.html'
			)
	),
	'home' => array(
			'type' => 'Zend\Mvc\Router\Http\Literal',
			'options' => array(
					'route' => '/',
					'defaults' => array(
							'controller' => 'Application\Controller\Index',
							'action' => 'index',
					),
			),
	),
	'registration' => array(
			'type' => 'Zend\Mvc\Router\Http\Literal',
			'options' => array(
					'route' => '/registration.html',
					'defaults' => array(
							'controller' => 'Application\Controller\User',
							'action' => 'registration'
					)
			)
	),
	'new_captcha' => array(
			'type' => 'Zend\Mvc\Router\Http\Literal',
			'options' => array(
					'route' => '/new-captcha.html',
					'defaults' => array(
							'controller' => 'Application\Controller\User',
							'action' => 'newCaptcha'
					)
			)
	),
	'registration_validation' => array(
			'type' => 'Zend\Mvc\Router\Http\Literal',
			'options' => array(
					'route' => '/registration-validation.html',
					'defaults' => array(
							'controller' => 'Application\Controller\User',
							'action' => 'registrationValidation'
					)
			)
	),
	'try_registration' => array(
			'type' => 'Zend\Mvc\Router\Http\Literal',
			'options' => array(
					'route' => '/try-registration.html',
					'defaults' => array(
							'controller' => 'Application\Controller\User',
							'action' => 'registrationProcess'
					)
			)
	),
	'active_user' => array(
			'type' => 'Zend\Mvc\Router\Http\Regex',
			'options' => array(
					'regex' => '/active-user/(?<key>\w+)',
					'defaults' => array(
							'controller' => 'Application\Controller\User',
							'action' => 'activeRegistration'
					),
					'spec' => '/active-user/%key%.html'
			)
	),
	'user_settings' => array(
			'type' => 'Zend\Mvc\Router\Http\Literal',
			'options' => array(
					'route' => '/user-settings.html',
					'defaults' => array(
							'controller' => 'Application\Controller\User',
							'action' => 'userSettings'
					)
			)
	),
	'login' => array(
			'type' => 'Zend\Mvc\Router\Http\Literal',
			'options' => array(
					'route' => '/login.html',
					'defaults' => array(
							'controller' => 'Application\Controller\User',
							'action' => 'login'
					)
			)
	),
	'try_login' => array(
			'type' => 'Zend\Mvc\Router\Http\Literal',
			'options' => array(
					'route' => '/try-login.html',
					'defaults' => array(
							'controller' => 'Application\Controller\User',
							'action' => 'loginProcess'
					)
			)
	),
	'password_recovery' => array(
			'type' => 'Zend\Mvc\Router\Http\Literal',
			'options' => array(
					'route' => '/password-recovery.html',
					'defaults' => array(
							'controller' => 'Application\Controller\User',
							'action' => 'passwordRecovery'
					)
			)
	),
	'logout' => array(
			'type' => 'Zend\Mvc\Router\Http\Literal',
			'options' => array(
					'route' => '/logout.html',
					'defaults' => array(
							'controller' => 'Application\Controller\User',
							'action' => 'logoutProcess'
					)
			)
	),
	'send_activation_email' => array(
			'type' => 'Zend\Mvc\Router\Http\Regex',
			'options' => array(
					'regex' => '/send-activation-email.*',
					'defaults' => array(
							'controller' => 'Application\Controller\User',
							'action' => 'resendActivationEmail'
					),
					'spec' => '/send-activation-email.html'
			)
	),
	'password_reset' => array(
			'type' => 'Zend\Mvc\Router\Http\Literal',
			'options' => array(
					'route' => '/password-reset',
					'defaults' => array(
							'controller' => 'Application\Controller\User',
							'action' => 'passwordReset'
					)
			)
	),
	'activate_email_change' => array(
			'type' => 'Zend\Mvc\Router\Http\Regex',
			'options' => array(
					'regex' => '/activate-email-change/(?<newEmail>.+?)/(?<key>\w+)',
					'defaults' => array(
							'controller' => 'Application\Controller\User',
							'action' => 'activateEmailChange'
					),
					'spec' => '/activate-email-change/%newEmail%/%key%'
			)
	),
   
    'clear-cache' => array(
        	'type' => 'Zend\Mvc\Router\Http\Literal', 
        	'options' => array(
            		'route' => '/mod_memcache/admin/clear-cache', 
            		'defaults' => array(
                			'controller' => 'Application\Controller\Memcache', 
            				'action' => 'clearCache'
            		)
        	)
    ), 
	'stylesheet' => array(
			'type' => 'Zend\Mvc\Router\Http\Regex',
			'options' => array(
					'regex' => '/(?:styles/)?(?<type>core(?:\_distribution)?|extra|noscript|override_[^\.]+|partner_[^\.]+)\.css',
					'defaults' => array(
							'controller' => 'Application\Controller\CssJs',
							'action' => 'stylesheet'
					),
					'spec' => '/styles/%type%.css'
			)
	),
	'product_list' => array(
			'type' => 'Zend\Mvc\Router\Http\Regex',
			'options' => array(
					'regex' => '/cl/(?<cat>\d+)/?(?<categoryName>.*)?\.html',
					'defaults' => array(
							'controller' => 'Application\Controller\Category',
							'action' => 'product'
					),
					'spec' => '/cl/%cat%/%categoryName%.html'
			)
	),
    
		
);