<?php
class CommonController
{

	private $fileCache;

	public function  __construct()
	{
		include_once(ROOT_PATH . 'qdapi/lib/FileCacheController.php');
		$this->fileCache = new FileCacheController();
	}

	public function Cget($name){
		return $this->fileCache->get($name);
	}

	public function Cset($name, $value, $expire = null){
		return $this->fileCache->set($name, $value, $expire = null);
	}

	public function Crm($name){
		return $this->fileCache->rm($name);
	}

	public function Cclear(){
		return $this->fileCache->claer();
	}

	//版本号比较
	public function CompareVersion($version,$operator = 'ge'){
		return version_compare( $this->version,$version,$operator);
	}
}

