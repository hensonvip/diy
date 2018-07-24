<?php
/**
 * 商品模块
 * @2016-11-02 jam
 */

include_once(ROOT_PATH . 'includes/lib_order.php');

if (!defined('IN_ECS'))
{
	die('Hacking attempt');
}

class cls_shipping
{
	protected $_db                = null;
	protected $_tb_goods          = null;
	protected $_tb_sku   		  = null;
	protected $_tb_brand		  = null;
	protected $_tb_gallery		  = null;
	protected $_tb_goods_description		  = null;
	protected $_tb_goods_attr	  = null;
	protected $_price_decimal     = 1;
	protected $_now_time          = 0;
	protected $_mc_time			  = 0;
	protected $_plan_time		  = 0;
	protected $_mc				  = null;
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
		$this->_db = $GLOBALS['db'];
	
		$this->_tb_goods         = $GLOBALS['ecs']->table('goods');
		$this->_tb_sku 			 = $GLOBALS['ecs']->table('products');
		$this->_tb_shipping	     = $GLOBALS['ecs']->table('shipping');
		$this->_tb_shipping_area = $GLOBALS['ecs']->table('shipping_area');
		$this->_tb_region        = $GLOBALS['ecs']->table('region');
		$this->_tb_area_region   = $GLOBALS['ecs']->table('area_region');
		$this->_now_time         = time();
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
	 * 运费详情
	 * @param $goods_id integer
	 * @param $region array
	 * @return string
	 */
	public function get_shipping_detail($goods_id = 0, $region = array(1)){

		if($goods_id){
			/* 检查是否免运费 */
			$sql = "SELECT is_shipping,goods_weight FROM " .$this->_tb_goods .
				" WHERE goods_id = " . $goods_id;
			$goodsInfo = $this->_db->GetRow($sql);
		}



		$shipping_list     = available_shipping_list($region);

		foreach ($shipping_list AS $key => $val)
		{
			$shipping_cfg = unserialize_config($val['configure']);

			$shipping_fee = $goodsInfo['is_shipping'] == 1 ? 0 : $this->shipping_fee($val['shipping_code'], unserialize($val['configure']), $goodsInfo['goods_weight'], 1, 1);



			$shipping_list[$key]['format_shipping_fee'] = price_format($shipping_fee, false);
			$shipping_list[$key]['shipping_fee']        = $shipping_fee;
			$shipping_list[$key]['free_money']          = price_format($shipping_cfg['free_money'], false);
			$shipping_list[$key]['insure_formated']     = strpos($val['insure'], '%') === false ?
				price_format($val['insure'], false) : $val['insure'];

		}



		return $shipping_list;

	}


    /**
     * 运费详情
     * @param $goods_id integer
     * @param $region array
     * @return string
     */
    public function get_shipping_goods_str($goods_id = 0, $region = array(1),$shop_price = 0){

        if($goods_id){
            /* 检查是否免运费 */
            $sql = "SELECT is_shipping,goods_weight,supplier_id FROM " .$this->_tb_goods .
                " WHERE goods_id = " . $goods_id;
            $goodsInfo = $this->_db->GetRow($sql);
        }



        $shipping_list     = available_shipping_list($region,$goodsInfo['supplier_id']);


		$shipping_list_info = array();
        foreach ($shipping_list AS $key => $val)
        {
            $shipping_cfg = unserialize_config($val['configure']);

            $shipping_fee = $goodsInfo['is_shipping'] == 1 ? 0 : $this->shipping_fee($val['shipping_code'], unserialize($val['configure']), $goodsInfo['goods_weight'], 1, 1);

			$shipping_list_info[$key]['format_shipping_fee'] = price_format($shipping_fee, false);
			$shipping_list_info[$key]['shipping_fee']        = $shipping_fee;
			$shipping_list_info[$key]['shipping_name']       = $val['shipping_name'];
			$shipping_list_info[$key]['min_money']       	 = $shipping_cfg['free_money'];//免费额度
			$shipping_list_info[$key]['free_money']          = price_format($shipping_cfg['free_money'], false);
			$shipping_list_info[$key]['insure_formated']     = strpos($val['insure'], '%') === false ? price_format($val['insure'], false) : $val['insure'];

        }

		$shipping_str = '未设置快递方式或配送地区';
		if($shipping_list_info){
			$shipping_info = current($shipping_list_info);
			$shipping_str = '快递方式:'.$shipping_info['shipping_name'].'，运费：'.$shipping_info['format_shipping_fee'];
			if($shipping_info['min_money'] > 0 && $shipping_info['min_money'] <= $shop_price){
				$shipping_str = '快递方式：'.$shipping_info['shipping_name'].'，免运费';
			}
		}

        return $shipping_str;

    } 
	/**
     * 运费详情
     * @param $goods_id integer
     * @param $region array
     * @return string
     */
    public function get_shipping_goods_arr($goods_id = 0, $region = array(1),$shop_price = 0){

        if($goods_id){
            /* 检查是否免运费 */
            $sql = "SELECT is_shipping,goods_weight FROM " .$this->_tb_goods .
                " WHERE goods_id = " . $goods_id;
            $goodsInfo = $this->_db->GetRow($sql);
        }



        $shipping_list     = available_shipping_list($region);


		$shipping_list_info = array();
        foreach ($shipping_list AS $key => $val)
        {
            $shipping_cfg = unserialize_config($val['configure']);

            $shipping_fee = $goodsInfo['is_shipping'] == 1 ? 0 : $this->shipping_fee($val['shipping_code'], unserialize($val['configure']), $goodsInfo['goods_weight'], 1, 1);

			$shipping_list_info[$key]['format_shipping_fee'] = price_format($shipping_fee, false);
			$shipping_list_info[$key]['shipping_fee']        = $shipping_fee;
			$shipping_list_info[$key]['shipping_name']       = $val['shipping_name'];
			$shipping_list_info[$key]['min_money']       	 = $shipping_cfg['free_money'];//免费额度
			$shipping_list_info[$key]['free_money']          = price_format($shipping_cfg['free_money'], false);
			$shipping_list_info[$key]['insure_formated']     = strpos($val['insure'], '%') === false ? price_format($val['insure'], false) : $val['insure'];

        }

		$shipping_str = array('way'=>'','fee'=>'');
		if($shipping_list_info){
			$shipping_info = current($shipping_list_info);
			$shipping_str = array('way'=>$shipping_info['shipping_name'],'fee'=>$shipping_info['format_shipping_fee']);
			if($shipping_info['min_money'] > 0 && $shipping_info['min_money'] <= $shop_price){
				$shipping_str = array('way'=>$shipping_info['shipping_name'],'fee'=>'¥0.00');
			}
		}

        return $shipping_str;

    }

	/**
	 * 计算运费
	 * @param   string  $shipping_code      配送方式代码
	 * @param   mix     $shipping_config    配送方式配置信息
	 * @param   float   $goods_weight       商品重量
	 * @param   float   $goods_amount       商品金额
	 * @param   float   $goods_number       商品数量
	 * @return  float   运费
	 */
	public function shipping_fee($shipping_code, $shipping_config, $goods_weight, $goods_amount, $goods_number='')
	{

		if (!is_array($shipping_config))
		{
			$shipping_config = unserialize($shipping_config);
		}

		$filename = ROOT_PATH . 'includes/modules/shipping/' . $shipping_code . '.php';

		if (file_exists($filename))
		{
			include_once($filename);

			$obj = new $shipping_code($shipping_config);

			return $obj->calculate($goods_weight, $goods_amount, $goods_number);
		}
		else
		{
			return 0;
		}
	}


}
