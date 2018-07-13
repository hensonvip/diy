<?php
/**
 * 自动提现定时任务
 */
define('IN_ECS', true);
require('../includes/init.php');
require_once( '../includes/lib_order.php');
require_once ('../includes/lib_clips.php');
if (local_date('d') == $_CFG['auto_tx_date']) {
	$sql = "SELECT user_id, user_name, user_money - frozen_money AS withdraw_money FROM " . $ecs->table('users') . " WHERE user_money - frozen_money >= $_CFG[auto_tx_money]";
	$tx_users = $db->getAll($sql);
	foreach ($tx_users as $key => $value) {
		$bank_card_id = $db->getOne("SELECT id FROM " . $ecs->table('bank_card') . " WHERE user_id = '$value[user_id]' LIMIT 1");
		if ($bank_card_id > 0) {
			$surplus = array(
			    'user_id' => $value['user_id'], 'process_type' => 1, 'payment' => '', 'user_note' => ''
			);
			// 插入会员账目明细
			$withdraw_money = '-' . $value['withdraw_money'];
			$surplus['rec_id'] = insert_user_account($surplus, $withdraw_money);
			if($surplus['rec_id'] > 0) {
				$db->query("UPDATE " . $ecs->table('user_account') . " SET bank_card_id = '$bank_card_id' WHERE id = '$surplus[rec_id]'");
				$db->query("UPDATE " . $ecs->table('users') . " SET frozen_money = frozen_money+'$value[withdraw_money]' WHERE user_id = '$value[user_id]'");
			    echo '用户名：' . $value['user_name'] . ' 提现成功!<br />';
			} else {
				echo '用户名：' . $value['user_name'] . ' 提现失败!<br />';
			}
		} else {
			echo '用户名：' . $value['user_name'] . ' 没有绑定银行卡!<br />';
		}
	}
} else {
	echo '系统设置的自动提现时间为每个月：' . $_CFG['auto_tx_date'] . '号';
}
?>