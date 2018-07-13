<?php

/**
 * ECSHOP ajax
 * ============================================================================
 * 版权所有 2005-2010 上海商派网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.hunuo.com；
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
*/

define('IN_ECS', true);

require(dirname(__FILE__) . '/includes/init.php');

if ($_REQUEST['act'] == 'tipemail')
{
	require(ROOT_PATH . 'includes/cls_json.php');
	$word_www_hunuo = json_str_iconv($_REQUEST['word']);
	$json_www_hunuo   = new JSON;
	$result_www_hunuo = array('error' => 0, 'message' => '', 'content' => '');
	
	if(!$word_www_hunuo ||  strlen($word_www_hunuo) > 30)
	{
        $result_www_hunuo['error']   = 1;
		die($json_www_hunuo->encode($result_www_hunuo));
	}
	$word_www_hunuo = str_replace(array(' ','*', "\'"), array('', '', ''), $word_www_hunuo);

	$email_name_arr = explode("@", $word_www_hunuo);
	$email_name = $email_name_arr[0];
    
	$_CFG['email_domain'] =str_replace(" ", "",$_CFG['email_domain']);
	$email_domain_arr = explode(",", str_replace("，",",",$_CFG['email_domain']));

    $logdb=array();
	foreach($email_domain_arr AS $key=>$edomain)
	{
		$email_domain_arr[$key] = $email_name.'@'.$edomain;
	}

	foreach($email_domain_arr AS $email_domain)
    {
		if (stristr($email_domain, $word_www_hunuo))
		{
			$logdb[] = $email_domain;
		}
	}
	$smarty->assign('logdb', $logdb);	

	if(count($logdb)==0)
	{
		$result_www_hunuo['content'] = '';
	}
	else
	{		
		$result_www_hunuo['content'] = $smarty->fetch('library/email_tip.lbi');
	}
	

	die($json_www_hunuo->encode($result_www_hunuo));
}
?>