<?php 
namespace Admin\Controller;

use Admin\Model\Translation;
use Zend\View\Model\ViewModel;
use Zend\InputFilter\InputFilter;
use Zend\InputFilter\Input;
use Zend\Validator;
use Test\Data;
use Admin\Util\Util;
use Application;

class TranslationController extends AbstractController{
	
	public function __construct(){
		
		$config = \Test\Data::getInstance()->get("config");
		$this->languageMapping = $config['languageMapping'];
		$stickTemp = $this->languageMapping['zh_CN'];
		unset($this->languageMapping['zh_CN']);
		array_unshift($this->languageMapping, $stickTemp);
		$this->languageMapping = array_combine($this->languageMapping, $this->languageMapping);
	}
	
	/**
	 *  translations  list 
	 * 
	 * @return \Zend\View\Model\ViewModel
	 */
	public function listAction(){
		
		try {
			$param = $this->params()->fromQuery();
		    $translation = new Translation();
		    
		    $paginator = $translation->paginator ( $param );
		    $paginator->setCurrentPageNumber ( ( int ) $param ['page'] );
		    if(empty($param['perpage'])){
				$param['perpage'] = 20; 
			}
			$paginator->setItemCountPerPage ( $param['perpage'] );
		    $viewData['languages'] = $this->languageMapping;
		    $viewData['paginator'] = $paginator;
		    $viewData = array_merge($viewData,$param);
		}catch (\Exception $e){
			$viewData ['error'] = $e->getMessage();
		}
	    return new ViewModel($viewData);
	}
	
	/**
	 * translation  edit
	 * 
	 */
	public function editAction(){
		
		$id = $this->params()->fromRoute("id",'');
		 
		$viewData['languages'] = $this->languageMapping;
		$viewData['id'] = $id;
		
		if($this->getRequest()->isPost()){
			
			$inputFilter = $this->getFilters();
			$postData = $this->params()->fromPost();
			$inputFilter->setData($postData);
			
			if($inputFilter->isValid()){
			
				$id = (int)$this->params()->fromPost("id","");
				$data = $inputFilter->getValues();
			
				$data['lang']  = trim($data['lang']);
				unset($data['search']);
				$translation = new Translation();
				$updateError = false;
				$viewData = array_merge($viewData,$data);
				
				foreach ($this->languageMapping as $l){
					$data[$l] = $this->params()->fromPost($l);
				}
				if($id){
					unset($data['id']);
					try {
						$translation->updateTranslationById($id, $data);
					}catch (\Exception $e){
						$updateError =  true;
						$viewData['error'] = $e->getMessage();
					}
				}
				else{
					$getTranslationByLang = $translation->getTranslationByLang($data['lang']);
					$getTranslationByLang = array_filter($getTranslationByLang);
					if(empty($getTranslationByLang)){
						$translation->insert($data);
					}else{
						$updateError =  true;
						 $viewData['error'] = "Key already exists!!";
					}
				}
				if(!$updateError){
					$config = Data::getInstance()->get('config');
					$languages = $config['languageMapping'];
					foreach($languages as $localeName => $language){
						$data = $translation->getLang($language);
						if (!empty($data)) {
							$tmp = array();
							foreach ($data as $lang){
								$tmp[$lang['lang']] = $lang[$language];
							}
							$data = $tmp;
							$cacheFile = \Application\Util\Util :: getWritableDir('dataCache') . '/language/' . $language . '.php';
							Util::safe_file_put_contents($cacheFile, '<?php return ' . var_export($data, true) . ';');
						}
					}
					$cache = \Application\Service\Cache::get('constantCache');
					$cacheKey = Application\Util\Util::makeCacheKey('TRANSLATE_OBJ',false);
					$cache->removeItem($cacheKey);
					
					$this->redirect()->toUrl("/translation/list") ;
				}
			}
			else {
				$viewData['error'] = $inputFilter->getMessages();
				$viewData['close_udf_error']  = true;
			}
		}
		
		if($id){
			$translation = new Translation();
			$rowTranslation = $translation ->getTranslationById($id);
			$viewData = array_merge($viewData,$rowTranslation);
		}
		
		return new ViewModel($viewData);
	}
	
	/**
	 *  Filter input
	 * @return \Zend\InputFilter\InputFilter
	 */
	public function getFilters(){
		
		$filter = new InputFilter();
		$lang = new Input('lang');
		$lang->getValidatorChain()
		->addValidator(new Validator\NotEmpty());
		$filter->add($lang);	

	    return $filter;
	}
	
	/**
	 * Delete action
	 * 
	 */
	public function deleteAction(){
		
		$id = (int)$this->params()->fromRoute("id","");
		
		if($id){
			$translation = new Translation();
			$translation->deleteTranslationById($id);
		}
		
		return $this->redirect ()->toRoute ( 'default', array (
				'controller' => 'translation',
				'action' => 'list'
		) );
	}
}





?>