<?php
namespace Admin\View\Helper;

use Zend\View\Helper\AbstractHelper;
class RenderLinks extends AbstractHelper
{

	/**
	 * comments
	 */
	public function __invoke($links, $class, $prependText = null, $appendText = null, $separator = ' | '){
	    $html = "<ol class='{$class}'>";
	    $linkCount = count($links);
        if(is_array($links) && $linkCount > 0){
            
            foreach ($links as $label => $link) {
                $linkClass = strtolower($label);
                $linkClass = str_replace(' ', '_', $linkClass);
                $current = $linkCount == 1 ? " class='active'" : "";
                if(empty($link)) {
                	$hyperlinks[] = "<li{$current}><span class='{$linkClass}'>{$label}</span></li>";
                }else {
            		$hyperlinks[] = "<li{$current}><a href='{$link}' class='{$linkClass}'>{$label}</a></li>";
                }
                $linkCount --;
            }
            $html .= $prependText . implode($separator, $hyperlinks) . $appendText ."</ol>";
            return $html;
        }
	}
}