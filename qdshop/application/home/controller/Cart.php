<?php
namespace app\home\controller;
use think\Controller;
use think\Session;
use think\Request;

class Cart extends Common
{

    public function __construct()
    {
        parent::__construct();
        $this->assign('footer_on','cart');//底部高亮

        //未登录处理
        $this->user_id = session('user_id') ? session('user_id') : '';
        //print_r(session('user_id'));die;
        if(empty($this->user_id)){
            if (Request::instance()->isAjax()){
                $result = array();
                $result['code'] = 401;
                $result['message'] = '请先登录！';
                echo json_encode($result);exit();
            }
            header("Location:".url('User/login'));exit;
        }
    }

    public function index()
    {
        Session::delete('flow_type');//清除购物类型状态

        $url = "cart/get_cart_goods";
        $data = array();
        $data['user_id'] = $this->user_id;
        $arr = input();
        if(isset($arr['sel_goods'])){
            $data['sel_goods'] = implode(',',$arr['sel_goods']);
        }else{
            $data['sel_goods'] = 0;
        }
        $result = $this->curlPost($url,$data);
        //异步加载数据
        $is_ajax = input('is_ajax','','intval') ? input('is_ajax','','intval') : 0;
        if($is_ajax){
            echo $result;exit();
            //echo json_encode($result);exit();
        }
        $result = json_decode($result,true);//json转数组
        //print_r($result);die;

		$this->assign('class','cart');

        $this->assign('data',$result['data']);

		//我的收藏
		/*$url = "user/getUserCollect";
        $data = array();
        $data['user_id'] = $this->user_id;
        $data['page_size'] = 16;//显示数据数量
        $data['page'] = 1;
        $result = $this->curlGet($url,$data);
        $result = json_decode($result,true);//json转数组
        //print_r($result);
		$this->assign('collect',$result['data']);*/

		//浏览历史
		//猜你喜欢(获取关联商品)
		/*$records = $this->getHistory();
		$records_list = array();
		if($records){
			$goods = implode(",",$records);
			$api = "goods/queryMore";
			$data = array();
			$data['goods'] = $goods;
			$records_list = $this->curlGet($api,$data);
			$records_list = json_decode($records_list,true);
			$records_list = $records_list['data']['list'];
		}
		//print_r($records_list);
		$this->assign('records_list',$records_list);*/

		//热销
		/*$api = "goods/query";
		$data = array();
		$data['cat_id'] = 0;
		$data['supplier_id'] = '-1';
		$data['filter'] = 'is_hot';
		$data['size'] = 3	;
		$rexiao = $this->curlGet($api,$data);
		$rexiao = json_decode($rexiao,true);
		//print_r($result);die();
		$this->assign('rexiao',$rexiao['data']);*/

        return $this->fetch();
    }

    /**
     * AJAX获取购物车
     */
    public function get_cart_goods()
    {
        $url = "cart/get_cart_goods";
        $data = array();
        $data['user_id'] = $this->user_id;
        $result = $this->curlPost($url,$data);
        $result = json_decode($result,true);//json转数组
        // print_r($result['data']);die;
        $this->assign('data',$result['data']);
        echo $this->fetch('cart/cart_list_ajax');exit();
    }

    //添加购物车
    public function addToCart()
    {
        $goods_id = input('goods_id',0,'intval');
        $quick = input('quick',0,'intval');//是否立即购买
        $parent = input('parent',0,'intval');//大于0作为配件
        $flow_type = input('flow_type',0,'intval');//扩展属性，0（普通商品）、1（团购商品）、6（预售商品）、7（虚拟团购）、101（砍价）、102（拼团）
        $group_log_id = input('group_log_id',0,'intval');//拼团活动，去拼单的group_lod表的ID
        $number = input('number',1,'intval');
        $is_design = input('is_design',0,'intval');
        $arr = input();
        if (count($arr['attr_id']) < 3){
            $result = array();
            $result['code'] = 500;
            $result['message'] = '请先选择规格属性！';
            echo json_encode($result);exit();
        }
        if(isset($arr['attr_id'])){
            if(is_array($arr['attr_id'])){
                $attr_id = implode(',',$arr['attr_id']);//普通购买是数组
            }else{
                $attr_id = trim($arr['attr_id']);//兼容砍价，砍价是字符串
            }
        }else{
            $attr_id = '';
        }
        $url = "cart/addToCart";
        $data = array();
        $data['user_id'] = $this->user_id;
        $data['goods'] = '{"quick":'.$quick.',"spec":['.$attr_id.'],"goods_id":'.$goods_id.',"number":"'.$number.'","parent":'.$parent.',"flow_type":"'.$flow_type.'","group_log_id":"'.$group_log_id.'","is_design":"'.$is_design.'"}';
        $result = $this->curlPost($url,$data);
        if($quick){
            $result_q = json_decode($result,true);
            if($result_q['code'] == 500){
                $result = json_decode($result);
                return $result;
            }
            $rec_id = $result_q['data']['supplier_list'][0]['goods_list'][0]['rec_id'];
            session::set('sel_goods',isset($rec_id) ? $rec_id : '');//一步购物的商品购物车ID
        }else{
            session::set('sel_goods','');
        }
        session::set('flow_type',$flow_type);//购物类型
        //print_r(json_decode($result,true));die;
        $result = json_decode($result);
        return $result;
    }

    //更新购物车
    public function updateCart(){
        $url = "cart/updateCart";
        $data = array();
        $data['user_id'] = $this->user_id;
        $data['rec_id'] = input('rec_id') ? intval(input('rec_id')) : 0;
        $data['goods_id'] = input('goods_id') ? intval(input('goods_id')) : 0;
        $data['number'] = input('number') ? intval(input('number')) : 1;
        $data['suppid'] = input('suppid') ? intval(input('suppid')) : 0;
        $data['spec'] = input('spec', '');  //选择的属性
        $arr = input();
        if(isset($arr['sel_goods'])){
            $data['sel_goods'] = implode(',',$arr['sel_goods']);
        }else{
            $data['sel_goods'] = 0;
        }
        $result = $this->curlPost($url,$data);
        //print_r(json_decode($result,true));die;
        $result = json_decode($result);
        return $result;
    }

    //删除购物车
    public function deleteCart(){
        $url = "cart/dropCart";
        $data = array();
        $data['user_id'] = $this->user_id;
        // $data['rec_id'] = input('rec_id') ? intval(input('rec_id')) : 0;
        $data['rec_id'] = input('rec_id', 0);
        $result = $this->curlPost($url,$data);
        //print_r(json_decode($result,true));die;
        $result = json_decode($result);
        return $result;
    }

    // 属性编辑框
    public function attrBox() {
        $api = "goods/getGoodsInfo";
        $data = array();
        $data['user_id'] = $this->user_id;
        $data['goods_id'] = input('goods_id', 0);//商品ID，必填
        $result = $this->curlGet($api,$data);
        $result = json_decode($result,true);
        // print_r($result['data']);die;
        $this->assign('goods_data', $result['data']);
        $goods_attr_id = input('goods_attr_id');//商品属性“3358,3360,3361”
        $goods_attr_id = explode(',', $goods_attr_id);
        $this->assign('goods_attr_id', $goods_attr_id);
        $rec_id = input('rec_id', 0);//购物车ID
        $this->assign('rec_id', $rec_id);
        $supp_id = input('supp_id', 0);//入驻商ID
        $this->assign('supp_id', $supp_id);
        $this->assign('goods_id', $data['goods_id']);
        echo $this->fetch('cart/attr_box_ajax');exit();
    }
}
