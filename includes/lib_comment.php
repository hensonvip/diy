<?php

/**
 * 评价
*/

if (!defined('IN_ECS'))
{
    die('Hacking attempt');
}

/**
 * 获取评论
 * @param  integral  $page_size 接口使用，每页显示的数量，有传值就以这个为主 add by qinglin 2017.09.06
 */
function get_my_comments($goods_id, $type = 0, $page = 1, $c_tag, $page_size = 0, $user_id)
{
	$res = $GLOBALS['db']->getAll("SELECT * FROM ".$GLOBALS['ecs']->table('goods_tag')." WHERE goods_id = '$goods_id' AND state = 1");	
	$tags = array();
	foreach ($res as $v)
	{
		$tags[$v['tag_id']] = $v['tag_name'];	
	}
	$where = ' AND 1 ';
	$item_list = array();
	$sql = "SELECT FLOOR(AVG(comment_rank)) AS comment_rank_avg FROM ".$GLOBALS['ecs']->table('comment')." WHERE id_value = '$goods_id' AND status = 1 AND comment_rank > 0";
	$comment_rank_avg = $GLOBALS['db']->getOne($sql);
	if ($type != 4)
	{
		if ($type == 1)
		{
			$where .= " AND c.comment_rank in (5,4)";	
		}
		if ($type == 2)
		{
			$where .= " AND c.comment_rank in (3,2)";	
		}
		if ($type == 3)
		{
			$where .= " AND c.comment_rank = 1";	
		}
		if ($type == 4)
		{
			$where .= " AND s.shaidan_id > 0";	
		}
		
		$tag_name_c = $GLOBALS['db']->getOne("select tag_id from " . $GLOBALS['ecs']->table('goods_tag') . " where goods_id = " . $goods_id . " and tag_name = '" . $c_tag . "'");
		if ($tag_name_c)
		{
			$where .= " AND FIND_IN_SET('$tag_name_c',comment_tag)";			
		}

		$count = $GLOBALS['db']->getOne("SELECT COUNT(*) FROM ".$GLOBALS['ecs']->table('comment')." AS c 
										 LEFT JOIN ".$GLOBALS['ecs']->table('shaidan')." AS s ON c.rec_id=s.rec_id
										 WHERE c.id_value = '$goods_id' AND c.status = 1 AND c.comment_rank > 0 $where");
		$size  = !empty($GLOBALS['_CFG']['comments_number']) ? $GLOBALS['_CFG']['comments_number'] : 5;
		if($page_size){
			$size = $page_size;
		}
		
		$page_count = ($count > 0) ? intval(ceil($count / $size)) : 1;
	
		$sql = "SELECT c.*, u.headimg, u.sex, s.shaidan_id, s.status AS shaidan_status FROM ".$GLOBALS['ecs']->table('comment')." AS c 
				LEFT JOIN ".$GLOBALS['ecs']->table('users')." AS u ON c.user_id=u.user_id
				LEFT JOIN ".$GLOBALS['ecs']->table('shaidan')." AS s ON c.rec_id=s.rec_id
				WHERE c.id_value = '$goods_id' AND c.status = 1 AND c.comment_rank > 0 $where ORDER BY c.add_time DESC";
		$res = $GLOBALS['db']->selectLimit($sql, $size, ($page-1) * $size);
		$points_list = array();
		
		while ($row = $GLOBALS['db']->fetchRow($res))
		{
			//print_r($row['rec_id']);
			$row['goods_attr'] = $GLOBALS['db']->getOne("SELECT og.goods_attr as goods_attr FROM ".$GLOBALS['ecs']->table('order_info')." AS o
													   LEFT JOIN ".$GLOBALS['ecs']->table('order_goods')." AS og ON o.order_id=og.order_id
													   WHERE og.rec_id = '$row[rec_id]'");
			
			$row['add_time_str'] = local_date("Y-m-d H:i:s", $row['add_time']);
			$row['buy_time_str'] = local_date("Y-m-d H:i:s", $row['buy_time']);
			$row['user_rank'] = get_user_rank($row['user_id']);
			$row['headimg'] = !empty($row['headimg']) ? str_replace("./../","",$row['headimg']) : 'data/default/sex'.$row['sex'].'.png';//头像
			if ($row['shaidan_id'] > 0 && $row['shaidan_status'] == 1)
			{
				$row['shaidan_imgs'] = $GLOBALS['db']->getAll("SELECT * FROM ".$GLOBALS['ecs']->table('shaidan_img')." WHERE shaidan_id = '$row[shaidan_id]'");
				$row['shaidan_imgs_num'] = count($row['shaidan_imgs']);
			}else{
				$row['shaidan_imgs'] = array();
				$row['shaidan_imgs_num'] = 0;
			}
			if ($row['comment_tag'] && $row['comment_tag'] != 'Array')
			{
				$comment_tag = explode(',',$row['comment_tag']);	
				foreach ($comment_tag as $tag_id)
				{	
					$row['tags'][] = isset($tags[$tag_id])?$tags[$tag_id]:'';
				}
			}
			
			$parent_res = $GLOBALS['db']->getAll("SELECT * FROM ".$GLOBALS['ecs']->table('comment')." WHERE parent_id = '$row[comment_id]'");	
			$row['comment_reps'] = $parent_res;

			// 点赞数
			$row['comment_zan'] = $GLOBALS['db']->getOne("SELECT COUNT(*) FROM ".$GLOBALS['ecs']->table('comment_zan')." WHERE comment_id = '$row[comment_id]'");
			$row['has_zan'] = $GLOBALS['db']->getOne("SELECT COUNT(*) FROM ".$GLOBALS['ecs']->table('comment_zan')." WHERE comment_id = '$row[comment_id]' AND user_id = '$user_id'");
			
			$item_list[] = $row;
		}
		
		
		$arr = array();
		$arr['item_list'] = $item_list;
		$arr['comment_rank_avg'] = $comment_rank_avg;
		$arr['page'] = $page;
		$arr['count'] = $count;
		$arr['size'] = $size;
		$arr['page_count'] = $page_count;
		for ($i = 1 ; $i <= $page_count ; $i ++)
		{
			$arr['page_number'][$i] = "ShowMyComments($goods_id,$type,$i)";
		}
		
		return $arr;
	}
	else
	{
		$count = $GLOBALS['db']->getOne("SELECT COUNT(*) FROM ".$GLOBALS['ecs']->table('shaidan')." AS s 
										 WHERE s.goods_id = '$goods_id' AND s.status = 1");
		$size  = 20;
		$page_count = ($count > 0) ? intval(ceil($count / $size)) : 1;
	
		$sql = "SELECT s.*, u.user_name, u.headimg, u.sex, c.comment_tag, c.comment_rank, c.comment_id FROM ".$GLOBALS['ecs']->table('shaidan')." AS s 
				LEFT JOIN ".$GLOBALS['ecs']->table('users')." AS u ON s.user_id=u.user_id
				LEFT JOIN ".$GLOBALS['ecs']->table('comment')." AS c ON c.rec_id=s.rec_id
				WHERE s.goods_id = '$goods_id' AND s.status = 1 ORDER BY s.add_time DESC";
		$res = $GLOBALS['db']->selectLimit($sql, $size, ($page-1) * $size);
		$points_list = array();
		while ($row = $GLOBALS['db']->fetchRow($res))
		{
			$row_n = $GLOBALS['db']->getRow("SELECT o.add_time as buy_time,og.goods_attr as goods_attr FROM ".$GLOBALS['ecs']->table('order_info')." AS o
													   LEFT JOIN ".$GLOBALS['ecs']->table('order_goods')." AS og ON o.order_id=og.order_id
													   WHERE og.rec_id = '$row[rec_id]'");
			$row['add_time_str'] = local_date("Y-m-d H:i:s", $row['add_time']);
			$row['buy_time_str'] = local_date("Y-m-d H:i:s", $row_n['buy_time']);
			$row['user_rank'] = get_user_rank($row['user_id']);
			$row['goods_attr'] = $row_n['goods_attr'];
			$row['headimg'] = !empty($row['headimg']) ? str_replace("./../","",$row['headimg']) : 'data/default/sex'.$row['sex'].'.png';//头像
			if ($row['shaidan_id'] > 0)
			{
				$row['shaidan_imgs'] = $GLOBALS['db']->getAll("SELECT * FROM ".$GLOBALS['ecs']->table('shaidan_img')." WHERE shaidan_id = '$row[shaidan_id]'");	
				$row['shaidan_imgs_num'] = count($row['shaidan_imgs']);
			}
			if ($row['comment_tag'])
			{
				$comment_tag = explode(',',$row['comment_tag']);	
				foreach ($comment_tag as $tag_id)
				{
					$row['tags'][] = $tags[$tag_id];
				}
			}
			$row['content'] = $row['message'];
			$row['hide_gnum'] = 1;
			if ($row['comment_id'] > 0)
			{
				$parent_res = $GLOBALS['db']->getAll("SELECT * FROM ".$GLOBALS['ecs']->table('comment')." WHERE parent_id = '$row[comment_id]'");	
				$row['comment_reps'] = $parent_res;

				// 点赞数
				$row['comment_zan'] = $GLOBALS['db']->getOne("SELECT COUNT(*) FROM ".$GLOBALS['ecs']->table('comment_zan')." WHERE comment_id = '$row[comment_id]'");
				$row['has_zan'] = $GLOBALS['db']->getOne("SELECT COUNT(*) FROM ".$GLOBALS['ecs']->table('comment_zan')." WHERE comment_id = '$row[comment_id]' AND user_id = '$user_id'");
			}
			$item_list[] = $row;
		}
		
		
		$arr = array();
		$arr['item_list'] = $item_list;
		$arr['comment_rank_avg'] = $comment_rank_avg;
		$arr['page'] = $page;
		$arr['count'] = $count;
		$arr['size'] = $size;
		$arr['page_count'] = $page_count;
		for ($i = 1 ; $i <= $page_count ; $i ++)
		{
			$arr['page_number'][$i] = "ShowMyComments($goods_id,$type,$i)";
		}

		return $arr;
	}
}


function get_user_rank($user_id)
{
	if ($user_id <= 0)
	{
		$arr['rank_id'] = 0;
		$arr['rank_name'] = '普通用户';
	}
	else
	{
		$infos = $GLOBALS['db']->getRow("select * from ".$GLOBALS['ecs']->table('users')." where user_id='$user_id'");
		if ($infos['user_rank'] > 0)
		{
			$sql = "SELECT rank_id, rank_name, discount FROM ".$GLOBALS['ecs']->table('user_rank') . " WHERE rank_id = '$infos[user_rank]'";
		}
		else
		{
			$sql = "SELECT rank_id, rank_name, discount, min_points FROM ".$GLOBALS['ecs']->table('user_rank') .
				   " WHERE min_points<= " . intval($infos['rank_points']) . " ORDER BY min_points DESC";
		}
	
		if ($row = $GLOBALS['db']->getRow($sql))
		{
			$rank_name     = $row['rank_name'];
		}
		else
		{
			$rank_name = $GLOBALS['_LANG']['undifine_rank'];
		}
		
		$arr['rank_id'] = $row['rank_id'];
		$arr['rank_name'] = $rank_name;
	}
    return $arr;
}


function array_sort($arr,$keys,$type='asc')
{ 
	$keysvalue = $new_array = array();
	foreach ($arr as $k=>$v){
		$keysvalue[$k] = $v[$keys];
	}
	if($type == 'asc'){
		asort($keysvalue);
	}else{
		arsort($keysvalue);
	}
	reset($keysvalue);
	foreach ($keysvalue as $k=>$v){
		$new_array[$k] = $arr[$k];
	}
	return $new_array; 
} 
?>