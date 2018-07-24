<?php
namespace app\mobile\controller;
use think\Controller;
use think\Session;

class Bargain extends Common
{

	public function __construct()
    {
        parent::__construct();
        $this->user_id = session('user_id') ? session('user_id') : 0;
    }

    //砍价商品列表
    public function index()
    {
    	$api = "bargain/query";
        $data = array();
        $data['user_id'] = $this->user_id;
        $data['page_size'] = 10;
        $data['page'] = input('page',1,'intval');
        $data['order'] = input('order','desc','trim');//按字段排序
        $data['sort'] = input('sort','id','trim');//按字段排序
        $data['keywords'] = input('keywords','','trim');//关键字搜索
        if(!empty($data['keywords'])){
            cookie('keywords', cookie('keywords').','.$data['keywords'], 3600*24*7);
        }
        $result = $this->curlGet($api,$data);
        $result = json_decode($result,true);//print_r($result['data']);die;
        $this->assign('data',$result['data']);
        $this->assign('keywords',$data['keywords']);//关键字
        $this->assign('order',$data['order']);
        $this->assign('order_opposite',$data['order'] == 'desc' ? 'asc' : 'desc');
        $this->assign('sort',$data['sort']);
        //异步加载分页数据
        $is_ajax = input('is_ajax','','intval') ? input('is_ajax','','intval') : 0;
        $this->assign('is_ajax',$is_ajax);
        if($is_ajax){
            echo $this->fetch('bargain/goods_list_ajax');exit();
        }
        return $this->fetch();
    }

    //砍价详情
    public function details(){
        $api = "bargain/details";
        $data = array();
        $data['user_id'] = $this->user_id;
        $data['bargain_id'] = input('bargain_id',0,'intval');
        $data['help_user_id'] = input('help_user_id',0,'intval');
        $result = $this->curlGet($api,$data);
        $result = json_decode($result,true);
        if($result['code'] == 500){
            $this->error($result['message']);exit();
        }
        if(!$result['data']){
            $this->error('砍价活动不存在！');exit();
        }
        
        //print_r($result['data']);die;
        $this->assign('data',$result['data']);
        $this->assign('share_url',$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'].'?help_user_id='.$this->user_id);
        $this->assign('site_title',$result['data']['goods_name']);//网页标题
        $this->assign('user_id',$this->user_id);//用户ID
        $this->assign('help_user_id',$data['help_user_id']);//要帮助的用户ID
        return $this->fetch();
    }

    //立即砍价、帮砍
    public function do_bargain(){
        $url = "bargain/doBargain";
        $data = array();
        $data['user_id'] = $this->user_id;
        $data['help_user_id'] = input('help_user_id',0,'intval');
        $data['bargain_id'] = input('bargain_id',0,'intval');
        $result = $this->curlPost($url,$data);
        $result = json_decode($result);//print_r($result);die;
        return $result;
    }

    //砍价记录
    public function logs(){
        $api = "bargain/bargainLog";
        $data = array();
        $data['user_id'] = $this->user_id;
        $data['bargain_id'] = input('bargain_id',0,'intval');
        $data['page_size'] = 10;
        $data['page'] = input('page',1,'intval');
        $data['order'] = input('order','desc','trim');//按字段排序
        $data['sort'] = input('sort','add_time','trim');//按字段排序
        $result = $this->curlGet($api,$data);
        $result = json_decode($result,true);
        if($result['code'] == 500){
            $this->error($result['message']);exit();
        }
        if(!$result['data']){
            $this->error('砍价活动不存在！');exit();
        }
        //print_r($result['data']);die;
        $this->assign('data',$result['data']);

        //异步加载分页数据
        $is_ajax = input('is_ajax','','intval') ? input('is_ajax','','intval') : 0;
        $this->assign('is_ajax',$is_ajax);
        if($is_ajax){
            echo $this->fetch('bargain/logs_list_ajax');exit();
        }
        return $this->fetch();
    }

}
