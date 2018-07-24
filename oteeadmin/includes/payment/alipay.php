<?php
//error_reporting(0);
if (! defined ( 'IN_ECS' )) {
	die ( 'Hacking attempt' );
}
/**
 * 类
 */
class alipay {
	
	private $aop;
	
	public function __construct() {
		$this->alipay();
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
	public function alipay() {
		include_once ("alipay/AopSdk.php");
		
		$alipay = json_decode($this->get_php_file(ROOT_PATH."/data/payment/alipay.php"), true);
		//print_r($alipay);
		$aop = new AopClient;
		//$aop->gatewayUrl = "https://openapi.alipaydev.com/gateway.do"; //测试
		$aop->gatewayUrl = "https://openapi.alipay.com/gateway.do";
		//$aop->appId = "2015122001015286";
		$aop->appId = $alipay['appId'];
		//$aop->rsaPrivateKey = 'MIIEpAIBAAKCAQEAudxVwink7ENDdHY6IExFI8oVHa8BDQIZxabuAivX4gChzJkEMaW8DYXxOadc4W9JMkM7GLb4fUC4BhghN99vrqW9v995Fmk0JmfJq2hLFFc7uu3tHYwqyMgBW96oQXtIS6E0L2OY76okSq3A2vFpjukq5jY/z4w46wENk1GoMoQQm1Dom9eBB4Mg1B901XL+qgo2ReiYshBp+0AhPtgl1E+B6GkGXbjjRmK+nwbWEBfutSdctScNLJgtw9k6HZ2t8jtLIN5N/NsGhwwuEE34CiFEVrVU7VwRFopfF4XIYS4ZJXe7lo410TxWcX6CrLBlu7foQAADwjLRjOcz6KcJiwIDAQABAoIBAFDT7oO7HnBVLD+4rryfDq5q0tYO5oEWuciIORe+o2sI5VSJ8xX4QpkK+AYfr7fmYsm2DTxJTjWCcMVNtxyKUhJ8x9FqyUkixWgyWLTvbT9SVIqNsEHaVDzvJhAi+79GcwFZAM3fHYKU1qWXfLh6pnJdGqf1Tbjf/VzOH25C48fbgtkYfoewrBnRNUVIgUrV0thFUwagACKnV8U4Qg9Ld5kG2IQt8LF4CVxuLKBnDGKwekhT9Uc1EDYsGM7Nd/p/WFXV5n3zxxn0RBc1wao+Q+lFLvRYedG6d0WLogLDelgMsNUd3dw9S4vqvSucZ2bw5r6DBsy2fxys5MIEUy+XlxECgYEA7H25hMjz6Oghffj87aYEq62HKO8uruZpsuAPpvroJhuSoSur7qN4k8in5x0fZuGDf7MtTOUmEyS34/YTwEAn3TbgwewJVJ1HFJJRQ6lZFd8p3SFAOdLEotHFyXe0uETxZw5JB0QWwRtCepk1b2hIgDcBn6LrsapqeCWMckvkrJMCgYEAyTFiitl66MTSF35g2ZfmtKhGoGdg/gIo2D7JC7VOKyw8NC5eG5F3OpXjEoPR9bydmsnWFTWUbHqlq8gp/0Sk5rfwNh92A7I8vLSgP7AG5CqB6iwztJa/xQAE+z6ibTt+oCn3gBcMYbh/V3HpLD5SfGfav5NN3KHEJpMYx9jAwikCgYEAmBaGz6l4iwl2OJB/AMqq6EmRybaAfER8J2hHSKjF0XQcCYCPcso4ijoaGNy2zDAtpFtzv6cCpH8RsA4aR6I6wN5BDxWgLoVzJ/ytPGlVfGZngumWZ1lv0hI8M8PDOlpEMXWTa5PTWl8Qks2i+sHDFonJhoN9NmYVUK09NGgwaPcCgYEAlFtMNOLllRr9aMGSHSG7x1+GpOVWSjfBcqPpkC6jc4Zs5nWCg4Wii5rXFGILws7Q0MEgkgcuaUePPrqc7VTq3A6qnN5aIaOe0e/Hcu8URc9qeQkbH4FB67x17+Zh03ZZizUnAlb8lFp93DdtH2jdXkserMGsWpMWlvNFBKi6ivECgYBoW0M3oU00R/3gPFvzl513FKgpVSg6bZrhzyfQjO4D1bKaDy1SjwpDSx6Is4NpVTYmvFbPOqB39piBUxNSrh4s0mIa4pV5LaTd6WXTlobgyU5kaGxJnFu+p87cSlE+wJDYe+80ciryI0T5/wDnBvWqwHhim+ebNCnpA4HJoAZMwg==';
		$aop->rsaPrivateKey = $alipay['rsa_private_key'];
		
		$aop->format = "json";
		$aop->charset = "UTF-8";
		
		//$aop->signType = "RSA2";
		$this->signType = $alipay['sign_type'];
		$aop->signType = $this->signType;
		//$aop->alipayrsaPublicKey ='MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAnNMSOq1MVZZiZjrtT9CMEqAkveTru6I1wWaVYR1bcXxUR7FU2sYCckgLmuwebOQwQhCdv9bwed1C0FiXxDvw5sYCDggvvllnOZoihbIS3ObWqUiNWYIy+JrT2IZDDFbi1lc9luk7vPICRGEtyivrOwgYCKgrSOgYoF1Jx98he5mDnL6fUXJek7pf4fiR2NaQg4ebXgL9orMnjC+oA4FFD0+sYnqx6nc1UXEkmttWfLqf71jQt7Dud1kNfX+OAyHuvHpuMy5c0XvqHccrbszLQgfUVzS7jeWLpaeRlrnUaY/voefG54+YnteTg5CX2aHMj+Tmre70l5zx3+2zYkzbNwIDAQAB';
		$aop->alipayrsaPublicKey = $alipay['ali_public_key'];;
		$this->aop = $aop;
	}
	
	/**
	 * 生成支付代码
	 * 
	 * @param array $order
	 *        	订单信息
	 * @param array $payment
	 *        	支付方式信息
	 */
	public function prepay($order, $payment, $other) {
		$return_url = 'http://' . $_SERVER ['HTTP_HOST'].'/respond.php';	
		switch($payment){
			case 'QUICK_MSECURITY_PAY':
				$c = 'AlipayTradeAppPayRequest';
				$product_code = 'QUICK_MSECURITY_PAY';
				break;
			case 'FAST_INSTANT_TRADE_PAY':
				$c = 'AlipayTradePagePayRequest';
				$product_code = 'FAST_INSTANT_TRADE_PAY';
				break;
			case 'QUICK_WAP_WAY':
				$c = 'AlipayTradeWapPayRequest';
				$product_code = 'QUICK_WAP_WAY';
				break;

			default:
				$this->error('支付方式错误');
				break;
		}
		$other['expire'] = isset($other['expire'])?$other['expire']:'3600';	
		$expire = time() + $other['expire'];
		
		$sql = "SELECT value FROM ".$GLOBALS['ecs']->table('shop_config')." WHERE `id` = 101";
		$shop = $GLOBALS['db']->getRow($sql);	
		$other['app_name'] = isset($other['app_name'])?$other['app_name']:$shop['value'];

		if(!isset($other['product_name'])){
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
		
		$pre = array();
		$pre['body'] = $other['app_name']."-".$other['product_name']."-".$order ['order_sn'];
		$pre['out_trade_no'] = $order['log_id'];
		$pre['subject'] = $other['app_name']."-".$other['product_name']."-".$order ['order_sn'];
		$pre['timeout_express'] = ($other['expire']/60).'m';
		$pre['total_amount'] = $order['order_amount'];
		$pre['product_code'] = $product_code;
		
		$bizcontent = json_encode($pre,true);
		$request = new $c();
		$request->setNotifyUrl($return_url);
		$request->setBizContent($bizcontent);

		$response = $this->aop->sdkExecute($request);
		
		$arr = array();
		//$arr['prepay_id'] = str_replace("alipay_sdk=alipay-sdk-php-20161101&","",$response);
		$arr['prepay_id'] = str_replace("sign_type=".$this->signType."&","",$response)."&sign_type=".$this->signType;
		//$arr['prepay_id'] = $response;
		$arr['time_expire'] = $expire;
		$arr['payment'] = $payment;
		
		if(!strstr($order['order_id'],'charge')){
			// $pay_name = $GLOBALS['db']->getOne('SELECT pay_name FROM ' . $GLOBALS['ecs']->table('payment') ." WHERE pay_id = 1 AND enabled = 1");
			// $GLOBALS['db']->query("update".$GLOBALS['ecs']->table('order_info')." set pay_id = 1, pay_name = '".$pay_name."', pay_code = '".$payment."' where order_id = ".$order['order_id']);

			$GLOBALS['db']->query("update".$GLOBALS['ecs']->table('order_info')." set pay_code = '".$payment."' where order_id = ".$order['order_id']);
		}
		
		return $arr;		
	}
	
	/**
	 * 退款~操作~
	 */
	public function refund($order, $other){
		include_once ("alipay/aop/request/AlipayTradeRefundContentBuilder.php");
		include_once ("alipay/aop/request/AlipayTradeService.php");
		include_once ("alipay/aop/request/AlipayTradeRefundRequest.php");
		include_once ("alipay/aop/AopClient.php");
		$RequestBuilder=new AlipayTradeRefundContentBuilder();
		$RequestBuilder->setOutTradeNo($order['log_id']);
		$RequestBuilder->setRefundAmount($order['refund_money_2']);
		$RequestBuilder->setRefundReason($other['desc']);

		$ats = new AlipayTradeService();
		$ats->signtype = $this->signType;
		$response = $ats->Refund($RequestBuilder);
		if($response->msg =='Success'){
			return $response->out_trade_no;
		}
		return false;

		// $request = new AlipayTradeRefundRequest();
		
		// $pre = array();
		// $pre['out_trade_no'] = $order['order_id'];
		// $pre['refund_amount'] = $order['order_amount'];
		// $pre['refund_reason'] = $other['desc'];
		// $pre['out_request_no'] = $other['refund_id'];
		
		// $bizcontent = json_encode($pre,true);
		
		// $request->setBizContent($bizcontent);
		
		// $response = $this->aop->sdkExecute($request);
		// $arr = json_decode($responce,true);
		// if($arr['alipay_trade_refund_response']['msg']=='Success'){
		// 	return $arr['alipay_trade_refund_response']['out_trade_no'];
		// }
		// return false;
	}
		
	/**
	 * 响应操作
	 */
	public function respond() {

		//include_once ("alipay/AopSdk.php");
        //$aop = new AopClient;
		//$_POST = $_GET;
		if($this->aop->rsaCheckV1($_POST, $this->aop->alipayrsaPublicKey, $this->aop->signType)){
			error_log(json_encode($_REQUEST),3,dirname(__FILE__) . '/data/payment/error2.log');
		}
		//print_r($_POST);
		if(true){
			$out_trade_no = $_POST['out_trade_no'];
			$total_amount = $_POST['total_amount'];
			if(substr($out_trade_no, 0, 6)=="charge"){
				$out_trade_no = str_replace("charge",'',$out_trade_no);
				$sql = "SELECT * FROM `hunuo_user_account` WHERE `id` = ".$out_trade_no." AND `amount` = ".$total_amount." AND `payment` LIKE 'alipay'";
				$res = $GLOBALS['db']->getRow($sql);
				if(!$res['id']){
					return false;
				}
				$this->log_account_change($res['user_id'], $total_amount, 0, 0, 0, '充值', 1);
				$sql = 'UPDATE ' .$GLOBALS['ecs']->table('user_account'). ' SET '.
					   "is_paid    = 1 ".
					   "WHERE id   = '".$out_trade_no."'";
				$GLOBALS['db']->query($sql);	
			}else{
				// $sql = 'SELECT log_id FROM ' . $GLOBALS['ecs']->table('pay_log') ." WHERE order_id = '$out_trade_no'";
				// $out_trade_no = $GLOBALS['db']->getOne($sql);
				// echo 2222;
				if (! check_money ( $out_trade_no, $total_amount)) {
					$this->addLog ( $_POST, 404 );
					return false;
				}
				order_paid ($out_trade_no, 2);
			}
			return true;
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