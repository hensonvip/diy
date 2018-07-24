<?php
/**
 * 文章模块
 * @2017-09-23 add by qinglin
 * @version v2.0
 */

if (!defined('IN_ECS'))
{
	die('Hacking attempt');
}

class cls_article
{
	protected $_db                = null;
	protected $_tb_article        = null;
	protected static $_instance   = null;
	public static $_errno = array(
			1 => '操作成功',
			2 => '参数错误',
			3 => '分类不存在',
	);

	function __construct()
	{
        $this->_db = $GLOBALS['db'];
        $this->_tb_article  = $GLOBALS['ecs']->table('article');
		$this->_tb_article_cat	= $GLOBALS['ecs']->table('article_cat');
	}




	public static function getInstance()
	{
		if (self::$_instance === null)
		{
			$instance = new self;
			self::$_instance = $instance;
		}
		return self::$_instance ;
	}

    /**
     * 获得该分类下的子分类
     *
     * @access  public
     * @param   integer     $cat_id     分类编号
     * @return  array
     */
    function get_categories_tree($cat_id = 0)
    {

		if ($cat_id > 0){
            $parent_id = $cat_id;
        }
        else{
            $parent_id = 0;
        }

        // 获取当前分类及其子分类
        $sql = 'SELECT a.cat_id, a.cat_name, a.sort_order AS parent_order, a.cat_id, a.file_url, ' .
                    'b.cat_id AS child_id, b.cat_name AS child_name, b.sort_order AS child_order, b.file_url AS child_file_url ' .
                'FROM ' . $this->_tb_article_cat . ' AS a ' .
                'LEFT JOIN ' . $this->_tb_article_cat . ' AS b ON b.parent_id = a.cat_id ' .
                "WHERE a.parent_id = '$parent_id' AND a.keywords<>'-1' ORDER BY parent_order ASC, a.cat_id ASC, child_order ASC";

        $res = $this->_db->getAll($sql);

        $cat_arr = array();
        foreach ($res AS $row)
        {
            $cat_arr[$row['cat_id']]['id']   = $row['cat_id'];
            $cat_arr[$row['cat_id']]['name'] = $row['cat_name'];
            $cat_arr[$row['cat_id']]['file_url'] = $row['file_url'];
            //$cat_arr[$row['cat_id']]['url']  = build_uri('article_cat', array('acid' => $row['cat_id']), $row['cat_name']);

            if ($row['child_id'] != NULL)
            {
                $cat_arr[$row['cat_id']]['children'][$row['child_id']]['id']   = $row['child_id'];
                $cat_arr[$row['cat_id']]['children'][$row['child_id']]['name'] = $row['child_name'];
                $cat_arr[$row['cat_id']]['children'][$row['child_id']]['file_url'] = $row['child_file_url'];
                //$cat_arr[$row['cat_id']]['children'][$row['child_id']]['url']  = build_uri('article_cat', array('acid' => $row['child_id']), $row['child_name']);
            }
        }

        return array_values($cat_arr);

    }

    /**
     * 获取文章列表
     */
    function get_ArticleList($cat_id, $num = 10, $start = 0){
        $cat_arr = $this->get_categories_tree($cat_id);
        $in_cat = '';
        if($cat_arr){
            foreach ($cat_arr as $k => $v) {
                $in_cat .=$v['id'].',';
            }
        }
        $in_cat .= $cat_id;
        $sql = "SELECT * FROM " .$this->_tb_article." WHERE is_open=1 and cat_id in($in_cat) ORDER BY sort_order ASC";
        $res = $this->_db->selectLimit($sql, $num, $start);
        $lsit = array();
        while ($rows = $this->_db->fetchRow($res))
        {
            $row['article_id'] = $rows['article_id'];
            //$row['url'] = "http://mall.qdshop.com/mobile.php/article/news_details/id/".$rows['article_id'].".html";
            $row['url'] = "/mobile.php/article/news_details/id/".$rows['article_id'].".html";
            //$row['url'] = "http://mall.qdshop.com/mobile/article.php?id=".$rows['article_id']."";
            $row['cat_id'] = $rows['cat_id'];
            $row['title'] = $rows['title'];
            $row['description'] = $rows['description'];
            $row['content'] = $rows['content'];
            $row['file_url'] = !empty($rows['file_url']) ? $rows['file_url'] : 'data/default/default.png';
            $row['add_time'] = local_date($GLOBALS['_CFG']['date_format'], $rows['add_time']);

            $lsit[] = $row;
        }
        return $lsit;
    }

    /**
     * 获取文章数量
     */
    function get_ArticleList_count($cat_id){
        $cat_arr = $this->get_categories_tree($cat_id);
        $in_cat = '';
        if($cat_arr){
            foreach ($cat_arr as $k => $v) {
                $in_cat .=$v['id'].',';
            }
        }
        $in_cat .= $cat_id;
        $sql = "SELECT count(*) FROM " .$this->_tb_article." WHERE is_open=1 and cat_id in($in_cat) ";
        $count = $this->_db->getOne($sql);

        return $count;
    }

    /**
     * 获得指定的文章的详细信息
     *
     * @access  private
     * @param   integer     $article_id
     * @return  array
     */
    function get_ArticleDefault($article_id)
    {
        /* 获得文章的信息 */
        $sql = "SELECT article_id,cat_id,title,content,author,keywords,description,add_time,praise_num,file_url ".
                "FROM " .$this->_tb_article ."WHERE is_open = 1 AND article_id = '$article_id' ";
        $row = $this->_db->getRow($sql);

        if ($row !== false)
        {
            $row['add_time']     = local_date($GLOBALS['_CFG']['date_format'], $row['add_time']); // 修正添加时间显示

            /* 作者信息如果为空，则用网站名称替换 */
            if (empty($row['author']) || $row['author'] == '_SHOPHELP')
            {
                $row['author'] = $GLOBALS['_CFG']['shop_name'];
            }
        }

        return $row;
    }

    /**
     * 文章点赞
     */
    public function do_articlePraise($article_id)
    {
        $sql_up = "UPDATE ".$this->_tb_article." SET praise_num = praise_num +1 WHERE article_id = $article_id";
        $res = $this->_db->query($sql_up);
        // $sql = "SELECT praise_num FROM ".$this->_tb_article." WHERE article_id = $article_id";
        return $res;
    }

    /**
     * 获取分类名称
     */
    public function get_category_info($cat_id)
    {
        $sql = "SELECT cat_name, file_url FROM " . $this->_tb_article_cat . " WHERE cat_id = '$cat_id'";
        $cat_info = $this->_db->getRow($sql);
        return $cat_info;
    }
}
