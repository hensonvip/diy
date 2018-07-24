<?php

/**
 * ECSHOP 字体分类管理程序
 * ============================================================================
 * 版权所有 2005-2011 上海商派网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.ecshop.com；
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * $Author: liubo $
 * $Id: font_type.php 17217 2011-01-19 06:29:08Z liubo $
*/

define('IN_ECS', true);

require(dirname(__FILE__) . '/includes/init.php');
$exc = new exchange($ecs->table("font_type"), $db, 'type_id', 'type_name');
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
    $font_type = font_type_list(0, 0, false);
    $smarty->assign('ur_here',     '字体分类列表');
    $smarty->assign('action_link', array('text' => '添加字体分类', 'href' => 'font_type.php?act=add'));
    $smarty->assign('full_page',   1);
    $smarty->assign('font_type',        $font_type);

    assign_query_info();
    $smarty->display('font_type_list.htm');
}

/*------------------------------------------------------ */
//-- 查询
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'query')
{
    $font_type = font_type_list(0, 0, false);
    $smarty->assign('font_type',        $font_type);

    make_json_result($smarty->fetch('font_type_list.htm'));
}

/*------------------------------------------------------ */
//-- 添加分类
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'add')
{
    /* 权限判断 */
    admin_priv('font_type');

    $smarty->assign('type_select',  font_type_list(0));
    $smarty->assign('ur_here',     '添加字体分类');
    $smarty->assign('action_link', array('text' => '字体分类列表', 'href' => 'font_type.php?act=list'));
    $smarty->assign('form_action', 'insert');

    assign_query_info();
    $smarty->display('font_type_info.htm');
}
elseif ($_REQUEST['act'] == 'insert')
{
    /* 权限判断 */
    admin_priv('font_type');

    $sql = "INSERT INTO ".$ecs->table('font_type')."(type_name, type_short_name, parent_id, sort_order, is_show)
           VALUES ('$_POST[type_name]', '$_POST[type_short_name]', '$_POST[parent_id]', '$_POST[sort_order]', '$_POST[is_show]')";
    $db->query($sql);

    admin_log($_POST['type_name'],'add','font_type');

    $link[0]['text'] = '继续添加字体分类';
    $link[0]['href'] = 'font_type.php?act=add';

    $link[1]['text'] = '返回字体分类列表';
    $link[1]['href'] = 'font_type.php?act=list';
    clear_cache_files();
    sys_msg($_POST['type_name'].'字体分类添加成功',0, $link);
}

/*------------------------------------------------------ */
//-- 编辑分类
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'edit')
{
    /* 权限判断 */
    admin_priv('font_type');

    $sql = "SELECT * FROM " . $ecs->table('font_type'). " WHERE type_id='$_REQUEST[id]'";
    $type = $db->GetRow($sql);

    $smarty->assign('disabled', 1);
    $options    =   font_type_list(0, $type['parent_id'], false);
    $select     =   '';
    $selected   =   $type['parent_id'];
    foreach ($options as $var)
    {
        if ($var['type_id'] == $_REQUEST['id'])
        {
            continue;
        }
        $select .= '<option value="' . $var['type_id'] . '" ';
        $select .= ($selected == $var['type_id']) ? "selected='ture'" : '';
        $select .= '>';
        if ($var['level'] > 0)
        {
            $select .= str_repeat('&nbsp;', $var['level'] * 4);
        }
        $select .= htmlspecialchars($var['type_name']) . '</option>';
    }
    unset($options);
    $smarty->assign('type',         $type);
    $smarty->assign('type_select',  $select);
    $smarty->assign('ur_here',     '字体分类列表');
    $smarty->assign('action_link', array('text' => '字体分类列表', 'href' => 'font_type.php?act=list'));
    $smarty->assign('form_action', 'update');

    assign_query_info();
    $smarty->display('font_type_info.htm');
}
elseif ($_REQUEST['act'] == 'update')
{
    /* 权限判断 */
    admin_priv('font_type');
    /*if(!isset($_POST['parent_id']))
    {
        $_POST['parent_id'] = 0;
    }*/

    /* 检查设定的分类的父分类是否合法 */
    $child_type = font_type_list($_POST['id'], 0, false);
    if (!empty($child_type))
    {
        foreach ($child_type as $child_data)
        {
            $type_id_array[] = $child_data['type_id'];
        }
    }
    if (in_array($_POST['parent_id'], $type_id_array))
    {
        sys_msg(sprintf('分类名 %s 的父分类不能设置成本身或本身的子分类', stripslashes($_POST['type_name'])), 1);
    }

    if ($exc->edit("type_name = '$_POST[type_name]', type_short_name='$_POST[type_short_name]', sort_order='$_POST[sort_order]', is_show = '$_POST[is_show]'",  $_POST['id']))
    {
        $link[0]['text'] = '返回字体分类列表';
        $link[0]['href'] = 'font_type.php?act=list';
        $note = sprintf('字体分类 %s 编辑成功', $_POST['type_name']);
        admin_log($_POST['type_name'], 'edit', 'font_type');
        clear_cache_files();
        sys_msg($note, 0, $link);
    }
    else
    {
        die($db->error());
    }
}



/*------------------------------------------------------ */
//-- 编辑字体分类的排序
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'edit_sort_order')
{
    check_authz_json('font_type');

    $id    = intval($_POST['id']);
    $order = json_str_iconv(trim($_POST['val']));

    /* 检查输入的值是否合法 */
    if (!preg_match("/^[0-9]+$/", $order))
    {
        make_json_error(sprintf('请输入一个整数', $order));
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
//-- 删除分类
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'remove')
{
    check_authz_json('font_type');

    $id = intval($_GET['id']);

    $sql = "SELECT type_name FROM " . $ecs->table('font_type') . " WHERE type_id = '$id'";
    $type_name = $db->getOne($sql);

    $sql = "SELECT COUNT(*) FROM " . $ecs->table('font_type') . " WHERE parent_id = '$id'";
    if ($db->getOne($sql) > 0)
    {
        /* 还有子分类，不能删除 */
        make_json_error('该分类下还有子分类，请先删除其子分类');
    }

    /* 非空的分类不允许删除 */
    $sql = "SELECT COUNT(*) FROM ".$ecs->table('font')." WHERE type_id = '$id'";
    if ($db->getOne($sql) > 0)
    {
        make_json_error(sprintf('分类下还有字体，不允许删除非空分类'));
    }
    else
    {
        $exc->drop($id);
        clear_cache_files();
        admin_log($type_name, 'remove', 'font_type');
    }

    $url = 'font_type.php?act=query&' . str_replace('act=remove', '', $_SERVER['QUERY_STRING']);

    ecs_header("Location: $url\n");
    exit;
}
/*------------------------------------------------------ */
//-- 切换是否显示在导航栏
/*------------------------------------------------------ */

if ($_REQUEST['act'] == 'toggle_is_show')
{
    check_authz_json('font_type');

    $id     = intval($_POST['id']);
    $val    = intval($_POST['val']);

    $exc->edit("is_show = '$val'", $id);
    clear_cache_files();

    make_json_result($val);
}
?>
