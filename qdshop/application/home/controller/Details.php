<?php
namespace app\home\controller;
use think\Controller;
use think\Session;

class Details extends Common
{

	public function __construct()
    {
        parent::__construct();
        if(empty($this->user_id)){
            header("Location:".url('User/login'));exit;
        }
        //$this->type_id = input('type_id','','intval') ? input('type_id','','intval') : 0;
        //$this->assign('type_id', $this->type_id);

        $this->record = input('record');
        $this->assign('record_id', $this->record);

        $matchRule_api = "Originality/matchRule";
        $matchRule_result = $this->curlGet($matchRule_api);
        $matchRule_result = json_decode($matchRule_result,true);
        $this->assign('match_cycle',$matchRule_result['data']['code']);
    }

    public function index(){
        $this->site_title .= ' - 创意擂台详情页投票期';
        $api = "index/head";
        $result = $this->curlGet($api);
        $result = json_decode($result,true);
        $this->assign('head',$result['data']);
        $this->assign('site_title', $this->site_title);

        //详情数据
        $data_ajax['record_id'] = $this->record;
        //$data_ajax['state'] =  input('state',2);
        $data_ajax['user_id'] = $this->user_id;
        $apis = "Originality/framedata";
        $results = $this->curlPost($apis,$data_ajax);
        $results = json_decode($results,true);
        $this->assign('details_data',$results['data']);
        //print_r($results);exit;

        // 私信举报原因
        $url = "user/getReportReason";
        $result = $this->curlGet($url);
        $result = json_decode($result,true);//json转数组
        $this->assign('reason_list', $result['data']);

        //评论
        $comment_data['record_id']=input('record');
        //var_dump($comment_data['record_id']);
        $comment_data['user_id']=$this->user_id;//用来判断是否作者本人，是本人有回复框
        $comment_data['page'] = input('pages',1,'intval');
        $comment = $this->curlGet("Originality/commentData",$comment_data);
        $comment = json_decode($comment,true);
        $this->assign('comment_list', $comment['data']['comment']);//评论列表
        $this->assign('comment_page', $comment['data']['pager']);//评论列表分页
        $is_comment_ajax = input('is_comment_ajax',0,'intval');
        $this->assign('is_commnt_ajax',$is_comment_ajax);
        if($is_comment_ajax){
            echo $this->fetch('Originality/comment_ajax');exit();
        }


        return $this->fetch();
    }


/*    //评论
    function comment(){
        $data['user_id'] = $this->user_id;//评论的人
        $data['author_id'] = input('author_id');//接收评论的人
        $data['record_id'] = input('record_id');//作品id
        $data['diy_id'] = input('diy_id');//作品id 暂时没用
        $data['content'] = input('content');//
        //echo json_encode($data);exit;
        $api = "Originality/comment";
        $result = $this->curlPost($api,$data);
        $return_data = json_decode($result,true);
        $results['user_data'] = $return_data['data'];
        //echo json_encode($results);exit;
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

    //取消点赞
    function zan_reduce(){
        $data['user_id'] = $this->user_id;
        $data['record_id'] = $this->record;
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
        $data['record_id'] = $this->record;
        $data['diy_id'] = input('diy_id');
        //echo json_encode($data);exit;
        $api = "Originality/zan_increase";
        $result = $this->curlPost($api,$data);
        if($data['record_id']&&$data['diy_id']&&$result){
            echo $result;
        }
    }*/

}