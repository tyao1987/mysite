<?php
namespace Application\View\Helper;

use Zend\View\Helper\AbstractHelper;

use Test\Data;
use Test\Util\Common;
use Test\Util\Timer;

use Application\Util\Util;
use Application\Service\Cache;

class GetScript extends AbstractHelper {

    public function __invoke() {
        Timer::start(__METHOD__);
        
        $config = Data::getInstance()->get("config");
        $cache = Cache::get("constantCache");
        $cacheKey = Util::makeCacheKey('jsVersion', false);
        $result = $cache->getItem($cacheKey);
        if (!$result) {
            $cacheFile = Util::getWritableDir('dataCache') . "jsVersion";
            $jsVersion = Common::readFile($cacheFile);
            $jsVersion = (!empty($jsVersion))?$jsVersion:date('Ymd');
            $result = '<script type="text/javascript" src="' . $config['jsfile'] . '?v=' . $jsVersion . '"></script>';
            $cache->setItem($cacheKey, $result);
        }
        
        
        Timer::end(__METHOD__);
        return $result;
    }

}
