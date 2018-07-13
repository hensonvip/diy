<?php

/**
 * 小树熊内部验签 For PHP
 * 
 * @version v1.0
 * @create 2015-08-05
 * @author veapon(veapon88@gmail.com)
 */
class Auth
{
	public static function checkAuth($request, $config)
	{
		$request_api_sign = $request['sign'];
		unset($request['sign']);

		$api_sign = self::getSign($request, $config);

		if (!$api_sign || $request_api_sign != $api_sign)
		{
			return false;
		}
		return true;

	}

	public static function getSign($param, $config)
	{
		$app_key = isset($config['key']) ? $config['key'] : '';
		$app_secret = isset($config['secret']) ? $config['secret'] : '';
		if (empty($app_key) || empty($app_secret))
		{
			return false;
		}

		$token = $app_key;
		$token .= self::loopArrayToken($param);
		$token .= $app_secret;

		$token = md5($token);
		return $token;
	}

	public static function loopArrayToken($param)
	{
		$token = "";
		ksort($param);
		foreach ($param as $k=>$v)
		{
			//参数sign不参与运算
			if ($k == 'sign') 
			{
				continue;
			}
			elseif(is_array($v))
			{
				$token .= "{$k}";
				$token .= $this->_loop_array_token($v);
			}
			else
			{
				$token .= "{$k}{$v}";
			}
		}
		return $token;
	}

}
