<?php

/**
 * ECSHOP 商品相关函数库
 * ============================================================================
 * 版权所有 2005-2010 上海商派网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.ecshop.com；
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * $Author: liuhui $
 * $Id: lib_goods.php 17113 2010-04-16 03:44:19Z liuhui $
*/

if (!defined('IN_ECS'))
{
    die('Hacking attempt');
}

/**
 * 为某商品生成唯一的货号
 * @param   int     $goods_id   商品编号
 * @return  string  唯一的货号
 */
function generate_goods_sn($goods_id)
{
    $goods_sn = $GLOBALS['_CFG']['sn_prefix'] . str_repeat('0', 6 - strlen($goods_id)) . $goods_id;

    $sql = "SELECT goods_sn FROM " . $GLOBALS['ecs']->table('goods') .
            " WHERE goods_sn LIKE '" . mysql_like_quote($goods_sn) . "%' AND goods_id <> '$goods_id' " .
            " ORDER BY LENGTH(goods_sn) DESC";
    $sn_list = $GLOBALS['db']->getCol($sql);
    if (in_array($goods_sn, $sn_list))
    {
        $max = pow(10, strlen($sn_list[0]) - strlen($goods_sn) + 1) - 1;
        $new_sn = $goods_sn . mt_rand(0, $max);
        while (in_array($new_sn, $sn_list))
        {
            $new_sn = $goods_sn . mt_rand(0, $max);
        }
        $goods_sn = $new_sn;
    }

    return $goods_sn;
}

/**
 * 格式化商品图片名称（按目录存储）
 *
 */
function reformat_image_name($type, $goods_id, $source_img, $position='')
{
    $rand_name = gmtime() . sprintf("%03d", mt_rand(1,999));
    $img_ext = substr($source_img, strrpos($source_img, '.'));
    $dir = 'images';
    if (defined('IMAGE_DIR'))
    {
        $dir = IMAGE_DIR;
    }
    $sub_dir = date('Ym', gmtime());
    if (!make_dir(ROOT_PATH.$dir.'/'.$sub_dir))
    {
        return false;
    }
    if (!make_dir(ROOT_PATH.$dir.'/'.$sub_dir.'/source_img'))
    {
        return false;
    }
    if (!make_dir(ROOT_PATH.$dir.'/'.$sub_dir.'/goods_img'))
    {
        return false;
    }
    if (!make_dir(ROOT_PATH.$dir.'/'.$sub_dir.'/thumb_img'))
    {
        return false;
    }
    switch($type)
    {
        case 'goods':
            $img_name = $goods_id . '_G_' . $rand_name;
            break;
        case 'goods_thumb':
            $img_name = $goods_id . '_thumb_G_' . $rand_name;
            break;
        case 'gallery':
            $img_name = $goods_id . '_P_' . $rand_name;
            break;
        case 'gallery_thumb':
            $img_name = $goods_id . '_thumb_P_' . $rand_name;
            break;
    }
    if ($position == 'source')
    {
        if (move_image_file(ROOT_PATH.$source_img, ROOT_PATH.$dir.'/'.$sub_dir.'/source_img/'.$img_name.$img_ext))
        {
            return $dir.'/'.$sub_dir.'/source_img/'.$img_name.$img_ext;
        }
    }
    elseif ($position == 'thumb')
    {
        if (move_image_file(ROOT_PATH.$source_img, ROOT_PATH.$dir.'/'.$sub_dir.'/thumb_img/'.$img_name.$img_ext))
        {
            return $dir.'/'.$sub_dir.'/thumb_img/'.$img_name.$img_ext;
        }
    }
    else
    {
        if (move_image_file(ROOT_PATH.$source_img, ROOT_PATH.$dir.'/'.$sub_dir.'/goods_img/'.$img_name.$img_ext))
        {
            return $dir.'/'.$sub_dir.'/goods_img/'.$img_name.$img_ext;
        }
    }
    return false;
}

function move_image_file($source, $dest)
{
    if (@copy($source, $dest))
    {
        // @unlink($source);
        return true;
    }
    return false;
}

/**
 * 保存某商品的相册图片
 * @param   int     $goods_id
 * @param   array   $image_files
 * @param   array   $image_descs
 * @return  void
 */
function handle_gallery_image($goods_id, $attr_img)
{
    include_once(ROOT_PATH . '/includes/cls_image.php');
    $image = new cls_image($GLOBALS['_CFG']['bgcolor']);
    $goods_attr_id = array();
    if (!empty($attr_img)) {
        foreach ($attr_img as $key => $value) {
            $attr = explode(',', $value['attr']);
            foreach ($attr as $key2 => $value2) {
                $_attr = explode('_', $value2);
                $sql = "SELECT goods_attr_id FROM " . $GLOBALS['ecs']->table('goods_attr') . " WHERE goods_id = '$goods_id' AND attr_id = '$_attr[0]' AND attr_value = '$_attr[1]'";
                $goods_attr_id[] = $GLOBALS['db']->getOne($sql);
            }

            //将base64编码转换为图片保存（设计图）
            if (preg_match('/^(data:\s*image\/(\w+);base64,)/', $value['file'], $result)) {
                $type = $result[2];
                $path = 'images/' . date('Ym') . '/';
                $new_file = ROOT_PATH . $path;
                if (!file_exists($new_file)) {
                    //检查是否有该文件夹，如果没有就创建，并给予最高权限
                    mkdir($new_file, 0777);
                }
                $img = $image->unique_name($path) . ".{$type}";
                $new_file = $new_file . $img;
                $img_original = $path . $img;
                //将图片保存到指定的位置
                if (file_put_contents($new_file, base64_decode(str_replace($result[1], '', $value['file'])))) {
                    $img_url = $thumb_url = $img_original;
                }
            }

            /* 重新格式化图片名称 */
            $img_original = reformat_image_name('gallery', $goods_id, $img_original, 'source');
            $img_url = reformat_image_name('gallery', $goods_id, $img_url, 'goods');
            $thumb_url = reformat_image_name('gallery_thumb', $goods_id, $thumb_url, 'thumb');

            // 入库
            if (!empty($goods_attr_id)) {
                $sql = "INSERT INTO " . $GLOBALS['ecs']->table('goods_gallery') . " (goods_id, img_url, thumb_url, img_original, goods_attr_id, goods_attr_id2) " .
                        "VALUES ('$goods_id', '$img_url', '$thumb_url', '$img_original', '$goods_attr_id[0]', '$goods_attr_id[1]')";
                $GLOBALS['db']->query($sql);
                // 重置数组
                $goods_attr_id = array();
            }
        }
    }
}

/**
 * 保存设计作品
 */
function save_design($user_id, $diy_title, $design_img, $design_img_t, $design_session, $goods_id = 0, $diy_json) {
    $add_time = gmtime();
    $sql = "INSERT INTO " . $GLOBALS['ecs']->table('diy') . " (diy_title, design_session, type, diy_json, design_img, design_img_t, user_id, add_time, goods_id) " .
                       "VALUES ('$diy_title', '$design_session', '1', '$diy_json', '$design_img', '$design_img_t', '$user_id', '$add_time', '$goods_id')";
    $GLOBALS['db']->query($sql);
}

?>