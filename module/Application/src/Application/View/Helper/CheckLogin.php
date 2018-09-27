<?php
namespace Application\View\Helper;

use Zend\View\Helper\AbstractHelper;
use Application\Model\Utilities;

class CheckLogin extends AbstractHelper {
    
    public function __invoke() {
        return Utilities::checkLogin();
    }
}
