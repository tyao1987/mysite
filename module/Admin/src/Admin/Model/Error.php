<?php namespace Admin\Model;use Test\Data;use Zend\Session\Container;
class Error
{
	/**
	 * the storage
	 *
	 * @var zend_session_namespace
	 */
    private $ns;
    
    /**
     * the errors stack
     *
     * @var array
     */
    private $errors;
    
    /**
     * set up the session namespace and load the errors stack
     *
     */
    function __construct()
    {
        $this->ns = new Container('errors');
        if(isset($this->ns->errors)){
            $this->errors = $this->ns->errors;   
        }
    }
    
    /**
     * clear the errors stack
     *
     */
    function clear()
    {
        unset($this->errors);
        $this->updateNs();
    }
    
    /**
     * add an error to the stack
     *
     * @param string $error
     */
    function add($error)
    {
        $this->errors[] = $error;
        $this->updateNs();
    }
    
    /**
     * check to see if any errors are set
     *
     * @return bool
     */
    function hasErrors()
    {
        if(count($this->errors) > 0){
            return true;
        }
    }
    
    /**
     * get the errors stack
     *
     * @return string
     */
    function get()
    {
        return $this->errors;
    }
    
    /**
     * update the storage
     *
     */
    private function updateNs()
    {
        if(isset($this->errors)){
            $this->ns->errors = $this->errors;
        }else{
            unset($this->ns->errors);
        }
    }
}