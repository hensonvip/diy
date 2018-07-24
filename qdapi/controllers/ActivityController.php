<?php

/**
 * 活动
 *
 * User: vincent.cao
 * Date: 14-9-10
 * Time: 下午2:37
 */
class ActivityController extends BaseController
{
    public function getPromotion()
    {
        $new_time=time();
        $num = isset($_GET['limit']) ? intval($_GET['limit']) : 0;
        $limit = $num > 0 ? ' LIMIT '.$num : '';
        $data = $brand_list = $activity_list = array();
        //获取推广品牌：（排序是100，101，102的）
        $sql = 'SELECT * FROM ' . $GLOBALS['ecs']->table('brand') .
            " WHERE sort_order IN ('100','101','102')";
        $res=$GLOBALS['db']->query($sql);
        while($row = $GLOBALS['db']->fetchRow($res))
        {
            $brand_list[$row['brand_id']] = $row;
        }
        if(!$brand_list)
        {
            Response::render($data);
        }

        //获取指定推广品牌，在展示时间内，属于品牌专场，活动状态为上架
        $sql='SELECT * FROM '.$GLOBALS['ecs']->table('special_activity').
            " WHERE brand_id " . db_create_in(array_keys($brand_list)) .
            " AND `start_time`<'$new_time'  AND `end_time`>'$new_time' AND `act_type`='1' AND `act_status`='3'".
            " GROUP BY brand_id".
            " ORDER BY start_time DESC" . $limit;
        $res=$GLOBALS['db']->query($sql);
        while($row = $GLOBALS['db']->fetchRow($res))
        {
            $row['brand_logo'] = isset($brand_list[$row['brand_id']]) ? $brand_list[$row['brand_id']]['brand_logo'] : '';
            $row['brand_name'] = isset($brand_list[$row['brand_id']]) ? $brand_list[$row['brand_id']]['brand_name'] : '';
            $activity_list[$row['act_id']] = $row;
        }
        if(!$activity_list)
        {
            Response::render($data);
        }

        //获取指定活动是否有显示的商品
        $sql='SELECT act_id, count(act_id) AS on_sale_num FROM ' . $GLOBALS['ecs']->table('special_goods').
            " WHERE act_id " . db_create_in(array_keys($activity_list)) .
            " AND is_show = 1 GROUP BY act_id";
        $res=$GLOBALS['db']->query($sql);
        while($row = $GLOBALS['db']->fetchRow($res))
        {
            if($row['on_sale_num']>0 && isset($activity_list[$row['act_id']]))
            {
                $activity_list[$row['act_id']]['image_url_format'] = $this->_getImgDomain() . $activity_list[$row['act_id']]['image_url'];
                $activity_list[$row['act_id']]['brand_logo_format'] = $this->_getImgDomain() . $activity_list[$row['act_id']]['brand_logo'];
                $data[] = $activity_list[$row['act_id']];
            }
        }

        Response::render($data);
    }

}
