<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2014 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Admin\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

use Test\Data;

use Admin\Model\AdminLog;
use Admin\Model\Auth;
use Admin\Model\Bookmark;
use Admin\Model\Message;
use Admin\Model\Note;
use Admin\Model\Sites;
use Admin\Model\User;
use Admin\Util\Post;


class IndexController extends AbstractController
{
    public function indexAction()
    {
    	$viewData = array();
    	$notes = new Note();
    	$viewData ['notes'] = $notes->getUsersNotes();
    	$bookmark = new Bookmark();
    	$viewData ['bookmarks'] = $bookmark->getUsersBookmarks();

    	return new ViewModel ( $viewData );
    }

    function notesAction()
    {
    	$notes = new Note();
        $myNotes = Post::get('content');
    	$notes->saveUsersNotes($myNotes);
    	return $this->redirect()->toUrl('/');
    }

    function logAction()
    {
    	$user = Auth::getIdentity();
    	if ($user['id'] != User::SUPERUSER_ROLE) {
    		//throw new \Exception('action not found');
    		return $this->redirect()->toUrl('/');
    	}

    	$param = $this->params ()->fromQuery ();

    	$adminLog = new AdminLog();
    	$paginator = $adminLog->paginator ( $param );
		$paginator->setCurrentPageNumber ( ( int ) $param ['page'] );
		if(empty($param['perpage'])){
			$param['perpage'] = 20; 
		}
		$paginator->setItemCountPerPage ( $param['perpage'] );

		$user = new User();
		$users = $user->getUsersPairs();
		$users[0] = 'All';
		ksort($users);

		$site = new Sites();
		$sites = $site->getSiteShortNameList();
		$sites[0] = 'All';
		ksort($sites);

		$viewData ['paginator'] = $paginator;
		$viewData = array_merge ( $viewData, $param ,array('users'=>$users,'sites'=>$sites) );

		return new ViewModel ( $viewData );

    }

    function bookmarkAction()
    {
    	$url = $this->params()->fromRoute("url", '');
    	$label = $this->params()->fromRoute("label", '');
    	$bookmark = new Bookmark();
    	$bookmark->addUsersBookmark($label, $url);
    }
    function deleteBookmarkAction()
    {
        $id = $this->params()->fromRoute('id');
        $bookmark = new Bookmark();
        $bookmark->deleteBookmark($id);
        $this->_redirect('/index');
    }

}
