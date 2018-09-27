<?php
namespace Admin\Model;

use Test\Data;
use Zend\Session\Container;
class Message
{
    private $ns;
    private $message;
    
    function __construct()
    {
        $data = Data::getInstance();
        if ($data->has('message')) {
            $this->message = $data->get('message');
        }else{
            $m = new Container('message');
            if(isset($m->message)){
                $this->message = $m->message;
            }
        }
    }
    
    function clear()
    {
        unset($this->message);
        $this->updateNs();
    }
    
    function add($message)
    {
        $this->message = $message;
        $this->updateNs();
    }
    
    function hasMessage()
    {
        if($this->message){
            return true;
        }
    }
    
    function get()
    {
        return $this->message;
    }
    
    private function updateNs()
    {
        $data = Data::getInstance();
        $m = new Container('message');
        if(isset($this->message)){
            $data->set('message', $this->message);
            $m->message = $this->message;
        }else{
            unset($m->message);
        }
    }
}