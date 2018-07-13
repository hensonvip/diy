<?php
/**
 * 在线DIY
 */
require_once(ROOT_PATH . 'includes/cls_diy.php');
require_once(ROOT_PATH . 'includes/cls_user.php');

class DiyController extends ApiController
{
    public function __construct()
    {
        parent::__construct();
        $this->data = $this->input();
        $this->diy = cls_diy::getInstance();
        $this->user_id = isset($this->data['user_id'])? $this->data['user_id'] : 0;
    }

    /* 款式 */
    public function goodsType(){
        $require_fields = array('type_id');
        foreach($require_fields as $v)
        {
            if(!isset($this->data[$v])) // || empty($this->data[$v])
            {
                $this->error("缺失必选参数 ({$v})，请参考API文档", '-1');
            }else{
                if(strpos($v,'_id')){
                    $$v = intval(trim($this->data[$v]));
                }else{
                    $$v = stripslashes(trim($this->data[$v]));
                }
            }
        }

        $type_id = $this->input('type_id', 0);
        $goods_type = $this->diy->goods_type($type_id);
        $this->success($goods_type);
    }

    /* 款式 */
    public function style(){
        $require_fields = array('type_id');
        foreach($require_fields as $v)
        {
            if(!isset($this->data[$v])) // || empty($this->data[$v])
            {
                $this->error("缺失必选参数 ({$v})，请参考API文档", '-1');
            }else{
                if(strpos($v,'_id')){
                    $$v = intval(trim($this->data[$v]));
                }else{
                    $$v = stripslashes(trim($this->data[$v]));
                }
            }
        }

        $type_id = $this->input('type_id', 0);
        $style = $this->diy->style($type_id);
        $this->success($style);
    }

    /* 颜色 */
    public function color(){
        $require_fields = array('type_id');
        foreach($require_fields as $v)
        {
            if(!isset($this->data[$v])) // || empty($this->data[$v])
            {
                $this->error("缺失必选参数 ({$v})，请参考API文档", '-1');
            }else{
                if(strpos($v,'_id')){
                    $$v = intval(trim($this->data[$v]));
                }else{
                    $$v = stripslashes(trim($this->data[$v]));
                }
            }
        }

        $type_id = $this->input('type_id', 0);
        $color = $this->diy->color($type_id);
        $this->success($color);
    }

    /* 属性组合图片 */
    public function attrGroupImg(){
        $require_fields = array('type_id');
        foreach($require_fields as $v)
        {
            if(!isset($this->data[$v])) // || empty($this->data[$v])
            {
                $this->error("缺失必选参数 ({$v})，请参考API文档", '-1');
            }else{
                if(strpos($v,'_id')){
                    $$v = intval(trim($this->data[$v]));
                }else{
                    $$v = stripslashes(trim($this->data[$v]));
                }
            }
        }

        $type_id = $this->input('type_id', 0);
        $attr_group_img = $this->diy->attr_group_img($type_id);
        $this->success($attr_group_img);
    }

    /* 获取字体 */
    public function fontList(){
        $font_list = $this->diy->font_list();
        $this->success($font_list);
    }

    /* 获取图形 */
    public function graphList(){
        $graph_list = $this->diy->graph_list();
        $this->success($graph_list);
    }

    /* 获取蒙版 */
    public function maskList(){
        $mask_list = $this->diy->mask_list();
        $this->success($mask_list);
    }

    /* diy作品信息 */
    public function diyInfo(){
        $require_fields = array('diy_id', 'user_id');
        foreach($require_fields as $v)
        {
            if(!isset($this->data[$v])) // || empty($this->data[$v])
            {
                $this->error("缺失必选参数 ({$v})，请参考API文档", '-1');
            }else{
                if(strpos($v,'_id')){
                    $$v = intval(trim($this->data[$v]));
                }else{
                    $$v = stripslashes(trim($this->data[$v]));
                }
            }
        }

        $goods_id = $this->input('diy_id', 0);
        $user_id = $this->input('user_id', 0);
        $diy_info = $this->diy->diy_Info($diy_id, $user_id);
        $this->success($diy_info);
    }

    /**
     * 导入图片
     */
    public function uploadFile(){
        if (empty($this->data['file'])) {
            $this->error('请选择图片');
        }
        $result = $this->diy->upload_File($this->user_id, $this->data['file'], $this->data['design_session']);
        if($result){
            $this->success($result);
        }else{
            $this->error('导入图片失败');
        }
    }

    /**
     * 删除图片
     */
    public function deleteFile(){
        $result = $this->diy->delete_File($this->data['file_id']);
        if($result){
            $this->success('删除成功');
        }else{
            $this->error('删除失败');
        }
    }

    /* 获取属性商品图片 */
    public function getGoodsImg(){
        $goods_img = $this->diy->get_Goods_Img($this->data['goods_attr_id'], $this->data['goods_attr_id2']);
        if (!empty($goods_img)) {
            $this->success(array('goods_img' => $goods_img), $code = 200, $msg = 'ok');
        } else {
            $this->error('属性图片不存在');
        }
    }

    /**
     * 创建设计商品
     */
    public function createGoods(){
        $user_id = $this->input('user_id', 0);
        $type_id = $this->input('type_id', 0);
        $goods_name = $this->data['goods_name'];
        $goods_img = $this->data['goods_img'];
        $design_img = $this->data['design_img'];
        $diy_json = $this->data['diy_json'];
        $attr_img = $this->data['attr_img'];
        $design_session = $this->data['design_session'];
        if (empty($goods_name)) {
            $this->error('商品名称不能为空');
        }
        if (empty($attr_img)) {
            $this->error('属性图片不能为空');
        }
        if (empty($goods_img)) {
            $this->error('商品图不能为空');
        }
        if (empty($design_img)) {
            $this->error('设计图不能为空');
        }
        $result = $this->diy->create_Goods($user_id, $type_id, $goods_name, $attr_img, $goods_img, $design_img, $design_session, $diy_json);
        if($result){
            $this->success($result);
        }else{
            $this->error('网络错误，请重试');
        }
    }
}
