<?php
namespace app\home\controller;
use anerg\OAuth2\OAuth;
use think\Controller;
use think\Session;

class Finds extends Common
{

    public function __construct()
    {
        parent::__construct();
    }


    // 发现列表
    public function index()
    {
		//我的订单
		$url = "user/getUserOrder";
        $data = array();
        $data['user_id'] = $this->user_id;
        $data['page_size'] = 10;//显示数据数量
        $data['page'] = 1;
        $result = $this->curlGet($url,$data);
        $result = json_decode($result,true);//json转数组

        //作品列表的对应领域名称
        $url = "Finds/findsName";
        $fieldName_arr = array();
        $sort = array();
        $fieldName_arr['field'] = input('field',0,'intval');
        $sort['sort_type'] = input('field',0,'intval');
        if($fieldName_arr['field']!=''){
            $fieldName_data = $this->curlGet($url,$fieldName_arr);
            //var_dump($fieldName_data);exit;
            $fieldName= json_decode($fieldName_data,true);//json转数组
            //var_dump($fieldName);exit;
            $this->assign('fieldName',$fieldName['data']);
        }else{
            $this->assign('fieldName',0);
        }

        //url拼接参数，筛选，排序
        //$fieldUrl='field/'.$fieldName_arr['field'];

        //$this->assign('fieldName',$fieldName['data']);


		//常用领域列表
        $url = "Finds/getCommonFields";
        $common_data = array();
        $common_field = $this->curlGet($url,$common_data);
        $common_field = json_decode($common_field,true);//json转数组
        //print_r($common_field);
        $this->assign('common_field_list',$common_field['data']);
		
		//所有领域列表
        $url = "Finds/getFields";
        $all_data = array();
        $all_field = $this->curlGet($url,$all_data);
        $all_field = json_decode($all_field,true);//json转数组
         //print_r($all_field);
        $this->assign('all_field_list',$all_field['data']);
		
		//作品列表数据
		$url = "Finds/findsListAll";
        $field_data = array();
        $field_data['field']=input('field',0,'intval');
        $field_data['sort_type']=input('type',0,'intval');
        $data_field = $this->curlGet($url,$field_data);
        $data_fields = json_decode($data_field,true);//json转数组
        //var_dump($data_fields);exit;
        $production_list=array();
        foreach($data_fields['data']['list'] as $key=>$value){
            $production_list[$key]=$value;
            $arr['user_id']=$value['user_id'];
            $arr['find_id']=$value['find_id'];
            $url = "Finds/findUserData";
            $user_data_json = $this->curlGet($url,$arr);
            $user_data=json_decode($user_data_json,true);//json转数组
            $production_list[$key]['user_data']=$user_data['data'];
        }
        //print_r($production_list);
        $this->assign('production_list',$production_list);

        //弹窗数据（ajax） 未完成
        $data_ajax['user_id']=input('user',0,'intval');
        $data_ajax['find_id']=input('find',0,'intval');
        $type=input('type',0,'intval');
        $url = "Finds/findUserData";
        $user_data_json = $this->curlGet($url,$data_ajax);
        $user_data=json_decode($user_data_json,true);//json转数组
        $this->assign('production_list_data',$user_data['data']);
        if($type==1){
            //$this->assign('production_list_data',$user_data['data']);
            echo $user_data_json;exit;

        }

        //
        $url = "Finds/getRecommend";
        $field_data = array();
        $field_data['field']=input('field',0,'intval');
        $field_data['sort_type']=input('type',0,'intval');
        $data_field = $this->curlGet($url,$field_data);
        $data_fields = json_decode($data_field,true);//json转数组

    	return $this->fetch();
    }

    function popup(){
        $data_ajax=array();
        $data_ajax['user_id']=input('user',0,'intval');
        $data_ajax['find_id']=input('find',0,'intval');
        $type=input('type',0,'intval');
        $url = "Finds/findUserData";
        $user_data_json = $this->curlGet($url,$data_ajax);
        if($type==1){
            $user_data=json_decode($user_data_json,true);//json转数组
            $this->assign('production_list_data',$user_data['data']);
            echo $user_data_json;exit;
        }else{
            $user_data=json_decode($user_data_json,true);//json转数组
            $this->assign('production_list_data',$user_data['data']);
            //print_r($user_data);

        }
        return $this->fetch();
    }

    public function details(){


        return $this->fetch();
    }
}
