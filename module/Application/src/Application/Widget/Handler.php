<?php
namespace Application\Widget;

use Test\Util\Timer;

class Handler {
	
    /* because complex widget may contains simple widget, so complex widget should place first */
	public $widget = array(
		'${'                        => 'Variable',
		'@cmsWidget'				=> 'StaticContent',
	);
	
	public $content;
	public $view;
	 
	public function __construct($content, $view) {
		$this->content = $content;
		$this->view = $view;
	}
	
	public function getContent() {
		$content = $this->content;
		$prefix= 'Application\Widget';
		foreach ($this->widget as $pattern => $widget) {
			if (FALSE !== stripos($content, $pattern)) {
				$class = $prefix.'\\'.$widget;
				Timer::start($class);
				
				$obj = new $class($content, $this->view);
				$content = $obj->getContent();	
				
				Timer::end($class);
			}
		}
		return $content;
	}
}
