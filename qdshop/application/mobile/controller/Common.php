<?php
namespace app\mobile\controller;
use think\Controller;
use think\Cache;
use think\Config;

//公共类
class Common extends Controller
{

	public $webUrl ;
	public $webApiUrl ;
	private $apiKey;
	private $apiSecret;
	private $cus_param = array('device'=>'wap','timestamp'=>'','version'=>'v1.0.3');

	public $site_title;
	public $site_keywords;
	public $site_description;

    public function __construct()
    {
    	$config = Config::get('qdshop');
		//print_r($config);die();
		$this->webUrl = $config['webUrl']?:$this->error('请先设置配置信息');
		$this->webApiUrl = $config['webApiUrl']?:$this->error('请先设置配置信息');
		$this->cus_param['version'] = $config['cus_param']['version']?:$this->error('请先设置配置信息');
		$this->apiKey = $config['apiKey']?:$this->error('请先设置配置信息');
		$this->apiSecret = $config['apiSecret']?:$this->error('请先设置配置信息');
		$this->site_title = $config['site_title']?:$this->error('请先设置配置信息');
		$this->site_keywords = $config['site_keywords']?:$this->error('请先设置配置信息');
		$this->site_description = $config['site_description']?:$this->error('请先设置配置信息');

    	/* 重定义系统配置参数，要放在实例化之前才有效 */
    	config('template.view_path',__DIR__.'/../view/default1/');//切换模版
    	config('dispatch_error_tmpl','public/error');//默认错误跳转对应的模板文件
		config('dispatch_success_tmpl','public/success');//默认成功跳转对应的模板文件

        parent::__construct();
        $this->seo();
    }

    public function render(){
		$this->seo();
		die($this->fetch());
	}
	
	public function seo(){
		 header("Content-type:text/html;charset=utf-8");
		//网站标题 关键字 描述
        $this->assign('site_title',$this->site_title);
        $this->assign('site_keywords',$this->site_keywords);
        $this->assign('site_description',$this->site_description);
        
        $this->assign('webUrl',$this->webUrl);//网站网址设置 页面上使用
        $this->assign('footer_on',1);//底部高亮 定义默认状态
	}

    public function curlGet($api,$param=array()){
    	$CommonFun = new \app\mobile\library\Common();
    	$this->cus_param['timestamp'] = $this->getMillisecond();
    	$api = array('act'=>$api);
    	$arr = array_merge($param,$this->cus_param,array('api_key'=>$this->apiKey),array('api_key'=>$this->apiKey));
    	$arr2 = array_merge($api,$param,$this->cus_param,array('api_key'=>$this->apiKey));
    	$sign = $this->_setToken($this->apiSecret,$arr);
    	$url = $this->webApiUrl."?".$this->_loopArray($arr2)."&api_sign=".utf8_decode($sign);
    	//echo $url;
    	return $CommonFun->curlGet($url);
    }

    public function curlPost($api,$param,$file=array()){
    	$CommonFun = new \app\mobile\library\Common();
    	$this->cus_param['timestamp'] = $this->getMillisecond();
    	$api = array('act'=>$api);
    	$arr = array_merge($param,$this->cus_param,array('api_key'=>$this->apiKey));
    	$sign = $this->_setToken($this->apiSecret,$arr);
    	$arr2 = array_merge($param,$this->cus_param,array('api_key'=>$this->apiKey),array('api_sign'=>$sign)); 	
    	$url = $this->webApiUrl."?".$this->_loopArray($api);
    	//echo $url;
    	return $CommonFun->curlPost($url,$arr2,$file);
    }

    protected function _setToken($secret, $param)
	{
		$token = $param['api_key'];
		$token .= $this->_loopArrayToken($param);
		$token .= $this->apiSecret;
		//print_r($token);
		$token = md5($token);
		return $token;
	}

	protected function _loopArrayToken($param){
		$token = "";
		//print_r($param);
		ksort($param);
		foreach($param as $k=>$v){
			if(is_array($v)){
				$token .="{$k}";
				$token .= $this->_loopArrayToken($v);
			}else{
				$token .= "{$k}{$v}";
			}
		}
		return stripslashes($token);
	}

	protected function _loopArray($param){
		$token = array();
		//print_r($param);
		//ksort($param);
		foreach($param as $k=>$v){
			$token[] = $k."=".$v;
		}
		$token = implode("&",$token);
		return stripslashes($token);
	}

	public function getMillisecond() {
		list($t1, $t2) = explode(' ', microtime());
		return (float)sprintf('%.0f',(floatval($t1)+floatval($t2))*1000);
	}

	//获取生成的地区信息
	public function getRegion(){
		$result = Cache::get('region_data');
		if(empty($result)){
			$url = "region/index";
	        $result = $this->curlGet($url);
	        $result = json_decode($result,true);//json转数组
	        $result = $result['data']['list'];
	        //print_r($result);die;
			cache('region_data', $result, 60*60*24*7);
		}

		return $result;
	}

	//省份
	public function getRegionP(){
		$region_array = $this->getRegion();
		$data = array();
		foreach ($region_array as $k => $v) {
			$data[$k]['region_id'] = $v['region_id'];
			$data[$k]['region_name'] = $v['region_name'];
			$data[$k]['pkey'] = $k;
		}
		return $data;
	}

	//城市
	public function getRegionC($pkey){
		$region_array = $this->getRegion();
		$data = array();
		foreach ($region_array[$pkey]['city'] as $k => $v) {
			$data[$k]['region_id'] = $v['region_id'];
			$data[$k]['region_name'] = $v['region_name'];
			$data[$k]['ckey'] = $k;
		}
		return $data;
	}

	//区县
	public function getRegionD($pkey,$ckey){
		$region_array = $this->getRegion();
		$data = array();
		foreach ($region_array[$pkey]['city'][$ckey]['district'] as $k => $v) {
			$data[$k]['region_id'] = $v['region_id'];
			$data[$k]['region_name'] = $v['region_name'];
			$data[$k]['dkey'] = $k;
		}
		return $data;
	}

}
