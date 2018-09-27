<?php
return array (
  'site' => NULL,
  'router' => 
  array (
    'routes' => NULL,
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
      'Application\\Controller\\Document' => 'Application\\Controller\\DocumentController',
      'Application\\Controller\\CssJs' => 'Application\\Controller\\CssJsController',
      'Application\\Controller\\Category' => 'Application\\Controller\\CategoryController',
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
      'GetScript' => 'Application\\View\\Helper\\GetScript',
      'GetStyle' => 'Application\\View\\Helper\\GetStyle',
      'GetArticle' => 'Application\\View\\Helper\\GetArticle',
      'ParseContent' => 'Application\\View\\Helper\\ParseContent',
      'GetStaticContent' => 'Application\\View\\Helper\\GetStaticContent',
    ),
  ),
  'console' => 
  array (
    'router' => 
    array (
      'routes' => 
      array (
        'hello' => 
        array (
          'type' => 'simple',
          'options' => 
          array (
            'route' => 'hello',
            'defaults' => 
            array (
              'controller' => 'Application\\Controller\\IndexController',
              'action' => 'index',
            ),
          ),
        ),
      ),
    ),
  ),
);