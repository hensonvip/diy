<?php
//register_shutdown_function(function(){ var_dump(error_get_last()); });
define('IN_ECS', true);
include_once ("unionpay.php");
$p = new unionpay;
$order['order_sn'] = '1234';
$order['order_id'] = '12345678aa34567';
$order['order_amount'] = '1111';
$payment = 'QUICK_WAP_WAY';
$other['expire'] = 300;
$other['app_name'] = 'app_name';
$other['product_name'] = 'product_name';
var_dump($p->prepay($order, $payment, $other));
/*
include_once ("ali/AopSdk.php");

$aop = new AopClient;
$aop->gatewayUrl = "https://openapi.alipaydev.com/gateway.do";
$aop->appId = "2016072800108070";
$aop->rsaPrivateKey = 'MIIEpAIBAAKCAQEAxhlE5Pa/vAEQm6xW2+ccCis4rQ4n+ci6YHZWgE3oW6qDIZ7gVLtnaoWZs+z9/UsWvqB39Lmvz4d+RIb4YArDa4cZmYubKf0cVQEJ3K51O8soXYT2F6+ckrEaceRUAZOMIzICfrSGlYo8+szDY5MntUYa3IFc61jnM4SA81lrKGEXisDSo/jMbEpGahqyywxI5DeDJtPdP8r/dSIAQa8FpCg7wTB0SIp+ZcUqWbZXQqHC3uFEoGQJv/zV9vpgUS+q0vXW8ca5+TLlDtZ/2FP+pmwx7OBEFTiE1kwNN4eDh140Jvvid84UttCHWt+0nhKd7eoDoxHQml4N8NfbAVoxwwIDAQABAoIBAQCgCKnvjqc2amXQXVmOLRYp4ODYbSc+Uzq90tvuqwGbjBSDhKYVzY+uLmANStelWJP3TAucWKX/MaaAFclxTUCyDWJQ+MdRFHle8ofVD9sFgwoJAvEMEYKbvxduCtcSru/3Pt9lHNHc3OCGfAUc3DSn7QqmOJuoRRoCNHk4HnTDGL/chYp+G/BrMjlf1c7kBVU7w9ZBi0m5JTHdQs0AvJgeoVYpmJvKjaRonZAefnMjiRlD5yNAk0dPkGllG5BOOFr6dTQG0LE/S8EZ48CMHQzMs77Jx0cWm6K5FZwQkcmOnp1C6SZIOfLhuxuqLf4mJ60K/bUaEXRtyd5G+Hefvt8BAoGBAPKwjsJZEeJM7H1TrERkPDH9eARzCiWo7uF/m6hFPVdJswb0AQ11E+qd6QjYi0KOBNjee6psY+NPhl90n97a4VSlYLfNelCUvCQXcnSFmlHkkHK+mpa7tbBFs7UUDWPIkfYVVx5XZrsihrouIeREIVyy2mVmK4wL25c03YGI/oA5AoGBAND2o8GEgfXswcvJEhBbHD++oRSvwe0asdKYC6vBSn3eZXpgfl1zGTP5DelbWAWcG0QlYBMqEPpAEoqsubcu7w5etVAOOgmJnQwjO6/NIMJLfNeQCCsAEHCqAf/psiocNwaXTIadbI6YUithJegVD0HCZbmCksxpsTYNpHs9JInbAoGBAIogGuBaOWeHSIH8AWvbcLcy///oSbotb+g/7KPU5JithYtLjN3P6Mn7ngo+1OPIWNHlrpTMo/1X4a6qfeMkyGKYzXdPJ3J9dKdGAQp4j+BUKjJkcS+hHkN5KFLDWuuT2B8q/i5yqDGR8QQ+BWQZsrNDyDE1+Ur35L8mg3uaMN9hAoGAH29rFRqc/mT+hmyaJhG+vywrSVRjZQrmA1tSLiVm0maZd356pA5DlVj7KcbPCBEC3Q6OVHO4mBz5bRks2wzZc5z1w7RQMM0d7gyC1yCRtAjtH/SP9gANeRVqNZhvb/xMntY18e5OvWjWu6XisPYyFF9tNCobcVrZCNumic+Z5o8CgYA3TxXpS2s4IkFHhUAUo43aA3knM0FWScrbcLZhMoSOJRHS0L840cyZYtCeDiF+lL/oBl5CuoPK5ps8s6pVKleSg44ooUIygVxZv5FXgRVqiP7fVXWqXfhQvt6uqEZYwgnSRzHBGtGyexRJP+0Vdc8DBoDI/b75/DMuHDuNRgyAIQ==' ;
$aop->format = "json";
$aop->charset = "UTF-8";
$aop->signType = "RSA2";
$aop->alipayrsaPublicKey ='MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAxhlE5Pa/vAEQm6xW2+ccCis4rQ4n+ci6YHZWgE3oW6qDIZ7gVLtnaoWZs+z9/UsWvqB39Lmvz4d+RIb4YArDa4cZmYubKf0cVQEJ3K51O8soXYT2F6+ckrEaceRUAZOMIzICfrSGlYo8+szDY5MntUYa3IFc61jnM4SA81lrKGEXisDSo/jMbEpGahqyywxI5DeDJtPdP8r/dSIAQa8FpCg7wTB0SIp+ZcUqWbZXQqHC3uFEoGQJv/zV9vpgUS+q0vXW8ca5+TLlDtZ/2FP+pmwx7OBEFTiE1kwNN4eDh140Jvvid84UttCHWt+0nhKd7eoDoxHQml4N8NfbAVoxwwIDAQAB';

//app
//实例化具体API对应的request类,类名称和接口名称对应,当前调用接口名称：alipay.trade.app.pay
$a = 'AlipayTradeAppPayRequest';
$request = new $a();
//SDK已经封装掉了公共参数，这里只需要传入业务参数
$bizcontent = "{\"body\":\"我是测试数据\"," 
                . "\"subject\": \"App支付测试\","
                . "\"out_trade_no\": \"20170125test08\","
                . "\"timeout_express\": \"30m\"," 
                . "\"total_amount\": \"0.01\","
                . "\"product_code\":\"QUICK_MSECURITY_PAY\""
                . "}";
$request->setNotifyUrl("http://hunuo.com");
$request->setBizContent($bizcontent);
//这里和普通的接口调用不同，使用的是sdkExecute
$response = $aop->sdkExecute($request);
//htmlspecialchars是为了输出到页面时防止被浏览器将关键参数html转义，实际打印到日志以及http传输不会有这个问题
echo htmlspecialchars($response);//就是orderString 可以直接给客户端请求，无需再做处理。
exit;
echo '---------------------------------------pc---------------------------------------------------';
//网页
//实例化具体API对应的request类,类名称和接口名称对应,当前调用接口名称：alipay.trade.page.pay
$request = new AlipayTradePagePayRequest();
//SDK已经封装掉了公共参数，这里只需要传入业务参数
$bizcontent = "{\"body\":\"我是测试数据\"," 
                . "\"subject\": \"App支付测试\","
                . "\"out_trade_no\": \"20170125test01\","
                . "\"timeout_express\": \"30m\"," 
                . "\"total_amount\": \"0.01\","
                . "\"product_code\":\"FAST_INSTANT_TRADE_PAY\""
                . "}";
$request->setNotifyUrl("http://hunuo.com");
$request->setReturnUrl("http://hunuo.com");
$request->setBizContent($bizcontent);				

$result = $aop->pageExecute($request); 
//输出表单
//echo $result;
				
//请求pageExecute 
$result = $aop->sdkExecute($request); 
//htmlspecialchars是为了输出到页面时防止被浏览器将关键参数html转义，实际打印到日志以及http传输不会有这个问题
echo htmlspecialchars($result);//就是orderString 可以直接给客户端请求，无需再做处理。

echo '--------------------------------------wap----------------------------------------------------';
//wap
//实例化具体API对应的request类,类名称和接口名称对应,当前调用接口名称：alipay.trade.wap.pay
$request = new AlipayTradeWapPayRequest ();
$request->setBizContent("{" .
"    \"body\":\"对一笔交易的具体描述信息。如果是多种商品，请将商品描述字符串累加传给body。\"," .
"    \"subject\":\"大乐透\"," .
"    \"out_trade_no\":\"70501111111S001111119\"," .
"    \"timeout_express\":\"90m\"," .
"    \"total_amount\":9.00," .
"    \"product_code\":\"QUICK_WAP_WAY\"" .
"  }");
//$result = $aop->pageExecute ( $request); 
//echo $result;
//请求pageExecute 
$result = $aop->sdkExecute($request); 
//htmlspecialchars是为了输出到页面时防止被浏览器将关键参数html转义，实际打印到日志以及http传输不会有这个问题
echo htmlspecialchars($result);//就是orderString 可以直接给客户端请求，无需再做处理。
*/
