<?php
namespace Application\Model;

use Application\Util\Util;

use Test\Data;
use Test\Util\Common;

use Zend\Captcha\Image;
use Zend\Session\Container;
use Zend\Mail\Message;

class Utilities {
	
	
	static public function checkLogin() {

		$userModel = new User();
		$userModel->checkLoginCookie();
		$user = self::getUserSessionData();
	
		if (isset($user->id) && $user->id > 0) {
			return $user;
		}
		return false;
	}
	
	/*
	 * if(true)
		* 		return $ar['id'], $ar['userName'],
	* else
		* 		return $obj->id, $obj->userName,
	*/
	static public function getUserSessionData($returnArray = false) {
		
		$userNamespace = new Container('User_Auth');
		
		$ret = new \stdClass();
		$ret->id = $userNamespace->id;
		$ret->userName = $userNamespace->userName;
		 
		if($returnArray) {
			$ret = self::objToArray($ret);
		}
		return $ret;
	}
	
	static public function objToArray($obj) {
		$ret = array();
		if(is_array($obj) || is_object($obj)){
			foreach($obj as $key => $value) {
				$ret[$key] = self::objToArray($value);
			}
		}else {
			return $obj;
		}
		return $ret;
	}
	
	static public function getUserInfoById($userId) {
			
		$userModel = new User();
		return $userModel->getUserInfoById($userId);
	}
	
	static public function getCaptchaUrl() {
		 
		$captcha = self::getCaptchaObj();
		$id = $captcha->getId();
		return array(
			'url' 	=> $captcha->getImgUrl() . $id . $captcha->getSuffix(),
			'id' 	=> $id
		);
	}
	
	static protected function getCaptchaObj() {

	    $data = Data::getInstance();
		if($data->has('Zend_Registration_Captcha')) {
			return $data->get('Zend_Registration_Captcha');
		}else {
			$captcha = new Image();
			$captcha->setImgDir(Util::getWritableDir('captcha'));
			$captcha->setImgUrl("/images/captcha/");
			$captcha->setName('captcha');
			$captcha->setFont(ROOT_PATH.'/data/fonts/BRITANIC.TTF');
			$captcha->setFontSize(30);
			$captcha->setWordlen(4);
			$captcha->setWidth(120);
			$captcha->setHeight(60);
			$captcha->setExpiration(10);
			$captcha->generate();
			$data->set('Zend_Registration_Captcha', $captcha);
			return $captcha;
		}
	}
	
	static public function checkCaptchaCode($params,$isAjax = false) {
		
		$name = 'captcha';
		if (!isset($params[$name]['value'])) {
			return false;
		}
		if (!isset($params[$name]['id'])) {
			return false;
		}
		$value = strtolower($params[$name]['value']);
		$id = $params[$name]['id'];
		$nameSpace = 'Zend_Form_Captcha_' . $id;
		if(!$isAjax) {
			$session = new Container($nameSpace);
			$word = strtolower($session->word);
		}else {
			$word = $_SESSION[$nameSpace]['word'];
			$word = strtolower($word);
		}
		if ($value !== $word) {
			return false;
		}
	
		return true;
	}
	
	/**
	 * Usage:
	 * 	$mail = Utilities::getSiteMailerInstance();
	 $mail->addTo('joe_chen@mezimedia.com');
	 $mail->setSubject($subject);
	 $mail->setBodyHtml($htmlContent);
	 $mail->setBodyText($textContent);
	 $mail->send();
	
	 $mail = Utilities::getSiteMailerInstance(true);
	 $mail->addTo('joe_chen@mezimedia.com');
	 $mail->setSubject($subject);
	 $mail->setBodyHtml($htmlContent);
	 $mail->setBodyText($textContent);
	 $mail->send();
	 *
	 */
	static public function getSiteMailerInstance($newInstance = false) {
		
		$data = Data::getInstance();
		$siteSetting = $data->get('siteSetting');
		
		$senderEmail = $siteSetting['mail_default_email'];
		$senderName = $siteSetting['mail_default_email_sender'];
		if($newInstance) {
			$mail = new Message();
			$mail->setEncoding("UTF-8");
			$mail->setFrom($senderEmail, $senderName);
			return $mail;
		}else {
			if($data->has('Site_Mailer')) {
				return $data->get('Site_Mailer');
			}
			$mail = new Message();
			$mail->setEncoding("UTF-8");
			$mail->setFrom($senderEmail, $senderName);
			$data->set('Site_Mailer', $mail);
			return $mail;
		}
	}
}
