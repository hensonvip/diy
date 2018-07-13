<?php
/**
 * 商品模块
 * @2016-10-26 cyq
 */

if (!defined('IN_ECS'))
{
    die('Hacking attempt');
}

include_once(ROOT_PATH . 'includes/cls_goods.php');

class cls_cart
{
    protected $_db                = null;
    protected $_tb_user           = null;
    protected $_now_time          = 0;
    protected $_mc_time           = 0;
    protected $_plan_time         = 0;
    protected $_mc                = null;
    protected static $_instance   = null;
    public static $_errno = array(
            1 => '操作成功',
            2 => '参数错误',
            3 => '分类不存在',
    );

    function __construct()
    {
        $this->_db = $GLOBALS['db'];
        $this->_tb_user          = $GLOBALS['ecs']->table('users');
        $this->_tb_cart          = $GLOBALS['ecs']->table('cart');
        $this->_tb_goods         = $GLOBALS['ecs']->table('goods');
        $this->_tb_collect_goods         = $GLOBALS['ecs']->table('collect_goods');
        $this->_tb_goods_attr    = $GLOBALS['ecs']->table('goods_attr');
        $this->_tb_goods_gallery = $GLOBALS['ecs']->table('goods_gallery');
        $this->_tb_attribute_icon = $GLOBALS['ecs']->table('attribute_icon');
        $this->_tb_attribute_color = $GLOBALS['ecs']->table('attribute_color');
        $this->_tb_attribute     = $GLOBALS['ecs']->table('attribute');
        $this->_tb_member_price  = $GLOBALS['ecs']->table('member_price');
        $this->_tb_order_goods   = $GLOBALS['ecs']->table('order_goods');
        $this->_tb_order_info    = $GLOBALS['ecs']->table('order_info');
        $this->_tb_goods_activity= $GLOBALS['ecs']->table('goods_activity');
        $this->_tb_package_goods = $GLOBALS['ecs']->table('package_goods');
        $this->_tb_favourable_activity = $GLOBALS['ecs']->table('favourable_activity');
        $this->_tb_group_goods   = $GLOBALS['ecs']->table('group_goods');
        $this->_tb_products   = $GLOBALS['ecs']->table('products');
        $this->_now_time         = time();
        $this->_plan_time        = 3600*24*15;

		$this->goods = cls_goods::getInstance();
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
     * 将商品添加到购物车
     * 如果商品有促销，价格不变
     *
     * @access  public
     * @param  array  $user_rank_info
     * @param  string   $param_post
     * @return  void
     */



	public function addFavourable($act_id,$gift,$user_rank_info){
        if (!is_array($gift))
        {
            $gift = explode(',', $gift);
        }
		  /* 取得优惠活动信息 */
		$favourable = $this->favourable_info($act_id);
		if (empty($favourable))
		{
			//show_message($_LANG['favourable_not_exist']);
			return array('code'=>500,'message'=>'优惠不存在');
		}

		/* 判断用户能否享受该优惠 */
		if (!$this->favourable_available($favourable,$user_rank_info))
		{
			//show_message($_LANG['favourable_not_available']);
			return array('code'=>500,'message'=>'不能享受该优惠');
		}

		/* 检查购物车中是否已有该优惠 */
		$cart_favourable = $this->cart_favourable($user_rank_info['user_id']);
		if ($this->favourable_used($favourable, $cart_favourable))
		{
			//show_message($_LANG['favourable_used']);
			return array('code'=>500,'message'=>'优惠已经使用');
		}

		/* 赠品（特惠品）优惠 */
		if ($favourable['act_type'] == FAT_GOODS)
		{
			/* 检查是否选择了赠品 */
			if (empty($gift))
			{
				//show_message($_LANG['pls_select_gift']);
				return array('code'=>500,'message'=>'请选择礼物');
			}

			/* 检查是否已在购物车 */
			$sql = "SELECT goods_name" .
					" FROM " . $GLOBALS['ecs']->table('cart') .
					" WHERE user_id = '" . $user_rank_info['user_id'] . "'" .
					" AND rec_type = '" . CART_GENERAL_GOODS . "'" .
					" AND is_gift = '$act_id'" .
					" AND goods_id " . db_create_in($gift);
			$gift_name = $GLOBALS['db']->getCol($sql);
			if (!empty($gift_name))
			{
				//show_message(sprintf($_LANG['gift_in_cart'], join(',', $gift_name)));
				return array('code'=>500,'message'=>'您选择的赠品（特惠品）已经在购物车中了');
			}

			/* 检查数量是否超过上限 */
			$count = isset($cart_favourable[$act_id]) ? $cart_favourable[$act_id] : 0;
			if ($favourable['act_type_ext'] > 0 && $count + count($gift) > $favourable['act_type_ext'])
			{
				//show_message($_LANG['gift_count_exceed']);
				return array('code'=>500,'message'=>'您选择的赠品（特惠品）已经超出数量了');
			}

			/* 检查赠品库存 */
			$gifts = $favourable['gift'];
			foreach($gifts as $v){
				$sql = "SELECT goods_number FROM ".$this->_tb_goods."WHERE goods_id = $v[id]";
				$rec = $this->_db->getOne($sql);
				if(1 > $rec)
				{
					$return['code'] = 500;
					$return['message'] = "赠品  ".$v['name']."  已经赠送完!";
					return $return;
				}
			}



			/* 添加赠品到购物车 */
			foreach ($favourable['gift'] as $g)
			{
				if (in_array($g['id'], $gift))
				{
					$id = $this->add_gift_to_cart($act_id, $g['id'], $g['price'],$user_rank_info['user_id']);
				}
			}
            return array('code'=>200,'message'=>'','data'=>$id);
		}
		elseif ($favourable['act_type'] == FAT_DISCOUNT)
		{
			$id = $this->add_favourable_to_cart($act_id, $favourable['act_name'], $this->cart_favourable_amount($favourable,$user_rank_info) * (100 - $favourable['act_type_ext']) / 100,$user_rank_info['user_id']);
			return array('code'=>200,'message'=>'','data'=>$id);
		}
		elseif ($favourable['act_type'] == FAT_PRICE)
		{
			$id = $this->add_favourable_to_cart($act_id, $favourable['act_name'], $favourable['act_type_ext'],$user_rank_info['user_id']);
			return array('code'=>200,'message'=>'','data'=>$id);
		}
	}

	 public function favourable_info($act_id)
	{
		$sql = "SELECT * FROM " . $GLOBALS['ecs']->table('favourable_activity') .
				" WHERE act_id = '$act_id'";
		$row = $GLOBALS['db']->getRow($sql);
		if (!empty($row))
		{
			$row['start_time'] = local_date($GLOBALS['_CFG']['time_format'], $row['start_time']);
			$row['end_time'] = local_date($GLOBALS['_CFG']['time_format'], $row['end_time']);
			$row['formated_min_amount'] = price_format($row['min_amount']);
			$row['formated_max_amount'] = price_format($row['max_amount']);
			$row['gift'] = unserialize($row['gift']);
			if ($row['act_type'] == FAT_GOODS)
			{
				$row['act_type_ext'] = round($row['act_type_ext']);
			}
		}

		return $row;
	}

	public function add_favourable_to_cart($act_id, $act_name, $amount,$user_id)
	{
		$sql = "INSERT INTO " . $GLOBALS['ecs']->table('cart') . "(" .
					"user_id, session_id, goods_id, goods_sn, goods_name, market_price, goods_price, ".
					"goods_number, is_real, extension_code, parent_id, is_gift, rec_type ) ".
				"VALUES('$user_id', '', 0, '', '$act_name', 0, ".
					"'" . (-1) * $amount . "', 1, 0, '', 0, '$act_id', '" . CART_GENERAL_GOODS . "')";
		$GLOBALS['db']->query($sql);
		return $GLOBALS['db']->insert_id();
	}

	 public function add_gift_to_cart($act_id, $id, $price,$user_id)
	{
		$sql = "INSERT INTO " . $GLOBALS['ecs']->table('cart') . " (" .
					"user_id, session_id, goods_id, goods_sn, goods_name, market_price, goods_price, ".
					"goods_number, is_real, extension_code, parent_id, is_gift, rec_type ) ".
				"SELECT '$user_id', '', goods_id, goods_sn, goods_name, market_price, ".
					"'$price', 1, is_real, extension_code, 0, '$act_id', '" . CART_GENERAL_GOODS . "' " .
				"FROM " . $GLOBALS['ecs']->table('goods') .
				" WHERE goods_id = '$id'";
		$GLOBALS['db']->query($sql);
		return $GLOBALS['db']->insert_id();
	}

    // 参考数据
    // {"quick":1,"spec":[],"goods_id":321,"number":"1","parent":0};
    public function addToCart($param_post = '', $user_rank_info = array()){
        define('SESS_ID',session_id());

        $_SESSION = $user_rank_info;

        $user_id = $user_rank_info['user_id'];

        $return = array(
            'code' => 500,
            'message' => '',
            'data' => array(),
        );
        // 将数据json格式化成变量
        $goods = json_decode($param_post,true);

        foreach($goods as $param_k => $param_v){
            $$param_k = $param_v;
        }

        if (!empty($goods_id) && empty($param_post))
        {
            if (!is_numeric($goods_id) || intval($goods_id) <= 0)
            {
                $return['message'] = '参数错误';
                return $return;
            }
            exit;
        }

        /* 判断是否为正在预售的商品 */
        if(!isset($extCode) || $extCode != 'pre_sale')
        {
            $pre_sale_id = is_pre_sale_goods($goods_id);
            if($pre_sale_id != null)
            {
                /* 进入收货人页面 */
                $uri = build_uri("pre_sale", array("pre_sale_id" => $pre_sale_id));
                $result['uri'] = $uri;
                $return['message'] = '此商品为预售商品，点击确定按钮将跳转到预售商品详情页面！';
                $return['data']    = $result;
                return $return;
//                $this->error('',500,$result);
            }
        }

        //判断拼团
        if($flow_type == 102){
            //跟别人拼单
            if($group_log_id > 0){
                //判断拼单状态
                $log_info = $GLOBALS['db']->getRow("SELECT * FROM " . $GLOBALS['ecs']->table('group_log') . " WHERE id = '$group_log_id' ");

                if(!empty($log_info)){
                    if($log_info['is_finish'] > 0){
                        $return['message'] = '该拼单已结束';
                        return $return;
                    }

                    //获取拼团活动信息
                    $time = gmtime();
                    $group_info = $GLOBALS['db']->getRow("SELECT * FROM " . $GLOBALS['ecs']->table('group_activity') . " WHERE id = '$log_info[group_id]' ");

                    if(empty($group_info)){
                        $return['message'] = '该拼单异常，请稍后再操作！';
                        return $return;
                    }

                    //判断拼单人数要求
                    $log_num = $GLOBALS['db']->getOne("SELECT count(*) FROM " . $GLOBALS['ecs']->table('group_log') . " WHERE parent_id = '$group_log_id' ");
                    $log_num = $log_num +1;//加上拼主记录条数
                    //判断拼团是否够人
                    if($log_num >= $group_info['group_num']){
                        $return['message'] = '该拼单已满人了';
                        return $return;
                    }
                }else{
                    $return['message'] = '该拼单没有拼主，请重新拼单';
                    return $return;
                }

            }
        }

        $time_xg_now = gmtime();

        // 查询该商品是否在限购范围内
        $sql = "select is_buy,buymax, buymax_start_date, buymax_end_date from ". $this->_tb_goods ." where goods_id='".$goods_id."' ";
        $row_xg= $this->_db->getRow($sql);

        if ( $row_xg['is_buy'] == 1 && $row_xg['buymax'] >0 && $row_xg['buymax_start_date'] < $time_xg_now  && $row_xg['buymax_end_date'] > $time_xg_now  )
        {
            if ($user_id == 0 ){
                $return['message'] = '此商品为限购商品，超出限购数量';
                return $return;
//                $this->error('此商品为限购商品，超出限购数量');
            }else{

                $sql_where = $user_id > 0 ? "user_id='". $user_id ."' " : " AND user_id=0 ";

                $num_cart_old_1=$GLOBALS['db']->getOne("select sum(goods_number) from ". $this->_tb_cart ." where " . $sql_where . " and goods_id = " . $goods_id );
                $num_cart_old_2=$GLOBALS['db']->getOne("select sum(og.goods_number) from ". $this->_tb_order_goods ." AS og , ". $this->_tb_order_info ." AS o where o.user_id='$_SESSION[user_id]' and  o.order_id = og.order_id and add_time > ". $row_xg['buymax_start_date'] ." and add_time < ". $row_xg['buymax_end_date'] ."  and og.goods_id = " . $goods_id );
                if ($quick != 1){
                    $num_cart_old = $num_cart_old_1 + $num_cart_old_2 ;
                }else{
                    $num_cart_old =  $num_cart_old_2 ;
                }
                $num_total = $num_cart_old +  intval($number);

                if ( $num_total > intval($row_xg['buymax']) )
                {
                    $return['error']   = 888;
                    if ($quick != 1){
                        $num_else=intval($row_xg['buymax'])-$num_cart_old_2-$num_cart_old_1;
                    }else{
                        $num_else=intval($row_xg['buymax'])-$num_cart_old_2;
                    }
                    $return['message'] ="注意：\n\r此商品限购期间每人限购 ". $row_xg['buymax'] . " 件\n\r";
                    if ($num_cart_old_2 > 0)
                    {
                        $return['message'] .="您在限购期间已经成功购买过". $num_cart_old_2 ." 件！\n\r";
                    }
                    if ($quick == 1){

                        $return['message'] .= "您只能再买 ". $num_else ." 件";
                    }else{
                        if ($num_cart_old_1 > 0)
                        {
                            $return['message'] .="您的购物车中已经存在". $num_cart_old_1 ."件！\n\r";
                        }
                        $return['message'] .= "您只能再添加 ". $num_else ." 件";
                        return $return;
                    }

                    $return['message'] = '此商品为限购商品，超出限购数量';
                    return $return;

//                    $this->error( $result['message']);

                }
            }

        }

        /* 检查：如果商品有规格，而post的数据没有规格，把商品的规格属性通过JSON传到前台 */
        if (empty($spec))
        {
            $sql = "SELECT a.attr_id, a.attr_name, a.attr_type, ".
                "g.goods_attr_id, g.attr_value, g.attr_price " .
                'FROM ' . $this->_tb_goods_attr . ' AS g ' .
                'LEFT JOIN ' . $this->_tb_attribute . ' AS a ON a.attr_id = g.attr_id ' .
                "WHERE a.attr_type != 0 AND g.goods_id = '" . $goods_id . "' " .
                'ORDER BY a.sort_order, g.attr_price, g.goods_attr_id';

            $res = $this->_db->getAll($sql);

            if (!empty($res))
            {
                $spe_arr = array();
                foreach ($res AS $row)
                {
                    $spe_arr[$row['attr_id']]['attr_type'] = $row['attr_type'];
                    $spe_arr[$row['attr_id']]['name']     = $row['attr_name'];
                    $spe_arr[$row['attr_id']]['attr_id']     = $row['attr_id'];
                    $spe_arr[$row['attr_id']]['values'][] = array(
                        'label'        => $row['attr_value'],
                        'price'        => $row['attr_price'],
                        'format_price' => price_format($row['attr_price'], false),
                        'id'           => $row['goods_attr_id']);
                }
                $i = 0;
                $spe_array = array();
                foreach ($spe_arr AS $row)
                {
                    $spe_array[]=$row;
                }
                $result['error']   = ERR_NEED_SELECT_ATTR;
                $result['goods_id'] = $goods['goods_id'];
                $result['parent'] = $goods['parent'];
                $result['message'] = $spe_array;

                $return['message'] = '请先选择规格属性！';
                $return['data']  = $result['message'];
                Response::render($result['message'], 500, $return['message']);
                exit();
                //return $return;

//                $this->error($result['error'],500,$result);

//                die($json->encode($result));
            }
        }

        /*if($quick == 1){
            $sql = "DELETE FROM " . $GLOBALS['ecs']->table('cart') ." WHERE goods_id = ".$goods_id ." and user_id = ".$user_id;
            $GLOBALS['db']->query($sql);
        }*/

        if($quick == 1 && $goods['is_design'] == 0){
            if (!empty($spec)) {
                $goods_attr_id = join(',', $spec);
                $sql = "DELETE FROM " . $GLOBALS['ecs']->table('cart') ." WHERE goods_id = '$goods_id' and user_id = '$user_id' and goods_attr_id = '$goods_attr_id'";
            } else {
                $sql = "DELETE FROM " . $GLOBALS['ecs']->table('cart') ." WHERE goods_id = ".$goods_id ." and user_id = ".$user_id;
            }
            $GLOBALS['db']->query($sql);
        }

        /* 更新：如果是一步购物，先清空购物车 */
        //if ($GLOBALS['_CFG']['one_step_buy'] == '1')
//        if ($quick == 1)
//        {
            //根据预售已改
//            clear_cart(null,'',$user_id);
//        }

        /* 检查：商品数量是否合法 */
        if (!is_numeric($number) || intval($number) <= 0)
        {
            $return['message'] = '商品数量不合法';
            return $return;

//            $this->error($_LANG['invalid_number']);
        }
        /* 更新：购物车 */
        else
        {
            if(!empty($goods->spec))
            {
                foreach ($goods->spec as  $key=>$val )
                {
                    $goods->spec[$key]=intval($val);
                }
            }

            //2018.1.30 创建扩展数组，方便传值
            $extendArray = array();
            $extendArray['group_log_id'] = isset($group_log_id) ? $group_log_id : 0;//拼团活动，去拼单的group_lod表的ID


            // 更新：添加到购物车
            if (addto_cart($goods_id, $goods['number'] , $spec, $parent, $user_rank_info,$flow_type,$extendArray,$goods['is_design']))
            {

//                if ($GLOBALS['_CFG']['cart_confirm'] > 2)
//                {
//                    $result['message'] = '';
//                }
//                else
//                {
//                    $result['message'] = $GLOBALS['_CFG']['cart_confirm'] == 1 ? $GLOBALS['_LANG']['addto_cart_success_1'] : $GLOBALS['_LANG']['addto_cart_success_2'];
//                }

//                $result['content'] = insert_cart_info();
                $result['one_step_buy'] = $quick;

                $return['message'] = '购物车更新成功';
                $return['data'] = $result;
                $return['code'] = 200;

                return $return;

            }
            else
            {
                $last_message = $GLOBALS['err']->last_message();
                $result['message']  = @$last_message[0];
                $result['goods_id'] = stripslashes($goods_id);
                if (is_array($spec))
                {
                    $result['product_spec'] = implode(',', $spec);
                }
                else
                {
                    $result['product_spec'] = $spec;
                }

                $return['message'] = $result['message'];
                $return['data'] = $result;

                return $return;

            }
        }

    }


	//test

	public function get_cart_goods($user_rank_info = array(), $other='')
    {
        $user_id = $user_rank_info['user_id'];

        //同步购物车中的商品价格 修复促销价过后不会自动恢复原价问题
        $get_cart_sql = "SELECT rec_id,goods_id,goods_attr_id,market_price,is_design FROM ". $this->_tb_cart . " WHERE user_id=".$user_id." and is_real = 1 and is_gift = 0 and extension_code = '' ";
        $get_cart = $this->_db->getAll($get_cart_sql);
        foreach ($get_cart as $k => $v) {
            $attr_id = empty($v['goods_attr_id']) ? array() : explode(',', $v['goods_attr_id']);
            $price = $this->get_final_price_api($v['goods_id'], 1, true, $attr_id, $user_rank_info, $v['is_design']);
            $this->_db->query("update ".$this->_tb_cart." set market_price='".$v['market_price']."',goods_price='".$price."' where rec_id=".$v['rec_id']);
        }

        /* 初始化 */
        $goods_list = array();
        $total = array(
            'goods_price'  => 0, // 本店售价合计（有格式）
            'market_price' => 0, // 市场售价合计（有格式）
            // 'saving'       => 0, // 节省金额（有格式）
            // 'save_rate'    => 0, // 节省百分比
            'goods_amount' => 0, // 本店售价合计（无格式）
        );

        /* 循环、统计 */
        $sql_where = "c.user_id='". $user_id ."' ";
        $sql = "SELECT c.*, g.cat_id, g.brand_id, g.goods_status, g.is_on_sale, g.goods_number AS goods_stock, g.goods_total, IF(ga.act_id, ga.supplier_id, g.supplier_id) as supplier_id, IF(c.parent_id, c.parent_id, c.goods_id) AS pid  " .
            " FROM " . $this->_tb_cart . " AS c left join " . $this->_tb_goods." AS g ".
            " on c.goods_id=g.goods_id ".
            " left join " . $this->_tb_goods_activity . " as ga " .
            " on ga.act_id = c.goods_id and c.extension_code = 'package_buy'" .
            //" on ga.act_id = c.goods_id " .
            " WHERE $sql_where $other " .
            " ORDER BY pid, c.parent_id";
        //echo $sql;die;
        $res = $this->_db->query($sql);
        $supplier_list =array();
        /* 取得优惠活动*/
        $favourable_list = $this->favourable_list($user_rank_info);
        /* 用于统计购物车中实体商品和虚拟商品的个数 */
        $virtual_goods_count = 0;
        $real_goods_count    = 0;

        while ($row = $this->_db->fetchRow($res))
        {
            $row['invalid'] = 0;
            // 判断是否无效商品（出售中的商品已下架、库存不足）
            if ($row['goods_status'] == GS_DEFAULT || $row['goods_status'] == GS_PASS) {
                if ($row['goods_number'] > $row['goods_stock'] || $row['is_on_sale'] == 0) {
                    $row['invalid'] = 1;
                }
            }

            $total['goods_price']  += $row['goods_price'] * $row['goods_number'];
            $total['market_price'] += $row['market_price'] * $row['goods_number'];

            $total['goods_number'] += $row['goods_number'];
            $row['subtotal']     = $row['goods_price'] * $row['goods_number'];
            $row['format_subtotal']     = price_format($row['subtotal'], false);
            $row['format_goods_price']  = price_format($row['goods_price'], false);
            $row['format_market_price'] = price_format($row['market_price'], false);
            $row['goods_attr'] = str_replace("\n", " ", $row['goods_attr']);

            /* 统计实体商品和虚拟商品的个数 */
            if ($row['is_real'])
            {
                $real_goods_count++;
            }
            else
            {
                $virtual_goods_count++;
            }

            /* 查询规格 */
            if (trim($row['goods_attr']) != '')
            {
                $row['goods_attr'] = addslashes($row['goods_attr']);
                $sql = "SELECT attr_value FROM " . $this->_tb_goods_attr . " WHERE goods_attr_id " .
                    db_create_in($row['goods_attr']);
                $attr_list = $this->_db->getCol($sql);
                foreach ($attr_list AS $attr)
                {
                    $row['goods_name'] .= ' [' . $attr . '] ';
                }
            }
            $goods_attr_id = explode(',', $row['goods_attr_id']);
            /* 增加是否在购物车里显示商品图 */
            if (($GLOBALS['_CFG']['show_goods_in_cart'] == "2" || $GLOBALS['_CFG']['show_goods_in_cart'] == "3") && $row['extension_code'] != 'package_buy')
            {
                // 获取属性商品图片
                $goods_thumb = $this->_db->getOne("SELECT img_url FROM " . $this->_tb_goods_gallery . " WHERE goods_attr_id = '$goods_attr_id[0]' AND goods_attr_id2 = '$goods_attr_id[1]'");
                if (!empty($goods_thumb)) {
                    $row['goods_thumb']  = get_image_path($row['goods_id'], $goods_thumb, true);
                } else {
                    $goods_thumb = $this->_db->getOne("SELECT `goods_thumb` FROM " . $this->_tb_goods . " WHERE `goods_id`='{$row['goods_id']}'");
                    $row['goods_thumb'] = get_image_path($row['goods_id'], $goods_thumb, true);
                }
            }
            if ($row['extension_code'] == 'package_buy')
            {
                $row['package_goods_list'] = get_package_goods($row['goods_id'], $row['package_attr_id'] );
				$row['goods_thumb'] = 'mobile/themesmobile/default/images/flow/libao.png';
            }

            $row['is_cansel'] = is_cansel($row['goods_id'], $row['product_id'], $row['extension_code']);

            // 获取款式图片
            $style_attr = $this->_db->getRow("SELECT attr_id, attr_value FROM " . $this->_tb_goods_attr . " WHERE goods_attr_id = '$goods_attr_id[0]' AND goods_id = '$row[goods_id]'");
            $row['attr_icon'] = $this->_db->getOne("SELECT default_icon FROM " . $this->_tb_attribute_icon . " WHERE attr_id = '$style_attr[attr_id]' AND attr_value_name = '$style_attr[attr_value]'");

            // 获取颜色值
            $color_attr = $this->_db->getRow("SELECT attr_id, attr_value FROM " . $this->_tb_goods_attr . " WHERE goods_attr_id = '$goods_attr_id[1]' AND goods_id = '$row[goods_id]'");
            $row['attr_color'] = $this->_db->getOne("SELECT color_code FROM " . $this->_tb_attribute_color . " WHERE attr_id = '$color_attr[attr_id]' AND color_name = '$color_attr[attr_value]'");

            // 获取尺码
            $row['attr_size'] = $this->_db->getOne("SELECT attr_value FROM " . $this->_tb_goods_attr . " WHERE goods_attr_id = '$goods_attr_id[2]' AND goods_id = '$row[goods_id]'");

            // 是否收藏
            $row['is_collected'] = (string)$this->is_collected($row['goods_id'], $user_id);

            $keyname = $row['supplier_id'] ? $row['supplier_id'] : '0' ;

            $row['subtotal']  = (string)$row['subtotal'];

            $supplier_id = $row['supplier_id'] ? $row['supplier_id'] : '0' ;

            unset($row['supplier_id']);
            unset($row['exclusive']);
            unset($row['package_attr_id']);
            // unset($row['goods_attr_id']);
            unset($row['can_handsel']);
            unset($row['rec_type']);
            unset($row['extension_code']);
            unset($row['split_money']);
            unset($row['session_id']);
            unset($row['parent_id']);
            unset($row['cat_id']);
            unset($row['brand_id']);
            unset($row['cost_price']);
            unset($row['add_time']);
            unset($row['pid']);

            // $goods_list[$keyname] = $row;
            // $goods_list[$keyname]['supplier_name'] = $supplier_name;

            $sql_supplier = "SELECT supplier_id,supplier_name FROM ". $GLOBALS['ecs']->table("supplier") . " WHERE supplier_id=".$supplier_id." AND status=1";
            $shopuserinfo = $this->_db->getRow($sql_supplier);

			//print_r($favourable_list);
			//自动添加购物车
			if($favourable_list){
				$new_fav = array();
				foreach($favourable_list as $key => $val){
					switch($val['act_range']){
						case 0:
							$ids = array();
							break;
						case 1:
							$children = get_children($val['act_range_ext']);
							$ids = $this->category_get_goods($user_rank_info,$children);
							break;
						case 2:
							$children = get_children($val['act_range_ext']);
							$ids = $this->category_get_goods($user_rank_info,'',$val['act_range_ext']);
							break;
						case 3:
							$ids = explode(',',$val['act_range_ext']);//把字符拆分为数组
							break;

					}

					if(in_array($row['goods_id'],$ids) && $row['is_gift']==0){
						$supplier_list[$supplier_id]['favourable'][] = $val;
						$row['favourable_info'][] = $val;
						// foreach($val['gift'] as $v){
						// 	//$this->addFavourable($val['act_id'],array($v['id']),$user_rank_info);  //预先加入购物车 勾选结算。。
						// }
					}
				}
			}

            $supplier_list[$supplier_id]['favourable'] = isset($supplier_list[$supplier_id]['favourable']) ? $supplier_list[$supplier_id]['favourable'] : array();
            $row['favourable_info'] = isset($row['favourable_info']) ? $row['favourable_info'] : array();

			//赠品在对应的商品下

			//$gift = $this->favourable_info($row['is_gift']);
			//$row['favourable'] = '';
            $supplier_list[$supplier_id]['supplier_id'] = $supplier_id;
            $supplier_list[$supplier_id]['supplier_name'] = $shopuserinfo['supplier_name']?:'商家自营';
            $supplier_list[$supplier_id]['goods_list'][] = $row;
        }
        //$goods_list = array_values(array_map(function($i){$i = array_values($i);return $i;}, $supplier_list));
        //$goods_list = $supplier_list;
        ksort($supplier_list);

        $new_supplier_list = array();
        foreach($supplier_list as $k=>$v){
            //print_r($v);
            $new_supplier_list[] = $v;
        }

        $total['goods_amount'] = $total['goods_price'];
        $total['goods_price']  = (string)$total['goods_price'];
        $total['market_price']  = (string)$total['market_price'];
        $total['goods_amount']  = (string)$total['goods_amount'];
        $total['format_goods_price']  = price_format($total['goods_price'], false);
        $total['real_goods_count']    = (string)$real_goods_count;
        $total['virtual_goods_count'] = (string)$virtual_goods_count;

        return array('supplier_list' => $new_supplier_list, 'total' => $total);
    }

	//test
    /**
     * 取得购物车商品
     * @param   int     $type   类型：默认普通商品
     * @param   int     $user_id   用户ID
     * @param   string  $sel_cart_goods   购物车选中的商品
     *
     * @return  array   购物车商品数组
     */
    public function cart_goods($type = CART_GENERAL_GOODS, $user_id = 0, $sel_cart_goods = '')
    {
        $id_ext = '';
        if ($sel_cart_goods)
        {
            $id_ext = " AND c.rec_id in (". $sel_cart_goods .") ";
        }
        $sql_where = "c.user_id='". $user_id ."' ";
        $sql = "SELECT c.rec_id, c.user_id, c.goods_id, c.goods_name, c.goods_sn, c.goods_number, c.market_price, c.goods_attr_id, c.is_design, " .
            " c.goods_price, c.goods_attr, c.is_real, c.extension_code, c.parent_id, c.is_gift, c.is_shipping, " .
            " package_attr_id, c.goods_price * c.goods_number AS subtotal, " .
            " IF(ga.act_id, ga.supplier_id, g.supplier_id) as supplier_id, " .
            " IF(ga.act_id, IFNULL(ss.supplier_name, '网站自营'), IFNULL(s.supplier_name, '网站自营')) as seller, g.goods_number AS goods_stock, g.goods_total " .
            " FROM " . $this->_tb_cart .
            " as c LEFT JOIN " . $this->_tb_goods . " as g ON c.goods_id = g.goods_id LEFT JOIN ". $GLOBALS['ecs']->table('supplier') .
            " as s ON s.supplier_id = g.supplier_id " .
            " left join " . $GLOBALS['ecs']->table('goods_activity') . " as ga " .
            " on ga.act_id = c.goods_id and c.extension_code = 'package_buy'" .
            " left join " . $GLOBALS['ecs']->table('supplier') . " as ss on ss.supplier_id = ga.supplier_id " .
            " WHERE $sql_where " .
            " AND c.rec_type = '$type' $id_ext ";

        $arr = $GLOBALS['db']->getAll($sql);

        /* 格式化价格及礼包商品 */
        foreach ($arr as $key => $value)
        {
            $arr[$key]['formated_market_price'] = price_format($value['market_price'], false);
            $arr[$key]['formated_goods_price']  = price_format($value['goods_price'], false);
            $arr[$key]['formated_subtotal']     = price_format($value['subtotal'], false);

            $arr[$key]['goods_attr'] = str_replace("\n", " ", $value['goods_attr']);

            // 获取属性商品图片
            $goods_attr_id = explode(',', $value['goods_attr_id']);
            $goods_thumb = $this->_db->getOne("SELECT img_url FROM " . $this->_tb_goods_gallery . " WHERE goods_attr_id = '$goods_attr_id[0]' AND goods_attr_id2 = '$goods_attr_id[1]'");
            if (!empty($goods_thumb)) {
                $arr[$key]['goods_thumb'] = get_image_path($value['goods_id'], $goods_thumb, true);
            } else {
                $arr[$key]['goods_thumb'] = $GLOBALS['db']->getOne("SELECT `goods_thumb` FROM " . $this->_tb_goods . " WHERE `goods_id`='{$value['goods_id']}'");
                $arr[$key]['goods_thumb'] = get_image_path($value['goods_id'], $arr[$key]['goods_thumb'], true);
            }

            if ($value['extension_code'] == 'package_buy')
            {
                $arr[$key]['package_goods_list'] = get_package_goods($value['goods_id'], $value['package_attr_id']);
            }

            $goods_attr_id = explode(',', $value['goods_attr_id']);
            // 获取款式图片
            $style_attr = $GLOBALS['db']->getRow("SELECT attr_id, attr_value FROM " . $this->_tb_goods_attr . " WHERE goods_attr_id = '$goods_attr_id[0]' AND goods_id = '$value[goods_id]'");
            $arr[$key]['attr_icon'] = $GLOBALS['db']->getOne("SELECT default_icon FROM " . $this->_tb_attribute_icon . " WHERE attr_id = '$style_attr[attr_id]' AND attr_value_name = '$style_attr[attr_value]'");

            // 获取颜色值
            $color_attr = $this->_db->getRow("SELECT attr_id, attr_value FROM " . $this->_tb_goods_attr . " WHERE goods_attr_id = '$goods_attr_id[1]' AND goods_id = '$value[goods_id]'");
            $arr[$key]['attr_color'] = $GLOBALS['db']->getOne("SELECT color_code FROM " . $this->_tb_attribute_color . " WHERE attr_id = '$color_attr[attr_id]' AND color_name = '$color_attr[attr_value]'");

            // 获取尺码
            $arr[$key]['attr_size'] = $GLOBALS['db']->getOne("SELECT attr_value FROM " . $this->_tb_goods_attr . " WHERE goods_attr_id = '$goods_attr_id[2]' AND goods_id = '$value[goods_id]'");
        }
        return $arr;
    }


    /**
     * 更新购物车中的商品数量
     *
     * @access  public
     * @param   array   user_rank_info        会员等级信息
     * @param   int   rec_id        购物车表ID
     * @param   int   number        商品数量
     * @param   int   goods_id      商品ID
     * @param   int   is_package    是否套餐
     * @param   int   suppid        供应商ID
     * @return  void
     */
    public function updateCart($user_rank_info, $rec_id, $number, $goods_id, $is_package = 0, $suppid = 0, $sel_goods = '', $attr='')
    {
        $_SESSION = $user_rank_info;

        include_once(ROOT_PATH . 'includes/cls_json.php');
        require_once(ROOT_PATH . 'languages/' .$GLOBALS['_CFG']['lang']. '/shopping_flow.php');
        $result = array('code' => 500, 'message' => '', 'content' => '', 'goods_id' => '');

        $user_id = $user_rank_info['user_id'];
        $result['suppid'] = intval($suppid);
        $result['rec_id'] = $rec_id;

        //没有传number的时候默认获取购物车number
        if(empty($number)){
            $sql_where = "user_id='". $user_id ."' ";
            $number = $this->_db->getOne("select goods_number from ". $this->_tb_cart ." where " . $sql_where . " and rec_id= " . $rec_id );
            if(empty($number)){
                $result['message'] = "非法操作！";
                return $result;
            }
        }

        $result['number'] = $number;

        if ($is_package == 0)
        {
            $time_xg_now = gmtime();
            $row_xg = $this->_db->getRow("select is_buy,buymax, buymax_start_date, buymax_end_date from ". $this->_tb_goods ." where goods_id='".$goods_id."' " );
            if ( $row_xg['is_buy'] == 1 && $row_xg['buymax'] >0 && $row_xg['buymax_start_date'] < $time_xg_now  && $row_xg['buymax_end_date'] > $time_xg_now  )
            {
                if ($user_id == 0 )
                {
                    $result['message'] = "此商品为限购商品，超出限购数量";
                    return $result;
                }
                else
                {
                    $sql_where = "user_id='". $user_id ."' ";

                    $num_cart_old_1=$this->_db->getOne("select sum(goods_number) from ". $this->_tb_cart ." where " . $sql_where . " and goods_id= " . $goods_id );
                    $num_cart_old_2=$this->_db->getOne("select sum(og.goods_number) from ". $this->_tb_order_goods ." AS og , ". $this->_tb_order_info ." AS o where o.user_id='$_SESSION[user_id]' and  o.order_id = og.order_id and add_time > ". $row_xg['buymax_start_date'] ." and add_time < ". $row_xg['buymax_end_date'] ."  and og.goods_id = " . $goods_id );
                    $num_cart_old = $num_cart_old_1 + $num_cart_old_2 ;
                    //$num_total = $num_cart_old_2 +  intval($number);

                    //判断其他属性的商品库存
                    $rec_number = $this->_db->getOne("select goods_number from ". $this->_tb_cart ." where " . $sql_where . " and rec_id= " . $rec_id );
                    $num_total = $num_cart_old_1 - $rec_number + $num_cart_old_2 +  intval($number);

                    if ( $num_total > intval($row_xg['buymax']) )
                    {
                        $num_else=intval($row_xg['buymax'])-$num_cart_old_2;
                        $result['message'] ="此商品限购期间每人限购 ". $row_xg['buymax'] . " 件\n\r";
                        if ($num_cart_old_2 > 0)
                        {
                            $result['message'] .="您在限购期间已经成功购买过". $num_cart_old_2 ." 件！\n\r";
                        }
                        if ($num_cart_old_1 > 0)
                        {
                            $result['message'] .="您的购物车中已经存在". $num_cart_old_1 ."件！\n\r";
                        }
                        $result['message'] .= "您只能再买 ". $num_else ." 件";
                        $result['number']   = $num_else;
                        return $result;
                    }
                }
            }
        }

        $goods_attr = "";

        $goods_status = $this->_db->getOne("SELECT goods_status FROM " . $this->_tb_goods . " WHERE goods_id = '$goods_id'");
        // 0：后台添加的商品，4：diy商品已出售状态 需要检查库存
        if ($goods_status == GS_DEFAULT || $goods_status == GS_PASS) {
            if ($GLOBALS['_CFG']['use_storage'] == 1)
            {
                $sql_where = " user_id='". $user_id ."' ";
                if ($is_package == 0)
                {
                    $pg_ids = $this->_db->getAll("select goods_id, goods_number from " . $this->_tb_cart . " where extension_code = 'package_buy' and " . $sql_where);
                    $pg_num = 0;
                    foreach($pg_ids as $pg_id)
                    {
                        $pg_num += $this->_db->getOne("select goods_number from " .$this->_tb_package_goods . " where package_id =  " . $pg_id['goods_id'] . " and goods_id = " . $goods_id);
                    }
                    $goods_number = $this->_db->getOne("select goods_number from ".$this->_tb_goods." where goods_id='$goods_id'");
                    $number2 = $number + $pg_num;
                    if($number2>$goods_number) //////// jx      库存判断
                    {
                        $result['error'] = '1';
                        $result['message'] ='对不起,您选择的数量超出库存您最多可购买'.$goods_number."件";
                        $result['message'] ="对不起,此单品超出库存,您最多可购买".$goods_number."件";
                        if ($pg_num > 0)
                        {
                            $result['message'] .= ",礼包中已包含此单品 " . $pg_num . "件";
                        }
                        $result['number']=$this->_db->getOne("select goods_number from ".$this->_tb_cart." where rec_id = '$rec_id'");
                        // die($json->encode($result));
                        return $result;
                    }
                    //添加判断商品有属性的时候的库存   jx
                    /*if(!empty($attr)){
                        $goods_attr_id=$attr;
                    }
                    else{
                        $goods_attr_id = $this->_db->getOne("SELECT goods_attr_id FROM ".$this->_tb_cart."WHERE rec_id = '$rec_id'");
                    }
                    if($goods_attr_id)
                    {
                        $str = explode(',',$goods_attr_id);//把字符串转换成数组
                        $goods_attr = implode('|',$str);// 把数组转换成以‘|’的字符传
                        $attr_number = $this->_db->getOne("select product_number from ".$this->_tb_products." where goods_id='$goods_id' AND goods_attr ='$goods_attr'");
                        if($number>$attr_number)
                        {
                            $result['error'] = '1';
                            $result['message'] ='对不起,您选择的数量超出库存您最多可购买'.$attr_number."件";
                            $result['number']=$this->_db->getOne("select product_number from ".$this->_tb_products." where goods_id='$goods_id' AND goods_attr ='$goods_attr'");
                            // die($json->encode($result));
                            return $result;
                        }
                    }*/
                }
                else
                {
                    $goods_infos = $this->_db->getAll("select pg.goods_id, pg.goods_number, g.goods_name from " . $this->_tb_package_goods . " as pg left join " . $this->_tb_goods . " as g on pg.goods_id = g.goods_id where package_id='$goods_id'");
                    $is_null_g = 0;
                    foreach($goods_infos as $goods_info)
                    {
                        $one_num = $this->_db->getOne("SELECT SUM(goods_number) FROM " . $this->_tb_cart . " WHERE goods_id = '$goods_info[goods_id]' and " . $sql_where);
                        $number2 = $number * $goods_info['goods_number'] + $one_num;
                        $goods_number = $this->_db->getOne("select goods_number from ".$this->_tb_goods." where goods_id='$goods_info[goods_id]'");

                        if($number2>$goods_number) //////// jx      库存判断
                        {
                            $result['error'] = '1';
                            $result['message'] ="对不起,礼包中单品[" . $goods_info['goods_name'] . "]超出库存,您最多可购买".$goods_number."件";
                            if ($one_num > 0)
                            {
                                $result['message'] .= ",已添加了单品 " . $one_num . "件";
                            }
                            $result['number']=$this->_db->getOne("select goods_number from ".$this->_tb_cart." where rec_id = '$rec_id'");
                            // die($json->encode($result));
                            return $result;
                        }
                        //添加判断商品有属性的时候的库存   jx
                        /*if(!empty($attr)){
                            $goods_attr_id=$attr;
                        }
                        else{
                            $goods_attr_id = $this->_db->getOne("SELECT goods_attr_id FROM ".$this->_tb_cart."WHERE rec_id = '$rec_id'");
                        }
                        if($goods_attr_id)
                        {
                            $str = explode(',',$goods_attr_id);//把字符串转换成数组
                            $goods_attr = implode('|',$str);// 把数组转换成以‘|’的字符传
                            $attr_number = $this->_db->getOne("select product_number from ".$this->_tb_products." where goods_id='$goods_id' AND goods_attr ='$goods_attr'");
                            if($number>$attr_number)
                            {
                                $result['error'] = '1';
                                $result['message'] ='对不起,您选择的数量超出库存您最多可购买'.$attr_number."件";
                                $result['number']=$this->_db->getOne("select product_number from ".$this->_tb_products." where goods_id='$goods_id' AND goods_attr ='$goods_attr'");
                                // die($json->encode($result));
                                return $result;
                            }
                        }*/
                    }
                }
            }
        }

        //更改属性~~
        if(!empty($attr)){
            $goods_attr_id = $attr;
        } else{
            $goods_attr_id = $this->_db->getOne("SELECT goods_attr_id FROM ".$this->_tb_cart."WHERE rec_id = '$rec_id'");
        }
        $attr_id_array  = empty($goods_attr_id) ? array() : explode(',', $goods_attr_id);
        if(!empty($attr_id_array)){
            // 0：后台添加的商品，4：diy商品已出售状态 需要检查库存
            /*if ($goods_status == GS_DEFAULT || $goods_status == GS_PASS) {
                //检查属性库存
                $attr_id = implode('|', $attr_id_array);
                $sql = "SELECT product_number FROM  ".$this->_tb_products." WHERE  `goods_attr` LIKE  '".$attr_id."'";
                if($this->_db->getOne($sql)<=0){
                    $result['message'] ='对不起,您选择的属性已经没有库存了';
                    return $result;
                }
            }*/

            // $goods_attr = '';
            // $attr_price = 0;
            // foreach($attr_id_array as $key=>$value){
            //     $sql = "select ga.attr_id, ga.attr_value, ga.attr_price, at.attr_name from ". $this->_tb_goods_attr ." as ga left join ". $this->_tb_attribute ." as at on ga.attr_id = at.attr_id  where ga.goods_attr_id = $value";
            //     $res = $this->_db->getRow($sql);
            //     $goods_attr .= $res['attr_name'].":".$res['attr_value'];
            //     $attr_price = empty($res['attr_price'])?0:$res['attr_price']+$attr_price;
            // }
            // $goods_attr_array = explode('|',$goods_attr);
            // $shop_price  = get_final_price($goods_id, $number, true, $goods_attr_array, $user_rank_info);
            // //$sql = "UPDATE " . $this->_tb_cart . " SET goods_attr = '$goods_attr', goods_attr_id = '$attr_id', goods_price = '$shop_price',goods_number = '$number' where rec_id = $rec_id";
            // $sql = "UPDATE " . $this->_tb_cart . " SET goods_price = '$shop_price',goods_number = '$number' where rec_id = $rec_id";
        }else{
            //$sql = "UPDATE " . $this->_tb_cart . " SET goods_number = '$number' WHERE rec_id = $rec_id";
        }
        $sql = "UPDATE " . $this->_tb_cart . " SET goods_number = '$number', goods_attr_id = '$goods_attr_id' WHERE rec_id = $rec_id";
        $this->_db->query($sql);

        //折扣活动
        $result['your_discount'] = '';
        $discount = $this->compute_discount($result['suppid'], $user_rank_info);
        if(is_array($discount)){
            $favour_name = empty($discount['name']) ? '' : join(',', $discount['name']);
            $result['your_discount'] = sprintf($_LANG['your_discount'], $favour_name, price_format($discount['discount']));
        }

        if ($is_package == 0)
        {
            //如果有优惠价格，获得商品最终价格
            $goods_attr_array = explode('|',$goods_attr);
            $sql = "SELECT is_design FROM " . $this->_tb_cart . " WHERE rec_id= " . $rec_id;
            $is_design = $this->_db->getOne($sql);
            $shop_price  = get_final_price($goods_id, $number, true, $goods_attr_array, $user_rank_info, $is_design);
            $sql = "UPDATE " . $this->_tb_cart . " SET goods_price = '$shop_price' WHERE rec_id = $rec_id";
            $this->_db->query($sql);
        }
        else
        {
            $sql_sp = "SELECT goods_price FROM " . $this->_tb_cart . " WHERE rec_id= " . $rec_id;
            $shop_price = $this->_db->getOne($sql_sp);
        }

        $subtotal = $shop_price * $number;
        $result['goods_price'] = price_format($shop_price, false);

        $result['subtotal'] = price_format($subtotal, false);

        $cart_goods = $this->get_cart_goods($user_rank_info);
        //$cart_goods = get_cart_goods();

        $result['cart_amount_desc'] = $cart_goods['total']['goods_price'];
        $shopping_money = sprintf($_LANG['shopping_money'], $cart_goods['total']['goods_price']);
        $result['market_amount_desc'] = $shopping_money;

        $result['code'] = 200;

        return $result;
    }



    /**
     * 删除购物车中的商品
     *
     * @access  public
     * @param   integer $id
     * @param   array   $user_rank_info
     * @return  void
     */
    public function flow_drop_cart_goods($id, $user_rank_info)
    {

        $user_id = $user_rank_info['user_id'];

        /* 取得商品id */
        $sql = "SELECT * FROM " .$this->_tb_cart. " WHERE rec_id = '$id'";
        $row = $this->_db->getRow($sql);
        if ($row)
        {
            $sql_where = "user_id='". $user_id ."' ";
            //如果是超值礼包
            if ($row['extension_code'] == 'package_buy')
            {
                $sql = "DELETE FROM " . $this->_tb_cart .
                    " WHERE $sql_where " .
                    "AND rec_id = '$id' LIMIT 1";
            }

            //如果是普通商品，同时删除所有赠品及其配件
            elseif ($row['parent_id'] == 0 && $row['is_gift'] == 0)
            {
                /* 检查购物车中该普通商品的不可单独销售的配件并删除 */
                $sql = "SELECT c.rec_id
                    FROM " . $this->_tb_cart . " AS c, " . $this->_tb_group_goods . " AS gg, " . $this->_tb_goods. " AS g
                    WHERE gg.parent_id = '" . $row['goods_id'] . "'
                    AND c.goods_id = gg.goods_id
                    AND c.parent_id = '" . $row['goods_id'] . "'
                    AND c.extension_code <> 'package_buy'
                    AND gg.goods_id = g.goods_id
                    AND g.is_alone_sale = 0";
                $res = $this->_db->query($sql);
                $_del_str = $id . ',';
                while ($id_alone_sale_goods = $this->_db->fetchRow($res))
                {
                    $_del_str .= $id_alone_sale_goods['rec_id'] . ',';
                }
                $_del_str = trim($_del_str, ',');


                if($_del_str){
                    $sql_plus = " rec_id IN ($_del_str) OR ";
                }
                $sql = "DELETE FROM " . $this->_tb_cart .
                    " WHERE $sql_where " .
                    "AND ({$sql_plus} parent_id = '$row[goods_id]' OR is_gift <> 0)";

            }

            //如果不是普通商品，只删除该商品即可
            else
            {

                $sql = "DELETE FROM " . $this->_tb_cart .
                    " WHERE $sql_where " .
                    "AND rec_id = '$id' LIMIT 1";

            }

            $this->_db->query($sql);
        }else{
            $result['code'] = 500;
            $result['message'] = '没有找到你要删除的商品';
            return $result;
        }

        $this->flow_clear_cart_alone($user_rank_info);
    }

    /**
     * 删除购物车中不能单独销售的商品
     *
     * @access  public
     * @param $user_rank_info 会员等级信息 user_id rank_info discount
     * @return  void
     */
    public function flow_clear_cart_alone($user_rank_info)
    {
        $user_id = $user_rank_info['user_id'];
        /* 查询：购物车中所有不可以单独销售的配件 */
        $sql_where = "c.user_id='". $user_id ."' ";


        $sql = "SELECT c.rec_id, gg.parent_id
            FROM " . $this->_tb_cart . " AS c
                LEFT JOIN " . $this->_tb_group_goods . " AS gg ON c.goods_id = gg.goods_id
                LEFT JOIN" . $this->_tb_goods . " AS g ON c.goods_id = g.goods_id
            WHERE $sql_where
            AND c.extension_code <> 'package_buy'
            AND gg.parent_id > 0
            AND g.is_alone_sale = 0";

        $res = $this->_db->query($sql);
        $rec_id = array();
        while ($row = $this->_db->fetchRow($res))
        {
            $rec_id[$row['rec_id']][] = $row['parent_id'];
        }

        if (empty($rec_id))
        {
            return;
        }

        $sql_where = "user_id='". $user_id ."' ";

        /* 查询：购物车中所有商品 */
        $sql = "SELECT DISTINCT goods_id
            FROM " . $this->_tb_cart . "
            WHERE $sql_where
            AND extension_code <> 'package_buy'";

        $res = $this->_db->query($sql);
        $cart_good = array();
        while ($row = $this->_db->fetchRow($res))
        {
            $cart_good[] = $row['goods_id'];
        }

        if (empty($cart_good))
        {
            return;
        }

        /* 如果购物车中不可以单独销售配件的基本件不存在则删除该配件 */
        $del_rec_id = '';
        foreach ($rec_id as $key => $value)
        {
            foreach ($value as $v)
            {
                if (in_array($v, $cart_good))
                {
                    continue 2;
                }
            }

            $del_rec_id = $key . ',';
        }
        $del_rec_id = trim($del_rec_id, ',');

        if ($del_rec_id == '')
        {
            return;
        }

        /* 删除 */

        if($del_rec_id){
            $sql_plus = " AND rec_id IN ($del_rec_id) ";
        }
        $sql = "DELETE FROM " . $this->_tb_cart ."
            WHERE $sql_where
            ".$sql_plus;

        $this->_db->query($sql);
    }


    /**
     * 计算折扣：根据购物车和优惠活动
     * @param int $supplierid  店铺id
     * @param array $user_rank_info  用户等级信息
     * @return  float   折扣
     */
    function compute_discount($supplierid=-1, $user_rank_info = array())
    {
        /* 查询优惠活动 */
        $now = gmtime();
        $user_rank = ',' . $user_rank_info['user_rank'] . ',';
        $sql = "SELECT *" .
            "FROM " . $this->_tb_favourable_activity .
            " WHERE start_time <= '$now'" .
            " AND end_time >= '$now'" .
            " AND CONCAT(',', user_rank, ',') LIKE '%" . $user_rank . "%'" .
            " AND act_type " . db_create_in(array(FAT_DISCOUNT, FAT_PRICE));
        $sql .= ($supplierid >= 0) ? " AND supplier_id=".$supplierid : "";
        $favourable_list = $this->_db->getAll($sql);
        if (!$favourable_list)
        {
            return 0;
        }

        /* 查询购物车商品 */
        $sql_where = "c.user_id='". $user_rank_info['user_id'] ."' " ;

        if ($supplierid >= 0)
        {
            $sql = "SELECT c.goods_id, c.goods_price * c.goods_number AS subtotal, g.cat_id, g.brand_id, " .
                " IF(c.extension_code = 'package_buy', ga.supplier_id, g.supplier_id) AS supplier_id " .
                " FROM " . $this->_tb_cart . " AS c " .
                " LEFT JOIN " . $this->_tb_goods . " AS g " .
                " ON c.goods_id = g.goods_id AND g.supplier_id = " . $supplierid .
                " LEFT JOIN " . $this->_tb_goods_activity . " AS ga " .
                " ON c.goods_id = ga.act_id AND ga.supplier_id = " . $supplierid .
                " WHERE " .$sql_where.
                " AND c.parent_id = 0 " .
                " AND c.is_gift = 0 " .
                " AND rec_type = '" . CART_GENERAL_GOODS . "'";
        }
        else
        {
            $sql = "SELECT c.goods_id, c.goods_price * c.goods_number AS subtotal, g.cat_id, g.brand_id, " .
                " IF(c.extension_code = 'package_buy', ga.supplier_id, g.supplier_id) AS supplier_id " .
                " FROM " . $this->_tb_cart . " AS c " .
                " LEFT JOIN " . $this->_tb_goods . " AS g " .
                " ON c.goods_id = g.goods_id " .
                " LEFT JOIN " . $this->_tb_goods_activity . " AS ga " .
                " ON c.goods_id = ga.act_id " .
                " WHERE " .$sql_where.
                " AND c.parent_id = 0 " .
                " AND c.is_gift = 0 " .
                " AND rec_type = '" . CART_GENERAL_GOODS . "'";
        }
        $sql .= (isset($_SESSION['sel_cartgoods']) && !empty($_SESSION['sel_cartgoods'])) ? " AND c.rec_id in (". $_SESSION['sel_cartgoods'] .") " : "";

        $goods_list = $this->_db->getAll($sql);

        if (!$goods_list)
        {
            return 0;
        }

        /* 初始化折扣 */
        $discount = 0;
        $favourable_name = array();

        /* 循环计算每个优惠活动的折扣 */
        foreach ($favourable_list as $favourable)
        {
            $total_amount = 0;
            if ($favourable['act_range'] == FAR_ALL)
            {
                foreach ($goods_list as $goods)
                {
                    if($favourable['supplier_id'] == $goods['supplier_id']){
                        $total_amount += $goods['subtotal'];
                    }
                }
            }
            elseif ($favourable['act_range'] == FAR_CATEGORY)
            {
                /* 找出分类id的子分类id */
                $id_list = array();
                $raw_id_list = explode(',', $favourable['act_range_ext']);
                foreach ($raw_id_list as $id)
                {
                    $id_list = array_merge($id_list, array_keys(cat_list($id, 0, false)));
                }
                $ids = join(',', array_unique($id_list));

                foreach ($goods_list as $goods)
                {
                    if (strpos(',' . $ids . ',', ',' . $goods['cat_id'] . ',') !== false && $favourable['supplier_id'] == $goods['supplier_id'])
                    {
                        $total_amount += $goods['subtotal'];
                    }
                }
            }
            elseif ($favourable['act_range'] == FAR_BRAND)
            {
                foreach ($goods_list as $goods)
                {
                    if (strpos(',' . $favourable['act_range_ext'] . ',', ',' . $goods['brand_id'] . ',') !== false && $favourable['supplier_id'] == $goods['supplier_id'])
                    {
                        $total_amount += $goods['subtotal'];
                    }
                }
            }
            elseif ($favourable['act_range'] == FAR_GOODS)
            {
                foreach ($goods_list as $goods)
                {
                    if (strpos(',' . $favourable['act_range_ext'] . ',', ',' . $goods['goods_id'] . ',') !== false && $favourable['supplier_id'] == $goods['supplier_id'])
                    {
                        $total_amount += $goods['subtotal'];
                    }
                }
            }
            else
            {
                continue;
            }

            /* 如果金额满足条件，累计折扣 */
            if ($total_amount > 0 && $total_amount >= $favourable['min_amount'] && ($total_amount <= $favourable['max_amount'] || $favourable['max_amount'] == 0))
            {
                if ($favourable['act_type'] == FAT_DISCOUNT)
                {
                    $discount += $total_amount * (1 - $favourable['act_type_ext'] / 100);

                    $favourable_name[] = $favourable['act_name'];
                }
                elseif ($favourable['act_type'] == FAT_PRICE)
                {
                    $discount += $favourable['act_type_ext'];

                    $favourable_name[] = $favourable['act_name'];
                }
            }
        }

        return array('discount' => $discount, 'name' => $favourable_name);
    }



    /**
     * 重新计算购物车中的商品价格：目的是当用户登录时享受会员价格，当用户退出登录时不享受会员价格
     * 如果商品有促销，价格不变
     *
     * @access  public
     * @param  integral  $user_id
     * @param  array  $user_rank_info
     * @return  void
     */
    public function recalculate_price($user_id, $user_rank_info)
    {
        /* 取得有可能改变价格的商品：除配件和赠品之外的商品 */
        $sql = 'SELECT c.rec_id, c.goods_id, c.goods_attr_id, c.is_design, g.market_price, g.promote_price, g.promote_start_date, c.goods_number,'.
            "g.promote_end_date, IFNULL(mp.user_price, g.shop_price * '$_SESSION[discount]') AS member_price ".
            'FROM ' . $this->_tb_cart . ' AS c '.
            'LEFT JOIN ' . $this->_tb_goods . ' AS g ON g.goods_id = c.goods_id '.
            "LEFT JOIN " . $this->_tb_member_price . " AS mp ".
            "ON mp.goods_id = g.goods_id AND mp.user_rank = '" . $_SESSION['user_rank'] . "' ".
            "WHERE user_id = '" .$user_id. "' AND c.parent_id = 0 AND c.is_gift = 0 AND c.goods_id > 0 " .
            "AND c.rec_type = '" . CART_GENERAL_GOODS . "' AND c.extension_code <> 'package_buy'";

        $res = $this->_db->getAll($sql);

        foreach ($res AS $row)
        {
            $attr_id    = empty($row['goods_attr_id']) ? array() : explode(',', $row['goods_attr_id']);


            $goods_price = get_final_price($row['goods_id'], 1, true, $attr_id, $user_rank_info, $row['is_design']);


            $goods_sql = "UPDATE " .$this->_tb_cart. " SET market_price = '".$row['market_price']."', goods_price = '$goods_price' ".
                "WHERE goods_id = '" . $row['goods_id'] . "' AND user_id = '" . $user_id . "' AND rec_id = '" . $row['rec_id'] . "'";

            $this->_db->query($goods_sql);
        }

        $time1=local_strtotime('today');
        $time2=local_strtotime('today') + 86400;
        $sql = "select rec_id,goods_id,goods_attr,goods_attr_id,goods_number ".
            " from ". $this->_tb_cart  ." where user_id=0 ".
            " AND user_id = '" .$user_id. "' AND parent_id = 0 ".
            " AND is_gift = 0 AND goods_id > 0 " .
            "AND rec_type = '" . CART_GENERAL_GOODS . "' ";
        $res = $this->_db->query($sql);
        while ($row = $this->_db->fetchRow($res))
        {
            $sql = "select rec_id from ".$this->_tb_cart." where user_id='". $user_id ."' ".
                //" AND add_time >='$time1' and add_time<'$time2' ".
                " AND goods_id='$row[goods_id]' and goods_attr_id= '$row[goods_attr_id]' ";
            $rec_id = $this->_db->getOne($sql);
            if($rec_id)
            {
                $sql = "update ".$this->_tb_cart." set goods_number= goods_number + ".$row['goods_number'].
                    " where rec_id='$rec_id' ";
                $this->_db->query($sql);
                $sql="delete from ".$this->_tb_cart." where rec_id='".$row['rec_id']."'";
                $this->_db->query($sql);
                //app功能修改
                $_SESSION['rec_id'][$row['rec_id']] = $rec_id;
            }
            else
            {
                $sql = "update ".$this->_tb_cart." set user_id='$user_id' ".
                    " where rec_id='".$row['rec_id']."'";
                $this->_db->query($sql);
            }
        }

        /* 删除赠品，重新选择 */
        $this->_db->query('DELETE FROM ' . $this->_tb_cart .
            " WHERE user_id = '" . $user_id . "' AND is_gift > 0");
    }


    /**
     * 检查订单中商品库存
     *
     * @access  public
     * @param   array   $user_rank_info
     * @param   array   $arr
     *
     * @return  void
     */
    public function flow_cart_stock($user_rank_info, $arr)
    {
        $user_id = $user_rank_info['user_id'];

        foreach ($arr AS $key => $val)
        {
            $val = intval(make_semiangle($val));
            if ($val <= 0 || !is_numeric($key))
            {
                continue;
            }

            $sql_where = "user_id='". $user_id ."' ";
            $sql = "SELECT `goods_id`, `goods_attr_id`, `extension_code` FROM" .$this->_tb_cart.
                " WHERE rec_id='$key' AND $sql_where";
            $goods = $this->_db->getRow($sql);


            $sql = "SELECT g.goods_name, g.goods_number, c.product_id ".
                "FROM " .$this->_tb_goods. " AS g, ".
                $this->_tb_cart. " AS c ".
                "WHERE g.goods_id = c.goods_id AND c.rec_id = '$key'";
            $row = $this->_db->getRow($sql);

            //系统启用了库存，检查输入的商品数量是否有效
            if (intval($GLOBALS['_CFG']['use_storage']) > 0 && $goods['extension_code'] != 'package_buy')
            {
                if ($row['goods_number'] < $val)
                {
                    $message = sprintf($GLOBALS['_LANG']['stock_insufficiency'], $row['goods_name'],
                        $row['goods_number'], $row['goods_number']);
                    return $message;
                }

                /* 是货品 */
                $row['product_id'] = trim($row['product_id']);
                if (!empty($row['product_id']))
                {
                    $sql = "SELECT product_number FROM " .$this->_tb_products. " WHERE goods_id = '" . $goods['goods_id'] . "' AND product_id = '" . $row['product_id'] . "'";
                    $product_number = $this->_db->getOne($sql);
                    if ($product_number < $val)
                    {
                        $message = sprintf($GLOBALS['_LANG']['stock_insufficiency'], $row['goods_name'],
                            $row['goods_number'], $row['goods_number']);
                        return $message;
                    }
                }
            }
            elseif (intval($GLOBALS['_CFG']['use_storage']) > 0 && $goods['extension_code'] == 'package_buy')
            {
                if (judge_package_stock($goods['goods_id'], $val))
                {
                    $message = $GLOBALS['_LANG']['package_stock_insufficiency'];
                    return $message;
                }
            }
        }

    }


    /**
     * 取得购物车总金额
     * @param   array   $user_rank_info 会员等级信息
     * @params  boolean $include_gift   是否包括赠品
     * @param   int     $type           类型：默认普通商品
     * @return  float   购物车总金额
     */
    public function cart_amount($user_rank_info, $include_gift = true, $type = CART_GENERAL_GOODS)
    {
        $sql_where = "user_id='". $user_rank_info['user_id'] ."' ";
        $sql = "SELECT SUM(goods_price * goods_number) " .
            " FROM " . $this->_tb_cart .
            " WHERE $sql_where " .
            "AND rec_type = '$type' ";

        if (!$include_gift)
        {
            $sql .= ' AND is_gift = 0 AND goods_id > 0';
        }

        return floatval($this->_db->getOne($sql));
    }


	public function cart_favourable($id)
	{
		$sql_where =" user_id=". $id ;
		$list = array();
		$sql = "SELECT is_gift, COUNT(*) AS num " .
				"FROM " . $GLOBALS['ecs']->table('cart') .
				" WHERE $sql_where " .
				" AND rec_type = '" . CART_GENERAL_GOODS . "'" .
				" AND is_gift > 0" .
				" GROUP BY is_gift";
		$res = $GLOBALS['db']->query($sql);
		while ($row = $GLOBALS['db']->fetchRow($res))
		{
			$list[$row['is_gift']] = $row['num'];
		}

		return $list;
	}

	public function favourable_list($user_rank_info,$is_have=true)
	{
		/* 购物车中已有的优惠活动及数量 */
		$used_list = $this->cart_favourable($user_rank_info['user_id']);
		$user_rank = $user_rank_info['user_rank'];
		/* 当前用户可享受的优惠活动 */
		$favourable_list = array();
		$user_rank = ',' . $user_rank . ',';
		$now = gmtime();
		if(isset($_REQUEST['suppid'])){
			$tj = " AND supplier_id=".$_REQUEST['suppid'];
		}else{
			$tj = '';
		}
		$sql = "SELECT * " .
				"FROM " . $GLOBALS['ecs']->table('favourable_activity') .
				" WHERE CONCAT(',', user_rank, ',') LIKE '%" . $user_rank . "%'" .
				" AND start_time <= '$now' AND end_time >= '$now'" .$tj.
				" AND act_type = '" . FAT_GOODS . "'" .
				" ORDER BY sort_order";
		$res = $GLOBALS['db']->query($sql);
		while ($favourable = $GLOBALS['db']->fetchRow($res))
		{
			$favourable['start_time'] = local_date($GLOBALS['_CFG']['time_format'], $favourable['start_time']);
			$favourable['end_time']   = local_date($GLOBALS['_CFG']['time_format'], $favourable['end_time']);
			$favourable['formated_min_amount'] = price_format($favourable['min_amount'], false);
			$favourable['formated_max_amount'] = price_format($favourable['max_amount'], false);
			$favourable['gift']       = unserialize($favourable['gift']);
			$_REQUEST['suppid'] = $favourable['supplier_id'] = $favourable['supplier_id'];

			foreach ($favourable['gift'] as $key => $value)
			{
				$favourable['gift'][$key]['formated_price'] = price_format($value['price'], false);

				$goods_thumb = $GLOBALS['db']->getOne("SELECT `goods_thumb` FROM " . $GLOBALS['ecs']->table('goods') . " WHERE `goods_id`='{$value['id']}'");
				$favourable['gift'][$key]['goods_thumb'] = get_image_path($value['id'], $goods_thumb, true);
				$sql = "SELECT COUNT(*) FROM " . $GLOBALS['ecs']->table('goods') . " WHERE is_on_sale = 1 AND goods_id = ".$value['id'];
				$is_sale = $GLOBALS['db']->getOne($sql);
				if(!$is_sale)
				{
					unset($favourable['gift'][$key]);
				}
			}

			$favourable['act_range_desc'] = $this->act_range_desc($favourable);
			//$favourable['act_type_desc'] = sprintf($GLOBALS['_LANG']['fat_ext'][$favourable['act_type']], $favourable['act_type_ext']);

			/* 是否能享受 */
			$favourable['available'] = $this->favourable_available($favourable,$user_rank_info);
			if ($favourable['available'] && $is_have)
			{
				/* 是否尚未享受 */
				$favourable['available'] = !$this->favourable_used($favourable, $used_list);
			}

			$favourable_list[] = $favourable;
		}

		return $favourable_list;
	}

	public function act_range_desc($favourable)
	{
		if ($favourable['act_range'] == FAR_BRAND)
		{
			$sql = "SELECT brand_name FROM " . $GLOBALS['ecs']->table('brand') .
					" WHERE brand_id " . db_create_in($favourable['act_range_ext']);
			return join(',', $GLOBALS['db']->getCol($sql));
		}
		elseif ($favourable['act_range'] == FAR_CATEGORY)
		{
			$sql = "SELECT cat_name FROM " . $GLOBALS['ecs']->table('category') .
					" WHERE cat_id " . db_create_in($favourable['act_range_ext']);
			return join(',', $GLOBALS['db']->getCol($sql));
		}
		elseif ($favourable['act_range'] == FAR_GOODS)
		{
			$sql = "SELECT goods_name FROM " . $GLOBALS['ecs']->table('goods') .
					" WHERE goods_id " . db_create_in($favourable['act_range_ext']);
			return join(',', $GLOBALS['db']->getCol($sql));
		}
		else
		{
			return '';
		}
	}

	public function favourable_available($favourable,$user_rank_info)
	{
		$user_rank = $user_rank_info['user_rank'];
		/* 会员等级是否符合 */
		if (strpos(',' . $favourable['user_rank'] . ',', ',' . $user_rank . ',') === false)
		{
			return false;
		}

		/* 优惠范围内的商品总额 */
		$amount = $this->cart_favourable_amount($favourable,$user_rank_info);//return $amount;

		/* 金额上限为0表示没有上限 */
		return $amount >= $favourable['min_amount'] &&
			($amount <= $favourable['max_amount'] || $favourable['max_amount'] == 0);
	}

	public function favourable_used($favourable, $cart_favourable)
	{
		if ($favourable['act_type'] == FAT_GOODS)
		{
			return isset($cart_favourable[$favourable['act_id']]) &&
				$cart_favourable[$favourable['act_id']] >= $favourable['act_type_ext'] &&
				$favourable['act_type_ext'] > 0;
		}
		else
		{
			return isset($cart_favourable[$favourable['act_id']]);
		}
	}

	public function cart_favourable_amount($favourable,$user_rank_info)
	{
		$sql_where =  "c.user_id='". $user_rank_info['user_id'] ."' " ;
		/* 查询优惠范围内商品总额的sql */
		$sql = "SELECT SUM(c.goods_price * c.goods_number) " .
				"FROM " . $GLOBALS['ecs']->table('cart') . " AS c, " . $GLOBALS['ecs']->table('goods') . " AS g " .
				"WHERE c.goods_id = g.goods_id " .
				"AND $sql_where " .
				"AND c.rec_type = '" . CART_GENERAL_GOODS . "' " .
				"AND g.supplier_id=".$favourable['supplier_id']." ".
				"AND c.is_gift = 0 " .
				"AND c.goods_id > 0 ";

		/* 根据优惠范围修正sql */
		if ($favourable['act_range'] == FAR_ALL)
		{
			// sql do not change
		}
		elseif ($favourable['act_range'] == FAR_CATEGORY)
		{
			/* 取得优惠范围分类的所有下级分类 */
			$id_list = array();
			$cat_list = explode(',', $favourable['act_range_ext']);
			foreach ($cat_list as $id)
			{
				$id_list = array_merge($id_list, array_keys(cat_list(intval($id), 0, false)));
			}

			$sql .= "AND g.cat_id " . db_create_in($id_list);
		}
		elseif ($favourable['act_range'] == FAR_BRAND)
		{
			$id_list = explode(',', $favourable['act_range_ext']);

			$sql .= "AND g.brand_id " . db_create_in($id_list);
		}
		else
		{
			$id_list = explode(',', $favourable['act_range_ext']);

			$sql .= "AND g.goods_id " . db_create_in($id_list);
		}

		//$sql .= (isset($_REQUEST['sel_goods']) && !empty($_REQUEST['sel_goods'])) ? " AND c.rec_id in (". $_REQUEST['sel_goods'] .") " : "";

		//计算某个店铺的商品总额
		if(isset($_REQUEST['suppid'])){
			$sql .= " AND g.supplier_id=".intval($_REQUEST['suppid']);
		}
		//echo $sql;

		/* 优惠范围内的商品总额 */
		return $GLOBALS['db']->getOne($sql);
	}

	public function category_get_goods($user_rank_info,$children='', $brand = 0, $min = 0, $max = 0, $ext = '', $size = 0, $page = 0, $sort='sort_order', $order= 'desc', $is_stock = 0)
	{
		$filter = (isset($_REQUEST['filter'])) ? intval($_REQUEST['filter']) : 0;

		if(!empty($children)){
			$where = "g.is_on_sale = 1 AND g.is_alone_sale = 1 AND ".
				"g.is_delete = 0 AND ($children OR " . get_extension_goods($children) . ')';
		}else{
			$where = " 1 ";
		}
		if($filter==1){

			$where .= ' AND g.supplier_id=0 ';

		}elseif($filter==2){

			$where .= ' AND g.supplier_id>0 ';

		}else{}

		if ($brand > 0)
		{
			/* 代码修改_start  By  www.hunuo.com */
			if (strstr($brand, '_'))
			{
				$brand_sql =str_replace("_", ",", $brand);
				$where .=  "AND g.brand_id in ($brand_sql) ";
			}
			else
			{
				$where .=  "AND g.brand_id=$brand ";
			}
			/* 代码修改_end  By  www.hunuo.com */
		}

		if ($min > 0)
		{
			$where .= " AND g.shop_price >= $min ";
		}

		if ($max > 0)
		{
			$where .= " AND g.shop_price <= $max ";
		}

		if($sort =='goods_number')
		{
			$where .= " AND g.goods_number != 0 ";
		}
		/* 代码增加 By  www.hunuo.com Start */
		if(!empty($is_stock))
		{
			$where .= " AND g.goods_number > 0 ";
		}
		/* 代码增加 By  www.hunuo.com End */

		/* 获得商品列表 */
		$sort = ($sort == 'shop_price' ? 'shop_p' : $sort);

		$sql = "SELECT g.goods_id, g.goods_name, g.goods_name_style, g.click_count, g.goods_number, g.market_price, " .
			   " g.is_new, g.is_best, g.is_hot, g.shop_price AS org_price, " .
			   " IFNULL(mp.user_price, g.shop_price * '$user_rank_info[discount]') AS shop_price, g.promote_price, " .
			   " IF(g.promote_price != '' " .
				   " AND g.promote_start_date < " . gmtime() .
				   " AND g.promote_end_date > " . gmtime() . ", g.promote_price, shop_price) " .
			   " AS shop_p, g.goods_type, " .
			   " g.promote_start_date, g.promote_end_date, g.goods_brief, g.goods_thumb, g.goods_img " .
			   " FROM " . $GLOBALS['ecs']->table('goods') .
			   " AS g " .
			   " LEFT JOIN " . $GLOBALS['ecs']->table('member_price') .
			   " AS mp " .
			   " ON mp.goods_id = g.goods_id " .
			   " AND mp.user_rank = '$user_rank_info[user_rank]' " .
			   " WHERE $where $ext " .
			   " ORDER BY $sort $order";


		//echo $sql;

		$arr = array();

		$a = $GLOBALS['db']->getAll($sql);

		foreach($a as $v){
			$arr[] = $v['goods_id'];
		}

		return $arr;
	}

    /**
     * 取得商品最终使用价格 从lib_common.php搬过来的get_final_price方法改动的,只是加多$user_rank_info参数传值 2018.01.02
     *
     * @param   string  $goods_id      商品编号
     * @param   string  $goods_num     购买数量
     * @param   boolean $is_spec_price 是否加入规格价格
     * @param   mix     $spec          规格ID的数组或者逗号分隔的字符串
     *
     * @return  商品最终购买价格
     */
    function get_final_price_api($goods_id, $goods_num = '1', $is_spec_price = false, $spec = array(), $user_rank_info = array(), $is_design = 0)
    {
        if ($is_design == 1) {
            $final_price = 99;  //设计库商品销售价格
            return $final_price;
        }

        $final_price   = '0'; //商品最终购买价格
        $volume_price  = '0'; //商品优惠价格
        $promote_price = '0'; //商品促销价格
        $user_price    = '0'; //商品会员价格

        /* 判断商品是否参与预售活动，如果参与则获取商品 */
        /*if(!empty($_REQUEST['pre_sale_id']))
        {
            $pre_sale = pre_sale_info($_REQUEST['pre_sale_id'], $goods_num);
            if(!empty($pre_sale)){
                $final_price = $pre_sale['cur_price'];

                //如果需要加入规格价格
                if ($is_spec_price)
                {
                    if (!empty($spec))
                    {
                        $spec_price   = spec_price($spec);
                        $final_price += $spec_price;
                    }
                }

                return $final_price;
            }
        }*/

        //取得商品优惠价格列表
        $price_list   = get_volume_price_list($goods_id, '1');

        if (!empty($price_list))
        {
            foreach ($price_list as $value)
            {
                if ($goods_num >= $value['number'])
                {
                    $volume_price = $value['price'];
                }
            }
        }

        @$discount = isset($user_rank_info['discount']) ? $user_rank_info['discount'] : $_SESSION['discount'];
        @$user_rank = isset($user_rank_info['user_rank']) ? $user_rank_info['user_rank'] : $_SESSION['user_rank'];

        //取得商品促销价格列表
        /* 取得商品信息 */
        $sql = "SELECT g.promote_price, g.promote_start_date, g.promote_end_date, ".
                    "IFNULL(mp.user_price, g.shop_price * '" . $discount . "') AS shop_price ".
               " FROM " .$GLOBALS['ecs']->table('goods'). " AS g ".
               " LEFT JOIN " . $GLOBALS['ecs']->table('member_price') . " AS mp ".
                       "ON mp.goods_id = g.goods_id AND mp.user_rank = '" . $user_rank. "' ".
               " WHERE g.goods_id = '" . $goods_id . "'" .
               " AND g.is_delete = 0";
        $goods = $GLOBALS['db']->getRow($sql);

        /* 计算商品的促销价格 */
        if ($goods['promote_price'] > 0)
        {
            $promote_price = bargain_price($goods['promote_price'], $goods['promote_start_date'], $goods['promote_end_date']);
        }
        else
        {
            $promote_price = 0;
        }

        //取得商品会员价格列表
        $user_price    = $goods['shop_price'];

        //比较商品的促销价格，会员价格，优惠价格
        if (empty($volume_price) && empty($promote_price))
        {
            //如果优惠价格，促销价格都为空则取会员价格
            $final_price = $user_price;
        }
        elseif (!empty($volume_price) && empty($promote_price))
        {
            //如果优惠价格为空时不参加这个比较。
            $final_price = min($volume_price, $user_price);
        }
        elseif (empty($volume_price) && !empty($promote_price))
        {
            //如果促销价格为空时不参加这个比较。
            $final_price = min($promote_price, $user_price);
        }
        elseif (!empty($volume_price) && !empty($promote_price))
        {
            //取促销价格，会员价格，优惠价格最小值
            $final_price = min($volume_price, $promote_price, $user_price);
        }
        else
        {
            $final_price = $user_price;
        }

        //如果需要加入规格价格
        if ($is_spec_price)
        {
            if (!empty($spec))
            {
                $spec_price   = spec_price($spec);
                $final_price += $spec_price;
            }
        }

        //返回商品最终购买价格
        return $final_price;
    }

    /**
     * 是否被收藏过
     * @param $goods_id integer
     * @param $user_id integer
     * @return int
     */
    public function is_collected($goods_id = 0,$user_id = 0){

        if(!$user_id){
            return 0;
        }
        /* 检查是否已经存在于用户的收藏夹 */
        $sql = "SELECT COUNT(*) FROM " .$this->_tb_collect_goods .
            " WHERE user_id='$user_id' AND goods_id = '$goods_id'";
        if ($this->_db->GetOne($sql) > 0)
        {
            $is_collected = 1;
        }
        else
        {
            $is_collected = 0;
        }
        return $is_collected;
    }

}
