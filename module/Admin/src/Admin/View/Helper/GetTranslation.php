<?php
namespace Admin\View\Helper;

use Zend\View\Helper\AbstractHelper;

use Test\Data;
use Test\Util\Timer;

use Application\Service\Resource;

class GetTranslation extends AbstractHelper {
    
    /**
     * @var array
     */
    protected static $translations = array();

    /**
     * this helper returns the translation for the passed key
     * it will optionally add the controller
     * and action to the key
     * 
     * example: controller_action_page_title
     *
     * @return unknown
     */
    public function __invoke($key, $locale = null) {
        
        return $key;
    }
}
