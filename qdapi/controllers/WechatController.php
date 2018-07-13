<?php
class WechatController
{
	private static $app_id = 'wxfa672190f74c9c38';
	private static $app_secret = '3e58031737aeff7dd435ad0f8345ab43';
	private static $token = 'mama';
	private static $cs_url = 'http://ims.live800.com/im/wechat/UP5YoWsJyNVW';
	private static $original_id = 'gh_db95179a56fc';
	
	private function valid(){
		$echoStr = isset($_GET["echostr"]) ? trim($_GET["echostr"]) : '';
		if($this->checkSignature()){
			echo $echoStr;
		}
		exit;
	}
	
	public function callback(){
		//$this->valid();
		if(!$this->checkSignature()){
			throw new Exception('验证错误', 1003);
		}
		$xml = file_get_contents("php://input");
		if(empty($xml)){
			throw new Exception('错误请求', 1004);
		}
		
		$request = $this->xmlToArray($xml);
		//业务日志
		$log_config = array(
				'type'=>'file',
				'log_path'=> ROOT_PATH . 'data/logs/wx/',
		);
		$logger = new Logger($log_config);
		
		if($request['MsgType'] == 'event')
		{
			$json_data = json_encode($request);
			switch ($request['Event'])
			{
				case 'card_pass_check':
					$message = '';
					break;
				case 'card_not_pass_check':
					$message = '';
					break;
				case 'user_get_card':
					$redis = Mama_Cache::factory('redis');
					$get_card_key = "weixin_event_user_get_card_key";
					$tmp['CardId'] = addslashes($request['CardId']);
					$tmp['UserCardCode'] = addslashes($request['UserCardCode']);
					$tmp['FromUserName'] = addslashes($request['FromUserName']);
					$tmp['FriendUserName'] = addslashes($request['FriendUserName']);
					$tmp['CreateTime'] = addslashes($request['CreateTime']);
					$tmp['IsGiveByFriend'] = addslashes($request['IsGiveByFriend']);
					$tmp['OldUserCardCode'] = addslashes($request['OldUserCardCode']);
					$data = json_encode($tmp);
					$redis->lpush($get_card_key, $data);
					$message = '';
					break;
				case 'user_del_card':
// 					$wx_card_id = addslashes($request['CardId']);
// 					$openid = addslashes($request['FromUserName']);
					$wx_update_time = addslashes($request['CreateTime']);
					$card_code = addslashes($request['UserCardCode']);
					$update_time = time();
					$sql = "UPDATE ".$GLOBALS['ecs']->table('wx_card_code')." SET `status`='4', wx_update_time='{$wx_update_time}', ".
							" update_time='{$update_time}' WHERE card_code = '{$card_code}' ";
					$result = $GLOBALS['db']->query($sql);
					if(!$result)
					{
						$error_key = 'weixin_event_handle_error_key';
						$error = $GLOBALS['db']->error();
						$redis = Mama_Cache::factory('redis');
						$msg = "删除事件更新数据库失败：".$error.": ".json_encode($request);
						$redis->lpush($error_key, $msg);
					}
					$message = '';
					break;
				case 'SCAN':
					$message = '小主好，你终于关注我了~有任何售后的问题请直接咨询';
					break;
				case 'subscribe':
					$content = array();
					$content[] = array("Title"=>"小主戳我！我是你的小树熊购物小指南！", "Description"=>"感谢小主关注，等你好久了呢！从此就可以一起经历买买买的愉快人生了！在此之前，先来看看小编贴心整理的各种常见的购物问题吧！", "PicUrl"=>"http://7mnpba.com1.z0.glb.clouddn.com/images/201503/goods_img/147_P_1426675374337.jpg", "Url"=>"http://mp.weixin.qq.com/s?__biz=MjM5MDc0NTgwNw==&amp;mid=205776742&amp;idx=1&amp;sn=a93fd0f220aec5e5bba563338d55105a#rd");
					$this->transmitNews($request['FromUserName'],$content);
					break;
			}
		}
		else
		{
			$json_data = json_encode($request);
			$nowtime = time();
			$nonce = rand(10001,99999);
				
			//post到live800服务器
			$query_str = array(
					'timestamp'	=> $nowtime,
					'nonce' => $nonce,
					'echostr' => uniqid(),
					'signature' => $this->createlive800Sign(self::$token, $nowtime, $nonce)
			);
			$query_str = http_build_query($query_str);
			$this->_post(self::$cs_url.'?'.$query_str, $xml);
			$message = '';
		}
		$logger->writeLog('wx_event_callback:'.$json_data, 'info');
		
		echo $this->_getResponse($request['FromUserName'], $message);
	}
	
	public function getAccessToken(){
		$signature = isset($_GET["signature"]) ? trim($_GET["signature"]) : '';
		$timestamp = isset($_GET["timestamp"]) ? trim($_GET["timestamp"]) : '';
		$nonce = isset($_GET["nonce"]) ? trim($_GET["nonce"]) : '';
		$str = urlencode($nonce.$timestamp.self::$token);
		$str = md5($str);
		$str = strtoupper($str);
		if($str !== $signature){
			echo json_encode(array("errcode" => -1,"errmsg" => "error msg"));
			exit;
		}
		//获取access_token
		$token_info = $this->_getAccessToken(); 
		if(empty($token_info) || !isset($token_info['access_token'])){
			echo json_encode(array("errcode" => -2,"errmsg" => "get access_token error"));
			exit;
		}
		echo json_encode(array("access_token" => $token_info['access_token'],"expires_in" => $token_info['expires_in']-time()));
	}
	
	public function getQrcode(){
		//获取access_token
		$token_info = $this->_getAccessToken();
		if(empty($token_info) || !isset($token_info['access_token'])){
			die('获取access_token失败');
		}
		$get_ticket_url = 'https://api.weixin.qq.com/cgi-bin/qrcode/create?access_token='.$token_info['access_token'];
		$data = array('action_name' => 'QR_LIMIT_SCENE','action_info' => array('scene' => array('scene_id' => 131)));
		$result = $this->_post($get_ticket_url,json_encode($data));
		$ticket_info = json_decode($result, true);
		if(empty($ticket_info) || !isset($ticket_info['ticket'])){
			die('获取ticket失败');
		}
		$qrcode_url = 'https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket='.$ticket_info['ticket'];
		$result = $this->_get($qrcode_url);
		header('Content-type: image/jpg');
		echo $result;
	}
	
	private function checkSignature()
	{
		$signature = isset($_GET["signature"]) ? trim($_GET["signature"]) : '';
		$timestamp = isset($_GET["timestamp"]) ? trim($_GET["timestamp"]) : '';
		$nonce = isset($_GET["nonce"]) ? trim($_GET["nonce"]) : '';
		$tmpArr = array(self::$token, $timestamp, $nonce);
		sort($tmpArr, SORT_STRING);
		$tmpStr = implode($tmpArr);
		$tmpStr = sha1($tmpStr);
	
		if($tmpStr == $signature){
			return true;
		}else{
			return false;
		}
	}
	
	private function createlive800Sign($token,$timestamp,$nonce){
		$tmpArr = array($token, $timestamp, $nonce);
		sort($tmpArr, SORT_STRING);
		$tmpStr = implode($tmpArr);
		$tmpStr = sha1($tmpStr);
		return $tmpStr;
		//$str = urlencode($nonce.$timestamp.$token);
		//$str = md5($str);
		//$str = strtoupper($str);
		//return $str;
	}
	
	/**
	 * 产生随机字符串，不长于32位
	 */
	public function createNoncestr( $length = 32 )
	{
		$chars = "abcdefghijklmnopqrstuvwxyz0123456789";
		$str ="";
		for ( $i = 0; $i < $length; $i++ )  {
			$str.= substr($chars, mt_rand(0, strlen($chars)-1), 1);
		}
		return $str;
	}
	
	/**
	 * array转xml
	 */
	private function arrayToXml($arr)
	{
		$xml = "<xml>";
		foreach ($arr as $key=>$val)
		{
			if (is_numeric($val))
			{
				$xml.="<".$key.">".$val."</".$key.">";
	
			}
			else
				$xml.="<".$key."><![CDATA[".$val."]]></".$key.">";
		}
		$xml.="</xml>";
		return $xml;
	}
	
	/**
	 * 将xml转为array
	 */
	private function xmlToArray($xml)
	{
		$array_data = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
		return $array_data;
	}
	
	private function _getAccessToken(){
		$mc = Mama_Cache::factory('memcache');
		$key = 'xiaoshuxiong_wechat_access_token';
		$token_info = $mc->get($key);
		if(!$token_info){
			if($mc->add($key.'_mutex',1,false,5) == true){
				$app_id = self::$app_id;
				$app_secret = self::$app_secret;
				$access_token_url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid={$app_id}&secret={$app_secret}";
				$result = $this->_get($access_token_url);
				$token_info = json_decode($result, true);
				if(!empty($token_info) && isset($token_info['access_token'])){
					$token_info['expires_in'] = time()+$token_info['expires_in'];
					$mc->set($key, $token_info, false, 7190);
				}else{
					return false;
				}
			}else{
				$token_info = $mc->get($key);
			}
		}
		return $token_info;
	}
	
	private function _getResponse($openid, $message = ''){
		if(empty($message)){
			return '';
		}				
		$response = array(
			'ToUserName' => $openid,
			'FromUserName' => self::$original_id,
			'CreateTime' => time(),
			'MsgType' => 'text',
			'Content' => $message
 		);
 		return $this->arrayToXml($response);
	}
	
	/**
	 * 被动回复
	 * @param string $openid
	 * @param array $newsArray
	 */
	private function transmitNews($openid, $newsArray){
		if(!is_array($newsArray)){
			return;
		}
		$itemTpl = "    <item>
		<Title><![CDATA[%s]]></Title>
		<Description><![CDATA[%s]]></Description>
		<PicUrl><![CDATA[%s]]></PicUrl>
		<Url><![CDATA[%s]]></Url>
		</item>
		";
		$item_str = "";
		foreach ($newsArray as $item){
			$item_str .= sprintf($itemTpl, $item['Title'], $item['Description'], $item['PicUrl'], $item['Url']);
		}
		$xmlTpl = "<xml>
		<ToUserName><![CDATA[%s]]></ToUserName>
		<FromUserName><![CDATA[%s]]></FromUserName>
		<CreateTime>%s</CreateTime>
		<MsgType><![CDATA[news]]></MsgType>
		<ArticleCount>%s</ArticleCount>
		<Articles>
		$item_str</Articles>
		</xml>";
		echo sprintf($xmlTpl, $openid, self::$original_id, time(), count($newsArray));
		exit;
	}
	
	/**
	 * 模拟GET请求
	 */
	private function _get($url){
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);         //请求URL
		curl_setopt($ch, CURLOPT_HEADER, 0);         //过滤HTTP头
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);//SSL证书认证
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
		curl_setopt($ch, CURLOPT_TIMEOUT, 5);
		$rs = curl_exec($ch);
		if (curl_errno($ch)) {
			curl_close($ch);
			return false;
		} else {
			curl_close($ch);
			return $rs;
		}
	}
	
	/**
	 * 模拟POST请求
	 */
	private function _post($url, $data) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);         //请求URL
		curl_setopt($ch, CURLOPT_HEADER, 0);         //过滤HTTP头
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);//SSL证书认证
		curl_setopt($ch, CURLOPT_POST, 1);           //设置post提交
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data); //post传输数据
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
		curl_setopt($ch, CURLOPT_TIMEOUT, 5);
		$rs = curl_exec($ch);
		if (curl_errno($ch)) {
			curl_close($ch);
			return false;
		} else {
			curl_close($ch);
			return $rs;
		}
	}
}
