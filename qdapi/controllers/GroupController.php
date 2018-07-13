<?php
/**
 * 拼团接口
 * 
 * @version v1.0.1
 * @create 2018-01-30
 * @author qinglin
 */
class GroupController extends ApiController
{
	//public $method = 'GET';
	public function __construct()
	{

		parent::__construct();
				
	}
	
	/**
	 * 拼团商品列表
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
		$sql = "SELECT ga.id,ga.goods_id,ga.start_time,ga.end_time,ga.join_num,ga.join_num_false,g.add_time,g.goods_name,g.goods_thumb,g.shop_price,(g.shop_price*ga.group_discount) as group_price,IFNULL(o.num,0) AS salenum " . " FROM " . $GLOBALS['ecs']->table('group_activity') . " as ga LEFT JOIN " . $GLOBALS['ecs']->table('goods') . " as g ON ga.goods_id = g.goods_id".

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

		" WHERE ga.is_open = 1 and ga.start_time <= '$time' and ga.end_time >= '$time' $where ORDER BY $sort $order";
		$res = $GLOBALS['db']->SelectLimit($sql, $page_size, $page_start);
		
		while($row = $GLOBALS['db']->fetchRow($res))
		{
			/* 拼团时间倒计时 */
            if ($time >= $row['start_time'] && $time <= $row['end_time'])
            {
                $row['format_start_time']  = local_date('Y-m-d H:i:s',$row['start_time']);
                $row['format_end_time']  = local_date('Y-m-d H:i:s',$row['end_time']);
            }
            else
            {
                $row['format_start_time'] = 0;
                $row['format_end_time'] = 0;
            }
            $row['join_num'] = $row['join_num']+$row['join_num_false'];//加虚假人数
            $row['group_price'] = round($row['group_price'],2);
            $row['format_shop_price']   = price_format($row['shop_price']);
            $row['format_group_price']   = price_format($row['group_price']);
            unset($row['add_time']);
			$result[] = $row;
		}

		if (empty($result))
		{
			$this->success(array('list'=>array(),'pager'=>new StdClass()), $code = 200, $msg = '找不到数据');
		}

		$count = $GLOBALS['db']->getOne("SELECT count(*) " . " FROM " . $GLOBALS['ecs']->table('group_activity') . " as ga LEFT JOIN " . $GLOBALS['ecs']->table('goods') . " as g ON ga.goods_id = g.goods_id WHERE ga.is_open = 1 and ga.start_time <= '$time' and ga.end_time >= '$time' $where ");

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

}