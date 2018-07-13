<?php
class BackOrderMapper extends FieldMapper
{

	public function getWmsHistory($data)
	{
		$data = $this->replace($data, 1);
		return $data;
	}

	public function backList($data)
	{
		$reList = $data['reList'] = $this->replace($data['reList'], 1);
		foreach($reList as $re_k => $re_v){
			if($reList[$re_k]['backGoodsVoList']){
			$backGoodsVoList = $reList[$re_k]['backGoodsVoList'];
				foreach($backGoodsVoList as $key=>$val){
					$reList[$re_k]['backGoodsVoList'][$key] = $this->replace($val);
				}
			}
		}

		$data['reList'] = $reList;

		return $data;
	}


	public function detail($data)
	{
		if($data['seller_suggest_gallery']){
			$data['seller_suggest_gallery']        = $this->replace($data['seller_suggest_gallery'],1);
		}
		if($data['buyer_suggest_gallery']){
			$data['buyer_suggest_gallery']        = $this->replace($data['buyer_suggest_gallery'],1);
		}
		$data['backAddress']     = $this->replace($data['backAddress']);
		$data['backGoodsVoList'] = $this->replace($data['backGoodsVoList'], 1);
		if($data['backLogList']) {
			$data['backLogList'] = $this->replace($data['backLogList'], 1);
		}
		if($data['customSugg']){
			$data['customSugg']      = $this->replace($data['customSugg']);
		}
		if($data['suppSugg']){
			$data['suppSugg']        = $this->replace($data['suppSugg']);
		}


		$data = $this->replace($data);
		return $data;
	}

}
