<?php
namespace Admin\Model;

use Test\Data;
use Test\Util\Common ;
use Application\Model\DbTable;
use Application\Model\SiteSettingTable;
use Application\Model\TranslationTable;

use Admin\Model\Sites;
use Admin\Model\User;

class DealCache extends DbTable
{
	protected $_name = '';
	protected $_primary = '';
	
	protected $config = null ;
	private $dataCacheDir = '' ;

	private $sitesCacheFile 			= 'sites.serialized';
	private $siteSettingsCacheFile 		= 'siteSettings.serialized';

	/**
	 *@var default file name of site setting witch save by site
	 */
	private $siteSettingsCacheSiteFile 	= 'siteSettings';

	//private $translationCacheFile 		= 'translations.serialized';
	//private $designsCacheFile 			= 'designs.serialized';
	private $aclResourcesCacheFile 		= 'aclResources.serialized';

	function __construct(){
		
		if($this->config === null){
			$data = Data::getInstance() ;
			if($data->has('config'))
				$this->config = $data->get('config') ;
			else{
				$frontConfig = ROOT_PATH . '/module/Shopping/config/config.'. APPLICATION_ENV .'.php' ;
				$adminConfig = ROOT_PATH . '/module/Admin/config/config.'. APPLICATION_ENV .'.php' ;
				$this->config = array_merge($adminConfig , $frontConfig) ;
			}

			$this->dataCacheDir = $this->config['writableDir']['dataCache'];
		}
	}


    public function dataCache(){

    	$sites = $this->dealSites();
		$this->dealSiteSettings($sites);
		//$this->dealTranslation();
		$this->dealAclResources();
    }

    public function dealSites()
    {
    	// load site ===========================================
		$siteModel = new Sites();
		$sites = $siteModel->fetchActiveAll();
		$array = array();
		foreach ($sites as $site) {
			$array[strtolower($site['hostname'])] = $site;
		}
		if (!empty($array)) {
			Common::writeFile($this->dataCacheDir . $this->sitesCacheFile, serialize($array));
		}

		return $sites ;
    }

    public function dealSiteSettings($sites)
    {
    	// load siteSettings ===========================================
		$array = array();
		foreach ($sites as $site) {
			$settings = new SiteSettingTable();
			$array[strtolower($site['hostname'])] = $settings->setDefaultXml($site['site_id'])->toArray();
		}
		if (!empty($array)) {
			Common::writeFile($this->dataCacheDir . $this->siteSettingsCacheFile, serialize($array));
		}
    }

    /**
     *save site setting file cache by different site
     * @param  $site_id site id
     * @param  $short_name 网站的short name
     * @return  void
     */
    public function dealSiteSettingsBySite($site_id , $short_name = null)
    {
		$settings = new SiteSettingTable();
		$cachexml = $settings->setDefaultXml($site_id)->toArray();
		if (!empty($cachexml)) {

			$dataDump = "<?php \r\n return " . var_export($cachexml , true) . ';' ;
			$filename = $short_name === null ? $this->siteSettingsCacheSiteFile . $site_id . '.php' : $short_name . '.php' ;
			
			Common::writeFile($this->dataCacheDir .'sitesetting/' .$filename , $dataDump);
		}
    }

    public function dealTranslation()
    {
    	// load translation ===========================================
		$languageArray = $this->config['languageMapping'] ;
		$translation = new TranslationTable();
		$array = array();
		foreach( $languageArray as $localeName => $language ){
			$array[$language] = $translation->getLang($language);
		}
		if (!empty($array)) {
			Common::writeFile($this->dataCacheDir . $this->translationCacheFile, serialize($array));
		}
    }

	public function dealAclResources()
	{
		// load acl resources ===========================================
		$user = new User();
		$array = $user->getAllAclResources();
		if (!empty($array)) {
			Common::writeFile($this->dataCacheDir . $this->aclResourcesCacheFile, serialize($array));
		}
	}
}