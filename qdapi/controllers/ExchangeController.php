<?php


include_once(ROOT_PATH . 'includes/lib_common.php');
include_once(ROOT_PATH . 'includes/cls_user.php');
include_once(ROOT_PATH . 'includes/cls_checkout.php');

/**
 * 商品接口
 *
 * @version v1.0
 * @create 2016-11-02
 * @author cyq
 */
class ExchangeController extends ApiController
{

	public function __construct()
	{

		parent::__construct();
		$this->data  = $this->input();
		$this->user  = cls_user::getInstance();
		$this->checkout  = cls_checkout::getInstance();

		$this->user_id = isset($this->data['user_id'])? intval($this->data['user_id']) : '';

		$user_rank_info = $this->user->get_user_rank($this->user_id);
		if($user_rank_info){
			$this->user_rank_info = $user_rank_info;
		}else{
			$this->error("该会员数据不存在或者参数错误");
		}

	}


	public function query()
	{
		$cat_id 	= $this->input('cat_id', 0);
		$integral_max 	= $this->input('integral_max', 0);
		$integral_min 	= $this->input('integral_min', 0);
		$page 	= $this->input('page', 1);
		$size 	= $this->input('page_size', 15);
		$sort 	= $this->input('sort', 'goods_id');
		$order 	= $this->input('order', 'DESC');

		if(!in_array($sort, array('goods_id', 'exchange_integral', 'last_update','click_count')) || !in_array($order, array('DESC','ASC'))){
			$this->error('参数错误');
			exit();
		}

		$children = get_children($cat_id);
		$count = $this->get_exchange_goods_count($children, $integral_min, $integral_max);
		$max_page = ($count> 0) ? ceil($count / $size) : 1;
		if ($page > $max_page)
		{
			$page = $max_page;
		}
		$ext = '';
		$goodslist = $this->exchange_get_goods($children, $integral_min, $integral_max, $ext, $size, $page, $sort, $order);

		$result['goods_list'] = empty($goodslist['goods_list'])?array():array_values($goodslist['goods_list']);
		//$result['pager'] = $goodslist['pager'];
		$result['pager'] = array('page'=>$page,'page_size'=>$size,'record_count'=>$goodslist['pager']['record_count'],'page_count'=>$goodslist['pager']['page_count']);

		$this->success($result);
	}

	public function getExchangeInfo(){

		$goods_id 	= $this->input('goods_id', 0);

		$goods = $this->get_exchange_goods_info($goods_id);
		$data['goods_id']           = $goods['goods_id'];//商品id
		$data['cat_id']             = $goods['cat_id'];//商品分类id
		$data['goods_sn']           = $goods['goods_sn'];//商品货号
		$data['goods_name']         = $goods['goods_name'];//商品名称
		$data['click_count']        = $goods['click_count'];//浏览次数
		$data['market_price']        = $goods['market_price'];//浏览次数
		$data['goods_desc']        = $goods['goods_desc'];//浏览次数
		//$data['shop_price']        = $goods['shop_price'];//浏览次数
		//$data['brand_id']           = $goods['brand_id'];//商品品牌id
		//$data['goods_brand']        = $goods['goods_brand'];//商品品牌名称
		$data['goods_number']       = $goods['goods_number'];//产品库存
		//$data['goods_weight']       = $goods['goods_weight'];//产品重量
		$data['exchange_integral']  = $goods['exchange_integral'];//购买商品所赠送的积分
		//$data['count']              = $goods['count'];//商品销量数量
		$properties                 = get_goods_properties($goods_id);
		$data['specification']      = array_values($properties['spe']);                              // 商品属性
		$img_list                   = $this->get_goods_gallery_attr_hunuo_com($goods_id);
		$data['img_list'] = $img_list;
		$result = $data;

		$this->success($result);
	}

	public function showProfile(){

		define('SESS_ID',session_id());
		$_SESSION = $this->user_rank_info;
		$goods_id 	= $this->input('goods_id', 0);
		$_SESSION['user_id'] = $user_id 	= $this->input('user_id', 0);
		$specs 	= $this->input('specs', 0);


		$sql = "DELETE FROM " . $GLOBALS['ecs']->table('cart') ." WHERE rec_type = '".CART_EXCHANGE_GOODS."' and user_id = ".$user_id;
		$GLOBALS['db']->query($sql);


		/* 查询：判断是否登录 */
		if ($user_id <= 0)
		{
			$this->error('缺失user_id参数');
			exit;
		}

		/* 查询：取得参数：商品id */
		if ($goods_id <= 0)
		{
			$this->error('缺失goods_id参数');
			exit;
		}

		/* 查询：取得兑换商品信息 */
		$goods = $this->get_exchange_goods_info($goods_id);
		//print_r($goods);
		if (empty($goods))
		{
			$this->error('非法操作');
			exit;
		}
		/* 查询：检查兑换商品是否有库存 */
		if($goods['goods_number'] == 0 )
		{
			$this->error('所选商品没有库存');
		}
		/* 查询：检查兑换商品是否是取消 */
		if ($goods['is_exchange'] == 0)
		{
			$this->error('所选商品暂停兑换');
		}

		$user_info   = $this->get_user_info($user_id);
		//print_r($user_info );
		$user_points = $user_info['integral']; // 用户的积分总数
		if ($goods['exchange_integral'] > $user_points)
		{
			$this->error('当前用户没有足够积分兑换');
		}

		include_once(ROOT_PATH . 'includes/lib_order.php');

		$specs = trim($specs, ',');
		/* 查询：如果商品有规格则取规格商品信息 配件除外 */
		if (!empty($specs))
		{
			$_specs = explode(',', $specs);

			$product_info = $this->get_products_info($goods_id, $_specs);
		}
		if (empty($product_info))
		{
			$product_info = array('product_number' => '', 'product_id' => 0);
		}

		//查询：商品存在规格 是货品 检查该货品库存
		if((!empty($specs)) && ($product_info['product_number'] == 0) && ($_CFG['use_storage'] == 1))
		{
			$this->error('所选商品没有库存');
		}

		/* 查询：查询规格名称和值，不考虑价格 */
		$attr_list = array();
		$sql = "SELECT a.attr_name, g.attr_value " .
				"FROM " . $GLOBALS['ecs']->table('goods_attr') . " AS g, " .
					$GLOBALS['ecs']->table('attribute') . " AS a " .
				"WHERE g.attr_id = a.attr_id " .
				"AND g.goods_attr_id " . db_create_in($specs);
		$res = $GLOBALS['db']->query($sql);
		while ($row = $GLOBALS['db']->fetchRow($res))
		{
			$attr_list[] = $row['attr_name'] . ': ' . $row['attr_value'];
		}
		$goods_attr = join(chr(13) . chr(10), $attr_list);

		/* 更新：清空购物车中所有团购商品 */



		/* 更新：加入购物车 */
		$number = 1;
		$cart = array(
			'user_id'        => $user_id,
			'session_id'     => session_id(),
			'goods_id'       => $goods['goods_id'],
			'product_id'     => $product_info['product_id'],
			'goods_sn'       => addslashes($goods['goods_sn']),
			'goods_name'     => addslashes($goods['goods_name']),
			'market_price'   => $goods['market_price'],
			'goods_price'    => 0,//$goods['exchange_integral']
			'goods_number'   => $number,
			'goods_attr'     => addslashes($goods_attr),
			'goods_attr_id'  => $specs,
			'is_real'        => $goods['is_real'],
			'extension_code' => 'exchange_goods',
			'parent_id'      => 0,
			'rec_type'       => CART_EXCHANGE_GOODS,
			'is_gift'        => 0
		);
		$GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('cart'), $cart, 'INSERT');
		$sel_goods = $_SESSION['sel_cartgoods'] = $GLOBALS['db']->insert_id();
		/* 记录购物流程类型：团购 */
		$flow_type = $_SESSION['flow_type'] = CART_EXCHANGE_GOODS;
		$flow_order['extension_code'] = $_SESSION['extension_code'] = 'exchange_goods';
		$flow_order['extension_id'] = $_SESSION['extension_id'] = $goods_id;
		$flow_order['address_id']       = !empty($this->data['address_id']) ? intval($this->data['address_id']) : 0;
		$supplier_id_list = array(

		);

		$flow_order = array();
		$result = $this->checkout->getCheckoutProfile($this->user_rank_info, $flow_type, $sel_goods, $flow_order,'0',$sel_goods,$this->input('device'));

			if($result['code']!=200){
				$return['code'] = 500;
				$return['error_code'] = $result['error_code'];
				$return['message'] = $result['message'];
				break ;
			}

			$return['data']['def_addr'] = $result['supplier']['def_addr'];
			$return['data']['address_list'] = $result['supplier']['address_list'];
			$return['data']['payment_list'] = $result['supplier']['payment_list'];
			$return['data']['order_info'] = $result['supplier']['order_info'];
			$return['data']['order_total'] = $result['supplier']['order_total'];


			unset($result['supplier']['address_list']);
			unset($result['error_code']);
			unset($result['supplier']['payment_list']);
			unset($result['supplier']['order_info']);
			unset($result['supplier']['order_total']);

			unset($result['message']);
			unset($result['code']);

			$sql_supplier = "SELECT s.supplier_id,s.supplier_name,s.add_time,sr.rank_name FROM ". $GLOBALS['ecs']->table("supplier") . " as s left join ". $GLOBALS['ecs']->table("supplier_rank") ." as sr ON s.rank_id=sr.rank_id WHERE s.supplier_id=".$result['supplier_id']." AND s.status=1";
			$shopuserinfo = $GLOBALS['db']->getRow($sql_supplier);
			$result = array_merge(array('supplier_name' => $shopuserinfo['supplier_name']?:'商家自营'), $result);

			@$result['shipping_list'] = $result['supplier']['shipping_list'];
			@$result['goods_list'] = $result['supplier']['goods_list'];
			@$result['bonus_list'] = $result['supplier']['bonus_list'];
			@$result['supplier_total'] = $result['supplier']['supplier_total'];
			unset($result['supplier']);

			$return['data']['supplier_list'][] = $result;


		if(isset($return['code']) && $return['code'] == 500){
			Response::render(array(),$return['error_code'],$return['message']);
		}else{
			$this->success($return['data']);
		}
	}


	/**
	 * 获得分类下的商品总数
	 *
	 * @access  public
	 * @param   string     $cat_id
	 * @return  integer
	 */
	private function get_exchange_goods_count($children, $min = 0, $max = 0, $ext='')
	{
		$where  = "eg.is_exchange = 1 AND g.is_delete = 0 AND ($children OR " . get_extension_goods($children) . ')';


		if ($min > 0)
		{
			$where .= " AND eg.exchange_integral >= $min ";
		}

		if ($max > 0)
		{
			$where .= " AND eg.exchange_integral <= $max ";
		}

		$sql = 'SELECT COUNT(*) FROM ' . $GLOBALS['ecs']->table('exchange_goods') . ' AS eg, ' .
			   $GLOBALS['ecs']->table('goods') . " AS g WHERE eg.goods_id = g.goods_id AND $where $ext";

		/* 返回商品总数 */
		return $GLOBALS['db']->getOne($sql);
	}

	/**
	 * 获得分类下的商品
	 *
	 * @access  public
	 * @param   string  $children
	 * @return  array
	 */
	private function exchange_get_goods($children, $min, $max, $ext, $size, $page, $sort, $order)
	{
		$display = isset($GLOBALS['display'])?$GLOBALS['display']:"";
		$where = "eg.is_exchange = 1 AND g.is_delete = 0 AND ".
				 "($children OR " . get_extension_goods($children) . ')';

		if ($min > 0)
		{
			$where .= " AND eg.exchange_integral >= $min ";
		}

		if ($max > 0)
		{
			$where .= " AND eg.exchange_integral <= $max ";
		}

		/* 获得商品列表 */
		$sql = 'SELECT g.goods_id, g.goods_name, g.goods_name_style, eg.exchange_integral, ' .
					'g.goods_type, g.goods_brief, g.goods_thumb , g.goods_img, eg.is_hot,g.shop_price,g.market_price ' .
				'FROM ' . $GLOBALS['ecs']->table('exchange_goods') . ' AS eg, ' .$GLOBALS['ecs']->table('goods') . ' AS g ' .
				"WHERE eg.goods_id = g.goods_id AND $where $ext ORDER BY $sort $order";
		$record_count = count($GLOBALS['db']->getAll($sql));


		$pager = get_pager('exchange.php', array(
			'act' => ''
		), $record_count, $page, $size);
		$res = $GLOBALS['db']->selectLimit($sql, $size, ($page - 1) * $size);

		$arr = array();
		while ($row = $GLOBALS['db']->fetchRow($res))
		{
			/* 处理商品水印图片 */
			$watermark_img = '';

	//        if ($row['is_new'] != 0)
	//        {
	//            $watermark_img = "watermark_new_small";
	//        }
	//        elseif ($row['is_best'] != 0)
	//        {
	//            $watermark_img = "watermark_best_small";
	//        }
	//        else
			if ($row['is_hot'] != 0)
			{
				$watermark_img = 'watermark_hot_small';
			}

			if ($watermark_img != '')
			{
				$arr[$row['goods_id']]['watermark_img'] =  $watermark_img;
			}

			$arr[$row['goods_id']]['goods_id']          = $row['goods_id'];
			if($display == 'grid')
			{
				$arr[$row['goods_id']]['goods_name']    = $GLOBALS['_CFG']['goods_name_length'] > 0 ? sub_str($row['goods_name'], $GLOBALS['_CFG']['goods_name_length']) : $row['goods_name'];
			}
			else
			{
				$arr[$row['goods_id']]['goods_name']    = $row['goods_name'];
			}
			//$arr[$row['goods_id']]['name']              = $row['goods_name'];
			$arr[$row['goods_id']]['goods_brief']       = $row['goods_brief'];
			$arr[$row['goods_id']]['shop_price']       = $row['shop_price'];
			//$arr[$row['goods_id']]['goods_style_name']  = add_style($row['goods_name'],$row['goods_name_style']);
			$arr[$row['goods_id']]['exchange_integral'] = $row['exchange_integral'];
			$arr[$row['goods_id']]['type']              = $row['goods_type'];
			//$arr[$row['goods_id']]['shop_price']              = $row['shop_price'];
			$arr[$row['goods_id']]['market_price']              = $row['market_price'];
			$arr[$row['goods_id']]['goods_thumb']       = get_image_path($row['goods_id'], $row['goods_thumb'], true);
			$arr[$row['goods_id']]['goods_img']         = get_image_path($row['goods_id'], $row['goods_img']);
			//$arr[$row['goods_id']]['url']               = build_uri('exchange_goods', array('gid'=>$row['goods_id']), $row['goods_name']);
		}

		return array('goods_list'=>$arr,'pager'=>$pager);
	}

	/**
	 * 获得积分兑换商品的详细信息
	 *
	 * @access  public
	 * @param   integer     $goods_id
	 * @return  void
	 */
	private function get_exchange_goods_info($goods_id)
	{
		//处理一下库存的问题啊！！

		$time = gmtime();
		$sql = 'SELECT g.*, c.measure_unit, b.brand_id, b.brand_name AS goods_brand, eg.exchange_integral, eg.is_exchange ' .
				'FROM ' . $GLOBALS['ecs']->table('goods') . ' AS g ' .
				'LEFT JOIN ' . $GLOBALS['ecs']->table('exchange_goods') . ' AS eg ON g.goods_id = eg.goods_id ' .
				'LEFT JOIN ' . $GLOBALS['ecs']->table('category') . ' AS c ON g.cat_id = c.cat_id ' .
				'LEFT JOIN ' . $GLOBALS['ecs']->table('brand') . ' AS b ON g.brand_id = b.brand_id ' .
				"WHERE g.goods_id = '$goods_id' AND g.is_delete = 0 " .
				'GROUP BY g.goods_id';

		$row = $GLOBALS['db']->getRow($sql);

		if ($row !== false)
		{
			/* 处理商品水印图片 */
			$watermark_img = '';

			if ($row['is_new'] != 0)
			{
				$watermark_img = "watermark_new";
			}
			elseif ($row['is_best'] != 0)
			{
				$watermark_img = "watermark_best";
			}
			elseif ($row['is_hot'] != 0)
			{
				$watermark_img = 'watermark_hot';
			}

			if ($watermark_img != '')
			{
				$row['watermark_img'] =  $watermark_img;
			}

			// 加一个属性库存的判断问题
			// $sql = "SELECT * FROM ". $GLOBALS['ecs']->table('goods_attr')." WHERE `goods_id` = ".$goods_id;
			// $allgoods= $GLOBALS['db']->getAll($sql);
			// if ($allgoods){
				// $row['goods_number']   = $GLOBALS['db']->getOne('SELECT SUM(product_number) as num FROM '. $GLOBALS['ecs']->table('products').'  WHERE goods_id = '.$goods_id)?:0;
			// }
			if($row['goods_type'] >0){
                //查询是否只有唯一属性，只有唯一属性的，只读总库存数量就行
                $sql = "SELECT count(*) as num FROM " . $GLOBALS['ecs']->table('attribute') . " WHERE cat_id = '".$row['goods_type']."' and attr_type > 0";
                $attribute_number = $GLOBALS['db']->getOne($sql);

                $sql = "SELECT sum( product_number ) as num FROM " . $GLOBALS['ecs']->table('products') . " WHERE goods_id = $goods_id";
                $attr_number = $GLOBALS['db']->getOne($sql);
                if($attribute_number){
                    $row['goods_number'] = isset($attr_number) ? $attr_number : 0;
                }else{
                    $row['goods_number'] = isset($attr_number) ? $attr_number : $row['goods_number'];
                }
            }

			/* 修正重量显示 */
			$row['goods_weight']  = (intval($row['goods_weight']) > 0) ?
				$row['goods_weight'] . $GLOBALS['_LANG']['kilogram'] :
				($row['goods_weight'] * 1000) . $GLOBALS['_LANG']['gram'];

			/* 修正上架时间显示 */
			$row['add_time']      = local_date($GLOBALS['_CFG']['date_format'], $row['add_time']);

			/* 修正商品图片 */
			$row['goods_img']   = get_image_path($goods_id, $row['goods_img']);
			$row['goods_thumb'] = get_image_path($goods_id, $row['goods_thumb'], true);

			return $row;
		}
		else
		{
			return false;
		}
	}

	/**
	 * 获得指定商品的相册
	 *
	 * @access  public
	 * @param   integer     $goods_id
	 * @return  array
	 */
	private function get_goods_gallery_attr_hunuo_com($goods_id, $goods_attr_id='')
	{

		$sql = 'SELECT img_id, img_original, img_url, thumb_url, img_desc' .
			' FROM ' . $GLOBALS['ecs']->table('goods_gallery') .
			" WHERE goods_id = '$goods_id' and goods_attr_id='$goods_attr_id' LIMIT " . $GLOBALS['_CFG']['goods_gallery_number'];
		$row = $GLOBALS['db']->getAll($sql);
		if (count($row)==0)
		{
			$sql = 'SELECT img_id, img_original, img_url, thumb_url, img_desc' .
			' FROM ' . $GLOBALS['ecs']->table('goods_gallery') .
			" WHERE goods_id = '$goods_id' and goods_attr_id='0' LIMIT " . $GLOBALS['_CFG']['goods_gallery_number'];
			$row = $GLOBALS['db']->getAll($sql);
		}
		/* 格式化相册图片路径 */
		foreach($row as $key => $gallery_img)
		{
			$row[$key]['img_url'] = get_image_path($goods_id, $gallery_img['img_url'], false, 'gallery');
			$row[$key]['thumb_url'] = get_image_path($goods_id, $gallery_img['thumb_url'], true, 'gallery');
			$row[$key]['img_original'] = get_image_path($goods_id, $gallery_img['img_original'], true, 'gallery');
		}
		return $row;
	}

	 /**
     * 获取用户中心默认页面所需的数据
     *
     * @access  public
     * @param   int         $user_id            用户ID
     *
     * @return  array       $info               默认页面所需资料数组
     */
    private function get_user_info($user_id)
    {
        require_once(ROOT_PATH . 'includes/lib_order.php');

        $user_bonus = get_user_bonus($user_id,1);
        $user_bonus = $user_bonus['bonus_count'];
        $sql = "SELECT * FROM " .$GLOBALS['ecs']->table('users'). " WHERE user_id = '$user_id'";
        $row = $GLOBALS['db']->getRow($sql);
        $_SESSION['user_name'] =$row['user_name'];
        $info = array();
        $info['user_name']  = stripslashes($row['user_name']);
        $info['shop_name'] = $GLOBALS['_CFG']['shop_name'];
        $info['integral']  = $row['pay_points'];
        /* 增加是否开启会员邮件验证开关 */
        $info['is_validate'] = ($GLOBALS['_CFG']['member_email_validate'] && !$row['is_validated'])?'0':'1';
        $info['mobile_phone'] = $row['mobile_phone'];
        $info['headimg'] = $row['headimg'];
        $info['email']	= $row['email'];
        $info['status'] = $row['status'];
        $info['bonus_count'] = $user_bonus;
        $info['validated'] = $row['validated'];
        //如果$_SESSION中时间无效说明用户是第一次登录。取当前登录时间。
        $last_time = !isset($_SESSION['last_time']) ? $row['last_login'] : $_SESSION['last_time'];

        if ($last_time == 0)
        {
            $_SESSION['last_time'] = $last_time = gmtime();
        }

        $info['last_time'] = local_date($GLOBALS['_CFG']['time_format'], $last_time);
        $info['surplus']   = $row['user_money'];
//        $info['bonus']     = sprintf($GLOBALS['_LANG']['user_bonus_info'], $user_bonus['bonus_count'], price_format($user_bonus['bonus_value'], false));

        /* 待付款的订单： */
        $ex_where = " and user_id=$user_id";
        $order_count['await_pay'] = (string)$GLOBALS['db']->GetOne('SELECT COUNT(*)' . ' FROM ' . $GLOBALS['ecs']->table('order_info') . " o WHERE 1 $ex_where " . order_query_sql('await_pay'));
        $info['order_count'] = $order_count['await_pay'];

        $sql = "SELECT COUNT(*) FROM " . $GLOBALS['ecs']->table('collect_goods').
            " WHERE user_id = '" .$user_id. "'";
        $info['collect_count'] = $GLOBALS['db']->getOne($sql);

        return $info;
    }

	/**
	 * 取指定规格的货品信息
	 *
	 * @access      public
	 * @param       string      $goods_id
	 * @param       array       $spec_goods_attr_id
	 * @return      array
	 */
	private function get_products_info($goods_id, $spec_goods_attr_id)
	{
		$return_array = array();

		if (empty($spec_goods_attr_id) || !is_array($spec_goods_attr_id) || empty($goods_id))
		{
			return $return_array;
		}

		$goods_attr_array = sort_goods_attr_id_array($spec_goods_attr_id);

		if(isset($goods_attr_array['sort']))
		{
			$goods_attr = implode('|', $goods_attr_array['sort']);

			$sql = "SELECT * FROM " .$GLOBALS['ecs']->table('products'). " WHERE goods_id = '$goods_id' AND goods_attr = '$goods_attr' LIMIT 0, 1";
			$return_array = $GLOBALS['db']->getRow($sql);
		}
		return $return_array;
	}
}