<?php
namespace app\mobile\controller;
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
        if($result['data']['supplier_list']){
            foreach ($result['data']['supplier_list'] as $k => $v) {
                //清除之前记录的cookies值
                cookie('shipping_list'.$v['supplier_id'],null);
                cookie('bonus'.$v['supplier_id'],null);
                cookie('message'.$v['supplier_id'],null);
            }
        }
        $this->assign('data',$result['data']);
        return $this->fetch();
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
        $arr = input();
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
        $data['goods'] = '{"quick":'.$quick.',"spec":['.$attr_id.'],"goods_id":'.$goods_id.',"number":"'.$number.'","parent":'.$parent.',"flow_type":"'.$flow_type.'","group_log_id":"'.$group_log_id.'"}';
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
        $data['rec_id'] = input('rec_id') ? intval(input('rec_id')) : 0;
        $result = $this->curlPost($url,$data);
        //print_r(json_decode($result,true));die;
        $result = json_decode($result);
        return $result;
    }

    //选择赠品
    public function gift(){
        $goods_id = input('goods_id',0,'intval');

        $api = "cart/getGift";
        $data = array();
        $data['user_id'] = $this->user_id;
        $data['goods_id'] = $goods_id;
        $result = $this->curlGet($api,$data);
        $result = json_decode($result,true);
        //print_r($result['data']);die;
        $this->assign('data',$result['data']);
        return $this->fetch();
    }

    //提交选中赠品
    public function do_gift(){
        $act_id = input('act_id',0,'intval');
        $arr = input();
        $sel_goods = implode(',',$arr['gift']);

        $api = "cart/addGiftToCart";
        $data = array();
        $data['user_id'] = $this->user_id;
        $data['act_id'] = $act_id;
        $data['sel_goods'] = $sel_goods;
        $result = $this->curlGet($api,$data);
        $result = json_decode($result);
        return $result;
    }


}
