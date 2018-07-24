<?php
/**
 * 意见反馈
 */

define('IN_ECS', true);

require(dirname(__FILE__) . '/includes/init.php');

/*初始化数据交换对象 */
$exc = new exchange($ecs->table("research"), $db, 'research_id', 'content');

/*------------------------------------------------------ */
//-- 意见反馈列表
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'list')
{
    /* 取得过滤条件 */
    $filter = array();
    $smarty->assign('ur_here',      '意见反馈列表');
    $smarty->assign('full_page',    1);
    $smarty->assign('filter',       $filter);

    $research_list = get_research_list();
    $smarty->assign('research_list',    $research_list['arr']);
    $smarty->assign('filter',          $research_list['filter']);
    $smarty->assign('record_count',    $research_list['record_count']);
    $smarty->assign('page_count',      $research_list['page_count']);

    $sort_flag  = sort_flag($research_list['filter']);
    $smarty->assign($sort_flag['tag'], $sort_flag['img']);

    assign_query_info();
    $smarty->display('research_list.htm');
}

/*------------------------------------------------------ */
//-- 翻页，排序
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'query')
{
    check_authz_json('research_report');

    $research_list = get_research_list();

    $smarty->assign('research_list',    $research_list['arr']);
    $smarty->assign('filter',          $research_list['filter']);
    $smarty->assign('record_count',    $research_list['record_count']);
    $smarty->assign('page_count',      $research_list['page_count']);

    $sort_flag  = sort_flag($research_list['filter']);
    $smarty->assign($sort_flag['tag'], $sort_flag['img']);

    make_json_result($smarty->fetch('research_list.htm'), '',
        array('filter' => $research_list['filter'], 'page_count' => $research_list['page_count']));
}

/*------------------------------------------------------ */
//-- 删除意见反馈
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'remove')
{
    check_authz_json('research_report');

    $id = intval($_GET['id']);

    $name = $exc->get_name($id);
    if ($exc->drop($id))
    {
        admin_log(addslashes($name),'remove','research');
        clear_cache_files();
    }

    $url = 'research.php?act=query&' . str_replace('act=remove', '', $_SERVER['QUERY_STRING']);

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
            admin_priv('personal_letter');

            if (!isset($_POST['checkboxes']) || !is_array($_POST['checkboxes']))
            {
                sys_msg('您没有选择任何记录', 1);
            }

            foreach ($_POST['checkboxes'] AS $key => $id)
            {
                if ($exc->drop($id))
                {
                    $name = $exc->get_name($id);
                    admin_log(addslashes($name),'remove','research');
                }
            }

        }
    }

    /* 清除缓存 */
    clear_cache_files();
    $lnk[] = array('text' => '返回意见反馈列表', 'href' => 'research.php?act=list');
    sys_msg('批量操作成功', 0, $lnk);
}

/* 获得意见反馈举报列表 */
function get_research_list()
{
    $result = get_filter();
    if ($result === false)
    {
        $filter = array();
        $filter['keyword']    = empty($_REQUEST['keyword']) ? '' : trim($_REQUEST['keyword']);
        if (isset($_REQUEST['is_ajax']) && $_REQUEST['is_ajax'] == 1)
        {
            $filter['keyword'] = json_str_iconv($filter['keyword']);
        }
        $filter['sort_by']    = empty($_REQUEST['sort_by']) ? 'a.add_time' : trim($_REQUEST['sort_by']);
        $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);

        $where = '';
        if (!empty($filter['keyword']))
        {
            $where .= " AND a.content LIKE '%" . mysql_like_quote($filter['keyword']) . "%'";
        }

        /* 意见反馈总数 */
        $sql = "SELECT COUNT(*) FROM " . $GLOBALS['ecs']->table('research'). " a LEFT JOIN " . $GLOBALS['ecs']->table('users') . " b ON a.user_id = b.user_id WHERE 1 " . $where;
        $filter['record_count'] = $GLOBALS['db']->getOne($sql);

        $filter = page_and_size($filter);

        /* 获取意见反馈数据 */
        $sql = "SELECT a.*, IFNULL(b.user_name, '匿名用户') AS user_name FROM " . $GLOBALS['ecs']->table('research') . " a LEFT JOIN " . $GLOBALS['ecs']->table('users') . " b ON a.user_id = b.user_id WHERE 1 " . $where . " ORDER by " . $filter['sort_by'] . " " . $filter['sort_order'];

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
        $arr[] = $rows;
    }
    return array('arr' => $arr, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']);
}

?>