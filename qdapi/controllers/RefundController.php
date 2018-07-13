<?php
/**
 * 支付平台的退款异步通知接口
 *
 * @version v1.0
 * @create 2015-08-06
 */

include_once(ROOT_PATH.'app/app_pay.php');
include_once(ROOT_PATH.'app/app_sms.php');
require_once(ROOT_PATH . 'includes/cls_order.php');
require_once(ROOT_PATH . 'includes/cls_refund.php');
require_once(ROOT_PATH . '/includes/cls_log.php');


class RefundController extends ApiController
{
	//页面跳转退款
	public function updateRefundStatus()
	{

		$config = array(
			'type'=>'file',
			'log_path'=> ROOT_PATH . '/data/logs/cron/'
		);

		$cls_refund = new cls_refund();
		$cls_order = new cls_order();

		$logger = new Logger($config);

		//$refund_serial_no = isset($_REQUEST['refund_serial_no']) ? trim($_REQUEST['refund_serial_no']) : '';
		$order_sn     = isset($_REQUEST['order_sn']) ? trim($_REQUEST['order_sn']) : '';
		$refund_sn    = isset($_REQUEST['refund_sn']) ? trim($_REQUEST['refund_sn']) : '';
		$refund_money = isset($_REQUEST['refund_money']) ? trim($_REQUEST['refund_money']) : '';
		$refund_status= isset($_REQUEST['refund_status']) ? trim($_REQUEST['refund_status']) : '';
		$api_key      = isset($_REQUEST['api_key']) ? trim($_REQUEST['api_key']) : '';
		$api_sign     = isset($_REQUEST['api_sign']) ? trim($_REQUEST['api_sign']) : '';
		$refund_detail     = isset($_REQUEST['refund_detail']) ? trim($_REQUEST['refund_detail']) : '';

		$logger->writeLog('退款信息'.json_encode($_REQUEST), 'info', 'refund');

		//if(empty($refund_serial_no)){
			//throw new ActivityException(1019);
		//}

		if(empty($order_sn)){
			throw new ActivityException(1020);
		}

		if(empty($refund_money) || $refund_money < 0){
			throw new ActivityException(1021);
		}

		if(empty($refund_status)){
			throw new ActivityException(1022);
		}

		if(empty($api_key)){
			throw new ActivityException(1023);
		}

		if(empty($api_sign)){
			throw new ActivityException(1024);
		}

		if (empty($refund_sn)) {
			throw new ActivityException(1018);
		}

		$refund = $cls_refund->getRefundBySn($refund_sn);

		if(!$refund){
			throw new ActivityException(1014);
		}



		if ($refund_status) {

			$cls_refund = new cls_refund();
			
			//如果已经完成退款不处理: 1=等待结算退款 2=退款已提交 3=退款已到账 4=退款失败
			if ($refund['refund_status'] == RFS_RECEIVED) {
				Response::render(array(), 0, 'OK');
			}

			$result = $cls_refund->updateRefundStatus($refund['id'], $refund_money, $refund_sn);



			if ($result['code'] == '0') {

				$order = $cls_order->getOrderBySn($refund['order_sn']);
				if ($order) {
					//发送退款成功短信
					app_sms::sendText($order['data']['mobile'], 'refund.agree',array('order_sn'=>$refund['order_sn'],'consignee'=>$order['data']['consignee'], 'refund_money'=>$refund_money));
				}
				$msg = $refund['id'] . '更新退款状态成功'.$result['msg'].'；退款信息'.json_encode($_REQUEST);
				$logger->writeLog($msg, 'info', 'refund');
				Response::render(array(), 0, 'OK');

			} else {
				$msg = $refund['id'] . '更新退款状态失败'.$result['msg'].'；退款信息'.json_encode($_REQUEST);
				$logger->writeLog($msg, 'info', 'refund');
				Response::render(array('msg'=>$result['msg']), 9999, 'False');

			}
		}

//		$msg = $pay->getPayMsg($result);
//		$this->output($msg);

	}

}
