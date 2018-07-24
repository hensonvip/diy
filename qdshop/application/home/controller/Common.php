<?php
namespace app\home\controller;
use think\Controller;
use think\Cache;
use think\Config;
use think\Cookie;
use think\Log;

//公共类
class Common extends Controller
{

	public $webUrl ;
	public $webApiUrl ;
	private $apiKey;
	private $apiSecret;
	private $cus_param = array('device'=>'pc','timestamp'=>'','version'=>'v1.0.3');

	public $site_title;
	public $site_keywords;
	public $site_description;

	public $display_html ;

    public function __construct()
    {
  		$config = Config::get('qdshop');
  		// print_r($config);die();
  		$this->webUrl = $config['webUrl']?:$this->error('请先设置配置信息');
  		$this->webApiUrl = $config['webApiUrl']?:$this->error('请先设置配置信息');
  		$this->cus_param['version'] = $config['cus_param']['version']?:$this->error('请先设置配置信息');
  		$this->apiKey = $config['apiKey']?:$this->error('请先设置配置信息');
  		$this->apiSecret = $config['apiSecret']?:$this->error('请先设置配置信息');
  		// $this->site_title = $config['site_title']?:$this->error('请先设置配置信息');
  		// $this->site_keywords = $config['site_keywords']?:$this->error('请先设置配置信息');
  		// $this->site_description = $config['site_description']?:$this->error('请先设置配置信息');
  		$this->_nowtime         = time();

      	//商店配置
      	$url = "index/getShopConfig";
      	$result = $this->curlGet($url);
      	$result = json_decode($result,true);//json转数组
      	$this->site_title = $result['data']['shop_title'];
      	$this->site_keywords = $result['data']['shop_keywords'];
      	$this->site_description = $result['data']['shop_desc'];

      	parent::__construct();
      	$this->seo();
      	$this->head();

      	$this->designer_id = input('designer_id','0','intval');//设计师用户ID
      	$this->user_id = session('user_id') ? session('user_id') : 0;
      	
  	  	//个人信息
      	$url = "user/getUserInfo";
      	$data = array();
      	$data['user_id'] = $this->designer_id ? $this->designer_id : $this->user_id;
      	$data['login_user_id'] = $this->user_id;
      	$result = $this->curlGet($url,$data);
      	$result = json_decode($result,true);//json转数组
    	// print_r($result);die;
      	$this->assign('user',$result['data']);

	  	//推荐设计师
    	$url = "user/getRecommendUsers";
    	$data = array();
    	$data['login_user_id'] = $this->user_id;
    	$result = $this->curlGet($url,$data);
    	$result = json_decode($result,true);//json转数组
  		// print_r($result);die;
    	$this->assign('recommend_users',$result['data']);

      	//系统设置
      	$url = "global/getSysCfg";
      	$result = $this->curlGet($url);
      	$result = json_decode($result,true);//json转数组
      	$this->sys_cfg = $result['data'];
      	$this->assign('sys_cfg',$this->sys_cfg);

      	// 购物车
      	$url = "cart/get_cart_goods";
      	$data = array();
      	$data['user_id'] = $this->user_id;
      	$result = $this->curlPost($url,$data);
      	$result = json_decode($result,true);//json转数组
      	$this->assign('cart_info',$result['data']);

  		$this->assign('user_id',$this->user_id);

  		$this->assign('class','index');
    }

	/**
  	* 获取分页的HTML内容
  	* @param integer $page 当前页
  	* @param integer $pages 总页数
  	* @param string $url 跳转url地址    最后的页数以 '&page=x' 追加在url后面
  	* @return string HTML内容;
  	*/
	public function getPage($page, $pages,$_pageNum = 5){
		//最多显示多少个页码 $_pageNum = 5
	  	//当前页面小于1 则为1
	  	$page = $page<1?1:$page;
	  	//当前页大于总页数 则为总页数
	  	$page = $page > $pages ? $pages : $page;
	  	//页数小当前页 则为当前页
	  	$pages = $pages < $page ? $page : $pages;

	  	//计算开始页
	  	$_start = $page - floor($_pageNum/2);
	  	$_start = $_start < 1 ? 1 : $_start;
	  	//计算结束页
	  	$_end = $page + floor($_pageNum/2);
	  	$_end = $_end>$pages? $pages : $_end;

	  	//当前显示的页码个数不够最大页码数，在进行左右调整
	  	$_curPageNum = $_end-$_start+1;
	  	//左调整
	  	if($_curPageNum<$_pageNum && $_start>1){
	   		$_start = $_start - ($_pageNum-$_curPageNum);
	   		$_start = $_start < 1 ? 1 : $_start;
	   		$_curPageNum = $_end-$_start+1;
	  	}
	  	//右边调整
	  	if($_curPageNum<$_pageNum && $_end<$pages){
	   		$_end = $_end + ($_pageNum-$_curPageNum);
	   		$_end = $_end>$pages? $pages : $_end;
	  	}
		return array('page'=>$page,'start'=>$_start,'end'=>$_end,'pages'=>$pages);
	  	// $_pageHtml = '<ul class="pagination">';
	  	// if($page>1){
	  	// 		$_pageHtml .= '<li><a  title="上一页" href="'.$url.'&page='.($page-1).'">«</a></li>';
	  	// }
	  	// for ($i = $_start; $i <= $_end; $i++) {
		// 		if($i == $page){
		// 			$_pageHtml .= '<li class="active"><a>'.$i.'</a></li>';
		// 		}else{
		// 			$_pageHtml .= '<li><a href="'.$url.'&page='.$i.'">'.$i.'</a></li>';
		// 		}
	  	// }

	  	// if($page<$_end){
	   	// 		$_pageHtml .= '<li><a  title="下一页" href="'.$url.'&page='.($page+1).'">»</a></li>';
	  	// }
	  	// $_pageHtml .= '</ul>';
	  	// echo $_pageHtml;
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

	public function head(){
		//分类
		$api = "category/query";
		$data = array();
		$data['cat_id'] = 0;
		$data['supplier_id'] = -1;
		$result = $this->curlGet($api,$data);
		// print_r($result);die;
		$result = json_decode($result,true);
		// print_r($result);die();
		$result['data']['b'] = array();
		for($i=0;$i<ceil(count($result['data']['brand_list']));$i++){
			  $result['data']['b'][] = array_slice($result['data']['brand_list'], $i * 6 ,6);
		}
		//print_r($result['data']['b'] );die();
		$this->assign('cat_list',$result['data']);

		//搜索
		$api = "goods/getHotSearch";
		$result = $this->curlGet($api);
		$result = json_decode($result,true);
		//print_r($result);die();
		$this->assign('search_keyword',$result['data']);

		//导航
		$api = "index/get_nav";
		$result = $this->curlGet($api);
		$result = json_decode($result,true);
		//print_r($result);die();
		$this->assign('nav',$result['data']);


	}

    public function curlGet($api,$param=array()){
    	$CommonFun = new \app\home\library\Common();
    	$this->cus_param['timestamp'] = $this->getMillisecond();
    	$api = array('act'=>$api);
    	$arr = array_merge($param,$this->cus_param,array('api_key'=>$this->apiKey),array('api_key'=>$this->apiKey));
    	$arr2 = array_merge($api,$param,$this->cus_param,array('api_key'=>$this->apiKey));
    	$sign = $this->_setToken($this->apiSecret,$arr);
    	$url = $this->webApiUrl."?".$this->_loopArray($arr2)."&api_sign=".utf8_decode($sign);
    	// echo $url;die;
		//\Think\Log::record($url,'log');
    	return $CommonFun->curlGet($url);
    }

    public function curlPost($api,$param,$file=array()){
    	$CommonFun = new \app\home\library\Common();
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

    //school - 省份
    public function getSchoolP(){
        $result = Cache::get('province_data');
        if(empty($result)){
            $url = "school/get_province";
            $result = $this->curlGet($url);
            $result = json_decode($result,true);//json转数组
            $result = $result['data']['list'];
            //print_r($result);die;
            cache('province_data', $result, 60*60*24*7);
        }

        return $result;
    }

    //school - 城市
    public function getSchoolC($sh_province){
        $data['sh_province'] = $sh_province;
        $url = "school/get_city";
        $result = $this->curlGet($url,$data);
        $result = json_decode($result,true);//json转数组
        $result = $result['data']['list'];

        return $result;
    }

    //school - 学校
    public function getSchoolS($sh_city){
        $data['sh_city'] = $sh_city;
        $url = "school/get_school";
        $result = $this->curlGet($url,$data);
        $result = json_decode($result,true);//json转数组
        $result = $result['data']['list'];

        return $result;
    }

	//写入历史记录
	public function setHistory($goods_id,$type='goods'){
		$name = $type.'Histrory';
		$cookie =$this->getHistory($type);
		if($cookie){
			if(is_array($cookie)){
				array_push($cookie,$goods_id);
				$cookie = implode('|',array_unique($cookie));
				Cookie::forever($name,$cookie);
				return true;
			}else{
				return false;
			}
		}else{
			$cookie =  implode('|',array($goods_id));
			Cookie::forever($name,$cookie);
			return true;
		}
	}

	//读取历史记录
	public function getHistory($type='goods'){
		$name = $type.'Histrory';
		$cookie = cookie($name);
		if($cookie){
			if(strpos($cookie,'|')===false){
				return array($cookie);
			}else{
				$cookie = explode('|',$cookie);
				if(is_array($cookie)){
					return $cookie;
				}
			}
		}

		return false;
	}

	/**
	 * 出生日期表单
	 */
	public function html_select_date($arr)
    {
        $pre = $arr['prefix'];
        if (isset($arr['time']))
        {
            if (intval($arr['time']) > 10000)
            {
                $arr['time'] = gmdate('Y-m-d', $arr['time'] + 8*3600);
            }
            $t     = explode('-', $arr['time']);
            $year  = strval($t[0]);
            $month = strval($t[1]);
            $day   = strval($t[2]);
        }
        $now = gmdate('Y', $this->_nowtime);
        if (isset($arr['start_year']))
        {
            if (abs($arr['start_year']) == $arr['start_year'])
            {
                $startyear = $arr['start_year'];
            }
            else
            {
                $startyear = $arr['start_year'] + $now;
            }
        }
        else
        {
            $startyear = $now - 3;
        }

        if (isset($arr['end_year']))
        {
            if (strlen(abs($arr['end_year'])) == strlen($arr['end_year']))
            {
                $endyear = $arr['end_year'];
            }
            else
            {
                $endyear = $arr['end_year'] + $now;
            }
        }
        else
        {
            $endyear = $now + 3;
        }

        $out = "<select name=\"{$pre}Year\" class=\"select\">";
        for ($i = $startyear; $i <= $endyear; $i++)
        {
            $out .= $i == $year ? "<option value=\"$i\" selected>$i</option>" : "<option value=\"$i\">$i</option>";
        }
        if ($arr['display_months'] != 'false')
        {
            $out .= "</select>&nbsp;<select name=\"{$pre}Month\" class=\"select\">";
            for ($i = 1; $i <= 12; $i++)
            {
                $out .= $i == $month ? "<option value=\"$i\" selected>" . str_pad($i, 2, '0', STR_PAD_LEFT) . "</option>" : "<option value=\"$i\">" . str_pad($i, 2, '0', STR_PAD_LEFT) . "</option>";
            }
        }
        if ($arr['display_days'] != 'false')
        {
            $out .= "</select>&nbsp;<select name=\"{$pre}Day\" class=\"select\">";
            for ($i = 1; $i <= 31; $i++)
            {
                $out .= $i == $day ? "<option value=\"$i\" selected>" . str_pad($i, 2, '0', STR_PAD_LEFT) . "</option>" : "<option value=\"$i\">" . str_pad($i, 2, '0', STR_PAD_LEFT) . "</option>";
            }
        }

        return $out . '</select>';
    }

    /**
     * 生成唯一ID
     */
    function uuid($prefix = '')
    {
        $chars = md5(uniqid(mt_rand(), true));
        $uuid  = substr($chars,0,8) . '-';
        $uuid .= substr($chars,8,4) . '-';
        $uuid .= substr($chars,12,4) . '-';
        $uuid .= substr($chars,16,4) . '-';
        $uuid .= substr($chars,20,12);
        return $prefix . $uuid;
    }
}
