<?php
/**
 * 微信卡券活动
 *
 * User: waen
 * Date: 15-1-22
 * Time: 下午5:21
 */
//微信接口
require(ROOT_PATH . 'app/app_weixin.php');
require(ROOT_PATH . 'includes/weixin_card.php');

class CardController extends BaseController
{
	public function getCard()
	{
// 		echo "no allow";
// 		exit;
		$card_id = !empty($_REQUEST['id']) ? trim($_REQUEST['id']) : 0;
		$card_info = app_weixin::get_card_info($card_id);
		var_dump($card_info);
		exit;
	}
	
	public function createCashCard()
	{
		echo "no allow";
		exit;
		$logo_url = "http://mmbiz.qpic.cn/mmbiz/uzMCtJSibK1kudzKUBleRmkHzRmeGMjKicA2hicmIXkdSxCA8icQiaqAdfQU6HhOcEuSJ9o70WRp8H3vwTCxuxiat55A/0";
		$brand_name = "小树熊进口母婴特卖";
		$code_type = "CODE_TYPE_TEXT";
		$title = "￥100现金券-满减";
		$color = "Color081";
		$notice = "妈妈圈·官方商城小树熊-只卖进口货";
		$service_phone = "4008699932";
		$description = "小树熊是妈妈圈自营的进口母婴特卖商城，专注妈妈圈5000万妈妈的分享和呼声，只选最佳口碑商品；全部国外原装进口和国内优质正规代理商授权，专注母婴，值得妈妈信赖；\n
1）该代金券适用于小树熊母婴商城内的所有商品，在提交订单前选择微信卡券，选择此代金券满399元即可减免100元 （只支持微信支付方式，非微信支付方式不可用）；\n
2）该优惠券仅限使用一次，且不可与其他微信卡券和小树熊优惠券同时使用；\n
3）本券不得兑换和折现现金，仅供商品结算时减免费用 ；\n
4）使用过程中有任何疑问，请拨打客服电话；";
		$date_info = new DateInfo(1, 1424188800, 1426780799);
		$sku = new Sku(2000000);
		$base_info = new BaseInfo( $logo_url, $brand_name, $code_type, $title, $color, $notice, $service_phone, $description, $date_info, $sku );
		$base_info->set_sub_title( "只卖进口货，此券全场通用，满399减100" );
		$base_info->set_use_limit( 1 );
		$base_info->set_get_limit( 1 );
		$base_info->set_use_custom_code( false );
		$base_info->set_bind_openid( false );
		$base_info->set_can_share( true );
		$base_info->set_url_name_type( "URL_NAME_TYPE_USE_IMMEDIATELY" );
		$base_info->set_custom_url( "http://www.xiaoshuxiong.com/mobile/?fm=1" );
// 		$base_info->set_can_shake( true );
// 		$base_info->set_shake_slogan_title("对不起，我一直很抠");
// 		$base_info->set_shake_slogan_sub_title("这次咬碎牙才舍得送你100元！");
		$card = new Card("CASH", $base_info);
		$card->get_card()->set_least_cost(39900);
		$card->get_card()->set_reduce_cost(10000);
		$card_info = $card->toJson();
		$result = app_weixin::create_card($card_info);
		if(!empty($result) && $result['errcode'] == 0)
		{
			$wx_card['card_id'] = $result['card_id'];
			$wx_card['title'] = $card->cash->base_info->title;
			$wx_card['card_type'] = $card->card_type;
			$wx_card['least_cost'] = ($card->cash->least_cost)/100;
			$wx_card['reduce_cost'] = ($card->cash->reduce_cost)/100;
			if(isset($card->cash->base_info->date_info->begin_timestamp))
			{
				$wx_card['begin_timestamp'] = $card->cash->base_info->date_info->begin_timestamp;
				$wx_card['end_timestamp'] = $card->cash->base_info->date_info->end_timestamp;
			}
			$GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('wx_card'), $wx_card, 'INSERT');
			echo "添加卡券成功";
		}
		else
		{
			var_dump($result);
		}
		exit;
	}
	
	public function createGiftCard()
	{
		echo "no allow";
		exit;
		$logo_url = "https://mmbiz.qlogo.cn/mmbiz/WibIYtQFktOM3vKbjwScicjQoibYPYVWOzmfsrqicy1tUhJwKRiboKYbNtsMFWJf3mmnzqmxIJkoUxwdRAAyotVYWog/0";
		$brand_name = "小树熊进口母婴特卖";
		$code_type = "CODE_TYPE_TEXT";
		$title = "小树熊礼品券";
		$color = "Color060";
		$notice = "妈妈圈·官方商城小树熊-只卖进口货";
		$service_phone = "4008699932";
		$description = "小树熊是妈妈圈自营的进口母婴特卖商城，专注妈妈圈5000万妈妈的分享和呼声，只选最佳口碑商品；全部国外原装进口和国内优质正规代理商授权，专注母婴，值得妈妈信赖；\n
		1）该礼品券适用于小树熊母婴商城内的礼品专场商品，在提交订单前选择微信卡券，选择此礼品券即可减免一件礼品专区的商品（只支持微信支付方式，非微信支付方式不可用）；\n
		2）该优惠券仅限使用一次，且不可与其他微信卡券和小树熊优惠券同时使用；\n
		3）本券不得兑换和折现现金，仅供商品结算时减免费用 ；\n
		4）使用过程中有任何疑问，请拨打客服电话；";
		$date_info = new DateInfo(1, 1422374400, 1423756799);
		$sku = new Sku(1000);
		$base_info = new BaseInfo( $logo_url, $brand_name, $code_type, $title, $color, $notice, $service_phone, $description, $date_info, $sku );
		$base_info->set_sub_title( "只卖进口货，此券只能兑换指定商品" );
		$base_info->set_use_limit( 1 );
		$base_info->set_get_limit( 10 );
		$base_info->set_use_custom_code( false );
		$base_info->set_bind_openid( false );
		$base_info->set_can_share( true );
		$base_info->set_url_name_type( "URL_NAME_TYPE_EXCHANGE" );
		$base_info->set_custom_url( "http://www.xiaoshuxiong.com/mobile/?fm=1" );
		$card = new Card("GIFT", $base_info);
		$card->get_card()->set_gift( "iphone6 plus" );
		$card_info = $card->toJson();
		$result = app_weixin::create_card($card_info);
		if(!empty($result) && $result['errcode'] == 0)
		{
			$wx_card['card_id'] = $result['card_id'];
			$wx_card['title'] = $card->gift->base_info->title;
			$wx_card['card_type'] = $card->card_type;
			if(isset($card->gift->base_info->date_info->begin_timestamp))
			{
				$wx_card['begin_timestamp'] = $card->gift->base_info->date_info->begin_timestamp;
				$wx_card['end_timestamp'] = $card->gift->base_info->date_info->end_timestamp;
			}
			$GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('wx_card'), $wx_card, 'INSERT');
			echo "添加卡券成功";
		}
		else
		{
			var_dump($result);
		}
		exit;
	}
	
	public function deleteCard()
	{
		echo "no allow";
		exit;
		$card_id = !empty($_REQUEST['id']) ? trim($_REQUEST['id']) : 0;
		$result = app_weixin::delete_card($card_id);
		var_dump($result);
		exit;
	}
	
	public function updateCard()
	{
		echo "no allow";
		exit;
		$card_id = !empty($_REQUEST['id']) ? trim($_REQUEST['id']) : 0;
		$card_info['card_id'] = $card_id;
// 		$card_info['cash']['base_info']['description'] = "小树熊是妈妈圈自营的进口母婴特卖商城，专注妈妈圈5000万妈妈的 分享和呼声，只选最佳口碑商品；全部国外原装进口和国内优质正规 代理商授权，专注母婴，值得妈妈信赖；
// 1）该代金券适用于小树熊母婴商城内的所有商品，在提交订单前选 择微信卡券，选择此代金券即可减免40元 ；
// 2）该优惠券仅限使用一次，且不可与其他微信卡券和小树熊优惠券 同时使用 ；
// 3）本券不得兑换和折现现金，仅供商品结算时减免费用 ；
// 4）使用过程中有任何疑问，请拨打客服电话；";
// 		$card_info['cash']['base_info']['url_name_type'] = "URL_NAME_TYPE_USE_IMMEDIATELY";
// 		$card_info['cash']['base_info']['custom_url'] = "http://www.xiaoshuxiong.com/mobile/?fm=1";
// 		$card_info['cash']['base_info']['promotion_url'] = "http://www.xiaoshuxiong.com/mobile/?fm=1";
// 		$card_info['cash']['base_info']['can_shake'] = true;
// 		$card_info['cash']['base_info']['shake_slogan_title'] = "妈妈圈母婴商城小树熊";
// 		$card_info['cash']['base_info']['shake_slogan_sub_title'] = "送你￥30的新年愿望，要啥就买啥";
// 		$card_info['cash']['base_info']['color'] = "";
// 		$card_info['cash']['least_cost'] = 39900;
// 		$card_info['cash']['reduce_cost'] = 10000;
// 		$card_info['cash']['base_info']['notice'] = "请在小树熊官网使用";
// 		$card_info['cash']['base_info']['get_limit'] = 10;
// 		$card_info['gift']['base_info']['custom_url'] = "http://www.xiaoshuxiong.com/mobile/?fm=1";
		$card_info['gift']['base_info']['url_name_type'] = "URL_NAME_TYPE_EXCHANGE";
		$result = app_weixin::update_card_info($card_info);
		var_dump($result);
	}
	
	public function addCardCode()
	{
		echo "no allow";
		exit;
		$code_list['card_id'] = 'pz-J_jgtMS-5NJH4UJgoVQT3AzBQ';
		$code_list['code'] = array('1234567890123456');
		$result = app_weixin::add_card_code($code_list);
		var_dump($result);
	}
	
	public function getCode()
	{
		echo "no allow";
		exit;
		$code = !empty($_REQUEST['code']) ? trim($_REQUEST['code']) : 0;
// 		$code = '983954488269';
// 		$card_id = 'pz-J_jtQzD5Nb4BrxYisDbc-JSnk';
		$result = app_weixin::get_code_info($code);
		var_dump($result);
		exit;
	}
	
	public function getApiTicket()
	{
		echo "no allow";
		exit;
		$result = app_weixin::get_api_ticket();
		var_dump($result);
	}
	
	public function getJsapiSignature()
	{
		echo "no allow";
		exit;
		$ticket = app_weixin::get_ticket();
		$data['noncestr'] = 'werertyrt';
		$data['jsapi_ticket'] = $ticket['ticket'];
		$data['timestamp'] = time();
		$data['url'] = 'http://www.xiaoshuxiong.com/wx_test/';
		ksort($data);
		$str = '';
		foreach ($data as $k => $v)
		{
			$str .= empty($str) ? $k.'='.$v : '&'.$k.'='.$v;
		}
		$signature = sha1($str);
		var_dump($signature,$data);
	}
	
	public function setTestWhiteList()
	{
		echo "no allow";
		exit;
// 		$user['openid'] = array();
// 		$user['username'] = array('wangpengai2008','holyrain');
		$result = app_weixin::set_testwhitelist($user);
		var_dump($result);
		exit;
	}
	
	public function createCardSign()
	{
		echo "no allow";
		exit;
		$result = app_weixin::create_cardSign();
		var_dump($result);
		exit;
	}
	
	public function consumeCode()
	{
		echo "no allow";
		exit;
		$code = !empty($_REQUEST['code']) ? trim($_REQUEST['code']) : 0;
		$result = app_weixin::consume_code($code);
		var_dump($result);
		exit;
	}
	
	public function createQrcode()
	{
		echo "no allow";
		exit;
		$card_id = !empty($_REQUEST['id']) ? trim($_REQUEST['id']) : 0;
		$qrcode['action_name'] = "QR_CARD";
		$qrcode['action_info']['card']['card_id'] = $card_id;
		$result = app_weixin::qrcode($qrcode);
		var_dump($result);
		exit;
	}
	
}