<?php
/**
 * 店铺模块
 * @2017.09.01 by qinglin
 */

if (!defined('IN_ECS'))
{
	die('Hacking attempt');
}

include_once(ROOT_PATH . 'includes/lib_order.php');
include_once(ROOT_PATH . 'includes/lib_common.php');

class cls_supplier
{
	protected $_db                = null;
	protected $_tb_goods          = null;
	protected static $_instance   = null;
	public static $_errno = array(
			1 => '操作成功',
			2 => '参数错误',
			500 => '系统异常',
	);

	function __construct()
	{
		$this->_db = $GLOBALS['db'];
        $this->_tb_goods            = $GLOBALS['ecs']->table('goods');
        $this->_tb_supplier_guanzhu = $GLOBALS['ecs']->table('supplier_guanzhu');
        $this->_tb_supplier_shop_config = $GLOBALS['ecs']->table('supplier_shop_config');
        $this->_tb_supplier_street = $GLOBALS['ecs']->table('supplier_street');
        $this->_tb_comment = $GLOBALS['ecs']->table('comment');
        $this->_tb_order_info = $GLOBALS['ecs']->table('order_info');
        $this->_tb_shop_grade = $GLOBALS['ecs']->table('shop_grade');
        $this->_tb_member_price = $GLOBALS['ecs']->table('member_price');
        $this->_tb_supplier_goods_cat = $GLOBALS['ecs']->table('supplier_goods_cat');
		$this->_tb_category = $GLOBALS['ecs']->table('category');
        $this->_tb_street_category = $GLOBALS['ecs']->table('street_category');
        $this->_tb_supplier         = $GLOBALS['ecs']->table('supplier');
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
     * 店铺列表
     * @param integer $supplier_type    店铺类型
     * @param integer $size             分页数量
     * @param integer $page             当前分页
     * @param string  $sort             排序方式
     * @param string  $order            按字段排序
     * @param string  $keywords         关键字
     * @return [type] [description]
     */
    public function getSupplierlist($supplier_type,$size,$page,$order,$sort,$keywords,$user_id)
    {
        $is_search = 0;//是否是搜索过来的

        //$keywords         = isset($_REQUEST['keywords']) ? trim(addslashes(htmlspecialchars($_REQUEST['keywords']))) : '';

        /* 分页大小 */
        $page = empty($page) || (intval($page) <= 0) ? 1 : intval($page);
        if (isset($size) && intval($size) > 0)
        {
            $size = intval($size);
        }elseif (isset($_COOKIE['ECSCP']['page_size']) && intval($_COOKIE['ECSCP']['page_size']) > 0)
        {
            $size = intval($_COOKIE['ECSCP']['page_size']);
        }else{
            $size = 13;
        }
        $filter['start']       = ($page - 1) * $size;
        
        $where = " where status=1 and is_show=1 ";
        if($supplier_type){
            $where .= ' and supplier_type='.$supplier_type;
        }
        if($keywords && $keywords != '请输入关键词'){
            $is_search = 1;
            $where .= " and supplier_id in(SELECT DISTINCT supplier_id
                    FROM ".$this->_tb_supplier_shop_config." AS ssc
                    WHERE (
                    code = 'shop_name'
                    AND value LIKE '%".$keywords."%'
                    )
                    OR (
                    code = 'shop_keywords'
                    AND value LIKE '%".$keywords."%'
                    ))";
        }
        
        $sql = "SELECT supplier_id,supplier_name,supplier_title,supplier_desc,logo ".
                   " FROM " . $this->_tb_supplier_street. 
                   " $where" .
                   " ORDER BY $sort $order ".
                   " LIMIT " . $filter['start'] . ",$size";
        $arr = $GLOBALS['db']->getAll($sql);
        foreach($arr as $key=>$val){
            //店铺logo
            $arr[$key]['logopath'] = '/'.DATA_DIR.'/supplier/logo/logo_supplier'.$val['supplier_id'].'.jpg';//店铺logo

            $arr[$key]['address'] = "";//地址
            $shopinfo = $GLOBALS['db']->getAll("select code,value from ".$this->_tb_supplier_shop_config." where supplier_id=".$val['supplier_id']." and code in('shop_province','shop_city','shop_address','qq','ww','service_phone')");
            foreach($shopinfo as $k => $v){
                if($is_search){
                    $v['value'] =  str_replace($keywords,"<font color=red>".$keywords."</font>",$v['value']);
                }
                
                $arr[$key][$v['code']] = $v['value'];
            }

            //所在地
            if(!empty($arr[$key]['shop_address'])){
                $arr[$key]['address'] = $arr[$key]['shop_address'];
            }
            if(!empty($arr[$key]['shop_city'])){
                $arr[$key]['address'] = get_region_info($arr[$key]['shop_city']).$arr[$key]['address'];
            }
            if(!empty($arr[$key]['shop_province'])){
                $arr[$key]['address'] = get_region_info($arr[$key]['shop_province']).$arr[$key]['address'];
                $arr[$key]['province'] = get_region_info($arr[$key]['shop_province']);
            }
            //屏蔽接口不需要显示的参数
            unset($arr[$key]['shop_province']);
            unset($arr[$key]['shop_city']);
            unset($arr[$key]['shop_address']);

            $arr[$key]['address'] = trim($arr[$key]['address'],',');//所在地
            $arr[$key]['is_guanzhu'] = is_guanzhu($val['supplier_id'],$user_id) ? 1 : 0;//是否关注
            
            //店铺动态评分
            $sql1 = "SELECT AVG(comment_rank) FROM " . $this->_tb_comment . " c" . " LEFT JOIN " . $this->_tb_order_info . " o"." ON o.order_id = c.order_id"." WHERE c.status > 0 AND  o.supplier_id = " . $val['supplier_id'];
            $avg_comment = $GLOBALS['db']->getOne($sql1);
            $avg_comment = round($avg_comment,1);       
            $arr[$key]['avg_comment'] = $avg_comment;

            $sql2 = "SELECT AVG(server), AVG(shipping) FROM " . $this->_tb_shop_grade . " s" . " LEFT JOIN " . $this->_tb_order_info . " o"." ON o.order_id = s.order_id"." WHERE s.is_comment > 0 AND  s.server >0 AND o.supplier_id = " . $val['supplier_id'];
            $row = $GLOBALS['db']->getRow($sql2);

            $avg_server = round($row['AVG(server)'],1);
            $avg_shipping = round($row['AVG(shipping)'],1);
            $arr[$key]['avg_server'] = $avg_server;
            $arr[$key]['avg_shipping'] = $avg_shipping;
            //好评
            $sql3 = " SELECT c.comment_rank,s.send,s.shipping FROM ".$this->_tb_shop_grade ." AS s ".
                " LEFT JOIN ". $this->_tb_comment ." AS c ON c.order_id = s.order_id " .
                " LEFT JOIN ". $this->_tb_order_info ." AS o ON o.order_id = s.order_id".
                " WHERE s.is_comment >0 AND  s.server >0 AND o.supplier_id = " . $val['supplier_id'];
            $h = $GLOBALS['db']->getAll($sql3);
            if(!empty($h)){
                $count = 0;
                foreach($h as $k=>$value)
                {
                    $count += array_sum($value);
                }

                $haoping = (($count/3)/count($h))/5*100;
                $arr[$key]['haoping'] = round($haoping,1).'%';
            }else{
                $arr[$key]['haoping'] = '100%';
            }
            

            //店铺中有多少商品
            $goods_list = $this->get_SupplierGoods($val['supplier_id'],4);
            $goods_number = $this->get_street_goods_count($val['supplier_id']);
            $arr[$key]['goods_number'] = $goods_number;
            $arr[$key]['goods_list'] = $goods_list;
			$sql = "SELECT * FROM  ".$GLOBALS['ecs']->table('supplier')." WHERE  `supplier_id` = ".$val['supplier_id'];
			$supplier = $GLOBALS['db']->getRow($sql);
			$arr[$key]['rank'] = $GLOBALS['db']->getOne('SELECT `rank_name`  FROM '.$GLOBALS['ecs']->table('supplier_rank').' WHERE `rank_id` = '.$supplier['rank_id']);
			$arr[$key]['fensi'] =  $this->get_supplier_fensi_count($val['supplier_id']);
        }
		//$arr['sql'] = $sql;
		//$arr['keywords '] = $keywords ;
        return $arr;

    }

	/**
     * 获取店铺商品列表信息(统计数量)
     * @param  integer $suppid 店铺id
     */
	public function getSupplierlist_count($supplier_type,$size,$page,$order,$sort,$keywords,$user_id)
    {
        $is_search = 0;//是否是搜索过来的

        $keywords         = isset($_REQUEST['keywords']) ? trim(addslashes(htmlspecialchars($_REQUEST['keywords']))) : '';

        /* 分页大小 */
        $page = empty($page) || (intval($page) <= 0) ? 1 : intval($page);
        if (isset($size) && intval($size) > 0)
        {
            $size = intval($size);
        }elseif (isset($_COOKIE['ECSCP']['page_size']) && intval($_COOKIE['ECSCP']['page_size']) > 0)
        {
            $size = intval($_COOKIE['ECSCP']['page_size']);
        }else{
            $size = 13;
        }
        $filter['start']       = ($page - 1) * $size;
        
        $where = " where status=1 and is_show=1 ";
        if($supplier_type){
            $where .= ' and supplier_type='.$supplier_type;
        }
        if($keywords && $keywords != '请输入关键词'){
            $is_search = 1;
            $where .= " and supplier_id in(SELECT DISTINCT supplier_id
                    FROM ".$this->_tb_supplier_shop_config." AS ssc
                    WHERE (
                    code = 'shop_name'
                    AND value LIKE '%".$keywords."%'
                    )
                    OR (
                    code = 'shop_keywords'
                    AND value LIKE '%".$keywords."%'
                    ))";
        }
        
        $sql = "SELECT count(*) ".
                   " FROM " . $this->_tb_supplier_street. 
                   " $where" .
                   " ORDER BY $sort $order ".
                   " LIMIT " . $filter['start'] . ",$size";
        $count = $GLOBALS['db']->getOne($sql);
		
		return $count;
	}
	
    /**
     * 获取店铺商品列表信息
     * @param  integer $suppid 店铺id
     */
    public function get_SupplierGoods($suppid, $num = 10, $start = 0 , $order = 'desc', $sort = 'goods_id' ,$filter="" ){
		
		
		if($filter && in_array($filter,array('is_best','is_new','is_hot'))){
			 $where .= " AND g.".$filter." = 1 ";
		}
		
		if($num){
			$limit = " limit ".$num;
		}else{
			$limit = "";
		}
		
        $sql = "SELECT g.goods_id,g.cat_id, g.goods_name, g.market_price, g.shop_price , g.promote_price, g.promote_start_date, g.promote_end_date, g.goods_thumb  FROM ".$this->_tb_goods." AS g  LEFT JOIN ".$this->_tb_member_price." AS mp  ON mp.goods_id = g.goods_id  AND mp.user_rank = '0' WHERE g.is_on_sale = 1 AND g.is_alone_sale = 1 AND g.is_delete = 0 AND g.supplier_id=".$suppid." order by g.$sort $order" .$limit;
        //$res = $this->_db->selectLimit($sql, $num, $start);
        $lsit = array();
        // while ($rows = $this->_db->fetchRow($res))
        // {
            // $rows['market_p']       = $rows['market_price'];
            // $rows['market_price']   = price_format($rows['market_price']);
            // $rows['shop_price']     = price_format($rows['shop_price']);
            // $rows['promote_price']  = ($rows['promote_price'] > 0) ? price_format($rows['promote_price']) : '';
            // $rows['goods_thumb']    = get_image_path($rows['goods_id'], $rows['goods_thumb'], true);
            // $rows['cat_name']       = $this->_db->GetOne("SELECT cat_name FROM " . $this->_tb_category . " WHERE cat_id='$rows[cat_id]'");
            // $rows['comment_count']       = $this->get_evaluation_sum($rows['goods_id']);

            // $lsit[] = $rows;
        // }
		//echo $sql;
		$list = $this->_db->getAll($sql);
		foreach($list as $k=>$v){
			$list[$k]['market_p']       = $v['market_price'];
            $list[$k]['market_price']   = price_format($v['market_price']);
            $list[$k]['shop_price']     = price_format($v['shop_price']);
            $list[$k]['promote_price']  = ($v['promote_price'] > 0) ? price_format($v['promote_price']) : '';
            $list[$k]['goods_thumb']    = get_image_path($v['goods_id'], $v['goods_thumb'], true);
            $list[$k]['cat_name']       = $this->_db->GetOne("SELECT cat_name FROM " . $this->_tb_category . " WHERE cat_id='$v[cat_id]'");
            $list[$k]['comment_count']       = $this->get_evaluation_sum($v['goods_id']);
		}
		
        return $list;
    }
	
    /**
     * 获取店铺商品列表信息(数量)
     * @param  integer $suppid 店铺id
     */
    public function get_SupplierGoods_count($suppid){
        $sql = "SELECT count(*)  FROM ".$this->_tb_goods." AS g  LEFT JOIN ".$this->_tb_member_price." AS mp  ON mp.goods_id = g.goods_id  AND mp.user_rank = '0' WHERE g.is_on_sale = 1 AND g.is_alone_sale = 1 AND g.is_delete = 0 AND g.supplier_id=".$suppid." order by g.goods_id desc";
        $count = $GLOBALS['db']->getOne($sql);
        return $count;
    }

    /**
     * 获取店铺商品数量
     * @param  integer $suppid 店铺id
     */
    public function get_street_goods_count($suppid){
        $sql = "SELECT g.goods_id, g.goods_name, g.market_price, g.shop_price AS org_price,  IFNULL(mp.user_price, g.shop_price * '1') AS shop_price, g.promote_price, g.shop_price AS shop_p, g.promote_start_date, g.promote_end_date, g.goods_thumb  FROM ".$this->_tb_goods." AS g  LEFT JOIN ".$this->_tb_member_price." AS mp  ON mp.goods_id = g.goods_id  AND mp.user_rank = '0' WHERE g.is_on_sale = 1 AND g.is_alone_sale = 1 AND g.is_delete = 0 AND g.supplier_id=".$suppid." order by g.goods_id desc";
        $goodsInfo = $this->_db->getAll($sql);
        $allnum = count($goodsInfo);
        return $allnum;
    }


    /**
     * 添加关注店铺
     *
     * @access  public
     * @param   int         $user_id            用户ID
     * @param   array       $goods_id           商品ID
     */
    function get_Guanzhu ($supplierid, $userid = 0)
    {
        // 检查是否已经存在于用户的收藏夹
        $sql = "SELECT COUNT(*) FROM " . $this->_tb_supplier_guanzhu . " WHERE userid='$userid' AND supplierid = '$supplierid'";
        if($this->_db->GetOne($sql) > 0)
        {
            $this->_db->query('DELETE FROM ' . $this->_tb_supplier_guanzhu . " WHERE supplierid = '$supplierid' AND userid ='$userid'");
            $result['status'] = 200;
            $result['is_add'] = 0;
            $result['message'] = '已取消关注！';
        }
        else
        {
            $time = time();
            $sql = "INSERT INTO " . $this->_tb_supplier_guanzhu . " (userid, supplierid, addtime)" . "VALUES ('$userid', '$supplierid', '$time')";
            
            if($this->_db->query($sql) === false)
            {
                $result['status'] = 500;
                $result['is_add'] = 0;
                $result['message'] = $this->_db->errorMsg();
            }
            else
            {
                $result['status'] = 200;
                $result['is_add'] = 1;
                $result['message'] = '已关注！';
                
            }
        }
        return $result;
    }

    /**
     * 店铺详情
     * @param  [int] $supplier_id [店铺ID]
     * @param  [int] $user_id     [用户ID 判断是否已关注用]
     */
    public function get_SupplierDetails($supplier_id,$user_id)
    {
        $where = " where status=1 and is_show=1 ";
        $where .= " and supplier_id = '$supplier_id' ";

        $sql = "SELECT supplier_id,supplier_name,supplier_title,content,supplier_desc,logo ".
                   " FROM " . $this->_tb_supplier_street. " $where";
        $arr = $GLOBALS['db']->getAll($sql);
        foreach($arr as $key=>$val){
            //店铺logo
            $arr[$key]['logopath'] = '/'.DATA_DIR.'/supplier/logo/logo_supplier'.$val['supplier_id'].'.jpg';//店铺logo

            $arr[$key]['address'] = "";//地址
            $shopinfo = $GLOBALS['db']->getAll("select code,value from ".$this->_tb_supplier_shop_config." where supplier_id=".$val['supplier_id']." and code in('shop_province','shop_city','shop_address','qq','ww','service_phone','shop_notice')");
            foreach($shopinfo as $k => $v){
                $arr[$key][$v['code']] = $v['value'];
            }

            //所在地
            if(!empty($arr[$key]['shop_address'])){
                $arr[$key]['address'] = $arr[$key]['shop_address'];
            }
            if(!empty($arr[$key]['shop_city'])){
                $arr[$key]['address'] = get_region_info($arr[$key]['shop_city']).$arr[$key]['address'];
                $arr[$key]['city'] = get_region_info($arr[$key]['shop_city']);
            }
            if(!empty($arr[$key]['shop_province'])){
                $arr[$key]['address'] = get_region_info($arr[$key]['shop_province']).$arr[$key]['address'];
            }
            //屏蔽接口不需要显示的参数
            unset($arr[$key]['shop_province']);
            unset($arr[$key]['shop_city']);
            unset($arr[$key]['shop_address']);

            $arr[$key]['address'] = trim($arr[$key]['address'],',');//所在地
            $arr[$key]['is_guanzhu'] = is_guanzhu($val['supplier_id'],$user_id) ? 1 : 0;//是否关注
            
            //店铺动态评分
            $sql1 = "SELECT AVG(comment_rank) FROM " . $this->_tb_comment . " c" . " LEFT JOIN " . $this->_tb_order_info . " o"." ON o.order_id = c.order_id"." WHERE c.status > 0 AND  o.supplier_id = " . $val['supplier_id'];
            $avg_comment = $GLOBALS['db']->getOne($sql1);
            $avg_comment = round($avg_comment,1);       
            $arr[$key]['avg_comment'] = $avg_comment;

            $sql2 = "SELECT AVG(server), AVG(shipping) FROM " . $this->_tb_shop_grade . " s" . " LEFT JOIN " . $this->_tb_order_info . " o"." ON o.order_id = s.order_id"." WHERE s.is_comment > 0 AND  s.server >0 AND o.supplier_id = " . $val['supplier_id'];
            $row = $GLOBALS['db']->getRow($sql2);

            $avg_server = round($row['AVG(server)'],1);
            $avg_shipping = round($row['AVG(shipping)'],1);
            $arr[$key]['avg_server'] = $avg_server;
            $arr[$key]['avg_shipping'] = $avg_shipping;
            //好评
            $sql3 = " SELECT c.comment_rank,s.send,s.shipping FROM ".$this->_tb_shop_grade ." AS s ".
                " LEFT JOIN ". $this->_tb_comment ." AS c ON c.order_id = s.order_id " .
                " LEFT JOIN ". $this->_tb_order_info ." AS o ON o.order_id = s.order_id".
                " WHERE s.is_comment >0 AND  s.server >0 AND o.supplier_id = " . $val['supplier_id'];
            $h = $GLOBALS['db']->getAll($sql3);
            if(!empty($h)){
                $count = 0;
                foreach($h as $k=>$value)
                {
                    $count += array_sum($value);
                }

                $haoping = (($count/3)/count($h))/5*100;
                $arr[$key]['haoping'] = round($haoping,1).'%';
            }else{
                $arr[$key]['haoping'] = '100%';
            }
            

            //店铺中有多少商品
            $goods_list = $this->get_SupplierGoods($val['supplier_id'],4,$order = 'desc', $sort = 'goods_id' ,$filter="");
            $goods_number = $this->get_street_goods_count($val['supplier_id']);
            $arr[$key]['goods_number'] = $goods_number;
            $arr[$key]['goods_list'] = $goods_list;

            //店铺广告
            $flash_file = "flash_data_supplier".$supplier_id.".xml";            
            $flashdb = array();
            if (file_exists(ROOT_PATH . DATA_DIR . '/'.$flash_file))
            {
                
                // 兼容v2.7.0及以前版本
                if (!preg_match_all('/item_url="([^"]+)"\slink="([^"]+)"\stext="([^"]*)"\ssort="([^"]*)"/', file_get_contents(ROOT_PATH . DATA_DIR . '/'.$flash_file), $t, PREG_SET_ORDER))
                {
                    preg_match_all('/item_url="([^"]+)"\slink="([^"]+)"\stext="([^"]*)"/', file_get_contents(ROOT_PATH . DATA_DIR . '/'.$flash_file), $t, PREG_SET_ORDER);
                }

                if (!empty($t))
                {
                    foreach ($t as $k => $val)
                    {
                        $val[4] = isset($val[4]) ? $val[4] : 0;
                        $flashdb[] = array('src'=>$val[1],'url'=>$val[2],'text'=>$val[3],'sort'=>$val[4]);
                    }
                }
            }
            $arr[$key]['playerdb'] = $flashdb;
			
			//店铺广告2
            $ad_file = "flash_data_supplier".$supplier_id."ad.xml";
			$ad = array();
            if (file_exists(ROOT_PATH . DATA_DIR . '/'.$ad_file))
            {
                
                // 兼容v2.7.0及以前版本
                if (!preg_match_all('/item_url="([^"]+)"\slink="([^"]+)"\stext="([^"]*)"\ssort="([^"]*)"/', file_get_contents(ROOT_PATH . DATA_DIR . '/'.$ad_file), $t, PREG_SET_ORDER))
                {
                    preg_match_all('/item_url="([^"]+)"\slink="([^"]+)"\stext="([^"]*)"/', file_get_contents(ROOT_PATH . DATA_DIR . '/'.$ad_file), $t, PREG_SET_ORDER);
                }

                if (!empty($t))
                {
                    foreach ($t as $k => $val)
                    {
                        $val[4] = isset($val[4]) ? $val[4] : 0;
                        $ad[] = array('src'=>$val[1],'url'=>$val[2],'text'=>$val[3],'sort'=>$val[4]);
                    }
                }
            }
            $arr[$key]['ad'] = $ad;
			
			$sql = "SELECT * FROM  ".$GLOBALS['ecs']->table('supplier')." WHERE  `supplier_id` = ".$supplier_id;
			$supplier = $GLOBALS['db']->getRow($sql);
			$arr[$key]['rank'] = $GLOBALS['db']->getOne('SELECT `rank_name`  FROM '.$GLOBALS['ecs']->table('supplier_rank').' WHERE `rank_id` = '.$supplier['rank_id']);
			$arr[$key]['open_time'] =  local_date("Y-m-d H:i:s",$supplier['add_time']);
			$arr[$key]['fensi'] =  $this->get_supplier_fensi_count($supplier_id);
        }
        
		return isset($arr[0])?$arr[0]:array();

    }



	/**
	 * 获取店铺被收藏数量
	 */
	public function get_supplier_fensi_count($suppid=0){
		$suppid = (intval($suppid)>0) ? intval($suppid) : intval($_GET['suppId']);
		$sql = "SELECT count(*) FROM " .$GLOBALS['ecs']->table('supplier_guanzhu') ." WHERE supplierid=$suppid";
		return $GLOBALS['db']->getOne($sql);
	}



    //以下函数还没用到的

    /**
     * 获得分类下的商品
     *
     * @access  public
     * @param   string  $children
     * @return  array
     */
    public function category_get_goods($children, $brand, $min, $max,  $size, $page, $sort, $order)
    {
        $display = $GLOBALS['display'];
        $where = "g.is_on_sale = 1 AND g.is_alone_sale = 1 AND ".
                "g.is_delete = 0 AND ($children)";
        
        if ($brand > 0)
        {
            $where .=  "AND g.brand_id=$brand ";
        }

        if ($min > 0)
        {
            $where .= " AND g.shop_price >= $min ";
        }

        if ($max > 0)
        {
            $where .= " AND g.shop_price <= $max ";
        }


        /* 获得商品列表 */
        $sql = 'SELECT distinct g.goods_id, g.goods_name, g.goods_name_style, g.market_price, g.is_new, g.is_best, g.is_hot, g.shop_price , ' .
                    " g.promote_price, g.goods_type, " .
                    'g.promote_start_date, g.promote_end_date, g.goods_brief, g.goods_thumb ,g.original_img, g.goods_img ' .
                'FROM ' . $this->_tb_supplier_goods_cat . ' AS sgc ' .
                'LEFT JOIN ' . $this->_tb_goods . ' AS g ' .
                    "ON sgc.goods_id = g.goods_id " .
                'LEFT JOIN ' . $this->_tb_member_price . ' AS mp ' .
                    "ON mp.goods_id = g.goods_id " .
                "WHERE $where ORDER BY $sort $order";

        $res = $GLOBALS['db']->selectLimit($sql, $size, ($page - 1) * $size);
        $arr = array();
        while ($row = $GLOBALS['db']->fetchRow($res))
        {
            if ($row['promote_price'] > 0)
            {
                $promote_price = bargain_price($row['promote_price'], $row['promote_start_date'], $row['promote_end_date']);
            }
            else
            {
                $promote_price = 0;
            }

            /* 处理商品水印图片 */
            $watermark_img = '';

            if ($promote_price != 0)
            {
                $watermark_img = "watermark_promote_small";
            }
            elseif ($row['is_new'] != 0)
            {
                $watermark_img = "watermark_new_small";
            }
            elseif ($row['is_best'] != 0)
            {
                $watermark_img = "watermark_best_small";
            }
            elseif ($row['is_hot'] != 0)
            {
                $watermark_img = 'watermark_hot_small';
            }

            if ($watermark_img != '')
            {
                $arr[$row['goods_id']]['watermark_img'] =  $watermark_img;
            }

            $arr[$row['goods_id']]['goods_id']         = $row['goods_id'];
            if($display == 'grid')
            {
                $arr[$row['goods_id']]['goods_name']       = $GLOBALS['_CFG']['goods_name_length'] > 0 ? sub_str($row['goods_name'], $GLOBALS['_CFG']['goods_name_length']) : $row['goods_name'];
            }
            else
            {
                $arr[$row['goods_id']]['goods_name']       = $row['goods_name'];
            }
            $arr[$row['goods_id']]['name']             = $row['goods_name'];
            $arr[$row['goods_id']]['goods_brief']      = $row['goods_brief'];
            $arr[$row['goods_id']]['goods_style_name'] = add_style($row['goods_name'],$row['goods_name_style']);
            $arr[$row['goods_id']]['market_price']     = price_format($row['market_price']);
            $arr[$row['goods_id']]['shop_price']       = price_format($row['shop_price']);
            $arr[$row['goods_id']]['type']             = $row['goods_type'];
            $arr[$row['goods_id']]['promote_price']    = ($promote_price > 0) ? price_format($promote_price) : '';
            $arr[$row['goods_id']]['goods_thumb']      = get_image_path($row['goods_id'], $row['goods_thumb'], true);
            $arr[$row['goods_id']]['goods_img']        = get_image_path($row['goods_id'], $row['goods_img']);
            $arr[$row['goods_id']]['original_img']        = get_image_path($row['goods_id'], $row['original_img']);
            $arr[$row['goods_id']]['url']              = build_uri('goods', array('gid'=>$row['goods_id']), $row['goods_name']);
        }
        return $arr;
    }

    /**
     * 获得分类下的商品总数
     *
     * @access  public
     * @param   string     $cat_id
     * @return  integer
     */
    public function get_cagtegory_goods_count($children, $brand, $min, $max)
    {
        $where = " g.is_on_sale = 1 AND g.is_alone_sale = 1 AND ".
                "g.is_delete = 0 AND ($children)";
        
        if ($brand > 0)
        {
            $where .=  "AND g.brand_id=$brand ";
        }

        if ($min > 0)
        {
            $where .= " AND g.shop_price >= $min ";
        }

        if ($max > 0)
        {
            $where .= " AND g.shop_price <= $max ";
        }
        

        /* 返回商品总数 */
        $sql = 'SELECT count(distinct g.goods_id) FROM ' . $this->_tb_supplier_goods_cat . ' AS sgc LEFT JOIN ' . $this->_tb_goods . ' AS g ' .
                    'ON sgc.goods_id = g.goods_id WHERE '.$where;
        return $GLOBALS['db']->getOne($sql);
    }
    
	/**
     * 获得商品的评论总数
     *
     * @access  public
     * @param   string     $goods_id
     * @return  integer
     */
	function get_evaluation_sum($goods_id)
    {
		$sql = "SELECT count(*) FROM " . $GLOBALS['ecs']->table('comment') . " WHERE status=1 and  comment_type =0 and id_value =".$goods_id ;//status=1表示通过了的评论才算  comment_type =0表示针对商品的评价 
        return $GLOBALS['db']->getOne($sql);
    }

    /**
     * @description 获取商家分类列表
     * @return  array
     */
    public function get_supplierStreet(){
        
        $sql = "SELECT str_id,str_name FROM ".$this->_tb_street_category." WHERE is_show=1 ORDER BY sort_order ASC";
        $result = $GLOBALS['db']->getAll($sql);
        return $result;
    }

}
