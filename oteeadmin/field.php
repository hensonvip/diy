<?php
/**
 * 领域管理
 */

define('IN_ECS', true);

require(dirname(__FILE__) . '/includes/init.php');
include_once(ROOT_PATH . 'includes/Pinyin.php');

/*初始化数据交换对象 */
$exc = new exchange($ecs->table("field"), $db, 'field_id', 'field_name');

/*------------------------------------------------------ */
//-- 领域列表
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'list')
{
    /* 取得过滤条件 */
    $filter = array();
    $smarty->assign('ur_here',      '领域列表');
    $smarty->assign('action_link',  array('text' => '添加新领域', 'href' => 'field.php?act=add'));
    $smarty->assign('full_page',    1);
    $smarty->assign('filter',       $filter);

    $field_list = get_field_list();

    $smarty->assign('field_list',    $field_list['arr']);
    $smarty->assign('filter',          $field_list['filter']);
    $smarty->assign('record_count',    $field_list['record_count']);
    $smarty->assign('page_count',      $field_list['page_count']);

    $sort_flag  = sort_flag($field_list['filter']);
    $smarty->assign($sort_flag['tag'], $sort_flag['img']);

    assign_query_info();
    $smarty->display('field_list.htm');
}

/*------------------------------------------------------ */
//-- 翻页，排序
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'query')
{
    check_authz_json('field_list');

    $field_list = get_field_list();

    $smarty->assign('field_list',    $field_list['arr']);
    $smarty->assign('filter',          $field_list['filter']);
    $smarty->assign('record_count',    $field_list['record_count']);
    $smarty->assign('page_count',      $field_list['page_count']);

    $sort_flag  = sort_flag($field_list['filter']);
    $smarty->assign($sort_flag['tag'], $sort_flag['img']);

    make_json_result($smarty->fetch('field_list.htm'), '',
        array('filter' => $field_list['filter'], 'page_count' => $field_list['page_count']));
}

/*------------------------------------------------------ */
//-- 添加领域
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'add')
{
    /* 权限判断 */
    admin_priv('field_list');

    $smarty->assign('field',     array());
    $smarty->assign('ur_here',     '添加新领域');
    $smarty->assign('action_link', array('text' => '领域列表', 'href' => 'field.php?act=list'));
    $smarty->assign('form_action', 'insert');

    assign_query_info();
    $smarty->display('field_info.htm');
}

/*------------------------------------------------------ */
//-- 添加字体
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'insert')
{
    /* 权限判断 */
    admin_priv('field_list');

    $field_name = trim($_POST['field_name']);   //领域名称

    //拼音首字母
    $field_pin = Pinyin($field_name, 1, 1);
    $field_pin = ucwords(substr($field_pin, 0, 1));

    /*检查领域名是否重复*/
    $is_only = $exc->is_only('field_name', $field_name);

    if (!$is_only)
    {
        sys_msg(sprintf('领域名称已存在', stripslashes($field_name)), 1);
    }

    if (!field_pin_ok($field_pin)) {
        sys_msg('拼音首字母不合法!');
    }

    $sql = "INSERT INTO ".$ecs->table('field')."(field_name, field_pin, sort_order, is_show, is_common) ".
            "VALUES ('$field_name', '$field_pin', '$_POST[sort_order]', '$_POST[is_show]', '$_POST[is_common]')";
    $db->query($sql);

    $link[0]['text'] = '继续添加新领域';
    $link[0]['href'] = 'field.php?act=add';

    $link[1]['text'] = '返回领域列表';
    $link[1]['href'] = 'field.php?act=list';

    admin_log($field_name,'add','field');

    clear_cache_files(); // 清除相关的缓存文件

    sys_msg('领域已经添加成功',0, $link);
}

/*------------------------------------------------------ */
//-- 编辑
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'edit')
{
    /* 权限判断 */
    admin_priv('field_list');

    /* 取字体数据 */
    $sql = "SELECT * FROM " .$ecs->table('field'). " WHERE field_id='$_REQUEST[id]'";
    $field = $db->GetRow($sql);

    $smarty->assign('field',     $field);
    $smarty->assign('ur_here',     '编辑领域');
    $smarty->assign('action_link', array('text' => '领域列表', 'href' => 'field.php?act=list&' . list_link_postfix()));
    $smarty->assign('form_action', 'update');

    assign_query_info();
    $smarty->display('field_info.htm');
}

if ($_REQUEST['act'] =='update')
{
    /* 权限判断 */
    admin_priv('field_list');

    $id = intval($_POST['id']);
    $field_name = trim($_POST['field_name']);

    //拼音首字母
    $field_pin = Pinyin($field_name, 1, 1);
    $field_pin = ucwords(substr($field_pin, 0, 1));

    /*检查重名*/
    if ($field_name != $_POST['old_field_name'])
    {
        $is_only = $exc->is_only('field_name', $field_name, $id);

        if (!$is_only)
        {
            sys_msg(sprintf('领域名称已存在', stripslashes($field_name)), 1);
        }
    }

    if (!field_pin_ok($field_pin)) {
        sys_msg('拼音首字母不合法!');
    }

    if ($exc->edit("field_name='$field_name', field_pin='$field_pin', is_show='$_POST[is_show]', sort_order = '$_POST[sort_order]', is_common = '$_POST[is_common]'", $id))
    {
        $link[0]['text'] = '返回领域列表';
        $link[0]['href'] = 'field.php?act=list&' . list_link_postfix();

        $note = sprintf('领域 %s 成功编辑', stripslashes($field_name));
        admin_log($field_name, 'edit', 'field');

        clear_cache_files();

        sys_msg($note, 0, $link);
    }
    else
    {
        die($db->error());
    }
}

/*------------------------------------------------------ */
//-- 切换是否显示
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'toggle_show')
{
    check_authz_json('field_list');

    $id = intval($_POST['id']);
    $val = intval($_POST['val']);

    $exc->edit("is_show = '$val'", $id);

    clear_cache_files();

    make_json_result($val);
}

/*------------------------------------------------------ */
//-- 切换是否常用
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'toggle_common')
{
    check_authz_json('field_list');

    $id = intval($_POST['id']);
    $val = intval($_POST['val']);

    $exc->edit("is_common = '$val'", $id);

    clear_cache_files();

    make_json_result($val);
}

/*------------------------------------------------------ */
//-- 删除领域
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'remove')
{
    check_authz_json('field_list');

    $id = intval($_GET['id']);

    $name = $exc->get_name($id);
    if ($exc->drop($id))
    {
        admin_log(addslashes($name),'remove','field');
        clear_cache_files();
    }

    $url = 'field.php?act=query&' . str_replace('act=remove', '', $_SERVER['QUERY_STRING']);

    ecs_header("Location: $url\n");
    exit;
}

/* 获得领域列表 */
function get_field_list()
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
        $filter['sort_by']    = empty($_REQUEST['sort_by']) ? 'field_pin' : trim($_REQUEST['sort_by']);
        $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'ASC' : trim($_REQUEST['sort_order']);

        $where = '';
        if (!empty($filter['keyword']))
        {
            $where = " AND field_name LIKE '%" . mysql_like_quote($filter['keyword']) . "%'";
        }

        /* 字体总数 */
        $sql = 'SELECT COUNT(*) FROM ' . $GLOBALS['ecs']->table('field'). ' WHERE 1 ' . $where;
        $filter['record_count'] = $GLOBALS['db']->getOne($sql);

        $filter = page_and_size($filter);

        /* 获取字体数据 */
        $sql = 'SELECT * FROM ' . $GLOBALS['ecs']->table('field') . ' WHERE 1 ' . $where . ' ORDER by '.$filter['sort_by'].' '.$filter['sort_order'];

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
        $arr[] = $rows;
    }
    return array('arr' => $arr, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']);
}

/**
 * 判断领域名称是否合法
 */
function field_name_ok($field_name) {
    return preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/',$field_name);
}

/**
 * 判断是否为单个字母
 */
function field_pin_ok($field_pin) {
    return preg_match("/^[a-zA-Z]$/",$field_pin);
}

?>