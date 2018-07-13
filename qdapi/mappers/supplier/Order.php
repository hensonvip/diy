<?php
class OrderMapper extends FieldMapper
{
	public function detail($data)
	{
		$data['goods_list'] = $this->replace($data['goods_list'], 1);
		$data['action_list'] = $this->replace($data['action_list'], 1);
		$data['order_customs'] = $this->replace($data['order_customs']);
		$data = $this->replace($data);
		return $data;
	}

	public function query($data)
	{
		foreach ($data as &$v)
		{
			if (isset($v['goods_list']) && $v['goods_list'])
			{
				$v['goods_list'] = $this->replace($v['goods_list'], 1);
			}
			else
			{
				$v['goods_list'] = array();
			}
		}
		unset($v);
		$data = $this->replace($data, 1); 
		return $data;
	}

	public function export($data)
	{
		$data = $this->replace($data, 1);
		return $data;
	}

	public function idCardList($data)
	{
		$data['idCards'] = $this->replace($data['idCards'],1);
		return $data;
	}
}
