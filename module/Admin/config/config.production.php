<?php
return array(
    'defaultSiteId' => 1,
	'cmsHost' => 'admin.mysite.com',
//     'editorPages' => array(
//         'tree-page',
//         'big-promotion',
//         'buying-advice',
//         'splash',
//         'product-pricerunner-review',
//         'mainsite-homepage',
//         'safe-buy',
//         'search-page',
//         'document'
//     ),
    
    'cacheHosts' => array (
			array (
				'ip' => '127.0.0.1',
				'domain' => 'www.mysite.com'
			)
	),
    'cmsWritableDir' => array (
			'base'            => '',
            'javascript'      => ROOT_PATH . '/public/scripts/',
            'images'          => ROOT_PATH . '/public/images/',
		),
		
	'cmsDefaultTimezone' => 'Asia/Shanghai',
);