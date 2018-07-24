<?php
include_once(ROOT_PATH . 'includes/cls_goods.php');
include_once(ROOT_PATH . 'includes/cls_user.php');
require_once(ROOT_PATH . 'includes/cls_category.php');
/**
 * 首页接口
 *
 * @version v1.0
 * @create 2016-11-02
 * @author cyq
 */
class IndexController extends ApiController
{
	public $method = 'GET';
	public function __construct()
	{

		parent::__construct();
		$this->data  = $this->input();
		$this->goods = cls_goods::getInstance();
		$this->user  = cls_user::getInstance();
		$this->category     = cls_category::getInstance();

	}

	/**
	 * 获取banner以及icon
	 *
	 */

	 public function all(){
		$device = $this->input('device');

		$user_id = $this->input('user_id', 0);

		if(!isset($device) && !in_array($device,$this->devices)){
			throw new ActivityException(1013);
			exit;
		}


		$head = array();


		$icpn_type = in_array($device,$this->app)?'app':(($device=='wap')?'wap':'pc');
		$head['banner'] =  $this->get_banner($icpn_type);
		$head['icon'] =  $this->get_icon($icpn_type);

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

		$children = get_children(0);

		$product1 = array('title'=>'最新商品','list'=>$this->goods->category_get_goods($user_rank_info, $children, '', '-1' ,0, 0, 0, '', 4, 1, 'desc', 'add_time' , 'is_new'),"ad"=>$this->ad('',6,time()),'type'=>'is_new');
		$product2 = array('title'=>'最热商品','list'=>$this->goods->category_get_goods($user_rank_info, $children, '', '-1' ,0, 0, 0, '', 4, 1, 'desc', 'sort_order' , 'is_hot'),"ad"=>$this->ad('',7,time()),'type'=>'is_hot');
		$product3 = array('title'=>'精选商品','list'=>$this->goods->category_get_goods($user_rank_info, $children, '', '-1' ,0, 0, 0, '', 4, 1, 'desc', 'sort_order' , 'is_best'),"ad"=>$this->ad('',8,time()),'type'=>'is_best');
		//print_r($head);
		$all = array('head'=>$head,"ad1"=>$this->ad('',4,time()),"product"=>array($product1,$product2,$product3),"ad2"=>$this->ad('',5,time()));
		$this->success($all);
	 }

	 public function head(){
		$device = $this->input('device');
		if(!isset($device) && !in_array($device,$this->devices)){
			throw new ActivityException(1013);
			exit;
		}
		$head = array();


		$icpn_type = in_array($device,$this->app)?'app':(($device=='wap')?'wap':'pc');
		$head['banner'] =  $this->get_banner($icpn_type);
		$head['icon'] =  $this->get_icon($icpn_type);
		$this->success($head);
	 }

	 public function ad($device=null,$position_id=null,$show = 1){
		$device = $device?:$this->input('device');
		$position_id = $position_id?:$this->input('position_id')?:$this->error('参数错误');

		if(!isset($device) && !in_array($device,$this->devices)){
			throw new ActivityException(1013);
			exit;
		}
		//print_r($this->app);
		$icpn_type = in_array($device,$this->app)?'app':(($device=='wap')?'wap':'pc');

		if($icpn_type == 'pc'){
			$table = 'ad';
		}else{
			$table = 'mobile_ad';
		}

		$sql = "SELECT ad_link,ad_code FROM ".$GLOBALS['ecs']->table($table). " WHERE  `position_id` = ".$position_id." AND start_time <= ".time();
		$ad = $GLOBALS['db']->getAll($sql);

		if(empty($ad)){
			$ad = array();
		}else{
			foreach($ad as $k=>$v){
				$ad[$k]['ad_code'] = 'data/afficheimg/'.$v['ad_code'];
			}
		}
		//print_r($sql);
		if($show!=1){
			return $ad;
		}
		$this->success($ad);
	 }

	//根据id批量获取品牌
	public function getBrands(){
		$brands = $this->input('brand_id');
		//$brands = explode(',',$brands);
		$sql = "SELECT brand_id,brand_name,brand_logo FROM ".$GLOBALS['ecs']->table('brand'). " WHERE  `is_show` = 1 and brand_id in(".$brands.")";
		$list = $GLOBALS['db']->getAll($sql);
		$this->success($list);

	}

 	//获取筛选项..
	public function getFilter(){
		$cat_ids = $this->input('cat_id');
		$arr = array();
		$sql = "SELECT cat_id,cat_name,filter_attr FROM ".$GLOBALS['ecs']->table('category'). " WHERE  `is_show` = 1 and (cat_id in(".$cat_ids.") or parent_id in (".$cat_ids."))";
		$list = $GLOBALS['db']->getAll($sql);

		foreach($list as $v){
			$ids = explode(",",$v['filter_attr']);
			foreach($ids as $k=>$v){
				if($v){
					$attr = $GLOBALS['db']->getRow("SELECT cat_id,attr_name,attr_values FROM ".$GLOBALS['ecs']->table('attribute'). " WHERE attr_id= '$v'");
					$arr[]=array('cat_id'=>$attr['cat_id'],'attr_name'=>$attr['attr_name'],'attr_values'=>str_replace(array("\r\n", "\r", "\n"), "", explode("\n",$attr['attr_values'])));
				}
			}
		}
		//$category_list = $this->category->get_categories_tree($cat_ids,false,0);
		$sql = "SELECT brand_id,brand_name,brand_logo FROM ". $GLOBALS['ecs']->table('brand')." where is_show = 1 AND cat_id in(".$cat_ids.") order by sort_order asc";
		$brand_list = $GLOBALS['db']->getAll($sql);
		foreach($brand_list as $k=>$v){
			$brand_list[$k]['brand_logo'] = $v['brand_logo']?'data/brandlogo/'.$v['brand_logo']:'data/brandlogo/437428736611050860.jpg';
		}
		$filter=array('brand'=>$brand_list,'attr'=>$arr);
		$this->success($filter);
	}


	 public function get_nav(){
		$where = 'WHERE 1 ';
		$where .= " AND `ifshow` = 1 AND `type` LIKE 'middle' ORDER BY type, vieworder ";
		$arr = array();
		$sql = "select s.* from ".$GLOBALS['ecs']->table('nav')." as s  $where ";
		$arr = $GLOBALS['db']->getAll($sql);
		$this->success($arr);
	}

	private function get_banner($icpn_type,$position_id = null){
		if($icpn_type == 'pc'){

			$playerdb = $this->get_flash_xml();
			foreach ($playerdb as $key => $val)
			{
				if (strpos($val['src'], 'http') === false)
				{
					$banner[$key]['ad_code'] =  $val['src'];
					$banner[$key]['url'] =  $val['url'];
					$banner[$key]['ad_name'] =  'PC广告';
				}
			}
			if(empty($banner)){
				return array();
			}else{
				return $banner;
			}
		}else{
			$table = 'mobile_ad';
			$position_id = $position_id?:1;

			$sql = "SELECT ad_link,ad_code,ad_name FROM ".$GLOBALS['ecs']->table($table). " WHERE  `position_id` = ".$position_id." AND `enabled` =1 AND end_time > ".time()." AND start_time < ".time();
			//echo $sql;
			$banner = $GLOBALS['db']->getAll($sql);

			if(empty($banner)){
				return array();
			}else{
				foreach($banner as $k=>$v){
					$banner[$k]['ad_code'] = 'data/afficheimg/'.$v['ad_code'];
				}
				return $banner;
			}
		}


	}

	private function get_icon($device){
		if($device!='pc'){
			$where = 'WHERE 1 ';
			$arr = array();
			$sql = "select s.* from ".$GLOBALS['ecs']->table('mobile_menu')." as s  $where order by sort asc";
			$arr = $GLOBALS['db']->getAll($sql);
			return $arr;
		}else{

		}

	}

	private function get_flash_xml()
	{
		$flashdb = array();
		if (file_exists(ROOT_PATH . DATA_DIR . '/flash_data.xml'))
		{

			// 兼容v2.7.0及以前版本
			if (!preg_match_all('/item_url="([^"]+)"\slink="([^"]+)"\stext="([^"]*)"\ssort="([^"]*)"/', file_get_contents(ROOT_PATH . DATA_DIR . '/flash_data.xml'), $t, PREG_SET_ORDER))
			{
				preg_match_all('/item_url="([^"]+)"\slink="([^"]+)"\stext="([^"]*)"/', file_get_contents(ROOT_PATH . DATA_DIR . '/flash_data.xml'), $t, PREG_SET_ORDER);
			}

			if (!empty($t))
			{
				foreach ($t as $key => $val)
				{
					$val[4] = isset($val[4]) ? $val[4] : 0;
					$flashdb[] = array('src'=>$val[1],'url'=>$val[2],'text'=>$val[3],'sort'=>$val[4]);
				}
			}
		}
		return $flashdb;
	}

	//快递100
	public function kuaidi(){
		$typeCom = $this->input('typeCom');
		$typeNu = $this->input('typeNu');


		$wuliu = array('EMS'=>'ems','中国邮政'=>'ems','申通快递'=>'shentong',"圆通速递"=>'yuantong','顺丰速运'=>'shunfeng','天天快递'=>'tiantian','韵达快递'=>'yunda','中通速递'=>'zhongtong','龙邦物流'=>'longbanwuliu','宅急送'=>'zhaijisong','全一快递'=>'quanyikuaidi','汇通速递'=>'huitongkuaidi','民航快递'=>'minghangkuaidi','亚风速递'=>'yafengsudi','快捷速递'=>'kuaijiesudi','华宇物流'=>'tiandihuayu','中铁快运'=>'zhongtiewuliu','百世汇通'=>'huitongkuaidi','全峰快递'=>'quanfengkuaidi','德邦'=>'debangwuliu','FedEx'=>'fedex','UPS'=>'ups','DHL'=>'dhl');

		$wuliu_state = array('在途','揽件','疑难','签收','退签','派件','退回');

		$postcom = $wuliu[$typeCom];

		//$url ='http://api.kuaidi100.com/api?id=e585dce131c9ef3d&com='.$typeCom.'&nu='.$typeNu;
		//$url ='http://www.kuaidi100.com/applyurl?key=e585dce131c9ef3d&com='.$typeCom.'&nu='.$typeNu;
		$url ='http://www.kuaidi100.com/query?id='.rand(1,9).'&type='.$postcom.'&postid='.$typeNu;
		//$url ='http://api.kuaidi100.com/api?id=[]&com=[]&nu=[]&valicode=[]&show=[0|1|2|3]&muti=[0|1]&order=[desc|asc]';
		$curl = curl_init();
		curl_setopt ($curl, CURLOPT_URL, $url);
		curl_setopt ($curl, CURLOPT_HEADER,0);
		curl_setopt ($curl, CURLOPT_RETURNTRANSFER, 1);
		//curl_setopt ($curl, CURLOPT_USERAGENT,$_SERVER['HTTP_USER_AGENT']);
		curl_setopt ($curl, CURLOPT_TIMEOUT,5);
		$get_content = curl_exec($curl);

		$arr = json_decode($get_content,true);
		$arr['com'] = array_search($arr['com'], $wuliu);//快递公司
		$arr['state'] = $wuliu_state[$arr['state']];//状态
		unset($arr['status']);
		unset($arr['condition']);
		unset($arr['ischeck']);
		unset($arr['message']);
		$this->success($arr);
	}

	public function getConfig(){

		$device = $this->input('device');

		if(!isset($device) && !in_array($device,$this->devices)){
			throw new ActivityException(1013);
			exit;
		}

		$icpn_type = in_array($device,$this->app)?'app':(($device=='wap')?'wap':'pc');

		$login = array();
		$payment = array();

		if($icpn_type == 'app'){

			$login['UMeng'] = array('AppKey'=>'59cd98d01c5dd0123e000016');
			$login['JPush'] = array('AppKey'=>'48338331fe271eff6e27510a');
			$login['Wechat'] = array('AppKey'=>'wxb76c4b55e21a050f','AppSecret'=>'1c556811f934b9037ec1cf554a22180d');
			$login['QQ'] = array('AppId'=>'1106304737','AppKey'=>'wUR03agDGdjP4guw');
			$login['Weibo'] = array('AppKey'=>'3217367206','AppScr'=>'3c647cdaa81c07a1a3e6f45edf9a9c3f');
			$wxpay_conf = json_decode($this->get_php_file(ROOT_PATH."/data/payment/wxpay_app.php"), true);
			$payment['Wechat'] = array('AppId'=>$wxpay_conf['appid'],'MchId'=>$wxpay_conf['mch_id'],'AppSecret'=>isset($wxpay_conf['appsecret'])?$wxpay_conf['appsecret']:'');
		}
		$arr = array('login'=>$login,'payment'=>$payment,'dongda'=>1);

		$this->success($arr);


	}

	public function get_php_file($filename) {
		return trim(substr(file_get_contents($filename), 15));
	}

	public function test(){
		return jpush();
	}

	/**
	 * 搜索价格列表
	 */
	public function getPrice(){
		$sql = "SELECT distinct sale_price FROM " . $GLOBALS['ecs']->table('user_rank') . " ORDER BY sale_price ASC";
		$list = $GLOBALS['db']->getCol($sql);
		$this->success($list);
	}

	/**
	 * 商店配置
	 */
	public function getShopConfig(){
		$config = load_config();
		$this->success($config);
	}
}