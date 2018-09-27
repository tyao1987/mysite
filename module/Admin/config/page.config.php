<?php
return array(
        'default' => array(
            array(
        	    'label' => 'Home',
                'route' => 'default',
                'module' => 'admin',
                'controller' => 'index',
                'action' => 'index',
                'resource' => 'admin_index_index',
            ),
            array(
                'label' => 'Site',
                'route' => 'default',
                'module' => 'admin',
                'controller' => 'site',
                'action' => 'index',
                'resource' => 'admin_site_index',
//                 'pages' => array(
//         	        array(
//                 	    'label' => 'Category list for choosing important filters',
//         	            'route' => 'default',
//         	            'module' => 'admin',
//         	            'controller' => 'site',
//         	            'action' => 'filter-category',
//         	            'resource' => 'admin_site_filter-category',
//         	            'pages' => array(
//         	                array(
//         	                    'label' => 'important filters',
//         	                    'route' => 'default',
//         	                    'module' => 'admin',
//         	                    'controller' => 'site',
//         	                    'action' => 'important-filters',
//         	                    'resource' => 'admin_site_important-filters',
//         	                ),
//                         ),
//                     ),
//                 )
            ),
            array(
                'label' => 'Aritcle',
                'route' => 'default',
                'module' => 'admin',
                'controller' => 'article',
                'action' => 'index',
                'resource' => 'admin_article_index',
            ),
            array(
                'label' => 'Memcache',
                'route' => 'default',
                'controller' => 'memcache',
                'action' => 'index',
                'resource' => 'admin_memcache_index',
            ),
            array(
                'label' => 'Admin Access Log',
                'module' => 'admin',
                'controller' => 'index',
                'action' => 'log',
                'resource' => 'admin_index_log',
            ),
            array(
                'label' => 'Acl',
                'module' => 'admin',
                'route' => 'default',
                'controller' => 'acl',
                'pages' => array(
                    array(
                        'label' => 'Module',
                        'module' => 'admin',
                        'route' => 'default',
                        'controller' => 'acl',
                        'action' => 'module-list',
                        'resource' => 'admin_acl_module-list',
                        'pages' => array(
                            array(
                                'label' => 'Add Module',
                                'module' => 'admin',
                                'route' => 'default',
                                'controller' => 'acl',
                                'action'     => 'module-edit',
                                'resource' => 'admin_acl_module-edit',
                            ),
                        )
                    ),
                    array(
                        'label' => 'Controller',
                        'module' => 'admin',
                        'route' => 'default',
                        'controller' => 'acl',
                        'action' => 'controller-list',
                        'resource' => 'admin_acl_controller-list',
                        'pages' => array(
                            array(
                                'label' => 'Add Controller',
                                'controller' => 'acl',
                                'action'     => 'controller-edit',
                                'resource' => 'admin_acl_controller-edit',
                            ),
                        )
                    ),
                    array(
                        'label' => 'Action',
                        'route' => 'default',
                        'controller' => 'acl',
                        'action' => 'action-list',
                        'resource' => 'admin_acl_action-list',
                        'pages' => array(
                            array(
                                'label' => 'Add Action',
                                'controller' => 'acl',
                                'action'     => 'action-edit',
                                'resource' => 'admin_acl_action-edit',
                            ),
                        )
                    ),
                    array(
                        'label' => 'User',
                        'route' => 'default',
                        'controller' => 'acl',
                        'action' => 'user-list',
                        'resource' => 'admin_acl_user-list',
                        'pages' => array(
                            array(
                                'label' => 'User Manage',
                                'controller' => 'acl',
                                'action'     => 'user-manage',
                                'resource' => 'admin_acl_user-manage',
                                'visible' => false,
                            ),
                            array(
                                'label' => 'Add User',
                                'controller' => 'acl',
                                'action'     => 'user-add',
                                'resource' => 'admin_acl_user-add',
                            ),
                        ),
                    ),
                    array(
                        'label' => 'Role',
                        'route' => 'default',
                        'controller' => 'acl',
                        'action' => 'role-list',
                        'resource' => 'admin_acl_role-list',
                        'pages' => array(
                            array(
                                'label' => 'Rule Manage',
                                'controller' => 'acl',
                                'action'     => 'role-manage',
                                'resource' => 'admin_acl_role-manage',
                            ),
                            array(
                                'label' => 'Add Role',
                                'controller' => 'acl',
                                'action'     => 'role-edit',
                                'resource' => 'admin_acl_role-edit',
                            ),
                        )
                    ),
                    array(
                        'label' => 'SiteGroup',
                        'route' => 'default',
                        'controller' => 'acl',
                        'action' => 'site-group-list',
                        'resource' => 'admin_acl_site-group-list',
                        'pages' => array(
                            array(
                                'label' => 'Manage SiteGroup',
                                'controller' => 'acl',
                                'action'     => 'site-group-manage',
                                'resource' => 'admin_acl_site-group-manage',
                            ),
                            array(
                                'label' => 'Add SiteGroup',
                                'controller' => 'acl',
                                'action'     => 'site-group-edit',
                                'resource' => 'admin_acl_site-group-edit',
                            ),
                        ),
                    ),
                ),	
            ),
            array(
                'label' => 'Translation',
                'route' => 'default',
                'controller' => 'translation',
                'action' => 'list',
//                 'resource' => 'admin_translation_list',
                'pages' => array(
                    array(
                        'label' => 'List',
                        'route' => 'default',
                        'controller' => 'translation',
                        'action' => 'list',
                        'resource' => 'admin_translation_list',
                    ),
                    array(
                        'label' => 'Add',
                        'controller' => 'translation',
                        'action'     => 'edit',
                        'resource' => 'admin_translation_edit',
                    ),
                )
            ),
        ),
);