<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2013 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Cron\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Application\Model\User;

class IndexController extends AbstractActionController
{
    public function indexAction()
    {
    	$userModel = new User();
    	var_dump($userModel->getUserInfoById(1));
    	exit;
    }
    
}

?>
