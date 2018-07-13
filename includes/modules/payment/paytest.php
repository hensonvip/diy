<?php
//register_shutdown_function(function(){ var_dump(error_get_last()); });
/*
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
*/

include_once ("alipay/AopSdk.php");

$aop = new AopClient;
$aop->gatewayUrl = "https://openapi.alipaydev.com/gateway.do";
$aop->appId = "2016072800108070";
$aop->rsaPrivateKey = 'MIIEpAIBAAKCAQEAqph57gTuqT6bEDIuCr8laDp8usuZ7hGYSGgjZrlYQTvxaA2ZCzdumLQ4NOC/FVbcO81fd86GRrH2pLme0FkfqWInkv7OyeH2axmB3y4p0gjOmJ9u3MwTwCxXqUb9y4f/K7cGdFAgOZGRU8hj+8N2Ko50I+dI1RsTxEBSR7io0RNZFAQVD/m29LKi3QYCtZa6fgGf4Nz7EE3bD7JOQ5Ry7MDphN73BIc+HNLAXIsN7s8oIFx2fppv3mwgp6Q6isk5eg/g3kV6i5b5d3mn96pQXcLY/JiekrZyM4wH0n0VCuGfnV8jZkgbSswA3NTe6D7Z3BrZ19by++j8anSTRLqcDwIDAQABAoIBAQCTs6xt3itcsW3EGg4vE8wpb+oqOLmvm+BDYJa2C+dTo7ihpJRLV9UTtYWskqIesbPAu1HbAx7S+qZXfLb6IQ/+sZMd/CvCMzgNBmqhdvCSfzmQfwmXdcHr8vh7AZIftEHD8ZVPA0jDTzuKVMfsZRFcSzZXF5rBl84HbsFOg5z2VuVCFIgXQDv/rkMzxBNdPlSt3keOmYnzY12ZtMuiLEGp9icRTfazCWYxmLkevzrKVj3Ys1bhh8CWEK8rTMvcizbvB/Ajgt73onuwo/tYjSgFHhAvCCSRgL5FWqYPbab/Je7rrDmZVuWP9pnt7pJfAH1u5OTwmG8KjQYVmLdzdI4RAoGBAODgK8FXUg5mIdyjZeVHjwg3K51E/dNZT4IHvZr7ZXhwlbGq0RhxBYI2JD4JNWnsz/YORHbzxFUwEFxmUIbTtexNnpXtEF2Px0WhEJO6lEWKKECp7VO+EEzCtoPiyf7+clfDWULEXNGM/GZFE92XRE/LKUnXMhiUNLPw85A1t16bAoGBAMI1C8uPixXEKak9/Klt6gTko97M6tsaUzm5c42GLI+aY4A4mHw+q1W5zf9GBgoJQ9XCEd0/95kiaQ+3k9NKZyhGtub3cCHod8YDsNwFZFyoWtbr7/hudpgwifT4iN+LUzxnIhZsHrUv36zDaAi3pFHak6YVt1fxGgk1oIjdRrWdAoGAV+7wzTKzEJRZa2itoKGBycmhEWd4BdwnngYe22qwvA7ySj4sc21GpSs8stFxBJGopGPh283omRMpYqhTltVUjymu+JtxydQ+LPkVfV75OdQTd227MwLgZtPBAQN+z2p6Fd16mwQj84E49VjPstfCy5z68TfoC/pwPWjcJSkMIj8CgYAmnLFZqZJ3o2a9FWv0q7vJeUPzej5/jX5ajbqhurmFW6bIyXfXzAX4p1aDmIJ+4FSyXUF8AcnSknrc+xzS94oHAfg/d5a9xyB5KCazuAa6PWbCMGqntB60J4JmSDu+Zk8IRWELoDeCwp/wE3HNueVbuN9+N/cZ7v/EMPtX+taiKQKBgQCb3m+s5X0UYmiiMhhZXN9+23aNLpjqoXTzK6N9Ks82/G5LoGPI9YT84UVbAM+UEy5jmpCFZBddJhqXMwDQpU1u3RcvlvElVUDrmmbg9Ks/9Nk/TokXQ+vm/A3QdNB7FcRaGOE4XjT/GTNBqOGYbFMH8P+wYEbn1ezDP/5Kj163Rw==' ;
$aop->format = "json";
$aop->charset = "UTF-8";
$aop->signType = "RSA2";
$aop->alipayrsaPublicKey ='MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAz/NepGpSq+BgiQHhU6pFhMxkYxF4GPvQLaiDDq0vA2+xFeSatzvJnqWDCOvwvATPTsj7a65gi36APE5mR3JZrKe5gLR+QcjiETmL84hzqX/lkybTadXe2TMa0oNRuxGtgmSjyYI2bhwtodm91pfdQeykDV/s2k18/6XBPHU7zGzDO/wlzst1MGjX41V90QnhwRQLQ2ww5S+xXxwp4zQvFHFsNNVFQPm8Bw5xMmcIGXIukWc3SkbP1T6bKTRoKmv7I3sTL20kqzho6CyoW4e6CkUJxOF+J6qD1FVJ5p/axEnh1SFNX4SxEpDecGykoU2DYdPDBMcQNf5FkEKz2HtobQIDAQAB';
/*
//app
//实例化具体API对应的request类,类名称和接口名称对应,当前调用接口名称：alipay.trade.app.pay
$a = 'AlipayTradeAppPayRequest';
$request = new $a();
//SDK已经封装掉了公共参数，这里只需要传入业务参数
$bizcontent = "{\"body\":\"我是测试数据\"," 
                . "\"subject\": \"App支付测试\","
                . "\"out_trade_no\": \"".date('YmdHis')."test08\","
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

*/
echo '---------------------------------------pc---------------------------------------------------';
//网页
//实例化具体API对应的request类,类名称和接口名称对应,当前调用接口名称：alipay.trade.page.pay
$request = new AlipayTradePagePayRequest();
//SDK已经封装掉了公共参数，这里只需要传入业务参数
$bizcontent = "{\"body\":\"我是测试数据\"," 
                . "\"subject\": \"App支付测试\","
                . "\"out_trade_no\": \"".date('YmdHis')."test01\","
                . "\"timeout_express\": \"30m\"," 
                . "\"total_amount\": \"0.01\","
                . "\"product_code\":\"FAST_INSTANT_TRADE_PAY\""
                . "}";
$request->setNotifyUrl("http://hunuo.com");
$request->setReturnUrl("http://hunuo.com");
$request->setBizContent($bizcontent);				

$result = $aop->pageExecute($request); 
//输出表单
echo $result;
				
//请求pageExecute 
//$result = $aop->sdkExecute($request); 
//htmlspecialchars是为了输出到页面时防止被浏览器将关键参数html转义，实际打印到日志以及http传输不会有这个问题
//echo htmlspecialchars($result);//就是orderString 可以直接给客户端请求，无需再做处理。

/*
echo '--------------------------------------wap----------------------------------------------------';
//wap
//实例化具体API对应的request类,类名称和接口名称对应,当前调用接口名称：alipay.trade.wap.pay
$request = new AlipayTradeWapPayRequest ();
$request->setBizContent("{" .
"    \"body\":\"对一笔交易的具体描述信息。如果是多种商品，请将商品描述字符串累加传给body。\"," .
"    \"subject\":\"大乐透\"," .
"    \"out_trade_no\":\"".date('YmdHis')."S001111119\"," .
"    \"timeout_express\":\"90m\"," .
"    \"total_amount\":9.00," .
"    \"product_code\":\"QUICK_WAP_WAY\"" .
"  }");
$result = $aop->pageExecute ( $request); 
echo $result;
//请求pageExecute 
//$result = $aop->sdkExecute($request); 
//htmlspecialchars是为了输出到页面时防止被浏览器将关键参数html转义，实际打印到日志以及http传输不会有这个问题
//echo htmlspecialchars($result);//就是orderString 可以直接给客户端请求，无需再做处理。
*/
