<?php
namespace app\home\controller;
use anerg\OAuth2\OAuth;
use think\Controller;
use think\Session;
use think\Request;

class Order extends Common
{

    public function __construct()
    {
        parent::__construct();
        $this->assign('footer_on','user');//底部高亮

        $request = \think\Request::instance();
        $action =  $request->action();

        //Session::delete('user_id');//清除登录状态

        //不需要登录的操作
        $no_login_action = array('login','third_login','callback','do_login','do_login_mobile','logout','register','do_register','forget','forget_one','forget_two','forget_third','getCode','jsonRegionC','jsonRegionD','send_email_code');
        //未登录处理

        //print_r(session('user_id'));die;
        if(empty($this->user_id)){
            if(!in_array(strtolower($action),array_map('strtolower',$no_login_action))){
                header("Location:".url('User/login'));exit;
            }
        }else{
			if(in_array(strtolower($action),array_map('strtolower',$no_login_action)) && strtolower($action)!='logout' && strtolower($action)!='jsonregionc' && strtolower($action)!='jsonregiond' && strtolower($action)!='getcode'){
                header("Location:".url('User/index'));exit;
            }
		}

        $this->assign('left','用户信息');



		//第三方登录配置信息
		$this->login_config = array(
			'qq'=>array(
				//qq
				'type' =>'QQ',
				'app_key'    => '101454505',
				'app_secret' => '26c432ac19d297cb120f00ae6c1b14c7',
				'scope'      => 'get_user_info',
				'callback'   => array(
					'default' => "http://" . $_SERVER['HTTP_HOST'] . "/User/callback/channel/qq"
				)
			),
			'wx_qrcode'=>array(
				'type' =>'Wechat',
				'app_key'    => 'wxa1470c5bc33aa48b',
				'app_secret' => '496ba108e8886b223b7814c91c3b34b1',
				'scope'      => 'snsapi_login',
				'response_type'      => 'code',
				'grant_type'      => 'authorization_code',
				'callback'   => array(
					'default' => 'http://'.$_SERVER ["HTTP_HOST"].'/User/callback/channel/wx_qrcode'
				)
			)
		);

    }


    //订单列表
    public function index()
    {
        //标题
        $this->assign('$site_title','-我的订单');

        $api = "Order/getOrderList";
        $data['user_id'] = $this->user_id;
        $data['page'] = input('page',1);
        $data['screen'] = input('screen',1);//时间筛选
        $data['state'] = input('state',0);//订单状态筛选
        $result = $this->curlGet($api,$data);
        //var_dump($result);exit;
        $result = json_decode($result,true);

        //物流
        if(isset($result['data']['order_data'])){
            foreach($result['data']['order_data'] as $k=>$v){
                if($v['shipping_name'] && $v['invoice_no']){
                    $url = "index/kuaidi";
                    $kuaidi_data = array();
                    $kuaidi_data['typeCom'] = $v['shipping_name'];
                    $kuaidi_data['typeNu'] = $v['invoice_no'];
                    $kuaidi_json = $this->curlGet($url,$kuaidi_data);
                    $kuaidi = json_decode($kuaidi_json,true);
                    //$this->assign('kuaidi',$kuaidi['data']);
                    $result['data']['order_data'][$k]['kuaidi'] = $kuaidi['data'];
                }
            }
        }


        //print_r($result['data']['order_data']);exit;
        $this->assign('order_data',$result['data']['order_data']);
        $this->assign('pager',$result['data']['pager']);



        //订单状态
        $state = $data['state'];
        $this->assign('state',$state);
        $state_list = array(
            '所有订单'=>'0',
            '待付款'=>'100',
            '待发货'=>'102',
            '待收货'=>'106',
            '待评价'=>'108',
            '已完成'=>'109',
            '售后中'=>'117'
        );

        $this->assign('state_list',$state_list);

        //筛选
        $st_ajax = input('st_ajax',0,'intval');
        $this->assign('st_ajax',$st_ajax);
        if($st_ajax){
            //var_dump($result['data']['order_data']);
            echo $this->fetch('order_ajax');exit;
        }

        //异步加载分页数据
        $is_ajax = input('is_ajax',0,'intval');
        $this->assign('is_ajax',$is_ajax);
        if($is_ajax){
            echo $this->fetch('order_ajax');exit;
        }
        return $this->fetch();

    }

/*    //订单确认收货 ajax
    function confirm_order(){
        $data['user_id'] = $this->user_id;
        $data['order_id'] = input('order_id',0);
        $api = "User/arrivedUserOrder";
        $result = $this->curlGet($api,$data);
        //$result = json_decode($result,true);
        echo $result;
    }

    //取消订单
    function cancel_order(){
        $data['user_id'] = $this->user_id;
        $data['order_id'] = input('order_id',0);
        $api = "User/cancelUserOrder";
        $result = $this->curlGet($api,$data);
        //$result = json_decode($result,true);
        echo $result;
    }*/




    //弹窗
    public function details_ajax(){
        $goods_id =  input('goods_id','','intval') ? input('goods_id','','intval') : 0;
        if(!$goods_id){
            $this->error('该商品不存在！');
        }

        $api = "goods/getGoodsInfo";
        $data = array();
        $data['user_id'] = $this->user_id;
        $data['goods_id'] = $goods_id;
        $result = $this->curlGet($api,$data);
        $result = json_decode($result,true);
        if(!$result['data']){
            $this->error($result['message']);
        }
        if ($result['data']['goods_status'] == 1 || $result['data']['goods_status'] == 2 || $result['data']['goods_status'] == 3) {
            $this->error('商品未出售');
        }
        $cat_id = $result['data']['cat_id'];
        // print_r($result['data']);die;
        $this->assign('data',$result['data']);
        $this->assign('comment_count',count($result['data']['comment']));//好评数量。做判断用
        $this->assign('goods_id', $goods_id);
        //var_dump($goods_id);exit;

        // 增加查看次数
        $api = "goods/addClickCount";
        $data = array();
        $data['goods_id'] = $goods_id;
        $this->curlGet($api,$data);

        //评论
        $api = "goods/getGoodsComment";
        $data = array();
        $data['goods_id'] = $goods_id;
        $data['page_size'] = 20;
        $data['page'] = input('page','','intval') ? input('page','','intval') : 1;
        $data['type'] = input('type','','intval') ? input('type','','intval') : 0;//0所有评价 1好评 2中评 3差评 4晒单
        $result = $this->curlGet($api,$data);
        $result = json_decode($result,true);
        $this->assign('comment_data',$result['data']);

        //print_r($result['data']);die;

        //获取推荐商品
        $api = "goods/query";
        $data = array();
        $data['cat_id'] = 0;
        $data['supplier_id'] = '-1';
        $data['filter'] = 'is_hot';
        $data['size'] = 3;
        $hot = $this->curlGet($api,$data);
        $hot = json_decode($hot,true);
        if ($hot['data']['list']) {
            $this->assign('hot',$hot['data']);
        } else {
            $api = "goods/query";
            $data = array();
            $data['cat_id'] = $cat_id;
            $data['supplier_id'] = '-1';
            $data['size'] = 3;
            $hot = $this->curlGet($api,$data);
            $hot = json_decode($hot,true);
            $this->assign('hot',$hot['data']);
        }
        // print_r($hot['data']);die();

        // 私信举报原因
        $url = "user/getReportReason";
        $data = array();
        $result = $this->curlGet($url);
        $result = json_decode($result,true);//json转数组
        $this->assign('reason_list', $result['data']);

        $html = $this->fetch();
        return ['html' => $html, 'goods_id' => $goods_id];
    }


    //订单详情
    function order_details(){
        $api = "Order/order_details";
        $data['user_id'] = $this->user_id;
        $data['order_id'] = input('order_id',0);
        $result_json = $this->curlGet($api,$data);
        $result = json_decode($result_json,true);//json转数组


        if($result['data']['shipping_name'] && $result['data']['invoice_no']){
            $url = "index/kuaidi";
            $kuaidi_data = array();
            $kuaidi_data['typeCom'] = $result['data']['shipping_name'];
            $kuaidi_data['typeNu'] = $result['data']['invoice_no'];
            //$kuaidi_data['typeCom'] = '圆通速递';
            //$kuaidi_data['typeNu'] = '800410081166733399';
            $kuaidi_json = $this->curlGet($url,$kuaidi_data);
            $kuaidi = json_decode($kuaidi_json,true);
            $result['data']['kuaidi'] = $kuaidi['data'];
        }

        $this->assign('data',$result['data']);
        //print_r($result);exit;

        return $this->fetch();
    }

    //订单评价
    function order_appraise(){
        $data['user_id'] = $this->user_id;
        $info_json = input('goods_arr',0);
        $data['info'] = json_decode($info_json);
        if(!$info_json){
            exit;
        }
        $api = "Order/order_goods_appraise";
        $result_json = $this->curlPost($api,$data);
        //$result = json_decode($result_json,true);//json转数组
        echo $result_json;
    }






    //获取订单数据
/*    public function order_ajax(){
        $api = "Order/getOrderList";
        $data['user_id'] = $this->user_id;
        $data['page'] = input('page',1);
        $data['screen_order'] = input('screen_order',1);
        $result = $this->curlGet($api,$data);
        $result = json_decode($result,true);
        $this->assign('order_data',$result);
        $is_ajax = input('is_ajax');
        if($is_ajax){
            echo $this->fetch('order_ajax');exit();
        }
        return $this->fetch();
    }*/

    //设计作品删除
    /*public function del_finds(){
        $api = "User/delFinds";
        $data = array();
        $data['user_id'] = $this->user_id;
        $data['find_id'] = input('find','0','intval');
        $del_find_data = $this->curlGet($api,$data);
        $del_find_data = json_decode($del_find_data,true);
        echo json_encode($del_find_data);

    }*/

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
		$data['page_size'] = 9;//显示数据数量
        $data['page'] = input('p','','intval') ? input('p','','intval') : 1;
        $data['is_used'] = input('is_used','','intval') ? input('is_used','','intval') : 0;
        $result = $this->curlGet($url,$data);
        $result = json_decode($result,true);//json转数组
        //print_r($result);die;

        $this->assign('data',$result['data']);
        $this->assign('is_used',$data['is_used']);

		$pageHtml = '';
		if($result['data']['pager']){
			//组装分页
			$prePage = $this->getPage($result['data']['pager']['page'],$result['data']['pager']['page_count']);
			$pageHtml = '  <div class="h-page"><a class="pn-first" href="'.url('user/bonus',array('p'=>1,'is_used'=>$data['is_used'])).'">首页</a>';
			if($prePage['page']>1){
			   $pageHtml .= '<a class="pn-prev" title="上一页" href="'.url('user/bonus',array('p'=>$prePage['page']-1,'is_used'=>$data['is_used'])).'">上一页</a>';
			 }
			for ($i = $prePage['start']; $i <= $prePage['end']; $i++) {
				if($i == $prePage['page']){
				$pageHtml .= '<a class="pn-num selected">'.$i.'</a>';
				}else{
					$pageHtml .= '<a class="pn-num" href="'.url('user/bonus',array('p'=>$i,'is_used'=>$data['is_used'])).'">'.$i.'</a>';
				}
			}

			if($prePage['page']<$prePage['end']){
			   $pageHtml .= '<a class="pn-next"  title="下一页" href="'.url('user/bonus',array('p'=>$prePage['page']+1,'is_used'=>$data['is_used'])).'">下一页</a>';
			 }
			$pageHtml .= '<a class="pn-last" href="'.url('user/bonus',array('p'=>$prePage['pages'],'is_used'=>$data['is_used'])).'">尾页</a><span class="page-num">共'.$prePage['pages'].'页</span></div>';
		}
		$this->assign('toPage',$pageHtml);

		$this->assign('class','mc my-coupon');
		$this->assign('left','我的资产');

        return $this->fetch();
    }

    //我的余额及流水账记录
    public function account()
    {
        $url = "user/getUserIntegral";
        $data = array();
        $data['user_id'] = $this->user_id;
        $data['page_size'] = 10;
        $data['page'] = input('p','','intval') ? input('p','','intval') : 1;
        $data['account_type'] = 'user_money';//user_money（消费金额）、 pay_points（消费积分）
        $data['log_type'] = input('log_type','','intval') ? input('log_type','','intval') : 0;//0（全部）、1（增加）、2（减少）
        $result = $this->curlGet($url,$data);
        $result = json_decode($result,true);//json转数组
        //print_r($result);die;
        $this->assign('data',$result['data']);
        $this->assign('log_type',$data['log_type']);

		$pageHtml = '';
		if($result['data']['pager']){
			//组装分页
			$prePage = $this->getPage($result['data']['pager']['page'],$result['data']['pager']['page_count']);
			$pageHtml = '  <div class="h-page"><a class="pn-first" href="'.url('user/account',array('p'=>1,'log_type'=>$data['log_type'])).'">首页</a>';
			if($prePage['page']>1){
			   $pageHtml .= '<a class="pn-prev" title="上一页" href="'.url('user/account',array('p'=>$prePage['page']-1,'log_type'=>$data['log_type'])).'">上一页</a>';
			 }
			for ($i = $prePage['start']; $i <= $prePage['end']; $i++) {
				if($i == $prePage['page']){
				$pageHtml .= '<a class="pn-num selected">'.$i.'</a>';
				}else{
					$pageHtml .= '<a class="pn-num" href="'.url('user/account',array('p'=>$i,'log_type'=>$data['log_type'])).'">'.$i.'</a>';
				}
			}

			if($prePage['page']<$prePage['end']){
			   $pageHtml .= '<a class="pn-next"  title="下一页" href="'.url('user/account',array('p'=>$prePage['page']+1,'log_type'=>$data['log_type'])).'">下一页</a>';
			 }
			$pageHtml .= '<a class="pn-last" href="'.url('user/account',array('p'=>$prePage['pages'],'log_type'=>$data['log_type'])).'">尾页</a><span class="page-num">共'.$prePage['pages'].'页</span></div>';
		}
		$this->assign('toPage',$pageHtml);

		$this->assign('class','mc asset');
		$this->assign('left','我的资产');

        return $this->fetch();
    }

    //我的余额 - 充值
    public function recharge()
    {

		$this->assign('class','mc recharge');

        $url = "payment/pay_list";
        $result = $this->curlGet($url);
        $result = json_decode($result,true);//json转数组
        //print_r($result);die;

		foreach($result['data'] as $k=>$v){
			if($v['pay_id'] == 0){
				unset($result['data'][$k]);
			}
		}
        $this->assign('pay_list',$result['data']);

        return $this->fetch();
    }

    //我的余额 - 充值操作
    public function do_recharge()
    {
		$pay_id = input('pay_id');
		$amount = input('amount');
		$user_note = input('user_note','1');

        $url = "payment/recharge";
		$data['user_id']= $this->user_id;
		$data['pay_id']= $pay_id;
		$data['amount']= $amount;
		$data['user_note']= $user_note;
        $result = $this->curlPost($url,$data);
        $result = json_decode($result,true);//json转数组
		if (request()->isAjax()){
			$arr = array();
			$arr['code'] = $result['code'];
			$arr['message'] = '';
			if($result['code'] == '500'){
				$arr['message']=$result['message'];
				return json($arr);
			}else{
				$arr['code'] = getQrCode($result['data']['payment']['code_url']);
				return json($arr);
			}
		}else{
			if($result['code'] == '500'){
				$this->error($result['message']);
			}else{
				echo $result['data']['payment']['prepay_id'];
			}
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
        $data['page'] = input('p','','intval') ? input('p','','intval') : 1;
        $data['account_type'] = 'pay_points';//user_money（消费金额）、 pay_points（消费积分）
        $data['log_type'] = input('log_type','','intval') ? input('log_type','','intval') : 0;//0（全部）、1（增加）、2（减少）
        $result = $this->curlGet($url,$data);
        $result = json_decode($result,true);//json转数组
        //print_r($result);die;
        $this->assign('data',$result['data']);
        $this->assign('log_type',$data['log_type']);

		$pageHtml = '';
		if($result['data']['pager']){
			//组装分页
			$prePage = $this->getPage($result['data']['pager']['page'],$result['data']['pager']['page_count']);
			$pageHtml = '  <div class="h-page"><a class="pn-first" href="'.url('user/integral',array('p'=>1,'log_type'=>$data['log_type'])).'">首页</a>';
			if($prePage['page']>1){
			   $pageHtml .= '<a class="pn-prev" title="上一页" href="'.url('user/integral',array('p'=>$prePage['page']-1,'log_type'=>$data['log_type'])).'">上一页</a>';
			 }
			for ($i = $prePage['start']; $i <= $prePage['end']; $i++) {
				if($i == $prePage['page']){
				$pageHtml .= '<a class="pn-num selected">'.$i.'</a>';
				}else{
					$pageHtml .= '<a class="pn-num" href="'.url('user/integral',array('p'=>$i,'log_type'=>$data['log_type'])).'">'.$i.'</a>';
				}
			}

			if($prePage['page']<$prePage['end']){
			   $pageHtml .= '<a class="pn-next"  title="下一页" href="'.url('user/integral',array('p'=>$prePage['page']+1,'log_type'=>$data['log_type'])).'">下一页</a>';
			 }
			$pageHtml .= '<a class="pn-last" href="'.url('user/integral',array('p'=>$prePage['pages'],'log_type'=>$data['log_type'])).'">尾页</a><span class="page-num">共'.$prePage['pages'].'页</span></div>';
		}
		$this->assign('toPage',$pageHtml);

		$this->assign('class','mc asset');
		$this->assign('left','我的资产');

        return $this->fetch();
    }

/*    //我的订单 - 列表
    public function order_list()
    {
        $url = "user/getUserOrder";
        $data = array();
        $data['user_id'] = $this->user_id;
        $data['page_size'] = 5;//显示数据数量
        $data['page'] = input('p','','intval') ? input('p','','intval') : 1;
        $data['status'] = input('status','','intval') ? input('status','','intval') : 0;//0（所有订单）、2（待付款）、3（待发货）、4（待收货）、5（已完成）
        $result = $this->curlGet($url,$data);
        $result = json_decode($result,true);//json转数组
        //print_r($result);die;
        $this->assign('data',$result['data']);
        $this->assign('status',$data['status']);

		$this->assign('left','我的订单');
		$this->assign('class','mc trading');

        $pageHtml = '';
		if($result['data']['pager']){
			//组装分页
			$prePage = $this->getPage($result['data']['pager']['page'],$result['data']['pager']['page_count']);
			$pageHtml = '  <div class="h-page"><a class="pn-first" href="'.url('user/order_list',array('p'=>1)).'">首页</a>';
			if($prePage['page']>1){
			   $pageHtml .= '<a class="pn-prev" title="上一页" href="'.url('user/order_list',array('p'=>$prePage['page']-1)).'">上一页</a>';
			 }
			for ($i = $prePage['start']; $i <= $prePage['end']; $i++) {
				if($i == $prePage['page']){
				$pageHtml .= '<a class="pn-num selected">'.$i.'</a>';
				}else{
					$pageHtml .= '<a class="pn-num" href="'.url('user/order_list',array('p'=>$i)).'">'.$i.'</a>';
				}
			}

			if($prePage['page']<$prePage['end']){
			   $pageHtml .= '<a class="pn-next"  title="下一页" href="'.url('user/order_list',array('p'=>$prePage['page']+1)).'">下一页</a>';
			 }
			$pageHtml .= '<a class="pn-last" href="'.url('user/order_list',array('p'=>$prePage['pages'])).'">尾页</a><span class="page-num">共'.$prePage['pages'].'页</span></div>';
		}
		$this->assign('toPage',$pageHtml);

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
        //print_r($result);die();
		$result['data']['order_info']['add_time'] = '提交于'.$result['data']['order_info']['add_time'];
		$result['data']['order_info']['shipping_time_end'] = '完成于'.$result['data']['order_info']['shipping_time_end'];
        //获取快递信息
       if(!empty($result['data']['order_info']['invoice_no']) && !empty($result['data']['order_info']['shipping_name'])){
            $url = "index/kuaidi";
            $kuaidi_data = array();
            $kuaidi_data['typeCom'] = $result['data']['order_info']['shipping_name'];
            $kuaidi_data['typeNu'] = $result['data']['order_info']['invoice_no'];
			//$kuaidi_data['typeCom'] = '天天快递';
			//$kuaidi_data['typeNu'] = '668298523342';
            $kuaidi_result = $this->curlGet($url,$kuaidi_data);
            $kuaidi_result = json_decode($kuaidi_result,true);
			foreach($kuaidi_result['data']['data'] as $k=>$v){
				$ku[date('Y-m-d',strtotime($v['time']))][] = array('time'=>date('H:i:s',strtotime($v['time'])),'data'=>$v['context']);
			}
			//print_r($ku);die;
            $this->assign('kuaidi_data',$ku);
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

		$this->assign('status',array(2=>'待付款',3=>'待发货',4=>'待收货',5=>'完成',6=>'取消'));

		$order_status = $result['data']['order_info']['order_status'];
		$shipping_status = $result['data']['order_info']['shipping_status'];
		$pay_status = $result['data']['order_info']['pay_status'];

		$selected = 0;
		$class = 'mc mc-index';
		switch($result['data']['order_info']['status']){
			case 0:
				$selected = 0; //所有订单
				$class = 'mc mc-index';
				break;
			case 2:
				$selected = 1; //待付款
				$class = 'mc pending-pay';
				break;
			case 3:
				$selected = 2; //待发货
				$class = 'mc pending-send';
				break;
			case 4:
				$selected = 3; //待收货
				$class = 'mc pending-receive';
				break;
			case 5:
				$selected = 4; //已完成
				$class = 'mc order-complete';
				break;
			case 6:
				$selected = 5; //取消
				$class = 'mc order-cancel';
				break;
		}

		$this->assign('class',$class);
		$this->assign('selected',$selected);

		print_r($result['data']);

		$this->assign('left','我的订单');

        $this->assign('order',$result['data']);
        return $this->fetch();
    }*/

    //我的信息
    public function user_info()
    {
        $url = "user/getUserInfo";
        $data = array();
        $data['user_id'] = $this->user_id;
        $result = $this->curlGet($url,$data);
        $result = json_decode($result,true);//json转数组
        $this->assign('user_info',$result['data']);

        // 出生日期
        $date_data = array(
            'field_order' => 'YMD',
            'prefix' => 'birthday',
            'time' => $result['data']['birthday'],
            'start_year' => '-60',
            'end_year' => '+1',
            'display_months' => 'true',
            'display_days' => 'true',
            'month_format' => '%m',
        );
        $this->assign('birthday_form', $this->html_select_date($date_data));

        //省
        $regionP = $this->getRegionP();
        $this->assign('regionP',$regionP);

        //市
        $regionC = array();
        if ($result['data']['province']) {
            foreach ($regionP as $key => $value) {
                if ($value['region_id'] == $result['data']['province']) {
                    $pkey = $value['pkey'];
                }
            }
            $regionC = $this->getRegionC($pkey);
        }
        $this->assign('regionC',$regionC);

        //区
        $regionD = array();
        if ($result['data']['city']) {
            foreach ($regionC as $key => $value) {
                if ($value['region_id'] == $result['data']['city']) {
                    $ckey = $value['ckey'];
                }
            }
            $regionD = $this->getRegionD($pkey, $ckey);
        }
        $this->assign('regionD',$regionD);

        // school - 省
        $schoolP = $this->getSchoolP();
        $this->assign('schoolP',$schoolP);

        //学校 - 市
        $schoolC = array();
        if ($result['data']['sh_province']) {
            foreach ($schoolP as $key => $value) {
                if ($value['pr_id'] == $result['data']['sh_province']) {
                    $sh_province = $value['pr_id'];
                }
            }
            $schoolC = $this->getSchoolC($sh_province);
        }
        $this->assign('schoolC',$schoolC);

        //学校 - 区
        $schoolS = array();
        if ($result['data']['sh_city']) {
            foreach ($schoolC as $key => $value) {
                if ($value['ci_id'] == $result['data']['sh_city']) {
                    $sh_city = $value['ci_id'];
                }
            }
            $schoolS = $this->getSchoolS($sh_city);
        }
        $this->assign('schoolS',$schoolS);

        // 所有领域
        $url = "user/getFields";
        $result = $this->curlGet($url);
        $result = json_decode($result,true);//json转数组
        $this->assign('fields',$result['data']);

        // 常用领域
        $url = "user/getCommonFields";
        $result = $this->curlGet($url);
        $result = json_decode($result,true);//json转数组
        $this->assign('common_fields',$result['data']);

        return $this->fetch();
    }

    //保存用户信息
    public function save_user_info()
    {
        $url = "user/updateUserInfo";
        $data = array();
        $data['user_id'] = $this->user_id;
        $data['nickname'] = input('nickname','','trim,strip_tags,htmlspecialchars');//昵称
        $data['sex'] = input('sex','0','intval');//性别
        $birthday_year = input('birthdayYear','0','intval');//年
        $birthday_month = input('birthdayMonth','0','intval');//月
        $birthday_day = input('birthdayDay','0','intval');//日
        $data['birthday'] = $birthday_year . '-' . $birthday_month . '-' . $birthday_day;
        $data['province'] = input('province','0','intval');//省
        $data['city'] = input('city','0','intval');//市
        $data['district'] = input('district','0','intval');//市
        $data['sh_province'] = input('sh_province','0','intval');//学校 - 省
        $data['sh_city'] = input('sh_city','0','intval');//学校 - 市
        $data['sh_school'] = input('sh_school','0','intval');//学校
        $data['fields'] = rtrim(input('fields','','trim,strip_tags,htmlspecialchars'), ',');//领域
        $data['weixin'] = rtrim(input('weixin','','trim,strip_tags,htmlspecialchars'), ',');//微信
        $data['wx_open'] = input('wx_open','0','intval');//是否公开微信
        $data['qq'] = rtrim(input('qq','','trim,strip_tags,htmlspecialchars'), ',');//QQ
        $data['qq_open'] = input('qq_open','0','intval');//是否公开QQ
        $data['profile'] = rtrim(input('profile','','trim,strip_tags,htmlspecialchars'), ',');//个性签名
        $data['weibo'] = rtrim(input('weibo','','trim,strip_tags,htmlspecialchars'), ',');//微博
        $data['facebook'] = rtrim(input('facebook','','trim,strip_tags,htmlspecialchars'), ',');//facebook
        $data['instagram'] = rtrim(input('instagram','','trim,strip_tags,htmlspecialchars'), ',');//instagram
        $data['website'] = rtrim(input('website','','trim,strip_tags,htmlspecialchars'), ',');//个人网站

        $result = $this->curlPost($url,$data);
        $result = json_decode($result,true);//json转数组
        if($result['code'] == 500){
            $this->error($result['message']);
        }else{
            $this->success($result['message'],url('User/user_info'));
        }
    }

    //账户安全
    public function account_security()
    {
        $url = "user/getUserInfo";
        $data = array();
        $data['user_id'] = $this->user_id;
        $result = $this->curlGet($url,$data);
        $result = json_decode($result,true);//json转数组
        $this->assign('user_info',$result['data']);

        return $this->fetch();
    }

    //实名认证
    public function renzheng()
    {
        return $this->fetch();
    }

    //实名认证提交
    public function do_renzheng()
    {
        $url = "user/doRenzheng";
        $data = array();
        $data['user_id'] = $this->user_id;
        $data['real_name'] = input('real_name','','trim,strip_tags,htmlspecialchars');
        $data['card'] = input('card','','trim,strip_tags,htmlspecialchars');
        $data['mobile_phone'] = input('mobile_phone','','trim,strip_tags,htmlspecialchars');
        $result = $this->curlPost($url,$data);
        $result = json_decode($result,true);//json转数组
        if($result['code'] == 500){
            $this->error($result['message']);
        }else{
            $this->success($result['message'],url('User/account_security'));
        }
    }

    //修改手机号码
    public function edit_mobile()
    {
        return $this->fetch();
    }

    //修改手机号码验证
    public function edit_mobile_two()
    {
        if (isset($_SERVER["HTTP_REFERER"])) {
            if (Request::instance()->isPost()) {
                $url = "passport/validate_phone";
                $data = array();
                $data['mobile_phone'] = input('mobile_phone','','trim');
                $data['mobile_code'] = input('mobile_code','','trim');
                $result = $this->curlPost($url,$data);
                $result = json_decode($result,true);
                if($result['code'] == 200){
                    return $this->fetch();
                }else{
                    $this->error($result['message'], url('User/account_security'));
                }
            } else {
                return $this->fetch();
            }
        } else {
            $this->error('非法操作！', url('User/account_security'));
        }
    }

    //修改手机号码验证
    public function edit_mobile_three()
    {
        $data = array();
        $data['mobile_phone'] = input('mobile_phone','','trim');
        $data['mobile_code'] = input('mobile_code','','trim');

        $url = "passport/validate_phone2";
        $result = $this->curlPost($url,$data);
        $result = json_decode($result,true);
        if($result['code'] == 200){
            $data['user_id'] = $this->user_id;
            $url = "user/updateUserInfo";
            $result = $this->curlPost($url,$data);
            $result = json_decode($result, true);
            if($result['code'] == 200){
                $this->success($result['message'], url('User/account_security'));
            }else{
                $this->error($result['message'], url('User/edit_mobile'));
            }
        }else{
            $this->error($result['message'], url('User/edit_mobile'));
        }
    }

    //修改邮箱
    public function edit_email()
    {
        return $this->fetch();
    }

    //修改邮箱验证
    public function edit_email_two()
    {
        if (isset($_SERVER["HTTP_REFERER"])) {
            if (Request::instance()->isPost()) {
                $url = "passport/validate_phone";
                $data = array();
                $data['mobile_phone'] = input('mobile_phone','','trim');
                $data['mobile_code'] = input('mobile_code','','trim');
                $result = $this->curlPost($url,$data);
                $result = json_decode($result,true);
                if($result['code'] == 200){
                    return $this->fetch();
                }else{
                    $this->error($result['message'], url('User/account_security'));
                }
            } else {
                return $this->fetch();
            }
        } else {
            $this->error('非法操作！', url('User/account_security'));
        }
    }

    //上传头像
    public function save_headimg()
    {
        $url = "user/saveHeadimg";
        $data = array();
        $data['user_id'] = $this->user_id;
        $data['base64_image_content'] = input('imgBase');
        $result = $this->curlPost($url,$data);
        $result = json_decode($result);
        return $result;
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

    //我的信息 - 手机 2017.10.10 未完成
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

    //我的信息 - 手机 - 保存 2017.10.10 未完成
    public function save_tel()
    {
        $url = "user/updateUserInfo";
        $data = array();
        $data['user_id'] = $this->user_id;
        $data['mobile_phone'] = input('tel','','intval');
        $result = $this->curlPost($url,$data);
        $result = json_decode($result);
        return $result;
    }

    //发送邮箱绑定邮件
    public function send_bind_email()
    {
        $url = "user/sendBindEmail";
        $data = array();
        $data['user_id'] = $this->user_id;
        $data['email'] = input('email','','trim,strip_tags,htmlspecialchars');
        $result = $this->curlPost($url,$data);
        $result = json_decode($result);
        return $result;
    }

    // 邮箱验证绑定
    public function valid_email()
    {
        $url = "user/validEmail";
        $data = array();
        $data['hash'] = input('hash','','trim,strip_tags,htmlspecialchars');
        $result = $this->curlGet($url,$data);
        $result = json_decode($result,true);//json转数组
        if($result['code'] == 500){
            $this->error($result['message'], url('User/account_security'));
        }else{
            $this->success($result['message'], url('User/account_security'));
        }
    }

    //发送验证邮件
    public function send_valid_email()
    {
        $url = "user/sendValidEmail";
        $data = array();
        $data['user_id'] = $this->user_id;
        $data['email'] = input('email','','trim,strip_tags,htmlspecialchars');
        $result = $this->curlPost($url,$data);
        $result = json_decode($result);
        return $result;
    }

    //发送验证邮件
    public function send_valid_email2()
    {
        $url = "user/sendValidEmail2";
        $data = array();
        $data['user_id'] = $this->user_id;
        $data['email'] = input('email','','trim,strip_tags,htmlspecialchars');
        $result = $this->curlPost($url,$data);
        $result = json_decode($result);
        return $result;
    }

    //发送修改邮件
    public function send_edit_email()
    {
        $url = "user/sendEditEmail";
        $data = array();
        $data['user_id'] = $this->user_id;
        $data['email'] = input('email','','trim,strip_tags,htmlspecialchars');
        $result = $this->curlPost($url,$data);
        $result = json_decode($result);
        return $result;
    }

    // 修改手机邮箱验证
    public function valid_email2()
    {
        $url = "user/validEmail2";
        $data = array();
        $data['hash'] = input('hash','','trim,strip_tags,htmlspecialchars');
        $result = $this->curlGet($url,$data);
        $result = json_decode($result,true);//json转数组
        if($result['code'] == 500){
            $this->error($result['message'], url('User/account_security'));
        }else{
            $this->success($result['message'], url('User/edit_mobile_two'));
        }
    }

    // 修改邮箱验证
    public function valid_email3()
    {
        $url = "user/validEmail3";
        $data = array();
        $data['hash'] = input('hash','','trim,strip_tags,htmlspecialchars');
        $result = $this->curlGet($url,$data);
        $result = json_decode($result,true);//json转数组
        if($result['code'] == 500){
            $this->error($result['message'], url('User/account_security'));
        }else{
            $this->success($result['message'], url('User/edit_email_two'));
        }
    }

    // 验证修改邮箱
    public function valid_edit_email()
    {
        $url = "user/validEditEmail";
        $data = array();
        $data['hash'] = input('hash','','trim,strip_tags,htmlspecialchars');
        $result = $this->curlGet($url,$data);
        $result = json_decode($result,true);//json转数组
        if($result['code'] == 500){
            $this->error($result['message'], url('User/edit_email'));
        }else{
            $this->success($result['message'], url('User/account_security'));
        }
    }

    //收货地址
    public function address()
    {
        $url = "user/getUserAddress";
        $data = array();
        $data['user_id'] = $this->user_id;
        $result = $this->curlGet($url,$data);
        $result = json_decode($result,true);//json转数组
        $this->assign('data',$result['data']);

		$pageHtml = '';
		if($result['data']['pager']){
			//组装分页
			$prePage = $this->getPage($result['data']['pager']['page'],$result['data']['pager']['page_count']);
			$pageHtml = '  <div class="h-page"><a class="pn-first" href="'.url('user/address',array('p'=>1)).'">首页</a>';
			if($prePage['page']>1){
			   $pageHtml .= '<a class="pn-prev" title="上一页" href="'.url('user/address',array('p'=>$prePage['page']-1)).'">上一页</a>';
			 }
			for ($i = $prePage['start']; $i <= $prePage['end']; $i++) {
				if($i == $prePage['page']){
				$pageHtml .= '<a class="pn-num selected">'.$i.'</a>';
				}else{
					$pageHtml .= '<a class="pn-num" href="'.url('user/address',array('p'=>$i)).'">'.$i.'</a>';
				}
			}

			if($prePage['page']<$prePage['end']){
			   $pageHtml .= '<a class="pn-next"  title="下一页" href="'.url('user/address',array('p'=>$prePage['page']+1)).'">下一页</a>';
			 }
			$pageHtml .= '<a class="pn-last" href="'.url('user/address',array('p'=>$prePage['pages'])).'">尾页</a><span class="page-num">共'.$prePage['pages'].'页</span></div>';
		}
		$this->assign('toPage',$pageHtml);

		$this->assign('class','mc street');
		$this->assign('left','收货地址');

        $regionP = $this->getRegionP();
        $this->assign('regionP',$regionP);//省份

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
        $data['mobile'] = input('mobile','','trim');//手机号码
        $data['tel'] = input('tel','','trim');//固定电话
        $data['province'] = input('province','','intval') ? input('province','','intval') : 0;//省
        $data['city'] = input('city','','intval') ? input('city','','intval') : 0;//市
        $data['district'] = input('district','','intval') ? input('district','','intval') : 0;//区
        $data['address'] = input('address','','trim');//详细地址
        $data['is_default'] = input('is_default','','intval');//是否默认地址
        $back_url = input('back_url') ? input('back_url') : '';

        $result = $this->curlPost($url,$data);
        $result = json_decode($result,true);//json转数组
        if($result['code'] == 500){
            $this->error($result['message']);
        }else{
            $back_url = $back_url ? $back_url : url('User/address');
            $this->success($result['message'],$back_url);
        }
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
        $data['mobile'] = input('mobile','','trim');//手机号码
        $data['tel'] = input('tel','','trim');//固定电话
        $data['province'] = input('province','','intval') ? input('province','','intval') : input('province_old','','intval');
        $data['city'] = input('city','','intval') ? input('city','','intval') : input('city_old','','intval');
        $data['district'] = input('district','','intval') ? input('district','','intval') : input('district_old','','intval');
        $data['address'] = input('address','','trim');//联系电话
        $data['is_default'] = input('is_default','','intval');//是否默认地址
        $back_url = input('back_url') ? input('back_url') : '';
        // print_r($data);die;
        $result = $this->curlPost($url,$data);
        $result = json_decode($result,true);//json转数组
        if($result['code'] == 500){
            $this->error($result['message']);
        }else{
            $back_url = $back_url ? $back_url : url('User/address');
            $this->success($result['message'],$back_url);
        }
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

    public function jsonSchoolC()
    {
        $sh_province = input('sh_province','','intval');
        $result = $this->getSchoolC($sh_province);
        echo json_encode($result);
        exit();
    }

    public function jsonSchoolS()
    {
        $sh_city = input('sh_city','','intval');
        $result = $this->getSchoolS($sh_city);
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
        $result = json_decode($result,true);//json转数组
        if($result['code'] == 500){
            $this->error($result['message']);
        }else{
            $this->success($result['message'],url('User/account_security'));
        }
    }

    //我的收藏 - 商品
    public function collect_goods()
    {
        $url = "user/getUserCollect";
        $data = array();
        $data['user_id'] = $this->user_id;
        $data['page_size'] = 12;//显示数据数量
        $data['page'] = input('p','','intval') ? input('p','','intval') : 1;
        $result = $this->curlGet($url,$data);
        $result = json_decode($result,true);//json转数组
        //print_r($result);die;
        $this->assign('data',$result['data']);


		$this->assign('class','mc collection');
		$this->assign('left','我的收藏');

		$pageHtml = '';
		if($result['data']['pager']){
			//组装分页
			$prePage = $this->getPage($result['data']['pager']['page'],$result['data']['pager']['page_count']);
			$pageHtml = '  <div class="h-page"><a class="pn-first" href="'.url('user/order_list',array('p'=>1)).'">首页</a>';
			if($prePage['page']>1){
			   $pageHtml .= '<a class="pn-prev" title="上一页" href="'.url('user/order_list',array('p'=>$prePage['page']-1)).'">上一页</a>';
			 }
			for ($i = $prePage['start']; $i <= $prePage['end']; $i++) {
				if($i == $prePage['page']){
				$pageHtml .= '<a class="pn-num selected">'.$i.'</a>';
				}else{
					$pageHtml .= '<a class="pn-num" href="'.url('user/order_list',array('p'=>$i)).'">'.$i.'</a>';
				}
			}

			if($prePage['page']<$prePage['end']){
			   $pageHtml .= '<a class="pn-next"  title="下一页" href="'.url('user/order_list',array('p'=>$prePage['page']+1)).'">下一页</a>';
			 }
			$pageHtml .= '<a class="pn-last" href="'.url('user/order_list',array('p'=>$prePage['pages'])).'">尾页</a><span class="page-num">共'.$prePage['pages'].'页</span></div>';
		}
		$this->assign('toPage',$pageHtml);


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
        $data['collection_id'] = implode(',',$arr['chk_value']);//print_r($data);die;
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
        $is_ajax = input('is_ajax','','intval') ? input('is_ajax','','intval') : 0;
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
        $data['collection_id'] = implode(',',$arr['chk_value']);//print_r($data);die;
        $result = $this->curlGet($url,$data);
        $result = json_decode($result);//print_r($result);die;
        return $result;
    }

    //我的消息
    public function my_message()
    {
        // 未读消息总数
        $url = "user/getUnreadCount";
        $data = array();
        $data['user_id'] = $this->user_id;
        $result = $this->curlGet($url,$data);
        $result = json_decode($result,true);//json转数组
        $this->assign('unread_count', $result['data']);

        // 是否有未读消息
        $url = "user/hasUnreadMessage";
        $data = array();
        $data['user_id'] = $this->user_id;
        $result = $this->curlGet($url,$data);
        $result = json_decode($result,true);//json转数组
        $this->assign('has_unread_message', $result['data']);

        $url = "user/getMyNews";
        $data = array();
        $data['user_id'] = $this->user_id;
        $data['msg_type'] = input('msg_type',0,'intval');
        $data['page_size'] = 10;//显示数据数量
        $data['page'] = input('page','','intval') ? input('page','','intval') : 1;
        $result = $this->curlGet($url,$data);
        $result = json_decode($result,true);//json转数组
        $this->assign('data',$result['data']);
        $this->assign('msg_type',$data['msg_type']);
        //异步加载分页数据
        $is_ajax = input('is_ajax',0,'intval');
        $this->assign('is_ajax',$is_ajax);
        if($is_ajax){
            echo $this->fetch('user/my_message_ajax');exit();
        }
        return $this->fetch();
    }

    //我的消息 - 删除
    public function del_my_message(){
        $url = "user/delMyNews";
        $data = array();
        $data['user_id'] = $this->user_id;
        $data['l_id'] = input('l_id','','trim,strip_tags,htmlspecialchars');
        $result = $this->curlPost($url,$data);
        $result = json_decode($result);
        return $result;
    }

    //我的消息 - 一键已读
    public function my_message_read(){
        $url = "user/getMyNewsChange";
        $data = array();
        $data['user_id'] = $this->user_id;
        $data['msg_type'] = input('msg_type',0,'intval');
        $result = $this->curlGet($url,$data);
        $result = json_decode($result,true);
        return $result;
    }

    //我的消息 - 详情
    public function my_message_details(){
        // 未读消息总数
        $url = "user/getUnreadCount";
        $data = array();
        $data['user_id'] = $this->user_id;
        $result = $this->curlGet($url,$data);
        $result = json_decode($result,true);//json转数组
        $this->assign('unread_count', $result['data']);

        // 是否有未读消息
        $url = "user/hasUnreadMessage";
        $data = array();
        $data['user_id'] = $this->user_id;
        $result = $this->curlGet($url,$data);
        $result = json_decode($result,true);//json转数组
        $this->assign('has_unread_message', $result['data']);

        $url = "user/getMyNewsDetail";
        $data = array();
        $data['user_id'] = $this->user_id;
        $data['l_id'] = input('l_id','','intval');
        $result = $this->curlGet($url,$data);
        $result = json_decode($result,true);//json转数组
        //print_r($result);die;
        $this->assign('data',$result['data']);

        $msg_type = input('msg_type',0,'intval');
        $this->assign('msg_type',$msg_type);
        $this->assign('l_id',$data['l_id']);
        return $this->fetch();
    }

    // 私信
    public function personal_letter() {
        // 未读消息总数
        $url = "user/getUnreadCount";
        $data = array();
        $data['user_id'] = $this->user_id;
        $result = $this->curlGet($url,$data);
        $result = json_decode($result,true);//json转数组
        $this->assign('unread_count', $result['data']);

        // 我的私信聊天用户列表
        $url = "user/getLetterUsers";
        $data = array();
        $data['user_id'] = $this->user_id;
        $result = $this->curlGet($url,$data);
        $result = json_decode($result,true);//json转数组
        $this->assign('letter_users', $result['data']);

        // 第一个用户
        $receive_user_id = 0;
        foreach ($result['data'] as $key => $value) {
            if ($key == 0) {
                $receive_user_id = $value['receive_user_id'];
                break;
            }
        }

        // 获取跟某个用户的私信聊天消息
        $url = "user/getLetters";
        $data = array();
        $data['user_id'] = $this->user_id;
        $data['receive_user_id'] = input('receive_user_id', $receive_user_id, 'intval');
        $result = $this->curlGet($url,$data);
        $result = json_decode($result,true);//json转数组
        $this->assign('letters', $result['data']);

        $this->assign('receive_user_id', $data['receive_user_id']);

        // 私信举报原因
        $url = "user/getReportReason";
        $data = array();
        $result = $this->curlGet($url);
        $result = json_decode($result,true);//json转数组
        $this->assign('reason_list', $result['data']);

        return $this->fetch();
    }

    // 发送私信
    public function send_letter() {
        $url = "user/sendLetter";
        $data = array();
        $data['user_id'] = $this->user_id;
        $data['receive_user_id'] = input('receive_user_id', 0, 'intval');
        $data['msg_content'] = input('msg_content','','trim,strip_tags,htmlspecialchars');
        $result = $this->curlPost($url,$data);
        $result = json_decode($result,true);
        return $result;
    }

    // AJAX发送私信
    public function send_letter_ajax() {
        $url = "user/sendLetter";
        $data = array();
        $data['user_id'] = $this->user_id;
        $data['receive_user_id'] = input('receive_user_id', 0, 'intval');
        $data['msg_content'] = input('msg_content','','trim,strip_tags,htmlspecialchars');
        $result = $this->curlPost($url,$data);
        $result = json_decode($result);
        return $result;
    }

    // 删除私信左侧用户
    public function remove_letter_user() {
        $url = "user/removeLetterUser";
        $data = array();
        $data['user_id'] = $this->user_id;
        $data['receive_user_id'] = input('receive_user_id', 0, 'intval');
        $result = $this->curlGet($url,$data);
        $result = json_decode($result,true);
        return $result;
    }

    // 删除私信
    public function remove_letter() {
        $url = "user/removeLetter";
        $data = array();
        $data['user_id'] = $this->user_id;
        $data['receive_user_id'] = input('receive_user_id', 0, 'intval');
        $data['msg_id'] = input('msg_id', 0, 'intval');
        $result = $this->curlGet($url,$data);
        $result = json_decode($result,true);
        return $result;
    }

    // 私信举报
    public function do_letter_report() {
        $url = "user/doLetterReport";
        $data = array();
        $data['msg_id'] = input('msg_id', 0, 'intval');
        $data['reason'] = input('reason','','trim,strip_tags,htmlspecialchars');
        $result = $this->curlPost($url,$data);
        $result = json_decode($result,true);
        return $result;
    }

    // 互动
    public function my_hudong() {
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
        $data['page_size'] = 3;//显示数据数量
        $data['page'] = input('p','','intval') ? input('p','','intval') : 1;
        $result = $this->curlGet($url,$data);
        $result = json_decode($result,true);//json转数组
        //print_r($result);die;
        $this->assign('data',$result['data']);

		$pageHtml = '';
		if($result['data']['pager']){
			//组装分页
			$prePage = $this->getPage($result['data']['pager']['page'],$result['data']['pager']['page_count']);
			$pageHtml = '  <div class="h-page"><a class="pn-first" href="'.url('user/bank_card_list',array('p'=>1)).'">首页</a>';
			if($prePage['page']>1){
			   $pageHtml .= '<a class="pn-prev" title="上一页" href="'.url('user/bank_card_list',array('p'=>$prePage['page']-1)).'">上一页</a>';
			 }
			for ($i = $prePage['start']; $i <= $prePage['end']; $i++) {
				if($i == $prePage['page']){
				$pageHtml .= '<a class="pn-num selected">'.$i.'</a>';
				}else{
					$pageHtml .= '<a class="pn-num" href="'.url('user/bank_card_list',array('p'=>$i)).'">'.$i.'</a>';
				}
			}

			if($prePage['page']<$prePage['end']){
			   $pageHtml .= '<a class="pn-next"  title="下一页" href="'.url('user/bank_card_list',array('p'=>$prePage['page']+1)).'">下一页</a>';
			 }
			$pageHtml .= '<a class="pn-last" href="'.url('user/bank_card_list',array('p'=>$prePage['pages'])).'">尾页</a><span class="page-num">共'.$prePage['pages'].'页</span></div>';
		}
		$this->assign('toPage',$pageHtml);

		$this->assign('class','mc my-card');
		$this->assign('left','我的资产');

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

		$this->assign('class','mc binding-card withdraw');
		$this->assign('left','我的资产');

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

    //登录
    public function login()
    {
		$this->site_title .= ' - 登录';
        $this->assign('back_url',isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '');

		return $this->fetch();
    }

	//三方登录
	public function third_login($channel)
    {
		$config = $this->login_config[$channel];
        $OAuth  = OAuth::getInstance($config, $channel);
        return redirect($OAuth->getAuthorizeURL());
    }

    //执行登录
    public function do_login()
    {
		if(!captcha_check(input('captcha'))){
			//$this->error('验证码错误','User/index');
		}
        $url = "passport/act_login";
        $data = array();
        $data['username'] = input('username');
        $data['password'] = input('password');
        //print_r($data);die();
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
            $this->success($result['message'],$back_url);
            // $this->success($result['message'],url('User/index'));
        }
        exit();
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
            $this->success($result['message'],$back_url);
            // header("Location:".$back_url);exit;
        }
        exit();
    }

    //退出登录
    public function logout()
    {
        Session::delete('user_id');//清除登录状态
        $this->success('退出登录成功！',url('/'));
    }

    //忘记密码
    public function forget()
    {
        return $this->fetch();
    }

    //忘记密码 - 第一步 验证用户名/邮箱/已验证手机号
    public function forget_one()
    {
        if(!captcha_check(input('captcha','','trim'))){
            $this->error('验证码错误');
        }

        $url = "passport/forget_one";
        $data = array();
        $data['username'] = input('username','','trim');
        $result = $this->curlPost($url,$data);
        $result = json_decode($result,true);
        if($result['code'] == 500){
            $this->error($result['message']);
        } else {
            $this->assign('validate_types', $result['data']);
            return $this->fetch();
        }
    }

    //忘记密码 - 第二步 验证身份
    public function forget_two()
    {
        $validate_type = input('validate_type','','trim');
        $code = input('code','','trim');
        if ($validate_type == 'mobile_phone') {
            $url = "passport/validate_phone";
            $data = array();
            $data['mobile_phone'] = input('mobile_phone','','trim');
            $data['mobile_code'] = $code;
            $result = $this->curlPost($url,$data);
            $result = json_decode($result,true);
            session('forget_mobile_phone', $data['mobile_phone']);
        } elseif ($validate_type == 'email') {
            $url = "passport/validate_email";
            $data = array();
            $data['email'] = input('email','','trim');
            $data['email_code'] = $code;
            $result = $this->curlPost($url,$data);
            $result = json_decode($result,true);
            session('forget_email', $data['email']);
        }
        if($result['code'] == 200){
            session('forget_verify_code', $result['data']['verify_code']);
            session('validate_type', $validate_type);
            return $this->fetch();
        }else{
            $this->error($result['message'], url('User/forget'));
        }
    }

    //忘记密码 - 第三步
    public function forget_third()
    {
        $url = "passport/reset_password2";
        $data = array();
        $data['validate_type'] = session('validate_type');
        $data['mobile_phone'] = session('forget_mobile_phone');
        $data['email'] = session('forget_email');
        $data['verify_code'] = session('forget_verify_code');
        $data['password'] = input('new_password','','trim');
        $data['confirm_password'] = input('confirm_password','','trim');
        $result = $this->curlPost($url,$data);
        $result = json_decode($result, true);
        if($result['code'] == 200){
            return $this->fetch();
        }else{
            $this->error($result['message'], url('User/forget'));
        }
    }

    //发送邮件验证码
    public function send_email_code()
    {
        $url = "user/sendEmailCode";
        $data = array();
        $data['user_id'] = $this->user_id;
        $data['email'] = input('email','','trim,strip_tags,htmlspecialchars');
        $result = $this->curlPost($url,$data);
        $result = json_decode($result);
        return $result;
    }

    //注册
    public function register()
    {
        return $this->fetch();
    }

    //执行注册
    public function do_register()
    {
        $url = "passport/act_register";
        $data = array();
        $data['username'] = input('username','','trim');
        $data['mobile_phone'] = input('mobile_phone','','trim');
        $data['mobile_code'] = input('mobile_code','','trim');
        $data['password'] = input('pwd','','trim');
        $result = $this->curlPost($url,$data);
        $result = json_decode($result,true);

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
            $this->success('注册成功',$back_url);
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

        $result = $this->curlGet($url,$data);
        //$result = json_decode($result);
        echo $result;
        //header("Location:".url('User/order_list',array('status'=>5)));exit();
	}

	//取消订单
	public function cancel_order(){
		$url = "user/cancelUserOrder";
        $data = array();
        $data['user_id'] = $this->user_id;
        $data['order_id'] = input('order_id',0);

        $result = $this->curlGet($url,$data);
        //$result = json_decode($result);
        //print_r($result);exit;
        echo $result;
	}

    //订单付款
    public function done(){
        $api = "Order/order_money";
        $data['user_id'] = $this->user_id;
        $data['order_id'] = input('order_id',0);
        $result = $this->curlPost($api,$data);
        //var_dump($result);exit;
        $result = json_decode($result,true);
        if(empty($result['data'])){
            $this->error('订单已超过30分钟未支付，已自动取消');
        }

        $this->assign('order_id',$result['data']['order_id']);
        $this->assign('order_amount',$result['data']['order_amount']);


        return $this->fetch('goods/done');
    }

/*	//查看物流
	public function kuaidi(){
		$url = "index/kuaidi";
        $data = array();
        //$data['typeCom'] = input('typeCom','','trim');
        //$data['typeNu'] = input('typeNu',0,'trim');
        $data['typeCom'] = '圆通速递';
        $data['typeNu'] = '800410081166733399';
		$result_json = $this->curlGet($url,$data);
		$result = json_decode($result_json,true);

        $this->assign('data',$result['data']);
        //print_r($result);die;
        //$html = '<li class="fix"><span>2017-06-05</span><span class="p_color">周一</span><span>15:22:49</span><span class="last">卖家发货</span></li>';
        $html = '<div class="wl_info"><span>'.$result['data']['com'].'</span><span>运单编号<font>'.$result['data']['nu'].'</font></span></div><div class="wl_con"><div class="p_wl p_wls">';
        foreach($result['data']['data'] as $key=>$value){
            $html .= '<ul class="fix"><li class="fix"><span>'.$value['time'].'</span><span class="last">'.$value['context'].'</span></li></ul>';
        }
        $html .='</div></div>';
        $is_ajax = input('is_ajax',0,'intval');
        $this->assign('is_ajax',$is_ajax);
        if($is_ajax){
            echo $html;exit;
        }
		return $this->fetch();
	}*/

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

	//我的足迹
	public function history(){
		$records = $this->getHistory();
		$records_list = array();

		if($records){
			$goods = implode(",",$records);
			$api = "goods/queryMore";
			$data = array();
			$data['goods'] = $goods;
			$data['num'] = 12;//显示数据数量
			$data['page'] = input('p','','intval') ? input('p','','intval') : 1;
			$records_list = $this->curlGet($api,$data);
			$records_list = json_decode($records_list,true);

		}

		$this->assign('class','mc collection');
		$this->assign('left','我的足迹');

		$pageHtml = '';
		if($records_list['data']['pager']){
			//组装分页
			$prePage = $this->getPage($records_list['data']['pager']['page'],$records_list['data']['pager']['page_count']);
			$pageHtml = '  <div class="h-page"><a class="pn-first" href="'.url('user/history',array('p'=>1)).'">首页</a>';
			if($prePage['page']>1){
			   $pageHtml .= '<a class="pn-prev" title="上一页" href="'.url('user/history',array('p'=>$prePage['page']-1)).'">上一页</a>';
			 }
			for ($i = $prePage['start']; $i <= $prePage['end']; $i++) {
				if($i == $prePage['page']){
				$pageHtml .= '<a class="pn-num selected">'.$i.'</a>';
				}else{
					$pageHtml .= '<a class="pn-num" href="'.url('user/history',array('p'=>$i)).'">'.$i.'</a>';
				}
			}

			if($prePage['page']<$prePage['end']){
			   $pageHtml .= '<a class="pn-next"  title="下一页" href="'.url('user/history',array('p'=>$prePage['page']+1)).'">下一页</a>';
			 }
			$pageHtml .= '<a class="pn-last" href="'.url('user/history',array('p'=>$prePage['pages'])).'">尾页</a><span class="page-num">共'.$prePage['pages'].'页</span></div>';
		}
		$this->assign('toPage',$pageHtml);

		$records_list = $records_list['data']['list'];
		$this->assign('records_list',$records_list);
		return $this->fetch();
	}

	//回调并绑定或者注册...Yip 20180125
	public function callback($channel) {
		$config = $this->login_config[$channel];
        $OAuth    = OAuth::getInstance($config, $channel);
        $OAuth->getAccessToken();
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

			//$this->error($result['message']);
        }
        exit();
    }





}
