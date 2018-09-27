<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2013 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Application\Controller;

use Zend\View\Model\ViewModel;
use Zend\Mvc\MvcEvent;
use Zend\Json\Json;
use Zend\Validator\StringLength;
use Zend\Validator\Regex;
use Zend\Validator\EmailAddress;
use Zend\Validator\Between;
use Zend\Session\Container;

use Application\Model\Utilities;
use Application\Model\User;
use Test\Data;
use Application\Util\Util;
use Test\Util\Timer;

class UserController extends AbstractController
{
    public function registrationAction() {
    	
    	if($this->_checkLogin()) {
    		return $this->_redirectToUrl('/',302);
    	}
    	$data = Data::getInstance();
    	$viewModel = new ViewModel($data->getData());
    	$viewModel->setTemplate('application/user/index.phtml');
        return $viewModel;
    }
    
    /**
     * Check if a user has login
     *
     * @return unknown
     */
    protected function _checkLogin() {
    	return Utilities::checkLogin();
    }
    
    public function registrationProcessAction(){
    	
    	$status = $this->registrationValidationProcess();
    	$postData = $this->getRegistrationPostData();
    	$pageVariables['status'] = $status;
    	$pageVariables['postData'] = $postData;
    	$data = Data::getInstance();
    	$result = array_merge($data->getData(), $pageVariables);
    	$viewModel = new ViewModel($result);
    	$userModel = new User();
    	if($this->isAjax){
    		if(!empty($status)) {
    			$viewModel->setTemplate('application/user/registration.phtml');
    		}else{
    			try {
    				$userModel->registrationProcess($postData);
    				$result['successful'] = true;
    			}catch (\Exception $e) {
    				$result['successful'] = false;
    			}
    			$viewModel->setVariable('successful', $result['successful']);
    			$viewModel->setTemplate('application/user/registration-process.phtml');
    		}
    		 
    		return $this->_renderViewModel($viewModel);
    	}
    	 
    	if(!empty($status)) {
    		$result['template'] = 'application/user/registration.phtml';
    	}else {
    		//do registration
    		try {
    			$userModel->registrationProcess($postData);
    			$result['successful'] = true;
    		}catch (\Exception $e) {
    			$result['successful'] = false;
    		}
    		 
    		$result['template'] = 'application/user/registration-process.phtml';
    	}
    	
    	$viewModel->setVariable('successful', $result['successful']);
    	$viewModel->setTemplate($result['template']);
    	
    	return $viewModel;
    }
    
    public function newCaptchaAction() {
    	
    	$cptchaUrl = Utilities::getCaptchaUrl();
    	$json = new Json();
    	$jsonOuput = $json::encode($cptchaUrl);
    	$response = $this->getResponse();
    	$response->setStatusCode(200);
    	$response->setContent($jsonOuput);
    	
    	return $response;
    	
    }
    
    /**
     * Activate the Registration
     * New registrated users always can get a validation email
     * User can click the validation link in the email to activiate their accounts.
     * Only after activation, user can login.
     *
     */
    public function activeRegistrationAction() {
    
    	$userModel = new User();
    	$key = $this->params['key'];
    	
    	$pageVariables = array();
    	
    	$data = Data::getInstance();
    	$regex = $data->get('regex');
    	$validatorRegex = new Regex($regex['user_verify']);
    	
    	if(!$validatorRegex->isValid($key) || !($userId = $userModel->checkIfExistsVerifyKey($key))) {
    		$pageVariables['keyInValid'] = true;
    	}else {
    		$pageVariables['keyInValid'] = false;
    		$emailValided = $userModel->getUserAccountIsActivedById($userId);
    		if($emailValided === 0) {
    			$userData = $userModel->getUserDataById($userId);
    			$userModel->activeRegistrationProcess($key);
    			$status = $userModel->loginAction($userData['Email'], $userData['Password'], false ,true);
    			if($status->passwordValided){
    				//redirect to memberRegistrationDetails
    				$urlHelper = Util::getViewHelper('GetUrl');
    				$url = $urlHelper('user_settings');
    				return $this->_redirectToUrl($url,302);
    			}
    		}
    	}
    	
    	return $this->_getViewModel($pageVariables);
    	
    }
    
    /**
     * Ajax validation of the data inputed by user from the registration page
     * Return json string
     *
     */
    public function registrationValidationAction() {
    
    	$status = $this->registrationValidationProcess();
    	$pageVariables = array();
    	$pageVariables['status'] = $status;
    	$pageVariables['postData'] = $this->getRegistrationPostData();
    	if($this->isAjax){
    		$viewModel = new ViewModel($pageVariables);
    		$viewModel->setTemplate('application/user/registration-validation.phtml');
    		return $this->_renderViewModel($viewModel);
    	}
    }
    
    /**
     * Validation of the data given by user from the registration page
     *
     * @return Array
     */
    protected function registrationValidationProcess() {
    
    	$postData = $this->getRegistrationPostData();
    	return $this->_postDataValidationEngine($postData,true);
    }
    
    /**
     * Get the data post by user from the registration page
     *
     * @return Array
     */
    protected function getRegistrationPostData() {
    
    	$ret = array();
   		$ret['username'] = $this->request->getPost('username','');
   		$ret['email'] = $this->request->getPost('email','');
   		$ret['password'] = $this->request->getPost('password','');
   		$ret['passwordRepeat'] = $this->request->getPost('passwordRepeat','');
   		$ret['captcha'] = $this->request->getPost('captcha',array('id' => null,'value' => null));
    	return $ret;
    }
    
    /**
     * Validation engine for all actions
     *
     * @param Array $params
     * @return Array
     */
    protected function _postDataValidationEngine($params,$checkCaptcha = false) {
    
    	$userModel = new User();
    	$ret = $params;
    	$data = Data::getInstance();
    	$regex = $data->get('regex');
     	$validatorStringMaxLength = new StringLength(array('max' => 21));
     	$validatorStringMinLength = new StringLength(array('min' => 3));
     	$validatorRegex = new Regex($regex['username']);
     	$validatorEmail = new EmailAddress();
     	$langHelper = Util::getViewHelper('GetLang');
     	//handle username
     	if(isset($ret['username'])) {
     		if(!$validatorStringMaxLength->isValid($ret['username'])) {
     			$ret['username'] = 'Your username is too long.';
     		}else if(!$validatorStringMinLength->isValid($ret['username'])) {
     			$ret['username'] = 'Your username is too short.';
     		}else if(!$validatorRegex->isValid($ret['username'])) {
     			$ret['username'] = 'Allowed characters: 1-9a-zA-Z. Minimum 3 characters.';
     		}else if($userModel->checkIfExistsUserName($ret['username'])) {
     			$ret['username'] = 'This username already exists. Please choose a new one.';
     		}else {
     			unset($ret['username']);
     		}
     	}
     	
     	//handle email
     	if(isset($ret['email'])) {
    		$validatorStringMaxLength->setMax(65);
    		if(!$validatorEmail->isValid($ret['email'])) {
    			$ret['email'] = $langHelper('User.INVALID_ADDRESS');
    		}else if(!$validatorStringMaxLength->isValid($ret['email'])) {
    			$ret['email'] = $langHelper('User.EMAIL_LONG');
    		}else if($userModel->checkIfExistsEmail($ret['email'])) {
    			$ret['email'] = $langHelper('User.ALREADY_REGISTERED');
    		}else {
    			unset($ret['email']);
    		}
     	}
     	
     	//handle password
     	if(isset($ret['password'])) {
     		$validatorStringMaxLength->setMax(21);
     		$validatorStringMinLength->setMin(6);
     		if(!$validatorStringMaxLength->isValid($ret['password'])) {
     			$ret['password'] = $langHelper('User.NEW_PASSWORD_LONG');
     		}else if(!$validatorStringMinLength->isValid($ret['password'])) {
     			$ret['password'] = $langHelper('User.NEW_PASSWORD_SHORT');
     		}else if($ret['password'] !== $ret['passwordRepeat']) {
     			$ret['password'] = $langHelper('User.NEW_PASSWORD_MISMATCH');
     		}else {
     			unset($ret['password']);
     		}
     		unset($ret['passwordRepeat']);
     	}
    
     	//handle oldPassword
     	if(array_key_exists('oldPassword', $params)) {
     		$user = Utilities::getUserSessionData();
     		if(!$userModel->checkIfOldPasswordTrue($user->id, $ret['oldPassword'])) {
     			$ret['oldPassword'] = $langHelper('User.INVALID_OLD_PASSWORD');
     		}else {
     			unset($ret['oldPassword']);
     		}
     	}
     	
     	//handle captcha
     	if($checkCaptcha){
     		if(!Utilities::checkCaptchaCode($ret,$this->isAjax)) {
     			$ret['captcha'] = $langHelper('User.CAPTCHA_CODE_WRONG');
     		}else {
     			unset($ret['captcha']);
     		}
     	}else{
     		if(isset($ret['captcha'])) {
     			if(!Utilities::checkCaptchaCode($ret,$this->isAjax)) {
     				$ret['captcha'] = $langHelper('User.CAPTCHA_CODE_WRONG');
     			}else {
     				unset($ret['captcha']);
     			}
     		}
     	}
     	
     	//handle newEmail
     	if(array_key_exists('newEmail', $params)) {
     		$user = Utilities::getUserSessionData();
     		$userId = $user->id;
     		$validatorStringMaxLength->setMax(41);
     		$userInfo = Utilities::getUserInfoById($userId);
     		$oldEmail = $userInfo['Email'];
     		if($userId && $ret['newEmail'] == $oldEmail) {
     			unset($ret['newEmail']);
     		}else if(!$validatorEmail->isValid($ret['newEmail'])) {
     			$ret['newEmail'] = $langHelper('User.INVALID_ADDRESS');
     		}else if(!$validatorStringMaxLength->isValid($ret['newEmail'])) {
     			$ret['newEmail'] = $langHelper('User.EMAIL_LONG');
     		}else if($userModel->checkIfExistsEmail($ret['newEmail'])) {
     			$ret['newEmail'] = $langHelper('User.ADDRESS_TAKEN');
     		}else {
     			unset($ret['newEmail']);
     		}
     	}
     	
     	//handle firstName
     	if(array_key_exists('firstName', $params)) {
     		$validatorStringMaxLength->setMax(33);
     		if(!$validatorStringMaxLength->isValid($ret['firstName'])) {
     			$ret['firstName'] = $langHelper('User.FIRST_NAME_LONG');
     		}else {
     			unset($ret['firstName']);
     		}
     	}
     	
     	//handle lastName
     	if(array_key_exists('lastName', $params)) {
     		$validatorStringMaxLength->setMax(33);
     		if(!$validatorStringMaxLength->isValid($ret['lastName'])) {
     			$ret['lastName'] = $langHelper('User.LAST_NAME_LONG');
     		}else {
     			unset($ret['lastName']);
     		}
     	}
     	
     	//handle gender
     	if(array_key_exists('gender', $params)) {
     		if($ret['gender'] == '') {
     			$ret['gender'] = $langHelper('User.GENDER_REQUIRED');
     		}else if(!in_array($ret['gender'], array('F', 'M'))) {
     			$ret['gender'] = $langHelper('User.GENDER_REQUIRED');
     		}else {
     			unset($ret['gender']);
     		}
     	}
     	
     	//handle year
     	if(array_key_exists('year', $params)) {
     		$dateTime = new \DateTime();
     		$year = $dateTime->format('Y');
     		$validatorBetween  = new Between(array('min' => 1940, 'max' => $year));
     		if($ret['year'] == '') {
     			$ret['year'] = $langHelper('User.YEAR_REQUIRED');
     		}else if(!$validatorBetween->isValid($ret['year'])) {
     			$ret['year'] = $langHelper('User.YEAR_REQUIRED');
     		}else {
     			unset($ret['year']);
     		}
     	}
     	
     	//handle street
     	if(array_key_exists('street', $params)) {
     		$validatorStringMaxLength->setMax(65);
     		if(!$validatorStringMaxLength->isValid($ret['street'])) {
     			$ret['street'] = $langHelper('User.INVALID_STREET');
     		}else {
     			unset($ret['street']);
     		}
     	}
     	
     	//handle city
     	if(array_key_exists('city', $params)) {
     		$validatorStringMaxLength->setMax(65);
     		if(!$validatorStringMaxLength->isValid($ret['city'])) {
     			$ret['city'] = $langHelper('User.INVALID_CITE');
     		}else {
     			unset($ret['city']);
     		}
     	}
     	
     	//handle postalcode
     	if(array_key_exists('postalcode', $params)) {
     		$validatorStringMaxLength->setMax(11);
     		if(!$validatorStringMaxLength->isValid($ret['postalcode'])) {
     			$ret['postalcode'] = $langHelper('User.INVALID_POSTALCODE');
     		}else {
     			unset($ret['postalcode']);
     		}
     	}
     	
     	//handle cellphone
     	if(array_key_exists('cellphone', $params)) {
     		$validatorRegex = new Regex($regex['mobile_phone']);
     		$validatorStringMaxLength->setMax(65);
     		if($ret['cellphone'] == '') {
     			unset($ret['cellphone']);
     		}else if(!$validatorRegex->isValid($ret['cellphone']) || !$validatorStringMaxLength->isValid($ret['cellphone'])) {
     			$ret['cellphone'] = $langHelper('User.INVALID_CELLPHONE_NUMBER');
     		}else {
     			unset($ret['cellphone']);
     		}
     	}
     	
    	return $ret;
    }
    
    /**
     * The login template.
     * Something looks like a login form box
     *
     */
    public function loginAction() {
    	if($this->_checkLogin()){
    		return $this->_redirectToUrl('/',302);
    	}
    	
    	$location = !empty($this->params['location']) ? (string)$this->params['location'] : '';
    	if($location){
    		$location = base64_decode($location);
    	}
    	
    	$pageVariables = array();
    	$pageVariables['location'] = $location;
    	$pageVariables['isAjax'] = false;

    	if($this->isAjax){
    		ob_get_clean(); ob_get_clean();
    		$pageVariables['isAjax'] = true;
    
    		$viewModel = new ViewModel($pageVariables);
    		$viewModel->setTemplate('application/user/login.phtml');
    		return $this->_renderViewModel($viewModel);
    	}
    	
    	return $this->_getViewModel($pageVariables);
    }
    
    
    /**
     * Login process work flow
     * Data from loginBoxAction
     */
    public function loginProcessAction() {
    	
    	$pageVariables = array();
    	$pageVariables['isAjax'] = false;
    	 
    	$params = $this->params()->fromPost();
    	$email = $pageVariables['email'] = $params['email'];
    	$password = $params['password'];
    	//$rememberMe = isset($params['rememberMe']) ? true : false;
    	$userModel = new User();
	    	 
    	$status = $userModel->loginAction($email, $password);
    	$pageVariables['status'] = $status;
    	$pageVariables['referer'] = $params['location'];
    
    	$data = Data::getInstance();
    	$result = array_merge($data->getData(), $pageVariables);
    	$viewModel = new ViewModel($result);
    	
    	if($this->isAjax){
    		ob_get_clean(); ob_get_clean();
    		$pageVariables['isAjax'] = true;
    		if($status->passwordValided) {
    			$viewModel->setTemplate('application/user/login-process.phtml');
    			return $this->_renderViewModel($viewModel);
    		} else {
    			$viewModel->setTemplate('application/user/login.phtml');
    			return $this->_renderViewModel($viewModel);
    		}
    	}
    
    	if($status->passwordValided){
    		return $this->_redirectToUrl($pageVariables['referer'],302);
    	}else {
    		$viewModel->setTemplate('application/user/index.phtml');
    		return $viewModel;
    	}
    }
    
    
    /**
     * Logout process work flow
     *
     */
    public function logoutProcessAction() {
    	$userModel = new User();
    	$userModel->logoutAction();
    	$referer = $this->getReferer();
    	return $this->_redirectToUrl($referer,302);
    }
    
    
    /**
     * Get the url of last page which redirect to current page
     *
     * @return String
     */
    protected function getReferer() {
    	
    	$urlHelper = Util::getViewHelper('GetUrl');
    	$referer = $urlHelper('home');
    	if(array_key_exists('HTTP_REFERER', $_SERVER)) {
    		$referer = $_SERVER['HTTP_REFERER'];
    	}
    	$notAllowed = array(
    			'login'
    			,'try-login'
    			,'registration'
    	);
    	foreach ($notAllowed as $item) {
    		$validatorRegex = new Regex("#/{$item}#");
    		if($validatorRegex->isValid($referer)){
    			$referer = $urlHelper('home');
    			break;
    		}
    	}
    	return $referer;
    }
    
    
    /**
     * New Registration user need to email validation
     * If such user hasn't get the validation email for some purposes
     * Then he/she can ask another validation emaill.
     *
     */
    public function resendActivationEmailAction() {
    
    	if($this->_checkLogin()) {
    		return $this->_redirectToUrl('/',302);
    	}
    	$email = isset($this->params['email']) ? $this->params['email'] : '';
    	$viewData = array();
    	$viewData['email'] = $email;
    	$submit = trim($this->request->getPost('submit',''));
    	if((!empty($submit) || isset($this->params['submit'])) && $email != '') {
    		$viewData['submit'] = true;
    		$userModel = new User();
    		$verifyKey = $userModel->getVerifyKeyByEmail($email);
    
    		if($verifyKey === null) {
    			$viewData['emailExists'] = 0; // email not registered
    		}else if($verifyKey === 1) {
    			$viewData['emailExists'] = 2; // email has been actived
    		}else {
    			$viewData['emailExists'] = 1; // email exists
    			$userModel->sendActiveAccountEmail($email, $verifyKey);
    		}
    	}else {
    		$viewData['submit'] = false;
    	}
    	
    	return $this->_getViewModel($viewData);
    	
    }
    
    
    /**
     * Password recovery html and process
     * After users' confirmation of the recovery of their password
     * System will send out an email.
     * If click the confirmation link in the email,
     *
     */
    public function passwordRecoveryAction() {
    	
    	if($this->_checkLogin()) {
    		return $this->_redirectToUrl('/',302);
    	}
    	$userModel = new User();
    	$status = array();
    	$viewData['email'] = '';
    	$viewData['isPost'] = false;
    	$form = $this->_createFormCsrf();
    	$viewData['csrf'] = $form->get("csrf");
    	$form->setData($this->getRequest()->getPost());
    	if($this->request->isPost() && $form->isValid()) {
    		$viewData['isPost'] = true;
    		$langHelper = Util::getViewHelper("GetLang");
    		$email = trim($this->request->getPost('email',''));
    		$validatorEmail = new EmailAddress();
    		if(!$validatorEmail->isValid($email)) {
    			$status['email'] = $langHelper('User.INVALID_ADDRESS');
    		}else if(!$userModel->checkIfExistsEmail($email)) {
    			$status['email'] = $langHelper('User.EMAIL_DOES_NOT_EXIST');
    		}else if($userModel->getUserAccountIsActivedByEmail($email) === 0) {
    			$status['email'] = $langHelper('User.EMAIL_NOT_VERIFIED');
    		}else {
    			$userModel->sendResetPasswordEmailProcess($email);
    		}
    
    		$viewData['email'] = $email;
    	}
    	
    	$viewData['status'] = $status;
    	$form->prepare();
    	return $this->_getViewModel($viewData);
    }
    
    protected function _getViewModel($viewData){
    	$result = array_merge(Data::getInstance()->getData(), $viewData);
    	$viewModel = new ViewModel($result);
    	$viewModel->setTemplate('application/user/index.phtml');
    	return $viewModel;
    }
    
    /**
     * Reset user's password
     * Followed by passwordRecoveryAction
     *
     */
    public function passwordResetAction() {
    	if($this->_checkLogin()) {
    		return $this->_redirectToUrl('/',302);
    	}
    	$userModel = new User();
		$key = isset($this->params['key']) ? $this->params['key'] : false;
    	$viewData['key'] = $key;

    	$data = Data::getInstance();
    	$regex = $data->get('regex');
    	$validatorRegex = new Regex($regex['user_verify']);
    	if(!$validatorRegex->isValid($key) || !($userId = $userModel->checkIfExistsResetKey($key))) {
    		$viewData['keyInValid'] = true;
    	}else {
    		$viewData['keyInValid'] = false;
    		$status = array();
    		if($this->request->isPost()) {
    			$viewData['isPost'] = true;
    			//handle password
    			$postData = array();
    			$postData['password'] = $this->request->getPost('password',false);
    			$postData['passwordRepeat'] = $this->request->getPost('passwordRepeat',false);
    			
    			$status = $this->_postDataValidationEngine($postData);
				
    			if(empty($status)) {
    				$userModel->resetPasswordProcess($userId, $postData['password']);
    			}
    		}else {
    			$viewData['isPost'] = false;
    		}
    		$viewData['status'] = $status;
    	}
    	return $this->_getViewModel($viewData);
    }
    
    public function userSettingsAction(){
    	
    	if(!$this->_checkLogin()) {
    		return $this->_redirectToUrl("/",302);
    	}
    	$userModel = new User();
    	$status = array();
    	 
    	$viewData =array();
    	$viewData['isPost'] = false;
    	$viewData['postType'] = null;
    	$postData = array();
    	 
    	$user = Utilities::getUserSessionData();
    	$userInfo = Utilities::getUserInfoById($user->id);
    	$oldEmail = $userInfo['Email'];
    	$form = $this->_createFormCsrf();
    	$viewData['csrf'] = $form->get("csrf");
    	$form->setData($this->getRequest()->getPost());
    	if($this->request->isPost() && $form->isValid()){
    		$viewData['isPost'] = true;
    		$viewData['postType'] = $this->request->getPost('type');
    		$postData = $this->_accountPostDataFilter();
    		
    		// 用户名并未修改
    		if ($viewData['postType'] == 'username' && $postData['username'] == $user->userName) {
    			unset($postData['username']);
    		}
    
    		$status = $this->_postDataValidationEngine($postData);
    		
    		//we need to know this, coz when email changed the message will be different
    		if(array_key_exists('newEmail', $postData) && !array_key_exists('newEmail', $status)) {
    			$viewData['isEmailChanged'] = true;
    		}else {
    			$viewData['isEmailChanged'] = false;
    		}
    		
    		if(empty($status)) {
    			$userModel->updateUserAccount($postData);
    			$viewData['postType'] = null;
    		}
    	}
    	 
    	$viewData['errorStatus'] = $status;
    	
    	$userProperties = $userModel->getUserPropertiesData();
    	$userSessionData = Utilities::getUserSessionData(true);
    	$userProperties = array_merge($userProperties, $userSessionData);
    	$userProperties['email'] = $oldEmail;
		
    	$viewData['rawUserProperties'] = $userProperties;
    	if($this->request->isPost() && $form->isValid($this->request->getPost())) {
    		$userProperties = $this->_userPropertiesAppendPostData($userProperties, $postData);
    	}
    	if($viewData['isEmailChanged'] && $viewData['postType'] == null) {
    		$userProperties['email'] = $oldEmail;
    	}
    	$viewData['postUserProperties'] = $userProperties;
    	$form->prepare();
    	return $this->_getViewModel($viewData);
    }
    
    /**
     * For Security
     * Filter some spam data, used for account setting
     *
     * @return Array
     */
    protected function _accountPostDataFilter() {
    
    	$ret = array();
    	$allowed = array(
    		'username',
    		'newEmail', 'firstName', 'lastName',
    		'gender', 'year',
    		'street', 'city', 'postalcode',
    		'cellphone',
    		'oldPassword', 'password', 'passwordRepeat',
    	);
    
    	$postData = $this->request->getPost();
    	foreach ($postData as $key => $value) {
    		if(in_array($key, $allowed)) {
    			$ret[$key] = $value;
    		}
    	}
    
    	$user = Utilities::getUserSessionData();
    	$userInfo = Utilities::getUserInfoById($user->id);
    	$oldEmail = $userInfo['Email'];
    	if(isset($ret['newEmail']) && $ret['newEmail'] == $oldEmail) {
    		unset($ret['newEmail']);
    	}
    
    	if(isset($ret['password']) && !isset($ret['oldPassword'])) {
    		$ret['oldPassword'] = ' ';
    	}
    	if(isset($ret['oldPassword']) && !isset($ret['password'])) {
    		$ret['password'] = ' ';
    	}
    	return $ret;
    }
    
    
    /**
     * Merge real user properties and post data from account setting.
     *
     * @param Array $rawData
     * @param Array $postData
     * @return Array
     */
    protected function _userPropertiesAppendPostData($rawData, $postData) {
    
    	$ret = $rawData;
    	foreach ($postData as $key => $value) {
    		if($key == 'newEmail') {
    			if(in_array('email', array_keys($ret))) {
    				$ret['email'] = $value;
    			}
    		}else {
    			if(in_array($key, array_keys($ret))) {
    				$ret[$key] = $value;
    			}
    		}
    	}
    	return $ret;
    }
    
    /**
     * If in the account setting, user changed his email.
     * Then system will send out an confirmation email.
     * This function is to comfirm your's change of their email.
     *
     */
    public function activateEmailChangeAction() {
    
    	$key = $this->params['key'];
    	$newEmail = base64_decode($this->params['newEmail'] ? $this->params['newEmail'] : "");
    	//clear cookie
    	$userModel = new User();
    	$userModel->logoutAction();
    
    	$status = array();
    	$viewData = array();
    	
    	$data = Data::getInstance();
    	$regex = $data->get('regex');
    	$validatorRegex = new Regex($regex['user_verify']);
    	if(!$validatorRegex->isValid($key) || !($userId = $userModel->checkIfExistsVerifyKey($key))) {
    		$viewData['keyInValid'] = true;
    	}else {
    		$viewData['keyInValid'] = false;
			$postData = array();
    		$postData['newEmail'] = $newEmail;
    		$status = $this->_postDataValidationEngine($postData);
    
    		if(empty($status)) {
    			$userModel->resetEmailProcess($userId,$newEmail);
    			$urlHelper = Util::getViewHelper("GetUrl");
    			$url = $urlHelper('user_settings');
    			return $this->_redirectToUrl($url,302);
    		}
    	}
    	$viewData['status'] = $status;
    	return $this->_getViewModel($viewData);
    }
}
