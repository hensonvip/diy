<?php
/**
 * 优惠券活动
 *
 * User: waen
 * Date: 15-1-17
 * Time: 下午4:05
 */
include_once(ROOT_PATH.'app/app_pms.php');

class CouponController extends BaseController
{
	public function gainCoupon()
	{
		$id = !empty($_REQUEST['promotion_id']) ? intval($_REQUEST['promotion_id']) : 0;
		$uid = !empty($_REQUEST['uid']) ? intval($_REQUEST['uid']) : 0;
		$data = array();
		$code = 200;
		if(($id < 1) || ($uid < 1))
		{
			$message = 'promotion_id or uid can not be empty';
			Response::render($data, $code, $message);
		}
		$data['userId'] = $uid;
		$data['promotionId'] = $id;
		$data['type'] = 0;
		$data['ip'] = real_ip();
		$result = app_pms::createCoupon($data);
		if(!empty($result) && $result['code'] == 0)
		{
			if($result['data']['code'] == 0)
			{
				Response::render(array());
			}
			else
			{
				Response::render(array(), $code, $result['data']['msg']);
			}
		}
		else
		{
			Response::render(array(), $code, $result['msg']);
		}
	}

	/**
	 * 领取育儿礼包接口
	 * 需要在后台设置育儿礼包优惠券id，优惠券wap页面要推荐的商品
	 * 
	 * @return 
	 * @since v1.0
	 * @create 2015-06-04
	 */
	public function yuerPackage()
	{
		$uid = $this->input('uid', 0);
		$ip = $this->input('ip', '');
		$uid = intval($uid);
		if (empty($uid))
		{
			Response::render(array(), 400, 'Param error');
		}
		// 获取育儿礼包优惠券id, 有缓存
		$mc = Mama_Cache::factory('memcache');
		$mc_key = md5('hygj_yuer_packer_couponid');
		if (!($coupon_id = $mc->get($mc_key)))
		{
			$sql = "SELECT * FROM ".$GLOBALS['ecs']->table('shop_config')." WHERE code='yuer_package_coupon' LIMIT 1";
			$cfg = $GLOBALS['db']->getRow($sql);
			$coupon_id = empty($cfg) ? false : $cfg['value'];

			if ($coupon_id)
			{
				$mc->set($mc_key, $coupon_id);
			}
		}

		
		// 没有设置育儿礼包优惠券id ?
		if (empty($coupon_id))
		{
			Response::render(array(), 1001, 'Promotion id is not set');
		}
		
		// 用户优惠券页面
		$url = 'http://'.$_SERVER['HTTP_HOST'].'/mobile/user_coupon.php?fm=2&act=yuer_package&uid='.$uid;

		$arr_coupon = explode(',', $coupon_id);
		foreach ($arr_coupon as $coupon_id)
		{
			// 调pms接口
			$data['userId'] = $uid;
			$data['promotionId'] = $coupon_id;
			$data['type'] = 0;
			$data['ip'] = $ip;
			$result = app_pms::createCoupon($data);
			if(!empty($result) && $result['code'] == 0)
			{
				if($result['data']['code'] != 0)
				{
					Response::render(array(), 1002, $result['data']['msg']);
				}
			}
			else
			{
				Response::render(array(), 1003, $result['msg']);
			}
		}
		
		Response::render(array('url'=>$url));
	}
}
