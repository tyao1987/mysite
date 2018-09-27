<?php
namespace Application\Widget;

class Document extends AbstractWidget {

    protected $_widgetName = "document";

    public function __construct($content, $view) {
        parent::__construct($content, $view);
    }

    /**
     * Widget handler will invoke this function.
     */
    public function getContent() {
        $content = $this->content;
        $pattern = $this->makeWidgetPattern();
        $matches = $this->getWidgetMatches();
        $matchCount = $this->matchCount;
        if ($matchCount) {
            $this->view->placeholder('documentold')->set('documentold');
        }
        return $content;
    }

}