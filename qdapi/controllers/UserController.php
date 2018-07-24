<?php
//error_reporting(0);// ====================================================================================================================================================================================================================
/**
 * 会员接口
 *
 * @version v1.0
 * @create 2016-10-26
 * @author cyq
 */

require(ROOT_PATH . 'languages/zh_cn/user.php');
require_once(ROOT_PATH . 'includes/cls_base.php');
require_once(ROOT_PATH . 'includes/cls_user.php');
require_once(ROOT_PATH . 'includes/cls_order.php');
require_once(ROOT_PATH . 'includes/cls_back_order.php');
require_once(ROOT_PATH . 'includes/cls_common.php');
require_once(ROOT_PATH . 'includes/lib_transaction.php');
require_once(ROOT_PATH . 'includes/lib_clips.php');
require_once(ROOT_PATH . 'includes/lib_validate_record.php');

$GLOBALS['_LANG'] = $_LANG;


class UserController extends ApiController
{
	public function __construct()
	{
		parent::__construct();
		$this->data       = $this->input();
		$this->user       = cls_user::getInstance();
		$this->order      = cls_order::getInstance();
		$this->back_order = cls_back_order::getInstance();
		$this->common     = cls_common::getInstance();
		$config = array(
			'type'=>'file',
			'log_path'=> ROOT_PATH . '/data/logs/api/user/'
		);

		$this->_tb_collect_goods = $GLOBALS['ecs']->table('collect_goods');

		$this->logger = new Logger($config);
		$this->user_id = isset($this->data['user_id'])? $this->data['user_id'] : 0;
		$this->login_user_id = isset($this->data['login_user_id'])? $this->data['login_user_id'] : 0;
		// if(empty($this->user_id) || !isset($this->user_id)){
			// $this->error("缺失必选参数 (user_id)，请参考API文档");
		// }

		$user_rank_info = $this->user->get_user_rank($this->user_id);
		if($user_rank_info){
			$this->user_rank_info = $user_rank_info;
		}else{
			$this->error("该会员数据不存在或者参数错误");
		}

		$this->device = isset($this->data['device'])? $this->data['device'] : 'pc';//请求机型
	}


	/**
	 * @description 会员中心
	 * @param integer user_id 用户ID
	 * @param string  device  请求机型
	 * @return array user_info
	 */
	public function getUserInfo(){
        $user_info = $this->user->get_user_info($this->user_id,$this->device);
		$score = 0;
		if(!empty($user_info['email'])){
			$score += 25;
		}
		if(!empty($user_info['mobile_phone'])){
			$score += 25;
		}
		if($user_info['is_validate']===1){
			$score += 25;
		}
		if($user_info['validated']===1){
			$score += 25;
		}

		if($score<50){
			$account_rank = '低';
		}
		if($score>=50){
			$account_rank = '中';
		}
		if($score==100){
			$account_rank = '高';
		}

		$user_info['account_rank'] = $account_rank;
		$user_info['score'] = $score;

		$user_info['follow_status'] = get_follow_status($this->login_user_id, $this->user_id);

        $this->success($user_info);
	}

	/**
	 * 获取领域列表
	 */
	public function getFields(){
    $fields = $this->user->get_fields();
    $this->success($fields);
	}

	/**
	 * 获取常用领域列表
	 */
	public function getCommonFields(){
    $common_fields = $this->user->get_common_fields();
    $this->success($common_fields);
	}

	/**
	 * @description 更换头像
	 * @param integer user_id 用户ID
	 * @param string  headimg 用户头像图片
	 */
	public function updateHeadimg(){
		if($_FILES['headimg']['size'] == 0)
		{
			$this->error("您没有选择要修改的头像图片！");
		}
		$is_headimg = $this->user->update_Headimg($this->user_id,$_FILES['headimg']);

        if($is_headimg){
			$this->success('修改的头像成功');
		}else{
			$this->error('修改的头像失败');
		}
	}

	/**
	 * @description 上传头像
	 * @param integer user_id 用户ID
	 * @param string  headimg 用户头像图片
	 */
	public function saveHeadimg(){
		$result = $this->user->save_Headimg($this->user_id, $this->data['base64_image_content']);
        if($result){
			$this->success('修改头像成功');
		}else{
			$this->error('修改头像失败');
		}
	}

	/**
	 * 实名认证
	 */
	public function doRenzheng(){
		$result = $this->user->do_Renzheng($this->user_id, $this->data['card'], $this->data['real_name'], $this->data['mobile_phone']);
        if($result){
			$this->success('实名认证成功');
		}else{
			$this->error('实名认证失败');
		}
	}

	/**
	 * 发送邮箱绑定邮件
	 */
	public function sendBindEmail(){
		$result = $this->user->send_Bind_Email($this->user_id, $this->data['email']);
        if($result){
			$this->success('邮件已发送至邮箱内，请注意查收！');
		}else{
			$this->error('发送邮件失败');
		}
	}

	/**
	 * 邮箱绑定
	 */
	public function validEmail(){
		$result = $this->user->valid_Email($this->data['hash']);
        if($result){
			$this->success('邮箱绑定成功');
		}else{
			$this->error('验证邮件已失效，请重新验证！');
		}
	}

	/**
	 * 发送验证邮件
	 */
	public function sendValidEmail(){
		$result = $this->user->send_Valid_Email($this->user_id, $this->data['email']);
        if($result){
			$this->success('邮件已发送至邮箱内，请注意查收！');
		}else{
			$this->error('发送邮件失败');
		}
	}

	/**
	 * 发送验证邮件
	 */
	public function sendValidEmail2(){
		$result = $this->user->send_Valid_Email2($this->user_id, $this->data['email']);
        if($result){
			$this->success('邮件已发送至邮箱内，请注意查收！');
		}else{
			$this->error('发送邮件失败');
		}
	}

	/**
	 * 发送邮件验证码
	 */
	public function sendEmailCode(){
		$result = $this->user->send_Email_Code($this->user_id, $this->data['email']);
        if($result){
			$this->success('邮件已发送至邮箱内，请注意查收！');
		}else{
			$this->error('发送邮件失败');
		}
	}

	/**
	 * 发送修改邮件
	 */
	public function sendEditEmail(){
		$result = $this->user->send_Edit_Email($this->user_id, $this->data['email']);
        if($result){
			$this->success('邮件已发送至邮箱内，请注意查收！');
		}else{
			$this->error('发送邮件失败');
		}
	}

	/**
	 * 修改手机邮箱验证
	 */
	public function validEmail2(){
		$result = $this->user->valid_Email($this->data['hash']);
        if($result){
			$this->success('邮箱验证通过，正在跳转至手机修改页面...');
		}else{
			$this->error('验证邮件已失效，请重新验证！');
		}
	}

	/**
	 * 修改邮箱验证
	 */
	public function validEmail3(){
		$result = $this->user->valid_Email($this->data['hash']);
        if($result){
			$this->success('邮箱验证通过，正在跳转至邮箱修改页面...');
		}else{
			$this->error('验证邮件已失效，请重新验证！');
		}
	}

	/**
	 * 验证修改邮箱
	 */
	public function validEditEmail(){
		$result = $this->user->valid_Email($this->data['hash']);
        if($result){
			$this->success('邮箱修改成功！');
		}else{
			$this->error('验证邮件已失效，请重新验证！');
		}
	}

	/**
	 * @description 更换头像
	 * @param integer user_id 用户ID
	 * @param string  headimg 用户头像图片URL
	 */
	public function updateHeadUrl(){
		$headimg = $this->data['headimg'];
		$is_headimg = $this->user->update_HeadUrl($this->user_id,$headimg);

        if($is_headimg){
			$this->success('修改的头像成功');
		}else{
			$this->error('修改的头像失败');
		}
	}

	/**
	 * @description 更新个人资料
	 * @param integer user_id 用户ID
	 * @param string  modify  字段更新
	 */
	public function updateUserInfo(){
		$base_info = array('user_name','sex','birthday','real_name','card','country','province','city','district','address','mobile_phone','headimg','nickname','fields','weixin','wx_open','qq','qq_open','profile','weibo','facebook','instagram','website','sh_province','sh_city','sh_school');//允许更新的字段
		// 过滤不符合的传值
		$modify = '';
		foreach($this->data as $i=>$item){
			if(in_array($i, $base_info) && $item != ''){
				// 拼接sql语句
				$modify .= " $i='".htmlspecialchars(trim($item))."',";//防止用户输入javascript代码
			}
		}
		$fields = rtrim($modify, ',');//清除字符串结尾空格符
		if(empty($fields))
		{
			$this->error("提交数据不能为空！");
		}
		if(!empty($this->data['user_name'])){
			$has_userName = $GLOBALS['db']->getOne("SELECT user_name FROM " . $GLOBALS['ecs']->table('users') . " WHERE user_name = '".$this->data['user_name']."' and user_id != '".$this->user_id."' ");
			if(!empty($has_userName)){
				$this->error('用户名已存在，修改失败！');
				exit();
			}
		}

		if(empty($_FILES)){
			$is_ok = $this->user->update_UserInfo($this->user_id,$fields);
		}else{
			$is_ok = $this->user->update_UserInfo($this->user_id,$fields,$_FILES);
		}
        if($is_ok){
			$this->success('修改成功');
		}else{
			$this->error('修改失败');
		}
	}

	/**
	 * @description 获取用户的红包列表
	 * @param integer user_id 用户ID
	 * @param integer is_used 是否已使用 0 为全部  1为未使用 2为已使用
	 * @param integer page_size 每页数量
	 * @param integer page 第几页
	 * @return array user_info
	 */
	public function getUserBonus(){

		$page      = !empty($this->data['page'])?intval($this->data['page']):1;
		$is_used   = !empty($this->data['is_used'])?intval($this->data['is_used']):0;
		$page_size = !empty($this->data['page_size'])?intval($this->data['page_size']):10;
		$supplier_id = isset($this->data['supplier_id']) ?intval($this->data['supplier_id']):-1;

		$page_start = $page_size*($page-1);


		$bonus = get_user_bouns_list($this->user_id, $page_size, $page_start, $supplier_id , $is_used);
		$count = get_user_bouns_count($this->user_id, $supplier_id , $is_used);

		//分页
        $pager = array();
        $pager['page']         = $page;
        $pager['page_size']    = $page_size;
        $pager['record_count'] = $count;
        $pager['page_count']   = $page_count = ($count > 0) ? intval(ceil($count / $page_size)) : 1;

        $bonus_data['list'] = $bonus;
        $bonus_data['pager'] = $pager;

		$this->success($bonus_data);

	}

	//添加优惠券
	public function addBonus(){
		if(!$this->user_id){
			$this->error('请先登录！');
		}
		$user_id = $this->user_id;

		$bonus_sn = !empty($this->data['bonus_sn'])?trim($this->data['bonus_sn']):'';
		if(empty($bonus_sn)){
			$this->error('请输入优惠券序列号！');
		}


	    /* 查询优惠券序列号是否已经存在 */
	    $sql = "SELECT bonus_id, bonus_sn, user_id, bonus_type_id FROM " .$GLOBALS['ecs']->table('user_bonus') .
	           " WHERE bonus_sn = '$bonus_sn'";
	    $row = $GLOBALS['db']->getRow($sql);
	    if ($row)
	    {
	        if ($row['user_id'] == 0)
	        {
	            //优惠券没有被使用
	            $sql = "SELECT send_end_date, use_end_date ".
	                   " FROM " . $GLOBALS['ecs']->table('bonus_type') .
	                   " WHERE type_id = '" . $row['bonus_type_id'] . "'";

	            $bonus_time = $GLOBALS['db']->getRow($sql);

	            $now = gmtime();
	            if ($now > $bonus_time['use_end_date'])
	            {
	            	$this->error('该优惠券已经过了使用期！');
	                return false;
	            }

	            if ($now > $bonus_time['use_end_date'])
	            {
	            	$this->error('该优惠券已经过期，不能添加。');
	                return false;
	            }

	            $sql = "UPDATE " .$GLOBALS['ecs']->table('user_bonus') . " SET user_id = '$user_id' ".
	                   "WHERE bonus_id = '$row[bonus_id]'";
	            $result = $GLOBALS['db'] ->query($sql);
	            if ($result)
	            {
	            	$this->success('添加优惠券成功');
	                return true;
	            }
	            else
	            {
	            	$this->error('该优惠券已经过期，不能添加。');
	                return $GLOBALS['db']->errorMsg();
	            }
	        }
	        else
	        {
	            if ($row['user_id']== $user_id)
	            {
	                //优惠券已经添加过了。
	                $this->error('你输入的优惠券你已经领取过了！');
	                return false;
	            }
	            else
	            {
	                //优惠券被其他人使用过了。
	                $this->error('你输入的优惠券已经被其他人领取！');
	                return false;
	            }

	            return false;
	        }
	    }
	    else
	    {
	        //优惠券不存在
	        $this->error('你输入的优惠券不存在');
	        return false;
	    }
	}

	/**
	 * @description 获取用户的收藏商品列表
	 * @param integer user_id 用户ID
	 * @param integer page 第几页
	 * @param integer page_size 每页数量
	 * @return array user_info
	 */
	public function getUserCollect(){

		$page = !empty($this->data['page']) ? intval($this->data['page']) : 1;
		$page_size = !empty($this->data['page_size']) ? intval($this->data['page_size']) : 10;
		$is_real = isset($this->data['is_real']) ? intval($this->data['is_real']) : 1;//0虚拟商品 1真实商品 3虚拟商品和真实商品共存在
		$cat_id = intval($this->data['cat_id']) ? intval($this->data['cat_id']) : 0;
		$type = intval($this->data['type']) ? intval($this->data['type']) : 1;
		$ext = '';
		switch ($type) {
			case 1:
				if($is_real == 0){
					$ext .= ' AND g.is_real = 0';
				}
				if($is_real == 1){
					$ext .= ' AND g.is_real = 1';
				}
				if($is_real == 3){//虚拟商品和真实商品
					$ext .= ' AND (g.is_real = 0 or g.is_real =1) ';
				}
				if($cat_id){
					$ext .= ' AND g.cat_id = ' . $cat_id;
				}
				break;
			case 2:
				$ext .= ' AND di.info_id IS NOT NULL';
				if($cat_id){
					$ext .= ' AND di.type = ' . $cat_id;
				}
				break;
		}

		$page_start = $page_size*($page-1);
		$collect = $this->user->get_collection_goods($this->user_rank_info, $page_size, $page_start, $ext, $type);
		$count = $this->user->get_collection_count($this->user_rank_info, $ext, $type);
		//分页
    $pager = array();
    $pager['page']         = $page;
    $pager['page_size']    = $page_size;
    $pager['record_count'] = $count;
    $pager['page_count']   = $page_count = ($count > 0) ? intval(ceil($count / $page_size)) : 1;

    $collect_data['list'] = $collect;
    $collect_data['cat_list'] = $this->user->get_collection_cat($this->user_rank_info, $ext, $type);
    $collect_data['pager'] = $pager;
    $collect_data['count']['goods_count'] = $this->user->get_collection_count($this->user_rank_info, ' AND g.is_real = 1', 1);
    $collect_data['count']['diy_count'] = $this->user->get_collection_count($this->user_rank_info, ' AND di.info_id IS NOT NULL', 2);
		$this->success($collect_data);
	}

	/**
	 * @description 用户添加收藏商品
	 * @param integer user_id 用户ID
	 * @param string goods_id 商品ID
	 * @param string str 插入表对应字段
	 */
	public function addCollect(){
		$goods_id = !empty($this->data['goods_id'])?intval($this->data['goods_id']):0;
		$str = !empty($this->data['str']) ? trim($this->data['str']) : 'goods_id';
		if(!$this->user_id){
			$this->error('请先登录！');
		}
		if(empty($goods_id) || empty($str)){
			$this->error('操作失败，缺少商品参数！');
		}
		$result = $this->user->add_collection($goods_id,$this->user_id,$str);
		if($result['status'] == 200){
			unset($result['status']);
			$this->success($result);
		}else{
			unset($result['status']);
			$this->error($result);
		}
	}

	/**
	 * @description 删除用户的某个收藏商品
	 * @param integer user_id 用户ID
	 * @param string collection_id 收藏ID
	 */
	public function dropUserCollect(){
		$id = !empty($this->data['id']) ? json_decode($this->data['id']) : array();
		if($this->user->delete_collection($id, $this->user_id)){
			$this->success('删除成功');
		}else{
			$this->error('删除失败');
		}
	}

	/**
	 * @description 获取用户的收藏店铺列表
	 * @param integer user_id 用户ID
	 * @param integer page 第几页
	 * @param integer page_size 每页数量
	 */
	public function getShopCollect(){

		$page      = !empty($this->data['page'])?intval($this->data['page']):1;
		$page_size = !empty($this->data['page_size'])?intval($this->data['page_size']):10;

		$page_start = $page_size*($page-1);
		$collect = $this->user->get_collection_shop($this->user_id, $page_size, $page_start);
		$count = $this->user->get_collection_shop_count($this->user_id);
		//分页
        $pager = array();
        $pager['page']         = $page;
        $pager['page_size']    = $page_size;
        $pager['record_count'] = $count;
        $pager['page_count']   = $page_count = ($count > 0) ? intval(ceil($count / $page_size)) : 1;

        $collect_data['list'] = $collect;
        $collect_data['pager'] = $pager;
		$this->success($collect_data);
	}

	/**
	 * @description 删除用户的某个收藏店铺
	 * @param integer user_id 用户ID
	 * @param string collection_id 收藏ID
	 */
	public function dropShopCollect(){
		$collection_id = !empty($this->data['collection_id'])?trim($this->data['collection_id']):0;
		if($this->user->delete_shop_collection($collection_id,$this->user_id)){
			$this->success('删除成功');
		}else{
			$this->error('删除失败');
		}
	}



	/**
	 * @description 会员积分中心界面
	 * @param integer user_id 用户ID
	 * @param integer page 第几页
	 * @param integer log_type 列表类型 1为增加 2为减少
	 * @param string  $account_type   消费类型     user_money 消费金额     pay_points 消费积分
	 * @param integer page_size 每页数量
	 * @return array user_info
	 */
	public function getUserIntegral ()
	{
		$page      = !empty($this->data['page'])?intval($this->data['page']):1;
		$log_type  = !empty($this->data['log_type'])?intval($this->data['log_type']):0;
		$page_size = !empty($this->data['page_size'])?intval($this->data['page_size']):10;
		$account_type = !empty($this->data['account_type'])?trim($this->data['account_type']):'user_money';

		$page_start = $page_size*($page-1);
		$account_log = $this->user->action_account_points($this->user_id, $log_type, $account_type, $page_size, $page_start);
		$count = $this->user->action_account_points_count($this->user_id, $log_type, $account_type);

		//分页
        $pager = array();
        $pager['page']         = $page;
        $pager['page_size']    = $page_size;
        $pager['record_count'] = $count;
        $pager['page_count']   = $page_count = ($count > 0) ? intval(ceil($count / $page_size)) : 1;

        $user_info = $this->user->get_user_info($this->user_id,$this->device);
        $account_log_data['integral'] = $user_info['integral'];//我的总积分
        $account_log_data['surplus'] = $user_info['surplus'];//我的总余额

        $account_log_data['list'] = $account_log;
        $account_log_data['pager'] = $pager;
		$this->success($account_log_data);
	}


	/**
	 * @description 会员我的订单
	 * @param integer user_id 用户ID
	 * @param integer page 第几页
	 * @param integer page_size 每页数量
	 * @return array user_info
	 */
	public function getUserOrder ()
	{
		$page      = !empty($this->data['page'])?intval($this->data['page']):1;
		$start_data= !empty($this->data['start_data'])?intval($this->data['start_data']):'';
		$end_data  = !empty($this->data['end_data'])?intval($this->data['end_data']):'';
		$page_size = !empty($this->data['page_size'])?intval($this->data['page_size']):10;
		$status = !empty($this->data['status'])?intval($this->data['status']):0;//订单处理状态
		$extension_code = !empty($this->data['extension_code'])?trim($this->data['extension_code']):'';//订单类型

		$page_start = $page_size*($page-1);
		$order_list = $this->user->order_list2($this->user_id, $start_data, $end_data, $page_size, $page_start,$status,$extension_code);

		$device = $this->input('device')?:$this->error('请求机型不能为空');
		//支付信息
		require_once('PaymentController.php');
		$payment = new PaymentController();
		if($device=='ios' || $device == 'android'){
			$device_where = " pay_code IN ('APP','QUICK_MSECURITY_PAY') ";
		}
		if($device=='wap' ){
			$device_where = " pay_code IN ('QUICK_WAP_WAY','JSAPI','MWEB') ";
		}
		if($device=='pc'){
			$device_where = " pay_code IN ('FAST_INSTANT_TRADE_PAY','NATIVE') ";
		}
		if($device=='xcx'){
			$device_where = " pay_code IN ('XCX') ";
		}
		foreach($order_list['order_list'] as $k=>$v){
			$device_wherea = $device_where." AND  pay_id = ".$v['pay_id'];
			$pay_result = $payment->get_pay_list($device_wherea,1);
			$order_list['order_list'][$k]['payment'] = current($pay_result);

			/*添加订单类型显示 2018.02.01*/
	        //$order_list['order_list'][$k]['order_type_status'] = '';
	        $order_list['order_list'][$k]['group_id'] = 0;
	        if($v['extension_code'] == ''){
	            $order_type_name = '普通订单';
	        }
	        if($v['extension_code'] == 'virtual_good'){
	            $order_type_name = '服务订单';
	        }
	        if($v['extension_code'] == 'exchange_goods'){
	            $order_type_name = '积分订单';
	        }
	        if($v['extension_code'] == 'bargain'){
	            $order_type_name = '砍价订单';
	        }
	        if($v['extension_code'] == 'group'){
	            $order_type_name = '拼团订单';
	            /*$is_finish = $GLOBALS['db']->getOne("SELECT is_finish FROM " . $GLOBALS['ecs']->table('group_log') . " WHERE order_id = '$v[order_id]' ");
	            switch ($is_finish) {
	                case '0':
	                    $order_list['order_list'][$k]['order_type_status'] = '<font color="green">进行中</font>';
	                    break;
	                case '1':
	                    $order_list['order_list'][$k]['order_type_status'] = '<font color="green">已完成</font>';
	                    break;
	                case '2':
	                    $order_list['order_list'][$k]['order_type_status'] = '<font color="red">拼团失败</font>';
	                    break;
	                default:
	                    # code...
	                    break;
	            }*/
	            $group_id = $GLOBALS['db']->getOne("SELECT group_id FROM " . $GLOBALS['ecs']->table('group_log') . " WHERE order_id = '$v[order_id]' ");
	            $order_list['order_list'][$k]['group_id'] = $group_id;
	        }
	        $order_list['order_list'][$k]['order_type_name'] = $order_type_name;//订单类型名称
		}

		//分页
		$count = $order_list['record_count'];
		unset($order_list['record_count']);
        $pager = array();
        $pager['page']         = $page;
        $pager['page_size']    = $page_size;
        $pager['record_count'] = $count;
        $pager['page_count']   = $page_count = ($count > 0) ? intval(ceil($count / $page_size)) : 1;

        $order_data['pager'] = $pager;
        $order_data['order_list'] = array_values($order_list['order_list']);
		$this->success($order_data);
	}


	/**
	 * @description 会员我的订单详情页
	 * @param integer user_id 用户ID
	 * @param integer order_id 订单ID
	 */
	public function getUserOrderDetail ()
	{

		$order_id   = !empty($this->data['order_id'])?intval($this->data['order_id']):0;
		if ($order_id <= 0)
        {
            $this->error('订单错误！');
            return false;
        }
		$orders = $this->user->order_detail($this->user_id, $order_id);//$this->success($orders);
		if (empty($orders))
        {
            $this->error('订单不存在！');
            return false;
        }

		//自提信息
		$ziti['pickup_cn']=$orders['pickup_cn']; //提货码

		/* 取自提点数据 */
		$sql = "SELECT pp.shop_name, pp.address, pp.phone, pp.contact, p.region_name as province_name, c.region_name as city_name, d.region_name as district_name " .
	            "FROM " . $GLOBALS['ecs']->table('pickup_point') . " AS pp " .
	                "LEFT JOIN " . $GLOBALS['ecs']->table('region') . " AS p ON pp.province_id = p.region_id " .
	                "LEFT JOIN " . $GLOBALS['ecs']->table('region') . " AS c ON pp.city_id = c.region_id " .
	                "LEFT JOIN " . $GLOBALS['ecs']->table('region') . " AS d ON pp.district_id = d.region_id " .
	            "WHERE pp.id = '" . $orders['pickup_point'] . "'";
	    $pickup_point = $GLOBALS['db']->GetRow($sql);
	    if($pickup_point){
	    	//$ziti['shop_name']=$pickup_point['shop_name'];
	    	$ziti['contact']=$pickup_point['contact'];
	    	$ziti['phone']=$pickup_point['phone'];
	    	$ziti['address']=$pickup_point['province_name'].$pickup_point['city_name'].$pickup_point['district_name'].$pickup_point['address'];
	    }
	    else{
	    	//$ziti['shop_name']='';
	    	$ziti['contact']='';
	    	$ziti['phone']='';
	    	$ziti['address']='';
	    }

	    $orders['shipping_ziti']=$ziti;

		//评价
		$min_time = local_strtotime(local_date('Y-m-d H:i:s', strtotime('-'.$GLOBALS['_CFG']['comment_youxiaoqi'].' days'))); //则自确认收货起$GLOBALS['_CFG']['comment_youxiaoqi']天内买家可以评价、晒单
        if($orders['shipping_time_end'] <= $min_time){
        	$evaluate_ed=0;
        }
        else{
        	$evaluate_ed=1;
        }

        $goods_num = 0;//商品总数

		$goods_list = $this->user->order_goods($order_id);//print_r($goods_list);die;

        $shaidan_comment = 0;
        //定义
		$back_can = 0;//退款   0不可以   1可以
		$back_can_num = 0;
		$can_goods_do=0;//退货   0不可以   1可以
		$can_goods_do_num = 0;
		$can_evaluate=0; //评价 0不可以   1可以
		foreach($goods_list as $goods_key => $goods_val)
		{
			$goods_list[$goods_key]['can_goods_do'] = 0;//0 无操作

			if($goods_val['comment_state'] == 0  && $goods_val['is_back'] == 0 && $orders['order_status'] == 5 && $orders['pay_status'] == 2 && $orders['shipping_status'] == 2 && $evaluate_ed==1){
				$goods_list[$goods_key]['can_evaluate']=1;//商品可评价
				$can_evaluate++;
			}
			elseif($goods_val['comment_state']==1){
				$goods_list[$goods_key]['can_evaluate']=2;//商品已评价
				$can_evaluate=0;
			}
			else{
				$goods_list[$goods_key]['can_evaluate']=0;//商品不可评价
				$can_evaluate=0;
			}



			if($goods_val['comment_state'] == 0 && $goods_val['shaidan_state'] == 0 && $goods_val['is_back'] == 0 && $shaidan_comment == 0){
				if($orders['order_status'] == 5 && $orders['pay_status'] == 2 && $orders['shipping_status'] == 2){
					$orders['can_do'] = CAN_COMMENT;//6 可评论
				}

				//$shaidan_comment = $goods_val['rec_id'];
			}

			$goods_num += $goods_val['goods_number'];





            //$min_time = local_strtotime(local_date('Y-m-d H:i:s', strtotime('-'.$GLOBALS['_CFG']['shouhou_time'].' days')));//则自确认收货起$GLOBALS['_CFG']['shouhou_time']天内买家可以退货
            $min_time = local_strtotime(local_date('Y-m-d H:i:s', strtotime('-7 days')));//则自确认收货起$GLOBALS['_CFG']['shouhou_time']天内买家可以退货   先默认7天，还没设置后台设置
            //可进行申请售后的商品
	        if($orders['order_status']==5 && $orders['shipping_status']==2 && $orders['pay_status']==2 && $orders['shipping_time_end']>$min_time && $orders['extension_code']!= 'pre_sale' && $orders['extension_code']!= 'virtual_good' && $goods_val['back_can']==1){
		    	$goods_list[$goods_key]['can_goods_do'] = 1;
	            $can_goods_do_num++;
		    }
		    else{
		    	$goods_list[$goods_key]['can_goods_do'] = 0;
		    	$can_goods_do_num=0;
		    }

            //可进行退款的商品
	        if(in_array($orders['order_status'],array(1,5)) && in_array($orders['shipping_status'],array(0,1,3,5))  && $orders['pay_status']==2 && $goods_val['back_can']==1){
		    	$goods_list[$goods_key]['back_can'] = 1;
	            $back_can_num++;
		    }elseif($orders['order_status']==5 && $orders['shipping_status']==1  && $orders['pay_status']==2 && $orders['extension_code']!='virtual_good'  && $goods_val['back_can']==1){
		    	$goods_list[$goods_key]['back_can'] = 1;
	            $back_can_num++;
		    }
	        else{
	        	$goods_list[$goods_key]['back_can'] = 0;
	        	$back_can_num=0;
	        }
		}

		if($can_evaluate>0){
        	$can_evaluate=1;//可评价
        }
        $orders['can_evaluate']=$can_evaluate;

        if($back_can_num>0){
        	$back_can=1;//可退款
        }
		$orders['back_can']=$back_can;

		if($can_goods_do_num>0){
        	$can_goods_do=1;//可退款
        }
		$orders['can_goods_do']=$can_goods_do;

		$sql_goods = "select bo.back_type from ". $GLOBALS['ecs']->table('back_order') . " as bo " .
	        " where bo.order_id='$orders[order_id]'  " .
	        "  and bo.status_back < 6 order by bo.back_id desc";
	    $back_order =$GLOBALS['db']->getRow($sql_goods);

	    $bt = '';
	    if($back_order){
	        switch ($back_order['back_type'])
	        {
	            case '1' : $bt = "退货"; break;
	            case '3' : $bt = "申请维修"; break;
	            case '4' : $bt = "退款"; break;
	            default : break;
	        }
	    }

	    $shouhou = $bt;

        $back_type_name="";
	    if(in_array($orders['order_status'],array(1,5)) && in_array($orders['shipping_status'],array(0,1,3,5))  && $orders['pay_status']==2 && $orders['back_can']==0){
            $back_type_name=$shouhou;
        }
        elseif($orders['order_status']==5 && $orders['shipping_status']==1  && $orders['pay_status']==2 && $orders['extension_code']!='virtual_good'  && $orders['back_can']==0){
	    	$back_type_name=$shouhou;
	    }
        elseif($orders['order_status']==5 && $orders['shipping_status']==2 && $orders['pay_status']==2 &&  $orders['extension_code']!= 'pre_sale' && $orders['extension_code']!= 'virtual_good' && $orders['extension_code']!= 'virtual_good' && $orders['back_can']==0){
            $back_type_name=$shouhou;
        }

        $orders['back_type_name']=$back_type_name;


        $orders['can_do'] = CAN_NOT;//0 无操作
		if ($orders['order_status'] == OS_UNCONFIRMED)
		{
			// 未确认状态可以取消
			$orders['can_do'] = CAN_CANCEL;//2 可取消
		}
		else if ($orders['order_status'] == OS_SPLITED)
		{
			// 已发货可确认收货
			/* 对配送状态的处理 */
			if ($orders['shipping_status'] == SS_SHIPPED)
			{
				$orders['can_do'] = CAN_RECEIVED;//5 可确认收货
			}
			else
			{
				if ($orders['pay_status'] == PS_UNPAYED)
				{
					// 未付款可支付
					$orders['can_do'] = CAN_PAY;//1 可支付
				}

			}
		}

		if ($orders['pay_status'] == 0)
		{
			$orders['can_do'] = CAN_PAY;//1 可支付
		}

		if ($orders['order_status'] == 3)
		{
			$orders['can_do'] = CAN_NOT;//0 无操作
		}

        $orders['status'] = returnOrderStatus($orders['order_status'],$orders['pay_status'],$orders['shipping_status']);

        $orders['status_name'] = returnOrderStatusName($orders);

        $orders['flow_type']=returnOrderType($orders['extension_code']);

        if($orders['status'] == 6){
            $orders['can_do'] = 7;//用户可删除订单（前端不显示）
        }


        $orders['order_handle']=returnOrderHandle($orders); //订单操作

		//挑选返回有用的信息
		$order_info = array();
		$order_info['supplier_id'] = $orders['supplier_id'];//店铺ID
		$order_info['referer'] = $orders['referer'];//商品来源（店铺名）
		$order_info['order_id'] = $orders['order_id'];//订单ID
		$order_info['order_sn'] = $orders['order_sn'];//订单号
		$order_info['status'] = $orders['status'];//订单状态
		$order_info['status_name'] = $orders['status_name'];//订单状态名称
		$order_info['order_handle'] = $orders['order_handle'];//订单操作
		$order_info['flow_type'] = $orders['flow_type'];//所属订单
		$order_info['add_time'] = local_date("Y-m-d H:i", $orders['add_time']);//下单时间
		$order_info['shipping_time_end'] = $orders['shipping_time_end'] ? local_date("Y-m-d H:i:s", $orders['shipping_time_end']) : '';
		$order_info['confirm_time'] = $orders['confirm_time'];
		$order_info['pay_time'] = $orders['pay_time'];
		$order_info['shipping_time'] = $orders['shipping_time'];
		$order_info['consignee'] = $orders['consignee'];//收货人
		$order_info['mobile'] = $orders['mobile'];//收货人 手机号码
		$order_info['address'] = $orders['province_name'].$orders['city_name'].$orders['district_name'].$orders['address'];//收货人地址
		$order_info['invoice_no'] = $orders['invoice_no'];//快递单号
		$order_info['shipping_name'] = $orders['shipping_name'];//快递公司
		$order_info['extension_code'] = $orders['extension_code'];//virtual_good（虚拟商品）
		$order_info['goods_amount'] = $orders['goods_amount'];//订单商品总价
		$order_info['formated_goods_amount'] = $orders['formated_goods_amount'];//订单商品总价格式化
		$order_info['discount'] = $orders['discount'];//折扣金额
		$order_info['formated_discount'] = $orders['formated_discount'];//折扣金额格式化
		$order_info['integral'] = $orders['integral'];//使用积分
		$order_info['integral_money'] = $orders['integral_money'];//使用积分抵扣的金额
		$order_info['formated_integral_money'] = $orders['formated_integral_money'];//使用积分抵扣的金额格式化
		$order_info['surplus'] = $orders['surplus']?:0;//余额支付金额
		$order_info['formated_surplus'] = $orders['formated_surplus']?:0;//余额支付金额格式化
		$order_info['tax'] = $orders['tax'];//发票金额
		$order_info['formated_tax'] = $orders['formated_tax'];//发票金额格式化
		$order_info['bonus'] = $orders['bonus'];//红包抵扣金额
		$order_info['formated_bonus'] = $orders['formated_bonus'];//红包抵扣金额格式化
		$order_info['shipping_fee'] = $orders['shipping_fee'];//订单配送费用
		$order_info['formated_shipping_fee'] = $orders['formated_shipping_fee'];//订单配送费用格式化
		$order_info['money_paid'] = $orders['money_paid'];//已付款金额
		$order_info['formated_money_paid'] = $orders['formated_money_paid'];//已付款金额格式化
		$order_info['order_amount'] = $orders['order_amount'];//应付款金额
		$order_info['formated_order_amount'] = $orders['formated_order_amount'];//应付款金额格式化
		$order_info['total'] = $orders['total_fee'];//废弃字段 2018.2.24
		$order_info['formated_total'] = price_format($orders['total_fee']);//废弃字段 2018.2.24
		$order_info['total_fee'] = $orders['total_fee'];
		$order_info['formated_total_fee'] = price_format($orders['total_fee']);
		$order_info['how_oos'] = $orders['how_oos'];//缺货处理
		$order_info['back_can'] = $orders['back_can'];//0 不可退款    1 可退款
		$order_info['can_goods_do'] = $orders['can_goods_do'];//0 不可退货   1 可退货
		$order_info['can_evaluate'] = $orders['can_evaluate'];//0 不可评价   1 可评价
		$order_info['can_do'] = $orders['can_do'];//可执行的操作
		$order_info['message'] = $orders['postscript'];//留言

		//状态返回
		$order_info['order_status'] = $orders['order_status'];
		$order_info['shipping_status'] = $orders['shipping_status'];
		$order_info['pay_status'] = $orders['pay_status'];

		//发票！
		$invoice=array();
		$invoice['inv_type'] =  $orders['inv_type'];//发票类型
		$invoice['inv_type_name'] =  $orders['inv_type']?($orders['inv_type']=='normal_invoice'?'纸质发票':'增值税发票'):'';//发票类型名称
		$invoice['inv_payee_type'] =  $orders['inv_payee_type'];//发票抬头类型
		$invoice['inv_payee_type_name'] =  $orders['inv_payee_type']?($orders['inv_payee_type']=='individual'?'个人':'企业'):'';//发票抬头类型名称
		$invoice['inv_payee'] =  $orders['inv_payee'];//发票抬头
		$invoice['inv_content'] =  $orders['inv_content'];//发票内容

		$invoice['vat_inv_taxpayer_id'] =  $orders['vat_inv_taxpayer_id'];//纳税人识别码
		$invoice['vat_inv_company_name'] =  $orders['vat_inv_company_name'];//单位名称
		$invoice['vat_inv_registration_address'] =  $orders['vat_inv_registration_address'];//注册地址
		$invoice['vat_inv_registration_phone'] =  $orders['vat_inv_registration_phone'];//注册电话
		$invoice['vat_inv_deposit_bank'] =  $orders['vat_inv_deposit_bank'];//开户银行
		$invoice['vat_inv_bank_account'] =  $orders['vat_inv_bank_account'];//银行账户
		$order_info['invoice'] = $invoice;

		//自提信息
		$order_info['shipping_ziti'] = $orders['shipping_ziti'];

		$device = $this->input('device')?:$this->error('参数错误');
		//支付信息
		require_once('PaymentController.php');
		$payment = new PaymentController();
		if($device=='ios' || $device == 'android'){
			$device_where = " pay_code IN ('APP','QUICK_MSECURITY_PAY') ";
		}
		if($device=='wap' ){
			$device_where = " pay_code IN ('QUICK_WAP_WAY','JSAPI','MWEB') ";
		}
		if($device=='pc'){
			$device_where = " pay_code IN ('FAST_INSTANT_TRADE_PAY','NATIVE') ";
		}
		if($device=='xcx'){
			$device_where = " pay_code IN ('XCX') ";
		}
		$device_where .= " AND  pay_id = ".$orders['pay_id'];
		$pay_result = $payment->get_pay_list($device_where,1);
		$order_info['payment'] = current($pay_result)?:array('pay_id'=>0,'pay_code'=>0,'pay_name'=>'余额支付','pay_desc'=>'余额支付','icon'=>'自己返回图标');

		$order_data['order_info'] = $order_info;
		$order_data['goods_list'] = $goods_list;
		$this->success($order_data);
	}

	/**
	 * 删除用户订单（前端不显示）
	 */
	public function delUserOrder (){
		$order_id = !empty($this->data['order_id'])?intval($this->data['order_id']):0;
		if ($order_id <= 0)
        {
            $this->error('参数错误！');
        }
        $result = $this->user->del_UserOrder($this->user_id,$order_id);
        if($result['status'] == 200){
        	$this->success($result['message']);
        }else{
        	$this->error($result['message']);
        }
	}

	/**
	 * 获取用户可以评论的商品列表
	 */
	public function orderGoodsComment (){
		$order_id = !empty($this->data['order_id'])?intval($this->data['order_id']):0;
		if ($order_id <= 0)
        {
            $this->error('参数错误！');
        }
        $is_order = $GLOBALS['db']->getOne("SELECT order_id FROM " . $GLOBALS['ecs']->table('order_info') . "WHERE user_id = '".$this->user_id."' and order_id = '$order_id' ");
        if (!$is_order)
        {
            $this->error('非法操作！');
        }

        $goods_list = $GLOBALS['db']->getAll("SELECT og.rec_id,og.order_id,og.goods_id,og.goods_name,og.goods_number,og.goods_price,og.goods_attr,g.goods_thumb,g.is_real FROM " . $GLOBALS['ecs']->table('order_goods') . " as og," . $GLOBALS['ecs']->table('goods') . " as g WHERE og.goods_id = g.goods_id and order_id = '".$order_id."' and comment_state = 0 and shaidan_state = 0 ");
        $data = array();
        $data['list'] = $goods_list;
        if($goods_list){
        	$this->success($data);
        }else{
        	$this->error('没有可评论的商品！');
        }
	}

	/**
	 * @description 会员我的退换货订单
	 * @param integer user_id 用户ID
	 * @param integer page 第几页
	 * @param integer page_size 每页数量
	 * @return array user_info
	 */
	public function getUserBackOrder ()
	{
		$page      = !empty($this->data['page'])?intval($this->data['page']):1;
		$start_data= !empty($this->data['start_data'])?intval($this->data['start_data']):'';
		$end_data  = !empty($this->data['end_data'])?intval($this->data['end_data']):'';
		$page_size = !empty($this->data['page_size'])?intval($this->data['page_size']):10;

		$page_start = $page_size*($page-1);
		$orders = $this->back_order->back_order_list($this->user_id, $start_data, $end_data, $page_size, $page_start);
		$order_list = cls_base::getInstance()->intToString($orders);
		$this->success($order_list);
	}


	/**
	 * @description 会员我的退换货订单详情页
	 * @param integer user_id 用户ID
	 * @param integer back_id 售后订单ID
	 * @return array user_info
	 */
	public function getUserBackOrderDetail ()
	{
		$back_id   = !empty($this->data['back_id'])?intval($this->data['back_id']):0;

		$orders = $this->back_order->back_order_detail($this->user_id, $back_id);
		$order_list = cls_base::getInstance()->intToString($orders);
		$this->success($order_list);
	}

	/**
	 * @description 生成我的退换货订单
	 * @param integer user_id 用户ID
	 * @param integer back_id 售后订单ID
	 * @return array user_info
	 */
	public function ToCreateUserBackOrder ()
	{
		$order_id   = !empty($this->data['order_id'])?intval($this->data['order_id']):0;
		if ($order_id <= 0)
        {
            $this->error('参数错误！');
        }
		$goods_id   = !empty($this->data['goods_id'])?intval($this->data['goods_id']):0;
		$product_id = !empty($this->data['product_id'])?intval($this->data['product_id']):0;

		$result = $this->back_order->to_create_back_order($this->user_id, $order_id, $goods_id, $product_id);



		if($result['code'] == 500){
			$this->error($result['message']);
		}else{
			$this->success($result['data']);
		}
	}


	/**
	 * @description 订单评论页面
	 * @param integer user_id 用户ID
	 * @param integer back_id 售后订单ID
	 * @return array user_info
	 */
	public function getUserComment ()
	{
		$order_id   = !empty($this->data['order_id'])?intval($this->data['order_id']):0;
		$page       = !empty($this->data['page'])?intval($this->data['page']):1;
		$page_size  = !empty($this->data['page_size'])?intval($this->data['page_size']):10;

		$orders = $this->order->get_UserComment($this->user_id, $order_id, $page, $page_size);

		$order_list = cls_base::getInstance()->intToString($orders);
		$this->success($order_list);
	}

	/**
	 * @description 我的评论
	 * @param integer user_id 用户ID
	 */
	public function getMyComment ()
	{
		$page      = !empty($this->data['page'])?intval($this->data['page']):1;
		$page_size = !empty($this->data['page_size'])?intval($this->data['page_size']):10;

		$page_start = $page_size*($page-1);

		$is_real = isset($this->data['is_real']) ? $this->data['is_real'] : '';//空为全部评价，0为服务商品评价，1为普通商品评价
		$where = '';
		if($is_real != ''){
			$where .= " and g.is_real = '$is_real' ";
		}

		$sql = "SELECT c.comment_id,c.id_value as goods_id,c.content,c.comment_rank,c.add_time,c.rec_id,g.is_real FROM " . $GLOBALS['ecs']->table('comment') . " as c," . $GLOBALS['ecs']->table('goods') . " as g WHERE c.id_value = g.goods_id and user_id = '".$this->user_id."' $where ORDER BY add_time desc";
        $res = $GLOBALS['db'] -> selectLimit($sql, $page_size, $page_start);
        $comment_list = array();
        while ($rows = $GLOBALS['db']->fetchRow($res)){
            $rows['add_time'] = date('Y-m-d',$rows['add_time']);
            $shaidan_id = $GLOBALS['db']->getOne("SELECT shaidan_id FROM " . $GLOBALS['ecs']->table('shaidan') . " WHERE rec_id = '".$rows['rec_id']."' ");
            $rows['shaidan_img'] = $GLOBALS['db']->getAll("SELECT thumb FROM " . $GLOBALS['ecs']->table('shaidan_img') . " WHERE shaidan_id = '".$shaidan_id."' ");
            unset($rows['rec_id']);
            $comment_list[] = $rows;
        }

		$count = $GLOBALS['db']->getOne("SELECT count(*) FROM " . $GLOBALS['ecs']->table('comment') . " as c," . $GLOBALS['ecs']->table('goods') . " as g WHERE c.id_value = g.goods_id and user_id = '".$this->user_id."' $where ");

		//分页
        $pager = array();
        $pager['page']         = $page;
        $pager['page_size']    = $page_size;
        $pager['record_count'] = $count;
        $pager['page_count']   = $page_count = ($count > 0) ? intval(ceil($count / $page_size)) : 1;

        $comment_data = array();
        $comment_data['list'] = $comment_list;
        $comment_data['pager'] = $pager;
		$this->success($comment_data);
	}

	/**
	 * 删除评论
	 * @param string comment_id 评论ID,多个用英文逗号隔开
	 */
	public function delMyComment ()
	{
		$user_id = $this->user_id;
		$id_str   = !empty($this->data['comment_id'])?trim($this->data['comment_id']):0;
		/* 获取记录条数 */
        $sql = "SELECT comment_id,rec_id FROM " . $GLOBALS['ecs']->table('comment') . " WHERE comment_id ".db_create_in($id_str)." AND user_id ='$user_id'";
        $row = $GLOBALS['db']->getAll($sql);
        if($row)
        {
        	//删除评论
        	$GLOBALS['db']->query('DELETE FROM ' . $GLOBALS['ecs']->table('comment') . " WHERE comment_id ".db_create_in($id_str)." AND user_id ='$user_id'");
        	foreach ($row as $key => $value) {
        		//删除晒单
        		$shaidan_id = $GLOBALS['db']->getOne("SELECT shaidan_id FROM " . $GLOBALS['ecs']->table('shaidan') . "WHERE rec_id = '".$value['rec_id']."' ");
        		$GLOBALS['db']->query('DELETE FROM ' . $GLOBALS['ecs']->table('shaidan') . " WHERE shaidan_id ='$shaidan_id'");

        		//删除晒单图片
            	$shaidan_img_arr = $GLOBALS['db']->getAll("SELECT image,thumb FROM " . $GLOBALS['ecs']->table('shaidan_img') . "WHERE shaidan_id = '".$shaidan_id."' ");
            	foreach ($shaidan_img_arr as $k => $v) {
            		@unlink(ROOT_PATH.$v['image']);
            		@unlink(ROOT_PATH.$v['thumb']);
            	}
        	}

            $this->success(new StdClass(), $code = 200, $msg = '删除成功');
        }
        else
        {
            $this->success(new StdClass(), $code = 200, $msg = '删除失败');
        }
	}

	/**
	 * 评论商品标签选择数据
	 */
	public function comment_tag(){
		$goods_id      = !empty($this->data['goods_id'])?intval($this->data['goods_id']):0;
		$result = $GLOBALS['db']->getAll("SELECT tag_id,tag_name FROM " . $GLOBALS['ecs']->table('goods_tag') . " WHERE goods_id = '$goods_id'");
		$data = array();
		$data['list'] = !empty($result) ? $result : array();
		$this->success($data);
	}

	/**
	 * @description 提交订单评论晒单
	 * @param integer user_id 用户ID
	 * @param integer back_id 售后订单ID
	 * @return array user_info
	 */
	public function addUserComment ()
	{
		$user_id 	   	= $this->user_id;
		$goods_id      	= !empty($this->data['goods_id']) ? intval($this->data['goods_id']) : 0;
		$comment_rank  	= !empty($this->data['comment_rank']) ? intval($this->data['comment_rank']) : '';//描述
		$order_id      	= !empty($this->data['order_id']) ? intval($this->data['order_id']) : 0;
		$rec_id        	= !empty($this->data['rec_id']) ? intval($this->data['rec_id']) : 0;
		$hide_username 	= !empty($this->data['hide_username']) ? intval($this->data['hide_username']) : '';//匿名评价
		$content       	= !empty($this->data['content']) ? stripcslashes($this->data['content']) : '';//评价内容
		$comment_tag   	= !empty($this->data['comment_tag']) ? $this->data['comment_tag'] :  '';//标签
		$tags_zi       	= !empty($this->data['tags_zi']) ? $this->data['tags_zi'] : array();//自定义标签
		$img_srcs     = !empty($this->data['img_srcs']) ? $this->data['img_srcs'] : '';//图片
		//$img_num       	= isset($this->data['img_num']) ? intval($this->data['img_num']) : 0;//图片数量

		$server        	= !empty($this->data['server']) ? stripcslashes($this->data['server']) : '';//服务
		$send          	= !empty($this->data['send']) ? stripcslashes($this->data['send']) : '';//发货
		$shipping      	= !empty($this->data['shipping']) ? stripcslashes($this->data['shipping']) : '';//物流

		if($img_srcs){
			if(count($img_srcs['name']) > 6){
				$this->error('图片数量不能大于6张！');
			}
		}

		if(empty($comment_rank)){
			$this->error('请选择评分！');
		}

		if(empty($content)){
			$this->error('请填写评价内容！');
		}

		//判断是否有评论商品
		$is_comment = $GLOBALS['db']->getRow("SELECT og.comment_state FROM " . $GLOBALS['ecs']->table('order_goods') . " as og," . $GLOBALS['ecs']->table('order_info') . " as oi WHERE og.order_id = oi.order_id and og.order_id = '$order_id' and og.rec_id = '$rec_id' and og.goods_id = '$goods_id' and oi.user_id = '$user_id'");
		if(empty($is_comment)){
			$this->error('非法操作！');
		}
		if($is_comment['comment_state'] > 0){
			$this->error('商品已评论，不可重复评论！');
		}

		//$orders = $this->order->add_UserComment($this->user_id, $goods_id, $comment_rank, $order_id, $rec_id, $hide_username, $content, $comment_tag, $tags_zi, $server, $send, $shipping,$img_srcs);

		include_once (ROOT_PATH . 'includes/lib_clips.php');

		$user_info = $GLOBALS['db']->getRow("SELECT email, user_name FROM " . $GLOBALS['ecs']->table('users') . " WHERE user_id = '$user_id'");
		$comment_type = 0;
		$email     = $user_info['email'];
		$user_name = $user_info['user_name'];
		//过滤js和html
		$search    = array ("'<script[^>]*?>.*?</script>'si", "'<[\/\!]*?[^<>]*?>'si");
		$content   =  preg_replace($search,'',$content);

		// 代码增加
		// $server = $_POST['server'];
		// $send = $_POST['send'];
		// $shipping = $_POST['shipping'];
		// $o_id = $_REQUEST['o_id'];

		// if(! $order_id)
		// {
		// 	$o_id = $_REQUEST['o1_id'];
		// }

		// 代码增加

		$add_time = gmtime();
		$ip_address = real_ip();
		$status = ($GLOBALS['_CFG']['comment_check'] == 1) ? 0 : 1;

		$buy_time = $GLOBALS['db']->getOne("SELECT o.add_time FROM " . $GLOBALS['ecs']->table('order_info') . " AS o
							 LEFT JOIN " . $GLOBALS['ecs']->table('order_goods') . " AS og ON o.order_id=og.order_id
							 WHERE og.rec_id = '$rec_id'");

		/* 自定义标签 */
		if(is_array($tags_zi))
		{
			foreach($tags_zi as $tag)
			{
				$status = $GLOBALS['_CFG']['user_tag_check'];
				$GLOBALS['db']->query("INSERT INTO " . $GLOBALS['ecs']->table('goods_tag') . " (goods_id, tag_name, is_user, state) VALUES ('$goods_id', '$tag', 1, '$status')");
				$tags[] = $GLOBALS['db']->insert_id();
			}
		}
		/*foreach($comment_tag as $tagid)
		{
			if($tagid > 0)
			{
				$tagids[] = $tagid;
			}
		}
		$comment_tag = (is_array($tagids)) ? implode(",", $tagids) : '';*/

		$sql = "INSERT INTO " . $GLOBALS['ecs']->table('comment') . "(comment_type, id_value, email, user_name, content, comment_rank, add_time, ip_address, user_id, status, rec_id, comment_tag, buy_time, hide_username, order_id)" . "VALUES ('$comment_type', '$goods_id', '$email', '$user_name', '$content', '$comment_rank', '$add_time', '$ip_address', '$user_id', '$status', '$rec_id', '$comment_tag', '$buy_time', '$hide_username', '$order_id')";

		$GLOBALS['db']->query($sql);
		$GLOBALS['db']->query("UPDATE " . $GLOBALS['ecs']->table('order_goods') . " SET comment_state = 1 WHERE rec_id = '$rec_id'");

		if($order_id)
		{
			$is_grade = $GLOBALS['db']->getOne("SELECT grade_id FROM " . $GLOBALS['ecs']->table('shop_grade') . "
							 WHERE order_id = '$order_id'");
			if(!$is_grade){
				$o_sn = $GLOBALS['db']->getOne("SELECT order_sn FROM " . $GLOBALS['ecs']->table('order_info') . "
							 WHERE order_id = '$order_id'");
				$sql = "INSERT INTO " . $GLOBALS['ecs']->table('shop_grade') . "(user_id, user_name, add_time, comment_rank, server, send, shipping, order_id, order_sn)" . "VALUES ('$user_id', '$user_name', '$add_time', '$comment_rank', '$server', '$send', '$shipping', '$order_id', '$o_sn')";
				$GLOBALS['db']->query($sql);
			}
		}
		$msg = '';
		if($status == 0)
		{
			$msg .= '您的评价信息已提交，需要管理员审核后才能显示！';
		}
		else
		{
			$msg .= '您的评价信息已提交！';
		}


		// 处理图片
		if($img_srcs)
		{
			$imgnum = count($img_srcs['name']);// 图片数量
			include_once (ROOT_PATH . '/includes/cls_image.php');
			$image = new cls_image($GLOBALS['_CFG']['bgcolor']);

			$title = trim($content);
			$message = trim($content);
			$add_time = gmtime();
			$status = $GLOBALS['_CFG']['shaidan_check'];

			$sql = "INSERT INTO " . $GLOBALS['ecs']->table('shaidan') . "(rec_id, goods_id, user_id, title, message, add_time, status, hide_username)" . "VALUES ('$rec_id', '$goods_id', '$user_id', '$title', '$message', '$add_time', '$status', '$hide_username')";
			$GLOBALS['db']->query($sql);
			$shaidan_id = $GLOBALS['db']->insert_id();
			$GLOBALS['db']->query("UPDATE " . $GLOBALS['ecs']->table('order_goods') . " SET shaidan_state = 1 WHERE rec_id = '$rec_id'");

			$img_path = 'shaidan/' . date('Ym');
			if(count($img_srcs['name']) == 1){
				//单张
				$original = $image->upload_image($img_srcs,$img_path);
				$thumb = $image->make_thumb(ROOT_PATH.$original, 100, 100, ROOT_PATH.'data/'.$img_path.'/thumb/');
				$sql = "INSERT INTO " . $GLOBALS['ecs']->table('shaidan_img') . "(shaidan_id, image, thumb)" . "VALUES ('$shaidan_id', '$original', '$thumb')";
				$GLOBALS['db']->query($sql);
			}else{
				//多张
				for($i=0; $i <= $imgnum;$i++ ){
					if(empty($img_srcs['tmp_name'][$i])){
						break;
					}
					$img = array('name'=>$img_srcs['name'][$i],'type'=>$img_srcs['type'][$i],'tmp_name'=>$img_srcs['tmp_name'][$i]);
					$original = $image->upload_image($img,$img_path);
					$thumb = $image->make_thumb(ROOT_PATH.$original, 100, 100, ROOT_PATH.'data/'.$img_path.'/thumb/');
					$sql = "INSERT INTO " . $GLOBALS['ecs']->table('shaidan_img') . "(shaidan_id, image, thumb)" . "VALUES ('$shaidan_id', '$original', '$thumb')";
					$GLOBALS['db']->query($sql);
				}
			}

			// 需要审核
			if($status == 0)
			{
				$msg .= '晒单图片已提交，需要管理员审核后才能显示！';
			}

			// 不需要审核
			else
			{
				$info = $GLOBALS['db']->GetRow("SELECT * FROM " . $GLOBALS['ecs']->table('shaidan') . " WHERE shaidan_id='$shaidan_id'");
				// 该商品第几位晒单者
				$res = $GLOBALS['db']->getAll("SELECT shaidan_id FROM " . $GLOBALS['ecs']->table("shaidan") . " WHERE goods_id = '$info[goods_id]' ORDER BY add_time ASC");
				foreach($res as $key => $value)
				{
					if($shaidan_id == $value['shaidan_id'])
					{
						$weizhi = $key + 1;
					}
				}

				// 是否赠送积分
				if($info['is_points'] == 0 && $weizhi <= $_CFG['shaidan_pre_num'] && $imgnum >= $_CFG['shaidan_img_num'])
				{
					$pay_points = $_CFG['shaidan_pay_points'];
					$GLOBALS['db']->query("UPDATE " . $GLOBALS['ecs']->table('shaidan') . " SET pay_points = '$pay_points', is_points = 1 WHERE shaidan_id = '$shaidan_id'");
					$GLOBALS['db']->query("INSERT INTO " . $GLOBALS['ecs']->table('account_log') . "(user_id, rank_points, pay_points, change_time, change_desc, change_type) " . "VALUES ('$info[user_id]', 0, '" . $pay_points . "', " . gmtime() . ", '晒单获得积分', '99')");
					$log = $GLOBALS['db']->getRow("SELECT SUM(rank_points) AS rank_points, SUM(pay_points) AS pay_points FROM " . $GLOBALS['ecs']->table("account_log") . " WHERE user_id = '$info[user_id]'");
					$GLOBALS['db']->query("UPDATE " . $GLOBALS['ecs']->table('users') . " SET rank_points = '" . $log['rank_points'] . "', pay_points = '" . $log['pay_points'] . "' WHERE user_id = '$info[user_id]'");
				}

				$msg .= '晒单图片已提交！';
			}

			//删除临时文件
	        $files = glob(ROOT_PATH.'runtime/temp/*');
	        foreach($files as $file){
	            if(is_file($file)){
	                @unlink($file);
	            }
	        }
		}

		$this->success($msg);

	}


	/**
	 * @description 取消订单
	 * @param integer user_id 用户ID
	 * @param integer order_id 订单ID
	 * @return void
	 */
	public function cancelUserOrder ()
	{
		include_once (ROOT_PATH . 'includes/lib_transaction.php');
		include_once (ROOT_PATH . 'includes/lib_order.php');

		$order_id = !empty($this->data['order_id'])?intval($this->data['order_id']) : 0;

		$result = $this->order->cancel_order($order_id, $this->user_rank_info['user_id']);
		if($result['code'] == 200)
		{
			$this->success(array(),200,'取消订单成功');
		}
		else
		{
			$this->error($result['message']);
		}
	}


	/**
	 * @description 订单确认收货
	 * @param integer user_id 用户ID
	 * @param integer order_id 订单ID
	 * @return array user_info
	 */
	public function arrivedUserOrder ()
	{
		include_once (ROOT_PATH . 'includes/lib_transaction.php');
		include_once (ROOT_PATH . 'includes/lib_order.php');

		$order_id = !empty($this->data['order_id'])?intval($this->data['order_id']) : 0;

		$result = $this->order->arrived_order($order_id, $this->user_rank_info['user_id']);

		if($result['code'] == 200)
		{
			$this->success(array(),200,'确认收货订单成功');
		}
		else
		{
			$this->error($result['message']);
		}
	}


	//myOrder 用户中心首页 我的订单
	public function myOrder(){
		$user_id = $this->input('user_id',0);
		if(!$user_id){
			$this->error('请先登录');
		}
		//待付款
		$data['unpay'] = $GLOBALS['db']->getOne("SELECT COUNT(`order_id`) FROM ".$GLOBALS['ecs']->table('order_info')." WHERE user_id = $user_id AND pay_status = 0 ");
		//已付款/待发货
		$data['chcked'] = $GLOBALS['db']->getOne("SELECT COUNT(`order_id`) FROM ".$GLOBALS['ecs']->table('order_info')." WHERE user_id = $user_id AND pay_status = 2 ");
		//已发货/待收货
		$data['shipped'] = $GLOBALS['db']->getOne("SELECT COUNT(`order_id`) FROM ".$GLOBALS['ecs']->table('order_info')." WHERE user_id = $user_id AND shipping_status = 1 ");
		//已收货/待评价
		$data['received'] = $GLOBALS['db']->getOne("SELECT COUNT(info.order_sn) FROM ".$GLOBALS['ecs']->table('order_info')." info LEFT JOIN ".$GLOBALS['ecs']->table('order_goods')." goods ON info.order_id = goods.order_id  WHERE user_id = $user_id AND info.shipping_status = 2 AND goods.comment_state = 0");
		//退货退款  售后中
		$data['service'] = 0;

		//print_r($data);exit;
		$this->success($data);
	}


	/**
	 * @description 收货地址列表
	 * @param integer user_id 用户ID
	 * @return array consignee_list
	 */
	public function getUserAddress ($address_id = 0)
	{
		$page      = !empty($this->data['page'])?intval($this->data['page']):1;
		$page_size = !empty($this->data['page_size'])?intval($this->data['page_size']):10;
		$page_start = $page_size*($page-1);

		$consignee_list = $this->user->get_consignee_list($this->user_id, $page_size, $page_start);
		$regions_list   = $this->user->get_regions_list();
		// 删除不必要的字段
		foreach($consignee_list as $region_id => $consignee){

			if($address_id > 0 && ($address_id != $consignee_list[$region_id]['address_id'])){
				unset($consignee_list[$region_id]);
				continue;
			}

			unset($consignee_list[$region_id]['address_name']);
			unset($consignee_list[$region_id]['zipcode']);
			unset($consignee_list[$region_id]['sign_building']);
			unset($consignee_list[$region_id]['best_time']);
			// unset($consignee_list[$region_id]['tel']);

			// 取得国家列表，如果有收货人列表，取得省市区列表
			$consignee_list[$region_id]['country_name']  = $regions_list[$consignee['country']]['region_name'];
			$consignee_list[$region_id]['province_name'] = $regions_list[$consignee['province']]['region_name'];
			$consignee_list[$region_id]['city_name']     = $regions_list[$consignee['city']]['region_name'];
			$consignee_list[$region_id]['district_name'] = $regions_list[$consignee['district']]['region_name'];

		}

		$count = $this->user->get_consignee_count($this->user_id);
		//分页
        $pager = array();
        $pager['page']         = $page;
        $pager['page_size']    = $page_size;
        $pager['record_count'] = $count;
        $pager['page_count']   = $page_count = ($count > 0) ? intval(ceil($count / $page_size)) : 1;
        $consignee_data['list'] = $consignee_list;
        $consignee_data['pager'] = $pager;
		$this->success($consignee_data);
	}

	/**
	 * @description 设置默认地址
	 * @param integer user_id 用户ID
	 * @param integer address 地址ID
	 * @return array consignee_list
	 */
	public function setUserDefaultAddress($address_id = null)
	{
		if($address_id){
			$this->data['address_id'] = $address_id;
		}
		$address_id      = !empty($this->data['address_id'])?intval($this->data['address_id']):$this->error('地址ID错误');
		if($this->user->set_default_address($this->user_id, $address_id)){
			$this->success('默认地址设置成功');
		}
		$this->error('默认地址设置失败');
	}

	/**
	 * @description 指定某个ID的地址信息
	 * @param integer user_id 用户ID
	 * @return array consignee_list
	 */
	public function getUserAddressInfo ()
	{
		$address_id      = !empty($this->data['address_id'])?intval($this->data['address_id']):$this->error('地址ID错误');
		$address_info    = current($this->getUserAddress($address_id));
		$this->success($address_info);
	}


	/**
	 * @description 添加/更新会员地址
	 * @param integer user_id 用户ID
	 * @return array void
	 */
	public function updateUserAddress ()
	{
		include_once (ROOT_PATH . 'includes/lib_transaction.php');

		$address = array(
			'user_id'    => $this->user_id, // 会员ID
			'address_id' => isset($this->data['address_id']) ? intval($this->data['address_id']) : 0, // 地址ID
			'country'    => isset($this->data['country']) ? intval($this->data['country']) : 1, // 国家
			'province'   => isset($this->data['province']) ? intval($this->data['province']) : 0, // 省份
			'city'       => isset($this->data['city']) ? intval($this->data['city']) : 0,  // 城市
			'district'   => isset($this->data['district']) ? intval($this->data['district']) : 0,  // 地区
			'address'    => isset($this->data['address']) ? compile_str(trim($this->data['address'])) : '',  // 地址
			'consignee'  => isset($this->data['consignee']) ? compile_str(trim($this->data['consignee'])) : '',  // 收货人
			'email'      => isset($this->data['email']) ? compile_str(trim($this->data['email'])) : '',  // 邮件
			'tel'        => isset($this->data['tel']) ? compile_str(make_semiangle(trim($this->data['tel']))) : '',  // 电话
			'mobile'     => isset($this->data['mobile']) ? compile_str(make_semiangle(trim($this->data['mobile']))) : '',  // 手机号码
			'best_time'  => isset($this->data['best_time']) ? compile_str(trim($this->data['best_time'])) : '',  // 最佳配送时间
			'sign_building' => isset($this->data['sign_building']) ? compile_str(trim($this->data['sign_building'])) : '',  // 标志建筑
			'zipcode'    => isset($this->data['zipcode']) ? compile_str(make_semiangle(trim($this->data['zipcode']))) : '', // 邮编地址
			'is_default'    => isset($this->data['is_default']) ? intval($this->data['is_default']) : 0 // 是否设置默认
		);

		if (empty($address['consignee']))
		{
			$this->error('收货人不能为空！');
		}
		if ($address['province'] < 1 || $address['city'] < 1 || $address['district'] < 1)
		{
			$this->error('请选择正确地址');
		}
		if (!is_mobile_phone($address['mobile']))
		{
			$this->error('手机号码格式错误');
		}
		if (!empty($address['tel']) && !is_tel($address['tel']))
		{
			$this->error('固定电话格式错误');
		}
		if (empty($address['address']))
		{
			$this->error('详细地址不能为空');
		}

		$address_id = update_address($address,$this->user_id);
		if($address_id)
		{
			if($address['is_default'] == 1){
				$this->setUserDefaultAddress($address_id);
			}
			$this->success('操作成功');
		}

		$this->error('操作失败');
	}


	/**
	 * @description 删除会员地址
	 * @param integer user_id 用户ID
	 * @param integer consignee_id 地址ID
	 * @return array void
	 */
	public function dropUserAddress ()
	{
		$_SESSION = $this->user_rank_info;
		include_once ('includes/lib_transaction.php');
		$address_id      = !empty($this->data['address_id'])?intval($this->data['address_id']):0;
		if(drop_consignee($address_id,$this->user_id))
		{
			$this->success('地址删除成功');
		}
		else
		{
			$this->error('地址删除失败');
		}
	}

	private function log($msg, $level = 'info')
	{
		$this->logger->writeLog($msg, $level, 'user');
	}

	/**
	 * @description 我的消息
	 * @param integer user_id 用户ID
	 */
	public function getMyNews()
	{
		$page      = !empty($this->data['page'])?intval($this->data['page']):1;
		$page_size = !empty($this->data['page_size'])?intval($this->data['page_size']):10;

		$page_start = $page_size*($page-1);
		$news = $this->user->get_MyNews($this->user_id, $this->data['msg_type'], $page_size, $page_start);
		$count = $this->user->get_MyNews_count($this->user_id, $this->data['msg_type']);
		//分页
        $pager = array();
        $pager['page']         = $page;
        $pager['page_size']    = $page_size;
        $pager['record_count'] = $count;
        $pager['page_count']   = $page_count = ($count > 0) ? intval(ceil($count / $page_size)) : 1;

        $news_data['list'] = $news;
        $news_data['pager'] = $pager;
		$this->success($news_data);
	}

	/**
	 * @description 我的消息详情
	 * @param integer user_id 用户ID
	 */
	public function getMyNewsDetail()
	{
		$l_id   = !empty($this->data['l_id'])?intval($this->data['l_id']):0;
		$newsDetail = $this->user->get_MyNewsDetail($this->user_id,$l_id);
		$this->success($newsDetail);
	}

	/**
	 * @description 我的消息 一键已读
	 * @param integer user_id 用户ID
	 */
	public function getMyNewsChange()
	{
		$newsChange = $this->user->get_MyNewsChange($this->user_id, $this->data['msg_type']);
		$this->success('已全部设置已读状态！');
	}

	/**
	 * @description 我的消息 删除
	 * @param integer user_id 用户ID
	 */
	public function delMyNews()
	{
		$id_str = !empty($this->data['l_id']) ? trim($this->data['l_id']) : '';
		$news = $this->user->del_MyNews($this->user_id, $id_str);
		$this->success(new stdClass);
	}

	/**
	 * @description 我的消息 是否有未读消息
	 * @param integer user_id 用户ID
	 */
	public function hasUnreadMessage()
	{
		$result = $this->user->has_Unread_Message($this->user_id);
		$this->success($result);
	}

	public function getInteract()
	{
		$type      = intval($this->data['type']) ? intval($this->data['type']) : 0;
		$page      = !empty($this->data['page']) ? intval($this->data['page']) : 1;
		$page_size = !empty($this->data['page_size']) ? intval($this->data['page_size']) : 10;
		$page_start = $page_size*($page-1);
		$result = $this->user->get_user_hudong($this->user_id, $type, $page_start, $page);
		$this->success($result);
	}

	/**
	 * 我的私信聊天用户列表
	 */
	public function getLetterUsers()
	{
		$result = $this->user->get_Letter_Users($this->user_id);
		$this->success($result);
	}

	/**
	 * 发送私信
	 */
	public function sendLetter()
	{
		if(empty($this->user_id) || !isset($this->user_id)){
			$this->error("请先登录");
		}
		if (empty($this->data['receive_user_id'])) {
		    $this->error('非法操作');
		}
		if ($this->user_id == $this->data['receive_user_id']) {
		    $this->error('不能发送私信给自己');
		}
		if (empty($this->data['msg_content'])) {
			$this->error('私信内容不能为空');
		}
		
		$result = $this->user->send_Letter($this->user_id, $this->data['receive_user_id'], $this->data['msg_content']);
		if ($result) {
			$this->success('发送成功');
		} else {
			$this->error('发送失败');
		}
	}

	/**
	 * 删除私信左侧用户
	 */
	public function removeLetterUser()
	{
		if (empty($this->data['receive_user_id'])) {
		    $this->error('非法操作');
		}
		$result = $this->user->remove_Letter_User($this->user_id, $this->data['receive_user_id']);
		if ($result) {
			$this->success('删除成功');
		} else {
			$this->error('删除失败');
		}
	}

	/**
	 * 删除私信
	 */
	public function removeLetter()
	{
		if (empty($this->data['msg_id'])) {
		    $this->error('非法操作');
		}
		$result = $this->user->remove_Letter($this->user_id, $this->data['receive_user_id'], $this->data['msg_id']);
		if ($result) {
			$this->success('删除成功');
		} else {
			$this->error('删除失败');
		}
	}

	/**
	 * 获取跟某个用户的私信聊天消息
	 */
	public function getLetters()
	{
		$result = $this->user->get_Letters($this->user_id, $this->data['receive_user_id']);
		$this->success($result);
	}

	/**
	 * 私信举报原因
	 */
	public function getReportReason()
	{
		$result = $this->common->get_Report_Reason();
		$this->success($result);
	}

	/**
	 * 私信举报
	 */
	public function doLetterReport()
	{
		$result = $this->user->do_Letter_Report($this->data['msg_id'], $this->data['reason']);
		if ($result) {
			$this->success('举报成功');
		} else {
			$this->error('您已举报过该消息');
		}
	}

	/**
	 * 未读消息总数
	 */
	public function getUnreadCount()
	{
		$result = $this->user->get_Unread_Count($this->user_id);
		$this->success($result);
	}

	/**
	 * 意见反馈
	 */
	public function doResearch()
	{
		if (empty($this->data['content'])) {
		    $this->error('反馈内容不能为空');
		}
		$result = $this->user->do_Research($this->user_id, $this->data['content']);
		if ($result) {
			$this->success('提交成功');
		} else {
			$this->error('提交失败');
		}
	}

	/**
	 * @description 银行列表
	 */
	public function getBankList()
	{
		$page      = !empty($this->data['page'])?intval($this->data['page']):1;
		$page_size = !empty($this->data['page_size'])?intval($this->data['page_size']):100;

		$page_start = $page_size*($page-1);
		$bank = $this->user->get_BankList($page_size, $page_start);
		$count = $this->user->get_BankList_count();
		//分页
        $pager = array();
        $pager['page']         = $page;
        $pager['page_size']    = $page_size;
        $pager['record_count'] = $count;
        $pager['page_count']   = $page_count = ($count > 0) ? intval(ceil($count / $page_size)) : 1;

        $bank_data['list'] = $bank;
        $bank_data['pager'] = $pager;
		$this->success($bank_data);
	}

	/**
	 * @description 银行卡列表
	 * @param integer user_id 用户ID
	 */
	public function getBankCardList()
	{
		$page      = !empty($this->data['page'])?intval($this->data['page']):1;
		$page_size = !empty($this->data['page_size'])?intval($this->data['page_size']):10;

		$page_start = $page_size*($page-1);
		$bank = $this->user->get_BankCardList($this->user_id, $page_size, $page_start);
		$count = $this->user->get_BankCardList_count($this->user_id);
		//分页
        $pager = array();
        $pager['page']         = $page;
        $pager['page_size']    = $page_size;
        $pager['record_count'] = $count;
        $pager['page_count']   = $page_count = ($count > 0) ? intval(ceil($count / $page_size)) : 1;

        $bank_data['list'] = $bank;
        $bank_data['pager'] = $pager;
		$this->success($bank_data);
	}

	/**
	 * @description 添加银行卡
	 * @param integer user_id 用户ID
	 * @param string real_name 真实姓名
	 * @param string id_card 身份证号码
	 * @param string card_number 银行卡号码
	 * @param string card_name 开户银行名称
	 * @param string mobile 预留手机号码
	 */
	public function addBankCard()
	{
		$real_name = !empty($this->data['real_name'])?trim($this->data['real_name']):'';//真实姓名
		$card = !empty($this->data['card'])?trim($this->data['card']):'';//身份证号码
		$bank_id = !empty($this->data['bank_id'])?trim($this->data['bank_id']):'';//银行ID
		$bank_logo = !empty($this->data['bank_logo'])?trim($this->data['bank_logo']):'';//银行LOGO
		$card_number = !empty($this->data['card_number'])?trim($this->data['card_number']):'';//银行卡号码
		$card_name = !empty($this->data['card_name'])?trim($this->data['card_name']):'';//开户支行
		$card_type = !empty($this->data['card_type'])?trim($this->data['card_type']):'';//卡类型
		$card_info = !empty($this->data['card_info'])?trim($this->data['card_info']):'';//卡详细信息

		if(empty($real_name) || empty($bank_id) || empty($card_number) || empty($card_name) || empty($card_type) || empty($card_info) || empty($bank_logo) || empty($card) ){
			$this->error('参数不能为空');
		}

		$info = $this->user->add_BankCard($this->user_id,$real_name,$bank_id,$card_number,$card_name,$card_type,$card_info,$bank_logo,$card);
		switch ($info) {
			case '2':
				$this->error('添加银行卡失败！');
				break;
			case '3':
				$this->error('银行卡号不正确！');
				break;
			default:
				$this->success(new stdClass);
				break;
		}

	}

	/**
	 * 修改银行卡
	 */
	public function editBankCard()
	{
		$edit_id = !empty($this->data['edit_id'])?intval($this->data['edit_id']):'';//修改的银行卡ID
		$real_name = !empty($this->data['real_name'])?trim($this->data['real_name']):'';//真实姓名
		$card = !empty($this->data['card'])?trim($this->data['card']):'';//身份证号码
		$bank_id = !empty($this->data['bank_id'])?trim($this->data['bank_id']):'';//银行ID
		$bank_logo = !empty($this->data['bank_logo'])?trim($this->data['bank_logo']):'';//银行LOGO
		$card_number = !empty($this->data['card_number'])?trim($this->data['card_number']):'';//银行卡号码
		$card_name = !empty($this->data['card_name'])?trim($this->data['card_name']):'';//开户支行
		$card_type = !empty($this->data['card_type'])?trim($this->data['card_type']):'';//卡类型
		$card_info = !empty($this->data['card_info'])?trim($this->data['card_info']):'';//卡详细信息

		if(empty($edit_id) || empty($real_name) || empty($bank_id) || empty($card_number) || empty($card_name) || empty($card_type) || empty($card_info) || empty($bank_logo) || empty($card) ){
			$this->error('参数不能为空');
		}

		$info = $this->user->edit_BankCard($this->user_id,$real_name,$bank_id,$card_number,$card_name,$card_type,$card_info,$bank_logo,$card,$edit_id);
		switch ($info) {
			case '2':
				$this->error('修改银行卡失败！');
				break;
			case '3':
				$this->error('银行卡号不正确！');
				break;
			default:
				$this->success(new stdClass);
				break;
		}

	}

	/**
	 * @description 银行卡 设置默认
	 * @param integer user_id 用户ID
	 * @param integer card_id 银行卡ID
	 */
	public function setBankCardDefault()
	{
		$card_id = !empty($this->data['card_id'])?intval($this->data['card_id']):0;
		$info = $this->user->set_BankCardDefault($this->user_id,$card_id);
		$this->success(new stdClass);
	}

	/**
	 * @description 银行卡 获取默认
	 * @param integer user_id 用户ID
	 */
	public function getBankCardDefault()
	{
		$info = $this->user->get_BankCardDefault($this->user_id);
		if($info){
			$this->success($info);
		}else{
			$this->success(new stdClass);
		}
	}



	/**
	 * @description 银行卡 删除
	 * @param integer user_id 用户ID
	 * @param integer bank_id 银行卡ID
	 */
	public function delBankCard()
	{
		$id_str = !empty($this->data['card_id'])?trim($this->data['card_id']):0;
		if(empty($id_str)){
			$this->error('缺少要删除银行卡ID参数！');
		}
		$info = $this->user->del_BankCard($this->user_id,$id_str);
		$this->success(new stdClass);
	}

	/**
	 * @description 申请提现记录列表
	 * @param integer user_id 用户ID
	 */
	public function getApplyDepositList()
	{
		$page      = !empty($this->data['page'])?intval($this->data['page']):1;
		$page_size = !empty($this->data['page_size'])?intval($this->data['page_size']):10;

		$page_start = $page_size*($page-1);
		$apply = $this->user->get_ApplyDepositList($this->user_id, $page_size, $page_start);
		$count = $this->user->get_ApplyDepositList_count($this->user_id);
		//分页
	    $pager = array();
	    $pager['page']         = $page;
	    $pager['page_size']    = $page_size;
	    $pager['record_count'] = $count;
	    $pager['page_count']   = $page_count = ($count > 0) ? intval(ceil($count / $page_size)) : 1;

	    $apply_data['surplus'] = $this->user->get_user_yue($this->user_id);//用户余额
	    $apply_data['payed'] = $this->user->get_user_payed($this->user_id);//用户已消费
	    $apply_data['list'] = $apply;
	    $apply_data['pager'] = $pager;

		$this->success($apply_data);
	}

	/**
	 * @description 申请提现
	 * @param integer user_id 用户ID
	 * @param string bank_id 银行卡ID
	 * @param string amount 提现金额
	 * @param string user_note 会员备注
	 */
	public function applyDeposit()
	{
		$card_id = !empty($this->data['card_id'])?intval($this->data['card_id']):0;
		$amount = !empty($this->data['amount'])?floatval($this->data['amount']):'';
		$user_note = !empty($this->data['user_note'])?trim($this->data['user_note']):'';

		if(empty($card_id)){
			$this->error('请选择银行卡！');
		}

		if(empty($amount)){
			$this->error('请输入余额！');
		}

		if($amount <= 0)
        {
            $this->error('申请金额要大于0！');
        }

        /* 判断是否有足够的余额的进行退款的操作 */
        $sur_amount = $this->user->get_user_yue($this->user_id);
        if($amount > $sur_amount)
        {
           $this->error('申请失败！申请金额大于现有余额！');
        }

		$news = $this->user->apply_Deposit($this->user_id,$card_id,$amount,$user_note);
		$this->success(new stdClass);
	}

	/**
	 * @description 取消提现
	 * @param integer user_id 用户ID
	 * @param string apply_id 申请ID
	 */
	public function delApplyDeposit()
	{
		$apply_id = !empty($this->data['apply_id'])?trim($this->data['apply_id']):0;

		if(empty($apply_id)){
			$this->error('请选择你要取消的申请！');
		}

		$news = $this->user->del_ApplyDeposit($this->user_id,$apply_id);
		$this->success(new stdClass);
	}

	//退货详情
	public function back_order_detail ()
	{

		$_LANG = $GLOBALS['_LANG'];
		$db = $GLOBALS['db'];
		$ecs = $GLOBALS['ecs'];
		//$user_id = $_SESSION['user_id'];

		$back_id = ! empty($_REQUEST['back_id']) ? intval($_REQUEST['back_id']) : 0;

		$sql = 'SELECT shipping_id, shipping_code, shipping_name ' . 'FROM ' . $GLOBALS['ecs']->table('shipping') . 'WHERE enabled = 1 and supplier_id = 0   ORDER BY shipping_order';
		$shipping_list = $db->getAll($sql);

		//$smarty->assign('shipping_list', $shipping_list);

		$sql = "SELECT * " . " FROM " . $GLOBALS['ecs']->table('back_order') . " WHERE back_id= '$back_id' ";
		$back_shipping = $db->getRow($sql);

		$sql_og = "SELECT * FROM " . $GLOBALS['ecs']->table('back_goods') . " WHERE back_id = " . $back_id;
		$back_shipping['goods_list'] = $GLOBALS['db']->getAll($sql_og);

		$back_shipping['add_time'] = local_date("Y-m-d H:i", $back_shipping['add_time']);
		$back_shipping['refund_money_1'] = price_format($back_shipping['refund_money_1'], false);
		$back_shipping['refund_money_2'] = price_format($back_shipping['refund_money_2'], false);
		$back_shipping['refund_type_name'] = $back_shipping['refund_type'] == '0' ? '' : ($back_shipping['refund_type'] == '1' ? '退回用户余额' : '线下退款');
		$back_shipping['country_name'] = $db->getOne("SELECT region_name FROM " . $ecs->table('region') . " WHERE region_id = '$back_shipping[country]'");
		$back_shipping['province_name'] = $db->getOne("SELECT region_name FROM " . $ecs->table('region') . " WHERE region_id = '$back_shipping[province]'");
		$back_shipping['city_name'] = $db->getOne("SELECT region_name FROM " . $ecs->table('region') . " WHERE region_id = '$back_shipping[city]'");
		$back_shipping['district_name'] = $db->getOne("SELECT region_name FROM " . $ecs->table('region') . " WHERE region_id = '$back_shipping[district]'");

		$back_shipping['status_back_1'] = $back_shipping['status_back'];
		$back_shipping['status_back'] = $_LANG['bos'][$back_shipping['status_back']] . ($back_shipping['status_back'] == '3' && $back_shipping['back_type'] && $back_shipping['back_type'] != '4' ? ' (换回商品已寄出，请注意查收) ' : '');
		$back_shipping['status_refund'] = $_LANG['bps'][$back_shipping['status_refund']];

		//$smarty->assign('back_shipping', $back_shipping);

		// 退货商品 + 换货商品 详细信息
		$list_backgoods = array();
		$sql = "select bg.*,g.goods_thumb,g.goods_img,g.original_img from " . $GLOBALS['ecs']->table('back_goods') . " as bg left join " . $GLOBALS['ecs']->table('goods') . " AS g " . " on bg.product_id=g.goods_id  " . " where back_id = '$back_id' order by back_type ";
		//$sql_goods = "SELECT bg.* ,g.goods_thumb,g.goods_img,g.original_img FROM " . $GLOBALS['ecs']->table('back_goods') . " as bg left join " . $GLOBALS['ecs']->table('goods') . " AS g " . " on bg.product_id=g.goods_id  " . " WHERE back_id = " . $row['back_id'];
		$res_backgoods = $db->query($sql);
		while($row_backgoods = $db->fetchRow($res_backgoods))
		{
			$back_type_temp = $row_backgoods['back_type'] == '2' ? '1' : $row_backgoods['back_type'];
			// $list_backgoods[$back_type_temp]['goods_list'][] = array(
				// 'goods_name' => $row_backgoods['goods_name'], 'goods_attr' => $row_backgoods['goods_attr'], 'back_goods_number' => $row_backgoods['back_goods_number'], 'back_goods_money' => price_format($row_backgoods['back_goods_number'] * $row_backgoods['back_goods_price'], false), 'status_back' => $_LANG['bos'][$row_backgoods['status_back']] . ($row_backgoods['status_back'] == '3' && $row_backgoods['back_type'] && $row_backgoods['back_type'] != '4' ? ' (换回商品已寄出，请注意查收) ' : ''), 'status_refund' => $_LANG['bps'][$row_backgoods['status_refund']], 'back_type' => $row_backgoods['back_type'], 'goods_thumb' => $row_backgoods['goods_thumb'], 'goods_img' => $row_backgoods['goods_img'], 'original_img' => $row_backgoods['original_img']
			// );
			$list_backgoods[] = array(
				'goods_name' => $row_backgoods['goods_name'], 'goods_attr' => $row_backgoods['goods_attr'], 'back_goods_number' => $row_backgoods['back_goods_number'], 'back_goods_money' => price_format($row_backgoods['back_goods_number'] * $row_backgoods['back_goods_price'], false), 'status_back' => $_LANG['bos'][$row_backgoods['status_back']] . ($row_backgoods['status_back'] == '3' && $row_backgoods['back_type'] && $row_backgoods['back_type'] != '4' ? ' (换回商品已寄出，请注意查收) ' : ''), 'status_refund' => $_LANG['bps'][$row_backgoods['status_refund']], 'back_type' => $row_backgoods['back_type'], 'goods_thumb' => $row_backgoods['goods_thumb'], 'goods_img' => $row_backgoods['goods_img'], 'original_img' => $row_backgoods['original_img']
			);
		}
		//$smarty->assign('list_backgoods', $list_backgoods);

		/* 回复留言 增加 */
		$res = $db->getAll("SELECT * FROM " . $GLOBALS['ecs']->table('back_replay') . " WHERE back_id = '$back_id' ORDER BY add_time ASC");
		$back_replay = array();
		foreach($res as $value)
		{
			$value['add_time'] = local_date("Y-m-d H:i", $value['add_time']);
			$back_replay[] = $value;
		}

		//$smarty->assign('back_replay', $back_replay);

		//$smarty->assign('back_id', $back_id);
		//$smarty->display('user_transaction.dwt');
		//$this->success(array('shipping_list'=>$shipping_list,'back_shipping'=>$back_shipping,'list_backgoods'=>$list_backgoods,'back_replay'=>$back_replay,'back_id'=>$back_id));
		//$this->success(array('back_shipping'=>$back_shipping,'list_backgoods'=>$list_backgoods,'back_replay'=>$back_replay,'back_id'=>$back_id));
		//print_r($back_shipping);
		$sttus_type = array(1=>'退货',2=>'换货',3=>'申请返修',4=>'退款（无需退货）');
		$refund = array('refund_type'=>$sttus_type[$back_shipping['back_type']],'refund_amount'=>$back_shipping['refund_money_1'],'refund_reason'=>$back_shipping['back_reason']);
		$orderInfo = $db->getRow("SELECT *,(goods_amount + shipping_fee + insure_fee + pay_fee + pack_fee + card_fee + tax - discount) as total FROM  " . $GLOBALS['ecs']->table('order_info') . " where order_id = ".$back_shipping['order_id']);
		$back = array('refund_id'=>$orderInfo['order_sn'],'refund_amount'=>$orderInfo['total'],'refund_time'=>local_date("Y-m-d H:i", $orderInfo['add_time']));
		$order = array('order_id'=>$back_id,'back_status'=>$back_shipping['status_back'],'back_time'=>$back_shipping['add_time']);
		$this->success(array('goods_list'=>$list_backgoods,'back_replay'=>$back_replay,'back'=>$back,'order'=>$order,'refund'=>$refund));
	}

	//退货 退款
	public function apply_back_order ()
	{
		//目前只考虑退款或退货
		$user_id = intval($this->input('user_id', 0));
		$order_id   = !empty($this->data['order_id'])?intval($this->data['order_id']):0;
		$back_type   = !empty($this->data['back_type'])?intval($this->data['back_type']):0;// 4 退款 3 返修 2 换货 1 退货

		$back_pay=($back_type==1 || $back_type==4)?1:0;

		$goods_id   = !empty($this->data['goods_id'])?intval($this->data['goods_id']):0;
		$product_id   = !empty($this->data['product_id'])?intval($this->data['product_id']):0;
		$reason_id   = !empty($this->data['reason_id'])?intval($this->data['reason_id']):0; //退款原因

		$back_postscript   = !empty($this->data['back_postscript'])?$this->data['back_postscript']:''; //留言

		$back_imgs      = !empty($this->data['back_imgs'])?$this->data['back_imgs']:array();//图片
		if($back_imgs){
			if(count($back_imgs['name']) > 6){
				$this->error('图片数量不能大于6张！');
			}
		}

		if($order_id<=0)
		{
			$this->error('对不起，您进行了错误操作！');
		}

		if($back_type<=0)
		{
			$this->error('对不起，您还没选择您的退款类型！');
		}


		$back_reason="";


		if(in_array($back_type,array(1,4))){
        	if($reason_id<=0){
        		$this->error('请选择退款原因！');
        	}
        	$back_reason = $GLOBALS['db']->getOne("SELECT reason_name FROM " . $GLOBALS['ecs']->table('reason') . " WHERE reason_id = " . $reason_id);
        	if(empty($back_reason)){
        		$this->error('您选择退款原因不存在，请重新选择！');
        	}
        }


		$sql_oi = "SELECT order_id,order_sn,supplier_id,order_status,shipping_status,pay_status,shipping_time_end,extension_code,(goods_amount +  insure_fee + pay_fee + pack_fee + card_fee + tax - discount - integral_money - bonus) AS total_fee,shipping_fee FROM " . $GLOBALS['ecs']->table('order_info') . " WHERE user_id='$user_id' AND order_id = " . $order_id;
	    $order_info = $GLOBALS['db']->getRow($sql_oi);


	    if(empty($order_info)){
	    	$this->error('非法操作！');
	    }

	    //判断是否有整单退款退货
	    $back_info_num = "SELECT COUNT(*) FROM " . $GLOBALS['ecs']->table('back_order') .
        " WHERE order_id = " . $order_id . " AND user_id='$user_id' AND goods_id=0 AND status_back < 6";
	    if ($GLOBALS['db']->getOne($back_info_num) > 0)
	    {
	        $this->error('对不起！您没权限操作该订单');
	    }


	    //判断单件商品是否有退款退货
	    if($goods_id>0){
	    	$back_info_num2 = "SELECT COUNT(*) FROM " . $GLOBALS['ecs']->table('back_order') .
	        " WHERE order_id = " . $order_id . " AND user_id='$user_id' AND goods_id='$goods_id' AND product_id='$product_id' AND status_back < 6";
		    if ($GLOBALS['db']->getOne($back_info_num2) > 0)
		    {
		        $this->error('对不起！您没权限操作该订单');
		    }
	    }

	    //$min_time = local_strtotime(local_date('Y-m-d H:i:s', strtotime('-'.$GLOBALS['_CFG']['shouhou_time'].' days')));//则自确认收货起$GLOBALS['_CFG']['comment_youxiaoqi']天内买家可以申请售后
	    $min_time = local_strtotime(local_date('Y-m-d H:i:s', strtotime('-7 days')));//则自确认收货起$GLOBALS['_CFG']['comment_youxiaoqi']天内买家可以申请售后   默认7天，还没做成后台设置该值



	    //服务类型[服务商品只能退款]
        //仅退款【未收到货（包含未签收），或卖家协商同意前提下】
	    //退款退货【已收到货需要退货已收到的货物】
	    if(in_array($order_info['order_status'],array(1,5)) && in_array($order_info['shipping_status'],array(0,1,3,5))  && $order_info['pay_status']==2){
	    	if(!in_array($back_type,array(4))){
	        	$this->error('对不起，你该订单只能申请退款');
	        }
	    }elseif($order_info['order_status']==5 && $order_info['shipping_status']==1  && $order_info['pay_status']==2 && $order_info['extension_code']!='virtual_good'){

	        if(!in_array($back_type,array(1,4))){
	        	$this->error('对不起，你该订单只能申请退款或退款退货类型');
	        }
	    }elseif($order_info['order_status']==5 && $order_info['shipping_status']==2 && $order_info['pay_status']==2 && $order_info['shipping_time_end']>$min_time){
	    	if($order_info['extension_code']=='virtual_good'){
	    		$this->error('非法操作');
	    	}
	    	if($back_type!=1){
	    		$this->error('对不起，你该订单已收到货，只能申请退款退货类型');
	    	}
	    }
	    else{
	    	$this->error('非法操作');
	    }

	    $where="";
        if($goods_id>0){
        	$where=" AND og.goods_id=$goods_id AND og.product_id=$product_id";
        }
		$sql_og = "SELECT  og.goods_id, og.product_id,og.goods_sn,og.goods_name, og.goods_number, " .
            "og.goods_price, og.goods_attr,  " .
            "og.goods_price * og.goods_number AS subtotal,  og.order_id, og.extension_code  " .
            "FROM " . $GLOBALS['ecs']->table('order_goods') . "as og " .
            " WHERE og.order_id = '$order_id' $where";
        $goods_list = $GLOBALS['db']->getAll($sql_og);

        if(empty($goods_list)){
        	$this->error('非法操作');
        }

	    //判断该订单有几种商品(只有一种的话，则默认为整单。以上的则$goods_id>0为整单，或者为单件)
	    $order_goods_num =$GLOBALS['db']->getOne("SELECT COUNT(*) FROM " . $GLOBALS['ecs']->table('order_goods') .
	        " WHERE order_id = " . $order_id . "");


	    if($order_goods_num>0){
	    	if($goods_id>0){
	    		$order_all=0;  //单件退款或退货
	    	}
	    	else{
	    		$order_all=1;  //整单退款或退货
	    	}
	    }
	    else{
	    	$order_all=1;
	    }

	    $add_time = gmtime();




		$data['type']=($order_info['extension_code']=='virtual_good')?1:0;
        $data['order_sn']=$order_info['order_sn'];
	    $data['order_id']=$order_info['order_id'];
	    $data['user_id']=$user_id;
	    $data['add_time']=$add_time;
    	$data['postscript']=$back_postscript;
    	$data['back_reason']=$back_reason;
    	$data['back_type']=$back_type;
    	$data['status_back']=5;
    	$data['supplier_id']=$order_info['supplier_id'];
    	$data['shipping_fee']=$order_info['shipping_fee'];
    	$data['back_pay']=$back_pay;
	    if($order_all==1){
	    	$data['goods_id']=0;
	    	$data['product_id']=0;
	    	$data['goods_name']='';
	    	$data['refund_money_1']=$order_info['total_fee'];
	    }
	    else{

	    	$tui_goods_number   = !empty($this->data['tui_goods_number'])?intval($this->data['tui_goods_number']):0; //退款数量
	    	if($tui_goods_number<=0){
	    		$this->error('请填写您的退款数量');
	    	}
	    	if($tui_goods_number>$goods_list[0]['goods_number']){
	    		$this->error('请填写您的退款数量不能大于'.$goods_list[0]['goods_number']);
	    	}
	    	$data['goods_id']=$goods_id;
	    	$data['product_id']=$goods_list[0]['product_id'];
	    	$data['goods_name']=$goods_list[0]['goods_name'];
	    	$data['refund_money_1']=$tui_goods_number*$goods_list[0]['goods_price'];
	    }



	    //凭证图
		$upload_img=array();
		// 处理图片
		if($back_imgs)
		{
			include_once (ROOT_PATH . '/includes/cls_image.php');
			$image = new cls_image($GLOBALS['_CFG']['bgcolor']);

			$img_path = 'refund/' . date('Ym');
			if(count($back_imgs['name']) == 1){
				//单张
				$original = $image->upload_image($back_imgs,$img_path);
				$upload_img[] = $original;
			}else{
				//多张
				for($i=0; $i <= count($back_imgs['name']);$i++ ){
					if(empty($back_imgs['tmp_name'][$i])){
						break;
					}
					$img = array('name'=>$back_imgs['name'][$i],'type'=>$back_imgs['type'][$i],'tmp_name'=>$back_imgs['tmp_name'][$i]);
					$original = $image->upload_image($img,$img_path);
					$upload_img[] = $original;
				}
			}

			//删除临时文件
	        $files = glob(ROOT_PATH.'runtime/temp/*');
	        foreach($files as $file){
	            if(is_file($file)){
	                @unlink($file);
	            }
	        }
		}

		if(!empty($upload_img)){
			foreach ($upload_img as $key => $value) {
				$upload_img[$key]='/'.$value;
			}
            $data['imgs']=implode(',', $upload_img);
		}


	    $GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('back_order'), $data, 'INSERT');
		// 插入退换货商品 80_back_goods
		$back_id = $GLOBALS['db']->insert_id();
		$have_tuikuan = 0; // 是否有退货
						   // foreach($back_type_list as $back_type)
						   // {
		if($back_type == 1)//退货
		{
			/*$have_tuikuan = 1;
			$tui_goods_number = $_REQUEST['tui_goods_number'] ? intval($_REQUEST['tui_goods_number']) : 1;
			$sql = "insert into " . $GLOBALS['ecs']->table('back_goods') . "(back_id, goods_id, goods_name, goods_sn, product_id, goods_attr, back_type, " . "back_goods_number, back_goods_price, status_back ) " . " values('$back_id', '$goods_id', '$goods_name', '$goods_sn', '$_REQUEST[product_id_tui]', '$_REQUEST[goods_attr_tui]', '0', " . " '$tui_goods_number', '$_REQUEST[tui_goods_price]', '5') ";
			$GLOBALS['db']->query($sql);*/
		}
		if($back_type == 4)//退款
		{
			/*$have_tuikuan = 1;
			$have_tuikuan2 = 1;
			$price_refund_all = 0;

			foreach($order_info['goods_list'] as $goods_info)
			{
				$price_refund_all += ($goods_info['goods_price'] * $goods_info['goods_number']);

				$sql = "INSERT INTO " . $GLOBALS['ecs']->table('back_goods') . "(back_id, goods_id, goods_name, goods_sn, product_id, goods_attr, back_type, " . "back_goods_number, back_goods_price, status_back) " . " values('$back_id', '".$goods_info['goods_id']."', '".$goods_info['goods_name']."', '".$goods_info['goods_sn']."', '".$goods_info['product_id']."', '".$goods_info['goods_attr']."', '4', '".$goods_info['goods_number']."', '".$goods_info['goods_price']."', '5') ";
				$GLOBALS['db']->query($sql);
			}*/
		}
		if($back_type == 1 || $back_type == 4){ //退款或退货
			$have_tuikuan = 1;
			foreach ($goods_list as $key2 => $value2) {
				$data2['back_id']=$back_id;
				$data2['goods_id']=$value2['goods_id'];
				$data2['goods_name']=$value2['goods_name'];
				$data2['goods_sn']=$value2['goods_sn'];
				$data2['product_id']=$value2['product_id'];
				$data2['goods_attr']=$value2['goods_attr'];
				$data2['back_type']=$back_type;
				$data2['back_goods_number']=$order_all?$value2['goods_number']:$tui_goods_number;
				$data2['back_goods_price']=$order_all?$value2['subtotal']:$tui_goods_number.$value2['goods_price'];
				$data2['status_back']=5;
				$GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('back_goods'), $data2, 'INSERT');

			}

		}
		if($back_type == 2)//换货
		{
			/*$huan_count = count($_POST['product_id_huan']);
			if($huan_count)
			{
				$sql = "insert into " . $GLOBALS['ecs']->table('back_goods') . "(back_id, goods_id, goods_name, goods_sn, product_id, goods_attr, back_type, status_refund, back_goods_number, status_back) " . " values('$back_id', '$goods_id', '$goods_name', '$goods_sn', '$_REQUEST[product_id_tui]', '$_REQUEST[goods_attr_tui]', '1', '9', '$huan_count', '5') ";
				$GLOBALS['db']->query($sql);
				$parent_id_huan = $GLOBALS['db']->insert_id();
				foreach($_POST['product_id_huan'] as $pid_key => $pid_huan)
				{
					$sql = "insert into " . $GLOBALS['ecs']->table('back_goods') . "(back_id, goods_id, goods_name, goods_sn, product_id, goods_attr,  back_type, parent_id, status_refund, back_goods_number, status_back) " . "values('$back_id', '$goods_id', '$goods_name', '$goods_sn',  '$pid_huan', '" . $_POST['goods_attr_huan'][$pid_key] . "', '2', '$parent_id_huan', '9', '1', '5')";
					$GLOBALS['db']->query($sql);
				}
			}*/
		}
		if($back_type == 3) //维修
		{
			/*$have_weixiu = 1;
			$tui_goods_number = $_REQUEST['tui_goods_number'] ? intval($_REQUEST['tui_goods_number']) : 1;
			$sql = "insert into " . $GLOBALS['ecs']->table('back_goods') . "(back_id, goods_id, goods_name, goods_sn, product_id, goods_attr, back_type, " . "back_goods_number, back_goods_price, status_back) " . " values('$back_id', '$goods_id', '$goods_name', '$goods_sn', '$_REQUEST[product_id_tui]', '$_REQUEST[goods_attr_tui]', '3', " . " '$tui_goods_number', '$_REQUEST[tui_goods_price]', '5') ";
			$GLOBALS['db']->query($sql);*/
		}
		// }

		/* 更新back_order */
		if($have_tuikuan)
		{
			/*if ($_POST['order_all'])
			{
				$price_refund = $GLOBALS['db']->getOne("SELECT money_paid FROM " . $GLOBALS['ecs']->table('order_info') . " WHERE order_id = " . $order_id);
			}
			else
			{
				$price_refund = $_REQUEST['tui_goods_price'] * $tui_goods_number;
			}
			$sql = "update " . $GLOBALS['ecs']->table('back_order') . " set refund_money_1= '$price_refund' where back_id='$back_id' ";
			$GLOBALS['db']->query($sql);*/
		}
		else
		{
			$sql = "update " . $GLOBALS['ecs']->table('back_order') . " set status_refund= '9' where back_id='$back_id' ";
			$GLOBALS['db']->query($sql);
		}


		$this->success('提交成功');

	}

	//退款退货列表
	public function refundList()
	{
		$user_id = $this->user_id;
		$page      = !empty($this->data['page']) ? intval($this->data['page']) : 1;
		$page_size = !empty($this->data['page_size']) ? intval($this->data['page_size']) : 10;
		$status = !empty($this->data['status']) ? intval($this->data['status']) : 0;
		$type = isset($this->data['type']) ? $this->data['type'] : '';//0（虚拟商品）、1（真实商品）

		$page_start = $page_size*($page-1);

		$sql_w = '';
		if($type != ''){
			$sql_w .= " AND type = '$type' ";
		}
		switch($status){
			case 1:
				$sql_w .= ' AND status_back = 5  ';
				break;
			case 2:
				$sql_w .= ' AND status_back = 3 AND status_refund = 1 ';
				break;
		}
		/* 取得订单列表 */
		$arr = array();

		$sql = "SELECT * " . " FROM " . $GLOBALS['ecs']->table('back_order') . " WHERE user_id = '$user_id' ".$sql_w." ORDER BY add_time DESC";
		$res = $GLOBALS['db']->SelectLimit($sql, $page_size, $page_start);

		//1为退货 2为换货 3为申请返修 4为退款（无需退货）
		$sttus_type = array(1=>'退货',2=>'换货',3=>'申请返修',4=>'退款（无需退货）');

		while($row = $GLOBALS['db']->fetchRow($res))
		{
			$row0['back_id'] = $row['back_id'];
			$row0['back_sn'] = $row['order_sn'].$row['back_id'];//退款单号
			$row0['order_sn'] = $row['order_sn'];
			$row0['refund_time'] = local_date($GLOBALS['_CFG']['time_format'], $row['add_time']);
			$row0['refund_status'] = $sttus_type[$row['back_type']];

			$row0['status'] = $row['status_back'];
			//0:审核通过,1:收到寄回商品,2:换回商品已寄出,3:完成退货/返修,4:退款(无需退货),5:审核中,6:申请被拒绝,7:管理员取消,8:用户自己取消
			switch ($row['status_back']) {
				case '0':
					$row0['status_back'] = '审核通过';
					break;
				case '1':
					$row0['status_back'] = '收到商品';
					break;
				case '2':
					$row0['status_back'] = '商品已寄出';
					break;
				case '3':
					$row0['status_back'] = '完成退货';
					break;
				case '4':
					$row0['status_back'] = '退款(无需退货)';
					break;
				case '5':
					$row0['status_back'] = '审核中';
					break;
				case '6':
					$row0['status_back'] = '申请被拒绝';
					break;
				case '7':
					$row0['status_back'] = '管理员取消';
					break;
				case '8':
					$row0['status_back'] = '取消';
					break;
				default:
					$row0['status_back'] = '取消';
					break;
			}
			//$row0['status_back'] = $GLOBALS['_LANG']['bps'][$row['status_refund']];

			$sql_goods = "SELECT bg.goods_id,bg.goods_name,bg.back_goods_price,bg.back_goods_number,bg.status_refund,bg.goods_attr ,g.goods_thumb FROM " . $GLOBALS['ecs']->table('back_goods') . " as bg left join " . $GLOBALS['ecs']->table('goods') . " AS g " . " on bg.goods_id=g.goods_id  " . " WHERE back_id = " . $row['back_id'];
			$row0['goods_list'] = $GLOBALS['db']->getAll($sql_goods);

			foreach($row0['goods_list'] as $key=>$value){
				$row0['goods_list'][$key]['goods_attr'] = preg_replace("/\[.*\]/", '', $value['goods_attr']);//属性处理，去掉中括号及里面的内容。如：颜色:粉色[798] 尺码:S[798] 变为 颜色:粉色 尺码:S
				$row0['goods_list'][$key]['format_back_goods_price'] = price_format($value['back_goods_price'], false);
			}

			$arr[] = $row0;
		}

		$count = $GLOBALS['db']->getOne("SELECT COUNT(*) FROM " . $GLOBALS['ecs']->table('back_order') . " WHERE user_id = '$user_id' ".$sql_w." ");
		//分页
        $pager = array();
        $pager['page']         = $page;
        $pager['page_size']    = $page_size;
        $pager['record_count'] = $count;
        $pager['page_count']   = $page_count = ($count > 0) ? intval(ceil($count / $page_size)) : 1;

        $refund_data['list'] = $arr;
        $refund_data['pager'] = $pager;
		$this->success($refund_data);
	}

	//退款退货 详情
	public function refundDetails()
	{

		$_LANG = $GLOBALS['_LANG'];
		$db = $GLOBALS['db'];
		$ecs = $GLOBALS['ecs'];
		$user_id = $this->user_id;
		$back_id = ! empty($_REQUEST['back_id']) ? intval($_REQUEST['back_id']) : 0;

		$sql = "SELECT * " . " FROM " . $GLOBALS['ecs']->table('back_order') . " WHERE user_id = '$user_id' and  back_id= '$back_id' ";
		$back_shipping = $db->getRow($sql);
		if(empty($back_shipping)){
			$this->error("非法操作");
		}

		// 退货商品 + 换货商品 详细信息
		$list_backgoods = array();
		$list_backgoods2 = array();
		$sql = "select bg.*,g.goods_thumb,g.goods_img,g.original_img from " . $GLOBALS['ecs']->table('back_goods') . " as bg left join " . $GLOBALS['ecs']->table('goods') . " AS g " . " on bg.goods_id=g.goods_id  " . " where back_id = '$back_id' order by back_type ";

		$res_backgoods = $db->query($sql);
		while($row_backgoods = $db->fetchRow($res_backgoods))
		{

			$list_backgoods['goods_id'] = $row_backgoods['goods_id'];
			$list_backgoods['goods_name'] = $row_backgoods['goods_name'];
			$row_backgoods['goods_attr'] = preg_replace("/\[.*\]/", '', $row_backgoods['goods_attr']);//属性处理，去掉中括号及里面的内容。如：颜色:粉色[798] 尺码:S[798] 变为 颜色:粉色 尺码:S
			$list_backgoods['goods_attr'] = $row_backgoods['goods_attr'];
			$list_backgoods['back_goods_number'] = $row_backgoods['back_goods_number'];
			$list_backgoods['back_goods_price'] = $row_backgoods['back_goods_price'];
			$list_backgoods['format_back_goods_price'] = price_format($row_backgoods['back_goods_price'], false);
			$list_backgoods['status_refund'] = $_LANG['bps'][$row_backgoods['status_refund']];
			$list_backgoods['goods_thumb'] = $row_backgoods['goods_thumb'];

			$list_backgoods2[] = $list_backgoods;
		}

		/* 回复留言 增加 */
		$res = $db->getAll("SELECT * FROM " . $GLOBALS['ecs']->table('back_replay') . " WHERE back_id = '$back_id' ORDER BY add_time ASC");
		$back_replay = array();
		foreach($res as $value)
		{
			$value['add_time'] = local_date("Y-m-d H:i", $value['add_time']);
			$back_replay[] = $value;
		}

		$sttus_type = array(1=>'退货',2=>'换货',3=>'申请返修',4=>'退款（无需退货）');
		$orderInfo = $db->getRow("SELECT *,(goods_amount + shipping_fee + insure_fee + pay_fee + pack_fee + card_fee + tax - discount) as total FROM  " . $GLOBALS['ecs']->table('order_info') . " where order_id = ".$back_shipping['order_id']);

		$result = array();
		$result['back_sn'] = $orderInfo['order_sn'].$back_id;//退款单号
		$result['order_sn'] = $orderInfo['order_sn'];//订单号
		$result['back_status'] = $_LANG['bos'][$back_shipping['status_back']];
		$result['refund_time'] = local_date("Y-m-d H:i", $back_shipping['add_time']);//退款时间
		$result['add_time'] = local_date("Y-m-d H:i", $orderInfo['add_time']);//下单时间
		$result['goods_list'] = $list_backgoods2;//商品列表
		$result['refund_type'] = $sttus_type[$back_shipping['back_type']];
		$result['order_amount'] = $orderInfo['total'];//下单金额
		$result['format_order_amount'] = price_format($orderInfo['total'], false);//下单金额
		$result['refund_amount'] = $back_shipping['refund_money_2'];//退款金额格式化
		$result['format_refund_amount'] = price_format($back_shipping['refund_money_2'], false);//退款金额格式化
		$result['refund_reason'] = $back_shipping['back_reason'];
		$result['back_replay'] = $back_replay;

		$this->success($result);
	}

	//退款退货列表
	public function back_list ()
	{

		$db = $GLOBALS['db'];
		$ecs = $GLOBALS['ecs'];

		$user_id = $this->input('user_id');
		$status = $this->input('status');

		if(empty($user_id) || !isset($user_id)){
			$this->error("请先登录");
		}

		$paper = array();
		$pager['page_size'] = $this->input('page_size',15);
		$pager['page'] = $this->input('page',1);

		include_once (ROOT_PATH . 'includes/lib_transaction.php');

		//$page = isset($_REQUEST['page']) ? intval($_REQUEST['page']) : 1;

		$record_count = $db->getOne("SELECT COUNT(*) FROM " . $ecs->table('back_order') . " WHERE user_id = '$user_id'");

		// $pager = get_pager('user.php', array(
			// 'act' => $action
		// ), $record_count, $page);

		$orders = $this->get_user_backorders($user_id, $pager['page_size'], $pager['page']-1, $status );

		//print_r($orders);
		//$smarty->assign('pager', $pager);
		//$smarty->assign('orders', $orders);
		//$smarty->display('user_transaction.dwt');

		$this->success($orders);
	}

	private function get_user_backorders ($user_id, $num = 10, $start = 0, $status = 0)
	{
		$sql_w = '';
		switch($status){
			case 1:
				$sql_w = ' AND bo.status_back = 5  ';
				break;
			case 2:
				$sql_w = ' AND bo.status_back = 3 AND bo.status_refund = 1 ';
				break;
		}
		/* 取得订单列表 */
		$arr = array();

		$sql = "SELECT bo.*, g.goods_name,g.goods_thumb,g.goods_img,g.original_img " . " FROM " . $GLOBALS['ecs']->table('back_order') . " AS bo left join " . $GLOBALS['ecs']->table('goods') . " AS g " . " on bo.goods_id=g.goods_id  " . " WHERE user_id = '$user_id' ".$sql_w." ORDER BY add_time DESC";
		$res = $GLOBALS['db']->SelectLimit($sql, $num, $start);

		//1为退货 2为换货 3为申请返修 4为退款（无需退货）
		$sttus_type = array(1=>'退货',2=>'换货',3=>'申请返修',4=>'退款（无需退货）');

		while($row = $GLOBALS['db']->fetchRow($res))
		{

			$row0['refund_time'] = local_date($GLOBALS['_CFG']['time_format'], $row['add_time']);
			$row0['refund_id'] = $row['back_id'];
			$row0['refund_money_1'] = price_format($row['refund_money_1'], false);

			$row0['refund_status'] = $sttus_type[$row['back_type']];

			// $row0['goods_url'] = build_uri('goods', array(
				// 'gid' => $row['goods_id']
			// ), $row['goods_name']);
			$row0['status_back_1'] = $row['status_back'];
			$row0['status_back'] = $GLOBALS['_LANG']['bos'][(($row['back_type'] == 4 && $row['status_back'] != 8) ? $row['back_type'] : $row['status_back'])] . ' - ' . $GLOBALS['_LANG']['bps'][$row['status_refund']];

			$sql_goods = "SELECT bg.* ,g.goods_thumb,g.goods_img,g.original_img FROM " . $GLOBALS['ecs']->table('back_goods') . " as bg left join " . $GLOBALS['ecs']->table('goods') . " AS g " . " on bg.product_id=g.goods_id  " . " WHERE back_id = " . $row['back_id'];
			$row0['goods_list'] = $GLOBALS['db']->getAll($sql_goods);
			//去null
			foreach($row0['goods_list'] as $key=>$value){
				foreach($value as $k=>$v){
					if(empty($v)){
						$row0['goods_list'][$key][$k] = '';
					}
				}
			}

			$arr[] = $row0;
		}
		//$arr['status_back'] = $GLOBALS['_LANG']['bos'][(($arr['back_type'] == 4 && $arr['status_back'] != 8) ? $row['back_type'] : $arr['status_back'])] . ' - ' . $GLOBALS['_LANG']['bps'][$arr['status_refund']];

		return $arr;
	}

	/**
	 * @description 到货通知
	 */
	public function arrivalNotice(){
		$goods_id = !empty($this->data['goods_id'])?intval($this->data['goods_id']):0;//商品ID
		$number = !empty($this->data['number'])?intval($this->data['number']):1;//商品数量
		$tel = !empty($this->data['tel'])?trim($this->data['tel']):0;//手机号码
		$email = !empty($this->data['email'])?trim($this->data['email']):0;//邮箱地址

		if(empty($goods_id)){
			$this->error('操作失败，缺少商品ID参数！');
		}
		if(empty($tel)){
			$this->error('操作失败，缺少手机号码参数！');
		}
		$result = $this->user->arrival_Notice($this->user_id,$goods_id,$number,$tel,$email);
		if($result['status'] == 200){
			$this->success($result['message']);
		}else{
			$this->error($result['message']);
		}
	}


	/**
	 * @description 我的留言（留言、投诉、询问、售后、求购）
	 */
	public function advise(){
		$user_id = !empty($this->data['user_id'])?intval($this->data['user_id']):'';//用户ID
		if(empty($user_id)){
			$this->error('请先登录，再操作！');
		}
		$msg_type = !empty($this->data['msg_type'])?intval($this->data['msg_type']):0;//0（留言）、1（投诉）、2（询问）、3（售后）、4（求购）
		$msg_content = !empty($this->data['msg_content'])?trim($this->data['msg_content']):'';//留言内容
		$msg_title = !empty($this->data['msg_title'])?trim($this->data['msg_title']):$msg_content;//留言标题
		$order_id = !empty($this->data['order_id'])?trim($this->data['order_id']):0;//订单ID
		if($order_id > 0){
			$msg_type = 5;
		}
		if(empty($msg_content)){
			$this->error('请填写留言内容！');
		}

		$result = $this->user->save_advise($user_id,$msg_type,$msg_content,$msg_title,$order_id);
		if($result['status'] == 200){
			$this->success($result['message']);
		}else{
			$this->error($result['message']);
		}
	}


	/**
	 * @description 我参与的砍价活动商品列表
	 * @param integer user_id 用户ID
	 */
	public function getBargain ()
	{
		$page      = !empty($this->data['page'])?intval($this->data['page']):1;
		$page_size = !empty($this->data['page_size'])?intval($this->data['page_size']):10;

		$page_start = $page_size*($page-1);

		$sql = "SELECT * FROM " . $GLOBALS['ecs']->table('bargain_log') . " WHERE help_user_id = '".$this->user_id."' and user_id = help_user_id ORDER BY status asc,add_time desc";
        $res = $GLOBALS['db'] -> selectLimit($sql, $page_size, $page_start);
        $bargain_list = array();
        while ($rows = $GLOBALS['db']->fetchRow($res)){
            $row['goods_id'] = $rows['goods_id'];
            $row['bargain_id'] = $rows['bargain_id'];
            $row['goods_thumb'] = $GLOBALS['db']->getOne("SELECT goods_thumb FROM " . $GLOBALS['ecs']->table('goods') . " WHERE goods_id = '".$rows['goods_id']."' ");
            $bargain_data = $GLOBALS['db']->getRow("SELECT goods_name,product_id,shop_price,low_price FROM " . $GLOBALS['ecs']->table('bargain_activity') . " WHERE id = '".$rows['bargain_id']."' ");
            $row['goods_name'] = $bargain_data['goods_name'];
            $row['shop_price'] = $bargain_data['shop_price'];
            $row['low_price'] = $bargain_data['low_price'];

            $row['order_id'] = $rows['order_id'];
            $row['status'] = $rows['status'];
            if($rows['status']){
            	$row['status_name'] = '活动已结束';//点击不做跳转
            }else{
            	$row['status_name'] = '活动进行中';//点击跳转砍价活动进度详情页
            }

            //获取最低价
            $log_info = $GLOBALS['db']->getRow("SELECT * FROM " . $GLOBALS['ecs']->table('bargain_log') . " WHERE bargain_id = '".$rows['bargain_id']."' and help_user_id = '".$rows['help_user_id']."' and product_id = '".$rows['product_id']."' and order_id = '".$rows['order_id']."' and goods_id = '".$rows['goods_id']."' order by now_price asc");
            $row['now_price'] = $log_info['now_price'];

            //$row['add_time'] = date('Y-m-d',$rows['add_time']);
            $row['format_shop_price'] = price_format($row['shop_price']);
            $row['format_low_price'] = price_format($row['low_price']);
            $row['format_now_price'] = price_format($row['now_price']);
            $bargain_list[] = $row;
        }

		$count = $GLOBALS['db']->getOne("SELECT count(*) FROM " . $GLOBALS['ecs']->table('bargain_log') . " WHERE help_user_id = '".$this->user_id."' and user_id = help_user_id ");

		//分页
        $pager = array();
        $pager['page']         = $page;
        $pager['page_size']    = $page_size;
        $pager['record_count'] = $count;
        $pager['page_count']   = $page_count = ($count > 0) ? intval(ceil($count / $page_size)) : 1;

        $comment_data = array();
        $comment_data['list'] = $bargain_list;
        $comment_data['pager'] = $pager;
		$this->success($comment_data);
	}

	/**
	 * 保存发票抬头
	 */
	public function saveInvTitle(){
		$user_id = $this->input('user_id', 0);
		$inv_title = $this->input('inv_title', '');
	    $result = $this->user->save_Inv_Title($user_id, $inv_title);
	    if($result){
	        $this->success('保存成功');
	    }else{
	        $this->error('保存失败');
	    }
	}

	/**
	 * 删除发票抬头
	 */
	public function delInvTitle(){
		$user_id = $this->input('user_id', 0);
		$inv_title = $this->input('inv_title', '');
	    $result = $this->user->del_Inv_Title($user_id, $inv_title);
	    $this->success('删除成功');
	}

	/**
	 * 用户发票公司抬头
	 */
	public function getInvTitle ()
	{
		$user_id = $this->input('user_id', 0);
		$inv_title_list = $this->user->get_Inv_Title($user_id);
        $this->success($inv_title_list);
	}

	/**
	 * @description 我的作品列表
	 * @param integer user_id 用户ID
	 * @param integer status 状态 0（所有作品）、1（待审核）、2（未通过）
	 */
	public function getFindsList()
	{
		$status = $this->input('status', 0);
		$user_id = $this->input('user_id', '');
		$page = $this->input('page', 1);
		$num = $this->input('num', 11);

		if (!$user_id)
		{
			$this->error("非法操作！");die;
		}

		$where = "user_id=$user_id";
		if ($status != 0)
		{
			$where .= " and state=$status";
		}

		$field = "*";
		$result = $this->user->getFinds($field,$where,$num,$page);


		$sql = "SELECT count(find_id) FROM ". $GLOBALS['ecs']->table('finds')." WHERE $where";
		$count = $GLOBALS['db']->getOne($sql);

		//分页
    $pager = array();
    $pager['page']         = $page;
    $pager['page_size']    = $num;
    $pager['record_count'] = $count;
    $pager['page_count']   = $page_count = ($count > 0) ? intval(ceil($count / $num)) : 1;

    $finds_data['list'] = $result;
    $finds_data['pager'] = $pager;


		$this->success($finds_data);
	}

	// 添加我的作品 作品领域列表
	public function addFinds()
	{
		$sql = "SELECT * FROM ". $GLOBALS['ecs']->table('finds_type'). " order by en_name asc";
		$list = $GLOBALS['db']->getAll($sql);
		$arr2 = array();
		$arr = array();

		foreach ($list as $key => $value)
		{
			$arr[$value['en_name']][] = $value;

			if ($value['is_common'] == 1)
			{
				$arr2[] = $value;
			}
		}

		$data['list'] = $list;
		$data['en_list'] = $arr;

		$data['list'] = $arr;
		$data['common'] = $arr2;
		$this->success($data);
	}

	//添加我的作品 数据
	//add_works_images 图片添加方法
	//add_works_cover 封面处理方法
	public function ajxAddFinds(){
		$user_id = $this->input('user_id','');
		$data_array = $this->input('add_data','');
		//PCfaseimg作品封面图 imgdatas作品图数组 productions领域 tagss标签
		//print_r(1);exit;
		//作品图处理 处理成id组合字符串
		//$arr_id_data=array();
		foreach($data_array['imgdatas'] as $key=>$value){
			$data = $this->user->add_works_images($value);
			if($data!=0){
				$arr_id_data[$key] = $data;
			}
		}
		$str_id_data = implode(",", $arr_id_data);
		//封面处理
		$PCimg_data = $this->user->add_works_cover($data_array['PCfaseimg']);
		$wapimg_data = $this->user->add_works_cover($data_array['wapfaseimg']);
		//$this->success($PCimg_data);exit;
		if($PCimg_data!=0){
			$PCimg_url = $PCimg_data;
			$wapimg_url = $wapimg_data;
		}

		//领域处理
		$str_productions_data = implode(",", $data_array['productions']);

		//标签处理
		//检查是否有使用过的标签 统计处理
		foreach($data_array['tagss'] as $k=>$v){
			$select = "SELECT * FROM ".$GLOBALS['ecs']->table('production_tags')." WHERE `tags_name`='$v' ";
			$tags_data = $GLOBALS['db']->getAll($select);
			$tags_id=$tags_data[0]['tags_id'];
			if(!empty($tags_data)){
				$num = $tags_data[0]['number']+1;
				$tags_num_sql = "UPDATE ".$GLOBALS['ecs']->table('production_tags')." SET number='$num' WHERE tags_id='$tags_id'";
				if((string)$GLOBALS['db']->query($tags_num_sql)){
					$tags_id_array[]=$tags_id;
				}
			}else{
				$add_time = time();
				$tags_name = $v;
				$tags_sql = "INSERT INTO ".$GLOBALS['ecs']->table('production_tags')."(`tags_name`,`addtime`) values ('$tags_name','$add_time') ";
				$GLOBALS['db']->query($tags_sql);
				$tags_id_array[]=$GLOBALS['db']->insert_id();
			}
		}
		if(count($tags_id_array) > 1){
			$str_tags = implode(",", $tags_id_array);
		}else{
			$str_tags = $tags_id_array[0];
		}
		//shorts简介  title标题 contents介绍 claim_type版权类型
		$shorts = $data_array['shorts'];
		$title = $data_array['title'];
		$contents = $data_array['contents'];
		$claim_type = $data_array['claim_type'];

		$sql = "INSERT INTO ".$GLOBALS['ecs']->table('finds'). "(`user_id`,`title`,`short`,`claim_type`,`production_type`,`production_img`,`content`,`label`,`pc_surface_img`,`wap_surface_img`) values ('$user_id','$title','$shorts','$claim_type','$str_productions_data','$str_id_data','$contents','$str_tags','$PCimg_url','$wapimg_url') ";
		$GLOBALS['db']->query($sql);
		$find_id=$GLOBALS['db']->insert_id();
		$this->success($find_id);
	}



	//删除我的作品 未完成
	public function delFinds(){
		$user_id = $this->input('user_id','');
		$find_id = $this->input('find_id','');
		//$del=$this->user->del_finds($user_id,$find_id);
		$where = " find_id = $find_id AND user_id = $user_id ";
		$sql = "DELETE FROM ".$GLOBALS['ecs']->table('finds')." WHERE $where";
		//$del_find = $GLOBALS['db']->query($sql);
		$this->success($GLOBALS['db']->query($sql));
	}

	//我收藏的作品列表
	public function productCollectList(){
		$user_id = $this->input('user_id','');
		$start = $this->input('start','0');
		$num = $this->input('num','12');

		$result = $this->user->product_collect($user_id,$start,$num);
		//$this->success($result);exit;
		$count = $this->user->product_collect_count($user_id);

		//分页
		$pager = array();
		$pager['page']         = $start;
		$pager['page_size']    = $num;
		$pager['record_count'] = $count;
		$pager['page_count']   = $page_count = ($count > 0) ? intval(ceil($count / $num)) : 1;

		$collect_data['list'] = $result;
		$collect_data['pager'] = $pager;

		$this->success($collect_data);


	}

	/**
	 * 点击取消作品收藏
	 * @param user_id
	 * @param find_id
	 */
	public function cancelCollect(){
		$user_id = $this->input('user_id','');
		$find_id = $this->input('find_id','');

		if(!empty($user_id)&&!empty($find_id)&&$user_id!=0&&$find_id!=0){
			$del_sql = "DELETE FROM ".$GLOBALS['ecs']->table('product_collect')." WHERE user_id='$user_id' AND find_id='$find_id'";
			$result = (string)$GLOBALS['db']->query($del_sql);
			if($result){
				$cancel_collect['code'] = 1;
			}else{
				$cancel_collect['code'] = 0;
			}
		}else{
			$cancel_collect['code'] = 0;
		}

		$this->success($cancel_collect);
	}

	/**
	 * 添加好友关注 / 取消好友关注
	 */
	public function add_user_attention()
	{
		if(!$this->user_id){
			$this->error('请先登录');
		}
		$be_user_id = $this->input('be_user_id')?:$this->error('请指定要关注的用户');
		if ($this->user_id == $be_user_id) {
			$this->error('不能关注自己');
		}
		$result = $this->user->add_user_attention($this->user_id,$be_user_id);
		if($result['status'] == 200){
			unset($result['status']);
			$this->success($result);
		}else{
			unset($result['status']);
			$this->error($result);
		}
	}

	/************************************/
	// 获取商品列表
	/************************************/
	public function getGoodsList()
	{
		$status = $this->input('status', 0);
		$sell_out = $this->input('sell_out', 0);
		$user_id = $this->input('user_id', '');
		$page = $this->input('page', 1);
		$num = $this->input('num', 15);

		if (!$user_id)
		{
			$this->error("非法操作！");die;
		}
		// $where = '1';
		$where = "user_id = $user_id";
		if ($status != 0)
		{
			$where .= " AND goods_status = $status";
		}
		if($sell_out){
			$where .= " AND goods_number = 0";
		}

		$field = "*";
		$sql = "SELECT * FROM ".$GLOBALS['ecs']->table('goods')." WHERE $where";
		$res = $GLOBALS['db']->selectLimit($sql, $num, ($page - 1) * $num);
		while ($row = $GLOBALS['db']->fetchRow($res))
    {
      $result[] = $row;
    }

		$sql = "SELECT count(goods_id) FROM ". $GLOBALS['ecs']->table('goods')." WHERE $where";
		$count = $GLOBALS['db']->getOne($sql);

		//分页
    $pager = array();
    $pager['page']         = $page;
    $pager['page_size']    = $num;
    $pager['record_count'] = $count;
    $pager['page_count']   = $page_count = ($count > 0) ? intval(ceil($count / $num)) : 1;

    $list_data['list'] = $result;
    $list_data['pager'] = $pager;

		$this->success($list_data);
	}

	/************************************/
	// 获取设计库列表
	/************************************/
	public function getDiyList()
	{
		$status = $this->input('status', -1);
		$type = $this->input('type', 0);
		$user_id = $this->input('user_id', '');
		$page = $this->input('page', 1);
		if($page == 1){
			$num = $this->input('num', 14);
			$start = ($page - 1) * $num;
		}else{
			$num = $this->input('num', 15);
			$start = (($page - 1) * $num) - 1;
		}

		if (!$user_id)
		{
			$this->error("非法操作！");die;
		}
		$where = "d.user_id = $user_id";
		if($status > -1){
			$where .= ' AND g.goods_status = ' . $status;
		}
		if($type){
			$where .= ' AND d.type = ' . $type;
		}
		$sql = "SELECT * FROM " . $GLOBALS['ecs']->table('diy') . " d LEFT JOIN " . $GLOBALS['ecs']->table('goods') . " g ON d.goods_id = g.goods_id WHERE $where";
		$res = $GLOBALS['db']->selectLimit($sql, $num, $start);
		while ($row = $GLOBALS['db']->fetchRow($res))
    {
      $result[] = $row;
    }

		$sql = "SELECT count(d.diy_id) FROM " . $GLOBALS['ecs']->table('diy') . " d LEFT JOIN " . $GLOBALS['ecs']->table('goods') . " g ON d.goods_id = g.goods_id WHERE $where";
		$count = $GLOBALS['db']->getOne($sql);

		//分页
    $pager = array();
    $pager['page']         = $page;
    $pager['page_size']    = $num;
    $pager['record_count'] = $count;
    if($page == 1){
    	$page_count = ($count > 0) ? (($count - $num) / 15) + 1 : 1;
    }else{
    	$page_count = ($count > 0) ? (($count - 14) / $num) + 1 : 1;
    }
    $pager['page_count']   = $page_count;

    $list_data['list'] = $result;
    $list_data['pager'] = $pager;

		$this->success($list_data);
	}

	/**
	 * 检查银行卡是否合法
	 */
	public function getBankCard(){
		if (empty($this->data['card_number'])) {
			$this->error('请填写银行卡');
		}
		
		$result = $this->user->get_Bank_Card($this->data['card_number']);
        if($result){
			$this->success($result);
		} else {
			$this->error('银行卡号码不合法');
		}
	}

	/**
	 * 第三方银行卡验证接口
	 */
	public function checkBankCard(){
		if (empty($this->data['card_number'])) {
			$this->error('请填写银行卡');
		}
		
		$result = $this->user->check_Bank_Card($this->data['card_number'], $this->data['real_name'], $this->data['card']);
        if($result && $result['resp']['code'] == 0){
			$this->success($result);
		} elseif ($result && $result['resp']['code'] > 0) {
			$this->error($result['resp']['desc']);
		} else {
			$this->error('第三方验证接口失败');
		}
	}

	/**
	 * 获取推荐设计师
	 */
	public function getRecommendUsers(){
  		$recommend_users = $this->user->get_Recommend_Users($this->login_user_id);
  		$this->success($recommend_users);
	}

	/**
	 * 搜索用户商品价格列表
	 */
	public function getUserPrice(){
		$designer_id = $this->input('designer_id');
		$sql = "SELECT distinct shop_price FROM " . $GLOBALS['ecs']->table('goods') . " WHERE user_id = '$designer_id' AND goods_status = 4";
		$list = $GLOBALS['db']->getCol($sql);
		$this->success($list);
	}

	//排行榜
	public function rankingList()
	{
		$user_id = $this->user_id;
		$page      = !empty($this->data['page']) ? intval($this->data['page']) : 1;
		$page_size = !empty($this->data['page_size']) ? intval($this->data['page_size']) : 10;
		// $status = !empty($this->data['status']) ? intval($this->data['status']) : 0;

		$page_start = $page_size*($page-1);

		$sql_w = ' WHERE b.user_id IS NOT NULL ';
		/*if($type != ''){
			$sql_w .= " AND type = '$type' ";
		}
		switch($status){
			case 1:
				$sql_w .= ' AND status_back = 5  ';
				break;
			case 2:
				$sql_w .= ' AND status_back = 3 AND status_refund = 1 ';
				break;
		}*/
		/* 取得用户列表 */
		$arr = array();

		$sql = "SELECT DISTINCT a.user_id, a.* FROM " . $GLOBALS['ecs']->table('users') . " a INNER JOIN " . $GLOBALS['ecs']->table('goods') . " b ON a.user_id = b.user_id AND b.goods_status = 4 " . $sql_w . " ORDER BY rank_sale_number DESC, sale_amount DESC, reg_time DESC";
		$res = $GLOBALS['db']->SelectLimit($sql, $page_size, $page_start);

		while($row = $GLOBALS['db']->fetchRow($res))
		{
			$arr[] = $row;
		}
		foreach ($arr as $key => $value) {
			$arr[$key]['rank'] = $page_start + $key +1;
			$arr[$key]['headimg'] = !empty($value['headimg']) ? str_replace("./../","",$value['headimg']) : 'data/default/sex'.$value['sex'].'.png';//
			$user_rank_arr = $this->user->get_user_rank($value['user_id']);//会员等级信息
			$arr[$key]['rank_name'] = $user_rank_arr['rank_name'];
			$arr[$key]['rank_icon'] = $user_rank_arr['rank_icon'];
			$arr[$key]['be_follow_number'] = $GLOBALS['db']->GetOne("SELECT COUNT(*) AS be_follow_number FROM " . $GLOBALS['ecs']->table('user_attention') . " WHERE be_user_id = '$value[user_id]'");//被关注数量(粉丝数)
			$arr[$key]['goods_number'] = $GLOBALS['db']->GetOne("SELECT COUNT(*) FROM " . $GLOBALS['ecs']->table('goods') . " WHERE user_id = '$value[user_id]' AND goods_status = 4");//设计商品数量（已出售）
			$arr[$key]['follow_status'] = get_follow_status($user_id, $value['user_id']);
			$goods_list = $GLOBALS['db']->GetAll("SELECT goods_id, goods_name, goods_thumb, shop_price, goods_number, goods_total, click_count, user_id FROM " . $GLOBALS['ecs']->table('goods') . " WHERE user_id = '$value[user_id]' AND goods_status = 4 ORDER BY click_count DESC LIMIT 3");//设计商品数量（已出售）
			if ($goods_list) {
				foreach ($goods_list as $key2 => $value2) {
					$goods_list[$key2]['zan'] = $GLOBALS['db']->getOne("SELECT COUNT(*) FROM " . $GLOBALS['ecs']->table('goods_zan') . " WHERE goods_id = '$value2[goods_id]'");
					$goods_list[$key2]['number_per'] = $value2['goods_number'] / $value2['goods_total'] * 100;
				}
			}
			$arr[$key]['goods_list'] = $goods_list;
		}
		$count = $GLOBALS['db']->getOne("SELECT COUNT(*) FROM " . $GLOBALS['ecs']->table('users') . " a INNER JOIN " . $GLOBALS['ecs']->table('goods') . " b ON a.user_id = b.user_id " . $sql_w);
		//分页
        $pager = array();
        $pager['page']         = $page;
        $pager['page_size']    = $page_size;
        $pager['record_count'] = $count;
        $pager['page_count']   = $page_count = ($count > 0) ? intval(ceil($count / $page_size)) : 1;

        $ranking_data['list'] = $arr;
        $ranking_data['pager'] = $pager;
		$this->success($ranking_data);
	}
}
