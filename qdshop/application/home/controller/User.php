<?php
namespace app\home\controller;
use anerg\OAuth2\OAuth;
use think\Controller;
use think\Session;
use think\Request;

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
        $no_login_action = array('login','third_login','callback','do_login','do_login_mobile','logout','register','do_register','forget','forget_one','forget_two','forget_third','getCode','jsonRegionC','jsonRegionD','send_email_code','designer_page','send_letter_ajax', 'my_ranking', 'ranking_list');
        //未登录处理

        //print_r(session('user_id'));die;
        if(empty($this->user_id)){
            if(!in_array(strtolower($action),array_map('strtolower',$no_login_action))){
                header("Location:".url('User/login'));exit;
            }
        }else{
			if(in_array(strtolower($action),array_map('strtolower',$no_login_action)) && strtolower($action)!='logout' && strtolower($action)!='jsonregionc' && strtolower($action)!='jsonregiond' && strtolower($action)!='getcode' && strtolower($action)!='designer_page' && strtolower($action)!='send_letter_ajax' && strtolower($action)!='my_ranking' && strtolower($action)!='ranking_list'){
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


    //会员中心主页
    public function index()
    {
		/*//我的订单
		$url = "user/getUserOrder";
        $data = array();
        $data['user_id'] = $this->user_id;
        $data['page_size'] = 10;//显示数据数量
        $data['page'] = 1;
        $data['status'] = 0;//0（所有订单）、2（待付款）、3（待发货）、4（待收货）、5（已完成）
        $result = $this->curlGet($url,$data);
        $result = json_decode($result,true);//json转数组
		$order = array();
		$volist_count = 0;
		foreach($result['data']['order_list'] as $v){
			$volist_count += count($v['goods_list']);
			if($volist_count>6){
				break;
			}
			foreach($v['goods_list'] as $vo){
				$order[] = $vo;
			}
		}
		//print_r($result);
		$this->assign('order',$order);*/

        //我的订单
        $api = "User/myOrder";
        $myorder['user_id'] = $this->user_id;
        $result = $this->curlGet($api,$myorder);
        $result = json_decode($result,true);
        //print_r($result);exit;

        $this->assign('order_data',$result['data']);

		//我的收藏
		$url = "user/getUserCollect";
        $data = array();
        $data['user_id'] = $this->user_id;
        $data['page_size'] = 16;//显示数据数量
        $data['page'] = 1;
        $result = $this->curlGet($url,$data);
        $result = json_decode($result,true);//json转数组
        //print_r($result);
		$this->assign('collect',$result['data']);

		//浏览历史
		//猜你喜欢(获取关联商品)
		$records = $this->getHistory();
		$records_list = array();

		if($records){
			$goods = implode(",",$records);
			$api = "goods/queryMore";
			$data = array();
			$data['goods'] = $goods;
			$records_list = $this->curlGet($api,$data);
			$records_list = json_decode($records_list,true);
			$records_list = $records_list['data']['list'];
		}
		//print_r($records_list);

		$this->assign('records_list',$records_list);

		$this->assign('class','mc mc-index');
		$this->assign('left','用户信息');


        //个人中心首页展示数据4条
        $url = "user/getFindsList";
        $data = array();
        $data['user_id'] = $this->user_id;
        $data['status'] = input('page','0','intval');//0（所有作品）、1（待审核）、2（未通过）
        $data['page_size'] = 4;//显示数据数量
        $data['page'] = input('page','','intval') ? input('page','','intval') : 1;
        $result = $this->curlGet($url,$data);
        $result = json_decode($result,true);//json转数组
        //print_r($result);exit;
        $production_list=array();
        foreach($result['data']['list'] as $key=>$value){
            $production_list[$key]=$value;
            //print_r($production_list);exit;
            $arr['user_id']=$value['user_id'];
            $arr['find_id']=$value['find_id'];
            $url = "Finds/findUserData";
            $user_data_json = $this->curlGet($url,$arr);
            //print_r($user_data_json);exit;
            $user_data=json_decode($user_data_json,true);//json转数组
            $production_list[$key]['user_data']=$user_data['data'];
        }
        $this->assign('production_list',$production_list);

        $api = "User/delFinds";
        $ajax_data = array();
        $ajax_data['user_id'] = $this->user_id;
        $ajax_data['find_id'] = input('find','0','intval');
        $del_find_data = $this->curlGet($api,$ajax_data);
        //$del_find_data = json_decode($del_find_data,true);
        if(!empty($ajax_data['find_id'])){
            echo $del_find_data;exit;
        }

        //个人页面作品收藏4条/收藏页作品收藏列表
        $url = "User/productCollectList";
        $data = array();
        $data['user_id'] = $this->user_id;
        $data['start'] = input('page','0','intval');
        $data['num'] = 4;//显示数据数量
        //$data['page'] = input('page','','intval') ? input('page','','intval') : 1;
        $collect_data = $this->curlGet($url,$data);
        $collect_data = json_decode($collect_data,true);//json转数组
        //$this->assign('collect_data',$collect_data['data']['list']);
        $this->assign('collect_page',$collect_data['data']['pager']);
        $produc_collect_list = array();
        if (!empty($collect_data['data']['list'])) {
            foreach($collect_data['data']['list'] as $key=>$value){
                $produc_collect_list[$key]=$value;
                //print_r($production_list);exit;
                $arr['user_id']=$value['user_id'];
                $arr['find_id']=$value['find_id'];
                $url = "Finds/findUserData";
                $collect_data_json = $this->curlGet($url,$arr);
                $collect_data=json_decode($collect_data_json,true);//json转数组
                $produc_collect_list[$key]['user_data']=$collect_data['data'];
            }
        }
        //print_r($produc_collect_list);
        $this->assign('produc_collect_list',$produc_collect_list);
        //print_r($collect_data);

        //取消作品收藏:异步
        $url = "User/cancelCollect";
        $data = array();
        $data['user_id'] = $this->user_id;
        $data['find_id'] = input('cancel_find','');
        $cancel_collect = $this->curlGet($url,$data);
        if($data['find_id']){
            echo $cancel_collect;exit;
        }

        //$cancel_collect = json_decode($cancel_collect,true);//json转数组
        //$this->assign('cancel_collect',$cancel_collect);
        //创意擂台 时间url定向
        $api = "originality/matchRule";
        $result = $this->curlGet($api);
        $result = json_decode($result,true);
        $this->assign('match_cycle',$result['data']['code']);



    	return $this->fetch();
    }

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

    //我的订单 - 列表
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

		//print_r($result['data']);

		$this->assign('left','我的订单');

        $this->assign('order',$result['data']);
        return $this->fetch();
    }

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
        $data['district'] = input('district','0','intval');//区
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

		/*$pageHtml = '';
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
		$this->assign('toPage',$pageHtml);*/

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


    /************************************/
    // group:  我的OTEE
    // menu:   我的收藏 - T恤收藏
    /************************************/
    public function collect_goods()
    {
        // $api = "category/getSubCat";
        // $data = ['cat_id' => 85];
        // $result = $this->curlGet($api, $data);
        // $result = json_decode($result, true);
        // $this->assign('category_list', $result['data']);

        $type = input('type', 1, 'intval');
        $url = "user/getUserCollect";
        $data = array();
        $data['user_id'] = $this->user_id;
        $data['cat_id'] = input('cat_id', 0, 'intval');
        $data['type'] = $type;
        $data['page_size'] = 12;//显示数据数量
        $data['page'] = input('p', '', 'intval') ? input('p', '', 'intval') : 1;
        $result = $this->curlGet($url, $data);
        $result = json_decode($result, true);//json转数组
        $this->assign('data', $result['data']);

        $this->assign('type', $type);
        $this->assign('left', '我的收藏');

        return $this->fetch();
    }

    /************************************/
    // group:  我的OTEE
    // menu:   我的收藏 - 取消收藏
    /************************************/
    public function unfavor()
    {
        $url = "user/dropUserCollect";
        $data = array();
        $data['user_id'] = $this->user_id;
        $data['id'] = input('id');
        $data['type'] = input('type');
        $result = $this->curlGet($url,$data);
        $result = json_decode($result);
        return $result;
    }

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
        $api = 'user/getInteract';
        $data = array();
        $data['type'] = input('type', 0, 'intval');  // 0 所有  1 关注  2 点赞  3 评论
        $result = $this->curlPost($api, $data);
print_r('<pre>');
var_dump($result);
exit();
        $result = json_decode($result,true);

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
        //手机验证 
        $url = "passport/validate_phone";
        $data = array();
        $data['mobile_phone'] = input('mobile_phone','','trim');
        $data['mobile_code'] = input('mobile_code','','trim');
        $result = $this->curlPost($url,$data);
        $result = json_decode($result, true);
        if($result['code'] == 500){
            echo json_encode($result);die;
        }

        //第三方银行卡验证接口 
        $url = "user/checkBankCard";
        $data = array();
        $data['card_number'] = input('card_number','','trim');
        $data['real_name'] = input('real_name','','trim');
        $data['card'] = input('card','','trim');
        $result = $this->curlPost($url,$data);
        $result = json_decode($result, true);
        if($result['code'] == 500){
            echo json_encode($result);die;
        }

        // 添加数据
        $url = "user/addBankCard";
        $data = array();
        $data['user_id'] = $this->user_id;
        $data['real_name'] = input('real_name','','trim');
        $data['card'] = input('card','','trim');//身份证号码
        $data['bank_id'] = input('bank_id','','trim');
        $data['bank_logo'] = input('bank_logo','','trim');
        $data['card_number'] = input('card_number','','trim');
        $data['card_name'] = input('bank_name','','trim');
        $data['card_type'] = input('card_type','','trim');
        $data['card_info'] = input('bank_name','','trim') . '-' . input('card_name','','trim') . '-' . input('card_type','','trim');
        $result = $this->curlGet($url,$data);
        $result = json_decode($result);
        return $result;
    }

    //我的银行卡 - 修改银行卡
    public function do_bank_card_edit(){
        //第三方银行卡验证接口 
        $url = "user/checkBankCard";
        $data = array();
        $data['card_number'] = input('card_number','','trim');
        $data['real_name'] = input('real_name','','trim');
        $data['card'] = input('card','','trim');
        $result = $this->curlPost($url,$data);
        $result = json_decode($result, true);
        if($result['code'] == 500){
            echo json_encode($result);die;
        }

        // 添加数据
        $url = "user/editBankCard";
        $data = array();
        $data['user_id'] = $this->user_id;
        $data['edit_id'] = input('edit_id','','intval');//修改的银行卡ID
        $data['real_name'] = input('real_name','','trim');
        $data['card'] = input('card','','trim');//身份证号码
        $data['bank_id'] = input('bank_id','','trim');
        $data['bank_logo'] = input('bank_logo','','trim');
        $data['card_number'] = input('card_number','','trim');
        $data['card_name'] = input('bank_name','','trim');
        $data['card_type'] = input('card_type','','trim');
        $data['card_info'] = input('bank_name','','trim') . '-' . input('card_name','','trim') . '-' . input('card_type','','trim');
        $result = $this->curlGet($url,$data);
        $result = json_decode($result);
        return $result;
    }

    // 手机验证
    public function validate_phone() {
        $url = "passport/validate_phone";
        $data = array();
        $data['mobile_phone'] = input('mobile_phone','','trim');
        $data['mobile_code'] = input('mobile_code','','trim');
        $result = $this->curlPost($url,$data);
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


    // 我的作品
    public function finds()
    {
        $url = "user/getFindsList";
        $data = array();
        $data['user_id'] = $this->user_id;
        $data['status'] = input('status','0','intval');//0（所有作品）、1（待审核）、2（未通过）
        $data['page_size'] = 11;//显示数据数量
        $data['page'] = input('page','','intval') ? input('page','','intval') : 1;

        $result = $this->curlGet($url,$data);
        $result = json_decode($result,true);//json转数组


        $this->assign('data',$result['data']);

        //异步加载分页数据
        $is_ajax = input('is_ajax',0,'intval');
        $this->assign('is_ajax',$is_ajax);
        if($is_ajax){
            echo $this->fetch('user/finds_list_ajax');exit();
        }
        return $this->fetch();
    }

    // 添加我作品页面
    public function add_finds()
    {
        $url = "user/addFinds";
        $data = array();
        $data['user_id'] = $this->user_id;
        $result = $this->curlGet($url,$data);
        $result = json_decode($result,true);
        // $this->assign('data',$result['data']['list']);
        // $this->assign('data',$result['data']['common']);
        $this->assign('data',$result['data']);
        $this->assign('today',date("Y-m-d",time()));

        //常用领域列表
        /*$url = "Finds/getCommonFields";
        $common_data = array();
        $common_field = $this->curlGet($url,$common_data);
        $common_field = json_decode($common_field,true);//json转数组
        $this->assign('common_field_list',$common_field['data']);

        //所有领域列表
        $url = "Finds/getFields";
        $all_data = array();
        $all_field = $this->curlGet($url,$all_data);
        $all_field = json_decode($all_field,true);//json转数组
        $this->assign('all_field_list',$all_field['data']);*/


        return $this->fetch();
    }

    // 作品添加
    public function ajx_finds()
    {
        $url = "user/ajxAddFinds";
        $data = array();
        $data['user_id'] = $this->user_id;
        $data['add_data'] = $_POST;

        $find_id = $this->curlPost($url,$data);
        print_r($find_id);die;
        $find_id = json_decode($find_id,true);//json转数组


    }


    /************************************/
    // group:  我的OTEE
    // menu:   待审核
    /************************************/
    public function my_goods_list()
    {
        $api = 'user/getGoodsList';
        $data = [];
        $data['user_id'] = $this->user_id;
        $data['status'] = input('status', 2, 'intval');
        $data['sell_out'] = input('sell_out', 0, 'intval');
        $data['page_size'] = 15;
        $data['page'] = input('page', 1, 'intval');
        $result = $this->curlGet($api, $data);
        $result = json_decode($result, true);//json转数组
        $this->assign('goods_list', $result['data']['list']);

        $this->assign('status', $data['status']);
        $this->assign('sell_out', $data['sell_out']);
        return $this->fetch();
    }

    /************************************/
    // group:  我的OTEE
    // menu:   我的设计库
    /************************************/
    public function design_library()
    {
        $is_ajax = input('is_ajax', 0, 'intval');
        $api = 'user/getDiyList';
        $data = [];
        $data['user_id'] = $this->user_id;
        $data['status'] = input('status', -1, 'intval');
        $data['type'] = input('type', 0, 'intval');
        $data['page'] = input('page', 1, 'intval');
        if($data['page'] == 1){
            $data['page_size'] = 14;
        }else{
            $data['page_size'] = 15;
        }
        $result = $this->curlGet($api, $data);
        $result = json_decode($result, true);//json转数组
        $this->assign('diy_list', $result['data']);
        
        if($is_ajax){
            $html = $this->fetch('design_library_ajax');
            return array('html' => $html);
        }else{
            $this->assign('type', $data['type']);
            $this->assign('status', $data['status']);
            return $this->fetch();
        }
    }

    //我的佣金
    public function commission()
    {
        $url = "user/getBankCardList";
        $data = array();
        $data['user_id'] = $this->user_id;
        $data['page_size'] = 1;//显示数据数量
        $data['page'] = input('p','','intval') ? input('p','','intval') : 1;
        $bank_card_list = $this->curlGet($url,$data);
        $bank_card_list = json_decode($bank_card_list,true);//json转数组
        
        $url = "user/getUserInfo";
        $data = array();
        $data['user_id'] = $this->user_id;
        $user_info = $this->curlGet($url,$data);
        $user_info = json_decode($user_info,true);//json转数组
        $this->assign('user_info',$user_info['data']);

        // 提现记录
        $url = "user/getApplyDepositList";
        $data = array();
        $data['user_id'] = $this->user_id;
        $data['page_size'] = 10;//显示数据数量
        $data['page'] = input('page','','intval') ? input('page','','intval') : 1;
        $tx_log = $this->curlGet($url,$data);
        $tx_log = json_decode($tx_log,true);//json转数组
        // print_r($tx_log);die;
        $this->assign('tx_log',$tx_log['data']);
        //异步加载分页数据
        $is_ajax = input('is_ajax',0,'intval');
        $this->assign('is_ajax',$is_ajax);
        if($is_ajax){
            echo $this->fetch('user/tx_log_ajax');exit();
        }

        // 显示页面
        if ($bank_card_list['data']['list']) {
            $this->assign('bank_card_list',$bank_card_list['data']);
            return $this->fetch('user/commission1');
        } else {
            return $this->fetch('user/commission');
        }
    }

    //检查银行卡是否合法
    public function get_bank_card()
    {
        $url = "user/getBankCard";
        $data = array();
        $data['user_id'] = $this->user_id;
        $data['card_number'] = input('card_number','','trim,strip_tags,htmlspecialchars');
        $result = $this->curlPost($url,$data);
        $result = json_decode($result);
        return $result;
    }


    /************************************/
    // group:  我的OTEE
    // menu:   获取商品属性列表
    /************************************/
    public function get_goods_attr()
    {
        $api = "goods/getGoodsInfo";
        $data = array();
        $data['user_id'] = $this->user_id;
        $data['goods_id'] = input('goods_id', 0, 'intval');//商品ID，必填
        $result = $this->curlGet($api,$data);
        $result = json_decode($result,true);
        $this->assign('goods_data', $result['data']);

        return $this->fetch('user/attr_box_ajax');
    }

    //设计师主页
    public function designer_page()
    {
        // 指定设计师已出售的商品
        $api = "goods/query";
        $data = array();
        $data['user_id'] = $this->user_id;
        $data['cat_id'] = input('cat_id',0,'intval');
        $data['brand'] = input('brand',0,'intval');
        $data['size'] = 16;
        $data['page'] = input('page',1,'intval');
        $data['shop_price'] = input('shop_price',0,'intval');//价格
        $data['sex'] = input('sex','','trim');//款式（男，女）
        $data['order'] = input('order','desc','trim');//按字段排序
        $data['sort'] = input('sort','sort_order','trim');//按字段排序
        $data['filter'] = input('filter','0','trim');//类型
        $data['keywords'] = input('keywords','','trim');//关键字搜索
        $data['is_real'] = input('is_real',3,'intval');//0虚拟商品 1真实商品
        $data['designer_id'] = $this->designer_id;
        if(!empty($data['keywords'])){
            cookie('keywords', cookie('keywords').','.$data['keywords'], 3600*24*7);
        }
        $result = $this->curlGet($api,$data);
        $result = json_decode($result,true);
        // print_r($result['data']);die;
        $this->assign('data',$result['data']);
        $this->assign('cat_id',$data['cat_id']);//分类
        $this->assign('brand',$data['brand']);//品牌
        $this->assign('filter',$data['filter']);
        $this->assign('keywords',$data['keywords']);//关键字
        $this->assign('order',$data['order']);
        $this->assign('order_opposite',$data['order'] == 'desc' ? 'asc' : 'desc');
        $this->assign('sort',$data['sort']);
        $this->assign('sex',$data['sex']);
        $this->assign('shop_price',$data['shop_price']);
        $this->assign('is_real',$data['is_real']);
        $this->assign('designer_id',$data['designer_id']);
        //异步加载分页数据
        $is_ajax = input('is_ajax','','intval') ? input('is_ajax','','intval') : 0;
        $this->assign('is_ajax',$is_ajax);
        if($is_ajax){
            echo $this->fetch('goods/designer_goods_list_ajax');exit();
        }

        // 获取指定用户商品分类
        $api = "category/getUserCat";
        $data['designer_id'] = $this->designer_id;
        $result = $this->curlGet($api, $data);
        $result = json_decode($result, true);
        $this->assign('category_list', $result['data']);

        //用户商品价格列表
        $api = "user/getUserPrice";
        $data['designer_id'] = $this->designer_id;
        $result = $this->curlGet($api, $data);
        $result = json_decode($result,true);
        // print_r($result);die;
        $this->assign('price_list',$result['data']);

        // 私信举报原因
        $url = "user/getReportReason";
        $data = array();
        $result = $this->curlGet($url);
        $result = json_decode($result,true);//json转数组
        $this->assign('reason_list', $result['data']);

        return $this->fetch();
    }

    /************************************/
    // group:  我的OTEE
    // menu:   出售我的OTEE
    /************************************/
    public function sell_my_otee()
    {
        $api = 'user/getDiyList';
        $data = [];
        $data['user_id'] = $this->user_id;
        $data['status'] = 5;
        $data['page'] = input('page', 1, 'intval');
        $data['page_size'] = input('page_size', 15, 'intval');
        $result = $this->curlGet($api, $data);
        $result = json_decode($result, true);//json转数组
        $this->assign('diy_list', $result['data']);

        return $this->fetch();   
    }

    //排行榜
    public function ranking_list()
    {
        $api = "user/rankingList";
        $data = array();
        $data['page_size'] = 5;
        $data['page'] = input('page',1,'intval');
        $data['user_id'] = $this->user_id;
        $result = $this->curlGet($api,$data);
        $result = json_decode($result,true);
        // print_r($result);die;
        $this->assign('data',$result['data']);
        //异步加载分页数据
        $is_ajax = input('is_ajax','','intval') ? input('is_ajax','','intval') : 0;
        $this->assign('is_ajax',$is_ajax);
        if($is_ajax){
            echo $this->fetch('user/ranking_ajax');exit();
        }

        // 私信举报原因
        $url = "user/getReportReason";
        $data = array();
        $result = $this->curlGet($url);
        $result = json_decode($result,true);//json转数组
        $this->assign('reason_list', $result['data']);
        
        return $this->fetch();
    }
}