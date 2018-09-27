<?php
namespace Admin\View\Helper;

use Zend\View\Helper\AbstractHelper;
use Admin\Model\User;
use Test\Data;
class RenderAdminMenu extends AbstractHelper {
	public $sections = array ('index' => 'index', 'site' => 'site', 'report' => 'site', 'user' => 'site',
	      'article'=>'article', 'acl' => 'acl', 'memcache' => 'memcache', 'translation' => 'translation',
	     'uploadimage' => 'uploadimage', 'member' => 'member', 'voucher' => 'voucher',
	     'popularsearches' => 'popularsearches', 'suggestionlog' => 'suggestionlog',
	);
	private $items = array(
	    "acl" => "<a href='/acl' class='acl'>Acl</a>",
	    "memcache" => "<a href='/memcache' class='memcache'>Memcache</a>",
	    "translation" => "<a href='/translation/list' class='translation'>Translation</a>",
	    "uploadimage" => "<a href='/uploadimage/admin' class='uploadimage'>Uploadimage</a>",
	    "member" => "<a href='/member/list' class='member'>Member</a>",
	    "voucher" => "<a href='/voucher' class='voucher'>Voucher</a>",
	    "popularsearches" => "<a href='/popularsearches/keyword-list' class='popularsearches'>Popularsearches</a>",
	    "suggestionlog" => "<a href='/suggestionlog' class='suggestionlog'>Suggestionlog</a>",
	);
	private $resources = NULL;
	public $defaultSection = 'index';
	public $selectedSection;
	/**
	 * @var \Admin\Model\User
	 */
	public $userModel;
	public $currentUser;

	public function __invoke($selectedItem = null, $id = 'adminMenu') {
		$this->userModel = new User();
		$this->currentUser = $this->userModel->getCurrentUser();
		$this->setResource();
		$this->setSelectedSection();

		if ( empty($selectedItem) )
			$selectedItem = 'index';

		$menu = "<ul class='nav navbar-nav navbar-left' id='{$id}'>";

		if (! $this->currentUser) {
		    $menu .= "<li class='active'><a href='/auth/login' id='loginLink'>Login</a></li>";
		} else {
			if ($this->hasAccess ( 'admin_index' )) {
				$menu .= "<li" . ($this->isSelected ( 'index' ) ? " class='active'" : "") . "><a href='/' id='homeLink'>Home</a></li>";
			}

			if ($this->hasAccess ( 'admin_site' )) {
				$menu .= "<li" . ($this->isSelected ( 'site' ) ? " class='active'" : "") . "><a href='/site' id='siteLink'>Site</a></li>";
			}
			
			if ($this->hasAccessModule()) {
			    $subMenu = array_keys($this->items);
			    $selected = $this->isSelected ( $subMenu ) ? true : false;
				$menu .= "<li" . ($selected ? " class='dropdown active'" : " class='dropdown'") . "><a href='javascript:;' id='moduleLink'  class='dropdown-toggle' data-toggle='dropdown'>Modules<span class='caret'></span></a>";
				$menu .= $this->view->htmllist($this->getShowItems(), false, array('class' => 'dropdown-menu', 'role' => 'menu'), false);
				$menu .= "</li>";
			}

            // RootUser(id==1) is not need to see articles and stylesheet.
			if ($this->hasAccess ( 'admin_article' ) && !$this->userModel->isCurrentRootUser() ) {
				$menu .= "<li" . ($this->isSelected ( 'article' ) ? " class='active'" : "") . "><a href='/article' id='articleLink'>Articles</a></li>";
			}
		}

		$menu .= "</ul>";

		return $menu;
	}

	public function isSelected($tab) {
	    if (is_array($tab)) {
	    	return in_array($this->selectedSection, $tab);
	    } elseif ($tab == $this->selectedSection) {
		    return true;
// 			return " class='active'";
		}
		return false;
	}

	public function setSelectedSection() {
		$data = Data::getInstance();
		$controller = $data->get('controller');
		$action = $data->get('action');
		if (isset ( $this->sections [$controller] )) {
			$this->selectedSection = $this->sections [$controller];
		} else {
			$this->selectedSection = $this->defaultSection;
		}
	}
    public function hasAccessModule() {
        $flg = 0;
        if ($this->hasAccessAll()) return 1;
        $items = array_keys($this->items);
        foreach ($items as $v) {
        	$flg = $flg || $this->userModel->queryPermissions ( 'admin_'.$v );
        }
        return $flg;
    }
    public function getShowItems() {
        $items = $this->items;
        if ($this->hasAccessAll()) return $this->items;
        $resources = array_keys($items);
        foreach ($resources as $v) {
        	if (!$this->userModel->queryPermissions('admin_'.$v)) {
        		unset($items[$v]);
        	}
        }
        return $items;
    }
	public function hasAccess($tab) {
		if ($this->currentUser) {
			if ($this->currentUser->id == User::SUPERUSER_ROLE) {
				return true;
			} elseif ($this->hasAccessAll() || $this->userModel->queryPermissions ( $tab )) {
				return true;
			}
		}
	}
	public function hasAccessAll() {
	    if ($this->resources == NULL) {
	    	$this->setResource();
	    }
	    $access = array_key_exists('admin', $this->resources) && $this->resources['admin'] == 1;
	    return $access;
	}
	public function setResource() {
	    $this->resources = $this->userModel->getCurrentUsersAclResources();
	}

}
