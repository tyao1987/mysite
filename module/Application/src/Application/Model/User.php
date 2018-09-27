<?php

namespace Application\Model;

use Zend\Mail\Transport\Sendmail;
use Zend\Mime\Mime;
use Zend\Mime\Message as MimeMessage;
use Zend\Mime\Part;
use Zend\Session\Container;
use Zend\Session\SessionManager;
use Zend\View\Model\ViewModel;

use Test\Data;

use Application\Model\DbTable;
use Application\Util\Util;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Where;

/**
 * User model
 * Class for fetching User from user Db.
 *
 *
 * @category Mod_Application
 * @package Model
 */
class User extends DbTable {
	
    /**
     * DB config map key
     */
    protected $_db = "userdb";

    /**
     * NOTICE: this is table name.
     */
    protected $_name = "SiteUsers";

    /**
     * Primary key column field in table
     */
    protected $_primary = "ID";
    
    /**
     * Used for encrypt the password
     */
    private $_sharedSalt = "bloodbar";
    
    
    /**
     * array(
     * 		 'FIRST_NAME'	=> 1
     * 		,'LAST_NAME'	=> 2
     * 		,'GENDER'		=> 3
     * 		,'YEAR_OF_BIRTH'=> 4
     * 		,'POST_ADDRESS'	=> 5
     * 		,...
     * )
     *
     */
    static protected $_userPropertiesID = array();
    
    /*
     * user properties schema mapping
    */
    public $schemaMapping = array(
    		'FIRST_NAME'	=> 'firstName'
    		,'LAST_NAME'	=> 'lastName'
    		,'GENDER'		=> 'gender'
    		,'YEAR_OF_BIRTH'=> 'year'
    		,'POST_ADDRESS'	=> 'street'
    		,'CITY'			=> 'city'
    		,'POST_CODE'	=> 'postalcode'
    		,'MOBILE_PHONE'	=> 'cellphone'
    
    );
    
    /**
     * @var string Login Cookie 用于用户登录
     */
    private $_loginCookie = '__ulc';
    
    /**
     * @var string Login Cookie 规则 , 格式为 : userId-md5(md5(password . time()))-time()-remember
     */
    private $_loginCookieRule = '/^(?P<userId>\d+)\-(?P<password>[a-z0-9]{32})\-(?P<time>\d+)$/i';
    
    /**
     * @var integer Login Cookie 的时间限制，超过此限制认为无效
     */
    private $_loginCookieTimeLimit = 86400;
    
    function __construct() {
        $this->setTableGateway($this->_db, $this->_name);
    }
    
    /*
     * Check if the given user name is exists. return 0/1
    */
    public function checkIfExistsUserName($userName) {
    	
    	$userName = $this->quote($userName);
    	$query = "call CheckIfExistsUserName({$userName}, @return_value)";
    	$this->query($query);
    	$ret = $this->fetchRow("select @return_value as return_value");
    	return $ret['return_value'];
    }
     
    /*
     * Check if the given user email is exists. return 0/1.
    */
    public function checkIfExistsEmail($email) {
    
    	$email = $this->quote($email);
    	$query = "call CheckIfExistsEmail({$email}, @return_value)";
    	$this->query($query);
    	$ret = $this->fetchRow("select @return_value as return_value");
    	return $ret['return_value'];
    	
    }

    public function registrationProcess($data) {
    
    	$userName = $data['username'];
    	$email = $data['email'];
    	$emailValid = 'NO';
    	$password = $data['password'];
    	$verifyKey = $this->_getEmailVerifyKey($email);
    	$createdDate = date('Y-m-d H:i:s');
    	$this->beginTransaction();
    	try {
    		$query = "call UserRegistration(";
    		$query .= $this->quote($userName);
    		$query .= ", ".$this->quote($email);
    		$query .= ", '{$emailValid}'";
    		$query .= ", '{$verifyKey}'";
    		$query .= ", '{$createdDate}'";
    		$query .= ", @return_value)";
    		$this->query($query);
    		$ret = $this->fetchRow("select @return_value as return_value");
    
    		$userId = $ret['return_value'];
    		if(!$userId) {
    			throw new \Exception('Cant get userId druing insert db manipulation!');
    		}
    		$password = $this->generatePassword($userId, $password);
    
    		$query = "call UpdateUserPassword(";
    		$query .= "{$userId}";
    		$query .= ", ".$this->quote($password).")";
    		$this->query($query);
    		$this->commit();
    		//handle send email
    		$this->sendActiveAccountEmail($email, $verifyKey);
    		return $userId;
    	}catch (\Exception $e) {
    		$this->rollback();
    		throw new \Exception($e->getMessage());
    	}
    }
    
    /**
     * Get email verify key
     * Used in md5
     *
     * @param string $email
     * @return string
     */
    protected function _getEmailVerifyKey($email) {
    	return md5($email . time());
    }
    
    public function sendActiveAccountEmail($email, $verifyKey) {
    
    	$data = Data::getInstance();
    	$urlHelper = Util::getViewHelper('GetUrl');
    	$activeUserUrl = $urlHelper('active_user', array('key' => $verifyKey));
    	$siteObj = $data->get('site');
    	$activeUserUrl = 'http://' . $siteObj['hostname'] . $activeUserUrl;
    	    	
    	$userId = $this->getUserIdByEmail($email);
    	$userInfo = $this->getUserInfoById($userId);
    	
    	$content = 'dear ${userName} if you want to active please click <a href="${activeUserUrl}">active</a>';
    	
    	$search_array = array("\${activeUserUrl}","\${userName}");
    	$replace_array = array($activeUserUrl,$userInfo['UserName']);
    	$content = str_replace($search_array, $replace_array, $content);
    	$subject = "active your email";
    	$this->sendMail($email, $subject, $content);

    }
    
    
    public function getUserIdByEmail($email){
    	
    	$email = $this->quote($email);
    	$query = "call GetUserIdByEmail($email, @return_value)";
    	$this->query($query);
    	$ret = $this->fetchRow("select @return_value as return_value");
    	return $ret['return_value'];
    }
    
    /**
     * Fetch a row from SiteUsers only by userId and it dont need specify the country.
     *
     * @param int $id   UserId
     * @return null|object
     */
    public function getUserInfoById($id) {
    	
    	$query = "call GetUserDataById('{$id}')";
    	$row = $this->fetchRow($query);
    	if($row) {
    		return $row;
    	}
    	return null;
    }
    
    /**
     * Generate password for user account
     *
     * @param int $userId
     * @param string $password
     * @return string
     */
    public function generatePassword($userId, $password) {
    	
    	$hashPassword = "SHA-256:[";
    	$beforeEncrypt = $password.'{'. $this->_sharedSalt . $userId .'}';
    	$afterEncrypt = base64_encode(hash('sha256', $beforeEncrypt, true));
    	$hashPassword .= $afterEncrypt;
    	return $hashPassword . "]";
    }
    
    /*
     * Check if the given key is exists.
    * return userId if exists
    */
    public function checkIfExistsVerifyKey($key) {
    	
    	$key = $this->quote($key);
    	$query = "call CheckIfExistsVerifyKey({$key}, @return_value)";
    	$this->query($query);
    	$ret = $this->fetchRow("select @return_value as return_value");
    	return $ret['return_value'];
    }
    
    /*
     * Get if the user account is actived by user id
    * return 0/1/null
    */
    public function getUserAccountIsActivedById($userId) {
    
    	$query = "call GetUserAccountIsActivedById('{$userId}', @return_value)";
    	$this->query($query);
    	$ret = $this->fetchRow("select @return_value as return_value");
    	switch ($ret['return_value']) {
    		case 'YES':
    			return 1;
    			break;
    		case 'NO':
    			return 0;
    			break;
    	}
    	return NULL;
    }
    
    public function activeRegistrationProcess($key) {
    	
    	$key = $this->quote($key);
    	$verifyEmailDate = date('Y-m-d H:i:s', time());
    
    	$query = "call ActiveUserAccount({$key},'{$verifyEmailDate}')";
    	$ret = $this->fetchRow($query);
    	//handle login write session

    	$this->writeUserDataToSession($ret);
    }
    
    /**
     * Write user infomation data into session
     *
     * @param $data object
     */
    public function writeUserDataToSession($data) {
    
    	$userNamespace = new Container('User_Auth');
    	if (isset($data['ID'])) {
    		$userNamespace->id = $data['ID'];
    	} else {
    		$userNamespace->id = $data['id'];
    	}
    	if (isset($data['UserName'])) {
    		$userNamespace->userName = $data['UserName'];
    	} else {
    		$userNamespace->userName = $data['userName'];
    	}
    }
    
    
    
    protected function _getUserLoginData($email) {
    	
    	$email = $this->quote($email);
    	$query = "call GetUserLoginData({$email}, @id, @userName, @password, @emailValid)";
    	$this->query($query);
    	$ret = $this->fetchRow("select @id as id, @userName as userName, @password as password, @emailValid as emailValid");
    	return $ret;
    }
    
    public function loginAction($email, $password, $isAutoLogin = false) {
    
    	$status = new \stdClass();
    	$status->emailValided = false;
    	$status->emailActived = false;
    	$status->passwordValided = false;
    	
    	$ret = $this->_getUserLoginData($email);
		
    	if($ret['id'] === NULL) {
    		return $status;
    	}
    	if($ret['userName'] == ''){
    		$ret['userName'] = $email;
    	}
    	$status->emailValided = true;
    	if($ret['emailValid'] == 'NO') {
    		return $status;
    	}
    	$status->emailActived = true;
		
    	if(!$isAutoLogin){
    		$rawPassword = $this->generatePassword($ret['id'], $password);
    	}else{
    		$rawPassword = $password;
    	}
    	if($rawPassword !== $ret['password']) {
    		return $status;
    	}
    	$status->passwordValided = true;
    
    	$this->writeUserDataToCookie($ret);
    	
    	//$userNamespace = new Container('User_Auth');
    
    	//if($rememberMe) {
    		//write cookie
    		//$userNamespace->getManager()->rememberMe(60 * 60 * 24 * 365);
    	//}
    	//else {
    		//$userNamespace->getManager()->forgetMe();
    	//}
    	
    	//write session
    	$this->writeUserDataToSession($ret);
    
    	return $status;
    }
    
    private function writeUserDataToCookie($ret) {
    	
    	$id = (!empty($ret['id']))?$ret['id']:$ret['ID'];
    	$password = (!empty($ret['password']))?$ret['password']:$ret['Password'];
    
    	//$cookieTime = 0;
    	$dateTime = new \DateTime();
    	$currentTimeStamp = $dateTime->getTimestamp();
    	$cookieTime = $currentTimeStamp + 60 * 60 * 24 * 10;
    	$loginData = $id .'-'. md5(md5($password . $currentTimeStamp))
    	. '-' . $currentTimeStamp;
    	$cookieDomain = Data::getInstance()->get('cookieDomain');
    	
    	setcookie($this->_loginCookie, $loginData, $cookieTime, '/', $cookieDomain);
    }
    
    public function checkLoginCookie() {
    
    	// already login ?
    	$userNamespace = new Container('User_Auth');
    	//$memberNamespace = new Zend_Session_Namespace('Member_Auth');
    	$alreadyLogin = ($userNamespace->id && $userNamespace->userName);
    	$dateTime = new \DateTime();
    	$currentTimeStamp = $dateTime->getTimestamp();
    	if (!empty($_COOKIE[$this->_loginCookie])
    			&& false != ($loginCookie = $_COOKIE[$this->_loginCookie])
    			&& preg_match($this->_loginCookieRule, $loginCookie, $matches)){
    		// 用户未登录，并且 Cookie 写入时间符合条件
    		if (!$alreadyLogin && $currentTimeStamp - $matches['time'] < $this->_loginCookieTimeLimit) {
    			$ret = $this->getUserInfoById($matches['userId']);
    			if ($ret && md5(md5($ret['Password'] . $matches['time'])) == $matches['password']) {
    				$this->writeUserDataToCookie($ret);
    				$this->writeUserDataToSession($ret);
    			} else {
    				$this->logoutAction();
    			}
    						
    		// 已登录，Cookie 写入时间超时，重写 Cookie
    		} else if ($alreadyLogin && $currentTimeStamp - $matches['time'] > $this->_loginCookieTimeLimit) {
    			$ret = $this->getUserInfoById($matches['userId']);
    			if ($ret && md5(md5($ret['Password'] . $matches['time'])) == $matches['password']) {
    				$this->writeUserDataToCookie($ret, $matches['remember']);
    			} else {
    				$this->logoutAction();
    			}
    		}
    	// 如果显示已经登录，但是没有 Cookie 值，则退出
    	} else if ($alreadyLogin && empty($_COOKIE[$this->_loginCookie])) {
    		$this->logoutAction();
    	} 
    }
    
    public function logoutAction() {
    	$userNamespace = new Container('User_Auth');
    	//$userNamespace->getManager()->forgetMe();
    	if ($userNamespace->offsetExists('id') && $userNamespace->id > 0) {
    		$userNamespace->offsetUnset('id');
    		$userNamespace->offsetUnset('userName');
    	}
    	$cookieDomain = Data::getInstance()->get('cookieDomain');
    	setcookie($this->_loginCookie, '', 0, '/', $cookieDomain);
    }
    
    /*
     * Get verify key by email
    * return 0/1/null
    */
    public function getVerifyKeyByEmail($email) {
    	
    	$email = $this->quote($email);
    	$query = "call GetVerifyKeyByEmail({$email}, @u_verifyKey, @u_emailValid)";
    	$this->query($query);
    	
    	$query = "select @u_emailValid as emailValid, @u_verifyKey as verifyKey";
    	$ret = $this->fetchRow($query);
    	if($ret['emailValid'] == 'YES') {
    		return 1;
    	}else if($ret['emailValid'] == 'NO') {
    		return $ret['verifyKey'];
    	}
    	//emailValid == NULL
    	return null;
    }
    
    //return 0/1/null
    public function getUserAccountIsActivedByEmail($email) {
    	
    	$email = $this->quote($email);
    	$query = "call GetUserAccountIsActivedByEmail({$email})";
    	$row = $this->fetchRow($query);
    	switch ($row['EmailValid']) {
    		case 'YES':
    			return 1;
    			break;
    		case 'NO':
    			return 0;
    			break;
    	}
    	return NULL;
    }
    
    public function sendResetPasswordEmailProcess($email) {
    	
    	$verifyKey = $this->_getPasswordResetKey($email);
    	$this->beginTransaction();
    	try{
    		$query = "call SetPasswordVerifyKey(".$this->quote($email).", '{$verifyKey}')";
    		$this->query($query);
    		
    		//handle send email
    		$this->_sendResetPasswordEmail($email, $verifyKey);
    		
    		$this->commit();
    	}catch (\Exception $e) {
    		$this->rollBack();
    		throw new \Exception($e->getMessage());
    	}
    }
    
    /**
     * Get password reset key
     * Used in md5
     *
     * @param string $email
     * @return string
     */
    protected function _getPasswordResetKey($email) {
    	return md5($email . time());
    }
    
    protected function _sendResetPasswordEmail($email, $verifyKey) {
    
    	$urlHelper = Util::getViewHelper("GetUrl");
    	$passwordResetUrl = $urlHelper('password_reset',array(), array('key' => $verifyKey));
    	$data = Data::getInstance();
    	$siteObj = $data->get('site');
    	$passwordResetUrl = 'http://' . $siteObj['hostname'] . $passwordResetUrl;
    	//Issue 263987 - Send automated emails as HTML
    	$userId = $this->getUserIdByEmail($email);
    	$userInfo = $this->getUserInfoById($userId);
		
    	$content = 'dear ${userName} if you want to reset your password <a href="${passwordResetUrl}">reset</a>';
  		
    	$search_array = array("\${passwordResetUrl}","\${userName}");
    	$replace_array = array($passwordResetUrl,$userInfo['UserName']);
    	$content = str_replace($search_array, $replace_array, $content);
    	
    	$subject = "reset your password";
    	$this->sendMail($email, $subject, $content);
    }
    
    
    /**
     * 发送邮件
     * @param unknown_type $email 邮件地址
     * @param unknown_type $subject 主题
     * @param unknown_type $content $content内容
     * @param unknown_type $type $content的类型
     * @param unknown_type $text text内容可选
     */
    public function sendMail($email,$subject,$content,$type = 'html',$text = null){

    	$mail = Utilities::getSiteMailerInstance(true);
    	$mail->addTo($email);
    	$mail->setSubject($subject);
    
    	$body = new MimeMessage();
    	$content = new Part($content);
    	$content->type = $type =="html" ? Mime::TYPE_HTML : Mime::TYPE_TEXT;
    	$content->charset = 'UTF-8';
    	if($text){
    		$text = new Part($text);
    		$text->type = Mime::TYPE_TEXT;
    		$body->setParts(array($content,$text));
    	}else{
    		$body->setParts(array($content));
    	}
    	
    	$mail->setbody($body);
    	if ('html' == $type){
    		$mail->getHeaders()->get('content-type')->setType(Mime::TYPE_HTML);
    	}

    
    	$transport = new Sendmail();
    	$transport->send($mail);
    }
    
    /*
     * Check if the given user id is exists.
    * return userId if exists
    */
    public function checkIfExistsResetKey($key) {
    	
    	$key = $this->quote($key);
    	$query = "call CheckIfExistsResetKey({$key}, @return_value)";
    	$this->query($query);
    	$ret = $this->fetchRow("select @return_value as return_value");
    	return $ret['return_value'];
    }
    
    public function resetPasswordProcess($userId, $newPassword) {
    
    	$dbPassword = $this->generatePassword($userId, $newPassword);
    	$this->beginTransaction();
    	try {
    		$query = "call UpdateUserPassword('{$userId}', ".$this->quote($dbPassword).")";
    		$this->query($query);
    
    		$query = "call CleanResetKeyById('{$userId}')";
    		$this->query($query);
    		
    		$this->commit();
    	}catch (\Exception $e) {
    		$this->rollBack();
    		throw new \Exception($e->getMessage());
    	}
    }
    
    public function getUserPropertiesData() {
    	
    	$ret = array();
    	
    	$userNamespace = new Container('User_Auth');
    	$userId = $userNamespace->id;

    	$query = "call GetUserPropertiesData({$userId})";
    	$rows = $this->fetchAll($query);
		
    	$tmp = $rows;
    	$rows = array();
    	foreach($tmp as $item) {
    		$rows[$item['PropertyID']] = $item['PropertyValue'];
    	}
    
    	$userPropertiesID = $this->_getUserPropertiesID();
    	$userPropertiesIDTmp = array_flip($userPropertiesID);
    	
    	foreach ($userPropertiesIDTmp as $pid => $pname) {
    		if(in_array($pid, array_keys($rows))) {
    			$ret[$this->schemaMapping[$pname]] = $rows[$pid];
    		}else {
    			$ret[$this->schemaMapping[$pname]] = null;
    		}
    	}
		
    	return $ret;
    }
    
    protected function _getUserPropertiesID() {
    
    	if(empty(self::$_userPropertiesID)) {
    		
    		$query = "call GetUserPropertiesID();";
    		$ret = $this->fetchAll($query);
    
    		$tmp = array();
    		foreach ($ret as $item) {
    			$tmp[$item['PropertyKeyName']] = $item['ID'];
    		}
    		self::$_userPropertiesID = $tmp;
    	}
    	return self::$_userPropertiesID;
    }
    
    
    public function updateUserAccount($data) {
    
    	$user = Utilities::getUserSessionData();
    	$userId = $user->id;
    	
    	if(array_key_exists('username', $data)) {
    		$this->tableGateway->update(array('username'=>$data['username']), $this->quoteInto(' ID = ?', $userId));

    		$userNamespace = new Container('User_Auth');
    		$userNamespace->userName = $data['username'];
    	}
    	if(array_key_exists('newEmail', $data)) {
    		
    		$userInfo = $this->getUserInfoById($userId);
    		$email = $userInfo['Email'];
    		$newEmail = $data['newEmail'];
    
    		$verifyKey = $this->_getEmailVerifyKey($newEmail);
    		
    		$this->beginTransaction();
    		try {
    			$query = "call SetVerifyKeyById({$userId}, '{$verifyKey}')";
    			$this->query($query);
    			$this->_sendResetEmail($email, $newEmail, $verifyKey);
    			$this->commit();
    		}catch (\Exception $e) {
    			$this->rollBack();
    			throw new \Exception($e->getMessage());
    		}
    	}
    
    	if(array_key_exists('firstName', $data)) {
    		 
    		$key = array_search('firstName', $this->schemaMapping);
    		 
    		$properties = $this->_getUserPropertiesID();
    		$pid = $properties[$key];
    		$pvalue = $data['firstName'];
    		$pvalue = $this->quote($pvalue);
    		$query = "call SetUserPropertiesData({$userId}, {$pid}, {$pvalue})";
    		$this->query($query);
    	}
    
    	if(array_key_exists('lastName', $data)) {
    		 
    		$key = array_search('lastName', $this->schemaMapping);
    		 
    		$properties = $this->_getUserPropertiesID();
    		$pid = $properties[$key];
    		$pvalue = $data['lastName'];
    		$pvalue = $this->quote($pvalue);
    		$query = "call SetUserPropertiesData({$userId}, {$pid}, {$pvalue})";
    		$this->query($query);
    	}
    
    	if(array_key_exists('gender', $data)) {
    		 
    		$key = array_search('gender', $this->schemaMapping);
    		 
    		$properties = $this->_getUserPropertiesID();
    		$pid = $properties[$key];
    		$pvalue = $data['gender'];
    		$pvalue = $this->quote($pvalue);
    		$query = "call SetUserPropertiesData({$userId}, {$pid}, {$pvalue})";
    		$this->query($query);
    	}
    
    	if(array_key_exists('year', $data)) {
    		 
    		$key = array_search('year', $this->schemaMapping);
    		 
    		$properties = $this->_getUserPropertiesID();
    		$pid = $properties[$key];
    		$pvalue = $data['year'];
    		$pvalue = $this->quote($pvalue);
    		$query = "call SetUserPropertiesData({$userId}, {$pid}, {$pvalue})";
    		$this->query($query);
    	}
    
    	if(array_key_exists('street', $data)) {
    		 
    		$key = array_search('street', $this->schemaMapping);
    		 
    		$properties = $this->_getUserPropertiesID();
    		$pid = $properties[$key];
    		$pvalue = $data['street'];
    		$pvalue = $this->quote($pvalue);
    		$query = "call SetUserPropertiesData({$userId}, {$pid}, {$pvalue})";
    		$this->query($query);
    	}
    
    	if(array_key_exists('city', $data)) {
    		 
    		$key = array_search('city', $this->schemaMapping);
    		 
    		$properties = $this->_getUserPropertiesID();
    		$pid = $properties[$key];
    		$pvalue = $data['city'];
    		$pvalue = $this->quote($pvalue);
    		$query = "call SetUserPropertiesData({$userId}, {$pid}, {$pvalue})";
    		$this->query($query);
    	}
    
    	if(array_key_exists('postalcode', $data)) {
    		 
    		$key = array_search('postalcode', $this->schemaMapping);
    		 
    		$properties = $this->_getUserPropertiesID();
    		$pid = $properties[$key];
    		$pvalue = $data['postalcode'];
    		$pvalue = $this->quote($pvalue);
    		$query = "call SetUserPropertiesData({$userId}, {$pid}, {$pvalue})";
    		$this->query($query);
    	}
    
    	if(array_key_exists('cellphone', $data)) {
    		 
    		$key = array_search('cellphone', $this->schemaMapping);
    		 
    		$properties = $this->_getUserPropertiesID();
    		$pid = $properties[$key];
    		$pvalue = $data['cellphone'];
    		$pvalue = $this->quote($pvalue);
    		$query = "call SetUserPropertiesData({$userId}, {$pid}, {$pvalue})";
    		$this->query($query);
    	}
    
    	if(array_key_exists('password', $data)) {
    		$this->resetPasswordProcess($userId, $data['password']);
    	}
    }
    
    
    protected function _sendResetEmail($email, $newEmail, $verifyKey) {
    	$viewData = array();
    
    	$urlHelper = Util::getViewHelper("GetUrl");
    	$langHelper = Util::getViewHelper("GetLang");
    	$newEmailHash = base64_encode($newEmail);
    	$emailResetUrl = $urlHelper('activate_email_change', array('key' => $verifyKey, 'newEmail' => $newEmailHash));
    	
    	$siteObj = Data::getInstance()->get('site');
    	$emailResetUrl = 'http://' . $siteObj['hostname'] . $emailResetUrl;
		
    	$isHtml = true;
    	$htmlContent = $this->_getEmailChangeTemplate($isHtml, $newEmail);
    	$htmlContentTmp = $this->_getEmailChangeVerifyTemplate($isHtml, $emailResetUrl);
    
    	$isHtml = false;
    	$textContent = $this->_getEmailChangeTemplate($isHtml, $newEmail);
    	$textContentTmp = $this->_getEmailChangeVerifyTemplate($isHtml, $emailResetUrl);
    
    	$this->sendMail($email, 'You requested a change in your active e-mail address', $htmlContent, "html", $textContent);
    	 
    	$this->sendMail($newEmail, 'You requested a change in your active e-mail address', $htmlContentTmp, "html", $textContentTmp);
    }
    
    protected function _getEmailChangeTemplate($isHtml,$newEmail){
    	$langHelper = Util::getViewHelper("GetLang");
    	$str = 'Thanks for updating your email address. An activation link was sent to'." ".$newEmail;
    	if($isHtml){
    		$str.="<br/>";
    	}
    	return $str;
    }
    
    protected function _getEmailChangeVerifyTemplate($isHtml,$emailResetUrl){
    	$langHelper = Util::getViewHelper("GetLang");
    	if($isHtml){
    		$str .= 'Please confirm you wish to change your email to this address by clicking this link:'." <a href=".$emailResetUrl.">".$emailResetUrl."</a><br /><br />";
    		$str .= 'If you don\'t want to change your email any more then please ignore this email.'."<br />";
    	}else{
    		$str .= 'Please confirm you wish to change your email to this address by clicking this link:'." ".$emailResetUrl;
    		$str .= 'If you don\'t want to change your email any more then please ignore this email.';
    	}
    	return $str;
    }
    
    public function resetEmailProcess($userId, $newEmail) {
    	
    	$newEmail = $this->quote($newEmail);
    	$this->beginTransaction();
    	try {
    		$query = "call ChangeUserEmailById('{$userId}', {$newEmail})";
    		$this->query($query);
    
    		$query = "call CleanVerifyKeyById('{$userId}')";
    		$this->query($query);
    
    		$this->commit();
    	}catch (\Exception $e) {
    		$this->rollBack();
    		throw new \Exception($e->getMessage());
    	}
    
    	//write session
    	$ret = $this->getUserInfoById($userId);
    	$this->writeUserDataToSession($ret);
    }
    
    public function checkIfOldPasswordTrue($id, $oldPassword) {
    
    	$user = $this->getUserInfoById($id);
    	$rawPassword = $this->generatePassword($id, $oldPassword);
    	if($rawPassword != $user['Password']) {
    		return false;
    	}
    	return true;
    }
}