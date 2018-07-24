<?php

/**
 * 银行管理
*/

define('IN_ECS', true);

require(dirname(__FILE__) . '/includes/init.php');
include_once(ROOT_PATH . 'includes/cls_image.php');
$image = new cls_image($_CFG['bgcolor']);
require_once(ROOT_PATH . 'languages/' .$_CFG['lang']. '/admin/bank.php');

/* act操作项的初始化 */
if (empty($_REQUEST['act']))
{
    $_REQUEST['act'] = 'list';
}
else
{
    $_REQUEST['act'] = trim($_REQUEST['act']);
}

$smarty->assign('lang', $_LANG);
$exc = new exchange($ecs->table("bank"), $db, 'id', 'bank_name');

/*------------------------------------------------------ */
//-- 银行列表
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'list')
{
    $smarty->assign('ur_here',     $_LANG['12_bank_list']);
    $smarty->assign('action_link', array('text' => $_LANG['bank_add'], 'href' => 'bank.php?act=add'));
    $smarty->assign('full_page',   1);

    $bank_list = bank_list();

    $smarty->assign('bank_list',  $bank_list['position']);
    $smarty->assign('filter',          $bank_list['filter']);
    $smarty->assign('record_count',    $bank_list['record_count']);
    $smarty->assign('page_count',      $bank_list['page_count']);

    assign_query_info();
    $smarty->display('bank_list.htm');
}

/*------------------------------------------------------ */
//-- 添加银行页面
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'add')
{
    //admin_priv('ad_manage');

    /* 模板赋值 */
    $smarty->assign('ur_here',     $_LANG['bank_add']);
    $smarty->assign('form_act',    'insert');

    $smarty->assign('action_link', array('href' => 'bank.php?act=list', 'text' => $_LANG['12_bank_list']));
    
    assign_query_info();
    $smarty->display('bank_info.htm');
}
elseif ($_REQUEST['act'] == 'insert')
{
    //admin_priv('ad_manage');

    /* 对POST上来的值进行处理并去除空格 */
    $bank_name = !empty($_POST['bank_name']) ? trim($_POST['bank_name']) : '';
    $bank_short_name = !empty($_POST['bank_short_name']) ? trim($_POST['bank_short_name']) : '';
    $bank_color = !empty($_POST['bank_color']) ? trim($_POST['bank_color']) : '';
    $sort_order = !empty($_POST['sort_order']) ? trim($_POST['sort_order']) : '50';
    $bankImg = '';
    if ((isset($_FILES['bank_icon']['error']) && $_FILES['bank_icon']['error'] == 0) || (!isset($_FILES['bank_icon']['error']) && isset($_FILES['bank_icon']['tmp_name'] ) &&$_FILES['bank_icon']['tmp_name'] != 'none'))
    {
        $bankImg = basename($image->upload_image($_FILES['bank_icon'], 'bankImg'));
    }
    $sql = 'INSERT INTO '.$ecs->table('bank').' (bank_name,bank_icon, bank_short_name,sort_order,bank_color) '. "VALUES ('$bank_name','$bankImg', '$bank_short_name','$sort_order', '$bank_color')";

    $db->query($sql);
    /* 记录管理员操作 */
    admin_log($bank_name, 'add', 'bank');

    /* 提示信息 */
    $link[0]['text'] = $_LANG['bank_add'];
    $link[0]['href'] = 'bank.php?act=add';

    sys_msg($_LANG['add'] . "&nbsp;" . stripslashes($bank_name) . "&nbsp;" . $_LANG['add_bank_succed'], 0, $link);
}

/*------------------------------------------------------ */
//-- 银行编辑页面
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'edit')
{
    //admin_priv('ad_manage');

    $id = !empty($_GET['id']) ? intval($_GET['id']) : 0;

    /* 获取银行数据 */
    $sql = 'SELECT * FROM ' .$ecs->table('bank'). " WHERE id='$id'";
    $bank = $db->getRow($sql);

    $smarty->assign('ur_here',     $_LANG['position_edit']);
    $smarty->assign('action_link', array('href' => 'bank.php?act=list', 'text' => $_LANG['12_bank_list']));
    $smarty->assign('bank',   $bank);
    $smarty->assign('form_act',    'update');

    assign_query_info();
    $smarty->display('bank_info.htm');
}
elseif ($_REQUEST['act'] == 'update')
{
    //admin_priv('ad_manage');

    $id = !empty($_POST['id']) ? trim($_POST['id']) : '';
    $bank_name = !empty($_POST['bank_name']) ? trim($_POST['bank_name']) : '';
    $bank_short_name = !empty($_POST['bank_short_name']) ? trim($_POST['bank_short_name']) : '';
    $bank_color = !empty($_POST['bank_color']) ? trim($_POST['bank_color']) : '';
    $sort_order = !empty($_POST['sort_order']) ? trim($_POST['sort_order']) : '50';
    $bankImg = '';
    if ((isset($_FILES['bank_icon']['error']) && $_FILES['bank_icon']['error'] == 0) || (!isset($_FILES['bank_icon']['error']) && isset($_FILES['bank_icon']['tmp_name']) && $_FILES['bank_icon']['tmp_name'] != 'none'))
    {
        $img_up_info = basename($image->upload_image($_FILES['bank_icon'], 'bankImg'));
        $bankImg = "bank_icon = '".$img_up_info."'".',';

        // 删除旧图片
        $sql = "SELECT bank_icon FROM " .$GLOBALS['ecs']->table('bank'). " WHERE id = '".$id."'";
        $logo_name = $db->getOne($sql);
        if (!empty($logo_name))
        {
            @unlink('../' . DATA_DIR . '/bankImg/' .$logo_name);
        }
    }

    /* 查看银行是否与其它有重复 */
    $sql = 'SELECT COUNT(*) FROM ' .$ecs->table('bank').
           " WHERE bank_name = '$bank_name' AND id <> '$id'";
    if ($db->getOne($sql) == 0)
    {
        $sql = "UPDATE " .$ecs->table('bank'). " SET ".$bankImg.
               "bank_name    = '$bank_name',bank_short_name    = '$bank_short_name',sort_order = '$sort_order',bank_color = '$bank_color' ".
               "WHERE id = '$id'";
        if ($db->query($sql))
        {
           /* 记录管理员操作 */
           admin_log($bank_name, 'edit', 'bank');

           /* 清除缓存 */
           clear_cache_files();

           /* 提示信息 */
           $link[] = array('text' => $_LANG['back_bank_list'], 'href' => 'bank.php?act=list');
           sys_msg($_LANG['edit'] . ' ' .stripslashes($bank_name).' '. $_LANG['edit_bank_succed'], 0, $link);
        }
    }
    else
    {
        $link[] = array('text' => $_LANG['go_back'], 'href' => 'javascript:history.back(-1)');
        sys_msg($_LANG['bank_name_exist'], 0, $link);
    }
}

/*------------------------------------------------------ */
//-- 排序、分页、查询
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'query')
{
    $bank_list = bank_list();

    $smarty->assign('bank_list',   $bank_list['position']);
    $smarty->assign('filter',          $bank_list['filter']);
    $smarty->assign('record_count',    $bank_list['record_count']);
    $smarty->assign('page_count',      $bank_list['page_count']);

    make_json_result($smarty->fetch('bank_list.htm'), '',
        array('filter' => $bank_list['filter'], 'page_count' => $bank_list['page_count']));
}

/*------------------------------------------------------ */
//-- 删除银行
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'remove')
{
    //check_authz_json('ad_manage');

    $id = intval($_GET['id']);

    // 删除旧图片
    $sql = "SELECT bank_icon FROM " .$GLOBALS['ecs']->table('bank'). " WHERE id = '".$id."'";
    $logo_name = $db->getOne($sql);
    if (!empty($logo_name))
    {
        @unlink('../' . DATA_DIR . '/bankImg/' .$logo_name);
    }

    $exc->drop($id);
    admin_log('', 'remove', 'bank');

    $url = 'bank.php?act=query&' . str_replace('act=remove', '', $_SERVER['QUERY_STRING']);

    ecs_header("Location: $url\n");
    exit;
}

/* 获取银行列表 */
function bank_list()
{	 
    $result = get_filter();
    if ($result === false)
    {
    	$filter = array();
		
        /* 分页大小 */
        
        /* 记录总数以及页数 */
        if (isset($_POST['keyword']))
        {
			$sql = "SELECT COUNT(*) FROM " . $GLOBALS['ecs']->table('bank') . " WHERE bank_name like '%" . $_POST['keyword'] . "%' ";
        }
        else
        {
            $sql = "SELECT COUNT(*) FROM ".$GLOBALS['ecs']->table('bank');
        }

        $filter['record_count'] = $GLOBALS['db']->getOne($sql);

        $filter = page_and_size($filter);
    }
    else
    {
        $sql    = $result['sql'];
        $filter = $result['filter'];
    }

    $sql = "SELECT * FROM ".$GLOBALS['ecs']->table('bank')." ORDER BY sort_order ASC, id ASC";
    /* 查询数据 */
    $arr = array();
    $res = $GLOBALS['db']->selectLimit($sql, $filter['page_size'], $filter['start']);
    while ($rows = $GLOBALS['db']->fetchRow($res))
    {
        $arr[] = $rows;
    }

    return array('position' => $arr, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']);
}
?>