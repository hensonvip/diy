<?php

/**
 * ECSHOP 支付响应页面
 * ============================================================================
 * * 版权所有 2005-2012 上海商派网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.ecshop.com；
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * $Author: liubo $
 * $Id: respond.php 17217 2011-01-19 06:29:08Z liubo $
 */
 
error_log(json_encode($_REQUEST),3,dirname(__FILE__) . '/data/payment/error.log');
define('IN_ECS', true);

require(dirname(__FILE__) . '/includes/init.php');
require(ROOT_PATH . 'includes/lib_payment.php');
require(ROOT_PATH . 'includes/lib_order.php');


if(xml_parser(file_get_contents('php://input'))){
	error_log(file_get_contents('php://input'),3,dirname(__FILE__) . '/data/payment/error1.log');
	$params = 'weixin';
}else{
	$params = 'alipay';
}

require_once(ROOT_PATH . 'includes/modules/payment/'.$params.'.php');
$new_class = $params;
$payment = new $new_class();

$result = $payment->respond();

if($result === true){
	if($params == 'weixin'){
		$result = "<xml>
					  <return_code><![CDATA[SUCCESS]]></return_code>
					  <return_msg><![CDATA[OK]]></return_msg>
					</xml>";
	}else{
		$result = "success";
	}
}else{
	$result = "error";
}

echo $result;

function xml_parser($str){
    $xml_parser = xml_parser_create();
    if(!xml_parse($xml_parser,$str,true)){
		xml_parser_free($xml_parser);
		return false;
    }else {
		return true;
    }
}

?>