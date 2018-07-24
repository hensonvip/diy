<?php

/**
 * 新版接口基类
 *
 */
class ApiController extends CommonController
{
	protected $logger = null;
	protected $mapper = null;
	protected $app_name = "";
	public $devices;
	public $app;
	public $method;
	public $version;



	public function  __construct($data = array())
	{
		parent::__construct();
		$this->devices = array("android", "ios", "xcx", "pc", "wap");
		$this->app = array("android", "ios", "xcx");
		//echo get_called_class();
		$config = array(
				'type'=>'file',
				'log_path'=> ROOT_PATH.'data/logs/api/',
			       );
		$this->logger = new Logger($config);

		if($this->method){
			if ($_SERVER['REQUEST_METHOD'] != $this->method) {
				throw new ActivityException(1001);
				exit;
			}
		}

		// 处理输入
		$request = array_merge($_GET, $_POST , $_FILES);

		//针对小程序的处理
		if(isset($request['device']) && $this->unicode_decode($request['device']) == 'xcx'){
			foreach($request as $k=>$v){
				$this->req[$k] = $this->unicode_decode($v);
			}
		}else{
			$this->req = $request;
		}

		$this->logRequest();
		$real_ip = real_ip();
		$host = $_SERVER['HTTP_HOST'];

		$debug = $this->input('debug',0);//$debug = 0;
		if($debug == 1){
			header('Access-Control-Allow-Origin:*');
			$this->version = $this->req['version'];
			$this->version = str_replace("v","",$this->version);
			error_reporting(E_ALL);
		}else{
			error_reporting(0);
		}

		if (strtolower(CONTROLLER) != 'test'  and $debug != 1)
		{
			$this->checkParams($request);
			$config = Config_Api::$config;
			if (isset($request['api_key']) && isset($config[$request['api_key']]['app_name']))
			{
				$this->app_name = $config[$request['api_key']]['app_name'];
			}
			$this->_authVerify();

		}

		//token验证


	}

	/**
	 * 获取$_GET, $_POST
	 *
	 * @param $key
	 * @param $default
	 * @return mixed
	 * @create 2015-05-29
	 */
	final protected function input($key = false, $default = null)
	{
		if ($key === false)
		{
			return $this->req;
		}

		if (isset($this->req[$key]))
		{
			return $this->req[$key];
		}

		if ($default !== null)
		{
			return $default;
		}

		return null;
	}

	protected function error($msg, $code = 500, $return_val = array())
	{
		Response::render($return_val, $code, $msg);
	}

	protected function success($data, $code = 200, $msg = 'SUCCESS')
	{
		//Response::render($data, $code, $msg);
		//Response::render($data, $code, (is_array($data) || is_object($data) ? $msg : $data));
		Response::render((is_array($data) || is_object($data) ? $data : array()), $code, (is_array($data) || is_object($data) ? $msg : $data));
	}

	protected function display($data,$html = 'goods')
	{
		/* 创建 Smarty 对象。*/
		require(ROOT_PATH . 'includes/cls_template.php');
		$smarty = new cls_template;

		$smarty->cache_lifetime = 3600;
		$smarty->template_dir   = ROOT_PATH . 'qdapi/template';
		$smarty->cache_dir      = ROOT_PATH . 'temp/caches';
		$smarty->compile_dir    = ROOT_PATH . 'temp/compiled';

		$smarty->assign('data',      $data);

		$smarty->display($html.'.dwt');
	}

	/**
	 * 数字签名验证
	 *
	 * @return bool
	 * @throws Exception
	 */
	private function _authVerify()
	{
		$config = Config_Api::$config;
		$request = array_merge($_GET, $_POST);
//		print_R($request);
//		exit();
		//if (isset($request['debug_code']) && $request['debug_code'] == '178533e7f670efae6fa6497703b1426e')
		//{
			//return true;
		//}

		//$this->logger->writeLog("============ API auth debug =================", 'debug', 'apidebug_');
		//foreach ($request as $k=>$v)
		//{
			//$this->logger->writeLog("$k: $v", 'debug', 'apidebug_');
		//}

		if (!isset($request['api_key']) || !isset($request['api_sign']) || empty($config[$request['api_key']]))
		{

			throw new ActivityException(1003);
		}
		$request_api_sign = $request['api_sign'];
		unset($request['api_sign']);

		$api_sign = $this->_setToken($config[$request['api_key']]['secret'], $request);
		$new_api_sign = $this->_getSign($config[$request['api_key']]['secret'], $request);

		if (($request_api_sign !== $api_sign) && ($request_api_sign !== $new_api_sign))
		{
			echo $api_sign;
			throw new ActivityException(1003);
		}



		return true;
	}

	protected function _setToken($secret, $param)
	{
		$token = $param['api_key'];
		$token .= $this->_loopArrayToken($param);
		$token .= $secret;
		$this->logger->writeLog('token before md5: '. $token, 'debug', 'apidebug_');
		$this->_token_before_md5 = $token;
		$token = md5($token);
		$this->logger->writeLog('token after md5: '. $token, 'debug', 'apidebug_');

		return $token;
	}

    protected function _getSign($secret, $param)
    {
        $token = $secret;
        $token .= $this->_loopArrayToken($param);
        $token .= $secret;
		$this->_token_before_md5 = $token;
        $token = strtoupper(md5($token));
        return $token;
    }

	protected function _loopArrayToken($param){
		$token = "";
		ksort($param);
		foreach($param as $k=>$v){
			if(is_array($v)){
				$token .="{$k}";
				$token .= $this->_loopArrayToken($v);
			}else{
				$token .= "{$k}{$v}";
			}
		}
		//echo $token;
		return stripslashes($token);
	}

	private function logRequest()
	{
		$log = sprintf("URI: %s/%s,Params: %s", CONTROLLER, ACTION, json_encode($_REQUEST));
		$this->logger->writeLog($log, 'info', 'apiaccess_');
	}

	public function mapFields(&$data)
	{
		$mapper = FieldMapper::factory($this->app_name, CONTROLLER);
		return $mapper->parse($data, ACTION);
	}

	public function getPageParam($params)
	{
		$current_page = isset($params['pageNo']) ? intval($params['pageNo']) : 1;
		$page_size = isset($params['pageSize']) ? intval($params['pageSize']) : 10;

		$start = ($current_page - 1) * $page_size;
		return array('start'=>$start, 'page_size'=>$page_size, 'current_page'=>$current_page);
	}
	// curl 提交
	public function https_request($url, $data = null){
		$ch = curl_init();
		$timeout = 300;
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_SSLVERSION,3);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
		if(!empty($data)){
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		}
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
		$output = curl_exec($ch);
		curl_close($ch);

		return $output;
	}

	//测试必填参数
	protected function checkParams($request){
		if(empty($request)){
			throw new ActivityException(1025);
			exit;
		}
		//检查的参数字段
		$params = array('device','version','timestamp');
		foreach($params as $v){
			if(!isset($request[$v])){
				throw new ActivityException(1025);
				exit;
			}
		}
		$this->version = $this->req['version'];
		//目前的允许的api版本
		$version = array('v1','v1.0.1','v1.0.2','v1.0.3');
		if(!in_array($this->version,$version)){
			throw new ActivityException(1088);
			exit;
		}
		$this->version = str_replace("v","",$this->version);
		//|| (substr($this->req['timestamp'],0,10) > (time()+50) ) || (substr($this->req['timestamp'],0,10) < (time()-50) )



		if(!in_array($this->req['device'],$this->devices) || ! is_numeric($this->req['timestamp'])  ){
			throw new ActivityException(1025);
			exit;
		}

		if(strlen($this->req['timestamp']) == 10){
			if(($this->req['timestamp'] > (time()+600) ) || ($this->req['timestamp'] < (time()-600) )){
				throw new ActivityException(1025);
				exit;
			}
		}else{
			if((floor($this->req['timestamp']/1000) > (time()+600) ) || (floor($this->req['timestamp']/1000) < (time()-600) )){
				throw new ActivityException(1025);
				exit;
			}
		}

	}

	//小程序的转换
	protected function unicode_decode($name)
	{
		// 转换编码，将Unicode编码转换成可以浏览的utf-8编码
		$pattern = '/([\w]+)|(\\\u([\w]{4}))/i';
		preg_match_all($pattern, $name, $matches);
		if (!empty($matches))
		{
			$name = '';
			for ($j = 0; $j < count($matches[0]); $j++)
			{
				$str = $matches[0][$j];
				if (strpos($str, '\\u') === 0)
				{
					$code = base_convert(substr($str, 2, 2), 16, 10);
					$code2 = base_convert(substr($str, 4), 16, 10);
					$c = chr($code).chr($code2);
					$c = iconv('UCS-2', 'UTF-8', $c);
					$name .= $c;
				}
				else
				{
					$name .= $str;
				}
			}
		}
		return $name;
	}

}


