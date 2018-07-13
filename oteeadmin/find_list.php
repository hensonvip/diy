<?php
/**
 * 发现
 */

define('IN_ECS', true);
require(dirname(__FILE__) . '/includes/init.php');
require_once(ROOT_PATH . 'includes/cls_image.php');
$exc = new exchange($ecs->table("finds"), $db, 'find_id', 'name');


/*------------------------------------------------------ */
//-- 审核作品列表
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'examine')
{
    $filter = array();
    $smarty->assign('ur_here',      '审核作品列表');
    //$smarty->assign('action_link',  array('text' => '添加新作品类型', 'href' => 'finds.php?act=add'));
    $smarty->assign('full_page',    1);
    $smarty->assign('filter',       $filter);

    $graph_list = get_graph_list();
    //var_dump($graph_list['arr']);
    $smarty->assign('graph_list',    $graph_list['arr']);
    $smarty->assign('filter',          $graph_list['filter']);
    $smarty->assign('record_count',    $graph_list['record_count']);
    $smarty->assign('page_count',      $graph_list['page_count']);

    $sort_flag  = sort_flag($graph_list['filter']);
    $smarty->assign($sort_flag['tag'], $sort_flag['img']);

    assign_query_info();
    $smarty->display('examine_list.htm');
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

    make_json_result($smarty->fetch('examine_list.htm'), '',
        array('filter' => $graph_list['filter'], 'page_count' => $graph_list['page_count']));
}


/*------------------------------------------------------ */
//-- 审核页面
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'see')
{
    /* 权限判断 */
    // admin_priv('graph_list');

    /* 取作品基本数据 */
    $sql = "SELECT * FROM " .$ecs->table('finds'). " WHERE find_id='$_REQUEST[id]'";
    $find_data = $db->GetRow($sql);

    /* 取作品分类 */
    $sql = "SELECT * FROM " .$ecs->table('finds_type'). " WHERE 'type_id'='$find_data[label]'";
    $label = $db->getCol($sql);

    //var_dump($find_data);
    $smarty->assign('find_data',     $find_data);
    $smarty->assign('label',     $label);
    //$smarty->assign('type_select',  graph_type_list());
    $smarty->assign('ur_here',     '审核');
    //$smarty->assign('action_link', array('text' => '审核作品列表', 'href' => 'find_list.php?act=see&' . list_link_postfix()));
    //$smarty->assign('form_action', 'update');
    //$smarty->assign('en_name', $arr);

    assign_query_info();
    $smarty->display('examine_see.htm');
}

if ($_REQUEST['act'] =='update')
{
    /* 权限判断 */
    // admin_priv('graph_list');

    $id = intval($_POST['id']);


    if ($exc->edit("is_common='$_POST[is_common]',name='$_POST[name]', en_name = '$_POST[en_name]'", $id))
    {
        $link[0]['text'] = '返回列表';
        $link[0]['href'] = 'find_list.php?act=examine_list&' . list_link_postfix();

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

    //$name = $exc->get_name($id);
    if ($exc->drop($id))
    {
        // admin_log(addslashes($name),'remove','graph');
        clear_cache_files();
    }

    $url = 'find_list.php?act=query&' . str_replace('act=remove', '', $_SERVER['QUERY_STRING']);

    ecs_header("Location: $url\n");
    exit;
}
/*------------------------------------------------------ */
//-- 审核通过
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'toggle_state')
{
    $find_id       = intval($_POST['id']);
    $state        = 3;

    $db->query("UPDATE ".$ecs->table('finds')." SET state = '$state' WHERE find_id = '$find_id'");
    make_json_result($state);
}

/*------------------------------------------------------ */
//-- 批量操作
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'batch')
{
    /* 批量删除 */
    if (isset($_POST['type'])) {
        if ($_POST['type'] == 'button_remove') {
            // admin_priv('graph_list');

            if (!isset($_POST['checkboxes']) || !is_array($_POST['checkboxes'])) {
                sys_msg('您没有选择任何类型', 1);
            }

            // print_r($_POST['checkboxes']);die;

            foreach ($_POST['checkboxes'] AS $key => $id) {
                // if ($exc->drop($id))
                // {
                $sql = 'DELETE FROM ' . $ecs->table('finds') . " WHERE find_id = '$id'";
                $db->query($sql);
                // $name = $exc->get_name($id);
                // admin_log(addslashes($name),'remove','graph');
                // }
            }

        }
    }

    /* 清除缓存 */
    clear_cache_files();
    $lnk[] = array('text' => '返回列表', 'href' => 'find_list.php?act=examine');
    sys_msg('批量操作成功', 0, $lnk);
}

/* 获得列表 */
function get_graph_list()
{
    //PRINT_R($filter['start']);
    $result = get_filter();
    //var_dump($result);
    if ($result === false)
    {
        $filter = array();
         $filter['state_cate'] = empty($_REQUEST['state_cate']) ? 0 : intval($_REQUEST['state_cate']);
        $filter['sort_by']    = empty($_REQUEST['sort_by']) ? 'add_time' : trim($_REQUEST['sort_by']);
        $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);

         $where = '';
         if ($filter['state_cate']) {
             $where = "WHERE state = " . $filter['state_cate']." ";
         }elseif($filter['state_cate']==0){
             $where = " " ;
         }
         //print_r($filter);die;

        /* 统计 */
        $sql_count = 'SELECT COUNT(*) FROM ' .$GLOBALS['ecs']->table('finds');
        $filter['record_count'] = $GLOBALS['db']->getOne($sql_count);

        $filter = page_and_size($filter);

        /* 获取作品数据 */
        $sql = 'SELECT *  FROM ' .$GLOBALS['ecs']->table('finds').$where. 'order by '.$filter['sort_by'].' '.$filter['sort_order'];

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