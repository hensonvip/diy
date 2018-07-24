<?php
/**
 * 订单接口
 * 
 * @version v1.0
 * @create 2015-08-07
 * 发货单接口
 *
 */

require_once(ROOT_PATH . 'includes/cls_order.php');
require_once(ROOT_PATH . 'includes/cls_shipping.php');
require_once(ROOT_PATH . 'includes/cls_refund.php');
require_once(ROOT_PATH . 'includes/cls_payment.php');
require_once(ROOT_PATH . 'includes/cls_haitao.php');

class DeliveryController extends ApiController
{
	private function log($msg, $level = 'info')
	{
		$this->logger->writeLog($msg, $level, 'order');
	}


	public function __construct()
	{
		parent::__construct();
		$this->data = json_decode(stripslashes($this->input('data')),true);
		$this->delivery  = cls_delivery::getInstance();
		$this->order     = cls_order::getInstance();
		$config = array(
			'type'=>'file',
			'log_path'=> ROOT_PATH . '/data/logs/api/delivery/'
		);
		$this->logger = new Logger($config);
	}

	public function haitaoOrderDelivery(){


//		$ss = '{"code":100,"data":[{"orderItems":[{"quantity":1,"sku":"952841-00016-01"}],"orderNumber":"SO9376515120100010","orderStatus":"R","referenceNumber":"2015112348564"}],"orderSn":"2015112348564","status":1}';
//
//		{\"code\":\"100\",\"data\":\"[{\\\"orderItems\\\":[{\\\"quantity\\\":1,\\\"sku\\\":\\\"952841-000010-02\\\"}],\\\"orderNumber\\\":\\\"SO9376515111800002\\\",\\\"orderSn\\\":\\\"2015111847444\\\",\\\"orderStatus\\\":\\\"R\\\"}]\",\"orderSn\":\"2015111847444\",\"status\":1}
//		error_reporting(E_ALL);
//		ini_set('display_errors', 1);



//		$this->data = json_decode(stripslashes($ss),true);
		$this->data = json_decode(stripslashes($this->input('data')),true);

		$json_data = $this->input('data');
		$this->logger->writeLog($json_data, 'INFO', 'haitao');
		$input['order_sn']  	    = isset($this->data['orderSn']) ? addslashes(trim($this->data['orderSn'])) : $this->error('缺少orderSn参数');


		$orderInfo = cls_order::getInstance()->getOrderBySn($input['order_sn'],'order_id,order_status,shipping_status,pay_status')['data'];
		if(!$orderInfo){
			$this->success('订单不存在',15007008);
		}

		$composite_status = cls_order::getInstance()->get_composite_status($orderInfo['order_status'],$orderInfo['shipping_status'],$orderInfo['pay_status']);

		// 只有已审核订单才能进行海淘分单
		if(!in_array($composite_status,array(CS_AWAIT_CHECK,CS_CHECKED))){
			$this->logger->writeLog('该订单状态不支持海淘分单,订单号：'.$input['order_sn'].';data:'.$json_data, 'ERROR', 'haitao');
			$action_note = '该订单状态不支持分单';
			cls_order::getInstance()->addOrderAction(array('action_note'=>$action_note, 'action_user'=>'系统', 'order_id'=>$orderInfo['order_id']));
			$this->success('该订单状态不支持海淘分单。');

		}


		// 失败直接写日志
		if($input['order_sn'] && $this->data['code'] == 100){

			$data = $this->data['data'];

			if(count($data) > 0){

				$return_result = 0;
				// 循环生成发货单
				foreach($data as $data_k=>$data_v){

//					$return_result[$data_k] = 0;

					$this->logger->writeLog('海淘生成发货单日志点;data:'.$json_data, 'DEBUG', 'haitao');

					$delivery_info['order_id'] = $orderInfo['order_id'];
					$delivery_info['action_note'] = '海淘分单成功';
					$delivery_info['action_user'] = '系统';
					$delivery_info['delivery_sn_cus'] = $data_v['orderNumber'];
					$delivery_info['is_haitao'] = 1;


					$old_delivery_order = cls_delivery::getInstance()->getDeliveryByOuterSn($data_v['orderNumber'],'order_id')['data'];

					if($old_delivery_order){
						$this->success('该发货单已存在。');
					}

					$send_goods = array();
					$product_sn_arr = array();
					if(count($data_v['orderItems']) > 0){

						foreach($data_v['orderItems'] as $goods_k => $goods_v){
							// todo 映射 product_sn
							$map_sku    = cls_haitao::getInstance()->getProductSnBymapperSn($goods_v['sku']);
							$goods_info = cls_order::getInstance()->getGoodsByProductSn($orderInfo['order_id'],$map_sku)['data'];
							if(!$goods_info){
								// 可以重推
								$arr['push_haitao'] = 3;
								cls_order::getInstance()->updateStatus($orderInfo['order_id'], $arr);
								$this->logger->writeLog('海淘分单失败,找不到相应的订单商品', 'ERROR', 'haitao');
								$action_note = '海淘分单失败,找不到相应的订单商品'.$goods_v['sku'];
								cls_order::getInstance()->addOrderAction(array('action_note'=>$action_note, 'action_user'=>'系统', 'order_id'=>$orderInfo['order_id']));
							}else{
								$product_sn_arr[$goods_k]['rec_id'] = $goods_info['rec_id'];
								$product_sn_arr[$goods_k]['quantity'] = $goods_v['quantity'];
							}
						}



						if(!$product_sn_arr){
							// 可以重推
							$arr['push_haitao'] = 3;
							cls_order::getInstance()->updateStatus($orderInfo['order_id'], $arr);
							$this->error('海淘分单失败,找不到相应的订单商品。',15007008);
						}

						foreach($product_sn_arr as $p_k=>$p_v){
							$send_goods[$p_v['rec_id']] = $p_v['quantity'];

						}

						$result = cls_delivery::getInstance()->create($delivery_info,$send_goods);

						if($result['code'] == 0){
							$return_result++;
						}else{
							$this->logger->writeLog('分单失败原因;data:'.$result['msg'], 'ERROR', 'haitao');
							$action_note = '分单失败，原因;data:'.$result['msg'];
							cls_order::getInstance()->addOrderAction(array('action_note'=>$action_note, 'action_user'=>'系统', 'order_id'=>$orderInfo['order_id']));
						}
					}else{
						$action_note = '分单失败，原因;data:没有返回订单商品';
						cls_order::getInstance()->addOrderAction(array('action_note'=>$action_note, 'action_user'=>'系统', 'order_id'=>$orderInfo['order_id']));
						// 可以重推
						$arr['push_haitao'] = 3; // 分单失败
						cls_order::getInstance()->updateStatus($orderInfo['order_id'], $arr);
						$this->error('没有返回订单商品。');
					}
				}

				if(count($data) == $return_result){
					$this->success('分单成功');
				}else{

					$arr['push_haitao'] = 3; // 分单失败
					cls_order::getInstance()->updateStatus($orderInfo['order_id'], $arr);
					$this->error('分单失败');
				}

			}else{
				$action_note = '回推海淘城分单信息失败';
				cls_order::getInstance()->addOrderAction(array('action_note'=>$action_note, 'action_user'=>'系统', 'order_id'=>$orderInfo['order_id']));
				$this->logger->writeLog('回推海淘城分单信息失败，返回data数据为空，不处理分单。'.$input['order_sn'].';data:'.$json_data, 'ERROR', 'haitao');
				$this->success('返回data数据为空，不处理分单。');
			}
		}else{

			$update['push_haitao'] = 3;
			cls_order::getInstance()->updateStatus($orderInfo['order_id'], $update);

			$action_note = '回推海淘城分单信息失败，返回状态不正确，不处理分单：'.$json_data;
			cls_order::getInstance()->addOrderAction(array('action_note'=>$action_note, 'action_user'=>'系统', 'order_id'=>$orderInfo['order_id']));
			$this->success('返回状态不正确，不处理分单。');
		}
	}

	/**
	 * 接收open推单给海淘城的订单状态
	 *
	 * @version v1.0
	 * @create 2015-11-13
	 * @author Jam.Cheng
	 *
	 */
	public function haitaoOrderStatus(){

//		已接单：0001
//		已发货：0006
//		取消或删除：0011

//		$json_data = $this->input('data');
//		$format_json_data = json_decode(stripslashes($this->input('data')),true);
//		$this->data = json_decode($format_json_data['data'],true);



//		$ss = '{\"carrier\":\"圆通\",\"deliveryTime\":\"1445322142000\",\"orderSn\":\"2015112559254\",\"orderStatus\":\"0006\",\"outerOrderSn\":\"SO9376515112500029\",\"trackingInfos\":[{\"carrier\":\"圆通\",\"isSupportQuery\":1,\"number\":\"805628805510\"}]}';

		$this->data = json_decode(stripslashes($this->input('data')),true);
//		$this->data = json_decode(stripslashes($ss),true);
		$json_data = $this->input('data');



		$this->logger->writeLog($json_data, 'INFO', 'haitao');

		$input['order_status']    = isset($this->data['orderStatus']) ? $this->data['orderStatus'] : $this->error('缺少orderStatus参数');
		$input['order_sn']        = isset($this->data['orderSn']) ? addslashes(trim($this->data['orderSn'])) : $this->error('缺少orderSn参数');
		$input['delivery_sn_cus'] = isset($this->data['outerOrderSn']) ? addslashes(trim($this->data['outerOrderSn'])) : $this->error('缺少outerOrderSn参数');
		$input['carrier']         = isset($this->data['carrier']) ? addslashes(trim($this->data['carrier'])) : '';
		$input['delivery_time']    = isset($this->data['deliveryTime']) ? addslashes(trim($this->data['deliveryTime'])) : '';
		$input['trackingInfos']   = isset($this->data['trackingInfos']) ? $this->data['trackingInfos'] : array();


		// 已接单
		if($input['order_status'] == '0001' OR $input['order_status'] == '0002'){
			$this->success('接收参数成功！');
		}else if($input['order_status'] == '0006'){	// 已发货

			if(empty($input['carrier'])){
				$this->success('缺少carrier参数！');
			}
			if(empty($input['delivery_time'])){
				$this->success('缺少deliveryTime参数！');
			}
			if(empty($input['trackingInfos'])){
				$this->success('缺少trackingInfos参数！');
			}

			$delivery_info = cls_delivery::getInstance()->getDeliveryByOuterSn($input['delivery_sn_cus'],'wms_type,delivery_sn,supplier_id,order_id,delivery_id,status')['data'];

			$shipping_info = cls_delivery::getInstance()->getShippingByName($input['carrier'])['data'];
			if(!$shipping_info){
				$update['push_haitao'] = 4;
				cls_order::getInstance()->updateStatus($delivery_info['order_id'], $update);
				$action_note = '找不到该快递公司或由快递公司不对应引起。海淘返回快递公司为：'.$input['carrier'];
				cls_order::getInstance()->addOrderAction(array('action_note'=>$action_note, 'action_user'=>'系统', 'order_id'=>$delivery_info['order_id']));
				$this->success($action_note);
			}

			if(!$delivery_info){
				$action_note = '找不到该发货单或发货单号不对应。海淘返回外部快递单号为：'.$input['delivery_sn_cus'];
				cls_order::getInstance()->addOrderAction(array('action_note'=>$action_note, 'action_user'=>'系统', 'order_id'=>$delivery_info['order_id']));
				$this->success($action_note);
			}

			if($delivery_info['status'] == '0'){
				$this->success('该发货单处于已发货状态，不能再发货:'.$input['delivery_sn_cus']);
			}

			$delivery['order_id']      = $delivery_info['order_id'];
			$delivery['supplier_id']   = $delivery_info['supplier_id'];
			$delivery['shipping_id']   = $shipping_info['shipping_id'];
			$delivery['invoice_no']    = $input['trackingInfos'][0]['number'];
			if(isset($input['trackingInfos'][0]['isSupportQuery']) AND $input['trackingInfos'][0]['isSupportQuery']==0){
				$delivery['wms_method'] = 1;
			}else{
				$delivery['wms_method'] = 2;
			}
			$delivery['action_user']   = '海淘城供应商';
			$delivery['delivery_sn']   = $delivery_info['delivery_sn'];
			$delivery['type']  		   = $delivery_info['wms_type'];
			$delivery['is_haitao']     = 1;
			$result = cls_delivery::getInstance()->deliveryShip($delivery,1);

			if($result['code'] == 1){
				$this->success('发货单发货成功');
			}else{
				$update['push_haitao'] = 4;
				cls_order::getInstance()->updateStatus($delivery_info['order_id'], $update);
				$action_note = '发货单发货失败,失败原因：'.$result['msg'];
				cls_order::getInstance()->addOrderAction(array('action_note'=>$action_note, 'action_user'=>'系统', 'order_id'=>$delivery_info['order_id']));
				$this->success($action_note);
			}
		}else if($input['order_status'] == '0011'){ 		// 取消或删除：0011

//			$delivery_info = cls_delivery::getInstance()->getDeliveryByOuterSn($input['delivery_sn_cus'],'delivery_sn,supplier_id,order_id,delivery_id')['data'];
//
//			if(!$delivery_info){
//				$this->error('找不到该发货单:'.$input['delivery_sn_cus']);
//			}
//
//			// 取消分单
//			$result = cls_delivery::getInstance()->cancelDelivery($delivery_info['order_id'],$delivery_info['delivery_id'],'[海淘城]');

			$delivery_info = cls_delivery::getInstance()->getDeliveryByOuterSn($input['delivery_sn_cus'],'delivery_sn,supplier_id,order_id,delivery_id,status')['data'];
			if(!$delivery_info){
				$this->success('找不到该发货单:'.$input['delivery_sn_cus']);
			}

			if($delivery_info['status'] == '0'){
				$this->success('该发货单处于已发货状态，不能再取消分单:'.$input['delivery_sn_cus']);
			}
			$order = cls_order::getInstance()->getOrderById($delivery_info['order_id'])['data'];

			/* 记录log */
			order_action($order['order_sn'], $order['order_status'], $order['shipping_status'], $order['pay_status'], '海淘城取消发货单', 0, '系统');
			$this->success('已记录日志');
		}else{ 		// 其他状态

			$delivery_info = cls_delivery::getInstance()->getDeliveryByOuterSn($input['delivery_sn_cus'],'delivery_sn,supplier_id,order_id,delivery_id,status')['data'];
			if(!$delivery_info){
				$this->success('找不到该发货单:'.$input['delivery_sn_cus']);
			}
			$order = cls_order::getInstance()->getOrderById($delivery_info['order_id'])['data'];
			/* 记录log */
			order_action($order['order_sn'], $order['order_status'], $order['shipping_status'], $order['pay_status'], '海淘城订单返回异常，请与对方联系', 0, '系统');

//			$arr['invoice_no'] = $order['invoice_no'];
//			$query = cls_order::getInstance()->updateStatus(array($input['order_id']), $arr);
//
//			if (!$query)
//			{
//				$this->db->query('ROLLBACK');
//				$msg = '修改订单状态失败。error:'.$this->db->error();
//				$this->log($msg,'error');
//				return $this->error('-1',$msg);
//			}

			$this->success('已记录日志');
		}

	}

	// 是否开启海淘城
	public function usingHaitao(){
		$using_haitao = cls_delivery::getInstance()->useingHaitao()['data'];
		if($using_haitao == 1){
			$this->success('已开启海淘城');
		}else{
			$this->error('已关闭海淘城');
		}
	}


	private function statusName($status_code)
	{
		/* 发货单状态 */
		$status[0] = '已发货';
		$status[1] = '退货';
		$status[2] = '正常';
		$status[3] = '已发货';
		$status[4] = '已签收';
		$status[6] = '已发货';
		$status[8] = '接单失败';
		$status[10] = '已接单';
		$status[11] = '已发货';
		$status[13] =  '撤单成功';
		
		return isset($status[$status_code]) ? $status[$status_code] : '未知状态';
	}

	protected function allStatusName(&$status)
	{
		require_once(ROOT_PATH . 'languages/zh_cn/admin/order.php');
		global $_LANG;
		$order_status = isset($_LANG['os'][$status['order_status']]) ? strip_tags($_LANG['os'][$status['order_status']]) :  "未知状态";
		$shipping_status = isset($_LANG['ss'][$status['shipping_status']]) ? strip_tags($_LANG['ss'][$status['shipping_status']]) :  "未知状态";
		$pay_status = isset($_LANG['ps'][$status['pay_status']]) ? strip_tags($_LANG['ps'][$status['pay_status']]) :  "未知状态";
		$delivery_status = isset($_LANG['delivery_status'][$status['delivery_status']]) ? strip_tags($_LANG['delivery_status'][$status['delivery_status']]):"未知状态";
		return array('order_status'=>$order_status, 'shipping_status'=>$shipping_status, 'pay_status'=>$pay_status, 'delivery_status'=>$delivery_status);
	}

	/**
	 * 发货单发货接口
	 * 
	 * @return array
	 * @create 2015-10-26 10:20:13
	 * @author lwp
	 * @wiki http://wiki.corp.mama.cn/pages/viewpage.action?pageId=65078679
	 */
	public function ship()
	{
		$require_fields = array('supplierId','orderId','shippingId','invoiceNo','actionUser','deliverySn','type', 'deliveryId');
		foreach($require_fields as $v)
		{
			if(!isset($this->data[$v]) || empty($this->data[$v]))
			{
				$this->error("缺失必选参数 ({$v})，请参考API文档", '-1');
			}else{
				$params[$v] = addslashes(trim($this->data[$v]));
			}
		}

		$order_id = intval($params['orderId']);
		if (isset($this->data['virtual']) && $this->data['virtual'])
		{
			$delivery_info = array('order_id'=>$order_id, 'delivery_id'=>$params['deliveryId'], 'action_user'=>$params['actionUser'], 'action_note'=>'');
			$send_goods = array();
			//$order_goods = $this->order->getGoodsById($order_id)['data'];
			//foreach ($order_goods as $v)
			//{
				//$send_goods[$v['rec_id']] = $v['goods_number'];
			//}
			$result_virtual = $this->delivery->deliveryShipVirtual($delivery_info, $send_goods);
			if ($result_virtual['code'] !== 0)
			{
				$this->error($result_virtual['msg']); 
			}
			else
			{
				$this->success($result_virtual['msg']);
			}
		}

		$input = array(
			'order_id'   	=> $order_id,
			'supplier_id'   => intval($params['supplierId']),
			'shipping_id' 	=> $params['shippingId'],
			'invoice_no' 	=> $params['invoiceNo'],
			'action_user' 	=> $params['actionUser'],
			'delivery_sn' 	=> $params['deliverySn'],
			'type' 	        => intval($params['type'])   // 物流段：1、国内发货（普通订单） 2、国际段发货（海外直邮）
		);


		$result = $this->delivery->deliveryShip($input);

		if($result['code'] > 0){
			$this->success($result['msg']);
		}else{
			$this->error($result['msg']);
		}

	}
	
	/**
	 * 订单发货接口
	 * 
	 * @return array
	 * @create 2015-10-26 10:20:13
	 * @author lwp
	 * @wiki http://wiki.corp.mama.cn/pages/viewpage.action?pageId=65078675	
	 */
	public function shipByOrder()
	{
		$require_fields = array('orderId','shippingId','invoiceNo','actionUser','type', 'supplierId');
		foreach($require_fields as $v)
		{
			if(!isset($this->data[$v]) || empty($this->data[$v]))
			{
				$this->error("缺失必选参数 ({$v})，请参考API文档", '-1');
			}else{
				$params[$v] = addslashes(trim($this->data[$v]));
			}
		}

		$order_id = intval($params['orderId']);
		// 虚拟商品发货
		if (isset($this->data['virtual']) && $this->data['virtual'])
		{
			$delivery_info = array('order_id'=>$order_id, 'action_user'=>$params['actionUser'], 'action_note'=>'');
			$send_goods = array();
			$order_goods = $this->order->getGoodsById($order_id)['data'];
			foreach ($order_goods as $v)
			{
				$send_goods[$v['rec_id']] = $v['goods_number'];
			}
			$result_virtual = $this->delivery->deliveryShipVirtual($delivery_info, $send_goods);
			if ($result_virtual['code'] !== 0)
			{
				$this->error($result_virtual['msg']); 
			}
			else
			{
				$this->success($result_virtual['msg']);
			}
		}

		$input_create = array(
			'order_id'   	=> $order_id,
			//'supplier_id'   => intval($params['supplierId']),
			'action_user' 	=> $params['actionUser'],
		);
		$result_create = $this->delivery->splitByOrder($input_create);
		if (!isset($result_create['data'][0]['data']) || empty($result_create['data'][0]['data']))
		{
			$msg = isset($result_create['data'][0]['msg']) ? $result_create['data'][0]['msg'] : "生成发货单失败";
			$this->error($msg);
		}
		$delivery_id = $result_create['data'][0]['data'];

		$input = array(
			'order_id'   	=> $order_id,
			'supplier_id'   => intval($params['supplierId']),
			'shipping_id' 	=> $params['shippingId'],
			'invoice_no' 	=> $params['invoiceNo'],
			'action_user' 	=> $params['actionUser'],
			'delivery_id' 	=> $delivery_id,
			'type' 	        => intval($params['type'])   // 物流段：1、国内发货（普通订单） 2、国际段发货（海外直邮）
		);

		$result = $this->delivery->deliveryShip($input);

		if($result['code'] > 0){
			$this->success($result['msg']);
		}else{
			$this->error($result['msg']);
		}

	}


	/**
	 * 发货单详情
	 * 
	 * @return string json
	 * @create 2015-11-02 10:01:56
	 * @author lwp
	 * @wiki http://wiki.corp.mama.cn/pages/viewpage.action?pageId=65079010
	 */
	public function detail()
	{
		$require_fields = array('supplierId','orderId','deliveryId');

		foreach($require_fields as $v)
		{
			if(!isset($this->data[$v]) || empty($this->data[$v]))
			{
				$this->error("缺失必选参数 ({$v})", '-1');
			}
			else
			{
				$params[$v] = intval($this->data[$v]);
			}
		}

		// 发货单信息
		$fields = "O.add_time, O.address, O.address_type, O.city, O.consignee, O.district, O.goods_amount, O.inv_payee, O.is_customs, O.mobile, O.pay_time, O.province, O.receive_time, O.shipping_time, O.shipping_time as delivery_time,O.tel, O.zipcode, D.order_id, D.order_sn, D.delivery_sn, D.status as delivery_status_code, D.wms_type, D.delivery_id, D.invoice_no, D.shipping_id, D.shipping_name, D.status";
		$result_delivery = $this->delivery->getDeliveryJoinById($params['deliveryId'], $fields);

		if (!isset($result_delivery['data']) || empty($result_delivery['data']))
		{
			$this->success(new stdClass());
		}
		$delivery = $result_delivery['data'];

		// 物流信息
		$result_wms = $this->delivery->getWmsHistoryByDeliverySn($delivery['delivery_sn']);
		$home_wms_history = $abroad_wms_history = array();
		if (isset($result_wms['data']) && !empty($result_wms['data']))
		{
			foreach ($result_wms['data'] as $v)
			{
				// 加上wms_type==1 的判断，因为商家中心发货订阅时未处理wms_history字段的express_type字段，所以临时加上，后期可以去掉
				if ($v['express_type'] == 1 || $delivery['wms_type'] == 1)
				{
					// 国内
					$home_wms_history[] = array(
							'context' 	=>$v['context'],
							'time' 		=>$v['ftime']
							);
				}
				else
				{
					// 国际
					$abroad_wms_history[] = array(
							'context' 	=>$v['context'],
							'time' 		=>$v['ftime']
							);
				
				}
			}
		}

		// 国内物流
		if (!empty($delivery['invoice_no']))
		{
			if ($delivery['wms_type'] == 1)
			{
				// 只有国内物流
				$delivery['home_wms_info'] = array(
					'delivery_id' 	=>$delivery['delivery_id'],
					'invoice_no' 	=>$delivery['invoice_no'],
					'shipping_id' 	=>$delivery['shipping_id'],
					'shipping_name' =>$delivery['shipping_name'],
					'wms_history' 	=>$home_wms_history,
					);

			}
			else
			{
				// 国际物流国内段
				$home_wms_info = $this->delivery->getWmsInfoByDeliveryId($delivery['delivery_id']);
				if (isset($home_wms_info['data']) && $home_wms_info['data'])
				{
					$delivery['delivery_time_in'] = $home_wms_info['data']['action_time'];
					$delivery['shipping_name_in'] = $home_wms_info['data']['shipping_name'];
					$delivery['invoice_no_in'] = $home_wms_info['data']['invoice_no'];
					$delivery['home_wms_info'] = array(
						'delivery_id' 	=>$home_wms_info['data']['delivery_id'],
						'invoice_no' 	=>$home_wms_info['data']['invoice_no'],
						'shipping_id' 	=>$home_wms_info['data']['shipping_id'],
						'shipping_name' =>$home_wms_info['data']['shipping_name'],
						'wms_history' 	=>$home_wms_history,
						);

				}
				else
				{
					$delivery['home_wms_info'] = null;
				}
			}
				
		}
		else
		{
			$delivery['home_wms_info'] = null;
		}

		// 国际物流，且已发货
		if (!empty($delivery['invoice_no']) && $delivery['wms_type'] == 2)
		{
			$delivery['abroad_wms_info'] = array(
					'delivery_id' 	=>$delivery['delivery_id'],
					'invoice_no' 	=>$delivery['invoice_no'],
					'shipping_id' 	=>$delivery['shipping_id'],
					'shipping_name' =>$delivery['shipping_name'],
					'wms_history' 	=>$abroad_wms_history,
					);
		}
		else
		{
			$delivery['abroad_wms_info'] = null;
		}


		// 发货单商品
		$fields = "d.goods_name, d.goods_id, d.goods_attr, d.product_sn, d.product_id, d.send_number as goods_number";
		$delivery_goods = $this->delivery->getGoodsJoinById($delivery['delivery_id'], $fields);

		// 查订单商品表，获取商品价格和优惠金额	
		$order_goods = $this->order->getOrderGoodsById($delivery['order_id']);
		$goods_list = array();
		if (isset($delivery_goods['data']) && !empty($delivery_goods['data'])
			&& isset($order_goods['data']) && !empty($order_goods['data']))
		{
			foreach ($delivery_goods['data'] as $dg)
			{
				foreach ($order_goods['data'] as $og)
				{
					if ($dg['product_id'] == $og['product_id'])
					{
						// 计算优惠金额
						$reduce_price = 0;
						if ($og['reduce_price'] > 0)
						{
							$reduce_price = bcmul(bcdiv($og['reduce_price'], $og['goods_number'], 2), $dg['goods_number'], 2);
						}
						$dg['goods_thumb'] = $og['goods_thumb'];
						$dg['goods_price'] = $og['goods_price'];
						$dg['reduce_price'] = $reduce_price;
						$goods_list[] = $dg;
					}
				}
			}
		}

		// 读取报关信息

		if($delivery['is_customs'] == 1) {
			$order_customs = $this->delivery->getOrderCustoms($delivery['order_id'])['data'];
		}

//		print_R($delivery);
//		exit();
		$delivery['goods_list'] = $goods_list;
		$delivery['delivery_status_code'] = $delivery['status'];
		$delivery['delivery_status'] = $this->statusName($delivery['status']);
		$delivery['order_customs'] = empty($order_customs)?'':$order_customs;
		
		$data = $this->mapFields($delivery);
		$this->success($data);
	}

	/**
	 * 
	 * 
	 * @return 
	 * @create 2015-11-02 16:39:50
	 * @author veapon(veapon88@gmail.com)
	 * @wiki http://wiki.corp.mama.cn/pages/viewpage.action?pageId=65078673
	 */
	public function getByOrderId()
	{
		if (!isset($this->data['orderId']) || empty($this->data['orderId']))
		{
			$this->error('参数 orderId 不能为空', -1);
		}

		$order_id = intval($this->data['orderId']);

		// 发货单信息
		$result_delivery = $this->delivery->getOrderDelivery($order_id);
		if (!isset($result_delivery['data']) || empty($result_delivery['data']))
		{
			$this->success(array(), 0, "找不到该订单的发货单");
		}

		$delivery = $result_delivery['data'];
		//dump($delivery);

		// 发货单商品
		$arr_delivery_ids = array_column($delivery, 'delivery_id');
		//dump($arr_delivery_ids);
		$result_goods = $this->delivery->getDeliveryGoods($arr_delivery_ids);
		if (!isset($result_goods['data']) || empty($result_goods['data']))
		{
			$this->success(array(), 0, "找不到发货单商品"); 
		}
		//dump($result_goods);die;

		$products_map = array();
		foreach ($delivery as &$d)
		{
			// 国际物流，再去查国内段物流信息
			if ($d['wms_type'] == 2)
			{
				$wms_info = $this->delivery->getWmsInfoByDeliveryId($d['delivery_id']);

				if (isset($wms_info['data']) && !empty($wms_info['data']))
				{
					$d['invoice_no_in'] = $wms_info['data']['invoice_no'];
					$d['shipping_name_in'] = $wms_info['data']['shipping_name'];
					$d['shipping_id_in'] = $wms_info['data']['shipping_id'];
					$d['delivery_time_in'] = date('Y-m-d H:i:s', $wms_info['data']['action_time']);
				}
				else
				{
					$d['invoice_no_in'] = '';
					$d['shipping_name_in'] = ''; 
					$d['shipping_id_in'] = '';
					$d['delivery_time_in'] = '';
				}
			}
			else
			{
				$d['invoice_no_in'] = '';
				$d['shipping_name_in'] = ''; 
				$d['shipping_id_in'] = '';
				$d['delivery_time_in'] = '';
			}

			$d['delivery_time'] = $d['update_time'] ? date('Y-m-d H:i:s', $d['update_time']) : '';
			$d['delivery_status'] = $this->statusName($d['status']);
			foreach ($result_goods['data'] as $v)
			{
				if ($d['delivery_id'] == $v['delivery_id'])
				{
					$d['products_map']["{$v['product_id']}"] = $v['send_number'];
				}
			}
		}
		unset($d);
		//dump($products_map);

		$data = $this->mapFields($delivery);
		$this->success($data);
	}

	/**
	 * 
	 * 
	 * @return 
	 * @create 2015-11-02 16:39:50
	 * @author veapon(veapon88@gmail.com)
	 * @wiki http://wiki.corp.mama.cn/pages/viewpage.action?pageId=65079223
	 */

	public function getByMultiOrderId()
	{
		if (!isset($this->data['orderIds']) || empty($this->data['orderIds']))
		{
			$this->error('参数 orderIds 不能为空', -1);
		}
		$arr_order_id = $this->data['orderIds'];

		// 发货单信息
		$fields = "D.delivery_sn, D.delivery_id, D.status, D.wms_type, D.delivery_sn_cus, 0 as goods_amount, D.order_id, D.order_sn, D.invoice_no, D.shipping_name, D.shipping_id";
		$result_delivery = $this->delivery->getDeliveryByOrderIds($arr_order_id, $fields);
		if (!isset($result_delivery['data']) || empty($result_delivery['data']))
		{
			$this->success(array(), 0, "找不到该订单的发货单");
		}
		$delivery = $result_delivery['data'];
		//dump($delivery);

		// 发货单商品
		$arr_delivery_ids = array_column($delivery, 'delivery_id');
		//dump($arr_delivery_ids);

		$fields = "d.goods_name, d.goods_id, d.goods_attr, d.send_number as goods_number, d.product_sn, d.product_id, d.delivery_id, g.pick_title, g.goods_thumb";
		$result_goods = $this->delivery->getGoodsJoinById($arr_delivery_ids, $fields);

		if (!isset($result_goods['data']) || empty($result_goods['data']))
		{
			$this->success(array(), 0, "找不到发货单商品"); 
		}
		//dump($result_goods);die;

		$data_list = array();
		foreach ($delivery as $d)
		{
			$d['delivery_status'] = $this->statusName($d['status']);

			// 查订单商品，用到优惠金额、商品金额
			$result_og = $this->order->getOrderGoodsById($d['order_id']);
			if (!isset($result_og['data']) || empty($result_og['data']))
			{
				continue;
			}
			$og = array();
			foreach ($result_og['data'] as $v)
			{
				$og[$v['product_id']] = $v;
			}

			// 国际物流，再去查国内段物流信息
			if ($d['wms_type'] == 2)
			{
				$wms_info = $this->delivery->getWmsInfoByDeliveryId($d['delivery_id']);

				if (isset($wms_info['data']) && !empty($wms_info['data']))
				{
					$d['invoice_no_in'] = $wms_info['data']['invoice_no'];
					$d['shipping_name_in'] = $wms_info['data']['shipping_name'];
				}
				else
				{
					$d['invoice_no_in'] = '';
					$d['shipping_name_in'] = ''; 
				}
			}
			else
			{
				$d['invoice_no_in'] = $d['invoice_no'];
				$d['shipping_name_in'] = $d['shipping_name'];
				$d['invoice_no'] = '';
				$d['shipping_name'] = '';
			}

			foreach ($result_goods['data'] as $dg)
			{
				if ($d['delivery_id'] == $dg['delivery_id'])
				{
					// 计算优惠金额
					$reduce_price = 0;
					if ($og[$dg['product_id']]['reduce_price'] > 0)
					{
						$reduce_price = bcmul(bcdiv($og[$dg['product_id']]['reduce_price'], $og[$dg['product_id']]['goods_number'], 2), $dg['send_number'], 2);
					}
					$dg['reduce_price'] = $reduce_price;
					$dg['goods_price'] = $og[$dg['product_id']]['goods_price'];
					$dg['rec_type'] = $og[$dg['product_id']]['rec_type'];
					$d['goods_amount'] = bcadd($d['goods_amount'], bcsub($dg['goods_price'], $reduce_price, 2), 2);
					$d['goods_list'][] = $dg;
				}
			}
			$data_list["{$d['order_id']}"][] = $d;
		}

		//$data = $this->mapFields($delivery);
		$this->success($data_list);
	}

	/**
	 * 订单分发货单（拆分包裹）
	 *
	 * @return array
	 * @create 2015-11-02 10:20:13
	 * @author lwp
	 * @wiki http://wiki.corp.mama.cn/pages/viewpage.action?pageId=65079012
	 */
	public function split()
	{
		$require_fields = array('orderId','supplierId','actionUser','productsMapList');
		foreach($require_fields as $v)
		{
			if(!isset($this->data[$v]) || empty($this->data[$v]))
			{
				$this->error("缺失必选参数 ({$v})，请参考API文档", '-1');
			}else{
				$params[$v] = $this->data[$v];
			}
		}

		$product_list = $this->data['productsMapList'];

		$input = array(
			'order_id'   	=> intval($params['orderId']),
			'supplier_id'   => intval($params['supplierId']),
			'action_user' 	=> addslashes(trim($params['actionUser'])),
			'delivery_goods'=> $product_list,
		);

		$result = $this->delivery->splitByOrder($input);
		//dump($result);die;

		//$success = $failed = 0;
		//if (is_array($result))
		//{
			//foreach ($result as $row)
			//{
				//if (isset($row['data']) && $row['data'] > 0)
				//{
					//$success++;
				//}
				//else
				//{
					//$failed++;
				//}
			//}
		//}

		if (isset($result['code']) && $result['code'] === 0)
		{
			$this->success(new stdClass, 0, '操作成功'); 
		}
		else
		{
			$msg = isset($result['msg']) ? ",".$result['msg'] : "";
			$this->errro("操作失败".$msg, -1);
		}
	}


	/**
	 * 订单分发货单（拆分包裹）
	 *
	 * @return array
	 * @create 2015-11-02 10:20:13
	 * @author lwp
	 * @wiki http://wiki.corp.mama.cn/pages/viewpage.action?pageId=65078679
	 */
	public function undeliveryNum()
	{
		$require_fields = array('supplierId','wmsType');
		foreach($require_fields as $v)
		{
			if(!isset($this->data[$v]) || empty($this->data[$v]))
			{
				$this->error("缺失必选参数 ({$v})，请参考API文档", '-1');
			}else{
				$params[$v] = addslashes(trim($this->data[$v]));
			}
		}

		$input = array(
			'supplier_id'   	=> intval($params['supplierId']),
			'wms_type'   	    => intval($params['wmsType']),
		);

		$result = $this->delivery->undeliveryNum($input);

		$this->success($result['data']);

	}

	/**
	 * 
	 * 
	 * @return 
	 * @create 2015-11-03 00:01:38
	 * @author veapon(veapon88@gmail.com)
	 * @wiki http://wiki.corp.mama.cn/pages/viewpage.action?pageId=65078683
	 */
	public function updateShippingInfo()
	{
		$require_fields = array('supplierId','type','deliveryId','invoiceNo','shippingId','shippingName','actionUser');
		foreach($require_fields as $v)
		{
			if(!isset($this->data[$v]) || empty($this->data[$v]))
			{
				$this->error("缺失必选参数 ({$v})，请参考API文档", '-1');
			}
		}
		$delivery_id = $this->data['deliveryId'];
		$wms_type = $this->data['type'];
		$supplier_id = $this->data['supplierId'];

		$params = array(
			'invoice_no' 	=> addslashes($this->data['invoiceNo']),
			'shipping_id' 	=> intval($this->data['shippingId']),
			'shipping_name' => addslashes($this->data['shippingName']),
		);
		$delivery = $this->delivery->getDeliveryById($delivery_id);
		if (!isset($delivery['data']) || empty($delivery['data']))
		{
			$this->error('找不到相应的发货单', -1);
		}
		$delivery = $delivery['data'];

		if ($wms_type == 3)
		{
			$action_note = "修改国内段物流，";
			$result = $this->delivery->updateWmsStatus($delivery_id, $params);
		}
		else
		{
			$action_note = $wms_type == 2 ? "修改国际段物流，" : "";
			$result = $this->delivery->updateStatus($delivery_id, $params);
		}
		if ($result)
		{
			// 操作日志
			$action_log['order_id'] = $delivery['order_id'];
			$action_log['action_user'] = $this->data['actionUser'];
			$action_log['action_place'] = 1;
			$action_log['relation_field'] = $delivery_id;
			$action_log['action_note'] = "【商家中心修改物流信息】{$action_note}发货单号：{$delivery['delivery_sn']}，{$params['shipping_name']}：{$params['invoice_no']}";
			$this->order->addOrderAction($action_log);
			$this->success(array(),0,'修改快递信息成功');
		}
		else
		{
			$this->error('更新失败', -1);
		}
	}


	/**
	 * 海外直邮订单国内段发货
	 *
	 * @return array
	 * @create 2015-11-02 10:20:13
	 * @author lwp
	 * @wiki http://wiki.corp.mama.cn/pages/viewpage.action?pageId=65078679
	 */
	public function shipWms()
	{
		$require_fields = array('orderId','supplierId','shippingId','invoiceNo','actionUser','deliveryId');
		foreach($require_fields as $v)
		{
			if(!isset($this->data[$v]) || empty($this->data[$v]))
			{
				$this->error("缺失必选参数 ({$v})，请参考API文档", '-1');
			}else{
				$params[$v] = addslashes(trim($this->data[$v]));
			}
		}

		$input = array(
			'order_id'       	=> intval($params['orderId']),
			'supplier_id' 	    => intval($params['supplierId']),
			'shipping_id' 	    => intval($params['shippingId']),
			'invoice_no' 	    => addslashes(trim($params['invoiceNo'])),
			'action_user' 	    => addslashes(trim($params['actionUser'])),
			'delivery_id' 	    => intval($params['deliveryId']),
		);

		$result = $this->delivery->shipWms($input);

		$this->success($result['data']);

	}

	/**
	 * 获取待发货订单列表
	 *
	 * @return array
	 * @create 2015-11-02 10:20:13
	 * @author lwp
	 * @wiki http://wiki.corp.mama.cn/pages/viewpage.action?pageId=65078679
	 */
	public function unshipOrderList()
	{
		$require_fields = array('supplierId','pageNo','pageSize');
		foreach($require_fields as $v)
		{
			if(!isset($this->data[$v]) || empty($this->data[$v]))
			{
				$this->error("缺失必选参数 ({$v})，请参考API文档", '-1');
			}else{
				//$params[$v] = addslashes(trim($this->data[$v]));
			}
		}



		$input['supplier_id']        = !empty($this->data['supplierId']) ? addslashes(trim($this->data['supplierId'])) : '';
		$input['order_sn']           = !empty($this->data['orderSn']) ? addslashes(trim($this->data['orderSn'])) : '';
		$input['mobile']             = !empty($this->data['mobile']) ? addslashes(trim($this->data['mobile'])) : '';
		$input['payTimeStart']       = !empty($this->data['payTimeStart']) ? addslashes(trim($this->data['payTimeStart'])) : '';
		$input['payTimeEnd']         = !empty($this->data['payTimeEnd']) ? addslashes(trim($this->data['payTimeEnd'])) : '';
		$input['keywords']           = !empty($this->data['keyWords']) ? addslashes(trim($this->data['keyWords'])) : '';
		$input['product_sn']         = !empty($this->data['productSn']) ? addslashes(trim($this->data['productSn'])) : '';
		$input['pageSize']           = !empty($this->data['pageSize']) ? addslashes(trim($this->data['pageSize'])) : '';
		$input['pageNo']             = !empty($this->data['pageNo']) ? addslashes(trim($this->data['pageNo'])) : '';
		$input['shipping_part']      = !empty($this->data['shippingPart']) ? addslashes(trim($this->data['shippingPart'])) : '';


		$page = $this->getPageParam($input);



		$where = '  ((d.status IS NOT NULL AND o.order_status IN (1,5,6) '.
			' AND o.shipping_status=8 AND o.pay_status=2 AND d.status=2)'.
			' OR (o.order_status IN (1,5,6) AND o.shipping_status IN (0,4) AND o.pay_status=2)'.
			' OR ((o.order_status IN (1,5,6) AND o.shipping_status IN(1,8) AND o.pay_status=2) '.
			' AND d.completed IS NOT NULL AND d.wms_type=2 AND d.completed=0))';

		$fields = 'distinct o.order_id,o.order_sn, o.pay_id, o.order_status,o.shipping_status,o.pay_status,'.
				' o.consignee,o.address_type,o.province,o.city,o.district,o.address,o.mobile,o.pay_name,'.
				' o.pay_time,o.supplier_id, o.tel, o.goods_amount,o.bonus FROM mall_order_info o';

		if($input['shipping_part'] && $input['shipping_part'] == 1){ //国际未发货
			$where .= 'AND ( o.shipping_status = 0 OR (d.wms_type=2 AND d.status=2) )';
		}else if($input['shipping_part'] && $input['shipping_part']==2){//国内未发货
			$where .= 'AND (d.wms_type=2 AND d.status=0 AND d.completed=0)';
		}

		$join_goods = false;
		if($input['supplier_id']){
			$where .= ' AND o.supplier_id='.$input['supplier_id'];
		}
		if($input['order_sn']){
			$where .= ' AND o.order_sn=\''.$input['order_sn'].'\'';
		}
		if($input['mobile']){
			$where .= ' AND o.mobile=\''.$input['mobile'].'\'';
		}
		if($input['payTimeStart']){
			$where .= ' AND o.pay_time > '.$input['payTimeStart'];
		}
		if($input['payTimeEnd']){
			$where .= ' AND o.pay_time < '.$input['payTimeEnd'];
		}
		if($input['keywords']){
			$where .= ' AND og.goods_name LIKE \'%'.mysql_like_quote($input['keywords']).'%\' ';
			$join_goods = true;
		}
		if($input['product_sn']){
			$where .= ' AND og.product_sn=\''.$input['product_sn'].'\'';
			$join_goods = true;
		}

		$count  = $this->delivery->countOrderJoinDnOG('unship',$where,$join_goods)['data'];
		if (!isset($count) || empty($count))
		{
			// 无数据
			$data['orders'] = array();
			$data['page'] = array('page'=>$page['current_page'], 'limit'=>$page['page_size'], 'count'=>$count);
			$this->success($data);
		}
		$result = $this->delivery->unshipOrderList('unship',$where, " {$page['start']}, {$page['page_size']}", '  o.pay_Time desc ',$fields,$join_goods)['data'];

		foreach($result as $key=>$val){
			$result[$key]['goodsList'] = cls_order::getInstance()->getOrderGoodsById($val['order_id'])['data'];
			$result[$key]['composite_status'] = cls_order::getInstance()->get_composite_status($val['order_status'],$val['shipping_status'],$val['pay_status']);
			$result[$key]['goods_amount'] = bcsub($val['goods_amount'], $val['bonus'], 2);
		}


		$map_result = $this->mapFields($result);
		$data['orders'] = $map_result;
		$data['page'] = array('page'=>$page['current_page'], 'limit'=>$page['page_size'], 'count'=>$count);
		$this->success($data);

	}

	public function batchShip()
	{
		//dump($this->data);
		if (!isset($this->data['actionUser']) || !($action_user = $this->data['actionUser']))
		{
			$this->error('参数 actionUser 不能为空', -1);
		}

		if (!isset($this->data['batchId']) || !($batch_id = $this->data['batchId']))
		{
			$this->error('参数 batchId 不能为空', -1);
		}

		if (!isset($this->data['fileContent']) || !($file_content = $this->data['fileContent']))
		{
			$this->error('参数 fileContent 不能为空', -1);
		}

		if (!isset($this->data['supplierId']) || !($supplier_id = $this->data['supplierId']))
		{
			$this->error('参数 supplierId 不能为空', -1);
		}

		$progress = array('total'=>count($file_content), 'success'=>0, 'failed'=>0);
		if ($progress['total'] < 1)
		{
			$this->error('无订单发货信息', -1);
		}
		
		$redis = Mama_Cache::factory('redis');
		$batch_key = "api_batch_ship_progress_{$batch_id}";
		if ($now_progress = $redis->get($batch_key))
		{
			//dump(json_decode($now_progress, true));die;
			$now_progress = json_decode($now_progress, true);
			if ($now_progress['success'] + $now_progress['failed'] == $now_progress['total'])
			{
				$this->error("该批订单已导入完成", -1);
			}
			else
			{
				$this->error("该批订单正在处理中", -1);
			}
		}

		$storage_list = storage_list();
		//dump($storage_list);die;

		$shipping_list = cls_shipping::getInstance()->getShppingCompany();
		//dump($shipping_list);die;

		$import_results = array();
		
		foreach ($file_content as $row)
		{
			$result_row = $this->batchShipRow($row, $shipping_list, $storage_list, $supplier_id, $action_user);
			if (isset($result_row['code']) && $result_row['code'] > 0)
			{
				$progress['success']++;
			}
			else
			{
				$progress['failed']++;
			}
			unset($result_row['code']);
			$import_results[] = $result_row;
			$redis->set($batch_key, json_encode($progress), 1800);
		
		}
		// foreach end
		
		$this->success($import_results);
	}

	private function batchShipRow(&$row, &$shipping_list, &$storage_list, $supplier_id, $action_user)
	{
		$tmp = array();
		list($tmp['order_sn'],
			$tmp['delivery_sn'],
			$tmp['invoice_no_in'],
			$tmp['shipping_name_in'],
			$tmp['invoice_no_out'],
			$tmp['shipping_name_out'],
			$tmp['is_virtual']) = explode("|", $row);
		$tmp['invoice_no_in'] = trim($tmp['invoice_no_in']);
		$tmp['invoice_no_out'] = trim($tmp['invoice_no_out']);

		$result_row = array(
				"deliverySn" =>"",
				"importStatus" =>"导入失败",
				"importTime" =>date('Y-m-d H:i:s'),
				"orderSn" =>"",
				"shippingStatus" =>"--",
				"remark" =>"",
				"code"=>-1
				);

		// 跳过订单号为空的
		if (empty($tmp['order_sn']))
		{
			$result_row['remark'] = "订单号不能为空";
			return $result_row;
		}
		$result_row['orderSn'] = $tmp['order_sn'];
		$result_row['deliverySn'] = $tmp['delivery_sn'];

		$fields = "I.*, G.goods_own_type, G.delivery_method, G.rec_id, G.goods_number";
		$order = $this->order->getOrdersByOrderGoods("I.order_sn=".addslashes($tmp['order_sn']), false, false, $fields)['data'];
		//dump($this->order->getLastSql());
		//dump($order);die;
		if (empty($order))
		{
			return array_merge($result_row, array(
						'remark'=>'找不到相应的订单',
						'orderSn'=>$tmp['order_sn']
						));;
		}

		//$order = $order[0];
		$is_virtual = $order[0]['goods_own_type'] == 4 ? 1 : 0;
		$is_foreign = isset($storage_list[$order[0]['delivery_method']]) && $storage_list[$order[0]['delivery_method']]['storage_cat_id'] == 6 ? 1 : 0;
		$order_id = $order[0]['order_id'];

		// 虚拟商品
		if ($is_virtual)
		{
			if (!$tmp['is_virtual'])
			{
				$result_row['remark'] = '该订单是虚拟商品发货';
				return $result_row;
			}

			$delivery_info = array('order_id'=>$order_id, 'action_user'=>$action_user, 'action_note'=>'');
			$send_goods = array();
			foreach ($order as $v)
			{
				$send_goods[$v['rec_id']] = $v['goods_number'];
			}
			$result_virtual = $this->delivery->deliveryShipVirtual($delivery_info, $send_goods);
			if ($result_virtual['code'] !== 0)
			{
				return array_merge($result_row, array(
						'remark'=>$result_virtual['msg'],
						'orderSn'=>$tmp['order_sn'],
						'deliverySn'=>$tmp['delivery_sn'],
						));;
			}
			else
			{
				return array_merge($result_row, array(
						'remark'=>'OK',
						'orderSn'=>$tmp['order_sn'],
						'deliverySn'=>$result_virtual['delivery_sn'],
						'importStatus'=>'导入成功',
						'shippingStatus'=>'已签收',
						'code' =>1
						));;
			}
		}

		if ((empty($tmp['invoice_no_in']) || empty($tmp['shipping_name_in'])) && (empty($tmp['shipping_name_out']) || empty($tmp['invoice_no_out'])))
		{
			if ($is_foreign)
			{
				$result_row['remark'] = "国际和国内快递信息不能同时为空";
				if ($tmp['shipping_name_out'] && empty($tmp['invoice_no_out']))
				{
					$result_row['remark'] = "国际快递单号不能为空";
				}
				elseif ($tmp['invoice_no_out'] && empty($tmp['shipping_name_out']))
				{
					$result_row['remark'] = "国际快递公司不能为空";
				}
				elseif ($tmp['invoice_no_in'] && empty($tmp['shipping_name_in']))
				{
					$result_row['remark'] = "国内快递公司不能为空";
				}
				elseif ($tmp['shipping_name_in'] && empty($tmp['invoice_no_in']))
				{
					$result_row['remark'] = "国内快递单号不能为空";
				}

			}
			else
			{
				$result_row['remark'] = "国内快递信息不能同时为空";
				if ($tmp['invoice_no_in'] && empty($tmp['shipping_name_in']))
				{
					$result_row['remark'] = "国内快递公司不能为空";
				}
				elseif ($tmp['shipping_name_in'] && empty($tmp['invoice_no_in']))
				{
					$result_row['remark'] = "国内快递单号不能为空";
				}
			}

			return $result_row;
		}
		
		// 发货单
		if (empty($tmp['delivery_sn']))
		{
			// 如果订单没有分成多个包裹发货，不需要输入【系统发货单号】，否则必须输入【系统发货单号】才能成功发货
			$delivery = $this->delivery->getOrderDelivery($order_id,'delivery_id,delivery_sn,status')['data'];
			if (is_array($delivery) && count($delivery) > 1)
			{
				return array_merge($result_row, array(
							'remark'=>'该订单有多个发货单，不能进行批量发货',
							'orderSn'=>$tmp['order_sn']
							));;
			}
			$delivery_id = isset($delivery[0]['delivery_id']) ? $delivery[0]['delivery_id'] : 0;
			$delivery_sn = isset($delivery[0]['delivery_sn']) ? $delivery[0]['delivery_sn'] : "";
		}
		else
		{
			$delivery = $this->delivery->getDeliveryBySn(addslashes($tmp['delivery_sn']))['data'];
			if (empty($delivery))
			{
				return array_merge($result_row, array(
							'remark'=>'发货单号不存在',
							'orderSn'=>$tmp['order_sn'],
							'deliverySn'=>$tmp['delivery_sn'],
							));;
			}
			$delivery_id = $delivery['delivery_id'];
			$delivery_sn = $delivery['delivery_sn'];
			$delivery = array(0=>$delivery);
		}

		if (!$delivery_id)
		{
			// 无发货单，先创建发货单
			$delivery_info = array('order_id'=>$order_id, 'action_user'=>$action_user, 'action_note'=>false);
			$send_goods = array();
			foreach ($order as $v)
			{
				$send_goods[$v['rec_id']] = $v['goods_number'];
			}
			$result_create = $this->delivery->create($delivery_info, $send_goods);
			if (isset($result_create['data']['delivery_id']) && $result_create['data']['delivery_id'])
			{
				$delivery_id = $result_create['data']['delivery_id'];
			}
			else
			{
				 return array_merge($result_row, array(
						'remark'=>'创建发货单失败。'.$result_create['msg'],
						'orderSn'=>$tmp['order_sn'],
						'deliverySn'=>$delivery_sn
						));;
			}
		}

		if ($is_foreign)
		{
			// 海外直邮
			if (!empty($tmp['shipping_name_out']) && !empty($tmp['invoice_no_out']))
			{
				$shipping_id = 0;
				foreach ($shipping_list as $v)
				{
					if ($v['shipping_name'] == $tmp['shipping_name_out'])
					{
						$shipping_id = $v['shipping_id'];
					}
				}
				//dump($shipping_id);die;
				if (!$shipping_id)
				{
					if (isset($result_create))
					{
						$this->delivery->deleteUnshipDelivery(array($delivery_id));
					}
					return array_merge($result_row, array(
							'remark'=>'不支持的快递公司：'.$tmp['shipping_name_out'],
							'orderSn'=>$tmp['order_sn'],
							'deliverySn' => $delivery_sn,
							));;
				}

				$input = array(
					'order_id'   	=> $order_id,
					'supplier_id'   => $supplier_id,
					'shipping_id' 	=> $shipping_id,
					'invoice_no' 	=> $tmp['invoice_no_out'],
					'action_user' 	=> $action_user,
					'delivery_id' 	=> $delivery_id,
					'type' 	        => 2   // 物流段：1、国内发货（普通订单） 2、国际段发货（海外直邮）
				);

				$result_ship = $this->delivery->deliveryShip($input);
				if($result_ship['code'] < 0)
				{
					if (isset($result_create))
					{
						$this->delivery->deleteUnshipDelivery(array($delivery_id));
					}
					$out_err = array(
						'-1' => $result_ship['msg'],
						'-2' => '国际段不能重复发货',
						'-3' => '国际快递单号格式不正确',
						);
					$result_row['remark'] = $out_err[$result_ship['code']];
					return $result_row; 
				}

			}
			// 海外直邮国际段 end

			if (!empty($tmp['shipping_name_in']) && !empty($tmp['invoice_no_in']))
			{
				if (empty($tmp['invoice_no_in']))
				{
					if (isset($result_create))
					{
						$this->delivery->deleteUnshipDelivery(array($delivery_id));
					}
					$result_row['remark'] = "国内快递单号不能为空"; 
					return $result_row;
				}
				elseif (empty($tmp['shipping_name_in']))
				{
					if (isset($result_create))
					{
						$this->delivery->deleteUnshipDelivery(array($delivery_id));
					}
					$result_row['remark'] = "国内快递公司不能为空";
					return $result_row;
				}

				$shipping_id = 0;
				foreach ($shipping_list as $v)
				{
					if ($v['shipping_name'] == $tmp['shipping_name_in'])
					{
						$shipping_id = $v['shipping_id'];
					}
				}
				//dump($shipping_id);die;
				if (!$shipping_id)
				{
					if (isset($result_create))
					{
						$this->delivery->deleteUnshipDelivery(array($delivery_id));
					}
					return array_merge($result_row, array(
							'remark'=>'不支持的快递公司：'.$tmp['shipping_name_in'],
							'orderSn'=>$tmp['order_sn'],
							'deliverySn' => $delivery_sn,
							));;
				}

				$input = array(
					'order_id'          => $order_id,
					'supplier_id' 	    => $supplier_id,
					'shipping_id' 	    => $shipping_id,
					'invoice_no' 	    => $tmp['invoice_no_in'],
					'action_user' 	    => $action_user,
					'delivery_id' 	    => $delivery_id,
				);

				$result_ship = $this->delivery->shipWms($input);
				if($result_ship['code'] < 0)
				{
					if (isset($result_create))
					{
						$this->delivery->deleteUnshipDelivery(array($delivery_id));
					}
					$in_err = array(
						'-1' => $result_ship['msg'],
						'-2' => '国内段不能重复发货',
						'-3' => '国内快递单号格式不正确',
						);
					$result_row['remark'] = $in_err[$result_ship['code']];
					return $result_row; 
				}

			}
			// 国内段发货end

			
			// 取最新的物流状态
			$shipping_status = "--";

			// shippingStatus：如果订单只有一条发货单，则显示订单的发货状态，如果订单有多条发货单，则显示对应发货单的发货状态。
			$fields = "I.order_id, I.shipping_status, I.order_status, I.pay_status, D.delivery_id, D.status as delivery_status";
			$latest_order = $this->delivery->getDeliveryByOrderIds(array($order_id), $fields)['data'];
			if ($latest_order && count($latest_order) > 1)
			{
				foreach ($latest_order as $v)
				{
					if ($v['delivery_id'] == $delivery_id)
					{
						$shipping_status = $this->allStatusName($v)['delivery_status'];
					}
				}
			}
			elseif($latest_order)
			{
				$shipping_status = $this->allStatusName($latest_order[0])['shipping_status'];
			}

			return array_merge($result_row, array(
							'remark'=>'OK',
							'orderSn'=>$tmp['order_sn'],
							'deliverySn'=>$delivery_sn,
							'importStatus'=>'导入成功',
							'shippingStatus'=>$shipping_status,
							'code'=>1
							));;

			// 海外直邮end
		}
		else
		{
			// 国内发货
			if (empty($tmp['shipping_name_in']) || empty($tmp['invoice_no_in']))
			{
				if (isset($result_create))
				{
					$this->delivery->deleteUnshipDelivery(array($delivery_id));
				}
				return array_merge($result_row, array(
						'remark'=>'国内快递公司和快递单号不能为空',
						'orderSn'=>$tmp['order_sn'],
						'deliverySn' => $delivery_sn,
						));;
			}
			$shipping_id = 0;
			foreach ($shipping_list as $v)
			{
				if ($v['shipping_name'] == $tmp['shipping_name_in'])
				{
					$shipping_id = $v['shipping_id'];
				}
			}
			//dump($shipping_id);die;
			if (!$shipping_id)
			{
				if (isset($result_create))
				{
					$this->delivery->deleteUnshipDelivery(array($delivery_id));
				}
				return array_merge($result_row, array(
						'remark'=>'不支持的快递公司：'.$tmp['shipping_name_in'],
						'orderSn'=>$tmp['order_sn'],
						'deliverySn' => $delivery_sn,
						));;
			}

			// 发货
			$input = array(
				'order_id'   	=> $order_id,
				'supplier_id'   => $supplier_id,
				'shipping_id' 	=> $shipping_id,
				'invoice_no' 	=> $tmp['invoice_no_in'],
				'action_user' 	=> $action_user,
				'delivery_id' 	=> $delivery_id,
				'type' 	        => 1  // 物流段：1、国内发货（普通订单） 2、国际段发货（海外直邮）
			);

			$result_ship = $this->delivery->deliveryShip($input);
			if($result_ship['code'] > 0)
			{
				// 取最新的物流状态
				$shipping_status = "--";

				// shippingStatus：如果订单只有一条发货单，则显示订单的发货状态，如果订单有多条发货单，则显示对应发货单的发货状态。
				$fields = "I.order_id, I.shipping_status, I.order_status, I.pay_status, D.delivery_id, D.status as delivery_status";
				$latest_order = $this->delivery->getDeliveryByOrderIds(array($order_id), $fields)['data'];
				if ($latest_order && count($latest_order) > 1)
				{
					foreach ($latest_order as $v)
					{
						if ($v['delivery_id'] == $delivery_id)
						{
							$shipping_status = $this->allStatusName($v)['delivery_status'];
						}
					}
				}
				elseif($latest_order)
				{
					$shipping_status = $this->allStatusName($latest_order[0])['shipping_status'];
				}
				
				return array_merge($result_row, array(
						'remark'=>'OK',
						'orderSn'=>$tmp['order_sn'],
						'deliverySn'=>$result_ship['data']['delivery_sn'],
						'importStatus'=>'导入成功',
						'shippingStatus'=>$shipping_status,
						'code' =>1
						));;
			}
			else
			{
				if (isset($result_create))
				{
					$this->delivery->deleteUnshipDelivery(array($delivery_id));
				}
				$in_err = array(
						'-1' => $result_ship['msg'],
						'-2' => '不能重复发货',
						'-3' => '国内快递单号格式不正确',
						);
				$result_row['remark'] = $in_err[$result_ship['code']];
				return $result_row; 
			}
		}
		// 国内发货end

	}
	
	public function batchShipProgress()
	{
		if (!isset($this->data['batchId']) || !($batch_id = $this->data['batchId']))
		{
			$this->error('参数 batchId 不能为空', -1);
		}
		
		$data = array('percent'=>0, 'showNumber'=>'0 / 0');
		$redis = Mama_Cache::factory('redis');
		$batch_key = "api_batch_ship_progress_{$batch_id}";
		if ($progress = $redis->get($batch_key))
		{
			$progress = json_decode($progress, true);
			$finished = $progress['success'] + $progress['failed'];
			$data['percent'] = bcdiv($finished, $progress['total'], 2) * 100;
			$data['showNumber'] = sprintf("%d / %d", $finished, $progress['total']);
		}

		$this->success($data); 
	}

}
