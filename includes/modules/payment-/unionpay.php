<?php
/**
* 中国银联商户支付
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
	//构造函数
	public function __construct(){
		$this->unionpay();
	}
	
	
	public function unionpay(){
		
	}
	/**
	* 生成支付代码
	* @param array $order 订单信息
	* @param array $payment 支付方式信息
	*/
	public function prepay($order, $payment, $other) {

		include_once ("unionpay/acp_service.php");
	
		$return_url = 'http://' . $_SERVER ['HTTP_HOST'].'/respond.php';
	
		$params = array(
		
			//以下信息非特殊情况不需要改动
			'version' => com\unionpay\acp\sdk\SDKConfig::getSDKConfig()->version,                 //版本号
			'encoding' => 'utf-8',				  //编码方式
			'txnType' => '01',				      //交易类型
			'txnSubType' => '01',				  //交易子类
			'bizType' => '000201',				  //业务类型
			'frontUrl' =>  com\unionpay\acp\sdk\SDKConfig::getSDKConfig()->frontUrl,  //前台通知地址
			'backUrl' => $return_url,	  //后台通知地址
			'signMethod' => com\unionpay\acp\sdk\SDKConfig::getSDKConfig()->signMethod,	              //签名方法
			'channelType' => '08',	              //渠道类型，07-PC，08-手机
			'accessType' => '0',		          //接入类型
			'currencyCode' => '156',	          //交易币种，境内商户固定156
			
			//TODO 以下信息需要填写
			'merId' => '777290058110048',		//商户代码，请改自己的测试商户号，此处默认取demo演示页面传递的参数
			'orderId' => $order["order_id"],	//商户订单号，8-32位数字字母，不能含“-”或“_”，此处默认取demo演示页面传递的参数，可以自行定制规则
			'txnTime' => date('YmdHis'),	//订单发送时间，格式为YYYYMMDDhhmmss，取北京时间，此处默认取demo演示页面传递的参数
			'txnAmt' => $order["order_amount"]*100,	//交易金额，单位分，此处默认取demo演示页面传递的参数

			// 请求方保留域，
			// 透传字段，查询、通知、对账文件中均会原样出现，如有需要请启用并修改自己希望透传的数据。
			// 出现部分特殊字符时可能影响解析，请按下面建议的方式填写：
			// 1. 如果能确定内容不会出现&={}[]"'等符号时，可以直接填写数据，建议的方法如下。
			//    'reqReserved' =>'透传信息1|透传信息2|透传信息3',
			// 2. 内容可能出现&={}[]"'符号时：
			// 1) 如果需要对账文件里能显示，可将字符替换成全角＆＝｛｝【】“‘字符（自己写代码，此处不演示）；
			// 2) 如果对账文件没有显示要求，可做一下base64（如下）。
			//    注意控制数据长度，实际传输的数据长度不能超过1024位。
			//    查询、通知等接口解析时使用base64_decode解base64后再对数据做后续解析。
			//    'reqReserved' => base64_encode('任意格式的信息都可以'),

			//TODO 其他特殊用法请查看 pages/api_05_app/special_use_purchase.php
		);
		com\unionpay\acp\sdk\AcpService::sign ( $params ); // 签名
		$url = com\unionpay\acp\sdk\SDKConfig::getSDKConfig()->appTransUrl;
		$result_arr = com\unionpay\acp\sdk\AcpService::post ($params,$url);
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
	* 响应操作
	*/
	public function respond()
	{
		include_once ("unionpay/acp_service.php");
	
		$return_url = 'http://' . $_SERVER ['HTTP_HOST'].'/respond.php';
	
		$params = array(
		
			//以下信息非特殊情况不需要改动
			'version' => com\unionpay\acp\sdk\SDKConfig::getSDKConfig()->version,                 //版本号
			'encoding' => 'utf-8',				  //编码方式
			'txnType' => '01',				      //交易类型
			'txnSubType' => '01',				  //交易子类
			'bizType' => '000201',				  //业务类型
			'frontUrl' =>  com\unionpay\acp\sdk\SDKConfig::getSDKConfig()->frontUrl,  //前台通知地址
			'backUrl' => $return_url,	  //后台通知地址
			'signMethod' => com\unionpay\acp\sdk\SDKConfig::getSDKConfig()->signMethod,	              //签名方法
			'channelType' => '08',	              //渠道类型，07-PC，08-手机
			'accessType' => '0',		          //接入类型
			'currencyCode' => '156',	          //交易币种，境内商户固定156
			
			//TODO 以下信息需要填写
			'merId' => $_POST["merId"],		//商户代码，请改自己的测试商户号，此处默认取demo演示页面传递的参数
			'orderId' => $_POST["orderId"],	//商户订单号，8-32位数字字母，不能含“-”或“_”，此处默认取demo演示页面传递的参数，可以自行定制规则
			'txnTime' => date('YmdHis'),	//订单发送时间，格式为YYYYMMDDhhmmss，取北京时间，此处默认取demo演示页面传递的参数
			'txnAmt' => $_POST["txnAmt"],	//交易金额，单位分，此处默认取demo演示页面传递的参数

			// 请求方保留域，
			// 透传字段，查询、通知、对账文件中均会原样出现，如有需要请启用并修改自己希望透传的数据。
			// 出现部分特殊字符时可能影响解析，请按下面建议的方式填写：
			// 1. 如果能确定内容不会出现&={}[]"'等符号时，可以直接填写数据，建议的方法如下。
			//    'reqReserved' =>'透传信息1|透传信息2|透传信息3',
			// 2. 内容可能出现&={}[]"'符号时：
			// 1) 如果需要对账文件里能显示，可将字符替换成全角＆＝｛｝【】“‘字符（自己写代码，此处不演示）；
			// 2) 如果对账文件没有显示要求，可做一下base64（如下）。
			//    注意控制数据长度，实际传输的数据长度不能超过1024位。
			//    查询、通知等接口解析时使用base64_decode解base64后再对数据做后续解析。
			//    'reqReserved' => base64_encode('任意格式的信息都可以'),

			//TODO 其他特殊用法请查看 pages/api_05_app/special_use_purchase.php
		);
		com\unionpay\acp\sdk\AcpService::sign ( $params ); // 签名
		if ($params['signature'] != $_POST['signature']) {
			return false;
		}
		/* 检查支付的金额是否相符 */
        if (!check_money($_POST['reqReserved'], $_POST['txnAmt']/100))
        {
            return false;
        }
		if($_POST['merId'] == $payment['unionpay_account']){
			order_paid($params['reqReserved']);
			return true;
		}else{
			return false;
		}
	}
	public function addLog($other = array(), $type = 1) {
		$log ['ip'] = $_SERVER['REMOTE_ADDR'];
		$log ['time'] = date('Y-m-d H:i:s');
		$log ['get'] = $_REQUEST;
		$log ['other'] = $other;
		$log = serialize ( $log );
		return $GLOBALS['db']->query( "INSERT INTO " . $GLOBALS['ecs']->table('weixin_paylog') . " (`log`,`type`) VALUES ('$log','$type')" );
	}
}


