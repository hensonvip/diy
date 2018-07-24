<?php
namespace app\home\controller;
use think\Controller;

class Index extends Common
{
	public function __construct()
    {
        parent::__construct();
    }

    public function index()
    {
        $this->site_title .= ' - 首页';

		$api = "index/head";
		$result = $this->curlGet($api);
		$result = json_decode($result,true);
		$this->assign('head',$result['data']);
        $this->assign('site_title', $this->site_title);

        $api = "goods/query";
        $data = array();
        $data['user_id'] = $this->user_id;
        $data['cat_id'] = input('cat_id',0,'intval');
        $data['brand'] = input('brand',0,'intval');
        $data['size'] = 6;
        $data['page'] = input('page',1,'intval');
        $data['shop_price'] = input('shop_price',0,'intval');//价格
        $data['sex'] = input('sex','','trim');//款式（男，女）
        $data['order'] = input('order','desc','trim');//按字段排序
        $data['sort'] = input('sort','sort_order','trim');//按字段排序
        $data['filter'] = input('filter','0','trim');//类型
        $data['keywords'] = input('keywords','','trim');//关键字搜索
        $data['is_real'] = input('is_real',3,'intval');//0虚拟商品 1真实商品
        if(!empty($data['keywords'])){
            cookie('keywords', cookie('keywords').','.$data['keywords'], 3600*24*7);
        }
        $result = $this->curlGet($api,$data);
        $result = json_decode($result,true);
        //print_r($result['data']);die;
        $this->assign('data',$result['data']);
        $this->assign('cat_id',$data['cat_id']);//分类
        $this->assign('brand',$data['brand']);//品牌
        $this->assign('filter',$data['filter']);
        $this->assign('keywords',$data['keywords']);//关键字
        $this->assign('order',$data['order']);
        $this->assign('order_opposite',$data['order'] == 'desc' ? 'asc' : 'desc');
        $this->assign('sort',$data['sort']);
        $this->assign('sex',$data['sex']);
        $this->assign('shop_price',$data['shop_price']);
        $this->assign('is_real',$data['is_real']);
        //异步加载分页数据
        $is_ajax = input('is_ajax','','intval') ? input('is_ajax','','intval') : 0;
        $this->assign('is_ajax',$is_ajax);
        if($is_ajax){
            echo $this->fetch('goods/goods_list_ajax');exit();
        }

        //筛选价格列表
        $api = "index/getPrice";
        $result = $this->curlGet($api);
        $result = json_decode($result,true);
        $this->assign('price_list',$result['data']);

        // 分类
        $api = "category/getSubCat";
        $data = ['cat_id' => 85];
        $result = $this->curlGet($api, $data);
        $result = json_decode($result, true);
        $this->assign('category_list', $result['data']);

        // 私信举报原因
        $url = "user/getReportReason";
        $data = array();
        $result = $this->curlGet($url);
        $result = json_decode($result,true);//json转数组
        $this->assign('reason_list', $result['data']);

		return $this->fetch();
    }

}