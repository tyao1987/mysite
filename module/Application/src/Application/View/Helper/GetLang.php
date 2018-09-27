<?php
namespace Application\View\Helper;

use Zend\View\Helper\AbstractHelper;

use Test\Data;
use Test\Util\Timer;

use Application\Service\Resource;

class GetLang extends AbstractHelper {
    
    /**
     * @var array
     */
    protected static $translations = array();

    /**
     * Get translation
     *
     * example: echo $this->GetLang('test ${key1} bb=${key2}', array('key1' =>
     * 'haha', 'key2' => 'dd'));
     * return : test haha bb=dd
     *
     * @param $key string           
     * @param $variables array           
     * @param $locale string           
     * @return string
     */
    
 	public function __invoke($key, $variables = array(), $locale = null) {
        
        Timer::start(__METHOD__);
        
        // 指定语言
        if ($locale === null) {
            $siteSetting = Data::getInstance()->get('siteSetting');
            $locale = $siteSetting['site_locale'];
        }
        // 不存在此种语言的翻译
        if (empty(self::$translations[$locale])) {
            self::$translations[$locale] = Resource::loadTranslations($locale);
        }
        if (isset(self::$translations[$locale][$key])) {
            $lang = self::$translations[$locale][$key];
        } else {
            $lang = "";
        }
        
        if (trim($lang) == '') {
            Timer::end(__METHOD__);
            return (APPLICATION_ENV != 'production') ? $key : '';
        }
        
        if ($variables) {
            $lang = Data::replaceVariables($lang, $variables);
        }
        
        Timer::end(__METHOD__);
        
        return $lang;
    }
}
