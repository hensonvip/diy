<?php
class DeliveryMapper extends FieldMapper
{
	public function detail($data)
	{
		$data['goods_list'] = $this->replace($data['goods_list'], 1);
		$data['abroad_wms_info'] = $this->replace($data['abroad_wms_info']);
		$data['home_wms_info'] = $this->replace($data['home_wms_info']);
		$data['order_customs'] = $this->replace($data['order_customs']);
		$data = $this->replace($data);
		return $data;
	}

	public function getByOrderId($data)
	{
		$data = $this->replace($data, 1);
		foreach ($data as $k=>$v)
		{
			$data[$k]['packageId'] = $k+1;
		}
		return $data;
	}

	public function unshipOrderList($data){
		$data = $this->replace($data, 1);
		foreach($data as $key=>$val){
			$data[$key]['goodsList'] = $this->replace($data[$key]['goodsList'], 1);
		}
		return $data;
	}
}
