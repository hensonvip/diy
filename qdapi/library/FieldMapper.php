<?php

/**
 * 接口返回字段映射  
 * 
 * @version v1.0
 * @create 2015-08-05
 * @author veapon(veapon88@gmail.com)
 */
class FieldMapper
{
	private static $_instance = array();

	public static function factory($app_name, $controller)
	{
		$controller = ucfirst($controller);
		$namespace = API_PATH . "mappers/{$app_name}/{$controller}";
		if (isset($_instance[$namespace]))
		{
			return self::$_instance[$namespace];
		}

		$file = "{$namespace}.php";
		if(!file_exists($file))
		{
			self::$_instance[$namespace] = new self();
			return self::$_instance[$namespace];
		}

		require_once($file);
		$class = "{$controller}Mapper";
		if (class_exists($class))
		{
			self::$_instance[$namespace] = new $class();
			return self::$_instance[$namespace];
		} 
		else
		{
			self::$_instance[$namespace] = new self();
			return self::$_instance[$namespace];
		}
	}

	/**
	 * 调用自定义方法处理
	 * 
	 * @package
	 * @version v1.0
	 * @create 2015-10-28
	 * @author veapon(veapon88@gmail.com)
	 */
	public function parse($data, $func)
	{
		if (method_exists($this, $func))
		{
			return $this->$func($data);
		}
		else
		{
			return $data;
		}
	}

	protected function underlineToCamel($str)
	{
		$str = explode('_' , $str);
		foreach($str as $key=>$val)
		{
			$str[$key] = ucfirst($val);
		}

		return lcfirst(implode('' , $str));
	}

	protected function replace($data, $recursive = 0, $map = "underlineToCamel")
	{
		if (empty($data))
		{
			return $data;
		}
		if ($recursive === 0)
		{
			foreach ($data as $field=>$value)
			{
				if (is_array($map) && isset($map[$field]))
				{
					$data[$map[$field]] = is_null($value) ? "" : $value;
					unset($data[$field]);
				}
				else
				{
					$new_field = $this->$map($field);
					if ($new_field != $field)
					{
						$data[$new_field] = is_null($value) ? "" : $value;
						unset($data[$field]);
					}
				}
			}

		}
		else
		{
			foreach ($data as $k=>$v)
			{
				$data[$k] = $this->replace($v, 0, $map);
			}
		}

		return $data;
	}

}

