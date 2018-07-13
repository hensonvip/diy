<?php

/**
 * ECSHOP 举报原因管理程序
 * ============================================================================
 * 版权所有 2005-2011 上海商派网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.ecshop.com；
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * $Author: liubo $
 * $Id: report_reason.php 17217 2011-01-19 06:29:08Z liubo $
*/

define('IN_ECS', true);

require(dirname(__FILE__) . '/includes/init.php');
$exc = new exchange($ecs->table("report_reason"), $db, 'reason_id', 'content');
/* act操作项的初始化 */
$_REQUEST['act'] = trim($_REQUEST['act']);
if (empty($_REQUEST['act']))
{
    $_REQUEST['act'] = 'list';
}

/*------------------------------------------------------ */
//-- 分类列表
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'list')
{
    $reason_list = report_reason_list();
    $smarty->assign('ur_here',     '举报原因列表');
    $smarty->assign('action_link', array('text' => '添加原因', 'href' => 'report_reason.php?act=add'));
    $smarty->assign('full_page',   1);
    $smarty->assign('reason_list',        $reason_list);

    assign_query_info();
    $smarty->display('report_reason_list.htm');
}

/*------------------------------------------------------ */
//-- 查询
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'query')
{
    $reason_list = report_reason_list();
    $smarty->assign('reason_list',        $reason_list);
    make_json_result($smarty->fetch('report_reason_list.htm'));
}

/*------------------------------------------------------ */
//-- 添加分类
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'add')
{
    /* 权限判断 */
    admin_priv('report_reason');

    $smarty->assign('ur_here',     '添加原因');
    $smarty->assign('action_link', array('text' => '举报原因列表', 'href' => 'report_reason.php?act=list'));
    $smarty->assign('form_action', 'insert');

    assign_query_info();
    $smarty->display('report_reason_info.htm');
}
elseif ($_REQUEST['act'] == 'insert')
{
    /* 权限判断 */
    admin_priv('report_reason');

    $sql = "INSERT INTO ".$ecs->table('report_reason')."(content, sort_order, is_show)
           VALUES ('$_POST[content]', '$_POST[sort_order]', '$_POST[is_show]')";
    $db->query($sql);

    admin_log($_POST['content'],'add','report_reason');

    $link[0]['text'] = '继续添加原因';
    $link[0]['href'] = 'report_reason.php?act=add';

    $link[1]['text'] = '返回举报原因列表';
    $link[1]['href'] = 'report_reason.php?act=list';
    clear_cache_files();
    sys_msg($_POST['content'].'原因添加成功',0, $link);
}

/*------------------------------------------------------ */
//-- 编辑分类
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'edit')
{
    /* 权限判断 */
    admin_priv('report_reason');

    $sql = "SELECT * FROM " . $ecs->table('report_reason'). " WHERE reason_id='$_REQUEST[id]'";
    $reason = $db->GetRow($sql);

    $smarty->assign('reason',         $reason);
    $smarty->assign('ur_here',     '举报原因列表');
    $smarty->assign('action_link', array('text' => '举报原因列表', 'href' => 'report_reason.php?act=list'));
    $smarty->assign('form_action', 'update');

    assign_query_info();
    $smarty->display('report_reason_info.htm');
}
elseif ($_REQUEST['act'] == 'update')
{
    /* 权限判断 */
    admin_priv('report_reason');

    if ($exc->edit("content = '$_POST[content]', sort_order='$_POST[sort_order]', is_show = '$_POST[is_show]'",  $_POST['id']))
    {
        $link[0]['text'] = '返回举报原因列表';
        $link[0]['href'] = 'report_reason.php?act=list';
        $note = sprintf('原因 %s 编辑成功', $_POST['content']);
        admin_log($_POST['content'], 'edit', 'report_reason');
        clear_cache_files();
        sys_msg($note, 0, $link);
    }
    else
    {
        die($db->error());
    }
}

/*------------------------------------------------------ */
//-- 编辑原因的排序
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'edit_sort_order')
{
    check_authz_json('report_reason');

    $id    = intval($_POST['id']);
    $order = json_str_iconv(trim($_POST['val']));

    /* 检查输入的值是否合法 */
    if (!preg_match("/^[0-9]+$/", $order))
    {
        make_json_error(sprintf($_LANG['enter_int'], $order));
    }
    else
    {
        if ($exc->edit("sort_order = '$order'", $id))
        {
            clear_cache_files();
            make_json_result(stripslashes($order));
        }
        else
        {
            make_json_error($db->error());
        }
    }
}

/*------------------------------------------------------ */
//-- 删除原因
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'remove')
{
    check_authz_json('report_reason');

    $id = intval($_GET['id']);

    $sql = "SELECT content FROM " . $ecs->table('report_reason') . " WHERE reason_id = '$id'";
    $content = $db->getOne($sql);

    $exc->drop($id);
    clear_cache_files();
    admin_log($content, 'remove', 'report_reason');

    $url = 'report_reason.php?act=query&' . str_replace('act=remove', '', $_SERVER['QUERY_STRING']);

    ecs_header("Location: $url\n");
    exit;
}
/*------------------------------------------------------ */
//-- 切换是否显示
/*------------------------------------------------------ */

if ($_REQUEST['act'] == 'toggle_is_show')
{
    check_authz_json('report_reason');

    $id     = intval($_POST['id']);
    $val    = intval($_POST['val']);

    $exc->edit("is_show = '$val'", $id);
    clear_cache_files();

    make_json_result($val);
}

/**
 * 获取原因
 */
function report_reason_list() {
    $sql = "SELECT * from ". $GLOBALS['ecs']->table('report_reason') ."  ORDER BY sort_order ASC, reason_id DESC";
    $reason_list = $GLOBALS['db'] ->getAll($sql);
    return $reason_list;
}
?>
