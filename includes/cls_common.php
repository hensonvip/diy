<?php
/**
 * 公共模块
 */

if (!defined('IN_ECS'))
{
	die('Hacking attempt');
}

class cls_common
{
	protected $_db                 = null;
    protected $_tb_report_reason      = null;
	protected $_now_time           = 0;
	protected $_plan_time		   = 0;
	protected static $_instance    = null;

	function __construct()
	{
        $this->_db                 = $GLOBALS['db'];
        $this->_tb_report_reason   = $GLOBALS['ecs']->table('report_reason');
        $this->_now_time           = time();
        $this->_plan_time          = 3600*24*15;
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

    /**
     * 商品类型
     */
    function get_Report_Reason()
    {
        $sql = "SELECT * FROM " . $this->_tb_report_reason . " WHERE is_show = '1' ORDER BY sort_order ASC, reason_id DESC";
        $reason_list = $this->_db->getAll($sql);
        return $reason_list;
    }

    /**
     * 获取系统设置
     */
    function get_Sys_Cfg() {
        $_CFG = load_config();
        return $_CFG;
    }
}
