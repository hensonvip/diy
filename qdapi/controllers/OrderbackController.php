<?php
/**
 * Created by PhpStorm.
 * User: zxf0510
 * Date: 2018/7/18
 * Time: 10:58
 */

require(ROOT_PATH . 'languages/zh_cn/user.php');
require_once(ROOT_PATH . 'includes/cls_base.php');
require_once(ROOT_PATH . 'includes/cls_user.php');
require_once(ROOT_PATH . 'includes/cls_order.php');
require_once(ROOT_PATH . 'includes/cls_back_order.php');
require_once(ROOT_PATH . 'includes/cls_common.php');
require_once(ROOT_PATH . 'includes/lib_transaction.php');
require_once(ROOT_PATH . 'includes/lib_clips.php');
require_once(ROOT_PATH . 'includes/lib_validate_record.php');

$GLOBALS['_LANG'] = $_LANG;

class OrderbackController extends ApiController
{
    public function __construct()
    {
        parent::__construct();
        $this->data = $this->input();
        $this->user = cls_user::getInstance();
        $this->order = cls_order::getInstance();
        $this->back_order = cls_back_order::getInstance();
        $this->common = cls_common::getInstance();
        $config = array(
            'type' => 'file',
            'log_path' => ROOT_PATH . '/data/logs/api/user/'
        );

        $this->_tb_collect_goods = $GLOBALS['ecs']->table('collect_goods');

        $this->logger = new Logger($config);
        $this->user_id = isset($this->data['user_id']) ? $this->data['user_id'] : 0;
        $this->login_user_id = isset($this->data['login_user_id']) ? $this->data['login_user_id'] : 0;
        // if(empty($this->user_id) || !isset($this->user_id)){
        // $this->error("缺失必选参数 (user_id)，请参考API文档");
        // }

        $user_rank_info = $this->user->get_user_rank($this->user_id);
        if ($user_rank_info) {
            $this->user_rank_info = $user_rank_info;
        } else {
            $this->error("该会员数据不存在或者参数错误");
        }

        $this->device = isset($this->data['device']) ? $this->data['device'] : 'pc';//请求机型
    }

    /**
     * @description 生成我的退换货订单
     * @param integer user_id 用户ID
     * @param integer back_id 售后订单ID
     * @return array user_info
     */
    public function ToCreateUserBackOrder()
    {
        $order_id = !empty($this->data['order_id']) ? intval($this->data['order_id']) : 0;
        if ($order_id <= 0) {
            $this->error('参数错误！');
        }
        $goods_id = !empty($this->data['goods_id']) ? intval($this->data['goods_id']) : 0;
        $product_id = !empty($this->data['product_id']) ? intval($this->data['product_id']) : 0;
        $rec_id = !empty($this->data['rec_id']) ? intval($this->data['rec_id']) : 0;

        $result = $this->back_order->to_create_back_order($this->user_id, $order_id, $goods_id, $product_id, $rec_id);

        if ($result['code'] == 500) {
            $this->error($result['message']);
        } else {
            $this->success($result['data']);
        }
    }

    /**
     * @description 判断订单/订单商品是否可以退款
     * @param integer user_id 用户ID
     * @param integer order_id 订单ID
     * @param integer goods_id 商品ID
     * @param integer rec_id 订单商品表ID
     * @return array json
     */
    public function checkOrderGoodsBack()
    {
        $user_id = !empty($this->data['user_id']) ? intval($this->data['user_id']) : 0;
        if ($user_id <= 0) {
            $this->error('参数错误！');
        }
        $order_id = !empty($this->data['order_id']) ? intval($this->data['order_id']) : 0;
        if ($order_id <= 0) {
            $this->error('参数错误！');
        }

        $goods_id = !empty($this->data['goods_id']) ? intval($this->data['goods_id']) : 0;
        $rec_id = !empty($this->data['rec_id']) ? intval($this->data['rec_id']) : 0;
        //查找订单信息
        $sql_oi = "SELECT order_id,order_sn,order_status,shipping_status,pay_status,shipping_time_end,extension_code,(goods_amount +  insure_fee + pay_fee + pack_fee + card_fee + tax - discount) AS total_fee,shipping_fee FROM " . $GLOBALS['ecs']->table('order_info') . " WHERE user_id='$user_id' AND order_id = " . $order_id;
        $order_info = $GLOBALS['db']->getRow($sql_oi);
        if (empty($order_info)) {
            $this->error('未找到订单信息', '500');
        }
        //判断订单是否存在申请中状态的退款订单。
        $sql= "SELECT back_id FROM " . $GLOBALS['ecs']->table('back_order') . " WHERE order_id = " . $order_id . " AND user_id='$user_id' AND status_back = 5";
        $row=$GLOBALS['db']->getOne($sql);
        if (!empty($row)) {
            $this->error('对不起！该订单存在审核中的退款订单。请等客服审核后再进行申请操作。', '501');
        }
        //判断是否有整单退款退货
        $back_info_num = "SELECT COUNT(*) FROM " . $GLOBALS['ecs']->table('back_order') .
            " WHERE order_id = " . $order_id . " AND user_id='$user_id' AND goods_id=0 AND status_back < 6";
        if ($GLOBALS['db']->getOne($back_info_num) > 0) {
            $this->error('该订单已申请了整单退款/退货/换货服务!', '501');
        }

        $back_goods_number = 0;
        if ($goods_id > 0) {
            //判断单件商品是否有退款退货
            $back_info_num2 = "SELECT back_id FROM " . $GLOBALS['ecs']->table('back_order') .
                " WHERE order_id = " . $order_id . " AND user_id='$user_id' AND goods_id='$goods_id' AND product_id='$rec_id' AND status_back < 6";
            $back_list = $GLOBALS['db']->getAll($back_info_num2);
            if ($back_list) {
                $tmp = array();
                foreach ($back_list as $k => $back_id) {
                    $tmp[] = $back_id['back_id'];
                }
                $back_list = implode(',', $tmp);
                unset($tmp);
                $back_goods = " SELECT SUM(back_goods_number) FROM " . $GLOBALS['ecs']->table('back_goods') .
                    " WHERE goods_id='$goods_id' AND product_id='$rec_id' AND back_id in ($back_list)";
                $back_goods_number = $GLOBALS['db']->getOne($back_goods);
                //存在单件商品的订单，检查是否有多余的数量
                $order_goods = " SELECT goods_number FROM " . $GLOBALS['ecs']->table('order_goods') .
                    " WHERE order_id = " . $order_id . "  AND goods_id='$goods_id' AND rec_id='$rec_id'";
                $order_goods_number = $GLOBALS['db']->getOne($order_goods);
                if ($back_goods_number >= $order_goods_number) {
                    $this->error('该订单商品可申请退款/退货/换货数量小于1!', '502');
                }
            }
        } else {
            //判断订单是否有单件退款退货
            $back_info_num = "SELECT COUNT(*) FROM " . $GLOBALS['ecs']->table('back_order') .
                " WHERE order_id = " . $order_id . " AND user_id='$user_id' AND status_back < 6";
            if ($GLOBALS['db']->getOne($back_info_num) > 0) {
                $this->error('订单已有商品单独申请了退款/退货/换货服务!', '501');
            }
        }
        $this->success('');
    }


    /**
     * @description 获取退款的实时价格
     * @param integer user_id 用户ID
     * @param integer order_id 订单ID
     * @param integer goods_id 商品ID
     * @param integer rec_id 订单商品表ID
     * @param integer tui_goods_number 退款数量
     * @return array
     */
    public function countRefundMoney()
    {
        //目前只考虑退款或退货
        $user_id = intval($this->input('user_id', 0));
        $order_id = !empty($this->data['order_id']) ? intval($this->data['order_id']) : 0;
        $goods_id = !empty($this->data['goods_id']) ? intval($this->data['goods_id']) : 0;
        $rec_id = !empty($this->data['rec_id']) ? intval($this->data['rec_id']) : 0;
        $product_id = $rec_id;
        if ($order_id <= 0) {
            $this->error('对不起，您进行了错误操作！');
        }
        $sql_oi = "SELECT order_id,order_sn,supplier_id,order_status,shipping_status,pay_status,shipping_time_end,extension_code,(goods_amount +  insure_fee + pay_fee + pack_fee + card_fee + tax - discount - integral_money - bonus) AS total_fee,goods_amount,shipping_fee FROM " . $GLOBALS['ecs']->table('order_info') . " WHERE user_id='$user_id' AND order_id = " . $order_id;
        $order_info = $GLOBALS['db']->getRow($sql_oi);
        if (empty($order_info)) {
            $this->error('非法操作！');
        }
        //判断订单是否存在申请中状态的退款订单。
        $sql= "SELECT back_id FROM " . $GLOBALS['ecs']->table('back_order') . " WHERE order_id = " . $order_id . " AND user_id='$user_id' AND status_back = 5";
        $row=$GLOBALS['db']->getOne($sql);
        if (!empty($row)) {
            $this->error('对不起！该订单存在审核中的退款订单。请等客服审核后再进行申请操作。');
        }
        //判断是否有整单退款退货
        $back_info_num = "SELECT COUNT(*) FROM " . $GLOBALS['ecs']->table('back_order') .
            " WHERE order_id = " . $order_id . " AND user_id='$user_id' AND goods_id=0 AND status_back < 6";
        if ($GLOBALS['db']->getOne($back_info_num) > 0) {
            $this->error('对不起！该订单有整单退款退货,您无权操作！');
        }

        //判断单件商品是否有退款退货
        $back_goods_number = 0;
        $back_list = array();
        if ($goods_id > 0) {
            $back_info_num2 = "SELECT back_id FROM " . $GLOBALS['ecs']->table('back_order') .
                " WHERE order_id = " . $order_id . " AND user_id='$user_id' AND goods_id='$goods_id' AND product_id='$rec_id' AND status_back < 6";
            $back_list = $GLOBALS['db']->getAll($back_info_num2);
            if ($back_list) {
                $tmp = array();
                foreach ($back_list as $k => $back_id) {
                    $tmp[] = $back_id['back_id'];
                }
                $back_list = implode(',', $tmp);
                unset($tmp);
                $back_goods = " SELECT SUM(back_goods_number) FROM " . $GLOBALS['ecs']->table('back_goods') .
                    " WHERE goods_id='$goods_id' AND product_id='$rec_id' AND back_id in ($back_list)";
                $back_goods_number = $GLOBALS['db']->getOne($back_goods);
                //存在单件商品的订单，检查是否有多余的数量
                $order_goods = " SELECT goods_number FROM " . $GLOBALS['ecs']->table('order_goods') .
                    " WHERE order_id = " . $order_id . "  AND goods_id='$goods_id' AND rec_id='$rec_id'";
                $order_goods_number = $GLOBALS['db']->getOne($order_goods);
                if ($back_goods_number >= $order_goods_number) {
                    $this->error('对不起！该订单商品可退数量不足 1');
                }
            }
        }

        $where = "";
        if ($goods_id > 0) {
            $where = " AND og.goods_id=$goods_id AND og.rec_id=$product_id ";
        }
        $sql_og = "SELECT  og.goods_id, og.product_id,og.goods_sn,og.goods_name, og.goods_number, " .
            "og.goods_price, og.goods_attr, og.rec_id, " .
            "og.goods_price * og.goods_number AS subtotal,  og.order_id, og.extension_code  " .
            "FROM " . $GLOBALS['ecs']->table('order_goods') . "as og " .
            " WHERE og.order_id = '$order_id' $where";
        //$this->error($sql_og);
        $goods_list = $GLOBALS['db']->getAll($sql_og);

        if (empty($goods_list)) {
            $this->error('非法操作');
        }
        //判断该订单有几种商品(只有一种的话，则默认为整单。以上的则$goods_id>0为整单，或者为单件)
        $order_goods_num = $GLOBALS['db']->getOne("SELECT COUNT(*) FROM " . $GLOBALS['ecs']->table('order_goods') .
            " WHERE order_id = " . $order_id . " ");
        if ($order_goods_num > 0) {
            if ($goods_id > 0) {
                $order_all = 0;  //单件退款或退货
            } else {
                $order_all = 1;  //整单退款或退货
            }
        } else {
            $order_all = 1;
        }
        $data['order_sn'] = $order_info['order_sn'];
        $data['order_id'] = $order_info['order_id'];
        $data['user_id'] = $user_id;
        $data['status_back'] = 5;
        $data['supplier_id'] = $order_info['supplier_id'];
        $data['shipping_fee'] = $order_info['shipping_fee'];
        if ($order_all == 1) {
            $data['goods_id'] = 0;
            $data['product_id'] = 0;
            $data['goods_name'] = '';
            $data['refund_money_1'] = $order_info['total_fee'];
        } else {
            $tui_goods_number = !empty($this->data['tui_goods_number']) ? intval($this->data['tui_goods_number']) : 0; //退款数量
            if ($tui_goods_number <= 0) {
                $this->error('请填写您的退款数量');
            }
            if ($tui_goods_number > $goods_list[0]['goods_number']) {
                $this->error('您的退款数量不能大于' . $goods_list[0]['goods_number']);
            }
            if ($back_list) {
                if ($tui_goods_number > $back_goods_number) {
                    $this->error('您的退款数量不能大于可退款数量' . $back_goods_number);
                }
            }
            $data['goods_id'] = $goods_id;
            $data['product_id'] = $goods_list[0]['rec_id'];
            $data['goods_name'] = $goods_list[0]['goods_name'];
            $data['refund_money_1'] = $tui_goods_number * $goods_list[0]['goods_price'];
        }
        //优惠折扣
        $sql = "SELECT u.bonus_id,u.bonus_sn,u.supplier_id, u.order_id, b.type_name, b.type_money, b.min_goods_amount, b.use_start_date, b.use_end_date " .
            " FROM " . $GLOBALS['ecs']->table('user_bonus') . " AS u ," .
            $GLOBALS['ecs']->table('bonus_type') . " AS b" .
            " WHERE u.bonus_type_id = b.type_id AND u.user_id = '" . $user_id . "' AND u.order_id='" . $order_id . "' ";
        $bonus = $GLOBALS['db']->getRow($sql);
        //算出该订单可退总金额
        $sql = " SELECT SUM(refund_money_1) FROM " . $GLOBALS['ecs']->table('back_order') . " WHERE user_id = {$user_id} AND order_id = {$order_id} AND status_back < 5 AND back_type  in (1,4) ";
        $apply_back_order_money = $GLOBALS['db']->getOne($sql);//已经在申请退款的金额
        $can_back_order_money = $order_info['goods_amount'] - $apply_back_order_money;//订单的商品总退款-已经申请的退款=剩下可退的
        if ($bonus) {
            if ($order_all == 0)  {
                if ($bonus['min_goods_amount'] > $can_back_order_money - $data['refund_money_1'])//剩下可退的-当前申请的=成功之后的剩下的
                {
                    $data['refund_money_1'] -= $bonus['type_money'];
                }
            }
        }
        $this->success($data);
    }

    //提交 退货/退款
    public function apply_back_order()
    {
        //目前只考虑退款或退货
        $user_id = intval($this->input('user_id', 0));
        $order_id = !empty($this->data['order_id']) ? intval($this->data['order_id']) : 0;
        $back_type = !empty($this->data['back_type']) ? intval($this->data['back_type']) : 0;// 4 退款 3 返修 2 换货 1 退货

        $back_pay = ($back_type == 1 || $back_type == 4) ? 2 : 0;//默认退款原路返回

        $goods_id = !empty($this->data['goods_id']) ? intval($this->data['goods_id']) : 0;
        $product_id = !empty($this->data['product_id']) ? intval($this->data['product_id']) : 0;
        $reason_id = !empty($this->data['reason_id']) ? intval($this->data['reason_id']) : 0; //退款原因
        $rec_id = !empty($this->data['rec_id']) ? intval($this->data['rec_id']) : 0;
        $product_id = $rec_id;
        $back_postscript = !empty($this->data['back_postscript']) ? $this->data['back_postscript'] : ''; //留言
        $this->data['back_imgs'] = $this->data['files'];
        $back_imgs = !empty($this->data['back_imgs']) ? $this->data['back_imgs'] : array();//图片
        if ($back_imgs) {
            if (count($back_imgs['name']) > 6) {
                //$this->error('图片数量不能大于6张！');
            }
        }
        if ($order_id <= 0) {
            $this->error('对不起，您进行了错误操作！');
        }
        if ($back_type <= 0) {
            $this->error('对不起，您还没选择您的退款类型！');
        }
        $back_reason = "";
        if (in_array($back_type, array(1, 4))) {
            if ($reason_id <= 0) {
                $this->error('请选择退款原因！');
            }
            $back_reason = $GLOBALS['db']->getOne("SELECT reason_name FROM " . $GLOBALS['ecs']->table('reason') . " WHERE reason_id = " . $reason_id);
            if (empty($back_reason)) {
                $this->error('您选择退款原因不存在，请重新选择！');
            }
        }
        $sql_oi = "SELECT order_id,order_sn,supplier_id,order_status,shipping_status,pay_status,shipping_time_end,extension_code,(goods_amount +  insure_fee + pay_fee + pack_fee + card_fee + tax - discount - integral_money - bonus) AS total_fee,goods_amount,shipping_fee FROM " . $GLOBALS['ecs']->table('order_info') . " WHERE user_id='$user_id' AND order_id = " . $order_id;
        $order_info = $GLOBALS['db']->getRow($sql_oi);
        if (empty($order_info)) {
            $this->error('非法操作！');
        }
        //判断订单是否存在申请中状态的退款订单。
        $sql= "SELECT back_id FROM " . $GLOBALS['ecs']->table('back_order') . " WHERE order_id = " . $order_id . " AND user_id='$user_id' AND status_back = 5";
        $row=$GLOBALS['db']->getOne($sql);
        if (!empty($row)) {
            $this->error('对不起！该订单存在审核中的退款订单。请等客服审核后再进行申请操作。');
        }
        //判断是否有整单退款退货
        $back_info_num = "SELECT COUNT(*) FROM " . $GLOBALS['ecs']->table('back_order') .
            " WHERE order_id = " . $order_id . " AND user_id='$user_id' AND goods_id=0 AND status_back < 6";
        if ($GLOBALS['db']->getOne($back_info_num) > 0) {
            $this->error('对不起！您没权限操作该订单');
        }

        //判断单件商品是否有退款退货
        /*        if($goods_id>0){
                    $back_info_num2 = "SELECT COUNT(*) FROM " . $GLOBALS['ecs']->table('back_order') .
                        " WHERE order_id = " . $order_id . " AND user_id='$user_id' AND goods_id='$goods_id' AND product_id='$product_id' AND status_back < 6";
                    if ($GLOBALS['db']->getOne($back_info_num2) > 0)
                    {
                        $this->error('对不起！您没权限操作该订单');
                    }
                }*/
        $back_goods_number = 0;
        $back_list = array();
        if ($goods_id > 0) {
            $back_info_num2 = "SELECT back_id FROM " . $GLOBALS['ecs']->table('back_order') .
                " WHERE order_id = " . $order_id . " AND user_id='$user_id' AND goods_id='$goods_id' AND product_id='$rec_id' AND status_back < 6";
            $back_list = $GLOBALS['db']->getAll($back_info_num2);
            if ($back_list) {
                $tmp = array();
                foreach ($back_list as $k => $back_id) {
                    $tmp[] = $back_id['back_id'];
                }
                $back_list = implode(',', $tmp);
                unset($tmp);
                $back_goods = " SELECT SUM(back_goods_number) FROM " . $GLOBALS['ecs']->table('back_goods') .
                    " WHERE goods_id='$goods_id' AND product_id='$rec_id' AND back_id in ($back_list)";
                $back_goods_number = $GLOBALS['db']->getOne($back_goods);
                //存在单件商品的订单，检查是否有多余的数量
                $order_goods = " SELECT goods_number FROM " . $GLOBALS['ecs']->table('order_goods') .
                    " WHERE order_id = " . $order_id . "  AND goods_id='$goods_id' AND rec_id='$rec_id'";
                $order_goods_number = $GLOBALS['db']->getOne($order_goods);
                if ($back_goods_number >= $order_goods_number) {
                    $this->error('对不起！您没权限操作该订单2');
                }
            }
        }
        $min_time = local_strtotime(local_date('Y-m-d H:i:s', strtotime('-' . $GLOBALS['_CFG']['shouhou_time'] . ' days')));//则自确认收货起$GLOBALS['_CFG']['comment_youxiaoqi']天内买家可以申请售后
        //$min_time = local_strtotime(local_date('Y-m-d H:i:s', strtotime('-7 days')));//则自确认收货起$GLOBALS['_CFG']['comment_youxiaoqi']天内买家可以申请售后   默认7天，还没做成后台设置该值

        //服务类型[服务商品只能退款]
        //仅退款【未收到货（包含未签收），或卖家协商同意前提下】
        //退款退货【已收到货需要退货已收到的货物】
        if (in_array($order_info['order_status'], array(1, 2, 5)) && in_array($order_info['shipping_status'], array(0, 3, 5)) && $order_info['pay_status'] == 2) {
            if (!in_array($back_type, array(4))) {
                $this->error('对不起，你该订单只能申请退款');
            }
        } elseif (in_array($order_info['order_status'], array(2, 5)) && $order_info['shipping_status'] == 1 && $order_info['pay_status'] == 2 && $order_info['extension_code'] != 'virtual_good') {

            if (!in_array($back_type, array(1, 2, 4))) {
                $this->error('对不起，你该订单只能申请退款,退款退货,换货类型');
            }
        } elseif ($order_info['order_status'] == 5 && $order_info['shipping_status'] == 2 && $order_info['pay_status'] == 2 && $order_info['shipping_time_end'] > $min_time) {
            if ($order_info['extension_code'] == 'virtual_good') {
                $this->error('非法操作');
            }
            if ($back_type != 1) {
                $this->error('对不起，你该订单已收到货，只能申请退款退货类型');
            }
        } else {
            $this->error('非法操作');
        }

        $where = "";
        if ($goods_id > 0) {
            //$where=" AND og.goods_id=$goods_id AND og.product_id=$product_id";
            $where = " AND og.goods_id=$goods_id AND og.rec_id=$product_id ";
        }
        $sql_og = "SELECT  og.goods_id, og.product_id,og.goods_sn,og.goods_name, og.goods_number, " .
            "og.goods_price, og.goods_attr, og.rec_id, " .
            "og.goods_price * og.goods_number AS subtotal,  og.order_id, og.extension_code  " .
            "FROM " . $GLOBALS['ecs']->table('order_goods') . "as og " .
            " WHERE og.order_id = '$order_id' $where";
        //$this->error($sql_og);
        $goods_list = $GLOBALS['db']->getAll($sql_og);

        if (empty($goods_list)) {
            $this->error('非法操作');
        }
        //判断该订单有几种商品(只有一种的话，则默认为整单。以上的则$goods_id>0为整单，或者为单件)
        $order_goods_num = $GLOBALS['db']->getOne("SELECT COUNT(*) FROM " . $GLOBALS['ecs']->table('order_goods') .
            " WHERE order_id = " . $order_id . " ");
        if ($order_goods_num > 0) {
            if ($goods_id > 0) {
                $order_all = 0;  //单件退款或退货
            } else {
                $order_all = 1;  //整单退款或退货
            }
        } else {
            $order_all = 1;
        }

        $add_time = gmtime();
        $data['type'] = ($order_info['extension_code'] == 'virtual_good') ? 1 : 0;
        $data['order_sn'] = $order_info['order_sn'];
        $data['order_id'] = $order_info['order_id'];
        $data['user_id'] = $user_id;
        $data['add_time'] = $add_time;
        $data['postscript'] = $back_postscript;
        $data['back_reason'] = $back_reason;
        $data['back_type'] = $back_type;
        $data['status_back'] = 5;
        $data['supplier_id'] = $order_info['supplier_id'];
        $data['shipping_fee'] = $order_info['shipping_fee'];
        $data['back_pay'] = $back_pay;
        if ($order_all == 1) {
            $data['goods_id'] = 0;
            $data['product_id'] = 0;
            $data['goods_name'] = '';
            $data['refund_money_1'] = $order_info['total_fee'];
        } else {
            $tui_goods_number = !empty($this->data['tui_goods_number']) ? intval($this->data['tui_goods_number']) : 0; //退款数量
            if ($tui_goods_number <= 0) {
                $this->error('请填写您的退款数量');
            }
            if ($tui_goods_number > $goods_list[0]['goods_number']) {
                $this->error('您的退款数量不能大于' . $goods_list[0]['goods_number']);
            }
            if ($back_list) {
                if ($tui_goods_number > $back_goods_number) {
                    $this->error('您的退款数量不能大于可退款数量' . $back_goods_number);
                }
            }
            $data['goods_id'] = $goods_id;
            //$data['product_id']=$goods_list[0]['product_id'];
            $data['product_id'] = $goods_list[0]['rec_id'];
            $data['goods_name'] = $goods_list[0]['goods_name'];
            $data['refund_money_1'] = $tui_goods_number * $goods_list[0]['goods_price'];
        }

        //优惠折扣
        $sql = "SELECT u.bonus_id,u.bonus_sn,u.supplier_id, u.order_id, b.type_name, b.type_money, b.min_goods_amount, b.use_start_date, b.use_end_date " .
            " FROM " . $GLOBALS['ecs']->table('user_bonus') . " AS u ," .
            $GLOBALS['ecs']->table('bonus_type') . " AS b" .
            " WHERE u.bonus_type_id = b.type_id AND u.user_id = '" . $user_id . "' AND u.order_id='" . $order_id . "' ";
        $bonus = $GLOBALS['db']->getRow($sql);
        //算出该订单可退总金额
        $sql = " SELECT SUM(refund_money_1) FROM " . $GLOBALS['ecs']->table('back_order') . " WHERE user_id = {$user_id} AND order_id = {$order_id} AND status_back < 5 AND back_type  in (1,4) ";
        $apply_back_order_money = $GLOBALS['db']->getOne($sql);//已经在申请退款的金额
        $can_back_order_money = $order_info['goods_amount'] - $apply_back_order_money;//订单的商品总退款-已经申请的退款=剩下可退的
        $is_replay=false;
        if ($bonus) {
            if ($order_all == 0)  {
                if ($bonus['min_goods_amount'] > $can_back_order_money - $data['refund_money_1'])//剩下可退的-当前申请的=成功之后的剩下的
                {
                    $data['refund_money_1'] -= $bonus['type_money'];
                    $is_replay=true;
                }
            }
        }

        //凭证图
        $upload_img = array();
        // 处理图片
        //$this->success($back_imgs);
        if ($back_imgs) {
            include_once(ROOT_PATH . '/includes/cls_image.php');
            $image = new cls_image($GLOBALS['_CFG']['bgcolor']);

            $img_path = 'refund/' . date('Ym');
            if (count($back_imgs['name']) == 1) {
                //单张
                $original = $image->upload_image($back_imgs, $img_path);
                $upload_img[] = $original;
            } else {
                //多张
                for ($i = 0; $i <= count($back_imgs['name']); $i++) {
                    if (empty($back_imgs['tmp_name'][$i])) {
                        break;
                    }
                    $img = array('name' => $back_imgs['name'][$i], 'type' => $back_imgs['type'][$i], 'tmp_name' => $back_imgs['tmp_name'][$i]);
                    $original = $image->upload_image($img, $img_path);
                    $upload_img[] = $original;
                }
                foreach ($back_imgs as $k => $v) {
                    $img = array('name' => $v['filename'], 'type' => $v['type'], 'tmp_name' => ROOT_PATH . $v['path']);
                    $original = $image->upload_image($img, $img_path);
                    $upload_img[] = $original;
                }
            }

            //删除临时文件
            $files = glob(ROOT_PATH . 'runtime/temp/*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    @unlink($file);
                }
            }
        }

        if (!empty($upload_img)) {
            foreach ($upload_img as $key => $value) {
                $upload_img[$key] = '/' . $value;
            }
            $data['imgs'] = implode(',', $upload_img);
        }
        //$this->success($data);
        $GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('back_order'), $data, 'INSERT');
        // 插入退换货商品 80_back_goods
        $back_id = $GLOBALS['db']->insert_id();
        $have_tuikuan = 0; // 是否有退货
        // foreach($back_type_list as $back_type)
        // {
        if ($back_type == 1)//退货
        {
            /*$have_tuikuan = 1;
            $tui_goods_number = $_REQUEST['tui_goods_number'] ? intval($_REQUEST['tui_goods_number']) : 1;
            $sql = "insert into " . $GLOBALS['ecs']->table('back_goods') . "(back_id, goods_id, goods_name, goods_sn, product_id, goods_attr, back_type, " . "back_goods_number, back_goods_price, status_back ) " . " values('$back_id', '$goods_id', '$goods_name', '$goods_sn', '$_REQUEST[product_id_tui]', '$_REQUEST[goods_attr_tui]', '0', " . " '$tui_goods_number', '$_REQUEST[tui_goods_price]', '5') ";
            $GLOBALS['db']->query($sql);*/
        }
        if ($back_type == 4)//退款
        {
            /*$have_tuikuan = 1;
            $have_tuikuan2 = 1;
            $price_refund_all = 0;

            foreach($order_info['goods_list'] as $goods_info)
            {
                $price_refund_all += ($goods_info['goods_price'] * $goods_info['goods_number']);

                $sql = "INSERT INTO " . $GLOBALS['ecs']->table('back_goods') . "(back_id, goods_id, goods_name, goods_sn, product_id, goods_attr, back_type, " . "back_goods_number, back_goods_price, status_back) " . " values('$back_id', '".$goods_info['goods_id']."', '".$goods_info['goods_name']."', '".$goods_info['goods_sn']."', '".$goods_info['product_id']."', '".$goods_info['goods_attr']."', '4', '".$goods_info['goods_number']."', '".$goods_info['goods_price']."', '5') ";
                $GLOBALS['db']->query($sql);
            }*/
        }
        if ($back_type == 1 || $back_type == 4 || $back_type == 2) { //退款或退货 or 换货
            $have_tuikuan = 1;
            foreach ($goods_list as $key2 => $value2) {
                $data2['back_id'] = $back_id;
                $data2['goods_id'] = $value2['goods_id'];
                $data2['goods_name'] = $value2['goods_name'];
                $data2['goods_sn'] = $value2['goods_sn'];
                $data2['product_id'] = $value2['rec_id'];
                $data2['goods_attr'] = $value2['goods_attr'];
                $data2['back_type'] = $back_type;
                $data2['back_goods_number'] = $order_all ? $value2['goods_number'] : $tui_goods_number;
                $data2['back_goods_price'] = $order_all ? $value2['subtotal'] : $tui_goods_number * $value2['goods_price'];
                $data2['status_back'] = 5;
                $GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('back_goods'), $data2, 'INSERT');
            }
        }
        if ($back_type == 2)//换货
        {/*
			$huan_count = count($_POST['product_id_huan']);
			if($huan_count)
			{
				$sql = "insert into " . $GLOBALS['ecs']->table('back_goods') . "(back_id, goods_id, goods_name, goods_sn, product_id, goods_attr, back_type, status_refund, back_goods_number, status_back) " . " values('$back_id', '$goods_id', '$goods_name', '$goods_sn', '$_REQUEST[product_id_tui]', '$_REQUEST[goods_attr_tui]', '1', '9', '$huan_count', '5') ";
				$GLOBALS['db']->query($sql);
				$parent_id_huan = $GLOBALS['db']->insert_id();
				foreach($_POST['product_id_huan'] as $pid_key => $pid_huan)
				{
					$sql = "insert into " . $GLOBALS['ecs']->table('back_goods') . "(back_id, goods_id, goods_name, goods_sn, product_id, goods_attr,  back_type, parent_id, status_refund, back_goods_number, status_back) " . "values('$back_id', '$goods_id', '$goods_name', '$goods_sn',  '$pid_huan', '" . $_POST['goods_attr_huan'][$pid_key] . "', '2', '$parent_id_huan', '9', '1', '5')";
					$GLOBALS['db']->query($sql);
				}
			}*/
        }
        if ($back_type == 3) //维修
        {
            /*$have_weixiu = 1;
            $tui_goods_number = $_REQUEST['tui_goods_number'] ? intval($_REQUEST['tui_goods_number']) : 1;
            $sql = "insert into " . $GLOBALS['ecs']->table('back_goods') . "(back_id, goods_id, goods_name, goods_sn, product_id, goods_attr, back_type, " . "back_goods_number, back_goods_price, status_back) " . " values('$back_id', '$goods_id', '$goods_name', '$goods_sn', '$_REQUEST[product_id_tui]', '$_REQUEST[goods_attr_tui]', '3', " . " '$tui_goods_number', '$_REQUEST[tui_goods_price]', '5') ";
            $GLOBALS['db']->query($sql);*/
        }
        // }
        //是否插入留言
        if ($is_replay){
            $replay_data=array(
                'back_id'=>$back_id,
                'message'=>"因退款，订单剩下的商品价格不满足优惠条件。退款时将扣除优惠金额 ".$bonus['type_money']." 元。如不同意请及时取消申请。",
                'add_time'=>$add_time
            );
            $GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('back_replay'), $replay_data, 'INSERT');
        }
        /* 更新back_order */
        if ($have_tuikuan) {
            /*if ($_POST['order_all'])
            {
                $price_refund = $GLOBALS['db']->getOne("SELECT money_paid FROM " . $GLOBALS['ecs']->table('order_info') . " WHERE order_id = " . $order_id);
            }
            else
            {
                $price_refund = $_REQUEST['tui_goods_price'] * $tui_goods_number;
            }
            $sql = "update " . $GLOBALS['ecs']->table('back_order') . " set refund_money_1= '$price_refund' where back_id='$back_id' ";
            $GLOBALS['db']->query($sql);*/
        } else {
            $sql = "update " . $GLOBALS['ecs']->table('back_order') . " set status_refund= '9' where back_id='$back_id' ";
            $GLOBALS['db']->query($sql);
        }
        $this->success(array('back_id' => $back_id));

    }

    //退款退货列表
    public function refundList()
    {
        $user_id = $this->user_id;
        $page = !empty($this->data['page']) ? intval($this->data['page']) : 1;
        $page_size = !empty($this->data['page_size']) ? intval($this->data['page_size']) : 10;
        $status = !empty($this->data['status']) ? intval($this->data['status']) : 0;
        $type = isset($this->data['type']) ? $this->data['type'] : '';//0（虚拟商品）、1（真实商品）

        $page_start = $page_size * ($page - 1);

        $sql_w = '';
        if ($type != '') {
            $sql_w .= " AND type = '$type' ";
        }
        switch ($status) {
            case 1:
                $sql_w .= ' AND status_back = 5  ';
                break;
            case 2:
                $sql_w .= ' AND status_back = 3 AND status_refund = 1 ';
                break;
        }
        /* 取得订单列表 */
        $arr = array();

        $sql = "SELECT * " . " FROM " . $GLOBALS['ecs']->table('back_order') . " WHERE user_id = '$user_id' " . $sql_w . " ORDER BY add_time DESC";
        $res = $GLOBALS['db']->SelectLimit($sql, $page_size, $page_start);

        //1为退货 2为换货 3为申请返修 4为退款（无需退货）
        $sttus_type = array(1 => '退货', 2 => '换货', 3 => '申请返修', 4 => '退款（无需退货）');

        while ($row = $GLOBALS['db']->fetchRow($res)) {
            $row0['back_id'] = $row['back_id'];
            $row0['back_sn'] = $row['order_sn'] . $row['back_id'];//退款单号
            $row0['order_sn'] = $row['order_sn'];
            $row0['refund_time'] = local_date($GLOBALS['_CFG']['time_format'], $row['add_time']);
            $row0['refund_status'] = $sttus_type[$row['back_type']];

            $row0['status'] = $row['status_back'];
            //0:审核通过,1:收到寄回商品,2:换回商品已寄出,3:完成退货/返修,4:退款(无需退货),5:审核中,6:申请被拒绝,7:管理员取消,8:用户自己取消
            switch ($row['status_back']) {
                case '0':
                    $row0['status_back'] = '审核通过';
                    break;
                case '1':
                    $row0['status_back'] = '收到商品';
                    break;
                case '2':
                    $row0['status_back'] = '商品已寄出';
                    break;
                case '3':
                    $row0['status_back'] = '完成退货';
                    break;
                case '4':
                    $row0['status_back'] = '退款(无需退货)';
                    break;
                case '5':
                    $row0['status_back'] = '审核中';
                    break;
                case '6':
                    $row0['status_back'] = '申请被拒绝';
                    break;
                case '7':
                    $row0['status_back'] = '管理员取消';
                    break;
                case '8':
                    $row0['status_back'] = '取消';
                    break;
                default:
                    $row0['status_back'] = '取消';
                    break;
            }
            //$row0['status_back'] = $GLOBALS['_LANG']['bps'][$row['status_refund']];

            $sql_goods = "SELECT bg.goods_id,bg.goods_name,bg.back_goods_price,bg.back_goods_number,bg.status_refund,bg.goods_attr ,g.goods_thumb FROM " . $GLOBALS['ecs']->table('back_goods') . " as bg left join " . $GLOBALS['ecs']->table('goods') . " AS g " . " on bg.goods_id=g.goods_id  " . " WHERE back_id = " . $row['back_id'];
            $row0['goods_list'] = $GLOBALS['db']->getAll($sql_goods);

            foreach ($row0['goods_list'] as $key => $value) {
                $row0['goods_list'][$key]['goods_attr'] = preg_replace("/\[.*\]/", '', $value['goods_attr']);//属性处理，去掉中括号及里面的内容。如：颜色:粉色[798] 尺码:S[798] 变为 颜色:粉色 尺码:S
                $row0['goods_list'][$key]['format_back_goods_price'] = price_format($value['back_goods_price'], false);
            }

            $arr[] = $row0;
        }

        $count = $GLOBALS['db']->getOne("SELECT COUNT(*) FROM " . $GLOBALS['ecs']->table('back_order') . " WHERE user_id = '$user_id' " . $sql_w . " ");
        //分页
        $pager = array();
        $pager['page'] = $page;
        $pager['page_size'] = $page_size;
        $pager['record_count'] = $count;
        $pager['page_count'] = $page_count = ($count > 0) ? intval(ceil($count / $page_size)) : 1;

        $refund_data['list'] = $arr;
        $refund_data['pager'] = $pager;
        $this->success($refund_data);
    }

    //退款退货 详情
    public function refundDetails()
    {

        $_LANG = $GLOBALS['_LANG'];
        $db = $GLOBALS['db'];
        $ecs = $GLOBALS['ecs'];
        $user_id = $this->user_id;
        $back_id = !empty($_REQUEST['back_id']) ? intval($_REQUEST['back_id']) : 0;

        $sql = "SELECT * " . " FROM " . $GLOBALS['ecs']->table('back_order') . " WHERE user_id = '$user_id' and  back_id= '$back_id' ";
        $back_shipping = $db->getRow($sql);
        if (empty($back_shipping)) {
            $this->error("非法操作");
        }

        // 退货商品 + 换货商品 详细信息
        $list_backgoods = array();
        $list_backgoods2 = array();
        $sql = "select bg.*,g.goods_thumb,g.goods_img,g.original_img,g.market_price from " . $GLOBALS['ecs']->table('back_goods') . " as bg left join " . $GLOBALS['ecs']->table('goods') . " AS g " . " on bg.goods_id=g.goods_id  " . " where back_id = '$back_id' order by back_type ";

        $res_backgoods = $db->query($sql);
        while ($row_backgoods = $db->fetchRow($res_backgoods)) {

            $list_backgoods['goods_id'] = $row_backgoods['goods_id'];
            $list_backgoods['goods_name'] = $row_backgoods['goods_name'];
            $row_backgoods['goods_attr'] = preg_replace("/\[.*\]/", '', $row_backgoods['goods_attr']);//属性处理，去掉中括号及里面的内容。如：颜色:粉色[798] 尺码:S[798] 变为 颜色:粉色 尺码:S
            $list_backgoods['goods_attr'] = $row_backgoods['goods_attr'];
            $list_backgoods['back_goods_number'] = $row_backgoods['back_goods_number'];
            $list_backgoods['back_goods_price'] = $row_backgoods['back_goods_price'];
            $list_backgoods['format_back_goods_price'] = price_format($row_backgoods['back_goods_price'], false);
            $list_backgoods['status_refund'] = $_LANG['bps'][$row_backgoods['status_refund']];
            $list_backgoods['goods_thumb'] = $row_backgoods['goods_thumb'];
            $list_backgoods['market_price'] = $row_backgoods['market_price'];
            //规格显示
            $properties = get_goods_properties($row_backgoods['goods_id']);
            $spec = array();
            $spec_list = explode(' ', trim($row_backgoods['goods_attr']));
            foreach ($spec_list as $ke => $va) {
                $spec_id = explode(':', trim($va));
                foreach ($properties['spe'] as $k => $v) {
                    if ($v['name'] == "款式") {
                        foreach ($v['values'] as $k1 => $v1) {
                            if ($v1['label'] == $spec_id[1]) {
                                $spec[0] = $v1;
                            }
                        }
                    }
                    if ($v['name'] == "颜色") {
                        foreach ($v['values'] as $k1 => $v1) {
                            if ($v1['label'] == $spec_id[1]) {
                                $spec[1] = $v1;
                            }
                        }
                    }
                    if ($v['name'] == "尺码") {
                        foreach ($v['values'] as $k1 => $v1) {
                            if ($v1['label'] == $spec_id[1]) {
                                $spec[2] = $v1;
                            }
                        }
                    }
                }
            }
            $list_backgoods['spec'] = $spec;

            $list_backgoods2[] = $list_backgoods;
        }

        /* 回复留言 增加 */
        $res = $db->getAll("SELECT * FROM " . $GLOBALS['ecs']->table('back_replay') . " WHERE back_id = '$back_id' ORDER BY add_time ASC");
        $back_replay = array();
        foreach ($res as $value) {
            $value['add_time'] = local_date("Y-m-d H:i", $value['add_time']);
            $back_replay[] = $value;
        }

        $sttus_type = array(1 => '退货', 2 => '换货', 3 => '申请返修', 4 => '退款（无需退货）');
        $orderInfo = $db->getRow("SELECT *,(goods_amount + shipping_fee + insure_fee + pay_fee + pack_fee + card_fee + tax - discount) as total FROM  " . $GLOBALS['ecs']->table('order_info') . " where order_id = " . $back_shipping['order_id']);

        $result = array();
        $result['back_sn'] = $orderInfo['order_sn'] . $back_id;//退款单号
        $result['order_sn'] = $orderInfo['order_sn'];//订单号
        $result['back_status'] = $_LANG['bos'][$back_shipping['status_back']];
        $result['status_back'] = $back_shipping['status_back'];
        $result['refund_time'] = local_date("Y-m-d H:i", $back_shipping['add_time']);//退款时间
        $result['add_time'] = local_date("Y-m-d H:i", $orderInfo['add_time']);//下单时间
        $result['goods_list'] = $list_backgoods2;//商品列表
        $result['refund_type'] = $sttus_type[$back_shipping['back_type']];
        $result['order_amount'] = $orderInfo['total'];//下单金额
        $result['format_order_amount'] = price_format($orderInfo['total'], false);//下单金额
        $result['refund_money_1'] = $back_shipping['refund_money_1'];//预计退款金额
        $result['format_refund_money_1'] = price_format($back_shipping['refund_money_1'], false);//预计退款金额格式化
        $result['refund_amount'] = $back_shipping['refund_money_2'];//退款金额格式化
        $result['format_refund_amount'] = price_format($back_shipping['refund_money_2'], false);//退款金额格式化
        $result['refund_reason'] = $back_shipping['back_reason'];
        $result['back_replay'] = $back_replay;
        $result['postscript'] = $back_shipping['postscript'];//备注
        $result['shipping_name'] = $back_shipping['shipping_name'];//寄回快递公司
        $result['invoice_no'] = $back_shipping['invoice_no'];//寄回快递订单
        $result['back_shipping_name'] = $back_shipping['back_shipping_name'];//平台换回商品快递公司
        $result['back_invoice_no'] = $back_shipping['back_invoice_no'];//平台换回商品快递订单
        if (!empty($back_shipping['imgs'])) {
            $result['imgs'] = explode(',', $back_shipping['imgs']);//图片
        } else {
            $result['imgs'] = array();
        }
        $this->success($result);
    }

    /**
     * @description 取消退款退货申请
     * @param integer user_id 用户ID
     * @return void
     */
    public function cancelUserRefund()
    {

        $back_id = !empty($this->data['back_id']) ? intval($this->data['back_id']) : 0;

        if ($back_id <= 0) {
            $this->error('参数错误！');
        }

        $back_order = $GLOBALS['db']->getRow("select status_back,back_id from " . $GLOBALS['ecs']->table('back_order') . " where back_id='$back_id' ");
        if (empty($back_order)) {
            $this->error('非法操作！');
        }

        if ($back_order['status_back'] != 0 && $back_order['status_back'] != 5) {
            $this->error('对不起，该退货单无法取消申请！');
        } else {
            $GLOBALS['db']->query("update " . $GLOBALS['ecs']->table('back_goods') . " set status_back = 8 where back_id='$back_id' ");
            $GLOBALS['db']->query("update " . $GLOBALS['ecs']->table('back_order') . " set status_back = 8 where back_id='$back_id' ");
            $this->success('恭喜，您已经成功取消该退货单');
        }

    }

    /**
     * @description 退货申请寄回商品物流信息提交
     * @param integer user_id 用户ID
     * @return void
     */
    public function UserRefundLogistics()
    {


        $back_id = !empty($this->data['back_id']) ? intval($this->data['back_id']) : 0;
        $shipping_name = !empty($this->data['shipping_name']) ? trim($this->data['shipping_name']) : '';
        $invoice_no = !empty($this->data['invoice_no']) ? trim($this->data['invoice_no']) : '';

        if ($back_id <= 0) {
            $this->error('非法操作！');
        }
        if ($invoice_no == '') {
            $this->error('请填写快递单号！');
        }
        if ($shipping_name == '') {
            $this->error('请填写快递公司！');
        }

        $back_order = $GLOBALS['db']->getRow("select status_back,back_id,back_type from " . $GLOBALS['ecs']->table('back_order') . " where back_id='$back_id' ");
        if (empty($back_order)) {
            $this->error('非法操作！');
        }

        if ($back_order['back_type'] != 1 && $back_order['status_back'] != 0) {
            $this->error('非法操作！');
        } elseif ($back_order['status_back'] != 0) {
            $this->error('非法操作！');
        } else {
            $GLOBALS['db']->query("update " . $GLOBALS['ecs']->table('back_order') . " set shipping_name='$shipping_name', invoice_no='$invoice_no' where back_id='$back_id' ");
            $this->success('提交成功！');
        }

    }

    //获取寄回快递公司信息
    public function getShippingList()
    {
        $display1 = array('pups');//门店自提
        $display2 = array('tc_express');//同城快递
        $modules = read_modules_re('../includes/modules/shipping');
        $list = array();
        for ($i = 0; $i < count($modules); $i++) {
            if (!(in_array($modules[$i]['code'], $display1) || in_array($modules[$i]['code'], $display2))) {
                /* 检查该插件是否已经安装 */
                $sql = "SELECT shipping_id,shipping_name,shipping_code FROM " . $GLOBALS['ecs']->table('shipping') . " WHERE shipping_code='" . $modules[$i]['code'] . "' and supplier_id=0 ORDER BY shipping_order";
                $row = $GLOBALS['db']->GetRow($sql);
                if ($row) {
                    $list[] = $row;
                }
            }
        }
        $this->success($list);
    }

    //获取退款配置
    public function getBackConfig()
    {
        $sql = "SELECT code,value FROM " . $GLOBALS['ecs']->table('shop_config') . " WHERE code IN ('back_address_name','back_address_mobile','back_address_address','back_remark_0','back_remark_5','back_remark_6')";
        $config = $GLOBALS['db']->getAll($sql);
        $shop_config = array();
        foreach ($config as $k => $v) {
            $shop_config[$v['code']] = $v['value'];
        }
        $back_config = array(
            'address' => array(
                'address' => $shop_config['back_address_address'] ? $shop_config['back_address_address'] : "",
                'mobile' => $shop_config['back_address_mobile'] ? $shop_config['back_address_mobile'] : "",
                'name' => $shop_config['back_address_name'] ? $shop_config['back_address_name'] : "",
            ),
            'remark' => array(
                0 => $shop_config['back_remark_0'] ? $shop_config['back_remark_0'] : "",
                5 => $shop_config['back_remark_5'] ? $shop_config['back_remark_5'] : "",
                6 => $shop_config['back_remark_6'] ? $shop_config['back_remark_6'] : "",
            ),
        );
        $this->success($back_config);
    }
}

function read_modules_re($directory = '.')
{
    $dir = @opendir($directory);
    $set_modules = true;
    $modules = array();

    while (false !== ($file = @readdir($dir))) {
        if (preg_match("/^.*?\.php$/", $file)) {
            include_once($directory . '/' . $file);
        }
    }
    @closedir($dir);
    unset($set_modules);

    foreach ($modules AS $key => $value) {
        ksort($modules[$key]);
    }
    ksort($modules);

    return $modules;
}

