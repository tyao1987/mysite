<?php

namespace Application\View\Helper;

use Zend\View\Helper\AbstractHelper;

use Test\Data;
use Test\Util\Uri;
use Test\Util\Timer;

class GetUrl extends AbstractHelper {

    /**
     * URL helper
     *
     * @param $name string
     *            router name
     * @param $userParams array           
     * @param $query array|string           
     * @param $anchor string           
     * @return string
     */
    public function __invoke($name = 'default', array $userParams = array(), $query = array(), $anchor = '', $encode = true) {
        
    	Timer::start(__METHOD__);
        
        if (!empty($userParams)) {
            
            $tmp = array();
            
            // replace variables
            foreach ($userParams as $key => $value) {
            
                // special logic
                $value = $this->FormatString($value);
                
                $tmp[$key] = urldecode($value);
            }
            
            $userParams = $tmp;
        }
        
        $keyUrl = $this->view->url($name, $userParams);
    	
        // replace // to /
        $keyUrl = preg_replace('/[\/]{2,}/', '/', $keyUrl);
        
        // replace -- to -
        $keyUrl = preg_replace('/[\-]{2,}/', '-', $keyUrl);
        
        // add queryString
        if (is_array($query)) {
            $queryString = Uri::makeUriFromArray($query, $encode);
        } else {
            $queryString = (string)$query;
        }
        
        $url = $keyUrl;
        
        if ($queryString != '') {
            $url .= '?' . $queryString;
        }
        
        if ($anchor != '') {
            $url .= '#' . $anchor;
        }
        
        Timer::end(__METHOD__);
  		
        return $url;
    }

    public function FormatString($value) {
        return Uri::formatString($value, true);
    }
}
