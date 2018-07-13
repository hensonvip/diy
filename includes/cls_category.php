<?php
/**
 * 商品模块
 * @2016-10-26 cyq
 */

if (!defined('IN_ECS'))
{
	die('Hacking attempt');
}

class cls_category
{
	protected $_db                = null;
	protected $_tb_category          = null;
	protected $_tb_sku   		  = null;
	protected $_tb_brand		  = null;
	protected $_tb_gallery		  = null;
	protected $_tb_category_description		  = null;
	protected $_tb_category_attr	  = null;
	protected $_price_decimal     = 1;
	protected $_now_time          = 0;
	protected $_mc_time			  = 0;
	protected $_plan_time		  = 0;
	protected $_mc				  = null;
	protected static $_instance   = null;
	public static $_errno = array(
		1 => '操作成功',
		2 => '参数错误',
		3 => '分类不存在',
	);

	function __construct()
	{
		$this->_tb_category	= $GLOBALS['ecs']->table('category');
		// $this->_price_decimal	 = PRICE_DECIMAL;
		$this->_now_time         = time();
		$this->_plan_time 		 = 3600*24*15;
	}

	public static function getInstance()
	{
		if (self::$_instance === null)
		{
			$instance = new self;
			self::$_instance = $instance;
		}
		return self::$_instance ;
	}

  /**
   * 获得指定分类同级的所有分类以及该分类下的子分类
   *
   * @access  public
   * @param   integer     $cat_id     分类编号
   * @param   boolean     $has_id     返回的数组键值是否含有分类ID
   * @return  array
   */
  function get_categories_tree($cat_id = 0, $has_id = true, $supplier_id = 0)
  {
		// 多店 Yip 20170822
		// $cat_nameimg = 'cat_nameimg';
		$cat_nameimg = 'type_img';
		if($supplier_id > 0){
			$this->_tb_category = $GLOBALS['ecs']->table('supplier_category');
			$cat_nameimg = 'cat_pic';
		}
		
    if ($cat_id > 0)
    {
      $sql = 'SELECT parent_id FROM ' . $this->_tb_category . " WHERE cat_id = '$cat_id'";
      $parent_id = $GLOBALS['db']->getOne($sql);
    }
    else
    {
      $parent_id = 0;
    }

    /*
     判断当前分类中全是是否是底级分类，
     如果是取出底级分类上级分类，
     如果不是取当前分类及其下的子分类
    */
    $sql = 'SELECT count(*) FROM ' . $this->_tb_category . " WHERE parent_id = '$parent_id' AND is_show = 1";
    if ($GLOBALS['db']->getOne($sql) || $parent_id == 0)
    {
      /* 获取当前分类及其子分类 */
      $sql = 'SELECT cat_id,cat_name ,parent_id,is_show,  ' .$cat_nameimg.
             ' FROM ' . $this->_tb_category .
             "WHERE parent_id = '$cat_id' AND is_show = 1  ORDER BY sort_order ASC, cat_id ASC";

      $res = $GLOBALS['db']->getAll($sql);

      foreach ($res AS $key => $row)
      {
				// print_r($row);
        if ($row['is_show'])
        {
          if($has_id){
            $cat_arr[$row['cat_id']]['id']   = $row['cat_id'];
            $cat_arr[$row['cat_id']]['supplier_id']   = $supplier_id;
            $cat_arr[$row['cat_id']]['name'] = $row['cat_name'];
            $cat_arr[$row['cat_id']]['cat_nameimg'] = isset($row[$cat_nameimg])?DATA_DIR.'/categorylogo/'.$row[$cat_nameimg]:'';
            $cat_arr[$row['cat_id']]['url']  = build_uri('category', array('cid' => $row['cat_id']), $row['cat_name']);
						

						$res = $this->get_flash_img($row['cat_id']);
						$cat_arr[$row['cat_id']]['cat_ad'] = array();
						
						if($res){
							$cat_arr[$row['cat_id']]['cat_ad'] = $res;
						}
						
            if (isset($row['cat_id']) != NULL){
              $cat_arr[$row['cat_id']]['cat_id'] = $this->get_child_tree($row['cat_id'],'',$supplier_id);
            }
          }else{
            $cat_arr[$key]['id']   = $row['cat_id'];
						$cat_arr[$key]['supplier_id']   = $supplier_id;
            $cat_arr[$key]['name'] = $row['cat_name'];
            $cat_arr[$key]['cat_nameimg'] = !empty($row[$cat_nameimg])?DATA_DIR.'/categorylogo/'.$row[$cat_nameimg]:'';
            $cat_arr[$key]['url']  = build_uri('category', array('cid' => $row['cat_id']), $row['cat_name']);

						$res = $this->get_flash_img($row['cat_id']);
						// print_r($res);
						$cat_arr[$key]['cat_ad'] = array();
						if($res){
							$cat_arr[$key]['cat_ad'] = $res;
						} 
						
            if (isset($row['cat_id']) != NULL){
              $cat_arr[$key]['cat_id'] = $this->get_child_tree($row['cat_id'],$has_id,$supplier_id);
            }
          }
        }
      }
    }
		
		// 多店 Yip 20170822
		// $cat_arr['supplier_id'] = $supplier_id;
		// $cat_arr['cat_id'] = $cat_id;

    if(isset($cat_arr))
    {
			// print_r($cat_arr);
      return $cat_arr;
    }
  }

	// 获取所有上级别 Yip 20180122
	function get_parent_tree($cat_id = 0, $has_id = true, $supplier_id = 0)
  {
		// 多店 Yip 20170822
		$cat_nameimg = 'cat_nameimg';
		$cat_nameimg = 'type_img';
		if($supplier_id > 0){
			$this->_tb_category = $GLOBALS['ecs']->table('supplier_category');
			$cat_nameimg = 'cat_pic';
			// $cat_nameimg = 'type_img';
		}
		
		$tree = function($cat_id,$cat_nameimg) use (&$tree){ //闭包自循环
			$sql = 'SELECT cat_id, cat_name, parent_id, is_show,parent_id,' .$cat_nameimg.' FROM ' . $this->_tb_category . " WHERE cat_id = '$cat_id' AND is_show = 1 ";
			$three_arr[]= $arr = $GLOBALS['db']->getRow($sql) ;
			if($arr['parent_id']){
				$three_arr=array_merge($three_arr,$tree($arr['parent_id'],$cat_nameimg));
			}
			return $three_arr;
		};
		
		$arr = $tree($cat_id,$cat_nameimg);
		krsort($arr);
		return $arr;
	}
	
  function get_child_tree($tree_id = 0, $has_id = true, $supplier_id = 0)
  {
		// 多店 Yip 20170822
		$cat_nameimg = 'cat_nameimg';
		$cat_nameimg = 'type_img';
		if($supplier_id > 0){
			$this->_tb_category = $GLOBALS['ecs']->table('supplier_category');
			$cat_nameimg = 'cat_pic';
			// $cat_nameimg = 'type_img';
		}
	
    $three_arr = array();
    $sql = 'SELECT count(*) FROM ' . $this->_tb_category . " WHERE parent_id = '$tree_id' AND is_show = 1 ";
    if ($GLOBALS['db']->getOne($sql) || $tree_id == 0)
    {
      $child_sql = 'SELECT cat_id, cat_name, parent_id, is_show,  ' .$cat_nameimg.
                   ' FROM ' . $this->_tb_category .
                   "WHERE parent_id = '$tree_id' AND is_show = 1 ORDER BY sort_order ASC, cat_id ASC";
      $res = $GLOBALS['db']->getAll($child_sql);
      foreach ($res AS $key => $row)
      {
				// print_r(!empty($row[$cat_nameimg])?DATA_DIR.'/categorylogo/'.$row[$cat_nameimg]:'');
        if ($row['is_show'])
          if($has_id) {
              $three_arr[$row['cat_id']]['id'] = $row['cat_id'];
              $three_arr[$row['cat_id']]['supplier_id'] = $supplier_id;
              $three_arr[$row['cat_id']]['name'] = $row['cat_name'];
              $three_arr[$row['cat_id']]['cat_nameimg'] = !empty($row[$cat_nameimg])?DATA_DIR.'/categorylogo/'.$row[$cat_nameimg]:'';
              $three_arr[$row['cat_id']]['url'] = build_uri('category', array('cid' => $row['cat_id']), $row['cat_name']);

              if (isset($row['cat_id']) != NULL) {
                $three_arr[$row['cat_id']]['cat_id'] = $this->get_child_tree($row['cat_id']);
              }
            }else{
              $three_arr[$key]['id'] = $row['cat_id'];
						  $three_arr[$key]['supplier_id']   = $supplier_id;
              $three_arr[$key]['name'] = $row['cat_name'];
              $three_arr[$key]['cat_nameimg'] = !empty($row[$cat_nameimg])?DATA_DIR.'/categorylogo/'.$row[$cat_nameimg]:'';
              $three_arr[$key]['url'] = build_uri('category', array('cid' => $row['cat_id']), $row['cat_name']);

              if (isset($row['cat_id']) != NULL) {
                $three_arr[$key]['cat_id'] = $this->get_child_tree($row['cat_id'],$has_id);
              }
            }
        }
      }
      return $three_arr;
    }
	
	function get_flash_img($cat_id) 
	{ 
		$sql="select * from ".$GLOBALS['ecs']->table("cat_flashimg") ." where cat_id='$cat_id' order by sort_order"; 
		$res_fimg=$GLOBALS['db']->query($sql); 
		$fimg_list=array(); 
		while($row_fimg=$GLOBALS['db']->fetchRow($res_fimg)) 
		{ 
			$fimg_list[$row_fimg['img_id']]=$row_fimg; 
			$fimg_list[$row_fimg['img_id']]['img_url']=  DATA_DIR .'/catflashimg/'.$row_fimg['img_url']; 
			$fimg_list[$row_fimg['img_id']]['img_link']= $row_fimg['href_url']; 
			$fimg_list[$row_fimg['img_id']]['img_title']=trim($row_fimg['img_title']); 
			$fimg_list[$row_fimg['img_id']]['img_desc']=trim($row_fimg['img_desc']); 
		} 
		return array_values($fimg_list); 
	} 
}
