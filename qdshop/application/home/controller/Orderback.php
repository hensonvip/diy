<?php
/**
 * Created by PhpStorm.
 * User: zxf0510
 * Date: 2018/7/16
 * Time: 13:42
 */

namespace app\home\controller;

use anerg\OAuth2\OAuth;
use think\Controller;
use think\Session;
use think\Request;

class Orderback extends Common
{

    //一个订单只能有一个申请中状态的退款订单。
    public function __construct()
    {
        parent::__construct();
        $this->assign('footer_on', 'user');//底部高亮

        $request = \think\Request::instance();
        $action = $request->action();

        //Session::delete('user_id');//清除登录状态

        //不需要登录的操作
        $no_login_action = array('login', 'third_login', 'callback', 'do_login', 'do_login_mobile', 'logout', 'register', 'do_register', 'forget', 'forget_one', 'forget_two', 'forget_third', 'getCode', 'jsonRegionC', 'jsonRegionD', 'send_email_code');
        //未登录处理

        //print_r(session('user_id'));die;
        if (empty($this->user_id)) {
            if (!in_array(strtolower($action), array_map('strtolower', $no_login_action))) {
                header("Location:" . url('User/login'));
                exit;
            }
        } else {
            if (in_array(strtolower($action), array_map('strtolower', $no_login_action)) && strtolower($action) != 'logout' && strtolower($action) != 'jsonregionc' && strtolower($action) != 'jsonregiond' && strtolower($action) != 'getcode') {
                header("Location:" . url('User/index'));
                exit;
            }
        }

        $this->assign('left', '用户信息');

        //第三方登录配置信息
        $this->login_config = array(
            'qq' => array(
                //qq
                'type' => 'QQ',
                'app_key' => '101454505',
                'app_secret' => '26c432ac19d297cb120f00ae6c1b14c7',
                'scope' => 'get_user_info',
                'callback' => array(
                    'default' => "http://" . $_SERVER['HTTP_HOST'] . "/User/callback/channel/qq"
                )
            ),
            'wx_qrcode' => array(
                'type' => 'Wechat',
                'app_key' => 'wxa1470c5bc33aa48b',
                'app_secret' => '496ba108e8886b223b7814c91c3b34b1',
                'scope' => 'snsapi_login',
                'response_type' => 'code',
                'grant_type' => 'authorization_code',
                'callback' => array(
                    'default' => 'http://' . $_SERVER ["HTTP_HOST"] . '/User/callback/channel/wx_qrcode'
                )
            )
        );
    }

    public function index()
    {
        echo '<a target="_blank" href="'.url('orderback/goods_apply').'">goods_apply</a>';
        echo '<a target="_blank" href="'.url('orderback/apply_refund',array('order_id'=>614,'goods_id'=>943,'rec_id'=>622)).'">apply_refund</a>';
        echo '<a target="_blank" href="'.url('orderback/apply_refund',array('order_id'=>620)).'">apply_refund_all</a>';
        echo '<a  href="'.url('orderback/refund_details',array('back_id'=>56)).'">refund_details</a>';
    }

    public function apply_refund()
    {
        $url = "orderback/ToCreateUserBackOrder";
        $data = array();
        $data['user_id'] = $this->user_id;
        $data['order_id'] = input('order_id', 0, 'intval');
        $data['goods_id'] = input('goods_id', 0, 'intval');
        $data['product_id'] = input('product_id', 0, 'intval');
        $data['rec_id'] = input('rec_id', 0, 'intval');
        $result = $this->curlGet($url, $data);
        $result = json_decode($result, true);//json转数组

        //echo "<pre>";print_r($result);die();
        if ($result['code'] == 500) {
            $this->success($result['message'], url('order/'));
            exit;
        }

        $this->assign('data', $result['data']);
        $this->assign('order_id', $data['order_id']);
        $this->assign('goods_id', $data['goods_id']);
        $this->assign('product_id', $data['product_id']);
        $this->assign('rec_id', $data['rec_id']);
        $this->assign('class', 'mc evaluate-details');
        $this->assign('left', '我的订单');
        //return $this->fetch();
        return $this->fetch('apply_refund');
    }

    //提交退款
    public function do_refund()
    {
        $url = "orderback/apply_back_order";
        $data = array();
        $data = input();
        $data['user_id'] = $this->user_id;
        //echo "<pre>";print_r($data);die();
        // 如果有图片上传，保存
        $files_up = array();
        $file_path = $data['file_path'] =  "runtime/temp/";
        $files_img = request()->file("upload_img");
        if(!empty($files_img)){
            foreach ($files_img as $key => $img) {
                if(!empty($img)){
                    // 保存图片
                    $img_info = $img->rule('uniqid')->move($file_path);
                    //echo "<pre>";print_r($img_info);
                    if($img_info){
                        // 保存图片名称 相当于$_FILE['name'] 唯一
                        $files_up[$key]['name'] = 'back_imgs';
                        // 保存图片路径
                        $files_up[$key]['path'] = $file_path.$img_info->getFilename();
                        $files_up[$key]['type']=$img_info->getInfo()['type'];
                        $files_up[$key]['filename']=$img_info->getFilename();
                    }else{// 删除所有图片
                        for ($i=0; $i < COUNT($files_up); $i++) {
                            @unlink($file_path.$files_up[$i]["name"]);
                        }
                        $return = array(
                            "code" => 500,
                            "message" => $img_info->getError()
                        );
                        echo json_encode($return);
                        exit;
                        // $this->error("上传照片错误！");
                    }
                }
            }
            $data['files']=$files_up;
        }
        $files_up=array();
        //echo "<pre>";print_r($data);die();
        $result = $this->curlPost($url, $data, $files_up);
        $result = json_decode($result, true);
        //echo "<pre>";print_r($result);die();
        if ($result['code'] != 200) {
            $this->error($result['message']);
            exit();
        }
        $this->success('提交成功！', url('orderback/refund_details', array('back_id' => $result['data']['back_id'])));
        exit;
        //echo $result;die();
    }

    //退款/售后 列表
    public function refund_list()
    {
        $url = "orderback/refundList";
        $data = array();
        $data['user_id'] = $this->user_id;
        $data['page_size'] = 8;//显示数据数量
        $data['page'] = input('page', 1, 'intval');
        $data['status'] = input('status', 0, 'intval');//0（全部）、1（审核中）、2（已完成）
        $data['type'] = input('type', '', 'intval');//''（全部）、0（虚拟商品）、1（真实商品）
        $data['order_id'] = input('order_id', '');//订单编号;
        $data['date'] = input('date', '', 'intval');//时间范围
        $result = $this->curlGet($url, $data);
        $result = json_decode($result, true);//json转数组

        $this->assign('data', $result['data']);
        $this->assign('status', $data['status']);
        $this->assign('type', $data['type']);
        $this->assign('date', $data['date']);
        $this->assign('order_id', $data['order_id']);

        //组装分页
        if (isset($result['data']['pager']['page'])) {
            $prePage = $this->getPage($result['data']['pager']['page'], $result['data']['pager']['page_count']);
            $this->assign('prePage', $prePage);
        } else {
            $this->assign('prePage', array());
        }

        $this->assign('class', 'mc return-goods');
        $this->assign('left', '退款退货');

        return $this->fetch();
    }

    //退款/售后 详情
    public function refund_details()
    {
        $url = "orderback/refundDetails";
        $data = array();
        $data['user_id'] = $this->user_id;
        $data['back_id'] = input('back_id', '', 'intval');
        $result = $this->curlGet($url, $data);
        $result = json_decode($result, true);//json转数组
        //echo "<pre>";print_r($result);die;
        if ($result['code'] == 500) {
            $this->success($result['message'], url('User/refund_list'));
            exit;
        }
        $this->assign('data', $result['data']);
        $this->assign('back_id', $data['back_id']);

        $status_back=$result['data']['status_back'];
        $data=$result['data'];
        if ( in_array($status_back,array(0,1,2))){
            //用户寄回换货/退货商品快递信息
            if (!empty($data['shipping_name'])&&!empty($data['invoice_no'])){
                $url = "index/kuaidi";
                $kuaidi_data = array(
                    'typeCom'=>$data['shipping_name'],
                    'typeNu'=>$data['invoice_no'],
                );
                $result = $this->curlGet($url, $kuaidi_data);
                $result = json_decode($result, true);//json转数组
                //echo "<pre>";print_r(print_shipping($result['data']));die();
                $result['data']=print_shipping($result['data']);
                $this->assign('kuaidi', $result['data']);
            }
            //平台换回商品快递信息
            if ($status_back==2){
                if (!empty($data['back_shipping_name'])&&!empty($data['back_invoice_no'])){
                    $url = "index/kuaidi";
                    $back_kuaidi_data = array(
                        'typeCom'=>$data['back_shipping_name'],
                        'typeNu'=>$data['back_invoice_no'],
                    );
                    $result = $this->curlGet($url, $back_kuaidi_data);
                    $result = json_decode($result, true);//json转数组
                    //echo "<pre>";print_r($result);die;
                    $result['data']=print_shipping($result['data']);
                    $this->assign('back_kuaidi', $result['data']);
                }
            }
        }
        $url = "orderback/getShippingList";
        $data = array();
        $result = $this->curlGet($url, $data);
        $result = json_decode($result, true);//json转数组
        $this->assign('shipping', $result['data']);

        $url = "orderback/getBackConfig";
        $data = array();
        $result = $this->curlGet($url, $data);
        $result = json_decode($result, true);//json转数组
        $this->assign('back_config', $result['data']);
        //echo "<pre>";print_r($result);die;
        return $this->fetch('orderback/apply_details');
    }

    //取消退款申请
    public function do_cancel_refund()
    {
        $url = "orderback/cancelUserRefund";
        $data = array();
        $data['user_id'] = $this->user_id;
        $data['back_id'] = input('back_id', '', 'intval');
        //echo "<pre>";print_r($data);die();
        $result = $this->curlGet($url, $data);
        echo $result;
        exit();
    }

    //退货申请寄回商品物流信息提交
    public function do_refund_logistics(){
        $url = "orderback/UserRefundLogistics";
        $data = array();
        $data['user_id'] = $this->user_id;
        $data['back_id'] = input('back_id', '0', 'intval');
        $data['shipping_name'] = input('shipping_name');
        $data['invoice_no'] = input('invoice_no');
        $result = $this->curlGet($url, $data);
        echo $result;
        exit();
    }
}

function print_shipping($kuaidi){
    $weekstr=array('周日','周一','周二','周三','周四','周五','周六');
    $newdata=array();
    foreach ($kuaidi['data'] as $k=>$v){
        $time=strtotime($v['ftime']);
        $date=date('Y-m-d',$time);//日期
        $week=date('w',$time);//星期
        $time=date('H:i:s',$time);//时间
        //$newdata[$date][]=array('week'=>$week,'data'=>array('week'=>$week,'time'=>$time,'context'=>$v['context']));
        $newdata[$date][]=array('date'=>$date,'week'=>$weekstr[$week],'time'=>$time,'context'=>$v['context']);
    }
    $kuaidi['data']=$newdata;
    unset($newdata);
    return $kuaidi;
}