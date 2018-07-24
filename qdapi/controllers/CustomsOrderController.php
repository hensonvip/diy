<?php

/**
 * 报关订单接口
 * 
 * @version v1.0
 * @create 2015-07-24
 * @author veapon(veapon88@gmail.com)
 */

require_once(ROOT_PATH . 'includes/cls_customs_order.php');
class CustomsOrderController extends ApiController
{
	
	public function updatePayStatus()
	{
		$where['order_sn'] = intval($this->input('order_sn', 0));
		$where['customs_id'] = intval($this->input('customs_id'));
		if (empty($where['order_sn']) || empty($where['customs_id']))
		{
			$this->error("参数错误", 400);
		}
		$where['customs_type'] = 1;

		// 1为待申报，2为待修改申报，3为申报中，4为申报成功，5为申报失败
		$status = intval($this->input('status'));
		if (empty($status) || !in_array($status, array(1,2,3,4,5)))
		{
			$this->error('状态码错误', 400);
		}
		switch($this->input('status'))
		{
			case 1:
			case 2:
				$data['pay_customs'] = COS_PENDING;
				break;
			case 3:
				$data['pay_customs'] = COS_PUSHED;
				break;
			case 4:
				$data['pay_customs'] = COS_SUCCESS;
				break;
			case 5:
				$data['pay_customs'] = COS_FAILED;
				$data['pay_note'] = $this->input('msg', '');
				break;
			default:
				$this->error('状态码错误', 400);
		}

		$this->updateStatus($where, $data); 
	}

	public function updateOrderStatus()
	{
		$where['order_sn'] = intval($this->input('order_sn', 0));
		$where['customs_id'] = intval($this->input('customs_id'));
		if (empty($where['order_sn']) || empty($where['customs_id']))
		{
			$this->error("参数错误", 400);
		}
		$where['customs_type'] = 1;

		$code = $this->input('code');
		if ($code != 'C01')
		{
			$data['order_customs'] = COS_FAILED;
			$data['order_note'] = $this->input('msg', '');
		}
		else
		{
			$data['order_customs'] = COS_SUCCESS;
		}

		$this->updateStatus($where, $data); 
	}

	public function updateShippingStatus()
	{
		$sn = intval($this->input('order_sn', 0));
		$data['shipping_customs'] = intval($this->input('status', 0));
		$data['shipping_notes'] = addslashes($this->input('notes', ''));

		$this->updateStatus($sn, $data); 
	}

	private function updateStatus($where, $data)
	{
		//$where = array('order_sn'=>$sn);
		$this->logger->writeLog('=================== customs order async api processing ================', 'INFO', 'customs_order');
		$this->logger->writeLog('where: '.json_encode($where), 'INFO', 'customs_order');
		$this->logger->writeLog('data: '.json_encode($data), 'INFO', 'customs_order');
		$result = cls_customs_order::getInstance()->update($where, $data);
		$this->logger->writeLog('result: '.json_encode($result), 'INFO', 'customs_order');
		if (!$result)
		{
			$this->error('Update failed');
		}
		$this->logger->writeLog('=================== customs order async api finished ================', 'INFO', 'customs_order');
		
		Response::render(array('msg'=>'OK'), 0);

	}
}
