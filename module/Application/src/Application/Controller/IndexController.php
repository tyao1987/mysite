<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2013 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Application\Controller;

use Zend\View\Model\ViewModel;
use Test\Data;
use Zend\Mvc\MvcEvent;
use Zend\Session\Container;
use Application\Service\Cache;
use Application\Util\Util;

class IndexController extends AbstractController
{
    public function indexAction()
    {
     	return new ViewModel();
    }
    
}

?>
