<?php
namespace hunuo\shop;

class payment {

    public $type; //传入 'pc','JSAPI','h5'

	private $gatewayUrl;
	private $postCharset;

    public function __construct() {
        if(!isset($this->type) || !in_array($this->type,array('pc','JSAPI','h5'))){
			return false;
		}
    }

	/**
     * 调起微信支付
     * @param $param 请求参数 （接口返回的数组）
     * @return 返回支付的链接
     */
    public function weixin($param) {
		if(!isset($param)){
			return false;
		}
		switch($this->type){
			//微信浏览器打开喔
			case 'JSAPI':
				return $param['prepay'];
				break;
			//返回直接打开的链接 （按要求拼接）
			case 'h5':
				return $param['url'];
				break;
			//返回扫码的链接
			case 'pc':
				return $param['url'];
				break;
		}
		return false;
    }

	/**
     * 调起支付宝支付
     * @param $param 请求参数 （接口返回的数组）
     * @return 返回支付的表单
     */
    public function alipay($param) {
    	return $param;

		/*if(!isset($param)){
			return false;
		}
		$this->gatewayUrl = 'https://openapi.alipay.com/gateway.do';
		$this->postCharset = 'utf-8';
		parse_str($param['prepay_id'],$totalParams);
		return $this->buildRequestForm($totalParams);*/
    }

	/**
     * 建立请求，以表单HTML形式构造（默认）
     * @param $para_temp 请求参数数组
     * @return 提交表单HTML文本
     */
	private function buildRequestForm($para_temp) {

		$sHtml = "<form id='alipaysubmit' name='alipaysubmit' action='".$this->gatewayUrl."?charset=".trim($this->postCharset)."' method='POST'>";
		while (list ($key, $val) = each ($para_temp)) {
			if (false === $this->checkEmpty($val)) {
				$val = str_replace("'","&apos;",$val);
				$sHtml.= "<input type='hidden' name='".$key."' value='".$val."'/>";
			}
        }

        $sHtml = $sHtml."<input type='submit' value='ok' style='display:none;'></form>";

		$sHtml = $sHtml."<script>document.forms['alipaysubmit'].submit();</script>";

		return $sHtml;
	}

	/**
	 * 校验$value是否非空
	 *  if not set ,return true;
	 *    if is null , return true;
	 **/
	private function checkEmpty($value) {
		if (!isset($value))
			return true;
		if ($value === null)
			return true;
		if (trim($value) === "")
			return true;

		return false;
	}


}

