<?php
namespace Application\Model;

use Zend\Config\Reader\Xml;

use Shopping\Exception;
use Shopping\Service\Cache;

class SiteSettingTable extends DbTable {

    /**
     * the parsed site setting file
     *
     * @var simpleXml object
     */
    protected $_xml = null ;

    /**
     * the filepath key
     *
     * @var string
     */
    protected $_settingsKey = 'site_settings';

    public function __construct($settingsKey = null) {
        $this->setTableGateway("cmsdb", "data");

        if($settingsKey !== null){
            $this->_settingsKey = $settingsKey;
        }
    }

    public function getSiteSetting($siteId, $tags = "site_settings") {
        $siteId = (int)$siteId;
        $rowset = $this->tableGateway->select(array(
            'site_id' => $siteId, 'tags' => $tags
        ));
        $row = $rowset->current();
        if (!$row) {
            throw new Exception("Could not find row $siteId");
        }
        return $row['data'];
    }

    public function getSiteSettingBySiteId($filename, $siteId, $siteType, $useCache = false) {
        $cache = Cache::get('dynamicCache');
        $cacheKey = 'SITE_SETTINGS_' . $siteId;
        
        if ($useCache && $cache->hasItem($cacheKey)) {
            $settings = $cache->getItem($cacheKey);
            return $settings;
            
        } else {
            
            $row = $this->getSiteSetting($siteId, $filename);
            
            if (!empty($row)) {
                
                $reader = new Xml();
                $settings = $reader->fromString($row);
                
                $defaultSettings = require ROOT_PATH . '/module/Shopping/data/site-settings/mainsite-default-settings.php';
                if ($siteType == 'DistributionSite') {
                    $tmp = require ROOT_PATH . '/module/Shopping/data/site-settings/whitelabel-default-settings.php';
                    $defaultSettings = array_merge($defaultSettings, $tmp);
                }
                
                $settings = array_merge($defaultSettings, $settings);
                if ($useCache) {
                    $cache->setItem($cacheKey, $settings);
                }
                
                return $settings;
                
            } else {
                return array();
            }
        }
    }

    /**
     * 保存要变成xml的键值对
     * @param string $siteId
     * @param string $tags
     */
    public function setDefaultXml($siteId, $tags = null){

        if( null === $tags) $tags = $this->_settingsKey ;

        $row = $this->getSiteSetting($siteId, $tags) ;

        if(!empty($row)) {
            $xml = simplexml_load_string($row);
            $this->_xml = $xml;
        }

        return $this ;
    }

    /**
     * 保存要变成xml的键值对
     * @param string $key
     * @param string $value
     */
    public function set($key , $value)
    {
        if(null === $this->_xml){
            $this->_xml = new \SimpleXMLElement('<settings/>');    
        }
        
        $this->_xml->$key = is_array($value) ? serialize($value) : $value ;
    }

    /**
     * remove specified key
     *
     * @param string $key
     */
    public function remove($key) {
        if (isset($this->_xml->$key)) {
            unset($this->_xml->$key);
        }
    }

    /**
     * returns the current site settings as an associative array
     *
     * @return array
     */
    public function toArray()
    {
        foreach ($this->_xml as $k => $v)
        {
            $array[$k] = (string)$v;
        }
        return $array;
    }

    /**
    *将结果xml保存到数据库中
    * 
    */
    public function save($site_id)
    {
        if(!(int)$site_id) return false ;

        $xml = is_object($this->_xml) ? $this->_xml->asxml() : $this->_xml ;
        
        $select = $this->tableGateway->getSql()->select();
        $select->where(array('site_id' => $site_id , 'tags' => $this->_settingsKey));
        $row = $this->fetchRow($select);
        if($row) {

            $this->tableGateway->update(array('data' => $xml) , '`id` = ' . $row->id) ;
        }else{
            $data = array(
                'tags'      =>  $this->_settingsKey,
                'data'      =>  $xml,
                'site_id'   =>  $site_id
            );
            $this->insert($data);
        }
    }
}