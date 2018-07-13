<?php
/**
 * 商品模块
 * @2016-10-26 cyq
 */

if (!defined('IN_ECS'))
{
    die('Hacking attempt');
}

include_once(ROOT_PATH . 'includes/cls_base.php');
include_once(ROOT_PATH . 'includes/cls_cart.php');


class cls_checkout
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
        $this->_tb_user          = $this->_tb_users = $GLOBALS['ecs']->table('users');
        $this->_tb_cart          = $GLOBALS['ecs']->table('cart');
        $this->_tb_goods         = $GLOBALS['ecs']->table('goods');
        $this->_tb_goods_attr    = $GLOBALS['ecs']->table('goods_attr');
        $this->_tb_attribute     = $GLOBALS['ecs']->table('attribute');
        $this->_tb_member_price  = $GLOBALS['ecs']->table('member_price');
        $this->_tb_order_goods   = $GLOBALS['ecs']->table('order_goods');
        $this->_tb_order_info    = $GLOBALS['ecs']->table('order_info');
        $this->_tb_goods_activity= $GLOBALS['ecs']->table('goods_activity');
        $this->_tb_package_goods = $GLOBALS['ecs']->table('package_goods');
        $this->_tb_favourable_activity = $GLOBALS['ecs']->table('favourable_activity');
        $this->_tb_group_goods   = $GLOBALS['ecs']->table('group_goods');
        $this->_tb_pickup_point  = $GLOBALS['ecs']->table('pickup_point');
        $this->_tb_region        = $GLOBALS['ecs']->table('region');
        $this->_tb_user_address  = $GLOBALS['ecs']->table('user_address');
        $this->_tb_payment       = $GLOBALS['ecs']->table('payment');
        $this->_tb_user_bonus    = $GLOBALS['ecs']->table('user_bonus');
        $this->_tb_bonus_type    = $GLOBALS['ecs']->table('bonus_type');
        $this->_tb_goods_activity= $GLOBALS['ecs']->table('goods_activity');
        $this->_tb_virtual_goods_card       = $GLOBALS['ecs']->table('virtual_goods_card');
        $this->_tb_exchange_goods       = $GLOBALS['ecs']->table('exchange_goods');
        $this->_tb_shipping      = $GLOBALS['ecs']->table('shipping');
        $this->_now_time         = time();
        $this->_plan_time        = 3600*24*15;

        $this->cart = cls_cart::getInstance();
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
     * @description 获取普通购物流程的订单流程页面
     * @param int $user_rank_info  会员等级信息
     * @param int $flow_type  0（普通商品）、1（团购商品）、2（拍卖商品）、3（夺宝奇兵）、4（积分商城）、6（预售商品）、7（虚拟团购）
     * @param string $sel_goods  选中商品
     * @return void
     */
    public function getCheckoutProfile($user_rank_info, $flow_type=0, $sel_goods = '', $flow_order = array(),$supplier_id=0,$sel_goods2 = '',$device)
    {

        $return = array(
            'code' => 500,
            'error_code' => 500,
            'message' => ''
        );


        include_once('includes/lib_transaction.php');

        $user_id = $user_rank_info['user_id'];

        //正常购物流程  清空其他购物流程情况
        $_SESSION['extension_id'] = isset($flow_order['extension_id'])? $flow_order['extension_id'] :'';
        $_SESSION['extension_code'] = isset($flow_order['extension_code'])? $flow_order['extension_code'] :'';

        /* 检查购物车中是否有商品 */

        $sql_where = "user_id ='". $user_id ."' ";

        $sql = "SELECT COUNT(*) FROM " . $this->_tb_cart .
            " WHERE $sql_where " .
            "AND parent_id = 0 AND is_gift = 0 AND rec_type = '$flow_type' ";

        $new_g = array();

        if ($this->_db->getOne($sql) == 0)
        {
            $return['message'] = $GLOBALS['_LANG']['no_goods_in_cart'];
            return $return;
        }
        else
        {


            if($flow_type != CART_EXCHANGE_GOODS)
            {
                //赠品
                //验证购物车中提交过来的商品中参加的活动是否都正常start
                $_REQUEST['sel_goods'] = $sel_goods;
                $favourable_list = $this->cart->favourable_list($user_rank_info,false);
                if($favourable_list){
                    $sql_where = $user_id>0 ? "user_id='". $user_id ."' " : "session_id = '" . SESS_ID . "' AND user_id=0 ";
                    foreach($favourable_list as $fk=>$fv){
                        if(!$fv['available']){
                            $sql = "select count(rec_id) as num from ". $this->_tb_cart .
                            " WHERE $sql_where " .
                            "AND is_gift = ".$fv['act_id'];
                            if($this->_db->getOne($sql) > 0){
                                $return['message'] = '购物车中参加['.$fv['act_name'].']活动的商品未满足条件，请重新设置或者将其赠品删除';
                                return $return;
                            }
                        }
                    }
                    unset($sql_where);
                }


                //限购
                $time_xg_now = gmtime();
                $sql_plus = $message = '';
                $sql_plus = ' and c.user_id = '.$user_rank_info['user_id'];
                if($sel_goods){
                    $sql_plus = " and c.rec_id in (".$sel_goods.")";
                }
                $sql_plus .= " and g.supplier_id = ".$supplier_id." AND c.rec_type = '$flow_type'";
                $sql="select c.rec_id, c.goods_number,g.goods_id, g.goods_name,g.is_buy, g.buymax, g.buymax_start_date, g.buymax_end_date, g.goods_thumb,  g.market_price,c.goods_price, g.supplier_id, c.goods_attr from ".$this->_tb_cart. " AS c left join ".$this->_tb_goods. " AS g on c.goods_id=g.goods_id where 1 ".$sql_plus;
                $goods_list = $this->_db->getAll($sql);
                //$new_goods_list = array();

                foreach($goods_list as $k => $v)
                {
                    if($v['is_buy'] == 1 && $v['buymax'] >0 && $v['buymax_start_date'] < $time_xg_now  && $v['buymax_end_date'] > $time_xg_now )
                    {
                        $num_cart_old=$this->_db->getOne("select sum(og.goods_number) from ". $this->_tb_order_goods ." AS og , ". $this->_tb_order_info ." AS o where o.user_id='$user_id' and o.order_id = og.order_id and add_time > ". $v['buymax_start_date'] ." and add_time < ". $v['buymax_end_date'] ."  and og.goods_id = " . $v['goods_id'] );
                        $num_total = $num_cart_old +  intval($v['goods_number']);
                        if ( $num_total > intval($v['buymax']) )
                        {
                            $num_else=intval($v['buymax'])-$num_cart_old;
                            $message .= "商品 <font color=#330099>【".$v['goods_name']."】</font> 限购期间每人限购 <font color=#330099>". $v['buymax'] . "</font> 件<br>";
                            if ($num_cart_old)
                            {
                                $message .="您在限购期间已经成功购买过 <font color=#330099>$num_cart_old</font> 件！<br>";
                            }
                            $message .= "您最多只能再买 <font color=#330099>". $num_else ."</font> 件<br>";
                        }
                    }
                    $goods_list[$k]['goods_attr'] = str_replace("\n", " ", $v['goods_attr']);
                    //$new_goods_list[$v['supplier_id']][] = $v;
                    //$supplier_id[] = $v['supplier_id'];
                    $new_g[] = $v['rec_id'];
                    $goods_list[$k]['format_goods_price']=price_format($v['goods_price'], false);
                    $goods_list[$k]['format_market_price']=price_format($v['market_price'], false);
                }
                //print_r(array_values(array_map(function($i){$i = array_values($i);return $i;}, $new_goods_list)));
                //$new_goods_list = array_values(array_map(function($i){$i = array_values($i);return $i;}, $new_goods_list));
                if($message != '')
                {
                    $return['message'] = $message;
                    return $return;
                }
            }
        }


        $consignee = get_consignee($user_id,$flow_order['address_id']);

        //print_r($consignee);die();

        if (empty($consignee))
        {
            $consignee['country']='1';
        }

        /* 检查收货人信息是否完整 */
        // if (!check_consignee_info($consignee, $flow_type, $user_id))
        // {
        //     $return['message'] = '收货地址信息不完整或者不支持本地区配送';
        //     $return['error_code'] = 40001;
        //     return $return;
        // }

        $_SESSION['flow_consignee'] = $consignee;


        if ($user_id > 0)
        {
            $sql="SELECT * FROM " . $this->_tb_user_address .
                " WHERE user_id = '". $user_id ."' order by address_id ";
            $consignee_list = $this->_db->getAll($sql);
            foreach ($consignee_list  as $cons_key => $cons_val)
            {
                $consignee_list[$cons_key]['country_name']  =  get_region_info($cons_val['country']);
                $consignee_list[$cons_key]['province_name'] =  get_region_info($cons_val['province']);
                $consignee_list[$cons_key]['city_name']     =  get_region_info($cons_val['city']);
                $consignee_list[$cons_key]['district_name'] =  get_region_info($cons_val['district']);
                if ($consignee['address_id'] == $cons_val['address_id'])
                {
                    $consignee_list[$cons_key]['def_addr'] = '1';
                    $have_def_addr = 1;
                }

                unset($consignee_list[$cons_key]['zipcode']);
                unset($consignee_list[$cons_key]['tel']);
                unset($consignee_list[$cons_key]['sign_building']);
                unset($consignee_list[$cons_key]['best_time']);
                unset($consignee_list[$cons_key]['address_name']);
            }
            if ( count($consignee_list) && !$have_def_addr){ $consignee_list[0]['def_addr'] = '1'; }
        }

        /* 对商品信息赋值 */
        $cart_goods = $this->cart->cart_goods($flow_type, $user_rank_info['user_id'], $sel_goods); // 取得商品列表，计算合计

        /*
         * 取得订单信息
         */
        $order = $this->flow_order_info($user_id, $flow_order,$device,$flow_type);

        $shipping_list=array();
        $shipping_ziti=array();
        //计算全部订单的运费
        if(count($cart_goods)>0){
            foreach($cart_goods as $key => $val){
                $shipping_list= $this->insert_get_shop_shipping(array('suppid'=>$val['supplier_id'],'consignee'=>$consignee,'flow_type'=>$flow_type),$user_rank_info['user_id'], $sel_goods);

                $order['shipping_pay'][$val['supplier_id']] = 0;

                foreach ($shipping_list as $key => $value) {

                    if($value['selected']=='checked'){
                        $order['shipping_pay'][$value['supplier_id']] = $value['shipping_id'];
                    }
                    if($value['shipping_code'] == 'pups'){
                        if(isset($shipping_list[$key]['shipping_ziti'])){
                            $shipping_ziti=$shipping_list[$key]['shipping_ziti'];
                            unset($shipping_list[$key]['shipping_ziti']);
                        }

                    }


                }
            }
        }

        //print_r($order);
        //$order['shipping_pay'] = isset($order['shipping_pay'])?$order['shipping_pay']:'';
        /*
         * 计算订单的费用
         */
        $total = $this->order_fee($order, $cart_goods, $consignee, $user_rank_info, $sel_goods,$flow_type);

        $cart_goods2 = $this->cart->cart_goods($flow_type, $user_rank_info['user_id'], $sel_goods2); // 取得商品列表，计算合计
        //计算全部订单的运费
        if(count($cart_goods2)>0){
            foreach($cart_goods2 as $key => $val){
                $shipping_list2 = $this->insert_get_shop_shipping(array('suppid'=>$val['supplier_id'],'consignee'=>$consignee,'flow_type'=>$flow_type),$user_rank_info['user_id'], $sel_goods2);
                $order['shipping_pay'][$val['supplier_id']] = 0;
                foreach ($shipping_list2 as $key => $value) {

                    if($value['selected']=='checked'){
                        $order['shipping_pay'][$value['supplier_id']] = $value['shipping_id'];
                    }
                }
            }
        }

        $total2 = $this->order_fee($order, $cart_goods2, $consignee, $user_rank_info, $sel_goods2,$flow_type);
        unset($total2['gift_amount']);
        unset($total2['pack_fee']);
        unset($total2['card_fee']);
        unset($total2['shipping_insure']);
        unset($total2['cod_fee']);
        unset($total2['pay_fee']);
        unset($total2['tax']);
        unset($total2['tax_formated']);
        unset($total2['pack_fee_formated']);
        unset($total2['card_fee_formated']);
        unset($total2['goods_price_supplier']);
        unset($total2['pay_fee_formated']);
        $total2['formated_discount'] = price_format($total2['discount']);

        /* 取得支付列表 */
        if ($order['shipping_id'] == 0)
        {
            $cod = true;
            $cod_fee = 0;
        }
        else
        {
            $shipping = shipping_info($order['shipping_id']);
            $cod = $shipping['support_cod'];

            if ($cod)
            {
                /* 如果是团购，且保证金大于0，不能使用货到付款 */
                if ($flow_type == CART_GROUP_BUY_GOODS)
                {
                    $group_buy_id = $_SESSION['extension_id'];
                    if ($group_buy_id <= 0)
                    {
                        $return['message'] = 'error group_buy_id';
                        return $return;
                    }
                    $group_buy = group_buy_info($group_buy_id);
                    if (empty($group_buy))
                    {
                        $return['message'] = 'group buy not exists: ' . $group_buy_id;
                        return $return;
                    }

                    if ($group_buy['deposit'] > 0)
                    {
                        $cod = false;
                        $cod_fee = 0;

                        /* 赋值保证金 */
//                        $smarty->assign('gb_deposit', $group_buy['deposit']);
                    }
                }

                if ($cod)
                {
                    $shipping_area_info = shipping_area_info($order['shipping_id']);
                    $cod_fee            = $shipping_area_info['pay_fee'];
                }
            }
            else
            {
                $cod_fee = 0;
            }
        }

        $device_where = '';
        if($device=='ios' || $device == 'android'){
            $device_where = " AND pay_code IN ('APP','QUICK_MSECURITY_PAY') ";
        }
        if($device=='wap' ){
            $device_where = " AND pay_code IN ('QUICK_WAP_WAY','JSAPI','MWEB') ";
        }
        if($device=='pc'){
            $device_where = " AND pay_code IN ('FAST_INSTANT_TRADE_PAY','NATIVE') ";
        }
        if($device=='xcx'){
            $device_where = " AND pay_code IN ('XCX','XCX') ";
        }

        // 给货到付款的手续费加<span id>，以便改变配送的时候动态显示
        $payment_list = available_payment_list(1, $cod_fee, false, $_SESSION['extension_code']=='virtual_good'?1:0,$device_where);
        $pay_balance_id = 0;//当前配置于的余额支付的递增id
        if(isset($payment_list))
        {
            foreach ($payment_list as $key => $payment)
            {
                unset($payment_list[$key]['pay_config']);
                unset($payment_list[$key]['pay_desc']);
                unset($payment_list[$key]['is_cod']);
                unset($payment_list[$key]['format_pay_fee']);
                unset($payment_list[$key]['pay_fee']);
                if ($payment['is_cod'] == '1')
                {
                    $payment_list[$key]['format_pay_fee'] = '<span id="ECS_CODFEE">' . $payment['format_pay_fee'] . '</span>';
                }
                /* 如果有余额支付 */
                if ($payment['pay_code'] == 'balance')
                {
                    $pay_balance_id = $payment['pay_id'];
                    /* 如果未登录，不显示 */
                    if ($user_id == 0)
                    {
                        unset($payment_list[$key]);
                    }
//                    else
//                    {
//                        if ($_SESSION['flow_order']['pay_id'] == $payment['pay_id'])
//                        {
//                            $smarty->assign('disable_surplus', 1);
//                        }
//                    }
                }
            }
        }else{
            $payment_list = array();
        }


        /* 取得配送列表 */
        /*$region            = array($consignee['country'], $consignee['province'], $consignee['city'], $consignee['district']);
        $shipping_list     = available_shipping_list($region,$supplier_id);
        $cart_weight_price = $this->cart_weight_price($flow_type,'-1', $user_rank_info, $sel_goods);
        $insure_disabled   = true;
        $cod_disabled      = true;

        $ext = '';
        if($sel_goods){
            $ext .= " AND rec_id in (".$sel_goods.")";
        }

        // 查看购物车中是否全为免运费商品，若是则把运费赋为零
        //$sql = 'SELECT count(*) FROM ' . $this->_tb_cart . " WHERE `session_id` = '" . SESS_ID. "' AND `extension_code` != 'package_buy' AND `is_shipping` = 0";
        $sql = 'SELECT count(*) FROM ' . $this->_tb_cart . " WHERE $sql_where AND `extension_code` != 'package_buy' AND `is_shipping` = 0 ".$ext;
        $shipping_count = $this->_db->getOne($sql);

        foreach ($shipping_list AS $key => $val)
        {
            $shipping_cfg = unserialize_config($val['configure']);
            $shipping_fee = ($shipping_count == 0 AND $cart_weight_price['free_shipping'] == 1) ? 0 : shipping_fee($val['shipping_code'], unserialize($val['configure']),
            $cart_weight_price['weight'], $cart_weight_price['amount'], $cart_weight_price['number']);

            $shipping_list[$key]['format_shipping_fee'] = price_format($shipping_fee, false);
            $shipping_list[$key]['shipping_fee']        = (string)$shipping_fee;

//            $shipping_list[$key]['free_money']          = price_format($shipping_cfg['free_money'], false);
//            $shipping_list[$key]['insure_formated']     = strpos($val['insure'], '%') === false ?
//                price_format($val['insure'], false) : $val['insure'];

            unset($shipping_list[$key]['configure']);
            unset($shipping_list[$key]['shipping_desc']);
            unset($shipping_list[$key]['insure']);
            unset($shipping_list[$key]['support_cod']);
            unset($shipping_list[$key]['support_pickup']);
            unset($shipping_list[$key]['format_shipping_fee']);

            // 当前的配送方式是否支持保价
            if ($val['shipping_id'] == $order['shipping_id'])
            {
                $insure_disabled = ($val['insure'] == 0);
                $cod_disabled    = ($val['support_cod'] == 0);
            }
            //print_r($val);

            // if(in_array($val['supplier_id'],$supplier_id)){
                // $new_shipping_list[$v['supplier_id']][] = $val;
            // }

        }*/
        //$new_shipping_list = array_values(array_map(function($i){$i = array_values($i);return $i;}, $new_shipping_list));
    //        if($consignee['city']){
    //            $sql = 'select r.region_name, r.region_id from ' . $this->_tb_pickup_point . ' p left join ' . $this->_tb_region .
    //                ' r on p.city_id=r.region_id where p.city_id=' . $consignee['city'];
    //            $district_list = $this->_db->getAll($sql);
    //        }

            $user_info = user_info($user_id);


            /* 如果使用余额，取得用户余额 */
        if ((!isset($GLOBALS['_CFG']['use_surplus']) || $GLOBALS['_CFG']['use_surplus'] == '1')
            && $user_id > 0
            && $user_info['user_money'] > 0)
        {
            // 能使用余额
            $order['surplus'] = $user_info['user_money'];
        }
        if($GLOBALS['_CFG']['use_surplus'] == '1'){
            $order['allow_use_surplus'] = 1;
//            $smarty->assign('allow_use_surplus', 1);
        }


        $order['integral_scale']=$GLOBALS['_CFG']['integral_scale']?floatval($GLOBALS['_CFG']['integral_scale']):0;//积分支付比例


        //此订单最多可使用的积分及可抵扣的积分金额
        $max_use_integral=0;
        $max_use_integral_price=0;
        /* 如果使用积分，取得用户可用积分及本订单最多可以使用的积分 */
        if ((!isset($GLOBALS['_CFG']['use_integral']) || $GLOBALS['_CFG']['use_integral'] == '1')
            && $user_id > 0
            && $user_info['pay_points'] > 0
            && ($flow_type != CART_GROUP_BUY_GOODS && $flow_type != CART_EXCHANGE_GOODS))
        {
            // 能使用积分
            $keyong = $this->flow_available_points($user_rank_info,$flow_type,$sel_goods2);// 可用积分
            foreach($keyong as $k=>$v){
                $cart_goods_new[$k]['jifen'] = $v;
                $max_use_integral+=$v;
            }
            $order['allow_use_integral'] = 1;
            $order['integral'] = $user_info['pay_points'];
        }

        $order['max_use_integral']=$max_use_integral;//此订单最多可使用的积分
        $order['max_use_integral_price']=value_of_integral($max_use_integral);//此订单最多可抵扣的积分金额


        $bonus_list = array();
        $userbonusid = array();
        if ((!isset($GLOBALS['_CFG']['use_bonus']) || $GLOBALS['_CFG']['use_bonus'] == '1')
            && ($flow_type != CART_GROUP_BUY_GOODS && $flow_type != CART_EXCHANGE_GOODS && $flow_type != CART_PRE_SALE_GOODS))
        {
            $res = array();
            // 取得用户可用红包

            $user_bonus = user_bonus($user_id, $total['goods_price_supplier'],$supplier_id);//把参数由总金额改为每个店铺的订单金额
            //$user_bonus = user_bonus($user_id, $total['goods_price']);
            if (!empty($user_bonus))
            {
                foreach ($user_bonus AS $key => $val)
                {
                    foreach($val as $k => $v)
                    {
                        $user_bonus[$key][$k]['bonus_money_formated'] = price_format($v['type_money'], false);

                        if($v['supplier_id'] == '0')
                        {
                            $user_bonus[$key][$k]['supplier_id'] = 0;
                            $user_bonus[$key][$k]['supplier_name'] = "自营商";
                        }
                        else
                        {
                            $supplierid = $v['supplier_id'];
                            $sql = "SELECT * FROM ".$GLOBALS['ecs']->table('supplier_shop_config')."WHERE supplier_id = '$supplierid' AND code = 'shop_name'";
                            $supp = $GLOBALS['db']->getRow($sql);
                            $user_bonus[$key][$k]['supplier_id'] = $supplierid;
                            $user_bonus[$key][$k]['supplier_name'] = $supp['value'];
                        }

                        $user_bonus[$key][$k]['use_startdate']   = local_date($GLOBALS['_CFG']['date_format'], $v['use_start_date']);
                        $user_bonus[$key][$k]['use_enddate']     = local_date($GLOBALS['_CFG']['date_format'], $v['use_end_date']);
                        unset($user_bonus[$key][$k]['type_id']);
                        $user_bonus[$key][$k]['can_use'] = 1;

                    }
                }

                foreach($user_bonus[$supplier_id] as $k=>$v){
                        $userbonusid[] = $v['bonus_id'];
                }
            }
            // 能使用红包
            $order['allow_use_bonus'] = 1;
        }
        $user_all_bonus = $this->get_user_bouns_list($user_id,$supplier_id);
        foreach($user_all_bonus as $k=>$v){
            $user_all_bonus[$k]['can_use'] = 0;
            $user_all_bonus[$k]['bonus_money_formated']   = price_format($v['type_money'], false);
            if(in_array($v['bonus_id'], $userbonusid)){
                unset($user_all_bonus[$k]);
            }
            unset($user_all_bonus[$k]['s_id']);
            unset($user_all_bonus[$k]['is_used']);
            //unset($user_all_bonus[$k]['status']);
        }

        $bonus_list  = array_merge(isset($user_bonus[$supplier_id]) ? $user_bonus[$supplier_id] : array(),isset($user_all_bonus) ? $user_all_bonus : array());

        /* 如果能开发票，取得发票内容列表 */
        /*if ((!isset($GLOBALS['_CFG']['can_invoice']) || $GLOBALS['_CFG']['can_invoice'] == '1')
            && isset($GLOBALS['_CFG']['invoice_content'])
            && trim($GLOBALS['_CFG']['invoice_content']) != '' && $flow_type != CART_EXCHANGE_GOODS)
        {
            $inv_content_list = explode("\n", str_replace("\r", '', $GLOBALS['_CFG']['invoice_content']));

            $inv_type_list = array();
            foreach ($GLOBALS['_CFG']['invoice_type']['type'] as $key => $type)
            {
                if (!empty($type)&&$GLOBALS['_CFG']['invoice_type']['enable'][$key]=='1')
                {
                    $inv_type_list[$type] = $GLOBALS['_LANG'][$type] . ' [' . floatval($GLOBALS['_CFG']['invoice_type']['rate'][$key]) . '%]';
                }
            }

        }*/

        $open_invoice=get_supplier_fapiao_list($supplier_id,$flow_type,$total['goods_price']);

        //判断是否开启余额支付
        $sql = 'SELECT `is_surplus_open`'.
            'FROM ' . $this->_tb_users . ''.
            'WHERE `user_id` = \''.$user_id.'\''.
            'LIMIT 1';
        $is_surplus_open = $this->_db->getOne($sql);

        /* 保存 session */
        $_SESSION['flow_order'] = $order;



        unset($order['pack_id']);
        unset($order['card_id']);
        unset($order['shipping_pay']);//去掉字段

        unset($total['gift_amount']);
        unset($total['pack_fee']);
        unset($total['card_fee']);
        unset($total['shipping_insure']);
        unset($total['cod_fee']);
        unset($total['pay_fee']);
        unset($total['tax']);
        unset($total['tax_formated']);
        unset($total['pack_fee_formated']);
        unset($total['card_fee_formated']);
        unset($total['goods_price_supplier']);
        unset($total['pay_fee_formated']);
        //unset($total['discount']);



        $return['supplier_id'] = $supplier_id;

        //地址修改
        $def_addr = new stdClass();
        foreach($consignee_list as $v){
            if(isset($v['def_addr']) && $v['def_addr'] == 1){
                $def_addr->defaul_addr = $v;
            }
        }
        if(!$def_addr->address_id){
            $def_addr->address_id = 0;
        }
        
        //print_r($cart_goods);
        //print_r($goods_list);

        //支付添加信息
        foreach($payment_list as $k=>$v){
            //print_r($v['pay_name']);
            switch($v['pay_name']){
                case 'weixin':
                    $payment_list[$k]['icon'] = 'data/payment/icon/weixin.png';
                    $payment_list[$k]['pay_desc'] = '微信';
                    break;
                case 'alipay':
                    $payment_list[$k]['icon'] = 'data/payment/icon/alipay.png';
                    $payment_list[$k]['pay_desc'] = '支付宝';
                    break;
                case 'unionpay':
                    $payment_list[$k]['icon'] = 'data/payment/icon/unionpay.png';
                    $payment_list[$k]['pay_desc'] = '银联';
                    break;
            }
        }
        $payment_list[] = array('pay_id'=>0,'pay_code'=>'balance','pay_name'=>'balance','pay_desc'=>'余额支付','icon'=>'data/payment/icon/balance.png');
        $i00=0;
        $selected00 = '';
        foreach ($payment_list as $key => $value) {
            if($i00==0){
                $selected00 = 'checked';
            }
            if(isset($order['pay_id']) && intval($order['pay_id']) == $value['pay_id']){
                $selected00 = 'checked';
            }
            $payment_list[$key]['selected'] = $selected00;
        }

        $return['supplier']['address_list'] = $consignee_list;
        $return['supplier']['def_addr'] = $def_addr;
        $return['supplier']['payment_list'] = $payment_list;
        $return['supplier']['shipping_list']= $shipping_list;
        $return['supplier']['shipping_ziti']= $shipping_ziti;

        $return['supplier']['goods_list']   =$this->cart->cart_goods($flow_type, $user_rank_info['user_id'], $sel_goods);

        $return['supplier']['bonus_num']   = isset($user_bonus[$supplier_id]) ? count($user_bonus[$supplier_id]) : 0;//判断是否有符合条件的可用红包
        $return['supplier']['bonus_list']   = $bonus_list;
        $return['supplier']['order_info']   = cls_base::getInstance()->intToString($order);
        $return['supplier']['order_total']  = cls_base::getInstance()->intToString($total2);
        $return['supplier']['supplier_total']  = cls_base::getInstance()->intToString($total);

        $return['supplier']['open_invoice']   = $open_invoice;//开发票

        $invoice_name="";
        if($open_invoice['can_invoice']){
            $invoice_type=key($open_invoice['invoice_type']);
            if($invoice_type=='normal_invoice'){
               $invoice_name=str_replace("发票","",current($open_invoice['invoice_type']))."（个人）";
            }
            else{
                $invoice_name=str_replace("发票","",current($open_invoice['invoice_type']));
            }
        }

        $return['supplier']['invoice_name']=$invoice_name;


        $return['code'] = 200;
        $return['message'] = 'SUCCESS';
//print_r( cart_goods($flow_type));
//print_r($return);
        return $return;

    }



    /**
     * @description 下单接口
     * @param int $user_rank_info  会员等级信息
     * @param int $flow_type  0  普通商品
     * @param string $sel_goods  选中商品
     * @param string $flow_order 其余参数 address_id, shipping_id, pay_id
     * @return void
     */
    public function addOrder($user_rank_info, $flow_type, $sel_goods = '', $flow_order, $device, $is_design = 0)
    {
        define('SESS_ID',session_id());

        /* 载入语言文件 */
        require_once(ROOT_PATH . 'languages/' .$GLOBALS['_CFG']['lang']. '/user.php');
        require_once(ROOT_PATH . 'languages/' .$GLOBALS['_CFG']['lang']. '/shopping_flow.php');

        $GLOBALS['_LANG'] = $_LANG;

        $return = array(
            'code' => 500,
            'data' => array(
            ),
            'message' => ''
        );

        //不是普通商品时执行
        if($flow_type){
            //2018.01.29  添加goods_id,product_id两个字段，为砍价活动更新日志用
            $cart_data = $GLOBALS['db']->getRow("SELECT extension_id,extension_code,goods_id,product_id,group_log_id,goods_price FROM ". $GLOBALS['ecs']->table("cart") . " WHERE rec_id in (".$sel_goods.") AND user_id='". $user_rank_info['user_id'] ."' ");
            $_SESSION['extension_id'] = $cart_data['extension_id'];
            $_SESSION['extension_code'] = $cart_data['extension_code'];
        }

        //判断拼团 重复判断，和cls_cart.php的addToCart方法一样
        if($flow_type == 102){
            $group_log_id = $cart_data['group_log_id'];
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

        //正常购物流程  清空其他购物流程情况
        $_SESSION['extension_id'] = isset($_SESSION['extension_id'])?$_SESSION['extension_id']:'';
        $_SESSION['extension_code'] = isset($_SESSION['extension_code'])?$_SESSION['extension_code']:'';

        $_POST['how_oos'] = isset($flow_order['how_oos']) ? intval($flow_order['how_oos']) : 0;
        $_POST['card_message'] = isset($flow_order['card_message']) ? compile_str($flow_order['card_message']) : '';

        /*$_POST['inv_type'] = !empty($flow_order['inv_type']) ? compile_str($flow_order['inv_type']) : '';
        $_POST['inv_payee_type'] = !empty($flow_order['inv_payee_type']) ? compile_str($flow_order['inv_payee_type']) : '';
        $_POST['vat_inv_taxpayer_id'] = !empty($flow_order['vat_inv_taxpayer_id']) ? $flow_order['vat_inv_taxpayer_id'] : '';
        $_POST['inv_payee'] = isset($flow_order['inv_payee']) ? compile_str($flow_order['inv_payee']) : '';
        $_POST['inv_content'] = isset($flow_order['inv_content']) ? compile_str($flow_order['inv_content']) : '';*/

        $order_pickup_point = isset($flow_order['pickup_point']) ? $flow_order['pickup_point'] : array();//自提点ID
        $order_integral = isset($flow_order['integral']) ? $flow_order['integral'] : 0;//积分
        $order_bonus_id = isset($flow_order['bonus']) ? $flow_order['bonus'] : array();
        $order_message = isset($flow_order['message']) ? $flow_order['message'] : array();
        $order_bonus_sn = isset($flow_order['bonus_sn']) ? $flow_order['bonus_sn'] : array();
        $order_surplus = isset($flow_order['surplus']) ? $flow_order['surplus'] : 0;

        //发票
        $order_inv_type = isset($flow_order['inv_type']) ? $flow_order['inv_type'] : array();
        $order_inv_payee_type = isset($flow_order['inv_payee_type']) ? $flow_order['inv_payee_type'] : array();
        $order_inv_payee = isset($flow_order['inv_payee']) ? $flow_order['inv_payee'] : array();
        $order_vat_inv_taxpayer_id = isset($flow_order['vat_inv_taxpayer_id']) ? $flow_order['vat_inv_taxpayer_id'] : array();
        $order_vat_inv_company_name = isset($flow_order['vat_inv_company_name']) ? $flow_order['vat_inv_company_name'] : array();
        $order_vat_inv_registration_address = isset($flow_order['vat_inv_registration_address']) ? $flow_order['vat_inv_registration_address'] : array();
        $order_vat_inv_registration_phone = isset($flow_order['vat_inv_registration_phone']) ? $flow_order['vat_inv_registration_phone'] : array();
        $order_vat_inv_deposit_bank = isset($flow_order['vat_inv_deposit_bank']) ? $flow_order['vat_inv_deposit_bank'] : array();
        $order_vat_inv_bank_account = isset($flow_order['vat_inv_bank_account']) ? $flow_order['vat_inv_bank_account'] : array();
        $order_inv_consignee_phone = isset($flow_order['inv_consignee_phone']) ? $flow_order['inv_consignee_phone'] : array();
        $order_inv_consignee_email = isset($flow_order['inv_consignee_email']) ? $flow_order['inv_consignee_email'] : array();
        $order_open_inv_type = isset($flow_order['open_inv_type']) ? $flow_order['open_inv_type'] : array();
        $order_inv_consignee_name = isset($flow_order['inv_consignee_name']) ? $flow_order['inv_consignee_name'] : array();
        $order_inv_consignee_province = isset($flow_order['inv_consignee_province']) ? $flow_order['inv_consignee_province'] : array();
        $order_inv_consignee_city = isset($flow_order['inv_consignee_city']) ? $flow_order['inv_consignee_city'] : array();
        $order_inv_consignee_district = isset($flow_order['inv_consignee_district']) ? $flow_order['inv_consignee_district'] : array();
        $order_inv_consignee_address = isset($flow_order['inv_consignee_address']) ? $flow_order['inv_consignee_address'] : array();

        $order_inv_content = isset($flow_order['inv_content']) ? $flow_order['inv_content'] : array();



        $user_id = $user_rank_info['user_id'];

        include_once('includes/lib_clips.php');
        include_once('includes/lib_payment.php');

        $id_ext ="";
        if ($sel_goods)
        {
            $id_ext = " AND rec_id in (". $sel_goods .") ";
        }

        $sql_where = "user_id='". $user_id ."' ";
        $sql_where .= $id_ext;


        /* 检查购物车中是否有商品 */
        $sql = "SELECT COUNT(*) FROM " . $this->_tb_cart .
            " WHERE $sql_where " .
            "AND parent_id = 0 AND is_gift = 0 AND rec_type = '$flow_type'";

        if ($this->_db->getOne($sql) == 0)
        {
            $return['message'] = $GLOBALS['_LANG']['no_goods_in_cart'];
            return $return;
        }
        $sql = "SELECT * FROM ".$this->_tb_cart."WHERE $sql_where AND parent_id = 0 AND is_gift > 0 AND rec_type = '$flow_type'";
        $res = $this->_db->getAll($sql);
        foreach($res as $key=>$value)
        {
            $goodsid = $value['goods_id'];
            $sql = "SELECT goods_number FROM ".$this->_tb_goods."WHERE goods_id = $goodsid";
            $rec = $this->_db->getOne($sql);
            if($value['goods_number'] > $rec)
            {
                $return['message'] = "赠品  ".$value['goods_name']."  已经赠送完!";
                return $return;
            }
        }

        /* 检查商品库存 */
        /* 如果使用库存，且下订单时减库存，则减少库存 */
        if ($GLOBALS['_CFG']['use_storage'] == '1' && $GLOBALS['_CFG']['stock_dec_time'] == SDT_PLACE)
        {
            $_cart_goods_stock = array();
            $cart_goods_stock = $this->cart->get_cart_goods($user_rank_info, $id_ext);
            //print_r($cart_goods_stock);
            foreach ($cart_goods_stock['supplier_list'] as $values)
            {
                foreach ($values['goods_list'] as $value)
                {
                    $_cart_goods_stock[$value['rec_id']] = $value['goods_number'];
                }
            }
            $return['message'] = $this->cart->flow_cart_stock($user_rank_info, $_cart_goods_stock);
            if($return['message']){
                return $return;
            }
            unset($cart_goods_stock, $_cart_goods_stock);
        }

        $consignee = get_consignee($user_id,$flow_order['address_id']);

        /* 检查收货人信息是否完整 */
        if (!check_consignee_info($consignee, $flow_type, $user_rank_info['user_id']))
        {
            $return['message'] = "地址信息不完整";
            return $return;
        }

        /* 订单中的商品 */
        $cart_goods = $this->cart->cart_goods($flow_type, $user_rank_info['user_id'], $sel_goods);

        $max_user_integral=$this->flow_order_available_points($user_rank_info,$flow_type, $sel_goods);//该订单最高可使用的积分

        if($order_integral>$max_user_integral){
            $return['message'] = "该订单最高可使用的积分为".$max_user_integral;
            return $return;
        }

        $cart_goods_new = array();
        if(count($cart_goods)>0){
            foreach($cart_goods as $key => $val){
                $cart_goods_new[$val['supplier_id']]['goodlist'][$val['rec_id']] = $val;
                $cart_goods_new[$val['supplier_id']]['referer'] = $val['seller'];
            }
        }

        if (empty($cart_goods))
        {
            $return['message'] = $GLOBALS['_LANG']['no_goods_in_cart'];
            return $return;
        }

        /* 检查商品总额是否达到最低限购金额 */
        if ($flow_type == CART_GENERAL_GOODS && $this->cart->cart_amount(true, CART_GENERAL_GOODS) < $GLOBALS['_CFG']['min_goods_amount'])
        {
            $return['message'] = sprintf($GLOBALS['_LANG']['goods_amount_not_enough'], price_format($GLOBALS['_CFG']['min_goods_amount'], false));
            return $return;
        }

        //获取余额支付的id
        /*$sql = 'SELECT pay_id ' .
            ' FROM ' . $this->_tb_payment .
            ' WHERE enabled = 1 and pay_code="balance"';
        $pay_balance_id = $this->_db->getOne($sql);*/
        $pay_balance_id = 0;



        //此订单拆分订单后的订单信息
        $order_info = array();
        //print_r($cart_goods_new);
        //组装拆分的子订单数组信息start

        $user_info = user_info($user_id);
        $user_money_credit=$user_info['user_money'] + $user_info['credit_line'];//用户的最大消费+可用余额
        $user_pay_points=$user_info['pay_points'];//用户的可用积分

        foreach ($cart_goods_new as $ckey=>$cval){

            $cart_goods = $cval['goodlist'];

            $order = array(
                //'shipping_id'     => intval($_POST['shipping']),
                'pay_id'          => intval($flow_order['pay_id']),
                'pack_id'         => isset($flow_order['pack']) ? intval($flow_order['pack']) : 0,
                'card_id'         => isset($flow_order['card']) ? intval($flow_order['card']) : 0,
                'card_message'    => trim($flow_order['card_message']),
                'surplus'         => $order_surplus,//isset($order_surplus[$ckey]) ? floatval($order_surplus[$ckey]) : 0.00,
                'integral'        => $order_integral,
                'bonus_id'        => isset($order_bonus_id[$ckey]) ? intval($order_bonus_id[$ckey]) : 0,
                'postscript'        => isset($order_message[$ckey]) ? addslashes($order_message[$ckey]) : '',
                'need_inv'        => isset($order_inv_type[$ckey]) ? 1 : 0,
                'inv_type'        => isset($order_inv_type[$ckey]) ? $order_inv_type[$ckey] : '',
                'inv_payee'       => isset($order_inv_payee[$ckey]) ? $order_inv_payee[$ckey] : '',
                'inv_content'     => isset($order_inv_content[$ckey]) ? $order_inv_content[$ckey] : '',
                'inv_payee_type'     => isset($order_inv_payee_type[$ckey]) ? $order_inv_payee_type[$ckey] : '',
                'vat_inv_taxpayer_id'     => isset($order_vat_inv_taxpayer_id[$ckey]) ? $order_vat_inv_taxpayer_id[$ckey] : '',
                'vat_inv_company_name'     => isset($order_vat_inv_company_name[$ckey]) ? $order_vat_inv_company_name[$ckey] : '',
                'vat_inv_registration_address'     => isset($order_vat_inv_registration_address[$ckey]) ? $order_vat_inv_registration_address[$ckey] : '',
                'vat_inv_registration_phone'     => isset($order_vat_inv_registration_phone[$ckey]) ? $order_vat_inv_registration_phone[$ckey] : '',
                'vat_inv_deposit_bank'     => isset($order_vat_inv_deposit_bank[$ckey]) ? $order_vat_inv_deposit_bank[$ckey] : '',
                'vat_inv_bank_account'     => isset($order_vat_inv_bank_account[$ckey]) ? $order_vat_inv_bank_account[$ckey] : '',
                'inv_consignee_phone'     => isset($order_inv_consignee_phone[$ckey]) ? $order_inv_consignee_phone[$ckey] : '',
                'inv_consignee_email'     => isset($order_inv_consignee_email[$ckey]) ? $order_inv_consignee_email[$ckey] : '',
                'open_inv_type'     => isset($order_open_inv_type[$ckey]) ? $order_open_inv_type[$ckey] : '',
                'inv_consignee_name'     => isset($order_inv_consignee_name[$ckey]) ? $order_inv_consignee_name[$ckey] : '',
                'inv_consignee_province'     => isset($order_inv_consignee_province[$ckey]) ? $order_inv_consignee_province[$ckey] : '',
                'inv_consignee_city'     => isset($order_inv_consignee_city[$ckey]) ? $order_inv_consignee_city[$ckey] : '',
                'inv_consignee_district'     => isset($order_inv_consignee_district[$ckey]) ? $order_inv_consignee_district[$ckey] : '',
                'inv_consignee_address'     => isset($order_inv_consignee_address[$ckey]) ? $order_inv_consignee_address[$ckey] : '',
                'how_oos'         => isset($GLOBALS['_LANG']['oos'][$_POST['how_oos']]) ? addslashes($GLOBALS['_LANG']['oos'][$_POST['how_oos']]) : '',
                'need_insure'     => isset($_POST['need_insure']) ? intval($_POST['need_insure']) : 0,
                'user_id'         => $user_id,
                'add_time'        => gmtime(),
                'order_status'    => OS_UNCONFIRMED,
                'shipping_status' => SS_UNSHIPPED,
                'pay_status'      => PS_UNPAYED,
                'agency_id'       => get_agency_by_regions(array($consignee['country'], $consignee['province'], $consignee['city'], $consignee['district'])),
                'supplier_id'     => $ckey

            );
            $order['defaultbank'] = isset($_POST['hunuo_bank']) ? trim($_POST['hunuo_bank']) : "";

            /*增值税发票*/
            /*发票信息*/
            /*$inv_arr = array();
            if(isset($flow_order['inv_type']) && $flow_order['inv_type'] == 'normal_invoice')
            {
                $inv_arr = array('inv_type','inv_payee_type','inv_payee','inv_content');
                if(isset($flow_order['inv_payee_type']) && $flow_order['inv_payee_type'] == 'individual')
                {
                    $order['inv_payee'] = '个人';
                }
            }
            elseif(isset($flow_order['inv_type']) && $flow_order['inv_type'] == 'vat_invoice')
            {
                $inv_arr = array('inv_type','inv_content','vat_inv_company_name',
                    'vat_inv_taxpayer_id','vat_inv_registration_address','vat_inv_registration_phone',
                    'vat_inv_deposit_bank','vat_inv_bank_account','inv_consignee_name',
                    'inv_consignee_phone','inv_consignee_province','inv_consignee_city',
                    'inv_consignee_district','inv_consignee_address');
            }

            foreach($inv_arr as $key)
            {
                $value = !empty($flow_order[$key])?trim($flow_order[$key]):'';;
                if(!empty($value))
                {
                    $order[$key] = $value;
                }
            }*/

            /*增值税发票*/

            /* 扩展信息 */
            if (isset($flow_type) && intval($flow_type) != CART_GENERAL_GOODS)
            {
                $order['extension_code'] = $_SESSION['extension_code'];
                $order['extension_id'] = $_SESSION['extension_id'];
            }
            else
            {
                $order['extension_code'] = '';
                $order['extension_id'] = 0;
            }

            //print_r($ckey);

            /*检查配送方式是否选择*/
            // 如果是虚拟商品不需要选择配送方式
            if( $_SESSION['extension_code'] != 'virtual_good'){
                if(!isset($flow_order['pay_ship'][$ckey])){
                    $return['message'] = "请选择配送方式";
                    return $return;
                }else{
                    if($flow_order['pay_ship'][$ckey]<=0){
                        $return['message'] = "请选择配送方式";
                        return $return;
                    }
                    else{
                        $shipid = $this->_db->getOne("select shipping_id from ".$this->_tb_shipping." where shipping_id=".$flow_order['pay_ship'][$ckey]." and supplier_id=".$ckey);

                        if($shipid){
                            $order['shipping_id'] = intval($shipid);
                        }else{
                            $return['message'] = "您选择的配送方式不存在，请重新选择！";
                            return $return;
                        }
                    }
                }
            }else{
                $order['shipping_id'] = 0;
            }

            /* 检查积分余额是否合法 */
            $user_id = $user_id;
            if ($user_id > 0)
            {
                //$user_info = user_info($user_id);

                //$order['surplus'] = min($order['surplus'], $user_info['user_money'] + $user_info['credit_line']);
                $order['surplus']=min($order['surplus'], $user_money_credit);
                if ($order['surplus'] < 0)
                {
                    $order['surplus'] = 0;
                }

                // 查询用户有多少积分
                $flow_points = $this->flow_available_points($user_rank_info,$flow_type,$sel_goods);  // 该订单允许使用的积分
                //$user_points = $user_info['pay_points']; // 用户的积分总数
                $user_points = $user_pay_points; // 用户的积分总数

                $order['integral'] = min($order['integral'], $user_points, isset($flow_points[$ckey])?$flow_points[$ckey]:0);
                if ($order['integral'] < 0)
                {
                    $order['integral'] = 0;
                }
            }
            else
            {
                $order['surplus']  = 0;
                $order['integral'] = 0;
            }

            $bonus = array();
            /* 检查红包是否存在 */
            if ($order['bonus_id'] > 0)
            {
                $bonus = bonus_info($order['bonus_id']);
                //|| $bonus['min_goods_amount'] > cart_amount_new(array_keys($cart_goods),true, $flow_type)

                if (empty($bonus) || $bonus['user_id'] != $user_id || $bonus['order_id'] > 0 )
                {
                    $order['bonus_id'] = 0;
                }else{

                }
            }
            elseif (isset($flow_order['bonus_sn'][$ckey]))
            {
                $bonus_sn = intval($flow_order['bonus_sn'][$ckey]);
                $bonus = bonus_info(0, $bonus_sn);
                $now = gmtime();
                //|| $bonus['min_goods_amount'] > cart_amount_new(array_keys($cart_goods),true, $flow_type)
                if (empty($bonus) || $bonus['user_id'] > 0 || $bonus['order_id'] > 0  || $now > $bonus['use_end_date'])
                {
                }
                elseif(!empty($bonus['user_id']) && $bonus['user_id'] != $user_rank_info['user_id']){}
                else
                {
                    if ($user_id > 0)
                    {
                        $sql = "UPDATE " . $this->_tb_user_bonus . " SET user_id = '$user_id' WHERE bonus_id = '$bonus[bonus_id]' LIMIT 1";
                        $this->_db->query($sql);
                    }
                    $order['bonus_id'] = '';//$bonus['bonus_id'];
                    //$order['bonus_id'] = $bonus['bonus_id'];
                    $order['bonus_sn'] = $bonus_sn;
                }
            }

            /* 判断是不是实体商品 */
            foreach ($cart_goods AS $val)
            {

                /* 统计实体商品的个数 */
                if ($val['is_real'])
                {
                    $is_real_good=1;
                }
            }
            if(isset($is_real_good))
            {
                $sql="SELECT shipping_id FROM " . $this->_tb_shipping . " WHERE shipping_id=".$order['shipping_id'] ." AND enabled =1";
                if(!$this->_db->getOne($sql))
                {
                    $return['message'] = $GLOBALS['_LANG']['flow_no_shipping'];
                    return $return;
                }
            }

            /* 收货人信息 */
            foreach ($consignee as $key => $value)
            {
                $order[$key] = addslashes($value);
            }

            $order['best_time'] = isset($_POST['best_time']) ? trim($_POST['best_time']) : '';

            //配送方式的钱算到里面
            $order['shipping_pay'][$ckey] = $flow_order['pay_ship'][$ckey];

            if($order['surplus'] > 0){
                $user_money_credit=$user_money_credit-$order['surplus'];//用户的总余额减去每一个平台方或入驻商家使用的余额,结果做为下一个商家使用的余额
            }
            if($order['integral'] > 0){
                $user_pay_points=$user_pay_points-$order['integral'];//用户的总积分减去每一个平台方或入驻商家使用的积分,结果做为下一个商家使用的积分
            }

            /* 订单中的总额 */
            $total = $this->order_fee($order, $cart_goods, $consignee, $user_rank_info, $sel_goods, $flow_type, $is_design);

            unset($order['shipping_pay'][$ckey]);// 去掉这条信息以免影响下订单操作

            $order['bonus']        = $total['bonus'];
            $order['goods_amount'] = $total['goods_price'];
            $order['discount'] = isset($total['discount'])?$total['discount']:0;
            $order['surplus']      = $total['surplus'];
            $order['integral']     = $total['integral'];
            if($order['surplus'] > 0){
                //前台的总余额减去每一个平台方或入驻商家使用的余额,结果做为下一个商家使用的余额
                $order_surplus = $order_surplus - $order['surplus'];
            }
            if($order['integral'] > 0){
                //前台的总积分减去每一个平台方或入驻商家使用的积分,结果做为下一个商家使用的积分
                $order_integral = $order_integral - $order['integral'];
            }
            if($total['amount'] <= 0){
                //余额全部支付，让支付方式修改为余额支付
                $order['pay_id'] = $pay_balance_id;//余额支付方式的id
            }
            $order['tax']          = $total['tax'];

            // 购物车中的商品能享受红包支付的总额
            // $discount_amout = compute_discount_amount($ckey);
            $discount_amout = $this->compute_discount_amount($ckey,$user_rank_info,$sel_goods);

            // 红包和积分最多能支付的金额为商品总额
            $temp_amout = $order['goods_amount'] - $discount_amout;

            if ($temp_amout <= 0)
            {
                $order['bonus_id'] = 0;
            }

            /* 配送方式 */
            if (isset($order['shipping_id']) && $order['shipping_id'] > 0)
            {
                $shipping = shipping_info($order['shipping_id']);
                $order['shipping_name'] = addslashes($shipping['shipping_name']);
                //如果是门店自提，订单需要做特殊标识
                if($shipping['shipping_code'] == 'pups'){
                    $order['is_pickup'] = $order['shipping_id'];
                }
            }else{
                $order['shipping_name'] = '';
            }

            $order['shipping_fee'] = $total['shipping_fee'];
            $order['insure_fee']   = $total['shipping_insure'];

            /* 支付方式 */
            if ($order['pay_id'] > 0)
            {

                $payment = payment_info($order['pay_id']);
                $order['pay_name'] = addslashes($payment['pay_name']);
            }else{
                //$return['message'] = '支付方式必须选择一项';
                //return $return;
                $payment['pay_code'] = 'balance';
                $order['pay_name'] = '余额支付';
            }
            $order['pay_fee'] = $total['pay_fee'];
            $order['cod_fee'] = $total['cod_fee'];

            /* 商品包装 */
            if ($order['pack_id'] > 0)
            {
                $pack               = pack_info($order['pack_id']);
                $order['pack_name'] = addslashes($pack['pack_name']);
            }
            $order['pack_fee'] = $total['pack_fee'];

            /* 祝福贺卡 */
            if ($order['card_id'] > 0)
            {
                $card               = card_info($order['card_id']);
                $order['card_name'] = addslashes($card['card_name']);
            }
            $order['card_fee']      = $total['card_fee'];

            $order['order_amount']  = number_format($total['amount'], 2, '.', '');

            /*增值税发票*/
            /*发票金额*/
            $order['inv_money'] =  $total['goods_price'] ;
            /*增值税发票*/

            /* 如果全部使用余额支付，检查余额是否足够 */
            if ($payment['pay_code'] == 'balance' && $order['order_amount'] > 0)
                //if ($order['order_amount'] > 0)
            {
                if($order['surplus'] >0) //余额支付里如果输入了一个金额
                {
                    $order['order_amount'] = $order['order_amount'] + $order['surplus'];
                    $order['surplus'] = 0;
                }
                if ($order['order_amount'] > ($user_info['user_money'] + $user_info['credit_line']))
                {
                    $return['message'] = $GLOBALS['_LANG']['balance_not_enough'];
                    return $return;
                }
                else
                {
                    $order['surplus'] = $order['order_amount'];
                    $order['order_amount'] = 0;
                }
            }

            /* 如果订单金额为0（使用余额或积分或红包支付），修改订单状态为已确认、已付款 */
            if ($order['order_amount'] <= 0)
            {
                $order['order_status'] = OS_CONFIRMED;
                $order['confirm_time'] = gmtime();
                $order['pay_status']   = PS_PAYED;
                $order['pay_time']     = gmtime();
                $order['order_amount'] = 0;
                //$order['order_amount'] = $order['surplus'];//把支付的金额存进order_amount这个中
            }

            $order['integral_money']   = $total['integral_money'];

            if ($order['extension_code'] == 'exchange_goods')
            {
                $order['integral_money']   = 0;
                $order['integral']         = $total['exchange_integral'];
            }

            $order['from_ad']          = !empty($_SESSION['from_ad']) ? $_SESSION['from_ad'] : '0';
            //$order['referer']          = !empty($_SESSION['referer']) ? addslashes($_SESSION['referer']) : '';
            $order['referer']          = $cval['referer'];

            $affiliate = unserialize($GLOBALS['_CFG']['affiliate']);
            if(isset($affiliate['on']) && $affiliate['on'] == 1 && $affiliate['config']['separate_by'] == 1)
            {
                //推荐订单分成
                #todo something wrong
                $parent_id = get_affiliate();
                if($user_id == $parent_id)
                {
                    $parent_id = 0;
                }
            }
            elseif(isset($affiliate['on']) && $affiliate['on'] == 1 && $affiliate['config']['separate_by'] == 0)
            {
                //推荐注册分成
                $parent_id = 0;
            }
            else
            {
                //分成功能关闭
                $parent_id = 0;
            }
            $order['parent_id'] = $parent_id;


            //判断所选择的配送是否是门店自提
            $shipping_code=$GLOBALS['db']->getOne("SELECT shipping_code from ".$GLOBALS['ecs']->table('shipping')." where shipping_id='$order[shipping_id]'");
            if($shipping_code=='pups'){
                /*  自提功能
                    获取订单确认页选择的自提点
                */
                $pickup_point = isset($order_pickup_point[$ckey]) ? intval($order_pickup_point[$ckey]) : 0;

                if($pickup_point<=0){
                    $return['message'] = "请选择自提点";
                    return $return;
                }


                //判断该自提点是否存在
                $pickup_id=$GLOBALS['db']->getOne("SELECT id from ".$GLOBALS['ecs']->table('pickup_point')." where id='$pickup_point' AND supplier_id='$ckey'");
                if(empty($pickup_id)){
                    $return['message'] = "您选择自提点不是属于该商家的，请重新选择";
                    return $return;
                }

                $order['is_pickup'] = 1;
                $order['pickup_point'] = $pickup_point;
                $pickup_cn=mb_substr(str_pad(mt_rand(1, 99999), 5, '6', STR_PAD_LEFT).$ckey.date('His').str_pad(mt_rand(1, 99999), 4, '3', STR_PAD_LEFT),0,15);

                $order['pickup_cn'] = $pickup_cn;
            }
            else{
                $order['is_pickup'] = 0;
                $order['pickup_point'] = 0;

                $order['pickup_cn']='';//提货码
            }

            if(count($order)>0){
                $order_info[$ckey] = $order;
            }

            unset($order);
        }
        //组装拆分的子订单数组信息end

        //$order['liuyan'] = $liuyan;

        //判断是否拆分为多个订单,多个订单就生成父订单id号
        $del_patent_id = 0;
        if(count($order_info)>1){
            $error_no = 0;
            do
            {
                $save['order_sn'] = get_order_sn(); //获取新订单号
                $this->_db->autoExecute($this->_tb_order_info, $save, 'INSERT');
                $error_no = $this->_db->errno();

                if ($error_no > 0 && $error_no != 1062)
                {
                    die($this->_db->errorMsg());
                }
            }
            while ($error_no == 1062); //如果是订单号重复则重新提交数据
            $del_patent_id = $parent_order_id = $this->_db->insert_id();
        }else{
            $parent_order_id = 0;
        }

        $all_order_amount = 0;//记录订单所需支付的总金额
        //用来展示用的数组数据
        $split_order = array();
        $split_order['sub_order_count'] = count($order_info);
        //生成订单
        //$payment_www_com['hunuo_alipay_bank'] = $_POST['hunuo_bank'] ? trim($_POST['hunuo_bank']) : "hunuo_com";

        foreach($order_info as $ok=>$order){

            $cart_goods = $cart_goods_new[$ok]['goodlist'];

            if($cart_goods){
                $id_ext_new = " AND rec_id in (". implode(',',array_keys($cart_goods)) .") ";
            }

            //获取佣金id
            $order['rebate_id'] = 0;//get_order_rebate($ok);

            //下单来源
            $order['froms'] = $device;

            $order['parent_order_id'] = $parent_order_id;

            //$order['liuyan'] = $liuyan;

            /* 插入订单表 */
            $error_no = 0;
            do
            {
                $order['order_sn'] = get_order_sn(); //获取新订单号

                $this->_db->autoExecute($this->_tb_order_info, $order, 'INSERT');

                $error_no = $this->_db->errno();

                if ($error_no > 0 && $error_no != 1062)
                {
                    die($this->_db->errorMsg());
                }
            }
            while ($error_no == 1062); //如果是订单号重复则重新提交数据

            $new_order_id = $this->_db->insert_id();
            $order['order_id'] = $new_order_id;

            $parent_order_id = ($parent_order_id>0) ? $parent_order_id : $new_order_id;

            /* 插入订单商品 下面这个SQL有修改  注意末尾那个字段 */
            $sql = "INSERT INTO " . $this->_tb_order_goods . "( " .
                "order_id, goods_id, goods_name, goods_sn, product_id, goods_number, market_price, cost_price, ".
                "goods_price, goods_attr, is_real, extension_code, parent_id, is_gift, goods_attr_id, package_attr_id) ".
                " SELECT '$new_order_id', goods_id, goods_name, goods_sn, product_id, goods_number, market_price, cost_price, ".
                "goods_price, goods_attr, is_real, extension_code, parent_id, is_gift, goods_attr_id, package_attr_id ".
                " FROM " .$this->_tb_cart .
                " WHERE $sql_where AND rec_type = '$flow_type' $id_ext_new ";

            $this->_db->query($sql);
            /* 修改拍卖活动状态 */
            if ($order['extension_code']=='auction')
            {
                $sql = "UPDATE ". $this->_tb_goods_activity ." SET is_finished='2' WHERE act_id=".$order['extension_id'];
                $this->_db->query($sql);
            }

            //修改砍价活动状态
            if ($order['extension_code']=='bargain')
            {
                //更改个人参与砍价活动结束状态
                $sql = "UPDATE ".$GLOBALS['ecs']->table('bargain_log')." SET status='1',order_id = '$new_order_id' WHERE help_user_id = '".$order['user_id']."' AND goods_id = '".$cart_data['goods_id']."' and bargain_id = '".$cart_data['extension_id']."' and product_id = '".$cart_data['product_id']."' and status = 0";
                $this->_db->query($sql);

                //更新砍价统计数量
                $bargain_guangzhu = $this->_db->getOne("SELECT count(*) FROM ".$GLOBALS['ecs']->table('bargain_log')." as l WHERE l.user_id = l.help_user_id and l.bargain_id = '" . $cart_data['extension_id'] . "' ");//关注人数
                $bargain_canyu = $this->_db->getOne("SELECT count(*) FROM ".$GLOBALS['ecs']->table('bargain_log')." as l WHERE l.user_id = l.help_user_id and l.bargain_id = '" . $cart_data['extension_id'] . "' and status = 1");//参与人数
                $bargain_bangkan = $this->_db->getOne("SELECT count(*) FROM ".$GLOBALS['ecs']->table('bargain_log')." as l WHERE l.bargain_id = '" . $cart_data['extension_id'] . "' ");//砍价人数
                $sql = "UPDATE ".$GLOBALS['ecs']->table('bargain_activity')." SET guanzhu_num='$bargain_guangzhu',join_num = '$bargain_canyu',bargain_num = '$bargain_bangkan' WHERE id = '".$cart_data['extension_id']."' ";
                $this->_db->query($sql);
            }

            //拼团
            if ($order['extension_code']=='group')
            {
                $group_info = $this->_db->getRow("SELECT * FROM ".$GLOBALS['ecs']->table('group_activity')." where id = '" . $cart_data['extension_id'] . "' ");

                //插入拼团记录
                $group_save = array();
                $group_save['user_id'] = $order['user_id'];
                $group_save['group_id'] = $cart_data['extension_id'];
                $group_save['goods_id'] = $cart_data['goods_id'];
                $group_save['product_id'] = $cart_data['product_id'];
                $group_save['group_price'] = $cart_data['goods_price'];
                $group_save['parent_id'] = $cart_data['group_log_id'];
                $group_save['add_time'] = gmtime();
                $group_save['end_time'] = gmtime() + 86400 * $group_info['group_day'];
                $group_save['order_id'] = $new_order_id;
                $GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('group_log'), $group_save, 'INSERT');

                //如果是拼单
                if($cart_data['group_log_id'] > 0){
                    //判断拼单人数要求
                    $log_num = $GLOBALS['db']->getOne("SELECT count(*) FROM " . $GLOBALS['ecs']->table('group_log') . " WHERE parent_id = '$cart_data[group_log_id]' ");
                    $log_num = $log_num +1;//加上拼主记录条数
                    //判断拼团是否够人，如果够人就更新拼团状态为完成
                    if($log_num >= $group_info['group_num']){
                        //更新完成
                        $time = gmtime();
                        $sql = "UPDATE ".$GLOBALS['ecs']->table('group_log')." SET is_finish='1',finish_time = '$time' WHERE parent_id = '".$cart_data['group_log_id']."' or id = '".$cart_data['group_log_id']."' ";
                        $this->_db->query($sql);
                    }
                }

                //参与人数统计
                $group_num = $this->_db->getOne("SELECT count(*) FROM ".$GLOBALS['ecs']->table('group_log')." WHERE group_id = '" . $cart_data['extension_id'] . "' ");//砍价人数
                $sql = "UPDATE ".$GLOBALS['ecs']->table('group_activity')." SET join_num = '$group_num' WHERE id = '".$cart_data['extension_id']."' ";
                $this->_db->query($sql);

            }

            /* 处理余额、积分、红包 */
            if ($order['user_id'] > 0 && $order['surplus'] > 0)
            {
                log_account_change($order['user_id'], $order['surplus'] * (-1), 0, 0, 0, sprintf($GLOBALS['_LANG']['pay_order'], $order['order_sn']));
                //是否开启余额变动给客户发短信-用户消费
                if($GLOBALS['_CFG']['sms_user_money_change'] == 1)
                {
                    $sql = "SELECT user_money,mobile_phone FROM " . $this->_tb_users . " WHERE user_id = '" . $order['user_id'] . "'";
                    $users = $this->_db->getRow($sql);
                    $content = sprintf($GLOBALS['_CFG']['sms_use_balance_reduce_tpl'],date("Y-m-d H:i:s",time()),$order['surplus'],$users['user_money'],$GLOBALS['_CFG']['sms_sign']);
                    if($users['mobile_phone'])
                    {
                        require_once (ROOT_PATH . 'sms/sms.php');
                        try{
                            sendSMS($users['mobile_phone'],$content);//发送短信
                        }catch(Exception $e) {

                        }
                    }
                }
            }

            if ($order['user_id'] > 0 && $order['integral'] > 0)
            {
                log_account_change($order['user_id'], 0, 0, 0, $order['integral'] * (-1), sprintf($GLOBALS['_LANG']['pay_order'], $order['order_sn']));
            }

            if ($order['bonus_id'] > 0 && $temp_amout > 0 )
            {
                use_bonus($order['bonus_id'], $new_order_id);
            }


            if($order['bonus_id'] == '' && isset($order['bonus_sn']) && (empty($bonus['user_id']) || $bonus['user_id'] == $user_id))
            {
                $order['bonus_id'] = $bonus['bonus_id'];
                use_bonus($order['bonus_id'], $new_order_id);
            }

            $split_order['suborder_list'][$ok]['order_sn'] = $order['order_sn'];
            $split_order['suborder_list'][$ok]['pay_name'] = $order['pay_name'];
            $split_order['suborder_list'][$ok]['shipping_name'] = $order['shipping_name'];
            //$split_order['suborder_list'][$ok]['order_amount_formated'] = price_format($order['order_amount']);
            //if($order['order_amount'] <=0 && $payment['pay_code'] == 'balance'){//余额全额支付
            if($order['order_amount'] <= 0){//余额全额支付
                $split_order['suborder_list'][$ok]['order_amount_formated'] = price_format($order['surplus'],false);
            }else{
                $split_order['suborder_list'][$ok]['order_amount_formated'] = price_format($order['order_amount'],false);
            }


            /* 如果使用库存，且下订单时减库存，则减少库存 */
            if ($GLOBALS['_CFG']['use_storage'] == '1' && $GLOBALS['_CFG']['stock_dec_time'] == SDT_PLACE)
            {
                change_order_goods_storage($order['order_id'], true, SDT_PLACE);
            }
            //$this->_db -> query("unlock tables");
            /* 给商家发邮件 */
            /* 增加是否给客服发送邮件选项 */
            /*if ($GLOBALS['_CFG']['send_service_email'] && $GLOBALS['_CFG']['service_email'] != '')
            {
                $tpl = get_mail_template('remind_of_new_order');
                $smarty->assign('order', $order);
                $smarty->assign('goods_list', $cart_goods);
                $smarty->assign('shop_name', $GLOBALS['_CFG']['shop_name']);
                $smarty->assign('send_date', date($GLOBALS['_CFG']['time_format']));
                $content = $smarty->fetch('str:' . $tpl['template_content']);
                send_mail($GLOBALS['_CFG']['shop_name'], $GLOBALS['_CFG']['service_email'], $tpl['template_subject'], $content, $tpl['is_html']);
            }*/
            /* 处理虚拟团购商品 */
            /* 如果订单金额为0 处理虚拟卡 */

            if ($order['order_amount'] <= 0)
            {
                $sql = "SELECT goods_id, goods_name,extension_code, goods_attr_id, goods_number AS num FROM ".
                    $this->_tb_cart .
                    " WHERE is_real = 0 ".
                    " AND $sql_where AND rec_type = '$flow_type'";

                $res = $this->_db->getAll($sql);

                $sql = "SELECT user_money,mobile_phone FROM " . $this->_tb_users . " WHERE user_id = '" . $order['user_id'] . "'";
                $users = $this->_db->getRow($sql);

                $virtual_goods = array();
                $virtual_goods_num = 0;
                foreach ($res AS $row)
                {
                    $sqla = "select valid_date,supplier_id from ".$this->_tb_goods ." where goods_id=".$row['goods_id'];
                    $goods_info = $this->_db->getRow($sqla);
                    $valid_date = $goods_info['valid_date'];
                    $supplier_id = $goods_info['supplier_id'];
                    $virtual_goods[$row['extension_code']][] = array('goods_id' => $row['goods_id'], 'goods_attr_id'=>$row['goods_attr_id'], 'goods_name' => $row['goods_name'], 'num' => $row['num'],'valid_date'=>$valid_date,'supplier_id'=>$supplier_id,'mobile_phone'=>$users['mobile_phone']);
                }

                if ($virtual_goods AND $flow_type != CART_GROUP_BUY_GOODS)
                {

                    $msg = '';
                    $card_sn = '';
                    /* 虚拟卡发货 */
                    if (virtual_goods_ship($virtual_goods,$msg, $order['order_sn'], true))
                    {
                        foreach($virtual_goods['virtual_good'] as $key=>$val){
                            if($val['supplier_id']){
                                $supplier_name = $this->_db->getOne("select supplier_name from ".$GLOBALS['ecs']->table('supplier')." where supplier_id = $val[supplier_id]");
                            }else{
                                $supplier_name = '网站自营';
                            }
                            $card = $this->_db->getAll("select card_sn from ".$this->_tb_virtual_goods_card." where order_sn='".$order['order_sn']."'");
                            require_once (ROOT_PATH . 'sms/sms.php');
                            foreach($card as $k=>$v){
                                $card_sn .= $v['card_sn'].", ";
                            }
                            $content = sprintf($GLOBALS['_LANG']['mobile_virtual_template'], $supplier_name, $val['goods_name'], $card_sn,local_date('Y-m-d',$val['valid_date']));
                            try{
                                //sendSMS($_REQUEST['mobile_phone'],$content);//发送短信
                                sendSMS($users['mobile_phone'],$content);//发送短信
                            }catch(Exception $e) {

                            }
                        }
                        /* 如果没有实体商品，修改发货状态，送积分和红包 */
                        $sql = "SELECT COUNT(*)" .
                            " FROM " . $this->_tb_order_goods .
                            " WHERE order_id = '$order[order_id]' " .
                            " AND is_real = 1";
                        if ($this->_db->getOne($sql) <= 0)
                        {
                            /* 修改订单状态 */
                            update_order($order['order_id'], array('shipping_status' => SS_SHIPPED, 'shipping_time' => gmtime()));
                            /* 如果订单用户不为空，计算积分，并发给用户；发红包 */

                            if ($order['user_id'] > 0)
                            {
                                /* 取得用户信息 */
                                $user = user_info($order['user_id']);

                                /* 计算并发放积分 */
                                $integral = integral_to_give($order);
                                log_account_change($order['user_id'], 0, 0, intval($integral['rank_points']), intval($integral['custom_points']), sprintf($GLOBALS['_LANG']['order_gift_integral'], $order['order_sn']));


                                /* 发放红包 */
                                send_order_bonus($order['order_id']);
                            }
                        }
                    }
                }

            }

            //为每一个订单生成一个支付日志记录
            $order['log_id'] = insert_pay_log($order['order_id'], $order['order_amount'], PAY_ORDER);
            $all_order_amount += $order['order_amount'];
            user_uc_call('add_feed', array($order['order_id'], BUY_GOODS)); //推送feed到uc
        }

        /* 清空购物车 */
        // 谁让你全部清空的啊？
        require_once (ROOT_PATH . 'includes/cls_cart.php');
        $cart = new cls_cart();
        foreach(explode(',',$sel_goods) as $v){
            $cart->flow_drop_cart_goods($v,$user_rank_info);
        }

        clear_cart($flow_type, $id_ext, $user_rank_info['user_id']);
        /* 清除缓存，否则买了商品，但是前台页面读取缓存，商品数量不减少 */
        clear_all_files();

        //删除父订单记录
        if($del_patent_id > 0){
            $sql="delete from ".$this->_tb_order_info." where order_id='$del_patent_id' ";
            $this->_db->query($sql);
        }

        //$split_order = split_order($new_order_id);
        // $smarty->assign('split_order',      $split_order);
        /* 如果需要，发短信 */
        if(count($split_order['suborder_list']) > 0){
            foreach($split_order['suborder_list'] as $key => $val){
                $supplier_ids[$key] = $val['order_sn'];
            }
        }
        //$supplier_ids = array_keys();
        require_once (ROOT_PATH . 'sms/sms.php');

        //send_sms($supplier_ids,$GLOBALS['_CFG']['sms_order_placed_tpl'],1);

        $order['order_amount'] = $all_order_amount; //替换为总金额去支付

        /* 取得支付信息，生成支付代码 */
        if ($split_order['sub_order_count'] >1)
        {
            //如果一次下单有多个订单要支付，生成一个父订单的日志
            $order['order_sn'] = $parent_order_id;
            /* 插入支付日志 */
            //$order['log_id'] = insert_pay_log($order['order_sn'], $order['order_amount'], PAY_ORDER);
        }else{
            $order['order_sn'] = $order['order_id'];
            /* 插入支付日志 */
            //$order['log_id'] = insert_pay_log($order['order_id'], $order['order_amount'], PAY_ORDER);
        }
        //print_r($order);
        if ($order['order_amount'] > 0 && false)
        {
            $payment = payment_info($order['pay_id']);
            //这里要写支付相关的
            include_once('includes/modules/payment/' . $payment['pay_code'] . '.php');

            $pay_obj    = new $payment['pay_code'];

            $pay_online = $pay_obj->get_code($order, unserialize_config($payment['pay_config']));

            $payment_www_com=unserialize_config($payment['pay_config']);
            if ($payment['pay_code']=='alipay_bank')
            {
                $payment_www_com['hunuo_alipay_bank'] = $_POST['hunuo_bank'] ? trim($_POST['hunuo_bank']) : "hunuo_com";

                $pay_online = $pay_obj->get_code($order, $payment_www_com);
            }

            $order['pay_desc'] = $payment['pay_desc'];

            // $smarty->assign('pay_online', $pay_online);
        }
        if(!empty($order['shipping_name']))
        {
            $order['shipping_name']=trim(stripcslashes($order['shipping_name']));
        }

        // 清除不必要的信息 , 只需要order_id
        foreach($order as $key => $value){
            if($key != 'order_id' && $key != 'order_amount'){
                unset($order[$key]);
            }
        }

        /* 订单信息 */
        $return = array(
            'code' => 200,
            'data' => $order,
            'message' => 'success'
        );

        return $return;
    }



    /**
     * 获得上一次用户采用的支付和配送方式
     *
     * @access  public
     * @return  void
     */
    private function last_shipping_and_payment($user_id)
    {
        $sql = "SELECT shipping_id, pay_id " .
            " FROM " . $this->_tb_order_info .
            " WHERE user_id = '$user_id' " .
            " ORDER BY order_id DESC LIMIT 1";
        $row = $this->_db->getRow($sql);

        if (empty($row))
        {
            /* 如果获得是一个空数组，则返回默认值 */
            $row = array('shipping_id' => 0, 'pay_id' => 0);
        }

        return $row;
    }

    /**
     * 获得订单信息
     *
     * @access  private
     * @param  int   $user_id 用户ID
     * @param  array $order   订单信息
     * @return  array
     */
    public function flow_order_info($user_id, $order,$device = '',$flow_type = 0)
    {
        $device=$device?$device:'ios';

        if($device=='ios' || $device == 'android'){
            $device_where = " AND pay_code IN ('APP','QUICK_MSECURITY_PAY') ";
        }
        elseif($device=='wap' ){
            $device_where = " AND pay_code IN ('QUICK_WAP_WAY','JSAPI','MWEB') ";
        }
        elseif($device=='pc'){
            $device_where = " AND pay_code IN ('FAST_INSTANT_TRADE_PAY','NATIVE') ";
        }
        elseif($device=='xcx'){
            $device_where = " AND pay_code IN ('XCX','XCX') ";
        }


        $pay_id  = $GLOBALS['db']->getOne("SELECT pay_id FROM " . $GLOBALS['ecs']->table('payment') . " WHERE enabled=1 $device_where  ORDER BY pay_id ASC LIMIT 1");
        $shipping_id=$pay_id?$pay_id:0;


        $shipping_id  = $GLOBALS['db']->getOne("SELECT shipping_id FROM " . $GLOBALS['ecs']->table('shipping') . " WHERE enabled=1 AND is_default_show=1 AND support_pickup=0   ORDER BY shipping_id ASC LIMIT 1");
        $shipping_id=$shipping_id?$shipping_id:0;


        /* 初始化配送和支付方式 */
        if (!isset($order['shipping_id']) || !isset($order['pay_id']))
        {
            /* 如果还没有设置配送和支付 */
            // if ($user_id > 0)
            // {
            //     /* 用户已经登录了，则获得上次使用的配送和支付 */
            //     $arr = $this->last_shipping_and_payment($user_id);

            //     if (!isset($order['shipping_id']))
            //     {
            //         $order['shipping_id'] = $arr['shipping_id'];
            //     }
            //     if (!isset($order['pay_id']))
            //     {
            //         $order['pay_id'] = $arr['pay_id'];
            //     }
            // }
            // else
            // {
            //     if (!isset($order['shipping_id']))
            //     {
            //         $order['shipping_id'] = 0;
            //     }
            //     if (!isset($order['pay_id']))
            //     {
            //         $order['pay_id'] = 0;
            //     }
            // }

            $order['pay_id']=$pay_id;
            $order['shipping_id']=$shipping_id;

        }

        if (!isset($order['pack_id']))
        {
            $order['pack_id'] = 0;  // 初始化包装
        }
        if (!isset($order['card_id']))
        {
            $order['card_id'] = 0;  // 初始化贺卡
        }
        if (!isset($order['bonus']))
        {
            $order['bonus'] = 0;    // 初始化红包
        }
        if (!isset($order['integral']))
        {
            $order['integral'] = 0; // 初始化积分
        }
        if (!isset($order['surplus']))
        {
            $order['surplus'] = 0;  // 初始化余额
        }
        if (!isset($order['surplus']))
        {
            $order['surplus'] = 0;  // 初始化余额
        }

//        $order['your_surplus'] = 0;  // 你的余额
        $order['allow_use_surplus'] = 0;  // 是否可使用余额
        $order['allow_use_integral'] = 0;  // 是否可使用积分
        $order['allow_use_bonus'] = 0;  // 是否可使用优惠券
        $order['can_invoice'] = 0;  // 是否可开发票
//        $order['your_integral'] = 0;  // 你的积分


        if($order['pay_id']>0){
            $order['pay_name']=$GLOBALS['db']->getOne("SELECT pay_desc FROM " . $GLOBALS['ecs']->table('payment') . " WHERE pay_id='$order[pay_id]'");
        }
        else{
            $order['pay_name']="余额支付";
        }


        /* 扩展信息 */
        if (isset($flow_type) && intval($flow_type) != CART_GENERAL_GOODS)
        {
            $order['extension_code'] = $_SESSION['extension_code'];
            $order['extension_id'] = $_SESSION['extension_id'];
            $order['flow_type'] = $flow_type;
        }

        return $order;
    }

    /**
     * 获得订单中的费用信息
     *
     * @access  public
     * @param   array   $order
     * @param   array   $goods
     * @param   array   $consignee
     * @param   bool    $is_gb_deposit  是否团购保证金（如果是，应付款金额只计算商品总额和支付费用，可以获得的积分取 $gift_integral）
     * @return  array
     */
    public function order_fee($order, $goods, $consignee, $user_rank_info, $sel_cartgoods, $flow_type, $is_design = 0)
    {
        $user_id = $user_rank_info['user_id'];
        /* 初始化订单的扩展code */
        if (!isset($order['extension_code']))
        {
            $order['extension_code'] = '';
        }

        if ($order['extension_code'] == 'group_buy')
        {
            $group_buy = group_buy_info($order['extension_id']);
        }

        /* 预售活动 */
        if ($order['extension_code'] == PRE_SALE_CODE)
        {
            $pre_sale = pre_sale_info($order['extension_id']);
        }

        $total  = array('real_goods_count' => 0,
            'goods_count'      => 0,
            'gift_amount'      => 0,
            'goods_price'      => 0,
            'market_price'     => 0,
            'discount'         => 0,
            'pack_fee'         => 0,
            'card_fee'         => 0,
            'shipping_fee'     => 0,
            'shipping_insure'  => 0,
            'integral_money'   => 0,
            'bonus'            => 0,
            'surplus'          => 0,
            'cod_fee'          => 0,
            'pay_fee'          => 0,
            'tax'              => 0);
        $weight = 0;

        /* 商品总价 */
        foreach ($goods AS $val)
        {
            /* 统计实体商品的个数 */
            if ($val['is_real'])
            {
                $total['real_goods_count'] += $val['goods_number'];
            }

            $total['goods_price']  += $val['goods_price'] * $val['goods_number'];
            $total['market_price'] += $val['market_price'] * $val['goods_number'];

            $total['goods_count'] += $val['goods_number'];
        }

        $total['saving']    = $total['market_price'] - $total['goods_price'];
        $total['save_rate'] = $total['market_price'] ? round($total['saving'] * 100 / $total['market_price']) . '%' : 0;

        $total['goods_price_formated']  = price_format($total['goods_price'], false);
        $total['market_price_formated'] = price_format($total['market_price'], false);
        $total['saving_formated']       = price_format($total['saving'], false);

        /* 折扣 */
        if ($order['extension_code'] != GROUP_BUY_CODE && $order['extension_code'] != PRE_SALE_CODE && $is_design == 0)
        {
            $discount = $this->compute_discount(isset($order['supplier_id']) ? $order['supplier_id'] : -1, $user_rank_info, $sel_cartgoods);
            $total['discount'] = $discount['discount'];
            $total['discount_name'] = $discount['name'];
            if ($total['discount'] > $total['goods_price'])
            {
                $total['discount'] = $total['goods_price'];
            }
        }
        $total['discount_formated'] = price_format($total['discount'], false);

        /* 税额 */
        if (!empty($order['need_inv']) && $order['inv_type'] != '')
        {
            /* 查税率 */
            // $rate = 0;
            // foreach ($GLOBALS['_CFG']['invoice_type']['type'] as $key => $type)
            // {
            //     if ($type == $order['inv_type'])
            //     {
            //         $rate = floatval($GLOBALS['_CFG']['invoice_type']['rate'][$key]) / 100;
            //         break;
            //     }
            // }
            // if ($rate > 0)
            // {
            //     $total['tax'] = $rate * $total['goods_price'];
            // }
        }
        $total['tax_formated'] = price_format($total['tax'], false);

        /* 包装费用 */
        if (!empty($order['pack_id']))
        {
            $total['pack_fee']      = pack_fee($order['pack_id'], $total['goods_price']);
        }
        $total['pack_fee_formated'] = price_format($total['pack_fee'], false);

        /* 贺卡费用 */
        if (!empty($order['card_id']))
        {
            $total['card_fee']      = card_fee($order['card_id'], $total['goods_price']);
        }
        $total['card_fee_formated'] = price_format($total['card_fee'], false);

        /* 红包 */
        $total['bonus'] = 0;

        if (!empty($order['bonus_id']))
        {
            $bonus          = bonus_info($order['bonus_id']);
            $total['bonus'] = $bonus['type_money'];
        }


        /* 线下红包 */
        if (!empty($order['bonus_sn']))
        {
            $bonus          = bonus_info(0,$order['bonus_sn']);
            $total['bonus'] += $bonus['type_money'];
            //$total['bonus_kill'] = $order['bonus_kill'];
            //$total['bonus_kill_formated'] = price_format($total['bonus_kill'], false);
        }
        $total['bonus_formated'] = price_format($total['bonus'], false);



        /* 配送费用 */
        $shipping_cod_fee = NULL;

        $sql_where = "user_id='". $user_id ."' ";

        //if (count($order['shipping_pay']) > 0 && $total['real_goods_count'] > 0){
        if (count($order['shipping_pay']) > 0){

            foreach ($goods AS $val)
            {
                if ($val['extension_code'] == 'package_buy')
                {
                    $sql_supp = "select g.supplier_id, IF(g.supplier_id='0', '本网站', s.supplier_name) AS supplier_name2 from ".$GLOBALS['ecs']->table('goods_activity').
                        " AS g left join ".$GLOBALS['ecs']->table('supplier')." AS s on g.supplier_id=s.supplier_id where g.act_id='". $val['goods_id'] ."' ";
                }
                else
                {
                    $sql_supp = "select g.supplier_id, IF(g.supplier_id='0', '本网站', s.supplier_name) AS supplier_name2 from ".$this->_tb_goods.
                        " AS g left join ".$GLOBALS['ecs']->table('supplier')." AS s on g.supplier_id=s.supplier_id where g.goods_id='". $val['goods_id'] ."' ";
                }
                $row_supp = $this->_db->getRow($sql_supp);
                $row_supp['supplier_id'] = $row_supp['supplier_id'] ? intval($row_supp['supplier_id']) :0;

                $region['country']  = $consignee['country'];
                $region['province'] = $consignee['province'];
                $region['city']     = $consignee['city'];
                $region['district'] = $consignee['district'];
                @$shipping_info = shipping_area_info($order['shipping_pay'][$row_supp['supplier_id']], $region);



                @$total['supplier_shipping'][$row_supp['supplier_id']]['supplier_name'] =$row_supp['supplier_name2'];
                @$total['supplier_shipping'][$row_supp['supplier_id']]['goods_number'] += $val['goods_number'];

                @$total['supplier_goodsnumber'][$row_supp['supplier_id']] += $val['goods_number'];

                @$total['goods_price_supplier'][$row_supp['supplier_id']]  += $val['goods_price'] * $val['goods_number'];

                if ($order['extension_code'] == 'group_buy')
                {
                    $weight_price2 = $this->cart_weight_price(CART_GROUP_BUY_GOODS, $row_supp['supplier_id'], $user_rank_info, $sel_cartgoods);
                }
                else
                {
                    $weight_price2 = $this->cart_weight_price(CART_GENERAL_GOODS, $row_supp['supplier_id'], $user_rank_info, $sel_cartgoods);
                }

                // 查看购物车中是否全为免运费商品，若是则把运费赋为零
                $sql_where = "c.user_id='". $user_rank_info['user_id'] ."' ";
                $sql_plus = '';
                if($sel_cartgoods)
                {
                    $sql_plus = " AND c.rec_id in (".$sel_cartgoods.")";
                }
                if ($val['extension_code'] == 'package_buy')
                {
                    $sql = 'SELECT count(*) FROM ' . $this->_tb_cart . " AS c left join ". $GLOBALS['ecs']->table('goods_activity') ." AS g on c.goods_id=g.act_id WHERE g.supplier_id = '". $row_supp['supplier_id'] ."' AND $sql_where AND c.extension_code != 'package_buy' AND c.is_shipping = 0 ".$sql_plus;  //jx
                }
                else
                {
                    $sql = 'SELECT count(*) FROM ' . $this->_tb_cart . " AS c left join ". $this->_tb_goods ." AS g on c.goods_id=g.goods_id WHERE g.supplier_id = '". $row_supp['supplier_id'] ."' AND $sql_where AND c.extension_code != 'package_buy' AND c.is_shipping = 0 ".$sql_plus;  //jx
                }

                $shipping_count_supp = $this->_db->getOne($sql);

                $total['supplier_shipping'][$row_supp['supplier_id']]['shipping_fee'] = ($shipping_count_supp == 0 AND $weight_price2['free_shipping'] == 1) ?0 :  shipping_fee($shipping_info['shipping_code'],$shipping_info['configure'], $weight_price2['weight'], $total['goods_price_supplier'][$row_supp['supplier_id']], $weight_price2['number']);
                $total['supplier_shipping'][$row_supp['supplier_id']]['formated_shipping_fee'] = price_format($total['supplier_shipping'][$row_supp['supplier_id']]['shipping_fee'], false);
            }

            krsort($total['supplier_shipping']);

            $total['shipping_fee']    = 0;
            foreach($total['supplier_shipping'] AS $supp_shipping)
            {
                $total['shipping_fee'] += $supp_shipping['shipping_fee'];
            }

        }

        //如果是虚拟商品，则运费为0
        if ($order['extension_code'] == 'virtual_good'){
            $total['shipping_fee']    = 0;
        }
        $total['shipping_fee_formated']    = price_format($total['shipping_fee'], false);

        // 购物车中的商品能享受红包支付的总额
        $bonus_amount = $this->compute_discount_amount(-1,$user_rank_info,$sel_cartgoods,$flow_type);
        // 红包和积分最多能支付的金额为商品总额
        //$max_amount = $total['goods_price'] == 0 ? $total['goods_price'] : $total['goods_price'] - $bonus_amount;
        $max_amount = $total['goods_price'] == 0 ? $total['goods_price'] : ($total['goods_price'] - $bonus_amount) > 0 ? $total['goods_price'] - $bonus_amount : 0 ;

        /* 计算订单总额 */
        if ($order['extension_code'] == GROUP_BUY_CODE && $group_buy['deposit'] > 0)
        {
            $total['amount'] = $total['goods_price'];
        }
        else if($order['extension_code'] == PRE_SALE_CODE && $pre_sale['deposit'] > 0)
        {
            $total['amount'] = $total['goods_price'];
        }
        else
        {
            $total['amount'] = $total['goods_price'] - $total['discount'] + $total['tax'] + $total['pack_fee'] + $total['card_fee'] +
                $total['shipping_fee'] + $total['shipping_insure'] + $total['cod_fee'];

            // 减去红包金额

            $use_bonus        = min($total['bonus'], $max_amount); // 实际减去的红包金额
            if(isset($total['bonus_kill']))
            {
                $use_bonus_kill   = min($total['bonus_kill'], $max_amount);
                $total['amount'] -=  $price = ($total['bonus_kill'] > 0 ? number_format($total['bonus_kill'], 2, '.', '') : 0); // 还需要支付的订单金额
            }

            $total['bonus']   = $use_bonus;
            $total['bonus_formated'] = price_format($total['bonus'], false);

            $total['amount'] -= $use_bonus; // 还需要支付的订单金额
            $max_amount      -= $use_bonus; // 积分最多还能支付的金额

        }

        /* 余额 */
        $order['surplus'] = $order['surplus'] > 0 ? $order['surplus'] : 0;
        if ($total['amount'] > 0)
        {
            if (isset($order['surplus']) && $order['surplus'] > $total['amount'])
            {
                $order['surplus'] = $total['amount'];
                $total['amount']  = 0;
            }
            else
            {
                $total['amount'] -= floatval($order['surplus']);
            }
        }
        else
        {
            $order['surplus'] = 0;
            $total['amount']  = 0;
        }
        $total['surplus'] = $order['surplus'];
        $total['surplus_formated'] = price_format($order['surplus'], false);

        /* 积分 */
        $order['integral'] = $order['integral'] > 0 ? $order['integral'] : 0;
        if ($total['amount'] > 0 && $max_amount > 0 && $order['integral'] > 0)
        {
            $integral_money = value_of_integral($order['integral']);

            // 使用积分支付
            $use_integral            = min($total['amount'], $max_amount, $integral_money); // 实际使用积分支付的金额
            $total['amount']        -= $use_integral;
            $total['integral_money'] = $use_integral;
            $order['integral']       = integral_of_value($use_integral);
        }
        else
        {
            $total['integral_money'] = 0;
            $order['integral']       = 0;
        }
        $total['integral'] = $order['integral'];
        $total['integral_formated'] = price_format($total['integral_money'], false);

        /* 保存订单信息 */
        $_SESSION['flow_order'] = $order;

        $se_flow_type = isset($flow_type) ? $flow_type : '';

        /* 支付费用 */
        if (!empty($order['pay_id']) && ($total['real_goods_count'] > 0 || $se_flow_type != CART_EXCHANGE_GOODS))
        {
            $total['pay_fee']      = pay_fee($order['pay_id'], $total['amount'], $shipping_cod_fee);
        }

        $total['pay_fee_formated'] = price_format($total['pay_fee'], false);

        //$total['amount']           += $total['pay_fee']; // 订单总额累加上支付费用
        $total['amount_formated']  = price_format($total['amount'], false);

        /* 取得可以得到的积分和红包 */
        if ($order['extension_code'] == GROUP_BUY_CODE)
        {
            $total['will_get_integral'] = $group_buy['gift_integral'];
        }
        else if($order['extension_code'] == PRE_SALE_CODE)
        {
            $total['will_get_integral'] = $pre_sale['gift_integral'];
        }
        elseif ($order['extension_code'] == 'exchange_goods')
        {
            $total['will_get_integral'] = 0;
        }
        else
        {
            $total['will_get_integral'] = $this->get_give_integral($user_rank_info, $sel_cartgoods,$flow_type);
        }
        //$total['will_get_bonus']        = $order['extension_code'] == 'exchange_goods' ? 0 : price_format(get_total_bonus(), false);
//        $total['will_get_bonus']        = $order['extension_code'] == 'exchange_goods' ? 0 : price_format($this->get_total_bonus($total['goods_price_supplier'], $user_rank_info, $sel_cartgoods), false);
        $total['goods_price_supplier'] = isset($total['goods_price_supplier']) ? $total['goods_price_supplier'] : 0;
        $total['will_get_bonus']        = $order['extension_code'] == 'exchange_goods' ? 0 : $this->get_total_bonus($total['goods_price_supplier'], $user_rank_info, $sel_cartgoods,$flow_type);
        $total['formated_goods_price']  = price_format($total['goods_price'], false);
        $total['formated_market_price'] = price_format($total['market_price'], false);
        $total['formated_saving']       = price_format($total['saving'], false);

        if ($order['extension_code'] == 'exchange_goods')
        {
            $sql_exchange = "c.user_id='". $user_id ."' ";
            $sql = 'SELECT SUM(eg.exchange_integral) '.
                'FROM ' . $this->_tb_cart . ' AS c,' . $this->_tb_exchange_goods . 'AS eg '.
                "WHERE c.goods_id = eg.goods_id AND " . $sql_exchange .
                "  AND c.rec_type = '" . CART_EXCHANGE_GOODS . "' " .
                '  AND c.is_gift = 0 AND c.goods_id > 0 ' .
                'GROUP BY eg.goods_id';
            $exchange_integral = $this->_db->getOne($sql);
            $total['exchange_integral'] = $exchange_integral;
        }

        unset($total['supplier_shipping']);
        unset($total['supplier_goodsnumber']);
//        unset($total['goods_price_supplier']);
        unset($total['saving']);
        unset($total['save_rate']);
        unset($total['market_price_formated']);
        unset($total['saving_formated']);
        //unset($total['discount_formated']);
        unset($total['surplus_formated']);
        unset($total['formated_market_price']);
        unset($total['formated_saving']);

        return $total;
    }

    /**
     * 计算折扣：根据购物车和优惠活动
     * @param int $supplierid  店铺id
     * @param array $user_rank_info  会员等级信息
     * @param string $sel_cartgoods  购物车选中的商品
     * @return  float   折扣
     */
    private function compute_discount($supplierid=-1, $user_rank_info, $sel_cartgoods)
    {
        $user_id = $user_rank_info['user_id'];
        /* 查询优惠活动 */
        $now = gmtime();
        $user_rank = ',' . $user_rank_info['user_rank'] . ',';
        $sql = "SELECT *" .
            "FROM " . $this->_tb_favourable_activity .
            " WHERE start_time <= '$now'" .
            " AND end_time >= '$now'" .
            " AND CONCAT(',', user_rank, ',') LIKE '%" . $user_rank . "%'" .
            " AND act_type " . db_create_in(array(FAT_DISCOUNT, FAT_PRICE));
        $sql .= ($supplierid>=0) ? " AND supplier_id=".$supplierid : "";
        $favourable_list = $this->_db->getAll($sql);
        if (!$favourable_list)
        {
            return 0;
        }

        /* 查询购物车商品 */
        $sql_where = "c.user_id='". $user_id ."' ";

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
        $sql .= (isset($sel_cartgoods) && !empty($sel_cartgoods)) ? " AND c.rec_id in (". $sel_cartgoods .") " : "";

        $goods_list = $this->_db->getAll($sql);

        if (!$goods_list)
        {
            return 0;
        }

        /* 初始化折扣 */
        $discount = 0;
        $favourable_name = '';

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
                    //享受价格折扣
                    $discount += $total_amount * (1 - $favourable['act_type_ext'] / 100);

                    $favourable_name .= ' | '.$favourable['act_name'];
                }
                elseif ($favourable['act_type'] == FAT_PRICE)
                {
                    //享受现金减免
                    //$discount += $favourable['act_type_ext'];//直接减后台设置的优惠值，2018.03.03改为以下满多少减多少的价格阶梯设置

                    //满多少减多少的价格阶梯方式
                    $price = 0;
                    $ext_info = unserialize($favourable['ext_info']);
                    /* 处理价格阶梯 */
                    $price_ladder = $ext_info['price_ladder'];
                    if (!is_array($price_ladder) || empty($price_ladder))
                    {
                        //没设置
                        //$price_ladder = array(array('amount' => 0, 'price' => 0));
                    }
                    else
                    {
                        foreach ($price_ladder as $key => $amount_price)
                        {
                            if($total_amount >= $amount_price['amount']){
                                $price = $amount_price['price'];
                            }
                        }
                    }
                    $discount += $price;

                    $favourable_name .= ' | '.$favourable['act_name'];
                }
            }
        }

        return array('discount' => $discount, 'name' => trim($favourable_name,' | '));
    }

    /**
     * 获得购物车中商品的总重量、总价格、总数量
     *
     * @access  public
     * @param   int     $type   类型：默认普通商品
     * @param   int     $supplier_id   供应商ID
     * @param   array   $user_rank_info   会员等级信息
     * @param   string  $sel_cartgoods   选中的购物车商品
     * @return  array
     */
    private function cart_weight_price($type = CART_GENERAL_GOODS, $supplier_id, $user_rank_info, $sel_cartgoods)
    {
        $package_row['weight'] = 0;
        $package_row['amount'] = 0;
        $package_row['number'] = 0;

        $packages_row['free_shipping'] = 1;

        $sql_where = " c.user_id='". $user_rank_info['user_id'] ."' ";
        $sql_plus = '';

        /* 计算超值礼包内商品的相关配送参数 */
        if($sel_cartgoods){
            $sql_plus = "AND c.rec_id in (".$sel_cartgoods.")";
        }
        $sql = 'SELECT goods_id, goods_number, goods_price FROM ' . $this->_tb_cart . " AS c WHERE c.extension_code = 'package_buy' AND ".$sql_where." ".$sql_plus;
        $row = $this->_db->getAll($sql);

        if ($row)
        {
            $packages_row['free_shipping'] = 0;
            $free_shipping_count = 0;

            foreach ($row as $val)
            {
                // 如果商品全为免运费商品，设置一个标识变量
                $sql = 'SELECT count(*) FROM ' .
                    $GLOBALS['ecs']->table('package_goods') . ' AS pg, ' .
                    $this->_tb_goods . ' AS g ' .
                    "WHERE g.supplier_id='". $supplier_id ."' and g.goods_id = pg.goods_id AND g.is_shipping = 0 AND pg.package_id = '"  . $val['goods_id'] . "'";
                $shipping_count = $this->_db->getOne($sql);

                if ($shipping_count > 0)
                {
                    // 循环计算每个超值礼包商品的重量和数量，注意一个礼包中可能包换若干个同一商品
                    $sql = 'SELECT SUM(g.goods_weight * pg.goods_number) AS weight, ' .
                        'SUM(pg.goods_number) AS number FROM ' .
                        $GLOBALS['ecs']->table('package_goods') . ' AS pg, ' .
                        $this->_tb_goods . ' AS g ' .
                        "WHERE g.supplier_id='". $supplier_id ."' and g.goods_id = pg.goods_id AND g.is_shipping = 0 AND pg.package_id = '"  . $val['goods_id'] . "'";

                    $goods_row = $this->_db->getRow($sql);
                    $package_row['weight'] += floatval($goods_row['weight']) * $val['goods_number'];
                    $package_row['amount'] += floatval($val['goods_price']) * $val['goods_number'];
                    $package_row['number'] += intval($goods_row['number']) * $val['goods_number'];
                }
                else
                {
                    $free_shipping_count++;
                }
            }

            $packages_row['free_shipping'] = $free_shipping_count == count($row) ? 1 : 0;
        }

        /* 获得购物车中非超值礼包商品的总重量 */
        $sql    = 'SELECT SUM(g.goods_weight * c.goods_number) AS weight, ' .
            'SUM(c.goods_price * c.goods_number) AS amount, ' .
            'SUM(c.goods_number) AS number '.
            'FROM ' . $this->_tb_cart . ' AS c '.
            'LEFT JOIN ' . $this->_tb_goods . ' AS g ON g.goods_id = c.goods_id '.
            "WHERE g.supplier_id='". $supplier_id ."' and ".$sql_where." $sql_plus  " .
            "AND c.rec_type = '$type' AND g.is_shipping = 0 AND c.extension_code != 'package_buy'";
        $row = $this->_db->getRow($sql);

        $packages_row['weight'] = floatval($row['weight']) + $package_row['weight'];
        $packages_row['amount'] = floatval($row['amount']) + $package_row['amount'];
        $packages_row['number'] = intval($row['number']) + $package_row['number'];
        /* 格式化重量 */
        $packages_row['formated_weight'] = formated_weight($packages_row['weight']);

        return $packages_row;
    }

    private function cart_weight_price2($type = CART_GENERAL_GOODS, $supplier_id, $user_id, $sel_cartgoods='')
    {
        $package_row['weight'] = 0;
        $package_row['amount'] = 0;
        $package_row['number'] = 0;

        $packages_row['free_shipping'] = 1;

        $sql_where = " c.user_id='". $user_id ."' ";

        /* 计算超值礼包内商品的相关配送参数 */
        $sql_plus='';
        if($sel_cartgoods){
            $sql_plus .= "AND c.rec_id in (".$sel_cartgoods.")";
        }
        $sql = 'SELECT goods_id, goods_number, goods_price FROM ' . $GLOBALS['ecs']->table('cart') . "  AS c WHERE c.extension_code = 'package_buy' AND ".$sql_where." ".$sql_plus;
        $row = $GLOBALS['db']->getAll($sql);

        if ($row)
        {
            $packages_row['free_shipping'] = 0;
            $free_shipping_count = 0;

            foreach ($row as $val)
            {
                // 如果商品全为免运费商品，设置一个标识变量
                $sql = 'SELECT count(*) FROM ' .
                        $GLOBALS['ecs']->table('package_goods') . ' AS pg, ' .
                        $GLOBALS['ecs']->table('goods') . ' AS g ' .
                        "WHERE g.supplier_id='". $supplier_id ."' and g.goods_id = pg.goods_id AND g.is_shipping = 0 AND pg.package_id = '"  . $val['goods_id'] . "'";
                $shipping_count = $GLOBALS['db']->getOne($sql);

                if ($shipping_count > 0)
                {
                    // 循环计算每个超值礼包商品的重量和数量，注意一个礼包中可能包换若干个同一商品
                    $sql = 'SELECT SUM(g.goods_weight * pg.goods_number) AS weight, ' .
                        'SUM(pg.goods_number) AS number FROM ' .
                        $GLOBALS['ecs']->table('package_goods') . ' AS pg, ' .
                        $GLOBALS['ecs']->table('goods') . ' AS g ' .
                        "WHERE g.supplier_id='". $supplier_id ."' and g.goods_id = pg.goods_id AND g.is_shipping = 0 AND pg.package_id = '"  . $val['goods_id'] . "'";

                    $goods_row = $GLOBALS['db']->getRow($sql);
                    $package_row['weight'] += floatval($goods_row['weight']) * $val['goods_number'];
                    $package_row['amount'] += floatval($val['goods_price']) * $val['goods_number'];
                    $package_row['number'] += intval($goods_row['number']) * $val['goods_number'];
                }
                else
                {
                    $free_shipping_count++;
                }
            }

            $packages_row['free_shipping'] = $free_shipping_count == count($row) ? 1 : 0;
        }

        /* 获得购物车中非超值礼包商品的总重量 */
        $sql    = 'SELECT SUM(g.goods_weight * c.goods_number) AS weight, ' .
                        'SUM(c.goods_price * c.goods_number) AS amount, ' .
                        'SUM(c.goods_number) AS number '.
                    'FROM ' . $GLOBALS['ecs']->table('cart') . ' AS c '.
                    'LEFT JOIN ' . $GLOBALS['ecs']->table('goods') . ' AS g ON g.goods_id = c.goods_id '.
                    "WHERE g.supplier_id='". $supplier_id ."' and ".$sql_where." $sql_plus " .
                    "AND c.rec_type = '$type' AND g.is_shipping = 0 AND c.extension_code != 'package_buy'";
        $row = $GLOBALS['db']->getRow($sql);

        $packages_row['weight'] = floatval($row['weight']) + $package_row['weight'];
        $packages_row['amount'] = floatval($row['amount']) + $package_row['amount'];
        $packages_row['number'] = intval($row['number']) + $package_row['number'];
        /* 格式化重量 */
        $packages_row['formated_weight'] = formated_weight($packages_row['weight']);

        return $packages_row;
    }

    /**
     * 计算购物车中的商品能享受红包支付的总额
     * @param   int     $suppid  店铺id
     * @param   array   $user_rank_info  会员等级信息
     * @param   string  $sel_cartgoods  选中的购物车商品
     * @return  float   享受红包支付的总额
     */
    private function compute_discount_amount($suppid=-1, $user_rank_info, $sel_cartgoods,$type = CART_GENERAL_GOODS)
    {

        $user_id = $user_rank_info['user_id'];
        /* 查询优惠活动 */
        $now = gmtime();
        $user_rank = ',' . $user_rank_info['user_rank'] . ',';
        $where_suppid = '';
        if($suppid>-1){
            $where_suppid = " AND supplier_id = ".$suppid;
        }
        $sql = "SELECT *" .
            "FROM " . $GLOBALS['ecs']->table('favourable_activity') .
            " WHERE start_time <= '$now'" .
            " AND end_time >= '$now'" .$where_suppid.
            " AND CONCAT(',', user_rank, ',') LIKE '%" . $user_rank . "%'" .
            " AND act_type " . db_create_in(array(FAT_DISCOUNT, FAT_PRICE));
        $favourable_list = $this->_db->getAll($sql);
        if (!$favourable_list)
        {
            return 0;
        }

        /* 查询购物车商品 */
        $sql_where = "c.user_id='". $user_id ."' ";
        $where_suppid = (isset($sel_cartgoods) && !empty($sel_cartgoods)) ? " AND c.rec_id in (". $sel_cartgoods .") " : "";
        if($suppid > -1)
        {
            $sql = "SELECT c.goods_id, c.goods_price * c.goods_number AS subtotal, g.cat_id, g.brand_id, " .
                " IF(c.extension_code = 'package_buy', ga.supplier_id, g.supplier_id) AS supplier_id " .
                " FROM " . $this->_tb_cart . " AS c " .
                " LEFT JOIN " . $this->_tb_goods . " AS g " .
                " ON c.goods_id = g.goods_id AND g.supplier_id = " . $suppid .
                " LEFT JOIN " . $this->_tb_goods . " AS ga " .
                " ON c.goods_id = ga.goods_id AND ga.supplier_id = " . $suppid .
                " WHERE $sql_where " .$where_suppid.
                " AND c.parent_id = 0 " .
                " AND c.is_gift = 0 " .
                " AND rec_type = '$type'";
        }
        else
        {
            $sql = "SELECT c.goods_id, c.goods_price * c.goods_number AS subtotal, g.cat_id, g.brand_id, " .
                " IF(c.extension_code = 'package_buy', ga.supplier_id, g.supplier_id) AS supplier_id " .
                " FROM " . $this->_tb_cart . " AS c " .
                " LEFT JOIN " . $this->_tb_goods . " AS g " .
                " ON c.goods_id = g.goods_id " .
                " LEFT JOIN " . $this->_tb_goods . " AS ga " .
                " ON c.goods_id = ga.goods_id " .
                " WHERE $sql_where " .$where_suppid.
                " AND c.parent_id = 0 " .
                " AND c.is_gift = 0 " .
                " AND rec_type = '$type'";
        }
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
            if ($total_amount > 0 && $total_amount >= $favourable['min_amount'] && ($total_amount <= $favourable['max_amount'] || $favourable['max_amount'] == 0))
            {
                if ($favourable['act_type'] == FAT_DISCOUNT)
                {
                    $discount += $total_amount * (1 - $favourable['act_type_ext'] / 100);
                }
                elseif ($favourable['act_type'] == FAT_PRICE)
                {
                    $discount += $favourable['act_type_ext'];
                }
            }
        }


        return $discount;
    }

    /**
     * 取得购物车该赠送的积分数
     * @param   array   $goods  商品信息
     * @param   array   $user_rank_info  会员等级信息
     * @param   string  $sel_cartgoods  选中的购物车商品
     * @return  int     积分数
     */
    private function get_give_integral($user_rank_info,$sel_cartgoods,$flow_type=0)
    {
        $user_id = $user_rank_info['user_id'];

        $sql_where = "c.user_id='". $user_id ."' ";
        $sql_plus = '';
        if($sel_cartgoods){
            $sql_plus = " AND c.rec_id in (".$sel_cartgoods.")" ;
        }
        $sql = "SELECT " .
            " SUM(IF(c.extension_code = 'package_buy', 0, c.goods_number * IF(g.give_integral > -1, g.give_integral, c.goods_price))) " .
            " FROM " . $this->_tb_cart . " AS c " .
            " LEFT JOIN " . $this->_tb_goods . " AS g " .
            " ON c.goods_id = g.goods_id " .
            " LEFT JOIN " . $GLOBALS['ecs']->table('goods_activity') . " AS ga " .
            " ON c.goods_id = ga.goods_id " .
            " WHERE " . $sql_where .
            $sql_plus.
            " AND c.goods_id > 0 " .
            " AND c.parent_id = 0 " .
            " AND c.rec_type = '$flow_type' " .
            " AND c.is_gift = 0";

        return intval($this->_db->getOne($sql));
    }


    /**
     * 取得当前用户应该得到的红包总额
     * @param array $supplier_money_info 各个店铺对应的商品的总钱信息
     * @param   array   $user_rank_info  会员等级信息
     * @param   string  $sel_cartgoods  选中的购物车商品
     * @return  void
     */
    private function get_total_bonus($supplier_money_info, $user_rank_info, $sel_cartgoods, $flow_type=0)
    {
        $day    = getdate();
        $today  = local_mktime(23, 59, 59, $day['mon'], $day['mday'], $day['year']);

        $sql_where = " c.user_id='". $user_rank_info['user_id'] ."' ";
        $sql_plus = '';
        if($sel_cartgoods){
            $sql_plus = " AND c.rec_id in (".$sel_cartgoods.") ";
        }

        /* 取得购物车中非赠品总金额 */
        if(!is_array($supplier_money_info)){
            /* 按商品发的红包 */

            $sql = "SELECT SUM(c.goods_number * t.type_money)" .
                "FROM " . $this->_tb_cart . " AS c, "
                . $this->_tb_bonus_type . " AS t, "
                . $this->_tb_goods . " AS g " .
                "WHERE $sql_where " .
                "AND c.is_gift = 0 " .
                "AND c.goods_id = g.goods_id " .
                "AND g.bonus_type_id = t.type_id " .
                "AND t.send_type = '" . SEND_BY_GOODS . "' " .
                "AND t.send_start_date <= '$today' " .
                "AND t.send_end_date >= '$today' " .
                $sql_plus .
                " AND c.rec_type = '$flow_type'";
            $goods_total = floatval($this->_db->getOne($sql));

            $sql = "SELECT SUM(c.goods_price * c.goods_number) " .
                "FROM " . $this->_tb_cart .
                " as c WHERE $sql_where " .
                " AND is_gift = 0 " .
                $sql_plus.
                " AND rec_type = '$flow_type'";
            $amount = floatval($this->_db->getOne($sql));

            /* 按订单发的红包 */
            $sql = "SELECT FLOOR('$amount' / min_amount) * type_money " .
                "FROM " . $this->_tb_bonus_type .
                " WHERE send_type = '" . SEND_BY_ORDER . "' " .
                " AND send_start_date <= '$today' " .
                "AND send_end_date >= '$today' " .
                "AND min_amount > 0 ";
            $order_total = floatval($this->_db->getOne($sql));
        }else{
            $order_total = $goods_total = 0;
            foreach($supplier_money_info as $key => $val){

                /* 按商品发的红包 */
                $sql = "SELECT SUM(c.goods_number * t.type_money)" .
                    "FROM " . $this->_tb_cart . " AS c, "
                    . $this->_tb_bonus_type . " AS t, "
                    . $this->_tb_goods . " AS g " .
                    "WHERE $sql_where " .
                    "AND c.is_gift = 0 " .
                    "AND c.goods_id = g.goods_id " .
                    "AND t.supplier_id = g.supplier_id " .
                    "AND g.bonus_type_id = t.type_id " .
                    "AND t.send_type = '" . SEND_BY_GOODS . "' " .
                    "AND t.send_start_date <= '$today' " .
                    "AND t.send_end_date >= '$today' " .
                    "AND g.supplier_id = ".$key.
                    $sql_plus.
                    " AND c.rec_type = '$flow_type'";
                $goods_total += $this->_db->getOne($sql);

                $sql = "SELECT FLOOR('$val' / min_amount) * type_money " .
                    "FROM " . $this->_tb_bonus_type .
                    " WHERE send_type = '" . SEND_BY_ORDER . "' " .
                    " AND send_start_date <= '$today' " .
                    "AND send_end_date >= '$today' " .
                    " AND supplier_id = ".$key.
                    " AND min_amount > 0 ";
                $order_total += $this->_db->getOne($sql);
            }
            $goods_total = floatval($goods_total);
            $order_total = floatval($order_total);
        }


        return $goods_total + $order_total;
    }


    /**
     * 获得用户的可用积分
     *
     * @access  private
     * @param   array $user_rank_info 会员等级信息
     * @return  integral
     */
    public function flow_available_points($user_rank_info,$flow_type=0,$sel_cartgoods='')
    {

        $sql_plus="";
        if($sel_cartgoods)
        {
            $sql_plus .= " AND c.rec_id in (".$sel_cartgoods.")";
        }

        $sql_where = "c.user_id='". $user_rank_info['user_id'] ."' ";
        $sql = "SELECT SUM(g.integral * c.goods_number) as integral,g.supplier_id ".
            "FROM " . $this->_tb_cart . " AS c, " . $this->_tb_goods . " AS g " .
            "WHERE $sql_where $sql_plus AND c.goods_id = g.goods_id AND c.is_gift = 0 AND g.integral > 0 " .
            "AND c.rec_type = '$flow_type' GROUP BY g.supplier_id";

        $info = $this->_db->getAll($sql);
        $ret = array(0=>0);
        foreach($info as $key => $val){
            $ret[$val['supplier_id']] = integral_of_value(intval($val['integral']));
        }

        return $ret;
    }

    /**
     * 获得用户订单的可用积分
     *
     * @access  private
     * @param   array $user_rank_info 会员等级信息
     * @return  integral
     */
    public function flow_order_available_points($user_rank_info,$flow_type=0,$sel_cartgoods='')
    {

        $sql_plus="";
        if($sel_cartgoods)
        {
            $sql_plus .= " AND c.rec_id in (".$sel_cartgoods.")";
        }

        $sql_where = "c.user_id='". $user_rank_info['user_id'] ."' ";
        $sql = "SELECT SUM(g.integral * c.goods_number) as integral ".
            "FROM " . $this->_tb_cart . " AS c, " . $this->_tb_goods . " AS g " .
            "WHERE $sql_where  $sql_plus AND c.goods_id = g.goods_id AND c.is_gift = 0 AND g.integral > 0 " .
            "AND c.rec_type = '$flow_type'";

		//区分是积分商城还是积分抵扣商品
		if($flow_type==4){
			$sql = "SELECT e.exchange_integral FROM ".$this->_tb_exchange_goods." AS e, " . $this->_tb_cart . " AS c WHERE $sql_where  $sql_plus AND  e.goods_id = c.goods_id";
		}

        $integral = $this->_db->getOne($sql);

        $integral=integral_of_value(intval($integral));

        return $integral;
    }

    //en
    public function get_user_bouns_list($user_id, $suppid=-1,$is_used = 0)
    {
       /* $sql = "SELECT u.bonus_sn,u.supplier_id, u.order_id, b.type_name, b.type_money, b.min_goods_amount, b.use_start_date, b.use_end_date ".
               " FROM " .$GLOBALS['ecs']->table('user_bonus'). " AS u ,".
               $GLOBALS['ecs']->table('bonus_type'). " AS b".
               " WHERE u.bonus_type_id = b.type_id AND u.user_id = '" .$user_id. "'";*/
        $sql = "SELECT u.bonus_id,u.bonus_sn,u.supplier_id, u.order_id, b.type_name, b.type_money, b.min_goods_amount, b.use_start_date, b.use_end_date ".
               " FROM " .$GLOBALS['ecs']->table('user_bonus'). " AS u ,".
               $GLOBALS['ecs']->table('bonus_type'). " AS b".
               " WHERE u.bonus_type_id = b.type_id AND u.user_id = '" .$user_id. "'";

        if($suppid>-1){
            $sql .= " AND u.supplier_id=".intval($suppid);
        }

        $res = $GLOBALS['db']->getAll($sql);
        $arr = array();

        $day = getdate();
        $cur_date = local_mktime(23, 59, 59, $day['mon'], $day['mday'], $day['year']);

        foreach ($res as $row)
        {
            /* 先判断是否被使用，然后判断是否开始或过期 */
            if (empty($row['order_id']))
            {
                /* 没有被使用 */
                if ($row['use_start_date'] > $cur_date)
                {
                    $row['is_used'] = 1;//未使用 add to qinglin 2017.09.06
                    $row['status'] = '未开始';//$GLOBALS['_LANG']['not_start']
                }
                else if ($row['use_end_date'] < $cur_date)
                {
                    $row['is_used'] = 2;//过期/已使用 add to qinglin 2017.09.06
                    $row['status'] = '已过期';//$GLOBALS['_LANG']['overdue']
                }
                else
                {
                    $row['is_used'] = 1;//未使用 add to qinglin 2017.09.06
                    $row['status'] = '未使用';//$GLOBALS['_LANG']['not_use']
                }
            }
            else
            {
                $row['is_used'] = 2;//过期/已使用 add to qinglin 2017.09.06
                $row['status'] = '已使用';
            }

            if($row['supplier_id'] == '0')
            {
                $row['s_id'] = 0;
                $row['supplier_id'] = 0;
                $row['supplier_name'] = "自营商";
            }
            else
            {
                $supplierid = $row['supplier_id'];
                $sql = "SELECT * FROM ".$GLOBALS['ecs']->table('supplier_shop_config')."WHERE supplier_id = '$supplierid' AND code = 'shop_name'";
                $supp = $GLOBALS['db']->getRow($sql);
                $row['s_id'] = $supplierid;
                $row['supplier_id'] = $supplierid;
                $row['supplier_name'] = $supp['value'];
            }
            $row['use_startdate']   = local_date($GLOBALS['_CFG']['date_format'], $row['use_start_date']);
            $row['use_enddate']     = local_date($GLOBALS['_CFG']['date_format'], $row['use_end_date']);

            $arr[] = $row;
        }

        //是否已使用 0 为全部  1为未使用 2为已使用 add to qinglin 2017.09.06
        if($is_used){
            $arr2 = array();
            foreach ($arr as $k => $v) {
                if($v['is_used'] == $is_used){
                    $arr2[] = $arr[$k];
                }
            }
            $arr = '';
            $arr = $arr2;
        }

        return $arr;

    }

    public function insert_get_shop_shipping($arr, $user_id = 0, $sel_cart_goods = ''){

        //$order = $_SESSION['flow_order'];//获取订单信息

        $suppid = intval($arr['suppid']);
        $consignee = $arr['consignee'];
        $flow_type = $arr['flow_type'];
        $region            = array($consignee['country'], $consignee['province'], $consignee['city'], $consignee['district']);

        $shipping_list = available_shipping_list($region,$suppid);
        $cart_weight_price = $this->cart_weight_price2($flow_type,$suppid, $user_id, $sel_cart_goods);



        if(count($shipping_list)>0){
            //获取当前地址下所有的配送方式
            $shipping_id = array();
            foreach($shipping_list as $v){
                $shipping_id[] = $v['shipping_id'];
            }
            $i=0;
            $sql_where = "c.user_id='". $user_id ."' ";
            $sql_plus="";
            if($sel_cart_goods){
                $sql_plus .= " AND c.rec_id in (".$sel_cart_goods.") ";
            }
            $sql = 'SELECT count(*) FROM ' . $GLOBALS['ecs']->table('cart') . " AS c LEFT JOIN ".$GLOBALS['ecs']->table('goods')." AS g ON g.goods_id=c.goods_id WHERE $sql_where AND c.extension_code != 'package_buy' AND g.is_shipping = 0 ".$sql_plus; //jx
            $shipping_count = $GLOBALS['db']->getOne($sql);





            $order['shipping_pay'][$suppid] = 0;

            foreach($shipping_list as $key=>$val){
                // 判断如果为门店自提，那么收货人的所在地区有自提点则显示此配送方式，否则不显示此配送方式
                $shipping_code = $shipping_list[$key]['shipping_code'];

                if($shipping_code == 'pups'){
                    $shipping_list[$key]['shipping_type']=3;//门店自提
                }
                elseif($shipping_code == 'tc_express'){
                    $shipping_list[$key]['shipping_type']=2;//同城快递
                }
                else{
                    $shipping_list[$key]['shipping_type']=1;//普通快递
                }


                if($shipping_code == 'pups')
                {
                    $pickinfo = get_pickup_info(intval($consignee['city']), $suppid);

                    $shipping_list[$key]['shipping_ziti']=$pickinfo;



                    if(empty($pickinfo) || $pickinfo == false)
                    {
                        unset($shipping_list[$key]);
                        continue;
                    }
                }

                $shipping_cfg = unserialize_config($val['configure']);
                $shipping_fee = ($shipping_count == 0 && $cart_weight_price['free_shipping'] == 1) ? 0 : shipping_fee($val['shipping_code'], unserialize($val['configure']),
                $cart_weight_price['weight'], $cart_weight_price['amount'], $cart_weight_price['number']);





                $shipping_list[$key]['shipping_fee_formated'] = price_format($shipping_fee, false);
                $shipping_list[$key]['shipping_fee']        = $shipping_fee;
                // $shipping_list[$key]['free_money1']          = $shipping_cfg['free_money'];
                // $shipping_list[$key]['free_money']          = price_format($shipping_cfg['free_money'], false);
                // $shipping_list[$key]['insure_formated']     = strpos($val['insure'], '%') === false ?
                //     price_format($val['insure'], false) : $val['insure'];

                $selected = '';
                if($i==0 && !in_array($order['shipping_pay'][$suppid],$shipping_id)){
                    $selected = 'checked';
                    $order['shipping_pay'][$suppid] = $val['shipping_id'];//记录第一个被选中的配送方式的id
                }
                if(isset($order['shipping_pay'][$suppid]) && intval($order['shipping_pay'][$suppid]) == $val['shipping_id'] && in_array($order['shipping_pay'][$suppid],$shipping_id)){
                    $selected = 'checked';
                }
                $shipping_list[$key]['selected'] = $selected;

                unset($shipping_list[$key]['shipping_desc']);
                unset($shipping_list[$key]['configure']);
                unset($shipping_list[$key]['support_pickup']);
                unset($shipping_list[$key]['support_cod']);
                unset($shipping_list[$key]['insure']);


            }
        }


        foreach ($shipping_list as  $value) {
            $shipping_list0[]=$value;
        }





		return $shipping_list0?:array();
    }

}


