<?php
namespace app\home\controller;
use think\Controller;
use think\Session;

class Diy extends Common
{

	public function __construct()
    {
        parent::__construct();
        if(empty($this->user_id)){
            header("Location:".url('User/login'));exit;
        }
        $this->type_id = input('type_id','','intval') ? input('type_id','','intval') : 0;
        $this->diy_id = input('diy_id','','intval') ? input('diy_id','','intval') : 0;
        $this->assign('type_id', $this->type_id);
        $this->assign('diy_id', $this->diy_id);
    }

    /**
     * 入口
     */
    public function entrance() {
        // 文章列表
        $url = "article/getArticleList";
        $data = array();
        $data['page_size'] = 6;
        $data['page'] = input('page',1,'intval');
        $data['cat_id'] = input('cat_id',26,'intval');
        $result = $this->curlGet($url,$data);
        $result = json_decode($result,true);//json转数组
        $this->assign('data',$result['data']);

        return $this->fetch();
    }

    /**
     * 首页
     */
    public function index()
    {
        if(!$this->type_id){
            $this->error('缺少商品类型ID！');
        }

        session::set('design_session', $this->uuid());//该设计的ID

        $this->site_title .= ' - 在线DIY';
        $this->assign('site_title', $this->site_title);

        // 商品类型
        $api = "diy/goodsType";
        $data = array();
        $data['type_id'] = $this->type_id;
        $result = $this->curlGet($api, $data);
        $result = json_decode($result,true);
        $this->assign('goods_type', $result['data']);

        // 款式
        $api = "diy/style";
        $data = array();
        $data['type_id'] = $this->type_id;
        $result = $this->curlGet($api, $data);
        $result = json_decode($result,true);
        // print_r($result['data']);die;
        $this->assign('style', $result['data']);

        // 颜色
        $api = "diy/color";
        $data = array();
        $data['type_id'] = $this->type_id;
        $result = $this->curlGet($api, $data);
        $result = json_decode($result,true);
        $this->assign('color', $result['data']);

        // 属性组合图片
        $api = "diy/attrGroupImg";
        $data = array();
        $data['type_id'] = $this->type_id;
        $result = $this->curlGet($api, $data);
        $result = json_decode($result,true);
        // print_r($result['data']);die;
        $this->assign('attr_group_img', $result['data']);

        // 字体
		$api = "diy/fontList";
		$result = $this->curlGet($api);
		$result = json_decode($result,true);
        // print_r($result['data']);die;
		$this->assign('font_list', $result['data']);

        // 图形
        $api = "diy/graphList";
        $result = $this->curlGet($api);
        $result = json_decode($result,true);
        $this->assign('graph_list', $result['data']);

        // 蒙版
        $api = "diy/maskList";
        $result = $this->curlGet($api);
        $result = json_decode($result,true);
        $this->assign('mask_list', $result['data']);

        // 获取复制的diy作品信息
        if (!empty($this->diy_id)) {
            $api = "diy/diyInfo";
            $data = array();
            $data['diy_id'] = $this->diy_id;
            $data['user_id'] = $this->user_id;
            $result = $this->curlGet($api, $data);
            $result = json_decode($result,true);
            if ($result['data']) {
                $this->assign('diy_json', $result['data']['diy_json']);
            } else {
                $this->error('作品不存在');
            }
        } else {
            $this->assign('diy_json', '');
        }

        $this->assign('diy_page', 1);

		return $this->fetch();
    }

    /**
     * 导入图片
     */
    public function upload_file () {
        $url = "diy/uploadFile";
        $data = array();
        $data['user_id'] = $this->user_id;
        $data['file'] = input('file');
        $data['design_session'] = session('design_session') ? session('design_session') : '';
        $result = $this->curlPost($url,$data);
        $result = json_decode($result);
        return $result;
    }

    /**
     * 删除图片
     */
    public function delete_file () {
        $url = "diy/deleteFile";
        $data = array();
        $data['file_id'] = input('file_id', 0, 'intval');
        $result = $this->curlPost($url,$data);
        $result = json_decode($result);
        return $result;
    }

    /**
     * AJAX获取属性商品图片
     */
    public function get_goods_img() {
        $url = "diy/getGoodsImg";
        $data = array();
        $data['goods_attr_id'] = input('goods_attr_id', 0, 'intval');
        $data['goods_attr_id2'] = input('goods_attr_id2', 0, 'intval');
        $result = $this->curlGet($url,$data);
        $result = json_decode($result);
        return $result;
    }

    /**
     * 创建设计商品
     */
    public function create_goods () {
        $url = "diy/createGoods";
        $data = array();
        $data['user_id'] = $this->user_id;
        $data['type_id'] = $this->type_id;
        $data['goods_name'] = input('goods_name');
        $data['attr_img'] = json_decode(input('attr_img'), true);
        $data['design_img'] = input('design_img');
        $data['diy_json'] = input('diy_json');
        $data['design_session'] = session('design_session') ? session('design_session') : '';
        $data['goods_img'] = '';
        $default_attr = '';//默认设计的属性
        foreach ($data['attr_img'] as $key => $value) {
            if ($value['is_design'] == 1) {
                $data['goods_img'] = $value['file'];
                $default_attr = $value['attr'];
                break;
            }
        }
        $result = $this->curlPost($url,$data);
        //echo $result;exit;
        $result = json_decode($result, true);
        if($result['code'] == 200){
            $api = "goods/getGoodsInfo";
            $data = array();
            $data['user_id'] = $this->user_id;
            $data['goods_id'] = $result['data']['goods_id'];//商品ID，必填
            $result = $this->curlGet($api,$data);
            $result = json_decode($result,true);
            $this->assign('goods_data', $result['data']);
            if (!empty($default_attr)) {
                $_default_attr = explode(',', $default_attr);
                $diy_default_attr = array();
                foreach ($_default_attr as $key => $value) {
                    $attr_data = explode('_', $value);
                    $diy_default_attr[] = $attr_data[1];
                }
                $this->assign('default_style', $diy_default_attr[0]);//默认设计的款式
                $this->assign('default_color', $diy_default_attr[1]);//默认设计的颜色
            }
            echo $this->fetch('diy/attr_box_ajax');exit();
        }else{
            echo 0;
        }
    }
}