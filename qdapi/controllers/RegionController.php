<?php
/**
 * 地区接口
 * @version v2.0
 */
class RegionController extends ApiController
{
	public function __construct()
	{
		parent::__construct();
		$this->data  = $this->input();
	}
	
	/**
	 * 获取地区
	 *
	 */
	 public function index(){
		//$region_type = !empty($this->data['region_type']) ? intval($this->data['region_type']) : 0;
		//$parent_id = !empty($this->data['parent_id']) ? intval($this->data['parent_id']) : 0;

		//$res =  $this->get_regions($region_type, $parent_id);
		$res =  $this->action_region_list();
		$datas = array();
		$datas['list'] = $res;
		$this->success($datas);
	 }

	/**
	 * 获得指定国家的所有省份
	 *
	 * @access      public
	 * @param       int     country    国家的编号
	 * @return      array
	 */
	function get_regions($region_type = 0, $parent_id = 0)
	{
	    if(empty($parent_id)){
	        $where = " WHERE region_type = '$region_type'";
	    }elseif(empty($region_type)){
	        $where = " WHERE parent_id = '$parent_id'";
	    }else{
	        $where = " WHERE region_type = '$region_type' AND parent_id = '$parent_id'";
	    }
	    $sql = 'SELECT region_id,parent_id,region_name,region_type FROM ' . $GLOBALS['ecs']->table('region') . $where;

	    return $GLOBALS['db']->GetAll($sql);
	}

	/*
	 * 获取省市区
	 */
	function action_region_list(){
	    //获取省市区
	    $region_list = $GLOBALS['db']->getAll("select region_id,region_name,parent_id from ".$GLOBALS['ecs']->table('region')." where parent_id = 1");

	    foreach ($region_list as $key => $value) {
	        $city = $GLOBALS['db']->getAll("select region_id,region_name,parent_id from ".$GLOBALS['ecs']->table('region')." where parent_id = ".$value['region_id']);
	        $region_list[$key]['city'] = $city;
	        foreach ($city as $ke => $val) {
	            $district = $GLOBALS['db']->getAll("select region_id,region_name,parent_id from ".$GLOBALS['ecs']->table('region')." where parent_id = ".$val['region_id']);
	           $region_list[$key]['city'][$ke]['district']=$district;
	        }
	    }

	    $result = is_array($region_list) ? $region_list : array();
	    return $result;
	}	 	 
	
}