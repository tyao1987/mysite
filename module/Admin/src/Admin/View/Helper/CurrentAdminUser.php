<?php
namespace Admin\View\Helper;

use Zend\View\Helper\AbstractHelper;
use Admin\Model\Auth;
use Zend\Authentication\Storage\Session as sessionStorage;
class CurrentAdminUser extends AbstractHelper
{
	public function __invoke($id = 'currentUser'){
		//$u = new User();
		//$user = $u->getCurrentUser();
// 		$storage = Auth::getBaseInfoStorageInstance();
// 		$user = $storage->read();
		$user = Auth::getIdentity();
		if($user)
		{
			$sites = $user['sites'];

			$s = new sessionStorage('cmscountry','cms_site_id');
			$currentSite = $s->read();
			$options = array();
			foreach ($sites as $site) {
				$options[$site['site_id']] = $site['short_name']." - ".$site['hostname'];
			}
			uasort($options, "strnatcmp");

            $element = new \Zend\Form\Element\Select('select_site');
            $element->setValue($currentSite);
            $element->setValueOptions($options);
            $element->setAttribute('class', 'form-control');
            $element->setAttribute('id', 'select_site');
			$select = $this->view->FormSelect($element);

			// remove role line, because user has multi roles q
			//<li>" . $this->view->GetTranslation('Role') . ": {$user->role}</li>
			$xhtml = "<a href='/auth/logout' class='navbar-text navbar-right navbar-link'>
			    Log Out</a>
			         <p class='navbar-text navbar-right'>Current User: {$user['first_name']}  {$user['last_name']}</p>
			          <form class='navbar-form navbar-right'>{$select}
			          </form><p class='navbar-text navbar-right'>Change Site:</p>
				<script type='text/javascript'>
				$('select#select_site').change(function(){
				    window.location = '/?site_id='+ this.value;
				    return false;
				});
				</script>
				";
			return $xhtml;
		}else{
		    return false;
		}
	}
}
