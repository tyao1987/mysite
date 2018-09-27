<?php
namespace Admin\View\Helper;
 
use Admin\Util\Util;

use Zend\View\Helper\AbstractHelper;


class CleanUri extends AbstractHelper
{
     
    public function __invoke($uri = null, $absolute = false, $stripUnderscores = false)
    {
        if($absolute && !empty($uri)){
	        $uri = '/' . $uri;
	    }
	    
	    if($stripUnderscores){
	        $uri = Util::stripUnderscores($uri, true);
	    }
        return  Util::addHyphens($uri);
    }
}