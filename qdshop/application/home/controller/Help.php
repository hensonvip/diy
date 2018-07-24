<?php
namespace app\home\controller;
use think\Controller;

class Help extends Common
{
	public function __construct()
    {
        parent::__construct();
    }

    /**
     * 关于OTEE
     */
    public function about_us()
    {
        // 关于OTEE
        $url = "article/getArticleDefault";
        $data = $this->curlGet($url, array('id' => 5));
        $data = json_decode($data, true);
        $this->assign('about_us',$data['data']);

        // 用户协议
        $url = "article/getArticleDefault";
        $data = $this->curlGet($url, array('id' => 4));
        $data = json_decode($data, true);
        $this->assign('user_agreement',$data['data']);

        // 企业服务
        $url = "article/getArticleList";
        $data = array();
        $data['page_size'] = 12;
        $data['page'] = input('page',1,'intval');
        $data['cat_id'] = input('cat_id',9,'intval');
        $result = $this->curlGet($url,$data);
        $result = json_decode($result,true);
        $this->assign('services',$result['data']);

        // 常见问题分类
        $url = "article/getCategoriesTree";
        $data = array();
        $data['cat_id'] = input('cat_id',27,'intval');
        $result = $this->curlGet($url,$data);
        $result = json_decode($result,true);
        $this->assign('question_cat_tree',$result['data']);

        // 联系我们
        $url = "article/getArticleDefault";
        $data = $this->curlGet($url, array('id' => 3));
        $data = json_decode($data, true);
        $this->assign('contact_us',$data['data']);

        // 网站使用调查
        $url = "article/getArticleDefault";
        $data = $this->curlGet($url, array('id' => 2));
        $data = json_decode($data, true);
        $this->assign('research',$data['data']);

		return $this->fetch();
    }

    /**
     * ajax获取问题列表
     */
    public function question_list() {
        $url = "article/getArticleList";
        $data = array();
        $data['page_size'] = 100;
        $data['page'] = input('page',1,'intval');
        $data['cat_id'] = input('cat_id',0,'intval');
        $result = $this->curlGet($url,$data);
        $result = json_decode($result,true);
        $this->assign('question_list',$result['data']);
        echo $this->fetch('help/question_list_ajax');exit();
    }

    // 意见反馈提交
    public function do_research() {
        $url = "user/doResearch";
        $data = array();
        $data['user_id'] = $this->user_id;
        $data['content'] = input('content','','trim,strip_tags,htmlspecialchars');
        $result = $this->curlPost($url,$data);
        $result = json_decode($result,true);
        if($result['code'] == 500){
            $this->error($result['message']);
        }else{
            $this->success($result['message'],url('Help/about_us'));
        }
    }

}