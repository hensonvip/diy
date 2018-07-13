<?php

/**
 * ECSHOP 图形分类管理程序
 * ============================================================================
 * 版权所有 2005-2011 上海商派网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.ecshop.com；
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * $Author: liubo $
 * $Id: graph_type.php 17217 2011-01-19 06:29:08Z liubo $
*/

define('IN_ECS', true);

require(dirname(__FILE__) . '/includes/init.php');
$exc = new exchange($ecs->table("graph_type"), $db, 'type_id', 'type_name');
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
    $graph_type = graph_type_list();
    $smarty->assign('ur_here',     '图形分类列表');
    $smarty->assign('action_link', array('text' => '添加图形分类', 'href' => 'graph_type.php?act=add'));
    $smarty->assign('full_page',   1);
    $smarty->assign('graph_type',        $graph_type);

    assign_query_info();
    $smarty->display('graph_type_list.htm');
}

/*------------------------------------------------------ */
//-- 查询
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'query')
{
    $graph_type = graph_type_list();
    $smarty->assign('graph_type',        $graph_type);
    make_json_result($smarty->fetch('graph_type_list.htm'));
}

/*------------------------------------------------------ */
//-- 添加分类
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'add')
{
    /* 权限判断 */
    admin_priv('graph_type');

    $smarty->assign('ur_here',     '添加图形分类');
    $smarty->assign('action_link', array('text' => '图形分类列表', 'href' => 'graph_type.php?act=list'));
    $smarty->assign('form_action', 'insert');

    assign_query_info();
    $smarty->display('graph_type_info.htm');
}
elseif ($_REQUEST['act'] == 'insert')
{
    /* 权限判断 */
    admin_priv('graph_type');

    $sql = "INSERT INTO ".$ecs->table('graph_type')."(type_name, sort_order, is_show)
           VALUES ('$_POST[type_name]', '$_POST[sort_order]', '$_POST[is_show]')";
    $db->query($sql);

    admin_log($_POST['type_name'],'add','graph_type');

    $link[0]['text'] = '继续添加图形分类';
    $link[0]['href'] = 'graph_type.php?act=add';

    $link[1]['text'] = '返回图形分类列表';
    $link[1]['href'] = 'graph_type.php?act=list';
    clear_cache_files();
    sys_msg($_POST['type_name'].'图形分类添加成功',0, $link);
}

/*------------------------------------------------------ */
//-- 编辑分类
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'edit')
{
    /* 权限判断 */
    admin_priv('graph_type');

    $sql = "SELECT * FROM " . $ecs->table('graph_type'). " WHERE type_id='$_REQUEST[id]'";
    $type = $db->GetRow($sql);

    $smarty->assign('type',         $type);
    $smarty->assign('ur_here',     '图形分类列表');
    $smarty->assign('action_link', array('text' => '图形分类列表', 'href' => 'graph_type.php?act=list'));
    $smarty->assign('form_action', 'update');

    assign_query_info();
    $smarty->display('graph_type_info.htm');
}
elseif ($_REQUEST['act'] == 'update')
{
    /* 权限判断 */
    admin_priv('graph_type');

    if ($exc->edit("type_name = '$_POST[type_name]', sort_order='$_POST[sort_order]', is_show = '$_POST[is_show]'",  $_POST['id']))
    {
        $link[0]['text'] = '返回图形分类列表';
        $link[0]['href'] = 'graph_type.php?act=list';
        $note = sprintf('图形分类 %s 编辑成功', $_POST['type_name']);
        admin_log($_POST['type_name'], 'edit', 'graph_type');
        clear_cache_files();
        sys_msg($note, 0, $link);
    }
    else
    {
        die($db->error());
    }
}

/*------------------------------------------------------ */
//-- 编辑图形分类的排序
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'edit_sort_order')
{
    check_authz_json('graph_type');

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
//-- 删除图形分类
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'remove')
{
    check_authz_json('graph_type');

    $id = intval($_GET['id']);

    $sql = "SELECT type_name FROM " . $ecs->table('graph_type') . " WHERE type_id = '$id'";
    $type_name = $db->getOne($sql);

    /* 非空的分类不允许删除 */
    $sql = "SELECT COUNT(*) FROM ".$ecs->table('graph')." WHERE type_id = '$id'";
    if ($db->getOne($sql) > 0)
    {
        make_json_error(sprintf('分类下还有字体，不允许删除非空分类'));
    }
    else
    {
        $exc->drop($id);
        clear_cache_files();
        admin_log($type_name, 'remove', 'graph_type');
    }

    $url = 'graph_type.php?act=query&' . str_replace('act=remove', '', $_SERVER['QUERY_STRING']);

    ecs_header("Location: $url\n");
    exit;
}
/*------------------------------------------------------ */
//-- 切换是否显示在导航栏
/*------------------------------------------------------ */

if ($_REQUEST['act'] == 'toggle_is_show')
{
    check_authz_json('graph_type');

    $id     = intval($_POST['id']);
    $val    = intval($_POST['val']);

    $exc->edit("is_show = '$val'", $id);
    clear_cache_files();

    make_json_result($val);
}
?>
