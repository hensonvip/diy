<?php
namespace app\mobile\controller;
use anerg\OAuth2\OAuth;
use think\Controller;
use think\Session;
use think\Request;
use hunuo\shop\payment;

class User extends Common
{

    public function __construct()
    {
        parent::__construct();
        $this->assign('footer_on','user');//底部高亮

        $request = \think\Request::instance();
        $action =  $request->action();

        //Session::delete('user_id');//清除登录状态

        //不需要登录的操作
        $no_login_action =array('login','third_login','callback','do_login','login_mobile','do_login_mobile','logout','register','do_register','forget','forget_two','forget_third','getCode','jsonRegionC','jsonRegionD');
        //未登录处理
        $this->user_id = session('user_id') ? session('user_id') : '';
        //print_r(session('user_id'));die;
        if(empty($this->user_id)){
            if(!in_array(strtolower($action),array_map('strtolower',$no_login_action))){
                if (Request::instance()->isAjax()){//如果是异步，则返回登录提示
                    $result = array();
                    $result['code'] = 401;
                    $result['message'] = '请先登录！';
                    echo json_encode($result);exit();
                }
                header("Location:".url('User/login'));exit;
            }
        }

		//第三方登录配置信息
		$this->login_config = array(
			'qq'=>array(
				//qq
				'type' =>'QQ',
				'app_key'    => '',
				'app_secret' => '',
				'scope'      => 'get_user_info',
				'callback'   => array(
					'default' => "http://" . $_SERVER['HTTP_HOST'] . "/User/callback/channel/qq"
				)
			),
			'weixin'=>array(
				'type' =>'Wechat',
				'app_key'    => 'wxa1470c5bc33aa48b',
				'app_secret' => '496ba108e8886b223b7814c91c3b34b1',
				'scope'      => 'snsapi_userinfo',
				'response_type'      => 'code',
				'grant_type'      => 'authorization_code',
				'callback'   => array(
					'default' => 'http://'.$_SERVER ["HTTP_HOST"].'/User/callback/channel/weixin'
				)
			)
		);

    }

    //会员中心主页
    public function index()
    {
        $url = "user/getUserInfo";
        $data = array();
        $data['user_id'] = $this->user_id;
        $result = $this->curlGet($url,$data);
        $result = json_decode($result,true);//json转数组
        //print_r($result);die;
        $this->assign('data',$result['data']);
        return $this->fetch();
    }

    //头像
    public function headicon(){
        $url = "user/getUserInfo";
        $data = array();
        $data['user_id'] = $this->user_id;
        $result = $this->curlGet($url,$data);
        $result = json_decode($result,true);//json转数组
        //print_r($result);die;
        $this->assign('data',$result['data']);
        return $this->fetch();
    }

    //上传头像
    public function do_headicon(){
        header('Content-type:text/html;charset=utf-8');
        $base64_image_content = input('headimg');
        //匹配出图片的格式
        if (preg_match('/^(data:\s*image\/(\w+);base64,)/', $base64_image_content, $result)){
            $type = $result[2];
            $new_file = ROOT_PATH."../data/headimg/".date('Ym',time())."/";
            if(!file_exists($new_file))
            {
                //检查是否有该文件夹，如果没有就创建，并给予最高权限
                mkdir($new_file, 0777);
            }
            $time = time().rand(1000000000,9999999999);
            $new_file = $new_file.$time.".{$type}";
            if (file_put_contents($new_file, base64_decode(str_replace($result[1], '', $base64_image_content)))){
                //echo '新文件保存成功：', $new_file;
                $save_file_url = "data/headimg/".date('Ym',time())."/".$time.".{$type}";
            }else{
                //echo '新文件保存失败';
                $save_file_url = '';
                echo 0;exit();
            }
        }

        $url = "user/updateHeadUrl";
        $data = array();
        $data['user_id'] = $this->user_id;
        $data['headimg'] = $save_file_url;
        $result = $this->curlPost($url,$data);
        $result = json_decode($result,true);//json转数组
        if($result['code'] == 200){
            echo 1;exit();
        }
        echo 0;exit();
    }

    //优惠券
    public function bonus()
    {
        $url = "user/getUserBonus";
        $data = array();
        $data['user_id'] = $this->user_id;
        $data['is_used'] = input('is_used','','intval') ? input('is_used','','intval') : 1;//1为未使用 2为已使用/已过期
        $result = $this->curlGet($url,$data);
        $result = json_decode($result,true);//json转数组
        //print_r($result);die;
        $this->assign('data',$result['data']);
        $this->assign('is_used',$data['is_used']);
        return $this->fetch();
    }

    //优惠券 - 添加
    public function bonus_add()
    {
        return $this->fetch();
    }

    //优惠券 - 执行添加
    public function do_bonus_add()
    {
        $url = "user/addBonus";
        $data = array();
        $data['user_id'] = $this->user_id;
        $data['bonus_sn'] = input('bonus_sn','','trim');//print_r($data);die;
        $result = $this->curlPost($url,$data);
        $result = json_decode($result);//print_r($result);die;
        return $result;
    }

    //我的余额及流水账记录
    public function account()
    {
        $url = "user/getUserIntegral";
        $data = array();
        $data['user_id'] = $this->user_id;
        $data['page_size'] = 10;
        $data['page'] = input('page','','intval') ? input('page','','intval') : 1;
        $data['account_type'] = 'user_money';//user_money（消费金额）、 pay_points（消费积分）
        $data['log_type'] = input('log_type','','intval') ? input('log_type','','intval') : 0;//0（全部）、1（增加）、2（减少）
        $result = $this->curlGet($url,$data);
        $result = json_decode($result,true);//json转数组
        //print_r($result);die;
        $this->assign('data',$result['data']);
        $this->assign('log_type',$data['log_type']);

        //异步加载分页数据
        $is_ajax = input('is_ajax',0,'intval');
        $this->assign('is_ajax',$is_ajax);
        if($is_ajax){
            echo $this->fetch('user/account_list_ajax');exit();
        }
        return $this->fetch();
    }

    //我的余额 - 充值
    public function recharge()
    {
        $url = "user/getUserInfo";
        $data = array();
        $data['user_id'] = $this->user_id;
        $result = $this->curlGet($url,$data);
        $result = json_decode($result,true);//json转数组
        //print_r($result);die;
        $this->assign('data',$result['data']);

        //判断是否微信端访问
        $user_agent = $_SERVER['HTTP_USER_AGENT'];
        if (strpos($user_agent, 'MicroMessenger') === false) {
            //不是微信端访问
            $weixin = 0;
        }else{
            $weixin = 1;
        }

        $url = "payment/pay_list";
        $result = $this->curlGet($url);
        $result = json_decode($result,true);//json转数组
        //print_r($result);die;
        if($result['data']){
            $pay_list = array();
            foreach ($result['data'] as $k => $v) {
                if($weixin == 1 && $v['pay_code'] == 'MWEB'){//微信端过滤H5支付
                    continue;
                }
                if($weixin == 0 && $v['pay_name'] == 'weixin'){//不是微信端过滤微信支付
                    continue;
                }
                if($v['pay_code'] == 'balance'){
                    continue;
                }
                $pay_list[] = $v;
            }
        }
        //print_r($pay_list);die;
        $this->assign('pay_list',$pay_list);

        return $this->fetch();
    }

    //我的余额 - 充值 - 执行
    public function do_recharge(){
        $url = "payment/recharge";
        $data = array();
        $data['user_id'] = $this->user_id;
        $data['amount'] = input('amount','','trim');
        $data['pay_id'] = input('pay_id','','intval');
        $result = $this->curlPost($url,$data);
        $result = json_decode($result,true);//json转数组

        /*if($data['pay_id']){
            $payment = new payment();
            $payment->type = 'h5';
            switch($result['data']['payment']['payment_name']){
                case 'weixin':
                    $payment->weixin($result['data']['payment']['prepay_id']);
                    break;
                case 'alipay':
                    echo $payment->alipay($result['data']['payment']['prepay_id']);
                    break;
            }
        }*/

        if($result['code'] == 500){
            $this->success($result['message'],url('User/order_list'));exit;
        }
        if($result['code'] == 200 && $data['pay_id'] == 0){//余额支付
            $this->success('支付成功！',url('User/order_list'));exit;
        }else{
            print_r($result['data']['payment']['prepay_id']);die;//其他支付 打印支付提交表单
        }
    }

    //我的余额 - 提现页面
    public function withdraw_deposit()
    {
        $url = "user/getBankCardDefault";
        $data = array();
        $data['user_id'] = $this->user_id;
        $result = $this->curlGet($url,$data);
        $result = json_decode($result,true);//json转数组
        //print_r($result);die;
        $this->assign('data',$result['data']);
        return $this->fetch();
    }

    //我的余额 - 提现
    public function apply_deposit()
    {
        $url = "user/applyDeposit";
        $data = array();
        $data['user_id'] = $this->user_id;
        $data['card_id'] = input('card_id','','intval');
        $data['amount'] = input('amount','','trim');
        $result = $this->curlGet($url,$data);
        $result = json_decode($result);
        return $result;
    }

    //我的积分及流水账记录
    public function integral()
    {
        $url = "user/getUserIntegral";
        $data = array();
        $data['user_id'] = $this->user_id;
        $data['page_size'] = 10;
        $data['page'] = input('page','','intval') ? input('page','','intval') : 1;
        $data['account_type'] = 'pay_points';//user_money（消费金额）、 pay_points（消费积分）
        $data['log_type'] = input('log_type','','intval') ? input('log_type','','intval') : 0;//0（全部）、1（增加）、2（减少）
        $result = $this->curlGet($url,$data);
        $result = json_decode($result,true);//json转数组
        //print_r($result);die;
        $this->assign('data',$result['data']);
        $this->assign('log_type',$data['log_type']);

        //异步加载分页数据
        $is_ajax = input('is_ajax',0,'intval');
        $this->assign('is_ajax',$is_ajax);
        if($is_ajax){
            echo $this->fetch('user/integral_list_ajax');exit();
        }
        return $this->fetch();
    }

    //支付列表 点击支付重新支付使用（因为APP支付的pay_id和手机版的pay_id不一样，解决重新支付时出错，所以要重新选择支付方式）
    public function order_pay_list(){
        //判断是否微信端访问
        $user_agent = $_SERVER['HTTP_USER_AGENT'];
        if (strpos($user_agent, 'MicroMessenger') === false) {
            //不是微信端访问
            $weixin = 0;
        }else{
            $weixin = 1;
        }

        $url = "payment/pay_list";
        $data = array();
        $result = $this->curlPost($url,$data);
        $result = json_decode($result,true);//json转数组

        if($result['data']){
            $pay_list = array();
            foreach ($result['data'] as $k => $v) {
                if($weixin == 1 && $v['pay_code'] == 'MWEB'){//微信端过滤H5支付
                    continue;
                }
                if($weixin == 0 && $v['pay_name'] == 'weixin'){//不是微信端过滤微信支付
                    continue;
                }
                /*if($v['pay_code'] == 'balance'){
                    continue;
                }*/
                $pay_list[] = $v;
            }
        }

        //print_r($result);die;
        $this->assign('data',$pay_list);
        $this->assign('order_id',input('order_id',0,'intval'));
        //异步加载数据
        $is_ajax = input('is_ajax',0,'intval');
        $this->assign('is_ajax',$is_ajax);
        if($is_ajax){
            echo $this->fetch('user/order_pay_list');exit();
        }
    }

    //订单支付
    public function order_pay(){
        //$url = "payment/repay";
        $url = "payment/change_pay";
        $data = array();
        $data['user_id'] = $this->user_id;
        $data['order_id'] = input('order_id','','intval');
        $data['pay_id'] = input('pay_id','','intval');//可以重新选择不同的支付方式
        $result = $this->curlPost($url,$data);
        $result = json_decode($result,true);//json转数组
        //file_put_contents(ROOT_PATH.'log2.txt', var_export($result, true),FILE_APPEND);//打印数组
        /*$payment = new payment();
        $payment->type = 'h5';
        switch($result['data']['payment']['payment_name']){
            case 'weixin':
                $payment->weixin($result['data']['payment']['prepay_id']);
                break;
            case 'alipay':
                echo $payment->alipay($result['data']['payment']['prepay_id']);
                break;
        }*/
        if($result['code'] == 500){
            $this->success($result['message'],url('User/order_list'));exit;
        }
        if($result['code'] == 200 && $data['pay_id'] == 0){//余额支付
            $this->success('支付成功！',url('User/order_list'));exit;
        }else{
            print_r($result['data']['payment']['prepay_id']);die;//其他支付 打印支付提交表单
        }

        //print_r($result);die;
    }

    //我的订单 - 列表
    public function order_list()
    {
        $url = "user/getUserOrder";
        $data = array();
        $data['user_id'] = $this->user_id;
        $data['page_size'] = 5;//显示数据数量
        $data['page'] = input('page','','intval') ? input('page','','intval') : 1;
        $data['status'] = input('status','','intval') ? input('status','','intval') : 0;//0（所有订单）、2（待付款）、3（待发货）、4（待收货）、5（已完成）
        $result = $this->curlGet($url,$data);
        $result = json_decode($result,true);//json转数组
        //print_r($result);die;
        $this->assign('data',$result['data']);
        $this->assign('status',$data['status']);

        //异步加载分页数据
        $is_ajax = input('is_ajax',0,'intval');
        $this->assign('is_ajax',$is_ajax);
        if($is_ajax){
            echo $this->fetch('user/order_list_ajax');exit();
        }
        return $this->fetch();
    }

    //我的订单 - 详情
    public function order_details(){
        $url = "user/getUserOrderDetail";
        $data = array();
        $data['user_id'] = $this->user_id;
        $data['order_id'] = input('order_id','','intval');
        $result = $this->curlGet($url,$data);
        $result = json_decode($result,true);//json转数组
        //print_r($result);die;
        //获取快递信息
        if(!empty($result['data']['order_info']['invoice_no']) && !empty($result['data']['order_info']['shipping_name'])){
            $url = "index/kuaidi";
            $kuaidi_data = array();
            $kuaidi_data['typeCom'] = $result['data']['order_info']['shipping_name'];
            $kuaidi_data['typeNu'] = $result['data']['order_info']['invoice_no'];
            $kuaidi_result = $this->curlGet($url,$kuaidi_data);
            $kuaidi_result = json_decode($kuaidi_result,true);//print_r($kuaidi_result);die;
            $this->assign('kuaidi_data',$kuaidi_result['data']);
        }else{
            $this->assign('kuaidi_data','');
        }

        $goods_sum = 0;
        if(!empty($result['data']['goods_list'])){
            foreach ($result['data']['goods_list'] as $k => $v) {
                $goods_sum += $v['goods_number'];
            }
        }
        $this->assign('goods_sum',$goods_sum);

        $this->assign('data',$result['data']);
        return $this->fetch();
    }

    //获取可以评论的商品列表数据
    public function order_goods_comment(){
        $order_id = input('order_id',0,'intval');
        $url = "user/orderGoodsComment";
        $data = array();
        $data['user_id'] = $this->user_id;
        $data['order_id'] = $order_id;
        $result = $this->curlGet($url,$data);
        $result = json_decode($result,true);//json转数组
        //print_r($result);die;
        $this->assign('data',$result['data']);
        //异步加载分页数据
        $is_ajax = input('is_ajax',0,'intval');
        $this->assign('is_ajax',$is_ajax);
        if($is_ajax){
            echo $this->fetch('user/order_goods_comment');exit();
        }
        return $this->fetch();
    }

    //商品订单评论
    public function order_comment(){
        $rec_id = input('rec_id',0,'intval');
        $order_id = input('order_id',0,'intval');
        $goods_id = input('goods_id',0,'intval');
        $is_real = input('is_real',1,'intval');

        $this ->assign('rec_id',$rec_id);
        $this ->assign('order_id',$order_id);
        $this ->assign('goods_id',$goods_id);
        $this ->assign('is_real',$is_real);
        return $this->fetch();
    }

    //商品订单评论 - 提交
    public function do_order_comment()
    {
        //print_r($_FILES);die;
        $url = "user/addUserComment";
        $data = array();
        $data = input();
        $data['user_id'] = $this->user_id;
        //print_r($data);die;
        $files_up = array();
        $file_path = $data['file_path'] =  "runtime/temp/";
        $files_img = request()->file("img_srcs");

        // 如果有图片上传，保存
        if(!empty($files_img)){
            //$img_num = 0;
            foreach ($files_img as $key => $img) {
                if(!empty($img)){
                    //$img_num++;
                    // 保存图片
                    $img_info = $img->rule('uniqid')->move($file_path);
                    // move_uploaded_file(filename, destination)
                    if($img_info){
                        // 保存图片名称 相当于$_FILE['name'] 唯一
                        $files_up[$key]['name'] = 'img_srcs';
                        //$files_up[$key]['name'] = 'img_srcs'.$img_num;
                        // 保存图片路径
                        $files_up[$key]['path'] = $file_path.$img_info->getFilename();

                    }else{// 删除所有图片
                        for ($i=0; $i < COUNT($files_up); $i++) {
                            @unlink($file_path.$files_up[$i]["name"]);
                        }
                        $return = array(
                            "code" => 500,
                            "message" => $file->getError()
                        );
                        echo json_encode($return);
                        exit;
                        // $this->error("上传照片错误！");
                    }
                }
            }
            //$data["img_num"] = $img_num;
            //print_r($files_up);die;
        }

        $result = $this->curlPost($url,$data,$files_up);//print_r(json_decode($result,true));die;
        $result = json_decode($result);
        return $result;
    }

    //退款/售后 列表
    public function refund_list(){
        $url = "user/refundList";
        $data = array();
        $data['user_id'] = $this->user_id;
        $data['page_size'] = 5;//显示数据数量
        $data['page'] = input('page',1,'intval');
        $data['status'] = input('status',0,'intval');//0（全部）、1（审核中）、2（已完成）
        $data['type'] = input('type','','intval');//''（全部）、0（虚拟商品）、1（真实商品）
        $result = $this->curlGet($url,$data);
        $result = json_decode($result,true);//json转数组
        //print_r($result);die;
        $this->assign('data',$result['data']);
        $this->assign('status',$data['status']);
        $this->assign('type',$data['type']);

        //异步加载分页数据
        $is_ajax = input('is_ajax',0,'intval');
        $this->assign('is_ajax',$is_ajax);
        if($is_ajax){
            echo $this->fetch('user/refund_list_ajax');exit();
        }
        return $this->fetch();
    }

    //退款/售后  详情
    public function refund_details(){
        $url = "user/refundDetails";
        $data = array();
        $data['user_id'] = $this->user_id;
        $data['back_id'] = input('back_id','','intval');
        $result = $this->curlGet($url,$data);
        $result = json_decode($result,true);//json转数组
        //print_r($result);die;
        if($result['code'] == 500){
            $this->success($result['message'],url('User/refund_list'));exit;
        }
        $this->assign('data',$result['data']);
        return $this->fetch();
    }

    //退款退货
    public function refund(){
        $url = "user/ToCreateUserBackOrder";
        $data = array();
        $data['user_id'] = $this->user_id;
        $data['order_id'] = input('order_id',0,'intval');
        $data['goods_id'] = input('goods_id',0,'intval');
        $data['product_id'] = input('product_id',0,'intval');
        $result = $this->curlGet($url,$data);
        $result = json_decode($result,true);//json转数组
        //print_r($result);die;
        if($result['code'] == 500){
            $this->success($result['message'],url('User/order_list'));exit;
        }
        $this->assign('data',$result['data']);
        $this->assign('order_id',$data['order_id']);
        $this->assign('goods_id',$data['goods_id']);
        $this->assign('product_id',$data['product_id']);
        return $this->fetch();
    }

    //提交退款退货
    public function do_refund(){
        $url = "user/apply_back_order";
        $data = array();
        $data = input();
        $data['user_id'] = $this->user_id;
        //print_r($data);die;
        $files_up = array();
        $file_path = $data['file_path'] =  "runtime/temp/";
        $files_img = request()->file("back_imgs");

        // 如果有图片上传，保存
        if(!empty($files_img)){
            //$img_num = 0;
            foreach ($files_img as $key => $img) {
                if(!empty($img)){
                   // $img_num++;
                    // 保存图片
                    $img_info = $img->rule('uniqid')->move($file_path);
                    // move_uploaded_file(filename, destination)
                    if($img_info){
                        // 保存图片名称 相当于$_FILE['name'] 唯一
                        $files_up[$key]['name'] = 'back_imgs';
                        //$files_up[$key]['name'] = 'back_imgs'.$img_num;
                        // 保存图片路径
                        $files_up[$key]['path'] = $file_path.$img_info->getFilename();

                    }else{// 删除所有图片
                        for ($i=0; $i < COUNT($files_up); $i++) {
                            @unlink($file_path.$files_up[$i]["name"]);
                        }
                        $return = array(
                            "code" => 500,
                            "message" => $file->getError()
                        );
                        echo json_encode($return);
                        exit;
                        // $this->error("上传照片错误！");
                    }
                }
            }
            //$data["img_num"] = $img_num;
            //print_r($files_up);die;
        }

        $result = $this->curlPost($url,$data,$files_up);//print_r(json_decode($result,true));die;
        $result = json_decode($result);
        return $result;
    }

    //我的信息
    public function user_info()
    {
        $url = "user/getUserInfo";
        $data = array();
        $data['user_id'] = $this->user_id;
        $result = $this->curlGet($url,$data);
        $result = json_decode($result,true);//json转数组
        //print_r($result);die;
        $this->assign('data',$result['data']);
        return $this->fetch();
    }

    //我的信息 - 用户名昵称
    public function nickname()
    {
        $url = "user/getUserInfo";
        $data = array();
        $data['user_id'] = $this->user_id;
        $result = $this->curlGet($url,$data);
        $result = json_decode($result,true);//json转数组
        //print_r($result);die;
        $this->assign('data',$result['data']);
        return $this->fetch();
    }

    //我的信息 - 用户名昵称 - 保存
    public function save_nickname()
    {
        $url = "user/updateUserInfo";
        $data = array();
        $data['user_id'] = $this->user_id;
        $data['user_name'] = input('user_name','','trim,strip_tags,htmlspecialchars');
        $result = $this->curlPost($url,$data);
        $result = json_decode($result);
        return $result;
    }

    //我的信息 - 性别
    public function sex()
    {
        $url = "user/getUserInfo";
        $data = array();
        $data['user_id'] = $this->user_id;
        $result = $this->curlGet($url,$data);
        $result = json_decode($result,true);//json转数组
        //print_r($result);die;
        $this->assign('data',$result['data']);
        return $this->fetch();
    }

    //我的信息 - 性别 - 保存
    public function save_sex()
    {
        $url = "user/updateUserInfo";
        $data = array();
        $data['user_id'] = $this->user_id;
        $data['sex'] = input('sex','','intval');
        $result = $this->curlPost($url,$data);
        $result = json_decode($result);
        return $result;
    }

    //我的信息 - 手机
    public function tel()
    {
        $url = "user/getUserInfo";
        $data = array();
        $data['user_id'] = $this->user_id;
        $result = $this->curlGet($url,$data);
        $result = json_decode($result,true);//json转数组
        //print_r($result);die;
        $this->assign('data',$result['data']);
        return $this->fetch();
    }

    //我的信息 - 手机 - 保存
    public function save_tel()
    {
        $data = array();

        $data['mobile_phone'] = input('mobile_phone','','trim');
        $data['mobile_code'] = input('mobile_code','','trim');

        $url = "passport/validate_phone";
        $result = $this->curlPost($url,$data);
        $result = json_decode($result,true);
        if($result['code'] == 200){
            $data['user_id'] = $this->user_id;
            $url = "user/updateUserInfo";
            $result = $this->curlPost($url,$data);
            $result = json_decode($result);
        }else{
            $result['code'] = 500;
            $result['message'] = '保存失败';
        }

        return $result;
    }

    //收货地址
    public function address()
    {
        $url = "user/getUserAddress";
        $data = array();
        $data['user_id'] = $this->user_id;
        $result = $this->curlGet($url,$data);
        $result = json_decode($result,true);//json转数组
        //print_r($result);die;
        $this->assign('data',$result['data']);
        return $this->fetch();
    }

    //收货地址 - 新增
    public function address_add()
    {
        $regionP = $this->getRegionP();
        $this->assign('regionP',$regionP);//省份
        return $this->fetch();
    }

    //收货地址 - 新增 - 保存
    public function do_address_add()
    {
        $url = "user/updateUserAddress";
        $data = array();
        $data['user_id'] = $this->user_id;
        $data['consignee'] = input('consignee','','trim,strip_tags,htmlspecialchars');//收货人姓名
        $data['mobile'] = input('mobile','','trim');//联系电话
        $data['province'] = input('province','','intval') ? input('province','','intval') : 0;
        $data['city'] = input('city','','intval') ? input('city','','intval') : 0;
        $data['district'] = input('district','','intval') ? input('district','','intval') : 0;
        $data['address'] = input('address','','trim');//联系电话
        $data['is_default'] = input('is_default','','intval');//是否默认地址

        $result = $this->curlPost($url,$data);
        $result = json_decode($result);
        return $result;
    }

    //收货地址 - 编辑
    public function address_edit()
    {
        $url = "user/getUserAddressInfo";
        $data = array();
        $data['user_id'] = $this->user_id;
        $data['address_id'] = input('address_id','','intval');
        $result = $this->curlGet($url,$data);
        $result = json_decode($result,true);//json转数组
        //print_r($result['data']);die;
        $result = array_values($result['data']['list']);
        $this->assign('data',$result[0]);//收货地址信息

        $regionP = $this->getRegionP();
        $this->assign('regionP',$regionP);//省份
        return $this->fetch();
    }

    //收货地址 - 编辑 - 保存
    public function do_address_edit()
    {
        $url = "user/updateUserAddress";
        $data = array();
        $data['user_id'] = $this->user_id;
        $data['address_id'] = input('address_id','','intval');
        $data['consignee'] = input('consignee','','trim,strip_tags,htmlspecialchars');//收货人姓名
        $data['mobile'] = input('mobile','','trim');//联系电话
        $data['province'] = input('province','','intval') ? input('province','','intval') : input('province_old','','intval');
        $data['city'] = input('city','','intval') ? input('city','','intval') : input('city_old','','intval');
        $data['district'] = input('district','','intval') ? input('district','','intval') : input('district_old','','intval');
        $data['address'] = input('address','','trim');//联系电话
        $data['is_default'] = input('is_default','','intval');//是否默认地址

        $result = $this->curlPost($url,$data);
        $result = json_decode($result);
        return $result;
    }

    //收货地址 - 默认地址 - 保存
    public function do_address_default()
    {
        $url = "user/setUserDefaultAddress";
        $data = array();
        $data['user_id'] = $this->user_id;
        $data['address_id'] = input('address_id','','intval');

        $result = $this->curlPost($url,$data);
        $result = json_decode($result);
        return $result;
    }

    //收货地址 - 删除
    public function do_address_del()
    {
        $url = "user/dropUserAddress";
        $data = array();
        $data['user_id'] = $this->user_id;
        $data['address_id'] = input('address_id','','intval');

        $result = $this->curlPost($url,$data);
        $result = json_decode($result);
        return $result;
    }

    public function jsonRegionC()
    {
        $pkey = input('pkey','','intval');
        $result = $this->getRegionC($pkey);
        echo json_encode($result);
        exit();
    }

    public function jsonRegionD()
    {
        $pkey = input('pkey','','intval');
        $ckey = input('ckey','','intval');
        $result = $this->getRegionD($pkey,$ckey);
        echo json_encode($result);
        exit();
    }

    //修改密码
    public function pwd()
    {
        return $this->fetch();
    }

    //修改密码
    public function do_pwd()
    {
        $url = "passport/updateUserPWD";
        $data = array();
        $data['user_id'] = $this->user_id;
        $data['old_password'] = input('old_password','','trim');
        $data['new_password'] = input('new_password','','trim');

        $result = $this->curlPost($url,$data);

        $result_arr = json_decode($result,true);
        if($result_arr['code'] == 200){
            Session::delete('user_id');//清除登录状态
        }

        $result = json_decode($result);
        return $result;
    }

    //我的收藏 - 商品
    public function collect_goods()
    {
        $url = "user/getUserCollect";
        $data = array();
        $data['user_id'] = $this->user_id;
        $data['page_size'] = 5;//显示数据数量
        $data['page'] = input('page',1,'intval');
        $data['is_real'] = input('is_real',1,'intval');
        $result = $this->curlGet($url,$data);
        $result = json_decode($result,true);//json转数组
        //print_r($result);die;
        $this->assign('data',$result['data']);
        $this->assign('is_real',$data['is_real']);
        //异步加载分页数据
        $is_ajax = input('is_ajax',0,'intval');
        $this->assign('is_ajax',$is_ajax);
        if($is_ajax){
            echo $this->fetch('user/collect_goods_ajax');exit();
        }
        return $this->fetch();
    }

    //我的收藏 - 商品 - 删除一个
    public function collect_goods_delOne()
    {
        $url = "user/dropUserCollect";
        $data = array();
        $data['user_id'] = $this->user_id;
        $data['collection_id'] = input('collection_id');//print_r($data);die;
        $result = $this->curlGet($url,$data);
        $result = json_decode($result);//print_r($result);die;
        return $result;
    }

    //我的收藏 - 商品 - 删除多个
    public function collect_goods_delMore()
    {
        $url = "user/dropUserCollect";
        $data = array();
        $data['user_id'] = $this->user_id;
        $arr = input();
        $data['collection_id'] = isset($arr['chk_value']) ? implode(',',$arr['chk_value']) : '';//print_r($data);die;
        $result = $this->curlGet($url,$data);
        $result = json_decode($result);//print_r($result);die;
        return $result;
    }

    //我的收藏 - 店铺
    public function collect_shop()
    {
        $url = "user/getShopCollect";
        $data = array();
        $data['user_id'] = $this->user_id;
        $data['page_size'] = 4;//显示数据数量
        $data['page'] = input('page','','intval') ? input('page','','intval') : 1;
        $result = $this->curlGet($url,$data);
        $result = json_decode($result,true);//json转数组
        //print_r($result);die;
        $this->assign('data',$result['data']);
        //异步加载分页数据
        $is_ajax = input('is_ajax',0,'intval');
        $this->assign('is_ajax',$is_ajax);
        if($is_ajax){
            echo $this->fetch('user/collect_shop_ajax');exit();
        }
        return $this->fetch();
    }

    //我的收藏 - 店铺 - 删除一个
    public function collect_shop_delOne()
    {
        $url = "user/dropShopCollect";
        $data = array();
        $data['user_id'] = $this->user_id;
        $data['collection_id'] = input('collection_id');//print_r($data);die;
        $result = $this->curlGet($url,$data);
        $result = json_decode($result);//print_r($result);die;
        return $result;
    }

    //我的收藏 - 店铺 - 删除多个
    public function collect_shop_delMore()
    {
        $url = "user/dropShopCollect";
        $data = array();
        $data['user_id'] = $this->user_id;
        $arr = input();
        $data['collection_id'] = isset($arr['chk_value']) ? implode(',',$arr['chk_value']) : '';//print_r($data);die;
        $result = $this->curlGet($url,$data);
        $result = json_decode($result);//print_r($result);die;
        return $result;
    }

    //我的消息
    public function my_message()
    {
        $url = "user/getMyNews";
        $data = array();
        $data['user_id'] = $this->user_id;
        $data['page_size'] = 10;//显示数据数量
        $data['page'] = input('page','','intval') ? input('page','','intval') : 1;
        $result = $this->curlGet($url,$data);
        $result = json_decode($result,true);//json转数组
        //print_r($result);die;
        $this->assign('data',$result['data']);
        //异步加载分页数据
        $is_ajax = input('is_ajax',0,'intval');
        $this->assign('is_ajax',$is_ajax);
        if($is_ajax){
            echo $this->fetch('user/my_message_ajax');exit();
        }
        return $this->fetch();
    }

    //我的消息 - 一键已读
    public function my_message_read(){
        $url = "user/getMyNewsChange";
        $data = array();
        $data['user_id'] = $this->user_id;
        $result = $this->curlGet($url,$data);
        $result = json_decode($result,true);
        return $result;
    }

    //我的消息 - 删除
    public function del_my_message(){
        $url = "user/delMyNews";
        $data = array();
        $data['user_id'] = $this->user_id;
        $data['l_id'] = input('l_id',0,'intval');
        $result = $this->curlPost($url,$data);
        //print_r(json_decode($result,true));die;
        $result = json_decode($result);
        return $result;
    }

    //我的消息 - 详情
    public function my_message_details(){
        $url = "user/getMyNewsDetail";
        $data = array();
        $data['user_id'] = $this->user_id;
        $data['l_id'] = input('l_id','','intval');
        $result = $this->curlGet($url,$data);
        $result = json_decode($result,true);//json转数组
        //print_r($result);die;
        $this->assign('data',$result['data']);
        return $this->fetch();
    }

    //设置
    public function setting()
    {
        $url = "user/getUserInfo";
        $data = $this->curlGet($url);
        //print_r($data);die;
        return $this->fetch();
    }

    //我的银行卡
    public function bank_card_list(){
        $url = "user/getBankCardList";
        $data = array();
        $data['user_id'] = $this->user_id;
        $data['page_size'] = 5;//显示数据数量
        $data['page'] = input('page','','intval') ? input('page','','intval') : 1;
        $result = $this->curlGet($url,$data);
        $result = json_decode($result,true);//json转数组
        //print_r($result);die;
        $this->assign('data',$result['data']);
        //异步加载分页数据
        $is_ajax = input('is_ajax',0,'intval');
        $this->assign('is_ajax',$is_ajax);
        if($is_ajax){
            echo $this->fetch('user/bank_card_list_ajax');exit();
        }
        return $this->fetch();
    }

    //我的银行卡 - 默认
    public function bank_card_default(){
        $url = "user/setBankCardDefault";
        $data = array();
        $data['user_id'] = $this->user_id;
        $data['card_id'] = input('card_id','','intval');
        $result = $this->curlGet($url,$data);
        $result = json_decode($result);
        return $result;
    }

    //我的银行卡 - 添加银行卡页面
    public function bank_card_add(){
        $url = "user/getBankList";
        $data = array();
        $data['user_id'] = $this->user_id;
        $result = $this->curlGet($url,$data);
        $result = json_decode($result,true);//json转数组
        //print_r($result);die;
        $this->assign('data',$result['data']);
        return $this->fetch();
    }

    //我的银行卡 - 添加银行卡
    public function do_bank_card_add(){
        $url = "user/addBankCard";
        $data = array();
        $data['user_id'] = $this->user_id;
        $data['real_name'] = input('real_name','','trim');
        $data['bank_id'] = input('bank_id','','intval');
        $data['card_number'] = input('card_number','','trim');
        $data['card_name'] = input('card_name','','trim');
        $result = $this->curlGet($url,$data);
        $result = json_decode($result);
        return $result;
    }

    //我的评论 - 商品评价
    public function my_comments(){
        $url = "user/getMyComment";
        $data = array();
        $data['user_id'] = $this->user_id;
        $data['page_size'] = 10;//显示数据数量
        $data['page'] = input('page','','intval') ? input('page','','intval') : 1;
        $data['is_real'] = input('is_real','','trim');
        $result = $this->curlGet($url,$data);
        $result = json_decode($result,true);//json转数组
        //print_r($result);die;
        $this->assign('data',$result['data']);
        $this->assign('is_real',$data['is_real']);


        //异步加载分页数据
        $is_ajax = input('is_ajax',0,'intval');
        $this->assign('is_ajax',$is_ajax);
        if($is_ajax){
            echo $this->fetch('user/my_comments_ajax');exit();
        }
        return $this->fetch();
    }

    //我的评论 - 服务评价
    /*public function my_comments_service(){
        $url = "user/getMyComment";
        $data = array();
        $data['user_id'] = $this->user_id;
        $data['page_size'] = 10;//显示数据数量
        $data['page'] = input('page','','intval') ? input('page','','intval') : 1;
        $data['is_real'] = input('is_real','0','trim');
        $result = $this->curlGet($url,$data);
        $result = json_decode($result,true);//json转数组
        //print_r($result);die;
        $this->assign('data',$result['data']);

        //异步加载分页数据
        $is_ajax = input('is_ajax',0,'intval');
        $this->assign('is_ajax',$is_ajax);
        if($is_ajax){
            echo $this->fetch('user/my_comments_ajax');exit();
        }
        return $this->fetch();
    }*/



    //我的留言（留言、投诉、询问、售后、求购）
    public function advise()
    {
        $msg_type_arr = array('留言','投诉','询问','售后','求购');
        $order_id = input('order_id',0,'intval');
        $msg_type = input('msg_type',0,'intval');

        $this->assign('msg_type_arr',$msg_type_arr);
        $this->assign('order_id',$order_id);
        $this->assign('msg_type',$msg_type);
        return $this->fetch();
    }

    //我的留言（留言、投诉、询问、售后、求购） - 保存
    public function do_advise()
    {
        $url = "user/advise";
        $data = array();
        $data['user_id'] = $this->user_id;
        $data['msg_type'] = input('msg_type',0,'intval');
        $data['order_id'] = input('order_id',0,'intval');
        $data['msg_content'] = input('msg_content',0,'trim');
        $data['msg_title'] = input('msg_title',$data['msg_content'],'trim');

        $result = $this->curlPost($url,$data);
        $result = json_decode($result,true);//json转数组
        if($result['code'] == 500){
            $this->error($result['message']);
        }else{
            $this->success($result['message'],url('User/advise'));
        }
        exit();
    }

    //我参与的砍价活动商品列表
    public function bargain()
    {
        $url = "user/getBargain";
        $data = array();
        $data['user_id'] = $this->user_id;
        $data['page_size'] = 10;//显示数据数量
        $data['page'] = input('page','','intval') ? input('page','','intval') : 1;
        $result = $this->curlGet($url,$data);
        $result = json_decode($result,true);//json转数组
        //print_r($result);die;
        $this->assign('data',$result['data']);

        //异步加载分页数据
        $is_ajax = input('is_ajax',0,'intval');
        $this->assign('is_ajax',$is_ajax);
        if($is_ajax){
            echo $this->fetch('user/bargain_list_ajax');exit();
        }
        return $this->fetch();
    }

    //登录
    public function login()
    {
        if($this->user_id){
            header("Location:".url('User/index'));exit;
        }
        $back_url = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
        $this->assign('back_url',input('back_url',$back_url,'trim'));//来源页
        return $this->fetch();
    }

    //执行登录
    public function do_login()
    {
        if($this->user_id){
            header("Location:".url('User/index'));exit;
        }
        $url = "passport/act_login";
        $data = array();
        $data['username'] = input('username','','trim,strip_tags,htmlspecialchars');
        $data['password'] = input('pwd');

        $result = $this->curlPost($url,$data);
        $result = json_decode($result,true);//json转数组
        if($result['code'] == 500){
            $this->error($result['message']);
        }else{
            session::set('user_id',$result['data']['user_id']);//会员ID
            session::set('rank_name',$result['data']['rank_name']);//等级名称
            session::set('user_rank',$result['data']['user_rank']);//会员等级
            session::set('discount',$result['data']['discount']);//会员折扣

            $back_url = input('back_url') ? input('back_url') : '';
            if(empty($back_url)){
                $back_url = $result['data']['back_url'] ? $result['data']['back_url'] : url('User/index');//来源页，没有就跳转会员中心
            }
            //$this->success($result['message'],$back_url);
            header("Location:".$back_url);exit;
        }
        exit();
    }

    //手机快捷登录
    public function login_mobile()
    {
        if($this->user_id){
            header("Location:".url('User/index'));exit;
        }
        $back_url = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
        $this->assign('back_url',input('back_url',$back_url,'trim'));//来源页
        return $this->fetch();
    }

    //手机快捷登录 - 执行
    public function do_login_mobile()
    {
        if($this->user_id){
            header("Location:".url('User/index'));exit;
        }
        $url = "passport/login_mobile_info";
        $data = array();
        $data['mobile_phone'] = input('mobile_phone','','trim');
        $data['mobile_code'] = input('mobile_code','','trim');

        $result = $this->curlPost($url,$data);
        $result = json_decode($result,true);//json转数组
        if($result['code'] == 500){
            $this->error($result['message']);
        }else{
            session::set('user_id',$result['data']['user_id']);//会员ID
            session::set('rank_name',$result['data']['rank_name']);//等级名称
            session::set('user_rank',$result['data']['user_rank']);//会员等级
            session::set('discount',$result['data']['discount']);//会员折扣

            $back_url = input('back_url') ? input('back_url') : '';
            if(empty($back_url)){
                $back_url = $result['data']['back_url'] ? $result['data']['back_url'] : url('User/index');//来源页，没有就跳转会员中心
            }
            //$this->success($result['message'],$back_url);
            header("Location:".$back_url);exit;
        }
        exit();
    }

    //退出登录
    public function logout()
    {
        Session::delete('user_id');//清除登录状态
        $this->success('退出登录成功！',url('Index/index'));
    }

    //忘记密码
    public function forget()
    {
        return $this->fetch();
    }

    //忘记密码 - 第二步
    public function forget_two()
    {
        $url = "passport/validate_phone";
        $data = array();
        $data['mobile_phone'] = input('mobile_phone','','trim');
        $data['mobile_code'] = input('mobile_code','','trim');
        $result = $this->curlPost($url,$data);
        $result = json_decode($result,true);
        if($result['code'] == 200){
            session('forget_verify_code', $result['data']['verify_code']);
            session('forget_mobile_phone', $data['mobile_phone']);
        }else{
            $this->error($result['message']);
        }
        return $this->fetch();
    }

    //忘记密码 - 第三步
    public function forget_third()
    {
        $url = "passport/reset_password";
        $data = array();
        $data['mobile_phone'] = session('forget_mobile_phone');
        $data['verify_code'] = session('forget_verify_code');
        $data['password'] = input('new_password','','trim');
        $result = $this->curlPost($url,$data);
        $result = json_decode($result);
        //$result = json_decode($result,true);print_r($result);die;

        return $result;
    }



    //注册
    public function register()
    {
        if($this->user_id){
            header("Location:".url('User/index'));exit;
        }
        return $this->fetch();
    }

    //执行注册
    public function do_register()
    {
        if($this->user_id){
            header("Location:".url('User/index'));exit;
        }
        $url = "passport/act_register";
        $data = array();
        $data['mobile_phone'] = input('mobile_phone','','trim');
        $data['mobile_code'] = input('mobile_code','','trim');
        $data['password'] = input('pwd','','trim');
        $data['agreement'] = input('agreement','','intval');
        $result = $this->curlPost($url,$data);
        $result = json_decode($result,true);
        if($result['code'] == 500){
            $this->error($result['message']);
        }else{
            //print_r($result);die;
            session::set('user_id',$result['data']['user_id']);//会员ID
            session::set('rank_name',$result['data']['rank_name']);//等级名称
            session::set('user_rank',$result['data']['user_rank']);//会员等级
            session::set('discount',$result['data']['discount']);//会员折扣

            $back_url = input('back_url') ? input('back_url') : '';
            if(empty($back_url)){
                $back_url = $result['data']['back_url'] ? $result['data']['back_url'] : url('User/index');//来源页，没有就跳转会员中心
            }
            //$this->success('注册成功',$back_url);
            header("Location:".$back_url);exit;
        }
        exit();
    }

    //获取手机验证码
    public function getCode()
    {

        $url = "passport/sendMessage";
        $data = array();
        $data['send_type'] = input('send_type');
        $data['mobile_phone'] = input('mobile');

        $result = $this->curlPost($url,$data);
        $result = json_decode($result);
        return $result;
    }

    //删除订单
    public function delete_order(){
        $url = "user/delUserOrder";
        $data = array();
        $data['user_id'] = $this->user_id;
        $data['order_id'] = input('order_id',0);

        $result = $this->curlGet($url,$data);
        //$result = json_decode($result);
        echo $result;
    }

    //确认收货
    public function arrived_order(){
        $url = "user/arrivedUserOrder";
        $data = array();
        $data['user_id'] = $this->user_id;
        $data['order_id'] = input('order_id',0);

        $result = $this->curlPost($url,$data);
        //$result = json_decode($result);
        header("Location:".url('User/order_list',array('status'=>5)));exit();
    }

    //取消订单
    public function cancel_order(){
        $url = "user/cancelUserOrder";
        $data = array();
        $data['user_id'] = $this->user_id;
        $data['order_id'] = input('order_id',0);

        $result = $this->curlGet($url,$data);
        //$result = json_decode($result);
        echo $result;
    }

    //查看物流
    public function kuaidi(){
        $url = "index/kuaidi";
        $data = array();
        $data['typeCom'] = input('typeCom','','trim');
        $data['typeNu'] = input('typeNu',0,'trim');
        $result = $this->curlGet($url,$data);
        $result = json_decode($result,true);//print_r($result);die;
        $this->assign('data',$result['data']);
        return $this->fetch();
    }

    //立即付款
    public function re_pay(){
        $url = "index/kuaidi";
        $data = array();
        $data['typeCom'] = input('typeCom','');
        $data['typeNu'] = input('typeNu',0);
        $result = $this->curlGet($url,$data);
        $result = json_decode($result);
        $this->assign('data',$result['data']);
        return $this->fetch();
    }

	//三方登录
	public function third_login($channel)
    {
		$config = $this->login_config[$channel];
        $OAuth  = OAuth::getInstance($config, $channel);
        return redirect($OAuth->getAuthorizeURL());
		//xcx.gz12.hostadm.net/wechat/auth/getCodeToUrl.php?rk=qdshop&cb=http://mallqdshop.com/User/callback/channel/weixin

    }

	//回调并绑定或者注册...Yip 20180125
	public function callback($channel) {
		$config = $this->login_config[$channel];
        $OAuth    = OAuth::getInstance($config, $channel);
        $OAuth->getAccessToken();
		$OAuth->setDisplay('mobile');
        $sns_info = $OAuth->userinfo();
		print_r($sns_info);


		$url = "passport/thirdPartLogin";
        $data = array();
        $data['uid'] = $sns_info['unionid'];
        $data['type'] = $this->login_config[$channel]['type'];
		$result = $this->curlPost($url,$data);
		$result = json_decode($result,true);
		//print_r($result);

		if($result['data']['bind']){
            session::set('user_id',$result['data']['user_id']);//会员ID
            session::set('rank_name',$result['data']['rank_name']);//等级名称
            session::set('user_rank',$result['data']['user_rank']);//会员等级
            session::set('discount',$result['data']['discount']);//会员折扣

			$this->success($result['message'],url('User/index'));
        }else{
			//这里是跳转去绑定的页面
			print_r('这里是跳转去绑定的页面');die;
			//$this->error($result['message']);
        }
        exit();
    }

}
