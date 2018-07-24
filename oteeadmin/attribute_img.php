<?php

/**
 * diy商品属性组合图片
 * ============================================================================
 * * 版权所有 2005-2012 上海商派网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.ecshop.com；
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * $Author: liubo $
 * $Id: attribute.php 17217 2011-01-19 06:29:08Z liubo $
*/

define('IN_ECS', true);

require(dirname(__FILE__) . '/includes/init.php');
require_once(ROOT_PATH . 'includes/cls_image.php');

/* 允许上传的文件类型 */
$allow_file_types = '|GIF|JPG|PNG|';

/* act操作项的初始化 */
$_REQUEST['act'] = trim($_REQUEST['act']);
if (empty($_REQUEST['act']))
{
    $_REQUEST['act'] = 'list';
}

/*------------------------------------------------------ */
//-- 属性列表
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'list')
{
    $cat_id = isset($_GET['goods_type']) ? intval($_GET['goods_type']) : 0;

    $smarty->assign('ur_here',          '属性图片列表');
    $smarty->assign('action_link',      array('href' => 'attribute_img.php?act=add&goods_type='.$cat_id , 'text' => '添加属性图片'));
    $smarty->assign('full_page',        1);

    $list = get_attrimglist();

    $smarty->assign('attr_img_list',    $list['item']);
    $smarty->assign('cat_id',    $cat_id);
    $smarty->assign('filter',       $list['filter']);
    $smarty->assign('record_count', $list['record_count']);
    $smarty->assign('page_count',   $list['page_count']);

    $sort_flag  = sort_flag($list['filter']);
    $smarty->assign($sort_flag['tag'], $sort_flag['img']);

    /* 显示模板 */
    assign_query_info();
    $smarty->display('attribute_img_list.htm');
}

/*------------------------------------------------------ */
//-- 排序、翻页
/*------------------------------------------------------ */

elseif ($_REQUEST['act'] == 'query')
{
    $list = get_attrimglist();

    $smarty->assign('attr_img_list',    $list['item']);
    $smarty->assign('filter',       $list['filter']);
    $smarty->assign('record_count', $list['record_count']);
    $smarty->assign('page_count',   $list['page_count']);

    $sort_flag  = sort_flag($list['filter']);
    $smarty->assign($sort_flag['tag'], $sort_flag['img']);

    make_json_result($smarty->fetch('attribute_img_list.htm'), '',
        array('filter' => $list['filter'], 'page_count' => $list['page_count']));
}

/*------------------------------------------------------ */
//-- 添加/编辑属性图片
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'add' || $_REQUEST['act'] == 'edit')
{
    /* 检查权限 */
    admin_priv('attr_manage');

    /* 添加还是编辑的标识 */
    if ($_REQUEST['act'] == 'add') {
        $is_add = true;
    } else {
        $is_add = false;
    }
    $smarty->assign('form_act', $is_add ? 'insert' : 'update');

    $cat_id = isset($_GET['goods_type']) ? intval($_GET['goods_type']) : 0;
    $attr_list = get_attrlist($cat_id);

    /* 取得属性列表信息 */
    if (!$is_add)
    {
        // 编辑
        $sql = "SELECT * FROM " . $ecs->table('attribute_img') . " WHERE img_id = '$_REQUEST[img_id]'";
        $attr_img = $db->getRow($sql);
        $smarty->assign('attr_img', $attr_img);
    }

    $smarty->assign('attr_list', $attr_list);
    $smarty->assign('cat_id', $cat_id);

    /* 模板赋值 */
    $smarty->assign('ur_here', $is_add ? '添加属性图片' : '编辑属性图片');
    $smarty->assign('action_link', array('href' => 'attribute_img.php?act=list', 'text' => '属性组合图片'));

    /* 显示模板 */
    assign_query_info();
    $smarty->display('attribute_img_info.htm');
}

/*------------------------------------------------------ */
//-- 插入/更新属性
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'insert' || $_REQUEST['act'] == 'update')
{
    /* 检查权限 */
    admin_priv('attr_manage');

    /* 插入还是更新的标识 */
    $is_insert = $_REQUEST['act'] == 'insert';

    $cat_id = intval($_REQUEST['cat_id']);

    /* 获取属性列表信息 */
    $attr_list = get_attrlist($cat_id);
    $attr_group = array();
    if ($is_insert) {
        foreach ($attr_list as $key => $value) {
            if ($_POST[$value['attr_form_name']]) {
                $attr_group[] = trim($_POST[$value['attr_form_name']]);
            } else {
                sys_msg('请选择属性！');
            }
        }
    }
    if (!$attr_group) {
        $attr_group = $_POST['attr_group'];
    } else {
        $attr_group = implode($attr_group, ',');
    }

    if ($is_insert) {
        $sql = "SELECT COUNT(8) FROM " . $ecs->table('attribute_img') . " WHERE attr_group = '$attr_group' AND cat_id = '$cat_id'";
        if ($db->getOne($sql)) {
            sys_msg('该属性组合已存在！');
        }
    }

    /* 取得上传图片地址 */
    $file_url = '';
    if ((isset($_FILES['file']['error']) && $_FILES['file']['error'] == 0) || (!isset($_FILES['file']['error']) && isset($_FILES['file']['tmp_name']) && $_FILES['file']['tmp_name'] != 'none'))
    {
        // 检查文件格式
        if (!check_file_type($_FILES['file']['tmp_name'], $_FILES['file']['name'], $allow_file_types))
        {
            sys_msg('上传文件格式不正确！');
        }

        // 复制文件
        $res = upload_attr_file($_FILES['file']);

        if ($res != false)
        {
            $file_url = $res;
        }
    }

    if (empty($file_url))
    {
        $file_url = $_POST['file_url'];
    }

    if (empty($file_url)) {
        sys_msg('请上传属性组合图片！');
    }

    /* 取得属性信息 */
    $attr = array(
        'cat_id' => $_POST['cat_id'],
        'attr_group' => $attr_group,
        'file_url' => $file_url,
    );

    /* 入库、记录日志、提示信息 */
    if ($is_insert)
    {
        $db->autoExecute($ecs->table('attribute_img'), $attr, 'INSERT');
        $insert_id=$db->insert_id();
        admin_log($attr['attr_group'], 'add', 'attr_group_img');
        $links = array(
            array('text' => '添加下一个属性图片', 'href' => '?act=add&goods_type=' . $_POST['cat_id']),
            array('text' => '返回属性图片列表', 'href' => '??act=list&goods_type='.$_POST['cat_id'].''),
        );
    }
    else
    {
        $db->autoExecute($ecs->table('attribute_img'), $attr, 'UPDATE', "img_id = '$_POST[img_id]'");
        admin_log($attr_group, 'edit', 'attr_group_img');
        $links = array(
            array('text' => $_LANG['back_list'], 'href' => '?act=list&goods_type='.$_POST['cat_id'].''),
        );
    }

    $msg_attr_hunuo_com = $is_insert ?   '添加属性 [%s] 成功。'  : '编辑属性 [%s] 成功。';
    sys_msg(sprintf($msg_attr_hunuo_com, $attr_group), 0, $links);

}

/*------------------------------------------------------ */
//-- 删除属性组合图片
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'remove')
{
    check_authz_json('attr_manage');

    $id = intval($_GET['id']);

    $db->query("DELETE FROM " .$ecs->table('attribute_img'). " WHERE img_id='$id'");

    $url = 'attribute_img.php?act=query&' . str_replace('act=remove', '', $_SERVER['QUERY_STRING']);

    ecs_header("Location: $url\n");
    exit;
}

/**
 * 获取属性列表
 */
function get_attrlist($cat_id) {
    $sql = "SELECT * FROM " . $GLOBALS['ecs']->table('attribute') . " WHERE cat_id = '$cat_id' AND is_diy = 1 ORDER BY sort_order ASC, attr_id ASC";
    $attr_list = $GLOBALS['db']->getAll($sql);
    foreach ($attr_list as $key => $val)
    {
        $attr_list[$key]['attr_values'] = explode("\n", $val['attr_values']);
    }
    return $attr_list;
}

/* 上传文件 */
function upload_attr_file($upload)
{
    if (!make_dir("../" . DATA_DIR . "/attr"))
    {
        /* 创建目录失败 */
        return false;
    }

    $filename = cls_image::random_filename() . substr($upload['name'], strpos($upload['name'], '.'));
    $path = ROOT_PATH. DATA_DIR . "/attr/" . $filename;

    if (move_upload_file($upload['tmp_name'], $path))
    {
        return DATA_DIR . "/attr/" . $filename;
    }
    else
    {
        return false;
    }
}

/**
 * 获取属性图片列表
 */
function get_attrimglist()
{
    /* 查询条件 */
    $filter = array();
    $filter['goods_type'] = empty($_REQUEST['goods_type']) ? 0 : intval($_REQUEST['goods_type']);
    $filter['sort_by']    = empty($_REQUEST['sort_by']) ? 'img_id' : trim($_REQUEST['sort_by']);
    $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'ASC' : trim($_REQUEST['sort_order']);

    $where = (!empty($filter['goods_type'])) ? " WHERE cat_id = '$filter[goods_type]' " : '';

    $sql = "SELECT COUNT(*) FROM " . $GLOBALS['ecs']->table('attribute_img') . " AS a $where";
    $filter['record_count'] = $GLOBALS['db']->getOne($sql);

    /* 分页大小 */
    $filter = page_and_size($filter);

    /* 查询 */
    $sql = "SELECT * FROM " . $GLOBALS['ecs']->table('attribute_img') . $where . " ORDER BY $filter[sort_by] $filter[sort_order] " . " LIMIT " . $filter['start'] .", $filter[page_size]";
    $row = $GLOBALS['db']->getAll($sql);

    $arr = array('item' => $row, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']);

    return $arr;
}
?>
