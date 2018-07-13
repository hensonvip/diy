<?php
namespace app\mobile\controller;
use think\Controller;

class Index extends Common
{

	public function __construct()
    {
        parent::__construct();
        $this->user_id = session('user_id') ? session('user_id') : 0;
        $this->assign('footer_on','index');//底部高亮
    }

    public function index()
    {
    	$api = "index/all";
        $data = array();
        $data['user_id'] = $this->user_id;
    	$result = $this->curlGet($api,$data);
    	$result = json_decode($result,true);
        //print_r($result['data']);die;
        $this->assign('data',$result['data']);

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
