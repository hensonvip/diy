<?php

/**
 * ECSHOP 会员中心
 * ============================================================================
 * * 版权所有 2005-2012 上海商派网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.ecshop.com；
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * $Author: liubo $
 * $Id: user.php 17217 2011-01-19 06:29:08Z liubo $
 */
define('IN_ECS', true);

require (dirname(__FILE__) . '/includes/init.php');

//$data = json_decode(stripslashes($_POST),true);
$data = $_GET;
$arr = array();

//print_r($data);die;
foreach($data  as $vo){
    $arr[] = urldecode($vo);
}

//print_r($arr);die;
echo send_mail_1($arr);
die;

?>
