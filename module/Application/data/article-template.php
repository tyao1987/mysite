<?php

return array(
    "document" => array(
        "main" => "/{mainsite_shortname}/document/{title}", 
        "type" => "file", 
        "method" => "priority"
    ), 
    
//     "mainsite-homepage" => array(
//         "main" => "/{mainsite_shortname}/mainsite-homepage/mainContent", 
//         "type" => "file", 
//         "method" => "priority"
//     ), 
//     "mainsite-header" => array(
//         "main" => "/{mainsite_shortname}/mainsite-header/{title}", 
//         "type" => "file", 
//         "method" => "priority"
//     ), 
//     "mainsite-footer" => array(
//         "main" => "/{mainsite_shortname}/mainsite-footer/{title}", 
//         "type" => "file", 
//         "method" => "priority"
//     ), 
    "seo-template" => array(
        "main-default" => "/{mainsite_shortname}/seo-template/default/0/", 
        "main-page" => "/{mainsite_shortname}/seo-template/{pagetype}/0/",
    	"main-id" => "/{mainsite_shortname}/seo-template/{pagetype}/{id}/",
        "type" => "directory", 
        "method" => "cover"
    ), 
   	 
    "css" => array(
        "global" => "/css-global/{skin}/", 
        "type" => "directory", 
        "method" => "cover"
    ), 
    "css-override" => array(
        //"wl" => "/distribution/{distributionsite_shortname}/css-override/", 
        "main" => "/{mainsite_shortname}/css-override/", 
        "type" => "directory", 
        "method" => "priority"
    ), 
    "custom-style" => array(
        "custom" => "/css-global/custom/{page_type}", 
        "type" => "file", 
        "method" => "priority"
    ),
	
);
