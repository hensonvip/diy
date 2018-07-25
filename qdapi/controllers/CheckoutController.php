<?php

include_once(ROOT_PATH . 'includes/cls_goods.php');
include_once(ROOT_PATH . 'includes/cls_checkout.php');
include_once(ROOT_PATH . 'includes/cls_user.php');
include_once(ROOT_PATH . 'includes/cls_cart.php');
include_once(ROOT_PATH . 'includes/cls_shipping.php');
include_once(ROOT_PATH . 'includes/lib_goods.php');
include_once(ROOT_PATH . 'includes/lib_main.php');

require_once(ROOT_PATH . 'includes/cls_order.php');

/**
 * 购物车接口
 *
 * @version v1.0
 * @create 2016-11-02
 * @author cyq
 */
class CheckoutController extends ApiController
{


	public function __construct()
	{

		parent::__construct();
		$this->data  = $this->input();
		$this->goods = cls_goods::getInstance();
		$this->user  = cls_user::getInstance();
		$this->cart  = cls_cart::getInstance();
		$this->checkout  = cls_checkout::getInstance();
		$this->shipping  = cls_shipping::getInstance();


		$this->user_id = isset($this->data['user_id'])? intval($this->data['user_id']) : '';

		if(empty($this->user_id) || !isset($this->user_id)){
			$this->error("请先登录！");
		}
		$user_rank_info = $this->user->get_user_rank($this->user_id);
		if($user_rank_info){
			$this->user_rank_info = $user_rank_info;
		}else{
			$this->error("该会员数据不存在或者参数错误");
		}
	}

	/**
	 * @description 订单提交页面
	 * @return void
	 */
	public function showProfile()
	{
		define('SESS_ID',session_id());
		/* 载入语言文件 */
        require_once(ROOT_PATH . 'languages/' .$GLOBALS['_CFG']['lang']. '/user.php');
        require_once(ROOT_PATH . 'languages/' .$GLOBALS['_CFG']['lang']. '/shopping_flow.php');

		$sel_goods       = !empty($this->data['sel_goods']) ? compile_str($this->data['sel_goods']) : '';
		$flow_order['address_id']       = !empty($this->data['address_id']) ? intval($this->data['address_id']) : 0;

		// $flow_order['shipping_id']     = !empty($this->data['shipping_id']) ? intval($this->data['shipping_id']) : 0;
		// $flow_order['pay_id']          = !empty($this->data['pay_id']) ? intval($this->data['pay_id']) : 0;
		// $flow_order['shipping_pay']          = !empty($this->data['shipping_pay']) ? intval($this->data['shipping_pay']) : 0;
		$flow_type = isset($this->data['flow_type']) ? intval($this->data['flow_type']) : 0;//取得购物类型

		if(empty($sel_goods)){
			$this->error('参数错误');
		}

		$flow_order['extension_id'] = '';//为空为普通商品
		$flow_order['extension_code'] = '';//为空为普通商品


		//不是普通商品时执行
		if($flow_type){
			$cart_data = $GLOBALS['db']->getRow("SELECT extension_id,extension_code FROM ". $GLOBALS['ecs']->table("cart") . " WHERE rec_id in (".$sel_goods.") AND user_id='". $this->user_rank_info['user_id'] ."' AND  rec_type='$flow_type' ");
			$flow_order['extension_id'] = $cart_data['extension_id'];
			$flow_order['extension_code'] = $cart_data['extension_code'];
		}

		//$goods = explode(',',$sel_goods);

		$sql = "SELECT DISTINCT g.supplier_id FROM ".$GLOBALS['ecs']->table("goods")." AS g RIGHT JOIN ".$GLOBALS['ecs']->table("cart")." AS c ON c.goods_id = g.goods_id WHERE c.rec_id in (".$sel_goods.") AND c.user_id='". $this->user_rank_info['user_id'] ."' AND  c.rec_type='$flow_type'";
		$supplier_id_list = $GLOBALS['db']->getAll($sql);


		$sql = "SELECT c.rec_id,g.supplier_id FROM ".$GLOBALS['ecs']->table("goods")." AS g RIGHT JOIN ".$GLOBALS['ecs']->table("cart")." AS c ON c.goods_id = g.goods_id WHERE c.rec_id in (".$sel_goods.") AND c.user_id='". $this->user_rank_info['user_id'] ."' AND  c.rec_type='$flow_type' ";
		$s_g_list = $GLOBALS['db']->getAll($sql);
		$supplier_goods = array();
		foreach($s_g_list as $v){
			$supplier_goods[$v['supplier_id']][] = $v['rec_id'];
		}
		foreach($supplier_goods as $k=>$v){
			if(is_array($v)){
				$supplier_goods[$k] = implode(",",$v);
			}
		}

		if(empty($supplier_id_list)){
			$this->error('暂无商品');
		}

		// var_dump($supplier_goods);exit();
		$result = array();
		$return['code'] = 200;
		$return['error_code'] = 500;
		$return['data'] = array();
		foreach($supplier_id_list as $v){

			$result = $this->checkout->getCheckoutProfile($this->user_rank_info, $flow_type, $supplier_goods[$v['supplier_id']], $flow_order,$v['supplier_id'],$sel_goods,$this->input('device'));
			// Response::render($result, '500', '');
			if($result['code']!=200){
				$return['code'] = 500;
				$return['error_code'] = $result['error_code'];
				$return['message'] = $result['message'];
				break ;
			}
			$return['data']['def_addr'] = $result['supplier']['def_addr'];
			$return['data']['address_list'] = $result['supplier']['address_list'];
			$return['data']['payment_list'] = $result['supplier']['payment_list'];
			$return['data']['order_info'] = $result['supplier']['order_info'];
			$return['data']['order_total'] = $result['supplier']['order_total'];


			unset($result['supplier']['address_list']);
			unset($result['error_code']);
			unset($result['supplier']['payment_list']);
			unset($result['supplier']['order_info']);
			unset($result['supplier']['order_total']);

			unset($result['message']);
			unset($result['code']);

			$sql_supplier = "SELECT s.supplier_id,s.supplier_name,s.add_time,sr.rank_name FROM ". $GLOBALS['ecs']->table("supplier") . " as s left join ". $GLOBALS['ecs']->table("supplier_rank") ." as sr ON s.rank_id=sr.rank_id
	     	WHERE s.supplier_id=".$result['supplier_id']." AND s.status=1";
			$shopuserinfo = $GLOBALS['db']->getRow($sql_supplier);
			$result = array_merge(array('supplier_name' => $shopuserinfo['supplier_name']?:'自营'), $result);

			$result['shipping_list'] = $result['supplier']['shipping_list'];
			$result['shipping_ziti'] = $result['supplier']['shipping_ziti'];
			$result['goods_list'] = $result['supplier']['goods_list'];
			$result['bonus_num'] = $result['supplier']['bonus_num'];
			$result['bonus_list'] = $result['supplier']['bonus_list'];
			$result['open_invoice'] = $result['supplier']['open_invoice'];
			$result['invoice_name'] = $result['supplier']['invoice_name'];
			$result['supplier_total'] = $result['supplier']['supplier_total'];
			unset($result['supplier']);

			$return['data']['supplier_list'][] = $result;
			//var_dump()
			// foreach($result as $v){
			// 	$result['shipping_list'] = $v['shipping_list'];
			// 	$result['goods_list'] = $v['goods_list'];
			// 	$result['bonus_list'] = $v['bonus_list'];
			// 	$result['supplier_total'] = $v['supplier_total'];
			// }
			// unset($result['supplier']);
			// $return['data']['supplier_list'][] = $result;

		}

		if($return['code'] == 500){
			Response::render(array(),$return['error_code'],$return['message']);
		}else{
			$this->success($return['data']);
		}
	}


	/**
	 * @description 订单提交资料页面
	 * @return void
	 */
	public function addOrder()
	{
		$user_rank_info = $this->user_rank_info;
		if(!empty($user_rank_info)){
	        $_SESSION['rank_name']=$user_rank_info['rank_name'];
	        $_SESSION['discount']=$user_rank_info['discount'];
	        $_SESSION['user_rank']=$user_rank_info['user_rank'];
	        $_SESSION['user_id']=$user_rank_info['user_id'];
	    }

		$device = $this->input('device');
		$is_design = $this->input('is_design',0,'intval');

		$_POST['supplier'] = isset($this->data['supplier'])?$this->data['supplier']:'';
		//error_log($_POST['supplier'],3,ROOT_PATH.'/data/payment/3.log');
		$supplier = json_decode(stripslashes(strip_tags(urldecode($_POST['supplier']))),true);

		if(empty($supplier) || !is_array($supplier['supplier']))
		{
			$this->error('参数错误');
		}
		//print_r($supplier['supplier']);

		foreach($supplier['supplier'] as $k=>$v){
			// $check1=array("shipping_id","bonus_id","bonus_sn","supplier_id");
			$check1=array("shipping_id","supplier_id");
			foreach($check1 as $vv ){
				if (!array_key_exists($vv,$v)){
					$this->error('supplier参数缺失');
				}
			}
			$flow_order['pay_ship'][$v['supplier_id']] = isset($v['shipping_id'])?intval($v['shipping_id']):0;
			$flow_order['pickup_point'][$v['supplier_id']] = isset($v['pickup_point'])?intval($v['pickup_point']):0;
			$flow_order['bonus'][$v['supplier_id']] = isset($v['bonus_id'])?intval($v['bonus_id']):0;
			$flow_order['bonus_sn'][$v['supplier_id']] = isset($v['bonus_sn'])?$v['bonus_sn']:'';
			$flow_order['message'][$v['supplier_id']] = isset($v['message'])?$v['message']:'';

			$inv_type = isset($v['inv_type'])?$v['inv_type']:'';
			if($inv_type){
				/*发票信息*/
				if($inv_type == 'normal_invoice') //普通发票
				{
					$inv_payee_type = isset($v['inv_payee_type'])?$v['inv_payee_type']:'individual';
					if($inv_payee_type=='individual'){
						$inv_arr = array('inv_type','inv_payee_type','inv_payee','inv_content');
					} else {
						$inv_arr = array('inv_type','inv_payee_type','vat_inv_taxpayer_id','inv_payee','inv_content');
					}

				}
				elseif($inv_type == 'online_normal_invoice') //电子普通发票
				{
					$inv_payee_type = isset($v['inv_payee_type'])?$v['inv_payee_type']:'individual';
					if($inv_payee_type=='individual'){
						$inv_arr = array('inv_type','inv_payee_type','inv_payee','inv_content','inv_consignee_phone','inv_consignee_email');
					} else {
						$inv_arr = array('inv_type','inv_payee_type','vat_inv_taxpayer_id','inv_payee','inv_content','inv_consignee_phone','inv_consignee_email');
					}
				}
				elseif($inv_type == 'vat_invoice') //增值税发票
				{
					$inv_arr = array('open_inv_type','inv_type','inv_content','vat_inv_company_name',
						'vat_inv_taxpayer_id','vat_inv_registration_address','vat_inv_registration_phone',
						'vat_inv_deposit_bank','vat_inv_bank_account','inv_consignee_name','inv_consignee_phone','inv_consignee_province','inv_consignee_city','inv_consignee_district','inv_consignee_address');
				}

				foreach($inv_arr as $key)
				{
					$value = !empty($v[$key])?trim($v[$key]):'';
					if(!empty($value))
					{
						$flow_order[$key][$v['supplier_id']]=$value;
					}
				}
			}
		}

		// $this->error($flow_order['message'][1]);
		//  exit;
		/*
		$_POST['invoice'] = isset($this->data['invoice'])?$this->data['invoice']:'';
		$invoice = json_decode(stripslashes(strip_tags(urldecode($_POST['invoice']))),true);
		if(!empty($invoice) && is_array($invoice))
		{
			foreach($invoice as $k=>$v){

				$check1=array("inv_type","inv_payee_type","inv_payee","inv_content,vat_inv_taxpayer_id");
			}

			$flow_order['inv_type'] = $invoice['inv_type'];
			$flow_order['inv_payee_type'] = $invoice['inv_payee_type'];
			$flow_order['inv_payee'] = $invoice['inv_payee']?:$this->error('发票抬头不能为空');


			if($invoice['inv_payee_type'] == 'unit'){
				$flow_order['vat_inv_taxpayer_id'] = isset($invoice['vat_inv_taxpayer_id'])?$invoice['vat_inv_taxpayer_id']:$this->error('发票纳税识别号不能为空');
			}

			$flow_order['inv_content'] = isset($invoice['inv_content'])?$invoice['inv_content']:'';
		}*/



		//print_r($flow_order);die();

		$sel_goods       = !empty($this->data['sel_goods']) ? compile_str($this->data['sel_goods']) : '';
		//$flow_order['pay_ship'][0] = !empty($this->data['shipping_id']) ? intval($this->data['shipping_id']) : 0;
		$flow_order['address_id']  = !empty($this->data['address_id']) ? intval($this->data['address_id']) : 0;
		$flow_order['pay_id']      = !empty($this->data['pay_id']) ? intval($this->data['pay_id']) : 0;

		$flow_order['integral']    = isset($this->data['integral']) ? $this->data['integral'] : 0;
		//$flow_order['bonus']       = isset($this->data['bonus']) ? $this->data['bonus'] : array();
		//$flow_order['bonus_sn']    = isset($this->data['bonus_sn']) ? $this->data['bonus_sn'] : array();
		//$flow_order['surplus']     = isset($this->data['surplus']) ? $this->data['surplus'] : 0;
		$flow_order['surplus']     = 0;
		$flow_order['pack']     = isset($this->data['pack']) ? $this->data['pack'] : 0;
		$flow_order['card']     = isset($this->data['card']) ? $this->data['card'] : 0;
		$flow_order['card_message']     = isset($this->data['card_message']) ? $this->data['card_message'] : '';
		$flow_order['need_inv']     = isset($this->data['need_inv']) ? $this->data['need_inv'] : 0;
		//$flow_order['postscript']     = isset($this->data['postscript']) ? $this->data['postscript'] : 0;
		//$flow_order['inv_type']     = isset($this->data['inv_type']) ? $this->data['inv_type'] : ''; // 发票类型
		//$flow_order['inv_payee_type']     = isset($this->data['inv_payee_type']) ? $this->data['inv_payee_type'] : '';

		//$flow_order['message']     = isset($this->data['message']) ? $this->data['message'] : '';

		/* 取得购物类型 */
		$flow_type = isset($this->data['flow_type']) ? $this->data['flow_type'] : CART_GENERAL_GOODS;




		//echo $flow_type;
		//$flow_type = CART_GENERAL_GOODS;
		//$flow_type = CART_EXCHANGE_GOODS;
		$result = $this->checkout->addOrder($this->user_rank_info, $flow_type, $sel_goods, $flow_order, $device, $is_design);


		//print_r($result);
		//$result['data']['payment'] = new stdClass();

		if($flow_order['pay_id'] && $result['data']){
			$sql = "SELECT pay_code,pay_name FROM ". $GLOBALS['ecs']->table("payment") ." WHERE `pay_id` = ".$flow_order['pay_id'];
			$payment = $GLOBALS['db']->getRow($sql);

			$params = array();
			$params['orderId'] = $result['data']['order_id'];
			$params['order_amount'] = $result['data']['order_amount'];
			$params['user_id'] = $this->user_id;
			$params['payment'] = $payment['pay_name'];
			$params['payment_code'] = $payment['pay_code'];

			$other = array();
			if($this->input('device')=='wap'){
				$other['openid'] = $params['openid'];
			}
			if($this->input('device')=='xcx'){
				$other['openid'] = $GLOBALS['db']->getOne("select openid FROM " . $GLOBALS['ecs']->table('third_login') ." WHERE user_id = '".$this->user_id."' and type = 'Wechat'");
			}
			//print_r($params);exit;
			$order = array();
			$order['order_id'] = $params['orderId'];
			$order['order_sn'] = $params['orderId'];

			//$order = $cls_order->getOrderById($params['orderId'],'*',$params['user_id']);
			//$order['log_id'] = $this->insert_pay_log($order['order_sn'], $order['order_amount'], PAY_ORDER);
			$sql = "SELECT *  FROM ". $GLOBALS['ecs']->table("order_info") ." WHERE `parent_order_id` = ".$order['order_id'];
			$parent_order_id = $GLOBALS['db']->getAll($sql);
			if(!empty($parent_order_id)){
				$all_order_amount = 0;
				foreach($parent_order_id as $v){
					$all_order_amount += $v['order_amount'];
				}
				$order['order_amount'] = $all_order_amount;
			}else{
				$order['order_amount'] = $GLOBALS['db']->getOne("SELECT order_amount  FROM ". $GLOBALS['ecs']->table("order_info") ." WHERE `order_id` = ".$order['order_id']);
				$order['order_sn'] = $GLOBALS['db']->getOne("SELECT order_sn  FROM ". $GLOBALS['ecs']->table("order_info") ." WHERE `order_id` = ".$order['order_id']);
			}

			$resultb = array();
			try {
				require(ROOT_PATH . 'includes/modules/payment/'.$params['payment'].'.php');
				$new_class = $params['payment'];
				$paymenta = new $new_class();
				$resultb['order_id'] = $params['orderId'];
				$resultb['order_amount'] = $params['order_amount'];
				$resulta = $paymenta->prepay($order,$params['payment_code'],$other);
				if($resulta){
					$resultb['payment'] = $resulta;
					$resultb['payment']['payment_name'] = $payment['pay_name'];
				}

				$this->success($resultb);

			} catch (Exception $e) {
				print_r($e);
			}
		}

		if($result['code'] == 500){
			$this->error($result['message']);
		}else{
			$result['flow_type']=$flow_type;
			$this->success($result['data']);
		}
	}

	private function get_pay_log($id)
	{
		$sql = "SELECT log_id FROM `hunuo_pay_log` WHERE `order_id` = ".$id." LIMIT 0, 30 ";
		$pay_id = $GLOBALS['db']->getOne($sql);

		return $pay_id;
	}




}
