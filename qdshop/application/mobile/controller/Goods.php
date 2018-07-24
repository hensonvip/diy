<?php
namespace app\mobile\controller;
use think\Controller;
use think\Session;
use think\Cookie;
use hunuo\shop\payment;


class Goods extends Common
{

	private $return; //内部返回

	public function __construct()
    {
        parent::__construct();
        $this->user_id = session('user_id') ? session('user_id') : 0;
        error_reporting(E_ERROR | E_WARNING | E_PARSE);//防止报未定义变量错误
    }

    //商品列表
    public function index()
    {
    	$api = "goods/query";
        $data = array();
        $data['user_id'] = $this->user_id;
        $data['cat_id'] = input('cat_id',0,'intval');
        $data['brand'] = input('brand',0,'intval');
        $data['size'] = 10;
        $data['page'] = input('page',1,'intval');
        $data['order'] = input('order','desc','trim');//按字段排序
        $data['sort'] = input('sort','sort_order','trim');//按字段排序
        $data['filter'] = input('filter','0','trim');//类型
        $data['keywords'] = input('keywords','','trim');//关键字搜索
        $data['is_real'] = input('is_real',3,'intval');//0虚拟商品 1真实商品
        if(!empty($data['keywords'])){
            cookie('keywords', cookie('keywords').','.$data['keywords'], 3600*24*7);
        }
        $result = $this->curlGet($api,$data);
        $result = json_decode($result,true);//print_r($result['data']);die;
        $this->assign('data',$result['data']);
        $this->assign('cat_id',$data['cat_id']);//分类
        $this->assign('brand',$data['brand']);//品牌
        $this->assign('filter',$data['filter']);
        $this->assign('keywords',$data['keywords']);//关键字
        $this->assign('order',$data['order']);
        $this->assign('order_opposite',$data['order'] == 'desc' ? 'asc' : 'desc');
        $this->assign('sort',$data['sort']);
        $this->assign('is_real',$data['is_real']);
        //异步加载分页数据
        $is_ajax = input('is_ajax','','intval') ? input('is_ajax','','intval') : 0;
        $this->assign('is_ajax',$is_ajax);
        if($is_ajax){
            echo $this->fetch('goods/goods_list_ajax');exit();
        }

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

	//搜索
    public function search(){
        $api = "goods/getHotSearch";
        $data = array();
        $result = $this->curlPost($api,$data);
        //print_r(json_decode($result,true));die;
        $result = json_decode($result,true);
        $this->assign('data',$result['data']);
        $keywords = cookie('keywords');
        $keywords_arr = '';
        if(!empty($keywords)){
            $keywords_arr = explode(',',$keywords);
            $keywords_arr = array_filter($keywords_arr);//排除数组空值
            $keywords_arr = array_unique($keywords_arr);//排除数组重复值
        }
        $this->assign('keywords_arr',$keywords_arr);//print_r($keywords_arr);die;
        return $this->fetch();
    }

    public function clear_keywords(){
        cookie('keywords',null);
        $data = array();
        $data['code'] = 200;
        $data['message'] = '清除成功！';
        echo json_encode($data);exit();
    }

    //联想搜索
    public function search_word(){
        $api = "goods/query";
        $data = array();
        $data['keywords'] = input('keywords','','trim');//关键字搜索
        $result = $this->curlPost($api,$data);
        $result = json_decode($result,true);
        if($result['code'] == 200){
            $row['code'] = 200;
            $row['msg']  = $result['message'];
            $row['data'] = $result['data']['list'];
        }else{
            $row['code'] = 500;
            $row['msg']  = $result['message'];
            $row['data'] = $result['data']['list'];
        }
        echo json_encode($row);exit();
    }

    //商品详情
    public function details()
    {
        $api = "goods/getGoodsInfo";
        $data = array();
        $data['user_id'] = $this->user_id;
        $data['goods_id'] = input('goods_id',0,'intval');//商品ID，必填
        $data['bargain_id'] = input('bargain_id',0,'intval');//砍价ID,非必填
        $data['group_id'] = input('group_id',0,'intval');//拼团ID,非必填
        $result = $this->curlGet($api,$data);
        $result = json_decode($result,true);
        if($result['code'] == 500){
            $this->error($result['message']);exit();
        }
        if(!$result['data']){
            $this->error('商品不存在！');exit();
        }
        //print_r($result['data']);die;
        $this->assign('data',$result['data']);
        $this->assign('comment_count',count($result['data']['comment']));//好评数量。做判断用
        $this->assign('goods_id',$data['goods_id']);


        $this->assign('user_id',$this->user_id);
        $this->assign('bargain_id',$data['bargain_id']);//砍价
        $this->assign('group_id',$data['group_id']);//拼团

        //猜你喜欢
        $api = "goods/query";
        $data = array();
        $data['cat_id'] = $result['data']['cat_id'];
        $data['supplier_id'] = '-1';
        $data['size'] = 4;//显示商品数量
        $result = $this->curlGet($api,$data);
        $result = json_decode($result,true);
        //print_r($result['data']);die;
        $this->assign('data2',$result['data']);

        //评论
        $api = "goods/getGoodsComment";
        $data = array();
        $data['goods_id'] = input('goods_id','','intval') ? input('goods_id','','intval') : 0;
        $data['page_size'] = 20;
        $data['page'] = input('page',1,'intval');
        $data['type'] = input('type',0,'intval');//0所有评价 1好评 2中评 3差评 4晒单
        $result = $this->curlGet($api,$data);
        $result = json_decode($result,true);
        $this->assign('comment_data',$result['data']);
        $this->assign('is_ajax',0);
        $this->assign('page',2);
        $this->assign('type',0);//0所有评价 1好评 2中评 3差评 4晒单

        $this->assign('share_url', $_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);//分享链接
        return $this->fetch();
    }

	//评论
    public function comment(){
        $url = "goods/getGoodsComment";
        $data = array();
        $data['goods_id'] = input('goods_id',0,'intval');
        $data['page_size'] = 20;
        $data['page'] = input('page',1,'intval');
        $data['type'] = input('type',0,'intval');//0所有评价 1好评 2中评 3差评 4晒单
        $result = $this->curlGet($url,$data);
        $result = json_decode($result,true);//json转数组
        //print_r($result);die;
        $this->assign('comment_data',$result['data']);
        $this->assign('type',$data['type']);
        $this->assign('page',$data['page']);

        //异步加载分页数据
        $is_ajax = input('is_ajax',0,'intval');
        $this->assign('is_ajax',$is_ajax);
        if($is_ajax){
            echo $this->fetch('goods/comment_list_ajax');exit();
        }
        return $this->fetch();
    }

    //异步获取属性变动的价格
    public function changePrice(){
        $api = "goods/getGoodsPrice";
        $data = array();
        $data['user_id'] = $this->user_id;
        $data['goods_id'] = input('goods_id',0,'intval');
        $data['number'] = input('number',1,'intval');
        $data['flow_type'] = input('flow_type',0,'intval');
        $arr = input();
        $data['attr_id'] = isset($arr['attr_id']) ? implode(',',$arr['attr_id']) : '';//print_r($data);die;
        $result = $this->curlGet($api,$data);
        //print_r(json_decode($result,true));die;
        $result = json_decode($result);
        return $result;
    }

    //收藏商品
    public function collectGoods(){
        if(!$this->user_id){
            $data['status'] = 500;
            $data['message'] = '请先登录！再收藏。';
            echo json_encode($data);exit();
        }
        $url = "user/addCollect";
        $data = array();
        $data['user_id'] = $this->user_id;
        $data['goods_id'] = input('goods_id','','intval');
        $result = $this->curlGet($url,$data);
        $result = json_decode($result,true);
        if($result['code'] == 200){
            $rows['status'] = 200;
            $rows['message'] = $result['data']['message'];
        }else{
            $rows['status'] = 500;
            $rows['message'] = '操作失败，链接服务器错误！';
        }
        echo json_encode($rows);exit();
    }

    //删除收藏商品
    public function delCollectGoods(){
        if(!$this->user_id){
            $data['status'] = 500;
            $data['message'] = '登录超时，请重新登录再操作！';
            echo json_encode($data);exit();
        }
        $url = "user/dropUserCollect";
        $data = array();
        $data['user_id'] = $this->user_id;
        $data['collection_id'] = input('collection_id','','trim');
        $result = $this->curlGet($url,$data);
        $result = json_decode($result);
        return $result;
    }

    //结算页面
    public function checkout(){
        if(!$this->user_id){
            $this->error('登录超时，请重新登录再操作！',url('User/login'));
            exit();
        }
        $api = "checkout/showProfile";
        $data = array();
        $data['user_id'] = $this->user_id;
        $sel_goods = input('sel_goods') ? input('sel_goods') : session('sel_goods');
        if(!empty($sel_goods)){
            $data['sel_goods'] = $sel_goods;
        }else{
            $this->error('请选择商品！','cart/index');exit();
        }
        session::set('sel_goods',$sel_goods);

        $flow_type = input('flow_type');
        $flow_type = isset($flow_type) ? $flow_type : session('flow_type');
        if(!empty($flow_type)){
            $data['flow_type'] = $flow_type;
        }else{
            $data['flow_type'] = 0;
        }
        session::set('flow_type',$data['flow_type']);

        $result = $this->curlPost($api,$data);
        $result = json_decode($result,true);
        //print_r($result);die;//输出测试
        if($result['code'] == 40001){//收货地址信息不完整或者不支持本地区配送
            $this->error($result['message'],url('User/address_add'));exit();
        }
        if($result['code'] == 500){
            $this->error($result['message']);exit();
        }
        if(empty($result['data'])){
            $this->redirect('cart/index');exit();
        }
        /**获取已选择数据缓存处理**/
        //获取已选择的地址数据记录
        $address_id_key =  cookie('address_id_key');
        if(isset($address_id_key)){
            $result['data']['def_addr'] = $result['data']['address_list'][$address_id_key];
        }

        if($this->return == 1){
            return $result['data'];
        }

        $shipping_id = array();
        $bonus = array();
        $message = array();
        foreach ($result['data']['supplier_list'] as $k => $v) {
            //获取已选快递方式缓存记录
            $key = cookie("shipping_id$v[supplier_id]") ? cookie("shipping_id$v[supplier_id]") : 0;//print_r($key);
            $shipping_id[$v['supplier_id']] = $key;
            //获取红包优惠券缓存记录
            $key = cookie("bonus$v[supplier_id]") ? cookie("bonus$v[supplier_id]") : 0;//print_r($key);
            $bonus[$v['supplier_id']] = $key;
            //获取留言缓存记录
            $key = cookie("message$v[supplier_id]") ? cookie("message$v[supplier_id]") : '';//print_r($key);
            $message[$v['supplier_id']] = $key;

            //发票
            $invoice = cookie("inv_payee_type$v[supplier_id]") ? cookie("inv_payee_type$v[supplier_id]") : '';
            if($invoice == 'individual'){
                $invoice_name[$v['supplier_id']] = '纸质发票(个人)';
            }
            if($invoice == 'unit'){
                $invoice_name[$v['supplier_id']] = '纸质发票(公司)';
            }
            if($invoice == ''){
                $invoice_name[$v['supplier_id']] = '未填写';
            }
        }
        $this->assign('shipping_id',$shipping_id);//快递方式
        $this->assign('bonus',$bonus);//优惠券
        $this->assign('message',$message);//留言
        $this->assign('invoice_name',$invoice_name);//发票选择
        //print_r($bonus);
        $payment = current($result['data']['payment_list']);
        if(cookie('payment') || cookie('payment')==0){
            foreach($result['data']['payment_list'] as $v){
                if($v['pay_id'] == cookie('payment')){
                    $payment = $v;
                }
            }
        }
        //print_r($payment);
        $this->assign('payment',$payment);
        //print_r($result['data']);die;
        $this->assign('data',$result['data']);
        $this->assign('flow_type',$data['flow_type']);

        return $this->fetch();
    }

    //选择收货地址
    public function consignee(){
        $url = "user/getUserAddress";
        $data = array();
        $data['user_id'] = $this->user_id;
        $result = $this->curlGet($url,$data);
        $result = json_decode($result,true);//json转数组
        //print_r($result);die;
        $this->assign('data',$result['data']);
        return $this->fetch();
    }

    //选择优惠卷
    public function bonus(){
        $supplier_id = input('supplier_id','','intval');

        $api = "checkout/showProfile";
        $data = array();
        $data['user_id'] = $this->user_id;
        $data['sel_goods'] = session('sel_goods');
        $data['flow_type'] = session('flow_type');
        $result = $this->curlPost($api,$data);
        $result = json_decode($result,true);
        if($result['code'] == 500){
            $this->error($result['message']);exit();
        }
        $used = array();
        foreach ($result['data']['supplier_list'] as $k => $v) {
            if($supplier_id == $v['supplier_id']){
                foreach ($v['bonus_list'] as $key => $value) {
                    $used[$value['can_use']][$key] = $value;
                }
            }
        }
        //print_r($used);die;
        $this->assign('data1',isset($used[1]) ? $used[1] : array());//可使用
        $this->assign('data2',isset($used[0]) ? $used[0] : array());//不可用
        $this->assign('supplier_id',$supplier_id);
        $this->assign('bonus_id',cookie("bonus$supplier_id") ? cookie("bonus$supplier_id") : 0);
        return $this->fetch();
    }

	//选择支付方式
	public function payment(){
        $api = "payment/pay_list";
        $result = $this->curlGet($api);
        $result = json_decode($result,true);//json转数组
        //print_r($result);
        $default = current($result['data']);
        $default = $default['pay_id'];
        if(cookie('payment') || cookie('payment')==0){
            $default = cookie('payment');
        }
        $this->assign('is_default',$default);

        //判断是否微信端访问
        $user_agent = $_SERVER['HTTP_USER_AGENT'];
        if (strpos($user_agent, 'MicroMessenger') === false) {
            //不是微信端访问
            $weixin = 0;
        }else{
            $weixin = 1;
        }

        $url = "payment/pay_list";
        $result = $this->curlGet($url);
        $result = json_decode($result,true);//json转数组
        //print_r($result);die;
        if($result['data']){
            $pay_list = array();
            foreach ($result['data'] as $k => $v) {
                if($weixin == 1 && $v['pay_code'] == 'MWEB'){//微信端过滤H5支付
                    continue;
                }
                if($weixin == 0 && $v['pay_name'] == 'weixin'){//不是微信端过滤微信支付
                    continue;
                }
                $pay_list[] = $v;
            }
        }
        //print_r($pay_list);die;
        //$this->assign('pay_list',$pay_list);

        $this->assign('payment',$pay_list);
        return $this->fetch();
    }

    //发票
    public function invoice(){
        $supplier_id = input('supplier_id',0,'intval');
        $this->assign('supplier_id',$supplier_id);

        //发票
        $inv_type = cookie("inv_type$supplier_id") ? cookie("inv_type$supplier_id") : 'normal_invoice';//发票类型，默认纸质发票
        $inv_payee_type = cookie("inv_payee_type$supplier_id") ? cookie("inv_payee_type$supplier_id") : 'individual';//发票抬头类型，默认个人
        $inv_payee = cookie("inv_payee$supplier_id") ? cookie("inv_payee$supplier_id") : '';//发票抬头内容
        $vat_inv_company_name = cookie("vat_inv_company_name$supplier_id") ? cookie("vat_inv_company_name$supplier_id") : '';//公司名称
        $vat_inv_taxpayer_id = cookie("vat_inv_taxpayer_id$supplier_id") ? cookie("vat_inv_taxpayer_id$supplier_id") : '';//纳税人识别码
        $vat_inv_registration_address = cookie("vat_inv_registration_address$supplier_id") ? cookie("vat_inv_registration_address$supplier_id") : '';
        $vat_inv_registration_phone = cookie("vat_inv_registration_phone$supplier_id") ? cookie("vat_inv_registration_phone$supplier_id") : '';
        $vat_inv_deposit_bank = cookie("vat_inv_deposit_bank$supplier_id") ? cookie("vat_inv_deposit_bank$supplier_id") : '';
        $vat_inv_bank_account = cookie("vat_inv_bank_account$supplier_id") ? cookie("vat_inv_bank_account$supplier_id") : '';
        $inv_content = cookie("inv_content$supplier_id") ? cookie("inv_content$supplier_id") : '商品明细';

        $this->assign('inv_type',$inv_type);
        $this->assign('inv_payee_type',$inv_payee_type);
        $this->assign('inv_payee',$inv_payee);
        $this->assign('vat_inv_company_name',$vat_inv_company_name);
        $this->assign('vat_inv_taxpayer_id',$vat_inv_taxpayer_id);
        $this->assign('vat_inv_registration_address',$vat_inv_registration_address);
        $this->assign('vat_inv_registration_phone',$vat_inv_registration_phone);
        $this->assign('vat_inv_deposit_bank',$vat_inv_deposit_bank);
        $this->assign('vat_inv_bank_account',$vat_inv_bank_account);
        $this->assign('inv_content',$inv_content);

        return $this->fetch();
    }

    //发票
    public function save_invoice(){
        $supplier_id = input('supplier_id',0,'intval');
        $data = input();
        foreach ($data as $k => $v) {
            if($k == 'supplier_id'){ continue;}
            cookie($k.$supplier_id, $v, 3600);
        }
        header("Location:".url('Goods/checkout'));exit();
    }

	//提交订单
	public function done(){
        $api = "checkout/addOrder";
        $data = array();
        $data['user_id'] = $this->user_id;
        $data['sel_goods'] = session('sel_goods');
        $data['flow_type'] = session('flow_type');
        $this->return = 1;
        $supplier_list = $this->checkout();
        $supplier_list = $supplier_list['supplier_list'];
        //print_r($supplier_list);die();
        $arr = array();
        foreach($supplier_list as $k=>$v){
            $supplier_id = $v['supplier_id'];
            $arr[] = array(
                'supplier_id'=>$v['supplier_id'],
                'shipping_id'=>input('shipping_id'.$supplier_id,0,'intval'),
                'pickup_point'=>input('pickup_point'.$supplier_id,0,'intval'),
                'bonus_id'=>input('bonus'.$supplier_id,0,'intval'),
                'bonus_sn'=>'',
                'message'=>input('message'.$supplier_id,'','trim'),
                'inv_type'=>cookie("inv_type$supplier_id") ? cookie("inv_type$supplier_id") : '',
                'inv_payee_type'=>cookie("inv_payee_type$supplier_id") ? cookie("inv_payee_type$supplier_id") : '',
                'inv_payee'=>cookie("inv_payee$supplier_id") ? cookie("inv_payee$supplier_id") : '',
                'vat_inv_company_name'=>cookie("vat_inv_company_name$supplier_id") ? cookie("vat_inv_company_name$supplier_id") : '',
                'vat_inv_taxpayer_id'=>cookie("vat_inv_taxpayer_id$supplier_id") ? cookie("vat_inv_taxpayer_id$supplier_id") : '',
                'vat_inv_registration_address'=>cookie("vat_inv_registration_address$supplier_id") ? cookie("vat_inv_registration_address$supplier_id") : '',
                'vat_inv_registration_phone'=>cookie("vat_inv_registration_phone$supplier_id") ? cookie("vat_inv_registration_phone$supplier_id") : '',
                'vat_inv_deposit_bank'=>cookie("vat_inv_deposit_bank$supplier_id") ? cookie("vat_inv_deposit_bank$supplier_id") : '',
                'vat_inv_bank_account'=>cookie("vat_inv_bank_account$supplier_id") ? cookie("vat_inv_bank_account$supplier_id") : '',
                'inv_content'=>cookie("inv_content$supplier_id") ? cookie("inv_content$supplier_id") : '');

            //清除之前记录的cookies值
            cookie('shipping_id'.$supplier_id,null);
            cookie('bonus'.$supplier_id,null);
            cookie('message'.$supplier_id,null);
            //发票
            cookie('inv_type'.$supplier_id,null);
            cookie('inv_payee_type'.$supplier_id,null);
            cookie('inv_payee'.$supplier_id,null);
            cookie('vat_inv_company_name'.$supplier_id,null);
            cookie('vat_inv_taxpayer_id'.$supplier_id,null);
            cookie('vat_inv_registration_address'.$supplier_id,null);
            cookie('vat_inv_registration_phone'.$supplier_id,null);
            cookie('vat_inv_deposit_bank'.$supplier_id,null);
            cookie('vat_inv_bank_account'.$supplier_id,null);
            cookie('inv_content'.$supplier_id,null);
        }
        $supplier = json_encode(array('supplier'=>$arr),JSON_UNESCAPED_UNICODE);

        $data['supplier'] = $supplier;
        $data['address_id'] = input('address_id',0,'intval');
        $data['pay_id'] = input('pay_id',0,'intval');
        $data['integral'] = input('integral',0,'intval');
        $data['surplus'] = '';

        $result = $this->curlPost($api,$data);
        $result = json_decode($result,true);
        //print_r($data);
        //print_r($result);die;//输出测试
        if(empty($result)){
            /*if($data['flow_type'] == 7){
                $url = url('User/service_order_list');//服务订单列表
            }else{
                $url = url('User/order_list');//订单列表
            }*/
            $url = url('User/order_list');//订单列表
            $this->success('支付异常，请稍后再试！',$url);
        }
        if($result['code'] == 500){
            $this->error($result['message']);exit();
        }
        if($data['pay_id']){
            $payment = new payment();
            $payment->type = 'h5';
            switch($result['data']['payment']['payment_name']){
                case 'weixin':
                    $payment->weixin($result['data']['payment']['prepay_id']);
                    break;
                case 'alipay':
                    echo $payment->alipay($result['data']['payment']['prepay_id']);
                    break;
            }
        }

        $this->assign('order_id',$result['data']['order_id']);

        return $this->fetch();

    }

    //保存选择的数据Cookie
    public function setCookie(){
        $is_ajax = input('is_ajax',0,'intval');
        if($is_ajax){
            $k = input('k');
            $v = input('v');//print_r($k);print_r($v);die;
            cookie($k, $v, 3600);
            $result = array();
            $result['code'] = 200;
            echo json_encode($result);
            exit();
        }

        $data = input();
        //print_r($data);die;
        foreach ($data as $k => $v) {
            cookie($k, $v, 3600);
        }
        header("Location:".url('Goods/checkout'));exit();
    }


}
