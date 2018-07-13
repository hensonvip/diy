<?php

/**
 * ECSHOP 管理中心文章处理程序文件
 * ============================================================================
 * 版权所有 2005-2011 上海商派网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.ecshop.com；
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * $Author: liubo $
 * $Id: originality.php 17217 2011-01-19 06:29:08Z liubo $
*/

define('IN_ECS', true);

require(dirname(__FILE__) . '/includes/init.php');

/*初始化数据交换对象 */
$exc   = new exchange($ecs->table("originality"), $db, 'id', 'name');
date_default_timezone_set("Asia/Shanghai");//定义时区，防止时间戳转换误差

/*------------------------------------------------------ */
//-- 文章列表
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'list')
{
    /* 取得过滤条件 */
    $filter = array();
    $smarty->assign('ur_here',      $_LANG['01_originality_list']);
    $smarty->assign('action_link',  array('text' => $_LANG['originality_add'], 'href' => 'originality_list.php?act=add'));
    $smarty->assign('full_page',    1);
    $smarty->assign('filter',       $filter);

    $originality_list = get_originalitylist();
    $smarty->assign('originality_list',$originality_list['arr']);
    $smarty->assign('filter',          $originality_list['filter']);
    $smarty->assign('record_count',    $originality_list['record_count']);
    $smarty->assign('page_count',      $originality_list['page_count']);

    //print_r($originality_list);

    $sort_flag  = sort_flag($originality_list['filter']);
    $smarty->assign($sort_flag['tag'], $sort_flag['img']);

    assign_query_info();
    $smarty->display('originality_list.htm');
}

/*------------------------------------------------------ */
//-- 翻页，排序
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'query')
{
    check_authz_json('originality_list');

    $originality_list = get_originalitylist();

    $smarty->assign('originality_list',    $originality_list['arr']);
    $smarty->assign('filter',          $originality_list['filter']);
    $smarty->assign('record_count',    $originality_list['record_count']);
    $smarty->assign('page_count',      $originality_list['page_count']);

    $sort_flag  = sort_flag($originality_list['filter']);
    $smarty->assign($sort_flag['tag'], $sort_flag['img']);

    make_json_result($smarty->fetch('originality_list.htm'), '',
        array('filter' => $originality_list['filter'], 'page_count' => $originality_list['page_count']));
}

/*------------------------------------------------------ */
//-- 添加比赛
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'add')
{
    /* 权限判断 */
    admin_priv('originality_list');

    /* 创建 html editor */
    create_html_editor('content');

    /*初始化*/
    $originality = array();
    $originality['is_show'] = 1;

    $smarty->assign('originality',     $originality);
    $smarty->assign('ur_here',     $_LANG['originality_add']);
    $smarty->assign('action_link', array('text' => $_LANG['01_originality_list'], 'href' => 'originality_list.php?act=list'));
    $smarty->assign('form_action', 'insert');

    assign_query_info();
    $smarty->display('originality_info.htm');
}

/*------------------------------------------------------ */
//-- 添加文章
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'insert')
{
    /* 权限判断 */
    admin_priv('originality_list');
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $collect_start_time = isset($_POST['collect_start_time']) ? trim($_POST['collect_start_time']) : '';
    $collect_end_time = isset($_POST['collect_end_time']) ? trim($_POST['collect_end_time']) : '';
    $vote_start_time = isset($_POST['vote_start_time']) ? trim($_POST['vote_start_time']) : '';
    $vote_end_time = isset($_POST['vote_end_time']) ? trim($_POST['vote_end_time']) : '';
    $publicity_start_time = isset($_POST['publicity_start_time']) ? trim($_POST['publicity_start_time']) : '';
    if(empty($name)){
        sys_msg("比赛名不能为空");
    }
    if(empty($collect_start_time) || empty($collect_end_time)){
        sys_msg("征集起始时间不能为空");
    }
    if(empty($vote_start_time) || empty($vote_end_time)){
        sys_msg("投票起始时间不能为空");
    }
    if(empty($publicity_start_time)){
        sys_msg("公示时间不能为空");
    }
    /*检查是否重复*/
    $is_only = $exc->is_only('name', $name);

    if (!$is_only)
    {
        sys_msg(sprintf($_LANG['name_exist'], stripslashes($name)), 1);
    }


    /*插入数据*/
    $add_time = gmtime();

    $sql = "INSERT INTO ".$ecs->table('originality')."(name, content, collect_start_time, collect_end_time,vote_start_time,vote_end_time,publicity_start_time, sort_order, add_time, is_show) ".
            "VALUES ('$name', '$_POST[content]', ".strtotime($collect_start_time).", ".strtotime($collect_end_time).",".strtotime($vote_start_time).",".strtotime($vote_end_time).",".strtotime($publicity_start_time).", "."'$_POST[sort_order]', '$add_time','$_POST[is_show]')";
    $db->query($sql);


    $link[0]['text'] = $_LANG['continue_add'];
    $link[0]['href'] = 'originality.php?act=add';

    $link[1]['text'] = $_LANG['back_list'];
    $link[1]['href'] = 'originality.php?act=list';

    admin_log($_POST['name'],'add','originality');

    clear_cache_files(); // 清除相关的缓存文件

    sys_msg($_LANG['originalityadd_succeed'],0, $link);
}

/*------------------------------------------------------ */
//-- 编辑
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'edit')
{
    /* 权限判断 */
    admin_priv('originality_list');

    /* 取数据 */
    $sql = "SELECT * FROM " .$ecs->table('originality'). " WHERE id='$_REQUEST[id]'";
    $originality = $db->GetRow($sql);
    $originality['collect_end_time'] = date($GLOBALS['_CFG']['time_format'], $originality['collect_end_time']);
    $originality['collect_start_time'] = date($GLOBALS['_CFG']['time_format'], $originality['collect_start_time']);
    $originality['vote_start_time'] = date($GLOBALS['_CFG']['time_format'], $originality['vote_start_time']);
    $originality['vote_end_time'] = date($GLOBALS['_CFG']['time_format'], $originality['vote_end_time']);
    $originality['publicity_start_time'] = date($GLOBALS['_CFG']['time_format'], $originality['publicity_start_time']);
    /* 创建 html editor */
    create_html_editor('content',htmlspecialchars($originality['content']));

    $smarty->assign('originality',     $originality);
    $smarty->assign('ur_here',     $_LANG['originality_edit']);
    $smarty->assign('action_link', array('text' => $_LANG['01_originality_list'], 'href' => 'originality_list.php?act=list&' . list_link_postfix()));
    $smarty->assign('form_action', 'update');

    assign_query_info();
    $smarty->display('originality_info.htm');
}

if ($_REQUEST['act'] =='update')
{
    /* 权限判断 */
    admin_priv('originality_list');


    $id = intval($_POST['id']);
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $collect_start_time = isset($_POST['collect_start_time']) ? trim($_POST['collect_start_time']) : '';
    $collect_end_time = isset($_POST['collect_end_time']) ? trim($_POST['collect_end_time']) : '';
    $vote_start_time = isset($_POST['vote_start_time']) ? trim($_POST['vote_start_time']) : '';
    $vote_end_time = isset($_POST['vote_end_time']) ? trim($_POST['vote_end_time']) : '';
    $publicity_start_time = isset($_POST['publicity_start_time']) ? trim($_POST['publicity_start_time']) : '';
    //print_r(strtotime($collect_start_time));exit;
    if(empty($name)){
        sys_msg("比赛名不能为空");
    }
    if(empty($collect_start_time) || empty($collect_end_time)){
        sys_msg("征集起始时间不能为空");
    }
    if(empty($vote_start_time) || empty($vote_end_time)){
        sys_msg("投票起始时间不能为空");
    }
    if(empty($publicity_start_time)){
        sys_msg("公示时间不能为空");
    }
    /*检查文章名是否相同*/
    $is_only = $exc->is_only('name', $name, $id);

    if (!$is_only)
    {
        sys_msg(sprintf($_LANG['name_exist'], stripslashes($name)), 1);
    }

    if ($exc->edit("name='$name', collect_start_time=".strtotime($collect_start_time).", collect_end_time=".strtotime($collect_end_time).", vote_start_time=".strtotime($vote_start_time).",vote_end_time=".strtotime($vote_end_time).",publicity_start_time=".strtotime($publicity_start_time).",is_show='$_POST[is_show]', content='$_POST[content]', sort_order='$_POST[sort_order]'", $id))
    {
        $link[0]['text'] = $_LANG['back_list'];
        $link[0]['href'] = 'originality_list.php?act=list&' . list_link_postfix();

        $note = sprintf($_LANG['originalityedit_succeed'], stripslashes($name));
        admin_log($name, 'edit', 'originality');

        clear_cache_files();
        sys_msg($note, 0, $link);
    }
    else
    {
        die($db->error());
    }
}

/*------------------------------------------------------ */
//-- 编辑比赛活动名
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'edit_name')
{
    check_authz_json('originality_list');

    $id    = intval($_POST['id']);
    $name = json_str_iconv(trim($_POST['val']));

    /* 检查文章标题是否重复 */
    if ($exc->num("name", $name, $id) != 0)
    {
        make_json_error(sprintf($_LANG['name_exist'], $name));
    }
    else
    {
        if ($exc->edit("name = '$name'", $id))
        {
            clear_cache_files();
            admin_log($name, 'edit', 'originality');
            make_json_result(stripslashes($name));
        }
        else
        {
            make_json_error($db->error());
        }
    }
}

/*------------------------------------------------------ */
//-- 切换是否显示
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'toggle_show')
{
    check_authz_json('originality_list');

    $id     = intval($_POST['id']);
    $val    = intval($_POST['val']);

    $exc->edit("is_show = '$val'", $id);
    clear_cache_files();

    make_json_result($val);
}





/*------------------------------------------------------ */
//-- 删除比赛活动
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'remove')
{
    check_authz_json('originality_list');

    $id = intval($_GET['id']);

    $name = $exc->get_name($id);
    if ($exc->drop($id))
    {
        $db->query("DELETE FROM " . $ecs->table('originality') . " WHERE id = $id");
        
        admin_log(addslashes($name),'remove','originality');
        clear_cache_files();
    }

    $url = 'originality_list.php?act=query&' . str_replace('act=remove', '', $_SERVER['QUERY_STRING']);

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
        // if ($_POST['type'] == 'button_remove')
        // {
        //     admin_priv('originality_manage');

        //     if (!isset($_POST['checkboxes']) || !is_array($_POST['checkboxes']))
        //     {
        //         sys_msg($_LANG['no_select_food'], 1);
        //     }

        //     /* 删除原来的文件 */
        //     // $sql = "SELECT file_url FROM " . $ecs->table('article') .
        //     //         " WHERE article_id " . db_create_in(join(',', $_POST['checkboxes'])) .
        //     //         " AND file_url <> ''";

        //     // $res = $db->query($sql);
        //     // while ($row = $db->fetchRow($res))
        //     // {
        //     //     $old_url = $row['file_url'];
        //     //     if (strpos($old_url, 'http://') === false && strpos($old_url, 'https://') === false)
        //     //     {
        //     //         @unlink(ROOT_PATH . $old_url);
        //     //     }
        //     // }

        //     foreach ($_POST['checkboxes'] AS $key => $id)
        //     {
        //         if ($exc->drop($id))
        //         {
        //             $name = $exc->get_name($id,'food_name');
        //             admin_log(addslashes($name),'remove','food');
        //         }
        //     }

        // }

        /* 批量隐藏 */
        if ($_POST['type'] == 'button_hide')
        {
            check_authz_json('originality_list');
            if (!isset($_POST['checkboxes']) || !is_array($_POST['checkboxes']))
            {
                sys_msg($_LANG['no_select_originality'], 1);
            }

            foreach ($_POST['checkboxes'] AS $key => $id)
            {
              $exc->edit("is_show = '0'", $id);
            }
        }

        /* 批量显示 */
        if ($_POST['type'] == 'button_show')
        {
            check_authz_json('originality_manage');
            if (!isset($_POST['checkboxes']) || !is_array($_POST['checkboxes']))
            {
                sys_msg($_LANG['no_select_originality'], 1);
            }

            foreach ($_POST['checkboxes'] AS $key => $id)
            {
              $exc->edit("is_show = '1'", $id);
            }
        }
    }

    /* 清除缓存 */
    clear_cache_files();
    $lnk[] = array('text' => $_LANG['back_list'], 'href' => 'originality.php?act=list');
    sys_msg($_LANG['batch_handle_ok'], 0, $lnk);
}



/* 获得比赛列表 */
function get_originalitylist()
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
        $filter['sort_by']    = empty($_REQUEST['sort_by']) ? 'id' : trim($_REQUEST['sort_by']);
        $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);

        $where = '';
        if (!empty($filter['keyword']))
        {
            $where = " AND name LIKE '%" . mysql_like_quote($filter['keyword']) . "%'";
        }

        /* 文章总数 */
        $sql = 'SELECT COUNT(*) FROM ' .$GLOBALS['ecs']->table('originality').
               'WHERE 1 ' .$where;
        $filter['record_count'] = $GLOBALS['db']->getOne($sql);

        $filter = page_and_size($filter);

        /* 获取文章数据 */
        $sql = 'SELECT * '.
               'FROM ' .$GLOBALS['ecs']->table('originality'). 
               'WHERE 1 ' .$where. ' ORDER by '.$filter['sort_by'].' '.$filter['sort_order'];

        $filter['keyword'] = stripslashes($filter['keyword']);
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
        $rows['collect_start_time'] = date($GLOBALS['_CFG']['time_format'], $rows['collect_start_time']);
        $rows['collect_end_time'] = date($GLOBALS['_CFG']['time_format'], $rows['collect_end_time']);
        $rows['vote_start_time'] = date($GLOBALS['_CFG']['time_format'], $rows['vote_start_time']);
        $rows['vote_end_time'] = date($GLOBALS['_CFG']['time_format'], $rows['vote_end_time']);
        $rows['publicity_start_time'] = date($GLOBALS['_CFG']['time_format'], $rows['publicity_start_time']);
        $rows['date'] = date($GLOBALS['_CFG']['time_format'], $rows['add_time']);

        $arr[] = $rows;
    }
    return array('arr' => $arr, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']);
}

?>