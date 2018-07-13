<?php
error_reporting(0);
//session_start();

header("Content-type:text/html; charset=UTF-8");

function sendSMS($mobile_phone, $mobile_code) {
	$host = "https://fesms.market.alicloudapi.com";//api访问链接
    $path = "/sms/";//API访问后缀
    $method = "GET";
    $appcode = "430f1e215f224db2adef5639d8e3ad7b";//替换成自己的阿里云appcode
    $headers = array();
    array_push($headers, "Authorization:APPCODE " . $appcode);
    $querys = "code={$mobile_code}&phone={$mobile_phone}&skin=1";  //参数写在这里, 自定义skin编号请找客服申请
    $bodys = "";
    $url = $host . $path . "?" . $querys;//url拼接

    $curl = curl_init();
    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($curl, CURLOPT_FAILONERROR, false);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_HEADER, false);
    if (1 == strpos("$".$host, "https://"))
    {
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
    }
    $result = json_decode(curl_exec($curl), true);
    if ($result && $result['Code'] == 'OK') {
        return true;
    } else {
        return false;
    }
}

function sendMessSMS($mobile_phone, $send_message) {
    $host = "https://fesms.market.alicloudapi.com";
    $path = "/smsmsg";
    $method = "GET";
    $appcode = "430f1e215f224db2adef5639d8e3ad7b";
    $headers = array();
    array_push($headers, "Authorization:APPCODE " . $appcode);
    $send_message = urlencode($send_message);
    $querys = "param={$send_message}&phone={$mobile_phone}&sign=1&skin=1002";
    $bodys = "";
    $url = $host . $path . "?" . $querys;

    $curl = curl_init();
    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($curl, CURLOPT_FAILONERROR, false);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_HEADER, false);
    if (1 == strpos("$".$host, "https://"))
    {
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
    }
    $result = json_decode(curl_exec($curl), true);
    if ($result && $result['Code'] == 'OK') {
        return true;
    } else {
        return false;
    }
}
?>
