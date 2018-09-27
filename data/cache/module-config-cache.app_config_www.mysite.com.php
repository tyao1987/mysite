<?php
return array (
  'site' => 
  array (
    'site_id' => '1',
    'hostname' => 'www.mysite.com',
    'domain' => 'www.mysite.com',
    'short_name' => 'cn',
    'country' => 'CHN',
    'display_name' => 'www.mysite.com',
    'site_type' => 'MainSite',
    'frontend_db' => 'FE_CHI',
    'backend_db' => 'BE_CHI',
    'currency' => 'CNY',
    'isactive' => 'YES',
  ),
  'router' => 
  array (
    'routes' => 
    array (
      'home' => 
      array (
        'type' => 'Zend\\Mvc\\Router\\Http\\Literal',
        'options' => 
        array (
          'route' => '/',
          'defaults' => 
          array (
            'controller' => 'Application\\Controller\\Index',
            'action' => 'index',
          ),
        ),
      ),
      'registration' => 
      array (
        'type' => 'Zend\\Mvc\\Router\\Http\\Literal',
        'options' => 
        array (
          'route' => '/registration.html',
          'defaults' => 
          array (
            'controller' => 'Application\\Controller\\User',
            'action' => 'registration',
          ),
        ),
      ),
      'new_captcha' => 
      array (
        'type' => 'Zend\\Mvc\\Router\\Http\\Literal',
        'options' => 
        array (
          'route' => '/new-captcha.html',
          'defaults' => 
          array (
            'controller' => 'Application\\Controller\\User',
            'action' => 'newCaptcha',
          ),
        ),
      ),
      'registration_validation' => 
      array (
        'type' => 'Zend\\Mvc\\Router\\Http\\Literal',
        'options' => 
        array (
          'route' => '/registration-validation.html',
          'defaults' => 
          array (
            'controller' => 'Application\\Controller\\User',
            'action' => 'registrationValidation',
          ),
        ),
      ),
      'try_registration' => 
      array (
        'type' => 'Zend\\Mvc\\Router\\Http\\Literal',
        'options' => 
        array (
          'route' => '/try-registration.html',
          'defaults' => 
          array (
            'controller' => 'Application\\Controller\\User',
            'action' => 'registrationProcess',
          ),
        ),
      ),
      'active_user' => 
      array (
        'type' => 'Zend\\Mvc\\Router\\Http\\Regex',
        'options' => 
        array (
          'regex' => '/active-user/(?<key>\\w+)',
          'defaults' => 
          array (
            'controller' => 'Application\\Controller\\User',
            'action' => 'activeRegistration',
          ),
          'spec' => '/active-user/%key%\\.html',
        ),
      ),
      'user_settings' => 
      array (
        'type' => 'Zend\\Mvc\\Router\\Http\\Literal',
        'options' => 
        array (
          'route' => '/user-settings.html',
          'defaults' => 
          array (
            'controller' => 'Application\\Controller\\User',
            'action' => 'userSettings',
          ),
        ),
      ),
      'login' => 
      array (
        'type' => 'Zend\\Mvc\\Router\\Http\\Literal',
        'options' => 
        array (
          'route' => '/login.html',
          'defaults' => 
          array (
            'controller' => 'Application\\Controller\\User',
            'action' => 'login',
          ),
        ),
      ),
      'try_login' => 
      array (
        'type' => 'Zend\\Mvc\\Router\\Http\\Literal',
        'options' => 
        array (
          'route' => '/try-login.html',
          'defaults' => 
          array (
            'controller' => 'Application\\Controller\\User',
            'action' => 'loginProcess',
          ),
        ),
      ),
      'password_recovery' => 
      array (
        'type' => 'Zend\\Mvc\\Router\\Http\\Literal',
        'options' => 
        array (
          'route' => '/password-recovery.html',
          'defaults' => 
          array (
            'controller' => 'Application\\Controller\\User',
            'action' => 'passwordRecovery',
          ),
        ),
      ),
      'logout' => 
      array (
        'type' => 'Zend\\Mvc\\Router\\Http\\Literal',
        'options' => 
        array (
          'route' => '/logout.html',
          'defaults' => 
          array (
            'controller' => 'Application\\Controller\\User',
            'action' => 'logoutProcess',
          ),
        ),
      ),
      'send_activation_email' => 
      array (
        'type' => 'Zend\\Mvc\\Router\\Http\\Regex',
        'options' => 
        array (
          'regex' => '/send-activation-email.*',
          'defaults' => 
          array (
            'controller' => 'Application\\Controller\\User',
            'action' => 'resendActivationEmail',
          ),
          'spec' => '/send-activation-email\\.html',
        ),
      ),
      'password_reset' => 
      array (
        'type' => 'Zend\\Mvc\\Router\\Http\\Literal',
        'options' => 
        array (
          'route' => '/password-reset',
          'defaults' => 
          array (
            'controller' => 'Application\\Controller\\User',
            'action' => 'passwordReset',
          ),
        ),
      ),
      'activate_email_change' => 
      array (
        'type' => 'Zend\\Mvc\\Router\\Http\\Regex',
        'options' => 
        array (
          'regex' => '/activate-email-change/(?<newEmail>.+?)/(?<key>\\w+)',
          'defaults' => 
          array (
            'controller' => 'Application\\Controller\\User',
            'action' => 'activateEmailChange',
          ),
          'spec' => '/activate-email-change/%newEmail%/%key%',
        ),
      ),
      'clear-cache' => 
      array (
        'type' => 'Zend\\Mvc\\Router\\Http\\Literal',
        'options' => 
        array (
          'route' => '/mod_memcache/admin/clear-cache',
          'defaults' => 
          array (
            'controller' => 'Application\\Controller\\Memcache',
            'action' => 'clearCache',
          ),
        ),
      ),
    ),
  ),
  'service_manager' => 
  array (
    'factories' => 
    array (
    ),
  ),
  'controllers' => 
  array (
    'invokables' => 
    array (
      'Application\\Controller\\Index' => 'Application\\Controller\\IndexController',
      'Application\\Controller\\Error' => 'Application\\Controller\\ErrorController',
      'Application\\Controller\\User' => 'Application\\Controller\\UserController',
      'Application\\Controller\\Memcache' => 'Application\\Controller\\MemcacheController',
    ),
  ),
  'view_manager' => 
  array (
    'display_not_found_reason' => false,
    'display_exceptions' => false,
    'doctype' => 'HTML5',
    'not_found_template' => 'error/404',
    'exception_template' => 'error/index',
    'template_map' => 
    array (
      'layout/layout' => '/var/www/mysite/module/Application/config/../view/layout/layout.phtml',
      'error/404' => '/var/www/mysite/module/Application/config/../view/error/404.phtml',
      'error/index' => '/var/www/mysite/module/Application/config/../view/error/index.phtml',
    ),
    'template_path_stack' => 
    array (
      0 => '/var/www/mysite/module/Application/config/../view',
    ),
  ),
  'view_helpers' => 
  array (
    'factories' => 
    array (
    ),
    'invokables' => 
    array (
      'GetUrl' => 'Application\\View\\Helper\\GetUrl',
      'GetCaptcha' => 'Application\\View\\Helper\\GetCaptcha',
      'GetLang' => 'Application\\View\\Helper\\GetLang',
      'CheckLogin' => 'Application\\View\\Helper\\CheckLogin',
      'GetImg' => 'Application\\View\\Helper\\GetImg',
    ),
  ),
);