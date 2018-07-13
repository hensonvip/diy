<?php
class StatisticException extends Exception {

	/**
	 * 错误信息数组
	 *
	 * @var array
	 */
	protected $_codeList = array(
	//		200 => 'OK',//服务器成功处理了请求
	//		201 => 'Created',//服务器资源已创建完毕(POST)
	//		202 => 'Accepted',//请求已接受， 但服务器尚未处理（异步处理）
	//		204 => 'Not Content',//请求成功，但返回的内容为空（既不返回内容，如更新成功或者删除资源成功后，根据是204判断操作成功；）
			301 => 'Moved Permanently',//请求的资源URL已移走。Response中应该包含一个Location URL, 说明资源现在所处的位置
			303 => 'See Other',//对于POST请求，它表示请求已经被处理，客户端可以接着使用GET方法去请求Location里的URI
			304 => 'Not Modified',//客户端的缓存资源是最新的， 要客户端使用缓存
			400 => 'Bad Request',//请求参数错误
			403 => 'Forbidden',//服务器拒绝请求
			404 => 'Not Found',//资源不存在
			405 => 'Method Not Allowed',//如delete一个不允许删除的订单
			406 => 'Not Acceptable',//服务端不支持客户端需要返回的数据格式，如客户端请求返回xml，但服务器不支持此格式返回时
			409 => 'Conflict',//通用冲突（如put一个状态已经变成不允许修改的订单时，可以返回此代码）
			412 => 'Precondition Failed',//post,put前置条件失败。修改订单时，Etag值已经过期（可能其他地方提交了更新，导致Etag被更新。不过是要在有使用此标签的地方）
			415 => 'Unsupported Media Type',//不支持的媒体类型，服务器无法理解或不支持客户端所发送的实体的内容类型
			500 => 'Internal Server Error',//内部服务器错误, 服务器遇到一个错误，使其无法为请求提供服务
			503 => 'Service Unavailable',//未提供此服务，服务器目前无法为请求提供服务，但过一段时间就可以恢复服务（如delete一个确实存在的订单时，服务器发送错误）

			1001 => 'Method Not Found',
			1002 => 'Class Not Found',
			1003 => 'Signature Verification Failed',
			);

	public function __construct($code, $message = null) {

		if (empty($code) || empty($this->_codeList) || false === array_key_exists($code, $this->_codeList)) {
			$code = -1;
			$message = '未定义异常信息';
		}

		if (empty($message)) {
			$message = $this->_codeList[$code];
		}

		parent::__construct($message, $code);
	}

	public function __call($method, $args = null) {
		$method = strtolower($method);
		if (true === method_exists($this, $method)) {
			$this->$method();
		} else {
			throw new Exception('方法不存在', 1000);
		}
	}

}