<?php
/**
 * 商品模块
 * @2016-11-02 jam
 */

if (!defined('IN_ECS'))
{
    die('Hacking attempt');
}

class cls_goods
{
    protected $_db                = null;
    protected $_tb_goods          = null;
    protected $_tb_goods_details  = null;
    protected $_tb_goods_report   = null;
    protected $_tb_user_attention = null;
    protected $_tb_sku            = null;
    protected $_tb_brand          = null;
    protected $_tb_gallery        = null;
    protected $_tb_diy            = null;
    protected $_tb_goods_description          = null;
    protected $_tb_goods_attr     = null;
    protected $_tb_goods_zan      = null;
    protected $_tb_report_comment = null;
    protected $_price_decimal     = 1;
    protected $_now_time          = 0;
    protected $_mc_time           = 0;
    protected $_plan_time         = 0;
    protected $_mc                = null;
    protected static $_instance   = null;
    public static $_errno = array(
            1 => '操作成功',
            2 => '参数错误',
            3 => '商品不存在',
            4 => '规格不存在',
            5 => '商品下架',
            6 => '库存不足',
            10 => "请选择规格",
            8000 => '系统异常',

    );

    const GOODS_OWN_TYPE_GROUP = 2;     //组合商品

    function __construct()
    {
        $this->user     = cls_user::getInstance();
        $this->_db = $GLOBALS['db'];

        $this->_tb_goods         = $GLOBALS['ecs']->table('goods');
        $this->_tb_goods_details = $GLOBALS['ecs']->table('goods_details');
        $this->_tb_goods_report  = $GLOBALS['ecs']->table('goods_report');
        $this->_tb_user_attention = $GLOBALS['ecs']->table('user_attention');
        $this->_tb_sku           = $GLOBALS['ecs']->table('products');
        $this->_tb_brand         = $GLOBALS['ecs']->table('brand');
        $this->_tb_gallery       = $GLOBALS['ecs']->table('goods_gallery');
        $this->_tb_diy           = $GLOBALS['ecs']->table('diy');
        $this->_tb_goods_attr    = $GLOBALS['ecs']->table('goods_attr');
        $this->_tb_goods_zan     = $GLOBALS['ecs']->table('goods_zan');
        $this->_tb_report_comment     = $GLOBALS['ecs']->table('report_comment');
        $this->_tb_category = $GLOBALS['ecs']->table('category');
        $this->_tb_goods_description    = $GLOBALS['ecs']->table('goods_description');
        $this->_tb_member_price  = $GLOBALS['ecs']->table('member_price');
        $this->_tb_order_goods   = $GLOBALS['ecs']->table('order_goods');
        $this->_tb_order_info    = $GLOBALS['ecs']->table('order_info');
        $this->_tb_collect_goods = $GLOBALS['ecs']->table('collect_goods');
        $this->_tb_comment       = $GLOBALS['ecs']->table('comment');
        $this->_tb_tag           = $GLOBALS['ecs']->table('tag');
        $this->_price_decimal    = MAMA_PRICE_DECIMAL;
        $this->_now_time         = time();
        $this->_plan_time        = 3600*24*15;
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
     * 获取商品限购数量
     *
     */
    public function getGoodsOrderQuota($goods_id)
    {
        $data = $this->getGoodsDetail_PC($goods_id);
        if (!empty($data)) {
            return $data['order_quota'];
        }
        return 0;
    }

    /**
     * 获取商品详细信息
     * @param int $goods_id 商品ID
     * @param int $user_id 会员ID
     * @param int $stock_status 判断是否检查库存
     * @return array
     */
    public function getGoodsDetail($goods_id,$user_id)
    {
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

        $goods_status = $this->_db->getOne("SELECT goods_status FROM " . $this->_tb_goods . " WHERE goods_id = '$goods_id'");
        // 1：diy商品未出售状态，2：diy商品申请出售审核中状态，3：审核不通过状态 不需要判断是否上架
        if ($goods_status == GS_UNSOLD || $goods_status == GS_CHECKED || $goods_status == GS_UNPASS) {
            $sql = "SELECT g.goods_id, g.goods_status, g.is_on_sale, g.goods_sn, g.is_shipping, g.add_time, g.supplier_id, g.user_id, g.goods_name, g.shop_price AS org_price, g.goods_design, " .
                " IFNULL(mp.user_price, g.shop_price * '$user_rank_info[discount]') AS shop_price, " .
                "   CONVERT(g.market_price,SIGNED) AS market_price, g.goods_brief, g.goods_number, g.goods_total, g.goods_type,g.click_count,g.promote_start_date,g.promote_end_date,g.buymax,g.buymax_start_date,g.buymax_end_date," .
                " g.goods_desc, b.brand_name AS goods_brand, b.brand_id, g.goods_img,g.is_virtual,g.valid_date, g.goods_weight,g.give_integral," .
                "   g.cat_id, c.cat_name".
                " FROM {$this->_tb_goods} AS g " .

                " LEFT JOIN " . $this->_tb_member_price .
                " AS mp " .
                " ON mp.goods_id = g.goods_id " .
                " AND mp.user_rank = '$user_rank_info[user_rank]' " .

                " LEFT JOIN {$this->_tb_brand} AS b ON g.brand_id = b.brand_id " .
                " LEFT JOIN {$this->_tb_category} AS c ON c.cat_id = g.cat_id " .
                " WHERE g.goods_id = '{$goods_id}' AND g.is_delete = 0 ";
            $row = $this->_db->getRow($sql);
        } else {
            $sql = "SELECT g.goods_id, g.goods_status, g.is_on_sale, g.goods_sn, g.is_shipping, g.add_time, g.supplier_id, g.user_id, g.goods_name, g.shop_price AS org_price, g.goods_design, " .
                " IFNULL(mp.user_price, g.shop_price * '$user_rank_info[discount]') AS shop_price, " .
                "   CONVERT(g.market_price,SIGNED) AS market_price, g.goods_brief, g.goods_number,g.goods_total,g.goods_type,g.click_count,g.promote_start_date,g.promote_end_date,g.buymax,g.buymax_start_date,g.buymax_end_date," .
                " g.goods_desc, b.brand_name AS goods_brand, b.brand_id, g.goods_img,g.is_virtual,g.valid_date, g.goods_weight,g.give_integral," .
                "   g.cat_id, c.cat_name".
                " FROM {$this->_tb_goods} AS g " .

                " LEFT JOIN " . $this->_tb_member_price .
                " AS mp " .
                " ON mp.goods_id = g.goods_id " .
                " AND mp.user_rank = '$user_rank_info[user_rank]' " .

                " LEFT JOIN {$this->_tb_brand} AS b ON g.brand_id = b.brand_id " .
                " LEFT JOIN {$this->_tb_category} AS c ON c.cat_id = g.cat_id " .
                " WHERE g.goods_id = '{$goods_id}' AND g.is_delete = 0 and g.is_on_sale = 1 ";
            $row = $this->_db->getRow($sql);
        }

        if ($row !== false) {
            if ($row['user_id']) {
                $user_info = $this->user->get_user_info($row['user_id']);
                $row['headimg'] = $user_info['headimg'];//头像
                $row['user_name'] = $user_info['user_name'];//用户名
                $row['nickname'] = $user_info['nickname'];//昵称
                $row['fields'] = trim($user_info['fields'], ',');//领域
            } else {
                $row['headimg'] = '';
                $row['user_name'] = '';
                $row['nickname'] = '';
                $row['fields'] = '';
            }

            $row['follow_status'] = get_follow_status($user_id, $row['user_id']);

            $row['diy_user_id'] = $row['user_id'];//设计师id
            $row['user_id'] = $user_id ? $user_id : 0;//当前登录用户id
            $row['goods_brand']    = isset($row['goods_brand'])?$row['goods_brand']:'';
            $row['goods_gallery']  = $this->getGoodsGallery($goods_id);
            $row['goods_details']  = $this->getGoodsDetailPic($goods_id);
            $row['goods_desc']     = str_replace('src="/','src="http://mall.qdshop.com/',htmlspecialchars_decode($row['goods_desc']));
            $row['goods_desc']     = str_replace('src="../','src="http://mall.qdshop.com/../',htmlspecialchars_decode($row['goods_desc']));

            // $row['goods_desc'] = str_replace('<img','<img style="width:100%;"',$row['goods_desc']);
            // $row['goods_desc'] = preg_replace('/height="(\d+)"/','',$row['goods_desc']);
            // $row['goods_desc'] = $js.preg_replace('/width="(\d+)"/','',$row['goods_desc']);

            $row['brand_id'] = isset($row['brand_id']) ? $row['brand_id'] : 0;
            $row['goods_name']     = encode_output($row['goods_name']);
            $row['format_add_time']= local_date('Y-m-d',$row['add_time']);
            $row['valid_date'] = isset($row['valid_date']) ? $row['valid_date'] : 0;
            $row['format_valid_date']= local_date('Y-m-d',$row['valid_date']);
            $row['market_price']   = encode_output($row['market_price']);
            $row['format_market_price']   = price_format($row['market_price']);

            //获取赠送积分
            if($row['give_integral'] == '-1'){
                $row['give_integral'] = round($row['shop_price'],0);
            }

           //促销
			$row['discount_price'] = (string)$this->getGoodsDiscount($goods_id);
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

            //$row['shop_price']        = (string)$this->getGoodsShopPrice($goods_id);
            $row['shop_price']          = round($row['shop_price'],2);
            $row['format_shop_price']   = price_format($row['shop_price']);
            $row['is_collected']        = (string)$this->is_collected($goods_id,$user_id);
            $row['comment_count']       = (string)$this->get_comment_count($goods_id);
            $row['selled_count']        = (string)selled_count($goods_id);

			//调整库存
            /*if($row['goods_type'] >0){
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
            }*/
            $row['goods_number'] = $row['goods_number'];
            $row['goods_total'] = $row['goods_total'];
            $row['number_per'] = $row['goods_number'] / $row['goods_total'] * 100;
            $row['click_count'] = $row['click_count'];
            $row['zan'] = $this->_db->getOne("SELECT COUNT(*) FROM " . $this->_tb_goods_zan . " WHERE goods_id = '$row[goods_id]'");
            $row['has_zan'] = $this->_db->getOne("SELECT COUNT(*) FROM " . $this->_tb_goods_zan . " WHERE goods_id = '$row[goods_id]' AND user_id = '$user_id'");
            $row['has_report'] = $GLOBALS['db']->getOne("SELECT COUNT(*) FROM " . $GLOBALS['ecs']->table('goods_report') . " WHERE goods_id = '$row[goods_id]' AND user_id = '$user_id'");
            $row['goods_design'] = $row['goods_design'];
            $row['t_goods_number'] = $this->_db->getOne("SELECT COUNT(*) FROM " . $this->_tb_goods . " WHERE user_id = '$user_id' AND goods_status = " . GS_PASS);//T恤作品数
            $row['t_diy_number'] = $this->_db->getOne("SELECT COUNT(*) FROM " . $this->_tb_diy . " WHERE user_id = '$user_id'");//设计作品数
            $row['fans_number'] = $this->_db->getOne("SELECT COUNT(*) FROM " . $this->_tb_user_attention . " WHERE user_id = '$row[user_id]'");//设计作品数

            /* 获得商品的规格和属性 */
            $properties = get_goods_properties($goods_id, $row['goods_status']);
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
                $shopuserinfo = $this->_db->getRow($sql_supplier);
                $other = array();
                //$sql = "select logo from ". $GLOBALS['ecs']->table('supplier_street') ." where supplier_id = ".$row['supplier_id'];
                //$shop_logo = $GLOBALS['db']->getOne($sql);
                $other['sell_num'] = $this->get_supplier_goods_count($row['supplier_id']);
                $other['fensi'] = $this->get_supplier_fensi_count($row['supplier_id']);
                //$other['shop_logo'] = $shop_logo?:'/mobile/themesmobile/default/images/goods/dianpu.jpg';

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

            return $row;
        }
        return false;
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

    /**
     * 获取促销价
     * @param $goods_id
     * @return int
     */
    public function getGoodsDiscount($goods_id){
        $now_time = time();
        $sql = "select is_promote, promote_price, promote_start_date, promote_end_date from " . $this->_tb_goods . " where goods_id='{$goods_id}' and promote_start_date <='$now_time' and promote_end_date >= '$now_time' limit 1";
        $discount_info = $this->_db->getRow($sql);

        if($discount_info){
            return $this->formatPrice($discount_info['promote_price']);
        }

        return 0;
    }

    /**
     * 格式化商品价格
     * @ram $price
     * @return int
     */
    public function formatPrice($price){
        if(intval($price) == $price){
            $price = intval($price);
        }else{
            $price = bcdiv(intval(bcmul($price,100,2)),100,2);
        }
        return $price;
    }

    /**
     * 获取商品售价
     * @param $goods_id
     * @return int
     */
    public function getGoodsShopPrice($goods_id){
        $discount_price = $this->getGoodsDiscount($goods_id);
        if($discount_price>0){
            $shop_price = $discount_price;
        }else{
            $sql = "select shop_price FROM {$this->_tb_goods} where goods_id='{$goods_id}'";
            $shop_price = $this->_db->getOne($sql);
        }
        return $this->formatPrice($shop_price);
    }

    /**
     * 获得商品相册
     * @param $goods_id
     */
    public function getGoodsGallery($goods_id)
    {
        $sql = "SELECT img_url, thumb_url ,img_original, goods_attr_id, goods_attr_id2 FROM {$this->_tb_gallery} WHERE goods_id='{$goods_id}' ORDER BY img_sort ASC LIMIT 0,10";
        $gallery = $this->_db->getAll($sql);
        /*$sql = "SELECT design_img FROM {$this->_tb_diy} WHERE goods_id='{$goods_id}' LIMIT 1";
        $design_img = $this->_db->getOne($sql);*/
        $sql = "SELECT design_img FROM {$this->_tb_goods} WHERE goods_id='{$goods_id}' LIMIT 1";
        $design_img = $this->_db->getOne($sql);
        $arr = array(
            'img_url' => $design_img,
            'thumb_url' => $design_img,
            'img_original' => $design_img,
            'goods_attr_id' => 0,
            'goods_attr_id2' => 0
        );
        array_push($gallery, $arr);
        return $gallery;
    }

    /**
     * 获得商品详情图册
     * @param $goods_id
     */
    public function getGoodsDetailPic($goods_id)
    {
        $sql = "SELECT thumb_img, original_img, sort_order, add_time FROM {$this->_tb_goods_details} WHERE goods_id='{$goods_id}' ORDER BY sort_order ASC, add_time DESC LIMIT 0,10";
        $details = $this->_db->getAll($sql);

        return $details;
    }

    /**
     * 获得pc商品详情
     * @param $goods_id
     */
    public function getGoodsPcDesc($goods_id)
    {
        $sql = "SELECT description FROM {$this->_tb_goods_description} WHERE goods_id='{$goods_id}'";
        return $this->_db->getOne($sql);
    }

    /**
     * 获取商品类型
     * @param $goods_id
     */
    public function getGoodsOwnType($goods_id){
        $sql = "SELECT goods_own_type FROM {$this->_tb_goods} WHERE goods_id='{$goods_id}'";
        $goods_own_type = $this->_db->getOne($sql);
        return $goods_own_type;
    }




    /**
     * 获取商品评论
     * @param $goods_id
     * @return mixed
     */
    public function getGoodsComment($goods_id){
        $sql = "select * from mall_goods_comment where status='1' and uid !='0' and goods_id='".$goods_id."' order by addtime desc limit 3";
        $goods_comment = $this->_db->getAll($sql);
        return $goods_comment;
    }



    /**
     * 查询SKU库存
     *
     * @param int $product_id 货品id
     * @return int|boolean
     * @create 2015-03-28
     */
    public function getProductNumber($product_id)
    {
        $product_id = intval($product_id);
        if (empty($product_id)) {
            return false;
        }

        $sql = "SELECT P.product_number,P.goods_id, G.goods_own_type FROM {$this->_tb_sku} P, {$this->_tb_goods} G WHERE P.product_id={$product_id} AND G.goods_id=P.goods_id";
        $result = $this->_db->getRow($sql);

        if (!isset($result['goods_id'])) {
            return false;
        }

        // 组合商品返回处理后的库存
        return $result['goods_own_type'] == 2 ? get_goods_link_stock($result['goods_id']) : $result['product_number'];
    }

    /**
     * 获取多个SKU信息
     *
     * @param array $product_id 一个整形数组: array(1,2,3,4)
     * @return boolean|array
     * @since v1.0
     * @create 2015-03-28
     */
    public function getGoodsSku($product_id)
    {
        $ids = isset($product_id[0]) ? $product_id : array($product_id);
        if (empty($ids))
        {
            return false;
        }

        $sql = "SELECT P.goods_id, G.goods_name, G.goods_sn, G.goods_own_type, G.shop_price, G.market_price, G.is_delete, G.is_on_sale, G.without_return,
            G.delivery_method, G.is_real, G.is_customs, G.goods_thumb, G.goods_weight, P.product_number, P.erp_goods_id, P.product_sn, P.ext_price, G.cdn_status,
            G.supplier_id, G.brand_id, G.cat_id, G.delivery_method, G.customs
            FROM {$this->_tb_sku} P
            LEFT JOIN {$this->_tb_goods} G ON G.goods_id=P.goods_id
            WHERE product_id ".db_create_in($ids);
        $res = $this->_db->getAll($sql);

        if (empty($res))
        {
            return array();
        }

        foreach ($res as &$v)
        {
            $discount_price = $this->getGoodsDiscount($v['goods_id'],true);
            if($discount_price>0)
            {
                $v['shop_price'] = $discount_price;
                unset($discount_price);
            }
            // 处理价格, 加上货品价格
//          if ($v['ext_price'] > 0)
//          {
                $v['shop_price'] += $v['ext_price'];
//          }
            $v['shop_price'] = price_format_decimal($v['shop_price']);
            $v['market_price'] = price_format_decimal($v['market_price']);

            // 图片路径
            $v['goods_thumb'] = get_img_url($v['goods_thumb'], $v['cdn_status']);

            // 组合商品库存
            if($v['goods_own_type'] == 2)
            {
                $v['product_number'] = get_goods_link_stock($v['goods_id']);
            }
        }
        unset($v);

        return $res;
    }

    /**
     * 获取单个SKU信息
     *
     * @param int $goodsid 商品id
     * @param array $attrid 商品属性id数组
     * @create 2015-03-30
     */
    public function getGoodsAttr($goodsid, array $attrid)
    {
        $goodsid = (int)$goodsid;
        if ($goodsid < 1 || empty($attrid))
        {
            return false;
        }

        $attrid_str = '';
        if(count($attrid)>1)
        {
            $sql = "SELECT goods_attr_id,goods_id,attr_id FROM {$this->_tb_goods_attr} WHERE goods_id = {$goodsid} AND " . db_create_in($attrid, 'goods_attr_id') . " ORDER BY attr_id ASC";
            $attrid_ary = $this->_db->getAll($sql);
            foreach($attrid_ary as &$v){
                $attrid_str .= $v['goods_attr_id'].'|';
            }
            $attrid_str = trim($attrid_str,'|');
        }else{
            $attrid_str = trim(implode('|', $attrid), '|');
        }

        // 查询货品信息
        $sql = "SELECT P.goods_id, P.product_id, P.product_sn, G.goods_name, G.goods_sn, G.goods_own_type,
            G.shop_price, G.market_price,G.is_on_sale,G.is_delete,G.is_customs,P.product_number,
            P.ext_price, G.delivery_method, G.goods_weight, G.cdn_status, G.goods_thumb, G.order_quota,
            G.is_real, G.supplier_id, G.without_return, P.erp_goods_id
            FROM {$this->_tb_sku} P
            LEFT JOIN {$this->_tb_goods} G ON G.goods_id=P.goods_id
            WHERE P.goods_id=$goodsid AND P.goods_attr LIKE '%{$attrid_str}%'";
        $product = $this->_db->getRow($sql);
        if (!$product)
        {
            return false;
        }
        $product['shop_price'] = $this->getGoodsShopPrice($goodsid);

        // 组合商品库存
        if ($product['goods_own_type'] == 2)
        {
            $product['product_number'] = get_goods_link_stock($product['goods_id']);
        }

        // 获取属性, 有缓存
        $mc_key = md5('attr_'.$product['product_id'].'_'.implode('|',$attrid));
        if (!$goods_attr = $this->_mc->get($mc_key))
        {
            require_once(ROOT_PATH.'/includes/lib_order.php');
            $goods_attr = get_goods_attr_info($attrid);
            $this->_mc->set($mc_key, $goods_attr, false, 3600*24);
        }
        $product['goods_attr'] = $goods_attr;
        $product['goods_attr_id'] = $attrid;

        return $product;
    }


    /**
     * 异步获取商品价格
     * @param  $goods_id 商品ID   $type = 'list' 列表  else 详情页
     * @param  $flow_type 商品类型   0（普通商品）、1（团购商品）、2（拍卖商品）、3（夺宝奇兵）、4（积分商城）、6（预售商品）、7（虚拟团购）、101（砍价）、102（拼团）
     * @return array
     */
    public function getGoodsPrice($goods_id,$number = 1,$attr_id = array(),$flow_type = 0)
    {


        //$number     = (isset($_REQUEST['number'])) ? intval($_REQUEST['number']) : 1;
        //$attr_id    = isset($_REQUEST['attr']) ? $_REQUEST['attr'] : array();

        if ($number == 0)
        {
            $res['qty'] = $number = 1;
        }
        else
        {
            $res['qty'] = $number;
        }
        if(empty($attr_id)){
            $attr_id = 0;
        }
        $res['attr_num'] = $this->get_product_attr_num($goods_id,$attr_id);

        $shop_price  = get_final_price($goods_id, $number, true, $attr_id);
        $mark_price = $this->get_mark_price($goods_id);

        $shop_price = ($shop_price>=0) ? $shop_price : 0;

        //拼团
        if($flow_type == 102){
            //获取没处理过的商品单价
            $shop_price = $GLOBALS['db']->getOne("SELECT shop_price FROM " . $GLOBALS['ecs']->table('goods') . " WHERE goods_id = '$goods_id' ");
            if (!empty($attr_id))
            {
                $spec_price   = spec_price($attr_id);//属性价格
                $shop_price += $spec_price;
            }
            //获取拼团活动折扣
            $time = gmtime();
            $group_info = $GLOBALS['db']->getRow("SELECT id,group_discount FROM " . $GLOBALS['ecs']->table('group_activity') . " WHERE goods_id = '$goods_id' and is_open = 1 and start_time <= '$time' and end_time >= '$time' ");
            //计算拼团折扣价
            $shop_price = round($group_info['group_discount'] * $shop_price,2);
        }

        $res['result'] = price_format($shop_price * $number);
        $res['result1'] = price_format($mark_price);
        $res['result_jf'] = floor($shop_price * $number);

        //预售，检查库存是否足够
        $current_number = $res['attr_num'];
        if($number > $current_number)
        {
            $res['qty'] = $current_number;
        }

        $res['qty'] = $res['qty']?:'0';
        $res['attr_num'] = $res['attr_num']?:'0';

        return $res;
        // $min_price  = get_final_price($goods_id, $number, true, 0);
        // $mark_price_min = $this->get_mark_price($goods_id);
        // $sql = "SELECT *
            // FROM " . $this->_tb_goods_attr ."
            // WHERE `goods_id` =".$goods_id;
        // $row = $GLOBALS['db']->getAll($sql);

        // if($row)
        // {
            // $ret = array();
            // foreach($row as $key=>$val){
                // if($val['attr_price']){
                    // $ret[$val['attr_id']][$val['attr_price']] = $val;
                // }
            // }

            // $ret1 = $ret2 = $ret3 = $ret4 = array();
            // foreach($ret as $k=>$v){
                // ksort($v);
                // $ret2 = end($v);
                // $ret1[$k] = $ret2['attr_price'];
            // }
            // $max_price = $min_price + array_sum($ret1);
            // $mark_price_max = $mark_price_min + array_sum($ret1);
            // if($min_price == $max_price){
                // $res['result'] = price_format($min_price * $number);
                // $res['result1'] = price_format($mark_price_min * $number);
                // $res['result2'] = price_format($min_price);
            // }else{
                // $res['result'] = price_format($min_price * $number)." ~ ".price_format($max_price * $number);
                // $res['result1'] = price_format($mark_price_min * $number)." ~ ".price_format($mark_price_max * $number);
                // $res['result2'] = price_format($mark_price_min)." ~ ".price_format($mark_price_max);
            // }
        // }else{
            // $res['result'] = price_format($min_price * $number);
            // $res['result1'] = price_format($mark_price_min * $number);
            // $res['result2'] = price_format($min_price);
        // }
        // $ret_result = array('min_price'=>$min_price,'max_price'=>$max_price);

        // return $ret_result;

    }

    /**
    * 异步获取商品价格后重新赋值goods_list 相关价格
    * @param  $goods_list 商品列 array   $type = '' 针对不同列表不同处理  special_goods,category,cat_goods,hot_goods
    * @return array
    */
    public function ajax_goods_list_price($goods_list=array(), $type='')
    {
        $result['code'] = 0;
        $result['msg'] = '';
        $goods_id = array();
        $price_temp = array();
        $discount_default = $type == 'special_goods' ? 10:9.9;
        if(!empty($goods_list)){
            foreach($goods_list as $goods_key=>$goods){
                $goods_id[] = $goods['goods_id'];
            }
            $price = cls_goods::getInstance()->getGoodsPrice($goods_id,'list');
            foreach($price as $k=>$v){
                $price_temp[$v['goods_id']] = $v;
            }
            foreach($goods_list as $goods_key=>$goods){
                if(array_key_exists($goods['goods_id'],$price_temp)){
                    if($type == 'special_goods' || $type == 'cat_goods'){
                        if(!empty($price_temp[$goods['goods_id']]['market_price'])){
                            $goods_list[$goods_key]['discount'] = round(($price_temp[$goods['goods_id']]['shop_price']/$price_temp[$goods['goods_id']]['market_price'])*10,1);
                        }else{
                            $goods_list[$goods_key]['discount'] = $discount_default;
                        }
                    }
                    $goods_list[$goods_key]['shop_price'] = $price_temp[$goods['goods_id']]['shop_price'];
                    $goods_list[$goods_key]['market_price'] = $price_temp[$goods['goods_id']]['market_price'];
                }else{
                    continue;
                }
            }
            $result['data'] = $goods_list;
        }else{
            $result['code'] = -1;
        }
        return $result;
    }

    /**
     * 获取商品信息
     * @param  $field
     * @param  $condition
     * @return array
     */
    public function getGoods($field='*', $condition='',$num=10,$page=1)
    {
        $sql = "SELECT ". $field ." FROM ". $this->_tb_goods;
        if (!empty($condition)) {
             $where = " WHERE $condition";
             $sql .= $where;
         }
		//$sql .= ' limit '.$num;
        //$row = $this->_db->getAll($sql);
		$res = $this->_db->selectLimit($sql, $num, ($page - 1) * $num);
		$arr = array();
        while ($row = $this->_db->fetchRow($res))
        {
			$arr[] = $row;
		}
        return $arr;
    }

    public function getGoodsCount($condition='')
    {
        $sql = "SELECT count(*) FROM ". $this->_tb_goods;
        if (!empty($condition)) {
             $where = " WHERE $condition";
             $sql .= $where;
         }
        $row = $this->_db->getOne($sql);
        return $row;
    }


    public function get_settings_max_by(){
        $sql = "SELECT * FROM mall_shop_config WHERE code = 'cart_max_single_sku' ";
        $row = $this->_db->getRow($sql);
        return $row;
    }



    /**
     * 获得分类下的商品
     *
     * @access  public
     * @param   array    $user_rank_info  会员等级信息
     * @param   string   $children  分类ID
     * @param   string   $keywords  搜索关键词
     * @param   integer  $brand     品牌ID
     * @param   integer  $min       范围价格 低价
     * @param   integer  $max       范围价格 高价
     * @param   string   $ext       扩展条件
     * @param   integer  $size      分页数量
     * @param   integer  $page      当前分页
     * @param   string   $sort      排序方式
     * @param   string   $order     按字段排序
     * @param   integer  $is_stock
     * @return  array
     */
    public function category_get_goods($user_rank_info, $children, $get_keywords = '', $supplier_id, $brand = 0, $min = 0, $max = 0, $ext = '', $size = 0, $page = 0, $order = 'desc', $sort = 'sort_order', $filter="", $is_stock = 0, $shop_price = 0, $sex = '', $designer_id = 0)
    {

        //$filter = (isset($_REQUEST['filter'])) ? intval($_REQUEST['filter']) : 0;
        $where = "g.is_on_sale = 1 AND g.is_alone_sale = 1 AND g.is_delete = 0 AND goods_status IN (0,4)  ";
        if($children){

            $where .= " AND ($children OR " . get_extension_goods($children) . ')';
        }


        if($supplier_id != '-1' && $supplier_id != ''){
            $where .= ' AND g.supplier_id='.$supplier_id;
        }
        /*
        if($filter==1){

            $where .= ' AND g.supplier_id=0 ';

        }elseif($filter==2){

            $where .= ' AND g.supplier_id>0 ';

        }else{}
        */

        if ($brand > 0)
        {
            if (strstr($brand, '_'))
            {
                $brand_sql =str_replace("_", ",", $brand);
                $where .=  "AND g.brand_id in ($brand_sql) ";
            }
            else
            {
                $where .=  "AND g.brand_id=$brand ";
            }
        }

        if ($min > 0)
        {
            $where .= " AND g.shop_price >= $min ";
        }

        if ($max > 0)
        {
            $where .= " AND g.shop_price <= $max ";
        }

        if ($shop_price > 0)
        {
            $where .= " AND g.shop_price = $shop_price ";
        }

        if (!empty($sex))
        {
            $where .= " AND ga.attr_value = '$sex' AND ga.is_sale = 1 ";
        }

        if (!empty($designer_id))
        {
            $where .= " AND g.user_id = '$designer_id' ";
        }

        if($sort == 'goods_number')
        {
            $where .= " AND g.goods_number != 0 ";
        }
        if(!empty($is_stock))
        {
            $where .= " AND g.goods_number > 0 ";
        }

        if($filter && in_array($filter,array('is_best','is_new','is_hot'))){
             $where .= " AND g.".$filter." = 1 ";
        }

        if($sort == 'sold_count'){
            $sort = 'salenum';
        }

        if($sort && $sort != 'salenum' && $sort != 'zan'){
             $sort = "g.".$sort;
        }


        /* 关键词搜索 */
        $tag_where = '';
        $keywords = '';
        if (!empty($get_keywords))
        {
            define('_SP_', chr(0xFF).chr(0xFE));
            define('UCS2', 'ucs-2be');

            include_once(ROOT_PATH.'includes/lib_splitword_hunuo.php');
            $Recordkw = str_replace(array("\'"), array(''), trim($get_keywords));
            $cfg_soft_lang_hunuo = 'utf-8';
            $sp_hunuo = new SplitWord($cfg_soft_lang_hunuo, $cfg_soft_lang_hunuo);
            $sp_hunuo->SetSource($Recordkw, $cfg_soft_lang_hunuo, $cfg_soft_lang_hunuo);
            $sp_hunuo->SetResultType(1);
            $sp_hunuo->StartAnalysis(TRUE);
            $word_hunuo = $sp_hunuo->GetFinallyResult(' ');
            //echo  $word_hunuo;
            $word_hunuo = preg_replace("/[ ]{1,}/", " ", trim($word_hunuo));
            $replacef_hunuo = explode(' ', $word_hunuo);

            $keywords = 'AND (';
            $goods_ids = array();
            foreach ($replacef_hunuo AS $key => $val)
            {
                if ($key > 0 && $key < count($replacef_hunuo) && count($replacef_hunuo) > 1)
                {
                    $keywords .= " OR ";
                }
                $val        = mysql_like_quote(trim($val));
                // $sc_dsad    = $_REQUEST['sc_ds'] ? " OR goods_desc LIKE '%$val%'" : '';
                $keywords  .= "(goods_name LIKE '%$val%' OR goods_sn LIKE '%$val%' OR keywords LIKE '%$val%')";

                /*$sql = 'SELECT DISTINCT goods_id FROM ' . $this->_tb_tag . " WHERE tag_words LIKE '%$val%' ";


                $res = $this->_db->query($sql);




                while ($row = $this->_db->fetchRow($res))
                {
                    $goods_ids[] = $row['goods_id'];
                }*/
            }

            $keywords .= ')'; //print_r($keywords);die;



            /*$goods_ids = array_unique($goods_ids);
            $tag_where = implode(',', $goods_ids);


            if (!empty($tag_where))
            {
                $tag_where = 'OR g.goods_id ' . db_create_in($tag_where);
            }*/


        }

        /* 获得商品列表 */
        $sort = ($sort == 'shop_price' ? 'shop_price' : $sort);

        $sql = "SELECT g.goods_id, g.goods_name, g.supplier_id, g.is_shipping, g.click_count, g.is_promote, g.goods_number, g.goods_total, g.market_price,g.cat_id,g.brand_id," .
            " g.is_new, g.is_best, g.is_hot, g.shop_price AS org_price, g.user_id, " .
            " IFNULL(mp.user_price, g.shop_price * '$user_rank_info[discount]') AS shop_price, g.promote_price, " .
            " IF(g.promote_price != '' " .
            " AND g.promote_start_date < " . $this->_now_time .
            " AND g.promote_end_date > " . $this->_now_time . ", g.promote_price, shop_price) " .
            " AS shop_p, g.goods_type, " .
            " g.promote_start_date, g.promote_end_date, g.goods_brief, g.goods_thumb, g.goods_img, g.best_img, g.user_id " .
            " FROM " . $this->_tb_goods .
            " AS g " .
            " LEFT JOIN " . $this->_tb_member_price .
            " AS mp " .
            " ON mp.goods_id = g.goods_id " .
            " AND mp.user_rank = '$user_rank_info[user_rank]' " .
            " WHERE $where $keywords $ext " .
            " ORDER BY $sort $order";
            //echo $sql;
            //error_log($sql,3,'koel.log');

        if (!empty($sex)) {
            $sql = "SELECT g.goods_id, g.goods_name, g.supplier_id, g.is_shipping, g.click_count, g.is_promote, g.goods_number, g.goods_total, g.market_price,g.cat_id,g.brand_id," .
                " g.is_new, g.is_best, g.is_hot, g.shop_price AS org_price, g.user_id, " .
                " IFNULL(mp.user_price, g.shop_price * '$user_rank_info[discount]') AS shop_price, g.promote_price, " .
                " IF(g.promote_price != '' " .
                " AND g.promote_start_date < " . $this->_now_time .
                " AND g.promote_end_date > " . $this->_now_time . ", g.promote_price, shop_price) " .
                " AS shop_p, g.goods_type, " .
                " g.promote_start_date, g.promote_end_date, g.goods_brief, g.goods_thumb, g.goods_img, g.best_img, g.user_id " .
                " FROM " . $this->_tb_goods .
                " AS g " .
                " LEFT JOIN " . $this->_tb_member_price .
                " AS mp " .
                " ON mp.goods_id = g.goods_id " .
                " AND mp.user_rank = '$user_rank_info[user_rank]' " .
                " LEFT JOIN " . $this->_tb_goods_attr .
                " AS ga " .
                " ON ga.goods_id = g.goods_id " .
                " WHERE $where $keywords $ext " .
                " ORDER BY $sort $order";
        }

        if ($sort=='zan') {
            $sql = "SELECT (SELECT COUNT(*) FROM " . $this->_tb_goods_zan . " gz WHERE gz.goods_id = g.goods_id) AS zan, g.goods_id, g.goods_name, g.supplier_id, g.is_shipping, g.click_count, g.is_promote, g.goods_number, g.goods_total, g.market_price,g.cat_id,g.brand_id," .
                " g.is_new, g.is_best, g.is_hot, g.shop_price AS org_price, g.user_id, " .
                " IFNULL(mp.user_price, g.shop_price * '$user_rank_info[discount]') AS shop_price, g.promote_price, " .
                " IF(g.promote_price != '' " .
                " AND g.promote_start_date < " . $this->_now_time .
                " AND g.promote_end_date > " . $this->_now_time . ", g.promote_price, shop_price) " .
                " AS shop_p, g.goods_type, " .
                " g.promote_start_date, g.promote_end_date, g.goods_brief, g.goods_thumb, g.goods_img, g.best_img, g.user_id " .
                " FROM " . $this->_tb_goods .
                " AS g " .
                " LEFT JOIN " . $this->_tb_member_price .
                " AS mp " .
                " ON mp.goods_id = g.goods_id " .
                " AND mp.user_rank = '$user_rank_info[user_rank]' " .
                " WHERE $where $keywords $ext " .
                " ORDER BY $sort $order";
        }

        if ($sort=='salenum')
        {
            $sql = "SELECT IFNULL(o.num,0) AS salenum, g.supplier_id, g.goods_id, g.goods_name, g.is_shipping, g.click_count, g.is_promote, g.goods_number, g.goods_total, g.cat_id, g.brand_id, " .
                " g.market_price, g.is_new, g.is_best, g.is_hot, g.shop_price AS org_price, g.user_id, " .
                " IFNULL(mp.user_price, g.shop_price * '$user_rank_info[discount]') AS shop_price, g.promote_price, g.goods_type, " .
                " g.promote_start_date, g.promote_end_date, g.goods_brief, g.goods_thumb, g.goods_img, g.best_img " .
                " FROM " . $this->_tb_goods .
                " AS g " .
                " LEFT JOIN " . $this->_tb_member_price .
                " AS mp " .
                " ON mp.goods_id = g.goods_id " .
                " AND mp.user_rank = '$user_rank_info[user_rank]' " .
                " LEFT JOIN " .
                " (SELECT " .
                " SUM(og.`goods_number`) " .
                " AS num,og.goods_id " .
                " FROM " . $this->_tb_order_goods . " AS og" .
                " , " . $this->_tb_order_info . " AS oi" .
                " WHERE oi.pay_status = 2 " .
                " AND oi.order_status >= 1 " .
                " AND oi.order_id = og.order_id " .
                " GROUP BY og.goods_id) " .
                " AS o " .
                " ON o.goods_id = g.goods_id " .
                " WHERE $where $keywords $ext" .
                " ORDER BY $sort $order";
        }
        $res = $this->_db->selectLimit($sql, $size, ($page - 1) * $size);
        $arr = array();
        while ($row = $this->_db->fetchRow($res))
        {
            //print_r($arr);
            $arr[$row['goods_id']]['goods_id']         = $row['goods_id'];
            $arr[$row['goods_id']]['supplier_id']      = $row['supplier_id'];
            $arr[$row['goods_id']]['goods_name']       = $row['goods_name'];
            $arr[$row['goods_id']]['goods_number']     = $row['goods_number'];
            $arr[$row['goods_id']]['goods_total']      = $row['goods_total'];
            $arr[$row['goods_id']]['number_per']       = $row['goods_number'] / $row['goods_total'] * 100;
            $arr[$row['goods_id']]['is_promote']       = $row['is_promote'];
            $arr[$row['goods_id']]['is_new']           = $row['is_new'];
            $arr[$row['goods_id']]['is_hot']           = $row['is_hot'];
            $arr[$row['goods_id']]['is_best']          = $row['is_best'];
            $arr[$row['goods_id']]['goods_brief']      = $row['goods_brief'];
            $arr[$row['goods_id']]['market_price']     = $row['market_price'];
            $arr[$row['goods_id']]['format_market_price']     = price_format($row['market_price']);
            $arr[$row['goods_id']]['shop_price']       = $row['shop_price'];
            $arr[$row['goods_id']]['format_shop_price'] = price_format($row['shop_price']);
            $arr[$row['goods_id']]['attr_type']        = $row['goods_type'];
            $arr[$row['goods_id']]['is_shipping']      = $row['is_shipping'];
            $arr[$row['goods_id']]['discount_price']   = (string)$this->getGoodsDiscount($row['goods_id']);
            $arr[$row['goods_id']]['format_discount_price'] = price_format($arr[$row['goods_id']]['discount_price']);
            $arr[$row['goods_id']]['goods_thumb']      = get_image_path($row['goods_id'], $row['goods_thumb'], true);
            $arr[$row['goods_id']]['best_img']         = $row['best_img'];
            $arr[$row['goods_id']]['comment_count']    = (string)self::get_comment_count($row['goods_id']);
            $arr[$row['goods_id']]['sold_count']       = (string)selled_count($row['goods_id']);
            $arr[$row['goods_id']]['click_count']      = $row['click_count'];
            $arr[$row['goods_id']]['zan']              = $this->_db->getOne("SELECT COUNT(*) FROM " . $this->_tb_goods_zan . " WHERE goods_id = '$row[goods_id]'");
            $arr[$row['goods_id']]['goods_type']       = $row['goods_type'];
            $arr[$row['goods_id']]['cat_id']           = $row['cat_id'];
            $arr[$row['goods_id']]['brand_id']         = $row['brand_id'];

            $arr[$row['goods_id']]['user_id'] = $row['user_id'];
            if ($row['user_id']) {
                $user_info = $this->user->get_user_info($row['user_id']);
                $arr[$row['goods_id']]['headimg'] = $user_info['headimg'];//头像
                $arr[$row['goods_id']]['user_name'] = $user_info['user_name'];//用户名
                $arr[$row['goods_id']]['nickname'] = $user_info['nickname'];//昵称
            } else {
                $arr[$row['goods_id']]['headimg'] = '';
                $arr[$row['goods_id']]['user_name'] = '';
                $arr[$row['goods_id']]['nickname'] = '';
            }

            $arr[$row['goods_id']]['is_collected']   = (string)$this->is_collected($row['goods_id'],$user_rank_info['user_id']);
            // echo (new ReflectionFunction('selled_count'))->getFileName();
            //Yip
            $arr[$row['goods_id']]['supplier_name'] ="网站自营";
            $arr[$row['goods_id']]['supplier'] = new stdClass;
            if ($row['supplier_id'] > 0)
            {
                 $sql_supplier = "SELECT s.supplier_id,s.supplier_name,s.add_time,sr.rank_name FROM ". $GLOBALS['ecs']->table("supplier") . " as s left join ". $GLOBALS['ecs']->table("supplier_rank") ." as sr ON s.rank_id=sr.rank_id WHERE s.supplier_id=".$row['supplier_id']." AND s.status=1";
                 $shopuserinfo = $this->_db->getRow($sql_supplier);
                 $arr[$row['goods_id']]['supplier_name']= $shopuserinfo['supplier_name'];
                 $arr[$row['goods_id']]['supplier']= empty($shopuserinfo) ? '':$shopuserinfo;
                 //get_dianpu_baseinfo($arr[$row['goods_id']]['supplier_id'],$shopuserinfo);
            }

            // 获得商品的规格和属性
            $properties = get_goods_properties($row['goods_id']);
            $arr[$row['goods_id']]['properties'] = array_values($properties['spe']);//去除键值

        }
        //按销量排序解决排序不对问题

        // if($sort=='salenum'){
            // foreach ($arr as $key => $value) {
                // $count[$key] = $value['count'];

            // }
            // if($order=="DESC"){
                // array_multisort($count,SORT_DESC, $arr);
            // }else{
                // array_multisort($count,SORT_ASC, $arr);
            // }
        // }
        //$this->function_dump('selled_count');
        //print_r($arr);
        return array_values($arr);
    }

    //获取商品数量
    function category_get_goods_count($user_rank_info, $children, $get_keywords = '', $supplier_id, $brand = 0, $min = 0, $max = 0, $ext = '', $size = 0, $page = 0, $order = 'desc', $sort = 'sort_order', $filter, $is_stock = 0, $shop_price = 0, $sex = '', $designer_id = 0)
    {

        //$filter = (isset($_REQUEST['filter'])) ? intval($_REQUEST['filter']) : 0;

        //$where = "g.is_on_sale = 1 AND g.is_alone_sale = 1 AND ".
        //    "g.is_delete = 0 AND ($children OR " . get_extension_goods($children) . ')';
/*
        if($children){
            $where = "g.is_on_sale = 1 AND g.is_alone_sale = 1 AND ".
            "g.is_delete = 0 AND ($children OR " . get_extension_goods($children) . ')';
        }else{
            $where = " 1 ";
        }

        if($supplier_id != '-1' && $supplier_id != ''){
            $where .= ' AND g.supplier_id='.$supplier_id;
        }
*/

	$where = "g.is_on_sale = 1 AND g.is_alone_sale = 1 AND "."g.is_delete = 0 AND goods_status IN (0,4)  ";
	if($children){

		$where .= " AND ($children OR " . get_extension_goods($children) . ')';
	}


	if($supplier_id != '-1' && $supplier_id != ''){
		$where .= ' AND g.supplier_id='.$supplier_id;
	}

        /*
        if($filter==1){

            $where .= ' AND g.supplier_id=0 ';

        }elseif($filter==2){

            $where .= ' AND g.supplier_id>0 ';

        }else{}
        */

        if ($brand > 0)
        {
            if (strstr($brand, '_'))
            {
                $brand_sql =str_replace("_", ",", $brand);
                $where .=  "AND g.brand_id in ($brand_sql) ";
            }
            else
            {
                $where .=  "AND g.brand_id=$brand ";
            }
        }

        if ($min > 0)
        {
            $where .= " AND g.shop_price >= $min ";
        }

        if ($max > 0)
        {
            $where .= " AND g.shop_price <= $max ";
        }

        if ($shop_price > 0)
        {
            $where .= " AND g.shop_price = $shop_price ";
        }

        if (!empty($sex))
        {
            $where .= " AND ga.attr_value = '$sex' AND ga.is_sale = 1 ";
        }

        if (!empty($designer_id))
        {
            $where .= " AND g.user_id = '$designer_id' ";
        }

        if($sort == 'goods_number')
        {
            $where .= " AND g.goods_number != 0 ";
        }
        if(!empty($is_stock))
        {
            $where .= " AND g.goods_number > 0 ";
        }

        if($filter && in_array($filter,array('is_best','is_new','in_hot'))){
             $where .= " AND g.".$filter." = 1 ";
        }

        if($sort == 'sold_count'){
             $sort = 'salenum';
        }

        if($sort && $sort != 'salenum' && $sort != 'zan'){
             $sort = "g.".$sort;
        }


        /* 关键词搜索 */
        $tag_where = '';
        $keywords = '';
        if (!empty($get_keywords))
        {
            //define('_SP_', chr(0xFF).chr(0xFE));
            //define('UCS2', 'ucs-2be');

            include_once(ROOT_PATH.'includes/lib_splitword_hunuo.php');
            $Recordkw = str_replace(array("\'"), array(''), trim($get_keywords));
            $cfg_soft_lang_hunuo = 'utf-8';
            $sp_hunuo = new SplitWord($cfg_soft_lang_hunuo, $cfg_soft_lang_hunuo);
            $sp_hunuo->SetSource($Recordkw, $cfg_soft_lang_hunuo, $cfg_soft_lang_hunuo);
            $sp_hunuo->SetResultType(1);
            $sp_hunuo->StartAnalysis(TRUE);
            $word_hunuo = $sp_hunuo->GetFinallyResult(' ');
            //echo  $word_hunuo;
            $word_hunuo = preg_replace("/[ ]{1,}/", " ", trim($word_hunuo));
            $replacef_hunuo = explode(' ', $word_hunuo);

            $keywords = 'AND (';
            $goods_ids = array();
            foreach ($replacef_hunuo AS $key => $val)
            {
                if ($key > 0 && $key < count($replacef_hunuo) && count($replacef_hunuo) > 1)
                {
                    $keywords .= " OR ";
                }
                $val        = mysql_like_quote(trim($val));
//                $sc_dsad    = $_REQUEST['sc_ds'] ? " OR goods_desc LIKE '%$val%'" : '';
                $keywords  .= "(goods_name LIKE '%$val%' OR goods_sn LIKE '%$val%' OR keywords LIKE '%$val%')";

                /*$sql = 'SELECT DISTINCT goods_id FROM ' . $this->_tb_tag . " WHERE tag_words LIKE '%$val%' ";


                $res = $this->_db->query($sql);




                while ($row = $this->_db->fetchRow($res))
                {
                    $goods_ids[] = $row['goods_id'];
                }*/
            }

            $keywords .= ')';



            /*$goods_ids = array_unique($goods_ids);
            $tag_where = implode(',', $goods_ids);


            if (!empty($tag_where))
            {
                $tag_where = 'OR g.goods_id ' . db_create_in($tag_where);
            }*/


        }

        /* 获得商品列表 */
        $sort = ($sort == 'shop_price' ? 'shop_price' : $sort);

        $sql = "SELECT count(*) " .
            " FROM " . $this->_tb_goods .
            " AS g " .
            " LEFT JOIN " . $this->_tb_member_price .
            " AS mp " .
            " ON mp.goods_id = g.goods_id " .
            " AND mp.user_rank = '$user_rank_info[user_rank]' " .
            " WHERE $where $keywords $ext ";

            //echo $sql;
            //error_log($sql,3,'koel.log');
        if (!empty($sex)) {
            $sql = "SELECT count(*) " .
                " FROM " . $this->_tb_goods .
                " AS g " .
                " LEFT JOIN " . $this->_tb_member_price .
                " AS mp " .
                " ON mp.goods_id = g.goods_id " .
                " AND mp.user_rank = '$user_rank_info[user_rank]' " .
                " LEFT JOIN " . $this->_tb_goods_attr .
                " AS ga " .
                " ON ga.goods_id = g.goods_id " .
                " WHERE $where $keywords $ext ";
        }

        if ($sort=='zan') {
            $sql = "SELECT count(*) " .
                " FROM " . $this->_tb_goods .
                " AS g " .
                " LEFT JOIN " . $this->_tb_member_price .
                " AS mp " .
                " ON mp.goods_id = g.goods_id " .
                " AND mp.user_rank = '$user_rank_info[user_rank]' " .
                " WHERE $where $keywords $ext ";
        }

        if ($sort=='salenum')
        {
            $sql = "SELECT count(*) " .
                " FROM " . $this->_tb_goods .
                " AS g " .
                " LEFT JOIN " . $this->_tb_member_price .
                " AS mp " .
                " ON mp.goods_id = g.goods_id " .
                " AND mp.user_rank = '$user_rank_info[user_rank]' " .
                " LEFT JOIN " .
                " (SELECT " .
                " SUM(og.`goods_number`) " .
                " AS num,og.goods_id " .
                " FROM " . $this->_tb_order_goods . " AS og" .
                " , " . $this->_tb_order_info . " AS oi" .
                " WHERE oi.pay_status = 2 " .
                " AND oi.order_status >= 1 " .
                " AND oi.order_id = og.order_id " .
                " GROUP BY og.goods_id) " .
                " AS o " .
                " ON o.goods_id = g.goods_id " .
                " WHERE $where $keywords $ext";

        }
        $count = $GLOBALS['db']->getOne($sql);
        return $count;
    }



    /**
     * 获取评论条数
     * @param $id int 商品ID
     * @param $type int 评论类型
     * @param $flag int 星级
     * @return int $count
     * */
    function get_comment_count( $id, $type = 0, $flag = 0 )
    {
        $where = "";
        if ( $flag == 1 )
        {
            $where = "comment_rank = 5";
        }
        if ( $flag == 2 )
        {
            $where = "comment_rank = 3 or comment_rank = 4";
        }
        if ( $flag == 3 )
        {
            $where = "comment_rank = 1 or comment_rank = 2";
        }
        if ( 0 < $flag )
        {
            $where = " AND (".$where.")";
        }
        $sql = "SELECT COUNT(*) FROM ".$this->_tb_comment.( " WHERE id_value = '".$id."' AND comment_type = '{$type}' AND status = 1 AND parent_id = 0 " ).$where;
        $count = $this->_db->getOne( $sql );
        return $count;
    }

    //获取各个评价数量值
    function get_comment_rank_num($goods_id){
        //评价晒单
        $rank_num['rank_a'] = $this->_db->getOne("SELECT COUNT(*) AS num FROM ".$GLOBALS['ecs']->table('comment')." WHERE id_value = '$goods_id' AND status = 1 AND comment_rank in (5,4)");
        $rank_num['rank_b'] = $this->_db->getOne("SELECT COUNT(*) AS num FROM ".$GLOBALS['ecs']->table('comment')." WHERE id_value = '$goods_id' AND status = 1 AND comment_rank in (3,2)");
        $rank_num['rank_c'] = $this->_db->getOne("SELECT COUNT(*) AS num FROM ".$GLOBALS['ecs']->table('comment')." WHERE id_value = '$goods_id' AND status = 1 AND comment_rank = 1");
        $rank_num['rank_total'] = $rank_num['rank_a'] + $rank_num['rank_b'] + $rank_num['rank_c'];
        $rank_num['rank_pa'] = ($rank_num['rank_a'] > 0) ? round(($rank_num['rank_a'] / $rank_num['rank_total']) * 100,1) : 100;
        $rank_num['rank_pb'] = ($rank_num['rank_b'] > 0) ? round(($rank_num['rank_b'] / $rank_num['rank_total']) * 100,1) : 100;
        $rank_num['rank_pc'] = ($rank_num['rank_c'] > 0) ? round(($rank_num['rank_c'] / $rank_num['rank_total']) * 100,1) : 100;
        $rank_num['shaidan_num'] = $this->_db->getOne("SELECT COUNT(*) AS num FROM ".$GLOBALS['ecs']->table('shaidan')." WHERE goods_id = '$goods_id' AND status = 1");
        return $rank_num;
    }

    //获取买家印象（商品标签）
    function get_comment_goods_tag($goods_id){
        $_CFG = $GLOBALS['_CFG'];
        $tag_arr = array();
        $res = $this->_db->getAll("SELECT tag_id,goods_id,tag_name FROM ".$GLOBALS['ecs']->table('goods_tag')." WHERE goods_id = '$goods_id' AND state = 1");
        foreach ($res as $v)
        {
            $v['tag_num'] = $this->_db->getOne("SELECT COUNT(*) AS num FROM ".$GLOBALS['ecs']->table('comment')." WHERE id_value = '$goods_id' AND status = 1 AND FIND_IN_SET($v[tag_id],comment_tag)");
            $tag_arr[] = $v;
        }
        $comment_tags = array();
        if ($tag_arr)
        {

            $tag_arr = array_sort($tag_arr,'tag_num','desc');
            foreach ($tag_arr as $key => $val)
            {
                if ($_CFG['tag_show_num'] > 0)
                {
                    if (($key + 1) <= $_CFG['tag_show_num'])
                    {
                        $comment_tags[] = $val;
                    }
                }
                else
                {
                    $comment_tags[] = $val;
                }
            }
        }

        return $comment_tags;
    }

    /**
     * 获取相关属性的库存
     * @param int $goodid 商品id
     * @param string(array) $attrids 商品属性id的数组或者逗号分开的字符串
     */
    private function get_product_attr_num($goodid,$attrids=0){
        $ret = array();

        /* 判断商品是否参与预售活动，如果参与则获取商品的（预售库存-已售出的数量） */
        if(!empty($_REQUEST['pre_sale_id']))
        {
            $pre_sale = pre_sale_info($_REQUEST['pre_sale_id'], $goods_num);
            //如果预售为空或者预售库存小于等于0则认为不限购
            if(!empty($pre_sale) && $pre_sale['restrict_amount'] > 0){

                $product_num = $pre_sale['restrict_amount'] - $pre_sale['valid_goods'];

                return $product_num;
            }
        }
        //print_r($attrids);
        if(empty($attrids)){
            $ginfo = $this->get_goods_attr_value($goodid,'goods_number');

            return $ginfo['goods_number'];
            //$ret[$attrids] = $ginfo['goods_number'];
            //return $ret;
        }
        if(!is_array($attrids)){
            $attrids = explode(',',$attrids);
        }

        $goods_attr_array = sort_goods_attr_id_array($attrids);

        if(isset($goods_attr_array['sort']))
        {
            $goods_attr = implode('|', $goods_attr_array['sort']);

            $sql = "SELECT product_id, goods_id, goods_attr, product_sn, product_number
                    FROM " . $GLOBALS['ecs']->table('products') . "
                    WHERE goods_id = $goodid AND goods_attr = '".$goods_attr."' LIMIT 0, 1";
            $row = $GLOBALS['db']->getRow($sql);

            return $row['product_number'];
        }

        //sort($attrids);
        //$attrids = implode('|',$attrids);
        //$attrids = array_unique($attrids);
        //$attrids = str_replace(',','|',$attrids);

        /*
        echo "<pre>";
        print_r($row);

        foreach ($row as $key => $value)
        {
            if(in_array($value['goods_attr'],$attrids)){
                $ret[$value['goods_attr']] = $value['product_number'];
            }
        }
        return $ret;
        */
    }

    /**
     * 获取商品的相关信息
     * @param int $goodsid 商品id
     * @param string $name  要获取商品的属性名称,多个，就用逗号分隔
     */
    private function get_goods_attr_value($goodsid,$name='goods_sn,goods_name')
    {
        $sql = "select ".$name." from ". $GLOBALS['ecs']->table('goods') ." where goods_id=".$goodsid;
        $row = $GLOBALS['db']->getRow($sql);
        return $row;
    }

    private function get_mark_price($goods_id)
    {
        $sql = "SELECT market_price".
               " FROM " .$GLOBALS['ecs']->table('goods').
               " WHERE goods_id = '$goods_id'";
        $res = $GLOBALS['db']->getRow($sql);
        return $res['market_price'];
    }

    public function get_HotSearch($type = 'wap')
    {
        if($type == 'wap'){
            $row = $GLOBALS['db']->getOne("select value from ". $GLOBALS['ecs']->table('mobile_shop_config') ." where code='search_keywords'");
            if (!empty($row)){
                $searchkeywords = explode(',', trim($row));
            }
            else{
            $searchkeywords = array();
            }
            return $searchkeywords;
        }

        if (!empty($GLOBALS['_CFG']['search_keywords'])){
            $searchkeywords = explode(',', trim($GLOBALS['_CFG']['search_keywords']));
        }
        else{
            $searchkeywords = array();
        }
        return $searchkeywords;
    }

    /**
     * 获取本店铺商品数量
     */
    public function get_supplier_goods_count($suppid=0){

        $suppid = (intval($suppid)>0) ? intval($suppid) : intval($_GET['suppId']);
        $sql="SELECT count(`goods_id`) FROM ".$GLOBALS['ecs']->table('goods')." as g WHERE  g.is_on_sale = 1 AND g.is_alone_sale = 1 AND g.supplier_id='$suppid'";
       return $GLOBALS['db']->getOne($sql);
    }
    /**
     * 获取店铺被收藏数量
     */
    public function get_supplier_fensi_count($suppid=0){
        $suppid = (intval($suppid)>0) ? intval($suppid) : intval($_GET['suppId']);
        $sql = "SELECT count(*) FROM " .$GLOBALS['ecs']->table('supplier_guanzhu') ." WHERE supplierid=$suppid";
        return $GLOBALS['db']->getOne($sql);
    }

    /**
     * 商品举报
     */
    public function do_Goods_Report($user_id, $goods_id, $reason)
    {
        // 是否已举报过
        $sql = "SELECT COUNT(*) FROM " . $this->_tb_goods_report . " WHERE goods_id = '$goods_id' AND user_id = '$user_id'";
        $has_report = $this->_db->getOne($sql);
        if ($has_report > 0) {
            return false;
        } else {
            // 写入数据
            $sql = "SELECT goods_name, user_id AS designer_id FROM " . $this->_tb_goods . " WHERE goods_id = '$goods_id'";
            $info = $this->_db->getRow($sql);
            $sql = "INSERT INTO " . $this->_tb_goods_report . " (goods_id, goods_name, user_id, designer_id, report_reason, report_time)".
                    "VALUES ('$goods_id', '$info[goods_name]', '$user_id', '$info[designer_id]', '$reason', $this->_now_time)";
            $this->_db->query($sql);
            return true;
        }
    }

    /**
     * 商品评论举报
     */
    public function do_Comment_Report($user_id = 0, $comment_id = 0, $reason = '', $type = 0, $goods_id = 0, $be_user_id = 0, $content = '')
    {
        // 是否已举报过
        $sql = "SELECT COUNT(*) FROM " . $this->_tb_report_comment . " WHERE comment_id = '$comment_id' AND user_id = '$user_id'";
        $has_report = $this->_db->getOne($sql);
        if ($has_report > 0) {
            return false;
        } else {
            // 写入数据
            $sql = "INSERT INTO " . $this->_tb_report_comment . " (type, id, user_id, be_user_id, report_reason, comment, add_time, comment_id)".
                    "VALUES ('$type', '$goods_id', '$user_id', '$be_user_id', '$reason', '$content', $this->_now_time, '$comment_id')";
            $this->_db->query($sql);
            return true;
        }
    }

}
