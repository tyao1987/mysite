<?php
namespace Application\Widget;

use Application\Util\Util;

class Variable extends AbstractWidget {

	public function __construct($content, $view) {
		
		parent::__construct($content, $view);
		
		// get tplParams
		$tplParams = $this->view->tplParams;

		$content = Util::replaceVariables($content, $tplParams);

        $this->content = $content;
	}
}
