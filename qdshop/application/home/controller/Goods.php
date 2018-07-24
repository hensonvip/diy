<?php
namespace app\home\controller;
use think\Controller;
use think\Session;
use think\Cookie;
use think\Request;
use think\Loader;
use hunuo\shop\payment;

class Goods extends Common
{
    private $return; //内部返回

	public function __construct()
    {
        parent::__construct();
		$this->user_id = session('user_id') ? session('user_id') : 0;

    }

    public function index()
    {
		//允许列表筛选的的字段过滤

		$param = input('');
		$p = input('p',1);


		//热销
		$api = "goods/query";
		$data = array();
		$data['cat_id'] = 0;
		$data['supplier_id'] = '-1';
		$data['filter'] = 'is_hot';
		$data['size'] = 3	;
		$rexiao = $this->curlGet($api,$data);
		$rexiao = json_decode($rexiao,true);
		//print_r($result);die();
		$this->assign('rexiao',$rexiao['data']);

		//新品
		$api = "goods/query";
		$data = array();
		$data['cat_id'] = 0;
		$data['supplier_id'] = '-1';
		$data['filter'] = 'is_new';
		$data['size'] = 5	;
		$xinpin = $this->curlGet($api,$data);
		$xinpin = json_decode($xinpin,true);
		//print_r($result);die();
		$this->assign('xinpin',$xinpin['data']);

		//列表
		//接收参数
		$api = "goods/query";
		$data = array();


		$params = array('brand','min','max','size','page','order','sort','filter','keywords','is_real');
		foreach($param as $k=>$v){
			if(in_array($k,$params)){
				$data[$k] = $v;
			}
		}

		$data['cat_id'] =  input('cat_id',0);
		$data['user_id'] =  $this->user_id;
		$data['supplier_id'] = input('supplier_id','-1');
		$data['size'] = 16	;
		$data['page'] = $p;
		$list = $this->curlGet($api,$data);
		$list = json_decode($list,true);
		//print_r($list['data']);die();
		$this->assign('goods_list',$list['data']);

		if(empty($data['keywords'])){
			//顶部面包屑
			$api = "Category/getCatIds";
			$data = array();
			$data['cat_id'] = input('cat_id',0);
			$data['supplier_id'] = input('supplier_id',0);
			$result = $this->curlGet($api,$data);
			$result = json_decode($result,true);
			$this->assign('crumbs',$result['data']);

			//筛选项
			$api = "index/getFilter";
			$data = array('cat_id'=>$data['cat_id']);
			$resp = $this->curlGet($api,$data);
			$resp = json_decode($resp,true);
			$this->assign('filter',$resp['data']);
		}
		$this->assign('keywords',isset($data['keywords'])?$data['keywords']:'');

		//分页
		$pageHtml = '';
		if($list['data']['pager']){
			//$this->assign('toPage',$this->toPage($list['data']['pager']['page'],$list['data']['pager']['page_count']));
			//组装分页
			$prePage = $this->getPage($list['data']['pager']['page'],$list['data']['pager']['page_count']);
			$pageHtml = '  <div class="h-page"><a class="pn-first" href="javascript:js_aup6(\'p\',\'1\',\'param\');">首页</a>';
			if($prePage['page']>1){
			   $pageHtml .= '<a class="pn-prev" title="上一页" href="javascript:js_aup6(\'p\',\''.($prePage['page']-1).'\',\'param\');">上一页</a>';
			 }
			for ($i = $prePage['start']; $i <= $prePage['end']; $i++) {
				if($i == $prePage['page']){
				$pageHtml .= '<a class="pn-num selected">'.$i.'</a>';
				}else{
					$pageHtml .= '<a class="pn-num" href="javascript:js_aup6(\'p\',\''.($i).'\',\'param\');">'.$i.'</a>'; //javascript:js_aup6('time','1','param');
				}
			}

			if($prePage['page']<$prePage['end']){
			   $pageHtml .= '<a class="pn-next"  title="下一页" href="javascript:js_aup6(\'p\',\''.($prePage['page']+1).'\',\'param\');">下一页</a>';
			 }
			$pageHtml .= '<a class="pn-last" href="javascript:js_aup6(\'p\',\''.($prePage['page']).'\',\'param\');">尾页</a><span class="page-num">共'.$prePage['pages'].'页</span></div>';
		}
		$this->assign('toPage',$pageHtml);



		//浏览历史
		//猜你喜欢(获取关联商品)
		$records = $this->getHistory();
		$records_list = array();
		$user_like = array();
		if($records){
			$goods = implode(",",$records);
			$api = "goods/queryMore";
			$data = array();
			$data['goods'] = $goods;
			$data['num'] = 5;
			$records_list = $this->curlGet($api,$data);
			$records_list = json_decode($records_list,true);
			$records_list = $records_list['data']['list'];

			$api = "goods/user_like";
			$data = array();
			$data['goods'] = $goods;
			$user_like = $this->curlGet($api,$data);
			$user_like = json_decode($user_like,true);
			$user_like = $user_like['data'];
		}
		$this->assign('records_list',$records_list);
		$this->assign('user_like',$user_like);

		$this->assign('class','skin');
		//$this->render();

		return $this->fetch();
    }

    public function details(){
        $goods_id =  input('goods_id','','intval') ? input('goods_id','','intval') : 0;
        if(!$goods_id){
            $this->error('该商品不存在！');
        }

        $api = "goods/getGoodsInfo";
        $data = array();
        $data['user_id'] = $this->user_id;
        $data['goods_id'] = $goods_id;
        $result = $this->curlGet($api,$data);
        $result = json_decode($result,true);
        // print_r($result['data']);die;
        if(!$result['data']){
            $this->error($result['message']);
        }
        if ($result['data']['goods_status'] == 1 || $result['data']['goods_status'] == 2 || $result['data']['goods_status'] == 3) {
            $this->error('商品未出售');
        }
        $cat_id = $result['data']['cat_id'];
        $this->assign('data',$result['data']);
        $this->assign('comment_count',count($result['data']['comment']));//好评数量。做判断用
        $this->assign('goods_id',$data['goods_id']);


        // 增加查看次数
        $api = "goods/addClickCount";
        $data = array();
        $data['goods_id'] = $goods_id;
        $this->curlGet($api,$data);

        //评论
        $api = "goods/getGoodsComment";
        $data = array();
        $data['goods_id'] = $goods_id;
        $data['user_id'] = $this->user_id;
        $data['page_size'] = 10;
        $data['page'] = input('page','','intval') ? input('page','','intval') : 1;
        $data['type'] = input('type','','intval') ? input('type','','intval') : 0;//0所有评价 1好评 2中评 3差评 4晒单
        $result = $this->curlGet($api,$data);
        $result = json_decode($result,true);
        // print_r($result);die;
        $this->assign('comment_data',$result['data']);
        //异步加载分页数据
        $is_ajax = input('is_ajax','','intval') ? input('is_ajax','','intval') : 0;
        $this->assign('is_ajax',$is_ajax);
        if($is_ajax){
            echo $this->fetch('goods/comment_list_ajax');exit();
        }

        //获取推荐商品
        $api = "goods/query";
        $data = array();
        $data['cat_id'] = 0;
        $data['supplier_id'] = '-1';
        $data['filter'] = 'is_hot';
        $data['size'] = 3;
        $hot = $this->curlGet($api,$data);
        $hot = json_decode($hot,true);
        if ($hot['data']['list']) {
             $this->assign('hot',$hot['data']);
        } else {
            $api = "goods/query";
            $data = array();
            $data['cat_id'] = $cat_id;
            $data['supplier_id'] = '-1';
            $data['size'] = 3;
            $hot = $this->curlGet($api,$data);
            $hot = json_decode($hot,true);
            $this->assign('hot',$hot['data']);
        }
        // print_r($hot['data']);die();
         
        // 私信举报原因
        $url = "user/getReportReason";
        $data = array();
        $result = $this->curlGet($url);
        $result = json_decode($result,true);//json转数组
        $this->assign('reason_list', $result['data']);

        return $this->fetch();
    }

	public function details_ajax(){
		$goods_id =  input('goods_id','','intval') ? input('goods_id','','intval') : 0;
		if(!$goods_id){
			$this->error('该商品不存在！');
		}

		$api = "goods/getGoodsInfo";
        $data = array();
        $data['user_id'] = $this->user_id;
        $data['goods_id'] = $goods_id;
        $result = $this->curlGet($api,$data);
        $result = json_decode($result,true);
        if(!$result['data']){
            $this->error($result['message']);
        }
        if ($result['data']['goods_status'] == 1 || $result['data']['goods_status'] == 2 || $result['data']['goods_status'] == 3) {
            $this->error('商品未出售');
        }
        $cat_id = $result['data']['cat_id'];
        // print_r($result['data']);die;
        $this->assign('data',$result['data']);
        $this->assign('comment_count',count($result['data']['comment']));//好评数量。做判断用
        $this->assign('goods_id', $goods_id);

        // 增加查看次数
        $api = "goods/addClickCount";
        $data = array();
        $data['goods_id'] = $goods_id;
        $this->curlGet($api,$data);

		//评论
        $api = "goods/getGoodsComment";
        $data = array();
        $data['goods_id'] = $goods_id;
        $data['user_id'] = $this->user_id;
        $data['page_size'] = 10;
        $data['page'] = input('page','','intval') ? input('page','','intval') : 1;
        $data['type'] = input('type','','intval') ? input('type','','intval') : 0;//0所有评价 1好评 2中评 3差评 4晒单
        $result = $this->curlGet($api,$data);
        $result = json_decode($result,true);
        // print_r($result);die;
        $this->assign('comment_data',$result['data']);
        //异步加载分页数据
        $is_ajax = input('is_ajax','','intval') ? input('is_ajax','','intval') : 0;
        $this->assign('is_ajax',$is_ajax);
        if($is_ajax){
            echo $this->fetch('goods/comment_list_ajax');exit();
        }

		//print_r($result['data']);die;

		//获取推荐商品
		$api = "goods/query";
		$data = array();
		$data['cat_id'] = 0;
		$data['supplier_id'] = '-1';
		$data['filter'] = 'is_hot';
		$data['size'] = 3;
		$hot = $this->curlGet($api,$data);
		$hot = json_decode($hot,true);
        if ($hot['data']['list']) {
            $this->assign('hot',$hot['data']);
        } else {
            $api = "goods/query";
            $data = array();
            $data['cat_id'] = $cat_id;
            $data['supplier_id'] = '-1';
            $data['size'] = 3;
            $hot = $this->curlGet($api,$data);
            $hot = json_decode($hot,true);
            $this->assign('hot',$hot['data']);
        }
        // print_r($hot['data']);die();
         
        // 私信举报原因
        $url = "user/getReportReason";
        $data = array();
        $result = $this->curlGet($url);
        $result = json_decode($result,true);//json转数组
        $this->assign('reason_list', $result['data']);

        $html = $this->fetch();
        return ['html' => $html, 'goods_id' => $goods_id];
	}

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
        // var_dump($result);die;
        // if($result['code'] == 40001){//收货地址信息不完整或者不支持本地区配送
        //     $this->error($result['message'],url('User/address_add'));exit();
        // }
        if($result['code'] == 500){
            $this->error($result['message']);exit();
        }
        if(empty($result['data'])){
            $this->redirect('cart/index');exit();
        }

        $supplier_id = input('supplier_id',0,'intval');
        $used = array();
        foreach ($result['data']['supplier_list'] as $k => $v) {
            if($supplier_id == $v['supplier_id']){
                foreach ($v['bonus_list'] as $key => $value) {
                    $used[$value['can_use']][$key] = $value;
                }
            }
        }
        $this->assign('bonus_list',isset($used[1]) ? $used[1] : array());//可使用

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
            $key = cookie("bonus$v[supplier_id]") ? cookie("bonus$v[supplier_id]") : 0;//print_r($key);die;
            $bonus[$v['supplier_id']] = $key;
            //获取留言缓存记录
            $key = cookie("message$v[supplier_id]") ? cookie("message$v[supplier_id]") : '';//print_r($key);
            $message[$v['supplier_id']] = $key;
            //发票
            $inv_type = cookie("inv_type$v[supplier_id]") ? cookie("inv_type$v[supplier_id]") : '';
            $inv_payee_type = cookie("inv_payee_type$v[supplier_id]") ? cookie("inv_payee_type$v[supplier_id]") : '';
            $inv_payee = cookie("inv_payee$v[supplier_id]") ? cookie("inv_payee$v[supplier_id]") : '';
            $vat_inv_company_name = cookie("vat_inv_company_name$v[supplier_id]") ? cookie("vat_inv_company_name$v[supplier_id]") : '';
            $vat_inv_taxpayer_id = cookie("vat_inv_taxpayer_id$v[supplier_id]") ? cookie("vat_inv_taxpayer_id$v[supplier_id]") : '';
            $vat_inv_registration_address = cookie("vat_inv_registration_address$v[supplier_id]") ? cookie("vat_inv_registration_address$v[supplier_id]") : '';
            $vat_inv_registration_phone = cookie("vat_inv_registration_phone$v[supplier_id]") ? cookie("vat_inv_registration_phone$v[supplier_id]") : '';
            $vat_inv_deposit_bank = cookie("vat_inv_deposit_bank$v[supplier_id]") ? cookie("vat_inv_deposit_bank$v[supplier_id]") : '';
            $vat_inv_bank_account = cookie("vat_inv_bank_account$v[supplier_id]") ? cookie("vat_inv_bank_account$v[supplier_id]") : '';
            $inv_content = cookie("inv_content$v[supplier_id]") ? cookie("inv_content$v[supplier_id]") : '';
            $inv_consignee_phone = cookie("inv_consignee_phone$v[supplier_id]") ? cookie("inv_consignee_phone$v[supplier_id]") : '';
            $inv_consignee_email = cookie("inv_consignee_email$v[supplier_id]") ? cookie("inv_consignee_email$v[supplier_id]") : '';
            $open_inv_type = cookie("open_inv_type$v[supplier_id]") ? cookie("open_inv_type$v[supplier_id]") : '';
            $inv_consignee_name = cookie("inv_consignee_name$v[supplier_id]") ? cookie("inv_consignee_name$v[supplier_id]") : '';
            $inv_consignee_province = cookie("inv_consignee_province$v[supplier_id]") ? cookie("inv_consignee_province$v[supplier_id]") : '';
            $inv_consignee_city = cookie("inv_consignee_city$v[supplier_id]") ? cookie("inv_consignee_city$v[supplier_id]") : '';
            $inv_consignee_district = cookie("inv_consignee_district$v[supplier_id]") ? cookie("inv_consignee_district$v[supplier_id]") : '';
            $inv_consignee_address = cookie("inv_consignee_address$v[supplier_id]") ? cookie("inv_consignee_address$v[supplier_id]") : '';

            /*if($inv_payee_type == 'individual'){
                $invoice_name[$v['supplier_id']] = '纸质发票(个人)';
            }
            if($inv_payee_type == 'unit'){
                $invoice_name[$v['supplier_id']] = '纸质发票(公司)';
            }
            if($inv_payee_type == ''){
                $invoice_name[$v['supplier_id']] = '未填写';
            }*/
        }
        $this->assign('shipping_id',$shipping_id);//快递方式
        $this->assign('bonus',$bonus);//优惠券
        $this->assign('message',$message);//留言
        // 发票
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
        $this->assign('inv_consignee_phone',$inv_consignee_phone);
        $this->assign('inv_consignee_email',$inv_consignee_email);
        $this->assign('open_inv_type',$open_inv_type);
        $this->assign('inv_consignee_name',$inv_consignee_name);
        $this->assign('inv_consignee_province',$inv_consignee_province);
        $this->assign('inv_consignee_city',$inv_consignee_city);
        $this->assign('inv_consignee_district',$inv_consignee_district);
        $this->assign('inv_consignee_address',$inv_consignee_address);
        //print_r($bonus);
        $payment = current($result['data']['payment_list']);
        if(cookie('payment') || cookie('payment')==0){
            foreach($result['data']['payment_list'] as $v){
                if($v['pay_id'] == cookie('payment')){
                    $payment = $v;
                }
            }
        }
        // print_r($result['data']);die;
        $this->assign('payment',$payment);
        $this->assign('data',$result['data']);
        $this->assign('flow_type',$data['flow_type']);

        // 是否设计库商品
        $is_design = 0;
        foreach ($result['data']['supplier_list'] as $key => $value) {
            foreach ($value['goods_list'] as $key2 => $value2) {
                if ($value2['is_design'] == 1) {
                    $is_design = 1;
                    break;
                }
            }
        }
        $this->assign('is_design',$is_design);

        // 收货地址
        $url = "user/getUserAddress";
        $data = array();
        $data['user_id'] = $this->user_id;
        $result = $this->curlGet($url,$data);
        $result = json_decode($result,true);//json转数组
        $this->assign('address',$result['data']);

        // 用户发票公司抬头
        $url = "user/getInvTitle";
        $data = array();
        $data['user_id'] = $this->user_id;
        $result = $this->curlGet($url,$data);
        $result = json_decode($result,true);//json转数组
        $this->assign('inv_title_list',$result['data']);

        $invoice_content= str_replace("\r\n", "\n", $this->sys_cfg['invoice_content']);
        $invoice_content = explode("\n", $invoice_content);
        $this->assign('invoice_content', $invoice_content);

        $regionP = $this->getRegionP();
        $this->assign('regionP',$regionP);//省份

        return $this->fetch();
	}

	//收藏商品
    public function collectGoods(){
		// ob_clean();
		// header("Content-Type:application/json;");
        if(!$this->user_id){
            $data['status'] = 500;
            $data['message'] = '请先登录！再收藏。';
            echo json_encode($data);exit();
        }
        $url = "user/addCollect";
        $data = array();
        $data['user_id'] = $this->user_id;
        $data['goods_id'] = input('goods_id','','intval');
        $data['str'] = input('str');
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
        $open_invoice = input('open_invoice', '');
        //print_r($supplier_list);die();
        $arr = array();
        foreach($supplier_list as $k=>$v){
            $supplier_id = $v['supplier_id'];
            if ($open_invoice == 'on') {//开具发票
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
                    'inv_content'=>cookie("inv_content$supplier_id") ? cookie("inv_content$supplier_id") : '',
                    'inv_consignee_phone'=>cookie("inv_consignee_phone$supplier_id") ? cookie("inv_consignee_phone$supplier_id") : '',
                    'inv_consignee_email'=>cookie("inv_consignee_email$supplier_id") ? cookie("inv_consignee_email$supplier_id") : '',
                    'open_inv_type'=>cookie("open_inv_type$supplier_id") ? cookie("open_inv_type$supplier_id") : '',
                    'inv_consignee_name'=>cookie("inv_consignee_name$supplier_id") ? cookie("inv_consignee_name$supplier_id") : '',
                    'inv_consignee_province'=>cookie("inv_consignee_province$supplier_id") ? cookie("inv_consignee_province$supplier_id") : '',
                    'inv_consignee_city'=>cookie("inv_consignee_city$supplier_id") ? cookie("inv_consignee_city$supplier_id") : '',
                    'inv_consignee_district'=>cookie("inv_consignee_district$supplier_id") ? cookie("inv_consignee_district$supplier_id") : '',
                    'inv_consignee_address'=>cookie("inv_consignee_address$supplier_id") ? cookie("inv_consignee_address$supplier_id") : ''
                );
            } else {
                $arr[] = array(
                    'supplier_id'=>$v['supplier_id'],
                    'shipping_id'=>input('shipping_id'.$supplier_id,0,'intval'),
                    'pickup_point'=>input('pickup_point'.$supplier_id,0,'intval'),
                    'bonus_id'=>input('bonus'.$supplier_id,0,'intval'),
                    'bonus_sn'=>'',
                    'message'=>input('message'.$supplier_id,'','trim'),
                    'inv_type'=>'',//发票类型
                    'inv_payee_type'=>'',//发票抬头类型
                    'inv_payee'=>'',//发票抬头
                    'vat_inv_company_name'=>'',//增值税发票单位名称
                    'vat_inv_taxpayer_id'=>'',//增值税发票纳税人识别号
                    'vat_inv_registration_address'=>'',//增值税发票注册地址
                    'vat_inv_registration_phone'=>'',//增值税发票注册电话
                    'vat_inv_deposit_bank'=>'',//增值税发票开户银行
                    'vat_inv_bank_account'=>'',//增值税发票银行账户
                    'inv_content'=>'',//发票内容
                    'inv_consignee_phone'=>'',//收票人手机
                    'inv_consignee_email'=>'',//收票人邮箱(new)
                    'open_inv_type'=>'',//开票方式(new)
                    'inv_consignee_name'=>'',//收票人姓名
                    'inv_consignee_province'=>'',//收票人省
                    'inv_consignee_city'=>'',//收票人市
                    'inv_consignee_district'=>'',//收票人县/区
                    'inv_consignee_address'=>''//收票人详细地址
                );
            }

            //清除之前记录的cookies值
            cookie('shipping_id'.$supplier_id,null);
            cookie('bonus'.$supplier_id,null);
            cookie('message'.$supplier_id,null);
            cookie('address_id_key',null);
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
            cookie('inv_consignee_phone'.$supplier_id,null);
            cookie('inv_consignee_email'.$supplier_id,null);
            cookie('open_inv_type'.$supplier_id,null);
            cookie('inv_consignee_name'.$supplier_id,null);
            cookie('inv_consignee_province'.$supplier_id,null);
            cookie('inv_consignee_city'.$supplier_id,null);
            cookie('inv_consignee_district'.$supplier_id,null);
            cookie('inv_consignee_address'.$supplier_id,null);
        }
        $supplier = json_encode(array('supplier'=>$arr),JSON_UNESCAPED_UNICODE);

        $data['supplier'] = $supplier;
        $data['address_id'] = input('address_id',0,'intval');
        $data['pay_id'] = input('pay_id',0,'intval');
        $data['integral'] = input('integral',0,'intval');
        $data['surplus'] = '';
        $data['is_design'] = input('is_design',0,'intval');

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
        /*if($data['pay_id']){
            $payment = new payment();
            // $payment->type = 'h5';
            $payment->type = 'pc';
            switch($result['data']['payment']['payment_name']){
                case 'weixin':
                    Loader::import('phpqrcode.phpqrcode', EXTEND_PATH);
                    ob_clean();
                    $code_url = $payment->weixin($result['data']['payment']);
                    \QRcode::png($code_url);die;
                    break;
                case 'alipay':
                    echo $payment->alipay($result['data']['payment']['prepay_id']);
                    break;
            }
        }*/
        $this->assign('order_id',$result['data']['order_id']);
        $this->assign('order_amount',$result['data']['order_amount']);

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

    //发票
    public function save_invoice(){
        $supplier_id = input('supplier_id',0,'intval');
        $data = input();
        // print_r($data);die;
        foreach ($data as $k => $v) {
            if($k == 'supplier_id'){ continue;}
            cookie($k.$supplier_id, $v, 3600);
        }
        header("Location:".url('Goods/checkout'));exit();
    }

    //保存发票抬头
    public function save_inv_title(){
        $api = "user/saveInvTitle";
        $data = array();
        $data['user_id'] = $this->user_id;
        $data['inv_title'] = input('inv_title', '');
        $result = $this->curlGet($api,$data);
        $result = json_decode($result);
        return $result;
    }

    //删除发票抬头
    public function del_inv_title(){
        $api = "user/delInvTitle";
        $data = array();
        $data['user_id'] = $this->user_id;
        $data['inv_title'] = input('inv_title', '');
        $result = $this->curlGet($api,$data);
        $result = json_decode($result);
        return $result;
    }

    //获取支付宝支付二维码
    public function alipay_qr(){
        //打开缓冲区
        $api = "order/alipayQr";
        $data = array();
        $data['user_id'] = $this->user_id;
        $data['order_id'] = input('order_id',0,'intval');
        $result = $this->curlGet($api,$data);
        $result = json_decode($result);
        return $result;
    }

    //获取微信支付二维码
    public function wxpay_qr(){
        //打开缓冲区
        $api = "order/wxpayQr";
        $data = array();
        $data['user_id'] = $this->user_id;
        $data['order_id'] = input('order_id',0,'intval');
        $result = $this->curlGet($api,$data);
        $result = json_decode($result, true);
        // print_r($result['data']);die;
        $payment = new payment();
        $payment->type = 'pc';
        ob_start();
        Loader::import('phpqrcode.phpqrcode', EXTEND_PATH);
        if (isset($result['data']['payment'])) {
            $code_url = $payment->weixin($result['data']['payment']);
            \QRcode::png($code_url);
            $imageString = base64_encode(ob_get_contents());
            //关闭缓冲区
            ob_end_clean();
            $result = array(
                'code' => 200,
                'message' => 'SUCCESS',
                'data' => $imageString,
            );
        } else {
            $result = array(
                'code' => 500,
                'message' => '获取支付二维码失败',
                'data' => '',
            );
        }
        echo json_encode($result);exit();
    }

    //ajax定时请求订单支付状态
    public function check_pay_status(){
        $api = "order/checkPayStatus";
        $data = array();
        $data['user_id'] = $this->user_id;
        $data['order_id'] = input('order_id',0,'intval');
        $result = $this->curlGet($api,$data);
        $result = json_decode($result);

        return $result;
    }

    //支付成功
    public function pay_success(){
        $api = "order/getOffsaleOrderGoods";
        $data = array();
        $data['order_id'] = input('order_id',0,'intval');
        $result = $this->curlGet($api,$data);
        $result = json_decode($result, true);
        $this->assign('goods_list',$result['data']);

        return $this->fetch();
    }

    //商品点赞
    public function like(){
        $api = "goods/like";
        $data = array();
        $data['user_id'] = $this->user_id;
        $data['goods_id'] = input('goods_id',0,'intval');
        $result = $this->curlGet($api,$data);
        $result = json_decode($result);
        return $result;
    }

    //商品取消点赞
    public function unlike(){
        $api = "goods/unlike";
        $data = array();
        $data['user_id'] = $this->user_id;
        $data['goods_id'] = input('goods_id',0,'intval');
        $result = $this->curlGet($api,$data);
        $result = json_decode($result);
        return $result;
    }

    //商品评论点赞
    public function c_like(){
        $api = "goods/c_like";
        $data = array();
        $data['user_id'] = $this->user_id;
        $data['comment_id'] = input('comment_id',0,'intval');
        $data['source'] = input('source',2,'intval');
        $result = $this->curlGet($api,$data);
        $result = json_decode($result);
        return $result;
    }

    //商品评论取消点赞
    public function c_unlike(){
        $api = "goods/c_unlike";
        $data = array();
        $data['user_id'] = $this->user_id;
        $data['comment_id'] = input('comment_id',0,'intval');
        $result = $this->curlGet($api,$data);
        $result = json_decode($result);
        return $result;
    }
    
	// 商品举报
    public function do_goods_report() {
        $url = "goods/doGoodsReport";
        $data = array();
        $data['user_id'] = $this->user_id;//举报人
        $data['goods_id'] = input('goods_id', 0, 'intval');
        $data['reason'] = input('reason','','trim,strip_tags,htmlspecialchars');
        $result = $this->curlPost($url,$data);
        $result = json_decode($result,true);
        return $result;
    }

    // 商品评论举报
    public function do_comment_report() {
        $url = "goods/doCommentReport";
        $data = array();
        $data['user_id'] = $this->user_id;//举报人
        $data['comment_id'] = input('comment_id', 0, 'intval');
        $data['type'] = input('type', 0, 'intval');//0为参赛作品评论 1为商品评论
        $data['reason'] = input('reason','','trim,strip_tags,htmlspecialchars');
        $result = $this->curlPost($url,$data);
        $result = json_decode($result,true);
        return $result;
    }

    /**********************/
    // 获取商品销售页面
    /**********************/
    public function sale_apply()
    {
        $api = "goods/getGoodsCoerceInfo";
        $data = [];
        $data['goods_id'] = input('goods_id', 0, 'intval');
        $result = $this->curlGet($api, $data);
        $result = json_decode($result, true);
        $this->assign('goods_info', $result['data']);

        $api = "category/getSubCat";
        $data = ['cat_id' => 85];
        $result = $this->curlGet($api, $data);
        $result = json_decode($result, true);
        $this->assign('category_list', $result['data']);

        $html = $this->fetch('pay_sale');
        return ['html' => $html];
    }

    /**********************/
    // 出售提交
    /**********************/
    public function sale_post()
    {
        $api = "goods/saleGoods";
        $data = input();
        $result = $this->curlPost($api, $data);
        $result = json_decode($result);
        return $result;
    }
}