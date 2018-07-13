<?php
/**
 * 支付接口
 * 
 * @version v1.0
 * @create 2017-07-15
 * @author Yip
 */
require_once(ROOT_PATH . 'includes/cls_order.php');
require_once(ROOT_PATH . 'includes/cls_user.php');

class PaymentController extends ApiController
{
	
	public $method ;
	
	public function __construct()
	{

		parent::__construct();
		$this->data = json_decode(stripslashes($this->input('data')),true);
		$this->user     = cls_user::getInstance();
		$config = array(
			'type'=>'file',
			'log_path'=> ROOT_PATH . '/data/logs/api/payment/'
		);
		$this->logger = new Logger($config);
	}

	private function log($msg, $level = 'info')
	{
		$this->logger->writeLog($msg, $level, 'payment');
	}

	/**
	 * 申请支付下发预支付接口
	 * @params integer orderId
	 * @params integer user_id
	 * @params string payment
	 * @return json
	 * @create 2015-10-26 10:28:33
	 * @author lwp
	 */
	public function prePay()
	{
		//$params = json_decode(stripslashes($this->input('data')), true);
		$this->method = 'POST';
		
		//if (empty($params) || !isset($params['orderId']) || !isset($params['user_id']) || !isset($params['payment']) || !isset($params['payment_code']))
		$order_id = intval($this->input('order_id', 0));
		$user_id = intval($this->input('user_id', 0));
		
		

		$payment_name = $this->input('payment','');
		$payment_code = $this->input('payment_code','');
		if(empty($order_id) || empty($user_id)){
			$this->error('参数错误');
		}
		$params['orderId'] = $order_id;
		$params['user_id'] = $user_id;

		if(!isset($payment_name) || !isset($payment_code)){
			$sql = "SELECT 	pay_id FROM ". $GLOBALS['ecs']->table("order_info") ." WHERE `order_id` = ".$params['orderId'];
			$payment = $GLOBALS['db']->getRow($sql);
			$order = "SELECT pay_code,pay_name FROM ". $GLOBALS['ecs']->table("payment") ." WHERE `pay_id` = ".$order['pay_id'];
			$payment = $GLOBALS['db']->getRow($sql);
			
			$params['payment'] = $payment['pay_name'];
			$params['payment_code'] = $payment['pay_code'];
		}else{
			$params['payment'] = $payment_name;
			$params['payment_code'] = $payment_code;
		}

		
		$other = array();
		if($this->input('device')=='wap'){
			$other['openid'] = $params['openid'];
		}
		if($this->input('device')=='xcx'){
			$other['openid'] = $GLOBALS['db']->getOne("select openid FROM " . $GLOBALS['ecs']->table('third_login') ." WHERE user_id = '". $user_id."' and type = 'Wechat'");
		}
		
		$cls_order = cls_order::getInstance();

		// 订单基本信息
		$order = $cls_order->getOrderById($params['orderId'],'*',$params['user_id']);
		$order['log_id'] = $this->insert_pay_log($order['order_sn'], $order['order_amount'], PAY_ORDER);
		if (!isset($order['data']) || empty($order['data']))
		{
			$this->error("查找不到相应的订单");
		}
		try {   
			require_once(ROOT_PATH . 'includes/modules/payment/'.$params['payment'].'.php');
			$new_class = $params['payment'];
			$payment = new $new_class();

			$result = $payment->prepay($order,$params['payment_code'],$other);
			if($result){
				Response::render($result);
			}else{
				$this->error('服务器错误');
			}		
		} catch (Exception $e) {
			$this->error('服务器错误');
		} 
		
	}
	
	
	//重新支付
	public function repay(){
		
		$this->method = 'POST';
		
		$order_id = intval($this->input('order_id', 0));
		$user_id = intval($this->input('user_id', 0));
		
		if(empty($order_id) || empty($user_id)){
			$this->error('参数错误');
		}
		
	
		$order = $GLOBALS['db']->getRow("SELECT order_id,order_sn,user_id,pay_status,shipping_status,pay_id,order_amount from " . $GLOBALS['ecs']->table('order_info') . " WHERE order_id = '$order_id' AND user_id= '$user_id'");

		if(empty($order)){
			$this->error('该订单不存在');
		}	

		/* 检查订单是否未付款和未发货 以及订单金额是否为0 和支付id是否为改变 */
		if($order['pay_status'] != PS_UNPAYED || $order['shipping_status'] != SS_UNSHIPPED || $order['order_amount'] <= 0)
		{
			$this->error('该订单无需支付');
		}

		


		$pay_id = $order['pay_id'];

		
		
		if($pay_id == 0){
			
			$sql = "SELECT * FROM ". $GLOBALS['ecs']->table("users") ." WHERE `user_id` = ".$order['user_id'];
			$user_info = $GLOBALS['db']->getRow($sql);
			
			
			if ($order['order_amount'] > ($user_info['user_money'] + $user_info['credit_line']))
			{
				$return['message'] = '余额不足';
				//return $return;
				$this->error($return['message']);
			}
			else 
			{		
				$order['surplus'] = $order['order_amount'];
				//是否开启余额变动给客户发短信-用户消费
				// if($_CFG['sms_user_money_change'] == 1)
				// {
					// $sql = "SELECT user_money,mobile_phone FROM " . $GLOBALS['ecs']->table('users') . " WHERE user_id = '" . $order['user_id'] . "'";
					// $users = $GLOBALS['db']->getRow($sql); 
					// $content = sprintf($_CFG['sms_use_balance_reduce_tpl'],date("Y-m-d H:i:s",gmtime()),$order['order_amount'],$users['user_money'],$_CFG['sms_sign']);
					// if($users['mobile_phone'])
					// {
						// require_once (ROOT_PATH . 'sms/sms.php');
						// sendSMS($users['mobile_phone'],$content);
					// }
				// }
	            $order['order_amount'] = 0;
				
			}

			//log_account_change($order['user_id'], $order['surplus'] * (-1), 0, 0, 0, sprintf('支付订单', $order['order_sn']));
			
			/* 如果订单金额为0（使用余额或积分或红包支付），修改订单状态为已确认、已付款 */
			if ($order['order_amount'] <= 0)
			{
				$order['order_status'] = 1;
				$order['confirm_time'] = gmtime();
				$order['pay_status']   = 2;
				$order['pay_time']     = gmtime();
				$order['order_amount'] = 0;
			}
			log_account_change($order['user_id'], $order['surplus'] * (-1), 0, 0, 0, sprintf('支付订单', $order['order_sn']));
			
			$GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('order_info'), $order, 'UPDATE', "order_id = ".$order['order_id']);
			
			$resultd['order_id'] = $order_id;				
			$resultd['payment']['payment_name'] = '余额支付';
			$resultd['payment']['pay_id'] = 0;
			$resultd['result'] = 'SUCCESS';
			
			$this->success($resultd);
			exit();
		}

		$sql = "SELECT pay_code,pay_name FROM ". $GLOBALS['ecs']->table("payment") ." WHERE `pay_id` = ".$pay_id;
		$payment = $GLOBALS['db']->getRow($sql);
		
		
		$params = array();
		$params['orderId'] = $order_id;
		$params['user_id'] = $user_id;
		$params['payment'] = $payment['pay_name'];
		$params['payment_code'] = $payment['pay_code'];
		
		$other = array();
		if($this->input('device')=='wap' ){
			$other['openid'] = $this->input('openid');
		}
		if($this->input('device')=='xcx'){
			$other['openid'] = $GLOBALS['db']->getOne("select openid FROM " . $GLOBALS['ecs']->table('third_login') ." WHERE user_id = '".$user_id."' and type = 'Wechat'");
		}
		//print_r($params);
		
		$order['log_id'] = $this->get_pay_log($order['order_id']);

			
		try {   
			require_once(ROOT_PATH . 'includes/modules/payment/'.$params['payment'].'.php');
			$new_class = $params['payment'];
			$paymenta = new $new_class();

			$resulta = $paymenta->prepay($order,$params['payment_code'],$other);
			if($resulta){
				//echo 232;
				$resultb['order_id'] = $params['orderId'];
				$resultb['payment'] = $resulta;					
				$resultb['payment']['payment_name'] = $payment['pay_name'];
				$resultb['payment']['pay_id'] = $pay_id;
			}	
			//print_r($resultb);
			$this->success($resultb);

		} catch (Exception $e) {
			print_r($e);
		} 
	}
	
	
	//列出支付
	public function pay_list(){
		
		$this->method = 'GET';
		
		$device = $this->input('device');
		if($device=='ios' || $device == 'android'){
			$device_where = " pay_code IN ('APP','QUICK_MSECURITY_PAY') ";
		}
		if($device=='wap' ){
			$device_where = " pay_code IN ('QUICK_WAP_WAY','JSAPI','MWEB') ";
		}
		if($device=='pc'){
			$device_where = " pay_code IN ('FAST_INSTANT_TRADE_PAY','NATIVE') ";
		}
		if($device=='xcx'){
			$device_where = " pay_code IN ('XCX') ";
		}
	
		
		$result = $this->get_pay_list($device_where);
		$this->success($result);
	}
	
	
	public function get_pay_list($device_where,$show=1){
		$payment = array();
		$sql = "SELECT pay_id,pay_code,pay_name,pay_desc FROM ". $GLOBALS['ecs']->table("payment") ." WHERE enabled = 1 AND ".(isset($device_where)?$device_where:$this->error('参数缺失'));
		//echo $sql;
		$payment = $GLOBALS['db']->getAll($sql);
		foreach($payment as $k=>$v){
			switch($v['pay_name']){
				case 'weixin':
					$payment[$k]['icon'] = 'data/payment/icon/weixin.png';
					break;
				case 'alipay':
					$payment[$k]['icon'] = 'data/payment/icon/alipay.png';
					break;
				case 'unionpay':
					$payment[$k]['icon'] = 'data/payment/icon/unionpay.png';
					break;
			}
			
		}
		
		if($show==1){
			$payment[] = array('pay_id'=>0,'pay_code'=>'balance','pay_name'=>'balance','pay_desc'=>'余额支付','icon'=>'data/payment/icon/balance.png');
		}
		
		return $payment;
	}
	
	private function insert_pay_log($id, $amount, $type = PAY_SURPLUS, $is_paid = 0)
	{
		$sql = 'INSERT INTO ' .$GLOBALS['ecs']->table('pay_log')." (order_id, order_amount, order_type, is_paid)".
				" VALUES  ('$id', '$amount', '$type', '$is_paid')";
		$GLOBALS['db']->query($sql);

		 return $GLOBALS['db']->insert_id();
	}

	private function get_pay_log($id)
	{

		$sql = "SELECT log_id FROM `hunuo_pay_log` WHERE `order_id` = ".$id." LIMIT 0, 30 ";
		$pay_id = $GLOBALS['db']->getOne($sql);

		return $pay_id;
	}
	
	//更改支付
	public function change_pay(){
		
		$this->method = 'POST';

		$order_id = intval($this->input('order_id', 0));
		$user_id = intval($this->input('user_id', 0));
	    $pay_id = intval($this->input('pay_id', 0));

		if(empty($order_id) || empty($user_id)){
			$this->error('参数错误');
		}

			



		/* 取得订单 */
		$order = $GLOBALS['db']->getRow("SELECT order_id,order_sn,user_id,pay_status,shipping_status,pay_id,order_amount,pay_fee,surplus,shipping_id from " . $GLOBALS['ecs']->table('order_info') . " WHERE order_id = '$order_id' AND user_id= '$user_id'");
		if(empty($order)){
			$this->error('该订单不存在');
		}	

		/* 检查订单是否未付款和未发货 以及订单金额是否为0 和支付id是否为改变 */
		if($order['pay_status'] != PS_UNPAYED || $order['shipping_status'] != SS_UNSHIPPED || $order['order_amount'] <= 0)
		{
			$this->error('该订单无需支付');
		}

		

		$order_amount = $order['order_amount'] - $order['pay_fee'];
		$pay_fee = pay_fee($pay_id, $order_amount);

		$order_amount += $pay_fee;


		$order['order_amount']=$order_amount;





		
		
		
		// $device = $this->input('device');		
		// if($device == 'ios'){
			// if($pay_id == 0){
				// $payment['pay_name'] = '余额支付';
			// }else{
				// $sql = "SELECT pay_code,pay_name FROM ". $GLOBALS['ecs']->table("payment") ." WHERE `pay_id` = ".$pay_id;
				// $payment = $GLOBALS['db']->getRow($sql);			
			// }
			// $sql = "UPDATE " . $GLOBALS['ecs']->table('order_info') . " SET pay_id = '$pay_id' ,pay_name = '$payment[pay_name]'  WHERE order_id = '$order_id' LIMIT 1";
			// $GLOBALS['db']->query($sql);
			// $this->success('更改支付成功');
			// exit();
		// }
		//后面全部是备份。。。。
		

		
       
		if($pay_id == 0){
			$payment['pay_name'] = '余额支付';
			$sql = "UPDATE " . $GLOBALS['ecs']->table('order_info') . " SET pay_id = '$pay_id' ,pay_name = '$payment[pay_name]', pay_fee='$pay_fee', order_amount='$order[order_amount]'  WHERE order_id = '$order_id' LIMIT 1";
			$GLOBALS['db']->query($sql);
			
			$sql = "SELECT user_money,credit_line FROM ". $GLOBALS['ecs']->table("users") ." WHERE `user_id` = ".$user_id;
			$user_info = $GLOBALS['db']->getRow($sql);
			
			//余额支付
			
			
			if ($order['order_amount'] > ($user_info['user_money'] + $user_info['credit_line']))
			{
				$return['message'] = '余额不够';
				$this->error($return['message']);
			}
			else
			{
				$order['surplus'] = $order['order_amount'];
				//是否开启余额变动给客户发短信-用户消费
				// if($_CFG['sms_user_money_change'] == 1)
				// {
					// $sql = "SELECT user_money,mobile_phone FROM " . $GLOBALS['ecs']->table('users') . " WHERE user_id = '" . $order['user_id'] . "'";
					// $users = $GLOBALS['db']->getRow($sql); 
					// $content = sprintf($_CFG['sms_use_balance_reduce_tpl'],date("Y-m-d H:i:s",gmtime()),$order['order_amount'],$users['user_money'],$_CFG['sms_sign']);
					// if($users['mobile_phone'])
					// {
						// require_once (ROOT_PATH . 'sms/sms.php');
						// sendSMS($users['mobile_phone'],$content);
					// }
				// }
	            $order['order_amount'] = 0;
			}

			if ($order['order_amount'] <= 0)
			{
				$order['order_status'] = 1;
				$order['confirm_time'] = gmtime();
				$order['pay_status']   = 2;
				$order['pay_time']     = gmtime();
				$order['order_amount'] = 0;
				$order['pay_id']=0;
				$order['pay_fee']=$pay_fee;
			}
			log_account_change($order['user_id'], $order['surplus'] * (-1), 0, 0, 0, sprintf('支付订单', $order['order_sn']));
			
			$GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('order_info'), $order, 'UPDATE', "order_id = ".$order['order_id']);


			if ($order['order_amount'] <= 0)
			{
				/* 对虚拟商品的支持 */
                $virtual_goods = get_virtual_goods($order_id);
                if (!empty($virtual_goods))
                {
                	include_once(ROOT_PATH. 'sms/sms.php');
                    $msg = '';
                    if (virtual_goods_ship_api($virtual_goods, $msg, $order['order_sn'], true))
                    {
                    	if(isset($virtual_goods['virtual_good'])){
                    		$sql = "SELECT user_money,mobile_phone FROM " . $GLOBALS['ecs']->table('users') . " WHERE user_id = '" . $order['user_id'] . "'";
                    		$users = $GLOBALS['db']->getRow($sql);
                    		foreach($virtual_goods['virtual_good'] as $key=>$val){
                                if($val['supplier_id']){
                                    $supplier_name = $GLOBALS['db']->getOne("select supplier_name from ".$GLOBALS['ecs']->table('supplier')." where supplier_id = $val[supplier_id]");
                                }else{
                                     $supplier_name = '网站自营';
                                }
                                $card = $GLOBALS['db']->getAll("select card_sn from ".$GLOBALS['ecs']->table('virtual_goods_card')." where order_sn='$order[order_sn]'");
                                include_once(ROOT_PATH .  'languages/' .$GLOBALS['_CFG']['lang']. '/user.php');
                                foreach($card as $k=>$v){  
                                    $card_sn .= $v['card_sn'].", ";
                                }   
                                $content = sprintf($_LANG['mobile_virtual_template'], $supplier_name, $val['goods_name'], $card_sn,local_date('Y-m-d',$val['valid_date']));
                                
                                sendSMS($users['mobile_phone'],$content);  
                            }
                    	}
                        
                       
	                }            
                    /* 如果订单没有配送方式，自动完成发货操作 */
                    if (!$order['shipping_id'] || $order['shipping_id'] == -1)
                    {
                            
                        /* 将订单标识为已发货状态，并记录发货记录 */
                        $sql = 'UPDATE ' . $GLOBALS['ecs']->table('order_info') .
                               " SET shipping_status = '" . SS_SHIPPED . "', shipping_time = '" . gmtime() . "'" .
                               " WHERE order_id = '$order_id'";
                        $GLOBALS['db']->query($sql);

                         /* 记录订单操作记录 */
                        order_action($order['order_sn'], OS_CONFIRMED, SS_SHIPPED, 2, '', $GLOBALS['_LANG']['buyer']);
                        $orders = $GLOBALS['db']->getRow("SELECT extension_code,extension_id,goods_amount,order_id from " . $GLOBALS['ecs']->table('order_info') . " WHERE order_id = '$order_id'");
                        $integral = integral_to_give($orders);
                        log_account_change($order['user_id'], 0, 0, intval($integral['rank_points']), intval($integral['custom_points']), sprintf($GLOBALS['_LANG']['order_gift_integral'], $order['order_sn']));
                    }
                }
            }
			
			
			$resultd['order_id'] = $order_id;				
			$resultd['payment']['payment_name'] = '余额支付';
			$resultd['payment']['pay_id'] = 0;
			$resultd['result'] = 'SUCCESS';
			
			$this->success($resultd);
			exit();
		}
		
		$sql = "SELECT pay_code,pay_name FROM ". $GLOBALS['ecs']->table("payment") ." WHERE `pay_id` = ".$pay_id;
		$payment = $GLOBALS['db']->getRow($sql);

		
		
		$sql = "UPDATE " . $GLOBALS['ecs']->table('order_info') . " SET pay_id = '$pay_id' ,pay_name = '$payment[pay_name]',pay_code = '$payment[pay_code]', pay_fee='$pay_fee', order_amount='$order[order_amount]'  WHERE order_id = '$order_id' LIMIT 1";
		$GLOBALS['db']->query($sql);
		
		
		
		$params = array();
		$params['orderId'] = $order_id;
		$params['user_id'] = $user_id;
		$params['payment'] = $payment['pay_name'];
		$params['payment_code'] = $payment['pay_code'];


		
		$other = array();

		
		
		$order['log_id'] = $this->get_pay_log($order['order_id']);

		



		if($this->input('device')=='wap'){
			$other['openid'] = $this->input('openid');
		}
		if($this->input('device')=='xcx'){
			$other['openid'] = $GLOBALS['db']->getOne("select openid FROM " . $GLOBALS['ecs']->table('third_login') ." WHERE user_id = '". $user_id."' and type = 'Wechat'");
		}
		try {   
			require_once(ROOT_PATH . 'includes/modules/payment/'.$params['payment'].'.php');
			$new_class = $params['payment'];
			$paymenta = new $new_class();
			
			//error_log($order['order_id']."-".time()."  -  ",3,ROOT_PATH . '/data/payment/payerror.log'); 
			
			$resulta = $paymenta->prepay($order,$params['payment_code'],$other);
			if($resulta){
				//echo 232;
				$resultb['order_id'] = $params['orderId'];
				$resultb['payment'] = $resulta;					
				$resultb['payment']['payment_name'] = $payment['pay_name'];
				$resultb['payment']['pay_id'] = $pay_id;
			}	
			//print_r($resultb);
			$this->success($resultb);

		} catch (Exception $e) {
			print_r($e);
		} 
	}
	
	public function test ()
	{
		$id = $this->input('id');
		require_once(ROOT_PATH . 'includes/modules/payment/weixin.php');
		$paymenta = new weixin();

		$paymenta->test($id);
	}
	
	public function close ()
	{
		$id = $this->input('id');
		require_once(ROOT_PATH . 'includes/modules/payment/weixin.php');
		$paymenta = new weixin();

		$paymenta->close($id);
	}
	
	public function recharge ()
	{

		$_LANG = $GLOBALS['_LANG'];
		//$smarty = $GLOBALS['smarty'];
		$db = $GLOBALS['db'];
		$ecs = $GLOBALS['ecs'];
		
		
		$user_id = intval($this->input('user_id', 0));
		//必须添加user_id
		if($user_id<=0){
			$this->error('请登录！');
		}
		
		$pay_id = intval($this->input('pay_id', 0));
		
		$user_note = $this->input('user_note','');
		
		$rec_id = $this->input('rec_id',0);
		
		$surplus_type = $this->input('surplus_type',0);
		
		
		
		include_once (ROOT_PATH . 'includes/lib_clips.php');
		include_once (ROOT_PATH . 'includes/lib_order.php');
		//include_once (ROOT_PATH . 'includes/lib_user.php');
		
		$_POST['amount']  = $this->input('amount',0);
		$amount = isset($_POST['amount']) ? floatval($_POST['amount']) : 0;
		if($amount <= 0)
		{
			$this->error('请在“金额”栏输入大于0的数字');
			//show_message($_LANG['amount_gt_zero']);
		}
		
		
		/* 变量初始化 */
		$surplus = array(
			'user_id' => $user_id, 'rec_id' => $rec_id, 'process_type' => $surplus_type, 'payment_id' => $pay_id , 'user_note' => $user_note, 'amount' => $amount
		);
		
		
		/* 退款申请的处理 */
		if($surplus['process_type'] == 1)
		{
			/* 判断是否有足够的余额的进行退款的操作 */
			$sur_amount = get_user_yue($user_id);
			if($amount > $sur_amount)
			{
				$content = $_LANG['surplus_amount_error'];
				$this->error($content);
				//show_message($content, $_LANG['back_page_up'], '', 'info');
			}
			
			// 插入会员账目明细
			$amount = '-' . $amount;
			$surplus['payment'] = '';
			$surplus['rec_id'] = insert_user_account($surplus, $amount);

			//更新银行卡 add by qinglin 2017.09.13
			$bank_card_id = intval($_POST['bank_card_id']);
			if($bank_card_id){
				$db->query("UPDATE " . $ecs->table('user_account') . " SET bank_card_id = '".$bank_card_id."' WHERE id = '".$surplus['rec_id']."'");
			}
			
			/* 如果成功提交 */
			if($surplus['rec_id'] > 0)
			{
				$content = $_LANG['surplus_appl_submit'];
				$this->success($content);
				//show_message($content, $_LANG['back_account_log'], 'user.php?act=account_log', 'info');
			}
			else
			{
				$content = $_LANG['process_false'];
				$this->error($content);
				//show_message($content, $_LANG['back_page_up'], '', 'info');
			}
		}
		/* 如果是会员预付款，跳转到下一步，进行线上支付的操作 */
		else
		{
			
			$sql = "SELECT pay_code,pay_name FROM ". $GLOBALS['ecs']->table("payment") ." WHERE `pay_id` = ".$surplus['payment_id'];
			$payment = $GLOBALS['db']->getRow($sql);
			
			$surplus['payment'] = $payment['pay_name'];
			
			if($surplus['payment_id'] <= 0)
			{
				$this->error($_LANG['select_payment_pls']);
				//show_message($_LANG['select_payment_pls']);
			}
					
			if($surplus['rec_id'] > 0)
			{
				// 更新会员账目明细
				$surplus['rec_id'] = update_user_account($surplus);
			}
			else
			{
				// 插入会员账目明细
				$surplus['rec_id'] = insert_user_account($surplus, $amount);
			}
					
			$params = array();
			$params['orderId'] = 'charge'.$surplus['rec_id'];
			$params['user_id'] = $user_id;
			$params['payment'] = $payment['pay_name'];
			$params['payment_code'] = $payment['pay_code'];
			
			$order = array();
			$order['order_id'] = $params['orderId'];
			$order['order_sn'] = $params['orderId'];
			$order['order_amount'] = $amount;
			
			$other = array();
			$other['product_name'] = '充值订单'.$params['orderId'];
			
			try {   
				require_once(ROOT_PATH . 'includes/modules/payment/'.$params['payment'].'.php');
				$new_class = $params['payment'];
				$paymenta = new $new_class();

				$resulta = $paymenta->prepay($order,$params['payment_code'],$other);
				if($resulta){
					//echo 232;
					$resultb['order_id'] = $params['orderId'];
					$resultb['payment'] = $resulta;					
					$resultb['payment']['payment_name'] = $payment['pay_name'];
				}	
				//print_r($resultb);
				$this->success($resultb);

			} catch (Exception $e) {
				print_r($e);
			} 
		}
	}
}
