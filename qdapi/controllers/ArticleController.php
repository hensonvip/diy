<?php
/**
 * 文章接口
 * @2017-09-23 add by qinglin
 * @version v2.0
 */
require_once(ROOT_PATH . 'includes/cls_article.php');

class ArticleController extends ApiController
{
	public function __construct()
	{
		parent::__construct();
		$this->data  = $this->input();
		$this->article = cls_article::getInstance();
	}

	/**
	 * 获取文章咨询列表
	 *
	 */
	 public function getArticleList(){
		$cat_id      = !empty($this->data['cat_id'])?intval($this->data['cat_id']):12;//12 获取站内资讯分类
		$page      = !empty($this->data['page'])?intval($this->data['page']):1;
		$page_size = !empty($this->data['page_size'])?intval($this->data['page_size']):10;
		$page_start = $page_size*($page-1);

		// $article_data['category'] = $this->article->get_categories_tree(12);//12 获取站内资讯分类
		//print_r($article_data['category']);die;
		$article_data['category_info'] = $this->article->get_category_info($cat_id);//获取分类名称
		$article_data['list'] = $this->article->get_ArticleList($cat_id,$page_size, $page_start);
		$count = $this->article->get_ArticleList_count($cat_id);

		//分页
        $pager = array();
        $pager['page']         = $page;
        $pager['page_size']    = $page_size;
        $pager['record_count'] = $count;
        $pager['page_count']   = $page_count = ($count > 0) ? intval(ceil($count / $page_size)) : 1;

        $article_data['pager'] = $pager;
		$this->success($article_data);
	 }

	 /**
	 * @description 文章详情
	 * @param integer id 文章ID
	 */
	public function getArticleDefault()
	{
		$id = !empty($this->data['id'])?intval($this->data['id']):'';
		if(empty($id)){
			$this->error("参数错误！");
		}
		$info = $this->article->get_ArticleDefault($id);
		$this->success($info);
	}

	/**
	 * @description 固定文章详情
	 * @param integer type_id 固定文章代码ID   中转为文章ID   防止文章误删，程序做中转，APP则不用更换新包
	 */
	public function getArticleTypeDefault()
	{
		$type_id = !empty($this->data['type_id'])?intval($this->data['type_id']):0;
		switch ($type_id) {
			case '1':
				$id = 20;//购物流程文章
				break;
			case '2':
				$id = 16;//支付与配送
				break;
			case '3':
				$id = 18;//常见问题
				break;
			case '4':
				$id = 1;//版权声明
				break;
			case '5':
				$id = 5;//平台说明
				break;
			case '6':
				$id = 4;//关于我们
				break;
			case '7':
				$id = 6;//注册协议
				break;
			default:
				$id = 0;
				break;
		}
		if(empty($id)){
			$this->error("参数错误！");
		}
		$info = $this->article->get_ArticleDefault($id);
		$this->success($info);
	}

	/**
	 * @description 文章点赞
	 * @param integer article_id 要点赞的文章ID
	 */
	public function doArticlePraise()
	{
		$user_id = !empty($this->data['user_id'])?intval($this->data['user_id']):0;
		$article_id = !empty($this->data['article_id'])?intval($this->data['article_id']):0;
		if(empty($article_id) || empty($user_id)){
			$this->error("参数错误！");
			exit;
		}
		 $res = $this->article->do_articlePraise($article_id);
		 if($res){
		 	$info = $this->article->get_ArticleDefault($article_id);
		 	$data = array();
		 	$data['praise_num'] = $info['praise_num'];
			$this->success($data,200,'点赞成功！');
		 	//$this->success("点赞成功！");
		 }else{
		 	$info = $this->article->get_ArticleDefault($article_id);
		 	$data = array();
		 	$data['praise_num'] = $info['praise_num'];
		 	$this->error("点赞失败！",200,$data);
		 }
		 exit;
	}

	/**
	 * 获取分类树
	 */
	public function getCategoriesTree() {
		$cat_id = !empty($this->data['cat_id']) ? intval($this->data['cat_id']) : 0;
		$cat_data['category_info'] = $this->article->get_category_info($cat_id);
		$cat_data['cat_tree'] = $this->article->get_categories_tree($cat_id);
		$this->success($cat_data);
	}


}