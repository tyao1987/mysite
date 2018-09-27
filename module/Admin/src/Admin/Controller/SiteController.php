<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2014 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Admin\Controller;

use Zend\Mvc\MvcEvent;
use Zend\Session\Container;
use Zend\View\Model\ViewModel;
use Zend\Http\Request;
use Zend\Config\Reader\Xml;

use Test\Data;

use Application\Model\CategoryEtl;
use Application\Model\Category;
use Application\Model\SiteSettingTable ;
use Application\Model\ArticleCms;
use Application\Util\Util;
use Application\Service\Resource;
use Application\Service\Cache;

use Admin\Model\Sites;
use Admin\Model\ArticleDB;
use Admin\Model\DealCache;

class SiteController extends AbstractController
{

    //The column that update from CMSDB.sites
    private $_CSMDBColumnMapping = array(
    	'host_name' => 'hostname',
        'site_currency' => 'currency',
    	'site_name' => 'short_name',
    	'site_country' => 'country',
    	'site_language' => 'language',
    	'site_type' => 'site_type',
    	'site_display_name' => 'display_name',
    	'site_frontend_db' => 'frontend_db',
    	'site_backend_db' => 'backend_db',
    	'site_active' => 'isactive',
    );

    static $site = null ;

    public function preDispatch(MvcEvent $e)
    {
        /*$this->view->breadcrumbs = array(
            $this->view->GetTranslation('Site Settings') =>   '/admin/site'
        );*/

        if(is_null(self::$site)) {
            $data = Data::getInstance();
            $config = $data->get('config');
            self::$site = $data->get('site');
        }

        //这部分可以移到AbstractController中
        $viewHelper = $this->getServiceLocator()->get('viewhelpermanager');
        Util::setViewhelperManager($viewHelper);
    }

    /**
     *index
     */
    public function indexAction()
    {
        $dataFilePath = ROOT_PATH . '/module/Application' ;
        $adminDataFilePath = ROOT_PATH . '/module/Admin' ;

        $defaultSettings = require $dataFilePath .  '/data/site-settings/mainsite-default-settings.php';
        //if (self::$site['site_type'] == 'DistributionSite') {
            //$tmp = require $dataFilePath .  '/data/site-settings/whitelabel-default-settings.php';
            //$defaultSettings = array_merge($defaultSettings, $tmp);
        //}
        $options = require $adminDataFilePath .  '/data/options.config.php';
        
        $result = $options;
        
        $settings = new SiteSettingTable();
        $siteSettingsXML = $settings->getSiteSetting(self::$site['site_id']);
        $config = Data::getInstance()->get('config');
        if(!$siteSettingsXML){
        	$siteSettings = array();
        }
        else{
        	$siteSettings = (array)simplexml_load_string($siteSettingsXML);
        }
        // marge with default settings
        $result['settings'] = array_merge($defaultSettings, $siteSettings);
        $siteCms = new Sites();
        $siteData = $siteCms->getSiteByID(self::$site['site_id']);
        if($siteData){
        	$tmpArray = array_flip($this->_CSMDBColumnMapping);
        	$settingsTmp = array();
        	foreach ($siteData as $key => $val){
        		if (in_array($key, $this->_CSMDBColumnMapping)){
        			$keyTmp = $tmpArray[$key];
        			$settingsTmp[$keyTmp] = $val;
        		}
        	}
        	$result['settings'] = array_merge($result['settings'], $settingsTmp);
        	unset($settingsTmp);
        }else{
        	throw new \Exception("Site not found");
        }
        $enabled = array();
        foreach ($siteSettings as $key => $val) {
            $enabled[$key] = 1;
        }
        
        //unset($tmpArray);
        $result['enabled'] = $enabled;
        $GetTranslation = Util::getViewHelper('GetTranslation') ;

        $this->layout()->setVariable('toolbarLinks', array('Add to my bookmarks' => '/index/bookmark/url/site'));
        return new ViewModel( $result ) ;
    }

    /**
     *edit
     */
    public function editAction()
    {
        $settings = $this->getRequest()->getPost('setting');
        $enabled = $this->getRequest()->getPost('enabled');

        // 对于逗号分隔的设置，需要清除空格和空值
        $needCleanUpSettings = array(
        		
        );


        $siteId = self::$site['site_id'];
        $siteSetting = new SiteSettingTable();
        $siteSetting->setDefaultXml($siteId) ;

        $defaultMode = '';
        foreach ($settings as $k => $v) {

            if ($enabled[$k] && $enabled[$k] == 1) {

                if (in_array($k, $needCleanUpSettings)) {
                    $v = $this->_cleanCommaSeperatedSetting($v);
                }

                $siteSetting->set($k, $v);
            }
        }

        // remove empty key
        foreach ($enabled as  $k => $v) {
            if ($enabled[$k] == 0) {
                $siteSetting->remove($k);
            }
        }

        $dataTmp = $siteSetting->toArray();
        if(!empty($dataTmp)){
            $dataCms = array();     // the data to update CMSDB.sites
            foreach ($this->_CSMDBColumnMapping as $form => $cms) {
                if (isset($dataTmp[$form])) {
                    $dataCms[$cms] = $dataTmp[$form];
                }
            }
        }
        // update CMSDB.sites
        $siteCms = new Sites();
        $siteCms->beginTransaction();
        try {
            $id = (int)$siteId;
            if ($id) {
                if(!empty($dataCms)){
                    $siteCms->updateCmsDb($id, $dataCms);
                }
            }
            $siteCms->commit();
        } catch (\Exception $e) {
            $siteCms->rollback();
            throw new \Exception($e->getMessage());
        }
        unset($dataTmp);
        unset($dataCms);

        // update CMSDB.data , using the data of XML Content
        $siteSetting->save($siteId);
        //@TODO 生成缓存
        $dealCache = new DealCache() ;
        $dealCache->dealSiteSettingsBySite($siteId , self::$site['short_name']) ;

        //$cache = Cache::get("dynamicCache");
        //$cacheKey = Util::makeCacheKey('siteSetting');
        //$cache->removeItem($cacheKey);

        //$cache = Cache::get("constantCache");
        //$cacheKey = Util::makeCacheKey('variableDesc' , false);
        //$cache->removeItem($cacheKey);

        $this->_redirect('/site');
    }


    /**
     * mkdir dir
     * @param ArticleDB $articleDbModel
     * @param int $dir
     * @param string $categoryType
     * @throws \Exception
     */
    private function _checkDir($articleDbModel,$dir,$categoryType){
        if ($articleDbModel instanceof ArticleDB){

            $fileDir = $articleDbModel->fetchNextListByAid($dir,0,1,0,$categoryType,'name',true);
            if (empty($fileDir[0]['id'])) {
                $fileDir = $articleDbModel->add($categoryType, $dir);
            } else {
                $fileDir = $fileDir[0]['id'];
            }
            if (empty($fileDir)) {
                throw new \Exception( $categoryType . ' dir not found');
            }
            return $fileDir;
        }
    }

    /**
     * mkdir category dir
     * @param ArticleDB $articleDbModel
     * @param int $dir
     * @param string $cat
     * @throws \Exception
     */
    private function _checkCategoryDir($articleDbModel,$dir,$cat){
        if ($articleDbModel instanceof ArticleDB){
            // check category dir
            $categoryDir = $articleDbModel->fetchNextListByAid($dir,0,1,0,$cat,'name',true);
            if (empty($categoryDir[0]['id'])) {
                $categoryDir = $articleDbModel->add($cat, $dir);
            } else {
                $categoryDir = $categoryDir[0]['id'];
            }

            if (empty($categoryDir)) {
                throw new \Exception($dir.' dir not found');
            }
            return $categoryDir;
        }
    }

    /**
     * generate filters node.
     * @param ArticleDB $articleDbModel
     * @param int $categoryDir
     * @param array $selectedFilters
     * @throws \Exception
     */
    private function _checkFilterNode ($articleDbModel,$categoryDir,$selectedFilters){
        if ($articleDbModel instanceof ArticleDB){
            $filtersNode = $articleDbModel->fetchNextListByAid($categoryDir,0,1,0,'filters','name',true);
            if (empty($filtersNode[0]['id'])) {
                $filtersNode = $articleDbModel->add('filters', $categoryDir, 'FILE');
            } else {
                $filtersNode = $filtersNode[0]['id'];
            }

            if (empty($filtersNode)) {
                throw new \Exception('cannot add filters node');
            }

            $articleDbModel->updateContent($filtersNode, $selectedFilters, 0, 'filters');
        }
    }

    /**
     * generate add_noindex|add_canonical
     * @param ArticleDB $articleDbModel
     * @param int $categoryDir
     * @param string $fileType
     * @throws \Exception
     */
    private function _checkFileNode($articleDbModel,$categoryDir,$fileType){
        if ($articleDbModel instanceof ArticleDB){
            $addNoindexNode = $articleDbModel->fetchNextListByAid($categoryDir,0,1,0,$fileType,'name',true);
            if (empty($addNoindexNode[0]['id'])) {
                $addNoindexNode = $articleDbModel->add($fileType, $categoryDir, 'FILE');
            } else {
                $addNoindexNode = $addNoindexNode[0]['id'];
            }

            if (empty($addNoindexNode)) {
                throw new \Exception('cannot add '.$fileType.' node');
            }
            return $addNoindexNode;
        }
    }

    /**
     * generate categories and files
     * @param ArticleDB $articleDbModel
     * @param int $rootNode
     * @param int $cat
     * @param int $addTag
     * @param String $categoryDir
     * @param String $nodeName
     * @param array $filters
     */
    private function _generateNode($articleDbModel,$rootNode,$cat,$addTag,$categoryDir,$nodeName,$filters){
        $dir = $this->_checkDir($articleDbModel,$rootNode,$categoryDir);
        $catDir = $this->_checkCategoryDir($articleDbModel,$dir,$cat);
        $addNode = $this->_checkFileNode($articleDbModel, $catDir, $nodeName);
        $articleDbModel->updateContent($addNode, (string)$addTag, 0, $nodeName);
        $this->_checkFilterNode($articleDbModel, $catDir, $filters);
    }

    /**
     * format filters.
     * @param array $Filters
     */
    private function _formatFilters($Filters = array()){
        $selected = array();
        foreach ($Filters as $filterId => $filterName) {
            if ($filterName) {
                $selected[] = $filterId . '|||' . $filterName;
            }
        }
        return implode("\n", $selected);
    }


    protected function _cleanCommaSeperatedSetting($str) {
        return implode(',', array_filter(array_map('trim', explode(',', $str))));
    }

    /**
     * get filters and format it.
     * @param array $filterSettings
     */
    private function _fetchFormatFilters($filterSettings = array()){
        $filters = (string)$filterSettings['filters'];
        $selectedFilters = array();
        if ($filters) {
            $filters = explode("\n", $filters);
            foreach ($filters as $filter) {
                $parts = explode('|||', $filter);
                if ($parts) {
                    $selectedFilters[] = trim($parts[0]);
                }
            }
            return $selectedFilters;
        }
        return $selectedFilters;
    }

    public function postDispatch(MvcEvent $e)
    {
        parent::postDispatch($e) ;
    }
}
