<?php
namespace app\mobile\controller;
use think\Controller;

class Supplier extends Common
{

	public function __construct()
    {
        parent::__construct();
        $this->user_id = session('user_id') ? session('user_id') : 0;

        //店铺信息
        $data = array();
        $data['supplier_id'] = input('supplier_id',0,'intval');
        $api = "supplier/getSupplierDetails";
        $result = $this->curlGet($api,$data);
        $this->result_supplier = json_decode($result,true);
        //print_r($this->result_supplier['data']);die;
        if(!$this->result_supplier['data']){
            $this->error('店铺不存在！');exit();
        }
        $this->assign('supplier_data',$this->result_supplier['data']);
        $this->assign('supplier_id',$this->result_supplier['data']['supplier_id']);

    }

    //店铺列表
    public function index()
    {
        $data = array();
        $data['page_size'] = 5;
        $data['page'] = input('page',1,'intval');
        $data['supplier_type'] = input('type',0,'intval');
        $api = "supplier/getSupplierList";
        $result = $this->curlGet($api,$data);
        $result = json_decode($result,true);
        //print_r($result['data']);die;
        $this->assign('data',$result['data']);
        $this->assign('type',$data['supplier_type']);

        //异步加载分页数据
        $is_ajax = input('is_ajax',0,'intval');
        $this->assign('is_ajax',$is_ajax);
        if($is_ajax){
            echo $this->fetch('supplier/supplier_list_ajax');exit();
        }

    	return $this->fetch();
    }

    //店铺主页
    public function supplier_details(){
        $this->assign('goods_list',$this->result_supplier['data']['goods_list']);

        $this->assign('head_on','details');//头部高亮
        return $this->fetch();
    }

    //店铺商品列表
    public function supplier_goods(){
        $data = array();
        $data['page_size'] = 10;
        $data['page'] = input('page',1,'intval');
        $data['supplier_id'] = input('supplier_id',0,'intval');
        $api = "supplier/getSupplierGoods";
        $result = $this->curlGet($api,$data);
        $result = json_decode($result,true);
        //print_r($result['data']);die;
        $result['data']['list'] = isset($result['data']['list']) ? $result['data']['list'] : array();
        $this->assign('goods_list',$result['data']['list']);

        //异步加载分页数据
        $is_ajax = input('is_ajax','','intval') ? input('is_ajax','','intval') : 0;
        $this->assign('is_ajax',$is_ajax);
        if($is_ajax){
            echo $this->fetch('supplier/supplier_goods_ajax');exit();
        }

        $this->assign('head_on','goods');//头部高亮
        return $this->fetch();
    }

    //店铺详情信息
    public function supplier_info(){
        
        $this->assign('head_on','info');//头部高亮
        return $this->fetch();
    }

    //关注店铺
    public function guanzhu(){
        if(!$this->user_id){
            $data['status'] = 500;
            $data['message'] = '请先登录！再关注。';
            echo json_encode($data);exit();
        }
        $url = "supplier/getGuanzhu";
        $data = array();
        $data['user_id'] = $this->user_id;
        $data['supplier_id'] = input('supplier_id','','intval');
        $result = $this->curlGet($url,$data);
        $result = json_decode($result,true);
        if($result['code'] == 200){
            $rows['status'] = 200;
            $rows['message'] = $result['data']['message'];
        }else{
            $rows['status'] = 500;
            $rows['message'] = '操作失败，链接服务器错误！';
        }
        echo json_encode($rows);exit();
    }

    //申请入驻 - 个人
    public function apply_enter()
    {   
        //获取申请入驻信息
        $url = "supplier/apply_enter_info";
        $data = array();
        $data['user_id'] = $this->user_id;
        $result = $this->curlGet($url,$data);
        $result = json_decode($result,true);//print_r($result);die;
        $this->assign('info',$result['data']);

        if(!empty($result['data']['business_licence_number'])){
            header("Location:".url('Supplier/apply_enter_company'));exit;
        }

        //获取店铺分类
        $url = "supplier/getSupplierStreet";
        $data = array();
        $data['user_id'] = $this->user_id;
        $result = $this->curlGet($url,$data);
        $result = json_decode($result,true);
        $this->assign('type_id_arr',$result['data']['list']);
        
        $regionP = $this->getRegionP();
        $this->assign('regionP',$regionP);//省份
        return $this->fetch();
    }

    //申请入驻 - 公司
    public function apply_enter_company()
    {   
        //获取申请入驻信息
        $url = "supplier/apply_enter_info";
        $data = array();
        $data['user_id'] = $this->user_id;
        $result = $this->curlGet($url,$data);
        $result = json_decode($result,true);//print_r($result);die;
        $this->assign('info',$result['data']);

        //获取店铺分类
        $url = "supplier/getSupplierStreet";
        $data = array();
        $data['user_id'] = $this->user_id;
        $result = $this->curlGet($url,$data);
        $result = json_decode($result,true);
        $this->assign('type_id_arr',$result['data']['list']);
        
        $regionP = $this->getRegionP();
        $this->assign('regionP',$regionP);//省份
        return $this->fetch();
    }

    //申请入驻 - 个人 -提交
    public function do_apply_enter()
    {   
        $url = "Supplier/apply_enter";
        $data = array();
        $data = input();
        $data['user_id'] = $this->user_id;
        //print_r($data);die;
        $files_up = array();
        $file_path = $data['file_path'] =  "runtime/temp/";
        //$save_file_path = ROOT_PATH."runtime/temp/";

        foreach ($_FILES as $k => $v) {
            $files_img = request()->file($k);
            if(!empty($files_img)){
                $img_info = $files_img->rule('uniqid')->move($file_path);
                $files_up[$k]['name'] = $k;
                $files_up[$k]['path'] = $file_path.$img_info->getFilename();
            }
        }
        //print_r($files_up);die;
        
        $result = $this->curlPost($url,$data,$files_up);
        $result = json_decode($result,true);
        
        if($result['code'] == 200){
            $this->success($result['message'],url('User/index'));
        }else{
            $this->error($result['message']);
        }
        
    }

    //申请入驻 - 公司 -提交
    public function do_apply_enter_company()
    {
        $url = "Supplier/apply_enter_company";
        $data = array();
        $data = input();
        $data['user_id'] = $this->user_id;
        //print_r($data);die;
        $files_up = array();
        $file_path = $data['file_path'] =  "runtime/temp/";
        //$save_file_path = ROOT_PATH."runtime/temp/";

        foreach ($_FILES as $k => $v) {
            $files_img = request()->file($k);
            if(!empty($files_img)){
                $img_info = $files_img->rule('uniqid')->move($file_path);
                $files_up[$k]['name'] = $k;
                $files_up[$k]['path'] = $file_path.$img_info->getFilename();
            }
        }
        //print_r($files_up);die;
        
        $result = $this->curlPost($url,$data,$files_up);
        $result = json_decode($result,true);
        
        if($result['code'] == 200){
            $this->success($result['message'],url('User/index'));
        }else{
            $this->error($result['message']);
        }

    }

}
