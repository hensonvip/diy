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
$exc   = new exchange($ecs->table("diy_record"), $db, 'record_id');
$excs   = new exchange($ecs->table("diy_info"), $db, 'record_id');

/*处理图片 生成不重复图片前缀*/
$image = new cls_image($_CFG['bgcolor']);
date_default_timezone_set("Asia/Shanghai");//定义时区，防止时间戳转换误差

/*------------------------------------------------------ */
//-- 文章列表
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'list')
{
    /* 取得过滤条件 */
    $filter = array();
    $smarty->assign('ur_here',      $_LANG['02_originality_examine']);
    //$smarty->assign('action_link',  array('text' => $_LANG['originality_add'], 'href' => 'originality_examine.php?act=add'));
    $smarty->assign('full_page',    1);
    $smarty->assign('filter',       $filter);

    $originality_list = get_originalitylist();
    //print_r($originality_list['arr']);
    $smarty->assign('originality_list', $originality_list['arr']);
    $smarty->assign('filter',          $originality_list['filter']);
    $smarty->assign('record_count',    $originality_list['record_count']);
    $smarty->assign('page_count',      $originality_list['page_count']);

    $sort_flag  = sort_flag($originality_list['filter']);
    //$smarty->assign($sort_flag['tag'], $sort_flag['img']);

    assign_query_info();
    $smarty->display('originality_examine_list.htm');
}

/*------------------------------------------------------ */
//-- 翻页，排序
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'query')
{
    check_authz_json('originality_examine');

    $originality_list = get_originalitylist();

    $smarty->assign('originality_list',    $originality_list['arr']);
    $smarty->assign('filter',          $originality_list['filter']);
    $smarty->assign('record_count',    $originality_list['record_count']);
    $smarty->assign('page_count',      $originality_list['page_count']);

    $sort_flag  = sort_flag($originality_list['filter']);
    $smarty->assign($sort_flag['tag'], $sort_flag['img']);

    make_json_result($smarty->fetch('originality_examine_list.htm'), '',
        array('filter' => $originality_list['filter'], 'page_count' => $originality_list['page_count']));
}

/*------------------------------------------------------ */
//-- 编辑
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'edit')
{
    /* 权限判断 */
    admin_priv('originality_edit');

    /* 取数据 */
    $sql = "SELECT * FROM " .$ecs->table('diy_record'). " dr LEFT JOIN ".$ecs->table('diy_info')." di ON dr.record_id = di.record_id WHERE dr.record_id='$_REQUEST[id]'";
    $info = $db->GetRow($sql);

    //标签
    $info['tags_name'] = "";
    if(!empty($info['tags'])){
        $tags_sql = "SELECT `tags_name` FROM ".$ecs->table('production_tags')." WHERE tags_id IN (".$info['tags'].")";
        $tags = $db->getAll($tags_sql);
        foreach($tags as $k=>$v){
            $tags_arr[] = $v['tags_name'];
        }
        $info['tags_name'] = implode(',',$tags_arr);
    }

    //类型列表
    $type_sql = "SELECT `cat_id`,`cat_name` FROM ".$ecs->table('category')." WHERE parent_id = 85";
    $type_data = $db->getAll($type_sql);
    // var_dump($type_data);
    foreach ($type_data as $key => $value) {
        $k = $value['cat_id'];
        $cat_list[$k] = $value['cat_name'];
    }

    //提交时间
    $info['time'] = date($GLOBALS['_CFG']['time_format'], $info['add_time']);

    //详情图
    $info['imgs_arr'] = "";
    if(!empty($info['imgs'])){
        $info['imgs_arr'] =  explode(',', $info['imgs']);
    }

    //作品图 T恤
    //$info['design_img_t_arr'] = "";
    if(!empty($info['design_img_t'])){
        $info['design_img_t_arr'] =  explode(',', $info['design_img_t']);
    }
    //print_r($info['design_img_t_arr']);exit;

    /* 创建 html editor */
    create_html_editor('imgs',htmlspecialchars('/'.$info['imgs']));



    $smarty->assign('info',     $info);
    $smarty->assign('cat_list',   $cat_list);
    $smarty->assign('ur_here',     $_LANG['originality_edit']);
    $smarty->assign('action_link', array('text' => $_LANG['02_originality_examine'], 'href' => 'originality_examine.php?act=list&' . list_link_postfix()));
    $smarty->assign('form_action', 'update');

    assign_query_info();
    $smarty->display('originality_examine_info.htm');
}

if ($_REQUEST['act'] =='update')
{
    /* 权限判断 */
    admin_priv('originality_edit');

    //print_r($_POST);exit;
    if(intval($_POST['record_id'])){
        //$state = $_POST['state'];
        admin_log('作品ID-'.$_POST['record_id'],'edit','originality_examine');
            if(empty($_POST['title'])){
                sys_msg('标题不能为空');
            }
            $title = $_POST['title'];
            $describe = $_POST['describe'];
            $tags_name = $_POST['tags_name'];
            $type = $_POST['type_cat'];//类型
            $time = strtotime($_POST['time']);
            $state = $_POST['state'];

            $name = explode(',',$tags_name);
            $name_count   =  count($name);
        if(!empty($tags_name)){
            $tags_name = rtrim($tags_name,',');
            $tags_name = ltrim($tags_name,',');
            $tags_name = str_replace(",","','",$tags_name);
            $tags_sql = "SELECT `tags_id`,`tags_name` FROM ".$ecs->table('production_tags')." WHERE tags_name IN ('".$tags_name."')";
            $tags_select = $db->getAll($tags_sql);
            if($tags_select){
                foreach ($tags_select as $k=>$v){
                    $tags_id_arr[] = $v['tags_id'];
                    $tags_name_arr[] = $v['tags_name'];
                }
                if(count($tags_select) <  $name_count){
                    $diff = array_diff($name,$tags_name_arr);
                    //$diff = implode(',',$diff);

                    //$arr = $db->getAll("SELECT `tags_id` FROM ".$ecs->table('production_tags')." WHERE tags_name IN ('".$diff."')");
                    foreach ($diff as $k=>$v){
                        $ins_sql = " INSERT INTO ".$ecs->table('production_tags')."( `tags_name`,`add_time`) VALUES ('$v',time()) ";
                        $db->query($ins_sql);
                        $tags_id_arr[] = $db->insert_id();
                    }
                    $tags  = implode(',', $tags_id_arr);//标签id字符串
                    //print_r($tags);exit;
                }else{
                    $tags  = implode(',', $tags_id_arr);//标签id字符串
                }
            }else{
                if($name_count >5){
                    sys_msg('标签不能超过5个');
                }
                foreach($name as $k=>$v){
                    $ins_sql = " INSERT INTO ".$ecs->table('production_tags')."( `tags_name`,`add_time`) VALUES ('$v',time()) ";
                    $db->query($ins_sql);
                    $id_arr[] = $db->insert_id();
                }
                $tags = implode(',', $id_arr);
            }
        }
        $sql = "UPDATE ".$ecs->table('diy_info')."di ,".$ecs->table('diy_record').
            " dr SET di.title = '$title',di.describe = '$describe',di.type = '$type',di.add_time = '$time',dr.state = '$state',di.tags = '$tags' WHERE di.record_id = ".$_POST['record_id']." AND dr.record_id=".$_POST['record_id'];
        if((string)$db->query($sql)){
            sys_msg('更改成功');
        }else{
            sys_msg('更改失败，请稍后重试');
        }
    }else{
        sys_msg('参数错误，修改异常');
    }
}

/*------------------------------------------------------ */
//-- 上传图
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 't_imgs_update')
{
    check_authz_json('originality_examine');
    $id    = intval($_POST['record_id']);
    $file_info = $_FILES['file'];
    $str  = $_POST['str'];
    $i = $_POST['i'];//区分多图上传跟单图替换  此字段有为单图替换
    if($file_info['error'] > 0 || empty($file_info) || empty($id) || empty($str)){
        $data['code'] = 0;
    }else{
        $dir = "match_img";
        $img_str[1] = $image->upload_image($file_info,$dir);
        if($img_str[1]){
            $sql = "SELECT `$str` FROM ".$ecs->table('diy_info')." WHERE record_id = $id";
            $img_str[0] = $db->getOne($sql);
            if(empty($i)){
                if(!empty($img_str[0])){
                    $imgs = implode(',', $img_str);//拼接为字符串
                }else{
                    $imgs = $img_str[1];
                }
            }else{
                $imgs = $img_str[1];
            }
            $up_sql = "UPDATE ".$ecs->table('diy_info')." SET $str = '$imgs' WHERE record_id = $id";
            if((string)$db->query($up_sql)){
                $data['code'] = 1;
                $data['url'] = "/".$img_str[1];
            }else{
                $data['code'] = 2;
                @unlink($img_str[1]);//插入失败删除图片文件
            }
        }else{
            $data['code'] = 3;
        }
    }
    echo json_encode($data);
}
/*------------------------------------------------------ */
//-- 移除图片
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 't_imgs_delete') {
    check_authz_json('originality_examine');

    $id    = intval($_POST['record_id']);
    $url  = $_POST['srcurl'];
    $str  = $_POST['str'];
    if($id&&$url&&$str){
        $sql = "SELECT `$str` FROM ".$ecs->table('diy_info')." WHERE record_id = $id";
        $img_str = $db->getOne($sql);
        if($img_str){
            $pot = strpos($url,'/');
            $url=substr_replace($url,"",$pot,1);
            $img_arr = explode(',',$img_str);
            foreach($img_arr as $k=>$v){
                if($v == $url){
                    unset($img_arr[$k]);
                }
            }
            $new_str =  implode(',', $img_arr);//拼接为字符串
            $up_sql = "UPDATE ".$ecs->table('diy_info')." SET ".$str." = '$new_str' WHERE record_id = $id";
            if((string)$db->query($up_sql)){
                $data['code'] = 1;
                $data['url'] = $url;
                @unlink($url);//插入失败删除图片文件
            }else{
                $data['code'] = 0;
            }
        }else{
            $data['code'] = 0;
        }
    }else{
        $data['code'] = 0;
    }

    echo json_encode($data);


}

/*------------------------------------------------------ */
//-- 切换是否通過 //通過
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'toggle_already')
{
    check_authz_json('originality_examine');

    $id     = intval($_POST['id']);
    $val    = intval($_POST['val']);
    if(empty($val)||$val == 0){
        $val = 0;
    }else{
        $val = 2;
    }


    $exc->edit("state = '$val'", $id);
    clear_cache_files();

    make_json_result($val);
}



/*------------------------------------------------------ */
//-- 切换是否通過  //未通過
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'toggle_not')
{
    check_authz_json('originality_examine');

    $id     = intval($_POST['id']);
    $val    = intval($_POST['val']);
    if(empty($val)||$val == 0){
        $val = 0;
    }else{
        $val = 1;
    }


    $exc->edit("state = '$val'", $id);
    clear_cache_files();

    make_json_result($val);
}





/*------------------------------------------------------ */
//-- 删除参赛作品
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'remove')
{
    check_authz_json('originality_delete');

    $id = intval($_GET['id']);

    $name_record = $exc->get_one($id);
    $name_info = $excs->get_one($id);
    //var_dump($name);exit;

    if($name_record){
        $design_img_t = $name_info['design_img_t'];//多张
        $imgs = $name_info['imgs'];//多张
        $design_img = $name_info['design_img'];//一张

        //@unlink($design_img);
        //var_dump(ROOT_PATH . $design_img);exit;

        if ($exc->drop($id)) {
            $db->query("DELETE FROM " . $ecs->table('diy_info') . " WHERE record_id = $id");

            $design_img_t_arr = explode(',',$design_img_t);
            $imgs_arr = explode(',',$imgs);
            $ROOT_PATH = ROOT_PATH;
            foreach($design_img_t_arr as $key=>$value){
                $url_t = $ROOT_PATH.$value;
                @unlink($url_t);
            }
            foreach($imgs_arr as $k=>$v){
                $url_s = $ROOT_PATH.$v;
                @unlink($url_s);
            }
            @unlink($ROOT_PATH.$design_img);

            admin_log('作品ID-'.$id,'remove','originality_examine');

            clear_cache_files();
        }
    }
    $url = 'originality_examine.php?act=query&' . str_replace('act=remove', '', $_SERVER['QUERY_STRING']);

    ecs_header("Location: $url\n");
    exit;
}


/*------------------------------------------------------ */
//-- 批量操作
/*------------------------------------------------------ */

elseif ($_REQUEST['act'] == 'batch') {
    /* 批量删除 */
    //print_r($_POST['checkboxes']);exit;
    if (isset($_POST['type'])) {
         if ($_POST['type'] == 'button_remove')
         {
             admin_priv('originality_delete');

             if (!isset($_POST['checkboxes']) || !is_array($_POST['checkboxes']))
             {
                 sys_msg($_LANG['no_select_food'], 1);
             }
             /* 删除原来的文件 */
              $sql = "SELECT * FROM " . $ecs->table('diy_info') .
                      " WHERE record_id " . db_create_in(join(',', $_POST['checkboxes']));

              $res = $db->getAll($sql);

             foreach($res as $key=>$value){
                 $img[] = $value['design_img'];
                 foreach(explode(',',$value['design_img_t']) as $k=>$v){
                     $img[] = $v;
                 }
                 foreach(explode(',',$value['imgs']) as $k=>$v){
                     $img[] = $v;
                 }
             }

             $del_sql = "DELETE di,dr FROM " . $ecs->table('diy_info')." di LEFT JOIN " .$ecs->table('diy_record'). " dr  ON dr.record_id = di.record_id WHERE dr.record_id ".db_create_in(join(',', $_POST['checkboxes']));

             if($db->query($del_sql)){
                 if(!empty($img)){
                     foreach($img as $k=>$v){
                         @unlink(ROOT_PATH . $v);
                     }
                 }
                 admin_log('作品ID-'.join(',', $_POST['checkboxes']),'batch_remove','originality_examine');
             }
             //print_r($img);exit;
         }

        /* 批量隐藏 */
        /*if ($_POST['type'] == 'button_hide')
        {
            check_authz_json('originality_manage');
            if (!isset($_POST['checkboxes']) || !is_array($_POST['checkboxes']))
            {
                sys_msg($_LANG['no_select_originality'], 1);
            }

            foreach ($_POST['checkboxes'] AS $key => $id)
            {
              $exc->edit("is_show = '0'", $id);
            }
        }*/

        /* 批量显示 */
     /*   if ($_POST['type'] == 'button_show')
        {
            check_authz_json('originality_manage');
            if (!isset($_POST['checkboxes']) || !is_array($_POST['checkboxes']))
            {
                sys_msg($_LANG['no_select_originality'], 1);
            }

            foreach ($_POST['checkboxes'] AS $key => $id)
            {
              $exc->edit("is_show = '1'", $id);
            }
        }*/
    }

    /* 清除缓存 */
    clear_cache_files();
    $lnk[] = array('text' => $_LANG['back_list'], 'href' => 'originality_examine.php?act=list');
    sys_msg($_LANG['batch_handle_ok'], 0, $lnk);
}



/* 获得参赛列表 */
function get_originalitylist()
{
    $result = get_filter();
    if ($result === false) {

        $filter = array();
        $filter['sort_by']    = empty($_REQUEST['sort_by']) ? 'record_id' : trim($_REQUEST['sort_by']);
        $filter['keyword']    = empty($_REQUEST['keyword']) ? '' : trim($_REQUEST['keyword']);
        $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);
        if (isset($_REQUEST['is_ajax']) && $_REQUEST['is_ajax'] == 1) {
            $filter['keyword'] = json_str_iconv($filter['keyword']);
        }

        $where = '';
        if (!empty($filter['keyword']))
        {
            $where = ' AND di.title LIKE "%' . mysql_like_quote($filter['keyword']) . '%"';
        }

        /* 取时间 */
        $time =get_time();
        $collect_start_time = $time['collect_start_time'];
        $collect_end_time = $time['collect_end_time'];

        /* 列表总数 */
        $sql = 'SELECT count(*) '.
               'FROM ' .$GLOBALS['ecs']->table('diy_record'). 'dr LEFT JOIN '.$GLOBALS['ecs']->table('diy_info').' di ON dr.record_id = di.record_id WHERE dr.state = 0 AND dr.add_time >= '.$collect_start_time.' AND dr.add_time < '.$collect_end_time. $where;
        $filter['record_count'] = $GLOBALS['db']->getOne($sql);

        $filter = page_and_size($filter);

        /* 获取列表数据 */
        $sql = 'SELECT u.user_name as nickname,dr.record_id as record_id,di.describe as describes,di.title as `title`,di.design_img as `img`,di.add_time as add_time,dr.state as state '.
               'FROM ' .$GLOBALS['ecs']->table('diy_record').
            ' dr LEFT JOIN '.$GLOBALS['ecs']->table('diy_info').' di ON dr.record_id = di.record_id LEFT JOIN '.$GLOBALS['ecs']->table('users').' u ON u.user_id = dr.user_id WHERE dr.state = 0 AND dr.add_time >= '
            .$collect_start_time.' AND dr.add_time < '.$collect_end_time. $where.' ORDER by '.$filter['sort_by'].' '.$filter['sort_order'];

        $filter['keyword'] = stripslashes($filter['keyword']);
        set_filter($filter, $sql);
    }else{
        $sql    = $result['sql'];
        $filter = $result['filter'];
    }
    $res = $GLOBALS['db']->selectLimit($sql, $filter['page_size'], $filter['start']);

    while($rows = $GLOBALS['db']->fetchRow($res)){
        $rows['add_time'] = date($GLOBALS['_CFG']['time_format'], $rows['add_time']);
        $arr[] = $rows;
    }

    //print_r($arr);
    return array('arr'=>$arr,'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']);
}

//获取当前赛季时间
function get_time(){
    /* 取数据 */
    $sql = "SELECT * FROM " .$GLOBALS['ecs']->table('originality'). " WHERE is_show = 1 order by sort_order asc limit 1 ";
    $originality = $GLOBALS['db']->GetRow($sql);
/*    $originality['collect_end_time'] = date($GLOBALS['_CFG']['time_format'], $originality['collect_end_time']);
    $originality['collect_start_time'] = date($GLOBALS['_CFG']['time_format'], $originality['collect_start_time']);
    $originality['vote_start_time'] = date($GLOBALS['_CFG']['time_format'], $originality['vote_start_time']);
    $originality['vote_end_time'] = date($GLOBALS['_CFG']['time_format'], $originality['vote_end_time']);
    $originality['publicity_start_time'] = date($GLOBALS['_CFG']['time_format'], $originality['publicity_start_time']);*/

    return $originality;
}

/**
 * 图片处理
 * @param $img_type 图片名
 * @param $tmp_name 图片tmp文件
 * @return int|string
 *//*
function upload_images($img_type,$tmp_name){
    $image = new cls_image($GLOBALS['_CFG']['bgcolor']);
    $type =strstr($img_type,'.');
    $path = DATA_DIR . '/images/' . date('Ym') . '/';
    $name = $image->unique_name($path).$type;
    $new_file = ROOT_PATH . $path;
    if (!file_exists($new_file)) {
        //检查是否有该文件夹，如果没有就创建，并给予最高权限
        mkdir($new_file, 0777);
    }
    if(move_uploaded_file($tmp_name,$new_file.$name)){
        return 1;
    }else{
        return 0;
    }
}*/

?>