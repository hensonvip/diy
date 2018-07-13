<?php

/**
 * 砍价、我·砍、砍、砍
 */

define('IN_ECS', true);
require(dirname(__FILE__) . '/includes/init.php');

$exc = new exchange($ecs->table('bargain_activity'), $db, 'id', 'goods_name');

/*------------------------------------------------------ */
//-- 活动列表页
/*------------------------------------------------------ */

if ($_REQUEST['act'] == 'list')
{
    /* 检查权限 */
    //admin_priv('bargain');

    /* 模板赋值 */
    $smarty->assign('full_page',   1);
    $smarty->assign('ur_here',     '砍价活动列表');
    $smarty->assign('action_link', array('href' => 'bargain.php?act=add', 'text' => '添加砍价活动商品'));

    $list = bargain_list();//print_r($list);die;

    $smarty->assign('bargain_list', $list['item']);
    $smarty->assign('filter',       $list['filter']);
    $smarty->assign('record_count', $list['record_count']);
    $smarty->assign('page_count',   $list['page_count']);

    $sort_flag  = sort_flag($list['filter']);

    /* 显示商品列表页面 */
    assign_query_info();
    $smarty->display('bargain_list.htm');
}

/*------------------------------------------------------ */
//-- 分页、排序、查询
/*------------------------------------------------------ */

elseif ($_REQUEST['act'] == 'query')
{
    $list = bargain_list();

    $smarty->assign('bargain_list', $list['item']);
    $smarty->assign('filter',       $list['filter']);
    $smarty->assign('record_count', $list['record_count']);
    $smarty->assign('page_count',   $list['page_count']);

    $sort_flag  = sort_flag($list['filter']);
    $smarty->assign($sort_flag['tag'], $sort_flag['img']);

    make_json_result($smarty->fetch('bargain_list.htm'), '',
        array('filter' => $list['filter'], 'page_count' => $list['page_count']));
}

/*------------------------------------------------------ */
//-- 分页、排序、查询
/*------------------------------------------------------ */

elseif ($_REQUEST['act'] == 'query_log')
{
    $act_id = intval($_REQUEST['act_id']);
    if($act_id <= 0){
        exit();
    }
    $list = bargain_log($act_id);

    $smarty->assign('bargain_log', $list['item']);
    $smarty->assign('filter',       $list['filter']);
    $smarty->assign('record_count', $list['record_count']);
    $smarty->assign('page_count',   $list['page_count']);

    $sort_flag  = sort_flag($list['filter']);
    $smarty->assign($sort_flag['tag'], $sort_flag['img']);

    make_json_result($smarty->fetch('bargain_log.htm'), '',
        array('filter' => $list['filter'], 'page_count' => $list['page_count']));
}

/*------------------------------------------------------ */
//-- 删除
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'remove')
{
    //check_authz_json('auction');

    $id = intval($_GET['id']);
    $data = bargain_info($id);
    if (empty($data))
    {
        make_json_error('您要操作的砍价活动不存在');
    }
    if ($data['bargain_num'] > 0)
    {
        make_json_error('活动已有人砍价，不能删除！');
    }
    $name = $data['goods_name'];
    $exc->drop($id);

    /* 记日志 */
    admin_log($name, 'remove', 'bargain');

    /* 清除缓存 */
    clear_cache_files();

    $url = 'bargain.php?act=query&' . str_replace('act=remove', '', $_SERVER['QUERY_STRING']);

    ecs_header("Location: $url\n");
    exit;
}

/*------------------------------------------------------ */
//-- 批量操作
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'batch')
{
    /* 取得要操作的记录编号 */
    if (empty($_POST['checkboxes']))
    {
        sys_msg('没有选择记录');
    }
    else
    {
        /* 检查权限 */
        //admin_priv('bargain');

        $ids = $_POST['checkboxes'];

        if (isset($_POST['drop']))
        {
            /* 查询哪些砍价活动已经有人砍价 */
            $sql = "SELECT DISTINCT bargain_id FROM " . $ecs->table('bargain_log') .
                    " WHERE bargain_id " . db_create_in($ids);
            $ids = array_diff($ids, $db->getCol($sql));
            if (!empty($ids))
            {
                /* 删除记录 */
                $sql = "DELETE FROM " . $ecs->table('bargain_activity') .
                        " WHERE id " . db_create_in($ids) .
                        " ";
                $db->query($sql);

                /* 记日志 */
                admin_log('', 'batch_remove', 'bargain');

                /* 清除缓存 */
                clear_cache_files();

                $links[] = array('text' => '返回砍价活动商品列表', 'href' => 'bargain.php?act=list&' . list_link_postfix());
                sys_msg('批量删除成功', 0, $links);
            }else{
                $links[] = array('text' => '返回砍价活动商品列表', 'href' => 'bargain.php?act=list&' . list_link_postfix());
                sys_msg('批量删除失败，有些砍价活动已经有人砍价', 0, $links);
            }
            
        }
    }
}

/*------------------------------------------------------ */
//-- 查看砍价记录
/*------------------------------------------------------ */

elseif ($_REQUEST['act'] == 'view_log')
{
    /* 检查权限 */
    //admin_priv('bargain');

    /* 参数 */
    if (empty($_GET['id']))
    {
        sys_msg('invalid param');
    }
    $id = intval($_GET['id']);
    $info = bargain_info($id);
    if (empty($info))
    {
        sys_msg('活动不存在！');
    }
    $smarty->assign('info', bargain_info($id));

    /* 模板赋值 */
    $smarty->assign('ur_here',     '砍价活动列表');
    $smarty->assign('action_link', array('href' => 'bargain.php?act=list', 'text' => '砍价活动列表'));
    //$smarty->assign('action_link', array('href' => 'bargain.php?act=add', 'text' => '添加砍价活动商品'));

    /* 砍价记录 */
    $list = bargain_log($id);//print_r($list);die;

    $smarty->assign('bargain_log', $list['item']);
    $smarty->assign('filter',       $list['filter']);
    $smarty->assign('record_count', $list['record_count']);
    $smarty->assign('page_count',   $list['page_count']);
    $smarty->assign('full_page',    1);

    $sort_flag  = sort_flag($list['filter']);

    /* 显示商品列表页面 */
    assign_query_info();

    $smarty->display('bargain_log.htm');
}

/*------------------------------------------------------ */
//-- 添加、编辑
/*------------------------------------------------------ */

elseif ($_REQUEST['act'] == 'add' || $_REQUEST['act'] == 'edit')
{
    /* 检查权限 */
    //admin_priv('bargain');

    /* 是否添加 */
    $is_add = $_REQUEST['act'] == 'add';
    $smarty->assign('form_action', $is_add ? 'insert' : 'update');

    /* 初始化、取得活动信息 */
    if ($is_add)
    {
        $info = array(
            'id'        => 0,
            'goods_id'      => '',
            'product_id'    => '',
            'goods_id'      => 0,
            'goods_name'    => '请搜索并选择商品',
            'start_time'    => '',
            'end_time'      => '',
            'shop_price'    => 0,
            'low_price'     => 0,
            'min_price'     => 0,
            'max_price'     => 0
        );
    }
    else
    {
        if (empty($_GET['id']))
        {
            sys_msg('invalid param');
        }
        $id = intval($_GET['id']);
        $info = bargain_info($id, true);
        if (empty($info))
        {
            sys_msg('您要操作的砍价活动不存在');
        }
    }
    $smarty->assign('info', $info);

    /* 赋值时间控件的语言 */
    $smarty->assign('cfg_lang', $_CFG['lang']);

    /* 商品货品表 */
    $smarty->assign('good_products_select', get_good_products_select($info['goods_id']));

    /* 显示模板 */
    if ($is_add)
    {
        $smarty->assign('ur_here', '添加砍价活动');
    }
    else
    {
        $smarty->assign('ur_here', '编辑砍价活动');
    }
    $smarty->assign('action_link', list_link($is_add));
    assign_query_info();
    $smarty->display('bargain_info.htm');
}

/*------------------------------------------------------ */
//-- 添加、编辑后提交
/*------------------------------------------------------ */

elseif ($_REQUEST['act'] == 'insert' || $_REQUEST['act'] == 'update')
{
    /* 检查权限 */
    //admin_priv('bargain');

    /* 是否添加 */
    $is_add = $_REQUEST['act'] == 'insert';

    /* 检查是否选择了商品 */
    $goods_id = intval($_POST['goods_id']);
    $product_id = intval($_POST['product_id']);
    if ($goods_id <= 0)
    {
        sys_msg($_LANG['pls_select_goods']);
    }

    $now = gmtime();

    $sql = "select promote_end_date from  " .$ecs->table('goods'). " where goods_id = '$goods_id' ";
    $row = $db->getRow($sql);
    if($row['promote_end_date'] > 0 && $row['promote_end_date'] > $now){
        sys_msg('此商品正在进行限时促销活动中，暂不能添加砍价活动！');
    }

	$sql = "select * from  " .$ecs->table('bargain_activity'). " where goods_id = '$goods_id' and product_id = '$product_id' and end_time >= '$now' ";
	$row = $db->getRow($sql);

	if($row && $_REQUEST['act'] != 'update'){
		sys_msg('此商品已经添加为砍价商品，不能重复添加');
	}
    $sql = "SELECT goods_name FROM " . $ecs->table('goods') . " WHERE goods_id = '$goods_id'";
    $row = $db->getRow($sql);
    if (empty($row))
    {
        sys_msg('对不起，指定的商品不存在');
    }
    $goods_name = $row['goods_name'];

    /* 提交值 */
    $data = array(
        'id'        => intval($_POST['id']),
        'goods_id'      => $goods_id,
        'product_id'    => $product_id,
        'goods_name'    => $goods_name,
        'start_time'    => local_strtotime($_POST['start_time']),
        'end_time'      => local_strtotime($_POST['end_time']),
        'shop_price'    => trim($_POST['shop_price']),
        'low_price'    => trim($_POST['low_price']),
        'min_price'    => trim($_POST['min_price']),
        'max_price'    => trim($_POST['max_price']),
        'add_time'    => gmtime()
    );
    if ($is_add)
    {
        unset($data['id']);
        $db->autoExecute($ecs->table('bargain_activity'), $data, 'INSERT');
        $data['act_id'] = $db->insert_id();
    }
    else
    {
        $db->autoExecute($ecs->table('bargain_activity'), $data, 'UPDATE', "id = '$data[id]'");
    }

    /* 记日志 */
    if ($is_add)
    {
        admin_log($goods_name, 'add', 'bargain');
    }
    else
    {
        admin_log($goods_name, 'edit', 'bargain');
    }

    /* 清除缓存 */
    clear_cache_files();

    /* 提示信息 */
    if ($is_add)
    {
        $links = array(
            array('href' => 'bargain.php?act=add', 'text' => '继续添加砍价活动商品'),
            array('href' => 'bargain.php?act=list', 'text' => '返回砍价活动商品列表')
        );
        sys_msg('添加砍价活动商品成功', 0, $links);
    }
    else
    {
        $links = array(
            array('href' => 'bargain.php?act=list&' . list_link_postfix(), 'text' => '返回砍价活动商品列表')
        );
        sys_msg('编辑砍价活动商品成功', 0, $links);
    }
}


/*------------------------------------------------------ */
//-- 搜索商品
/*------------------------------------------------------ */

elseif ($_REQUEST['act'] == 'search_goods')
{
    check_authz_json('auction');

    include_once(ROOT_PATH . 'includes/cls_json.php');

    $json   = new JSON;
    $filter = $json->decode($_GET['JSON']);
    $arr['goods']    = get_goods_list($filter);

    if (!empty($arr['goods'][0]['goods_id']))
    {
        $arr['products'] = get_good_products($arr['goods'][0]['goods_id']);
    }

    make_json_result($arr);
}

/*------------------------------------------------------ */
//-- 搜索货品
/*------------------------------------------------------ */

elseif ($_REQUEST['act'] == 'search_products')
{
    include_once(ROOT_PATH . 'includes/cls_json.php');
    $json = new JSON;

    $filters = $json->decode($_GET['JSON']);

    if (!empty($filters->goods_id))
    {
        $arr['products'] = get_good_products($filters->goods_id);
    }

    make_json_result($arr);
}

/*
 * 取得活动列表
 * @return   array
 */
function bargain_list()
{
    $result = get_filter();
    if ($result === false)
    {
        /* 过滤条件 */
        $filter['keyword']    = empty($_REQUEST['keyword']) ? '' : trim($_REQUEST['keyword']);
        if (isset($_REQUEST['is_ajax']) && $_REQUEST['is_ajax'] == 1)
        {
            $filter['keyword'] = json_str_iconv($filter['keyword']);
        }
        $filter['sort_by']    = empty($_REQUEST['sort_by']) ? 'id' : trim($_REQUEST['sort_by']);
        $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);

        $where = " supplier_id=0 ";
        if (!empty($filter['keyword']))
        {
            $where .= " AND goods_name LIKE '%" . mysql_like_quote($filter['keyword']) . "%'";
        }

        $sql = "SELECT COUNT(*) FROM " . $GLOBALS['ecs']->table('bargain_activity') .
                " WHERE $where";
        $filter['record_count'] = $GLOBALS['db']->getOne($sql);

        /* 分页大小 */
        $filter = page_and_size($filter);

        /* 查询 */
        $sql = "SELECT * ".
                "FROM " . $GLOBALS['ecs']->table('bargain_activity') .
                " WHERE $where ".
                " ORDER BY $filter[sort_by] $filter[sort_order] ".
                " LIMIT ". $filter['start'] .", $filter[page_size]";

        $filter['keyword'] = stripslashes($filter['keyword']);
        set_filter($filter, $sql);
    }
    else
    {
        $sql    = $result['sql'];
        $filter = $result['filter'];
    }
    $res = $GLOBALS['db']->query($sql);

    $list = array();
    while ($row = $GLOBALS['db']->fetchRow($res))
    {
        $arr = $row;

        $arr['start_time']  = local_date('Y-m-d H:i:s', $arr['start_time']);
        $arr['end_time']    = local_date('Y-m-d H:i:s', $arr['end_time']);

        //获取做砍价活动的商品属性值
        $goods_attr_data = $GLOBALS['db']->getRow("SELECT goods_attr,product_number FROM " . $GLOBALS['ecs']->table('products') . " WHERE product_id = '$arr[product_id]'");
        $product_id_arr = isset($goods_attr_data['goods_attr']) ? explode('|',$goods_attr_data['goods_attr']) : '';
        $attr_name = '';
        if($product_id_arr){
            foreach ($product_id_arr as $k => $v) {
                $attr_name .=  $GLOBALS['db']->getOne("SELECT attr_value FROM " . $GLOBALS['ecs']->table('goods_attr') . " WHERE goods_attr_id = '$v'");
                $attr_name .= '、';
            }
            $attr_name = trim($attr_name,'、');
        }
        if(!empty($attr_name)){
            $arr['goods_name'] = $arr['goods_name'].'（'.$attr_name.'）';//更改商品显示名称，加上属性值
        }else{
            $arr['goods_name'] = $arr['goods_name'];
        }

        $list[] = $arr;
    }
    $arr = array('item' => $list, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']);

    return $arr;
}

/**
 * 列表链接
 * @param   bool    $is_add     是否添加（插入）
 * @param   string  $text       文字
 * @return  array('href' => $href, 'text' => $text)
 */
function list_link($is_add = true, $text = '')
{
    $href = 'bargain.php?act=list';
    if (!$is_add)
    {
        $href .= '&' . list_link_postfix();
    }
    if ($text == '')
    {
        $text = '砍价活动列表';
    }

    return array('href' => $href, 'text' => $text);
}

/**
 * 取得活动信息
 * @param   int     $act_id     活动id
 * @return  array
 */
function bargain_info($id, $config = false)
{
    $sql = "SELECT * FROM " . $GLOBALS['ecs']->table('bargain_activity') . " WHERE id = '$id'";
    $info = $GLOBALS['db']->getRow($sql);

    if ($config == true)
    {

        $info['start_time'] = local_date('Y-m-d H:i:s', $info['start_time']);
        $info['end_time'] = local_date('Y-m-d H:i:s', $info['end_time']);
    }
    else
    {
        $info['start_time'] = local_date($GLOBALS['_CFG']['time_format'], $info['start_time']);
        $info['end_time'] = local_date($GLOBALS['_CFG']['time_format'], $info['end_time']);
    }

    return $info;
}

/**
 * 取得砍价活动砍价记录
 * @param   int     $act_id     活动id
 * @return  array
 */
function bargain_log($act_id)
{
    $result = get_filter();
    if ($result === false)
    {
        // $filter['act']    = 'query_log';
        $filter['act_id']    = isset($_REQUEST['act_id']) ? intval($_REQUEST['act_id']) : $act_id;

        $filter['sort_by']    = empty($_REQUEST['sort_by']) ? 'add_time' : trim($_REQUEST['sort_by']);
        $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);

        $sql = "SELECT COUNT(*) FROM " . $GLOBALS['ecs']->table('bargain_log') .
                " where bargain_id = '$act_id' and user_id = help_user_id";
        $filter['record_count'] = $GLOBALS['db']->getOne($sql);

        /* 分页大小 */
        $filter = page_and_size($filter);

        /* 查询 */
        $sql = "SELECT * ".
                "FROM " . $GLOBALS['ecs']->table('bargain_log') .
                " where bargain_id = '$act_id' and user_id = help_user_id ".
                " ORDER BY $filter[sort_by] $filter[sort_order] ".
                " LIMIT ". $filter['start'] .", $filter[page_size]";

        $filter['keyword'] = stripslashes($filter['keyword']);
        set_filter($filter, $sql);
    }
    else
    {
        $sql    = $result['sql'];
        $filter = $result['filter'];
    }
    $res = $GLOBALS['db']->query($sql);

    $list = array();
    while ($row = $GLOBALS['db']->fetchRow($res))
    {
        //用户
        $user_data = $GLOBALS['db']->getRow("SELECT headimg,user_name,sex " . " FROM " . $GLOBALS['ecs']->table('users') . " WHERE user_id = '$row[user_id]' ");
        $row['user_name'] = $user_data['user_name'];
        $sex  = !empty($user_data['sex']) ? $user_data['sex'] : 0;//性别
        $row['headimg'] = !empty($user_data['headimg']) ? str_replace("./../","",$user_data['headimg']) : 'data/default/sex'.$sex.'.png';//头像
        
        $shop_price = $GLOBALS['db']->getOne("SELECT shop_price " . " FROM " . $GLOBALS['ecs']->table('bargain_activity') . " WHERE id = '$row[bargain_id]' ");
        $row['bargain_price'] = $shop_price - $row['now_price'];//总砍价

        $row['format_bargain_price'] = price_format($row['bargain_price']);
        $row['format_now_price'] = price_format($row['now_price']);
        $row['format_add_time']  = local_date('Y-m-d H:i:s',$row['add_time']);

        $list[] = $row;
    }
    $arr = array('item' => $list, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']);

    return $arr;

}


?>