<?php

/**
 * ECSHOP  管理中心会员消息程序
 * ============================================================================
 * 版权所有 2005-2011 上海商派网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.ecshop.com；
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * $Author: liubo $
 * $Id: mem_mess.php 17217 2011-01-19 06:29:08Z liubo $
*/

define('IN_ECS', true);

require(dirname(__FILE__) . '/includes/init.php');

/* act操作项的初始化 */
$_REQUEST['act'] = trim($_REQUEST['act']);
if (empty($_REQUEST['act']))
{
    $_REQUEST['act'] = 'list';
}


/*------------------------------------------------------ */
//-- 消息列表页面
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'list')
{
    $smarty->assign('ur_here',     $_LANG['msg_list']);
    $smarty->assign('action_link', array('text' => $_LANG['send_msg'], 'href' => 'mem_mess.php?act=send'));
    $smarty->assign('full_page',   1);

    $list = get_mem_mess_list();

    $smarty->assign('message_list',    $list['item']);
    $smarty->assign('filter',       $list['filter']);
    $smarty->assign('record_count', $list['record_count']);
    $smarty->assign('page_count',   $list['page_count']);

    $sort_flag  = sort_flag($list['filter']);
    $smarty->assign($sort_flag['tag'], $sort_flag['img']);

    assign_query_info();
    $smarty->display('mem_mess_list.htm');

}

/*------------------------------------------------------ */
//-- ajax会员查询页面
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'user_query')
{
    /* 过滤条件 */
    $filter['keywords'] = empty($_REQUEST['keywords']) ? '' : trim($_REQUEST['keywords']);
    if(isset($_REQUEST['is_ajax']) && $_REQUEST['is_ajax'] == 1)
    {
        $filter['keywords'] = json_str_iconv($filter['keywords']);
    }
    $filter['level'] = isset($_REQUEST['level']) ? intval($_REQUEST['level']) : -1;
    $ex_where = ' WHERE 1 ';
    if($filter['keywords'])
    {
        $ex_where .= " AND user_name LIKE '%" . mysql_like_quote($filter['keywords']) . "%' or email like  '%" . mysql_like_quote($filter['keywords']) . "%' or mobile_phone like  '%" . mysql_like_quote($filter['keywords']) . "%' ";
    }

    $filter['record_count'] = $GLOBALS['db']->getOne("SELECT COUNT(*) FROM " . $GLOBALS['ecs']->table('users') . $ex_where);

    $sql = "SELECT user_id, user_name FROM " . $GLOBALS['ecs']->table('users') . $ex_where ;
    $user_list = $GLOBALS['db']->getAll($sql);
    $user_option = '';
    foreach($user_list as $item){
        $user_option .= '<option value="'.$item['user_id'].'" selected>'.$item['user_name'].'</option>';
    }
    $arr = array(
        'user_option' => $user_option, 'record_count' => $filter['record_count'].'条记录'
    );
    echo json_encode($arr);
}

/*------------------------------------------------------ */
//-- 翻页、排序
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'query')
{
    $list = get_mem_mess_list();

    $smarty->assign('message_list',    $list['item']);
    $smarty->assign('filter',       $list['filter']);
    $smarty->assign('record_count', $list['record_count']);
    $smarty->assign('page_count',   $list['page_count']);

    $sort_flag  = sort_flag($list['filter']);
    $smarty->assign($sort_flag['tag'], $sort_flag['img']);

    make_json_result($smarty->fetch('mem_mess_list.htm'), '',
        array('filter' => $list['filter'], 'page_count' => $list['page_count']));
}

/*------------------------------------------------------ */
//-- 消息发送页面
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'send')
{

    $smarty->assign('ur_here',     $_LANG['send_msg']);
    $smarty->assign('action_link', array('href' => 'mem_mess.php?act=list', 'text' => $_LANG['msg_list']));
    $smarty->assign('action',      'add');
    $smarty->assign('form_act',    'insert');

    assign_query_info();
    $smarty->display('mem_mess_info.htm');
}

/*------------------------------------------------------ */
//-- 处理消息的发送
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'insert')
{
    $rec_arr = $_POST['receiver_id'];
    if($rec_arr=='null' || $rec_arr=='' || $rec_arr[0]==''){
        sys_msg("请选择您要发送消息的会员");
    }
    $rec_arr = json_encode($rec_arr);

    $sql = "INSERT INTO " .$ecs->table('member_message'). " (sent_time, title, message, admin_id, sent_all, msg_type) ".
            "VALUES ('".gmtime()."', '".$_POST['title']."', '" .$_POST['message']. "', '".$_SESSION['admin_id']. "', '".$rec_arr."', '" .$_POST['msg_type']. "')";
    $db->query($sql);
    $m_id = $db->insert_id();
    // 根据插入数据的id查询需要发送数据的id，和会员信息[多此一步查询，确保数据同步！]
    $sql = 'SELECT `m_id`, `sent_all` FROM '.$ecs->table('member_message').' WHERE m_id="'.$m_id.'"';
    $sent_all = $GLOBALS['db']->getRow($sql);
    $mem = json_decode($sent_all['sent_all']);
    // 根据查询到的数据进行组装条件数据
    if($mem['0']=='all' && count($mem)==1 ){
        $sql = 'SELECT `user_id` FROM '.$ecs->table('users');
        $mem = $GLOBALS['db']->getCol($sql);
    }
    // 根据usersid进行消息的插入
    $sql = 'INSERT INTO ' .$ecs->table('mem_mess_list'). ' (mem_id, mess_id, receive_time) VALUES ';
    foreach( $mem as $i=>$item ){
        $sql .= '( '.$item.', '.$sent_all['m_id'].', '.gmtime().' ) ,';
        if(count($mem)-1 == $i) $sql = rtrim($sql, ',');
    }
    $state = $db->query($sql);

    $link[0]['text'] = $_LANG['back_list'];
    $link[0]['href'] = 'mem_mess.php?act=list';

    sys_msg($_LANG['send_msg'] . "&nbsp;" . $_LANG['action_succeed'],0, $link);

    /* 记录会员操作 */
    admin_log($_LANG['send_msg'], 'add', 'member_message');
    exit;
}
/*------------------------------------------------------ */
//-- 消息编辑页面
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'edit')
{
    $id = intval($_REQUEST['id']);

    /* 获取会员列表 */
    $admin_list = $db->getAll('SELECT user_id, user_name FROM ' .$ecs->table('admin_user'));

    /* 获得消息数据*/
    $sql = 'SELECT message_id, receiver_id, title, message'.
           'FROM ' .$ecs->table('admin_message'). " WHERE message_id='$id'";
    $msg_arr = $db->getRow($sql);

    $smarty->assign('ur_here',     $_LANG['edit_msg']);
    $smarty->assign('action_link', array('href' => 'mem_mess.php?act=list', 'text' => $_LANG['msg_list']));
    $smarty->assign('form_act',    'update');
    $smarty->assign('admin_list',  $admin_list);
    $smarty->assign('msg_arr',     $msg_arr);

    assign_query_info();
    $smarty->display('message_info.htm');
}
if ($_REQUEST['act'] == 'update')
{
    /* 获得消息数据*/
    $msg_arr = array();
    $msg_arr = $db->getRow('SELECT * FROM ' .$ecs->table('admin_message')." WHERE message_id='$_POST[id]'");

    $sql = "UPDATE " .$ecs->table('admin_message'). " SET ".
           "title = '$_POST[title]',".
           "message = '$_POST[message]'".
           "WHERE sender_id = '$msg_arr[sender_id]' AND sent_time='$msg_arr[send_time]'";
    $db->query($sql);

    $link[0]['text'] = $_LANG['back_list'];
    $link[0]['href'] = 'mem_mess.php?act=list';

    sys_msg($_LANG['edit_msg'] . ' ' . $_LANG['action_succeed'],0, $link);

    /* 记录会员操作 */
    admin_log(addslashes($_LANG['edit_msg']), 'edit', 'admin_message');
}

/*------------------------------------------------------ */
//-- 消息查看页面
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'view')
{
    $msg_id = intval($_REQUEST['id']);

    /* 获得会员消息数据 */
    $msg_arr = array();
    $sql     = "SELECT m.*, a.user_name ".
               "FROM " .$ecs->table('member_message')." AS m ".
               "LEFT JOIN " .$ecs->table('admin_user')." AS a ON a.user_id = m.admin_id ".
               "WHERE m.m_id = '$msg_id'";
    $msg_arr = $db->getRow($sql);

    $msg_arr['sent_all'] = json_decode($msg_arr['sent_all']);
    if(count($msg_arr['sent_all']) == 1 && $msg_arr['sent_all'][0]== 'all'){
        $msg_arr['sent_all'] = '全部会员';
    }else{
        $wh = implode( ',', $msg_arr['sent_all'] );
        $sql = 'SELECT user_name FROM '.$ecs->table('users').' where user_id in ('.$wh.')';
        $user_name = $db->getCol($sql);
        $msg_arr['sent_all'] = implode(',', $user_name);
    }
    $msg_arr['title']    = nl2br(htmlspecialchars($msg_arr['title']));
    $msg_arr['sent_time'] = local_date($GLOBALS['_CFG']['time_format'], $msg_arr['sent_time']);
    $msg_arr['message']  = nl2br(htmlspecialchars($msg_arr['message']));

    /* 如果还未阅读 */
    if ($msg_arr['readed'] == 0)
    {
        $msg_arr['read_time'] = gmtime(); //阅读日期为当前日期

        //更新阅读日期和阅读状态
        $sql = "UPDATE " .$ecs->table('admin_message'). " SET ".
               "read_time = '" . $msg_arr['read_time'] . "', ".
               "readed = '1' ".
               "WHERE message_id = '$msg_id'";
        $db->query($sql);
    }

    //模板赋值，显示
    $smarty->assign('ur_here',     $_LANG['view_msg']);
    $smarty->assign('action_link', array('href' => 'mem_mess.php?act=list', 'text' => $_LANG['msg_list']));
    $smarty->assign('admin_user',  $_SESSION['admin_name']);
    $smarty->assign('msg_arr',     $msg_arr);

    assign_query_info();
    $smarty->display('mem_mess_view.htm');
}

/*------------------------------------------------------ */
//--消息回复页面
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'reply')
{
    $msg_id = intval($_REQUEST['id']);

    /* 获得消息数据 */
    $msg_val = array();
    $sql     = "SELECT a.*, b.user_name ".
               "FROM " .$ecs->table('admin_message')." AS a ".
               "LEFT JOIN " .$ecs->table('admin_user')." AS b ON b.user_id = a.sender_id ".
               "WHERE a.message_id = '$msg_id'";
    $msg_val = $db->getRow($sql);

    $smarty->assign('ur_here',     $_LANG['reply_msg']);
    $smarty->assign('action_link', array('href' => 'mem_mess.php?act=list', 'text' => $_LANG['msg_list']));

    $smarty->assign('action',      'reply');
    $smarty->assign('form_act',    're_msg');
    $smarty->assign('msg_val',     $msg_val);

    assign_query_info();
    $smarty->display('message_info.htm');
}

/*------------------------------------------------------ */
//--消息回复的处理
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 're_msg')
{
    $sql = "INSERT INTO " .$ecs->table('admin_message'). " (sender_id, receiver_id, sent_time, ".
                "read_time, readed, deleted, title, message) ".
           "VALUES ('".$_SESSION['admin_id']."', '$_POST[receiver_id]', '" . gmtime() . "', ".
                "0, '0', '0', '$_POST[title]', '$_POST[message]')";
    $db->query($sql);

    $link[0]['text'] = $_LANG['back_list'];
    $link[0]['href'] = 'mem_mess.php?act=list';

    sys_msg($_LANG['send_msg'] . ' ' . $_LANG['action_succeed'],0, $link);

    /* 记录会员操作 */
    admin_log(addslashes($_LANG['send_msg']), 'add', 'admin_message');
}

/*------------------------------------------------------ */
//-- 批量删除消息记录
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'drop_msg')
{
    if (isset($_POST['checkboxes']))
    {
        $count = count($_POST['checkboxes']);
        // 批量删除消息
        $sql = "DELETE FROM " .$ecs->table('member_message'). " WHERE m_id in(".implode(',', $_POST['checkboxes']).")";
        $db->query($sql);
        admin_log('', 'delete', 'member_message');

        // 删除同此消息关联的会员的消息
        $sql = "DELETE FROM " .$ecs->table('mem_mess_list'). " WHERE mess_id in(".implode(',', $_POST['checkboxes']).")";
        $db->query($sql);

        $link[] = array('text' => $_LANG['back_list'], 'href' => 'mem_mess.php?act=list');
        sys_msg(sprintf($_LANG['batch_drop_success'], $count), 0, $link);
    }
    else
    {
        sys_msg($_LANG['no_select_msg'], 1);
    }
}

/*------------------------------------------------------ */
//-- 删除消息
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'remove')
{
    $id = intval($_GET['id']);

    $sql = "DELETE FROM ".$ecs->table('member_message')." where m_id=".$id;
    $db->query($sql);
    // 删除同此消息关联的会员的消息
    $sql = "DELETE FROM " .$ecs->table('mem_mess_list'). " WHERE mess_id=".$id;
    $db->query($sql);

    $link[] = array('text' => $_LANG['back_list'], 'href' => 'mem_mess.php?act=list');
    sys_msg(sprintf($_LANG['batch_drop_success'], 1), 0, $link);
    exit;
}

/**
 *  获取会员消息列表
 *
 * @return void
 */
function get_mem_mess_list()
{
    $filter['sort_by'] = empty($_REQUEST['sort_by']) ? 'sent_time' : trim($_REQUEST['sort_by']);
    $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);

    $sql = "SELECT COUNT(*) FROM ".$GLOBALS['ecs']->table('member_message');
    $filter['record_count'] = $GLOBALS['db']->getOne($sql);

    /* 分页大小 */
    $filter = page_and_size($filter);

    $sql = "SELECT * " .
            "FROM " . $GLOBALS['ecs']->table('member_message') . " ".
            "ORDER by $filter[sort_by] $filter[sort_order] ".
            "LIMIT " . $filter['start'] . ', ' . $filter['page_size'];
    $row = $GLOBALS['db']->getAll($sql);
    foreach ($row AS $key => $value)
    {
        $row[$key]['sent_time'] = local_date($GLOBALS['_CFG']['time_format'], $value['sent_time']);
        $row[$key]['sent_all'] = $value['sent_all'] == '["all"]' ? '全部会员' : '非全部会员';
    }



    $arr = array('item' => $row, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']);

    return $arr;
}

?>
