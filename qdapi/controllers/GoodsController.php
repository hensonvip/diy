<?php

include_once(ROOT_PATH . 'includes/cls_goods.php');
include_once(ROOT_PATH . 'includes/cls_user.php');
include_once(ROOT_PATH . 'includes/cls_shipping.php');
include_once(ROOT_PATH . 'includes/lib_goods.php');
include_once(ROOT_PATH . 'includes/lib_main.php');
include_once(ROOT_PATH . 'includes/lib_common.php');

/**
 * 商品接口
 *
 * @version v1.0
 * @create 2016-11-02
 * @author cyq
 */
class GoodsController extends ApiController
{

	public function __construct()
	{

		parent::__construct();
		$this->data  = $this->input();
		$this->goods = cls_goods::getInstance();
		$this->user  = cls_user::getInstance();
		$this->shipping  = cls_shipping::getInstance();
	}



	public function get_search_attr(){
		$attr = array();
		$cat_id 	= $this->input('cat_id', 0);
		$sql = "SELECT * FROM " .$GLOBALS['ecs']->table('category'). " WHERE cat_id='$cat_id' LIMIT 1";
		$cat = $GLOBALS['db']->getRow($sql);
		$children = get_children($cat_id);
		$cat_filter_attr = explode(',', $cat['filter_attr']);
		foreach ($cat_filter_attr AS $key => $value){
			$sql = "SELECT a.attr_name FROM " . $GLOBALS['ecs']->table('attribute') . " AS a, " . $GLOBALS['ecs']->table('goods_attr') . " AS ga, " . $GLOBALS['ecs']->table('goods') . " AS g WHERE ($children OR " . get_extension_goods($children) . ") AND a.attr_id = ga.attr_id AND g.goods_id = ga.goods_id AND g.is_delete = 0 AND g.is_on_sale = 1 AND g.is_alone_sale = 1 AND a.attr_id='$value'";
				if($temp_name = $GLOBALS['db']->getOne($sql)){
					$sql = "SELECT a.attr_id, MIN(a.goods_attr_id ) AS goods_id, a.attr_value AS attr_value FROM " . $GLOBALS['ecs']->table('goods_attr') . " AS a, " . $GLOBALS['ecs']->table('goods') .
						   " AS g" .
						   " WHERE ($children OR " . get_extension_goods($children) . ') AND g.goods_id = a.goods_id AND g.is_delete = 0 AND g.is_on_sale = 1 AND g.is_alone_sale = 1 '.
						   " AND a.attr_id='$value' ".
						   " GROUP BY a.attr_value";

					$attr_list = $GLOBALS['db']->getAll($sql);
					$attr[] = array('temp_name'=>$temp_name,'value'=>$attr_list);
				}
		}
		$this->success($attr);
	}

	//查询指定几个商品的信息
	public function queryMore(){

		$goods = $this->input('goods', 0);
		$num = $this->input('num', 10);
		$page = $this->input('page', 1);

		$field = " goods_name,goods_thumb,shop_price,goods_id ";

		$condition =" goods_id in (" .$goods. ")  ";

		$result = $this->goods->getGoods($field,$condition,$num,$page);

		foreach($result as $k=>$v){
			 $result[$k]['format_shop_price'] = price_format($v['shop_price']);
			 $result[$k]['goods_thumb']      = get_image_path($v['goods_id'], $v['goods_thumb'], true);
			 $result[$k]['comment_count']    = (string)$this->goods->get_comment_count($v['goods_id']);
		}

		//分页
        $pager = array();
        $pager['page']         = $page;
        $pager['page_size']    = $num;
        $pager['record_count'] = $count = $this->goods->getGoodsCount($condition);
        $pager['page_count']   = $page_count = ($count > 0) ? intval(ceil($count / $num)) : 1;

        $goods_data['list'] = $result;
        $goods_data['pager'] = $pager;



		$this->success($goods_data);

		//$this->success($result);
	}


	/**
	 * 获取商品信息
	 *
	 * @param   string   $cat_id  分类ID
	 * @param   integer  $brand     品牌ID
	 * @param   integer  $min       范围价格 低价
	 * @param   integer  $max       范围价格 高价
	 * @param   integer  $size      分页数量
	 * @param   integer  $page      当前分页
	 * @param   string   $sort      排序方式
	 * @param   string   $order     按字段排序
	 * @param   integer   $user_id     按字段排序
	 *
	 * @since v1.0
	 * @create 2016-11-02
	 */
	public function query()
	{

		$cat_id 	= $this->input('cat_id', 0);
		$supplier_id  	= $this->input('supplier_id', '-1');
		$brand  	= $this->input('brand', 0);
		$min    	= $this->input('min', 0);
		$max    	= $this->input('max', 0);
		$shop_price = $this->input('shop_price', 0);
		$sex 		= $this->input('sex', '');
		$size   	= $this->input('size', 10);
		$page   	= $this->input('page', 1);
		$order   	= $this->input('order', 'desc');
		$sort    	= $this->input('sort', 'sort_order');
		$filter  	= $this->input('filter', '');
		$user_id 	= $this->input('user_id', 0);
		$keywords 	= htmlspecialchars(urldecode(trim($this->input('keywords', ''))));
		$is_real = $this->input('is_real', 3);//0虚拟商品 1真实商品 3虚拟商品和真实商品共存在

		if(isset($cat_id)){
			$children = get_children($cat_id);
		}else{
			$children = 0;
		}

		$user_rank_info  = array(
			'rank_id'   => 0,
			'rank_name' => '普通会员',
			'discount'  => 1,
			'user_rank' => 0,
			'user_id'   => 0,
		);
		if($user_id){
			$user_rank_info = $this->user->get_user_rank($user_id);
		}

		$ext = '';
		if($is_real == 0){
			$children = 0;
			$ext = ' and g.is_real = 0';
		}
		if($is_real == 3){//虚拟商品和真实商品
			$ext = ' and (g.is_real = 0 or g.is_real =1) ';
		}

		$result = $this->goods->category_get_goods($user_rank_info, $children, $keywords, $supplier_id ,$brand, $min, $max, $ext, $size, $page, $order, $sort, $filter, 0, $shop_price, $sex);
		$count = $this->goods->category_get_goods_count($user_rank_info, $children, $keywords, $supplier_id ,$brand, $min, $max, $ext, $size, $page, $order, $sort , $filter, 0, $shop_price, $sex);

		if (empty($result))
		{
			$this->success(array('list'=>array(),'pager'=>array('record_count' => 0)), $code = 200, $msg = '找不到数据');
		}
		//var_dump($obj);
		//sort($result); //..........Yip 改~

		//分页
        $pager = array();
        $pager['page']         = $page;
        $pager['page_size']    = $size;
        $pager['record_count'] = $count;
        $pager['page_count']   = $page_count = ($count > 0) ? intval(ceil($count / $size)) : 1;

        $goods_data['list'] = $result;
        $goods_data['pager'] = $pager;

		$this->success($goods_data);
	}

	/**
	 * 搜索页面内容
	 */
	public function getHotSearch(){
		$hot_data = $this->goods->get_HotSearch();
		$this->success($hot_data);
	}

	/**
	 * 获取商品信息，标题，主图链接，商品链接
	 *
	 */
	public function getGoodsInfo()
	{
		$id      = $this->input('goods_id', 0);
		$user_id = $this->input('user_id', 0);

		//砍价活动信息
		$bargain_id = $this->input('bargain_id', 0);//砍价ID
		if($bargain_id){
			$time = gmtime();
			$bargain_info = $GLOBALS['db']->getRow("SELECT * FROM " . $GLOBALS['ecs']->table('bargain_activity') . " WHERE id = '$bargain_id' and goods_id = '$id' and is_open = 1 and start_time <= '$time' and end_time >= '$time' ");
			if(!$bargain_info){
				$this->error('该商品没有参与砍价活动！');
			}
		}

		//拼团活动信息
		$group_id = $this->input('group_id', 0);//拼团ID
		if($group_id){
			$time = gmtime();
			$group_info = $GLOBALS['db']->getRow("SELECT * FROM " . $GLOBALS['ecs']->table('group_activity') . " WHERE id = '$group_id' and goods_id = '$id' and is_open = 1 and start_time <= '$time' and end_time >= '$time' ");
			if(!$group_info){
				$this->error('该商品没有参与拼团活动！');
			}
		}

		$id = intval($id);
		if (empty($id)) {
			$this->error('参数错误');
		}

		$user_rank_info  = array(
			'rank_id'   => 0,
			'rank_name' => '普通会员',
			'discount'  => 1,
			'user_rank' => 0,
			'user_id'   => 0,
		);
		if($user_id){
			$user_rank_info = $this->user->get_user_rank($user_id);
		}

		$result = $this->goods->getGoodsDetail($id,$user_id);
		if (empty($result))
		{
			$this->error('商品已下架');
		}
		
		$result['shipping_str']   = $this->shipping->get_shipping_goods_str($id,array(1),$result['shop_price']);
		$ship   = $this->shipping->get_shipping_goods_arr($id,array(1),$result['shop_price']);
		$result['shipping_way'] = $ship['way'];
		$result['shipping_fee'] = $ship['fee'];
		$children = get_children($result['cat_id']);

		//评论
		include_once(ROOT_PATH . '/includes/lib_comment.php');
		$comment_list = get_my_comments($id,1,1,'',1);
		$item_list = $comment_list['item_list'];
		foreach($item_list as $item_k => $item_v){

			$item_list[$item_k]['shaidan_id']     = (string)$item_list[$item_k]['shaidan_id'];
			$item_list[$item_k]['shaidan_status'] = (string)$item_list[$item_k]['shaidan_status'];

			$item_list[$item_k]['headimg'] = (string)str_replace("./../","",$item_list[$item_k]['headimg']);

			unset($item_list[$item_k]['good_num']);
			unset($item_list[$item_k]['hide_username']);
			unset($item_list[$item_k]['user_rank']);
			unset($item_list[$item_k]['email']);
			unset($item_list[$item_k]['parent_id']);
			unset($item_list[$item_k]['rec_id']);
			unset($item_list[$item_k]['comment_type']);

			if($item_v['comment_reps']){
				$comment_reps = $item_v['comment_reps'];
				foreach($comment_reps as $rep_k => $rep_v){
					unset($comment_reps[$rep_k]['comment_type']);
					unset($comment_reps[$rep_k]['comment_rank']);
					unset($comment_reps[$rep_k]['ip_address']);
					unset($comment_reps[$rep_k]['status']);
					unset($comment_reps[$rep_k]['user_id']);
					unset($comment_reps[$rep_k]['rec_id']);
					unset($comment_reps[$rep_k]['parent_id']);
					unset($comment_reps[$rep_k]['comment_tag']);
					unset($comment_reps[$rep_k]['buy_time']);
					unset($comment_reps[$rep_k]['good_num']);
					unset($comment_reps[$rep_k]['hide_username']);
					unset($comment_reps[$rep_k]['order_id']);

					$comment_reps[$rep_k]['format_add_time'] = local_date('Y-m-d',$comment_reps[$rep_k]['add_time']);
				}
				$item_list[$item_k]['comment_reps'] = $comment_reps;
			}

		}
        $result['comment'] = $item_list;

		//关联商品
		$relative_goods = $this->goods->category_get_goods($user_rank_info, $children, '', '', '', '', '', '',5, 1,  'desc', 'add_time','');
		sort($relative_goods);
		$result['relative_goods'] = $relative_goods;

		$result['package_goods'] = $other = $this->get_package_goods_list($id,$user_rank_info );

		$_SESSION['user_rank'] = $user_rank_info['user_rank'];
		$result['promotion'] =  get_promotion_info($id,$result['supplier_id']);//促销活动数据集

		//砍价活动信息 add by qinglin 2018.01.23
		if($bargain_id){
			$result['is_bargain'] = 1;//是否有砍价活动（有）
			$time = gmtime();
	        /* 砍价时间倒计时 */
            if ($time >= $bargain_info['start_time'] && $time <= $bargain_info['end_time'])
            {
                $bargain_info['format_start_time']  = local_date('Y-m-d H:i:s',$bargain_info['start_time']);
                $bargain_info['format_end_time']  = local_date('Y-m-d H:i:s',$bargain_info['end_time']);
            }
            else
            {
            	$bargain_info['start_time']  = 0;
                $bargain_info['end_time'] = 0;
                $bargain_info['format_start_time'] = 0;
                $bargain_info['format_end_time'] = 0;
            }
            $bargain_info['format_shop_price']   = price_format($bargain_info['shop_price']);
            $bargain_info['format_low_price']   = price_format($bargain_info['low_price']);

            //获取做砍价活动的商品属性值
            $goods_attr_data = $GLOBALS['db']->getRow("SELECT goods_attr,product_number FROM " . $GLOBALS['ecs']->table('products') . " WHERE product_id = '$bargain_info[product_id]'");
	        $product_id_arr = isset($goods_attr_data['goods_attr']) ? explode('|',$goods_attr_data['goods_attr']) : '';
	        $attr_name = '';
	        if($product_id_arr){
	        	foreach ($product_id_arr as $k => $v) {
	        		$attr_name .=  $GLOBALS['db']->getOne("SELECT attr_value FROM " . $GLOBALS['ecs']->table('goods_attr') . " WHERE goods_attr_id = '$v'");
	        		$attr_name .= '、';
	        	}
	        	$attr_name = trim($attr_name,'、');
	        }
	        if(!empty($attr_name)){
	        	$result['goods_name'] = $result['goods_name'].'（'.$attr_name.'）';//更改商品显示名称，加上属性值
	        }else{
	        	$result['goods_name'] = $result['goods_name'];
	        }

            $result['goods_number'] = isset($goods_attr_data['product_number']) ? $goods_attr_data['product_number'] : $result['goods_number'];//更改商品库存，显示砍价商品属性库存

            unset($bargain_info['supplier_id']);
            unset($bargain_info['is_open']);
		}else{
			$result['is_bargain'] = 0;//是否有砍价活动（无）
			$bargain_info = array(
				'id' => 0,
				'goods_id' => 0,
				'product_id' => 0,
				'goods_name' => '',
				'start_time' => 0,
				'end_time' => 0,
				'shop_price' => 0,
				'low_price' => 0,
				'min_price' => 0,
				'max_price' => 0,
				'guanzhu_num' => 0,
				'join_num' => 0,
				'bargain_num' => 0,
				'format_start_time' => 0,
				'format_end_time' => 0,
				'format_shop_price' => '￥0.00',
				'format_low_price' => '￥0.00'
			);
		}
		$result['bargain_info'] = $bargain_info;
		//print_r($bargain_info);die;

		//拼团活动信息 add by qinglin 2018.01.30
		if($group_id){
			$result['is_group'] = 1;//是否有拼团活动（有）
			$time = gmtime();
      /* 砍价时间倒计时 */
      if ($time >= $group_info['start_time'] && $time <= $group_info['end_time'])
      {
        $group_info['format_start_time']  = local_date('Y-m-d H:i:s',$group_info['start_time']);
        $group_info['format_end_time']  = local_date('Y-m-d H:i:s',$group_info['end_time']);
      }
      else
      {
        $group_info['format_start_time'] = 0;
        $group_info['format_end_time'] = 0;
      }
      $group_info['shop_price']   = $result['org_price'];
      $group_info['group_price']   = round($result['org_price']*$group_info['group_discount'],2);
      $group_info['format_shop_price']   = price_format($result['org_price']);
      $group_info['format_group_price']   = price_format($result['org_price']*$group_info['group_discount']);
      $group_info['join_num'] = $group_info['join_num'] + $group_info['join_num_false'];//参与人数

            //正在拼单中的记录
			$group_log = $GLOBALS['db']->getAll("SELECT id,user_id,end_time FROM " . $GLOBALS['ecs']->table('group_log') . " WHERE parent_id = '0' and is_finish = 0 and end_time > '$time' and goods_id = '$id' and group_id = '$group_id' order by add_time ASC");
			foreach ($group_log as $k => $v) {
				$user_data = $GLOBALS['db']->getRow("SELECT user_name,sex,headimg FROM " . $GLOBALS['ecs']->table('users') . " WHERE user_id = '$v[user_id]' ");
				$headimg = !empty($user_data['headimg']) ? str_replace("./../","",$user_data['headimg']) : 'data/default/sex'.$user_data['sex'].'.png';//头像
				$group_log[$k]['user_name'] = $user_data['user_name'];
				$group_log[$k]['headimg'] = $headimg;
				$group_log[$k]['format_end_time'] = local_date('Y-m-d H:i:s',$v['end_time']);

				//判断拼单人数要求
        $log_num = $GLOBALS['db']->getOne("SELECT count(*) FROM " . $GLOBALS['ecs']->table('group_log') . " WHERE parent_id = '$v[id]' ");
        $log_num = $log_num +1;//加上拼主记录条数

        //还差多少人
        $differ_num = $group_info['group_num'] - $log_num;
        $group_log[$k]['differ_num'] = $differ_num ? $differ_num : 0;

        //获取正在拼单的会员头像数组
        $group_user = $GLOBALS['db']->getAll("SELECT id,user_id FROM " . $GLOBALS['ecs']->table('group_log') . " WHERE (parent_id = '$v[id]' or id = '$v[id]') and is_finish = 0 and end_time > '$time' order by add_time ASC");
        foreach ($group_user as $kk => $vv) {
        	$user_data = $GLOBALS['db']->getRow("SELECT sex,headimg FROM " . $GLOBALS['ecs']->table('users') . " WHERE user_id = '$vv[user_id]' ");
	  			$headimg = !empty($user_data['headimg']) ? str_replace("./../","",$user_data['headimg']) : 'data/default/sex'.$user_data['sex'].'.png';//头像
	  			$user_array['user_id'] = $vv['user_id'];
        	$user_array['headimg'] = $headimg;
          $group_log[$k]['group_user'][] = $user_array;
        }
			}


      unset($group_info['add_time']);
      unset($group_info['group_num']);
      unset($group_info['group_day']);
      unset($group_info['supplier_id']);
      unset($group_info['is_open']);
      unset($group_info['join_num_false']);
		}else{
			$result['is_group'] = 0;//是否有拼团活动（无）
			$group_info = array(
				'id' => 0,
				'goods_id' => 0,
				'goods_name' => '',
				'start_time' => 0,
				'end_time' => 0,
				'group_discount' => 0,
				'join_num' => 0,
				'format_start_time' => 0,
				'format_end_time' => 0,
				'shop_price' => 0,
				'group_price' => 0,
				'format_shop_price' => '￥0.00',
				'format_group_price' => '￥0.00'
			);
			$group_log = array();
		}
		$result['group_info'] = $group_info;//拼团信息
		$result['group_num'] = count($group_log);//拼团记录人数
		$result['group_log'] = $group_log;//拼团记录


		//qq、旺旺客服信息
		$result['chat'] = $GLOBALS['db']->getAll("SELECT cus_name,cus_no,cus_type FROM " . $GLOBALS['ecs']->table('chat_third_customer') . " WHERE supplier_id = '$result[supplier_id]' order by is_master DESC");

		$this->success($result);
	}
	/**
	 * 获取商品信息，标题，主图链接，商品链接（所有，包括下架）
	 *
	 */
	public function getGoodsCoerceInfo()
	{
		$id      = $this->input('goods_id', 0);
		$user_id = $this->input('user_id', 0);

		$user_rank_info = $this->user->get_user_rank($user_id);

		$sql = "SELECT g.goods_id,g.is_on_sale, g.goods_sn, g.is_shipping, g.add_time, g.supplier_id, g.user_id, g.goods_name, g.goods_rank, g.commision1, g.commision2, g.shop_price AS org_price, " .
	        " IFNULL(mp.user_price, g.shop_price * '$user_rank_info[discount]') AS shop_price, " .
	        "   CONVERT(g.market_price,SIGNED) AS market_price, g.goods_brief, g.goods_number,g.goods_total,g.goods_type,g.click_count,g.promote_start_date,g.promote_end_date,g.buymax,g.buymax_start_date,g.buymax_end_date," .
	        " g.goods_desc, b.brand_name AS goods_brand, b.brand_id, g.goods_img,g.is_virtual,g.valid_date, g.goods_weight,g.give_integral," .
	        "   g.cat_id, c.cat_name".
	        " FROM ".$GLOBALS['ecs']->table('goods')." AS g " .

	        " LEFT JOIN " . $GLOBALS['ecs']->table('member_price') .
	        " AS mp " .
	        " ON mp.goods_id = g.goods_id " .
	        " AND mp.user_rank = '$user_rank_info[user_rank]' " .

	        " LEFT JOIN ".$GLOBALS['ecs']->table('brand')." AS b ON g.brand_id = b.brand_id " .
	        " LEFT JOIN ".$GLOBALS['ecs']->table('category')." AS c ON c.cat_id = g.cat_id " .
	        " WHERE g.goods_id = '".$id."' AND g.is_delete = 0 ";
		$row = $GLOBALS['db']->getRow($sql);

		if ($row !== false) {
      if ($row['user_id']) {
        $user_info = $this->user->get_user_info($row['user_id']);
        $row['headimg'] = $user_info['headimg'];//头像
        $row['user_name'] = $user_info['user_name'];//用户名
        $row['nickname'] = $user_info['nickname'];//昵称
      } else {
        $row['headimg'] = '';
        $row['user_name'] = '';
        $row['nickname'] = '';
      }

			$row['follow_status']       = get_follow_status($user_id, $row['user_id']);
			
			$row['diy_user_id']         = $row['user_id'];
			$row['user_id']             = $user_id ? $user_id : 0;
			$row['goods_brand']         = isset($row['goods_brand']) ? $row['goods_brand'] : '';
			$row['goods_gallery']       = cls_goods::getInstance()->getGoodsGallery($id);

			$row['goods_desc']          = str_replace('src="/','src="http://mall.qdshop.com/',htmlspecialchars_decode($row['goods_desc']));
			$row['goods_desc']          = str_replace('src="../','src="http://mall.qdshop.com/../',htmlspecialchars_decode($row['goods_desc']));
			
			$row['brand_id']            = isset($row['brand_id']) ? $row['brand_id'] : 0;
			$row['goods_name']          = encode_output($row['goods_name']);
			$row['format_add_time']     = local_date('Y-m-d',$row['add_time']);
			$row['valid_date']          = isset($row['valid_date']) ? $row['valid_date'] : 0;
			$row['format_valid_date']   = local_date('Y-m-d', $row['valid_date']);
			$row['market_price']        = encode_output($row['market_price']);
			$row['format_market_price'] = price_format($row['market_price']);

      //获取赠送积分
      if($row['give_integral'] == '-1'){
        $row['give_integral'] = round($row['shop_price'],0);
      }

      //促销
			$row['discount_price'] = (string)cls_goods::getInstance()->getGoodsDiscount($id);
      $row['format_discount_price'] = price_format($row['discount_price']);
			/* 促销时间倒计时 */
      $time = gmtime();
      if ($time >= $row['promote_start_date'] && $time <= $row['promote_end_date'])
      {
        $row['discount_end_time']  = $row['promote_end_date'];
        $row['discount_start_time']  = $row['promote_start_date'];
        $row['format_discount_end_time']  = local_date('Y-m-d H:i:s',$row['promote_end_date']);
        $row['format_discount_start_time']  = local_date('Y-m-d H:i:s',$row['promote_start_date']);
      }
      else
      {
        $row['discount_end_time'] = 0;
				$row['discount_start_time']  = 0;
        $row['format_discount_start_time'] = 0;
        $row['format_discount_end_time'] = 0;
      }
      $row['discount_price_img'] = 'data/default/promotion.png';//促销价默认图标

			//限购
			$row['buymax'] = $row['buymax'];
			/* 促销时间倒计时 */
      $time = gmtime();
      if ($time >= $row['buymax_start_date'] && $time <= $row['buymax_end_date'])
      {
        $row['buymax_start_date']  = $row['buymax_start_date'];
        $row['buymax_end_date']  = $row['buymax_end_date'];
        $row['format_buymax_start_date']  = local_date('Y-m-d H:i:s',$row['buymax_start_date']);
        $row['format_buymax_end_date']  = local_date('Y-m-d H:i:s',$row['buymax_end_date']);
      }
      else
      {
        $row['buymax'] = 0;
        $row['buymax_start_date']  = 0;
        $row['buymax_end_date']  = 0;
        $row['format_buymax_start_date'] = 0;
        $row['format_buymax_end_date'] = 0;
      }

			$row['shop_price']          = round($row['shop_price'],2);
      $row['format_shop_price']   = price_format($row['shop_price']);
      $row['is_collected']        = (string)cls_goods::getInstance()->is_collected($id, $user_id);
      $row['comment_count']       = (string)cls_goods::getInstance()->get_comment_count($id);
      $row['selled_count']        = (string)selled_count($id);

      $row['goods_number'] = $row['goods_number'];
      $row['goods_total'] = $row['goods_total'];
      $row['number_per'] = ($row['goods_total'] - $row['goods_number']) / $row['goods_total'] * 100;
      $row['click_count'] = $row['click_count'];
      $row['zan'] = $GLOBALS['db']->getOne("SELECT COUNT(*) FROM " . $GLOBALS['ecs']->table('goods_zan') . " WHERE goods_id = '$row[goods_id]'");
      $row['has_zan'] = $GLOBALS['db']->getOne("SELECT COUNT(*) FROM " . $GLOBALS['ecs']->table('goods_zan') . " WHERE goods_id = '$row[goods_id]' AND user_id = '$user_id'");

      /* 获得商品的规格和属性 */
      $properties = get_goods_properties($id);
      sort($properties['spe']);
      $row['properties'] = $properties['spe'];
      //only one
      $row['is_only_one'] = '1';

			/* 调整唯一属性 */
			$pro = array();
			$pro[] = array(
				'name'=>'商品名称',
				'value'=>$row['goods_name']
			);
			$pro[] = array(
				'name'=>'商品编号',
				'value'=>$row['goods_sn']
			);
			$pro[] = array(
				'name'=>'商品重量',
				'value'=>$row['goods_weight']
			);
			$pro[] = array(
				'name'=>'商品库存',
				'value'=>$row['goods_number']
			);
			$pro[] = array(
				'name'=>'品牌',
				'value'=>$row['goods_brand']
			);
			if($properties['pro']){
				foreach($properties['pro']['属性'] as $v){
					$pro[] = array(
						'name'=>$v['name'],
						'value'=>$v['value']
					);
				}
			}
			$row['properties_pro'] = $pro;

      //Yip
      $row['supplier_name'] ="网站自营";
      $row['supplier'] = new stdClass;
      if ($row['supplier_id'] > 0)
      {
        $sql_supplier = "SELECT s.supplier_id,s.supplier_name,s.add_time,sr.rank_name FROM ". $GLOBALS['ecs']->table("supplier") . " as s left join ". $GLOBALS['ecs']->table("supplier_rank") ." as sr ON s.rank_id=sr.rank_id WHERE s.supplier_id=".$row['supplier_id']." AND s.status=1";
        $shopuserinfo = $GLOBALS['db']->getRow($sql_supplier);
        $other = array();
        $other['sell_num'] = $GLOBALS['db']->get_supplier_goods_count($row['supplier_id']);
        $other['fensi'] = $GLOBALS['db']->get_supplier_fensi_count($row['supplier_id']);

        $other['is_guanzhu'] = is_guanzhu($row['supplier_id'],$user_id) ? 1 : 0;//是否关注

        $sql = "SELECT * FROM " .$GLOBALS['ecs']->table('supplier_shop_config'). " WHERE supplier_id = " . $row['supplier_id'];
        $shopinfo = $GLOBALS['db']->getAll($sql);
        $_goods_attr = array();
        foreach ($shopinfo as $value)
        {
            $_goods_attr[$value['code']] = $value['value'];
        }
        $other['shop_logo'] = empty($_goods_attr['shop_logo'])?'/data/supplier/dianpu.jpg':$_goods_attr['shop_logo'];

        $shopuserinfo = array_merge($shopuserinfo,$other);
        $row['supplier_name']= $shopuserinfo['supplier_name'];
        $row['supplier']= $shopuserinfo;
        //get_dianpu_baseinfo($arr[$row['goods_id']]['supplier_id'],$shopuserinfo);
      }

      foreach ($properties['spe'] as $spe) {
        if (count($spe['values']) != 1) {
          $row['is_only_one'] = '0';
        }
      }

      $row['subsection'] = floor(intval($row['goods_total']) * 0.4);
    }else{
			$this->error('商品已下架 ');
		}

		$result = $row;

		//qq、旺旺客服信息
		$result['chat'] = $GLOBALS['db']->getAll("SELECT cus_name,cus_no,cus_type FROM " . $GLOBALS['ecs']->table('chat_third_customer') . " WHERE supplier_id = '$row[supplier_id]' order by is_master DESC");

		$this->success($result);
	}

	//增加商品查看次数
	public function addClickCount(){
		$goods_id = $this->input('goods_id', 0);
		$sql = "UPDATE " . $GLOBALS['ecs']->table('goods') . " SET click_count = click_count + 1 WHERE goods_id = '$goods_id'";
		$GLOBALS['db']->query($sql);
	}

	//商品点赞
	public function like(){
		$user_id = $this->input('user_id', 0);
		$goods_id = $this->input('goods_id', 0);
		if (empty($user_id)) {
			$this->error('请先登录');
		} else {
			$add_time = gmtime();
			$GLOBALS['db']->query("INSERT INTO " . $GLOBALS['ecs']->table('goods_zan') . " (goods_id, user_id, add_time) VALUES ('$goods_id', '$user_id', '$add_time')");
			$this->success(array(),200,'点赞成功');
		}
	}

	//商品取消点赞
	public function unlike(){
		$user_id = $this->input('user_id', 0);
		$goods_id = $this->input('goods_id', 0);
		if (empty($user_id)) {
			$this->error('请先登录');
		} else {
			$add_time = gmtime();
			$GLOBALS['db']->query("DELETE FROM " . $GLOBALS['ecs']->table('goods_zan') . " WHERE user_id = '$user_id' AND goods_id = '$goods_id'");
			$this->success(array(),200,'取消点赞成功');
		}
	}

	//猜你喜欢
	public function user_like(){
		$goods = $this->input('goods', 0);
		//$goods = "673,672,667";
		$goods_id = $GLOBALS['db']->getAll("SELECT goods_id FROM " . $GLOBALS['ecs']->table('goods') . " WHERE cat_id in (SELECT cat_id FROM " . $GLOBALS['ecs']->table('goods') . " WHERE goods_id in (".$goods.") group by cat_id)");
		$goods_id = array_rand($goods_id,5);
		$goods_id = implode(',', $goods_id);
		$field = " goods_name,goods_thumb,shop_price,goods_id ";

		$condition =" goods_id in (" .$goods_id. ")  ";

		$result = $this->goods->getGoods($field,$condition);

		foreach($result as $k=>$v){
			 $result[$k]['format_shop_price'] = price_format($v['shop_price']);
			 $result[$k]['goods_thumb']      = get_image_path($v['goods_id'], $v['goods_thumb'], true);
			 $result[$k]['comment_count']    = (string)$this->goods->get_comment_count($v['goods_id']);
		}
		$this->success($result);
	}

	/******************/
	// 出售商品
	/******************/
	public function saleGoods()
	{
		$data = $this->input();
		if(!$data['agreement']){
			$this->error('请先同意用户协议！');
		}
		if(!$data['goods_name']){
			$this->error('请填写作品标题！');
		}
		if(!$data['cat_id']){
			$this->error('请选择作品类别！');
		}
		if(!$data['goods_design']){
			$this->error('请填写设计描述！');
		}

		$goods_id = $data['goods_id'];
		$goods_update = 'cat_id = "'.$data['cat_id'].'", goods_name = "'.$data['goods_name'].'", goods_design = "'.$data['goods_design'].'", goods_status = "2"';

		// 插入商品详情图片
		if($data['goods_desc']){
			$file = 'goods_img';
			foreach($data['goods_desc'] as $key => $value){
				if($value){
					$img_url = $this->diy_images($file, $value);
					if($img_url){
						$GLOBALS['db']->query('INSERT INTO '.$GLOBALS['ecs']->table('goods_details').' (goods_id, original_img, add_time) VALUES ("'.$goods_id.'", "'.$img_url.'", "'.time().'")');
					}
				}
			}
		}

		// 插入标签
		if($data['goods_tags']){
			$tags_id = [];
			foreach ($data['goods_tags'] as $key => $value) {
				$exists = $GLOBALS['db']->getRow("SELECT * FROM ".$GLOBALS['ecs']->table('production_tags')." WHERE tags_name = '".$value."'");
				if($exists){
					$GLOBALS['db']->query('UPDATE '.$GLOBALS['ecs']->table('production_tags').' SET number = "'.($exists['number']+1).'" WHERE tags_id = "'.$exists['tags_id'].'"');
					$tags_id[] = $exists['tags_id'];
				}else{
					$GLOBALS['db']->query('INSERT INTO '.$GLOBALS['ecs']->table('production_tags').' (tags_name, number, addtime) VALUES ("'.$value.'", "1", "'.time().'")');
					$tags_id[] = $GLOBALS['db']->insert_id();
				}
			}

			$tags = implode(',', $tags_id);
			$goods_update .= ', goods_tags = "'.$tags.'"';
		}

		// 款式、颜色修改
		if($data['goods_style']){
			foreach ($data['goods_style'] as $key => $value) {
				$GLOBALS['db']->query('UPDATE '.$GLOBALS['ecs']->table('goods_attr').' SET is_sale = 1 WHERE goods_attr_id = "'.$value.'"');
			}
		}
		if($data['goods_color']){
			foreach ($data['goods_color'] as $key => $value) {
				$GLOBALS['db']->query('UPDATE '.$GLOBALS['ecs']->table('goods_attr').' SET is_sale = 1 WHERE goods_attr_id = "'.$value.'"');
			}
		}

		$GLOBALS['db']->query('UPDATE '.$GLOBALS['ecs']->table('goods').' SET '.$goods_update.' WHERE goods_id = "'.$goods_id.'"');
		$GLOBALS['db']->query('UPDATE '.$GLOBALS['ecs']->table('diy').' SET diy_title = "'.$data['goods_name'].'" WHERE goods_id = "'.$goods_id.'"');

		$this->success('出售成功');
	}

	//获取关联商品
	public function get_linked_goods()
	{
		$goods_id 	= $this->input('goods_id', 0);
		$user_id = $this->input('user_id', 0);
		$user_rank_info  = array(
			'rank_id'   => 0,
			'rank_name' => '普通会员',
			'discount'  => 1,
			'user_rank' => 0,
			'user_id'   => 0,
		);
		if($user_id){
			$user_rank_info = $this->user->get_user_rank($user_id);
		}

		$sql = 'SELECT g.goods_id, g.goods_name, g.goods_thumb, g.goods_img, g.shop_price AS org_price, ' .
					"IFNULL(mp.user_price, g.shop_price * '$user_rank_info[discount]') AS shop_price, ".
					'g.market_price, g.promote_price, g.promote_start_date, g.promote_end_date ' .
				'FROM ' . $GLOBALS['ecs']->table('link_goods') . ' lg ' .
				'LEFT JOIN ' . $GLOBALS['ecs']->table('goods') . ' AS g ON g.goods_id = lg.link_goods_id ' .
				"LEFT JOIN " . $GLOBALS['ecs']->table('member_price') . " AS mp ".
						"ON mp.goods_id = g.goods_id AND mp.user_rank = '$user_rank_info[user_rank]' ".
				"WHERE lg.goods_id = '$goods_id' AND g.is_on_sale = 1 AND g.is_alone_sale = 1 AND g.is_delete = 0 ".
				"LIMIT " . $GLOBALS['_CFG']['related_goods_number'];
		$res = $GLOBALS['db']->query($sql);

		$arr = array();
		while ($row = $GLOBALS['db']->fetchRow($res))
		{
			$arr[$row['goods_id']]['goods_id']     = $row['goods_id'];
			$arr[$row['goods_id']]['goods_name']   = $row['goods_name'];
			$arr[$row['goods_id']]['short_name']   = $GLOBALS['_CFG']['goods_name_length'] > 0 ?
				sub_str($row['goods_name'], $GLOBALS['_CFG']['goods_name_length']) : $row['goods_name'];
			$arr[$row['goods_id']]['goods_thumb']  = get_image_path($row['goods_id'], $row['goods_thumb'], true);
			$arr[$row['goods_id']]['goods_img']    = get_image_path($row['goods_id'], $row['goods_img']);
			$arr[$row['goods_id']]['market_price'] = price_format($row['market_price']);
			$arr[$row['goods_id']]['shop_price']   = price_format($row['shop_price']);
			$arr[$row['goods_id']]['url']          = build_uri('goods', array('gid'=>$row['goods_id']), $row['goods_name']);

			if ($row['promote_price'] > 0)
			{
				$arr[$row['goods_id']]['promote_price'] = bargain_price($row['promote_price'], $row['promote_start_date'], $row['promote_end_date']);
				$arr[$row['goods_id']]['formated_promote_price'] = price_format($arr[$row['goods_id']]['promote_price']);
			}
			else
			{
				$arr[$row['goods_id']]['promote_price'] = 0;
			}
		}
		$this->success($arr);
		//return $arr;
	}

	public function getPackageGoodsList(){
		$id      = $this->input('goods_id', 0);
		$user_id = $this->input('user_id', 0);
		$id = intval($id);
		if (empty($id)) {
			$this->error('参数错误');
		}

		$user_rank_info  = array(
			'rank_id'   => 0,
			'rank_name' => '普通会员',
			'discount'  => 1,
			'user_rank' => 0,
			'user_id'   => 0,
		);
		if($user_id){
			$user_rank_info = $this->user->get_user_rank($user_id);
		}
		$result = array();
		$result['package_goods'] = $other = $this->get_package_goods_list($id,$user_rank_info );
		$this->success($result);
	}

	/**
	 * 获取商品详细介绍，仅显示详细介绍
	 * @param goods_id integrate 商品ID
	 * @param user_id  integrate 会员ID
	 * @param display_type  integrate 显示类型 0为商品详细 1为商品属性
	 * @return void
	 */
	public function getGoodsInfoDetail()
	{
		$goods_id      = $this->input('goods_id', 0);
		$user_id       = $this->input('user_id', 0);
		$display_type  = $this->input('display_type', 0);
		$goods_id = intval($goods_id);
		if (empty($goods_id)) {
			$this->error('参数错误');
		}

		// 图片设置为宽度100%，并把图片高度去掉
		$result = $this->goods->getGoodsDetail($goods_id,$user_id);
		$result['goods_desc'] = str_replace('<img','<img style="width:100%;"',$result['goods_desc']);
		$result['goods_desc'] = preg_replace('/height="(\d+)"/','',$result['goods_desc']);
		$result['goods_desc'] = preg_replace('/width="(\d+)"/','',$result['goods_desc']);

		if (empty($result))
		{
			$this->error('找不到数据');
		}

		if($display_type == 1)
		{
			$properties = get_goods_properties($goods_id);  // 获得商品的规格和属性
			$result['properties'] = $properties['pro'];  // 商品属性
			$this->display($result,'goods_attr');
		}
		else
		{
			$this->display($result,'goods');
		}
	}


	/**
	 * 获取商品详情页评论列表
	 * @param goods_id integrate 商品ID
	 * @param user_id  integrate 会员ID
	 * @param page_size  integrate 每页显示多少数据
	 * @param page  integrate 当前页数
	 * @param c_tag  integrate 标签
	 * @param type  integrate 评论类型 0所有评价 1好评 2中评 3差评 4晒单
	 * @return void
	 */
	public function getGoodsComment()
	{

		$goods_id      = $this->input('goods_id', 0);
		$type          = $this->input('type', 0);
		$page_size     = $this->input('page_size', 5);
		$page          = $this->input('page', 1);
		$c_tag         = $this->input('c_tag', '');

		include_once(ROOT_PATH . '/includes/lib_comment.php');

		$comment_list = get_my_comments($goods_id,$type, $page, $c_tag, $page_size);
		//print_r($comment_list['item_list']);
		$item_list = $comment_list['item_list'];
		foreach($item_list as $item_k => $item_v){

			$item_list[$item_k]['shaidan_id']     = (string)$item_list[$item_k]['shaidan_id'];
			$item_list[$item_k]['shaidan_status'] = (string)$item_list[$item_k]['shaidan_status'];

			$item_list[$item_k]['headimg'] = (string)str_replace("./../","",$item_list[$item_k]['headimg']);

			unset($item_list[$item_k]['good_num']);
			unset($item_list[$item_k]['hide_username']);
			unset($item_list[$item_k]['user_rank']);
			unset($item_list[$item_k]['email']);
			unset($item_list[$item_k]['parent_id']);
			unset($item_list[$item_k]['rec_id']);
			unset($item_list[$item_k]['comment_type']);

			if($item_v['comment_reps']){
				$comment_reps = $item_v['comment_reps'];
				foreach($comment_reps as $rep_k => $rep_v){
					unset($comment_reps[$rep_k]['comment_type']);
					unset($comment_reps[$rep_k]['comment_rank']);
					unset($comment_reps[$rep_k]['ip_address']);
					unset($comment_reps[$rep_k]['status']);
					unset($comment_reps[$rep_k]['user_id']);
					unset($comment_reps[$rep_k]['rec_id']);
					unset($comment_reps[$rep_k]['parent_id']);
					unset($comment_reps[$rep_k]['comment_tag']);
					unset($comment_reps[$rep_k]['buy_time']);
					unset($comment_reps[$rep_k]['good_num']);
					unset($comment_reps[$rep_k]['hide_username']);
					unset($comment_reps[$rep_k]['order_id']);

					$comment_reps[$rep_k]['format_add_time'] = local_date('Y-m-d',$comment_reps[$rep_k]['add_time']);
				}
				$item_list[$item_k]['comment_reps'] = $comment_reps;
			}

		}

		$comment_data['comment_list'] = $item_list;//评论列表
		//分页
        $pager = array();
        $pager['page']         = $comment_list['page'];
        $pager['page_size']    = $comment_list['size'];
        $pager['record_count'] = $comment_list['count'];
        $pager['page_count']   = $comment_list['page_count'];
        $comment_data['pager'] = $pager;

		$comment_data['rank_num'] = $this->goods->get_comment_rank_num($goods_id);//评价各数量数据
		$comment_data['goods_tag_num'] = $this->goods->get_comment_goods_tag($goods_id);//评价各数量数据
		$this->success($comment_data);
	}

	/**
	 * 获取商品价格
	 *
	 */
	public function getGoodsPrice()
	{

		$id = $this->input('goods_id', 0);
		$user_id = $this->input('user_id', 0);
		$number = $this->input('number', 1);
		$attr_id = $this->input('attr_id', '');
		$flow_type = $this->input('flow_type', 0);


		$id = intval($id);
		if (empty($id)) {
			Response::render(array(), 400, '参数错误');
		}

		if($user_id){
			$_SESSION = $this->user->get_user_rank($user_id);
		}else{
			$_SESSION['discount'] = 1;
			$_SESSION['user_rank '] = 0;
		}
		$result = $this->goods->getGoodsPrice($id,$number,$attr_id,$flow_type);

		if (empty($result))
		{
			Response::render(array(), 404, '找不到数据');
		}

		Response::render($result);
	}

	/**
	 * 获取单个商品库存
	 *
	 */
	public function getGoodsNumber()
	{
		$id = $this->input('goods_id', 0);
		$id = intval($id);
		if (empty($id)) {
			Response::render(array(), 400, '参数错误');
		}

		$result = cls_goods::getInstance()->getGoodsNumber($id);
		$result = isset($result[$id]['goods_number']) ? intval($result[$id]['goods_number']) : 0;

		Response::render($result);
	}

	/**
	 * 获取商品库存，过个用','分割
	 *
	 */
	public function getGoodsNumbers()
	{
		$id = $this->input('goods_id', 0);
		if (empty($id))
		{
			Response::render(array(), 400, '参数错误');
		}

		$id = explode('-', $id);
		if (!isset($id[0]))
		{
			Response::render(array(), 400, '参数错误');
		}

		$id = array_map('intval', $id);
		$result = cls_goods::getInstance()->getGoodsNumber($id);
		if (empty($result))
		{
			Response::render(array(), 404, '找不到数据');
		}
		$data = array();
		foreach ($id as $v) {
			$temp = array();
			$temp['goods_id'] = $v;
			$temp['goods_number'] = $result[$v]['goods_number'];
			$data[] = $temp;
		}

		Response::render($data);

	}


	/**
	 * 获取属性
	 * @param id goods_id
	 * return json
	 */
	public function getGoodsAttr(){
		$goods_id = (int)$this->input('goods_id', 0);
		if (empty($goods_id)) {
			Response::render(array(), 400, '参数错误');
		}

		/* 获取商品规格列表 */
		$attribute = get_goods_specifications_list($goods_id , ' a.sort_order desc ');

		if($attribute && !empty($attribute))
		{
			foreach ($attribute as $attribute_value)
			{
				//转换成数组
				$_attribute[$attribute_value['attr_id']]['attr_values'][] = array(
					'label'        => $attribute_value['attr_value'],
					'id'           => $attribute_value['goods_attr_id'],
				);
				$_attribute[$attribute_value['attr_id']]['attr_id'] = $attribute_value['attr_id'];
				$_attribute[$attribute_value['attr_id']]['attr_name'] = $attribute_value['attr_name'];
			}
			Response::render($_attribute);
		}

		Response::render(array(), -1, "attribut is null");
	}

	/**
	 * 获取详情
	 * @param int goods_id
	 * return json
	 */
	public function getGoodsDetail(){
		$goods_id = (int)$this->input('goods_id', 0);
		if (empty($goods_id)) {
			Response::render(array(), 400, '参数错误');
		}

		/* 获取商品规格列表 */
		$goods_info = cls_goods::getInstance()->getGoodsDetail($goods_id);
		if($goods_info && !empty($goods_info))
		{
			Response::render($goods_info);
		}

		Response::render(array(), -1, "is null");
	}

	/*
	 * 获取商品邮费
	 * @param int $storage_id
	 * return json
	 */
	public function getGoodsshipping(){
		$storage_id = (int)$this->input('storage_id', 0);
		if (empty($storage_id)) {
			Response::render(array(), 400, '参数错误');
		}

		$result = cls_goods::getInstance()->get_shipping_detail($storage_id);

		if($result && !empty($result))
		{
			Response::render($result);
		}

		Response::render(array(), -1, "is null");

	}

	public function  getCartNum()
	{
		$user_id = isset($this->data['user_id'])? intval($this->data['user_id']) : 1;



		$sql_where = "c.user_id='". $user_id ."' ";

		$number = 0;

	    $sql = 'SELECT SUM(goods_number) AS number' .
	           ' FROM ' . $GLOBALS['ecs']->table('cart') ." AS c ".
	           " WHERE $sql_where AND rec_type = '" . CART_GENERAL_GOODS . "'";
	    $total_num = $GLOBALS['db']->getOne($sql);
	    if ($total_num)
	    {
	        $number = intval($total_num);
	    }

	    $cart['cart_number']=$number;


	    $this->success($cart);
	}

	public function get_package_goods_list($goods_id,$user_rank_info)
	{
		$discount = $user_rank_info['discount'];
		$user_rank = $user_rank_info['user_rank'];

		$now = gmtime();
		$sql = "SELECT ga.act_id,ga.ext_info
				FROM " . $GLOBALS['ecs']->table('goods_activity') . " AS ga, " . $GLOBALS['ecs']->table('package_goods') . " AS pg
				WHERE pg.package_id = ga.act_id
				AND ga.start_time <= '" . $now . "'
				AND ga.end_time >= '" . $now . "'
				AND pg.goods_id = " . $goods_id . "
				GROUP BY pg.package_id
				ORDER BY ga.act_id";

		$res = $GLOBALS['db']->getAll($sql);

		foreach ($res as $tempkey => $value)
		{
			$subtotal = 0;
			$i=1;

			//获取礼包价
			$row = unserialize($value['ext_info']);
			unset($value['ext_info']);
			if ($row)
			{
				foreach ($row as $key=>$val)
				{
					$res[$tempkey][$key] = $val;
				}
			}

			$sql = "SELECT pg.package_id, pg.goods_id, pg.product_id, pg.goods_number, pg.admin_id, p.goods_attr, g.goods_sn, g.goods_name, g.market_price, g.goods_thumb, IFNULL(mp.user_price, g.shop_price * '$discount') AS rank_price
					FROM " . $GLOBALS['ecs']->table('package_goods') . " AS pg
						LEFT JOIN ". $GLOBALS['ecs']->table('goods') . " AS g
							ON g.goods_id = pg.goods_id
						LEFT JOIN ". $GLOBALS['ecs']->table('products') . " AS p
							ON p.product_id = pg.product_id
						LEFT JOIN " . $GLOBALS['ecs']->table('member_price') . " AS mp
							ON mp.goods_id = g.goods_id AND mp.user_rank = '$user_rank'
					WHERE pg.package_id = " . $value['act_id']. "
					ORDER BY pg.package_id, pg.goods_id";

			$goods_ress = $GLOBALS['db']->query($sql);
			$goods_res = array();
			while ($row = $GLOBALS['db']->fetchRow($goods_ress))
			{
				if ($row['goods_id'] == $goods_id )
				{
					$goods_res[0]=$row;
				}
				else
				{
					$goods_res[$i]=$row;
					$i++;
				}
			}

			foreach($goods_res as $key => $val)
			{
				$goods_id_array[] = $val['goods_id'];
				$goods_res[$key]['goods_thumb']  = '/'.get_image_path($val['goods_id'], $val['goods_thumb'], true);
				$goods_res[$key]['market_price'] = price_format($val['market_price']);
				$goods_res[$key]['rank_price']   = $val['rank_price'];
				$subtotal += $val['rank_price'] * $val['goods_number'];
			}

			/* 取商品属性 */
			$sql = "SELECT ga.goods_attr_id, ga.attr_value
					FROM " .$GLOBALS['ecs']->table('goods_attr'). " AS ga, " .$GLOBALS['ecs']->table('attribute'). " AS a
					WHERE a.attr_id = ga.attr_id
					AND a.attr_type = 1
					AND " . db_create_in($goods_id_array, 'goods_id');
			$result_goods_attr = $GLOBALS['db']->getAll($sql);

			$_goods_attr = array();
			foreach ($result_goods_attr as $value)
			{
				$_goods_attr[$value['goods_attr_id']] = $value['attr_value'];
			}

			/* 处理货品 */
			$format = '[%s]';
			foreach($goods_res as $key => $val)
			{
				if ($val['goods_attr'] != '')
				{
					$goods_attr_array = explode('|', $val['goods_attr']);

					$goods_attr = array();
					foreach ($goods_attr_array as $_attr)
					{
						$goods_attr[] = $_goods_attr[$_attr];
					}

					$goods_res[$key]['goods_attr_str'] = sprintf($format, implode('，', $goods_attr));
				}
			}

			ksort($goods_res); //重新排序数组

			/* 重新计算套餐内的商品折扣价 */
			$zhekou=  round(($res[$tempkey]['package_price'] / $subtotal), 8);
			foreach($goods_res as $key => $val)
			{
				$goods_res[$key]['rank_price_zk']=$val['rank_price'] * $zhekou;
				$goods_res[$key]['rank_price_zk_format']= price_format($goods_res[$key]['rank_price_zk']);
			}

			$res[$tempkey]['goods_list']    = $goods_res;
			$res[$tempkey]['subtotal']      = price_format($subtotal);
			$res[$tempkey]['zhekou']      = $zhekou*100;
			$res[$tempkey]['saving']        = price_format(($subtotal - $res[$tempkey]['package_price']));
			$res[$tempkey]['package_price'] = price_format($res[$tempkey]['package_price']);

		}

		return $res;
	}
	
	/**
	 * 商品举报
	 */
	public function doGoodsReport()
	{
		if (empty($this->data['user_id'])) {
			$this->error('请先登录');
		}

		if (empty($this->data['goods_id'])) {
			$this->error('非法操作');
		}

		if (empty($this->data['reason'])) {
			$this->error('请选择原因');
		}

		$sql = "SELECT user_id FROM " . $GLOBALS['ecs']->table('goods') . " WHERE goods_id = " . $this->data['goods_id'];
		$designer_id = $GLOBALS['db']->getOne($sql);
		if ($designer_id == $this->data['user_id']) {
			$this->error('不能举报自己设计的商品');
		}
		
		$result = $this->goods->do_goods_Report($this->data['user_id'], $this->data['goods_id'], $this->data['reason']);
		if ($result) {
			$this->success('举报成功');
		} else {
			$this->error('您已举报过该商品');
		}
	}

	/**
   * 图片处理
   * @param $file 存放图片的根目录
   * @param $base64_image_content 图片base64串
   * @return int|string
   */
  public function diy_images($file, $base64_image_content){
    //将base64编码转换为图片保存
    if (preg_match('/^(data:\s*image\/(\w+);base64,)/', $base64_image_content, $result)) {
      $type = $result[2];
      $path = DATA_DIR . '/'.$file.'/' . date('Ym') . '/';
      $new_file = ROOT_PATH . $path;
      if (!file_exists($new_file)) {
        //检查是否有该文件夹，如果没有就创建，并给予最高权限
        mkdir($new_file, 0777);
      }
      $img = time() . ".{$type}";
      $new_file = $new_file . $img;
      $url = $path . $img;

      //将图片保存到指定的位置
      if (file_put_contents($new_file, base64_decode(str_replace($result[1], '', $base64_image_content)))) {
        return $url;
      }else{
        return 0;
      }
    }else{
      return 0;
    }
  }
}
