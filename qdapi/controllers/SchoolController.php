<?php
/**
 * 高校接口
 * @version v2.0
 */
class SchoolController extends ApiController
{
	public function __construct()
	{
		parent::__construct();
		$this->data  = $this->input();
	}

	/**
	 * 获取省
	 */
	 public function get_province(){
	 	$province_list = $GLOBALS['db']->getAll("SELECT * FROM " . $GLOBALS['ecs']->table('province'));
	 	$res = is_array($province_list) ? $province_list : array();

		$datas = array();
		$datas['list'] = $res;
		$this->success($datas);
	 }

	 /**
	  * 获取市
	  */
	  public function get_city(){
	  	$sh_province = $this->data['sh_province'];
	  	$city_list = $GLOBALS['db']->getAll("SELECT * FROM " . $GLOBALS['ecs']->table('city') . " WHERE ci_province = '$sh_province'");
	  	$res = is_array($city_list) ? $city_list : array();

	 	$datas = array();
	 	$datas['list'] = $res;
	 	$this->success($datas);
	  }

	  /**
	   * 获取学校
	   */
	   public function get_school(){
	   	$sh_city = $this->data['sh_city'];
	   	$school_list = $GLOBALS['db']->getAll("SELECT * FROM " . $GLOBALS['ecs']->table('school') . " WHERE sh_city = '$sh_city'");
	   	$res = is_array($school_list) ? $school_list : array();

	  	$datas = array();
	  	$datas['list'] = $res;
	  	$this->success($datas);
	   }
}