<?php
namespace Application\Controller;

use Test\Util\Common;
use Test\Data;

use Application\Util\Util;
use Application\Service\Cache;

class MemcacheController extends AbstractController {

    public function preDispatch($e) {
        parent::preDispatch($e);
    }

    public function postDispatch($e) {
        parent::postDispatch($e);
    }

    /**
     * Wrapper action for clear one single host(local) cache.
     * You can call this action from local host, or from other host.
     */
    public function clearCacheAction() {
        ob_end_clean();
        ob_end_clean();
        $this->clearAllCache();
        $content = "success: clear current host all cache";
        echo $content;
        exit();
    }

    //只用于cms server，只需执行1次
    //public function clearConfigCacheAction(){
        //$config = include(ROOT_PATH.'/config/application.config.php');
        //$files = glob($config['module_listener_options']['cache_dir'].'/*{app_config*,module_map}.php',GLOB_BRACE);
        //foreach ($files as $file){
            //unlink($file);
        //}
        //exit();
    //}
    
    public function clearAllCache() {
        $this->clearAllMemcache();
        return true;
    }

    public function clearAllMemcache() {
        
        Cache::get('cache')->flush();
        Cache::get('constantCache')->flush();
        Cache::get('dynamicCache')->flush();
        
        return true;
    
    }

}
