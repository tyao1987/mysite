<?php
namespace Admin\Controller;

use Admin\Model\Article;
use Admin\Model\Sites;
use Admin\Model\Auth;
use Zend\Mvc\MvcEvent;
use Zend\Paginator\Paginator;
use Zend\View\Model\ViewModel;
use Zend\Form\Form;
use Test\Data;
use Zend\Stdlib\ArrayObject;
use Admin\Model\User;
class ArticleController extends AbstractController
{
    protected $base_aid = null;
    protected $aid = null;
    protected $article = null;
    public function preDispatch(MvcEvent $e) {
        parent::preDispatch($e);

        $article = $this->article = new Article();
        $siteaid = $article->getAidBySite();
        $aid = $siteaid['id'];

        $sites = new Sites();
        $site_count = $sites->getSiteCounts();

        $storage = Auth::getBaseInfoStorageInstance();
        $user = $storage->read();
        if (count($user['sites']) == $site_count->counts || $user['id'] == User::SUPERUSER_ROLE) {
            $aid = 1;
        }
        $this->base_aid = $aid;

        $this->aid = isset($this->params['aid']) ? $this->params['aid'] : 0;
    }
	public function indexAction() {
		
	    $params['aid'] = $this->aid;
	    
	    $articleModel = $this->article;

	    $params['sort'] = $this->params['sort'] ? $this->params['sort'] : 'name';


	    $params['ishide'] = intval($this->params['ishide'], 0);
	    $params['page'] = $this->params['page'] ? $this->params['page'] : 0;

	    $params['name'] = $this->params['name'] ? $this->params['name'] : '';

	    $params['offset'] = $this->params['offset'] ? $this->params['offset'] : 0;
	    $params['perpage'] = $this->params['perpage'] ? $this->params['perpage'] : 20;

	    if(empty($params['aid'])){
	        $params['aid'] = $this->base_aid;
	    }

	    if(!$this->checkPemission($params['aid'])) return;

	    $paginator = new Paginator($articleModel->getListToPaginator($params));
	    $paginator->setCurrentPageNumber($params['page'])->setItemCountPerPage($params['perpage']);
        $lists = $paginator->getCurrentItems()->toArray();

        $assign = array(
        	'lists' => $lists,
            'paginator' => $paginator,
            'aid' => $params['aid'],
        );
        $this->layout()->setVariable('path', $articleModel->getBreadCrumbPathByAid($params['aid']));
        return new ViewModel($assign);
	}
	/**
	 * 检查用户有没有操作该节点的权限。如果没有。返回false
	 */
	private function checkPemission($aid){
	    $articleModel = $this->article;
	    if($articleModel->checkPemission($aid, $this->base_aid)){
	    	return true;
	    }else{
	        return false;
	    }
	}
	public function addAction() {
	    //post action
	    $this->editHandle();

	    //type   file or directory
	    $type = $this->params()->fromQuery('type');
	    $assign['type'] = $type;


	    $assign['aid'] = $this->aid;
	    $assign['isCreate'] = 1;

	    $assign['action'] = 'add';

// 	    if($this->getSiteName() != ''){
// 	        $assign['isEditStaticContent'] = true;
// 	        $siteName = $this->getSiteName();
// 	        $assign['siteName'] = $siteName;
// 	    }else{
// 	        $assign['isEditStaticContent'] = false;
// 	    }
	    $assign['form'] = $this->_setForm($assign);
	    $v = new ViewModel($assign);
	    $v->setTemplate('admin/article/edit');
	    $path = $this->article->getBreadCrumbPathByAid($assign['aid']);
	    $last = current(array_slice($path, -1, 1));
	    if ($last) {
	        $child = clone $last;
	    } else {
	    	$chlid = new ArrayObject();
	    }
	    $child->name = 'Add';
	    $child->level += 1;
	    $path[] = $child;
	    $this->layout()->setVariable('path', $path);
	    return $v;
	}
	
	private function _setForm($var) {
	    $row = $var['row'];
	    $referer = $this->params()->fromQuery('referer', $_SERVER['HTTP_REFERER']);
	    $form = new Form();
	    $form->setAttribute('action', $var['isCreate'] ? '/article/add' : '/article/edit');
	    $form->setAttribute('method', 'post');
	    $form->add(array(
	        'name' => 'isCreate',
	        'attributes' => array(
	            'value' => $var['isCreate'],
	        )
	    ));
	    $form->add(array(
	        'name' => 'aid',
	        'attributes' => array(
	            'value' => $var['aid'],
	        )
	    ));
	    $form->add(array(
	        'name' => 'action',
	        'attributes' => array(
	            'value' => $var['action'],
	        )
	    ));
	    $form->add(array(
	        'name' => 'type',
	        'attributes' => array(
	            'value' => $var['type'],
	        )
	    ));
	    $form->add(array(
	        'name' => 'referer',
	        'attributes' => array(
	            'value' => $referer,
	        )
	    ));
	    $form->add( array (
	        'name' => 'name',
	        'attributes' => array(
	            'class' => 'form-control',
	            'placeholder' => 'name',
	            'value' => $row['name'],
	            'readonly' => $var['type'] == 'VERSION' ? true : false
	        )
	    ));
	    $form->add( array (
	        'name' => 'description',
	        'attributes' => array(
	            'class' => 'form-control',
	            'placeholder' => 'description',
	            'value' => $row['description'],
	            'readonly' => $var['type'] == 'VERSION' ? true : false
	        )
	    ));
	    if ($var['type'] == 'FILE' || $var['type'] == 'VERSION') {
	        $form->add( array (
	            'name' => 'ishide',
	            'type' => 'Zend\Form\Element\Checkbox',
	            'options' => array(
	        	    'value' => $row['ishide'] ? true : false,
	            ),
	            'attributes' => array(
	                'checked' => $row['ishide'] ? true : false,
	            )
	        ));
	        $form->add(array(
	        	'name' => 'content',
	            'type' => 'Zend\Form\Element\Textarea',
	            'options' => array(
	            ),
	            'attributes' => array(
	                'cols' => 60,
	                'rows' => 10,
	                'class' => 'form-control ckeditor',
	                'value' => $row['content'],
	                'readonly' => $var['type'] == 'VERSION' ? true : false
	            )
	        ));
	    }
	    return $form;
	}
	public function editAction() {

	    $this->editHandle();
	    $assign = array();
        $type = $this->params['type'];
	    $isnewversion = $this->params['isnewversion'] ? $this->params['isnewversion'] : 0;


		$aid = $this->aid;
		$isCreate = 1;

		if(array_key_exists('aid', $this->params) || array_key_exists('vid', $this->params)){
			$aid = $this->params['aid'];
			$vid = $this->params['vid'];
			$isCreate = 0;

			$articleModel = $this->article;

			if($type == 'VERSION'){
				$row = $articleModel->getContentByVersion($vid);
			}else{
				$row = $articleModel->getContentByAid($aid);
			}


		}
		
		$assign = array(
			'isCreate' => $isCreate,
		    'isnewversion' => $isnewversion,
		    'row' => $row,
		    'aid' => $aid,
		    'type' => $type,
		    'action' => 'edit',
		);

// 		if($this->getSiteName() != ''){
// 			$assign['isEditStaticContent'] = true;
// 			$siteName = $this->getSiteName();
// 			$assign['siteName'] = $siteName;
// 			if(!$this->checkTagIsAligned($row->content)){
// 				$assign['editSuggestion'] = true;
// 			}
// 		}else{
// 			$assign['isEditStaticContent'] = false;
// 		}
        $assign['form'] = $this->_setForm($assign);
        $this->layout()->setVariable('path', $this->article->getBreadCrumbPathByAid($aid));
		return new ViewModel($assign);
	}
	
	public function editHandle() {
	    if($this->request->isPost()){

	        $content = $this->params()->fromPost('content');
	        $content = preg_replace('/"(?:\{C\})+\<\!--/i', '"<!--', $content);

	        $aid = $this->params()->fromPost('aid');

	        $referer = $this->params()->fromPost('referer');
// 	        $this->view->referer = $referer;

	        $action = $this->params()->fromPost('action');
	        $type = $this->params()->fromPost('type');

	        $description = $this->params()->fromPost('description');

	        $articleModel = $this->article;

	        $ishide = $this->params()->fromPost('ishide');
	        $ishide = $ishide?1:0;

	        $name = $this->params()->fromPost('name');

	        if(!$this->checkPemission($aid)) return;

	        /**
	         * update content
	         */
	        if($type == 'FILE') {
	            $parentid = $aid;
	            if($action == 'add'){
	                $aid = $articleModel->add($name,$aid,'FILE',$description,$ishide);
	            }
	            if($action == 'edit'){
	                $array['name'] = $name;
	                $array['description'] = $description;
	                $array['ishide'] = $ishide;
	                $aid = $articleModel->updateNode($aid, $array, $type);
	            }
	            //增加错误
	            if(!$aid){
	                //错误信息
	                $this->_message("the name is exist or the name format is not -_0-9a-zA-Z.", self::MSG_ERROR);
	                $url = $action == 'add' ? "/article/add?aid=".$parentid."&type=FILE&referer=".urlencode($referer)
	                     : "/article/edit?aid=".$parentid."&type=FILE&referer=".urlencode($referer);
	                return $this->redirect()->toUrl($url);
	            } else {
	                $flg = $articleModel->updateContent($aid,$content,$ishide,$name);

	                //如果是更新或者新增内容，则返回上层aid
	                $path = $articleModel->getPathByAid($aid);
	                array_pop($path);
	                $lastaid = array_pop($path);
	                $returnaid = $lastaid->id;

	                return $this->redirect()->toUrl("/article?aid=".$returnaid);
	            }
	        } else {
	            if($action == 'add'){
	                $thisaid = $articleModel->add($_POST['name'],$aid,'DIRECTORY',$description,$ishide);
	                if(!$thisaid){
	                    //错误信息
	                    $this->_message("the name is exist or the name format is not -_0-9a-zA-Z.%", self::MSG_ERROR);
	                    return $this->redirect()->toUrl("/article/add?aid=".$aid."&type=DIRECTORY&referer=".urlencode($referer));
	                }
	                $returnaid = $aid;

	                return $this->redirect()->toUrl("/article?aid=".$returnaid);
	            }

	            if($action == 'edit'){

	                $array['name'] = $name;
	                $array['description'] = $description;
	                $thisaid = $articleModel->updateNode($aid,$array,$type);


	                if(!$thisaid){
	                    //错误信息
	                    $this->_message("the name is exist or the name format is not -_0-9a-zA-Z.%", self::MSG_ERROR);
	                    return $this->redirect()->toUrl("/article/edit?aid=".$aid."&type={$type}&referer=".urlencode($referer));
	                }

	                $path = $articleModel->getPathByAid($aid);
	                array_pop($path);
	                $lastaid = array_pop($path);
	                $returnaid = $lastaid->id;

	                return $this->redirect()->toUrl("/article?aid=".$returnaid);
	            }
	        }
	    }
	}
	public function versionAction(){
	    $articleModel = $this->article;
	    $aid = $this->params()->fromQuery('aid');
	    
	    if(!$this->checkPemission($aid)) return;
	    
	    $history = $articleModel->getContentHistoryByAid($aid);
	    
	    $path = $articleModel->getPathByAid($aid);
	    $thiscontent = array_pop($path);
		
	    $assign = array(
	    	'path' => $path,
	        'name' => $thiscontent,
	        'data' => $history
	    );
	    $this->layout()->setVariable('path', $articleModel->getBreadCrumbPathByAid($aid));
	    return new ViewModel($assign);
	}
	/**
	 * delete node action
	 */
	public function deleteAction() {
	    $aid = $this->params()->fromQuery('aid');

	    //init article model
	    $articleModel = $this->article;

	    /**
	     * get father aid,for return url
	     */
	    $path = $articleModel->getPathByAid($aid);
	    array_pop($path);
	    $father = array_pop($path);
	    $fatheraid = $father->id;


	    $articleModel->deleteContent($aid);

	    return $this->redirect()->toUrl("/article?aid=".$fatheraid);
	}
	public function activeAction() {
	    $aid = $this->params()->fromQuery('aid');
	    $ishide = $this->params()->fromQuery('ishide');
	    $type = $this->params()->fromQuery('type');
	    //init article model
	    $articleModel = $this->article;

	    $returnid = $articleModel->trunActive($aid,$ishide,$type);


	    return $this->redirect()->toUrl("/article?aid=".$returnid);
	}
	public function makecurrentAction(){
	    $vid = $this->params()->fromQuery('vid');

	    $articleModel = $this->article;
	    $returnid = $articleModel->makeCurrent($vid);
	    return $this->redirect()->toUrl('/article/version?aid='.$returnid);

	}
	function getPagesWithEditor(){
	    $articleModel = $this->article;
	    $nodePath = $articleModel->getPathByAid($this->aid);
	    $data = Data::getInstance();
	    $config = $data->get('config');
	    $editorPages = $config['editorPages'];

	    if($nodePath[0]->level == 3 && in_array($nodePath[0]->name, $editorPages)){
	        return $nodePath[0];
	    }else if($nodePath[1]->level == 3 && in_array($nodePath[1]->name , $editorPages)){
	        return $nodePath[0];
	    }else{
	        return "";
	    }
	}
	
	function getSiteName(){
	    $nodePath = $this->getPagesWithEditor();
	    if(is_object($nodePath) && $nodePath != ''){
	        return $nodePath->name;
	    }
	    else{
	        return '';
	    }
	}
	
	function checkTagIsAligned($content){
	    if(!empty($content)){
	        $pregTag = array('div'=>array('/<div\s*[^<>]*>/i','/<\s*\/div>/i'));
	        foreach ($pregTag as $tag){
	            preg_match_all("$tag[0]", $content, $matches);
	            preg_match_all("$tag[1]", $content, $endMatches);
	            if (count($matches[0]) != count($endMatches[0])){
	                return false;
	            }
	        }
	        return true;
	    }
	    return true;
	}
}