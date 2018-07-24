<?php

/**
 * 店铺 判断是否关注
*/


//判断是否有ajax请求
$act = !empty($_GET['act']) ? $_GET['act'] : '';
if ($act == 'add_guanzhu')
{
	
	$user_id = intval($_SESSION['user_id']);
	$suppId = intval($_GET['suppId']);
    
    include_once('includes/cls_json.php');
    $json = new JSON;
    $result   = array('error' => 0, 'info' => '', 'data'=>'');
    
	if(empty($user_id)){
		$result['info'] = '请先登陆！';
		die($json->encode($result));
	}
	try {
		$sql = 'INSERT INTO '. $ecs->table('supplier_guanzhu') . ' (`userid`, `supplierid`, `addtime`) VALUES ('.$user_id.','.$suppId.','.time().') ON DUPLICATE KEY UPDATE addtime='.time();
		$db->query($sql);
		if($db->affected_rows() > 1){
			$result['error'] = 2;
    		$result['info'] = '已经关注！';
		}else{
			$result['error'] = 1;
    		$result['info'] = '关注成功！';
		}
	} catch (Exception $e) {
		$result['error'] = 2;
    	$result['info'] = '已经关注！';
	}
	$result['suppId'] = $suppId;
    die($json->encode($result));
}


?>