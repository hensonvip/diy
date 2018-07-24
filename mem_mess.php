<?php

/**
 * 站内信消息
*/

define('IN_ECS', true);

require(dirname(__FILE__) . '/includes/init.php');

// if (empty($_CFG['message_board']))
// {
//     show_message($_LANG['message_board_close']);
// }


/*
 * 检查用户是否已经登录
 * 如果没有登录则跳转到登录和注册页面
 */
if ($_SESSION['user_id'] == 0){
    /* 用户没有登录且没有选定匿名购物，转向到登录页面 */
    ecs_header("Location: user.php\n");
    exit;
}


$action  = isset($_REQUEST['act']) ? trim($_REQUEST['act']) : 'default';

// 这是action供左侧栏目判断   
$smarty->assign('action', 'my_message');

$affiliate = unserialize($GLOBALS['_CFG']['affiliate']);
$smarty->assign('affiliate', $affiliate);

/*   用户中心获取会员信息*/
include_once (ROOT_PATH . 'includes/lib_clips.php');
if($rank = get_rank_info())
{
    $smarty->assign('rank_name', $rank['rank_name']);
    if(! empty($rank['next_rank_name']))
    {
        $smarty->assign('next_rank_name', sprintf($_LANG['next_level'], $rank['next_rank'], $rank['next_rank_name']));
    }
    $rn = $rank['rank_name'];
    $recomm = $db->getOne("SELECT is_recomm FROM " . $GLOBALS['ecs']->table('user_rank') . " WHERE rank_name= '$rn'");
    $smarty->assign('recomm', $recomm); // 获取当前用户是否是分成用户判断是否显示我的推荐
}


if ($action == 'act_add_message')
{
    include_once(ROOT_PATH . 'includes/lib_clips.php');

    /* 验证码防止灌水刷屏 */
    if ((intval($_CFG['captcha']) & CAPTCHA_MESSAGE) && gd_version() > 0)
    {
        include_once('includes/cls_captcha.php');
        $validator = new captcha();
        if (!$validator->check_word($_POST['captcha']))
        {
            show_message($_LANG['invalid_captcha']);
        }
    }
    else
    {
        /* 没有验证码时，用时间来限制机器人发帖或恶意发评论 */
        if (!isset($_SESSION['send_time']))
        {
            $_SESSION['send_time'] = 0;
        }

        $cur_time = gmtime();
        if (($cur_time - $_SESSION['send_time']) < 30) // 小于30秒禁止发评论
        {
            show_message($_LANG['cmt_spam_warning']);
        }
    }
    $user_name = '';
    if (empty($_POST['anonymous']) && !empty($_SESSION['user_name']))
    {
        $user_name = $_SESSION['user_name'];
    }
    elseif (!empty($_POST['anonymous']) && !isset($_POST['user_name']))
    {
        $user_name = $_LANG['anonymous'];
    }
    elseif (empty($_POST['user_name']))
    {
        $user_name = $_LANG['anonymous'];
    }
    else
    {
        $user_name = htmlspecialchars(trim($_POST['user_name']));
    }

    $user_id = !empty($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;
    $message = array(
        'user_id'     => $user_id,
        'user_name'   => $user_name,
        'user_email'  => isset($_POST['user_email']) ? htmlspecialchars(trim($_POST['user_email']))     : '',
        'msg_type'    => isset($_POST['msg_type']) ? intval($_POST['msg_type'])     : 0,
        'msg_title'   => isset($_POST['msg_title']) ? trim($_POST['msg_title'])     : '',
        'msg_content' => isset($_POST['msg_content']) ? trim($_POST['msg_content']) : '',
        'order_id'    => 0,
        'msg_area'    => 1,
        'upload'      => array()
     );

    if (add_message($message))
    {
        if (intval($_CFG['captcha']) & CAPTCHA_MESSAGE)
        {
            unset($_SESSION[$validator->session_word]);
        }
        else
        {
            $_SESSION['send_time'] = $cur_time;
        }
        $msg_info = $_CFG['message_check'] ? $_LANG['message_submit_wait'] : $_LANG['message_submit_done'];
        show_message($msg_info, $_LANG['message_list_lnk'], 'message.php');
    }
    else
    {
        $err->show($_LANG['message_list_lnk'], 'message.php');
    }
}

if ($action == 'default')
{
    assign_template();
    $smarty->assign('page_title', '我的消息列表');    // 页面标题
    $smarty->assign('helps',      get_shop_help());       // 网店帮助

    $smarty->assign('categories', get_categories_tree()); // 分类树
    $smarty->assign('top_goods',  get_top10());           // 销售排行
    $smarty->assign('cat_list',   cat_list(0, 0, true, 2, false));
    $smarty->assign('brand_list', get_brand_list());
    $smarty->assign('promotion_info', get_promotion_info());

    $smarty->assign('enabled_mes_captcha', (intval($_CFG['captcha']) & CAPTCHA_MESSAGE));
    $sele = isset($_REQUEST['sele']) ? trim($_REQUEST['sele']) : '';

    /*数据删除*/
    if( isset($sele) && $sele == 'del' ){
        $id = intval($_REQUEST['id']);
        if(!empty($id)){
            $id = intval($id);
            $sql = ' DELETE FROM '.$GLOBALS['ecs']->table('mem_mess_list').' WHERE mem_id = '.$_SESSION['user_id'].' and l_id = '.$id;
            $del = $db->query($sql);
            show_message('删除成功！！！', '返回消息列表主页', 'mem_mess.php');exit;
        }
    }

    $sql = "SELECT COUNT(*) FROM " .$GLOBALS['ecs']->table('mem_mess_list')." WHERE mem_id = ".$_SESSION['user_id']." ";
    $record_count = $db->getOne($sql);

    /* 获取留言的数量 */
    $page = isset($_REQUEST['page']) ? intval($_REQUEST['page']) : 1;
    $pagesize = get_library_number('message_list', 'message_board');
    $pager = get_pager('mem_mess.php', array(), $record_count, $page, $pagesize);
    $msg_lists = get_msg_list($pagesize, $pager['start']);

    // assign_dynamic('message_board');
    $smarty->assign('rand',      mt_rand());
    $smarty->assign('msg_lists', $msg_lists);
    $smarty->assign('pager', $pager);
    $smarty->display('mem_mess.dwt');
}

if ($action == 'details')
{
    assign_template();
    $smarty->assign('page_title', '我的消息详情');    // 页面标题
    $smarty->assign('helps',      get_shop_help());       // 网店帮助

    $smarty->assign('categories', get_categories_tree()); // 分类树
    $smarty->assign('top_goods',  get_top10());           // 销售排行
    $smarty->assign('cat_list',   cat_list(0, 0, true, 2, false));
    $smarty->assign('brand_list', get_brand_list());
    $smarty->assign('promotion_info', get_promotion_info());

    $smarty->assign('enabled_mes_captcha', (intval($_CFG['captcha']) & CAPTCHA_MESSAGE));
    $id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;

    $sql = "SELECT ml.l_id, ml.receive_time, mm.title, mm.message FROM " .$GLOBALS['ecs']->table('mem_mess_list')." as ml ".
            " left join ".$GLOBALS['ecs']->table('member_message'). " as mm on mm.m_id = ml.mess_id".
            " WHERE ml.mem_id=".$_SESSION['user_id']." and ml.l_id=".$id;
    $record_details = $db->getRow($sql);
    // 更改此记录为已读，并更新已读时间
    $l_id = $record_details['l_id'];
    $sql = ' UPDATE '.$GLOBALS['ecs']->table('mem_mess_list').' SET read_time='.gmtime().', readed = 1 WHERE l_id='.$l_id;
    $db->query($sql);

    $record_details['receive_time'] = date('Y-m-d H:i:s', $record_details['receive_time']);
    $smarty->assign('record_details', $record_details);
    
    $smarty->display('mem_mess_details.dwt');
}


if ($action == 'sele')
{
    $change = isset($_POST['change']) ? trim($_POST['change']) : '';
    // 执行标为已读操作
    if(!empty($_POST['change'])){
        $sql = ' UPDATE '.$GLOBALS['ecs']->table('mem_mess_list').' SET read_time='.gmtime().', readed = 1 WHERE mem_id = '.$_SESSION['user_id'];
        $db->query($sql);
        header("location:mem_mess.php");exit();
    }

    $del = isset($_POST['del']) ? trim($_POST['del']) : '';
    $mess_id = implode(',', $_POST['mess_id']);
    if($mess_id == ''){
        show_message('请选择数据！');exit;
    }
    
    if(!empty($_POST['del'])){
    // 执行批量删除操作
        $sql = ' DELETE FROM '.$GLOBALS['ecs']->table('mem_mess_list').' WHERE mem_id = '.$_SESSION['user_id'].' and l_id in('.$mess_id.')';
        $del = $db->query($sql);
        header("location:mem_mess.php");exit();
    }else{
        show_message('非法进入');exit;
    }
    
}

/**
 * 获取消息列表
 *
 * @param   integer $num
 * @param   integer $start
 * @param   string  $where
 * @return  array
 */
function get_msg_list($num, $start)
{
    /* 获取消息数据 */
    $sql = "SELECT ml.l_id, ml.receive_time, mm.title,ml.readed FROM " .$GLOBALS['ecs']->table('mem_mess_list')." as ml ".
        " left join ".$GLOBALS['ecs']->table('member_message'). " as mm on mm.m_id = ml.mess_id".
        " WHERE ml.mem_id=".$_SESSION['user_id']." order by receive_time desc ";
    $res = $GLOBALS['db']->SelectLimit($sql, $num, $start);
    $result = array();
    while ($rows = $GLOBALS['db']->fetchRow($res)){
        $rows['receive_time'] = date('Y-m-d H:i:s',$rows['receive_time']);
        $result[] = $rows;
    }

    return $result;
}

?>
