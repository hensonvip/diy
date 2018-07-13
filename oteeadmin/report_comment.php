<?php
/**
 * 商品举报列表
 */

define('IN_ECS', true);

require(dirname(__FILE__) . '/includes/init.php');

/*初始化数据交换对象 */
$exc = new exchange($ecs->table("report_comment"), $db, 'report_cid', 'report_cid');

/*------------------------------------------------------ */
//-- 商品举报列表
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'list')
{
    /* 取得过滤条件 */
    $filter = array();
    $smarty->assign('ur_here',      '商品举报列表');
    $smarty->assign('full_page',    1);
    $smarty->assign('filter',       $filter);

    $report_list = get_goods_report_list();
    $smarty->assign('report_list',    $report_list['arr']);
    $smarty->assign('filter',          $report_list['filter']);
    $smarty->assign('record_count',    $report_list['record_count']);
    $smarty->assign('page_count',      $report_list['page_count']);

    $sort_flag  = sort_flag($report_list['filter']);
    $smarty->assign($sort_flag['tag'], $sort_flag['img']);

    assign_query_info();
    $smarty->display('report_comment_list.htm');
}

/*------------------------------------------------------ */
//-- 翻页，排序
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'query')
{
    check_authz_json('report_comment');

    $report_list = get_goods_report_list();

    $smarty->assign('report_list',    $report_list['arr']);
    $smarty->assign('filter',          $report_list['filter']);
    $smarty->assign('record_count',    $report_list['record_count']);
    $smarty->assign('page_count',      $report_list['page_count']);

    $sort_flag  = sort_flag($report_list['filter']);
    $smarty->assign($sort_flag['tag'], $sort_flag['img']);

    make_json_result($smarty->fetch('goods_report_list.htm'), '',
        array('filter' => $report_list['filter'], 'page_count' => $report_list['page_count']));
}

/*------------------------------------------------------ */
//-- 删除商品
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'remove')
{
    check_authz_json('report_comment');

    $id = intval($_GET['id']);

    $name = $exc->get_name($id);
    if ($exc->drop($id))
    {
        admin_log(addslashes($name),'remove','report_comment');
        clear_cache_files();
    }

    $url = 'report_comment.php?act=query&' . str_replace('act=remove', '', $_SERVER['QUERY_STRING']);

    ecs_header("Location: $url\n");
    exit;
}

/*------------------------------------------------------ */
//-- 批量操作
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'batch')
{
    /* 批量删除 */
    if (isset($_POST['type']))
    {
        if ($_POST['type'] == 'button_remove')
        {
            admin_priv('report_comment');

            if (!isset($_POST['checkboxes']) || !is_array($_POST['checkboxes']))
            {
                sys_msg('您没有选择任何记录', 1);
            }

            foreach ($_POST['checkboxes'] AS $key => $id)
            {
                if ($exc->drop($id))
                {
                    $name = $exc->get_name($id);
                    admin_log(addslashes($name),'remove','report_comment');
                }
            }

        }
    }

    /* 清除缓存 */
    clear_cache_files();
    $lnk[] = array('text' => '返回举报列表', 'href' => 'report_comment.php?act=list');
    sys_msg('批量操作成功', 0, $lnk);
}

/* 获得举报列表 */
function get_goods_report_list()
{
    $result = get_filter();
    if ($result === false)
    {
        $filter = array();
        $filter['keyword']    = empty($_REQUEST['keyword']) ? '' : trim($_REQUEST['keyword']);//商品名称
        $filter['user_name']    = empty($_REQUEST['user_name']) ? '' : trim($_REQUEST['user_name']);//举报人
        $filter['designer']    = empty($_REQUEST['designer']) ? '' : trim($_REQUEST['designer']);//设计师
        if (isset($_REQUEST['is_ajax']) && $_REQUEST['is_ajax'] == 1)
        {
            $filter['keyword'] = json_str_iconv($filter['keyword']);
        }
        $filter['sort_by']    = empty($_REQUEST['sort_by']) ? 'a.add_time' : trim($_REQUEST['sort_by']);
        $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);

        $where = '';
        /*if (!empty($filter['keyword']))
        {
            $where .= " AND a.goods_name LIKE '%" . mysql_like_quote($filter['keyword']) . "%'";
        }*/
        if (!empty($filter['user_name']))
        {
            $where .= " AND b.user_name LIKE '%" . mysql_like_quote($filter['user_name']) . "%'";
        }
        /*if (!empty($filter['designer']))
        {
            $where .= " AND c.user_name LIKE '%" . mysql_like_quote($filter['designer']) . "%'";
        }*/

        /* 总数 */
        $sql = "SELECT COUNT(*) FROM " . $GLOBALS['ecs']->table('report_comment'). "  WHERE 1 " . $where;
        $filter['record_count'] = $GLOBALS['db']->getOne($sql);

        $filter = page_and_size($filter);

        /* 获取数据 */
        $sql = "SELECT a.*, b.user_name, c.user_name as be_user_name FROM " . $GLOBALS['ecs']->table('report_comment') . " a LEFT JOIN " . $GLOBALS['ecs']->table('users') . " b ON a.user_id = b.user_id LEFT JOIN " . $GLOBALS['ecs']->table('users') . " c ON a.be_user_id = c.user_id WHERE 1 " . $where . " ORDER by " . $filter['sort_by'] . " " . $filter['sort_order'];

        set_filter($filter, $sql);
    }
    else
    {
        $sql    = $result['sql'];
        $filter = $result['filter'];
    }
    $arr = array();
    $res = $GLOBALS['db']->selectLimit($sql, $filter['page_size'], $filter['start']);

    while ($rows = $GLOBALS['db']->fetchRow($res))
    {
        $rows['add_time'] = local_date('Y-m-d H:i:s', $rows['add_time']);
        $rows['comment'] = base64_decode($rows['comment']);
        $arr[] = $rows;
    }
    return array('arr' => $arr, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']);
}

?>