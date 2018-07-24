<?php
/**
 * 退货单接口
 * 
 * @version v1.0
 * @create 2015-08-07
 * @author liangwp
 */
require_once(ROOT_PATH . 'includes/cls_order.php');
require_once(ROOT_PATH . 'includes/cls_backorder.php');
require_once(ROOT_PATH . 'includes/cls_delivery.php');
require_once(ROOT_PATH . 'includes/cls_refund.php');


class BackorderController extends ApiController
{
	public function __construct()
	{
		parent::__construct();
		$this->data = json_decode(stripslashes($this->input('data')),true);
		$this->backorder = cls_backorder::getInstance();
		$this->delivery  = cls_delivery::getInstance();
		$this->order     = cls_order::getInstance();
		$config = array(
			'type'=>'file',
			'log_path'=> ROOT_PATH . '/data/logs/api/back_order/'
		);
		$this->logger = new Logger($config);
	}

	private function log($msg, $level = 'info')
	{
		$this->logger->writeLog($msg, $level, 'backorder_');
	}

	/**
	 * 获取退货单物流信息
	 *
	 * @return json
	 * @create 2015-10-26 14:42:00
	 * @author Jam.Cheng
	 */
	public function getWmsHistory()
	{
		$require_fields = array('backSn','nu');
		foreach($require_fields as $v)
		{
			if(!isset($this->data[$v]) || empty($this->data[$v]))
			{
				$this->error("缺失必选参数 ({$v})，请参考API文档", '-1');
			}else{
				$$v = addslashes(trim($this->data[$v]));
			}
		}


		$result = $this->backorder->getWmsBySn($backSn,$nu)['data'];
		$this->log("cls_backorder->getWmsBySn: param:".$this->input('data').";result:".json_encode($result), 'debug');
		if($result){
			$result = $this->mapFields($result);
			$this->success($result);
		}else{
			$this->log("cls_backorder->getWmsBySn: param:".$this->input('data').";result:".json_encode($result), 'error');
			$this->error("系统异常", '-1');
		}
	}


	/**
	 * 查询供应商的待处理的退货单的数目
	 *
	 * @return json
	 * @create 2015-10-26 15:52:00
	 * @author Jam.Cheng
	 */
	public function countProgressing()
	{
		$require_fields = array('supplierId');
		foreach($require_fields as $v)
		{
			if(!isset($this->data[$v]) || empty($this->data[$v]))
			{
				$this->error("缺失必选参数 ({$v})，请参考API文档", '-1');
			}else{
				$$v = intval(addslashes(trim($this->data[$v])));
			}
		}
		$result = $this->backorder->countProgressing($supplierId)['data'];
		$this->log("cls_backorder->getWmsBySn: param:".$this->input('data').";result:".json_encode($result), 'debug');

		$return_data['number'] = (int)$result;
		$this->success($return_data);

	}

	/**
	 * 查询退货单列表
	 *
	 * @return json
	 * @create 2015-10-28
	 * @author Jam.Cheng
	 */
	public function backList(){
		$require_fields = array('supplierId');
		foreach($require_fields as $v)
		{
			if(!isset($this->data[$v]) || empty($this->data[$v]))
			{
				$this->error("缺失必选参数 ({$v})，请参考API文档", '-1');
			}else{
				$$v = addslashes(trim($this->data[$v]));
			}
		}

		/* 载入系统参数 */
		$_CFG = load_config();

		$searchFields['backStatus']    = isset($this->data['backStatus']) ? $this->data['backStatus'] : '';
		$searchFields['supplier_id']   = intval($this->data['supplierId']);
		$searchFields['order_sn']      = isset($this->data['orderSn']) ? addslashes(trim($this->data['orderSn'])) : '';
		$searchFields['back_sn']       = isset($this->data['backSn']) ? addslashes(trim($this->data['backSn'])) : '';
		$searchFields['mobile']        = isset($this->data['orderReceiveMobile']) ? addslashes(trim($this->data['orderReceiveMobile'])) : '';
		$searchFields['start_time']    = isset($this->data['appStartTime']) ? addslashes(trim($this->data['appStartTime'])) : '';
		$searchFields['end_time']      = isset($this->data['appEndTime']) ? addslashes(trim($this->data['appEndTime'])) : '';
		$searchFields['sort_by']       = isset($this->data['sort_by']) ? addslashes(trim($this->data['sort_by'])) : '';
		$searchFields['sort_order']    = isset($this->data['sort_order']) ? addslashes(trim($this->data['sort_order'])) : '';
		$searchFields['page']          = isset($this->data['pageNo']) ? intval($this->data['pageNo']) : 1;
		$searchFields['page_size']     = isset($this->data['pageSize']) ? intval($this->data['pageSize']) : 20;

		// 待处理字段
		$searchFields['waitHandleFlag']   = isset($this->data['waitHandleFlag']) ? intval($this->data['waitHandleFlag']) : 0;

		$result = $this->backorder->get_back_list($searchFields);

		$back_list = $result['reList'];
		foreach($back_list as $key=>$val){
			// 退货单商品
			$back_goods_list  = $this->backorder->getGoodsById($val['back_id'],$val['order_id'])['data'];
			foreach($back_goods_list as $goods_k=>$goods_v){
				if($goods_v['reduce_price'] > 0){
					$back_list[$goods_k]['reducePrice'] = bcmul(bcdiv($goods_v['reduce_price'],$goods_v['goods_number'], 2), $goods_v['back_number'], 2);
				}else{
					$back_list[$goods_k]['reducePrice'] = 0;
				}
//				$back_list[$goods_k]['reducePrice'] = ($goods_v['reduce_price']/$goods_v['goods_number']) * $goods_v['back_number'];
				$back_goods_list[$goods_k]['back_number'] = $goods_v['return_number'] ? $goods_v['return_number'] : $goods_v['back_number'];
			}
			$back_list[$key]['backGoodsVoList'] = $back_goods_list;
			$back_list[$key]['seller_appeal_time'] = (int)$_CFG['seller_appeal_time'];
			$back_list[$key]['wait_confirm_time']  = (int)$_CFG['wait_confirm_time'];

			// 虚拟商品
			if(isset($back_list[$goods_k]['delivery_sn'])){
				$back_list[$key]['withoutShipping']    = $this->delivery->getDeliveryById(0,'without_shipping',"delivery_sn = '".$back_list[$goods_k]['delivery_sn']."'")['data']['without_shipping'];
			}else{
				$back_list[$key]['withoutShipping'] = 0;
			}

			// 退货单商品总额
			$back_list[$key]['backOrderAmount'] = $this->backorder->getTotalBackPrice($back_goods_list,$val['order_id']);
		}
		$result['reList']    = $back_list;
		$result['pageSize']  = $result['filter']['page_size'];
		$result['recordCount'] = (int)$result['record_count'];
		$result['pageCount']  = (int)$result['page_count'];
		$result['pageNo']  = (int)$result['filter']['page'];
		unset($result['filter']);
		unset($result['record_count']);
		unset($result['page_count']);
		$result = $this->mapFields($result);

		$this->log("cls_backorder->get_back_list: param:".$this->input('data').";result:".json_encode($result), 'debug');
		if($result){
			$this->success($result);
		}else{
			$this->log("cls_backorder->countOrderProgressing: param:".$this->input('data').";result:".json_encode($result), 'error');
			$this->error("系统异常", '-1');
		}

	}

	/**
	 * 查询退货单详情
	 *
	 * @return json
	 * @create 2015-10-26 17:49:00
	 * @author Jam.Cheng
	 */
	public function detail()
	{

		global $_LANG;
		/* 载入系统参数 */
		$_CFG = load_config();

		$require_fields = array('supplierId','backOrderId');
		foreach($require_fields as $v)
		{
			if(!isset($this->data[$v]) || empty($this->data[$v]))
			{
				$this->error("缺失必选参数 ({$v})，请参考API文档", '-1');
			}
		}

		$supplierId    = isset($this->data['supplierId']) ? intval($this->data['supplierId']) : '';
		$backOrderId   = isset($this->data['backOrderId']) ? intval($this->data['backOrderId']) : '';


		$back_order = $this->backorder->getBackById($backOrderId,'order_id,order_sn,back_id,back_sn,add_time,status,buyer_shipping_name,buyer_invoice_no,buy_shipping_time as buyer_shipping_time,return_time,suggest_status,insure_fee,shipping_fee,update_time,delivery_sn,supplier_id')['data'];
		if($back_order['supplier_id'] != $supplierId){
			$this->error('退货单不属于该供应商', '-1');
		}
		if($back_order){
			// 退货意见
			$suggest_row      = $this->backorder->getBackSuggest($backOrderId)['data'];
			$back_order['statusName'] = $_LANG['back_status'][$back_order['status']];
			$back_order['wait_delivery_time'] = $_CFG['seller_appeal_time'];
			$back_order['wait_confirm_time']  = $_CFG['wait_confirm_time'];
			$back_order['buyer_suggest_gallery']  = $suggest_row['buyer_suggest_gallery'];
			$back_order['seller_suggest_gallery']  = $suggest_row['seller_suggest_gallery'];
			$back_order['appBackTime']  = $back_order['return_time'];

			$back_order['suggestStatus']   = $_LANG['suggest_status'][$back_order['suggest_status']];

			// 退货地址
			$back_order['backAddress']     = $back_address     = $this->backorder->getBackAddressById($backOrderId)['data'];
			// 退货单商品
			$back_goods_list  = $this->backorder->getGoodsById($backOrderId,$back_order['order_id'])['data'];
			foreach($back_goods_list as $goods_k=>$goods_v){
				if($goods_v['reduce_price'] > 0){
					$reduce_price = bcdiv($goods_v['reduce_price'],$goods_v['goods_number']);
				}else{
					$reduce_price = 0;
				}
				$return_number = $goods_v['return_number'] ? $goods_v['return_number'] : $goods_v['back_number'];
				$back_goods_list[$goods_k]['reducePrice'] = bcmul($reduce_price , $return_number, 2);
				$back_goods_list[$goods_k]['back_number'] = $return_number;
			}
			$back_order['backGoodsVoList'] = $back_goods_list;
				// 退货单商品总额
			$back_order['backOrderAmount'] = $this->backorder->getTotalBackPrice($back_goods_list,$back_order['order_id']);

			// 虚拟商品
			$back_order['withoutShipping'] = $this->delivery->getDeliveryById(0,'without_shipping',"delivery_sn = '".$back_order['delivery_sn']."'")['data']['without_shipping'];

			// 退货单日志
			$back_order['backLogList']     = $this->backorder->getLogByBackId($backOrderId)['data'];


			$back_order['customSugg'] = $suggest_row['buyer_suggest'];
			$back_order['suppSugg']   = $suggest_row['seller_suggest'];

			$this->log("cls_backorder->getBackById: param:".$this->input('data').";result:".json_encode($back_order), 'debug');

			$back_order = $this->mapFields($back_order);

			if(!$back_order['backAddress'])	$back_order['backAddress'] = new stdClass();
			if(!$back_order['customSugg'])	$back_order['customSugg'] = new stdClass();
			if(!$back_order['suppSugg'])	$back_order['suppSugg'] = new stdClass();


			$this->success($back_order);
		}else{
			$this->error('系统异常','-1');
			$this->log("cls_backorder->getBackById: param:".$this->input('data').";result:".json_encode($back_order), 'error');
		}

	}


	/**
	 * 同意退货
	 *
	 * @return json
	 * @create 2015-10-28
	 * @author Jam.Cheng
	 */
	public function agree(){

		$require_fields = array('supplierId','backOrderId','orderId','contacts','address','province','city','adminName');
		foreach($require_fields as $v)
		{
			if(!isset($this->data[$v]) || empty($this->data[$v]))
			{
				$this->error("缺失必选参数 ({$v})，请参考API文档", '-1');
			}
		}

		$mobile      = isset($this->data['mobile']) ? addslashes(trim($this->data['mobile'])) : '';
		$callNo      = isset($this->data['callNo']) ? addslashes(trim($this->data['callNo'])) : '';
		$zipCode     = isset($this->data['zipCode']) ? addslashes(trim($this->data['zipCode'])) : '';
		$supplierId  = isset($this->data['supplierId']) ? intval($this->data['supplierId']) : '';
		$orderId     = isset($this->data['orderId']) ? intval($this->data['orderId']) : '';
		$backOrderId = isset($this->data['backOrderId']) ? intval($this->data['backOrderId']) : '';
		$contacts    = isset($this->data['contacts']) ? addslashes(trim($this->data['contacts'])) : '';
		$address     = isset($this->data['address']) ? addslashes(trim($this->data['address'])) : '';
//		$country     = !empty($this->data['country'])     ? intval($this->data['country']) : 0;
		$country     = 0;
		$province    = isset($this->data['province']) ? intval($this->data['province']) : '';
		$city        = isset($this->data['city']) ? intval($this->data['city']) : '';
		$district    = isset($this->data['district']) ? intval($this->data['district']) : '';
		$admin_name  = isset($this->data['adminName']) ? addslashes(trim($this->data['adminName'])) : '';

		$this->log("backorder:agree: param:".$this->input('data'), 'debug');

		if($mobile && !preg_match('/1[34578]{1}\d{9}$/',$mobile)){
			$this->error('您填写的非手机号码','-1');
		}

		$preg = '/(\(\d{3,4}\)|\d{3,4}-|\s)?\d{8}/';
		if($callNo && !preg_match($preg,$callNo)){
			$this->error('您填写的固话格式错误','-1');
		}

		$preg = '/\d{6}/';
		if($zipCode && !preg_match($preg,$zipCode)){
			$this->error('您填写的邮编格式错误','-1');
		}

		$order = $this->order->getOrderformat($orderId)['data'];
		if (empty($order))
		{
			$this->error('订单不存在', '-1');
		}

		$back_order = $this->backorder->getBackById($backOrderId)['data'];

		if($supplierId != $back_order['supplier_id']){
			$this->error('退货单不属于该供应商', '-1');
		}

		if(empty($back_order))
		{
			$this->error('退货单不存在或已删除', '-1');
		}
		elseif($back_order['status']!=4)
		{
			$this->error('该状态退货单不支持同意退货操作', '-1');
		}


		// 新增配送地址
		$back_address_data['back_id']     = $back_order['back_id'];
		$back_address_data['consignee']   = $contacts;
		$back_address_data['address']     = $address;
		$back_address_data['country']     = $country;
		$back_address_data['province_id'] = $province;
		$back_address_data['city_id']     = $city;
		$back_address_data['district_id'] = $district;
		$back_address_data['zipcode']     = $zipCode;
		$back_address_data['mobile']      = $mobile;

		$post_data['back_order'] = $back_order;
		$post_data['order']      = $order;
		$post_data['rma_address_info'] = $back_address_data;
		$post_data['admin_name'] = $admin_name;
		$post_data['remark'] = '商家同意退货';
		$post_data['admin_id']   = 0;
		$post_data['order_remark']   = '商家同意退货，更改订单状态为退货';
		$result = $this->backorder->return_agree($post_data);

		if($result['code'] < 0){
			$this->error('同意退货失败：更新退货记录失败', '-1');
		}else{
			$this->success('同意退货成功！');
		}
	}


	/**
	 * 提交退货意见接口
	 *
	 * @return json
	 * @create 2015-10-29
	 * @author Jam.Cheng
	 */
	public function addSuggest(){

		$require_fields = array('supplierId','backOrderId','suggestRemark','adminName');
		foreach($require_fields as $v)
		{
			if(!isset($this->data[$v]) || empty(trim($this->data[$v])))
			{
				$this->error("缺失必选参数 ({$v})，请参考API文档", '-1');
			}
		}



		$return_info['supplier_id']    = isset($this->data['supplierId']) ?  intval($this->data['supplierId']) : '';
		$return_info['backOrderId']    = isset($this->data['backOrderId']) ?  intval($this->data['backOrderId']) : '';
		$return_info['suggest_remark'] = isset($this->data['suggestRemark']) ?  addslashes(trim($this->data['suggestRemark'])) : '';

		$remark                        = '商家提交退货意见';
		$admin_name                    = !empty($this->data['adminName'])   ? addslashes(trim($this->data['adminName'])) : '';

		$back_order = $this->backorder->getBackById($return_info['backOrderId'])['data'];

		if(empty($back_order))
		{
			$this->error('退货单不存在或已删除', '120');
		}
		elseif($back_order['status']!=4)
		{
			$this->error('该状态退货单不支持提交意见操作', '120');
		}elseif($back_order['suggest_status']!=1)
		{
			$this->error('该退货状态退货单不支持提交意见操作', '120');
		}
		elseif($return_info['supplier_id'] != $back_order['supplier_id'])
		{
			$this->error('退货单不属于该供应商', '120');
		}

		$this->log("backorder:addSuggest: param:".$this->input('data'), 'debug');

		// 意见相册
		$back_gallery = array();
		$sellerSuggestGallery = isset($this->data['sellerSuggestGallery']) ? $this->data['sellerSuggestGallery'] : array();

		if(isset($sellerSuggestGallery)){

			foreach($sellerSuggestGallery as $key=>$value){
				$back_gallery[$key]['img_original'] = $value['imgOriginal'];
				$back_gallery[$key]['img_thumb']    = $value['imgThumb'];
				$back_gallery[$key]['img_comp']     = $value['imgComp'];
			}
		}

		$return_info['suggest_reason'] = '';
		$return_info['suggest_type']   = 2;

		// 退货单信息
		$suggest_data['suggest_status'] = 2;
		$suggest_data['supplier_suggest_send'] = 1;

		$is_exist = $this->backorder->backSuggestExist($back_order['back_id'],2)['data'];
		if($is_exist){
			$this->error('已经提交过意见', '500');
		}



		$result = $this->backorder->addSuggest($back_order,$return_info,$back_gallery,$suggest_data,$remark,$admin_name);

		if($result['code'] < 0){
			$this->error($result['msg'], '500');
		}else{
			$this->success('提交商家意见成功！');
		}

	}


	/**
	 * 确认收货接口
	 *
	 * @return json
	 * @create 2015-10-29
	 * @author Jam.Cheng
	 */
	public function confirmReceive(){

		$require_fields = array('supplierId','backOrderId','orderId','adminName');
		foreach($require_fields as $v)
		{
			if(!isset($this->data[$v]) || empty(trim($this->data[$v])))
			{
				$this->error("缺失必选参数 ({$v})，请参考API文档", '-1');
			}
		}





		$return_info['supplier_id']    = isset($this->data['supplierId']) ? intval($this->data['supplierId']) : '';
		$return_info['back_id']        = isset($this->data['backOrderId']) ? intval($this->data['backOrderId']) : '';
		$return_info['order_id']       = isset($this->data['orderId']) ? intval($this->data['orderId']) : '';
//		$remark                        = '商家确认收到退货商品';
		$admin_name                    = isset($this->data['adminName']) ? addslashes(trim($this->data['adminName'])) : '';

		$back_order = $this->backorder->getBackById($return_info['back_id'])['data'];
		$order = cls_order::getInstance()->getOrderformat($back_order['order_id'])['data'];

		if(empty($back_order))
		{
			$this->error('退货单不存在或已删除', '-1');
		}
		elseif($back_order['status']!=0)
		{
			// 退货中
			$this->error('该状态退货单不支持确认收货操作', '-1');
		}elseif(!in_array($back_order['suggest_status'],array(4,5,6,7)))
		{
			// 6买家已填快递信息（待商家收货） 7待商家收货：已逾期
			$this->error('该退货状态退货单不支持确认收货操作', '-1');
		}
		elseif($return_info['supplier_id'] != $back_order['supplier_id'])
		{
			$this->error('退货单不属于该供应商', '-1');
		}
		elseif($return_info['order_id'] != $back_order['order_id'])
		{
			$this->error('退货单不属于该订单', '-1');
		}
		elseif(empty($order)){
			$this->error('订单不存在或已删除', '-1');
		}

		$this->log("backorder:confirmReceive: param:".$this->input('data'), 'debug');

		$goods_list = $this->backorder->getGoodsById($return_info['back_id'],$return_info['order_id'])['data'];

		$return_data['return_number'] = array();
		$return_data['return_shipping_fee'] = '0';
		$return_data['goods_discount_total'] = '0';
		$return_data['goods_discount'] = '0';
		foreach($goods_list as $key => $goods){
			$return_data['goods_return_money'][$goods['rec_id']] = ((($goods['goods_price'] * $goods['goods_number']) - $goods['reduce_price'])/$goods['goods_number'])*$goods['back_number'];
			$return_data['return_number'][$goods['rec_id']] = $goods['back_number'];
		}


		// 可退运费
		$remark_note['admin_name'] = $admin_name;
		$remark_note['admin_id']   = 0;
		$remark_note['refund_remark'] = $admin_name.'确认退货，生成退款单';
		$remark_note['remark'] = $admin_name.'确认收货，更改订单状态为退款中';
		$result = cls_backorder::getInstance()->confirm_return($return_info['back_id'],$order,$return_data,$remark_note);

		if($result['code'] < 0){
			$this->log('确认退货收货失败;传参：'.json_encode($return_info).';返回值：'.json_encode($result));
			$this->error('确认退货收货失败', '-1');
		}else{
			$this->success('确认退货收货成功！');
		}
		
	}


}
