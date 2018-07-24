<?php
/**
 *  公用方法
 */


class Response
{
    /**
     * 获取输出结果
     *
     * @param $data 返回数据
     * @param int $code 返回状态代码
     * @param string $message   返回信息描述
     * @return mixed|string
     */
    public static function render($data, $code = 200, $message = 'OK') {
        header( 'Content-type: application/json; charset=UTF-8' );
		//$return = array('code' => $code, 'message' => $message, 'data'=>(is_array($data) || is_object($data) ? $data : $data) );
		$return = array('code' => $code, 'message' => $message, 'data'=>$data);
		echo json_encode($return);
        exit;
	}


	/**
	 * 获取出错信息，返回出错代码
	 *
	 * @param  $exception 异常对象
     * @return mixed|string
	 */
	public static function exception($exception) {
        return self::render(array(), $exception->getCode(), $exception->getMessage());
	}
	

}