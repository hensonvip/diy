<?php

/**
 * 控制器基类
 *
 * User: vincent.cao
 * Date: 14-9-10
 * Time: 下午2:37
 */
class BaseController
{
	private $req = array();

    public function  __construct()
    {
        if(!isset($_GET['debug']))
        {
            $this->_authVerify();
        }

	// 处理输入
	$req = array_merge($_GET, $_POST);
	$this->req = addslashes_deep($req);
    }

   
    /**
     * 获取$_GET, $_POST
     * 
     * @param $key 
     * @param $default
     * @return mixed
     * @create 2015-05-29
     */
    final protected function input($key = false, $default = NULL)
    {
	    if ($key === false)
	    {
	    	return $this->req;
	    }

	    if (isset($this->req[$key])) 
	    {
	    	return $this->req[$key];
	    }

	    if ($default !== NULL)
	    {
	    	return $default;	
	    }

	    return NULL;
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
        if (!isset($request['api_key']) || !isset($request['api_sign']) || empty($config[$request['api_key']]))
        {
            throw new ActivityException(1003);
        }
        $request_api_sign = $request['api_sign'];
        unset($request['api_sign']);

        $api_sign = $this->_setToken($config[$request['api_key']]['secret'], $request);

        if ($request_api_sign !== $api_sign)
        {
            throw new ActivityException(1003);
        }
        return true;
    }

    protected function _setToken($secret, $param)
    {
        $token = $secret;
        $token .= $this->_loopArrayToken($param);
        $token .= $secret;
//        echo $token;
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
        return stripslashes($token);
    }

    protected function _getImgDomain()
    {
//     	return 'http://public-mama-img.qiniudn.com/';
        return 'http://7mnpba.com1.z0.glb.clouddn.com/';
    }





}
