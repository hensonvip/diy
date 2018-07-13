<?php
/**
* 中国银联商户支付 mall.qdshop.com/includes/modules/payment/unionpay.php
*/
header("Content-type:text/html;charset=utf-8");
error_reporting(E_ALL); //E_ALL  
   function cache_shutdown_error() {  
   
    $_error = error_get_last();  
   
    if ($_error && in_array($_error['type'], array(1, 4, 16, 64, 256, 4096, E_ALL))) {  
   
        echo '<font color=red>你的代码出错了：</font></br>';  
        echo '致命错误:' . $_error['message'] . '</br>';  
        echo '文件:' . $_error['file'] . '</br>';  
        echo '在第' . $_error['line'] . '行</br>';  
    }  
}  
register_shutdown_function("cache_shutdown_error");  

require "unionpay/vendor/autoload.php";
use Omnipay\Omnipay;
use Omnipay\UnionPay\Common\Signer;

if (!defined('IN_ECS'))
{
	$a = new hunuoPay('alipay');
	//$a->query();exit();
	//$a->refund();exit();

	if($_REQUEST['orderId']){
		$a->respond();exit();
	}
	$a->prepay();
	die('Hacking attempt');
}

/**
* 类
*/
class hunuoPay
{
	
	private $pay ;
	private $gateway;
	
	//构造函数
	public function __construct($pay=''){
		$this->pay = $pay;
		$this->hunuoPay();
	}
	
	
	public function hunuoPay(){
		
		
		switch($this->pay){
			case 'UnionPay_Express':
				$this->gateway = Omnipay::create($this->pay);
				$this->gateway->setMerId('310420173990264');
		
				$this->gateway->setCertDir(''); // .pfx file
				$this->gateway->setCertPath('3dyun.pfx'); // .pfx file
				$this->gateway->setCertPassword('000000');
				$this->gateway->setPublicKey('3dyun.cer');
				
				$this->gateway->setCertId(Signer::readCertId(Signer::readCert('3dyun.pfx', '000000')));
				//$this->gateway->setCertId('40220995861346480087409489142384722381');
				//$this->gateway->setPrivateKey(file_get_contents('private_key.pem')); // path or content
				$this->gateway->setReturnUrl('http://mall.qdshop.com/includes/modules/payment/unionpay.php');
				$this->gateway->setNotifyUrl('http://mall.qdshop.com/includes/modules/payment/unionpay.php');
				//$this->gateway->setEnvironment('sandbox');  //沙箱
				$this->gateway->setEnvironment('production');
				break;
			case 'WechatPay':
				$this->gateway = Omnipay::create($this->pay);
				//$this->gateway->setAppId('wxb76c4b55e21a050f');
				$this->gateway->setAppId('wx209a81d435e8c121');
				//$this->gateway->setMchId('1489938572');
				$this->gateway->setMchId('10057690');
				//$this->gateway->setApiKey('qGhme1PTK4utiPMTehAbOuoNVY9EiQHo');
				$this->gateway->setApiKey('wx209a81d435e8c1wx209a81d435e8c1');
				$this->gateway->setNotifyUrl('http://mall.qdshop.com/includes/modules/payment/unionpay.php');
				$this->gateway->setTradeType('JSAPI');
				break;
			case 'alipay':
				$this->gateway = new \AopClient(); 
				$this->gateway->gatewayUrl = "https://openapi.alipaydev.com/gateway.do"; //测试
				$this->gateway->appId = "2016072800108070";
				$this->gateway->rsaPrivateKey = 'MIIEowIBAAKCAQEAv95mLKX2kFIXzA94m3WR6lxAh6KoNrLk+GCXsYNwMNQkkH60iInULYWDAKjEAvjSb0sQwNcDUPX5XVyaJSioyUtK0YJ9aOUmA6uOLQenx9ECR6r96bmJ3kbI6BH7/6vS/86OD1tdSmT5D0r50JRf+J8r6WGCgwdPtMGcW7nGrXmmqGdlAGtBGMIihazAHC1D+C2uijhP/t6Bfb06mCrhMpMod+q+Nat+MIRm0U23//JK+I7INi7l5dRdHo9NyNvSd4qDCQUyD8fVAm8DJmyHcRdeZ4bHcDuC12eAwtowh5mE7qwbrW/WJv/YImg8P5PXpMHUxNaIbFcRjMBjRAsYhQIDAQABAoIBAAruivfERPYYF08DGqYQ3s1xkCOqOtdS/gTAzCPLD1tY2vR4o6Sb5cDqOHqTIwfgUW0R49R467FzMlAFdKrlVCiT56FkQ+z0EWRoI+Og6ghSekTHE0x7y8UkV1IRpD/+9Diq9iEd3YTdl+stJGqj6Yvq1b+oVJ1Oj1YHKfd38D/18yt+PNGAUKWJCEPg/WKU7q53tu0s3RgOr48MDmeRMqIS5U5Y3ozVBz2d4hxRLEfclvF36Pu3q5IrNeacS/MO1ziW4q27FE7uFHmGDSwcL66G0T1jaNu2ryTpGb8lCrXqfFDLvZvoeJAi+Td7HD8gUbYmYaPE2VaPsYzW8U8PvsECgYEA42r0cUQ/xXehZUn11ynSHtjxjubINlFS5eGmES9xcJ0Kf+jH7lX2cP+fZVx5IBFkCuQ+FbOP3p8H34WrDUyH1H5Ui7IIJj3gUFia0zsCmxCr6TFBf3c3w3QdzPiZsxqLRUKK/b/QKqGNsC7PT4D07Y9JXtXV7gMz6VhC+t0MxI0CgYEA1/usPwfrKg3igEpM1K68I2FvlaLnX/Tcq87ZxTOMubfYgPvb3DwnZzvJdq3In4ogFs2A7pf3hMA7aiOBNstkJyXncpEkq7MGr1WaI25Ex05JMbxT75dq8F3sarBravyJXXnwr7WJbK3teEWSXJ3youMkSM+0s2gbVWrkX6BfsdkCgYBoaEohVjwQ5INshTAgwEp/bwo3mSyCA4QTli0v5qAmG3Meljhz63NhHXqIhpP1bQaJGZCHVhDPHCP5Mtz9Fg2mOPmHyQyEwhOhz4wFPipMFrNWNA7l/k3TphOobtnhqGYYPhyhstZXPCRYpqT20zWfexEsQPAefcnvb14Q3jtmYQKBgEVMt0OyIr5nnhlTvbswJaWkic0Va+/Z/UQTJsSjea/NUWZxaWYM1wfEoyu1Lv4hr5IhdhEYKY8qruWrZrZSJFx7SPv1njKJSsfPS5m6s87PS6TtrHDfwKxHNqg02wKE7P5VNejq8qEwXVWN3RxigYvKHcZO/sfhy4NovMYCCzJhAoGBAKKEWLZqz9GDJgTWROYvSQYn+/IXP4qCZ4PcrJcnoVYO7NV5Ts4tAJItu3TzlqwV720tAUdvOsyTTwNPWW3MpaEkXDpvCne9c9OmNlStEENI5liwZWYVwnMNaV7lUcrNw3z/90vt2rxmk5ju6SauvE4kRKDEaLZCv3hMMAz6w92B';
				$this->gateway->alipayrsaPublicKey ='MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAz/NepGpSq+BgiQHhU6pFhMxkYxF4GPvQLaiDDq0vA2+xFeSatzvJnqWDCOvwvATPTsj7a65gi36APE5mR3JZrKe5gLR+QcjiETmL84hzqX/lkybTadXe2TMa0oNRuxGtgmSjyYI2bhwtodm91pfdQeykDV/s2k18/6XBPHU7zGzDO/wlzst1MGjX41V90QnhwRQLQ2ww5S+xXxwp4zQvFHFsNNVFQPm8Bw5xMmcIGXIukWc3SkbP1T6bKTRoKmv7I3sTL20kqzho6CyoW4e6CkUJxOF+J6qD1FVJ5p/axEnh1SFNX4SxEpDecGykoU2DYdPDBMcQNf5FkEKz2HtobQIDAQAB';
				$this->gateway->format = "json";
				$this->gateway->charset = "UTF-8";
				$this->gateway->signType = "RSA2";				
				break;
		}
		
		
	}
	/**
	* 生成支付代码
	* @param array $order 订单信息
	* @param array $payment 支付方式信息
	*/
	public function prepay() {

		$order = [
			'orderId'   => date('YmdHis'), //Your order ID
			'txnTime'   => date('YmdHis'), //Should be format 'YmdHis'
			'orderDesc' => 'My order title', //Order Title
			'body' => 'My order title', //Order Title
			'out_trade_no'      => date('YmdHis').mt_rand(1000, 9999),
			'total_fee'         => 1, //=0.01
			'txnAmt'    => '30', //Order Total Fee
			'spbill_create_ip'    => '8.8.8.8',
			'open_id'    => 'oGDCUjjDRtzlODsifwoUgu9QEByI',
		];

		$pre = array();
		$pre['body'] = 'My order title';
		$pre['out_trade_no'] = date('YmdHis');
		$pre['subject'] = 'My order title';
		$pre['total_amount'] = 100;
		$pre['product_code'] = 'FAST_INSTANT_TRADE_PAY';
		
		$bizcontent = json_encode($pre,true);

		$request = new \AlipayTradePagePayRequest();
		$request->setNotifyUrl('http://mall.qdshop.com/includes/modules/payment/unionpay.php');
		$request->setBizContent($bizcontent);
		$response = $this->gateway->pageExecute($request); 
		
		echo $response;
		var_dump($response);die();
		
		//$response = $this->gateway->purchase($order)->send();

		//var_dump($response->getData())	;
		if($response->isSuccessful()){
			
			var_dump($response->getData())	; //For debug
			//var_dump($response->getAppOrderData()); //For APP
			//var_dump($response->getCodeUrl()); //For NATIVE
			//var_dump($response->getJsOrderData()); //For JS
			//die();
			//echo $response->getRedirectHtml(); //银联
			//echo $response->getRedirectUrl();
			
		}
		//For PC WAP
		//$response = $this->gateway->purchase($order)->send();
		//echo $response->getRedirectHtml();
		

		//For APP
		//$response = $this->gateway->createOrder($order)->send();
		//echo $response->getTradeNo();
	
		if(count($result_arr)>0 && com\unionpay\acp\sdk\AcpService::validate ($result_arr) && $result_arr["respCode"] == "00") {		
			$arr = array();
			$arr['prepay_id'] = $result_arr["tn"];
			$arr['time_expire'] = $expire;
			$arr['payment'] = 'unionpay';
			return $arr;
		} 
		return false;
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
			error_log(print_r($response->getData(),1),3,'1.log');print_r($_REQUEST);
			return true;
		}else{
			return false;
		}

       
	}

}


