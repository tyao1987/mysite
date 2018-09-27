<?php
/**
 * User authentication
 *
 */
namespace Admin\Model;

use Application\Service\DbAdapterCluster;
use Zend\Authentication\Adapter\DbTable as dbAuthAdapter;
use Zend\Authentication\AuthenticationService;
use Admin\Model\User;
use Test\Data;
use Zend\Authentication\Validator\Authentication;
use Zend\Authentication\Storage\Session as sessionStorage;

class Auth {
	
	const AUTH_TYPE_DB   = "db";
	const AUTH_TYPE_HTTP = "http"; 
	const AUTH_TYPE_DEFAULT = "default";
	
	/**
	 * auth adapter
	 *
	 * @var zend_auth_adapter
	 */
	private $_authAdapter;
	private $_dbAdapter;
	
	/**
	 * the passed username
	 *
	 * @var string
	 */
	private $_username;
	
	/**
	 * the passed password
	 *
	 * @var string
	 */
	private $_password;
	
	/**
	 * the table that contains the user credentials
	 *
	 * @var string
	 */
	private $_userTable = "users";
	
	/**
	 * the indentity column
	 *
	 * @var string
	 */
	private $_identityColumn = "email";
	
	/**
	 * the credential column
	 *
	 * @var string
	 */
	private $_credentialColumn = "password";
	
	/**
	 * select column
	 * 
	 * @var Array
	 */
	private $_visibleColumnList = array (
			'id',
			'user_group_id',
			'first_name',
			'last_name',
			'email',
			'role',
			'acl_resources' 
	);
	
	/**
	 * current adapter type
	 */
	private $_authAdapterType = "";
	
	/**
	 * Auth manager
	 */
	private $authenticate = null;
	
	/**
	 * User
	 */
	private $_user = null;
	
	/**
	 * the user session storage
	 *
	 * @var zend_session_namespace
	 */
	private $_storage;
	
	private $_errorMessage;
	
	/**
	 *
	 * @param string $username        	
	 * @param string $password        	
	 * @param string $adapter        	
	 */
	public function __construct($username, $password, $adapter = "") {
		$this->_username = $username;
		$this->_password = $password;
		$this->_authAdapterType = $adapter;
		$this->_user = new User ();
		$this->_storage = self::getBaseInfoStorageInstance();
		$this->_errorMessage = new Message();
		$this->_errorMessage->clear();
		$this->init();
	}
	
	/**
	 * get sessionStorage instance  of baseinfo
	 * 
	 * @return \Zend\Authentication\Storage\Session
	 */
	public static function getBaseInfoStorageInstance() {
		return new sessionStorage("adminUser", "baseUserInfo");
	}
	
	/**
	 * init config  data
	 * 
	 * @throws Exception\RuntimeException
	 */
	public function init() {
		
		switch ($this->_authAdapterType) {
			case '':
			case self::AUTH_TYPE_DEFAULT:
			case self::AUTH_TYPE_DB:
				$this->_dbAdapter = DbAdapterCluster::getAdapter ( "cmsdb" );
				$this->_authAdapter = new dbAuthAdapter ( $this->_dbAdapter, $this->_userTable, $this->_identityColumn, $this->_credentialColumn, "MD5(?)" );
				break;
		}
		
		$authService = new AuthenticationService();
		
		$this->authenticate = new Authentication ( array (
				"adapter" => $this->_authAdapter,
				"identity" => $this->_username,
				"credential" => $this->_password,
				"service" => $authService 
		) );
	}
	
	/**
	 * 
	 * @return \Zend\Authentication\Validator\Authentication
	 */
	public function getAuthenticate(){
		return $this->authenticate;
	}
	
	/**
	 *  Authenticate and return result
	 * 
	 * @return boolean|Ambigous <multitype:, NULL, \ArrayObject, ArrayObject>|multitype:
	 */
	public function authenticate() {
		
		if ($this->authenticate->isValid ()) {
			
			if($this->authenticate->getAdapter() instanceof dbAuthAdapter){
				
				$userInfo = (array)$this->authenticate->getAdapter ()->getResultRowObject ( $this->_visibleColumnList );
				$userSites = $this->_user->getUserSites($userInfo['id']);
				// valid site
				if(!$this->validSite($userInfo['id'], $userSites)) {
					
					$this->_errorMessage->add(array(Authentication::GENERAL=>"Valid site faild"));
					return false;
				}
				// add to user info
				$userInfo['sites'] = $userSites;
				$this->_storage->write($userInfo);
				
				return $userInfo;
				
			}else if($this->authenticate->getAdapter() instanceof ldapAuthAdapter){
				
				$identity = $this->authenticate->getService()->getIdentity();
				// get sites
				$userInfo = (array)$this->_user->getUserByLdapIdentity($identity);
				if (!$userInfo) {
					
					$this->_errorMessage->add(array(Authentication::GENERAL=>"Valid user faild"));
					return false;
				}
				
				foreach ($userInfo as $key =>$value){
					if(!in_array($key, $this->_visibleColumnList))
					{
						unset($userInfo[$key]);
					}
				}	
				
				$userSites = $this->_user->getUserSites($userInfo['id']);
				// valid site
				if(!$this->validSite($userInfo['id'], $userSites)) {
					
					$this->_errorMessage->add(array(Authentication::GENERAL=>"Valid site faild"));
					return false;
				}
					
				$storageUserInfo = array();
				// add to user info
				$storageUserInfo['sites'] = $userSites;
				$storageUserInfo = array_merge($storageUserInfo,$userInfo);
				
				$this->_storage->write($storageUserInfo);
				
				return $storageUserInfo;
			}
		}
		return false;
	}
	
	/**
	 * authenticate failed  message
	 * 
	 * @return multitype:
	 */
	public function getErrorMessage()
	{
		$errorMessage = $this->authenticate->getMessages();
		
		empty($errorMessage) && $errorMessage = $this->_errorMessage->get();
		
		return $errorMessage;
	}

	/**
	 * valid site
	 *
	 * @param int $userId        	
	 * @return boolean
	 */
	public function validSite($userId, $userSites) {
		
		// if superadmin, return true
		if ($userId == User::SUPERUSER_ROLE) {
			return TRUE;
		}
		
		$host = $_SERVER ['SERVER_NAME'];
		
		$config = Data::getInstance ()->get ( "config" );
	    
		if (isset ( $config['cmsHost'] ) && $host == $config['cmsHost']) {
			if (count ( $userSites ) > 0) {
				return TRUE;
			}
		} else {
			foreach ( $userSites as $site ) {
				if ($site['hostname'] == $host) {
					return TRUE;
				}
			}
		}
		return false;
	}
	
	/**
	 * get acl_user_site
	 *
	 * @param int $userId        	
	 * @return array
	 */
	public function getSitesByUser($userId) {
		$sites = $this->_user->getSelectedSitesByUserId ( $userId );
		return $sites;
	}
	
	/**
	 * get acl_user_sitegroup
	 *
	 * @param int $userId        	
	 * @return array
	 */
	public function getSitesBySiteGroup($userId) {
		$siteList = $this->_user->getSelectedSitesBySiteGroup ( $userId );
		return $siteList;
	}
	
	/**
	 * get the current user identity if it exists
	 *
	 * @return zend_auth_response
	 */
	public static function getIdentity() {
		$sessionStorage = self::getBaseInfoStorageInstance();
		$userIdentity = null;
		if (!$sessionStorage->isEmpty()) {
			$userIdentity = $sessionStorage->read();
		}
		return $userIdentity;
	}
	
	/**
	 * destroys the current user session
	 */
	public static function destroy() {
		$sessionStorage = new sessionStorage();
		$sessionStorage->clear ();
		self::getBaseInfoStorageInstance ()->clear ();
	}
}