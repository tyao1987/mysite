<?php
namespace Test;

use Exception;

class Data {
    
	/**
	 * @var Data 单例
	 */
	protected static $instance = null;
	
	/**
	 * @var array 只读数据
	 */
	protected $data = array();
	
	/**
	 * @var array Callback 调用
	 */
	protected $callbacks = array();
	
	/**
	 * @var array 完整数据缓存，设置数据时会被重置
	 */
	protected $fullData = null;
	
	private function __construct() {
		$this->data = array();
		$this->fullData = null;
	}
	
	/**
	 * 单例模式
	 * 
	 * @return Data
	 */
	public static function getInstance() {
		if (null === static::$instance) {
			static::$instance = new self;
		}
		return static::$instance;
	}
	
	/**
	 * 根据 name 获取数据内容
	 * 
	 * @param string $name 数据存储的 key
	 * @throws Exception
	 */
	public function get($name) {
	    $data = null;
	    if (property_exists($this, $name)) {
			$data = $this->{$name};
		} else if (array_key_exists($name, $this->data)) {
			$data = $this->data[$name];
		} else {
			throw new Exception($name . ' doesn\'t exist.');
		}
		
		if (isset($this->callbacks[$name]) && is_callable($data)) {
		    $args = func_get_args();
		    array_shift($args);
		    $data = call_user_func_array($data, $args);
		}
		
		return $data;
	}
	
	/**
	 * 根据 name 设置数据内容
	 * 
	 * @param string $name 数据存储的 key
	 * @param mix $value 数据内容
	 * @param boolean $readonly 是否只读
	 * @param boolean $callback 是否是回调函数
	 * @throws Exception
	 * @return Data Data Instance
	 */
	public function set($name, $value, $readonly = false, $callback = false) {
	    
	    // 不允许重复设置只读数据，同时也不允许覆盖保留属性
		if (array_key_exists($name, $this->data) 
		    || in_array($name, array('callbacks', 'data', 'fullData'))) {
			throw new Exception('Readonly data: ' . $name . '.');
		}
		
		// 强制将 callback 置为只读
		if ($callback) {
		    $readonly = true;
		    $this->callbacks[$name] = true;
		}
		
		if ($readonly) {
			if (property_exists($this, $name)) {
				throw new Exception('Already have writable data: ' . $name . '.');
			}
			$this->data[$name] = $value;
		} else {
			$this->{$name} = $value;
		}
		
		// 清空缓存
		$this->fullData = null;
		
		return $this;
	}
	
	/**
	 * 检查指定的数据是否存在
	 * 
	 * @param string $name 数据存储的 key
	 * @return boolean
	 */
	public function has($name) {
	    return (array_key_exists($name, $this->data) || property_exists($this, $name));
	}
	
	/**
	 * 获取全部数据
	 * 
	 * @return array
	 */
	public function getData() {
		if (null === $this->fullData) {
			$objectVars = get_object_vars($this);
			unset($objectVars['data']);
			unset($objectVars['fullData']);
			unset($objectVars['callbacks']);
			$this->fullData = array_merge($objectVars, $this->data);
		}
		return $this->fullData;
	}
	
	public function __get($name) {
		return $this->get($name);
	}
	
	public function __set($name, $value) {
		return $this->set($name, $value);
	}
	
	public function __isset($name) {
		return (array_key_exists($name, $this->data) || property_exists($this, $name));
	}
	
	public function __unset($name) {
		if (array_key_exists($name, $this->data)) {
			throw new Exception('Readonly data: ' . $name . '.');
		}
	}
	
	/**
	 * 获取指定的变量值
	 *
	 * <code>
	 * $pattern = 'parent.child';
	 * $variables = array(
	 * 		'parent' => array('child' => 'CHILD'),
	 * 		'demo' => 'DEMO',
	 * );
	 * echo Data::getVariables($pattern, $variables); // CHILD
	 * </code>
	 *
	 * @param string $pattern
	 * @param array $variables
	 * @return mixed
	 */
	public static function getVariables($pattern, array $variables = array()) {
	
		$value = false;
	
		// 如果是以 . 分隔，则变量是多维数组或对象
		if (false !== strpos($pattern, '.')) {
	
			// 以 . 分隔字符串
			$parts = explode('.', $pattern);
	
			// 如果变量存在
			if (!empty($variables[$parts[0]])) {
	
				while ($parts && $variables) {
					$key = array_shift($parts);
					if (is_array($variables)) {
						$value = (isset($variables[$key])) ? $variables[$key] : false;
						$variables = ($value !== false) ? $variables[$key] : false;
					} else if (is_object($variables)) {
						$value	= (isset($variables->{$key})) ? $variables->{$key} : false;
						$variables = ($value !== false) ? $variables->{$key} : false;
					} else if (is_string($variables)) {
						$value = $variables;
						$variables = false;
					}
	
					if ($value === false) {
						break;
					}
				}
			}
	
			// 否则，按普通数组处理
		} else if (array_key_exists($pattern, $variables)) {
			$value = $variables[$pattern];
		}
	
		return $value;
	}
	
	/**
	 * 替换内容中的变量标签
	 *
	 * <code>
	 * $content = '"${KEY_NOT_EXISTS}" == "" | ${demo} == DEMO | second ${demo} == DEMO | ${parent.child} == CHILD | \${demo} will not be replaced';
	 * $variables = array(
	 * 		'parent' => array('child' => 'CHILD'),
	 * 		'demo' => 'DEMO',
	 * );
	 * echo Data::replaceVariables($content, $variables);
	 * </code>
	 *
	 * @param string $content
	 * @param array $variables
	 * @return string
	 */
	public static function replaceVariables($content, array $variables = array()) {
	
		// 如果内容为空，直接返回
		if (empty($content)) {
			return '';
		}
	
		// 合并自身数据
		$variables = array_merge(static::getInstance()->getData(), $variables);
		
		$content = trim($content);
	
		$regex = static::getInstance()->get('regex');
		// 获取内容中的变量标签
		if (preg_match_all('/(?<!\\\)\$\{([-_\.a-zA-Z0-9]+)\}/i', $content, $matches)) {
	
			// 存放已经处理过的标签
			$replacedVariables = array();
	
			foreach($matches[1] as $match) {
	
				// 如果当前标签没有处理过
				if (!in_array($match, $replacedVariables)) {
	
					$replacement = static::getVariables($match, $variables);
	
					if (false === $replacement
							|| (!is_string($replacement) && !is_numeric($replacement))) {
								$replacement = '';
							}
	
					// 替换变量
					$content = str_replace('${' . $match . '}', $replacement, $content);
	
					// 已处理
					$replacedVariables[] = $match;
				}
			}
		}
	
		return $content;
	}
}