<?php
namespace app\home\controller;
use think\Controller;

class Brand extends Common
{

	public function __construct()
    {
        parent::__construct();
        $this->assign('footer_on','index');//底部高亮
	
    }

    public function index()
    {
		$api = "category/query";
		$data = array();
		$data['cat_id'] = 0;
		$data['supplier_id'] = '-1';
		$result = $this->curlGet($api,$data);
		$result = json_decode($result,true);
		//print_r($result);die();	
		$this->assign('data',$result['data']);
		
		$this->assign('class','all-type');

		
		$this->render();
    }

}