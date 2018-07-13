<?php

/**
 * 会员接口
 *
 * @version v1.0
 * @create 2016-10-26
 * @author cyq
 */
require_once(ROOT_PATH . 'includes/lib_common.php');
require_once(ROOT_PATH . 'includes/cls_passport.php');
require_once(ROOT_PATH . 'includes/cls_user.php');
require_once(ROOT_PATH . 'includes/cls_cart.php');
/*
 *
 *
 *
{avatarUrl:"http://wx.qlogo.cn/mmopen/vi_32/xutZ8LVH3198zmeY5ElfPbWtR4UAup9TvSJDCSib3khR5YZeydafyfFGbRz11zoMNDDlB6EsFCbic3icuWc8XE4mw/0",city:"Jiangmen",country:"CN",gender:1,language:"zh_CN",nickName:"Yip",province:"Guangdong"}
*/


class PassportController extends ApiController
{
	public function __construct()
	{

		parent::__construct();
		$this->data = $this->input();
		$this->user     = cls_user::getInstance();
		$this->passport = cls_passport::getInstance();
		$this->cart     = cls_cart::getInstance();

		$config = array(
			'type'=>'file',
			'log_path'=> ROOT_PATH . '/data/logs/api/passport/'
		);
		$this->logger = new Logger($config);


		$this->user_table = 'users';
		$this->field_id = 'user_id';
		$this->ec_salt = 'ec_salt';
		$this->field_name = 'user_name';
		$this->field_pass = 'password';
		$this->field_email = 'email';
		$this->field_gender = 'sex';
		$this->field_bday = 'birthday';
		$this->field_reg_date = 'reg_time';
		$this->field_mobile_phone = 'mobile_phone';
		$this->field_email_validated = 'is_validated';
		$this->field_mobile_validated = 'validated';
		$this->need_sync = false;
		$this->is_ecshop = 1;

	}


	/**
	 * 微信小程序登录会员接口
	 * @param username string 登录用户名/手机号码
	 * @param password string 登录密码
	 * @param back_act string 返回链接 仅作用于手机版
	 * @param remember int 是否记住用户
	 * */
	public function xcxLogin(){
		$input = json_decode(file_get_contents("php://input"));
		/* 小程序获取openid */
		include_once ROOT_PATH . 'includes/encryptData/wxBizDataCrypt.php';

		$appid = 'wx272b0005bac843f3';
		$secret = '02d34546ea268a0bb7dd2efaec851f68';

		$gdata = json_decode(file_get_contents('https://api.weixin.qq.com/sns/jscode2session?appid='.$appid.'&secret='.$secret.'&js_code='.$input->code.'&grant_type=authorization_code'));

		if(isset($gdata->errcode)){
			$this->error('登录失败，错误内容：'.$gdata->errmsg . "--\n");
			exit;
		}

		$BizDataCrypt = new WXBizDataCrypt($appid, $gdata->session_key);
		$errCode = $BizDataCrypt->decryptData($input->encryptedData, $input->iv, $data );


		if ($errCode != 0) {
			$this->error('登录失败，错误码：'.$errCode . "\n");
			exit;
		}

		$data = json_decode($data,true);
		if($data['openId']){
			$data['openid'] = 'weixin_'.$data['openId'];
		}else{
			$this->error('缺少openid参数。');
		}

		$result = $this->passport->xcxLogin($data);

		if($result['code'] == 200){
			$this->success($result['data'],'登录成功');
		}else{
			$this->error('登录失败');
		}

	}

	/**
	 * 登录会员接口
	 * @param username string 登录用户名/手机号码
	 * @param password string 登录密码
	 * @param back_act string 返回链接 仅作用于手机版
	 * @param remember int 是否记住用户
	 * */
	public function act_login(){

		$require_fields = array('username','password');

		foreach($require_fields as $v)
		{
			if(!isset($this->data[$v])) // || empty($this->data[$v])
			{
				$this->error("缺失必选参数 ({$v})，请参考API文档", '-1');
			}
			else
			{
				if(strpos($v,'_id')){
					$$v = intval(trim($this->data[$v]));
				}else{
					$$v = stripslashes(trim($this->data[$v]));
				}
			}
		}

		$user_id = $this->passport->action_login($username, $password, isset($_POST['remember']));
		$back_act = '';
		if($user_id)
		{
			// 更新用户SESSION,COOKIE及登录时间、登录次数。
			$this->user->update_user_info($user_id);
			// 重新计算购物车中的商品价格：目的是当用户登录时享受会员价格，当用户退出登录时不享受会员价格
			// 获取用户信息
			$user_info = $this->user->get_user_rank($user_id);
			//$this->cart->recalculate_price($user_id,$user_info);

			$data = array('back_url'=>'');
			$data = array_merge($user_info,$data);

			$this->success($data,200,'登录成功');
		}
		else
		{

			$arr['rank_name'] = '';
			$arr['discount']  = '';
			$arr['user_rank'] = '';
			$arr['user_id']   = '0';

			$data = array('back_url'=>$back_act);

			$data = array_merge($arr,$data);

			$this->error('密码错误或不存在该账号',500,$data);
		}

	}

	/**
	 * 会员注册接口
	 * @param username string 登录用户名/手机号码
	 * @param password string 登录密码
	 * */
	public function act_register(){
		error_reporting(E_ALL || ~E_NOTICE);
		include_once(ROOT_PATH . 'includes/modules/integrates/' . $GLOBALS['_CFG']['integrate_code'] . '.php');
		include_once (ROOT_PATH . 'includes/lib_passport.php');
		/* 载入语言文件 */
		require(ROOT_PATH . 'languages/' . $GLOBALS['_CFG']['lang'] . '/user.php');
		// 获取全局变量
		$GLOBALS['_LANG'] = $_LANG;

		$GLOBALS['user'] = & init_users();

		$username = isset($_POST['username']) ? htmlspecialchars(trim($_POST['username'])) : '';
		$password = isset($this->data['password']) ? htmlspecialchars(trim($this->data['password'])) : '';
		$back_act = isset($this->data['back_act']) ? htmlspecialchars(trim($this->data['back_act'])) : '';

		if(strlen($password) < 6)
		{
			$this->error('密码不能少于6位字符');
		}
		if(strpos($password, ' ') > 0)
		{
			$this->error('密码不能为空');
		}

		/*====验证验证码==================================================================================*/
		$mobile_phone = ! empty($this->data['mobile_phone']) ? htmlspecialchars(trim($this->data['mobile_phone'])) : '';
		$mobile_code  = ! empty($this->data['mobile_code']) ? htmlspecialchars(trim($this->data['mobile_code'])) : '';

		if(!$mobile_phone)
		{
			$this->error('手机号不能为空');
		}
		if(!$mobile_code)
		{
			$this->error('验证码不能为空');
		}

		// 验证手机号码是否已经存在
		if(self::action_check_mobile_exist($mobile_phone)){
			$this->error('该手机号码已存在');
		}

		$validate_status = self::validate_verify_code($mobile_phone,$mobile_code);

		if($validate_status['code'] == 200){
			if (empty($username)) {
				/* 手机注册时，用户名默认为u+手机号 */
				$username = $this->passport->generate_username_by_mobile($mobile_phone);
			}
			if ($this->passport->check_username($username)) {
				$this->error('用户名已存在');
			}
			/* 手机注册 */
			$result = register_by_mobile($username, $password, $mobile_phone, array(), $this->data['device']?:'test');
			if($result['code'] == 500){
				$this->error($result['message'],500,new stdClass());
			}
			if($result)
			{
				//设置为app普通会员
				$this->user->update_user_app($_SESSION['user_id']);
				// 获取用户信息
				$user_info = $this->user->get_user_rank($_SESSION['user_id']);
				$data = array('back_url'=>$back_act);
				$data = array_merge($user_info,$data);

				$this->success($data);

			}
		}else{
			$this->error($validate_status['msg']);
		}
	}

	/**
	 * 修改手机号
	 */
	/*public function save_mobile() {
		$mobile_phone = ! empty($this->data['mobile_phone']) ? htmlspecialchars(trim($this->data['mobile_phone'])) : '';
		$mobile_code  = ! empty($this->data['mobile_code']) ? htmlspecialchars(trim($this->data['mobile_code'])) : '';

		if(!$mobile_phone)
		{
			$this->error('手机号不能为空');
		}
		if(!$mobile_code)
		{
			$this->error('验证码不能为空');
		}

		// 验证手机号码是否已经存在
		if(self::action_check_mobile_exist($mobile_phone)){
			$this->error('该手机号码已存在');
		}

		$validate_status = self::validate_verify_code($mobile_phone,$mobile_code);

		if($validate_status['code'] == 200){
			$sql = "UPDATE " . $this->table($this->user_table) . " SET " . $this->field_mobile_phone . " = '$mobile_phone WHERE user_id ='"
			$GLOBALS['db']->query($sql);
		}else{
			$this->error($validate_status['msg']);
		}
	}*/

	/**
	 * 密码重置
	 * @param $mobile_phone string 手机号码
	 * @param $mobile_code  string 手机验证码
	 * @return void
	 * */
	public function reset_password(){

		$mobile_phone = ! empty($this->data['mobile_phone']) ? htmlspecialchars(trim($this->data['mobile_phone'])) : '';
		$verify_code = ! empty($this->data['verify_code']) ? htmlspecialchars(trim($this->data['verify_code'])) : '';
		$password = isset($this->data['password']) ? htmlspecialchars(trim($this->data['password'])) : '';
		if(!$mobile_phone)
		{
			$this->error('手机号不能为空');
		}
		if(!$verify_code)
		{
			$this->error('验证码不能为空');
		}
		if(!$password)
		{
			$this->error('新密码不能为空');
		}

		if(md5($mobile_phone.'hunuo.com') != $verify_code){
			$this->error('非法操作');
		}

		$sql = "select user_id,user_name from " . $GLOBALS['ecs']->table('users') . " where mobile_phone='" . $mobile_phone . "'";
		$rows = $GLOBALS['db']->query($sql);

		while($row = $GLOBALS['db']->fetchRow($rows))
		{
			$user_id = $row['user_id'];
			$user_name = $row['user_name'];
		}

		if(!$user_id){
			$this->error('非法操作');
		}
		$result = $this->edit_user(array('username' => $user_name, 'password' => $password));
		if($result == false){
			$this->error('重置密码错误，请稍候再试');
		}
		$this->success('修改成功');
	}

	/**
	 * 密码重置
	 * @param $mobile_phone string 手机号码
	 * @param $mobile_code  string 手机验证码
	 * @return void
	 * */
	public function reset_password2(){

		$validate_type = ! empty($this->data['validate_type']) ? htmlspecialchars(trim($this->data['validate_type'])) : '';
		$mobile_phone = ! empty($this->data['mobile_phone']) ? htmlspecialchars(trim($this->data['mobile_phone'])) : '';
		$email = ! empty($this->data['email']) ? htmlspecialchars(trim($this->data['email'])) : '';
		$verify_code = ! empty($this->data['verify_code']) ? htmlspecialchars(trim($this->data['verify_code'])) : '';
		$password = isset($this->data['password']) ? htmlspecialchars(trim($this->data['password'])) : '';
		$confirm_password = isset($this->data['confirm_password']) ? htmlspecialchars(trim($this->data['confirm_password'])) : '';
		if ($validate_type == 'mobile_phone') {
			if(!$mobile_phone)
			{
				$this->error('手机号不能为空');
			}
		} elseif ($validate_type == 'email') {
			if(!$email)
			{
				$this->error('邮箱不能为空');
			}
		}
		if(!$verify_code)
		{
			$this->error('验证码不能为空');
		}
		if(!$password)
		{
			$this->error('新密码不能为空');
		}
		if($password != $confirm_password)
		{
			$this->error('两次输入的密码不一致，请重新输入');
		}

		if ($validate_type == 'mobile_phone') {
			if(md5($mobile_phone.'hunuo.com') != $verify_code){
				$this->error('非法操作');
			}
			$sql = "select user_id,user_name from " . $GLOBALS['ecs']->table('users') . " where mobile_phone='" . $mobile_phone . "'";
		} elseif ($validate_type == 'email') {
			if(md5($email.'hunuo.com') != $verify_code){
				$this->error('非法操作');
			}
			$sql = "select user_id,user_name from " . $GLOBALS['ecs']->table('users') . " where email='" . $email . "'";
		}
		$rows = $GLOBALS['db']->query($sql);

		while($row = $GLOBALS['db']->fetchRow($rows))
		{
			$user_id = $row['user_id'];
			$user_name = $row['user_name'];
		}

		if(!$user_id){
			$this->error('非法操作');
		}
		$result = $this->edit_user(array('username' => $user_name, 'password' => $password));
		if($result == false){
			$this->error('重置密码错误，请稍候再试');
		}
		$this->success('修改成功');
	}

	/**
	 * @description 更新密码
	 * @param integer user_id 用户ID
	 * @param string  modify  字段更新
	 */
	public function updateUserPWD(){
		$user_id = isset($this->data['user_id'])? $this->data['user_id'] : 0;
		$old_password  = !empty($this->data['old_password'])?trim($this->data['old_password']):'';
		$new_password  = !empty($this->data['new_password'])?trim($this->data['new_password']):'';
		if(empty($user_id)){
			$this->error('缺少用户ID参数！');
		}
		if(empty($old_password)){
			$this->error('请输入旧密码！');
		}
		if(empty($new_password)){
			$this->error('请输入新密码！');
		}

		if($new_password == $old_password){
			$this->error('新密码和旧密码一致！');
		}

		$user_name = $GLOBALS['db']->getOne("SELECT user_name FROM " . $this->table($this->user_table) . " WHERE user_id = '$user_id' ");


		if($this->passport->check_user($user_name, $old_password) == 0)
		{
			$this->error('旧密码错误');
		}

		$result = $this->edit_user(array(
			'username' => $user_name, 'password' => $new_password
		));

		if($result == false)
		{
			$this->error('更改密码失败，请重新尝试！');
		}
		else
		{
			$this->success('更改密码成功！');
		}

	}

	public function validate_phone(){
		$mobile_phone = ! empty($this->data['mobile_phone']) ? htmlspecialchars(trim($this->data['mobile_phone'])) : '';
		$mobile_code  = ! empty($this->data['mobile_code']) ? htmlspecialchars(trim($this->data['mobile_code'])) : '';

		if(!$mobile_phone)
		{
			$this->error('手机号不能为空');
		}
		if(!$mobile_code)
		{
			$this->error('验证码不能为空');
		}

		$validate_status = self::validate_verify_code($mobile_phone,$mobile_code);

		if($validate_status['code'] == 200){
			$validate = array('verify_code'=>md5($mobile_phone.'hunuo.com'));
			$this->success($validate);
			exit;
		}
		$this->error($validate_status['msg']);
	}

	public function validate_phone2(){
		$mobile_phone = ! empty($this->data['mobile_phone']) ? htmlspecialchars(trim($this->data['mobile_phone'])) : '';
		$mobile_code  = ! empty($this->data['mobile_code']) ? htmlspecialchars(trim($this->data['mobile_code'])) : '';

		if(!$mobile_phone)
		{
			$this->error('手机号不能为空');
		}
		if(!$mobile_code)
		{
			$this->error('验证码不能为空');
		}

		$validate_status = self::validate_verify_code($mobile_phone,$mobile_code);

		// 验证手机号码是否已经存在
		if(self::action_check_mobile_exist($mobile_phone)){
			$this->error('该手机号码已存在');
		}

		if($validate_status['code'] == 200){
			$validate = array('verify_code'=>md5($mobile_phone.'hunuo.com'));
			$this->success($validate);
			exit;
		}
		$this->error($validate_status['msg']);
	}

	/**
	 * 验证码校验
	 * @param $mobile_phone string 手机号码
	 * @param $mobile_code  string 手机验证码
	 * @return void
	 * */
	private function validate_verify_code($mobile_phone,$mobile_code){
		$res = self::get_php_file(ROOT_PATH.'data/payment/sms_code.php');
		$res = json_decode($res,true);
		//print_r($res);
		$data = array();

		foreach($res as $k=>$v){
			if(isset($v[0]) && $v[0] == $mobile_phone){
				if($v[2] < (time() - 600)){
					unset($res[$k]);
					$data['msg'] = '验证码已过期';
					$data['code'] = 500;
				}else{
					if($v[1] == $mobile_code){
						unset($res[$k]);
						$data['msg'] = '验证通过';
						$data['code'] = 200;
					}else{
						$data['msg'] = '验证码有误';
						$data['code'] = 500;
					}
				}

			}else{
				if($v[2] < (time() - 600)){
					unset($res[$k]);
				}
			}
		}
		$res = json_encode($res);
		self::set_php_file(ROOT_PATH.'data/payment/sms_code.php',$res);
		if(!isset($data['code'])){
			$data['msg'] = '请进行手机验证';
			$data['code'] = 500;
		}
		return $data;

	}

	/**
	 * 发送短信
	 * @param $mobile_phone string 手机号码
	 * @param $send_type string 发送类型 1为注册 2为找回密码 3为绑定手机 4为手机验证码快捷登录
	 * @return void
	 * */
	public function sendMessage(){
		/* 载入语言文件 */
		require_once (ROOT_PATH . 'languages/' . $GLOBALS['_CFG']['lang'] . '/user.php');
		require_once (ROOT_PATH . 'sms/sms.php');

		$mobile_phone = !empty($this->data['mobile_phone']) ? htmlspecialchars($this->data['mobile_phone']) : '';
		$send_type = !empty($this->data['send_type']) ? intval($this->data['send_type']) : 11;
		$send_message = !empty($this->data['send_message']) ? htmlspecialchars($this->data['send_message']) : '';

		if($send_type != 11){
			$seed = "0123456789";
			$mobile_code = self::mc_random(6, $seed);
			// $content = sprintf($_LANG['mobile_code_template'], $GLOBALS['_CFG']['shop_name'], $mobile_code, $GLOBALS['_CFG']['shop_name']);

			$res = self::get_php_file(ROOT_PATH.'data/payment/sms_code.php');
			$res = json_decode($res,true);
			foreach($res as $k=>$v){
				if($v[2] < (time() - 600)){
					unset($res[$k]);
				}
				if($v[0] == $mobile_phone && $v[3] == $send_type){
					if($v[2] > (time() - 600)){
						$this->error('已经请求过验证码，过十分钟再请求。');
						exit();
					}else{
						$res[$k][2] = time();
						$res = json_encode($res);
						try{
							$result = sendSMS($mobile_phone, $mobile_code);
						}catch(Exception $e) {
							$this->error('发送失败');
						}
						if($result){
							self::set_php_file(ROOT_PATH.'data/payment/sms_code.php',$res);
							$this->success('发送成功');
						}else{
							$this->error('发送失败');
						}
						exit();
					}
				}
			}
			$res1[0] = $mobile_phone;
			$res1[1] = $mobile_code;
			$res1[2] = time();
			$res1[3] = $send_type;
			$res[] = $res1;
			$res = json_encode($res);
			try{
				$result = sendSMS($mobile_phone, $mobile_code);
			}catch(Exception $e) {
				$this->error('发送失败');
			}
			//var_dump($result);
			if($result){
				self::set_php_file(ROOT_PATH.'data/payment/sms_code.php',$res);
				$this->success('发送成功');
			}else{
				$this->error('发送失败');
			}
			exit();
		}

		$res = self::get_php_file(ROOT_PATH.'data/payment/sms_content.php');
		$res = json_decode($res,true);
		$res[][0] = $mobile_phone;
		$res[][1] = $send_message;
		$res[][2] = time();
		$res[][3] = $send_type;
		$res = json_encode($res);
		try{
			$result = sendMessSMS($mobile_phone, $send_message);
		}catch(Exception $e) {
			$this->error('发送失败');
		}
		if($result){
			self::set_php_file(ROOT_PATH.'data/payment/sms_code.php',$res);
			$this->success('发送成功');
		}else{
			$this->error('发送失败');
		}
		exit();

	}

	// 验证邮箱验证码
	public function validate_email(){
		$email = ! empty($this->data['email']) ? htmlspecialchars(trim($this->data['email'])) : '';
		$email_code  = ! empty($this->data['email_code']) ? htmlspecialchars(trim($this->data['email_code'])) : '';

		if(!$email)
		{
			$this->error('邮箱不能为空');
		}
		if(!$email_code)
		{
			$this->error('验证码不能为空');
		}
		require_once (ROOT_PATH . 'includes/lib_passport.php');

		$result = validate_email_code($email, $email_code);

		if($result == 1)
		{
			$this->error('邮件地址不能为空');
		}
		else if($result == 2)
		{
			$this->error('邮件地址不合法');
		}
		else if($result == 3)
		{
			$this->error('邮箱验证码不能为空');
		}
		else if($result == 4)
		{
			$this->error('对不起，您输入的邮箱验证码不正确或已过期');
		}
		else if($result == 5)
		{
			$this->error('对不起，您输入的邮箱验证码不正确或已过期');
		} else {
			$validate = array('verify_code'=>md5($email.'hunuo.com'));
			$this->success($validate);
		}
	}

	function get_php_file($filename) {
		return trim(substr(file_get_contents($filename), 15));
	}
	function set_php_file($filename, $content) {
		file_put_contents($filename, "<?php exit();?>" . $content);
		/*$fp = fopen($filename, "w");
		fwrite($fp, "<?php exit();?>" . $content);
		fclose($fp);*/
	}

	/**
	 * 产生随机数
	 * @return  array
	 */
	function mc_random ($length, $char_str = 'abcdefghijklmnopqrstuvwxyz0123456789')
	{
		$hash = '';
		$chars = $char_str;
		$max = strlen($chars);
		for($i = 0; $i < $length; $i ++)
		{
			$hash .= substr($chars, (rand(0, 1000) % $max), 1);
		}
		return $hash;
	}

	/**
	 * 发短信
	 * @return  array
	 */
	function send_phone_origi($phone_id,$msg)
	{
		$msg = iconv('utf-8','gb2312',$msg);
		$URL = "http://sms3.mobset.com/SDK/Sms_Send.asp?CorpID=302981&LoginName=Admin&Passwd=Cr137232&send_no=".$phone_id."&Timer=&LongSms=1&msg=" .rawurlencode($msg);
		$tt  = self::https_request($URL);
		/*调用MSXML，发送请求*/
		$test = explode(',', $tt);
		if($test[0]==1){

			return true;
		}else{
			return false;
		}
		/*释放对象*/
	}


	function action_check_mobile_exist ($mobile_phone)
	{
		//$mobile = empty($_POST['mobile']) ? '' : $_POST['mobile'];
		if($this->passport->check_mobile_phone($mobile_phone))
		{
			return true;
		}
		else
		{
			return false;
		}
	}



	private function log($msg, $level = 'info')
	{
		$this->logger->writeLog($msg, $level, 'passport');
	}

	private function edit_user ($cfg)
	{
		if(empty($cfg['username']))
		{
			return false;
		}
		else
		{
			$cfg['post_username'] = $cfg['username'];
		}

		$values = array();
		if(! empty($cfg['password']) && empty($cfg['md5password']))
		{
			$cfg['md5password'] = md5($cfg['password']);
		}
		if((! empty($cfg['md5password'])) && $this->field_pass != 'NULL')
		{
			$values[] = $this->field_pass . "='" . $this->compile_password(array(
				'md5password' => $cfg['md5password'])) . "'";
			// 重置ec_salt、salt
			$values[] = "salt = 0";
			$values[] = "ec_salt = 0";
		}

		if((! empty($cfg['email'])) && $this->field_email != 'NULL')
		{
			/* 检查email是否重复 */
			$sql = "SELECT " . $this->field_id . " FROM " . $this->table($this->user_table) . " WHERE " . $this->field_email . " = '$cfg[email]' " . " AND " . $this->field_name . " != '$cfg[post_username]'";
			if($GLOBALS['db']->getOne($sql, true) > 0)
			{
				$this->error = ERR_EMAIL_EXISTS;

				return false;
			}

			$values[] = $this->field_email . "='" . $cfg['email'] . "'";

			if(isset($cfg['email_validated']) && ! empty($cfg['email_validated']))
			{
				if($cfg['email_validated'] != 1)
				{
					$cfg['email_validated'] = 0;
				}
				$values[] = $this->field_email_validated . "='" . $cfg['email_validated'] . "'";
			}
			else
			{

				// 检查是否为新E-mail
				$sql = "SELECT count(*)" . " FROM " . $this->table($this->user_table) . " WHERE " . $this->field_email . " = '$cfg[email]' ";
				if($GLOBALS['db']->getOne($sql, true) == 0)
				{
					// 新的E-mail
					$cfg['email_validated'] = 0;
				}

				$values[] = $this->field_email_validated . "='" . $cfg['email_validated'] . "'";
			}
		}

		// 手机号
		if((! empty($cfg['mobile_phone'])) && $this->field_mobile_phone != 'NULL')
		{
			/* 检查email是否重复 */
			$sql = "SELECT " . $this->field_id . " FROM " . $this->table($this->user_table) . " WHERE " . $this->field_mobile_phone . " = '$cfg[mobile_phone]' " . " AND " . $this->field_name . " != '$cfg[post_username]'";
			if($GLOBALS['db']->getOne($sql, true) > 0)
			{
				$this->error = ERR_MOBILE_PHONE_EXISTS;

				return false;
			}

			$values[] = $this->field_mobile_phone . "='" . $cfg[mobile_phone] . "'";

			if(isset($cfg['mobile_validated']) && ! empty($cfg['mobile_validated']))
			{
				if($cfg['mobile_validated'] != 1)
				{
					$cfg['mobile_validated'] = 0;
				}
				$values[] = $this->field_mobile_validated . "='" . $cfg['mobile_validated'] . "'";
			}
			else
			{
				// 检查是否为新Mobile
				$sql = "SELECT count(*)" . " FROM " . $this->table($this->user_table) . " WHERE " . $this->field_mobile_phone . " = '$cfg[mobile_phone]' ";
				if($GLOBALS['db']->getOne($sql, true) == 0)
				{
					// 新的Mobile
					$cfg['mobile_validated'] = 0;
				}
				$values[] = $this->field_mobile_validated . "='" . $cfg['mobile_validated'] . "'";
			}
		}

		if(isset($cfg['gender']) && $this->field_gender != 'NULL')
		{
			$values[] = $this->field_gender . "='" . $cfg['gender'] . "'";
		}

		if((! empty($cfg['bday'])) && $this->field_bday != 'NULL')
		{
			$values[] = $this->field_bday . "='" . $cfg['bday'] . "'";
		}

		if($values)
		{
			$sql = "UPDATE " . $this->table($this->user_table) . " SET " . implode(', ', $values) . " WHERE " . $this->field_name . "='" . $cfg['post_username'] . "' LIMIT 1";

			$GLOBALS['db']->query($sql);

			if($this->need_sync)
			{
				if(empty($cfg['md5password']))
				{
					$this->sync($cfg['username']);
				}
				else
				{
					$this->sync($cfg['username'], '', $cfg['md5password']);
				}
			}
		}

		return true;
	}

	private function compile_password ($cfg)
	{
		if(isset($cfg['password']))
		{
			$cfg['md5password'] = md5($cfg['password']);
		}
		if(empty($cfg['type']))
		{
			$cfg['type'] = PWD_MD5;
		}

		switch($cfg['type'])
		{
			case PWD_MD5:
				if(! empty($cfg['ec_salt']))
				{
					return md5($cfg['md5password'] . $cfg['ec_salt']);
				}
				else
				{
					return $cfg['md5password'];
				}

			case PWD_PRE_SALT:
				if(empty($cfg['salt']))
				{
					$cfg['salt'] = '';
				}

				return md5($cfg['salt'] . $cfg['md5password']);

			case PWD_SUF_SALT:
				if(empty($cfg['salt']))
				{
					$cfg['salt'] = '';
				}

				return md5($cfg['md5password'] . $cfg['salt']);

			default:
				return '';
		}
	}
	private function table ($str)
	{
		return $GLOBALS['ecs']->table($str);
	}



	/**
	 * @description 第三方登录
	 */
	public function thirdPartLogin(){
		error_reporting(E_ALL || ~E_NOTICE);
		$device = $this->input('device');

		if(!isset($device) && !in_array($device,$this->devices)){
			throw new ActivityException(1013);
			exit;
		}

		if($device == 'xcx'){
			$arr = $this->xcxLogin_code();
			if(isset($arr['unionid'])){
				$uid = $arr['unionid'];
			}else{
				$uid = isset($arr['openid'])?$arr['openid']:$this->error('小程序获取session_key失败');
			}
		}else{
			$uid = !empty($this->data['uid'])?$this->data['uid']:$this->error('必须传入第三方登录的唯一标识');
		}

		$type = !empty($this->data['type'])?$this->data['type']:$this->error('必须传入第三方登录的名称');

		$sql = "select * FROM " . $GLOBALS['ecs']->table('third_login') ." WHERE uid = '".$uid."' and type = '".$type."'";
		$user = $GLOBALS['db']->getRow($sql);
		if($user){
			if($device == 'xcx'){
				if(empty($user['openid'])){
					$GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('third_login'), array('openid'=>$arr['openid']), "UPDATE","uid = '".$uid."' and type = '".$type."'");
				}
			}
			$user_id = $user['user_id'];
			// 更新用户SESSION,COOKIE及登录时间、登录次数。
			$this->user->update_user_info($user_id);
			// 重新计算购物车中的商品价格：目的是当用户登录时享受会员价格，当用户退出登录时不享受会员价格
			// 获取用户信息
			$user_info = $this->user->get_user_rank($user_id);
			//$this->cart->recalculate_price($user_id,$user_info);

			$data = array('bind'=>1,'uid'=>$uid);
			$data = array_merge($user_info,$data);

			$this->success($data,200,'登录成功');
		}
		$data = array('bind'=>0,'uid'=>$uid);
		$this->success($data,200,'未绑定');
	}

	/**
	 * @description 第三方绑定 手机号
	 */
	public function thirdPartBind(){
		$uid = !empty($this->data['uid'])?$this->data['uid']:$this->error('必须传入第三方登录的唯一标识');
		$type = !empty($this->data['type'])?$this->data['type']:$this->error('必须传入第三方登录的名称');
		$phone = !empty($this->data['phone'])?$this->data['phone']:$this->error('必须传入需要绑定的手机号');
		$nickname = !empty($this->data['nickname'])?$this->data['nickname']:'';
		$headimg = !empty($this->data['headimg'])?$this->data['headimg']:'';

		$sql = "select * FROM " . $GLOBALS['ecs']->table('users') ." WHERE mobile_phone = '".$phone."'";
		$user = $GLOBALS['db']->getRow($sql);

		$device = $this->data['device'];

		if($device == 'xcx'){
			$arr = $this->xcxLogin_code();
			if(!isset($arr['openid'])){
				$this->success(array(),888,'确实openid');
			}
		}

		if($user){
			if($device == 'xcx'){
				if(empty($user['openid'])){
					$GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('third_login'), array('openid'=>$arr['openid']), "UPDATE","uid = '".$uid."' and type = '".$type."'");
				}
			}
			$user_id = $user['user_id'];
			// 更新用户SESSION,COOKIE及登录时间、登录次数。
			$this->user->update_user_info($user_id);
			// 重新计算购物车中的商品价格：目的是当用户登录时享受会员价格，当用户退出登录时不享受会员价格
			// 获取用户信息
			$user_info = $this->user->get_user_rank($user_id);
			//$this->cart->recalculate_price($user_id,$user_info);

			$data = array('back_url'=>'');
			$data = array('bind'=>0);
			$data = array_merge($user_info,$data);

			$field_values = array("type" => $type, "uid" => $uid, "user_id" => $user_id, "time" => time());
			$GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('third_login'), $field_values, "INSERT");

			$this->success($data,200,'绑定成功');
		}else{
			error_reporting(E_ALL || ~E_NOTICE);
			include_once(ROOT_PATH . 'includes/modules/integrates/' . $GLOBALS['_CFG']['integrate_code'] . '.php');
			include_once (ROOT_PATH . 'includes/lib_passport.php');
			/* 载入语言文件 */
			require(ROOT_PATH . 'languages/' . $GLOBALS['_CFG']['lang'] . '/user.php');
			// 获取全局变量
			$GLOBALS['_LANG'] = $_LANG;

			$GLOBALS['user'] = & init_users();

			/* 手机注册时，用户名默认为u+手机号 */
			$username = $this->passport->generate_username_by_mobile($phone);
			/* 手机注册 */
			$password = $this->generate_password(6);
			$result = register_by_mobile($username, $password, $phone, array(), $this->data['device']?:'test');
			if($result['code'] == 500){
				$this->error($result['message'],500,new stdClass());
			}
			if($result)
			{
				//设置为app普通会员
				$this->user->update_user_info($_SESSION['user_id']);
				// 获取用户信息
				$user_info = $this->user->get_user_rank($_SESSION['user_id']);
				$data = array('back_url'=>'');
				$data = array('bind'=>1);
				$data = array_merge($user_info,$data);


				$h_name = DATA_DIR . '/headimg/' . date('Ym') .'/'.time().rand().'.jpg';
				file_put_contents(ROOT_PATH.$h_name,file_get_contents($headimg));
				//$this->getImage($headimg,$d,$n);
				$sql = 'UPDATE ' . $GLOBALS['ecs']->table('users') . " SET `headimg`='$h_name'  WHERE `user_id`='".$_SESSION['user_id']."'";
				$GLOBALS['db']->query($sql);


				if($device == 'xcx'){
					$field_values = array("type" => $type, "uid" => $uid, "user_id" => $_SESSION['user_id'],"openid"=>$arr['openid'], "time" => time());
				}else{
					$field_values = array("type" => $type, "uid" => $uid, "user_id" => $_SESSION['user_id'], "time" => time());
				}
				$GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('third_login'), $field_values, "INSERT");

				$this->success($data,200,'绑定成功');

			}
		}
	}

	public function ttt(){
		//头像
		echo ROOT_PATH;
		$d =  ROOT_PATH.DATA_DIR . '/headimg/' . date('Ym').'/';
		$n =  time().rand().'.jpg';
		$h_name = $d .'/'.$n;
		//echo $headimg;
		$a = file_get_contents('https://wx.qlogo.cn/mmopen/vi_32/Q0j4TwGTfTJrkvBpiadicoTNbCibm6TZKU2SicyVnZ93c2xAkILdcNWTYepTUcq1A1EWubaq7zcBaCibYzOfaOM3YcA/0');
		file_put_contents($h_name,$a);
		//$this->getImage('https://wx.qlogo.cn/mmopen/vi_32/Q0j4TwGTfTJrkvBpiadicoTNbCibm6TZKU2SicyVnZ93c2xAkILdcNWTYepTUcq1A1EWubaq7zcBaCibYzOfaOM3YcA/0',$d,$n);
	}

	/**
	 * @description 小程序第三方登录
	 */
	public function xcxLogin_code(){

		$jsCode = !empty($this->data['uid'])?$this->data['uid']:$this->error('必须传入第三方登录的唯一标识');

		$appid = "wx272b0005bac843f3";
		$appsecret = "02d34546ea268a0bb7dd2efaec851f68";

		$url = "https://api.weixin.qq.com/sns/jscode2session?appid=".$appid."&secret=".$appsecret."&js_code=".$jsCode."&grant_type=authorization_code";
		//echo $url;
		$xcx = file_get_contents($url);
		//echo $xcx;
		return json_decode($xcx,true);
	}


	//生成随即密码
	private function generate_password( $length = 8 ) {
		// 密码字符集，可任意添加你需要的字符
		$chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()-_ []{}<>~`+=,.;:/?|';

		$password = '';
		for ( $i = 0; $i < $length; $i++ ) {
			// 这里提供两种字符获取方式
			// 第一种是使用 substr 截取$chars中的任意一位字符；
			// 第二种是取字符数组 $chars 的任意元素
			// $password .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
			$password .= $chars[ mt_rand(0, strlen($chars) - 1) ];
		}

		return $password;
	}

	//手机验证码快捷登录
	public function login_mobile_info(){
		$mobile_phone = ! empty($this->data['mobile_phone']) ? htmlspecialchars(trim($this->data['mobile_phone'])) : '';
		$mobile_code  = ! empty($this->data['mobile_code']) ? htmlspecialchars(trim($this->data['mobile_code'])) : '';

		if(!$mobile_phone)
		{
			$this->error('手机号不能为空');
		}
		if(!$mobile_code)
		{
			$this->error('验证码不能为空');
		}

		$validate_status = self::validate_verify_code($mobile_phone,$mobile_code);

		if($validate_status['code'] == 200){
			$sql = "SELECT user_id " . " FROM " . $this->table($this->user_table) . " WHERE mobile_phone = '$mobile_phone' ";
			$user_id = $GLOBALS['db']->getOne($sql);
			if($user_id){
				$user_info = $this->user->get_user_rank($user_id);
				$user_info['back_url'] = '';
				$this->success($user_info,200,'登录成功');
			}else{
				$arr = array();
				$arr['rank_name'] = '';
				$arr['discount']  = '';
				$arr['user_rank'] = '';
				$arr['user_id']   = '0';
				$this->error('不存在该账号',500,$arr);
			}

		}

		$this->error($validate_status['msg']);
	}

	//忘记密码 - 第一步 验证用户名/邮箱/已验证手机号
	public function forget_one() {
		$username = !empty($this->data['username']) ? htmlspecialchars(trim($this->data['username'])) : '';
		if(empty($username))
		{
			$this->error('请输入用户名/邮箱/已验证的手机号');
		}

		$username_exist = false;

		$sql = "select user_id from " . $this->table($this->user_table) . " where user_name = '".$username."'";
		$user_id = $GLOBALS['db']->getOne($sql);

		if($user_id)
		{
			// 用户名存在
			$username_exist = true;
		}

		// 判断是否邮箱
		if(is_email($username))
		{
			$sql = "select user_id from " . $this->table($this->user_table) . " where email='" . $username . "' ";
			$user_id = $GLOBALS['db']->getOne($sql);
			if($user_id)
			{
				// 用户名存在
				$username_exist = true;
			}
		}

		// 判断是否为手机号
		if(is_mobile_phone($username))
		{
			$sql = "select user_id from " . $this->table($this->user_table) . " where mobile_phone='" . $username . "'";
			$rows = $GLOBALS['db']->query($sql);

			$index = 0;
			while($row = $GLOBALS['db']->fetchRow($rows))
			{
				$user_id = $row['user_id'];
				$index = $index + 1;
			}
			if($index > 1)
			{
				$this->error('本网站有多个会员ID绑定了和您相同的手机号，请使用其他登录方式，如：邮箱或用户名');
			}
			else if($index == 1)
			{
				if($user_id)
				{
					// 用户名存在
					$username_exist = true;
				}
			}
		}

		// 检查用户名是否存在
		if(! $username_exist)
		{
			$this->error('您输入的账户名不存在，请核对后重新输入');
		}

		// 获取用户信息，判断用户是否验证了手机、邮箱
		$sql = "select user_id, user_name, email, mobile_phone from " . $this->table($this->user_table) . " where user_id = '" . $user_id . "'";

		$row = $GLOBALS['db']->getRow($sql);

		if($row == false)
		{
			$this->error('您输入的账户名不存在，请核对后重新输入');
		}

		$validate_types = array();

		if(isset($row['mobile_phone']) && ! empty($row['mobile_phone']))
		{
			// 处理手机号，不让前台显示
			$mobile_phone = $row['mobile_phone'];
			$mobile_phone = substr($mobile_phone, 0, 3) . '*****' . substr($mobile_phone, - 3);
			$validate_types[] = array(
				'type' => 'mobile_phone','name' => '已绑定手机','value' => $mobile_phone,'val' => $row['mobile_phone']
			);
		}
		if(isset($row['email']) && ! empty($row['email']))
		{
			$email = $row['email'];
			// 处理手机号，不让前台显示
			$email_head = substr($email, 0, strpos($email, '@'));
			$email_domain = substr($email, strpos($email, '@'));

			if(strlen($email_head) == 1)
			{
				$email = substr($email_head, 0, 1) . '*****' . $email_domain;
			}
			else if(strlen($email_head) <= 4)
			{
				$email = substr($email_head, 0, 1) . '*****' . substr($email_head, - 1) . $email_domain;
			}
			else if(strlen($email_head) <= 7)
			{
				$email = substr($email_head, 0, 2) . '*****' . substr($email_head, - 2) . $email_domain;
			}
			else
			{
				$email = substr($email_head, 0, 3) . '*****' . substr($email_head, - 3) . $email_domain;
			}
			$validate_types[] = array(
				'type' => 'email','name' => '已绑定邮箱','value' => $email,'val' => $row['email']
			);
		}

		$this->success($validate_types);
	}
}
