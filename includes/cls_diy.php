<?php
/**
 * diy模块
 */

if (!defined('IN_ECS'))
{
	die('Hacking attempt');
}

class cls_diy
{
	protected $_db                 = null;
    protected $_tb_goods_type      = null;
    protected $_tb_attribute       = null;
    protected $_tb_attribute_img   = null;
    protected $_tb_attribute_icon  = null;
    protected $_tb_attribute_color = null;
	protected $_tb_font_type       = null;
	protected $_tb_font		       = null;
	protected $_tb_graph_type      = null;
    protected $_tb_graph           = null;
    protected $_tb_mask            = null;
    protected $_tb_goods_gallery   = null;
	protected $_tb_diy             = null;
	protected $_now_time           = 0;
	protected $_plan_time		   = 0;
	protected static $_instance    = null;

	function __construct()
	{
        $this->user                = cls_user::getInstance();
        $this->_db                 = $GLOBALS['db'];
        $this->_tb_goods           = $GLOBALS['ecs']->table('goods');
        $this->_tb_goods_type      = $GLOBALS['ecs']->table('goods_type');
        $this->_tb_attribute       = $GLOBALS['ecs']->table('attribute');
        $this->_tb_goods_attr      = $GLOBALS['ecs']->table('goods_attr');
        $this->_tb_products        = $GLOBALS['ecs']->table('products');
        $this->_tb_attribute_img   = $GLOBALS['ecs']->table('attribute_img');
        $this->_tb_attribute_icon  = $GLOBALS['ecs']->table('attribute_icon');
        $this->_tb_attribute_color = $GLOBALS['ecs']->table('attribute_color');
        $this->_tb_font_type       = $GLOBALS['ecs']->table('font_type');
        $this->_tb_font            = $GLOBALS['ecs']->table('font');
        $this->_tb_graph_type      = $GLOBALS['ecs']->table('graph_type');
        $this->_tb_graph           = $GLOBALS['ecs']->table('graph');
        $this->_tb_mask            = $GLOBALS['ecs']->table('mask');
        $this->_tb_diy_file        = $GLOBALS['ecs']->table('diy_file');
        $this->_tb_bar_code        = $GLOBALS['ecs']->table('bar_code');
        $this->_tb_goods_gallery   = $GLOBALS['ecs']->table('goods_gallery');
        $this->_tb_diy             = $GLOBALS['ecs']->table('diy');
        $this->_now_time           = time();
        $this->_plan_time          = 3600*24*15;
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
     * 商品类型
     */
    function goods_type($type_id)
    {
        $sql = "SELECT * FROM " . $this->_tb_goods_type . " WHERE cat_id = '$type_id'";
        $goods_type = $this->_db->getRow($sql);
        return $goods_type;
    }

    /**
     * 款式
     */
    function style($type_id)
    {
        $sql = "SELECT * FROM " . $this->_tb_attribute . " WHERE cat_id = '$type_id' AND is_diy = 1 AND attr_name <> '颜色' limit 1";
        $style = $this->_db->getRow($sql);
        $sql = "SELECT attr_value_name, default_icon, select_icon, attr_id FROM " . $this->_tb_attribute_icon . " WHERE attr_id = " . $style['attr_id'] . " ORDER BY attr_icon_id ASC";
        $icon_list = $this->_db->getAll($sql);
        $style['icon_list'] = $icon_list;

        $sql = "SELECT * FROM " . $this->_tb_attribute . " WHERE cat_id = '$type_id' AND is_diy = 1 AND attr_name = '颜色' limit 1";
        $color = $this->_db->getRow($sql);
        $color_list= str_replace("\r\n", "\n", $color['attr_values']);
        $color_array = explode("\n", $color_list);

        foreach ($style['icon_list'] as $key => $value) {
            $color_file = $this->_db->getAll("SELECT * FROM " . $this->_tb_attribute_color . " WHERE attr_id = '$color[attr_id]'");
            $style['icon_list'][$key]['color_file'] = $color_file;
            foreach ($style['icon_list'][$key]['color_file'] as $key2 => $value2) {
                // 过滤不存在的颜色
                if (!in_array($value2['color_name'], $color_array)) {
                    unset($style['icon_list'][$key]['color_file'][$key2]);
                } else {
                    $file_url = $this->_db->getOne("SELECT file_url FROM " . $this->_tb_attribute_img . " WHERE cat_id = '$type_id' AND attr_group = '$value[attr_value_name],$value2[color_name]'");
                    if (!empty($file_url)) {
                        $style['icon_list'][$key]['color_file'][$key2]['file_url'] = $file_url;
                        $style['icon_list'][$key]['color_file'][$key2]['data_src'] = pathinfo($file_url)['filename'];
                    } else {
                        // 过滤没有组合图片的颜色
                        unset($style['icon_list'][$key]['color_file'][$key2]);
                    }
                }
            }
            foreach ($style['icon_list'][$key]['color_file'] as $key2 => $value2) {
                $style['icon_list'][$key]['color_file'] = array_values($style['icon_list'][$key]['color_file']);
            }
        }
        return $style;
    }

    /**
     * 颜色
     */
    function color($type_id)
    {
        $sql = "SELECT * FROM " . $this->_tb_attribute . " WHERE cat_id = '$type_id' AND is_diy = 1 AND attr_name = '颜色' limit 1";
        $color = $this->_db->getRow($sql);
        return $color;
    }

    /**
     * 属性组合图片
     */
    function attr_group_img($type_id)
    {
        $sql = "SELECT * FROM " . $this->_tb_attribute_img . " WHERE cat_id = '$type_id' ";
        $attr_group_img = $this->_db->getAll($sql);
        foreach ($attr_group_img as $key => $value) {
            $attr_group_img[$key]['data_src'] = pathinfo($value['file_url'])['filename'];
        }
        return $attr_group_img;
    }

    /**
     * 字体
     */
	function font_list()
	{
		$sql = "SELECT * FROM " . $this->_tb_font_type ." WHERE is_show = 1 ORDER BY sort_order ASC, type_id ASC";
        $_type_list = $this->_db->getAll($sql);
        $type_list = list_to_tree($_type_list);
        foreach ($type_list as $key => $value) {
            if ($value['type_short_name'] == 'ch') {
                $type_list[$key]['data_id'] = 'chFont';
                $type_list[$key]['attr_name'] = 'data_ch';
            }
            if ($value['type_short_name'] == 'en') {
                $type_list[$key]['data_id'] = 'enFont';
                $type_list[$key]['attr_name'] = 'data_en';
            }
            
            if ($value['_child']) {
                foreach ($value['_child'] as $key2 => $value2) {
                    $sql = "SELECT * FROM " . $this->_tb_font ." WHERE type_id = '$value2[type_id]' AND is_show = 1 ORDER BY sort_order ASC, font_id DESC";
                    $type_list[$key]['_child'][$key2]['font_list'] = $this->_db->getAll($sql);

                }
            }
        }
        return $type_list;
	}

    /**
     * 图形
     */
    function graph_list()
    {
        $sql = "SELECT * FROM " . $this->_tb_graph_type ." WHERE is_show = 1 ORDER BY sort_order ASC, type_id ASC";
        $type_list = $this->_db->getAll($sql);
        foreach ($type_list as $key => $value) {
            $sql = "SELECT * FROM " . $this->_tb_graph . " WHERE type_id = " . $value['type_id'] . " AND is_show = 1 ORDER BY sort_order ASC, graph_id DESC";
            $graph_list = $this->_db->getAll($sql);
            if (empty($graph_list)) {
                unset($type_list[$key]);
            } else {
                $type_list[$key]['graph_list'] = $graph_list;
            }
        }
        return $type_list;
    }

    /**
     * 蒙版
     */
    function mask_list()
    {
        $sql = "SELECT * FROM " . $this->_tb_mask ." WHERE is_show = 1 ORDER BY sort_order ASC";
        $mask_list = $this->_db->getAll($sql);
        return $mask_list;
    }

    /**
     * 获取属性商品图片
     */
    function get_Goods_Img($goods_attr_id = 0, $goods_attr_id2 = 0)
    {
        $sql = "SELECT img_url FROM " . $this->_tb_goods_gallery ." WHERE goods_attr_id = '$goods_attr_id' AND goods_attr_id2 = '$goods_attr_id2'";
        $goods_img = $this->_db->getOne($sql);
        return $goods_img;
    }

    /**
     * diy作品信息
     */
    function diy_Info($diy_id, $user_id)
    {
        $sql = "SELECT * FROM " . $this->_tb_diy . " WHERE diy_id = '$diy_id' AND user_id = '$user_id'";
        $diy_info = $this->_db->getRow($sql);
        return $diy_info;
    }

    /**
     * 导入图片
     */
    public function upload_File($user_id, $file, $design_session){
        include_once(ROOT_PATH . '/includes/cls_image.php');
        $image = new cls_image($GLOBALS['_CFG']['bgcolor']);

        $res = array();
        //将base64编码转换为图片保存
        if (preg_match('/^(data:\s*image\/(\w+);base64,)/', $file, $result)) {
            $type = $result[2];
            $path = DATA_DIR . '/diy/' . date('Ym') . '/';
            $new_file = ROOT_PATH . $path;
            if (!file_exists($new_file)) {
                //检查是否有该文件夹，如果没有就创建，并给予最高权限
                mkdir($new_file, 0777);
            }
            $img = $image->unique_name($path) . ".{$type}";
            $new_file = $new_file . $img;
            $upload_file = $path . $img;

            //将图片保存到指定的位置
            if (file_put_contents($new_file, base64_decode(str_replace($result[1], '', $file)))) {
                $add_time = $this->_now_time;
                $sql = "insert into " . $this->_tb_diy_file . "(`user_id`,`design_session`,`file_url`,`add_time`) values('$user_id','$design_session','$upload_file','$add_time')";

                if((string)$this->_db->query($sql)){
                    $res['file_id'] = $this->_db->insert_id();
                    $res['file_url'] = $upload_file;
                }
            }
        }
        return $res;
    }

    /**
     * 删除图片
     */
    function delete_File($file_id)
    {
        $sql = "SELECT file_url FROM " . $this->_tb_diy_file . " WHERE file_id = '$file_id'";
        $file_url = $this->_db->getOne($sql);

        $sql = "DELETE FROM " . $this->_tb_diy_file ." WHERE file_id = '$file_id'";
        $res = $this->_db->query($sql);
        if ($res === false) {
            return false;
        } else {
            @unlink(ROOT_PATH . $file_url);
            return true;
        }
    }

    /**
     * 创建设计商品
     * @param  int $type_id    商品类型ID
     * @param  string $goods_name 商品名称
     * @param  array $attr_img   属性图片
     * @param  string $goods_img  商品图片
     * @param  string $design_img 设计图
     * @return boolean
     */
    function create_Goods($user_id, $type_id, $goods_name, $attr_img, $goods_img, $design_img, $design_session, $diy_json)
    {
        require_once(ROOT_PATH . '/includes/lib_diy.php');
        include_once(ROOT_PATH . '/includes/cls_image.php');
        $image = new cls_image($GLOBALS['_CFG']['bgcolor']);

        /* 是否处理缩略图 */
        $proc_thumb = $auto_thumb = true;

        /* 插入还是更新的标识 */
        $is_insert = 1;

        /* 处理商品图片 */
        $goods_thumb      = '';  // 初始化商品缩略图
        $original_img     = '';  // 初始化原始图片
        $old_original_img = '';  // 初始化原始图片旧图
        //将base64编码转换为图片保存（商品图）
        if (preg_match('/^(data:\s*image\/(\w+);base64,)/', $goods_img, $result)) {
            $type = $result[2];
            $path = 'images/' . date('Ym') . '/';
            $new_file = ROOT_PATH . $path;
            if (!file_exists($new_file)) {
                //检查是否有该文件夹，如果没有就创建，并给予最高权限
                mkdir($new_file, 0777);
            }
            $img = $image->unique_name($path) . ".{$type}";
            $new_file = $new_file . $img;
            $original_img = $path . $img;
            //将图片保存到指定的位置
            if (file_put_contents($new_file, base64_decode(str_replace($result[1], '', $goods_img)))) {
                $goods_img = $original_img;   // 商品图片
            }
        }

        // 上传了商品图片，自动生成缩略图
        if ($proc_thumb && isset($auto_thumb) && !empty($original_img))
        {
            // 如果设置缩略图大小不为0，生成缩略图
            if ($GLOBALS['_CFG']['thumb_width'] != 0 || $GLOBALS['_CFG']['thumb_height'] != 0)
            {
                $goods_thumb = $image->make_thumb('../' . $original_img, $GLOBALS['_CFG']['thumb_width'],  $GLOBALS['_CFG']['thumb_height']);
                if ($goods_thumb === false)
                {
                    return false;
                }
            }
            else
            {
                $goods_thumb = $original_img;
            }
        }

        //将base64编码转换为图片保存（设计图）
        if (preg_match('/^(data:\s*image\/(\w+);base64,)/', $design_img, $result)) {
            $type = $result[2];
            $path = 'images/' . date('Ym') . '/';
            $new_file = ROOT_PATH . $path;
            if (!file_exists($new_file)) {
                //检查是否有该文件夹，如果没有就创建，并给予最高权限
                mkdir($new_file, 0777);
            }
            $img = $image->unique_name($path) . ".{$type}";
            $new_file = $new_file . $img;
            $original_design_img = $path . $img;

            //将图片保存到指定的位置
            if (file_put_contents($new_file, base64_decode(str_replace($result[1], '', $design_img)))) {
                $design_img = $original_design_img;   // 设计图
            }
        }

        /* 自动生成一个商品货号 */
        $max_id = $this->_db->getOne("SELECT MAX(goods_id) + 1 FROM " . $this->_tb_goods);
        $goods_sn = generate_goods_sn($max_id);

        /* 等级信息 */
        $rank_info = $this->user->get_user_rank($user_id);

        /* 处理商品数据 */
        $shop_price = $rank_info['sale_price'] ? $rank_info['sale_price'] : 99;
        $market_price = $rank_info['sale_price'] ? $rank_info['sale_price'] : 99;
        $promote_price = 0;
        $is_promote = 0;
        $zhekou = 10.0;
        $promote_start_date = 0;
        $promote_end_date = 0;
        $goods_weight = 0;
        $is_best = 0;
        $is_new = 0;
        $is_hot = 0;
        $is_on_sale = 0;
        $is_alone_sale = 1;
        $is_shipping = 0;
        $goods_number = $rank_info['sale_number'] ? $rank_info['sale_number'] : 999;//库存
        $goods_total = $rank_info['sale_number'] ? $rank_info['sale_number'] : 999;//可销售数量
        $warn_number = 1;
        $goods_type = $type_id;//T恤
        $give_integral = '-1';
        $rank_integral = '-1';
        $suppliers_id = '0';
        $goods_rank = $rank_info['rank_id'] ? $rank_info['rank_id'] : 1;//商品等级
        $commision1 = $rank_info['commision1'] ? $rank_info['commision1'] : 0;//第1阶段佣金
        $commision2 = $rank_info['commision2'] ? $rank_info['commision2'] : 0;//第2阶段佣金

        //手机专享价格 app   jx
        $exclusive = -1;
        //手机专享价格 app  jx

        $cost_price = 0;
        $goods_name_style = '+';

        $catgory_id = 85;   //T恤
        $brand_id = 0;

        $buymax = 0;
        $is_buy = 0;
        $buymax_start_date = 0;
        $buymax_end_date = 0;
        $goods_status = 1;//（未申请出售状态）

        /* 入库 */
        $sql = "INSERT INTO " . $this->_tb_goods . " (goods_name, goods_name_style, goods_sn, " .
                "cat_id, brand_id, shop_price, market_price, is_promote, zhekou, promote_price, " .
                "promote_start_date, promote_end_date, is_buy,buymax,buymax_start_date,buymax_end_date, goods_img, goods_thumb, original_img, design_img, keywords, goods_brief, " .
                "goods_weight, goods_number, goods_total, warn_number, give_integral, is_best, is_new, is_hot, " .
                "is_on_sale, is_alone_sale, is_shipping, goods_desc, add_time, last_update, goods_type, rank_integral,exclusive, suppliers_id, cost_price, goods_status, user_id, goods_rank, commision1, commision2)" .
            "VALUES ('$goods_name', '$goods_name_style', '$goods_sn', '$catgory_id', " .
                "'$brand_id', '$shop_price', '$market_price', '$is_promote', '$zhekou', '$promote_price', ".
                "'$promote_start_date', '$promote_end_date', '$is_buy','$buymax','$buymax_start_date','$buymax_end_date', '$goods_img', '$goods_thumb', '$original_img', '$design_img', ".
                "'$goods_name', '$goods_name', '$goods_weight', '$goods_number','$goods_number',".
                " '$warn_number', '$give_integral', '$is_best', '$is_new', '$is_hot', '$is_on_sale', '$is_alone_sale', $is_shipping, ".
                " '$goods_name', '" . gmtime() . "', '". gmtime() ."', '$goods_type', '$rank_integral','$exclusive', '$suppliers_id', '$cost_price', '$goods_status', '$user_id', '$goods_rank', '$commision1', '$commision2')";

        $this->_db->query($sql);
        /* 商品编号 */
        $goods_id = $this->_db->insert_id();

        /* 保存设计作品 */
        save_design($user_id, $goods_name, $design_img, $goods_img, $design_session, $goods_id, $diy_json);

        $dir_clear  = get_dir('category', $catgory_id);
        $prefix_clear = "category-".$catgory_id;
        clearhtml_dir(ROOT_PATH.$dir_clear, $prefix_clear);

        /* 存入条形码 */
        $this->_db->query("DELETE FROM" .$this->_tb_bar_code."WHERE goods_id ='$goods_id'");//根据商品ID清空数据

        /* 处理属性 */
        $sql = "SELECT * FROM " . $this->_tb_attribute . " WHERE cat_id = '$type_id'";
        $cat_list = $this->_db->getAll($sql);
        $attr_id_list = array();    // 属性ID数组
        $attr_value_list = array(); // 属性值数组
        $attr_price_list = array(); // 属性价格数组
        foreach ($cat_list as $key => $value) {
            if($value['attr_values'])
            {
                $attr_list = str_replace("\r\n", "\n", $value['attr_values']);
                $attr_list = explode("\n", $attr_list);
                for ($i=0; $i < count($attr_list); $i++) {
                    $attr_id_list[] = $value['attr_id'];
                    $attr_value_list[] = $attr_list[$i];
                    $attr_price_list[] = 0;
                }
            }
        }
        if (!empty($attr_id_list) && !empty($attr_value_list))
        {
            // 取得原有的属性值
            $goods_attr_list = array();

            $sql = "SELECT attr_id, attr_index FROM " . $this->_tb_attribute . " WHERE cat_id = '$goods_type'";

            $attr_res = $this->_db->query($sql);

            $attr_list = array();

            while ($row = $this->_db->fetchRow($attr_res))
            {
                $attr_list[$row['attr_id']] = $row['attr_index'];
            }

            $sql = "SELECT g.*, a.attr_type
                    FROM " . $this->_tb_goods_attr . " AS g
                        LEFT JOIN " . $this->_tb_attribute . " AS a
                            ON a.attr_id = g.attr_id
                    WHERE g.goods_id = '$goods_id'";

            $res = $this->_db->query($sql);

            while ($row = $this->_db->fetchRow($res))
            {
                $goods_attr_list[$row['attr_id']][$row['attr_value']] = array('sign' => 'delete', 'goods_attr_id' => $row['goods_attr_id']);
            }
            // 循环现有的，根据原有的做相应处理
            foreach ($attr_id_list as $key => $attr_id)
            {
                $attr_value = $attr_value_list[$key];
                $attr_price = $attr_price_list[$key];
                $attr_price = ($attr_price >= 0) ? $attr_price : 0;
                if (!empty($attr_value))
                {
                    if (isset($goods_attr_list[$attr_id][$attr_value]))
                    {
                        // 如果原来有，标记为更新
                        $goods_attr_list[$attr_id][$attr_value]['sign'] = 'update';
                        $goods_attr_list[$attr_id][$attr_value]['attr_price'] = $attr_price;
                    }
                    else
                    {
                        // 如果原来没有，标记为新增
                        $goods_attr_list[$attr_id][$attr_value]['sign'] = 'insert';
                        $goods_attr_list[$attr_id][$attr_value]['attr_price'] = $attr_price;
                    }
                }
            }

            /* 插入、更新、删除数据 */
            foreach ($goods_attr_list as $attr_id => $attr_value_list)
            {
                foreach ($attr_value_list as $attr_value => $info)
                {
                    if ($info['sign'] == 'insert')
                    {
                        $sql = "INSERT INTO " . $this->_tb_goods_attr . " (attr_id, goods_id, attr_value, attr_price)".
                                "VALUES ('$attr_id', '$goods_id', '$attr_value', '$info[attr_price]')";
                    }
                    elseif ($info['sign'] == 'update')
                    {
                        $sql = "UPDATE " . $this->_tb_goods_attr . " SET attr_price = '$info[attr_price]' WHERE goods_attr_id = '$info[goods_attr_id]' LIMIT 1";
                    }
                    else
                    {
                        //删除商品属性
                        $sql = "DELETE FROM " . $this->_tb_goods_attr . " WHERE goods_attr_id = '$info[goods_attr_id]' LIMIT 1";
                        $this->_db->query($sql);
                        //删除商品属性对应的货品信息
                        $sql = "DELETE FROM " . $this->_tb_products . " WHERE goods_id = '$goods_id' and (goods_attr = '".$info['goods_attr_id']."' or goods_attr like '%|".$info['goods_attr_id']."' or goods_attr like '".$info['goods_attr_id']."|%' or goods_attr like '%|".$info['goods_attr_id']."|%')";
                        $this->_db->query($sql);
                        continue;
                    }
                    $this->_db->query($sql);
                }
            }
        }

        /* 重新格式化图片名称 */
        $original_img = reformat_image_name('goods', $goods_id, $original_img, 'source');
        $goods_img = reformat_image_name('goods', $goods_id, $goods_img, 'goods');
        $goods_thumb = reformat_image_name('goods_thumb', $goods_id, $goods_thumb, 'thumb');

        if ($goods_img !== false)
        {
            $this->_db->query("UPDATE " . $this->_tb_goods . " SET goods_img = '$goods_img' WHERE goods_id='$goods_id'");
        }

        if ($original_img !== false)
        {
            $this->_db->query("UPDATE " . $this->_tb_goods . " SET original_img = '$original_img' WHERE goods_id='$goods_id'");
        }

        if ($goods_thumb !== false)
        {
            $this->_db->query("UPDATE " . $this->_tb_goods . " SET goods_thumb = '$goods_thumb' WHERE goods_id='$goods_id'");
        }

        /* 处理相册图片 */
        handle_gallery_image($goods_id, $attr_img);

        $data['goods_id'] = $goods_id;

        return $data;
    }

    //查询比赛活动数据 is_show=1 为开放的比赛
    public function originality(){
        $sql = " SELECT * FROM ".$GLOBALS['ecs']->table('originality')." WHERE is_show = 1 order by sort_order asc limit 1 ";
        return $GLOBALS['db']->getRow($sql);
    }

    /*
     * 获取类型（分类）
     * $cat_id 顶级分类id
     */
    public function category($cat_id){
        $sql = " SELECT `cat_name`,`cat_id` FROM ".$GLOBALS['ecs']->table('category')." WHERE parent_id = $cat_id AND is_show = 1 order by sort_order asc ";
        return $GLOBALS['db']->getAll($sql);
    }
}
