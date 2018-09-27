<?php

namespace Admin\Controller;

use Zend\InputFilter\InputFilter;
use Zend\Filter\StripTags;
use Admin\Model\User;

use Zend\Form\Form;
use Zend\InputFilter\Factory;

use Admin\Model\Auth;
use Admin\Util\Util;
use Zend\Mail\Transport\Sendmail;
use Zend\Mime\Message as MimeMessage;
use Zend\Mime\Part as MimePart;

use Zend\View\Model\ViewModel;
use Zend\Mail\Message;
use Admin\Util\Post;


class AuthController extends AbstractController {

	/**
	 *  Login Validator
	 *
	 * @return multitype:Ambigous <multitype:, multitype:NULL >
	 */
	public function loginAction()
	{
		if(Auth::getIdentity()){
			return $this->redirect()->toUrl('/');
		}
		if($this->getRequest()->isPost())
		{
			$errorResponse = array();
			$data = $this->params()->fromPost();

			$inputFilter = $this->getInputFilter();
			$inputFilter->setData($data);

			if (!$inputFilter->isValid())
			{
				$errorMessage = $inputFilter->getMessages();

				$errorResponse['error'] = $errorMessage;
				return $errorResponse;
			}
			$data = $inputFilter->getValues();

			$auth = new Auth($data['adminUsername'], $data['adminPassword'],$data['adapter']);

			$loginResult = $auth->authenticate();
			if($loginResult)
			{
				return $this->redirect()->toUrl('/');
			}
			else{
				$errorMessage = $auth->getErrorMessage();
				$errorResponse['error'] = $errorMessage;
				return $errorResponse;
			}
		}
	}

	/**
	 *  Log out
	 */
	public function logoutAction(){
		try {

			Auth::destroy();
			return  $this->redirect()->toRoute('default', array('controller'=> 'auth',"action"=>"login"));

		}catch (\Exception $e){
			echo $e->getMessage();
		}
	}

	/**
	 *  Input Filter
	 *
	 * @return \Zend\InputFilter\InputFilter
	 */
	public function getInputFilter()
	{
		$inputFilter = new InputFilter();
		$factory     = new Factory();
		
		$inputFilter->add($factory->createInput(array(
				'name'     => 'adminUsername',
				'required' => true,
				'allowEmpty' => false,
				'filters'  => array(
						array('name' => 'StripTags'),
						array('name' => 'StringTrim'),
				),
				'validators' => array(
						array(
								'name' => 'NotEmpty',
								'options' => array(
										'message' => array(
												\Zend\Validator\NotEmpty::IS_EMPTY => 'You must enter a username.',
										)
								)
						)
				)
		)));
		
		
		$inputFilter->add($factory->createInput(array(
				'name'     => 'adminPassword',
				'required' => true,
				'allowEmpty' => false,
				'filters'  => array(
						array('name' => 'StringTrim'),
				),
				'validators' => array(
						array(
								'name' => 'NotEmpty',
								'options' => array(
										'message' => array(
												\Zend\Validator\NotEmpty::IS_EMPTY => 'You must enter a password.',
										)
								)
						)
				)
		)));
		
// 		$inputFilter->add($factory->createInput(array(
// 				'name'     => 'adapter',
// 				'required' => true,
// 				'allowEmpty' => false,
// 				'filters'  => array(
// 				        array('name' => 'StripTags'),
// 						array('name' => 'StringTrim'),
// 				),
// 				'validators' => array(
// 						array(
// 								'name' => 'NotEmpty',
// 								'options' => array(
// 										'message' => array(
// 												\Zend\Validator\NotEmpty::IS_EMPTY => 'You must select a login adapter.',
// 										)
// 								)
// 						)
// 				)
// 		)));
		
		return $inputFilter;
	}

	/**
	 *  reset password
	 *
	 */
	public function resetPasswordAction()
	{
		if ($this->getRequest()->isPost()) {

			$filter = new StripTags();
			$data = $this->params()->fromPost();
// 			$email = $data['email'];
            $email = Post::get('email');
			$user = new User();
			$match = $user->getUserByUsername($email);
			if($match){
				
				//create the password
				$password = Util::random(10); //10 character random string

				//load the email data
				$data['first_name'] = $match->first_name;
				$data['last_name'] = $match->last_name;
				$data['username'] = $match->email;
				$data['password'] = $password;
				try {
				$subject = 'CMS admininstrator - reset password';
				$content = 'Your new Password: '.$password;
				
				$mail = new Message();
				$mail->setEncoding("UTF-8");
				 
				$mail->setSubject($subject);
				 
				$body = new MimeMessage();
				$content = new MimePart($content);
				$content->type = "text/html";
				$body->setParts(array($content));
				 
				$mail->setbody($body);
				$mail->setFrom('no-reply@cms.com', 'no-reply@cms.com');
				$mail->addTo($email);
				 
				$transport = new Sendmail();
				$transport->send($mail);
				
				$user->tableGateway->update(array('password' => md5($password)), "id={$match->id}");
				$this->_message("Your password has been reset for security and sent to your email address", self::MSG_SUCCESS);
				}catch (\Exception $e){
					$this->_message($e->getMessage(),self::MSG_ERROR);
				}
			}else{
				$this->_message("Sorry, we could not locate your account. Please contact us to resolve this issue.", self::MSG_ERROR);
			}
			$url =  "/auth/login";
			$this->_redirect($url);

		}

	}
	public function noAuthAction()
	{

	}

	public function searchAction()
	{
		if (!$this->getRequest()->isXmlHttpRequest()) {
            return  $this->redirect()->toUrl('/');
        }

        $form = $this->getSearchForm(array_merge($_POST,array('uri'=>$_SERVER['REQUEST_URI'])));

        if ($this->request->isPost()) {
        	if($form->isValid()){
        		$data = $form->getData();
        		$auth = new Auth($data['adminUsername'], $data['adminPassword'],Auth::AUTH_TYPE_LDAP);

        		$authenticate = $auth->getAuthenticate();
        		if($authenticate->isValid()){
        			echo  $authenticate->getService()->getIdentity();
        		}
        		else {
        			echo "\n";
        			foreach($authenticate->getMessages() as $message){
        				if(is_array($message)){
        					echo implode("\n", $message);
        				}else{
        					echo "\n".$message;
        				}
        			}
        		}
        	}else{
        		echo "\n";
        		foreach($form->getMessages() as $message){
        			if(is_array($message)){
        				echo implode("\n", $message);
        			}else{
        				echo "\n".$message;
        			}
        		}

        	}
        	die;
        }

        $viewData = array ();
        $viewData['form'] = $form;
        return new ViewModel ( $viewData );
	}

	public function getSearchForm($data = array()){
		$form = new Form ( );
		$form->setAttribute('class', 'form-horizontal');

		$form->add(array(
				'name' => 'adminUsername',
				'type' => 'Text',
				'options' => array(
						'label' => 'Your PC Username',
				),
				'attributes' => array(
						'id'    => 'adminUsername',
						'class' => 'form-control',
						'required'=>'required',
				),
		));


		$form->add(array(
				'name' => 'adminPassword',
				'type' => 'Password',
				'options' => array(
						'label' => 'Your PC Password',
				),
				'attributes' => array(
						'id'    => 'adminPassword',
						'class' => 'form-control',
						'required'=>'required',
				),
		));

		$form->add(array(
				'name' => 'getLdapIdentity',
				'type' => 'Button',
				'options' => array(
						'label' => 'Search',

				),
				'attributes' => array(
						'id' => 'getLdapIdentity',
						'value' => 'Search Ldap',
						'class' => 'btn btn-primary btn-lg',
						'style'=>'width: 20%',
				),
		));

		$form->add(array('name' => 'uri', 'type' => 'Hidden'));

		$inputFilter = new InputFilter();
		$factory     = new Factory();

		$inputFilter->add($factory->createInput(array(
				'name'     => 'adminUsername',
				'required' => true,
				'allowEmpty' => false,
				'filters'  => array(
						array('name' => 'StripTags'),
						array('name' => 'StringTrim'),
				),
		)));


		$inputFilter->add($factory->createInput(array(
				'name'     => 'adminPassword',
				'required' => true,
				'filters'  => array(
						array('name' => 'StringTrim'),
				),
		)));

		$form->setInputFilter($inputFilter);

		//set data
		if (is_array ( $data )) {
			$form->setData( $data );
		}

		return $form;

	}

}