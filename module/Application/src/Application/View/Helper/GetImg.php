<?php
namespace Application\View\Helper;

use Zend\View\Helper\AbstractHelper;
use Application\Util\Util;

class GetImg extends AbstractHelper {

    /**
     *
     * @param $string unknown_type           
     * @return string
     */
    public function __invoke($string) {
        return Util::getImg($string);
    }

}