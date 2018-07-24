<?php
// error_reporting(E_ALL); //E_ALL  
   
// function cache_shutdown_error() {  
   
    // $_error = error_get_last();  
   
    // if ($_error && in_array($_error['type'], array(1, 4, 16, 64, 256, 4096, E_ALL))) {  
   
        // echo '<font color=red>你的代码出错了：</font></br>';  
        // echo '致命错误:' . $_error['message'] . '</br>';  
        // echo '文件:' . $_error['file'] . '</br>';  
        // echo '在第' . $_error['line'] . '行</br>';  
    // }  
// }  
   
// register_shutdown_function("cache_shutdown_error"); 
if (! defined ( 'IN_ECS' )) {
	die ( 'Hacking attempt' );
}
/**
 * 类
 */
class weixin {

	public function __construct() {
		$this->weixin ();
	}
	
	/**
	 * 构造函数
	 *
	 * @access public
	 * @param        	
	 *
	 *
	 * @return void
	 */
	public function weixin() {
		//return;
	}

	
	/**
	 * 生成支付代码
	 * 
	 * @param array $order
	 *        	订单信息
	 * @param array $payment
	 *        	支付方式信息
	 */
	public function prepay($order, $payment,$other) {
		$return_url = 'http://' . $_SERVER ['HTTP_HOST'].'/respond.php';
		
		
		$sql = "SELECT value FROM ".$GLOBALS['ecs']->table('shop_config')." WHERE `id` = 101";
		$shop = $GLOBALS['db']->getRow($sql);		
		$other['app_name'] = isset($other['app_name'])?$other['app_name']:$shop['value'];
		
		if(!isset($other['product_name'])){
			//echo $other['product_name'];die();
			$sql = "SELECT order_id FROM ".$GLOBALS['ecs']->table('order_info')." WHERE (order_id = ".$order['order_id']." or parent_order_id = ".$order['order_id'].")";
			$orderIds = $GLOBALS['db']->getAll($sql);
			$orderIdSting = array();
			foreach($orderIds as $v){
				$orderIdArray[] = $v['order_id'];
			}
			$orderIdSting = implode(",",$orderIdArray);
			$sql = "SELECT goods_name FROM ".$GLOBALS['ecs']->table('order_goods')." WHERE order_id in (".$orderIdSting.")";
			$names = $GLOBALS['db']->getAll($sql);
			foreach($names as $v){
				$nameArray[] = $v['goods_name'];
			}
			$product_name = implode("-",$nameArray);
			
			$other['product_name'] = $product_name;
		}

		include_once ("wxpay/WxPay.Api.php");
		$input = new WxPayUnifiedOrder();
		switch($payment){
			case 'JSAPI':
				$other['openid'] = isset($other['openid'])?$other['openid']:$this->error('openid缺失');
				$input->SetOpenid($other['openid']);
				$input->SetTrade_type("JSAPI");
				$wxpay_conf = json_decode($this->get_php_file(ROOT_PATH."/data/payment/wxpay_pub.php"), true);
				define('SSLCERT_PATHa', ROOT_PATH.'/data/payment/wxpay_pub/apiclient_cert.pem'); 
				define('SSLKEY_PATHa', ROOT_PATH.'/data/payment/wxpay_pub/apiclient_key.pem'); 
				break;
			case 'NATIVE':
				$input->SetProduct_id($other['product_id']);
				$input->SetTrade_type("NATIVE");
				$wxpay_conf = json_decode($this->get_php_file(ROOT_PATH."/data/payment/wxpay_pub.php"), true);
				define('SSLCERT_PATHa', ROOT_PATH.'/data/payment/wxpay_pub/apiclient_cert.pem'); 
				define('SSLKEY_PATHa', ROOT_PATH.'/data/payment/wxpay_pub/apiclient_key.pem'); 
				break;
			case 'MWEB':
				$input->SetScene_info(isset($other['scene_info'])?$other['scene_info']:'{"h5_info": {"type":"Wap","wap_url": "http://'.$_SERVER ['HTTP_HOST'].'","wap_name": "'.$other['app_name'].'"}}');   //'http://' . $_SERVER ['HTTP_HOST']
				$input->SetTrade_type("MWEB");
				$wxpay_conf = json_decode($this->get_php_file(ROOT_PATH."/data/payment/wxpay_pub.php"), true);
				define('SSLCERT_PATHa', ROOT_PATH.'/data/payment/wxpay_pub/apiclient_cert.pem'); 
				define('SSLKEY_PATHa', ROOT_PATH.'/data/payment/wxpay_pub/apiclient_key.pem'); 
				break;
			case 'APP':
				$input->SetTrade_type("APP");
				$wxpay_conf = json_decode($this->get_php_file(ROOT_PATH."/data/payment/wxpay_app.php"), true);
				define('SSLCERT_PATHa', ROOT_PATH.'/data/payment/wxpay_app/apiclient_cert.pem'); 
				define('SSLKEY_PATHa', ROOT_PATH.'/data/payment/wxpay_app/apiclient_key.pem'); 
				break;
			case 'XCX':
				$other['openid'] = isset($other['openid'])?$other['openid']:$this->error('openid缺失');
				$input->SetOpenid($other['openid']);
				$input->SetTrade_type("JSAPI");
				$wxpay_conf = json_decode($this->get_php_file(ROOT_PATH."/data/payment/wxpay_xcx.php"), true);
				define('SSLCERT_PATHa', ROOT_PATH.'/data/payment/wxpay_xcx/apiclient_cert.pem'); 
				define('SSLKEY_PATHa', ROOT_PATH.'/data/payment/wxpay_xcx/apiclient_key.pem'); 
				break;
			default:
				$this->error('支付方式错误');
				break;
		}
		
		
		define('APPIDa', $wxpay_conf['appid']); 
		define('MCHIDa', $wxpay_conf['mch_id']); 
		define('KEYa', $wxpay_conf['nonce_str']); 
		define('APPSECRETa', isset($wxpay_conf['appsecret'])?:''); 
		
		
		$other['expire'] = isset($other['expire'])?$other['expire']:'3600';	
		$expire = time() + $other['expire'];
			
		
		$input->SetBody($other['app_name']."-".$other['product_name']."-".$order ['order_sn']);
		$input->SetAttach($other['app_name']."-".$other['product_name']."-".$order ['order_sn']);
		$input->SetOut_trade_no($order['log_id']);
		$input->SetTotal_fee($order ['order_amount'] * 100);
		//$input->SetTime_start(date("YmdHis"));
		//$input->SetTime_expire(date("YmdHis"));
		//$input->SetGoods_tag("test");
		$input->SetNotify_url($return_url);

		$result = WxPayApi::unifiedOrder($input);
//print_r($wxpay_conf);		
//print_r($result);die();		
		if($result['return_code'] == 'SUCCESS' && $result['result_code'] == 'SUCCESS'){
			$arr = array();			
			$arr['prepay_id'] = $result['prepay_id'];
			if($payment=='JSAPI'){
				$jsapi = new WxPayJsApiPay();
				$jsapi->SetAppid(APPIDa);
				$timeStamp = time();
				$jsapi->SetTimeStamp(time());
				$jsapi->SetNonceStr($this->getNonceStr());
				$jsapi->SetPackage("prepay_id=" . $result['prepay_id']);
				$jsapi->SetSignType("MD5");
				$jsapi->SetPaySign($jsapi->MakeSign());
				$parameters = json_encode($jsapi->GetValues());
				$arr['prepay'] = $parameters;
			}
			if($payment=='APP'){
				//第二签名返回信息给APP
				$inputObj = new WxAppSign();
				$inputObj->appid(APPIDa);
				$inputObj->partnerid(MCHIDa);
				$inputObj->prepayid($result['prepay_id']);
				$inputObj->package('Sign=WXPay');
				$inputObj->noncestr($this->getNonceStr());
				$inputObj->timestamp(time());
				$inputObj->SetSign();
				
				//var_dump($inputObj->GetValues());
				//print_r($inputObj->GetValues());
				$GetValues = $inputObj->GetValues();
				
				$arr['prepay'] = array();
				$arr['prepay']['partnerid'] = $GetValues['partnerid'];
				$arr['prepay']['appid'] = APPIDa;
				$arr['prepay']['prepayid'] = $GetValues['prepayid'];
				$arr['prepay']['package'] = $GetValues['package'];
				$arr['prepay']['noncestr'] = $GetValues['noncestr'];
				$arr['prepay']['timestamp'] = $GetValues['timestamp'];
				$arr['prepay']['sign'] = $GetValues['sign'];
			}
			//$arr['time_expire'] = $expire;
			if($payment=='NATIVE'){
				$arr['url'] = $result['code_url'];
			}
			if($payment=='MWEB'){
				
				$arr['url'] = $result['mweb_url'];
			}
			$arr['payment'] = $payment;
			
			if(!strstr($order['order_id'],'charge')){
				// $pay_name = $GLOBALS['db']->getOne('SELECT pay_name FROM ' . $GLOBALS['ecs']->table('payment') ." WHERE pay_id = 1 AND enabled = 1");
				// $GLOBALS['db']->query("update".$GLOBALS['ecs']->table('order_info')." set pay_id = 1, pay_name = '".$pay_name."', pay_code = '".$payment."' where order_id = ".$order['order_id']);

				$GLOBALS['db']->query("update".$GLOBALS['ecs']->table('order_info')." set pay_code = '".$payment."' where order_id = ".$order['order_id']);
			}
			
			//print_r($arr);
			return $arr;
		}elseif($result['result_code'] == 'FAIL'){
			$arr['error'] = $result['err_code_des'];
			//print_r($arr);
			return $arr ;
		}		
	}
	
	/**
	 * 退款~操作~
	 */
	public function refund($order, $other){
		include_once ("wxpay/WxPay.Api.php");
		$input = new WxPayRefund();
		
		switch($order['pay_code']){
			case 'JSAPI':
				$wxpay_conf = json_decode($this->get_php_file(ROOT_PATH."/data/payment/wxpay_pub.php"), true);
				define('SSLCERT_PATHa', ROOT_PATH.'/data/payment/wxpay_pub/apiclient_cert.pem'); 
				define('SSLKEY_PATHa', ROOT_PATH.'/data/payment/wxpay_pub/apiclient_key.pem'); 
				break;
			case 'NATIVE':
				$wxpay_conf = json_decode($this->get_php_file(ROOT_PATH."/data/payment/wxpay_pub.php"), true);
				define('SSLCERT_PATHa', ROOT_PATH.'/data/payment/wxpay_pub/apiclient_cert.pem'); 
				define('SSLKEY_PATHa', ROOT_PATH.'/data/payment/wxpay_pub/apiclient_key.pem'); 
				break;
			case 'H5':
				$wxpay_conf = json_decode($this->get_php_file(ROOT_PATH."/data/payment/wxpay_pub.php"), true);
				define('SSLCERT_PATHa', ROOT_PATH.'/data/payment/wxpay_pub/apiclient_cert.pem'); 
				define('SSLKEY_PATHa', ROOT_PATH.'/data/payment/wxpay_pub/apiclient_key.pem'); 
				break;
			case 'APP':
				$wxpay_conf = json_decode($this->get_php_file(ROOT_PATH."/data/payment/wxpay_app.php"), true);
				define('SSLCERT_PATHa', ROOT_PATH.'/data/payment/wxpay_app/apiclient_cert.pem'); 
				define('SSLKEY_PATHa', ROOT_PATH.'/data/payment/wxpay_app/apiclient_key.pem'); 
				break;
			case 'XCX':
				$wxpay_conf = json_decode($this->get_php_file(ROOT_PATH."/data/payment/wxpay_xcx.php"), true);
				define('SSLCERT_PATHa', ROOT_PATH.'/data/payment/wxpay_xcx/apiclient_cert.pem'); 
				define('SSLKEY_PATHa', ROOT_PATH.'/data/payment/wxpay_xcx/apiclient_key.pem'); 
				break;
			default:
				return false;
				break;
		}
		
		define('APPIDa', $wxpay_conf['appid']); 
		define('MCHIDa', $wxpay_conf['mch_id']); 
		define('KEYa', $wxpay_conf['nonce_str']); 
		define('APPSECRETa', isset($wxpay_conf['appsecret'])?:''); 

		$input->SetOut_trade_no($order['log_id']);
		$input->SetOut_refund_no($order['log_id']);
		// $input->SetOut_trade_no($order['order_id']);
		// $input->SetOut_refund_no($other['refund_id']);
		$input->SetTotal_fee($order['money_paid'] * 100);
		$input->SetRefund_fee($order['refund_money_2'] * 100);
		// echo json_encode($order['refund_money_2']);die();
		$input->SetOp_user_id(MCHIDa);
		
		$input->SetRefund_account($other['source']==1?'REFUND_SOURCE_RECHARGE_FUNDS':'REFUND_SOURCE_RECHARGE_FUNDS');
		$input->SetRefund_desc($other['desc']);

		$result = WxPayApi::refund($input);
		// echo json_encode($result);die();
		if($result['return_code'] == 'SUCCESS' && $result['result_code'] == 'SUCCESS'){
			return $result['refund_id'];
		}
		return false;
	}
	
	/**
	 * 响应操作
	 */
	public function respond() {
		// 写死了APP,后期pc/wap需要调整
		$wxpay_conf = json_decode($this->get_php_file(ROOT_PATH."/data/payment/wxpay_app.php"), true);
		define('SSLCERT_PATHa', ROOT_PATH.'/data/payment/wxpay_app/apiclient_cert.pem'); 
		define('SSLKEY_PATHa', ROOT_PATH.'/data/payment/wxpay_app/apiclient_key.pem'); 
		define('APPIDa', $wxpay_conf['appid']); 
		define('MCHIDa', $wxpay_conf['mch_id']); 
		define('KEYa', $wxpay_conf['nonce_str']); 
		define('APPSECRETa', isset($wxpay_conf['appsecret'])?:''); 
		include_once ("wxpay/WxPay.Api.php");
		//$xml = $GLOBALS ['HTTP_RAW_POST_DATA'];
		$xml = file_get_contents('php://input');
		try {
			$result = WxPayResults::Init($xml);
			if ($result["return_code"] == "FAIL") {
				$this->addLog ( $result, 401 );
			} elseif ($result["result_code"] == "FAIL") {
				$this->addLog ( $result, 402 );
			} else {
				

				$this->addLog ( $result, 200 );		
				$out_trade_no = $result['out_trade_no'];
				//$order_sns = explode('-',$out_trade_no);
				//$order_sn = $order_sns[0];
				
				if(substr($out_trade_no, 0, 6)=="charge"){
					$out_trade_no = str_replace("charge",'',$out_trade_no);
					$sql = "SELECT * FROM `hunuo_user_account` WHERE `id` = ".$out_trade_no." AND `amount` = ".($result['total_fee']/100)." AND `payment` LIKE 'weixin'";
					$res = $GLOBALS['db']->getRow($sql);
					if(!$res['id']){
						return false;
					}
					$this->log_account_change($res['user_id'], $result['total_fee']/100, 0, 0, 0, '充值', 1);
					$sql = 'UPDATE ' .$GLOBALS['ecs']->table('user_account'). ' SET '.
						   "is_paid    = 1 ".
						   "WHERE id   = '".$out_trade_no."'";
					$GLOBALS['db']->query($sql);
				}else{
					// $sql = 'SELECT log_id FROM ' . $GLOBALS['ecs']->table('pay_log') ." WHERE order_id = '$out_trade_no'";
					// $out_trade_no = $GLOBALS['db']->getOne($sql);
					if (! check_money ( $out_trade_no, $result['total_fee']/100 )) {
						$this->addLog ( $result, 404 );
						return false;
					}
					order_paid ($out_trade_no, 2);
				}				
				return true;
				//echo 'success';exit;
			}
		} catch (WxPayException $e){
			$msg = $e->errorMessage();
			$this->addLog ($msg, 403 );
			return false;
		}
		return false;
	}
	public function addLog($other = array(), $type = 1) {
		$log ['ip'] = $_SERVER['REMOTE_ADDR'];
		$log ['time'] = date('Y-m-d H:i:s');
		$log ['get'] = $_REQUEST;
		$log ['other'] = $other;
		$log = serialize ( $log );
		return $GLOBALS['db']->query( "INSERT INTO " . $GLOBALS['ecs']->table('weixin_paylog') . " (`log`,`type`) VALUES ('$log','$type')" );
	}

	public function get_php_file($filename) {
		return trim(substr(file_get_contents($filename), 15));
	}

	public static function getNonceStr($length = 32) 
	{
		$chars = "abcdefghijklmnopqrstuvwxyz0123456789";  
		$str ="";
		for ( $i = 0; $i < $length; $i++ )  {  
			$str .= substr($chars, mt_rand(0, strlen($chars)-1), 1);  
		} 
		return $str;
	}
	
	public function log_account_change($user_id, $user_money = 0, $frozen_money = 0, $rank_points = 0, $pay_points = 0, $change_desc = '', $change_type = ACT_OTHER)
	{
		/* 插入帐户变动记录 */
		$account_log = array(
			'user_id'       => $user_id,
			'user_money'    => $user_money,
			'frozen_money'  => $frozen_money,
			'rank_points'   => $rank_points,
			'pay_points'    => $pay_points,
			'change_time'   => gmtime(),
			'change_desc'   => $change_desc,
			'change_type'   => $change_type
		);
		$GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('account_log'), $account_log, 'INSERT');

		/* 更新用户信息 */
		$sql = "UPDATE " . $GLOBALS['ecs']->table('users') .
				" SET user_money = user_money + ('$user_money')," .
				" frozen_money = frozen_money + ('$frozen_money')," .
				" rank_points = rank_points + ('$rank_points')," .
				" pay_points = pay_points + ('$pay_points')" .
				" WHERE user_id = '$user_id' LIMIT 1";
		$GLOBALS['db']->query($sql);
	}
}
?>