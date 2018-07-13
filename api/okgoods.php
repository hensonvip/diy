<?php
define('IN_ECS', true);
require('../includes/init.php');
require_once( '../includes/lib_order.php');

// 自动确认收货
$okg = $GLOBALS['db']->getAll("select order_id, add_time,shipping_time from " . $GLOBALS['ecs']->table('order_info') . " where shipping_status = 1 and order_status in(1,5,6)");
$okgoods_time = $GLOBALS['db']->getOne("select value from " . $GLOBALS['ecs']->table('shop_config') . " where code='okgoods_time'");
if(!empty($okg)){
	foreach($okg as $okg_id)
	{

		// $okg_time = $okgoods_time - (local_date('d',gmtime()) - local_date('d',$okg_id['add_time']));
		/*$okg_time = $okgoods_time - abs(local_date('d',gmtime()) - local_date('d',$okg_id['add_time']));
		$okg_times = $okgoods_time - abs(local_date('d',gmtime()) - local_date('d',$okg_id['shipping_time']));*/
		$okg_time = $okgoods_time - abs(diffBetweenTwoDays(gmtime(),$okg_id['add_time']));
		$okg_times = $okgoods_time - abs(diffBetweenTwoDays(gmtime(),$okg_id['shipping_time']));
		$is_back_now = 0;
		$is_back_now = $GLOBALS['db']->getOne("SELECT COUNT(*) FROM " . $ecs->table('back_order') . " WHERE order_id = " . $okg_id['order_id'] . " AND status_back < 6 AND status_back != 3");
		
		if ($okg_time <= 0 && $is_back_now == 0 && $okg_times <= 0 )
		{
			$db->query("update " . $ecs->table('order_info') . " set shipping_status = 2, shipping_time_end = " . gmtime() . "  where order_id = " . $okg_id['order_id']);
			if(get_cod_id($okg_id['order_id'])){
				get_pingtai_rebate_from_supplier($okg_id['order_id']);
				$GLOBALS['db']->query("UPDATE " . $GLOBALS['ecs']->table('order_info') . " SET rebate_ispay = 2 WHERE order_id = ".$okg_id['order_id']);

				/* 佣金结算 */
				commission($order_id);
			}
		}
	}
}



function diffBetweenTwoDays ($day1, $day2)
{
  $second1 = $day1;
  $second2 = $day2;
    
  if ($second1 < $second2) {
    $tmp = $second2;
    $second2 = $second1;
    $second1 = $tmp;
  }
  return ($second1 - $second2) / 86400;
}
// 自动通过审核
$okb = $GLOBALS['db']->getAll("select back_id, add_time, back_type from " . $GLOBALS['ecs']->table('back_order') . " where status_back = 5");
$okback_time = $GLOBALS['db']->getOne("select value from " . $GLOBALS['ecs']->table('shop_config') . " where code='okback_time'");
if(!empty($okb)){
	foreach($okb as $okb_id)
	{
		$okb_time = $okback_time - (local_date('d',gmtime()) - local_date('d',$okb_id['add_time']));
		if ($okb_time <= 0)
		{
			$status_back_c = ($okb_id['back_type'] == 4) ? 4 : 0;
			$GLOBALS['db']->query("update " . $GLOBALS['ecs']->table('back_order') . " set status_back = " . $status_back_c . " where back_id = " . $okb_id['back_id']);
			$GLOBALS['db']->query("update " . $GLOBALS['ecs']->table('back_goods') . " set status_back = " . $status_back_c . " where back_id = " . $okb_id['back_id']);
		}
	}
}


// 自动取消退货/维修（退货/维修买家发货期限）
/*$delback_time = $GLOBALS['db']->getOne("select value from " . $GLOBALS['ecs']->table('shop_config') . " where code='delback_time'");
$back_goods = $GLOBALS['db']->getAll("select back_id, add_time, invoice_no, shipping_id from " . $GLOBALS['ecs']->table('back_order') . " where status_back < 5");
if(!empty($back_goods)){
	foreach ($back_goods as $bgoods_list)
	{
		if ($bgoods_list['invoice_no'] == NULL or $bgoods_list['shipping_id'] == 0)
		{
			$delb_time = $delback_time - (local_date('d',gmtime()) - local_date('d',$bgoods_list['add_time']));
			if ($delb_time <= 0)
			{
				$GLOBALS['db']->query("update " . $GLOBALS['ecs']->table('back_order') . " set status_back = 7 where back_id = '" . $bgoods_list['back_id'] . "'");
				$GLOBALS['db']->query("update " . $GLOBALS['ecs']->table('back_goods') . " set status_back = 7 where back_id = '" . $bgoods_list['back_id'] . "'");
			}
		}
	}
}*/


// 虚拟商品自动下架
$virtual_goods = $GLOBALS['db']->getAll("select valid_date,goods_id from ". $GLOBALS['ecs']->table('goods') ." where is_virtual=1" );
if(!empty($virtual_goods)){
	foreach($virtual_goods as $v){
		
		if($v['valid_date']<gmtime()){
			 $GLOBALS['db']->query("update ". $GLOBALS['ecs']->table('goods') ." set is_on_sale = 0 where goods_id=".$v['goods_id']);
		}
	}
}



//订单付款超时自动取消 12小时不支付自动取消订单
$time_12h = 3600 * 12;
$differ_time = gmtime() - $time_12h;//现在支付的时间戳
$sql = "select order_id,order_sn from ".$GLOBALS['ecs']->table('order_info')." where (order_status=0 or order_status=1) and pay_status=0 and shipping_status=0 and add_time<=$differ_time";
$no_pay_order = $db->getAll($sql);
if(!empty($no_pay_order)){
	foreach ($no_pay_order as $key => $value) {
	  	update_order($value['order_id'], array('order_status' => 2, 'to_buyer' => '订单付款超时自动取消'));//取消订单
	  	change_order_goods_storage($value['order_id'], false, 1);//恢复库存
	}
}



//拼团过时，拼单失败改变操作状态
$time = gmtime();
//获取过期的拼主数据
$sql = "select id,user_id from ".$GLOBALS['ecs']->table('group_log')." where parent_id = 0 and end_time <= '$time' and is_finish = 0 ";
$group_log = $db->getAll($sql);
if(!empty($group_log)){
	foreach ($group_log as $k => $v) {
		//设置为拼团失败未退款状态
		$GLOBALS['db']->query("update ". $GLOBALS['ecs']->table('group_log') ." set is_finish = 2,finish_time = '$time' where id= '$v[id]' or parent_id = '$v[id]' ");
	}
}
/*
//获取拼团失败未退款状态的数据进行原路退款
$sql = "select gl.id,gl.user_id,oi.order_id,gl.goods_id,gl.product_id from ".$GLOBALS['ecs']->table('group_log')." as gl LEFT JOIN " . $GLOBALS['ecs']->table('order_info') . " AS oi ON gl.order_id = oi.order_id where gl.parent_id = 0 and gl.end_time <= '$time' and gl.is_finish = 2 ";
$group_log = $db->getAll($sql);
//print_r($group_log);die;
if(!empty($group_log)){
	require_once(ROOT_PATH . 'admin/includes/lib_main.php');
	foreach ($group_log as $k => $v) {
		$user_id = intval($v['user_id']);
		$order_id = intval($v['order_id']);
		if(!$order_id){
	    	continue;
	    }
	    
		//生成退款订单记录
		$sql_oi = "SELECT order_id,order_sn,supplier_id,order_status,shipping_status,pay_status,shipping_time_end,extension_code,(goods_amount +  insure_fee + pay_fee + pack_fee + card_fee + tax - discount - integral_money - bonus) AS total_fee,shipping_fee FROM " . $GLOBALS['ecs']->table('order_info') . " WHERE user_id='$user_id' AND order_id = " . $order_id;
	    $order_info = $GLOBALS['db']->getRow($sql_oi);

	    if(empty($order_info)){
	    	continue;
	    }

	    $data = array();
        $data['type']=($order_info['extension_code']=='virtual_good')?1:0;
        $data['order_sn']=$order_info['order_sn'];
	    $data['order_id']=$order_info['order_id'];
	    $data['user_id']=$user_id;
	    $data['add_time']=gmtime();
    	$data['postscript']='';
    	$data['back_reason']='拼单失败，自动退款！';
    	$data['back_type']=4;
    	$data['status_back']=5;
    	$data['supplier_id']=$order_info['supplier_id'];
    	$data['shipping_fee']=$order_info['shipping_fee'];
    	$data['back_pay']=2;
	    $data['goods_id']=0;
    	$data['product_id']=0;
    	$data['goods_name']='';
    	$data['refund_money_1']=$order_info['total_fee'];
    	$GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('back_order'), $data, 'INSERT');
		$back_id = $GLOBALS['db']->insert_id();

	    $goods_id = $v['goods_id'];
		$product_id = $v['product_id'];
		$where="";
        if($goods_id>0){
        	$where=" AND og.goods_id=$goods_id AND og.product_id=$product_id";
        }
		$sql_og = "SELECT  og.goods_id, og.product_id,og.goods_sn,og.goods_name, og.goods_number, " .
            "og.goods_price, og.goods_attr,  " .
            "og.goods_price * og.goods_number AS subtotal,  og.order_id, og.extension_code  " .
            "FROM " . $GLOBALS['ecs']->table('order_goods') . "as og " .
            " WHERE og.order_id = '$order_id' $where";
        $goods_list = $GLOBALS['db']->getAll($sql_og); 

        if(!empty($goods_list)){
        	$data2 = array();
        	foreach ($goods_list as $key2 => $value2) {
				$data2['back_id']=$back_id;
				$data2['goods_id']=$value2['goods_id'];
				$data2['goods_name']=$value2['goods_name'];
				$data2['goods_sn']=$value2['goods_sn'];
				$data2['product_id']=$value2['product_id'];
				$data2['goods_attr']=$value2['goods_attr'];
				$data2['back_type']=4;
				$data2['back_goods_number']=$value2['goods_number'];
				$data2['back_goods_price']=$value2['subtotal'];
				$data2['status_back']=5;
				$GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('back_goods'), $data2, 'INSERT');
				
			}
        }
        
	    //订单id  订单金额  退款id  退款金额  描述
		$money_paid = $GLOBALS['db']->getOne("SELECT money_paid FROM " .$GLOBALS['ecs']->table('order_info'). " where order_id = '$order_id'");
		$refund_id = $back_id;
		$refund_amount = $order_info['total_fee'];
		$desc = '拼单失败，自动退款！';
		$refund_id = wxRefund($order_id,$money_paid,$refund_id,$refund_amount,$desc);
		if(!empty($refund_id)){
			$is_finish = 4;//拼团失败并原路退回到支付账户
			$GLOBALS['db']->query("update ". $GLOBALS['ecs']->table('back_order') ." set refund_money_2 = refund_money_1,status_back = 3,status_refund = 1 where back_id=".$back_id);
			$GLOBALS['db']->query("update ". $GLOBALS['ecs']->table('back_goods') ." set status_back = 3,status_refund = 1,refund_id='$refund_id' where back_id=".$back_id);
		}else{
			$is_finish = 5;//拼团失败自动原路退回支付账户失败
		}
		$GLOBALS['db']->query("update ". $GLOBALS['ecs']->table('group_log') ." set is_finish = '$is_finish',back_id = '$back_id',refund_id='$refund_id' where id=".$v['id']);
		
	}
}



//当达到最低价时，调用推送功能
require_once(ROOT_PATH . 'api_includes/modules/payment/weixin.php');
$payment = new weixin();
// 交易时间{{keyword1.DATA}}
// 商品详情{{keyword2.DATA}}
// 订单状态{{keyword3.DATA}}
// 现价{{keyword4.DATA}}
// 原价{{keyword5.DATA}}
// 备注{{keyword6.DATA}} 
$log_info = $GLOBALS['db']->getAll("SELECT bl.bargain_id,bl.now_price,bl.help_user_id,ba.low_price,ba.goods_name,ba.shop_price,ba.add_time FROM " . $GLOBALS['ecs']->table('bargain_log') . " as bl LEFT JOIN " . $GLOBALS['ecs']->table('bargain_activity') . " AS ba ON bl.now_price = ba.low_price WHERE ba.low_price > 0 and bl.order_id = 0 and bl.status = 0");
if(!empty($log_info)){
	foreach ($log_info as $k => $v) {
		$time_format = local_date("Y-m-d H:i:s", $v['add_time']);
		$goods_name = $v['goods_name'];
		$shop_price = $v['shop_price'];
		$current_price = $v['low_price'];
		//file_put_contents(ROOT_PATH.'log2.txt', var_export($goods_name, true),FILE_APPEND);//打印数组
		$json = '{
				  "keyword1": {
					  "value": "'.$time_format.'", 
					  "color": "#173177"
				  }, 
				  "keyword2": {
					  "value": "'.$goods_name.'", 
					  "color": "#173177"
				  }, 
				  "keyword3": {
					  "value": "商品已砍到最低价", 
					  "color": "#173177"
				  } , 
				  "keyword4": {
					  "value": "'.$current_price.'", 
					  "color": "#173177"
				  }, 
				  "keyword5": {
					  "value": "'.$shop_price.'", 
					  "color": "#173177"
				  } ,
				  "keyword6": {
					  "value": "请尽快去支付，祝您生活愉快！", 
					  "color": "#173177"
				  } 
			  }';
		$payment->tuisong($v['help_user_id'],$v['bargain_id'],$json);  //会员id 砍价id 上面的json
	}
}

//当活动时间结束时，调用推送功能通知还没下单的用户
$time = gmtime();
$id_array = $GLOBALS['db']->getAll("SELECT id FROM " . $GLOBALS['ecs']->table('bargain_activity') . " where is_inform = 0 and end_time <= '$time'");
if(!empty($id_array)){
	$id_str = '0';
	foreach ($id_array as $k => $v) {
		$id_str .= ','.$v['id'];
	}
	$log_info = $GLOBALS['db']->getAll("SELECT * FROM " . $GLOBALS['ecs']->table('bargain_log') . " where order_id = 0 and user_id = help_user_id and bargain_id in ($id_str)");
	if(!empty($log_info)){
		foreach ($log_info as $k => $v) {
			$time_format = local_date("Y-m-d H:i:s", $v['add_time']);
			$goods_name = $GLOBALS['db']->getOne("select goods_name from " . $GLOBALS['ecs']->table('goods') . " where goods_id = '".$v['goods_id']."'");
			$shop_price = $GLOBALS['db']->getOne("select shop_price from " . $GLOBALS['ecs']->table('bargain_activity') . " where id = '".$v['bargain_id']."'");
			$current_price = $GLOBALS['db']->getOne("select now_price from " . $GLOBALS['ecs']->table('bargain_log') . " where bargain_id = '".$v['bargain_id']."' and goods_id = '".$v['goods_id']."' and product_id = '".$v['product_id']."' and order_id = 0 order by now_price asc");
			$json = '{
					  "keyword1": {
						  "value": "'.$time_format.'", 
						  "color": "#173177"
					  }, 
					  "keyword2": {
						  "value": "'.$goods_name.'", 
						  "color": "#173177"
					  }, 
					  "keyword3": {
						  "value": "商品砍价活动时间结束", 
						  "color": "#173177"
					  } , 
					  "keyword4": {
						  "value": "'.$current_price.'", 
						  "color": "#173177"
					  }, 
					  "keyword5": {
						  "value": "'.$shop_price.'", 
						  "color": "#173177"
					  } ,
					  "keyword6": {
						  "value": "请尽快去支付，祝您生活愉快！", 
						  "color": "#173177"
					  } 
				  }';
			$payment->tuisong($v['help_user_id'],$v['bargain_id'],$json);  //会员id 砍价id 上面的json
		}
		$GLOBALS['db']->query("update ". $GLOBALS['ecs']->table('bargain_activity') ." set is_inform = 1 where id in ($id_str) ");
	}

}*/





?>