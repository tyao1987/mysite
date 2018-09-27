<?php
namespace Application\View\Helper;

use Zend\View\Helper\AbstractHelper;
use Test\Util\Timer;
use Application\Widget\Handler;

class ParseContent extends AbstractHelper {

    /**
     * ParseContent helper
     *
     * @param  string $namerouter name
     * @param array $userParams
     * @param array | string $query
     * @param $anchor string           
     * @return string
     */
    public function __invoke($content = '') {
        Timer::start(__METHOD__);
        if (trim($content) == '') {
            Timer::end(__METHOD__);
            return $content;
        }
        
        $obj = new Handler($content, $this->view);
        $content = $obj->getContent();
        
        Timer::end(__METHOD__);
        
        return $content;
    }
}
