<?php
/**
 * 蒙版管理
 */

define('IN_ECS', true);

require(dirname(__FILE__) . '/includes/init.php');
require_once(ROOT_PATH . 'includes/cls_image.php');

/*初始化数据交换对象 */
$exc = new exchange($ecs->table("mask"), $db, 'mask_id', 'mask_name');
//$image = new cls_image();

/* 允许上传的文件类型 */
$allow_file_types = '|GIF|JPG|PNG|BMP|';

/*------------------------------------------------------ */
//-- 蒙版列表
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'list')
{
    /* 取得过滤条件 */
    $filter = array();
    $smarty->assign('ur_here',      '蒙版列表');
    $smarty->assign('action_link',  array('text' => '添加新蒙版', 'href' => 'mask.php?act=add'));
    $smarty->assign('full_page',    1);
    $smarty->assign('filter',       $filter);

    $mask_list = get_mask_list();

    $smarty->assign('mask_list',    $mask_list['arr']);
    $smarty->assign('filter',          $mask_list['filter']);
    $smarty->assign('record_count',    $mask_list['record_count']);
    $smarty->assign('page_count',      $mask_list['page_count']);

    $sort_flag  = sort_flag($mask_list['filter']);
    $smarty->assign($sort_flag['tag'], $sort_flag['img']);

    assign_query_info();
    $smarty->display('mask_list.htm');
}

/*------------------------------------------------------ */
//-- 翻页，排序
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'query')
{
    check_authz_json('mask_list');

    $mask_list = get_mask_list();

    $smarty->assign('mask_list',    $mask_list['arr']);
    $smarty->assign('filter',          $mask_list['filter']);
    $smarty->assign('record_count',    $mask_list['record_count']);
    $smarty->assign('page_count',      $mask_list['page_count']);

    $sort_flag  = sort_flag($mask_list['filter']);
    $smarty->assign($sort_flag['tag'], $sort_flag['img']);

    make_json_result($smarty->fetch('mask_list.htm'), '',
        array('filter' => $mask_list['filter'], 'page_count' => $mask_list['page_count']));
}

/*------------------------------------------------------ */
//-- 添加蒙版
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'add')
{
    /* 权限判断 */
    admin_priv('mask_list');

    $smarty->assign('mask',     array());
    $smarty->assign('ur_here',     '添加新蒙版');
    $smarty->assign('action_link', array('text' => '蒙版列表', 'href' => 'mask.php?act=list'));
    $smarty->assign('form_action', 'insert');

    assign_query_info();
    $smarty->display('mask_info.htm');
}

/*------------------------------------------------------ */
//-- 添加字体
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'insert')
{
    /* 权限判断 */
    admin_priv('mask_list');

    $mask_name = trim($_POST['mask_name']);   //蒙版名称
    $mask_code = trim($_POST['mask_code']);   //蒙版JS代码

    /*检查蒙版名是否重复*/
    $is_only = $exc->is_only('mask_name', $mask_name);

    if (!$is_only)
    {
        sys_msg(sprintf('蒙版名称已存在', stripslashes($mask_name)), 1);
    }

    if (!mask_name_ok($mask_name)) {
        sys_msg('蒙版名称不合法!');
    }

    /* 蒙版图片 */
    $mask_img = '';
    if ((isset($_FILES['mask_img']['error']) && $_FILES['mask_img']['error'] == 0) || (!isset($_FILES['mask_img']['error']) && isset($_FILES['mask_img']['tmp_name']) && $_FILES['mask_img']['tmp_name'] != 'none'))
    {
        // 检查文件格式
        if (!check_file_type($_FILES['mask_img']['tmp_name'], $_FILES['mask_img']['name'], $allow_file_types))
        {
            sys_msg('上传文件格式不正确!');
        }

        // 复制文件
        $res = upload_mask_img($_FILES['mask_img']);
        if ($res != false)
        {
            $mask_img = $res;
        }
    } else {
        sys_msg('请上传蒙版图片!');
    }

    if ($mask_img == '')
    {
        $mask_img = $_POST['mask_img'];
    }

    $sql = "INSERT INTO ".$ecs->table('mask')."(mask_name, mask_img, mask_code, sort_order, is_show) ".
            "VALUES ('$mask_name', '$mask_img', '$mask_code', '$_POST[sort_order]', '$_POST[is_show]')";
    $db->query($sql);

    // 组织蒙版代码
    mask_code();

    $link[0]['text'] = '继续添加新蒙版';
    $link[0]['href'] = 'mask.php?act=add';

    $link[1]['text'] = '返回蒙版列表';
    $link[1]['href'] = 'mask.php?act=list';

    admin_log($mask_name,'add','mask');

    clear_cache_files(); // 清除相关的缓存文件

    sys_msg('蒙版已经添加成功',0, $link);
}

/*------------------------------------------------------ */
//-- 编辑
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'edit')
{
    /* 权限判断 */
    admin_priv('mask_list');

    /* 取字体数据 */
    $sql = "SELECT * FROM " .$ecs->table('mask'). " WHERE mask_id='$_REQUEST[id]'";
    $mask = $db->GetRow($sql);

    $smarty->assign('mask',     $mask);
    $smarty->assign('ur_here',     '编辑蒙版');
    $smarty->assign('action_link', array('text' => '蒙版列表', 'href' => 'mask.php?act=list&' . list_link_postfix()));
    $smarty->assign('form_action', 'update');

    assign_query_info();
    $smarty->display('mask_info.htm');
}

if ($_REQUEST['act'] =='update')
{
    /* 权限判断 */
    admin_priv('mask_list');

    $id = intval($_POST['id']);
    $mask_name = trim($_POST['mask_name']);
    $mask_code = trim($_POST['mask_code']);

    /*检查重名*/
    if ($mask_name != $_POST['old_mask_name'])
    {
        $is_only = $exc->is_only('mask_name', $mask_name, $id);

        if (!$is_only)
        {
            sys_msg(sprintf('蒙版名称已存在', stripslashes($mask_name)), 1);
        }
    }

    if (!mask_name_ok($mask_name)) {
        sys_msg('蒙版名称不合法!');
    }

    /* 蒙版图片 */
    $mask_img = '';
    if (empty($_FILES['mask_img']['error']) || (!isset($_FILES['mask_img']['error']) && isset($_FILES['mask_img']['tmp_name']) && $_FILES['mask_img']['tmp_name'] != 'none'))
    {
        // 检查文件格式
        if (!check_file_type($_FILES['mask_img']['tmp_name'], $_FILES['mask_img']['name'], $allow_file_types))
        {
            sys_msg('上传文件格式不正确!');
        }

        // 复制文件
        $res = upload_mask_img($_FILES['mask_img']);
        if ($res != false)
        {
            $mask_img = $res;
        }
    }

    if ($mask_img == '')
    {
        $mask_img = $_POST['mask_img'];
    }

    /* 如果 mask_img 跟以前不一样，且原来的文件是本地文件，删除原来的文件 */
    $sql = "SELECT mask_img FROM " . $ecs->table('mask') . " WHERE mask_id = '$id'";
    $old_url = $db->getOne($sql);
    if ($old_url != '' && $old_url != $mask_img && strpos($old_url, 'http://') === false && strpos($old_url, 'https://') === false)
    {
        @unlink(ROOT_PATH . $old_url);
    }

    if ($exc->edit("mask_name='$mask_name', mask_img='$mask_img', mask_code='$mask_code', is_show='$_POST[is_show]', sort_order = '$_POST[sort_order]'", $id))
    {
        // 组织蒙版代码
        mask_code();

        $link[0]['text'] = '返回蒙版列表';
        $link[0]['href'] = 'mask.php?act=list&' . list_link_postfix();

        $note = sprintf('蒙版 %s 成功编辑', stripslashes($mask_name));
        admin_log($mask_name, 'edit', 'mask');

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
    check_authz_json('mask_list');

    $id = intval($_POST['id']);
    $val = intval($_POST['val']);

    $exc->edit("is_show = '$val'", $id);
    // 组织蒙版代码
    mask_code();

    clear_cache_files();

    make_json_result($val);
}

/*------------------------------------------------------ */
//-- 删除蒙版
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'remove')
{
    check_authz_json('mask_list');

    $id = intval($_GET['id']);

    /* 删除原来的文件 */
    $sql = "SELECT mask_img FROM " . $ecs->table('mask') . " WHERE mask_id = '$id'";
    $mask_img = $db->getOne($sql);
    if ($mask_img != '' && strpos($mask_img, 'http://') === false && strpos($mask_img, 'https://') === false)
    {
        @unlink(ROOT_PATH . $mask_img);

    }

    $name = $exc->get_name($id);
    if ($exc->drop($id))
    {
        admin_log(addslashes($name),'remove','mask');
        clear_cache_files();
    }

    // 组织蒙版代码
    mask_code();

    $url = 'mask.php?act=query&' . str_replace('act=remove', '', $_SERVER['QUERY_STRING']);

    ecs_header("Location: $url\n");
    exit;
}

/* 获得蒙版列表 */
function get_mask_list()
{
    $result = get_filter();
    if ($result === false)
    {
        $filter = array();
        $filter['sort_by']    = empty($_REQUEST['sort_by']) ? 'mask_id' : trim($_REQUEST['sort_by']);
        $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'ASC' : trim($_REQUEST['sort_order']);

        $where = '';

        /* 字体总数 */
        $sql = 'SELECT COUNT(*) FROM ' . $GLOBALS['ecs']->table('mask'). ' WHERE 1 ' . $where;
        $filter['record_count'] = $GLOBALS['db']->getOne($sql);

        $filter = page_and_size($filter);

        /* 获取字体数据 */
        $sql = 'SELECT * FROM ' . $GLOBALS['ecs']->table('mask') . ' WHERE 1 ' . $where . ' ORDER by '.$filter['sort_by'].' '.$filter['sort_order'];

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

/* 上传蒙版图片 */
function upload_mask_img($upload)
{
    if (!make_dir("../" . DATA_DIR . "/mask"))
    {
        /* 创建目录失败 */
        return false;
    }

    $filename = cls_image::random_filename() . substr($upload['name'], strpos($upload['name'], '.'));

    $path = ROOT_PATH. DATA_DIR . "/mask/" . $filename;

    if (move_upload_file($upload['tmp_name'], $path))
    {
        return DATA_DIR . "/mask/" . $filename;
    }
    else
    {
        return false;
    }
}

/**
 * 组织蒙版代码
 */
function mask_code() {
    $sql = "SELECT * FROM " . $GLOBALS['ecs']->table('mask') . " WHERE is_show = 1 ORDER BY sort_order ASC";
    $mask_list = $GLOBALS['db']->getAll($sql);
    $mask_js_code = "var canvasDraw=[\r\n";
    foreach ($mask_list as $key => $value) {
        $mask_js_code .= $value['mask_code'] . ',';
    }
    $mask_js_code = rtrim($mask_js_code, ',');
    $mask_js_code .= "\r\n];";
    $mask_js_file = ROOT_PATH . "qdshop/public/static/home/default1/diy/js/mask.js";
    file_put_contents($mask_js_file, $mask_js_code);
}

/**
 * 判断蒙版名称是否合法
 */
function mask_name_ok($mask_name) {
    return preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/',$mask_name);
}

?>