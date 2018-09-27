<?php
namespace Application\View\Helper;

use Zend\View\Helper\AbstractHelper;

use Test\Data;
use Test\Util\Common;
use Test\Util\Timer;

use Application\Util\Util;
use Application\Service\Cache;

class GetStyle extends AbstractHelper {

    public function __invoke() {
        
        Timer::start(__METHOD__);
        
        $siteObj = Data::getInstance()->get("site");
        $config = Data::getInstance()->get("config");
        
        $host = $config['styleServer'];
        $cache = Cache::get("constantCache");
        $cacheKey = Util::makeCacheKey('cssVersion', false);
        if (null === ($cssVersion = $cache->getItem($cacheKey))) {
            $cssVersion = array();
            $cacheFile = Util::getWritableDir('dataCache') . "cssVersion";
            $result = Common::readFile($cacheFile);
            
            if ($result) {
                $cssVersion = (array)unserialize($result);
            }
            $cache->setItem($cacheKey, $cssVersion);
        }
        
        $return = '';
        $dateTime = new \DateTime();
        $defaultVersion = $dateTime->format('Ymd');
        
        // 手机站点不需要添加 core 和 noscript
        $return .= '<link media="screen,monitor,print" href="';
        
        if ($siteObj['site_type'] == 'MainSite') {
        	$version = (!empty($cssVersion['core'])) ? $cssVersion['core'] : $defaultVersion;
            $return .= $host . '/styles/core.css?v=' . $version;
        } else {
            $version = (!empty($cssVersion['core_distribution'])) ? $cssVersion['core_distribution'] : $defaultVersion;
            $return .= $host . '/styles/core_distribution.css?v=' . $version;
        }
        $return .= '" type="text/css" rel="stylesheet"/>';
        
        
        // hanlde overriding style
        $version = (!empty($cssVersion['override_' . $siteObj['short_name']])) ? $cssVersion['override_' . $siteObj['short_name']] : $defaultVersion;
        $return .= "\n";
        $return .= '<link media="screen,monitor,print" href="';
        $return .= $host . '/styles/override_' . $siteObj['short_name'] . '.css?v=' . $version;
        $return .= '" type="text/css" rel="stylesheet"/>';
        
        Timer::end(__METHOD__);
        
        return $return;
    }

}
