<?php
/**
 * 来源统计信息
 *
 * User: waen
 * Date: 15-4-30
 * Time: 上午11:15
 */

require_once(ROOT_PATH . 'includes/lib_order.php');
require_once(ROOT_PATH . 'includes/cls_order.php');
class StatisticController extends BaseController
{
	function order()
	{
		$cls_order = new cls_order();
		$start_time = intval($_REQUEST['start_time']);
		$end_time = intval($_REQUEST['end_time']);
		$type = trim($_REQUEST['type']);
		//业务日志
		$log_config = array(
				'type'=>'file',
				'log_path'=> ROOT_PATH . 'data/logs/statistic/',
		);
		$logger = new Logger($log_config);
		if((strlen($start_time) != 10) || (strlen($end_time) != 10) || empty($type))
		{
			$logger->writeLog('统计开始时间：'.$start_time.'，统计结束时间：'.$end_time.'，统计类型：'.$type);
			throw new StatisticException(400);
		}
		$data = array();
		$code = 0;
		$message = 'ok';
		switch($type)
		{
			case 'baidu':
				$data['coupons_count'] = 0;
				$data['coupons_obtained'] = 0;
				$data['coupons_used'] = 0;
				$sql = "SELECT COUNT(*) FROM ".$GLOBALS['ecs']->table('users')." WHERE is_local=3 AND reg_time >= {$start_time} AND reg_time < {$end_time}";
				$data['user_enrolled_count'] = $GLOBALS['slave_db']->getOne($sql);
				$order_sql = "SELECT SUM(".$cls_order->order_amount_field().") FROM ".$GLOBALS['ecs']->table('order_info')." WHERE source = '{$type}' "
								." AND add_time >= {$start_time} AND add_time < {$end_time} AND parent_order < 1 AND pay_status < 3";
				$data['orders_sum'] = $GLOBALS['slave_db']->getOne($order_sql);
				if(empty($data['orders_sum']))
				{
					$data['orders_sum'] = 0.00;
				}
				$count_sql = "SELECT COUNT(*) FROM ".$GLOBALS['ecs']->table('order_info')." WHERE source = '{$type}' "
								." AND add_time >= {$start_time} AND add_time < {$end_time} AND parent_order < 1";
				$order_paid_sql = $count_sql." AND pay_status = 2";
				$data['paid_orders_count'] = $GLOBALS['slave_db']->getOne($order_paid_sql);
				$order_unpaid_sql = $count_sql." AND pay_status = 0";
				$data['unpaid_orders_count'] = $GLOBALS['slave_db']->getOne($order_unpaid_sql);
				$order_pay_sql = "SELECT SUM(money_paid) FROM ".$GLOBALS['ecs']->table('order_info')." WHERE source = '{$type}' "
							." AND add_time >= {$start_time} AND add_time < {$end_time} AND pay_status = 2 AND parent_order < 1";
				$data['paid_orders_sum'] = $GLOBALS['slave_db']->getOne($order_pay_sql);
				if(empty($data['paid_orders_sum']))
				{
					$data['paid_orders_sum'] = 0.00;
				}
				break;
			default:
				$data = array();
		}
		if(empty($data))
		{
			$code = -1;
			$message = $type.'类型不存在';
		}
		$return = array('code' => $code, 'msg' => $message, 'data'=>(is_array($data) ? $data : array($data)) );
		$logger->writeLog(json_encode($return), 'info');
		Response::render($data, $code, $message);
	}
}