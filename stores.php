<?php

/**
 * ECSHOP 专题前台
 * ============================================================================
 * 版权所有 2005-2011 上海商派网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.ecshop.com；
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * ---------------------------------------------
 */

define('IN_ECS', true);

require(dirname(__FILE__) . '/includes/init.php');
require(dirname(__FILE__) . '/includes/lib_order.php');

if ((DEBUG_MODE & 2) != 2)
{
    $smarty->caching = true;
}


//线上红包
$sql = "SELECT * FROM " .$GLOBALS['ecs']->table('bonus_type') .
    " WHERE send_type = '4'";
$row = $GLOBALS['db']->GetAll($sql);
$time=time();
date_default_timezone_set('PRC');
$smarty->assign('time',$time);
$smarty->assign('online_bonus',$row);

//判断 弹框登陆 验证码是否显示
$captcha = intval($_CFG['captcha']);
if(($captcha & CAPTCHA_LOGIN) && (! ($captcha & CAPTCHA_LOGIN_FAIL) || (($captcha & CAPTCHA_LOGIN_FAIL) && $_SESSION['login_fail'] > 2)) && gd_version() > 0)
{
    $GLOBALS['smarty']->assign('enabled_captcha', 1);
    $GLOBALS['smarty']->assign('rand', mt_rand());
}


$tpl = 'stores.dwt';

$cache_id = sprintf('%X', crc32($cat_id . '-' . $display . '-' . $sort  .'-' . $order  .'-' . $page . '-' . $size . '-' . $_SESSION['user_rank'] . '-' .
	    $_CFG['lang'] .'-'. $brand. '-' . $price_max . '-' .$price_min . '-' . $filter_attr_str . '-' . $filter));

$filter['id']               = empty($_REQUEST['id']) ? 0 : intval($_REQUEST['id']);
$filter['keywords']         = isset($_REQUEST['keywords']) ? trim(addslashes(htmlspecialchars($_REQUEST['keywords']))) : '';
$filter['sort_by']          = empty($_REQUEST['sort_by']) ? 'sort_order' : trim($_REQUEST['sort_by']);
$filter['sort_order']       = empty($_REQUEST['sort_order']) ? 'ASC' : trim($_REQUEST['sort_order']);
$filter['page'] = empty($_REQUEST['page']) || (intval($_REQUEST['page']) <= 0) ? 1 : intval($_REQUEST['page']);

$cache_id = sprintf('%X', crc32(date('ymd' . '-' . $filter)));

if (!$smarty->is_cached($tpl, $cache_id))
{
	//店铺分类
	$cats = get_all_category();

	
	//店铺列表
	$shop_list = get_all_supplier();
	
	//推荐分类中的店铺
	$tuijian = get_tuijian_shop($cats['tuijian']);


    /* 模板赋值 */
    assign_template();
    $position = assign_ur_here(0, $GLOBALS["_LANG"]["stores"]);
    $smarty->assign('page_title',       $position['title']);       // 页面标题
    $smarty->assign('ur_here',          $position['ur_here'] . '> ' . $topic['title']);     // 当前位置
    $smarty->assign('helps',            get_shop_help()); // 网店帮助
    $smarty->assign('all',   	$cats['all']);
    $smarty->assign('tuijian',       $tuijian);
    
    $smarty->assign('logopath',		'/'.DATA_DIR.'/supplier/logo/');
    $smarty->assign('shops_list',   $shop_list['shops']);
    $smarty->assign('filter',       $shop_list['filter']);
    $smarty->assign('record_count', $shop_list['record_count']);
    $smarty->assign('page_count',   $shop_list['page_count']);
    
    $page = (isset($_REQUEST['page'])) ? intval($_REQUEST['page']) : 1;

    $start_array = range(1,$page);
    $end_array   = range($page,$shop_list['page_count']);
    if($page-5>0){
    	$smarty->assign('start',$page-3);
    	$start_array = range($page,$page-2);
    }
    if($shop_list['page_count'] - $page > 5){
    	$smarty->assign('end',$page+3);
    	$end_array   = range($page,$page+2);
    }
    $page_array  = array_merge($start_array,$end_array);
    sort($page_array);
    $smarty->assign('page_array',	array_unique($page_array));
	assign_dynamic('stores');

}
///* 显示模板 */

$smarty->display($tpl, $cache_id);

/**
 * 获取所有店铺分类
 */
function get_all_category(){
	$sql = "select * from ".$GLOBALS['ecs']->table('street_category')." where is_show=1 order by sort_order";
	$all = $GLOBALS['db']->getAll($sql);
	$ret1 = $ret = array();
	foreach($all as $key => $val){
		$val['url'] = build_uri('stores', array('cid'=>$val['str_id']), $val['str_name']);
		if($val['is_groom']>0){
			$ret[$val['str_id']] = $val;
		}
		$ret1[$val['str_id']] = $val;
	}
	return array('all'=>$ret1,'tuijian'=>$ret);
}

/*
 * 获取推荐类中相关店铺
 * @param array $tuijian 分类信息
 */
function get_tuijian_shop($tuijian){
	$keys = array_keys($tuijian);
	$types = implode(',',$keys);
	if(empty($types)){
		return array();
	}
	$sql = "select * from ".$GLOBALS['ecs']->table('supplier_street')." where supplier_type in($types) and is_groom=1 and status=1 order by sort_order";
	$all = $GLOBALS['db']->getAll($sql);
	foreach($all as $k => $v){
		$tuijian[$v['supplier_type']]['shoplist'][$v['supplier_id']] = $v;
	}
	return $tuijian;
}

/**
 * 获取店铺店铺街中的店铺
 */
function get_all_supplier(){
	global $tpl;
	$is_search = 0;//是否是搜索过来的
	$filter['id']               = empty($_REQUEST['id']) ? 0 : intval($_REQUEST['id']);
	$filter['keywords']         = isset($_REQUEST['keywords']) ? trim(addslashes(htmlspecialchars($_REQUEST['keywords']))) : '';
	$filter['sort_by']          = empty($_REQUEST['sort_by']) ? 'sort_order' : trim($_REQUEST['sort_by']);
    $filter['sort_order']       = empty($_REQUEST['sort_order']) ? 'ASC' : trim($_REQUEST['sort_order']);
	/* 分页大小 */
    $filter['page'] = empty($_REQUEST['page']) || (intval($_REQUEST['page']) <= 0) ? 1 : intval($_REQUEST['page']);
    if (isset($_REQUEST['page_size']) && intval($_REQUEST['page_size']) > 0)
    {
        $filter['page_size'] = intval($_REQUEST['page_size']);
    }elseif (isset($_COOKIE['ECSCP']['page_size']) && intval($_COOKIE['ECSCP']['page_size']) > 0)
    {
        $filter['page_size'] = intval($_COOKIE['ECSCP']['page_size']);
    }else{
    	$filter['page_size'] = 13;
    }
    $filter['start']       = ($filter['page'] - 1) * $filter['page_size'];
	
	$where = " where status=1 and is_show=1 ";
	if($filter['id']){
		$where .= ' and supplier_type='.$filter['id'];
	}
	if($filter['keywords'] && $filter['keywords'] != '请输入关键词'){
		$is_search = 1;
		$tpl = 'search_store.dwt';
		$GLOBALS['smarty']->assign('search_keywords',   stripslashes(htmlspecialchars_decode($_REQUEST['keywords'])));
		$where .= " and supplier_id in(SELECT DISTINCT supplier_id
				FROM ".$GLOBALS['ecs']->table('supplier_shop_config')." AS ssc
				WHERE (
				code = 'shop_name'
				AND value LIKE '%".$filter['keywords']."%'
				)
				OR (
				code = 'shop_keywords'
				AND value LIKE '%".$filter['keywords']."%'
				))";
	}
	$GLOBALS['smarty']->assign('issearch',$is_search);
	
	/* 记录总数 */
     $sql = "SELECT COUNT(*) FROM " .$GLOBALS['ecs']->table('supplier_street'). " $where";
     $filter['record_count'] = $GLOBALS['db']->getOne($sql);
     $filter['page_count']     = $filter['record_count'] > 0 ? ceil($filter['record_count'] / $filter['page_size']) : 1;
	
	$sql = "SELECT * ".
	           " FROM " . $GLOBALS['ecs']->table('supplier_street'). 
	           " $where" .
	           " ORDER BY $filter[sort_by] $filter[sort_order] ".
	           " LIMIT " . $filter['start'] . ",$filter[page_size]";
	$arr = $GLOBALS['db']->getAll($sql);
	foreach($arr as $key=>$val){
		$arr[$key]['address'] = "";//地址
		$shopinfo = $GLOBALS['db']->getAll("select code,value from ".$GLOBALS['ecs']->table('supplier_shop_config')." where supplier_id=".$val['supplier_id']." and code in('shop_closed','shop_name','shop_keywords','shop_province','shop_city','shop_address','qq','ww')");
		foreach($shopinfo as $k => $v){
			if($is_search){
				$v['value'] =  str_replace($filter['keywords'],"<font color=red>".$filter['keywords']."</font>",$v['value']);
			}
			
			$arr[$key][$v['code']] = $v['value'];
		}

		//所在地
		if(!empty($arr[$key]['shop_address'])){
			$arr[$key]['address'] = ','.$arr[$key]['shop_address'];
		}
		if(!empty($arr[$key]['shop_city'])){
			$arr[$key]['address'] = ','.get_region_info($arr[$key]['shop_city']).$arr[$key]['address'];
		}
		if(!empty($arr[$key]['shop_province'])){
			$arr[$key]['address'] = get_region_info($arr[$key]['shop_province']).$arr[$key]['address'];
		}
		$arr[$key]['address'] = trim($arr[$key]['address'],',');
		$arr[$key]['is_guanzhu'] = is_guanzhu($val['supplier_id'],$_SESSION['user_id'] ? $_SESSION['user_id'] : 0);
		//店铺中有多少商品
		$goodsInfo = get_street_goods_info($val['supplier_id']);
		$arr[$key]['goods_number'] = $goodsInfo['num'];
		$arr[$key]['goods_info'] = $goodsInfo['info'];

		//店铺动态评分
		$sql1 = "SELECT AVG(comment_rank) FROM " . $GLOBALS['ecs']->table('comment') . " c" . " LEFT JOIN " . $GLOBALS['ecs']->table('order_info') . " o"." ON o.order_id = c.order_id"." WHERE c.status > 0 AND  o.supplier_id = " . $val['supplier_id'];
		$avg_comment = $GLOBALS['db']->getOne($sql1);
		$avg_comment = round($avg_comment,1);		
		$arr[$key]['avg_comment'] = $avg_comment;

		$sql2 = "SELECT AVG(server), AVG(shipping) FROM " . $GLOBALS['ecs']->table('shop_grade') . " s" . " LEFT JOIN " . $GLOBALS['ecs']->table('order_info') . " o"." ON o.order_id = s.order_id"." WHERE s.is_comment > 0 AND  s.server >0 AND o.supplier_id = " . $val['supplier_id'];
		$row = $GLOBALS['db']->getRow($sql2);

		$avg_server = round($row['AVG(server)'],1);
		$avg_shipping = round($row['AVG(shipping)'],1);
		$arr[$key]['avg_server'] = $avg_server;
		$arr[$key]['avg_shipping'] = $avg_shipping;

	}
    return array('shops' => $arr, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']);
}

function get_street_goods_info($suppid){
	global $db,$ecs;

	$sql = "SELECT g.goods_id, g.goods_name, g.goods_name_style, g.click_count, g.goods_number, g.market_price,  g.is_new, g.is_best, g.is_hot, g.shop_price AS org_price,  IFNULL(mp.user_price, g.shop_price * '1') AS shop_price, g.promote_price,  IF(g.promote_price != ''  AND g.promote_start_date < 1439592730 AND g.promote_end_date > 1439592730, g.promote_price, shop_price)  AS shop_p, g.goods_type,  g.promote_start_date, g.promote_end_date, g.goods_brief, g.goods_thumb, g.goods_img  FROM ".$ecs->table('goods')." AS g  LEFT JOIN ".$ecs->table('member_price')." AS mp  ON mp.goods_id = g.goods_id  AND mp.user_rank = '0' WHERE g.is_on_sale = 1 AND g.is_alone_sale = 1 AND g.is_delete = 0 AND g.supplier_id=".$suppid." order by g.goods_id desc";


	$goodsInfo = $db->getAll($sql);

	$allnum = count($goodsInfo);
	if($allnum > 0){
		if($allnum > 4){
			array_splice($goodsInfo, 4);
		}
		foreach($goodsInfo as $key=>$row){
			$goodsInfo[$key]['shop_price']       = price_format($row['shop_price']);
			$goodsInfo[$key]['promote_price']    = ($row['promote_price'] > 0) ? price_format($row['promote_price']) : '';
			$goodsInfo[$key]['goods_thumb']      = get_image_path($row['goods_id'], $row['goods_thumb'], true);
			$goodsInfo[$key]['url']              = build_uri('goods', array('gid'=>$row['goods_id']), $row['goods_name']);
		}
	}
	return array('num'=>$allnum,'info'=>$goodsInfo);
}

?>