<?php

namespace Application\Widget;

class StaticContent extends AbstractWidget {

    protected $_widgetName = "cmsWidget";

    public function getReplaceContents($paramsArray) {
        
        $ret = array();
        foreach ($paramsArray as $params) {
            if (substr($params['node'], -5) == '.html') {
                $params['node'] = substr($params['node'], 0, -5);
            }
            $content = $this->view->GetStaticContent('document', $params['node']);
            $ret[] = $content;
        }
        
        return $ret;
    }
}
