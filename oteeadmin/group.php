<?php

/**
 * 拼团
 */

define('IN_ECS', true);
require(dirname(__FILE__) . '/includes/init.php');

$exc = new exchange($ecs->table('group_activity'), $db, 'id', 'goods_name');

/*------------------------------------------------------ */
//-- 活动列表页
/*------------------------------------------------------ */

if ($_REQUEST['act'] == 'list')
{
    /* 检查权限 */
    //admin_priv('group');

    /* 模板赋值 */
    $smarty->assign('full_page',   1);
    $smarty->assign('ur_here',     '拼团活动列表');
    $smarty->assign('action_link', array('href' => 'group.php?act=add', 'text' => '添加拼团活动商品'));

    $list = group_list();//print_r($list);die;

    $smarty->assign('group_list', $list['item']);
    $smarty->assign('filter',       $list['filter']);
    $smarty->assign('record_count', $list['record_count']);
    $smarty->assign('page_count',   $list['page_count']);

    $sort_flag  = sort_flag($list['filter']);

    /* 显示商品列表页面 */
    assign_query_info();
    $smarty->display('group_list.htm');
}

/*------------------------------------------------------ */
//-- 分页、排序、查询
/*------------------------------------------------------ */

elseif ($_REQUEST['act'] == 'query')
{
    $list = group_list();

    $smarty->assign('group_list', $list['item']);
    $smarty->assign('filter',       $list['filter']);
    $smarty->assign('record_count', $list['record_count']);
    $smarty->assign('page_count',   $list['page_count']);

    $sort_flag  = sort_flag($list['filter']);
    $smarty->assign($sort_flag['tag'], $sort_flag['img']);

    make_json_result($smarty->fetch('group_list.htm'), '',
        array('filter' => $list['filter'], 'page_count' => $list['page_count']));
}

/*------------------------------------------------------ */
//-- 删除
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'remove')
{
    //check_authz_json('auction');

    $id = intval($_GET['id']);
    $data = group_info($id);
    if (empty($data))
    {
        make_json_error('您要操作的拼团活动不存在');
    }
    if ($data['join_num'] > 0)
    {
        make_json_error('活动已有人拼团，不能删除！');
    }
    $name = $data['goods_name'];
    $exc->drop($id);

    /* 记日志 */
    admin_log($name, 'remove', 'group');

    /* 清除缓存 */
    clear_cache_files();

    $url = 'group.php?act=query&' . str_replace('act=remove', '', $_SERVER['QUERY_STRING']);

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
        //admin_priv('group');

        $ids = $_POST['checkboxes'];

        if (isset($_POST['drop']))
        {
            /* 查询哪些拼团活动已经有人拼团 */
            $sql = "SELECT DISTINCT group_id FROM " . $ecs->table('group_log') .
                    " WHERE group_id " . db_create_in($ids);
            $ids = array_diff($ids, $db->getCol($sql));
            if (!empty($ids))
            {
                /* 删除记录 */
                $sql = "DELETE FROM " . $ecs->table('group_activity') .
                        " WHERE id " . db_create_in($ids) .
                        " ";
                $db->query($sql);

                /* 记日志 */
                admin_log('', 'batch_remove', 'group');

                /* 清除缓存 */
                clear_cache_files();

                $links[] = array('text' => '返回拼团活动商品列表', 'href' => 'group.php?act=list&' . list_link_postfix());
                sys_msg('批量删除成功', 0, $links);
            }else{
                $links[] = array('text' => '返回拼团活动商品列表', 'href' => 'group.php?act=list&' . list_link_postfix());
                sys_msg('批量删除失败，有些拼团活动已经有人拼团', 0, $links);
            }
            
        }
    }
}


/*------------------------------------------------------ */
//-- 添加、编辑
/*------------------------------------------------------ */

elseif ($_REQUEST['act'] == 'add' || $_REQUEST['act'] == 'edit')
{
    /* 检查权限 */
    //admin_priv('group');

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
            'group_discount' => 0.95,
            'group_num'     => 2,
            'group_day'     => 1,
            'join_num_false'     => 0
        );
    }
    else
    {
        if (empty($_GET['id']))
        {
            sys_msg('invalid param');
        }
        $id = intval($_GET['id']);
        $info = group_info($id, true);
        if (empty($info))
        {
            sys_msg('您要操作的拼团活动不存在');
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
        $smarty->assign('ur_here', '添加拼团活动');
    }
    else
    {
        $smarty->assign('ur_here', '编辑拼团活动');
    }
    $smarty->assign('action_link', list_link($is_add));

    assign_query_info();
    $smarty->display('group_info.htm');
}

/*------------------------------------------------------ */
//-- 添加、编辑后提交
/*------------------------------------------------------ */

elseif ($_REQUEST['act'] == 'insert' || $_REQUEST['act'] == 'update')
{
    /* 检查权限 */
    //admin_priv('group');

    /* 是否添加 */
    $is_add = $_REQUEST['act'] == 'insert';

    /* 检查是否选择了商品 */
    $goods_id = intval($_POST['goods_id']);
    //$product_id = intval($_POST['product_id']);
    if ($goods_id <= 0)
    {
        sys_msg($_LANG['pls_select_goods']);
    }

    $now = gmtime();

	$sql1="select * from  " .$ecs->table('group_activity'). " where goods_id = '$goods_id' and end_time >= '$now' ";
	$row1=$db->getRow($sql1);

	if($row1 && $_REQUEST['act'] != 'update'){
		sys_msg('此商品已经添加为拼团商品，不能重复添加');
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
        'goods_name'    => $goods_name,
        'start_time'    => local_strtotime($_POST['start_time']),
        'end_time'      => local_strtotime($_POST['end_time']),
        'group_discount'    => trim($_POST['group_discount']),
        'group_num'    => trim($_POST['group_num']),
        'group_day'    => trim($_POST['group_day']),
        'join_num_false' => trim($_POST['join_num_false']),
        'add_time'    => $now
    );
    if ($is_add)
    {
        unset($data['id']);
        $db->autoExecute($ecs->table('group_activity'), $data, 'INSERT');
        $data['act_id'] = $db->insert_id();
    }
    else
    {
        $db->autoExecute($ecs->table('group_activity'), $data, 'UPDATE', "id = '$data[id]'");
    }

    /* 记日志 */
    if ($is_add)
    {
        admin_log($goods_name, 'add', 'group');
    }
    else
    {
        admin_log($goods_name, 'edit', 'group');
    }

    /* 清除缓存 */
    clear_cache_files();

    /* 提示信息 */
    if ($is_add)
    {
        $links = array(
            array('href' => 'group.php?act=add', 'text' => '继续添加拼团活动商品'),
            array('href' => 'group.php?act=list', 'text' => '返回拼团活动商品列表')
        );
        sys_msg('添加拼团活动商品成功', 0, $links);
    }
    else
    {
        $links = array(
            array('href' => 'group.php?act=list&' . list_link_postfix(), 'text' => '返回拼团活动商品列表')
        );
        sys_msg('编辑拼团活动商品成功', 0, $links);
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
function group_list()
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
        //$filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);

        $where = " supplier_id=0 ";
        if (!empty($filter['keyword']))
        {
            $where .= " AND goods_name LIKE '%" . mysql_like_quote($filter['keyword']) . "%'";
        }

        $sql = "SELECT COUNT(*) FROM " . $GLOBALS['ecs']->table('group_activity') .
                " WHERE $where";
        $filter['record_count'] = $GLOBALS['db']->getOne($sql);

        /* 分页大小 */
        $filter = page_and_size($filter);

        /* 查询 */
        $sql = "SELECT * ".
                "FROM " . $GLOBALS['ecs']->table('group_activity') .
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
    $href = 'group.php?act=list';
    if (!$is_add)
    {
        $href .= '&' . list_link_postfix();
    }
    if ($text == '')
    {
        $text = '拼团活动列表';
    }

    return array('href' => $href, 'text' => $text);
}

/**
 * 取得活动信息
 * @param   int     $act_id     活动id
 * @return  array
 */
function group_info($id, $config = false)
{
    $sql = "SELECT * FROM " . $GLOBALS['ecs']->table('group_activity') . " WHERE id = '$id'";
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


?>