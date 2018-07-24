<?php

/**
 * ECSHOP ajax
 * ============================================================================
 * 版权所有 2005-2010 上海商派网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.ecshop.com；
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
*/

define('IN_ECS', true);

require(dirname(__FILE__) . '/includes/init.php');

if ($_REQUEST['act'] == 'tipword')
{
	require(ROOT_PATH . 'includes/cls_json.php');
	$word_hunuo = json_str_iconv($_REQUEST['word']);
	$json_hunuo   = new JSON;
	$result_hunuo = array('error' => 0, 'message' => '', 'content' => '');
	
	if(!$word_hunuo || strlen($word_hunuo) < 2 || strlen($word_hunuo) > 30)
	{
        $result_hunuo['error']   = 1;
		die($json_hunuo->encode($result_hunuo));
	}
	$needle = $replace = array();
	$word_hunuo = str_replace(array(' ','*', "\'"), array('%', '%', ''), $word_hunuo);
	$needle[] = $word_hunuo;
	$replace[] = '<strong style="color:cc0000;">'.$word_hunuo.'</strong>';
	$logdb = array();
	if(preg_match("/^[a-z0-9A-Z]+$/", $word_hunuo)) {	
    	$sql_qq = "SELECT * FROM " . $ecs->table('keyword') ." WHERE searchengine='ecshop' AND status='1' AND letter LIKE '%$word_hunuo%' ORDER BY total_search DESC";
	} else {
    	$sql_qq = "SELECT * FROM " . $ecs->table('keyword') ." WHERE searchengine='ecshop' AND status='1' AND word LIKE '%$word_hunuo%' ORDER BY total_search DESC";
	}
    $res_hunuo = $db->SelectLimit($sql_qq, 10, 0);

	$iii=1;
	while ($rows_hunuo = $db->fetchRow($res_hunuo))
    {
		$rows_hunuo['kword'] = str_ireplace($needle, $replace, $rows_hunuo['word']);

		if($iii==1 && $rows_hunuo['keyword_cat_count'])
		{  
			$rows_hunuo['keyword_cat'] =  '<a href="' . $rows_hunuo['keyword_cat_url'] . '"><font color=#666>在<font color=#cc0000>'. $rows_hunuo['keyword_cat'] .'</font>分类中搜索</font></a>';
			$rows_hunuo['keyword_cat_count'] = intval($rows_hunuo['keyword_cat_count']);
		}
		$iii=$iii+1;  

		$logdb[] = $rows_hunuo; 

		
	}
	$smarty->assign('logdb', $logdb);
	$result_hunuo['content'] = $smarty->fetch('library/search_tip.lbi');
	die($json_hunuo->encode($result_hunuo));
}
?>