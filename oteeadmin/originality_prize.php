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
include_once(ROOT_PATH . 'includes/cls_image.php');

/*初始化数据交换对象 */
$exc   = new exchange($ecs->table("diy_reward"), $db, 'reward_id', 'describe');
$image = new cls_image($_CFG['bgcolor']);
date_default_timezone_set("Asia/Shanghai");//定义时区，防止时间戳转换误差

/*------------------------------------------------------ */
//-- 文章列表
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'list')
{
    /* 取得过滤条件 */
    $filter = array();
    $smarty->assign('ur_here',      $_LANG['03_originality_prize']);
    $smarty->assign('action_link',  array('text' => $_LANG['originality_add'], 'href' => 'originality_prize.php?act=add'));
    $smarty->assign('full_page',    1);
    $smarty->assign('filter',       $filter);

    $list = get_originalitylist();
    //print_r($originality_list);exit;
    $smarty->assign('list',$list['arr']);
    $smarty->assign('filter',          $list['filter']);
    $smarty->assign('record_count',    $list['record_count']);
    $smarty->assign('page_count',      $list['page_count']);

    //print_r($originality_list);
    //print_r($list['arr']);
    $sort_flag  = sort_flag($list['filter']);
    $smarty->assign($sort_flag['tag'], $sort_flag['img']);

    assign_query_info();
    $smarty->display('originality_prize_list.htm');
}

/*------------------------------------------------------ */
//-- 翻页，排序
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'query')
{
    check_authz_json('originality_prize');

    $list = get_originalitylist();

    $smarty->assign('list',    $list['arr']);
    $smarty->assign('filter',          $list['filter']);
    $smarty->assign('record_count',    $list['record_count']);
    $smarty->assign('page_count',      $list['page_count']);

    $sort_flag  = sort_flag($list['filter']);
    $smarty->assign($sort_flag['tag'], $sort_flag['img']);

    make_json_result($smarty->fetch('originality_prize_list.htm'), '',
        array('filter' => $list['filter'], 'page_count' => $list['page_count']));
}

/*------------------------------------------------------ */
//-- 添加比赛
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'add')
{
    /* 权限判断 */
    admin_priv('originality_prize');


    /*初始化*/
    $info = array();
    $info['is_show'] = 1;

    $smarty->assign('info',     $info);
    $smarty->assign('ur_here',     $_LANG['originality_add']);
    $smarty->assign('action_link', array('text' => $_LANG['originality_list'], 'href' => 'originality_prize.php?act=insert'));
    $smarty->assign('form_action', 'insert');

    assign_query_info();
    $smarty->display('originality_prize_info.htm');
}

/*------------------------------------------------------ */
//-- 添加文章
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'insert')
{
    /* 权限判断 */
    admin_priv('originality_prize');

    //print_r($_POST);
    //print_r($_FILES);
   //exit;
    $describe = $_POST['describe'];
    $grade = $_POST['grade'];
    $prize = $_POST['prize'];
    $time = strtotime($_POST['time']);
    $is_show = $_POST['is_show'];

    /* 检查文章标题是否重复 */
    if ($exc->num("grade", $grade, $id) != 0)
    {
        //make_json_error(sprintf($_LANG['name_exist'], $name));
        sys_msg($_LANG['name_exist']);
    }

    /*插入数据*/
    $add_time = gmtime();

    if ((isset($_FILES['img']['error']) && $_FILES['img']['error'] == 0) || (!isset($_FILES['img']['error']) && isset($_FILES['img']['tmp_name'] ) &&$_FILES['img']['tmp_name'] != 'none')) {
        $img_url = $image->upload_image($_FILES['img'], 'prize');
    }
    if(empty($img_url)|| $img_url == false){
        sys_msg('图片上传失败');
    }


    //print_r($img_url);exit;
    //Array ( [describe] => 三等奖 [grade] => 3 [prize] => 100元 [time] => [is_show] => 0 [id] => 3 [act] => update

    $sql = "INSERT INTO ".$ecs->table('diy_reward')."(`describe`,`grade`,`prize`,`add_time`,`is_show`,`img` ) ".
            "VALUES ('$describe','$grade','$prize','$time','$is_show','$img_url' )";
    $db->query($sql);


    $link[0]['text'] = $_LANG['continue_add'];
    $link[0]['href'] = 'originality_prize.php?act=add';

    $link[1]['text'] = $_LANG['back_list'];
    $link[1]['href'] = 'originality_prize.php?act=list';

    //admin_log($_POST['name'],'add','originality');

    clear_cache_files(); // 清除相关的缓存文件

    sys_msg($_LANG['originalityadd_succeed'],0, $link);
}

/*------------------------------------------------------ */
//-- 编辑
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'edit')
{
    /* 权限判断 */
    admin_priv('originality_prize');

    /* 取数据 */
    $sql = "SELECT * FROM " .$ecs->table('diy_reward'). " WHERE reward_id='$_REQUEST[id]'";
    $info = $db->GetRow($sql);

    $info['add_time'] = date($GLOBALS['_CFG']['time_format'], $info['add_time']);

    $smarty->assign('info',     $info);
    $smarty->assign('ur_here',     $_LANG['originality_list']);
    $smarty->assign('action_link', array('text' => $_LANG['originality_list'], 'href' => 'originality_prize.php?act=list&' . list_link_postfix()));
    $smarty->assign('form_action', 'update');

    assign_query_info();
    $smarty->display('originality_prize_info.htm');
}

if ($_REQUEST['act'] =='update')
{
    /* 权限判断 */
    admin_priv('originality_prize');

    //print_r($_POST);exit;
    //$id = intval($_POST['id']);
    $describe = $_POST['describe'];
    $grade = $_POST['grade'];
    $prize = $_POST['prize'];
    $time = strtotime($_POST['time']);
    $is_show = $_POST['is_show'];
    $id = $_POST['id'];
    if(!$id){
        sys_msg('更改失败，请稍后重试');
    }
    if(!$describe){
        sys_msg('奖项名为必填项');
    }
    if(intval($grade) == 0 ){
        sys_msg('奖项等级输入有误，请填写数字');
    }

    /*检查文章名是否相同*/
    /*$is_only = $exc->is_only('name', $describe, $id);

    if (!$is_only)
    {
        sys_msg(sprintf($_LANG['name_exist'], stripslashes($name)), 1);
    }*/
    //Array ( [describe] => 三等奖 [grade] => 3 [prize] => 100元 [time] => [is_show] => 0 [id] => 3 [act] => update

    if ((isset($_FILES['img']['error']) && $_FILES['img']['error'] == 0) || (!isset($_FILES['img']['error']) && isset($_FILES['img']['tmp_name'] ) &&$_FILES['img']['tmp_name'] != 'none')) {
        $img_url = $image->upload_image($_FILES['img'], 'prize');
    }
    if(empty($img_url)|| $img_url == false){
        sys_msg('图片上传失败');
    }

    $sql = "UPDATE ".$ecs->table('diy_reward')." SET  `describe` = '$describe',`grade` = '$grade',`prize` = '$prize',`add_time` = '$time',`is_show` = '$is_show',`img` = '$img_url'  WHERE reward_id = $id";
    if((string)$db->query($sql)){
        sys_msg('更改成功');
    }else{
        sys_msg('更改失败，请稍后重试');
    }

}



/*------------------------------------------------------ */
//-- 编辑比赛活动名
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'edit_name')
{
    check_authz_json('originality_prize');

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
    check_authz_json('originality_prize');

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
    check_authz_json("originality_delete");

    $id = intval($_GET['id']);

    $name = $exc->get_name($id);


    if ($exc->drop($id))
    {
        //$db->query("DELETE FROM " . $ecs->table('diy_reward') . " WHERE id = $id");
        
        admin_log(addslashes($name),'remove','originality_prize');
        clear_cache_files();
    }

    $url = 'originality_prize.php?act=query&' . str_replace('act=remove', '', $_SERVER['QUERY_STRING']);

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
            check_authz_json('originality_prize');
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
            check_authz_json('originality_prize');
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
        $filter['sort_by']    = empty($_REQUEST['sort_by']) ? 'reward_id' : trim($_REQUEST['sort_by']);
        $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);

        $where = '';
        if (!empty($filter['keyword']))
        {
            $where = " AND name LIKE '%" . mysql_like_quote($filter['keyword']) . "%'";
        }

        /* 文章总数 */
        $sql = 'SELECT COUNT(*) FROM ' .$GLOBALS['ecs']->table('diy_reward').
               'WHERE 1 ' .$where;
        $filter['record_count'] = $GLOBALS['db']->getOne($sql);

        $filter = page_and_size($filter);

        /* 获取文章数据 */
        $sql = 'SELECT *,`describe` as describes '.
               'FROM ' .$GLOBALS['ecs']->table('diy_reward').
               ' WHERE 1 ' .$where. ' ORDER by '.$filter['sort_by'].' '.$filter['sort_order'];

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
        $rows['add_time'] = date($GLOBALS['_CFG']['time_format'], $rows['add_time']);
        $arr[] = $rows;
    }
    return array('arr' => $arr, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']);
}

?>