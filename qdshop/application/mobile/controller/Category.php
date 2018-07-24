<?php
namespace app\mobile\controller;
use think\Controller;

class Category extends Common
{

	public function __construct()
    {
        parent::__construct();
        $this->user_id = session('user_id') ? session('user_id') : 0;
        $this->assign('footer_on','category');//底部高亮
    }

    //所有分类
    public function index()
    {
    	$api = "category/query";
        $data = array();
        $data['cat_id'] = '0';
        $data['supplier_id'] = '-1';
        $result = $this->curlGet($api,$data);
        $result = json_decode($result,true);//print_r($result);die;
        $this->assign('data',$result['data']);
        //$this->assign('data',$result['data']['category_list']);
        
        //获取站内信数量
        $url = "user/getUserInfo";
        $data = array();
        $data['user_id'] = $this->user_id;
        $result = $this->curlGet($url,$data);
        $result = json_decode($result,true);//json转数组
        //print_r($result);die;
        $this->assign('userinfo',$result['data']);

    	return $this->fetch();
    }


}
