<?php
/**
 * 字体管理
 */

define('IN_ECS', true);

require(dirname(__FILE__) . '/includes/init.php');
require_once(ROOT_PATH . 'includes/cls_image.php');

/*初始化数据交换对象 */
$exc = new exchange($ecs->table("font"), $db, 'font_id', 'font_name');
//$image = new cls_image();

/* 允许上传的文件类型 */
$allow_file_types = '|GIF|JPG|PNG|BMP|';
$allow_ttf = '|TTF|';
$allow_eot = '|EOT|';

/*------------------------------------------------------ */
//-- 字体列表
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'list')
{
    /* 取得过滤条件 */
    $filter = array();
    $smarty->assign('type_select',  font_type_list(0));
    $smarty->assign('ur_here',      '字体列表');
    $smarty->assign('action_link',  array('text' => '添加新字体', 'href' => 'font.php?act=add'));
    $smarty->assign('full_page',    1);
    $smarty->assign('filter',       $filter);

    $font_list = get_font_list();

    $smarty->assign('font_list',    $font_list['arr']);
    $smarty->assign('filter',          $font_list['filter']);
    $smarty->assign('record_count',    $font_list['record_count']);
    $smarty->assign('page_count',      $font_list['page_count']);

    $sort_flag  = sort_flag($font_list['filter']);
    $smarty->assign($sort_flag['tag'], $sort_flag['img']);

    assign_query_info();
    $smarty->display('font_list.htm');
}

/*------------------------------------------------------ */
//-- 翻页，排序
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'query')
{
    check_authz_json('font_list');

    $font_list = get_font_list();

    $smarty->assign('font_list',    $font_list['arr']);
    $smarty->assign('filter',          $font_list['filter']);
    $smarty->assign('record_count',    $font_list['record_count']);
    $smarty->assign('page_count',      $font_list['page_count']);

    $sort_flag  = sort_flag($font_list['filter']);
    $smarty->assign($sort_flag['tag'], $sort_flag['img']);

    make_json_result($smarty->fetch('font_list.htm'), '',
        array('filter' => $font_list['filter'], 'page_count' => $font_list['page_count']));
}

/*------------------------------------------------------ */
//-- 添加字体
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'add')
{
    /* 权限判断 */
    admin_priv('font_list');

    $smarty->assign('font',     array());
    $smarty->assign('type_select',  font_type_list(0));
    $smarty->assign('ur_here',     '添加新字体');
    $smarty->assign('action_link', array('text' => '字体列表', 'href' => 'font.php?act=list'));
    $smarty->assign('form_action', 'insert');

    assign_query_info();
    $smarty->display('font_info.htm');
}

/*------------------------------------------------------ */
//-- 添加字体
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'insert')
{
    /* 权限判断 */
    admin_priv('font_list');

    // 字体名称
    $font_name = generate_font_name();

    if (empty($_FILES['font_img']['tmp_name'])) {
        sys_msg('请上传字体图片!');
    }
    if (empty($_FILES['font_file']['tmp_name'])) {
        sys_msg('请上传字体文件!');
    }
    if (empty($_FILES['font_file_ie']['tmp_name'])) {
        sys_msg('请上传字体文件!');
    }

    /* 字体图片 */
    $font_img = '';
    if ((isset($_FILES['font_img']['error']) && $_FILES['font_img']['error'] == 0) || (!isset($_FILES['font_img']['error']) && isset($_FILES['font_img']['tmp_name']) && $_FILES['font_img']['tmp_name'] != 'none'))
    {
        // 检查文件格式
        if (!check_file_type($_FILES['font_img']['tmp_name'], $_FILES['font_img']['name'], $allow_file_types))
        {
            sys_msg('上传文件格式不正确!');
        }

        // 复制文件
        $res = upload_font_img($_FILES['font_img']);
        if ($res != false)
        {
            $font_img = $res;
        }
    } else {
        sys_msg('请上传字体图片!');
    }

    if ($font_img == '')
    {
        $font_img = $_POST['font_img'];
    }

    /* 字体文件 */
    $font_file = '';
    if ((isset($_FILES['font_file']['error']) && $_FILES['font_file']['error'] == 0) || (!isset($_FILES['font_file']['error']) && isset($_FILES['font_file']['tmp_name']) && $_FILES['font_file']['tmp_name'] != 'none'))
    {
        // 检查文件格式
        if (!check_file_type($_FILES['font_file']['tmp_name'], $_FILES['font_file']['name'], $allow_ttf))
        {
            sys_msg('上传文件格式不正确!');
        }

        // 复制文件
        $res = upload_font_file($_FILES['font_file']);
        if ($res != false)
        {
            $font_file = $res;

            // 追加字体样式
            $file_name = basename($font_file);
            $font_css = <<<Eof
@font-face{
    font-family: $font_name;
    src: url("../fonts/$file_name");
}
Eof;
            $css_file = ROOT_PATH . "qdshop/public/static/home/default1/diy/fontstyle/font.css";
            file_put_contents($css_file, $font_css.PHP_EOL, FILE_APPEND);

            // 文件名称，不包括后缀名
            $ttf_file_name = basename($font_file, '.ttf');
        }
    } else {
        sys_msg('请上传字体文件!');
    }

    if ($font_file == '')
    {
        $font_file = $_POST['font_file'];
    }

    /* 兼容IE字体文件 */
    $font_file_ie = '';
    if ((isset($_FILES['font_file_ie']['error']) && $_FILES['font_file_ie']['error'] == 0) || (!isset($_FILES['font_file_ie']['error']) && isset($_FILES['font_file_ie']['tmp_name']) && $_FILES['font_file_ie']['tmp_name'] != 'none'))
    {
        // 检查文件格式
        if (!check_file_type($_FILES['font_file_ie']['tmp_name'], $_FILES['font_file_ie']['name'], $allow_eot))
        {
            sys_msg('上传文件格式不正确!');
        }

        // 复制文件
        $res = upload_font_file($_FILES['font_file_ie'], $ttf_file_name);
        if ($res != false)
        {
            $font_file_ie = $res;

            // 追加字体样式
            $file_name = basename($font_file_ie);
            $font_css = <<<Eof
@font-face{
    font-family: $font_name;
    src: url("../fonts/$file_name?#iefix") format('embedded-opentype');
}
Eof;
            $css_file = ROOT_PATH . "qdshop/public/static/home/default1/diy/fontstyle/font_ie.css";
            file_put_contents($css_file, $font_css.PHP_EOL, FILE_APPEND);
        }
    } else {
        sys_msg('请上传字体文件!');
    }

    if ($font_file_ie == '')
    {
        $font_file_ie = $_POST['font_file_ie'];
    }

    /*插入数据*/
    if (empty($_POST['type_id']))
    {
        $_POST['type_id'] = 0;
    }

    $sql = "INSERT INTO ".$ecs->table('font')."(font_name, type_id, font_img, font_file, font_file_ie, sort_order, is_show) ".
            "VALUES ('$font_name', '$_POST[type_id]', '$font_img', '$font_file', ". "'$font_file_ie', '$_POST[sort_order]', '$_POST[is_show]')";
    $db->query($sql);

    $link[0]['text'] = '继续添加新字体';
    $link[0]['href'] = 'font.php?act=add';

    $link[1]['text'] = '返回字体列表';
    $link[1]['href'] = 'font.php?act=list';

    admin_log($font_name,'add','font');

    clear_cache_files(); // 清除相关的缓存文件

    sys_msg('字体已经添加成功',0, $link);
}

/*------------------------------------------------------ */
//-- 编辑
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'edit')
{
    /* 权限判断 */
    admin_priv('font_list');

    /* 取字体数据 */
    $sql = "SELECT * FROM " .$ecs->table('font'). " WHERE font_id='$_REQUEST[id]'";
    $font = $db->GetRow($sql);

    $smarty->assign('font',     $font);
    $smarty->assign('type_select',  font_type_list(0, $font['type_id']));
    $smarty->assign('ur_here',     '编辑字体');
    $smarty->assign('action_link', array('text' => '字体列表', 'href' => 'font.php?act=list&' . list_link_postfix()));
    $smarty->assign('form_action', 'update');

    assign_query_info();
    $smarty->display('font_info.htm');
}

if ($_REQUEST['act'] =='update')
{
    /* 权限判断 */
    admin_priv('font_list');

    $id = intval($_POST['id']);
    $type_id = intval($_POST['type_id']);
    $font_name = trim($_POST['font_name']);

    if (empty($_POST['type_id']))
    {
        $_POST['type_id'] = 0;
    }

    /* 字体图片 */
    $font_img = '';
    if (empty($_FILES['font_img']['error']) || (!isset($_FILES['font_img']['error']) && isset($_FILES['font_img']['tmp_name']) && $_FILES['font_img']['tmp_name'] != 'none'))
    {
        // 检查文件格式
        if (!check_file_type($_FILES['font_img']['tmp_name'], $_FILES['font_img']['name'], $allow_file_types))
        {
            sys_msg('上传文件格式不正确!');
        }

        // 复制文件
        $res = upload_font_img($_FILES['font_img']);
        if ($res != false)
        {
            $font_img = $res;
        }
    }

    if ($font_img == '')
    {
        $font_img = $_POST['font_img'];
    }

    /* 如果 font_img 跟以前不一样，且原来的文件是本地文件，删除原来的文件 */
    $sql = "SELECT font_img FROM " . $ecs->table('font') . " WHERE font_id = '$id'";
    $old_url = $db->getOne($sql);
    if ($old_url != '' && $old_url != $font_img && strpos($old_url, 'http://') === false && strpos($old_url, 'https://') === false)
    {
        @unlink(ROOT_PATH . $old_url);
    }

    /* 字体文件 */
    $font_file = '';
    if (empty($_FILES['font_file']['error']) || (!isset($_FILES['font_file']['error']) && isset($_FILES['font_file']['tmp_name']) && $_FILES['font_file']['tmp_name'] != 'none'))
    {
        // 检查文件格式
        if (!check_file_type($_FILES['font_file']['tmp_name'], $_FILES['font_file']['name'], $allow_ttf))
        {
            sys_msg('上传文件格式不正确!');
        }

        // 复制文件
        $res = upload_font_file($_FILES['font_file']);
        if ($res != false)
        {
            $font_file = $res;

            // 文件名称，不包括后缀名
            $ttf_file_name = basename($font_file, '.ttf');
        }
    }

    if ($font_file == '')
    {
        $font_file = $_POST['font_file'];
    }

    /* 如果 font_file 跟以前不一样，且原来的文件是本地文件，删除原来的文件 */
    $sql = "SELECT font_file FROM " . $ecs->table('font') . " WHERE font_id = '$id'";
    $old_url = $db->getOne($sql);
    if ($old_url != '' && $old_url != $font_file && strpos($old_url, 'http://') === false && strpos($old_url, 'https://') === false)
    {
        @unlink(ROOT_PATH . $old_url);

        // 修改字体样式
        $old_file_name = basename($old_url);
        $new_file_name = basename($font_file);
        $origin_css = file_get_contents(ROOT_PATH . "qdshop/public/static/home/default1/diy/fontstyle/font.css");
        $update_css = str_replace($old_file_name, $new_file_name, $origin_css);
        file_put_contents(ROOT_PATH . "qdshop/public/static/home/default1/diy/fontstyle/font.css", $update_css);
    }

    /* 兼容IE字体文件 */
    $font_file_ie = '';
    if (empty($_FILES['font_file_ie']['error']) || (!isset($_FILES['font_file_ie']['error']) && isset($_FILES['font_file_ie']['tmp_name']) && $_FILES['font_file_ie']['tmp_name'] != 'none'))
    {
        // 检查文件格式
        if (!check_file_type($_FILES['font_file_ie']['tmp_name'], $_FILES['font_file_ie']['name'], $allow_eot))
        {
            sys_msg('上传文件格式不正确!');
        }

        // 复制文件
        $res = upload_font_file($_FILES['font_file_ie'], $ttf_file_name);
        if ($res != false)
        {
            $font_file_ie = $res;
        }
    }

    if ($font_file_ie == '')
    {
        $font_file_ie = $_POST['font_file_ie'];
    }

    /* 如果 font_file 跟以前不一样，且原来的文件是本地文件，删除原来的文件 */
    $sql = "SELECT font_file_ie FROM " . $ecs->table('font') . " WHERE font_id = '$id'";
    $old_url = $db->getOne($sql);
    if ($old_url != '' && $old_url != $font_file_ie && strpos($old_url, 'http://') === false && strpos($old_url, 'https://') === false)
    {
        @unlink(ROOT_PATH . $old_url);

        // 修改字体样式
        $old_file_name = basename($old_url);
        $new_file_name = basename($font_file_ie);
        $origin_css = file_get_contents(ROOT_PATH . "qdshop/public/static/home/default1/diy/fontstyle/font_ie.css");
        $update_css = str_replace($old_file_name, $new_file_name, $origin_css);
        file_put_contents(ROOT_PATH . "qdshop/public/static/home/default1/diy/fontstyle/font_ie.css", $update_css);
    }

    if ($exc->edit("font_name='$font_name', type_id='$type_id', font_img='$font_img', font_file='$font_file', font_file_ie='$font_file_ie', is_show='$_POST[is_show]', sort_order = '$_POST[sort_order]'", $id))
    {
        $link[0]['text'] = '返回字体列表';
        $link[0]['href'] = 'font.php?act=list&' . list_link_postfix();

        $note = sprintf('字体 %s 成功编辑', stripslashes($font_name));
        admin_log($font_name, 'edit', 'font');

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
    check_authz_json('font_list');

    $id = intval($_POST['id']);
    $val = intval($_POST['val']);

    $exc->edit("is_show = '$val'", $id);
    clear_cache_files();

    make_json_result($val);
}

/*------------------------------------------------------ */
//-- 删除字体主题
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'remove')
{
    check_authz_json('font_list');

    $id = intval($_GET['id']);

    /* 删除原来的文件 */
    $sql = "SELECT font_name, font_img, font_file, font_file_ie FROM " . $ecs->table('font') . " WHERE font_id = '$id'";
    $font_info = $db->getRow($sql);
    $font_name = $font_info['font_name'];
    if ($font_info['font_img'] != '' && strpos($font_info['font_img'], 'http://') === false && strpos($font_info['font_img'], 'https://') === false)
    {
        @unlink(ROOT_PATH . $font_info['font_img']);

    }
    if ($font_info['font_file'] != '' && strpos($font_info['font_file'], 'http://') === false && strpos($font_info['font_file'], 'https://') === false)
    {
        @unlink(ROOT_PATH . $font_info['font_file']);

        // 修改字体样式
        $file_name = basename($font_info['font_file']);
        $font_css = <<<Eof
@font-face{
    font-family: $font_name;
    src: url("../fonts/$file_name");
}
Eof;
        $origin_css = file_get_contents(ROOT_PATH . "qdshop/public/static/home/default1/diy/fontstyle/font.css");
        $update_css = str_replace($font_css, '', $origin_css);
        file_put_contents(ROOT_PATH . "qdshop/public/static/home/default1/diy/fontstyle/font.css", $update_css);
    }
    if ($font_info['font_file_ie'] != '' && strpos($font_info['font_file_ie'], 'http://') === false && strpos($font_info['font_file_ie'], 'https://') === false)
    {
        @unlink(ROOT_PATH . $font_info['font_file_ie']);

        // 修改字体样式
        $file_name = basename($font_info['font_file_ie']);
        $font_css = <<<Eof
@font-face{
    font-family: $font_name;
    src: url("../fonts/$file_name?#iefix") format('embedded-opentype');
}
Eof;
        $origin_css = file_get_contents(ROOT_PATH . "qdshop/public/static/home/default1/diy/fontstyle/font_ie.css");
        $update_css = str_replace($font_css, '', $origin_css);
        file_put_contents(ROOT_PATH . "qdshop/public/static/home/default1/diy/fontstyle/font_ie.css", $update_css);
    }

    $name = $exc->get_name($id);
    if ($exc->drop($id))
    {
        admin_log(addslashes($name),'remove','font');
        clear_cache_files();
    }

    $url = 'font.php?act=query&' . str_replace('act=remove', '', $_SERVER['QUERY_STRING']);

    ecs_header("Location: $url\n");
    exit;
}

/* 获得字体列表 */
function get_font_list()
{
    $result = get_filter();
    if ($result === false)
    {
        $filter = array();
        $filter['type_id'] = empty($_REQUEST['type_id']) ? 0 : intval($_REQUEST['type_id']);
        $filter['sort_by']    = empty($_REQUEST['sort_by']) ? 'a.font_id' : trim($_REQUEST['sort_by']);
        $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);

        $where = '';
        if ($filter['type_id'])
        {
            $where .= " AND a." . get_font_children($filter['type_id']);
        }

        /* 字体总数 */
        $sql = 'SELECT COUNT(*) FROM ' .$GLOBALS['ecs']->table('font'). ' AS a '.
               'LEFT JOIN ' .$GLOBALS['ecs']->table('font_type'). ' AS ac ON ac.type_id = a.type_id '.
               'WHERE 1 ' .$where;
        $filter['record_count'] = $GLOBALS['db']->getOne($sql);

        $filter = page_and_size($filter);

        /* 获取字体数据 */
        $sql = 'SELECT a.* , ac.type_name '.
               'FROM ' .$GLOBALS['ecs']->table('font'). ' AS a '.
               'LEFT JOIN ' .$GLOBALS['ecs']->table('font_type'). ' AS ac ON ac.type_id = a.type_id '.
               'WHERE 1 ' .$where. ' ORDER by '.$filter['sort_by'].' '.$filter['sort_order'];

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

/* 上传字体图片 */
function upload_font_img($upload)
{
    if (!make_dir("../" . DATA_DIR . "/font"))
    {
        /* 创建目录失败 */
        return false;
    }

    $filename = cls_image::random_filename() . substr($upload['name'], strpos($upload['name'], '.'));

    $path = ROOT_PATH. DATA_DIR . "/font/" . $filename;

    if (move_upload_file($upload['tmp_name'], $path))
    {
        return DATA_DIR . "/font/" . $filename;
    }
    else
    {
        return false;
    }
}

/* 上传字体文件 */
function upload_font_file($upload, $file_name = '')
{
    if (!make_dir("../" . "qdshop/public/static/home/default1/diy/fonts"))
    {
        /* 创建目录失败 */
        return false;
    }

    if (!empty($file_name)) {
        $filename = $file_name . substr($upload['name'], strpos($upload['name'], '.'));
    } else {
        $filename = cls_image::random_filename() . substr($upload['name'], strpos($upload['name'], '.'));
    }

    $path = ROOT_PATH . "qdshop/public/static/home/default1/diy/fonts/" . $filename;

    if (move_upload_file($upload['tmp_name'], $path))
    {
        return "qdshop/public/static/home/default1/diy/fonts/" . $filename;
    }
    else
    {
        return false;
    }
}

/**
 * 生成唯一字体名称
 */
function generate_font_name ()
{
    $font_name = 'FONT'.rand_number(2);

    $charts = "ABCDEFGHJKLMNPQRSTUVWXYZ";
    $max = strlen($charts);

    for($i = 0; $i < 2; $i ++)
    {
        $font_name .= $charts[mt_rand(0, $max)];
    }

    $font_name .= rand_number(2);

    $sql = "SELECT COUNT(*) from " . $GLOBALS['ecs']->table('font') . " WHERE font_name = '$font_name'";
    $count = $GLOBALS['db']->getOne($sql);
    if($count > 0)
    {
        return generate_font_name();
    }

    return $font_name;
}

?>