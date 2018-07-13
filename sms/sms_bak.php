<?php
error_reporting(0);
//session_start();

header("Content-type:text/html; charset=UTF-8");

function random ($length = 6, $numeric = 0)
{
	PHP_VERSION < '4.2.0' && mt_srand((double)microtime() * 1000000);
	if($numeric)
	{
		$hash = sprintf('%0' . $length . 'd', mt_rand(0, pow(10, $length) - 1));
	}
	else
	{
		$hash = '';
		$chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789abcdefghjkmnpqrstuvwxyz';
		$max = strlen($chars) - 1;
		for($i = 0; $i < $length; $i ++)
		{
			$hash .= $chars[mt_rand(0, $max)];
		}
	}
	return $hash;
}

function read_file ($file_name)
{
	$content = '';
	$filename = date('Ymd') . '/' . $file_name . '.log';
	if(function_exists('file_get_contents'))
	{
		@$content = file_get_contents($filename);
	}
	else
	{
		if(@$fp = fopen($filename, 'r'))
		{
			@$content = fread($fp, filesize($filename));
			@fclose($fp);
		}
	}
	$content = explode("\r\n",$content);
	return end($content);
}

if($_GET['act'] == 'check')
{
	$mobile = isset($_POST['mobile']) ? trim($_POST['mobile']) : '';
	$mobile_code = isset($_POST['mobile_code']) ? trim($_POST['mobile_code']) : '';

	if(time() - $_SESSION['time'] > 30 * 60)
	{
		unset($_SESSION['mobile_code']);
		exit(json_encode(array(
			'msg' => '验证码超过30分钟。'
		)));
	}
	else
	{
		if($mobile != $_SESSION['mobile'] or $mobile_code != $_SESSION['mobile_code'])
		{
			exit(json_encode(array(
				'msg' => '手机验证码输入错误。'
			)));
		}
		else
		{
			exit(json_encode(array(
				'code' => '2'
			)));
		}
	}

}

if($_GET['act'] == 'send')
{

	$mobile = isset($_POST['mobile']) ? trim($_POST['mobile']) : '';
	$mobile_code = isset($_POST['mobile_code']) ? trim($_POST['mobile_code']) : '';

	//session_start();
	if(empty($mobile))
	{
		exit(json_encode(array(
			'msg' => '手机号码不能为空'
		)));
	}

	$preg = '/^1[0-9]{10}$/'; // 简单的方法
	if(! preg_match($preg, $mobile))
	{
		exit(json_encode(array(
			'msg' => '手机号码格式不正确'
		)));
	}

	$mobile_code = random(6, 1);

	$content = sprintf($GLOBALS['_CFG']['sms_register_tpl'],$mobile_code,$GLOBALS['_CFG']['sms_sign']);


	if($_SESSION['mobile'])
	{
		if(strtotime(read_file($mobile)) > (time() - 60))
		{
			exit(json_encode(array(
				'msg' => '获取验证码太过频繁，一分钟之内只能获取一次。'
			)));
		}
	}

	$num = sendSMS($mobile, $content);
	if($num == true)
	{
		$_SESSION['mobile'] = $mobile;
		$_SESSION['mobile_code'] = $mobile_code;
		$_SESSION['time'] = time();
		exit(json_encode(array(
			'code' => 2
		)));
	}
	else
	{
		exit(json_encode(array(
			'msg' => '手机验证码发送失败。'
		)));
	}
}

function sendSMS ($mobile, $content, $time = '', $mid = '')
{return true;//本地暂时改为不发送短信通知
	$_POST['LoginName'] = $GLOBALS['_CFG']['ecsdxt_user_name']; // 用户账号
	$_POST['send_no']   = $mobile; // 手机号码
	$_POST['CorpID']    = $GLOBALS['_CFG']['sms_sign'];//企业ID
	$_POST['msg']       = $content;// 短信内容

	$strPasswd=$GLOBALS['_CFG']['ecsdxt_pass_word'];// 密码
	$strTimeStamp=GetTimeString();
	$strInput=$_POST['CorpID'].$strPasswd.$strTimeStamp;
	$strMd5=md5($strInput);

	$_POST['LoginName'] = iconv('utf-8', 'gbk', $_POST['LoginName']);
	$_POST['msg'] = iconv('utf-8', 'gbk', $_POST['msg']);

	$url = "http://sms3.mobset.com/SDK2/Sms_Send.asp?CorpID=".$_POST['CorpID']."&LoginName=".rawurlencode($_POST['LoginName'])."&TimeStamp=".$strTimeStamp."&Passwd=".$strMd5."&send_no=".$_POST['send_no']."&Timer=".$_POST['Timer']. "&LongSms=1&msg=" .rawurlencode($_POST['msg']);

	if(false)
	{
		$file_contents = @file_get_contents($url);
	}
	else
	{
		$ch = curl_init();
		$timeout = 5;
		curl_setopt ($ch, CURLOPT_URL, $url);
		curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
		$file_contents = curl_exec($ch);
		curl_close($ch);
	}


	$status = explode(',',$file_contents);
	$status_code = $status[0];
	if($status_code > 0){
		//echo '短信发送成功';
		return true;
	}else{
		error_log($file_contents,3,'sendsms.log');
		/*$data = array();
		$data['code'] = 500;
		$data['message'] = '短信发送失败,请联系管理员！';
		$data['data'] = new stdClass;
		echo json_encode($data);exit();*/
		//echo '短信发送失败'.$file_contents;
		//echo '短信发送失败'.$url;
		return false;
	}

}


function GetTimeString()
{
	date_default_timezone_set('Asia/Shanghai');
	$timestamp=time();
	$hours = date('H',$timestamp);
	$minutes = date('i',$timestamp);
	$seconds =date('s',$timestamp);
	$month = date('m',$timestamp);
	$day =  date('d',$timestamp);
	$stamp= $month.$day.$hours.$minutes.$seconds;
	return $stamp;
}

function postSMS ($url, $data = '')
{
	$row = parse_url($url);
	$host = $row['host'];
	$port = $row['port'] ? $row['port'] : 80;
	$file = $row['path'];
	while(list($k, $v) = each($data))
	{
		$post .= rawurlencode($k) . "=" . rawurlencode($v) . "&"; // 转URL标准码
	}
	$post = substr($post, 0, - 1);
	$len = strlen($post);
	$fp = @fsockopen($host, $port, $errno, $errstr, 10);
	if(! $fp)
	{
		return "$errstr ($errno)\n";
	}
	else
	{
		$receive = '';
		$out = "POST $file HTTP/1.1\r\n";
		$out .= "Host: $host\r\n";
		$out .= "Content-type: application/x-www-form-urlencoded\r\n";
		$out .= "Connection: Close\r\n";
		$out .= "Content-Length: $len\r\n\r\n";
		$out .= $post;
		fwrite($fp, $out);
		while(! feof($fp))
		{
			$receive .= fgets($fp, 128);
		}
		fclose($fp);
		$receive = explode("\r\n\r\n", $receive);
		unset($receive[0]);
		return implode("", $receive);
	}
}

function checkSMS ($mobile, $mobile_code)
{
	$arr = array(
		'error' => 0,'msg' => ''
	);
	if(time() - $_SESSION['time'] > 30 * 60)
	{
		unset($_SESSION['mobile_code']);
		$arr['error'] = 1;
		$arr['msg'] = '验证码超过30分钟。';
	}
	else
	{
		if($mobile != $_SESSION['mobile'] or $mobile_code != $_SESSION['mobile_code'])
		{
			$arr['error'] = 1;
			$arr['msg'] = '手机验证码输入错误。';
		}
		else
		{
			$arr['error'] = 2;
		}
	}
	return $arr;
}
?>
