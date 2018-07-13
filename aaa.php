<?php

/**
 * ECSHOP 首页文件
 * ============================================================================
 * 版权所有 2005-2010 上海商派网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.ecshop.com；
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * $Author: liuhui $
 * $Id: index.php 17063 2010-03-25 06:35:46Z liuhui $
*/

define('IN_ECS', true);

require(dirname(__FILE__) . '/includes/init.php');

//已存在的店铺ID
$aaa="1,2,4,5,6,7,8,10,18,21,25";

//1、添加是否可以开发票的配置
$aaa_array=explode(',',$aaa);
foreach ($aaa_array as  $value) {
    $sql = 'INSERT INTO '.$ecs->table('supplier_shop_config').' (`id`, `parent_id`, `code`, `type`, `store_range`, `store_dir`, `value`, `sort_order`, `supplier_id`) '.
               "VALUES (401, 1, 'can_invoice', 'select', '1,0', '', 1, 1, ".$value.")";

        $db->query($sql);
}




//2、添加发票类型的配置
$sql = "SELECT value FROM " . $ecs->table('shop_config') . " WHERE id = 422";
                    $shopinfo = $db->getOne($sql);
/*foreach ($aaa_array as  $value) {
    $sql = 'INSERT INTO '.$ecs->table('supplier_shop_config').' (`id`, `parent_id`, `code`, `type`, `store_range`, `store_dir`, `value`, `sort_order`, `supplier_id`) '.
               "VALUES (422, 1, 'invoice_type', 'manual', '', '',  '".$shopinfo."', 1, ".$value.")";

    $db->query($sql);
}*/


?>