<?php
namespace Admin\View\Helper;

use Zend\View\Helper\AbstractHelper;
use Test\Data;
use Admin\Model\User;
class RenderOptions extends AbstractHelper
{
    public $optionsPath;
    public $userModel;
    public $resources;
    private $pages;
    /**
     * this helper renders the admin options.
     * 
     * you can add content before the body by setting options_before placeholder
     * you can add content after the body by setting options_after placeholder
     *
     * @param unknown_type $selectedItem
     * @param unknown_type $id
     * @return unknown
     */
	public function __invoke()
	{
	    $pages = include ROOT_PATH.'/module/Admin/config/page.config.php';
	    $this->pages = $pages['default'];
	    $this->userModel = new User();
	    $this->setResource();
        $this->setOptionsPath();
        
        //render the column first so you can set the headline pla
        $column = $this->renderBody();
   
        return $column;
	}
	
	public function renderBody()
	{
	    $xhtml = "";
	    $xhtml .= $this->view->partial($this->optionsPath, 
	        array('resources' => $this->getResource(),
	              'userModel' => $this->userModel,
	              'pages' => $this->pages,
	    ));
        $xhtml .= "";
        
	    return $xhtml;
	}
	
	public function setOptionsPath()
	{
	    $data = Data::getInstance();
	    $controller = $data->get('controller');
	    $action = $data->get('action');
	    $this->optionsPath = 'partial/default.options.phtml';
// 	    $this->optionsPath = 'admin/' . strtolower($controller) . '/' . strtolower($action) . '.options.phtml';
	}
	public function setResource() {
		$this->resources = $this->userModel->getCurrentUsersAclResources();
	}
	public function getResource() {
		if (!$this->resources) {
			$this->setResource();
		}
		return $this->resources;
	}
}