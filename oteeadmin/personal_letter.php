<?php
/**
 * 私信列表
 */

define('IN_ECS', true);

require(dirname(__FILE__) . '/includes/init.php');

/*初始化数据交换对象 */
$exc = new exchange($ecs->table("personal_letter"), $db, 'msg_id', 'msg_content');

/*------------------------------------------------------ */
//-- 私信列表
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'list')
{
    /* 取得过滤条件 */
    $filter = array();
    $smarty->assign('ur_here',      '私信列表');
    $smarty->assign('full_page',    1);
    $smarty->assign('filter',       $filter);

    $letter_list = get_letter_list();
    $smarty->assign('letter_list',    $letter_list['arr']);
    $smarty->assign('filter',          $letter_list['filter']);
    $smarty->assign('record_count',    $letter_list['record_count']);
    $smarty->assign('page_count',      $letter_list['page_count']);

    $sort_flag  = sort_flag($letter_list['filter']);
    $smarty->assign($sort_flag['tag'], $sort_flag['img']);

    assign_query_info();
    $smarty->display('letter_list.htm');
}

/*------------------------------------------------------ */
//-- 翻页，排序
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'query')
{
    check_authz_json('personal_letter');

    $letter_list = get_letter_list();

    $smarty->assign('letter_list',    $letter_list['arr']);
    $smarty->assign('filter',          $letter_list['filter']);
    $smarty->assign('record_count',    $letter_list['record_count']);
    $smarty->assign('page_count',      $letter_list['page_count']);

    $sort_flag  = sort_flag($letter_list['filter']);
    $smarty->assign($sort_flag['tag'], $sort_flag['img']);

    make_json_result($smarty->fetch('letter_list.htm'), '',
        array('filter' => $letter_list['filter'], 'page_count' => $letter_list['page_count']));
}

/*------------------------------------------------------ */
//-- 切换是否显示
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'toggle_read')
{
    check_authz_json('personal_letter');

    $id = intval($_POST['id']);
    $val = intval($_POST['val']);

    $exc->edit("is_read = '$val'", $id);

    clear_cache_files();

    make_json_result($val);
}

/*------------------------------------------------------ */
//-- 删除领域
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'remove')
{
    check_authz_json('personal_letter');

    $id = intval($_GET['id']);
    $sql = "SELECT user_id, receive_user_id FROM " . $GLOBALS['ecs']->table('personal_letter') . " WHERE msg_id = '$id'";
    $info = $GLOBALS['db']->getRow($sql);

    $name = $exc->get_name($id);
    if ($exc->drop($id))
    {
        admin_log(addslashes($name),'remove','letter');
        clear_cache_files();
    }

    // 判断是否还有私信，没有的话删除临时表关系
    if (!has_letter($info['user_id'], $info['receive_user_id'])) {
        $GLOBALS['db']->query("DELETE FROM " . $GLOBALS['ecs']->table('personal_letter_temp') . " WHERE (user_id = '$info[user_id]' AND receive_user_id = '$info[receive_user_id]') OR (user_id = '$info[receive_user_id]' AND receive_user_id = '$info[user_id]')");
    }

    $url = 'personal_letter.php?act=query&' . str_replace('act=remove', '', $_SERVER['QUERY_STRING']);

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
                $sql = "SELECT user_id, receive_user_id FROM " . $GLOBALS['ecs']->table('personal_letter') . " WHERE msg_id = '$id'";
                $info = $GLOBALS['db']->getRow($sql);

                if ($exc->drop($id))
                {
                    // 判断是否还有私信，没有的话删除临时表关系
                    if (!has_letter($info['user_id'], $info['receive_user_id'])) {
                        $GLOBALS['db']->query("DELETE FROM " . $GLOBALS['ecs']->table('personal_letter_temp') . " WHERE (user_id = '$info[user_id]' AND receive_user_id = '$info[receive_user_id]') OR (user_id = '$info[receive_user_id]' AND receive_user_id = '$info[user_id]')");
                    }

                    $name = $exc->get_name($id);
                    admin_log(addslashes($name),'remove','letter');
                }
            }

        }

        /* 批量未读 */
        if ($_POST['type'] == 'button_unread')
        {
            check_authz_json('personal_letter');
            if (!isset($_POST['checkboxes']) || !is_array($_POST['checkboxes']))
            {
                sys_msg('您没有选择任何记录', 1);
            }

            foreach ($_POST['checkboxes'] AS $key => $id)
            {
              $exc->edit("is_read = '0'", $id);
            }
        }

        /* 批量已读 */
        if ($_POST['type'] == 'button_read')
        {
            check_authz_json('personal_letter');
            if (!isset($_POST['checkboxes']) || !is_array($_POST['checkboxes']))
            {
                sys_msg('您没有选择任何记录', 1);
            }

            foreach ($_POST['checkboxes'] AS $key => $id)
            {
              $exc->edit("is_read = '1'", $id);
            }
        }
    }

    /* 清除缓存 */
    clear_cache_files();
    $lnk[] = array('text' => '返回私信列表', 'href' => 'personal_letter.php?act=list');
    sys_msg('批量操作成功', 0, $lnk);
}

/* 获得私信列表 */
function get_letter_list()
{
    $result = get_filter();
    if ($result === false)
    {
        $filter = array();
        $filter['keyword']    = empty($_REQUEST['keyword']) ? '' : trim($_REQUEST['keyword']);
        $filter['user_name']    = empty($_REQUEST['user_name']) ? '' : trim($_REQUEST['user_name']);
        $filter['receive_user_name']    = empty($_REQUEST['receive_user_name']) ? '' : trim($_REQUEST['receive_user_name']);
        if (isset($_REQUEST['is_ajax']) && $_REQUEST['is_ajax'] == 1)
        {
            $filter['keyword'] = json_str_iconv($filter['keyword']);
        }
        $filter['sort_by']    = empty($_REQUEST['sort_by']) ? 'a.msg_time' : trim($_REQUEST['sort_by']);
        $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);

        $where = '';
        if (!empty($filter['keyword']))
        {
            $where = " AND a.msg_content LIKE '%" . mysql_like_quote($filter['keyword']) . "%'";
        }
        if (!empty($filter['user_name']))
        {
            $where = " AND b.user_name LIKE '%" . mysql_like_quote($filter['user_name']) . "%'";
        }
        if (!empty($filter['receive_user_name']))
        {
            $where = " AND c.user_name LIKE '%" . mysql_like_quote($filter['receive_user_name']) . "%'";
        }

        /* 私信总数 */
        $sql = "SELECT COUNT(*) FROM " . $GLOBALS['ecs']->table('personal_letter'). " a LEFT JOIN " . $GLOBALS['ecs']->table('users') . " b ON a.user_id = b.user_id LEFT JOIN " . $GLOBALS['ecs']->table('users') . " c ON a.receive_user_id = c.user_id WHERE 1 " . $where;
        $filter['record_count'] = $GLOBALS['db']->getOne($sql);

        $filter = page_and_size($filter);

        /* 获取字体数据 */
        $sql = "SELECT a.*, b.user_name, c.user_name AS receive_user_name FROM " . $GLOBALS['ecs']->table('personal_letter') . " a LEFT JOIN " . $GLOBALS['ecs']->table('users') . " b ON a.user_id = b.user_id LEFT JOIN " . $GLOBALS['ecs']->table('users') . " c ON a.receive_user_id = c.user_id WHERE 1 " . $where . " ORDER by " . $filter['sort_by'] . " " . $filter['sort_order'];

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
        $rows['msg_time'] = local_date('Y-m-d H:i:s', $rows['msg_time']);
        $arr[] = $rows;
    }
    return array('arr' => $arr, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']);
}

/**
 * 判断跟某个用户是否有聊天记录
 */
function has_letter($user_id, $receive_user_id)
{
    $sql = "SELECT COUNT(*) FROM " . $GLOBALS['ecs']->table('personal_letter') . " WHERE (user_id = '$user_id' AND receive_user_id = '$receive_user_id') OR (user_id = '$receive_user_id' AND receive_user_id = '$user_id')";
    $count = $GLOBALS['db']->getOne($sql);
    if ($count > 0) {
        return true;
    } else {
        return false;
    }
}

?>