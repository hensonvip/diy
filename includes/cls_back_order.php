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

class cls_back_order{

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
		$this->_tb_brand         = $GLOBALS['ecs']->table('brand');
		$this->_tb_goods_attr    = $GLOBALS['ecs']->table('goods_attr');
		$this->_tb_back_goods    = $GLOBALS['ecs']->table('back_goods');
		$this->_tb_region        = $GLOBALS['ecs']->table('region');
		$this->_tb_back_replay   = $GLOBALS['ecs']->table('back_replay');
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
	 *  新“退换货”订单列表
	 *
	 * @access  public
	 * @param   int     $user_id        用户ID
	 * @param   string  $start_date     开始时间
	 * @param   string  $end_date       结束时间
	 * @param   int     $num            列表最大数量
	 * @param   int     $start          列表起始位置
	 *
	 * @return  array   $arr
	 */
	public function back_order_list($user_id, $start_date = '', $end_date = '', $num = 10, $start = 1){

		include_once (ROOT_PATH . 'includes/lib_transaction.php');

		/* 取得订单列表 */
		$arr = array();

		$sql = "SELECT bo.back_id, bo.order_sn, bo.order_id, bo.add_time, bo.refund_type, bo.refund_desc, bo.refund_money_1, bo.refund_money_2, bo.back_type, bo.status_refund, bo.status_back, bo.back_reason " . " FROM " . $this->_tb_back_order . " AS bo left join " . $this->_tb_goods . " AS g " . " on bo.goods_id=g.goods_id  " . " WHERE user_id = '$user_id' ORDER BY add_time DESC";
		$res = $this->_db->SelectLimit($sql, $num, $start);

		while($row = $this->_db->fetchRow($res))
		{

			$row['order_time'] = local_date($GLOBALS['_CFG']['time_format'], $row['add_time']);
			$row['refund_money_1'] = price_format($row['refund_money_1'], false);

//			$row['goods_url'] = build_uri('goods', array(
//				'gid' => $row['goods_id']
//			), $row['goods_name']);
			$row['status_back_1'] = $row['status_back'];
			$row['status_back'] = $GLOBALS['_LANG']['bos'][(($row['back_type'] == 4 && $row['status_back'] != 8) ? $row['back_type'] : $row['status_back'])] . ' - ' . $GLOBALS['_LANG']['bps'][$row['status_refund']];

			$sql_goods = "SELECT rec_id,goods_id,goods_name,goods_sn,is_real,send_number,goods_attr,back_goods_price,back_goods_number FROM " . $this->_tb_back_goods . " WHERE back_id = " . $row['back_id'];
			$row['goods_list'] = $this->_db->getAll($sql_goods);

			$arr[] = $row;
		}

		return $arr;

	}

	/**
	 *  新“退换货”订单详情
	 *
	 * @access  public
	 * @param   int     $user_id        用户ID
	 * @param   int     $back_id        售后单ID
	 *
	 * @return  array   $arr
	 */

	public function back_order_detail($user_id,$back_id){

		$sql = "SELECT back_id, order_sn, order_id, add_time, country, province, city, district, refund_type, refund_desc, refund_money_1, refund_money_2, back_type, status_refund, status_back, back_reason " . " FROM " . $this->_tb_back_order . " WHERE back_id= '$back_id' and user_id = '$user_id' ";
		$back_shipping = $this->_db->getRow($sql);

		if(!$back_shipping){
			$this->return_data['message'] = '您无权限操作其他用户的订单';
			return $this->return_data;
		}

		$sql_og = "SELECT * FROM " . $this->_tb_back_goods . " WHERE back_id = " . $back_id;
		$back_shipping['goods_list'] = $this->_db->getAll($sql_og);

		$back_shipping['add_time']         = local_date("Y-m-d H:i", $back_shipping['add_time']);
		$back_shipping['refund_money_1']   = price_format($back_shipping['refund_money_1'], false);
		$back_shipping['refund_money_2']   = price_format($back_shipping['refund_money_2'], false);
		$back_shipping['refund_type_name'] = $back_shipping['refund_type'] == '0' ? '' : ($back_shipping['refund_type'] == '1' ? '退回用户余额' : '线下退款');
		$back_shipping['country_name']     = $this->_db->getOne("SELECT region_name FROM " . $this->_tb_region . " WHERE region_id = '$back_shipping[country]'");
		$back_shipping['province_name']    = $this->_db->getOne("SELECT region_name FROM " . $this->_tb_region . " WHERE region_id = '$back_shipping[province]'");
		$back_shipping['city_name']        = $this->_db->getOne("SELECT region_name FROM " . $this->_tb_region . " WHERE region_id = '$back_shipping[city]'");
		$back_shipping['district_name']    = $this->_db->getOne("SELECT region_name FROM " . $this->_tb_region . " WHERE region_id = '$back_shipping[district]'");

		$back_shipping['status_back_1'] = $back_shipping['status_back'];
		$back_shipping['status_back'] = $GLOBALS['_LANG']['bos'][$back_shipping['status_back']] . ($back_shipping['status_back'] == '3' && $back_shipping['back_type'] && $back_shipping['back_type'] != '4' ? ' (换回商品已寄出，请注意查收) ' : '');
		$back_shipping['status_refund'] = $GLOBALS['_LANG']['bps'][$back_shipping['status_refund']];

		// 退货商品 + 换货商品 详细信息
		$list_backgoods = array();
		$sql = "select * from " . $this->_tb_back_goods . " where back_id = '$back_id' order by back_type ";
		$res_backgoods = $this->_db->query($sql);
		while($row_backgoods = $this->_db->fetchRow($res_backgoods))
		{
			$back_type_temp = $row_backgoods['back_type'] == '2' ? '1' : $row_backgoods['back_type'];
			$list_backgoods[$back_type_temp]['goods_list'][] = array(
				'goods_name' => $row_backgoods['goods_name'], 'goods_attr' => $row_backgoods['goods_attr'], 'back_goods_number' => $row_backgoods['back_goods_number'], 'back_goods_money' => price_format($row_backgoods['back_goods_number'] * $row_backgoods['back_goods_price'], false), 'status_back' => $GLOBALS['_LANG']['bos'][$row_backgoods['status_back']] . ($row_backgoods['status_back'] == '3' && $row_backgoods['back_type'] && $row_backgoods['back_type'] != '4' ? ' (换回商品已寄出，请注意查收) ' : ''), 'status_refund' => $GLOBALS['_LANG']['bps'][$row_backgoods['status_refund']], 'back_type' => $row_backgoods['back_type']
			);
		}

		/* 回复留言 增加 */
//		$res = $this->_db->getAll("SELECT * FROM " . $this->_tb_back_replay . " WHERE back_id = '$back_id' ORDER BY add_time ASC");
//		foreach($res as $value)
//		{
//			$value['add_time'] = local_date("Y-m-d H:i", $value['add_time']);
//			$back_replay[] = $value;
//		}

		return $back_shipping;

	}

	/**
	 *  创建退款退货表单页面
	 *
	 * @access  public
	 * @param   int     $user_id        用户ID
	 * @param   int     $order_id       订单ID
	 * @param   int     $goods_id       申请售后的订单商品ID
	 * @param   int     $product_id     申请售后的订单商品的货品ID
	 *
	 * @return  array   $arr
	 */

	public function to_create_back_order($user_id, $order_id, $goods_id = 0, $product_id = 0)
	{
		$return = array(
            'code' => 500,
            'data' => array(
            ),
            'message' => ''
        );

        $sql_oi = "SELECT order_id,order_sn,order_status,shipping_status,pay_status,shipping_time_end,extension_code,(goods_amount +  insure_fee + pay_fee + pack_fee + card_fee + tax - discount) AS total_fee,shipping_fee FROM " . $GLOBALS['ecs']->table('order_info') . " WHERE user_id='$user_id' AND order_id = " . $order_id;
	    $order_info = $GLOBALS['db']->getRow($sql_oi);
	    
	    
	    if(empty($order_info)){
	    	$return['message'] = "非法操作,错误码001";
            return $return;
	    }
        
        //判断是否有整单退款退货
	    $back_info_num = "SELECT COUNT(*) FROM " . $GLOBALS['ecs']->table('back_order') .
        " WHERE order_id = " . $order_id . " AND user_id='$user_id' AND goods_id=0 AND status_back < 6";
	    if ($GLOBALS['db']->getOne($back_info_num) > 0)
	    {
	        $return['message'] = "对不起！您没权限操作该订单";
            return $return;
	    }


	    //判断单件商品是否有退款退货
	    if($goods_id>0){
	    	$back_info_num2 = "SELECT COUNT(*) FROM " . $GLOBALS['ecs']->table('back_order') .
	        " WHERE order_id = " . $order_id . " AND user_id='$user_id' AND goods_id='$goods_id' AND product_id='$product_id' AND status_back < 6";
		    if ($GLOBALS['db']->getOne($back_info_num2) > 0)
		    {
		        $return['message'] = "对不起！您没权限操作该订单";
	            return $return;
		    }
	    }

	    $min_time = local_strtotime(local_date('Y-m-d H:i:s', strtotime('-7 days')));//则自确认收货起$GLOBALS['_CFG']['comment_youxiaoqi']天内买家可以申请售后 先默认7天之内还可申请退款，没做后台设置该值
	    //$min_time = local_strtotime(local_date('Y-m-d H:i:s', strtotime('-'.$GLOBALS['_CFG']['shouhou_time'].' days')));//则自确认收货起$GLOBALS['_CFG']['comment_youxiaoqi']天内买家可以申请售后
	   

	    $order=array();

        

        //服务类型[服务商品只能退款]
        //仅退款【未收到货（包含未签收），或卖家协商同意前提下】
	    //退款退货【已收到货需要退货已收到的货物】
	    if(in_array($order_info['order_status'],array(1,5)) && in_array($order_info['shipping_status'],array(0,1,3,5))  && $order_info['pay_status']==2){
	    	$back_type[0]['type_id']=4;
	    	$back_type[0]['type_name']='退款';
	    	$back_type[0]['selected']="checked";

	    	//$order['back_type']=array('4'=>'退款','1'=>'退款退货');
	    }elseif($order_info['order_status']==5 && $order_info['shipping_status']==1  && $order_info['pay_status']==2 && $order_info['extension_code']!='virtual_good'){
	    	$back_type[0]['type_id']=4;
	    	$back_type[0]['type_name']='退款';
	    	$back_type[0]['selected']="checked";

	    	$back_type[1]['type_id']=1;
	    	$back_type[1]['type_name']='退款退货';
	    	$back_type[1]['selected']="";

	    	//$order['back_type']=array('4'=>'退款','1'=>'退款退货');
	    }elseif($order_info['order_status']==5 && $order_info['shipping_status']==2 && $order_info['pay_status']==2 && $order_info['shipping_time_end']>$min_time){
	    	if($order_info['extension_code']=='virtual_good'){
	    		$return['message'] = "非法操作,错误码002";
	            return $return;
	    	}
	    	$back_type[0]['type_id']=1;
	    	$back_type[0]['type_name']='退款退货';
	    	$back_type[0]['selected']="checked";
	    }
	    else{
	    	$return['message'] = "非法操作,错误码003";
            return $return;
	    }

	    $where="";
        if($goods_id>0){
        	$where=" AND og.goods_id=$goods_id AND og.product_id=$product_id";
        }
		$sql_og = "SELECT  og.goods_id, og.product_id,og.goods_name, g.goods_thumb, og.goods_number, " .
            "og.goods_price, og.goods_attr,  " .
            "og.goods_price * og.goods_number AS subtotal,  og.order_id, og.extension_code  " .
            "FROM " . $this->_tb_order_goods . "as og right join" . $this->_tb_goods .
            "as g on og.goods_id = g.goods_id" .
            " WHERE og.order_id = '$order_id' $where";
        $goods_list = $GLOBALS['db']->getAll($sql_og); 

        if(empty($goods_list)){
        	$return['message'] = "非法操作,错误码004";
            return $return;
        }  
        
        $order_goods_total=0; //商品总金额
        $order_goods_number=0; //商品总数量
        $order_goods_price=0; //商品单价[针对单件退款退货商品]
        foreach ($goods_list as $key => $value) {
        	$order_goods_total+=$value['subtotal'];
        	$order_goods_number+=$value['goods_number'];

        	$order_goods_price=$value['goods_price'];
         	//所属商品
            if($value['extension_code']=='virtual_good'){
                $is_type   = 1;//服务商品
            }
            else{
                $is_type   = 0;//普通商品
            }

            $goods_list[$key]['is_type']=$is_type;


            $goods_list[$key]['format_goods_price'] = price_format($value['goods_price']);
            $goods_list[$key]['format_subtotal'] = price_format($value['subtotal']);

            unset($goods_list[$key]['extension_code']);

        } 


        $order['goods_list']=$goods_list;


        //判断该订单有几种商品(只有一种的话，则默认为整单。以上的则$goods_id>0为整单，或者为单件)
	    $order_goods_num =$GLOBALS['db']->getOne("SELECT COUNT(*) FROM " . $GLOBALS['ecs']->table('order_goods') .
	        " WHERE order_id = " . $order_id . "");
	    

	    if($order_goods_num>0){
	    	if($goods_id>0){
	    		$tui_goods_subtotal=$order_goods_total;
	    		$goods_price=$order_goods_price;
	    		$order_all=0;
	    	}
	    	else{
	    		$tui_goods_subtotal=$order_info['total_fee'];
	    		$goods_price=0;
	    		$order_all=1;
	    	}
	    }
	    else{
	    	$tui_goods_subtotal=$order_goods_total;
	    	$goods_price=0;
	    	$order_all=1;
	    }


        $order['order_all']=$order_all; //（1是整单的，0为单件）
        $order['order_sn']=$order_info['order_sn']; //订单号
	    $order['goods_price']=$goods_price; //商品单价（针对的是单件商品退款退货。如果是整单的，那么此项为0）

	    $order['tui_goods_number']=$order_goods_number; //退款数量

	    $order['tui_goods_subtotal']=$tui_goods_subtotal; //退款金额
	    $order['format_tui_goods_subtotal']=price_format($tui_goods_subtotal);

	    

        

	    $order['back_type']=$back_type;//服务类型

	    //退款退货原因
		$reason_list=$GLOBALS['db']->getAll("SELECT * from ".$GLOBALS['ecs']->table('reason')." order by reason_id asc");
		foreach ($reason_list as $key => $value) {
			if($value['reason_id']==1){
				$reason_list[$key]['selected']="checked";
			}
			else{
				$reason_list[$key]['selected']="";
			}
			
		}

	    $order['reason_list']=$reason_list; //退款退货原因

	    $return = array(
            'code' => 200,
            'data' => $order,
            'message' => 'success'
        );

        return $return;
	}
}
