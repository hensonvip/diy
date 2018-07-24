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
		$this->gatewayUrl = $aop->gatewayUrl = "https://openapi.alipay.com/gateway.do";
		$this->appId = $aop->appId = "2017021405667066";
		$this->rsaPrivateKey = $aop->rsaPrivateKey = 'MIICeAIBADANBgkqhkiG9w0BAQEFAASCAmIwggJeAgEAAoGBAOGQFt9L2ZNp54YbmQozWnjZaXKiEpgYW4ytJaox9qr93MTTJZ5FwTtoE8RyCoCMVuyQhFhDMfbfTuDSMZVox9gp0g2U27fTfLcr6cIs14B2ixVNP7Ds1/ZQgk/KIHoWxozjTLnFr/uKr9+6cPOZe9Ef1zHsGGqujLG6WhbUA0r9AgMBAAECgYBXTcPsjZkbG4SHPatQbWv5Fn1f5yc3Q0Y7/tBzPX9x02xdqjQHPIx8B96OzG0QMEF8srrmxqtSGECZeAHFCJdAc3lr5IMtk3aFGHiJAtsSjt9Y61Sgl7oh4EGp0uXs0txfHqCk2hPSh/MoWPJmf1rPeMnDg1swcVbFIsk6ArOAgQJBAPIMbQEaYefaX6szJvcW7xeto+O5LPd5LPMeHqLXrBxjk1JT2ti+b8nBMH2ZpT9gFbMd3D15gJz9iZlRnTGEB60CQQDukGg5A9zjQbFaq1QQ1XwjFFjSFfKBYf/66nVfE66K+e9XxYwAF4G+kuw78gQI/mp3/qACpEkSWrZvKeSDLPqRAkEAyPIX7LqCXYGluvOUfb3EcNvvG7c35Zvr/UocFQUIFel0/Pwwy5AOLmj8YMmVrq+OVE2N8ltUswCpo2dmIR2DYQJBAJXpewTxD3pjiq6eYSpe7S4iGBqUnhp92dz0PqNre9BrdOLkCbD5FJgMPZUB5VU9guw6vpRKZny5MlEysx1XQ3ECQQC7j59VKpT4ctBzgqHtQIJyF8oME2RJnM6vSUtKtalLdSDc0tmGqqyOErZNt8+r+k8/zh27e7cLE99hroBkxgzB';
		$this->format = $aop->format = "json";
		$this->charset = $aop->postCharset = "UTF-8";
		$this->signType = $aop->signType = "RSA";
		$this->alipayrsaPublicKey = $aop->alipayrsaPublicKey ='MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQDDI6d306Q8fIfCOaTXyiUeJHkrIvYISRcc73s3vF1ZT7XN8RNPwJxo8pWaJMmvyTn9N4HQ632qJBVHf8sxHi/fEsraprwCtzvzQETrNRwVxLO5jVmRGi60j8Ue1efIlzPXV9je9mkjzOmdssymZkh2QhUrCmZYI/FCEa3/cNMW0QIDAQAB';

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
		$return_url = 'http://' . $_SERVER ['HTTP_HOST'].'/respond.php';
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

		include_once ("alipay/aop/AlipayService.php");
		$aliPay = new AlipayService();
		$aliPay->setAppid($this->appId);
		$aliPay->setNotifyUrl($return_url);
		$aliPay->setRsaPrivateKey($this->rsaPrivateKey);
		$aliPay->setTotalFee($order['order_amount']);
		$aliPay->setOutTradeNo($order['order_id']);
		$aliPay->setOrderName($other['app_name']."-".$other['product_name']."-".$order ['order_sn']);

	    $result = $aliPay->doPay();
	    $result = $result['alipay_trade_precreate_response'];
	    $arr = array();
	    if($result['code'] && $result['code'] == '10000'){
	        require_once(ROOT_PATH . 'includes/phpqrcode.php');
	        ob_start();
	        $errorCorrectionLevel = intval(4);//容错级别
	        $matrixPointSize = intval(8);//生成图片大小
	        QRcode::png($result['qr_code'], false, $errorCorrectionLevel, $matrixPointSize, 2);
	        $arr['qrcode'] = base64_encode(ob_get_contents());
	        //关闭缓冲区
	        ob_end_clean();
	    }/*else{
	        echo $result['msg'].' : '.$result['sub_msg'];
	    }*/
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