<?php
namespace app\mobile\controller;
use think\Controller;
use think\Session;

class Article extends Common
{

	public function __construct()
    {
        parent::__construct();
        $this->user_id = session('user_id') ? session('user_id') : '';
    }

    //文章资讯
    public function news()
    {
        $url = "article/getArticleList";
        $data = array();
        $data['page_size'] = 5;
        $data['page'] = input('page',1,'intval');
        $data['cat_id'] = input('cat_id',12,'intval');
        $result = $this->curlGet($url,$data);
        $result = json_decode($result,true);//json转数组
        //print_r($result);die;
        $this->assign('data',$result['data']);
        $this->assign('cat_id',$data['cat_id']);

        //异步加载分页数据
        $is_ajax = input('is_ajax','','intval') ? input('is_ajax','','intval') : 0;
        $this->assign('is_ajax',$is_ajax);
        if($is_ajax){
            echo $this->fetch('article/news_ajax');exit();
        }
        return $this->fetch();
    }

    //文章资讯详情
    public function news_details()
    {
        $id = input('id',0,'intval');
        $url = "article/getArticleDefault";
        $data = $this->curlGet($url,array('id'=>$id));
        $data = json_decode($data,true);
        $this->assign('data',$data['data']);
        $this->assign('device',input('device','','trim'));
        return $this->fetch();
    }

    //固定文章资讯详情
    public function type_details()
    {
        $type_id = input('type_id',0,'intval');
        $url = "article/getArticleTypeDefault";
        $data = $this->curlGet($url,array('type_id'=>$type_id));
        $data = json_decode($data,true);
        $this->assign('data',$data['data']);
        $this->assign('device',input('device','','trim'));
        return $this->fetch('article/news_details');
    }

    /**
     * 点赞
     */
    public function do_praise()
    {
        if(!$this->user_id){
            $return = array(
                "code" => 500,
                "message" => "请先登录",
                "url" => url('User/login')
            );
            exit(json_encode($return));
        }
        $data['article_id'] = input('article_id',0,'intval');
        $data['user_id'] = $this->user_id;
        // 调用接口
        $url = "article/doArticlePraise";
        $praise_info = $this->curlPost($url,$data);
        $praise_info = json_decode($praise_info);
        return $praise_info;
    }


}
