<?php

namespace Application\View\Helper;

use Zend\View\Helper\AbstractHelper;

use Test\Util\Timer;

use Application\Model\ArticleCms;

class GetStaticContent extends AbstractHelper {

    static $site = null;

    public function __invoke($type, $title) {
        
        Timer::start(__METHOD__);
        
        $title = str_replace(" ", "_", $title);
        $articleModel = new ArticleCms();
        $params['title'] = $title;
        
        $description = $this->view->ParseContent($articleModel->fetchContent($type, $params));
        
        Timer::end(__METHOD__);
        
        return $description;
    }

}
