<?php

/**
 * 商品分类接口
 *
 * @version v1.0
 * @create 2016-10-26
 * @author cyq
 */

require_once(ROOT_PATH . 'includes/cls_category.php');

class CategoryController extends ApiController
{
	public function __construct()
	{

		parent::__construct();
		$this->data = $this->input();
		$this->category     = cls_category::getInstance();
		$config = array(
			'type'=>'file',
			'log_path'=> ROOT_PATH . '/data/logs/api/category/'
		);
		$this->logger = new Logger($config);
	}

	public function query(){

		$require_fields = array('cat_id','supplier_id');
		foreach($require_fields as $v)
		{
			if(!isset($this->data[$v])) // || empty($this->data[$v])
			{
				$this->error("缺失必选参数 ({$v})，请参考API文档", '-1');
			}else{
				if(strpos($v,'_id')){
					$$v = intval(trim($this->data[$v]));
				}else{
					$$v = stripslashes(trim($this->data[$v]));
				}
			}
		}

		$test = $this->input('test', 0);
		if($this->CompareVersion('1.0.1','lt')){
			//echo $this->version;
			$category_list = $this->category->get_categories_tree($cat_id,false,$supplier_id);
			sort($category_list);
			$this->success($category_list);
			exit();
		}

		$category_list = $this->category->get_categories_tree($cat_id,false,$supplier_id);
		sort($category_list);

		$sql = "SELECT brand_id,brand_name,brand_logo FROM ". $GLOBALS['ecs']->table('brand')." where is_show = 1 order by sort_order asc";
		$brand_list = $GLOBALS['db']->getAll($sql);
		foreach($brand_list as $k=>$v){
			$brand_list[$k]['brand_logo'] = $v['brand_logo']?'data/brandlogo/'.$v['brand_logo']:'data/brandlogo/437428736611050860.jpg';
		}

		$result = array('category_list'=>$category_list,'brand_list'=>$brand_list);

		$this->success($result);

	}

	/* 获取分类下的品牌 */
	public function getCatBrand ()
	{

	}

	/* 获取指定分类下的子分类 */
	public function getSubCat()
	{
		$cat_id = $this->input('cat_id', 0, 'intval');
		if(!$cat_id){
			$this->error('缺少必要参数！');
		}

		$category_list = $this->category->get_categories_tree($cat_id);
		$this->success($category_list);
	}

	/* 获取所有级分类 */
	public function getCatIds(){
		$require_fields = array('cat_id','supplier_id');
		foreach($require_fields as $v)
		{
			if(!isset($this->data[$v])) // || empty($this->data[$v])
			{
				$this->error("缺失必选参数 ({$v})，请参考API文档", '-1');
			}else{
				if(strpos($v,'_id')){
					$$v = intval(trim($this->data[$v]));
				}else{
					$$v = stripslashes(trim($this->data[$v]));
				}
			}
		}
		$category_list = $this->category->get_parent_tree($cat_id,false,$supplier_id);
		$this->success($category_list);
	}

	/**
	 * @description 获取某个地区下的地区列表
	 * @param integer type 地区级别
	 * @param integer parent 上级的地址ID
	 * @return array regions
	 */
	public function getRegion ()
	{
		$type   = !empty($this->data['type'])   ? intval($this->data['type'])   : 0;
		$parent = !empty($this->data['parent']) ? intval($this->data['parent']) : 0;

		$regions = get_regions($type, $parent);

		$this->success($regions);
	}

	private function log($msg, $level = 'info')
	{
		$this->logger->writeLog($msg, $level, 'category');
	}
}
