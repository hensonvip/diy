<?php
/**
 * 砍价接口
 * 
 * @version v1.0.1
 * @create 2018-01-22
 * @author qinglin
 */
class BargainController extends ApiController
{
	//public $method = 'GET';
	public function __construct()
	{

		parent::__construct();
				
	}
	
	/**
	 * 砍价商品列表
	 */
	public function query(){
		$supplier_id= $this->input('supplier_id', '-1');
		$page_size  = $this->input('page_size', 10);
		$page   	= $this->input('page', 1);
		$order   	= $this->input('order', 'desc');
		$sort    	= $this->input('sort', 'id');
		$keywords 	= htmlspecialchars(urldecode(trim($this->input('keywords', ''))));

		$where = " AND g.is_on_sale = 1 AND g.is_alone_sale = 1 AND g.is_delete = 0  ";
		if($supplier_id != '-1' && $supplier_id != ''){
            $where .= ' AND g.supplier_id='.$supplier_id;
        } 
		if($sort == 'sold_count'){
            $sort = 'salenum';
        }

		$page_start = $page_size*($page-1);

		$time = gmtime();
		$sql = "SELECT b.id,b.goods_id,b.product_id,b.start_time,b.end_time,b.shop_price,b.low_price,b.guanzhu_num,b.join_num,b.bargain_num,g.add_time,g.goods_name,g.goods_thumb,IFNULL(o.num,0) AS salenum " . " FROM " . $GLOBALS['ecs']->table('bargain_activity') . " as b LEFT JOIN " . $GLOBALS['ecs']->table('goods') . " as g ON b.goods_id = g.goods_id".

			//销量
			" LEFT JOIN " .
            " (SELECT " .
            " SUM(og.`goods_number`) " .
            " AS num,og.goods_id " .
            " FROM " . $GLOBALS['ecs']->table('order_goods') . " AS og" .
            " , " . $GLOBALS['ecs']->table('order_info') . " AS oi" .
            " WHERE oi.pay_status = 2 " .
            " AND oi.order_status >= 1 " .
            " AND oi.order_id = og.order_id " .
            " GROUP BY og.goods_id) " .
            " AS o " .
            " ON o.goods_id = g.goods_id " .

		" WHERE b.is_open = 1 and b.start_time <= '$time' and b.end_time >= '$time' $where ORDER BY $sort $order";
		$res = $GLOBALS['db']->SelectLimit($sql, $page_size, $page_start);
		
		while($row = $GLOBALS['db']->fetchRow($res))
		{
			/* 砍价时间倒计时 */
            if ($time >= $row['start_time'] && $time <= $row['end_time'])
            {
            	$row['start_time']  = $row['start_time'];
                $row['end_time']  = $row['end_time'];
                $row['format_start_time']  = local_date('Y-m-d H:i:s',$row['start_time']);
                $row['format_end_time']  = local_date('Y-m-d H:i:s',$row['end_time']);
            }
            else
            {
            	$row['start_time']  = 0;
                $row['end_time'] = 0;
                $row['format_start_time'] = 0;
                $row['format_end_time'] = 0;
            }
            $row['format_shop_price']   = price_format($row['shop_price']);
            $row['format_low_price']   = price_format($row['low_price']);

            //获取做砍价活动的商品属性值
            $goods_attr_data = $GLOBALS['db']->getRow("SELECT goods_attr,product_number FROM " . $GLOBALS['ecs']->table('products') . " WHERE product_id = '$row[product_id]'");
	        $product_id_arr = isset($goods_attr_data['goods_attr']) ? explode('|',$goods_attr_data['goods_attr']) : '';
	        $attr_name = '';
	        if($product_id_arr){
	        	foreach ($product_id_arr as $k => $v) {
	        		$attr_name .=  $GLOBALS['db']->getOne("SELECT attr_value FROM " . $GLOBALS['ecs']->table('goods_attr') . " WHERE goods_attr_id = '$v'");
	        		$attr_name .= '、';
	        	}
	        	$attr_name = trim($attr_name,'、');
	        }
	        if(!empty($attr_name)){
	        	$row['goods_name'] = $row['goods_name'].'（'.$attr_name.'）';//更改商品显示名称，加上属性值
	        }else{
	        	$row['goods_name'] = $row['goods_name'];
	        }

			$result[] = $row;
		}

		if (empty($result))
		{
			$this->success(array('list'=>array(),'pager'=>new StdClass()), $code = 200, $msg = '找不到数据');
		}

		$count = $GLOBALS['db']->getOne("SELECT count(*) " . " FROM " . $GLOBALS['ecs']->table('bargain_activity') . " as b LEFT JOIN " . $GLOBALS['ecs']->table('goods') . " as g ON b.goods_id = g.goods_id WHERE b.is_open = 1 and b.start_time <= '$time' and b.end_time >= '$time' $where ");

		//分页
        $pager = array();
        $pager['page']         = $page;
        $pager['page_size']    = $page_size;
        $pager['record_count'] = $count;
        $pager['page_count']   = $page_count = ($count > 0) ? intval(ceil($count / $page_size)) : 1;

        $goods_data['list'] = $result;
        $goods_data['pager'] = $pager;

		$this->success($goods_data);
	}

	/**
	 * 砍价页面详情
	 */
	public function details(){
		$user_id = $this->input('user_id', '0');//用户ID
		$bargain_id = $this->input('bargain_id', '0');//砍价活动ID
		$help_user_id = $this->input('help_user_id') ? $this->input('help_user_id') : $user_id;//帮砍用户ID  帮砍页面才要传值

		/*if(!$user_id){
			$this->error('请先登录！');
		}*/

		$time = gmtime();
		if($bargain_id){
			//活动已结束
			$bargain_info = $GLOBALS['db']->getRow("SELECT * FROM " . $GLOBALS['ecs']->table('bargain_activity') . " WHERE id = '$bargain_id' and is_open = 1 and start_time <= '$time' and end_time <= '$time'");
			if($bargain_info && $user_id != $help_user_id){//帮砍用户进入已过时的活动时提示
				$this->error('该活动已结束！');
			}

			//活动结束也可以进详情页，去进行购买 2018.02.06改
			$bargain_info = $GLOBALS['db']->getRow("SELECT * FROM " . $GLOBALS['ecs']->table('bargain_activity') . " WHERE id = '$bargain_id' and is_open = 1 ");
			if(!$bargain_info){
				$this->error('该商品没有参与砍价活动！1');
			}
		}else{
			$this->error('操作错误，缺少必填参数！');
		}

		//获取做砍价活动的商品属性值
        $goods_attr_data = $GLOBALS['db']->getRow("SELECT goods_attr,product_number FROM " . $GLOBALS['ecs']->table('products') . " WHERE product_id = '$bargain_info[product_id]'");
        $product_id_arr = isset($goods_attr_data['goods_attr']) ? explode('|',$goods_attr_data['goods_attr']) : '';
        $attr_id = '';//属性ID
        $attr_name = '';//属性名
        if($product_id_arr){
        	foreach ($product_id_arr as $k => $v) {
        		$attr_id .= $v;
        		$attr_id .= ',';
        		$attr_name .=  $GLOBALS['db']->getOne("SELECT attr_value FROM " . $GLOBALS['ecs']->table('goods_attr') . " WHERE goods_attr_id = '$v'");
        		$attr_name .= '、';
        	}
        	$attr_name = trim($attr_name,'、');
        	$attr_id = trim($attr_id,',');
        }


        //商品相册
        $goods_gallery = $GLOBALS['db']->getAll("SELECT img_url,thumb_url,img_original FROM " . $GLOBALS['ecs']->table('goods_gallery') . " WHERE goods_id = '$bargain_info[goods_id]'");
        if(empty($goods_gallery)){
        	//如果没有数据，设置默认图片
        	$goods_gallery[0]['img_url'] = 'data/default/default.png';
        	$goods_gallery[0]['thumb_url'] = 'data/default/default.png';
        	$goods_gallery[0]['img_original'] = 'data/default/default.png';
        }

        $bargain_info['format_start_time']  = local_date('Y-m-d H:i:s',$bargain_info['start_time']);
        $bargain_info['format_end_time']  = local_date('Y-m-d H:i:s',$bargain_info['end_time']);
        $bargain_info['format_shop_price']   = price_format($bargain_info['shop_price']);
        $bargain_info['format_low_price']   = price_format($bargain_info['low_price']);

        //获取商品最新砍价值
        $log_info = $GLOBALS['db']->getRow("SELECT now_price FROM " . $GLOBALS['ecs']->table('bargain_log') . " WHERE bargain_id = '$bargain_id' and help_user_id = '$help_user_id' and status = 0 order by now_price asc");
        //如果没有就读取商品原价
		$now_price = isset($log_info['now_price']) ? $log_info['now_price'] : $bargain_info['shop_price'];

		//过滤不需要的字段
		unset($bargain_info['product_id']);
		unset($bargain_info['min_price']);
		unset($bargain_info['max_price']);
		unset($bargain_info['supplier_id']);
		unset($bargain_info['is_open']);
		unset($bargain_info['guanzhu_num']);
		unset($bargain_info['join_num']);
		unset($bargain_info['bargain_num']);

        $result = array();
        $result = $bargain_info;
        if(!empty($attr_name)){
        	$result['goods_name'] = $result['goods_name'].'（'.$attr_name.'）';//更改商品显示名称，加上属性值
        }else{
        	$result['goods_name'] = $result['goods_name'];//更改商品显示名称，加上属性值
        }
        $result['attr_id'] = $attr_id;//属性ID
        $goods_number = $GLOBALS['db']->getOne("SELECT goods_number FROM " . $GLOBALS['ecs']->table('goods') . " WHERE goods_id = '$bargain_info[goods_id]'");
        $result['goods_number'] = isset($goods_attr_data['product_number']) ? $goods_attr_data['product_number'] : $goods_number;//属性商品库存

        $result['now_price'] = $now_price;//最新价格
        $result['format_now_price']   = price_format($now_price);
        //进度百分比
        $percentum = round((($bargain_info['shop_price']-$now_price)/($bargain_info['shop_price']-$bargain_info['low_price']))*100,0);
        $result['percentum'] = $percentum;
        if($percentum > 100){
        	$result['percentum'] = 100;
        }

        //帮砍显示的数据
        if($help_user_id){
        	$result['help_user_name'] = $GLOBALS['db']->getOne("SELECT user_name FROM " . $GLOBALS['ecs']->table('users') . " WHERE user_id = '$help_user_id'");
        	$result['goods_desc'] = $GLOBALS['db']->getOne("SELECT goods_desc FROM " . $GLOBALS['ecs']->table('goods') . " WHERE goods_id = '$bargain_info[goods_id]'");
        }else{
        	$result['help_user_name'] = '';
        	$result['goods_desc'] = '';
        }

        $result['goods_gallery'] = $goods_gallery;//商品相册

        //定义APP分享数据源
        $result['share_title'] = $result['goods_name'];//分享标题
        $result['share_img'] = 'http://'.$_SERVER['HTTP_HOST'].'/'.$goods_gallery[0]['thumb_url'];//分享标题
        $result['share_url'] = 'http://'.$_SERVER['HTTP_HOST'].'/mobile.php/bargain/details/bargain_id/'.$bargain_id.'/help_user_id/'.$help_user_id;//分享标题

        $this->success($result);
	}

	/**
	 * 立即砍价、帮砍
	 */
	public function doBargain(){
		$user_id = $this->input('user_id', '0');//用户ID
		$bargain_id = $this->input('bargain_id', '0');//砍价活动ID
		$help_user_id = $this->input('help_user_id') ? $this->input('help_user_id') : $user_id;//帮砍价用户ID,选填

		if(!$user_id){
			$this->error('请先登录！');
		}

		//验证活动
		$time = gmtime();
		if($bargain_id){
			$bargain_info = $GLOBALS['db']->getRow("SELECT * FROM " . $GLOBALS['ecs']->table('bargain_activity') . " WHERE id = '$bargain_id' and is_open = 1 and start_time <= '$time' and end_time >= '$time'");
			if(!$bargain_info){
				$this->error('该商品没有参与砍价活动！');
			}
		}else{
			$this->error('操作错误，缺少必填参数！');
		}

		$user_data = $GLOBALS['db']->getRow("SELECT sex,headimg FROM " . $GLOBALS['ecs']->table('users') . " WHERE user_id = '$user_id' ");
		$headimg = !empty($user_data['headimg']) ? str_replace("./../","",$user_data['headimg']) : 'data/default/sex'.$user_data['sex'].'.png';//头像

		$is_log = $GLOBALS['db']->getRow("SELECT * FROM " . $GLOBALS['ecs']->table('bargain_log') . " WHERE bargain_id = '$bargain_id' and user_id = '$user_id' and help_user_id = '$help_user_id' and product_id = '".$bargain_info['product_id']."' and status = 0");

		if(!empty($is_log)){
			if($user_id == $help_user_id){
				$result = array();
				$result['headimg'] = $headimg;//头像
				$result['bargain_price'] = $is_log['bargain_price'];
				$result['format_bargain_price'] = price_format($result['bargain_price']);
				$result['bargain_name'] = '砍掉'.$is_log['bargain_price'].'元';
				$result['bargain_str'] = '你之前已参与活动，去看看进度吧！';
				$this->success($result);
				//$this->success('你之前已参与活动，去看看进度吧！');
			}
			$this->error('你之前已经砍了一刀啦！');
		}

		$log_info = $GLOBALS['db']->getRow("SELECT * FROM " . $GLOBALS['ecs']->table('bargain_log') . " WHERE bargain_id = '$bargain_id' and help_user_id = '$help_user_id' and product_id = '".$bargain_info['product_id']."' and status = 0 order by now_price asc");

		//获取商品最新砍价值，如果没有就读取商品原价
		$now_price = isset($log_info['now_price']) ? $log_info['now_price'] : $bargain_info['shop_price'];

		if($bargain_info['low_price'] == $now_price){
			$this->error('已经是最低价了，商家也不容易！');
		}

		//计算砍价浮动值
		$num = $bargain_info['min_price'] + mt_rand() / mt_getrandmax() * ($bargain_info['max_price'] - $bargain_info['min_price']);
		$bargain_price = sprintf("%.2f", $num);

		//计算现价
		$current_price = $now_price - $bargain_price;
		if($current_price <= $bargain_info['low_price']){
			//当砍价后价格低于最低价，则设置砍价后为最低价
			$bargain_price = $bargain_info['low_price'] - $now_price;//随机值重置为刚刚好为最低值
			$current_price = $bargain_info['low_price'];
		}

		$save = array();//要保存的数据集
		$save['user_id'] = $user_id;
		$save['help_user_id'] = $help_user_id;
		$save['bargain_id'] = $bargain_id;
		$save['goods_id'] = $bargain_info['goods_id'];
		$save['product_id'] = $bargain_info['product_id'];
		$save['bargain_price'] = $bargain_price;
		$save['now_price'] = $current_price;
		$save['add_time'] = $time;

		if ($GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('bargain_log'), $save, 'INSERT') !== false){
			$result = array();
			$result['headimg'] = $headimg;//头像
			$result['bargain_price'] = $bargain_price;
			$result['format_bargain_price'] = price_format($result['bargain_price']);
			$result['bargain_name'] = '砍掉'.$bargain_price.'元';
			if($help_user_id == $user_id){
				$result['bargain_str'] = '手起刀落，成功砍下一刀';
			}else{
				$result['bargain_str'] = '手起刀落，成功帮TA砍下一刀';
			}

			//更新砍价统计数量
            $bargain_guangzhu = $GLOBALS['db']->getOne("SELECT count(*) FROM ".$GLOBALS['ecs']->table('bargain_log')." as l WHERE l.user_id = l.help_user_id and l.bargain_id = '" . $bargain_id . "' ");//关注人数 
            $bargain_canyu = $GLOBALS['db']->getOne("SELECT count(*) FROM ".$GLOBALS['ecs']->table('bargain_log')." as l WHERE l.user_id = l.help_user_id and l.bargain_id = '" . $bargain_id . "' and status = 1");//参与人数 
            $bargain_bangkan = $GLOBALS['db']->getOne("SELECT count(*) FROM ".$GLOBALS['ecs']->table('bargain_log')." as l WHERE l.bargain_id = '" . $bargain_id . "' ");//砍价人数 
            $sql = "UPDATE ".$GLOBALS['ecs']->table('bargain_activity')." SET guanzhu_num='$bargain_guangzhu',join_num = '$bargain_canyu',bargain_num = '$bargain_bangkan' WHERE id = '".$bargain_id."' ";
            $GLOBALS['db']->query($sql);

			$this->success($result);
		}
		$this->error('刀断了，砍不了！');

	}

	/**
	 * 砍价记录
	 */
	public function bargainLog(){
		$user_id = $this->input('user_id', '0');//用户ID
		$bargain_id = $this->input('bargain_id', '0');//砍价活动ID

		$page_size  = $this->input('page_size', 10);
		$page   	= $this->input('page', 1);
		$order   	= $this->input('order', 'desc');
		$sort    	= $this->input('sort', 'add_time');

		$page_start = $page_size*($page-1);

		if(!$user_id){
			$this->error('请先登录！');
		}

		$product_id = $GLOBALS['db']->getOne("SELECT product_id " . " FROM " . $GLOBALS['ecs']->table('bargain_activity') . " WHERE id = '$bargain_id'");

		$sql = "SELECT user_id,bargain_price,now_price,add_time " . " FROM " . $GLOBALS['ecs']->table('bargain_log') . " WHERE help_user_id = '$user_id' and bargain_id = '$bargain_id' and product_id = '$product_id' and status = 0 ORDER BY $sort $order";
		$res = $GLOBALS['db']->SelectLimit($sql, $page_size, $page_start);
		
		while($row = $GLOBALS['db']->fetchRow($res))
		{
			$user_data = $GLOBALS['db']->getRow("SELECT headimg,user_name,sex " . " FROM " . $GLOBALS['ecs']->table('users') . " WHERE user_id = '$row[user_id]' ");
			
			$sex  = !empty($user_data['sex']) ? $user_data['sex'] : 0;//性别
        	$row['headimg'] = !empty($user_data['headimg']) ? str_replace("./../","",$user_data['headimg']) : 'data/default/sex'.$sex.'.png';//头像
			
			//用户名处理
        	$search = array(" ","　","\n","\r","\t");
			$replace = array("","","","","");
			$row['user_name'] = str_replace($search, $replace, $user_data['user_name']);
			if(strlen($row['user_name']) > 10){
				$row['user_name'] = substr($row['user_name'],0,3).'****'.substr($row['user_name'],-3,3);
			}else{
				$row['user_name'] = trim($row['user_name']).'****';
			}
			
			$row['format_add_time']  = local_date('Y-m-d H:i:s',$row['add_time']);
			$row['format_bargain_price'] = price_format($row['bargain_price']);
			$row['format_now_price'] = price_format($row['now_price']);
			$result[] = $row;
		}

		if (empty($result))
		{
			$this->success(array('list'=>array(),'pager'=>new StdClass()), $code = 200, $msg = '找不到数据');
		}

		$count = $GLOBALS['db']->getOne("SELECT count(*) " . " FROM " . $GLOBALS['ecs']->table('bargain_log') . " WHERE help_user_id = '$user_id' and bargain_id = '$bargain_id' ");

		//分页
        $pager = array();
        $pager['page']         = $page;
        $pager['page_size']    = $page_size;
        $pager['record_count'] = $count;
        $pager['page_count']   = $page_count = ($count > 0) ? intval(ceil($count / $page_size)) : 1;

        $log_data['list'] = $result;
        $log_data['pager'] = $pager;

		$this->success($log_data);

	}

}