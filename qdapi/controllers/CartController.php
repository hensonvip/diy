<?php

include_once(ROOT_PATH . 'includes/cls_goods.php');
include_once(ROOT_PATH . 'includes/cls_cart.php');
include_once(ROOT_PATH . 'includes/cls_user.php');
include_once(ROOT_PATH . 'includes/cls_shipping.php');
include_once(ROOT_PATH . 'includes/lib_goods.php');
include_once(ROOT_PATH . 'includes/lib_main.php');

/**
 * 购物车接口
 *
 * @version v1.0
 * @create 2016-11-02
 * @author cyq
 */
class CartController extends ApiController
{


	public function __construct()
	{

		parent::__construct();
		$this->data  = $this->input();
		$this->goods = cls_goods::getInstance();
		$this->user  = cls_user::getInstance();
		$this->cart  = cls_cart::getInstance();
		$this->shipping  = cls_shipping::getInstance();


		$this->user_id = isset($this->data['user_id'])? $this->data['user_id'] : '';

		if(empty($this->user_id) || !isset($this->user_id)){
			$this->error("请先登录！");
		}
		$user_rank_info = $this->user->get_user_rank($this->user_id);
		if($user_rank_info){
			$this->user_rank_info = $user_rank_info;
		}else{
			$this->error("该会员数据不存在或者参数错误");
		}
	}


	//赠品添加购物车
	public function add_package_to_cart(){

		$user_rank_info = $this->user_rank_info;

		$package_id = $this->input('package_id', 0);

		$num = 1;
		$package_attr_id='';
		$package_prices='';

		$GLOBALS['err']->clean();

		if($package_prices)
		{
			$package_pricea=explode("-", $package_prices);
		}


		/* 取得礼包信息 */
		$package = $this->get_package_info($package_id,$this->user_rank_info);

		if (empty($package))
		{
			$GLOBALS['err']->add($GLOBALS['_LANG']['goods_not_exists'], ERR_NOT_EXISTS);

			return false;
		}

		/* 是否正在销售 */
		if ($package['is_on_sale'] == 0)
		{
			$GLOBALS['err']->add($GLOBALS['_LANG']['not_on_sale'], ERR_NOT_ON_SALE);

			return false;
		}

		/* 现有库存是否还能凑齐一个礼包 */
		if ($GLOBALS['_CFG']['use_storage'] == '1' && $this->judge_package_stock($package_id))
		{
			$GLOBALS['err']->add(sprintf($GLOBALS['_LANG']['shortage'], 1), ERR_OUT_OF_STOCK);

			return false;
		}

		/* 检查库存 */
	//    if ($GLOBALS['_CFG']['use_storage'] == 1 && $num > $package['goods_number'])
	//    {
	//        $num = $goods['goods_number'];
	//        $GLOBALS['err']->add(sprintf($GLOBALS['_LANG']['shortage'], $num), ERR_OUT_OF_STOCK);
	//
	//        return false;
	//    }

		/* 初始化要插入购物车的基本件数据 */
		$parent = array(
			'user_id'       => $user_rank_info['user_id'],
			'session_id'    =>'',
			'goods_id'      => $package_id,
			'goods_sn'      => '',
			'goods_name'    => addslashes($package['package_name']),
			'market_price'  => isset($package_pricea[0]) ? $package_pricea[0] :  $package['market_package'],
			'goods_price'   => isset($package_pricea[1] )? $package_pricea[1] :  $package['package_price'],
			'package_attr_id' =>$package_attr_id,
			'goods_number'  => $num,
			'goods_attr'    => '',
			'goods_attr_id' => '',
			'is_real'       => $package['is_real'],
			'extension_code'=> 'package_buy',
			'is_gift'       => 0,
			'rec_type'      => CART_GENERAL_GOODS
		);

		/* 如果数量不为0，作为基本件插入 */
		if ($num > 0)
		{
			 /* 检查该商品是否已经存在在购物车中 */
			$sql = "SELECT goods_number FROM " .$GLOBALS['ecs']->table('cart').
					" WHERE user_id = '" .$user_rank_info['user_id']. "' AND goods_id = '" . $package_id . "' ".
					" AND parent_id = 0 AND extension_code = 'package_buy' " .
					" AND package_attr_id = '$package_attr_id'  AND rec_type = '" . CART_GENERAL_GOODS . "'";

			$row = $GLOBALS['db']->getRow($sql);

			if($row) //如果购物车已经有此物品，则更新
			{
				$num += $row['goods_number'];
				if ($GLOBALS['_CFG']['use_storage'] == 0 || $num > 0)
				{
					$sql = "UPDATE " . $GLOBALS['ecs']->table('cart') . " SET goods_number = '" . $num . "'" .
						   " WHERE user_id = '" .$user_rank_info['user_id']. "' AND goods_id = '$package_id' ".
						   " AND parent_id = 0 AND extension_code = 'package_buy' " .
						   " AND package_attr_id = '$package_attr_id' AND rec_type = '" . CART_GENERAL_GOODS . "'";
					$GLOBALS['db']->query($sql);
					//echo $GLOBALS['db']->update_id();
					$sql = " SELECT rec_id FROM " . $GLOBALS['ecs']->table('cart') . " WHERE user_id = '" .$user_rank_info['user_id']. "' AND goods_id = '$package_id'  AND parent_id = 0 AND extension_code = 'package_buy' AND package_attr_id = '$package_attr_id' AND rec_type = '" . CART_GENERAL_GOODS . "'";
					$id = $GLOBALS['db']->getOne($sql);
				}
				else
				{
					$GLOBALS['err']->add(sprintf($GLOBALS['_LANG']['shortage'], $num), ERR_OUT_OF_STOCK);
					return false;
				}
			}
			else //购物车没有此物品，则插入
			{
				$GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('cart'), $parent, 'INSERT');
				$id =  $GLOBALS['db']->insert_id();
			}
		}

		/* 把赠品删除 */
		$sql = "DELETE FROM " . $GLOBALS['ecs']->table('cart') . " WHERE user_id = '" . $user_rank_info['user_id'] . "' AND is_gift <> 0";
		$GLOBALS['db']->query($sql);

		$this->success(array('rec_id'=>$id));
		//return true;
	}

	/**
	 * 获取购物车商品
	 *
	 */
	public function get_cart_goods()
	{
		$sel_goods    = !empty($this->data['sel_goods']) ? $this->data['sel_goods'] : '';
		$other = '';
		if($sel_goods){
			$other .=" AND c.rec_id in (". $sel_goods.")  ";
		}
		$other .=" AND c.rec_type = 0 AND c.is_design = 0 ";//只显示普通商品，非设计库商品
		// 加入购物车成功才显示购物车商品信息

		$cart_goods = $this->cart->get_cart_goods($this->user_rank_info,$other);

		$this->success($cart_goods);
	}

	//获取该商品的赠品数据
	public function getGift(){
		$goods_id = !empty($this->data['goods_id']) ? intval($this->data['goods_id']) : $this->error('缺少参数');
		$user_rank_info = $this->user->get_user_rank($this->user_id);
		/* 取得优惠活动*/
        $favourable_list = $this->cart->favourable_list($user_rank_info);
        if($favourable_list){
			foreach($favourable_list as $key => $val){
				switch($val['act_range']){
					case 0:
						$ids = array();
						break;
					case 1:
						$children = get_children($val['act_range_ext']);
						$ids = $this->cart->category_get_goods($user_rank_info,$children);
						break;
					case 2:
						$children = get_children($val['act_range_ext']);
						$ids = $this->cart->category_get_goods($user_rank_info,'',$val['act_range_ext']);
						break;
					case 3:
						$ids = explode(',',$val['act_range_ext']);//把字符拆分为数组
						break;

				}

				if($val['act_type_ext'] > 0){
					//限制领取数量
					$val['get_max_gift'] = round($val['act_type_ext'],0);//能领取的赠品数量
				}else{
					//不限领取数量
					$val['get_max_gift'] = count($val['gift']);//统计赠品数量
				}



				if(in_array($goods_id,$ids)){
					$favourable_info[] = $val;
				}
			}
		}else{
			$favourable_info = array();
		}
		$data =array();

		$data['favourable_info'] = $favourable_info;

		$this->success($data);
	}

	/**
	 * 将商品添加到购物车
	 *
	 * @param   string   goods  加入商品购物车
	 *
	 */


	 //赠品
	 public function addGiftToCart()
	{
		$sel_goods = $this->data['sel_goods']?:'';
		$act_id = $this->data['act_id']?:'';
		$result = $this->cart->addFavourable($act_id,$sel_goods,$this->user_rank_info);
		if($result['code'] == 500){
			$this->error($result['message']);
		}else{
			$cart_goods = $this->cart->get_cart_goods($this->user_rank_info);
			//$this->success($cart_goods);
			Response::render($cart_goods, 200, '加入购物车成功');
		}
	}

	public function addToCart()
	{
		// 参考数据
		$_POST['goods'] = $this->data['goods']?:'';

		$param_post = stripslashes(strip_tags(urldecode($_POST['goods'])));
		if(empty($param_post))
		{
			$this->error('参数错误');
		}

		$param_posta = json_decode($param_post,true);
		/*if($param_posta['quick'] == 1){
			$sql = "DELETE FROM " . $GLOBALS['ecs']->table('cart') ." WHERE goods_id = ".$param_posta['goods_id'] ." and user_id = ".$this->user_id;
			$GLOBALS['db']->query($sql);
		}*/

		$result = $this->cart->addToCart($param_post,$this->user_rank_info);

		if($result['code'] == 500){
			$this->error($result['message']);
		}else{
			// 加入购物车成功才显示购物车商品信息
			$other = '';
			if($param_posta['quick'] == 1){
				$other = ' AND g.goods_id = '.$param_posta['goods_id'] ;
				if($param_posta['spec']){
					$other .= ' AND c.goods_attr_id = "'.implode(",",$param_posta['spec']).'"';
				}
			}else{
				//rec_type    0 普通商品、1 团购商品、2 拍卖商品、3 夺宝奇兵、4 积分商城、6 预售商品、7 虚拟团购、101（砍价）
				$other = ' AND c.rec_type = 0 ';
			}

			$cart_goods = $this->cart->get_cart_goods($this->user_rank_info, $other);
			//$this->success($cart_goods);
			Response::render($cart_goods, 200, '加入购物车成功');
		}
	}

	/**
	 *  更新购物车商品数量
	 * @param   int   rec_id        购物车表ID
	 * @param   int   number        商品数量
	 * @param   int   goods_id      商品ID
	 * @param   int   is_package    是否套餐
	 * @param   int   suppid        供应商ID
	 * @return  void
	 */
	public function updateCart()
	{
		$rec_id       = !empty($this->data['rec_id']) ? intval($this->data['rec_id']) : $this->error('缺少参数');
		$number       = !empty($this->data['number']) ? intval($this->data['number']) : '';
		$spec         = !empty($this->data['spec']) ? $this->data['spec'] : '';
		$goods_id     = !empty($this->data['goods_id']) ? intval($this->data['goods_id']) : $this->error('缺少参数');
		$is_package   = !empty($this->data['is_package']) ? intval($this->data['is_package']) : 0;
		$suppid       = isset($this->data['suppid']) ? intval($this->data['suppid']) : -1;
		//购物车商品
		$sel_goods    = !empty($this->data['sel_goods']) ? $this->data['sel_goods'] : '';
		$result = $this->cart->updateCart($this->user_rank_info, $rec_id, $number, $goods_id, $is_package, $suppid,'',$spec);
		//print_r($result);
		if($result['code'] == 500){
			$this->error($result['message']);
		}else{
			$other = '';
			if($sel_goods){
				$other .=" AND c.rec_id in (". $sel_goods.")  ";
			}
			$other .=" AND c.rec_type = 0 ";//只显示普通商品
			// 加入购物车成功才显示购物车商品信息
			$cart_goods = $this->cart->get_cart_goods($this->user_rank_info,$other);
			$this->success($cart_goods);
		}
	}


	/**
	 *  删除购物车商品
	 * @param   int   rec_id        购物车表ID
	 * @param   int   number        商品数量
	 * @param   int   goods_id      商品ID
	 * @param   int   is_package    是否套餐
	 * @param   int   suppid        供应商ID
	 * @return  void
	 */
	public function dropCart()
	{
		$rec_id = !empty($this->data['rec_id']) ? (strpos($this->data['rec_id'],',') === false ? intval($this->data['rec_id']) : explode(',', $this->data['rec_id'])) : $this->error('缺少参数');

		if(is_array($rec_id)){
			foreach($rec_id as $v){
				$result = $this->cart->flow_drop_cart_goods($v, $this->user_rank_info);
				if($result['code'] == 500){
					break;
				}
			}
		}else{
			$result = $this->cart->flow_drop_cart_goods($rec_id, $this->user_rank_info);
		}

		if($result['code'] == 500){
			$this->error($result['message']);
		}else{
			$other = '';
			$other .=" AND c.rec_type = 0 ";//只显示普通商品
			// 加入购物车成功才显示购物车商品信息
			$cart_goods = $this->cart->get_cart_goods($this->user_rank_info,$other);
			$this->success($cart_goods);
		}

	}


	public function get_package_info($id,$user_rank_info){

		$discount = $user_rank_info['discount'];
		$user_rank = $user_rank_info['user_rank'];


		global $ecs, $db,$_CFG;
		$id = is_numeric($id)?intval($id):0;
		$now = gmtime();

		$sql = "SELECT act_id AS id,  act_name AS package_name, goods_id , goods_name, start_time, end_time, act_desc, ext_info".
			   " FROM " . $GLOBALS['ecs']->table('goods_activity') .
			   " WHERE act_id='$id' AND act_type = " . GAT_PACKAGE;

		$package = $db->GetRow($sql);

		/* 将时间转成可阅读格式 */
		if ($package['start_time'] <= $now && $package['end_time'] >= $now)
		{
			$package['is_on_sale'] = "1";
		}
		else
		{
			$package['is_on_sale'] = "0";
		}
		$package['start_time'] = local_date('Y-m-d H:i', $package['start_time']);
		$package['end_time']   = local_date('Y-m-d H:i', $package['end_time']);
		$row = unserialize($package['ext_info']);
		unset($package['ext_info']);
		if ($row)
		{
			foreach ($row as $key=>$val)
			{
				$package[$key] = $val;
			}
		}

		$sql = "SELECT pg.package_id, pg.goods_id, pg.goods_number, pg.admin_id, ".
			   " g.goods_sn, g.goods_name, g.market_price, g.goods_thumb, g.is_real, ".
			   " IFNULL(mp.user_price, g.shop_price * '$discount') AS rank_price " .
			   " FROM " . $GLOBALS['ecs']->table('package_goods') . " AS pg ".
			   "   LEFT JOIN ". $GLOBALS['ecs']->table('goods') . " AS g ".
			   "   ON g.goods_id = pg.goods_id ".
			   " LEFT JOIN " . $GLOBALS['ecs']->table('member_price') . " AS mp ".
					"ON mp.goods_id = g.goods_id AND mp.user_rank = '$user_rank' ".
			   " WHERE pg.package_id = " . $id. " ".
			   " ORDER BY pg.package_id, pg.goods_id";

		$goods_res = $GLOBALS['db']->getAll($sql);

		$market_price        = 0;
		$real_goods_count    = 0;
		$virtual_goods_count = 0;

		foreach($goods_res as $key => $val)
		{
			$goods_res[$key]['goods_thumb']         = get_image_path($val['goods_id'], $val['goods_thumb'], true);
			$goods_res[$key]['market_price_format'] = price_format($val['market_price']);
			$goods_res[$key]['rank_price_format']   = price_format($val['rank_price']);
			$market_price += $val['market_price'] * $val['goods_number'];
			/* 统计实体商品和虚拟商品的个数 */
			if ($val['is_real'])
			{
				$real_goods_count++;
			}
			else
			{
				$virtual_goods_count++;
			}
		}

		if ($real_goods_count > 0)
		{
			$package['is_real']            = 1;
		}
		else
		{
			$package['is_real']            = 0;
		}

		$package['goods_list']            = $goods_res;
		$package['market_package']        = $market_price;
		$package['market_package_format'] = price_format($market_price);
		$package['package_price_format']  = price_format($package['package_price']);

		return $package;
	}

	public function judge_package_stock($package_id, $package_num = 1){
		$sql = "SELECT goods_id, product_id, goods_number
				FROM " . $GLOBALS['ecs']->table('package_goods') . "
				WHERE package_id = '" . $package_id . "'";
		$row = $GLOBALS['db']->getAll($sql);
		if (empty($row))
		{
			return true;
		}

		/* 分离货品与商品 */
		$goods = array('product_ids' => '', 'goods_ids' => '');
		foreach ($row as $value)
		{
			if ($value['product_id'] > 0)
			{
				$goods['product_ids'] .= ',' . $value['product_id'];
				continue;
			}

			$goods['goods_ids'] .= ',' . $value['goods_id'];
		}

		/* 检查货品库存 */
		if ($goods['product_ids'] != '')
		{
			$sql = "SELECT p.product_id
					FROM " . $GLOBALS['ecs']->table('products') . " AS p, " . $GLOBALS['ecs']->table('package_goods') . " AS pg
					WHERE pg.product_id = p.product_id
					AND pg.package_id = '$package_id'
					AND pg.goods_number * $package_num > p.product_number
					AND p.product_id IN (" . trim($goods['product_ids'], ',') . ")";
			$row = $GLOBALS['db']->getAll($sql);

			if (!empty($row))
			{
				return true;
			}
		}

		/* 检查商品库存 */
		if ($goods['goods_ids'] != '')
		{
			$sql = "SELECT g.goods_id
					FROM " . $GLOBALS['ecs']->table('goods') . "AS g, " . $GLOBALS['ecs']->table('package_goods') . " AS pg
					WHERE pg.goods_id = g.goods_id
					AND pg.goods_number * $package_num > g.goods_number
					AND pg.package_id = '" . $package_id . "'
					AND pg.goods_id IN (" . trim($goods['goods_ids'], ',') . ")";
			$row = $GLOBALS['db']->getAll($sql);

			if (!empty($row))
			{
				return true;
			}
		}

		return false;
	}

	/*
	 *获取购物车 商品数量
	 @param   int   rec_id        购物车表ID
	 @param   int   user_id       会员ID
	 */

	public function getCartnumber(){


			$sql_where= ' rec_type = 0 and user_id = ' .$this->user_id;

			 $number = $GLOBALS['db']->getOne("select sum(goods_number) from ". $GLOBALS['ecs']->table('cart')  ." where " . $sql_where  );


			// $rec_id=$_POST['rec_id'];

		 // 	$sql_where = "user_id='". $user_id ."' and is_gift=0 and is_real=1 ";

   //          $number = $GLOBALS['db']->getOne("select goods_number from ". $GLOBALS['ecs']->table('cart')  ." where " . $sql_where . " and rec_id= " . $rec_id );
   //          if(empty($number)){
   //              $result['message'] = "非法操作！";
   //              $result['number']=0;
   //              $this->error($result['message']);
   //          }

            $result['message'] = "购物车商品数量";
            $result['number']=$number?:0;
            $this->success($result);



	}


}
