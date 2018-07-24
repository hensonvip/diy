<?php
/**
 * 发现
 */

define('IN_ECS', true);
require(dirname(__FILE__) . '/includes/init.php');
require_once(ROOT_PATH . 'includes/cls_image.php');
$exc = new exchange($ecs->table("finds_type"), $db, 'type_id', 'name');


/*------------------------------------------------------ */
//-- 作品类型列表
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'creativity')
{
    $filter = array();
    $smarty->assign('ur_here',      '作品类型列表');
    $smarty->assign('action_link',  array('text' => '添加新作品类型', 'href' => 'finds.php?act=add'));
    $smarty->assign('full_page',    1);
    $smarty->assign('filter',       $filter);

    $graph_list = get_graph_list();

    $smarty->assign('graph_list',    $graph_list['arr']);
    $smarty->assign('filter',          $graph_list['filter']);
    $smarty->assign('record_count',    $graph_list['record_count']);
    $smarty->assign('page_count',      $graph_list['page_count']);

    $sort_flag  = sort_flag($graph_list['filter']);
    $smarty->assign($sort_flag['tag'], $sort_flag['img']);

    assign_query_info();
    $smarty->display('creativity_list.htm');
}

/*------------------------------------------------------ */
//-- 翻页，排序
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'query')
{
    check_authz_json('graph_list');

    $graph_list = get_graph_list();

    $smarty->assign('graph_list',    $graph_list['arr']);
    $smarty->assign('filter',          $graph_list['filter']);
    $smarty->assign('record_count',    $graph_list['record_count']);
    $smarty->assign('page_count',      $graph_list['page_count']);

    $sort_flag  = sort_flag($graph_list['filter']);
    $smarty->assign($sort_flag['tag'], $sort_flag['img']);

    make_json_result($smarty->fetch('creativity_list.htm'), '',
        array('filter' => $graph_list['filter'], 'page_count' => $graph_list['page_count']));
}

/*------------------------------------------------------ */
//-- 添加类型
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'add')
{
    /* 权限判断 */
    // admin_priv('graph_list');
    $arr = array('A','B','C','D','E','F','G','H','I','J','K','L','N','M','O','P','Q','R','S','T','U','V','W','S','Y','Z');

    $smarty->assign('graph',     array());
    $smarty->assign('ur_here',     '添加新类型');
    $smarty->assign('action_link', array('text' => '作品类型列表', 'href' => 'finds.php?act=creativity'));
    $smarty->assign('form_action', 'insert');
    $smarty->assign('en_name', $arr);

    assign_query_info();
    $smarty->display('creativity_info.htm');
}

/*------------------------------------------------------ */
//-- 添加图形
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'insert')
{
    /* 权限判断 */
    // admin_priv('graph_list');
    $name = trim($_POST['name']);
    if (!$name) 
    {
        sys_msg('类型名称未填写!');die;
    }

    $en_name = $_POST['en_name'];
    $is_common = $_POST['is_common'];
    $add_time = time();

    $sql = "INSERT INTO ".$ecs->table('finds_type')."(name, en_name, is_common, add_time) ".
            "VALUES ('$name', '$en_name', '$is_common', '$add_time')";
    $db->query($sql);

    $link[0]['text'] = '继续添加';
    $link[0]['href'] = 'finds.php?act=add';

    $link[1]['text'] = '返回列表';
    $link[1]['href'] = 'finds.php?act=creativity';

    // admin_log($_POST['type_id'],'add','graph');

    clear_cache_files(); // 清除相关的缓存文件

    sys_msg('添加成功',0, $link);
}

/*------------------------------------------------------ */
//-- 编辑
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'edit')
{
    /* 权限判断 */
    // admin_priv('graph_list');

    /* 取图形数据 */
    $sql = "SELECT * FROM " .$ecs->table('finds_type'). " WHERE type_id='$_REQUEST[id]'";
    $graph = $db->GetRow($sql);

    $arr = array('A','B','C','D','E','F','G','H','I','J','K','L','N','M','O','P','Q','R','S','T','U','V','W','S','Y','Z');

    $smarty->assign('graph',     $graph);
    $smarty->assign('type_select',  graph_type_list());
    $smarty->assign('ur_here',     '编辑');
    $smarty->assign('action_link', array('text' => '作品类型列表', 'href' => 'finds.php?act=creativity&' . list_link_postfix()));
    $smarty->assign('form_action', 'update');
    $smarty->assign('en_name', $arr);

    assign_query_info();
    $smarty->display('creativity_info.htm');
}

if ($_REQUEST['act'] =='update')
{
    /* 权限判断 */
    // admin_priv('graph_list');

    $id = intval($_POST['id']);


    if ($exc->edit("is_common='$_POST[is_common]',name='$_POST[name]', en_name = '$_POST[en_name]'", $id))
    {
        $link[0]['text'] = '返回列表';
        $link[0]['href'] = 'finds.php?act=creativity&' . list_link_postfix();

        $note = sprintf('成功编辑', stripslashes($graph_name));
        // admin_log($graph_name, 'edit', 'graph');

        clear_cache_files();

        sys_msg($note, 0, $link);
    }
    else
    {
        die($db->error());
    }
}

/*------------------------------------------------------ */
//-- 删除
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'remove')
{
    check_authz_json('graph_list');

    $id = intval($_GET['id']);

    $name = $exc->get_name($id);
    if ($exc->drop($id))
    {
        // admin_log(addslashes($name),'remove','graph');
        clear_cache_files();
    }

    $url = 'finds.php?act=query&' . str_replace('act=remove', '', $_SERVER['QUERY_STRING']);

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
            // admin_priv('graph_list');

            if (!isset($_POST['checkboxes']) || !is_array($_POST['checkboxes']))
            {
                sys_msg('您没有选择任何类型', 1);
            }

            // print_r($_POST['checkboxes']);die;

            foreach ($_POST['checkboxes'] AS $key => $id)
            {
                // if ($exc->drop($id))
                // {
                    $sql = 'DELETE FROM ' . $ecs->table('finds_type') . " WHERE type_id = '$id'";
                    $db->query($sql);
                    // $name = $exc->get_name($id);
                    // admin_log(addslashes($name),'remove','graph');
                // }
            }

        }

        /* 批量隐藏 */
        if ($_POST['type'] == 'button_hide')
        {
            check_authz_json('graph_list');
            if (!isset($_POST['checkboxes']) || !is_array($_POST['checkboxes']))
            {
                sys_msg('您没有选择任何类型', 1);
            }

            foreach ($_POST['checkboxes'] AS $key => $id)
            {
                $sql = 'UPDATE ' . $ecs->table('finds_type') . ' SET is_common=1 WHERE type_id='.$id;
                $db->query($sql);
            }
        }

        /* 批量显示 */
        if ($_POST['type'] == 'button_show')
        {
            check_authz_json('graph_list');
            if (!isset($_POST['checkboxes']) || !is_array($_POST['checkboxes']))
            {
                sys_msg('您没有选择任何类型', 1);
            }

            foreach ($_POST['checkboxes'] AS $key => $id)
            {
                $sql = 'UPDATE ' . $ecs->table('finds_type') . ' SET is_common=0 WHERE type_id='.$id;
                $db->query($sql);
            }
        }
    }

    /* 清除缓存 */
    clear_cache_files();
    $lnk[] = array('text' => '返回列表', 'href' => 'finds.php?act=creativity');
    sys_msg('批量操作成功', 0, $lnk);
}

/* 获得列表 */
function get_graph_list()
{
    $result = get_filter();

    if ($result === false)
    {
        $filter = array();
        // $filter['type_id'] = empty($_REQUEST['type_id']) ? 0 : intval($_REQUEST['type_id']);
        $filter['sort_by']    = empty($_REQUEST['sort_by']) ? 'is_common' : trim($_REQUEST['sort_by']);
        $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);

        // $where = '';
        // if ($filter['type_id'])
        // {
        //     $where .= " AND a.type_id = " . $filter['type_id'];
        // }
        // print_r($filter);die;

        /* 图形总数 */
        $sql = 'SELECT COUNT(*) FROM ' .$GLOBALS['ecs']->table('finds_type');
        $filter['record_count'] = $GLOBALS['db']->getOne($sql);

        $filter = page_and_size($filter);

        /* 获取图形数据 */
        $sql = 'SELECT *  FROM ' .$GLOBALS['ecs']->table('finds_type'). 'order by '.$filter['sort_by'].' '.$filter['sort_order'].',en_name asc';

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

?>