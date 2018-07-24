<?php
/**
 * 该示例主要为JPush Push API的调用示例
 * HTTP API文档:http://docs.jpush.io/server/rest_api_v3_push/
 * PHP API文档:https://github.com/jpush/jpush-api-php-client/blob/master/doc/api.md#push-api--构建推送pushpayload
 */
register_shutdown_function(function(){ var_dump(error_get_last()); });
require_once("./lib/JPush/JPush.php");

$app_key = '48338331fe271eff6e27510a';
$master_secret = '40ffab634e60b2e1efecd85f';

// 初始化
$client = new JPush($app_key, $master_secret);
$body = "测试推送内容，这是一条商品推广，嗯！！";
// 简单推送示例
$result = $client->push()
    ->setPlatform(array('ios', 'android'))
    ->setNotificationAlert('商品推送测试')
	->addRegistrationId('160a3797c8014d7e456')
    ->addAndroidNotification($body, '', 1, array("type"=>"Goods", "value"=>"667", "info"=>"商品推荐"))
    ->addIosNotification($body, 'iOS sound', JPush::DISABLE_BADGE, true, 'iOS category', array("type"=>"Goods", "value"=>"667", "info"=>"商品推荐"))
    ->setOptions(100000, 3600, null, false)
    ->send();

echo 'Result=' . json_encode($result);
die();

// 简单推送示例
$result = $client->push()
    ->setPlatform(array('ios', 'android'))
    ->setNotificationAlert('商品推送测试')
	->addAllAudience()
    ->addAndroidNotification('这是安卓，商品推送测试', '安卓独有的标题？', 1, array("type"=>"Goods", "value"=>"667", "info"=>"商品推荐"))
    ->addIosNotification("这是苹果，商品推送测试", 'iOS sound', JPush::DISABLE_BADGE, true, 'iOS category', array("type"=>"Goods", "value"=>"667", "info"=>"商品推荐"))
    ->setOptions(100000, 3600, null, false)
    ->send();

echo 'Result=' . json_encode($result) . $br;


// 简单推送示例
$result = $client->push()
	->setOptions(100000, 3600, null, false)
    ->setPlatform('ios')
	->addRegistrationId('13165ffa4e0c8852319')
    ->setNotificationAlert('这是一条其锋看不到的！！啦啦啦啦啦啦')
    ->send();

echo json_encode($result);
// 完整的推送示例,包含指定Platform,指定Alias,Tag,指定iOS,Android notification,指定Message等
$result = $client->push()
    ->setPlatform(array('ios', 'android'))
    ->addAlias('alias1')
    ->addTag(array('tag1', 'tag2'))
    ->setNotificationAlert('Hi, JPush')
    ->addAndroidNotification('Hi, android notification', 'notification title', 1, array("key1"=>"value1", "key2"=>"value2"))
    ->addIosNotification("Hi, iOS notification", 'iOS sound', JPush::DISABLE_BADGE, true, 'iOS category', array("key1"=>"value1", "key2"=>"value2"))
    ->setMessage("msg content", 'msg title', 'type', array("key1"=>"value1", "key2"=>"value2"))
    ->setOptions(100000, 3600, null, false)
    ->send();

echo 'Result=' . json_encode($result) . $br;


// 指定推送短信示例(推送未送达的情况下进行短信送达, 该功能需预付短信费用, 并调用Device API绑定设备与手机号)
$result = $client->push()
    ->setPlatform('all')
    ->addTag('tag1')
    ->setNotificationAlert("Hi, JPush SMS")
    ->setSmsMessage('Hi, JPush SMS', 60)
    ->send();

echo 'Result=' . json_encode($result) . $br;