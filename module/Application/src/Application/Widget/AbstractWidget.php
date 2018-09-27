<?php

namespace Application\Widget;

class AbstractWidget {

    public $content = null; // contents
    
    public $view = null;

    public $extraParams = array();

    public $matches = null;

    public $matchCount = 0;

    protected $_regFlag = 's'; // regexp flag, such as U, i(ignore case), s(.// match new line).
    
    protected $_delimiter = '`'; // regexp delimiter, using when construct pattern
    
    protected $_widgetName = null;

    protected $_pattern = null;

    protected $_mapping = array();

    protected $_defaults = array();

    public function __construct($content, $view) {
        $this->content = $content;
        $this->view = $view;
        $this->init();
    }

    protected function init() {}

    /**
     * Widget handler will invoke this function.
     */
    public function getContent() {
        $content = $this->content;
        $pattern = $this->makeWidgetPattern();
        $matches = $this->getWidgetMatches();
        $matchCount = $this->matchCount;
        if ($matchCount) {
            $params = $this->constructParams();
            $replaceContents = $this->getReplaceContents($params);
            $content = str_ireplace($matches[0], $replaceContents, $content);
            $this->content = $content;
        }
        return $content;
    }

    public function setExtraParams($extraParams) {
        $this->extraParams = $extraParams;
    }

    public function setWidgetName($widgetName) {
        $this->_widgetName = $widgetName;
    }

    /**
     * construct the widget pattern which is used to match contents.
     * 
     * @param $patternType int|string
     *            indicate the pattern type:
     *            0(default) format like <!-- @translate ... -->
     *            1 format like $(translate ... )
     * @return string $constructed pattern
     */
    protected function makeWidgetPattern($patternType = 0) {
        $widgetName = $this->_widgetName;
        $delimiter = $this->_delimiter;
        $regFlag = $this->_regFlag;
        if (!$widgetName) {
            return false;
        }
        switch ($patternType) {
            case 1:
                $pattern = "\\\$\($widgetName\s*([^\)]*?)\s*\)";
                break;
            case 0:
            default:
                $pattern = "<!--\s*@$widgetName\s*(.*?)\s*-->";
        }
        $pattern = "{$delimiter}$pattern{$delimiter}$regFlag";
        $this->_pattern = $pattern;
        return $pattern;
    }

    /**
     * Merge array values: values from getWidgetParams(), $defaults,
     * $extraParams,
     * usually used for constuct API params or other.
     *
     * @param
     *            array defaults default param values
     * @param
     *            array extraParams extra param values
     * @return array constructed params.
     */
    protected function constructParams($defaults = null, $extraParams = null) {
        $ret = array();
        $defaults = isset($defaults)?$defaults:$this->_defaults;
        $extraParams = isset($extraParams)?$extraParams:$this->extraParams;
        $params = $this->getWidgetParams();
        if (false === $params) {
            return false;
            // throw new Widget_Exception('cannot get wiget params');
        }
        if ($params) {
            foreach ($params as $item) {
                $item = $this->replaceKeys($item);
                $item = array_merge($defaults, $item);
                $item = array_merge($extraParams, $item);
                $ret[] = $item;
            }
        } else {
            $item = array();
            $item = array_merge($defaults, $item);
            $item = array_merge($extraParams, $item);
            $ret[] = $item;
        }
        return $ret;
    
    }

    protected function replaceKeys($array = null, $mapping = null) {
        $mapping = isset($mapping)?$mapping:$this->_mapping;
        if (!$mapping) {
            return $array;
        }
        $ret = array();
        foreach ((array)$array as $key => $val) {
            if (array_key_exists($key, $mapping)) {
                $ret[$mapping[$key]] = $val;
            } else {
                $ret[$key] = $val;
            }
        }
        return $ret;
    }

    /**
     * Main method, use preg_match_all to parse contents.
     *
     * @param $content string
     *            string html contents.
     * @param $pattern string
     *            regexp pattern.
     * @return array preg_match_all returned array.
     */
    protected function getWidgetMatches($content = null, $pattern = null) {
        $content = isset($content)?$content:$this->content;
        $pattern = isset($pattern)?$pattern:$this->_pattern;
        if (!$content || !$pattern) {
            return false;
        }
        $matchCount = preg_match_all($pattern, $content, $matches);
        $this->matchCount = $matchCount;
        $this->matches = $matches;
        return $matches;
    }

    /**
     * parse contents and collect widget inner params.
     *
     * @param $content string
     *            string contents for parse.
     * @param $pattern string
     *            widget regexp pattern
     * @return false array when not pattern match, other return array.
     *        
     */
    protected function getWidgetParams($content = null, $pattern = null) {
        if (!isset($this->matches)) {
            $this->getWidgetMatches($content, $pattern);
        }
        if (!$this->matchCount) {
            return false;
        }
        $matches = $this->matches;
        $paramMatches = $matches[1];
        $result = array();
        for ($i = 0; $i < $this->matchCount; $i++) {
            $params = array();
            $item = $paramMatches[$i];
            if (strlen($item)) {
                preg_match_all('/[\w\-]+=["][^\"]+["]/', $item, $attrs);
                $attrs = $attrs[0];
                foreach ((array)$attrs as $attr) {
                    if (strlen($attr)) {
                        list($key, $value) = explode("=", $attr, 2);
                        $value = preg_replace('/^["](.*)["]$/', '\1', $value);
                        $params[$key] = $value;
                    }
                }
            }
            $result[] = $params;
        }
        return $result;
    }

}
