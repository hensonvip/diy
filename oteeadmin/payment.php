<?php

/**
 * ECSHOP 支付方式管理程序
 * ============================================================================
 * * 版权所有 2005-2012 上海商派网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.ecshop.com；
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * $Author: liubo $
 * $Id: payment.php 17217 2011-01-19 06:29:08Z liubo $
*/

define('IN_ECS', true);

require(dirname(__FILE__) . '/includes/init.php');


/*------------------------------------------------------ */
//-- 支付方式列表 ?act=list
/*------------------------------------------------------ */

//获取文件
function get_php_file($filename) {
    return trim(substr(file_get_contents($filename), 15));
}
function set_php_file($filename, $content) {
    $fp = fopen($filename, "w");
    fwrite($fp, "<?php exit();?>" . $content);
    fclose($fp);
}
if ($_REQUEST['act'] == 'list')
{
    /* 查询数据库中启用的支付方式 */
    $pay_list = array();
    $sql = "SELECT * FROM " . $ecs->table('payment') . " WHERE enabled = '1' ORDER BY pay_order";
    $res = $db->query($sql);
    while ($row = $db->fetchRow($res))
    {
        $pay_list[$row['pay_code']] = $row;
    }
	$alipay = json_decode(get_php_file("../data/payment/alipay.php"), true);
	$wxpay_pub = json_decode(get_php_file("../data/payment/wxpay_pub.php"), true);
	$wxpay_app = json_decode(get_php_file("../data/payment/wxpay_app.php"), true);
	$wxpay_xcx = json_decode(get_php_file("../data/payment/wxpay_xcx.php"), true);
	$unionpay = json_decode(get_php_file("../data/payment/unionpay.php"), true);//

	$alipay['limit_pay'] = explode(',',$alipay['limit_pay']);
	
	$smarty->assign('pay_list', $pay_list);
	$smarty->assign('alipay', $alipay);
    $smarty->assign('wxpay_pub', $wxpay_pub);
    $smarty->assign('wxpay_app', $wxpay_app);
    $smarty->assign('wxpay_xcx', $wxpay_xcx);
    $smarty->assign('unionpay', $unionpay);
	
    $smarty->display('payment_test.htm');
}
elseif ($_REQUEST['act'] == 'upload')
{
	//print_r($_FILES);
	if($_FILES){
		$path = explode('-',current(array_keys($_FILES)));
		$uploadpath = "../data/payment/".$path[0];
		if(move_uploaded_file($_FILES[current(array_keys($_FILES))]['tmp_name'],$uploadpath.'/'.$path[1].'.pem')){
			echo json_encode(array('msg'=>'success'));
			exit();
		}		
	}
	echo json_encode(array('msg'=>'fail'));
} 

/*------------------------------------------------------ */
//-- 提交支付方式 post
/*------------------------------------------------------ */
elseif (isset($_POST['Submit']))
{
    admin_priv('payment');
	$link[] = array('text' => $_LANG['back_list'], 'href' => 'payment.php?act=list');
//print_r($_POST);echo count($_POST['product_code']);print_r(current(array_keys($_POST['product_code'])));print_r(array_keys($_POST['limit_pay']));die();
    /* 检查输入 */
    if (empty($_POST['pay_name']))
    {
        sys_msg($_LANG['payment_name'] . $_LANG['empty']);
    }

	if($_POST['pay_name'] == 'alipay')
	{
		$product_code=array("QUICK_MSECURITY_PAY","FAST_INSTANT_TRADE_PAY","QUICK_WAP_WAY");
		if(array_keys($_POST['product_code']) && count(array_diff($product_code,array_keys($_POST['product_code'])))==0){
			sys_msg('保存的支付方式有误！请返回重新操作');
			exit();
		}
		foreach(array_keys($_POST['product_code']) as $v){
			$sql = "UPDATE " . $ecs->table('payment') .
               "SET enabled = '1' " .
               "WHERE pay_code = '".$v."' and pay_name = 'alipay' LIMIT 1";
			$db->query($sql);
		}
		if(count(array_diff($product_code,array_keys($_POST['product_code'])))>0){
			foreach(array_diff($product_code,array_keys($_POST['product_code'])) as $v){
				$sql = "UPDATE " . $ecs->table('payment') .
				   "SET enabled = '0' " .
				   "WHERE pay_code = '".$v."' and pay_name = 'alipay' LIMIT 1";
				$db->query($sql);
			}
		}
		$data->appId = $_POST['appId'];
        $data->sign_type = $_POST['sign_type'];
        $data->ali_public_key = $_POST['ali_public_key'];
        $data->rsa_private_key = $_POST['rsa_private_key'];
        $data->limit_pay = implode(",",array_keys($_POST['limit_pay']));
        set_php_file("../data/payment/alipay.php", json_encode($data));
		sys_msg('保存成功', 0, $link);
	}   

	if($_POST['pay_name'] == 'weixin')
	{
		$data->appid = $_POST['appid'];
        $data->mch_id = $_POST['mch_id'];
        $data->nonce_str = $_POST['nonce_str'];
        $data->appsecret = $_POST['appsecret']?:'';
		$product_code_array=array("JSAPI","NATIVE","MWEB","APP","XCX");
		if($_POST['product_code'] == 'APP' || $_POST['product_code'] == 'XCX'){
			$sql = "UPDATE " . $ecs->table('payment') .
				   "SET enabled = '1' " .
				   "WHERE pay_code = '".$_POST['product_code']."' and pay_name = 'weixin' LIMIT 1";
			$db->query($sql);
			switch($_POST['product_code']){
				case 'APP':
					set_php_file("../data/payment/wxpay_app.php", json_encode($data));
					break;
				case 'XCX':
					set_php_file("../data/payment/wxpay_xcx.php", json_encode($data));
					break;
			}
		}elseif(count(array_diff($product_code_array,array_keys($_POST['product_code'])))>0){
			foreach(array_keys($_POST['product_code']) as $v){
				$sql = "UPDATE " . $ecs->table('payment') .
				   "SET enabled = '1' " .
				   "WHERE pay_code = '".$v."' and pay_name = 'weixin' LIMIT 1";
				$db->query($sql);
			}
			if(count(array_diff($product_code_array,array_keys($_POST['product_code'])))>0){
				foreach(array_diff($product_code_array,array_keys($_POST['product_code'])) as $v){
					$sql = "UPDATE " . $ecs->table('payment') .
					   "SET enabled = '0' " .
					   "WHERE pay_code = '".$v."' and pay_name = 'weixin' LIMIT 1";
					$db->query($sql);
				}
			}
			set_php_file("../data/payment/wxpay_pub.php", json_encode($data));
		}else{
			sys_msg('保存的支付方式有误！请返回重新操作');
			exit();
		}
		
		sys_msg('保存成功', 0, $link);
	}
exit(); // =============================================-=-=-=-=-=-=-=-===========================================================================================================-=-=-=-=-=-=-=-====-=-==-=-=-=-=-=-=-=-=-=-==--=-=-=
    /* 取得配置信息 */
    $pay_config = array();
    if (isset($_POST['cfg_value']) && is_array($_POST['cfg_value']))
    {
        for ($i = 0; $i < count($_POST['cfg_value']); $i++)
        {
            $pay_config[] = array('name'  => trim($_POST['cfg_name'][$i]),
                                  'type'  => trim($_POST['cfg_type'][$i]),
                                  'value' => trim($_POST['cfg_value'][$i])
            );
        }
    }

	if($_POST['pay_code'] == 'alipay')
	{
		//读取配置文件信息
		$info = @file_get_contents("../mobile/pay/alipay.config.php");
		//对配置文件信息进行正则替换
		$info = preg_replace("/define\(\"PARTNER\",\".*?\"\)/","define(\"PARTNER\",\"{$pay_config[2]['value']}\")",$info);
		$info = preg_replace("/define\(\"KEY\",\".*?\"\)/","define(\"KEY\",\"{$pay_config[1]['value']}\")",$info);
		$info = preg_replace("/define\(\"ACCOUNT\",\".*?\"\)/","define(\"ACCOUNT\",\"{$pay_config[0]['value']}\")",$info);
		//将替换后的信息写入配置文件中
		file_put_contents("../mobile/pay/alipay.config.php",$info);	
	}

	
	//dqy add start 2015-9-7
	if($_POST['pay_code'] == 'alipay')
	{
		//读取配置文件信息
		$info = @file_get_contents("../mobile/pay/alipay.config.php");
		//对配置文件信息进行正则替换
		$info = preg_replace("/define\(\"PARTNER\",\".*?\"\)/","define(\"PARTNER\",\"{$pay_config[2]['value']}\")",$info);
		$info = preg_replace("/define\(\"KEY\",\".*?\"\)/","define(\"KEY\",\"{$pay_config[1]['value']}\")",$info);
		$info = preg_replace("/define\(\"ACCOUNT\",\".*?\"\)/","define(\"ACCOUNT\",\"{$pay_config[0]['value']}\")",$info);
		//将替换后的信息写入配置文件中
		file_put_contents("../mobile/pay/alipay.config.php",$info);	
	}
	//dqy add end 2015-9-7
	

    $pay_config = serialize($pay_config);
    /* 取得和验证支付手续费 */
    $pay_fee    = empty($_POST['pay_fee'])?0:$_POST['pay_fee'];

    /* 检查是编辑还是安装 */
    $link[] = array('text' => $_LANG['back_list'], 'href' => 'payment.php?act=list');
    if ($_POST['pay_id'])
    {
        /* 编辑 */
        $sql = "UPDATE " . $ecs->table('payment') .
               "SET pay_name = '$_POST[pay_name]'," .
               "    pay_desc = '$_POST[pay_desc]'," .
               "    pay_config = '$pay_config', " .
               "    pay_fee    =  '$pay_fee' ".
               "WHERE pay_code = '$_POST[pay_code]' LIMIT 1";
        $db->query($sql);

        /* 记录日志 */
        admin_log($_POST['pay_name'], 'edit', 'payment');

        sys_msg($_LANG['edit_ok'], 0, $link);
    }
    else
    {
        /* 安装，检查该支付方式是否曾经安装过 */
        $sql = "SELECT COUNT(*) FROM " . $ecs->table('payment') . " WHERE pay_code = '$_REQUEST[pay_code]'";
        if ($db->GetOne($sql) > 0)
        {
            /* 该支付方式已经安装过, 将该支付方式的状态设置为 enable */
            $sql = "UPDATE " . $ecs->table('payment') .
                   "SET pay_name = '$_POST[pay_name]'," .
                   "    pay_desc = '$_POST[pay_desc]'," .
                   "    pay_config = '$pay_config'," .
                   "    pay_fee    =  '$pay_fee', ".
                   "    enabled = '1' " .
                   "WHERE pay_code = '$_POST[pay_code]' LIMIT 1";
            $db->query($sql);
        }
        else
        {
           /* 代码修改_start   By www.ecshop68.com */
            /* 该支付方式没有安装过, 将该支付方式的信息添加到数据库 */
            $sql = "INSERT INTO " . $ecs->table('payment') . " (pay_code, pay_name, pay_desc, pay_config, is_cod, pay_fee, enabled, is_online, is_pickup)" .
                   "VALUES ('$_POST[pay_code]', '$_POST[pay_name]', '$_POST[pay_desc]', '$pay_config', '$_POST[is_cod]', '$pay_fee', 1, '$_POST[is_online]', $_POST[is_pickup])";
            $db->query($sql);
			/* 代码修改_end   By www.ecshop68.com */
        }

        /* 记录日志 */
        admin_log($_POST['pay_name'], 'install', 'payment');

        sys_msg($_LANG['install_ok'], 0, $link);
    }
}


?>
