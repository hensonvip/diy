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
	private $signType;
	private $alipayrsaPublicKey;

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
		$aop = new AopClient;
		/*
		$alipay = json_decode($this->get_php_file(ROOT_PATH."/data/payment/alipay.php"), true);
		$aop->gatewayUrl = "https://openapi.alipay.com/gateway.do";
		$aop->appId = $alipay['appId'];
		$aop->rsaPrivateKey = $alipay['rsa_private_key'];
		$aop->format = "json";
		$aop->charset = "UTF-8";
		$this->signType = $aop->signType =  $alipay['sign_type'];
		$this->alipayrsaPublicKey = $aop->alipayrsaPublicKey = $alipay['ali_public_key'];
		*/

		//以下是沙盒测试环境的配置，可用以下帐号密码测试支付。当客户提供支付宝资料过来后，把以上代码的注释去掉，在后台支付配置好支付资料信息。把以下代码注释或去掉即可。
		// 买家账号
		// xdpodw3190@sandbox.com
		// 登录密码
		// 111111
		// 支付密码
		// 111111
		$this->gatewayUrl = $aop->gatewayUrl = "https://openapi.alipaydev.com/gateway.do";
		$this->appId = $aop->appId = "2016082100303041";
		$this->rsaPrivateKey = $aop->rsaPrivateKey = 'MIIEowIBAAKCAQEA85oJly2/5ZBhCgAU0HB0r0TN4lYw8ugG7KlF9h6GQg6JJUJMg8MtayBIYVTtAqoDwmZm2RgSAwbu34WHto1emL/C1zYYcIAXUc13ru8EuBWXa/3oONWQBAd8lUbKxpcPzKW39gCBGMfS7nEaxLwCpeBJLQ/ND1P7u6IZ2CQAOPChYB5gO0iLF4P6iOa9NBmLR5MDS5bSiYJSHN2WyabOJRH/LcFqnGOuQvAzhWd42/2YO0A4+jTqMUuIOVS2RNgKSe6RbOaYcTukXJNmtkTo74E1bBNUbeBrMLJr/tlGlBFq1KL6yWq6hmBLeBEPoi3gsDfRz/x9ncoxcqTl6IDeTwIDAQABAoIBAQCAXAlzrLqyFzrazyIolykU9bda7TnLtPF5INTplDoAcrJXfLDeuSikixU4nExrG/TxKY5GFIXhLHUQOMdDLQjMijb7udh05znic9b9yZp9+XqZf+guknHZfaxq6CuPKyg7GJNvv+JGkXyXAeguBDeM+jr00J+n1QQPYaImnwmy9imJIVK8f7K18dHx4RneeeZ/AVW2sv/yZm6XumEi5qhOLG6NpNXdq+vEvHukEUz9mUHfCc8mm0OFMuGSSzDsV23yFpZ1d6ehqX3w6Qbmj186WUIDGLPbh4bnYPaW/Zzjs4OyPG23otgiTdeny9RjfZMnCV0sQdTU83L9HN7xWCmRAoGBAP6ZV9JdphUQDWemo3rj3E+q6ss9WDMj4lAouv19/Mp1Qy2Add88LJfQ/qS1jGt427H1Ksf0ui3U/5cYje5GUaJ/S1gAJxLgfw4WQlrgVmaaD4d1yzfOvWH9oBEkucnxvphHRoOlWMax0ppfEHtBJEjX2NPH81Ywhwd8AJM+dsC5AoGBAPTxM8+VyFz5pgEVUSzsDTAoxI/MSqCSnOiyGTyofH7utBMCRvHJEa1q3LOk39c98it7L9anPWIZ9dqoRtXQ3zajYXj8MznpTR4gQAMZO+092CCVES4qvUd2siWJYmRRAzdymyTzRbSpTDJMLey99Gx3TmSBwMc9/mRh+KH/qUNHAoGAVEXzwOlIuT4YAdAx2tKjsSc1EtMaZ8sf9UKWKqRSUb2g3+Xenmbvp80BDQofEc/ugKhTYd6K5fLUK3JwQIe8K9qQ2O8r/96Zj9MkYapSTyH9s+v0uWNQYqguHJ6YdNT1LrihCaBok1R3Dqwa64Fzfj0cQ3WzATuM5phQJG0Pp9kCgYBPdlSVmoT2zLKzFURRa37XydICyIbJUub+gpC3Yf2JfE0FAa8cT1uhw9I3oVxQyrLeskcRvw2l3sAooIDiarGPWy+K/V5tAifXhyTdHHmZfH+6CkIZlRn0rigbD1fRLYr6mP6F3ToW4vlqM9aOQA2khovzBTkSKiV2UWZEv9kcjwKBgGWNgsHBfimFJkoPMSqXAcFSYMpbLoOkUXMKjjvIE5gyrgc6cxlx9VES1IaHq/cWEGKT6Lvr3j/DyKjCW6npYmGxaX6OwmQhL+GKrxTYtxKbVfnxTNeWQnZ77or/7AWccWs8X1sGGItFqML97zZ9snBMEPR+7CMP+aGg95s//ATp';
		$this->format = $aop->format = "json";
		$this->charset = $aop->postCharset = "UTF-8";
		$this->signType = $aop->signType = "RSA2";
		$this->alipayrsaPublicKey = $aop->alipayrsaPublicKey ='MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEA5CDEoE8S+bI4Qph2NQpwScOytFBoiSfGZ0BXyC5XsqN61vo3u7LP5jy9qoNBqo7SBtcY7mD8CWcydmi0sMbi44qX5t8g9Ry8bVsSrWJKlr6np8aujlPcpF9UY/m/eKSvBRzLENVTWWmCpFWiebpXgYoad8vsrbwJx6tng0GoVWO84O2RZBDjctxGiJJH22i0TZtgOmpQcoi3BekOlOFCWaiv3QCvtnOFeoLT7bDJ0hPKf+4egMKVlCM++4Kjda4yGwf356iLYTMHxg9H3mvn0dtOXVSZ4K5bCPbJXCed0ikxrHcXukNUGkYOfkbmz10ux9L3wPKPfWnURFDYNO0NNQIDAQAB';

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
		$pre['out_trade_no'] = $order['order_id'];
		$pre['subject'] = $other['app_name']."-".$other['product_name']."-".$order ['order_sn'];
		$pre['timeout_express'] = ($other['expire']/60).'m';
		$pre['total_amount'] = $order['order_amount'];
		$pre['product_code'] = $product_code;

		$bizcontent = json_encode($pre);
		$request = new $c();
		$request->setNotifyUrl($return_url);
		$request->setBizContent($bizcontent);

		$arr = array();

		if($payment == 'QUICK_MSECURITY_PAY'){
			$response = $this->aop->sdkExecute($request);
			$arr['prepay_id'] = str_replace("sign_type=".$this->signType."&","",$response)."&sign_type=".$this->signType;
		}else{
			$response = $this->aop->pageExecute($request);
			//echo $response;
			$arr['prepay_id'] = $response;
		}

		$arr['time_expire'] = $expire;
		$arr['payment'] = $payment;

		if(!strstr($order['order_id'],'charge')){
			$GLOBALS['db']->query("update".$GLOBALS['ecs']->table('order_info')." set pay_code = '".$payment."' where order_id = ".$order['order_id']);
		}

		return $arr;
	}

	/**
	 * 二维码支付
	 */
	public function qrpay($order, $other)
	{
		$sql = "SELECT value FROM ".$GLOBALS['ecs']->table('shop_config')." WHERE `id` = 101";
		$shop = $GLOBALS['db']->getRow($sql);
		$other['app_name'] = isset($other['app_name']) ? $other['app_name'] : $shop['value'];
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

	    // 配置参数
	    $res = array();
	    $res['return_url'] = 'http://' . $_SERVER ['HTTP_HOST'].'/respond.php';        // 回调地址
	    $res['out_trade_no'] = $order['order_id'];        // 商户订单号
	    $res['subject']      = $other['app_name']."-".$other['product_name']."-".$order ['order_sn'];          // 商品名称
	    $res['total_amount'] = $order['order_amount'];          // 商品总价
	    $res['body']         = $other['app_name']."-".$other['product_name']."-".$order ['order_sn'];    // 商品描述
	    // 引入支付核心文件
	    // include_once ("alipay/aop/AopClient.php");
	    include_once ("alipay/aop/signData.php");
	    include_once ("alipay/aop/request/AlipayTradePrecreateRequest.php");

	    file_put_contents('test.txt', var_export($this->aop, true));
	    $data = json_encode(array(
	    	"out_trade_no" => $res["out_trade_no"],
	    	"total_amount" => $res["total_amount"],
	    	"subject" => trim($res["subject"]),
	    	"body" => trim($res["body"])
	    ),JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
	    $request->setBizContent($data);
	    $request->setNotifyUrl($res['return_url']);
	    $result = $this->aop->execute($request);
	    $responseNode = str_replace(".", "_", $request->getApiMethodName()) . "_response";
	    $resultCode = $result->$responseNode->code;
	    file_put_contents('test.txt', var_export($resultCode, true));
	    $arr = array();
	    if(!empty($resultCode) && $resultCode == 10000){
	    	$resp = (array)$result->$responseNode;
	    	require_once(ROOT_PATH . 'includes/phpqrcode.php');
	    	ob_start();
	    	# 这里开始是生成二维码
	    	$url = $resp['qr_code'];
	    	$errorCorrectionLevel = intval(4);//容错级别
	    	$matrixPointSize = intval(8);//生成图片大小
	    	QRcode::png($url, false, $errorCorrectionLevel, $matrixPointSize, 2);
	    	$arr['qrcode'] = base64_encode(ob_get_contents());
	    	//关闭缓冲区
	    	ob_end_clean();
	    }
	    return $arr;
	}

	/**
	 * 退款~操作~
	 */
	public function refund($order, $other){
		$request = new AlipayTradeRefundRequest();

		$pre = array();
		$pre['out_trade_no'] = $order['order_id'];
		$pre['refund_amount'] = $order['order_amount'];
		$pre['refund_reason'] = $other['desc'];
		$pre['out_request_no'] = $other['refund_id'];

		$bizcontent = json_encode($pre,true);

		$request->setBizContent($bizcontent);

		$response = $this->aop->sdkExecute($request);
		$arr = json_decode($responce,true);
		if($arr['alipay_trade_refund_response']['msg']=='Success'){
			return $arr['alipay_trade_refund_response']['out_trade_no'];
		}
		return false;
	}

	/**
	 * 响应操作
	 */
	public function respond() {

		include_once ("alipay/AopSdk.php");
        $aop = new AopClient;
        $aop->alipayrsaPublicKey = $this->alipayrsaPublicKey;
        $aop->signType = $this->signType;

		$_POST['fund_bill_list'] = stripslashes($_POST['fund_bill_list']);

		if($aop->rsaCheckV1($_POST, NULL, $this->signType)){
			error_log(print_r($_POST,1),3,'respond.log');
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
				$sql = 'SELECT log_id FROM ' . $GLOBALS['ecs']->table('pay_log') ." WHERE order_id = '$out_trade_no'";
				$out_trade_no = $GLOBALS['db']->getOne($sql);
				//echo 2222;
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