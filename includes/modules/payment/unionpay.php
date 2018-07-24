<?php
/**
* 中国银联商户支付 mall.qdshop.com/includes/modules/payment/unionpay.php
*/

if (!defined('IN_ECS'))
{
	die('Hacking attempt');
}

/**
* 类
*/
class unionpay
{
	
	private $gateway;
	
	
	//构造函数
	public function __construct(){
		$this->unionpay();
	}
	
	
	public function unionpay(){
		$return_url = 'http://' . $_SERVER ['HTTP_HOST'].'/respond.php?type=unionpay';
		
		$unionpay = json_decode($this->get_php_file(ROOT_PATH."/data/payment/unionpay.php"), true);	
		$unionpay = array(
			'merid'=>'310420173990264',
			'cert_path'=>'3dyun.pfx',
			'cert_password'=>'000000',
			'public_key'=>'3dyun.cer',
		);
		
		$this->gateway = Omnipay::create($this->pay);
		$this->gateway->setMerId($unionpay['merid']);

		$this->gateway->setCertDir(''); 
		$this->gateway->setCertPath($unionpay['cert_path']); 
		$this->gateway->setCertPassword($unionpay['cert_password']);
		$this->gateway->setPublicKey($unionpay['public_key']);
		
		$this->gateway->setCertId(Signer::readCertId(Signer::readCert($unionpay['cert_path'], $unionpay['cert_password'])));

		$this->gateway->setReturnUrl($return_url);
		$this->gateway->setNotifyUrl('http://mall.qdshop.com/includes/modules/payment/unionpay.php');
		//$this->gateway->setEnvironment('sandbox');  //沙箱
		$this->gateway->setEnvironment('production');

		
		
	}
	/**
	* 生成支付代码
	* @param array $order 订单信息
	* @param array $payment 支付方式信息
	*/
	public function prepay() {

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
	
		$order = [
			'orderId'   => $order['order_id'],
			'txnTime'   => date('YmdHis'), //Should be format 'YmdHis'
			'orderDesc' => $other['app_name']."-".$other['product_name']."-".$order ['order_sn'], //Order Title
			'txnAmt'    => $order['order_amount']*100, //Order Total Fee
		];
		
		//var_dump($response->getData())	; //For debug
		//var_dump($response->getAppOrderData()); //For APP
		//var_dump($response->getCodeUrl()); //For NATIVE
		//var_dump($response->getJsOrderData()); //For JS
				
		$arr = array();
	
		if($payment == 'unionpay_app'){
			$response = $this->gateway->createOrder($order)->send();
			if($response->isSuccessful()){
				$arr['prepay_id'] = $response->getTradeNo();
			}else{
				return false;
			}
		}else{
			$response = $this->gateway->purchase($order)->send();
			if($response->isSuccessful()){
				$arr['prepay_id'] = $response->getRedirectHtml();
			}else{
				return false;
			}
		}
		
		$arr['time_expire'] = $expire;
		$arr['payment'] = $payment;
		
		if(!strstr($order['order_id'],'charge')){
			$GLOBALS['db']->query("update".$GLOBALS['ecs']->table('order_info')." set pay_code = '".$payment."' where order_id = ".$order['order_id']);
		}
		
		return $arr;
	
	}

	/**
	* 订单查询操作
	*/
	public function query(){
		$response =$this->gateway->query([
			'orderId' => '2084146296TT20171220111942', //Your site trade no, not union tn.
			'txnTime' => date('YmdHis'), //Order trade time
			'txnAmt'  => '30', //Order total fee
		])->send();

		if($response->isSuccessful()){
			var_dump($response->getData());
		}	
	}
	
	/**
	* 退款操作
	*/
	public function refund(){		
		$response = $this->gateway->refund([
			'orderId' => rand().'TT'.date('YmdHis'), //Your site trade no, not union tn.
			'txnTime' => date('YmdHis'), //Order trade time
			'txnAmt'  => '30', //Order total fee
			'queryId'  => '721712201041341950078', //Order total fee
		])->send();

		if($response->isSuccessful()){
			var_dump($response->getData());
		}
		return false;
	}

	/**
	* 响应操作
	*/
	public function respond()
	{
		
		$response = $this->gateway->completePurchase(['request_params'=>$_REQUEST])->send();
		var_dump($response->getData());
		if ($response->isPaid()) {
			
			$out_trade_no = $_POST['orderId'];
			$total_amount = $_POST['txnAmt'];
			$queryId = $_POST['queryId'];  //银联
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
		}else{
			return false;
		}

       
	}

}


