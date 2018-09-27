<?php 
namespace Admin\Util;

use Zend\Filter\StripTags;
class Post
{
    static function get($key)
    {
        $filter = new StripTags();
        $post = self::toObject();
        return trim($filter->filter($post->$key));
    }
    static function toObject()
    {
        $post = new \stdClass();
        foreach ($_POST as $k => $v)
        {
            if(is_array($v)) {
                $post->$k = $v;
            }else{
                $post->$k = stripslashes($v);
            }
        }
        return $post;
    }
    static function has($key){
        if(isset($_POST[$key])){return true;}
    }
}