<?php
/**
 * 订单类
 *
 * @version v1.0
 * @create 2016-11-21
 * @author Jam Cheng
 */

if (!defined('IN_ECS'))
{
	die('Hacking attempt');
}



require_once(ROOT_PATH.'includes/lib_order.php');
/* 载入语言文件 */

class cls_order{

	protected $_db                = null;
	protected $_tb_user          = null;
	protected static $_instance   = null;
	public static $_errno = array(
		1 => '操作成功',
		2 => '参数错误',
		3 => '会员不存在',
	);

	function __construct()
	{



		$this->_db = $GLOBALS['db'];
//		$this->_cfg = $GLOBALS['cfg'];

		$this->_tb_user          = $GLOBALS['ecs']->table('users');
		$this->_tb_order_info    = $GLOBALS['ecs']->table('order_info');
		$this->_tb_user_rank     = $GLOBALS['ecs']->table('user_rank');
		$this->_tb_user_bonus    = $GLOBALS['ecs']->table('user_bonus');
		$this->_tb_bonus_type    = $GLOBALS['ecs']->table('bonus_type');
		$this->_tb_collect_goods = $GLOBALS['ecs']->table('collect_goods');
		$this->_tb_goods         = $GLOBALS['ecs']->table('goods');
		$this->_tb_member_price  = $GLOBALS['ecs']->table('member_price');
		$this->_tb_account_log   = $GLOBALS['ecs']->table('account_log');
		$this->_tb_delivery_order = $GLOBALS['ecs']->table('delivery_order');
		$this->_tb_payment       = $GLOBALS['ecs']->table('payment');
		$this->_tb_shop_config   = $GLOBALS['ecs']->table('shop_config');
		$this->_tb_back_order    = $GLOBALS['ecs']->table('back_order');
		$this->_tb_supplier_shop_config   = $GLOBALS['ecs']->table('supplier_shop_config');
		$this->_tb_order_goods   = $GLOBALS['ecs']->table('order_goods');
		$this->_tb_products      = $GLOBALS['ecs']->table('products');
		$this->_tb_goods         = $GLOBALS['ecs']->table('goods');
		$this->_tb_brand         = $GLOBALS['ecs']->table('brand');
		$this->_tb_goods_attr    = $GLOBALS['ecs']->table('goods_attr');
		$this->_tb_back_goods    = $GLOBALS['ecs']->table('back_goods');
		$this->_tb_shaidan       = $GLOBALS['ecs']->table('shaidan');
		$this->_tb_supplier_shop_config       = $GLOBALS['ecs']->table('supplier_shop_config');
		$this->_tb_goods_tag     = $GLOBALS['ecs']->table('goods_tag');
		$this->_tb_comment       = $GLOBALS['ecs']->table('comment');
		$this->_tb_shop_grade       = $GLOBALS['ecs']->table('shop_grade');
		$this->_now_time         = time();
		$this->_mc_time			 = 3600;
		$this->_plan_time 		 = 3600*24*15;
		$this->return_data = array(
			'code' => 500,
			'message' => '',
			'data' => array(),
		);
	}

	public static function getInstance()
	{
		if (self::$_instance === null)
		{
			$instance = new self;
			self::$_instance = $instance;
		}
		return self::$_instance ;
	}

	/**
	 *  获取用户指定范围的订单列表
	 *
	 * @access  public
	 * @param   int         $user_id        用户ID号
	 * @param   int         $num            列表最大数量
	 * @param   int         $start          列表起始位置
	 * @return  array       $order_list     订单列表
	 */
	function get_user_orders_1($user_id, $num = 10, $start = 0,$where='')
	{
		/* 取得订单列表 */
		$arr    = array();

		$sql = "SELECT o.*, ifnull(ssc.value,'网站自营') as shopname, " .
			"(goods_amount + shipping_fee + insure_fee + pay_fee + pack_fee + card_fee + tax - discount) AS total_fee ".
			" FROM " .$this->_tb_order_info . ' as o '.
			" LEFT JOIN " .$this->_tb_supplier_shop_config . 'as ssc '.
			" ON o.supplier_id=ssc.supplier_id AND ssc.code='shop_name' ".
			" WHERE user_id = '$user_id' $where ORDER BY add_time DESC";

		$res = $this->_db->SelectLimit($sql, $num, $start);

		// 查询退款单退货单信息，对比订单状态，更新订单状态综合显示
		// back_type 1 退货 4 退款
		$sql = "SELECT back_id, order_sn, order_id, status_back, back_type, status_refund, add_time ".
			" FROM " .$this->_tb_back_order .
			" WHERE user_id = '$user_id' ORDER BY back_id DESC";

		$back_order = $this->_db->getAll($sql);
		foreach($back_order as $back_k => $back_v){
			if(isset($back_order[$back_v['order_id']])){
				continue;
			}
			$back_order[$back_v['order_id']] = $back_v;
		}

		while ($row = $this->_db->fetchRow($res))
		{

			$row['can_do'] = CAN_NOT;//0 无操作
			if ($row['order_status'] == OS_UNCONFIRMED)
			{
				// 未确认状态可以取消
				$row['can_do'] = CAN_CANCEL;//2 可取消
				$row['handler'] = "<a href=\"user.php?act=cancel_order&order_id=" .$row['order_id']. "\" onclick=\"if (!confirm('".$GLOBALS['_LANG']['confirm_cancel']."')) return false;\">".$GLOBALS['_LANG']['cancel']."</a>";
			}
			else if ($row['order_status'] == OS_SPLITED)
			{
				// 已发货可确认收货
				/* 对配送状态的处理 */
				if ($row['shipping_status'] == SS_SHIPPED)
				{
					$back_num = $this->_db->getOne("SELECT COUNT(*) FROM " . $this->_tb_back_order . " WHERE order_id = " . $row['order_id'] . " AND status_back < 6 AND status_back != 3");
					if ($back_num > 0)
					{
						$back_info = "此单存在正在退货商品，确认收货退货申请将取消。";
					}
					else
					{
						$back_info = "";
					}
					@$okgoods_time = $this->_db->getOne("select value from " . $this->_tb_shop_config  . " where code='okgoods_time'");
					@$row_time = $okgoods_time - (local_date('d',gmtime()) - local_date('d',$row['shipping_time']));

					$row['can_do'] = CAN_RECEIVED;//5 可确认收货
					@$row['handler'] = "<div class='clearfix'><i class='endtime-icon fl'></i><em class='endtime-text fl'>还剩" . $row_time . "天自动收货</em></div><a href=\"user.php?act=affirm_received&order_id=" .$row['order_id']. "\" onclick=\"if (!confirm('".$back_info.$GLOBALS['_LANG']['confirm_received']."')) return false;\" style='display:inline-block;background:#E31939;color:#fff;padding:3px 5px ;margin:3px 0px;'>".$GLOBALS['_LANG']['received']."</a>";
				}
				elseif ($row['shipping_status'] == SS_RECEIVED)
				{
					@$row['handler'] = '<span style="color:red">'.$GLOBALS['_LANG']['ss_received'] .'</span>';
				}
				else
				{
					if ($row['pay_status'] == PS_UNPAYED)
					{
						// 未付款可支付
						$row['can_do'] = CAN_PAY;//1 可支付
						@$row['handler'] = "<a href=\"user.php?act=order_detail&order_id=" .$row['order_id']. '">' .$GLOBALS['_LANG']['pay_money']. '</a>';
					}
					else
					{
						@$row['handler'] = "<a href=\"user.php?act=order_detail&order_id=" .$row['order_id']. '">' .$GLOBALS['_LANG']['view_order']. '</a>';
					}

				}
			}
			else
			{
				$row['handler'] = '<span style="color:red">'.$GLOBALS['_LANG']['os'][$row['order_status']] .'</span>';
			}

//			$row['shipping_status'] = ($row['shipping_status'] == SS_SHIPPED_ING) ? SS_PREPARING : $row['shipping_status'];
			$row['order_status_text'] = $GLOBALS['_LANG']['os'][$row['order_status']] . ',' . $GLOBALS['_LANG']['ps'][$row['pay_status']] . ',' . $GLOBALS['_LANG']['ss'][$row['shipping_status']];


			$sql_invoices = "SELECT invoice_no,shipping_name FROM ".$this->_tb_delivery_order." WHERE order_id = ".$row['order_id']." AND status = 0";
			$row['invoices'] = $this->_db->getAll($sql_invoices);

			$cod_code = $this->_db->getOne("select pay_code from " . $this->_tb_payment . " where pay_id=" . $row['pay_id']);
			$weixiu_time = $this->_db->getOne("select value from " . $this->_tb_shop_config  . " where code='weixiu_time'");
			$row['weixiu_time'] = ($weixiu_time - (local_date('d',gmtime()) - local_date('d',$row['shipping_time_end'])) <= 0) ? 0 : 1;





			$back_can_a = 1;
			$comment_s = 0;
			$shaidan_s = 0;
			$goods_list_r = $this->get_order_goods($row);
			foreach($goods_list_r as $g_key => $g_val)
			{
				$goods_list_r[$g_key]['can_goods_do'] = CAN_NOT;//0 无操作
				if ($g_val['back_can'] == 0)
				{
					$back_can_a = 0;
				}
				if ($g_val['comment_state'] == 0 && $g_val['is_back'] == 0 && $comment_s == 0)
				{
					if($row['order_status'] == 5 && $row['pay_status'] == 2 && $row['shipping_status'] == 2){
						$row['can_do'] = CAN_COMMENT;//6 可评论
					}

					$comment_s = $g_val['rec_id'];
				}
				if ($g_val['shaidan_state'] == 0 && $g_val['is_back'] == 0 && $shaidan_s == 0)
				{
					if($row['order_status'] == 5 && $row['pay_status'] == 2 && $row['shipping_status'] == 2){
						$row['can_do'] = CAN_COMMENT;//6 可评论
					}

					$shaidan_s = $g_val['rec_id'];
				}
			}

			$back_info_num = "SELECT COUNT(*) FROM " . $this->_tb_back_order .
				" WHERE order_id = " . $row['order_id'] . " AND status_back < 6";
			if ($this->_db->getOne($back_info_num) > 0)
			{
				$row['back_can'] = 0;
			}
			else
			{
				if (($row['shipping_status'] == 0 || $row['shipping_status'] == 3) && $row['pay_status'] == 2){
					$goods_list_r[$g_key]['can_goods_do'] = CAN_REFUND;//3 可退款
//					$row['can_do_goods'] = CAN_REFUND;
				}
				if($row['shipping_status'] == 1){
					$goods_list_r[$g_key]['can_goods_do'] = CAN_RETURN;//4 可退货
//					$row['can_do_goods'] = CAN_RETURN;
				}
				$row['back_can'] = 1;
			}

			if ($row['pay_status'] == 0)
			{
				$row['can_do'] = CAN_PAY;
			}

			if ($row['order_status'] == 3)
			{
				$row['can_do'] = CAN_NOT;
			}

			$status_back = isset($back_order[$row['order_id']]['status_back']) ? $back_order[$row['order_id']]['status_back'] : -1;
			$status_refund = isset($back_order[$row['order_id']]['status_refund']) ? $back_order[$row['order_id']]['status_refund'] : 0;
			$back_type = isset($back_order[$row['order_id']]['back_type']) ? $back_order[$row['order_id']]['back_type'] : 0;

//			if($row['order_sn']=='2017010592827'){
				$row['composite_status'] = $this->get_composite_status($row['order_status'], $row['shipping_status'], $row['pay_status'], $status_back, $status_refund, $back_type);
//			}else{
//				$row['composite_status'] = '';
//			}
			$arr[$row['order_id']] = array('order_id'       => $row['order_id'],
				'order_sn'       => $row['order_sn'],
				'order_time'     => local_date($GLOBALS['_CFG']['time_format'], $row['add_time']),
//				'order_status'   => str_replace(',','</br>',$row['order_status']),
				'order_status_text'   => $row['order_status'], //聊天系统-订单状态
				'consignee'   	 => $row['consignee'], //聊天系统-收货人
				'pay_name'   	 => $row['pay_name'], //聊天系统-支付方式
				'back_can'       => (string)$row['back_can'],
//				'comment_s'      => $comment_s,
//				'shaidan_s'      => $shaidan_s,
				'total_fee'      => $row['total_fee'],
				'format_total_fee'      => price_format($row['total_fee'], false),
				'goods_list'     => $goods_list_r,
//						'pay_online'     => $row['pay_online'],
//				'is_suborder' => $row['parent_order_id'] ? "(子订单)" : "",


				'order_status'   => $row['order_status'],
				'shipping_status'=> $row['shipping_status'],
				'pay_status'     => $row['pay_status'],
				'can_do'     => $row['can_do'],

				'handler'        => $row['handler'],
				'shipping_id'    => $row['shipping_id'],
				'shipping_name'  => $row['shipping_name'],
				'composite_status'  => (string)$row['composite_status'],
//				'shipping_name_2'=> (strpos($row['shipping_name'],'同城快递') != FALSE ? "同城快递" : $row['shipping_name']),

				'pay_id'         => ($cod_code == 'cod' ? '' : $row['pay_id']),
				'invoice_no'     => $row['invoice_no'],
//				'extension_code'     => $row['extension_code'], // 用于前台辨识预售活动
//						'pre_sale_status'     => $pre_sale_status, // 用于前台辨识预售活动状态
//						'pre_sale_deposit'     => $pre_sale_deposit, // 定金
//						'pre_sale_deposit_format'     => $pre_sale_deposit_format, // 格式化定金
//				'invoices'    => $row['invoices'],
				'weixiu_time'    => (string)$row['weixiu_time']);
		}

		return $arr;
	}

	/**
	 *  获取用户指定范围的订单列表  edit by qinglin 2017.09.18
	 *
	 * @access  public
	 * @param   int         $user_id        用户ID号
	 * @param   int         $num            列表最大数量
	 * @param   int         $start          列表起始位置
	 * @return  array       $order_list     订单列表
	 */
	function get_user_orders_2($user_id, $num = 10, $start = 0,$where='')
	{
		/* 取得订单列表 */
		$arr    = array();

		$sql = "SELECT o.*, ifnull(ssc.value,'网站自营') as shopname, " .
			"(goods_amount + shipping_fee + insure_fee + pay_fee + pack_fee + card_fee + tax - discount - integral_money - bonus) AS total_fee ".
			" FROM " .$this->_tb_order_info . ' as o '.
			" LEFT JOIN " .$this->_tb_supplier_shop_config . 'as ssc '.
			" ON o.supplier_id=ssc.supplier_id AND ssc.code='shop_name' ".
			" WHERE user_id = '$user_id' $where ORDER BY add_time DESC";

		$res = $this->_db->SelectLimit($sql, $num, $start);

		// 查询退款单退货单信息，对比订单状态，更新订单状态综合显示
		// back_type 1 退货 4 退款
		$sql = "SELECT back_id, order_sn, order_id, status_back, back_type, status_refund, add_time ".
			" FROM " .$this->_tb_back_order .
			" WHERE user_id = '$user_id' ORDER BY back_id DESC";

		$back_order = $this->_db->getAll($sql);
		foreach($back_order as $back_k => $back_v){
			if(isset($back_order[$back_v['order_id']])){
				continue;
			}
			$back_order[$back_v['order_id']] = $back_v;
		}

		while ($row = $this->_db->fetchRow($res))
		{

			$row['can_do'] = CAN_NOT;//0 无操作
			if ($row['order_status'] == OS_UNCONFIRMED)
			{
				// 未确认状态可以取消
				$row['can_do'] = CAN_CANCEL;//2 可取消
				$row['handler'] = "<a href=\"user.php?act=cancel_order&order_id=" .$row['order_id']. "\" onclick=\"if (!confirm('".$GLOBALS['_LANG']['confirm_cancel']."')) return false;\">".$GLOBALS['_LANG']['cancel']."</a>";
			}
			else if ($row['order_status'] == OS_SPLITED)
			{
				// 已发货可确认收货
				/* 对配送状态的处理 */
				if ($row['shipping_status'] == SS_SHIPPED)
				{
					$back_num = $this->_db->getOne("SELECT COUNT(*) FROM " . $this->_tb_back_order . " WHERE order_id = " . $row['order_id'] . " AND status_back < 6 AND status_back != 3");
					if ($back_num > 0)
					{
						$back_info = "此单存在正在退货商品，确认收货退货申请将取消。";
					}
					else
					{
						$back_info = "";
					}
					@$okgoods_time = $this->_db->getOne("select value from " . $this->_tb_shop_config  . " where code='okgoods_time'");
					@$row_time = $okgoods_time - (local_date('d',gmtime()) - local_date('d',$row['shipping_time']));

					$row['can_do'] = CAN_RECEIVED;//5 可确认收货
					@$row['handler'] = "<div class='clearfix'><i class='endtime-icon fl'></i><em class='endtime-text fl'>还剩" . $row_time . "天自动收货</em></div><a href=\"user.php?act=affirm_received&order_id=" .$row['order_id']. "\" onclick=\"if (!confirm('".$back_info.$GLOBALS['_LANG']['confirm_received']."')) return false;\" style='display:inline-block;background:#E31939;color:#fff;padding:3px 5px ;margin:3px 0px;'>".$GLOBALS['_LANG']['received']."</a>";
				}
				elseif ($row['shipping_status'] == SS_RECEIVED)
				{
					@$row['handler'] = '<span style="color:red">'.$GLOBALS['_LANG']['ss_received'] .'</span>';
				}
				else
				{
					if ($row['pay_status'] == PS_UNPAYED)
					{
						// 未付款可支付
						$row['can_do'] = CAN_PAY;//1 可支付
						@$row['handler'] = "<a href=\"user.php?act=order_detail&order_id=" .$row['order_id']. '">' .$GLOBALS['_LANG']['pay_money']. '</a>';
					}
					else
					{
						@$row['handler'] = "<a href=\"user.php?act=order_detail&order_id=" .$row['order_id']. '">' .$GLOBALS['_LANG']['view_order']. '</a>';
					}

				}
			}
			else
			{
				$row['handler'] = '<span style="color:red">'.$GLOBALS['_LANG']['os'][$row['order_status']] .'</span>';
			}


			$row['order_status_text'] = $GLOBALS['_LANG']['os'][$row['order_status']] . ',' . $GLOBALS['_LANG']['ps'][$row['pay_status']] . ',' . $GLOBALS['_LANG']['ss'][$row['shipping_status']];


			$sql_invoices = "SELECT invoice_no,shipping_name FROM ".$this->_tb_delivery_order." WHERE order_id = ".$row['order_id']." AND status = 0";
			$row['invoices'] = $this->_db->getAll($sql_invoices);

			$cod_code = $this->_db->getOne("select pay_code from " . $this->_tb_payment . " where pay_id=" . $row['pay_id']);
			$weixiu_time = $this->_db->getOne("select value from " . $this->_tb_shop_config  . " where code='weixiu_time'");
			$row['weixiu_time'] = ($weixiu_time - (local_date('d',gmtime()) - local_date('d',$row['shipping_time_end'])) <= 0) ? 0 : 1;

			$back_can_a = 1;
			$comment_s = 0;
			$shaidan_s = 0;
			$shaidan_comment = 0;
			$goods_list_r = $this->get_order_goods($row);

            //评价
			$min_time = local_strtotime(local_date('Y-m-d H:i:s', strtotime('-'.$GLOBALS['_CFG']['comment_youxiaoqi'].' days')));//则自确认收货起$GLOBALS['_CFG']['comment_youxiaoqi']天内买家可以评价、晒单
	        if($row['shipping_time_end'] <= $min_time){
	        	$evaluate_ed=0;
	        }
	        else{
	        	$evaluate_ed=1;
	        }

			$goods_num = 0;//商品总数

			//定义
			$back_can = 0;//退款   0不可以   1可以
			$back_can_num = 0;
			$can_goods_do=0;//退货   0不可以   1可以
			$can_goods_do_num = 0;
			$can_evaluate=0; //评价 0不可以   1可以
			foreach($goods_list_r as $g_key => $g_val)
			{
				//过滤不需要的
				//unset($goods_list_r[$g_key]['product_id']);
				unset($goods_list_r[$g_key]['storage']);
				unset($goods_list_r[$g_key]['brand_name']);
				unset($goods_list_r[$g_key]['product_sn']);
				unset($goods_list_r[$g_key]['parent_id']);

				$goods_list_r[$g_key]['can_goods_do'] = 0;//0 无操作

				if($g_val['comment_state'] == 0  && $g_val['is_back'] == 0 && $row['order_status'] == 5 && $row['pay_status'] == 2 && $row['shipping_status'] == 2 && $evaluate_ed==1){
					$goods_list_r[$g_key]['can_evaluate']=1;//商品可评价
					$can_evaluate++;
				}
				elseif($g_val['comment_state']==1){
					$goods_list_r[$g_key]['can_evaluate']=2;//商品已评价
					$can_evaluate=0;
				}
				else{
					$goods_list_r[$g_key]['can_evaluate']=0;//商品不可评价
					$can_evaluate=0;
				}



				if($g_val['comment_state'] == 0 && $g_val['shaidan_state'] == 0 && $g_val['is_back'] == 0 && $shaidan_comment == 0){
					if($row['order_status'] == 5 && $row['pay_status'] == 2 && $row['shipping_status'] == 2){
						$row['can_do'] = CAN_COMMENT;//6 可评论
					}

					//$shaidan_comment = $g_val['rec_id'];
				}

				$goods_num += $g_val['goods_number'];

                //$min_time = local_strtotime(local_date('Y-m-d H:i:s', strtotime('-'.$GLOBALS['_CFG']['shouhou_time'].' days')));//则自确认收货起$GLOBALS['_CFG']['shouhou_time']天内买家可以退货
                $min_time = local_strtotime(local_date('Y-m-d H:i:s', strtotime('-7 days')));//则自确认收货起$GLOBALS['_CFG']['shouhou_time']天内买家可以退货 默认7天，还没做成后台设置
                //可进行申请售后的商品
		        if($row['order_status']==5 && $row['shipping_status']==2 && $row['pay_status']==2 && $row['shipping_time_end']>$min_time && $row['extension_code']!= 'pre_sale' && $row['extension_code']!= 'virtual_good' && $row['extension_code']!= 'service_goods' && $g_val['back_can']==1){
			    	$goods_list_r[$g_key]['can_goods_do'] = 1;
		            $can_goods_do_num++;
			    }
			    else{
			    	$goods_list_r[$g_key]['can_goods_do'] = 0;
			    	$can_goods_do_num=0;
			    }

                //可进行退款的商品

		        if(in_array($row['order_status'],array(1,5)) && in_array($row['shipping_status'],array(0,1,3,5))  && $row['pay_status']==2 && $g_val['back_can']==1){
			    	$goods_list_r[$g_key]['back_can'] = 1;
		            $back_can_num++;
			    }elseif($row['order_status']==5 && $row['shipping_status']==1  && $row['pay_status']==2 && $row['extension_code']!='service_goods'  && $g_val['back_can']==1){
			    	$goods_list_r[$g_key]['back_can'] = 1;
		            $back_can_num++;
			    }
		        else{
		        	$goods_list_r[$g_key]['back_can'] = 0;
		        	$back_can_num=0;
		        }



			}

			if($can_evaluate>0){
            	$can_evaluate=1;//可评价
            }
            $row['can_evaluate']=$can_evaluate;

            if($back_can_num>0){
            	$back_can=1;//可退款
            }
			$row['back_can']=$back_can;

			if($can_goods_do_num>0){
            	$can_goods_do=1;//可退款
            }
			$row['can_goods_do']=$can_goods_do;



			$sql_goods = "select bo.back_type from ". $GLOBALS['ecs']->table('back_order') . " as bo " .
		        " where bo.order_id='$row[order_id]'  " .
		        "  and bo.status_back < 6 order by bo.back_id desc";
		    $back_order =$GLOBALS['db']->getRow($sql_goods);

		    $bt = '';
		    if($back_order){
		        switch ($back_order['back_type'])
		        {
		            case '1' : $bt = "退货"; break;
		            case '3' : $bt = "申请维修"; break;
		            case '4' : $bt = "退款"; break;
		            default : break;
		        }
		    }

		    $shouhou = $bt;

            $back_type_name="";
		    if(in_array($row['order_status'],array(1,5)) && in_array($row['shipping_status'],array(0,1,3,5))  && $row['pay_status']==2 && $row['back_can']==0){
	            $back_type_name=$shouhou;
	        }
	        elseif($row['order_status']==5 && $row['shipping_status']==1  && $row['pay_status']==2 && $row['extension_code']!='service_goods'  && $row['back_can']==0){
		    	$back_type_name=$shouhou;
		    }
	        elseif($row['order_status']==5 && $row['shipping_status']==2 && $row['pay_status']==2 &&  $row['extension_code']!= 'pre_sale' && $row['extension_code']!= 'virtual_good' && $row['extension_code']!= 'service_goods' && $row['back_can']==0){
	            $back_type_name=$shouhou;
	        }

	        $row['back_type_name']=$back_type_name;

			if ($row['pay_status'] == 0)
			{
				$row['can_do'] = CAN_PAY;//1 可支付
			}

			if ($row['order_status'] == 3)
			{
				$row['can_do'] = CAN_NOT;//0 无操作
			}

			$status_back = isset($back_order[$row['order_id']]['status_back']) ? $back_order[$row['order_id']]['status_back'] : -1;
			$status_refund = isset($back_order[$row['order_id']]['status_refund']) ? $back_order[$row['order_id']]['status_refund'] : 0;
			$back_type = isset($back_order[$row['order_id']]['back_type']) ? $back_order[$row['order_id']]['back_type'] : 0;

			//			if($row['order_sn']=='2017010592827'){
				$row['composite_status'] = $this->get_composite_status($row['order_status'], $row['shipping_status'], $row['pay_status'], $status_back, $status_refund, $back_type);
			//			}else{
			//				$row['composite_status'] = '';
			//			}

			$row['status']= returnOrderStatus($row['order_status'],$row['pay_status'],$row['shipping_status']);


	        $status_name = returnOrderStatusName($row);

	        $row['flow_type']= returnOrderType($row['extension_code']);

	        $order_handle=returnOrderHandle($row); //订单操作

			if($row['status'] == 6){
				$row['can_do'] = 7;//用户可删除订单（前端不显示）
			}
			$arr[$row['order_id']] = array(
				'order_id'       => $row['order_id'],
				'order_sn'       => $row['order_sn'],
				'order_time'     => local_date($GLOBALS['_CFG']['time_format'], $row['add_time']),
				//'order_status'   => str_replace(',','</br>',$row['order_status']),
				////'order_status_text'   => $row['order_status'], //聊天系统-订单状态
				////'consignee'   	 => $row['consignee'], //聊天系统-收货人
				////'pay_name'   	 => $row['pay_name'], //聊天系统-支付方式
				'shopname'       => $row['shopname'],
				//'back_type_name'       => (string)$row['back_type_name'],
				'back_can'       => (string)$row['back_can'],
				'can_goods_do'       => (string)$row['can_goods_do'],
				'can_evaluate'       => (string)$row['can_evaluate'],
				//'comment_s'      => $comment_s,
				//'shaidan_s'      => $shaidan_s,
				'total_fee'      => $row['total_fee'],
				'format_total_fee'      => price_format($row['total_fee'], false),
				//'pay_online'     => $row['pay_online'],
				//'is_suborder' => $row['parent_order_id'] ? "(子订单)" : "",


				//'order_status'   => $row['order_status'],
				//'shipping_status'=> $row['shipping_status'],
				//'pay_status'     => $row['pay_status'],
				'status'     => $row['status'],
				'status_name'     => $status_name,
				'order_handle'     => $order_handle,//订单操作
				'flow_type'     => $row['flow_type'],

				'can_do'     => $row['can_do'],

				////'handler'        => $row['handler'],
				'shipping_id'    => $row['shipping_id'],
				'shipping_name'  => $row['shipping_name'],
				////'composite_status'  => (string)$row['composite_status'],
				//'shipping_name_2'=> (strpos($row['shipping_name'],'同城快递') != FALSE ? "同城快递" : $row['shipping_name']),

				'pay_id'         => ($cod_code == 'cod' ? '' : $row['pay_id']),
				'invoice_no'     => $row['invoice_no'],
				'extension_code'     => $row['extension_code'], // 用于前台辨识预售活动
				//						'pre_sale_status'     => $pre_sale_status, // 用于前台辨识预售活动状态
				//						'pre_sale_deposit'     => $pre_sale_deposit, // 定金
				//						'pre_sale_deposit_format'     => $pre_sale_deposit_format, // 格式化定金
				//				'invoices'    => $row['invoices'],
				////'weixiu_time'    => (string)$row['weixiu_time'],
				'goods_count'    => $goods_num,
				'goods_list'     => $goods_list_r
				);
		}

		return $arr;
	}



	/**
	 * 获取订单商品
	 *
	 * @param $order
	 * @return void;
	 */
	public function get_order_goods($order)
	{

		/* 取得订单商品及货品 */
		$goods_list = array();
		$goods_attr = array();
		$sql = "SELECT o.*, IF(o.product_id > 0, p.product_number, g.goods_number) AS storage, o.goods_attr, o.goods_attr_id, g.suppliers_id, IFNULL(b.brand_name, '') AS brand_name, p.product_sn, a.attr_value,g.goods_thumb,g.goods_id
            FROM " . $this->_tb_order_goods . " AS o
                LEFT JOIN " . $this->_tb_products . " AS p
                    ON p.product_id = o.product_id
                LEFT JOIN " . $this->_tb_goods . " AS g
                    ON o.goods_id = g.goods_id
                LEFT JOIN " . $this->_tb_brand . " AS b
                    ON g.brand_id = b.brand_id
				LEFT JOIN " . $this->_tb_goods_attr . " AS a
                    ON o.goods_attr_id = a.goods_attr_id
            WHERE o.order_id = '$order[order_id]'";
		$res = $this->_db->query($sql);
		while ($row = $this->_db->fetchRow($res))
		{
			/* 虚拟商品支持 */
//			if ($row['is_real'] == 0)
//			{
//				/* 取得语言项 */
//				$filename = ROOT_PATH . 'plugins/' . $row['extension_code'] . '/languages/common_' . $_CFG['lang'] . '.php';
//				if (file_exists($filename))
//				{
//					include_once($filename);
//					if (!empty($_LANG[$row['extension_code'].'_link']))
//					{
//						$row['goods_name'] = $row['goods_name'] . sprintf($_LANG[$row['extension_code'].'_link'], $row['goods_id'], $order['order_sn']);
//					}
//				}
//			}

			$row['formated_subtotal']       = price_format($row['goods_price'] * $row['goods_number']);
			$row['formated_goods_price']    = price_format($row['goods_price']);
			$row['subtotal']    = (string)($row['goods_price'] * $row['goods_number']);
			//$row['formated_goods_price']    = (string)$row['goods_price'];
//			$row['url'] = build_uri('goods', array('gid' => $row['goods_id']), $row['goods_name']);
//			$row['thumb'] = get_image_path($row['goods_id'], $row['goods_thumb'],true);

			$row['goods_attr'] = preg_replace("/\[.*\]/", '', $row['goods_attr']);//属性处理，去掉中括号及里面的内容。如：颜色:粉色[798] 尺码:S[798] 变为 颜色:粉色 尺码:S
			$goods_attr[] = explode(' ', trim($row['goods_attr'])); //将商品属性拆分为一个数组

			if ($row['extension_code'] == 'package_buy')
			{
				$row['goods_id'] = 0;
				$row['goods_thumb'] = 'mobile/themesmobile/default/images/flow/libao.png';
				$row['storage'] = '';
				$row['brand_name'] = '';
				$row['package_goods_list'] = get_package_goods($row['goods_id']);
			}

			unset($row['suppliers_id']);
			unset($row['exclusive']);
			unset($row['promote_price']);
			unset($row['cost_price']);
			unset($row['package_attr_id']);
			unset($row['is_gift']);
			unset($row['extension_code']);
			//unset($row['is_real']);
			unset($row['split_money']);
			$row['product_sn'] = !empty($row['product_sn']) ? (string)$row['product_sn'] : '';
			$row['attr_value'] = !empty($row['attr_value']) ? (string)$row['attr_value'] : '';

			$goods_list[] = $row;
		}

		foreach ($goods_list as $goods_key => $goods_val)
		{
			$sql_goods = "select bo.*,bg.product_id from ". $this->_tb_back_order . " as bo " .
				" left join " . $this->_tb_back_goods . " as bg " .
				" on bo.back_id = bg.back_id " .
				" where bo.order_id='$order[order_id]' and bg.goods_id='$goods_val[goods_id]' " .
				" and bg.product_id='$goods_val[product_id]' and bo.status_back < 6";
			$back_order =$this->_db->getRow($sql_goods);
			$goods_list[$goods_key]['back_can'] =  count($back_order['order_id']) > 0 ? '0' : '1';

			$sb = $bt = '';
			if($back_order){
				switch ($back_order['status_back'])
				{
					case '3' : $sb = "已完成"; break;
					case '5' : $sb = "已申请"; break;
					//case '6' : $sb = ""; break;
					//case '7' : $sb = ""; break;
					default : $sb = "正在"; break;
				}

				switch ($back_order['back_type'])
				{
					case '1' : $bt = "退货"; break;
					case '3' : $bt = "申请维修"; break;
					case '4' : $bt = "退款"; break;
					default : break;
				}
			}

			$goods_list[$goods_key]['shouhou'] = $sb . " " . $bt;
			$goods_list[$goods_key]['back_can_no'] = $sb . " " . $bt;
			$goods_list[$goods_key]['goods_thumb'] = isset($goods_val['goods_thumb']) ? $goods_val['goods_thumb'] : 'data/default/default.png';
		}
		return $goods_list;
	}


	/**
	 * 订单评论页面
	 *
	 * @access  public
	 * @param   int         $order_id       订单ID
	 * @param   int         $user_id        用户ID
	 * @param   int         $page           第几页
	 * @param   int         $page_size      每页分页数量
	 *
	 * @return void
	 */
	public function get_UserComment($user_id = 0, $order_id,  $page = 1, $page_size = 10)
	{

		$count = $this->_db->getOne("SELECT COUNT(*) FROM " . $this->_tb_order_goods . " AS og
						  LEFT JOIN " . $this->_tb_order_info . " AS o ON og.order_id=o.order_id
						  WHERE o.user_id = '$user_id' AND o.shipping_time_end > 0 AND og.is_back = 0");

		// 评论有效期
		$min_time = gmtime() - 86400 * $GLOBALS['_CFG']['comment_youxiaoqi'];

		if($order_id)
		{

			$sql = "SELECT og.*, o.add_time, o.shipping_time_end, o.order_id, g.goods_thumb, s.shaidan_id, s.pay_points AS shaidan_points, s.status AS shaidan_status,
			c.status AS comment_status,g.supplier_id,ifnull(ssc.value,'网站自营') AS shopname
			FROM " . $this->_tb_order_goods . " AS og
			LEFT JOIN " . $this->_tb_order_info . " AS o ON og.order_id=o.order_id
			LEFT JOIN " . $this->_tb_goods . " AS g ON og.goods_id=g.goods_id
			LEFT JOIN " . $this->_tb_shaidan . " AS s ON og.rec_id=s.rec_id
			LEFT JOIN " . $this->_tb_comment . " AS c ON og.rec_id=c.rec_id
			LEFT JOIN " . $this->_tb_supplier_shop_config . " AS ssc ON ssc.supplier_id=g.supplier_id AND ssc.code='shop_name'
			WHERE o.user_id = '$user_id' AND og.order_id = '$order_id' AND o.shipping_time_end > 0 AND og.is_back = 0 ORDER BY o.add_time DESC";
		}
		else
		{
			$sql = "SELECT og.*, o.add_time, o.shipping_time_end, o.order_id, g.goods_thumb, s.shaidan_id, s.pay_points AS 	shaidan_points, s.status AS shaidan_status,
			c.status AS comment_status,g.supplier_id,ifnull(ssc.value,'网站自营') AS shopname
			FROM " . $this->_tb_order_goods . " AS og
			LEFT JOIN " . $this->_tb_order_info . " AS o ON og.order_id=o.order_id
			LEFT JOIN " . $this->_tb_goods . " AS g ON og.goods_id=g.goods_id
			LEFT JOIN " . $this->_tb_shaidan . " AS s ON og.rec_id=s.rec_id
			LEFT JOIN " . $this->_tb_comment . " AS c ON og.rec_id=c.rec_id
			LEFT JOIN " . $this->_tb_supplier_shop_config . " AS ssc ON ssc.supplier_id=g.supplier_id AND ssc.code='shop_name'
			WHERE o.user_id = '$user_id' AND o.shipping_time_end > 0 AND og.is_back = 0 ORDER BY o.add_time DESC";
		}
		$res = $this->_db->selectLimit($sql, $page_size, ($page - 1) * $page_size);
		while($row = $this->_db->fetchRow($res))
		{
			$row['thumb'] = get_image_path($row['goods_id'], $row['goods_thumb'], true);
			$row['url'] = build_uri('goods', array(
				'gid' => $row['goods_id']
			), $row['goods_name']);
			$row['add_time_str'] = local_date("Y-m-d", $row['add_time']);
			$row['goods_tags'] = $this->_db->getAll("SELECT * FROM " . $this->_tb_goods_tag . " WHERE goods_id = '$row[goods_id]'");

			$row['formated_goods_price']    = price_format($row['goods_price']);

			// 隐藏暂不需要的字段
			unset($row['exclusive']);
			unset($row['is_back']);
			unset($row['cost_price']);
			unset($row['promote_price']);
			unset($row['extension_code']);
			unset($row['is_gift']);
			unset($row['goods_attr_id']);
			unset($row['package_attr_id']);
			unset($row['supplier_id']);
			//unset($row['shopname']);
			unset($row['split_money']);
			unset($row['product_id']);
			unset($row['shaidan_id']);
			unset($row['shaidan_points']);
			unset($row['shaidan_status']);
			unset($row['comment_status']);
			unset($row['goods_thumb']);
			unset($row['url']);
			unset($row['add_time']);

			$item_list[] = $row;
		}
		// 代码增加 for 循环
		for($i = 1; $i < count($item_list); $i ++)
		{
			$item_list[$i]['o_id'] = $item_list[$i]['order_id'];
			unset($item_list[$i]['order_id']);
		}

		/*$pager = get_pager('user.php', array(
			'act' => 'comment'
		), $count, $page, $page_size);*/

		$pager = array();
        $pager['page']         = $page;
        $pager['page_size']    = $page_size;
        $pager['record_count'] = $count;
        $pager['page_count']   = $page_count = ($count > 0) ? intval(ceil($count / $page_size)) : 1;

		$data = array(
			'item_list' => $item_list,
			'pager'     => $pager,
			'min_time'  => $GLOBALS['_CFG']['comment_youxiaoqi'],
		);

		return $data;
	}

	/**
	 * 提交订单商品评论
	 *
	 * @access  public
	 * @param   int         $goods_id       商品ID
	 * @param   int         $user_id        用户ID
	 * @param   int         $comment_rank   评论等级
	 * @param   string      $content        评论内容
	 * @param   array       $comment_tag    选择的订单评论标签
	 * @param   array       $tags_zi        填写的订单评论标签
	 *
	 * @param   int         $order_id       订单ID
	 * @param   int         $rec_id
	 * @param   int         $hide_username  是否匿名
	 *
	 * @param   string      $server 		服务
	 * @param   string      $send 			发货
	 * @param   string      $shipping 		物流
	 *
	 * @return void
	 */
	public function add_UserComment($user_id = 0, $goods_id, $comment_rank = 5, $order_id = 0, $rec_id = 0, $hide_username = 0, $content = '', $comment_tag = '', $tags_zi = array(), $server = '', $send = '', $shipping = '',$img_srcs)
	{
		include_once (ROOT_PATH . 'includes/lib_clips.php');

		$user_info = $this->_db->getRow("SELECT email, user_name FROM " . $this->_tb_user . " WHERE user_id = '$user_id'");
		$comment_type = 0;
		$email     = $user_info['email'];
		$user_name = $user_info['user_name'];
		//过滤js和html
		$search    = array ("'<script[^>]*?>.*?</script>'si", "'<[\/\!]*?[^<>]*?>'si");
		$content   =  preg_replace($search,'',$content);

		// 代码增加
//		$server = $_POST['server'];
//		$send = $_POST['send'];
//		$shipping = $_POST['shipping'];
//		$o_id = $_REQUEST['o_id'];

//		if(! $order_id)
//		{
//			$o_id = $_REQUEST['o1_id'];
//		}

		// 代码增加

		$add_time = gmtime();
		$ip_address = real_ip();
		$status = ($GLOBALS['_CFG']['comment_check'] == 1) ? 0 : 1;

		$buy_time = $this->_db->getOne("SELECT o.add_time FROM " . $this->_tb_order_info . " AS o
							 LEFT JOIN " . $this->_tb_order_goods . " AS og ON o.order_id=og.order_id
							 WHERE og.rec_id = '$rec_id'");

		/* 自定义标签 */
		if(is_array($tags_zi))
		{
			foreach($tags_zi as $tag)
			{
				$status = $GLOBALS['_CFG']['user_tag_check'];
				$this->_db->query("INSERT INTO " . $this->_tb_goods_tag . " (goods_id, tag_name, is_user, state) VALUES ('$goods_id', '$tag', 1, '$status')");
				$tags[] = $this->_db->insert_id();
			}
		}
		/*foreach($comment_tag as $tagid)
		{
			if($tagid > 0)
			{
				$tagids[] = $tagid;
			}
		}
		$comment_tag = (is_array($tagids)) ? implode(",", $tagids) : '';*/

		$sql = "INSERT INTO " . $this->_tb_comment . "(comment_type, id_value, email, user_name, content, comment_rank, add_time, ip_address, user_id, status, rec_id, comment_tag, buy_time, hide_username, order_id)" . "VALUES ('$comment_type', '$goods_id', '$email', '$user_name', '$content', '$comment_rank', '$add_time', '$ip_address', '$user_id', '$status', '$rec_id', '$comment_tag', '$buy_time', '$hide_username', '$order_id')";

		$this->_db->query($sql);
		$this->_db->query("UPDATE " . $this->_tb_order_goods . " SET comment_state = 1 WHERE rec_id = '$rec_id'");

		if($order_id)
		{
			$o_sn = $this->_db->getOne("SELECT order_sn FROM " . $this->_tb_order_info . "
							 WHERE order_id = '$order_id'");
			$sql = "INSERT INTO " . $this->_tb_shop_grade . "(user_id, user_name, add_time,  server, send, shipping, order_id, order_sn)" . "VALUES ('$user_id', '$user_name', '$add_time', '$server', '$send', '$shipping', '$order_id', '$o_sn')";
			$this->_db->query($sql);
		}
		$msg = '';
		if($status == 0)
		{
			$msg .= '您的信息提交成功，需要管理员审核后才能显示！';
		}
		else
		{
			$msg .= '您的信息提交成功！';
		}

		// 处理图片 暂未处理
		if(is_array($img_srcs))
		{
			include_once (dirname(__FILE__) . '/includes/cls_image.php');
			$image = new cls_image($_CFG['bgcolor']);

			$title = trim($_POST['message']);
			$message = $_POST['message'];
			$add_time = gmtime();
			$status = $_CFG['shaidan_check'];
			$hide_username = intval($_POST['hide_username']);

			$sql = "INSERT INTO " . $ecs->table('shaidan') . "(rec_id, goods_id, user_id, title, message, add_time, status, hide_username)" . "VALUES ('$rec_id', '$goods_id', '$user_id', '$title', '$message', '$add_time', '$status', '$hide_username')";
			$db->query($sql);
			$shaidan_id = $db->insert_id();
			$db->query("UPDATE " . $ecs->table('order_goods') . " SET shaidan_state = 1 WHERE rec_id = '$rec_id'");

			foreach($img_srcs as $i => $src)
			{
				$thumb = $image->make_thumb($src, 100, 100);
				$sql = "INSERT INTO " . $ecs->table('shaidan_img') . "(shaidan_id, image, thumb)" . "VALUES ('$shaidan_id', '$src', '$thumb')";
				$db->query($sql);
			}

			// 需要审核
			if($status == 0)
			{
				$msg .= '您的晒单提交成功，需要管理员审核后才能显示！';
			}

			// 不需要审核
			else
			{
				$info = $db->GetRow("SELECT * FROM " . $ecs->table('shaidan') . " WHERE shaidan_id='$shaidan_id'");
				// 该商品第几位晒单者
				$res = $db->getAll("SELECT shaidan_id FROM " . $ecs->table("shaidan") . " WHERE goods_id = '$info[goods_id]' ORDER BY add_time ASC");
				foreach($res as $key => $value)
				{
					if($shaidan_id == $value['shaidan_id'])
					{
						$weizhi = $key + 1;
					}
				}
				// 图片数量
				$imgnum = count($img_srcs);

				// 是否赠送积分
				if($info['is_points'] == 0 && $weizhi <= $_CFG['shaidan_pre_num'] && $imgnum >= $_CFG['shaidan_img_num'])
				{
					$pay_points = $_CFG['shaidan_pay_points'];
					$db->query("UPDATE " . $ecs->table('shaidan') . " SET pay_points = '$pay_points', is_points = 1 WHERE shaidan_id = '$shaidan_id'");
					$db->query("INSERT INTO " . $ecs->table('account_log') . "(user_id, rank_points, pay_points, change_time, change_desc, change_type) " . "VALUES ('$info[user_id]', 0, '" . $pay_points . "', " . gmtime() . ", '晒单获得积分', '99')");
					$log = $db->getRow("SELECT SUM(rank_points) AS rank_points, SUM(pay_points) AS pay_points FROM " . $ecs->table("account_log") . " WHERE user_id = '$info[user_id]'");
					$db->query("UPDATE " . $ecs->table('users') . " SET rank_points = '" . $log['rank_points'] . "', pay_points = '" . $log['pay_points'] . "' WHERE user_id = '$info[user_id]'");
				}

				$msg .= '您的晒单提交成功！';
			}
		}

		$this->return_data['message'] = $msg;
		$this->return_data['status']  = 200;
		return $this->return_data;
	}


	/**
	 * 取消一个用户订单
	 *
	 * @access  public
	 * @param   int         $order_id       订单ID
	 * @param   int         $user_id        用户ID
	 *
	 * @return void
	 */
	public function cancel_order($order_id, $user_id = 0)
	{
		$return = $this->return_data;
		/* 查询订单信息，检查状态 */
		$sql = "SELECT user_id, order_id, order_sn , surplus , integral , bonus_id, order_status, shipping_status, pay_status FROM " .$this->_tb_order_info ." WHERE order_id = '$order_id'";
		$order = $this->_db->GetRow($sql);

		if (empty($order))
		{
			$return['message'] = $GLOBALS['_LANG']['order_exist'];
			return $return;
		}

		// 如果用户ID大于0，检查订单是否属于该用户
		if ($user_id > 0 && $order['user_id'] != $user_id)
		{
			$return['message'] = $GLOBALS['_LANG']['no_priv'];
			return $return;
		}

		// 订单状态只能是“未确认”或“已确认”
		if ($order['order_status'] != OS_UNCONFIRMED && $order['order_status'] != OS_CONFIRMED)
		{
			$return['message'] = $GLOBALS['_LANG']['current_os_not_unconfirmed'];
			return $return;
		}

		//订单一旦确认，不允许用户取消
		/*if ( $order['order_status'] == OS_CONFIRMED)
		{
			$return['message'] = $GLOBALS['_LANG']['current_os_already_confirmed'];
			return $return;
		}*/

		// 发货状态只能是“未发货”
		if ($order['shipping_status'] != SS_UNSHIPPED)
		{
			$return['message'] = $GLOBALS['_LANG']['current_ss_not_cancel'];
			return $return;
		}

		// 如果付款状态是“已付款”、“付款中”，不允许取消，要取消和商家联系
		if ($order['pay_status'] != PS_UNPAYED)
		{
			$return['message'] = $GLOBALS['_LANG']['current_ps_not_cancel'];
			return $return;
		}

		//将用户订单设置为取消
		$sql = "UPDATE ".$this->_tb_order_info ." SET order_status = '".OS_CANCELED."' WHERE order_id = '$order_id'";
		if ($this->_db->query($sql))
		{
			/* 记录log */
			order_action($order['order_sn'], OS_CANCELED, $order['shipping_status'], PS_UNPAYED,$GLOBALS['_LANG']['buyer_cancel'],'buyer');
			/* 退货用户余额、积分、红包 */
			if ($order['user_id'] > 0 && $order['surplus'] > 0)
			{
				$change_desc = sprintf($GLOBALS['_LANG']['return_surplus_on_cancel'], $order['order_sn']);
				log_account_change($order['user_id'], $order['surplus'], 0, 0, 0, $change_desc);
			}
			if ($order['user_id'] > 0 && $order['integral'] > 0)
			{
				$change_desc = sprintf($GLOBALS['_LANG']['return_integral_on_cancel'], $order['order_sn']);
				log_account_change($order['user_id'], 0, 0, 0, $order['integral'], $change_desc);
			}
			if ($order['user_id'] > 0 && $order['bonus_id'] > 0)
			{
				change_user_bonus($order['bonus_id'], $order['order_id'], false);
			}

			/* 如果使用库存，且下订单时减库存，则增加库存 */
			if ($GLOBALS['_CFG']['use_storage'] == '1' && $GLOBALS['_CFG']['stock_dec_time'] == SDT_PLACE)
			{
				change_order_goods_storage($order['order_id'], false, 1);
			}

			/* 修改订单 */
			$arr = array(
				'bonus_id'  => 0,
				'bonus'     => 0,
				'integral'  => 0,
				'integral_money'    => 0,
				'surplus'   => 0
			);
			$this->update_order($order['order_id'], $arr);

			$return['code'] = '200';
			return $return;
		}
		else
		{
			$return['message'] = $this->_db->errorMsg();
			return $return;
		}

	}


	/**
	 * 用户订单确认收货
	 *
	 * @access  public
	 * @param   int         $order_id       订单ID
	 * @param   int         $user_id        用户ID
	 *
	 * @return void
	 */
	public function arrived_order($order_id, $user_id = 0)
	{
		require_once(ROOT_PATH . '/includes/lib_order.php');
		include_once (ROOT_PATH . 'includes/lib_transaction.php');

		$result = $this->affirm_received($order_id, $user_id);

		if($result['code'] == 200)
		{
			$this->return_data['code'] = '200';
			return $this->return_data;
		}
		else
		{
			$this->return_data['message'] = $result['message'];
			return $this->return_data;
		}
	}


	/**
	 * 确认一个用户订单
	 *
	 * @access  public
	 * @param   int         $order_id       订单ID
	 * @param   int         $user_id        用户ID
	 *
	 * @return  bool        $bool
	 */
	private function affirm_received($order_id, $user_id = 0)
	{


		require (ROOT_PATH . 'languages/' . $GLOBALS['_CFG']['lang'] . '/common.php');
		require (ROOT_PATH . 'languages/' . $GLOBALS['_CFG']['lang'] . '/user.php');

		$return = $this->return_data;

		/* 查询订单信息，检查状态 */
		$sql = "SELECT user_id, order_sn , order_status, shipping_status, pay_status FROM ".$this->_tb_order_info ." WHERE order_id = '$order_id'";

		$order = $this->_db->GetRow($sql);

		// 如果用户ID大于 0 。检查订单是否属于该用户
		if ($user_id > 0 && $order['user_id'] != $user_id)
		{
			$return['message'] = $_LANG['no_priv'];
			return $return;
		}
		/* 检查订单 */
		elseif ($order['shipping_status'] == SS_RECEIVED)
		{
			$return['message'] = $_LANG['order_already_received'];
			return $return;
		}
		elseif ($order['shipping_status'] != SS_SHIPPED)
		{
			$return['message'] = $_LANG['order_invalid'];
			return $return;
		}
		/* 修改订单发货状态为“确认收货” */
		else
		{
			$sql = "UPDATE " . $this->_tb_order_info . " SET shipping_status = '" . SS_RECEIVED . "',shipping_time_end = '" . gmtime() . "' WHERE order_id = '$order_id'";
			if ($this->_db->query($sql))
			{
				if(get_cod_id($order_id)){
					get_pingtai_rebate_from_supplier($order_id);
					$this->_db->query("UPDATE " . $this->_tb_order_info . " SET rebate_ispay = 2 WHERE order_id = ".$order_id);
				}
				$sql_2 = "SELECT back_id FROM " . $this->_tb_back_order . " WHERE order_id = '$order_id' AND status_back < 6 AND status_back != 3";
				$re_2  = $this->_db->getCol($sql_2);
				if (count($re_2) > 0)
				{
					$sql_3 = "UPDATE " . $this->_tb_back_goods . " SET status_back = 8 WHERE back_id in (" . implode(',', $re_2) . ")";
					$this->_db->query($sql_3);
				}

				$sql_4 = "UPDATE " . $this->_tb_back_order . " SET status_back = 8 WHERE order_id = '$order_id' AND status_back < 6 AND status_back != 3";
				$this->_db->query($sql_4);

				/* 记录日志 */
				order_action($order['order_sn'], $order['order_status'], SS_RECEIVED, $order['pay_status'], '', $_LANG['buyer']);

				$return['message'] = '确认收货成功';
				$return['code'] = '200';
				return $return;
			}
			else
			{
				$return['message'] = $this->_db->errorMsg();
				return $return;
			}
		}

	}

	/**
	 * 修改订单
	 * @param   int     $order_id   订单id
	 * @param   array   $order      key => value
	 * @return  bool
	 */
	private function update_order($order_id, $order)
	{
		if(isset($order['shipping_status']) && $order['shipping_status'] == SS_RECEIVED && get_cod_id($order_id)){
			//收货确认的订单有可能发生佣金操作
			get_pingtai_rebate_from_supplier($order_id);
			$order['rebate_ispay'] = 2;
		}
		return $this->_db->autoExecute($this->_tb_order_info,
			$order, 'UPDATE', "order_id = '$order_id'");
	}



	/**
	 * 根据订单状态，物流状态和支付状态，合并成一个综合状态
	 *
	 * @param $order_status
	 * @param $shipping_status
	 * @param $pay_status
	 * @param $status_back
	 * @param $status_refund
	 * @param $back_type
	 * @return mixed
	 */
	public function get_composite_status($order_status, $shipping_status, $pay_status, $status_back = -1, $status_refund = 0, $back_type = 0)
	{

//    define('CS_UNKNOW',               99); // 未知状态：
//    define('CS_UNPAY',                100); // 未支付：
//    define('CS_UNWORK',               101); // 订单无效：
//    define('CS_CHECKED',              102); // 已付款
//    define('CS_SPLITED_PART',         103); // 部分分单
//    define('CS_SPLITED',              104); // 已分单
//    define('CS_SHIPPED_PART',         105); // 部分发货
//    define('CS_SHIPPED',              106); // 已发货
//    define('CS_RECEIVED_PART',        107); // 部分签收
//    define('CS_RECEIVED',             108); // 已签收
//    define('CS_FINISHED',             109); // 已完成
//    define('CS_CANCEL',               110); // 已取消
//    define('CS_RETURNING',            111); // 退货中
//    define('CS_PAY_RETURNING',        112); // 退款中
//    define('CS_PAY_RETURNED',         113); // 已退款
//    define('CS_PAY_RETURNING_REJECT', 114); // 退款中（拒收）
//    define('CS_PAY_RETURNED_REJECT',  115); // 已退款（拒收）
//    define('CS_PAY_RETURNING_ADMIN_REJECT',  116); // 后台驳回退款退货请求


		if($status_back > -1){

			/* status_back
             * 0:审核通过,
             * 1:收到寄回商品,
             * 2:换回商品已寄出,
             * 3:完成退货/返修,
             * 4:退款(无需退货),
             * 5:审核中,
             * 6:申请被拒绝,
             * 7:管理员取消,
             * 8:用户自己取消
             * */

			/* $status_refund
             * 0:未退款,
             * 1:已退款,
             * */

			/* $status_refund
             * 1:退货,
             * 4:退款,
             * */


			if($status_back == 5 && $back_type == 4){
				$order_status = 2;
				$shipping_status = 0;
				$pay_status = 3;
			}
			else if($status_back == 5 && $back_type == 1){
				$order_status = 4;
				$shipping_status = 1;
				$pay_status = 2;


			}
			else if($status_back == 0 || $status_back == 1 || $status_back == 2 || $status_back == 3)
			{

				$order_status = 4;
				$shipping_status = 1;
				$pay_status = 2;

				if($status_refund == 1){
					$pay_status = 4;
				}
			}
			else if($status_back == 4 || $status_back == 5)
			{
				$order_status = 2;
				$shipping_status = 0;
				$pay_status = 3;

				if($status_refund == 1){
					$pay_status = 4;
				}
			}
			else if($status_back == 6)
			{
				$order_status = 4;
				$shipping_status = 3;
				$pay_status = 3;
			}
			else if($status_back == 6)
			{
				$order_status = 2;
				$shipping_status = 0;
				$pay_status = 4;
			}
		}



//		print_r($order_status);
//		print_r($shipping_status);
//		print_r($pay_status);
//		print_r($status_back);
//		print_r($status_refund);
//		echo "<br/>";



		//已取消 = 已取消，未发货，未付款
		if($order_status == 2 && $shipping_status==0 && ($pay_status==0 || $pay_status==2))
		{
			$composite_status = CS_CANCEL;//已取消
		}
		//订单无效 = 已无效，未发货，未付款
		elseif($order_status == 3 && $shipping_status == 0 && $pay_status == 0)
		{
			$composite_status = CS_UNWORK;
		}
		//未付款 = 未审核，未发货，未付款
		elseif(($order_status == 1 || $order_status == 0) && $shipping_status == 0 && $pay_status == 0)
		{
			$composite_status = CS_UNPAY;
		}
		//已审核 = 已审核，未发货，已付款
		elseif($order_status == 1 && $shipping_status == 0 && $pay_status == 2)
		{
			$composite_status = CS_CHECKED;
		}
		//已分单（部分分单）（部分商品生成发货单） = 部分分单，未发货，已付款
		elseif( $order_status == 6 && $shipping_status == 5 && $pay_status == 2)
		{
			$composite_status = CS_SPLITED_PART;
		}
		//已分单（全部商品生成发货单） = 已分单，未发货，已付款
		elseif( $order_status == 5 && $shipping_status == 5 && $pay_status == 2)
		{
			$composite_status = CS_SPLITED;
		}
		//已发货（部分发货） = 已分单/部分分单，已发货（部分商品），已付款
		elseif(($order_status == 5 || $order_status == 6) && $shipping_status == 4 && $pay_status == 2)
		{
			$composite_status = CS_SHIPPED_PART;
		}
		//已发货 = 已分单/部分分单，已发货，已付款
		elseif(($order_status == 5 || $order_status == 6) && $shipping_status == 1 && $pay_status == 2)
		{
			$composite_status = CS_SHIPPED;
		}
		//已签收(部分签收) = 已分单/部分分单，已签收（部分签收），已付款
		elseif(($order_status == 5 || $order_status == 6) && $shipping_status == 8 && $pay_status == 2)
		{
			$composite_status = CS_RECEIVED_PART;
		}
		//已签收 = 已分单，已签收，已付款
		elseif($order_status == 5 && $shipping_status == 2 && $pay_status == 2)
		{
			$composite_status = CS_RECEIVED;;
		}
		//已完成 = 已完成，已签收，已付款
		elseif($order_status == 7 && $shipping_status == 2 && $pay_status == 2)
		{
			$composite_status = CS_FINISHED;
		}
		//退货中（用户申请退货，或拒收） = 退货，已发货(部分发货)/已签收/已签收（部分商品），已付款
		elseif($order_status == 4 && ($shipping_status == 1 || $shipping_status == 2 || $shipping_status == 4 || $shipping_status == 7 || $shipping_status == 8) && $pay_status == 2)
		{
			$composite_status = CS_RETURNING;
		}
		//退款中（取消已付款的订单） = 已取消，未发货，退款中
		elseif($order_status == 2 && $shipping_status == 0 && $pay_status == 3)
		{
			$composite_status = CS_PAY_RETURNING;
		}
		//退款中（申请退货） = 退货，已发货(部分发货)/已签收/已签收（部分商品），退款中
		elseif($order_status == 4 && ($shipping_status == 1 || $shipping_status == 2 || $shipping_status == 4 || $shipping_status == 8) && $pay_status == 3)
		{
			$composite_status = CS_PAY_RETURNING;
		}
		//退款中（拒收）（后台驳回） = 退款中
		elseif($order_status == 4 && $shipping_status == 3 && $pay_status == 3)
		{
			$composite_status = CS_PAY_RETURNING_ADMIN_REJECT;
		}
		//退款中（拒收）（用户拒收） = 退货，已拒收，退款中
		elseif($order_status == 4 && $shipping_status == 7 && $pay_status == 3)
		{
			$composite_status = CS_PAY_RETURNING_REJECT;
		}
		//已退款（取消已付款订单） = 已取消，未发货，已退款
		elseif($order_status == 2 && $shipping_status == 0 && $pay_status == 4)
		{
			$composite_status = CS_PAY_RETURNED;
		}
		//已退款（申请退货） = 退货，已发货(部分发货)/已签收/已签收（部分商品），已退款
		elseif($order_status == 4 && ($shipping_status == 1 || $shipping_status == 2 || $shipping_status == 4 || $shipping_status == 8) && $pay_status == 4)
		{
			$composite_status = CS_PAY_RETURNED;
		}
		//已退款（拒收） = 退货，已拒收，已退款
		elseif($order_status == 4 && $shipping_status == 7 && $pay_status == 4)
		{
			$composite_status = CS_PAY_RETURNED_REJECT;
		}
		//申请退货
		elseif($order_status == 8 && ($shipping_status == 1 || $shipping_status == 2 || $shipping_status == 4 || $shipping_status == 7 || $shipping_status == 8) && $pay_status == 2){
			$composite_status = CS_APPLY_RETURN;
		}
		elseif($order_status == 5 && $shipping_status == 7 && $pay_status == 2)
		{
			$composite_status = CS_SHIPPED;		//拒收取消退货，返回已发货状态
		}else
		{
			$composite_status = CS_UNKNOW;
		}



		return $composite_status;
	}

	/**
	 * 返回订单未出售的设计商品(goods_status = 1)
	 */
	public function get_Offsale_Order_Goods($order_id)
	{
		$sql = "SELECT DISTINCT a.goods_id, a.goods_name, b.goods_thumb, b.goods_img FROM " . $this->_tb_order_goods . " a INNER JOIN " . $this->_tb_goods . " b ON a.goods_id = b.goods_id WHERE a.order_id = '$order_id' AND b.goods_status = 1 AND b.is_delete = 0 ORDER BY a.goods_id DESC";
        $list = $this->_db->getAll($sql);
        return $list;
	}
}
