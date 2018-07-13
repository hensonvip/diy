<?php
/**
 * 图形管理
 */

define('IN_ECS', true);

require(dirname(__FILE__) . '/includes/init.php');
require_once(ROOT_PATH . 'includes/cls_image.php');
require_once(ROOT_PATH . 'includes/Unzip.php');

/*初始化数据交换对象 */
$exc = new exchange($ecs->table("graph"), $db, 'graph_id', 'graph_name');
//$image = new cls_image();

/* 允许上传的文件类型 */
$allow_zip = '|ZIP|';
$allow_svg = '|SVG|';

/*------------------------------------------------------ */
//-- 图形列表
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'list')
{
    /* 取得过滤条件 */
    $filter = array();
    $smarty->assign('type_select',  graph_type_list());
    $smarty->assign('ur_here',      '图形列表');
    $smarty->assign('action_link',  array('text' => '添加新图形', 'href' => 'graph.php?act=add'));
    $smarty->assign('action_link2',  array('text' => '批量导入图形', 'href' => 'graph.php?act=import'));
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
    $smarty->display('graph_list.htm');
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

    make_json_result($smarty->fetch('graph_list.htm'), '',
        array('filter' => $graph_list['filter'], 'page_count' => $graph_list['page_count']));
}

/*------------------------------------------------------ */
//-- 添加图形
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'add')
{
    /* 权限判断 */
    admin_priv('graph_list');

    $smarty->assign('graph',     array());
    $smarty->assign('type_select',  graph_type_list());
    $smarty->assign('ur_here',     '添加新图形');
    $smarty->assign('action_link', array('text' => '图形列表', 'href' => 'graph.php?act=list'));
    $smarty->assign('form_action', 'insert');

    assign_query_info();
    $smarty->display('graph_info.htm');
}

/*------------------------------------------------------ */
//-- 添加图形
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'insert')
{
    /* 权限判断 */
    admin_priv('graph_list');

    /* 图形图片 */
    $graph_file = '';
    if ((isset($_FILES['graph_file']['error']) && $_FILES['graph_file']['error'] == 0) || (!isset($_FILES['graph_file']['error']) && isset($_FILES['graph_file']['tmp_name']) && $_FILES['graph_file']['tmp_name'] != 'none'))
    {
        // 检查文件格式
        if (!check_file_type($_FILES['graph_file']['tmp_name'], $_FILES['graph_file']['name'], $allow_svg))
        {
            sys_msg('上传文件格式不正确!');
        }

        // 复制文件
        $res = upload_graph_file($_FILES['graph_file'], 'svg'.$_POST['type_id']);
        if ($res != false)
        {
            $graph_file = $res;
        }
    } else {
        sys_msg('请上传图形文件!');
    }

    if ($graph_file == '')
    {
        $graph_file = $_POST['graph_file'];
    }

    /*插入数据*/
    if (empty($_POST['type_id']))
    {
        $_POST['type_id'] = 0;
    }

    $graph_name = basename($graph_file, '.svg');

    $sql = "INSERT INTO ".$ecs->table('graph')."(type_id, graph_name, graph_file, sort_order, is_show) ".
            "VALUES ('$_POST[type_id]', '$graph_name', '$graph_file', '$_POST[sort_order]', '$_POST[is_show]')";
    $db->query($sql);

    $link[0]['text'] = '继续添加新图形';
    $link[0]['href'] = 'graph.php?act=add';

    $link[1]['text'] = '返回图形列表';
    $link[1]['href'] = 'graph.php?act=list';

    admin_log($_POST['type_id'],'add','graph');

    clear_cache_files(); // 清除相关的缓存文件

    sys_msg('图形已经添加成功',0, $link);
}

/*------------------------------------------------------ */
//-- 编辑
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'edit')
{
    /* 权限判断 */
    admin_priv('graph_list');

    /* 取图形数据 */
    $sql = "SELECT * FROM " .$ecs->table('graph'). " WHERE graph_id='$_REQUEST[id]'";
    $graph = $db->GetRow($sql);

    $smarty->assign('graph',     $graph);
    $smarty->assign('type_select',  graph_type_list());
    $smarty->assign('ur_here',     '编辑图形');
    $smarty->assign('action_link', array('text' => '图形列表', 'href' => 'graph.php?act=list&' . list_link_postfix()));
    $smarty->assign('form_action', 'update');

    assign_query_info();
    $smarty->display('graph_info.htm');
}

if ($_REQUEST['act'] =='update')
{
    /* 权限判断 */
    admin_priv('graph_list');

    $id = intval($_POST['id']);
    $type_id = intval($_POST['type_id']);

    if (empty($_POST['type_id']))
    {
        $_POST['type_id'] = 0;
    }

    /* 图形文件 */
    $graph_file = '';
    if (empty($_FILES['graph_file']['error']) || (!isset($_FILES['graph_file']['error']) && isset($_FILES['graph_file']['tmp_name']) && $_FILES['graph_file']['tmp_name'] != 'none'))
    {
        // 检查文件格式
        if (!check_file_type($_FILES['graph_file']['tmp_name'], $_FILES['graph_file']['name'], $allow_svg))
        {
            sys_msg('上传文件格式不正确!');
        }

        // 复制文件
        $res = upload_graph_file($_FILES['graph_file'], 'svg'.$id);
        if ($res != false)
        {
            $graph_file = $res;
        }
    }

    if ($graph_file == '')
    {
        $graph_file = $_POST['graph_file'];
    }

    $graph_name = basename($graph_file, '.svg');

    /* 如果 graph_file 跟以前不一样，且原来的文件是本地文件，删除原来的文件 */
    $sql = "SELECT graph_file FROM " . $ecs->table('graph') . " WHERE graph_id = '$id'";
    $old_url = $db->getOne($sql);
    if ($old_url != '' && $old_url != $graph_file && strpos($old_url, 'http://') === false && strpos($old_url, 'https://') === false)
    {
        @unlink(ROOT_PATH . $old_url);
    }

    if ($exc->edit("type_id='$type_id', graph_name='$graph_name', graph_file='$graph_file', is_show='$_POST[is_show]', sort_order = '$_POST[sort_order]'", $id))
    {
        $link[0]['text'] = '返回图形列表';
        $link[0]['href'] = 'graph.php?act=list&' . list_link_postfix();

        $note = sprintf('图形 %s 成功编辑', stripslashes($graph_name));
        admin_log($graph_name, 'edit', 'graph');

        clear_cache_files();

        sys_msg($note, 0, $link);
    }
    else
    {
        die($db->error());
    }
}

/*------------------------------------------------------ */
//-- 导入图形
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'import')
{
    /* 权限判断 */
    admin_priv('graph_list');

    $smarty->assign('graph',     array());
    $smarty->assign('type_select',  graph_type_list());
    $smarty->assign('ur_here',     '批量导入图形');
    $smarty->assign('action_link', array('text' => '图形列表', 'href' => 'graph.php?act=list'));
    $smarty->assign('form_action', 'act_import');

    assign_query_info();
    $smarty->display('graph_import.htm');
}

/*------------------------------------------------------ */
//-- 导入图形操作
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'act_import')
{
    /* 权限判断 */
    admin_priv('graph_list');

    $type_id = intval($_POST['type_id']);   //分类ID

    /*插入数据*/
    if (empty($type_id))
    {
        sys_msg('请选择图形分类!');
    }

    /* 图形图片 */
    $zip_file = '';
    if ((isset($_FILES['zip_file']['error']) && $_FILES['zip_file']['error'] == 0) || (!isset($_FILES['zip_file']['error']) && isset($_FILES['zip_file']['tmp_name']) && $_FILES['zip_file']['tmp_name'] != 'none'))
    {
        // 检查文件格式
        if (!check_file_type($_FILES['zip_file']['tmp_name'], $_FILES['zip_file']['name'], $allow_zip))
        {
            sys_msg('上传文件格式不正确!');
        }

        // 复制文件
        $res = upload_zip_file($_FILES['zip_file']);
        if ($res != false)
        {
            $zip_file = $res;
        }
    } else {
        sys_msg('请上传压缩包!');
    }

    if ($zip_file == '')
    {
        sys_msg('导入失败，请重试!');
    }

    $dir = ROOT_PATH . 'qdshop/public/static/home/default1/diy/svg' . $type_id . '/';
    $z = new Unzip();
    $svg_list = $z->unzip(ROOT_PATH . $zip_file, $dir, true, true); //图片列表
    if (!empty($svg_list)) {
        foreach ($svg_list as $key => $value) {
            $file_info = pathinfo($value);
            // 过滤非svg格式的文件
            if ($file_info['extension'] == 'svg') {
                $file_name = cls_image::random_filename();
                $new_file_name = $dir . $file_name . '.' . $file_info['extension'];
                rename(iconv('UTF-8','GBK',$value), iconv('UTF-8','GBK',$new_file_name));

                // 写入数据库
                $graph_name = basename($new_file_name, '.svg');

                $graph_file = str_replace(ROOT_PATH, '', $new_file_name);
                $sql = "INSERT INTO ".$ecs->table('graph')."(type_id, graph_name, graph_file, sort_order, is_show) ".
                        "VALUES ('$type_id', '$graph_name', '$graph_file', '100', '1')";
                $db->query($sql);
            }
        }
        unlink(ROOT_PATH . $zip_file); //删除本地压缩包
    }

    $link[0]['text'] = '继续导入图形';
    $link[0]['href'] = 'graph.php?act=import';

    $link[1]['text'] = '返回图形列表';
    $link[1]['href'] = 'graph.php?act=list';

    admin_log($_POST['type_id'],'add','graph');

    clear_cache_files(); // 清除相关的缓存文件

    sys_msg('图形导入成功',0, $link);
}

/*------------------------------------------------------ */
//-- 切换是否显示
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'toggle_show')
{
    check_authz_json('graph_list');

    $id = intval($_POST['id']);
    $val = intval($_POST['val']);

    $exc->edit("is_show = '$val'", $id);
    clear_cache_files();

    make_json_result($val);
}

/*------------------------------------------------------ */
//-- 删除图形主题
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'remove')
{
    check_authz_json('graph_list');

    $id = intval($_GET['id']);

    /* 删除原来的文件 */
    $sql = "SELECT graph_file FROM " . $ecs->table('graph') . " WHERE graph_id = '$id'";
    $graph_file = $db->getOne($sql);
    if ($graph_file != '' && strpos($graph_file, 'http://') === false && strpos($graph_file, 'https://') === false)
    {
        @unlink(ROOT_PATH . $graph_file);

    }

    $name = $exc->get_name($id);
    if ($exc->drop($id))
    {
        admin_log(addslashes($name),'remove','graph');
        clear_cache_files();
    }

    $url = 'graph.php?act=query&' . str_replace('act=remove', '', $_SERVER['QUERY_STRING']);

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
            admin_priv('graph_list');

            if (!isset($_POST['checkboxes']) || !is_array($_POST['checkboxes']))
            {
                sys_msg('您没有选择任何图形', 1);
            }

            /* 删除原来的文件 */
            $sql = "SELECT graph_file FROM " . $ecs->table('graph') .
                    " WHERE graph_id " . db_create_in(join(',', $_POST['checkboxes'])) .
                    " AND graph_file <> ''";

            $res = $db->query($sql);
            while ($row = $db->fetchRow($res))
            {
                $old_url = $row['graph_file'];
                if (strpos($old_url, 'http://') === false && strpos($old_url, 'https://') === false)
                {
                    @unlink(ROOT_PATH . $old_url);
                }
            }

            foreach ($_POST['checkboxes'] AS $key => $id)
            {
                if ($exc->drop($id))
                {
                    $name = $exc->get_name($id);
                    admin_log(addslashes($name),'remove','graph');
                }
            }

        }

        /* 批量隐藏 */
        if ($_POST['type'] == 'button_hide')
        {
            check_authz_json('graph_list');
            if (!isset($_POST['checkboxes']) || !is_array($_POST['checkboxes']))
            {
                sys_msg('您没有选择任何图形', 1);
            }

            foreach ($_POST['checkboxes'] AS $key => $id)
            {
              $exc->edit("is_show = '0'", $id);
            }
        }

        /* 批量显示 */
        if ($_POST['type'] == 'button_show')
        {
            check_authz_json('graph_list');
            if (!isset($_POST['checkboxes']) || !is_array($_POST['checkboxes']))
            {
                sys_msg('您没有选择任何图形', 1);
            }

            foreach ($_POST['checkboxes'] AS $key => $id)
            {
              $exc->edit("is_show = '1'", $id);
            }
        }
    }

    /* 清除缓存 */
    clear_cache_files();
    $lnk[] = array('text' => '返回图形列表', 'href' => 'graph.php?act=list');
    sys_msg('批量操作成功', 0, $lnk);
}

/* 获得图形列表 */
function get_graph_list()
{
    $result = get_filter();
    if ($result === false)
    {
        $filter = array();
        $filter['type_id'] = empty($_REQUEST['type_id']) ? 0 : intval($_REQUEST['type_id']);
        $filter['sort_by']    = empty($_REQUEST['sort_by']) ? 'a.graph_id' : trim($_REQUEST['sort_by']);
        $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);

        $where = '';
        if ($filter['type_id'])
        {
            $where .= " AND a.type_id = " . $filter['type_id'];
        }

        /* 图形总数 */
        $sql = 'SELECT COUNT(*) FROM ' .$GLOBALS['ecs']->table('graph'). ' AS a '.
               'LEFT JOIN ' .$GLOBALS['ecs']->table('graph_type'). ' AS ac ON ac.type_id = a.type_id '.
               'WHERE 1 ' .$where;
        $filter['record_count'] = $GLOBALS['db']->getOne($sql);

        $filter = page_and_size($filter);

        /* 获取图形数据 */
        $sql = 'SELECT a.* , ac.type_name '.
               'FROM ' .$GLOBALS['ecs']->table('graph'). ' AS a '.
               'LEFT JOIN ' .$GLOBALS['ecs']->table('graph_type'). ' AS ac ON ac.type_id = a.type_id '.
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

/* 上传图形文件 */
function upload_graph_file($upload, $upload_dir)
{
    if (!make_dir("../" . "qdshop/public/static/home/default1/diy/" . $upload_dir))
    {
        /* 创建目录失败 */
        return false;
    }

    $filename = cls_image::random_filename() . substr($upload['name'], strpos($upload['name'], '.'));

    $path = ROOT_PATH . "qdshop/public/static/home/default1/diy/" . $upload_dir . "/" . $filename;

    if (move_upload_file($upload['tmp_name'], $path))
    {
        return "qdshop/public/static/home/default1/diy/" . $upload_dir . "/" . $filename;
    }
    else
    {
        return false;
    }
}

/* 上传压缩包文件 */
function upload_zip_file($upload)
{
    if (!make_dir("../" . DATA_DIR . "/graph"))
    {
        /* 创建目录失败 */
        return false;
    }

    $filename = cls_image::random_filename() . substr($upload['name'], strpos($upload['name'], '.'));

    $path = ROOT_PATH. DATA_DIR . "/graph/" . $filename;

    if (move_upload_file($upload['tmp_name'], $path))
    {
        return DATA_DIR . "/graph/" . $filename;
    }
    else
    {
        return false;
    }
}

?>