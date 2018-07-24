<?php

/**
 * 银行卡管理
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
$exc = new exchange($ecs->table("bank_card"), $db, 'id', 'card_name');

/*------------------------------------------------------ */
//-- 银行卡列表
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'list')
{
    $smarty->assign('ur_here',     $_LANG['11_bank_card_list']);
    
    $smarty->assign('full_page',   1);

    $bank_card_list = bank_card_list();

    $smarty->assign('bank_card_list',  $bank_card_list['position']);
    $smarty->assign('filter',          $bank_card_list['filter']);
    $smarty->assign('record_count',    $bank_card_list['record_count']);
    $smarty->assign('page_count',      $bank_card_list['page_count']);

    assign_query_info();
    $smarty->display('bank_card_list.htm');
}

/*------------------------------------------------------ */
//-- 排序、分页、查询
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'query')
{
    $bank_card_list = bank_card_list();

    $smarty->assign('bank_card_list',   $bank_card_list['position']);
    $smarty->assign('filter',          $bank_card_list['filter']);
    $smarty->assign('record_count',    $bank_card_list['record_count']);
    $smarty->assign('page_count',      $bank_card_list['page_count']);

    make_json_result($smarty->fetch('bank_card_list.htm'), '',
        array('filter' => $bank_card_list['filter'], 'page_count' => $bank_card_list['page_count']));
}

/* 获取银行卡列表 */
function bank_card_list()
{    
    $result = get_filter();
    if ($result === false)
    {
        $filter = array();

        /* 记录总数以及页数 */
        $sql = "SELECT COUNT(*) FROM ".$GLOBALS['ecs']->table('bank_card');

        $filter['record_count'] = $GLOBALS['db']->getOne($sql);

        $filter = page_and_size($filter);
    }
    else
    {
        $sql    = $result['sql'];
        $filter = $result['filter'];
    }

    $sql = "SELECT * FROM ".$GLOBALS['ecs']->table('bank_card')." ORDER BY id DESC";
    /* 查询数据 */
    $arr = array();
    $res = $GLOBALS['db']->selectLimit($sql, $filter['page_size'], $filter['start']);
    while ($rows = $GLOBALS['db']->fetchRow($res))
    {
        $rows['bank_user_name'] = $GLOBALS['db']->getOne("SELECT user_name FROM ".$GLOBALS['ecs']->table('users')." where user_id = '".$rows['user_id']."' ");
        $rows['add_time'] = date('Y-m-d H:i:s',$rows['add_time']);
        $arr[] = $rows;
    }

    return array('position' => $arr, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']);
}
?>