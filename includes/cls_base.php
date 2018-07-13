<?php
/**
 * 商品模块
 * @2016-10-26 cyq
 */

if (!defined('IN_ECS'))
{
	die('Hacking attempt');
}

class cls_base
{
	protected $_db                = null;
	protected $_tb_user           = null;
	protected $_now_time          = 0;
	protected $_mc_time			  = 0;
	protected $_plan_time		  = 0;
	protected $_mc				  = null;
	protected static $_instance   = null;
	public static $_errno = array(
			1 => '操作成功',
			2 => '参数错误',
			3 => '分类不存在',
	);

	function __construct()
	{
		$this->_now_time         = time();
		$this->_plan_time 		 = 3600*24*15;
	}


	public static function getInstance()
	{
		if (self::$_instance === null)
		{
			$instance = new self;
			self::$_instance = $instance;
		}
		return self::$_instance ;
	}


    public function intToString($arr){
        // 将数字类型转成字符串
        foreach($arr as $key => $value){
            if(is_numeric($value) and !is_array($value)){
                $arr[$key] = (string)$value;
            }
            if(is_array($value)){
                $arr[$key] = self::intToString($value);
            }
        }
        return $arr;
    }
}
