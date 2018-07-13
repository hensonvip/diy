<?php
namespace app\home\controller;
use think\Controller;
use think\Session;

class Originality extends Common
{

	public function __construct()
    {
        parent::__construct();
        if(empty($this->user_id)){
            header("Location:".url('User/login'));exit;
        }
        $this->type_id = input('type_id','','intval') ? input('type_id','','intval') : 0;
        $this->assign('type_id', $this->type_id);

        //左侧导航标题
        $left_title['my_list'] = "我的参赛作品";
        $left_title['author_list'] = "实时排行榜";
        $left_title['already_list'] = "已征集作品";
        $left_title['details'] = "比赛详情";
         $this->assign('left_title',$left_title);


        //创意擂台 不同时期不同nav
        $matchRule_api = "Originality/matchRule";
        $matchRule_result = $this->curlGet($matchRule_api);
        $matchRule_result = json_decode($matchRule_result,true);
        $this->assign('match_cycle',$matchRule_result['data']['code']);
        $this->assign('match_data',$matchRule_result['data']['time_data']);
        $this->assign('left_list_title', $matchRule_result['data']['left_title']);
        $this->assign('url_title', $matchRule_result['data']['url']);

        $productList_apis = "Originality/productList";
        $productList_results = $this->curlGet($productList_apis);
        //print_r($productList_results);exit;
        $productList_results = json_decode($productList_results,true);
        $this->assign('list_count', $productList_results['data']['count']);//已征集作品统计

        $vote_data['user_id']    = $this->user_id;
        $vote_data['only_count'] = 1;//只读取总数
        $voteUserList_results    = $this->curlGet("Originality/voteUserList",$vote_data);
        $voteUserList_results    = json_decode($voteUserList_results,true);
        //print_r($voteUserList_results);exit;
        $this->assign('count', $voteUserList_results['data']['pager']['record_count']);//统计参赛人数

        $data['user_id']=$this->user_id;
        $results = $this->curlGet("Originality/completeProductList",$data);
        $results = json_decode($results,true);
        $this->assign('complete_list', $results['data']);//已有的参赛作品列表

        $ajax_cat['cat_id'] = $results['data'][0]['cat_id'];
        $cat = $this->curlGet("Originality/category",$ajax_cat);
        $cat = json_decode($cat,true);
        $this->assign('cat_list', $cat['data']);
        //print_r($cat);exit;

        $comment_data['record_id']=input('record');
        $comment_data['user_id']=$this->user_id;//用来判断是否作者本人，是本人有回复框
        $comment_data['page'] = input('pages',1,'intval');
        $comment = $this->curlGet("Originality/commentData",$comment_data);
        $comment = json_decode($comment,true);

        // 私信举报原因
        $url = "user/getReportReason";
        $result = $this->curlGet($url);
        $result = json_decode($result,true);//json转数组
        $this->assign('reason_list', $result['data']);


        $this->assign('comment_list', $comment['data']['comment']);//评论列表
        $this->assign('comment_page', $comment['data']['pager']);//评论列表分页
        $is_comment_ajax = input('is_comment_ajax',0,'intval');
        $this->assign('is_commnt_ajax',$is_comment_ajax);
        if($is_comment_ajax){
            echo $this->fetch('comment_ajax');exit();
        }

        //print_r($comment);exit;


    }
    /**
     * 页面背景数据
     */
    private function html_bg()
    {
        $result = $this->curlGet("Originality/index");
        $result = json_decode($result,true);
        $this->assign('adv_list', $result['data']['adv_list']);
    }

    public function index()
    {
        $this->html_bg();
        $this->site_title .= ' - 创意擂台';
        $this->assign('site_title', $this->site_title);

        return $this->fetch();
    }

    public function open_list()
    {
        $this->html_bg();
        $this->site_title .= ' - 创意擂台-作品公示';
        $this->assign('site_title', $this->site_title);

        $api = "Originality/open_list";
        $results = $this->curlGet($api);

        $results = json_decode($results,true);
        //print_r($results);exit;
        //$data = array();
        //print_r($results);exit;

        $this->assign('product_list', $results['data']);

        $this->assign('count', $results['data']['count']);

        return $this->fetch();
    }

    public function vote_list()
    {
        $this->html_bg();
        $this->site_title .= ' - 创意擂台-作品投票';
        $this->assign('site_title', $this->site_title);

        $data['vote_uid'] = $this->user_id;//已投票的用户
        $data['page'] = input('page','1','intval');
        $apis = "Originality/productList";
        $results = $this->curlGet($apis,$data);
        $results = json_decode($results,true);
        $this->assign('product_list', $results['data']);
        //$this->assign('list_count', count($results['data']));
        $this->assign('vote_list', $results['data']);

        //print_r($results['data']['list']);exit;
        //$this->assign('user_id', $this->user_id);//当前用户（判断是否已投票）
        //异步加载分页数据
        $is_ajax = input('is_ajax',0,'intval');
        $this->assign('is_ajax',$is_ajax);
        /*if($is_ajax){
            echo $this->fetch('vote_list_ajax');exit();
        }*/
        if($is_ajax){
            $result['list'] = $this->fetch('vote_list_ajax');
            $html_pop = '';
            //print_r('<pre>');print_r($result['list']);exit();
            foreach ($results['data']['list'] as $key => $value) {
                $html_pop .= '<div class="ipro_popup ipro_popup-'.$value['record_id'].'"></div>';
            }
            $result['html_pop'] = $html_pop;
            echo json_encode($result);exit();
        }

        return $result['list'] = $this->fetch();

        //return $this->fetch();
    }

    //ajax投票
    public function vote_ajax(){
        $ajax_data['record_id'] = input('record');
        $ajax_data['user_id'] = $this->user_id;
        if($ajax_data['record_id']) {
            $apis = "Originality/ajax_vote";
            $results = $this->curlGet($apis,$ajax_data);
            echo $results;
            //$results = json_decode($results,true);
            //print_r($results);
        }
    }

    //举报
    public function report_ajax(){
        $ajax_data['comment_id'] = input('comment_id');//评论id
        $ajax_data['reason'] = input('reason');//原因
        $ajax_data['type'] = input('type');//类型

        $ajax_data['user_id'] = $this->user_id;
        if($ajax_data['comment_id']) {
            $apis = "Originality/report";
            $results = $this->curlGet($apis,$ajax_data);
            echo $results;
        }
    }

    //我的参赛作品
    public function competit_my_list()
    {
        $this->html_bg();
        $this->site_title .= ' - 创意擂台-我的参赛作品';
        $this->assign('site_title', $this->site_title);

        //审核状态
        /*$state=array(array('name'=>'已通过','code'=>'2'),array('name'=>'待审核','code'=>'0'),array('name'=>'未通过','code'=>'1'));
        $this->assign('state', $state);*/

        $data_already['state'] = input('state',2);//已通过
        $data_already['user_id'] = $this->user_id;//作者
        $data_already['page'] = input('page',1,'intval');
        $url = "Originality/productList";
        $results = $this->curlGet($url,$data_already);
        $results = json_decode($results,true);
        $this->assign('already_my_list', $results['data']);
        //print_r($results);

        //前端未传state 默认参数查出对应列表
        $data_already['state'] = input('state',1);//未通过
        $results = $this->curlGet($url,$data_already);
        $results = json_decode($results,true);
        $this->assign('not_my_list', $results['data']);

        $data_already['state'] = input('state',0);//待审核
        $results = $this->curlGet($url,$data_already);
        //print_r($results);exit;
        $results = json_decode($results,true);
        //print_r($results);exit;
        $this->assign('wait_my_list', $results['data']);



        //异步加载分页数据
        $is_ajax = input('is_ajax',0,'intval');
        $this->assign('is_ajax',$is_ajax);
        /*if($is_ajax == 1) {
            if ($data_already['state'] == 2) {
                echo $this->fetch('already_my_list_ajax');exit();
            } elseif ($data_already['state'] == 0) {
                echo $this->fetch('wait_my_list_ajax');exit();
            } elseif ($data_already['state'] == 1) {
                echo $this->fetch('not_my_list_ajax');exit();
            }
        }*/
        /*return $this->fetch();*/
        if($is_ajax){
            if ($data_already['state'] == 2) {
                $result['state'] = 2;
                $result['list'] = $this->fetch('already_my_list_ajax');
                $html_pop = '';
                //print_r('<pre>');var_dump($results['data']['list']);exit();
                foreach ($results['data']['list'] as $key => $value) {
                    $html_pop .= '<div class="ipro_popup ipro_popup-'.$value['record_id'].'"></div>';
                }
                $result['html_pop'] = $html_pop;
                echo json_encode($result);exit();
            } elseif ($data_already['state'] == 0) {
                $result['state'] = 0;
                $result['list'] = $this->fetch('wait_my_list_ajax');
                $html_pop = '';
                //print_r('<pre>');var_dump($results['data']['list']);exit();
                foreach ($results['data']['list'] as $key => $value) {
                    $html_pop .= '<div class="ipro_popup ipro_popup-'.$value['record_id'].'"></div>';
                }
                $result['html_pop'] = $html_pop;
                echo json_encode($result);exit();

            } elseif ($data_already['state'] == 1) {
                $result['state'] = 1;
                $result['list'] = $this->fetch('not_my_list_ajax');
                $html_pop = '';
                //print_r('<pre>');var_dump($results['data']['list']);exit();
                foreach ($results['data']['list'] as $key => $value) {
                    $html_pop .= '<div class="ipro_popup ipro_popup-'.$value['record_id'].'"></div>';
                }
                $result['html_pop'] = $html_pop;
                echo json_encode($result);exit();
            }
        }

        return $result['list'] = $this->fetch();
    }

    public function competit_author_list()
    {
        $this->html_bg();
        $this->site_title .= ' - 创意擂台-参赛作者';
        $this->assign('site_title', $this->site_title);

        $vote_data['user_id']   = $this->user_id;
        $vote_data['page']      = input('page','1','intval');
        $voteUserList_results   = $this->curlGet("Originality/voteUserList",$vote_data);
        $this->assign('voteUserList_results', json_decode($voteUserList_results,true));
        //异步加载分页数据
        $is_ajax = input('is_ajax',0,'intval');
        $this->assign('is_ajax',$is_ajax);  
        if($is_ajax){
            echo $this->fetch('competit_author_list_ajax');exit();
        }
        return $this->fetch();
    }

    public function competit_product_list()
    {
        $this->html_bg();
        $this->site_title .= ' - 创意擂台-参赛作品';
        $this->assign('site_title', $this->site_title);
        $ajax_page = array();
        $ajax_page['page'] = input('page',1);
        $apis = "Originality/productList";
        $results = $this->curlGet($apis,$ajax_page);
        //print_r($results);exit;
        $results = json_decode($results,true);
        
        $this->assign('product_list', $results['data']);

        //异步加载分页数据
        $is_ajax = input('is_ajax',0,'intval');
        $this->assign('is_ajax',$is_ajax);
        if($is_ajax){
            $result['list'] = $this->fetch('competit_product_list_ajax');
            $html_pop = '';
            //print_r('<pre>');var_dump($results['data']['list']);exit();
            foreach ($results['data']['list'] as $key => $value) {
                $html_pop .= '<div class="ipro_popup ipro_popup-'.$value['record_id'].'"></div>';
            }
            $result['html_pop'] = $html_pop;
            echo json_encode($result);exit();
        }
        
        return $result['list'] = $this->fetch();
    }

    //弹窗数据 ajax
    function popup_ajax(){
        $data_ajax['key'] = input('key',0);
        $data_ajax['record_id'] = input('record');
        //$data_ajax['state'] =  input('state');
        $data_ajax['user_id'] = $this->user_id;

        if($data_ajax['record_id']){
            $apis = "Originality/framedata";
            $results = $this->curlPost($apis,$data_ajax);
            //var_dump($results);exit();
            $results = json_decode($results,true);
            $data['key'] = $data_ajax['key'];
            $data['data'] = $results;
            //print_r('<pre>');var_dump($data);exit();
            $this->assign('record_id', $data_ajax['record_id']);
            $this->assign('details_data', $data['data']['data']);
            // echo json_encode($data);exit;
        }

        $tpl = $this->fetch('details_popup');

        echo json_encode($tpl);exit;
    }

    //私信
    function private_message(){
        $is_ajax['user_id'] = $this->user_id;
        $is_ajax['receive_user_id'] = input('receive_user_id');//接收人
        $is_ajax['msg_content'] = input('msg_content');

        $apis = "User/sendLetter";
        $results = $this->curlGet($apis,$is_ajax);
        //$results = json_decode($results,true);
        if($results){
            echo $results;
        }
    }

    //ajax获取类型（分类）
/*    function category(){
        //设计类型
        $ajax_cat['cat_id'] = input('cat_id');
        $cat = $this->curlGet("Originality/category",$ajax_cat);
        $cat = json_decode($cat,true);
        $is_cat_ajax = input('is_cat_ajax',1,'intval');
        $this->assign('is_cat_ajax',$is_cat_ajax);
        if($is_cat_ajax){
            //var_dump($cat);exit;
            echo json_encode($cat['data']);exit;
        }
    }*/

    
    

     /**
      * diy首页
      */
     public function diyindex()
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

         $this->assign('button',1);

         //类型
         $datas['cat_id'] = 85;
         $api = "Originality/category";
         $result = $this->curlGet($api,$datas);
         $result = json_decode($result,true);
         $this->assign('category', $result['data']);
         //print_r($datas);exit;
         $this->assign('diy_page', 1);


         // 获取复制的diy作品信息
         if (!empty($this->goods_id)) {
             $api = "diy/diyInfo";
             $data = array();
             $data['goods_id'] = $this->goods_id;
             $data['user_id'] = $this->user_id;
             $result = $this->curlGet($api, $data);
             $result = json_decode($result,true);
             if ($result['data']) {
                 $this->assign('diy_json', $result['data']['diy_json']);
             } else {
                 $this->assign('diy_json', '');
             }
         } else {
             $this->assign('diy_json', '');
         }

         $this->assign('diy_page', 1);

         return $this->fetch('diy/index');
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

    //提交参赛作品信息
    function from_ajax(){
        //echo 1;exit();
        $data['info'] = $_POST;
        $data['user_id'] = $this->user_id;
        $api = "Originality/diyInfoAjax";
        $result = $this->curlPost($api,$data);
        //var_dump($result);exit;
        //$result = json_decode($result);
        if($result){
            echo $result;exit();
        }
    }


    //取消点赞
    function zan_reduce(){
        $data['user_id'] = $this->user_id;
        $data['record_id'] = input('record');
        $data['diy_id'] = input('diy_id');
        //echo json_encode($data);exit;
        $api = "Originality/zan_reduce";
        $result = $this->curlPost($api,$data);
        if($data['record_id']&&$data['diy_id']&&$result){
            echo $result;
        }

    }

    //点赞
    function zan_increase(){
        $data['user_id'] = $this->user_id;
        $data['record_id'] = input('record');
        $data['diy_id'] = input('diy_id');
        //$data['author_id'] =input('author_id');
        //echo json_encode($data);exit;
        $api = "Originality/zan_increase";
        $result = $this->curlPost($api,$data);
        if($data['record_id']&&$data['diy_id']&&$result){
            echo $result;
        }
    }

    //评论
    function comment(){
        $data['user_id'] = $this->user_id;//评论的人
        $data['author_id'] = input('author_id');//接收评论的人
        $data['record_id'] = input('record_id');//作品id
        //$data['diy_id'] = input('diy_id');//作品id 暂时没用
        $data['content'] = input('content');//
        //echo json_encode($data);exit;
        $api = "Originality/comment";
        $result = $this->curlPost($api,$data);
        $return_data = json_decode($result,true);
        $results['user_data'] = $return_data['data'];
        $results['data'] = $data;
        if($results['user_data']){
            echo json_encode($results);
        }

    }

    //回复
    function reply(){
        $data['author_id'] = $this->user_id;//回复的人 只有作者能回复
        $data['user_id'] = input('user_id');//接收回复的人
        $data['record_id'] = input('record_id');//作品id
        $data['comment_id'] = input('comment_id');//评论id
        $data['content'] = input('content');
        //echo json_encode($data);exit;
        $api = "Originality/reply";
        $result = $this->curlPost($api,$data);
        //echo $result;exit;
        $return_data = json_decode($result,true);
        $results['user_data'] = $return_data['data'];
        $results['data'] = $data;
        if($results['user_data']){
            echo json_encode($results);
        }

    }

    function comment_zan(){
        $data['user_id'] = $this->user_id;
        $data['comment_id'] = input('comment_id');//评论id

        $api = "Originality/comment_zan";
        $result = $this->curlGet($api,$data);
        $return_data = json_decode($result,true);
        if($result){
            echo json_encode($return_data['data']);
        }
    }

     /**
      * 创建设计商品
      */
/*     public function create_goods () {
         $url = "diy/createGoods";
         $data = array();
         $data['user_id'] = $this->user_id;
         $data['type_id'] = $this->type_id;
         $data['goods_name'] = input('goods_name');
         $data['attr_img'] = json_decode(input('attr_img'), true);
         $data['design_img'] = input('design_img');
         $data['design_session'] = session('design_session') ? session('design_session') : '';
         $data['goods_img'] = '';
         foreach ($data['attr_img'] as $key => $value) {
             if ($value['is_design'] == 1) {
                 $data['goods_img'] = $value['file'];
                 break;
             }
         }
         $result = $this->curlPost($url,$data);
         $result = json_decode($result, true);
         if($result['code'] == 200){
             $api = "goods/getGoodsInfo";
             $data = array();
             $data['user_id'] = $this->user_id;
             $data['goods_id'] = $result['data']['goods_id'];//商品ID，必填
             $data['is_design'] = 1;//定制商品
             $result = $this->curlGet($api,$data);
             $result = json_decode($result,true);
             $this->assign('goods_data', $result['data']);
             echo $this->fetch('diy/attr_box_ajax');exit();
         }else{
             echo 0;
         }
     }*/
}