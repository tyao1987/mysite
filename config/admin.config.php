<?php
/**
 * pr前台的启动配置文件
 */
return array(
    'modules' => array(
        'Admin',
    ),
    'module_listener_options' => array(
        'config_glob_paths'    => array(
            'config/autoload/{,*.}{global,local}.php',
        ),
        'module_paths' => array(
            './module',
            './vendor',
        ),
    ),
);