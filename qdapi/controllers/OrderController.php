<?php
/**
 * 订单接口
 *
 * @version v1.0
 * @create 2016-10-26
 * @author cyq
 */

require_once(ROOT_PATH . 'includes/cls_order.php');
require_once(ROOT_PATH . 'includes/cls_user.php');
require_once(ROOT_PATH . 'includes/lib_common.php');
require_once(ROOT_PATH . 'includes/cls_cart.php');

class OrderController extends ApiController
{
	public function __construct()
	{

		parent::__construct();
		$this->data = json_decode(stripslashes($this->input('data')),true);
		$this->order     = cls_order::getInstance();
		$this->user     = cls_user::getInstance();
		$this->cart     = cls_cart::getInstance();
		$config = array(
			'type'=>'file',
			'log_path'=> ROOT_PATH . '/data/logs/api/order/'
		);
		$this->logger = new Logger($config);
	}

	public function test(){
		exit('1111');
	}

	private function log($msg, $level = 'info')
	{
		$this->logger->writeLog($msg, $level, 'order');
	}

	protected function statusName(&$status)
	{

		require_once(ROOT_PATH . 'languages/zh_cn/admin/order.php');
		global $_LANG;
		$order_status = isset($_LANG['os'][$status['order_status']]) ? strip_tags($_LANG['os'][$status['order_status']]) :  "未知状态";
		$shipping_status = isset($_LANG['ss'][$status['shipping_status']]) ? strip_tags($_LANG['ss'][$status['shipping_status']]) :  "未知状态";
		$pay_status = isset($_LANG['ps'][$status['pay_status']]) ? strip_tags($_LANG['ps'][$status['pay_status']]) :  "未知状态";
		$delivery_status = isset($_LANG['delivery_status'][$status['delivery_status']]) ? strip_tags($_LANG['delivery_status'][$status['delivery_status']]):"未知状态";
		return array('order_status'=>$order_status, 'shipping_status'=>$shipping_status, 'pay_status'=>$pay_status, 'delivery_status'=>$delivery_status);
	}

	/**
	 * 支付异步通知接口
	 *
	 * @return json
	 * @create 2015-10-26 10:28:33
	 * @author lwp
	 */
	public function updatePayStatus()
	{

		//dump($_REQUEST);die;
		//$order_sn = $this->input('order_sn');
		//$pay_money = $this->input('order_amount');
		//$trade_no = $this->input('trade_no');
		//$pay_serial_no = $this->input('order_sn');
		//$mamapay_id = $this->input('pay_id');
		//$buyer = $this->input('buyer');
		//$pay_status = $this->input('pay_status');

		// 必传字段
		$required_fields = array('order_sn', 'order_amount', 'trade_no', 'pay_serial_no', 'pay_id','order_status');
		foreach ($required_fields as $v)
		{
			if (!($$v = $this->input($v)))
			{
				$this->error("参数错误: `$v` 不能为空", 400);
			}
		}
		// 非必传字段 buyer_account
		$buyer_account = $this->input('buyer_account', '');

		$this->log("============ begin process, order_sn: $order_sn ================");

		// 转换pay域pay_id
		$mamapay_id = $pay_id;
		$pay_money = (float) $order_amount;

		$cls_order = cls_order::getInstance();
		// 订单信息
		$order = $cls_order->getOrderBySn($order_sn);
		$this->log("cls_order->getOrderBySn: ".json_encode($order), 'INFO');
		if (!isset($order['code']) || $order['code'] != 0)
		{
			$this->error("找不到该订单信息");
		}
		$order = $order['data'];

		// 检查是否是父订单
		$child_order = false;
		$child_order_fee = 0;
		if ($order['parent_order'] == -1)
		{
			//$sql = "SELECT order_id, order_sn, pay_id, pay_name, order_amount, pay_status, order_status, parent_order,
			//(goods_amount + shipping_fee + insure_fee + pay_fee + pack_fee + card_fee + tax - discount - bonus) AS total_fee
			//FROM {$this->_tbl} WHERE parent_order = :pid";
			//$child_order = $this->getWriteConnection()->fetchAll(
			//$sql,
			//$this->_assoc,
			//array(':pid' => $order['order_id'])
			//);
			$child_order = $cls_order->getChildrenByPid($order['order_id']);
			$this->log("cls_order->getChildrenByPid: ".json_encode($child_order), 'INFO');

			// 找不到子订单?
			if (!isset($child_order['code']) || $child_order['code'] != 0 || empty($child_order['data']))
			{
				$this->error("找不到子订单");
			}
			$child_order = $child_order['data'];

			// 计算子订单总金额
			foreach ($child_order as &$v)
			{
				$v['total_fee'] = $v['goods_amount'] + $v['shipping_fee'] - $v['bonus'];
				$child_order_fee += $v['total_fee'];
			}
			unset($v);

		}

		$payment = cls_payment::getInstance()->getByMamapayId($pay_id);
		$this->log("cls_payment->getByMamapayId: ".json_encode($payment), 'INFO');
		if (empty($payment))
		{
			$this->error('无效的支付方式: pay_id='.$pay_id);
		}
		$pay_id = $payment['pay_id'];

		// 退款单操作类
		// 1. 重复支付时生成退款单
		// 2. 订单已取消时需要生成退款单
		$cls_refund = cls_refund::getInstance();

		//$refund_data = array(
				//'order_id' 	=>$order['order_id'],
				//'order_sn' 	=>$order['order_sn'],
				//'trade_no' 	=>$trade_no,
				//'pay_id' 	=>$pay_id,
				//'order_amount' 	=>$order['order_amount'],
				//'pay_money' 	=>$pay_money,
				//'refund_money' 	=>$pay_money,
				//);
		//$this->log("refund_data: ".json_encode($refund_data), 'INFO');

		//如果已经支付过: 0=未付款 1=付款中 2=已付款
		if (intval($order['pay_status']) != 0)
		{
			if ($order['pay_id'] != $pay_id)
			{
				//没有退款单
				$sql = "SELECT order_id  FROM ".$GLOBALS['ecs']->table('refund')." WHERE order_id={$order['order_id']} AND trade_no=$trade_no";
				$refund_info = $GLOBALS['db']->getRow($sql);
				if (!$refund_info)
				{
					$refund_data = array();
					$refund_data['order_id'] = $order['order_id'];
					$refund_data['refund_money'] = $pay_money;
					$refund_data['refund_status'] = RFS_WAIT_CHECKOUT;
					$refund_data['refund_type'] =RFT_PAY_EXCEPTION;
					$rs = $cls_refund->create($refund_data);
					if (!$rs)
					{
						$log_msg = '订单重复支付，系统生成退款单失败';
						$this->log($log_msg.', data: '.json_encode($refund_data), 'ERROR');
					}
					else
					{
						$log_msg = '订单重复支付，系统生成退款单成功';
						$this->log($log_msg.', data: '.json_encode($refund_data), 'INFO');
					}
					order_action($order['order_sn'], $order['order_status'], $order['shipping_status'], PS_PAYED, $log_msg, 0, '系统');
				}
			}
			//$this->error('该订单已经支付过');
			Response::render(array(), 0, 'OK');
		}

		$pay_id_change = false;
		$time_now = time();
		$order_values = array(
				'pay_status' => PS_PAYED,
				'money_paid' => $pay_money,
				'pay_time'   => $time_now,
				);

		if ($order['pay_id'] != $pay_id)
		{
			if ($order['pay_id'])
			{
				$pay_id_change = true;
			}
			$order_values['pay_id'] = $pay_id;
			$order_values['pay_name'] = $payment['pay_name'];
		}

		$GLOBALS['db']->query('START TRANSACTION');
		if (!$cls_order->updateStatus($order['order_id'], $order_values))
		{
			$GLOBALS['db']->query('ROLLBACK');
			$this->log('更新订单状态失败, data: '.json_encode($order_values), 'ERROR');
			$this->error('更新订单状态失败');
		}
		$this->log('更新订单状态成功, data: '.json_encode($order_values), 'INFO');

		//pay_type 订单支付=0  会员预付款=1
		$pay_log_data = array(
				'order_id' 	=>$order['order_id'],
				'trade_no' 	=>$trade_no,
				'order_amount' 	=>$pay_money,
				'is_paid' 	=>1,
				'buyer_account' =>$buyer_account,
				'add_time' 	=>$time_now,
				'fm' 		=>$order['source'],
				'pay_serial_no' =>$pay_serial_no
				);


		$this->log("pay_log_data: ".json_encode($pay_log_data), 'INFO');
		$rs = $GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('pay_log'), $pay_log_data, 'INSERT', '', 'SILENT');
		if (!$rs)
		{
			$GLOBALS['db']->query('ROLLBACK');
			$this->log('写入pay_log失败, data: '.json_encode($pay_log_data), 'error');
			$this->error('保存支付记录失败');
		}
		$this->log('写入pay_log成功, data: '.json_encode($pay_log_data), 'INFO');

		$pay_log_str = '';
		if ($pay_id_change) {
			$pay_log_str = '支付方式由原：'.$order['pay_id'].'-'.$order['pay_name'].', 改为实际支付方式：'.$pay_id.'-'.$payment['pay_name'].',';
		}
		$pay_log_str .= '支付成功，支付金额：'. $pay_money;

		order_action($order['order_sn'], $order['order_status'], $order['shipping_status'], PS_PAYED, $pay_log_str, 0, '系统');

		// 如果订单已经被取消，生成退款单
		// 父订单不生成退款单
		if ($order['order_status'] == 2 && $order['parent_order'] > -1)
		{
			$cls_order->updateStatus($order['order_id'], array('pay_status'=>PS_BACKING, 'money_paid'=>$pay_money));
			$refund_data = array();
			$refund_data['order_id'] = $order['order_id'];
			$refund_data['refund_money'] = $pay_money;
			$refund_data['refund_status'] = RFS_WAIT_CHECKOUT;
			$refund_data['refund_type'] = 2;
			if (!$cls_refund->create($refund_data))
			{
				$log_msg = "订单已取消，系统生成退款单失败";
				$this->log($log_msg.', data: '.json_encode($refund_data), 'ERROR');
			}
			else
			{
				$log_msg = "订单已取消，系统生成退款单成功";
				$this->log($log_msg.', data: '.json_encode($refund_data), 'INFO');
			}
			order_action($order['order_sn'], $order['order_status'], $order['shipping_status'], PS_PAYED, $log_msg, 0, '系统');
		}

		// 更新子订单状态
		if (!empty($child_order))
		{
			$child_order_count = count($child_order);
			foreach ($child_order as $k=>$v)
			{
				// 校验子订单实付金额
				if ($child_order_fee == $pay_money)
				{
					// 总额相等， 直接用子订单金额作为实付金额
					$child_order_paid_money = $v['total_fee'];
				}
				else
				{
					// 总额不相等，最后一个子订单承担
					$diff = $child_order_fee - $pay_money;
					if ($diff > 0)
					{
						// 少付了
						$child_order_paid_money = $v['total_fee'] - $diff;

					}
					else
					{
						// 多付了，按子订单实际计算
						$child_order_paid_money = $v['total_fee'];
					}
				}

				$order_values['money_paid'] = $child_order_paid_money;
				if (!$cls_order->updateStatus($v['order_id'], $order_values))
				{
					$GLOBALS['db']->query('ROLLBACK');
					$this->log('更新子订单（order_id='.$v['order_id'].'）状态失败, data: '.json_encode($order_values), 'error');
					$this->error('更新子订单状态失败');
				}

				$pay_log_data['order_id'] = $v['order_id'];
				$pay_log_data['order_amount'] = $child_order_paid_money;
				$rs = $GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('pay_log'), $pay_log_data, 'INSERT', '', 'SILENT');
				if (!$rs)
				{
					$GLOBALS['db']->query('ROLLBACK');
					$this->log('写入pay_log失败, data: '.json_encode($pay_log_data), 'error');
					$this->error('保存支付记录失败');
				}

				$pay_log_str = '';
				if ($pay_id_change)
				{
					$pay_log_str = '支付方式由原：'.$v['pay_id'].'-'.$v['pay_name'].', 改为实际支付方式：'.$pay_id.'-'.$payment['pay_name'].',';
				}
				$pay_log_str .= '支付成功，支付金额：'. $child_order_paid_money;

				order_action($v['order_sn'], $v['order_status'], $v['shipping_status'], PS_PAYED, $pay_log_str, 0, '系统');

				//如果订单已经被取消，生成退款单
				if ($v['order_status'] == 2)
				{
					$cls_order->updateStatus($v['order_id'], array('pay_status'=>PS_BACKING, 'money_paid'=>$child_order_paid_money));
					$refund_data = array();
					$refund_data['order_id'] = $v['order_id'];
					$refund_data['refund_money'] = $child_order_paid_money;
					$refund_data['refund_status'] = RFS_WAIT_CHECKOUT;
					$refund_data['refund_type'] = 2;
					if ($cls_refund->create($refund_data))
					{
						$log_msg = "收到异步通知时，订单状态为已取消，系统生成退款单失败";
						$this->log($log_msg.', data: '.json_encode($refund_data), 'ERROR');
					}
					else
					{
						$log_msg = "收到异步通知时，订单状态为已取消，系统生成退款单成功";
						$this->log($log_msg.', data: '.json_encode($refund_data), 'INFO');
					}
					order_action($v['order_sn'], $v['order_status'], $v['shipping_status'], PS_PAYED, $log_msg, 0, '系统');
				}

			}
			// end of $child_order
		}
		// end of if

		$GLOBALS['db']->query('COMMIT');

		$this->log("============ finish process, order_sn: $order_sn ================");
		Response::render(array(), 0, 'OK');
	}


	 /* 获取要报关的订单的用户身份证图片链接
	 *
	 * @return json
	 * @create 2015-10-26 10:32:41
	 * @author lwp
	 * @wiki http://wiki.corp.mama.cn/pages/viewpage.action?pageId=65079017
	 */
	public function idCardList()
	{
		$params = json_decode(stripslashes($this->input('data')), true);
		// 必传字段
		$required_fields = array('supplierId', 'payTimeStart', 'payTimeEnd');
		foreach ($required_fields as $v)
		{
			if (!isset($params[$v]) || empty($params[$v]))
			{
				$this->error("参数错误: `$v` 不能为空", 400);
			}
			else
			{
				$input[$v] = addslashes($params[$v]);
			}
		}

		$input['order_sn']       = empty($params['orderSn'])?'':addslashes($params['orderSn']);
		$input['mobile']         = empty($params['mobile'])?'':addslashes($params['mobile']);
		$input['supplier_id']    = intval($input['supplierId']);
		$input['payTimeStart']   = addslashes($input['payTimeStart']);
		$input['payTimeEnd']     = addslashes($input['payTimeEnd']);
		$result['idCards'] = cls_order::getInstance()->getOrderCustom($input);
		$result['idCards'] = $result['idCards']['data'];

//		$result['idCards'] = empty($result['idCards'])?new stdClass():$result['idCards'];
		$data = $this->mapFields($result);


		$this->success($data);
	}

	/**
	 * 获取订单详情
	 *
	 * @return json
	 * @create 2015-10-26 10:32:41
	 * @author lwp
	 * @wiki http://wiki.corp.mama.cn/pages/viewpage.action?pageId=65078671
	 */
	public function detail()
	{
		$params = json_decode(stripslashes($this->input('data')), true);

		if (empty($params) || !isset($params['orderId']))
		{
			$this->error('参数错误');
		}

		$cls_order = cls_order::getInstance();

		// 订单基本信息
		$order = $cls_order->getOrderById($params['orderId']);
		if (!isset($order['data']) || empty($order['data']))
		{
			$this->success(new stdClass(), 0, $msg = "查找不到相应的订单");
		}
		else
		{
			$order = $order['data'];

			// 供应商字段校验
			if (isset($params['supplierId']) && $params['supplierId'] != $order['supplier_id'])
			{
				$this->success(new stdClass(), 0, $msg = "查找不到相应的订单");
			}

			$order['composite_status'] = $cls_order->get_composite_status($order['order_status'], $order['shipping_status'], $order['pay_status']);
			$order['good_is_over_sea'] = 0;
			$order['good_is_virtual'] = 0;
			$order_id = $order['order_id'];
		}

		// 优惠金额
		$order['goods_amount'] = bcsub($order['goods_amount'],$order['bonus'], 2);

		// 操作日志
		$action_list = array();
		$action = $cls_order->getOrderAction($order_id);
		if (isset($action['data']) && $action['data'])
		{
			$action_list = $action['data'];
		}
		$order['action_list'] = $action_list;

		// 商品
		if ($order['parent_order'] == -1)
		{
			$goods_list = $cls_order->getGoodsByParentId($order_id);
		}
		else
		{
			$goods_list = $cls_order->getGoodsById($order_id);
		}
		if (isset($goods_list['data']) && $goods_list['data'])
		{
			$storage_list = storage_list();
			foreach ($goods_list['data'] as $goods)
			{
				// 海外直邮
				if (isset($storage_list[$goods["delivery_method"]]) && $storage_list[$goods["delivery_method"]]["storage_cat_id"] == 6)
				{
					$order['good_is_over_sea'] = 1;
				}

				// 虚拟商品
				if ($goods['goods_own_type'] == 4)
				{
					$order['good_is_virtual'] = 1;
				}

				break;
			}
			$goods_list = $goods_list['data'];

		}
		$order['goods_list'] = $goods_list;
		if($order['is_customs'] > 0){
			$order_customs = cls_delivery::getInstance()->getOrderCustoms($order['order_id']);
			$order_customs = $order_customs['data'];
		}
		$order['order_customs'] = empty($order_customs)?'':$order_customs;

		$data = $this->mapFields($order);

		$this->success($data);
	}

	/**
	 * 记录订单操作日志
	 *
	 * @return
	 * @create 2015-10-26 10:35:41
	 * @author lwp
	 * @wiki http://wiki.corp.mama.cn/pages/viewpage.action?pageId=65079014
	 */
	public function actionLog()
	{
		$params = json_decode(stripslashes($this->input('data')), true);
		$required_fields = array('orderId', 'content', 'actionUser');
		foreach ($required_fields as $v)
		{
			if (!isset($params[$v]) || empty($params[$v]))
			{
				$this->error("参数错误: `$v` 不能为空", 400);
			}
		}

		$input = array(
				'order_id' 	=>intval($params['orderId']),
				'action_note' 	=>addslashes($params['content']),
				'action_user' 	=>addslashes($params['actionUser']),
			      );

		$action_place_map = array('delivery'=>1, 'back'=>2, 'refund'=>3);
		$input['action_place'] = isset($params['actionPlace']) && isset($action_place_map[$params['actionPlace']]) ? $action_place_map[$params['actionPlace']] : 0;

		$input['relation_field'] = isset($params['relationField']) ? intval($params['relationField']) : 0;

		$result = cls_order::getInstance()->addOrderAction($input);
		if (!isset($result['code']) || $result['code'] !== 0)
		{
			$msg = isset($result['msg']) ? $result['msg'] : '未知错误';
			$this->error($msg);
		}

		$this->success(new stdClass());
	}

	/**
	 * 查询
	 *
	 * @return
	 * @create 2015-10-26 10:52:22
	 * @author lwp
	 * @wiki http://wiki.corp.mama.cn/pages/viewpage.action?pageId=65078667
	 */
	public function query()
	{
		$params = json_decode(stripslashes($this->input('data')), true);

		$cls_order = cls_order::getInstance();

		$page = $this->getPageParam($params);

		$params['supplierId'] = empty($params['supplierId'])?$this->error("参数错误: 供应商ID不能为空", 400):$params['supplierId'];

		// 查询条件
		$where = ' order_status NOT IN ('.OS_UNCONFIRMED.','.OS_INVALID.') AND pay_status NOT IN ('.PS_UNPAYED.','.PS_PAYING.') ';

		// 是否需要连表查询
		$need_join = false;

		// 查订单字段
		if (isset($params['orderSn']) && $params['orderSn'])
		{
			$where .= " AND order_sn='{$params['orderSn']}'";
		}

		if (isset($params['supplierId']) && $params['supplierId'])
		{
			$where .= " AND supplier_id=".intval($params['supplierId']);
		}
		if (isset($params['mobile']) && $params['mobile'])
		{
			$where .= " AND mobile='{$params['mobile']}'";
		}

		if (isset($params['payTimeStart']) && $params['payTimeStart'])
		{
			$where .= " AND pay_time >= {$params['payTimeStart']}";
		}

		if (isset($params['payTimeEnd']) && $params['payTimeEnd'])
		{
			$where .= " AND pay_time <= {$params['payTimeEnd']}";
		}

		if (isset($params['queryType']))
		{
			// 1已发货 2已签收 3已完成 4已退款
			$type_map = array(
					0 => true,
					1 => CS_SHIPPED,
					2 => CS_RECEIVED,
					3 => CS_FINISHED,
					4 => CS_PAY_RETURNED,
					);
			if (!isset($type_map[$params['queryType']]))
			{
				$this->error('错误的参数值：queryType='.$params['queryType']);
			}
			if ($params['queryType'])
			{
				$where .= $cls_order->order_status_sql($type_map[$params['queryType']]);
			}
		}

		// 订单商品表字段
		if (isset($params['productSn']) && $params['productSn'])
		{
			$need_join = true;
			$where .= " AND product_sn = '{$params['productSn']}'";
		}

		if (isset($params['keyWords']) && $params['keyWords'])
		{
			$need_join = true;
			$where .= " AND goods_name LIKE '%{$params['keyWords']}%'";
		}

		if ($need_join)
		{
			// 按商品信息查询
			$count = $cls_order->countOrderByOrderGoods($where);
			if (!isset($count['data']) || empty($count['data']))
			{
				// 无数据
				$return['order'] = array();
				$return['page'] = array('page'=>$page['current_page'], 'limit'=>$page['page_size'], 'count'=>0);
				$this->success($return);
			}

			$order = $cls_order->getOrdersByOrderGoods($where, "{$page['start']}, {$page['page_size']}", "order_sn DESC");
			// 无数据直接返回
			if (!isset($order['data']) || empty($order['data']))
			{
				$return['order'] = array();
				$return['page'] = array('page'=>$page['current_page'], 'limit'=>$page['page_size'], 'count'=>0);
				$this->success($return);
			}

			$order = $order['data'];
		}
		else
		{
			// 按订单信息查询
			$count = $cls_order->countOrder($where);
			if (!isset($count['data']) || empty($count['data']))
			{
				$return['order'] = array();
				$return['page'] = array('page'=>$page['current_page'], 'limit'=>$page['page_size'], 'count'=>0);
				$this->success($return);
			}

			$order = $cls_order->getOrders($where, "{$page['start']}, {$page['page_size']}", "order_id DESC");
			// 无数据直接返回
			if (!isset($order['data']) || empty($order['data']))
			{
				$return['order'] = array();
				$return['page'] = array('page'=>$page['current_page'], 'limit'=>$page['page_size'], 'count'=>0);
				$this->success($return);
			}

			$order = $order['data'];
		}


		// 查订单商品
		$arr_order_id = array_column($order, 'order_id');
		if (empty($arr_order_id))
		{
			$this->error('系统异常', -2);
		}
		$goods = $cls_order->getGoodsById($arr_order_id);
		$goods = (!isset($goods['data']) || empty($goods['data'])) ? array() : $goods['data'];

		foreach ($order as &$v)
		{
			$v['composite_status'] = $cls_order->get_composite_status($v['order_status'], $v['shipping_status'], $v['pay_status']);
			foreach ($goods as $g)
			{
				if ($v['order_id'] == $g['order_id'])
				{
					$v['goods_list'][] = $g;
				}
			}

			// 计算优惠金额
			$v['goods_amount'] = bcsub($v['goods_amount'],$v['bonus'], 2);
		}
		unset($v);

		$data = array();
		$data['orders'] = $this->mapFields($order);
		$data['page'] = array('page'=>$page['current_page'], 'limit'=>$page['page_size'], 'count'=>$count['data']);

		$this->success($data);
	}


	/**
	 * 商家中心导出
	 *
	 * @return
	 * @create 2015-10-30
	 * @author lwp
	 * @wiki http://wiki.corp.mama.cn/pages/viewpage.action?pageId=65079004
	 */
	public function export()
	{
		$params = json_decode(stripslashes($this->input('data')), true);
		$this->log("============== export params =============");
		$this->log(json_encode($params));

		$cls_order = cls_order::getInstance();


		// supplierId验证
		$supplierId = intval($params['supplierId']);
		if (!isset($params['supplierId']) || $supplierId <= 0)
		{
			$this->error('参数 supplierId 不能为空', 400);
		}

		// paytime
		// 间隔限制为3个月内
		if (!isset($params['payTimeStart']) || intval($params['payTimeStart']) <= strtotime("20140901")
				|| !isset($params['payTimeEnd']) || intval($params['payTimeEnd']) < $params['payTimeStart']
				|| $params['payTimeEnd'] - $params['payTimeStart'] > 90 * 24 * 3600)
		{
			$this->error('导出时间不正确', 400);
		}

		// 查询条件
		$where = "OI.supplier_id = $supplierId";
		$where .= " AND OI.pay_time >= {$params['payTimeStart']}";
		$where .= " AND OI.pay_time <= {$params['payTimeEnd']}";

		// 按order_sn查询
		if (isset($params['orderSn']) && $params['orderSn'])
		{
			$where .= " AND OI.order_sn='{$params['orderSn']}'";
		}

		// 查订单字段
		if (isset($params['mobile']) && $params['mobile'])
		{
			$where .= " AND OI.mobile='{$params['mobile']}'";
		}

		if (isset($params['queryType']))
		{
			// 1已发货 2已签收 3已完成 4已退款 5.待发货
			$type_map = array(
					1 => CS_SHIPPED,
					2 => CS_RECEIVED,
					3 => CS_FINISHED,
					4 => CS_PAY_RETURNED,
					5 => 1,
					);
			if (!isset($type_map[$params['queryType']]))
			{
				$this->error('错误的参数值：queryType='.$params['queryType']);
			}
			if ($params['queryType'] != 5)
			{
				$where .= $cls_order->order_status_sql($type_map[$params["queryType"]], "OI.");
			}
			else
			{
				// 待发货订单包括已审核、已分单、部分分单、部分发货、已发货（有发货单国内段未发货的）、部分签收（有发货单还未发货的）的情况
				$where .= ' AND (
						OI.order_status = 1
						OR OI.order_status = 6
						OR (OI.order_status = 5 AND OI.shipping_status IN (0,1,4,8))
						)';

			}
		}

		// 订单商品表字段
		if (isset($params['productSn']) && $params['productSn'])
		{
			$where .= " AND OG.product_sn = '{$params['productSn']}'";
		}

		if (isset($params['keyWords']) && $params['keyWords'])
		{
			$where .= " AND OG.goods_name LIKE '%{$params['keyWords']}%'";
		}

		// 先查订单信息
		$this->log("============== export order =============");
		$time0 = -microtime(true);
		$this->log("time0: ". $time0);
		$order = $cls_order->getOrderExport($where);
		$order = $order['data'];
		$this->log($cls_order->getLastSql());
		$time1 = $time0 + microtime(true);
		$this->log('order query time: '. $time1);
		if (empty($order))
		{
			$this->success(array(), 0, "查找不到相应的订单");
		}
		//dump($cls_order->getLastSql());
		//dump($order);
		//die;

		// 再查发货单
		$where = "1";
		$shipping_part = 0;
		if (isset($params['shippingPart']))
		{
			// 国际未发货
			if($params['shippingPart'] == 1){
				$shipping_part = 1;
				$where .= " AND D.wms_type = 2 AND D.completed = 0 AND D.status = 2";
			}
			// 国内未发货
			if($params['shippingPart'] == 2){
				$shipping_part = 2;
				$where .= " AND D.wms_type = 2 AND D.completed = 0 AND D.status = 0";
			}
		}
		$this->log("============== export delivery  =============");
		$time0 = -microtime(true);
		$this->log("time0: ". $time0);
		$fields = "D.order_id, D.delivery_sn, D.delivery_id, D.shipping_name, D.invoice_no, D.wms_type, D.status, DG.product_id, DG.send_number, D.completed";
		$delivery = cls_delivery::getInstance()->getDeliveryJoinGoods(array('order_id'=>array_column($order, 'order_id')), $fields, $where)['data'];
		$this->log(cls_delivery::getInstance()->getLastSql());
		$time1 = $time0 + microtime(true);
		$this->log('delivery query time: '. $time1);
		//$this->log(json_encode($delivery));
		//dump($delivery);
		//die;

		$wms_info = array();
		if ($delivery)
		{
			$this->log("============== export wms_info  =============");
			$time0 = -microtime(true);
			$this->log("time0: ". $time0);
			$wms_info = cls_delivery::getInstance()->getWmsInfoByDeliveryIds(array_column($delivery, 'delivery_id'), "*", 'delivery_id')['data'];
			$this->log(cls_delivery::getInstance()->getLastSql());
			$time1 = $time0 + microtime(true);
			$this->log('wms_info query time: '. $time1);
			//$this->log(json_encode($wms_info));
			//dump(cls_delivery::getInstance()->getLastSql());
		}
		//dump($wms_info);

		// 处理数据
		$this->log("============== handling data  =============");
		$time0 = -microtime(true);
		$this->log("time0: ". $time0);
		$data = array();
		$region_list = get_all_regions();
		foreach ($order as $v)
		{
			// 发货单
			$v['deliverySn'] = "";
			$v['shippingNameOut'] = "";
			$v['shippingNameIn'] = "";
			$v['invoiceNoOut'] = "";
			$v['invoiceNoIn'] = "";
			$v['delivery_status'] = 999;

			// 时间
			$v['add_time'] = date('Y-m-d H:i:s', $v['add_time']);
			$v['create_time'] = date('Y-m-d H:i:s', $v['create_time']);
			$v['pay_time'] = $v['pay_time'] ? date('Y-m-d H:i:s', $v['pay_time']) : "";
			$v['shipping_time'] = $v['shipping_time'] ? date('Y-m-d H:i:s', $v['shipping_time']) : "";
			$v['receive_time'] = $v['receive_time'] ? date('Y-m-d H:i:s', $v['receive_time']) : "";
			$v['finish_time'] = $v['finish_time'] ? date('Y-m-d H:i:s', $v['finish_time']) : "";
			$v['refund_time'] = $v['refund_time'] ? date('Y-m-d H:i:s', $v['refund_time']) : "";

			// 身份证
			$v['idCard'] = $v['identit_card'] ? $v['identit_card'] : "";

			// 状态转换成中文前保存到变量里
			$order_status = $v['order_status'];
			$shipping_status = $v['shipping_status'];
			// 状态转换成中文
			$status = $this->statusName($v);
			$v['order_status'] = $status['order_status'];
			$v['pay_status'] = $status['pay_status'];
			$v['shipping_status'] = ($v['delivery_status'] == 999) ? $status['shipping_status'] : $status['delivery_status'];

			// 省市区
			if ($v['address_type'] == 1)
			{
				// 微信地址
				$v['province'] = get_wx_addr_name($v['province']);
				$v['city'] = get_wx_addr_name($v['city']);
				$v['district'] = get_wx_addr_name($v['district']);
			}
			else
			{
				//$region_ids = array();
				//!empty($v['country']) && $region_ids[] = $v['country'];
				//!empty($v['province']) && $region_ids[] = $v['province'];
				//!empty($v['city']) && $region_ids[] = $v['city'];
				//!empty($v['district']) && $region_ids[] = $v['district'];
				//$region_list = get_region_by_ids($region_ids);
				$v['province'] = isset($region_list[$v['province']]) ? $region_list[$v['province']]['region_name'] : "";
				$v['city'] = isset($region_list[$v['city']]) ? $region_list[$v['city']]['region_name'] : "";
				$v['district'] = isset($region_list[$v['district']]) ? $region_list[$v['district']]['region_name'] : "";
			}

			if ($shipping_status == 0)
			{
				$v['shipping_name'] = "";
			}
			// 已审核状态的，是没有发货单信息的，不用继续走了
			if ($order_status == 1 && $shipping_status == 0)
			{
				// 海外直邮查国内段未发货的，不包括国际段未发货的(已审核状态为国际段未发货)
				if ($shipping_part != 2)
				{
					$data[] = $v;
				}
				continue;
			}

			foreach ($delivery as $d)
			{
				if ($v['order_id'] == $d['order_id'] && $v['product_id'] == $d['product_id'])
				{
					// 查询待发货的, 发货单completed == 1的不算
					if (isset($params['queryType']) && $params['queryType'] == 5 && $d['completed'] == 1)
					{
						continue;
					}



					$v['deliverySn'] = $d['delivery_sn'];
					if ($d['wms_type'] == 1)
					{
						$v['shippingNameOut'] = "";
						$v['shippingNameIn'] = $d['invoice_no'] ? $d['shipping_name'] : "";
						$v['invoiceNoOut'] = "";
						$v['invoiceNoIn'] = $d['invoice_no'] ? $d['invoice_no'] : "";
						$v['delivery_status'] = $d['status'];
					}
					else
					{
						$delivery_id = $d['delivery_id'];
						$v['shippingNameOut'] =  $d['invoice_no'] ? $d['shipping_name'] : "";
						$v['shippingNameIn'] = isset($wms_info[$delivery_id]) ? $wms_info[$delivery_id]['shipping_name'] : "";
						$v['invoiceNoOut'] = $d['invoice_no'] ? $d['invoice_no'] : "";
						$v['invoiceNoIn'] = isset($wms_info[$delivery_id]) ? $wms_info[$delivery_id]['invoice_no'] : "";
						$v['delivery_status'] = $d['status'];
					}

					// goods_number 变成发货单商品发货数量
					$v['goods_number'] = $d['send_number'];

					$data[] = $v;
				}
			}

			unset($v['delivery_status']);
		}
		//unset($v);
		$time1 = $time0 + microtime(true);
		$this->log('handle data time: '. $time1);

		//$this->log('export data: '.json_encode($data));
		$this->log("============== handling fields  =============");
		$time0 = -microtime(true);
		$this->log("time0: ". $time0);
		$data = $this->mapFields($data);
		$time1 = $time0 + microtime(true);
		$this->log('handle fields time: '. $time1);
		$this->success($data);
		//dump($order);
	}

	/**
	 *
	 *
	 * @return
	 * @create 2015-11-03 00:03:14
	 * @author veapon(veapon88@gmail.com)
	 * @wiki http://wiki.corp.mama.cn/pages/viewpage.action?pageId=65079008
	 */
	public function updateAddress()
	{
		$params = json_decode(stripslashes($this->input('data')), true);
		$cls_order = cls_order::getInstance();

		$input = array();
		// 必传字段
		$required_fields = array('orderId', 'consignee');
		foreach ($required_fields as $v)
		{
			if (!isset($params[$v]) || empty($params[$v]))
			{
				$this->error("参数错误: `$v` 不能为空", 400);
			}
			else
			{
				$input[$v] = addslashes($params[$v]);
			}
		}

		// 非必传字段
		$required_fields = array('province', 'city', 'district', 'address');
		foreach ($required_fields as $v)
		{
			if (isset($params[$v]))
			{
				$input[$v] = addslashes($params[$v]);
			}
			else
			{
				$input[$v] = "";
			}
		}

		$order_id = intval($input['orderId']);
		unset($input['order_id']);

		if ((!isset($params['tel']) || empty($params['tel']))
				&& (!isset($params['mobile']) || empty($params['mobile'])))
		{
			$this->error("电话号码和手机号码必须填一个", 400);
		}

		if (isset($params['tel']))
		{
			$input['tel'] = $params['tel'];
		}

		if (isset($params['mobile']))
		{
			$input['mobile'] = $params['mobile'];
		}

		if (isset($params['zipcode']))
		{
			$input['zipcode'] = $params['zipcode'];
		}

		$result_order = $cls_order->getOrderById($order_id);
		if (!isset($result_order['data']) || empty($result_order['data']))
		{
			$this->error('找不到相应订单', -1);
		}
		$old_order = $result_order['data'];
		if ($cls_order->updateStatus(array($order_id), $input))
		{
			// 日志
			// 将省市区id转换成名称
			$region_ids = array();
			!empty($old_order['province']) && $region_ids[] = $old_order['province'];
			!empty($old_order['city']) && $region_ids[] = $old_order['city'];
			!empty($old_order['district']) && $region_ids[] = $old_order['district'];
			$region_list = get_region_by_ids($region_ids);
			$old_region = isset($region_list[$old_order['province']]) ? $region_list[$old_order['province']]['region_name'] : '';
			$old_region .= isset($region_list[$old_order['city']]) ? ' ' . $region_list[$old_order['city']]['region_name'] : '';
			$old_region .= isset($region_list[$old_order['district']]) ? ' ' . $region_list[$old_order['district']]['region_name'] : '';

			$region_ids = array();
			!empty($input['province']) && $region_ids[] = $input['province'];
			!empty($input['city']) && $region_ids[] = $input['city'];
			!empty($input['district']) && $region_ids[] = $input['district'];
			$region_list = get_region_by_ids($region_ids);
			$new_region = $input['province'] && isset($region_list[$input['province']]) ? $region_list[$input['province']]['region_name'] : '';
			$new_region .= $input['city'] && isset($region_list[$input['city']]) ? ' ' . $region_list[$input['city']]['region_name'] : '';
			$new_region .= $input['district'] && isset($region_list[$input['district']]) ? ' ' . $region_list[$input['district']]['region_name'] : '';
			//$new_region .= isset($region_list[$input['country']]) ? ' ' . $region_list[$input['country']]['region_name'] : '';

			$action_note = "修改收货人信息：将 {$old_order['consignee']} {$old_order['mobile']} 【{$old_region}】{$old_order['address']} 改成 {$input['consignee']} {$input['mobile']} 【{$new_region}】{$input['address']}";
			//order_action($old_order['order_sn'], $old_order['order_status'], $old_order['shipping_status'], $old_order['pay_status'], $action_note, '系统');
			$cls_order->addOrderAction(array('action_note'=>$action_note, 'action_user'=>'系统', 'order_id'=>$order_id));

			$this->success(array(), 0, '更新成功');
		}
		else
		{
			$this->error('更新失败', -1);
		}
	}

	/* 获取支付宝支付二维码 */
	public function alipayQr(){
		$user_id = $this->input('user_id', 0);
		$order_id = $this->input('order_id', 0);
		$other = array();
		$order = array();
		$order['order_id'] = $order_id;
		$order['order_sn'] = $order_id;

		$sql = "SELECT *  FROM " . $GLOBALS['ecs']->table("order_info") . " WHERE `parent_order_id` = " . $order_id;
		$parent_order_id = $GLOBALS['db']->getAll($sql);
		if(!empty($parent_order_id)){
			$all_order_amount = 0;
			foreach($parent_order_id as $v){
				$all_order_amount += $v['order_amount'];
			}
			$order['order_amount'] = $all_order_amount;
		}else{
			$order['order_amount'] = $GLOBALS['db']->getOne("SELECT order_amount  FROM ". $GLOBALS['ecs']->table("order_info") ." WHERE `order_id` = ".$order_id);
			$order['order_sn'] = $GLOBALS['db']->getOne("SELECT order_sn  FROM ". $GLOBALS['ecs']->table("order_info") ." WHERE `order_id` = ".$order_id);
		}

		// 修改支付方式
		$GLOBALS['db']->query("UPDATE " . $GLOBALS['ecs']->table("order_info") . " SET pay_id = 2, pay_name = 'alipay', pay_code = '' WHERE order_id = '$order_id'");

		require_once(ROOT_PATH . 'includes/modules/payment/alipay.php');
		$new_class = 'alipay';
		$payment = new $new_class();
		$result = $payment->qrpay($order, $other);
		if ($result) {
			$this->success($result);
		} else {
			$this->error('生成支付二维码失败');
		}
	}

	/* 获取微信支付二维码 */
	public function wxpayQr(){
		$user_id = $this->input('user_id', 0);
		$order_id = $this->input('order_id', 0);
		$other = array();
		$order = array();
		$order['order_id'] = $order_id;
		$order['order_sn'] = $order_id;

		$sql = "SELECT *  FROM " . $GLOBALS['ecs']->table("order_info") . " WHERE `parent_order_id` = " . $order_id;
		$parent_order_id = $GLOBALS['db']->getAll($sql);
		if(!empty($parent_order_id)){
			$all_order_amount = 0;
			foreach($parent_order_id as $v){
				$all_order_amount += $v['order_amount'];
			}
			$order['order_amount'] = $all_order_amount;
		}else{
			$order['order_amount'] = $GLOBALS['db']->getOne("SELECT order_amount  FROM ". $GLOBALS['ecs']->table("order_info") ." WHERE `order_id` = ".$order_id);
			$order['order_sn'] = $GLOBALS['db']->getOne("SELECT order_sn  FROM ". $GLOBALS['ecs']->table("order_info") ." WHERE `order_id` = ".$order_id);
		}

		// 修改支付方式
		$GLOBALS['db']->query("UPDATE " . $GLOBALS['ecs']->table("order_info") . " SET pay_id = 5, pay_name = 'weixin', pay_code = 'NATIVE' WHERE order_id = '$order_id'");

		$resultb = array();
		try {
			require_once(ROOT_PATH . 'includes/modules/payment/weixin.php');
			$new_class = 'weixin';
			$paymenta = new $new_class();
			$resulta = $paymenta->prepay($order,'NATIVE',$other);
			if($resulta){
				$resultb['payment'] = $resulta;
				$resultb['payment']['payment_name'] = 'weixin';
			}
			$this->success($resultb);

		} catch (Exception $e) {
			print_r($e);
		};
	}

	/* ajax定时请求订单状态 */
	public function checkPayStatus(){
		$user_id = $this->input('user_id', 0);
		$order_id = $this->input('order_id', 0);

		$sql = "SELECT pay_status, add_time FROM " . $GLOBALS['ecs']->table("order_info") . " WHERE `order_id` = " . $order_id;
		$order_info = $GLOBALS['db']->getRow($sql);
		if (gmtime() - $order_info['add_time'] >= 1800 && $order_info['pay_status'] != 2) {
			update_order($order_id, array('order_status' => 2, 'to_buyer' => '订单付款超时自动取消'));//取消订单
			change_order_goods_storage($order_id, false, 1);//恢复库存
			$this->error('30分钟之内未付款成功，订单已取消');
		} else {
			if ($order_info['pay_status'] == 2) {
				$message = '支付成功，正在跳转...';
			} else {
				$message = '等待支付...';
			}
			$this->success(array('pay_status' => $order_info['pay_status']), 200, $message);
		}
	}

	/**
	 * 返回订单未出售的设计商品
	 */
	public function getOffsaleOrderGoods() {
		$order_id = $this->input('order_id', 0);
		$cls_order = cls_order::getInstance();
		$result = $cls_order->get_Offsale_Order_Goods($order_id);
		$this->success($result);
	}


	//订单列表
	public function getOrderList(){
		$user_id = $this->input('user_id',0);
		if(!$user_id){
			$this->error('请先登录！');exit;
		}
		$page = $this->input('page',1);
		$page_size = $this->input('page_size',8);
		$start = ($page-1)*$page_size;
		$screen = $this->input('screen',1);//筛选时间
		$where = "";
		if($screen != 1){
			$where = " AND add_time >= $screen ";
		}

		/*$order_sql = "SELECT oi.order_id as id,oi.order_sn as order_sn,og.goods_name as goods_name,oi.add_time as add_time,og.goods_sn as goods_sn,og.goods_number as goods_number,og.goods_price as goods_price,oi.order_status as order_status FROM ".$GLOBALS['ecs']->table('order_info')." oi LEFT JOIN ".$GLOBALS['ecs']->table('order_goods')." og ON oi.order_id = og.order_id WHERE ".
	 			"user_id = $user_id group by oi.order_id order by oi.add_time";*/
		//db_create_in
		$order_sql = "SELECT order_id,order_sn,add_time,order_status,pay_status,shipping_fee FROM ".$GLOBALS['ecs']->table('order_info')." WHERE ".
	 			"user_id = 159 $where order by add_time DESC LIMIT $start,$page_size";
		$order_data = $GLOBALS['db']->getAll($order_sql);
		$count = $GLOBALS['db']->getOne("SELECT COUNT(`order_id`) FROM ".$GLOBALS['ecs']->table('order_info')." WHERE  user_id = 159 $where");
		$pege_count = ceil($count/$page_size);
		foreach($order_data as $key=>$value){

			$sql = 'SELECT og.goods_name as goods_name,og.goods_sn,sum(og.goods_number) as total,og.goods_attr as goods_attr,og.goods_attr_id,g.goods_thumb,g.goods_sn,g.goods_id as goods_id FROM '.$GLOBALS['ecs']->table('order_goods').'og LEFT JOIN '.$GLOBALS['ecs']->table('goods').' g ON g.goods_id = og.goods_id WHERE og.order_id = '.$value['order_id'] .' group by goods_id,goods_attr' ;
			$order_data[$key]['goods_info'] = $GLOBALS['db']->getAll($sql);
			$total = "";
			//var_dump($order_data[$key]['goods_info']);exit;
			foreach($order_data[$key]['goods_info'] as $k=>$v){
				$id_str = db_create_in($v['goods_attr_id']);
				$attr = $GLOBALS['db']->getAll('SELECT * FROM '.$GLOBALS['ecs']->table('goods_attr').' WHERE goods_attr_id '.$id_str .' group by goods_attr_id' );

				//款式图
				$icon_sql = "SELECT `default_icon` FROM ".$GLOBALS['ecs']->table('attribute_icon')." WHERE attr_id = ".$attr[0]["attr_id"]." AND attr_value_name = '".$attr[0]["attr_value"]."'";
				$order_data[$key]['goods_info'][$k]['attr_icon'] = $GLOBALS['db']->getOne($icon_sql);
				//颜色编码
				$color_sql = "SELECT `color_code` FROM " . $GLOBALS['ecs']->table('attribute_color') ." WHERE attr_id = ".$attr[1]["attr_id"]." AND color_name = '".$attr[1]["attr_value"]."'";
				$order_data[$key]['goods_info'][$k]['attr_color'] = $GLOBALS['db']->getOne($color_sql);
				//获取尺码
				$order_data[$key]['goods_info'][$k]['attr_size'] = $GLOBALS['db']->getOne("SELECT attr_value FROM " .$GLOBALS['ecs']->table('goods_attr'). " WHERE goods_attr_id = ".$attr[2]['goods_attr_id']." AND goods_id =" .$v['goods_id']);

				//单价
				$order_data[$key]['goods_info'][$k]['unit_price'] = $this->cart->get_final_price_api($v['goods_id']);


				//价格get_final_price_api
				$order_data[$key]['price']+=$this->cart->get_final_price_api($v['goods_id'],$v['total']);

			}

		}

		//数据
		$data['order_data'] = $order_data;

		//分页
		$data['pager']['page']=$page;
		$data['pager']['count']=$count;
		$data['pager']['page_size']=$page_size;

		$this->success($data);
	}

}
