<?php
/**
 * 创意擂台
 */
require_once(ROOT_PATH . 'includes/cls_diy.php');
require_once(ROOT_PATH . 'includes/cls_user.php');
require_once(ROOT_PATH . 'includes/lib_common.php');
include_once(ROOT_PATH . '/includes/cls_image.php');


class OriginalityController extends ApiController
{
    public function __construct()
    {
        parent::__construct();
        $this->data = $this->input();
        $this->diy = cls_diy::getInstance();
        $this->user = cls_user::getInstance();
    }

    public function index()
    {
        // 获取广告图
        $adv_list = array();
        $adv_list['bg_first'] = get_advlist_("创意擂台背景一");
        $adv_list['bg_second'] = get_advlist_("创意擂台背景二");
        $adv_list['bg_third'] = get_advlist_("创意擂台背景三");
        $data['adv_list'] = $adv_list;

        $this->success($data);
    }

    //比赛详情
    public function matchRule(){
        $data['time_data'] = $this->diy->originality();
        //当前时间
        $matct_time=gmtime();
        //征集期时间
        $collect_start_time =  $data['time_data']['collect_start_time'];
        $collect_end_time =  $data['time_data']['collect_end_time'];
        //投票期时间
        $vote_start_time =  $data['time_data']['vote_start_time'];
        $vote_end_time =  $data['time_data']['vote_end_time'];
        //公示期时间
        $publicity_start_time =  $data['time_data']['publicity_start_time'];
        //导航栏nav定向
        if($collect_start_time<=$matct_time && $matct_time<$vote_start_time){
            $data['code']=ORY_COLLECT;//征集期时间
            $data['left_title'] = "我要参赛";
            $data['url'] = "/originality/index";
        }elseif($vote_start_time<=$matct_time && $matct_time<$publicity_start_time){
            $data['code']=ORY_VOTE;//投票期时间
            $data['left_title'] = "我要投票";
            $data['url'] = "/originality/vote_list";
        }elseif($vote_end_time<=$matct_time){
            $data['code']=ORY_PUBLICITY;//公示期时间
            $data['left_title'] = "获奖公示";
            $data['url'] = "/originality/open_list";
        }else{
            $data['code']=ORY_NOT;
            $data['left_title'] = "当前无赛事";
            $data['url'] = "/originality/index";
        }
        $this->success($data);
    }

    //diy参赛列表
    public function diyList(){
        $user_id = $this->input('user_id',0);
        $state = $this->input('state',0);

        //查询比赛活动ID及时间
        $data = $this->diy->originality();
        //diy作品征集开始时间，为防止比赛ID与以前相同
        $collect_start_time =  $data['collect_start_time'];

        //关联查询参赛作品列表 diy_record diy diy_vote
        $diy_sql = "SELECT * FROM".$GLOBALS['ecs']->table('diy_record').
            " A LEFT JOIN ".$GLOBALS['ecs']->table('diy'). " B ON A.diy_id = B.diy_id LEFT JOIN ".
            $GLOBALS['ecs']->table('user')."C ON  WHERE B.user_id='$user_id' AND A.add_time > '$collect_start_time' AND state = '$state'";
        $list_data = $GLOBALS['db']->getAll($diy_sql);

        //统计单个作品的票数
    }

    //设计者排行
    public function voteUserList(){
        $user_id    = isset($this->data['user_id'])? $this->data['user_id'] : 0;
        $page       = !empty($this->data['page'])?intval($this->data['page']):1;
        $page_size  = !empty($this->data['page_size'])?intval($this->data['page_size']):10;
        $only_count  = !empty($this->data['only_count'])?intval($this->data['only_count']):0;
        $page_start = $page_size*($page-1);

        $originality_data = $this->diy->originality();
        if(empty($originality_data)){
            $this->error("暂无征集比赛");
        }
        $collect_start_time =  $originality_data['collect_start_time'];

        $sql  = "SELECT C.user_id as user_id,C.headimg as face_card,C.user_name as name,COUNT(D.record_id) as vote_count FROM ".
                     $GLOBALS['ecs']->table('diy_record')." A LEFT JOIN ".
                     $GLOBALS['ecs']->table('diy'). " B ON A.diy_id = B.diy_id LEFT JOIN ".
                     $GLOBALS['ecs']->table('users')." C ON B.user_id = C.user_id LEFT JOIN ".
                     $GLOBALS['ecs']->table('diy_vote')." D ON A.record_id = D.record_id AND  D.add_time >= ".$originality_data['vote_start_time']." AND D.add_time <= ".$originality_data['vote_end_time'].
                     " WHERE  A.originality_id = ".$originality_data['id']." AND A.state = ".ORY_RED_PASS." AND A.add_time >= ".$originality_data['collect_start_time']." AND A.add_time <= ".$originality_data['collect_end_time'].
                     " group by B.user_id ";
        $sql_count      = "SELECT count(user_id) from (".$sql.") as t";
        $count          = $GLOBALS['db']->getOne($sql_count);
        if(!$only_count){        
            $sql_data       = $sql." order by vote_count desc LIMIT ".$page_start.', '.$page_size;
            $vote_user_list = $GLOBALS['db']->getAll($sql_data);

            if(empty($vote_user_list)){
                $this->error("暂无设计者参赛");
            }
            foreach ($vote_user_list as $key => $value) {
                $vote_user_list[$key]['fansi'] = $GLOBALS['db']->getOne("SELECT count(id) from ".$GLOBALS['ecs']->table('user_attention')." where be_user_id = ".$value['user_id']);
                $u_to_author = $GLOBALS['db']->getOne("SELECT id from ".$GLOBALS['ecs']->table('user_attention')." where be_user_id = ".$value['user_id']." AND user_id = ".$user_id);
                $author_to_u = $GLOBALS['db']->getOne("SELECT id from ".$GLOBALS['ecs']->table('user_attention')." where be_user_id = ".$user_id." AND user_id = ".$value['user_id']);
                $vote_user_list[$key]['is_user_attention'] = $u_to_author > 0 ? ($author_to_u > 1 ? USER_ATTENTION_BOTH :USER_ATTENTION ) :USER_ATTENTION_NO;

                //点赞数
                $user_zan_sql = "SELECT count(zan_id) FROM ".$GLOBALS['ecs']->table('diy_zan')." where author_id = ".$value['user_id']." AND add_time >= $collect_start_time";
                $vote_user_list[$key]['zan_count'] = $GLOBALS['db']->getOne($user_zan_sql);
            
                
                
            }
            //分页
            $pager                 = array();
            $pager['page']         = $page;
            $pager['page_size']    = $page_size;
            $pager['page_count']   = $page_count = ($count > 0) ? intval(ceil($count / $page_size)) : 1;
        }
        $pager['record_count'] = $count;
        $list_data['list']     = $vote_user_list;
        $list_data['pager']    = $pager;
        $this->success($list_data);
    }

    //已征集作品列表 state=2
    //我的参赛列表：state=2已通过 or state=0待审核 or state=1未通过  AND user_id不为空
    public function productList(){
        $page_size  = $this->input('page_size',6);//一页多少条
        $page  = $this->input('page',1);//第几页
        $vote_uid  = $this->input('vote_uid');//投票者的id
        $user_id  = $this->input('user_id');//作者id
        $state = $this->input('state',2);
        //$state = 2;
        //查询比赛活动ID及时间
        $data = $this->diy->originality();
        //diy作品征集开始时间，为防止比赛ID与以前相同
        $collect_start_time =  $data['collect_start_time'];
        //$collect_end_time =  $data['collect_end_time'];

        //投票期时间
        $vote_start_time =  $data['vote_start_time'];
        $vote_end_time =  $data['vote_end_time'];

        $time = " A.add_time >= $collect_start_time AND A.add_time < $vote_end_time ";

        $oid = $data['id'];
        if($state == 2){
            $str = "A.record_id as record_id,C.user_id as user_id,B.diy_id as diy_id,C.user_name as name,C.headimg as face_photo,B.design_img_t as img,E.title as title,COUNT(if(D.add_time >= '$vote_start_time' AND D.add_time <= '$vote_end_time',D.vote_id,null)) as vote_count";
            $order = "";
            $group = "A.record_id";
            //$this->success($this->listdata($user_id,$str,$oid,$state,$time,$group,$order));
            $list_count = count($this->listdata($user_id,$str,$oid,$state,$time,$group,$order));
            $start = $page_size*($page-1);//从第几条开始
            $order = " LIMIT $start,$page_size";
            $list_data = $this->listdata($user_id,$str,$oid,$state,$time,$group,$order);
            //print_r($list_data);exit;
        }else{
            $str = "A.record_id as record_id,C.user_id as user_id,B.diy_id as diy_id,C.user_name as name,C.headimg as face_photo,B.design_img_t as img,E.title as title";
            $order = "";
            $group = "B.diy_id";
            //$this->success($this->listdata($user_id,$str,$oid,$state,$time,$group,$order));
            $list_count = count($this->listdatas($user_id,$str,$oid,$state,$time,$group,$order));
            $start = $page_size*($page-1);//从第几条开始
            $order = " LIMIT $start,$page_size";
            $list_data = $this->listdatas($user_id,$str,$oid,$state,$time,$group,$order);
        }

        //是否已投票
        $time = " AND add_time >= '$vote_start_time' AND add_time < '$vote_end_time'";
        foreach($list_data as $k=>$v){
            $vote = $this->voteData($v['record_id'],$vote_uid,$time);
            if(empty($vote)){
                $list_data[$k]['vote'] = 0;
            }else{
                $list_data[$k]['vote'] = 1;
            }
        }
        //分页
        $pager                 = array();
        $pager['page']         = $page;
        $pager['page_size']    = $page_size;
        $pager['page_count']   = $page_count = ($list_count > 0) ? intval(ceil($list_count / $page_size)) : 1;
        $pager['count']   = $list_count;
        $pager['this_count']   = count($list_data);

        $list['list'] = $list_data;
        $list['count'] = $list_count;
        $list['pager'] = $pager;

        $this->success($list);
    }

    /**
     * 列表数据函数
     * @param $user_id
     * @param $str
     * @param $oid
     * @param $state
     * @param $time
     * @param $group
     * @param $order
     * @return mixed
     */
    public function listdata($user_id,$str,$oid,$state,$time,$group,$order){
        $id="";
        if(!empty($user_id)||$user_id!=0){
            $id .= " AND B.user_id = '$user_id'";
        }
        $diy_sql = "SELECT ".$str." FROM".$GLOBALS['ecs']->table('diy_record')." A LEFT JOIN ".
            $GLOBALS['ecs']->table('diy'). " B ON A.diy_id = B.diy_id LEFT JOIN ".
            $GLOBALS['ecs']->table('users')." C ON B.user_id = C.user_id LEFT JOIN".
            $GLOBALS['ecs']->table('diy_vote')." D ON A.record_id = D.record_id LEFT JOIN".
            $GLOBALS['ecs']->table('diy_info')." E ON E.record_id = A.record_id  WHERE  A.originality_id = '$oid' AND A.state = '$state' AND $time $id group by ".$group." ".$order;
        $list_data = $GLOBALS['db']->getAll($diy_sql);
        //print_r($diy_sql);exit;
        return $list_data;
    }

    //列表数据函数 ---待审核 未通过
    public function  listdatas($user_id,$str,$oid,$state,$time,$group,$order){
        $id="";
        if(!empty($user_id)||$user_id!=0){
            $id .= " AND B.user_id = '$user_id'";
        }
        $diy_sql = "SELECT ".$str." FROM".$GLOBALS['ecs']->table('diy_record')." A LEFT JOIN ".
            $GLOBALS['ecs']->table('diy'). " B ON A.diy_id = B.diy_id LEFT JOIN ".
            $GLOBALS['ecs']->table('users')." C ON B.user_id = C.user_id LEFT JOIN ".
            $GLOBALS['ecs']->table('diy_info')." E ON A.record_id = E.record_id WHERE  A.originality_id = '$oid' AND A.state = '$state' AND $time $id group by ".$group." ".$order;
        $list_data = $GLOBALS['db']->getAll($diy_sql);
        //print_r();exit;
        return $list_data;

    }

    //ajax 投票
    public function ajax_vote(){
        $record_id = $this->input('record_id');
        $user_id = $this->input('user_id');

        //查询比赛活动ID及时间
        $data_time = $this->diy->originality();
        //diy作品征集开始时间，为防止比赛ID与以前相同
        $collect_start_time =  $data_time['collect_start_time'];
        //投票期
        $vote_start_time =  $data_time['vote_start_time'];
        $vote_end_time =  $data_time['vote_end_time'];

        $time = "AND add_time >= $vote_start_time";

        $select=$this->voteData($record_id,$user_id,$time);

        $addtime =time();

        if(!empty($select)){
            $data = '0';//已投票
        }elseif($vote_end_time < $addtime) {
            $data = '3';//已过投票期
        }else{
            $sql = "INSERT INTO".$GLOBALS['ecs']->table('diy_vote')." (`record_id`,`user_id`,`add_time`) VALUES ('$record_id','$user_id','$addtime')";
            $insert = $GLOBALS['db']->query($sql);
            if($insert){
                $data = '1';//成功
            }else{
                $data = '2';//失败
            }
        }
        $this->success($data);
    }

    //查询是否已投票数据
    public function voteData($record_id,$user_id,$time){
        $select_sql = "SELECT vote_id FROM ".$GLOBALS['ecs']->table('diy_vote')." WHERE record_id = '$record_id' AND user_id = '$user_id' $time";
        $select = $GLOBALS['db']->getOne($select_sql);
        return $select;
    }

    // 已有作品参赛列表(筛掉出售未通过，已参赛作品)
     public function completeProductList(){
         $user_id = $this->input('user_id',0);

         $page       = !empty($this->data['page'])?intval($this->data['page']):1;
         $page_size  = !empty($this->data['page_size'])?intval($this->data['page_size']):8;
         $only_count  = !empty($this->data['only_count'])?intval($this->data['only_count']):0;
         $page_start = $page_size*($page-1);

         //查询比赛活动ID及时间
         $data = $this->diy->originality();
         //diy作品征集开始时间，为防止比赛ID与以前相同
         $collect_start_time =  $data[0]['collect_start_time'];
         $collect_end_time =  $data[0]['collect_end_time'];

         $where = "";
         if($collect_start_time&&$collect_end_time){
             $where .= " AND '$collect_start_time'<= C.add_time AND C.add_time <='$collect_end_time'";
         }

/*         $count_sql = "SELECT COUNT('A.diy_title') FROM ".$GLOBALS['ecs']->table('diy')." A LEFT JOIN ".$GLOBALS['ecs']->table('goods').
                " B ON A.goods_id = B.goods_id WHERE A.user_id = $user_id AND B.goods_status != 3 AND A.diy_id NOT IN (SELECT `diy_id` FROM ".$GLOBALS['ecs']->table('diy_record')." C WHERE C.diy_id = A.diy_id  $where)";

         $sql = "SELECT A.diy_title as title,A.design_img as imgs,A.diy_id as diy_id FROM ".$GLOBALS['ecs']->table('diy')." A LEFT JOIN ".$GLOBALS['ecs']->table('goods').
             " B ON A.goods_id = B.goods_id WHERE A.user_id = $user_id AND B.goods_status != 3 AND A.diy_id NOT IN (SELECT `diy_id` FROM ".$GLOBALS['ecs']->table('diy_record')." C WHERE C.diy_id = A.diy_id  $where) LIMIT " .$page_start.','.$page_size;*/
         $count_str = " COUNT('A.diy_title') ";
         $count_sql = $this->completeProductList_fun($count_str,$user_id,$where);
         $str = " A.diy_title as title,A.design_img_t as imgs,A.diy_id as diy_id,B.cat_id ";
         $sql = $this->completeProductList_fun($str,$user_id,$where,$page_start,$page_size);

         $select = $GLOBALS['db']->getAll($sql);
         $count = $GLOBALS['db']->getOne($count_sql);

         //分页
         $pager                 = array();
         $pager['page']         = $page;
         $pager['page_size']    = $page_size;
         $pager['page_count']   = $page_count = ($count > 0) ? intval(ceil($count / $page_size)) : 1;
         $this->success($select);
     }

    //已有参赛作品列表sql_function
    function completeProductList_fun($str,$user_id,$where,$page_start,$page_size){
        $page = "";
        if(!empty($page_start)&&!empty($page_size)){
            $page = " LIMIT $page_start','$page_size";
        }
        $sql = "SELECT $str FROM ".$GLOBALS['ecs']->table('diy')." A LEFT JOIN ".$GLOBALS['ecs']->table('goods').
            " B ON A.goods_id = B.goods_id WHERE A.user_id = $user_id AND B.goods_status != 3 AND A.diy_id NOT IN (SELECT `diy_id` FROM ".$GLOBALS['ecs']->table('diy_record')." C WHERE C.diy_id = A.diy_id  $where) $page";
        return $sql;
    }

    //diy作品信息提交
    public function diyInfoAjax(){
        $array = $this->input('info');
        $user_id = $this->input('user_id');

        $title = $array['title'];
        $type = $array['type'];
        $describe = $array['describe'];
        $diy_id = empty($array['diy_id'])? '' : $array['diy_id'];
        $design_img_t = strstr("images",$array['img_t']);
        //var_dump($array['img_t']);exit;
        //$design_img_t=$this->diy_images($file,$design_img_t);

        //立即设计提交的图片
        //$file = 'diy';
        /*if(isset($array['design_img'])&&!empty($array['design_img'])){
            $design_img = $array['design_img'];
            $design_img = $this->diy_images($file,$design_img);
            $design_img_t = $this->diy_images($file,$array['img_t']);
            $file_id = $array['file_id'];
            $design_session="";
            if(!empty($file_id)){
                $design_session = $GLOBALS['db']->getOne("SELECT `design_session` FROM ".$GLOBALS['ecs']->table('diy_file')."WHERE file_id = $file_id ");
            }
            $thistime = time();
            $sql_ins = "insert into ".$GLOBALS['ecs']->table('diy')." (`design_img`,`add_time`,`design_img_t`,`type`,`user_id`,`design_session`) values ('$design_img','$thistime','$design_img_t',2,'$user_id','$design_session')";
            $GLOBALS['db']->query($sql_ins);
            $diy_id = $GLOBALS['db']->insert_id();
        }*/
        if(isset($array['goods_id'])&&!empty($array['goods_id'])){
            $diy_id_sql = "SELECT `diy_id` FROM ".$GLOBALS['ecs']->table('diy')." WHERE goods_id = ".$array['goods_id']; 
            $diy_id = $GLOBALS['db']->getOne($diy_id_sql);
            $GLOBALS['db']->query('UPDATE '.$GLOBALS['ecs']->table('diy').'set type = 2 WHERE goods_id='.$array['goods_id']);
        }


        $originality = $this->diy->originality();
        $originality_id = $originality['id'];

        //图片 imgs

        foreach($array['imgs'] as $key=>$value ){
            $img_url =$this->diy_images($file,$value);
            $imgs_str[$key] = $img_url;
        }
        $img_str = implode(",",$imgs_str);

        //标签 production_tags
        //$str = implode("','",$array['tags']);
        $str =  db_create_in($array['tags']);
        $select_sql = "select tags_id,tags_name from ".$GLOBALS['ecs']->table('production_tags')." where `tags_name` ".$str;
        $select =  $GLOBALS['db']->getAll($select_sql);
        //$this->success($select);
        if(!empty($select)){
            foreach($select as $key=>$value){
                $tag_id[$key] = $value['tags_id'];
                $tag_name[$key] = $value['tags_name'];
            }
            $tags_id_str = implode(",",$tag_id);
            //$selects = implode("','",$tag_id);
            $selects = db_create_in($tag_id);
            $update_sql = "update ".$GLOBALS['ecs']->table('production_tags')."set number = number+1 where tags_id ".$selects;
            $GLOBALS['db']->query($update_sql);
            //var_dump($update_sql);exit;
        }
        $array_diff = array_diff($array['tags'],$tag_name);
        if($array['tags'] != $tag_name){
            $array_merge = array_unique(array_merge($array['tags'],$tag_name));
            $array_diff = empty($array_diff)?$array_merge:$array_diff;
        }
        $time = time();
        //var_dump($array_diff);exit;
        //是否有新标签处理
        if(!empty($array_diff)){
            foreach($array_diff as $key=>$value){
                $arr = "('".$value."','".$time."')";
                $insert_sql = "insert into ".$GLOBALS['ecs']->table('production_tags')." (`tags_name`,`addtime`) values $arr";
                $GLOBALS['db']->query($insert_sql);
                $id[] = $GLOBALS['db']->insert_id();
            }
            //$str = implode(",",$arr);
            //var_dump($id);exit;
            $tags_id = array_unique(array_merge($id,$tag_id));
            $tags_id_str = implode(",",$tags_id);
        }
        $diy_sql = "SELECT design_img,design_img_t FROM".$GLOBALS['ecs']->table('diy')." where diy_id = $diy_id AND user_id = $user_id ";
        if((string)$GLOBALS['db']->getRow($diy_sql)){
            $tupian = $GLOBALS['db']->getRow($diy_sql);
            if(empty($design_img_t)){
                $design_img_t = $tupian['design_img_t'];
            }
            $design_img = $tupian['design_img'];
        }


        //$this->success($insert_sql);

        $record_sql = "insert into ".$GLOBALS['ecs']->table('diy_record')." (`diy_id`,`user_id`,`originality_id`,`add_time`) values ('$diy_id','$user_id','$originality_id','$time')";
        if((string)$GLOBALS['db']->query($record_sql)){
            //数据插入
            $record_id = $GLOBALS['db']->insert_id();

            $info_sql = "insert into ".$GLOBALS['ecs']->table('diy_info')." (`diy_id`,`record_id`,`title`,`type`,`design_img`,`design_img_t`,`describe`,`tags`,`imgs`,`add_time`) values ('$diy_id','$record_id','$title','$type','$design_img','$design_img_t','$describe','$tags_id_str','$img_str','$time')";
            $GLOBALS['db']->query($info_sql);
            $data = 1 ;
        }else{
            $data = 0 ;
        }
        $this->success($data);
    }


    /**
     * 图片处理
     * @param $file 存放图片的根目录
     * @param $base64_image_content 图片base64串
     * @return int|string
     */
    public function diy_images($file,$base64_image_content){
        $image = new cls_image($GLOBALS['_CFG']['bgcolor']);
        //将base64编码转换为图片保存
        if (preg_match('/^(data:\s*image\/(\w+);base64,)/', $base64_image_content, $result)) {
            $type = $result[2];
            $path = DATA_DIR . '/'.$file.'/' . date('Ym') . '/';
            $new_file = ROOT_PATH . $path;
            if (!file_exists($new_file)) {
                //检查是否有该文件夹，如果没有就创建，并给予最高权限
                mkdir($new_file, 0777);
            }
            $img = $image->unique_name($path) . ".{$type}";
            $new_file = $new_file . $img;
            $url = $path . $img;

            //将图片保存到指定的位置
            if (file_put_contents($new_file, base64_decode(str_replace($result[1], '', $base64_image_content)))) {
                return $url;
            }else{
                return 0;
            }
        }else{
            return 0;
        }
    }

    //公示sql
    function open_sql($str,$oid,$grade,$collect_start_time){
        $diy_sql = "SELECT $str FROM".$GLOBALS['ecs']->table('diy_record')." A LEFT JOIN ".
            $GLOBALS['ecs']->table('diy'). " B ON A.diy_id = B.diy_id LEFT JOIN ".
            $GLOBALS['ecs']->table('users')." C ON B.user_id = C.user_id LEFT JOIN".
            $GLOBALS['ecs']->table('diy_vote')." D ON A.record_id = D.record_id WHERE  A.originality_id = '$oid' $grade AND A.state = 2 AND A.add_time >= '$collect_start_time' group by A.record_id order by vote_count desc";
        return $diy_sql;
    }


    //公示列表数据
    function open_list(){
        //查询比赛活动ID及时间
        $data_time = $this->diy->originality();
        //diy作品征集开始时间，为防止比赛ID与以前相同
        $collect_start_time =  $data_time['collect_start_time'];
        //投票期
        $vote_start_time =  $data_time['vote_start_time'];
        $vote_end_time =  $data_time['vote_end_time'];
        //比赛id
        $oid = $data_time['id'];

        $str = "A.record_id as record_id,C.user_id as user_id,B.diy_id as diy_id,C.user_name as name,C.face_card as face_photo,B.design_img as img,B.diy_title as title,COUNT(if(D.add_time >= '$vote_start_time' AND D.add_time <= '$vote_end_time',D.vote_id,null)) as vote_count";
        $sql = "SELECT * FROM ".$GLOBALS['ecs']->table('diy_reward')."WHERE is_show = 1 order by grade asc";
        $data['reward_list'] = $GLOBALS['db']->getAll($sql);
        if($data['reward_list']){
            foreach($data['reward_list'] as $k=>$v){
                $grades=$v['grade'];

                $grade =" AND A.grade = $grades";
               /* $sqls = "SELECT * FROM ".$GLOBALS['ecs']->table('diy')." diy LEFT JOIN ".$GLOBALS['ecs']->table('diy_record')." record ON diy.diy_id = record.diy_id ".
                    " WHERE record.grade = '$grade'  AND record.add_time >= '$collect_start_time'";
                $data[$k]['data'] = $GLOBALS['db']->getAll($sqls);*/

                $diy_sql =  $this->open_sql($str,$oid,$grade,$collect_start_time);
                
                $data['reward_list'][$k]['data'] = $GLOBALS['db']->getAll($diy_sql);

            }
            $grade_count =" AND A.grade > 0";
            $count = $this->open_sql($str,$oid,$grade_count,$collect_start_time);
            $list_data = $GLOBALS['db']->getAll($count);
            $data['count'] = count($list_data);
            $data['list'] = $list_data;

        }
        $this->success($data);


    }

/*    //根据value值归类 $arr （倒序排列的数组）
    public function value_sort($arr){
        $i=1;
        for($i;$i<count($arr);$i++){
            if($arr[$i]['count_vote'] == $arr[$i+1]['count_vote']){

            }
        }
    }*/




    /*---------弹窗详情模块---------*/
    //弹框数据
    public function framedata(){
        $record_id = $this->input('record_id',0);
        //$state = $this->input('state');
        $user_id = $this->input('user_id',0);//当前用户

        //查询比赛活动ID及时间
        $data_time = $this->diy->originality();
        //diy作品征集开始时间，为防止比赛ID与以前相同
        $collect_start_time =  $data_time['collect_start_time'];
        //投票期
        $vote_start_time =  $data_time['vote_start_time'];
        $vote_end_time =  $data_time['vote_end_time'];
        //赛事id
        $oid = $data_time['id'];

        //浏览量
        $sql = "UPDATE " . $GLOBALS['ecs']->table('diy_info') . " SET click_count = click_count + 1 WHERE record_id = '$record_id'";
        $GLOBALS['db']->query($sql);
        //作品信息
        $data['info'] = $this->productData($record_id,$state,$collect_start_time);

        //票数
        $data['info']['vote_num'] = $this->num('vote_id',$record_id,$vote_start_time,'diy_vote');

        //点赞数
        $data['info']['zan_num'] =  $this->num('zan_id',$record_id,$collect_start_time,'diy_zan');

        //评论数
        $data['info']['comment_num'] =  $this->num('comment_id',$record_id,$collect_start_time,'diy_comment',1);

        //是否已点赞
        $data['info']['if_zan'] =  $this->ifZan($record_id,$user_id,$collect_start_time);

        //作者信息
        $data['author'] = $this->user->user_name_card($data['info']['user_id']);

        //是否关注
        $data['attention'] = $this->attentionAuthor($user_id,$data['info']['user_id']);

        //带T恤的设计图
        $data['imgs_one'] = explode(",",$data['info']['design_img_t']);
        //设计图
        $data['img_one'] = explode(",",$data['info']['design_img']);
        //详情图
        $data['img_two'] = explode(",",$data['info']['imgs']);


        
        //是否已投票
        $time="AND add_time >= $collect_start_time";
        $select = $this->voteData($record_id,$user_id,$time);
        if(empty($select)){
            $data['vote_code'] = 0;
        }else{
            $data['vote_code'] = 1;
        }

        //是否已收藏
        $select_collect = $GLOBALS['db']->getRow("SELECT * FROM ".$GLOBALS['ecs']->table('collect_goods')." WHERE diy_id =". $data['info']['diy_id']);
        if(empty($select_collect)){
            $data['collect'] = 0;
        }else{
            $data['collect'] = 1;
        }
        //$this->success($data);

        //$data['vote_code'] = $this->vote($data['info']['record_id'],$user_id);
        //推荐 其他参赛作品 （除弹窗作品外的3个作品）
        $str = "C.user_id as user_id,A.record_id as record_id,B.diy_id as diy_id,B.design_img_t as img,B.diy_title as title,COUNT(if(D.add_time >= '$vote_start_time' AND D.add_time <= '$vote_end_time',D.vote_id,null)) as vote_count";
        $time =" A.add_time >= $collect_start_time AND A.record_id <> $record_id";
        $group = "D.record_id ";
        $order = " limit 3 ";
        $data['product_three'] =  $this->listdata($data['info']['user_id'],$str,$oid,$state=2,$time,$group,$order);
        //print_r($data['product_three']);
        //print_r($data);EXIT;
        $this->success($data);
    }

    /**
     * 作品数据
     * @param $record_id
     * @param $state
     * @param $collect_start_time
     * @return mixed
     */
    function productData($record_id,$state='',$collect_start_time){
        $where="";
        if(!empty($state)){
            $where .=" AND record.state = '$state'";
        }
        $sql = "SELECT *,info.type as `cat_id`,record.grade as `grade`,info.describe as `describe`,reward.describe as `prize_name`,info.add_time as add_time FROM ".$GLOBALS['ecs']->table('diy_info')." info LEFT JOIN ".$GLOBALS['ecs']->table('diy_record')." record ON info.record_id = record.record_id LEFT JOIN ".$GLOBALS['ecs']->table('diy_reward')." reward ON  reward.grade = record.grade".
            " WHERE record.record_id = '$record_id' $where  AND info.add_time >= '$collect_start_time' order by info.info_id desc limit 1 ";
        $info_data = $GLOBALS['db']->getRow($sql);

        //标签
        if(!empty($info_data['tags'])){
            $tags_str = $info_data['tags'];
            $tags_sql ="SELECT * FROM ".$GLOBALS['ecs']->table('production_tags')." WHERE tags_id in ($tags_str)";
            $tags = $GLOBALS['db']->getAll($tags_sql);
            foreach($tags as $k=>$v){
                $tags_arr[] = $v['tags_name'];
            }
            $info_data['tags_str'] = implode(",",$tags_arr);
        }else{
            $info_data['tags_str'] = '';
        }


        //类型
        if($info_data['cat_id'] != 0||!empty($info_data['cat_id'])){
            $catid = $info_data['cat_id'];
            $cat_sql = " SELECT `cat_name` FROM " .$GLOBALS['ecs']->table('category')." WHERE cat_id = $catid ";
            $info_data['category'] = $GLOBALS['db']->getOne($cat_sql);
        }else{
            $info_data['category'] = "";
        }
        return $info_data;
    }

    //统计 点赞数 票数 评论数
    function num($str,$record_id,$time,$table,$comment=0){
        $where="";
        if($comment == 1){
            $where .= "AND is_comment = 0";
        }

        $sql="SELECT COUNT(`$str`) FROM ".$GLOBALS['ecs']->table($table)." WHERE record_id = '$record_id' AND add_time >= '$time' $where ";

        $count = $GLOBALS['db']->getOne($sql);

        return $count;
    }


    //是否已点赞

    function ifZan($record_id,$user_id,$collect_start_time){

        $sql="SELECT `zan_id` FROM ".$GLOBALS['ecs']->table('diy_zan')." WHERE record_id = '$record_id' AND user_id = '$user_id' AND add_time >= '$collect_start_time' ";

        $select = $GLOBALS['db']->getOne($sql);
        if(!empty($select)){
            $data = 1;
        }else{
            $data = 0;
        }



        return $data;
    }

    /**
     * 查询是否已投票
     * @param $record_id
     * @param $user_id 投票的用户id
     * @return int
     */
    /*public function vote($record_id,$user_id){
        //$record_id = $this->input('record_id');
        //$user_id = $this->input('user_id');

        //查询比赛活动ID及时间
        $data = $this->diy->originality();
        //diy作品征集开始时间，为防止比赛ID与以前相同
        //投票期时间
        $vote_start_time =  $data['vote_start_time'];
        $vote_end_time =  $data['vote_start_time'];

        $time = " AND add_time >= '$vote_start_time' AND add_time <= '$vote_end_time'";

        $select=$this->voteData($record_id,$user_id,$time);
        if($select){
            $data = 1;
        }else{
            $data = 0;
        }
        return $data;
    }*/

    //查询是否已关注
    function attentionAuthor($user_id,$author_id){
        $u_to_author = $GLOBALS['db']->getOne("SELECT id from ".$GLOBALS['ecs']->table('user_attention')." where be_user_id = ".$author_id." AND user_id = ".$user_id);
        $author_to_u = $GLOBALS['db']->getOne("SELECT id from ".$GLOBALS['ecs']->table('user_attention')." where be_user_id = ".$user_id." AND user_id = ".$author_id);
        $result = $u_to_author > 0 ? ($author_to_u > 1 ? USER_ATTENTION_BOTH :USER_ATTENTION ) :USER_ATTENTION_NO;
        return $result;
    }


    //评论数据
    function commentData(){
        $record_id = $this->input('record_id');
        $id = $this->input('user_id');//是否为作者id

        $page       = !empty($this->data['page'])?intval($this->data['page']):1;
        $page_size  = !empty($this->data['page_size'])?intval($this->data['page_size']):2;
        $page_start = $page_size*($page-1);

        $where = "";
        if(!empty($record_id)||$record_id!=0){
            $where = "record_id = $record_id AND";
        }
        $count = $GLOBALS['db']->getOne("SELECT count(`comment_id`) FROM " .$GLOBALS['ecs']->table('diy_comment')." WHERE $where is_comment = 0");//评论
        $sql = "SELECT dc.comment_id as comment_id,dc.r_user_id as ruid,dc.c_user_id as cuid,dc.add_time as add_time,dc.content as content,count(cz.user_id) as zan FROM " .$GLOBALS['ecs']->table('diy_comment')." dc LEFT JOIN ".$GLOBALS['ecs']->table('comment_zan')."cz ON dc.comment_id =cz.comment_id  WHERE dc.$where dc.is_comment = 0 group by dc.comment_id order by dc.add_time desc limit $page_start,$page_size ";
        $comment = $GLOBALS['db']->getAll($sql);//评论

        if(!empty($comment)){
            foreach($comment as $k=>$v){
                $user_id = $v['cuid'];
                if($v['ruid'] == $id){
                    $comment[$k]['code'] = 1;//有回复区
                }else{
                    $comment[$k]['code'] = 0;//
                }
                $cid = $v['comment_id'];
                //是否点赞

                if($GLOBALS['db']->getOne("SELECT `user_id` FROM " .$GLOBALS['ecs']->table('comment_zan')." WHERE user_id = $id AND comment_id = $cid")){
                    $comment[$k]['ifzan'] = 1;//已点赞
                }else{
                    $comment[$k]['ifzan'] = 0;
                }

                //是否举报过
                if($GLOBALS['db']->getOne("SELECT `report_cid` FROM " .$GLOBALS['ecs']->table('report_comment')." WHERE user_id = $id AND comment_id = $cid")){
                    $comment[$k]['ifreport'] = 1;//已举报
                }else{
                    $comment[$k]['ifreport'] = 0;
                }

                $comment[$k]['user_data'] = $GLOBALS['db']->getRow("SELECT `user_id`,`user_name`,`headimg` FROM " .$GLOBALS['ecs']->table('users')." WHERE user_id = $user_id");//用户数据
                $is_comment = $v['comment_id'];
                $reply=$GLOBALS['db']->getAll("SELECT * FROM " .$GLOBALS['ecs']->table('diy_comment')." WHERE $where record_id = $record_id AND is_comment = $is_comment");//回复
                $comment[$k]['author_data'] = $GLOBALS['db']->getRow("SELECT `user_name` FROM " .$GLOBALS['ecs']->table('users')." WHERE user_id = ".$v['ruid']);//作者数据
                if($reply){
                    $comment[$k]['reply'] = $reply;
                }
            }
        }

        //分页
        $pager                 = array();
        $pager['page']         = $page;
        $pager['page_size']    = $page_size;
        $pager['page_count']   = $page_count = ($count > 0) ? intval(ceil($count / $page_size)) : 1;
        $pager['count']   = $count;

        $data['comment'] = $comment;
        $data['pager']= $pager;

        $this->success($data);
    }

/*    //私信 ajax
    function privateMessage(){
        $user_id = $this->input('user_id');
        $receive_user_id = $this->input('receive_user_id');//接收人
        $msg_content = $this->input('msg_content');

        if($this->user->send_Letter($user_id,$receive_user_id,$msg_content)){
            $result = 1;
        }else{
            $result = 0;
        }
        $this->success($result);
    }*/

    //举报评论
    function report(){
        $comment_id = $this->input('comment_id');
        $reason = $this->input('reason');
        $type = $this->input('type',0);
        $user_id = $this->input('user_id');

        $result = $this->user->report_comment($comment_id,$reason,$user_id,$type);

        $this->success($result);
    }



        //取消点赞
    function zan_reduce(){
        $user_id = $this->input('user_id');
        $record_id = $this->input('record_id');
        $diy_id = $this->input('diy_id');
        $sql = "DELETE FROM ".$GLOBALS['ecs']->table('diy_zan')."WHERE  user_id = '$user_id' AND record_id = '$record_id' AND diy_id = '$diy_id' "; 
        if((string)$GLOBALS['db']->query($sql)){
            $data = 1;//true
        }else{
            $data = 0;//fales
        }
         $this->success($data);
        
    }

    //点赞
    function zan_increase(){
        $user_id = $this->input('user_id');
        $record_id = $this->input('record_id');
        $diy_id = $this->input('diy_id');

        //查询比赛活动ID及时间
        $data_time = $this->diy->originality();
        //diy作品征集开始时间，为防止比赛ID与以前相同
        $collect_start_time =  $data_time['collect_start_time'];

        $ifzan = $this->ifZan($record_id,$user_id,$collect_start_time);
        if(empty($ifzan)){
            $addtime = gmtime();
            if(empty($diy_id)){
                $data = 0; 
                $this->success($data);   
            }
            $author_id = $GLOBALS['db']->getOne("SELECT user_id FROM ".$GLOBALS['ecs']->table('diy')." WHERE diy_id = $diy_id");
            $sql = "INSERT INTO ".$GLOBALS['ecs']->table('diy_zan')." (`user_id`,`record_id`,`author_id`,`diy_id`,`add_time`) VALUES ($user_id,$record_id,$author_id,$diy_id,$addtime)"; 
            if((string)$GLOBALS['db']->query($sql)){
                $data = 1;//true
            }else{
                $data = 0;//false
            }
        }else{
            $data = 0;
        }

        
         $this->success($data);

    }

    //评论设计作品
    function comment(){
        $user_id = $this->input('user_id');//评论的人
        $author_id = $this->input('author_id');//接收评论的人
        $record_id = $this->input('record_id');//作品id
        $content = $this->input('content');//

        //$user_IP = ($_SERVER["HTTP_VIA"]) ? $_SERVER["HTTP_X_FORWARDED_FOR"] : $_SERVER["REMOTE_ADDR"];
        //$ip_address = ($user_IP) ? $user_IP : $_SERVER["REMOTE_ADDR"];
        //r_user_id 接收的人 c_user_id 评论的人
        $addtime = gmtime();
            $sql = "INSERT INTO ".$GLOBALS['ecs']->table('diy_comment')." (`c_user_id`,`record_id`,`r_user_id`,`content`,`add_time`) VALUES ('$user_id','$record_id','$author_id','$content','$addtime')";
            if((string)$GLOBALS['db']->query($sql)){
                $data['comment_id'] = $GLOBALS['db']->insert_id();
                $data['code'] = 1;//true
                $user_sql = "SELECT user_name,face_card,user_id FROM ".$GLOBALS['ecs']->table('users')." WHERE user_id = $user_id " ;
                $data['user_data'] = $GLOBALS['db']->getOne($user_sql); 
                $data['user_data']['addtime'] = $addtime;
            }else{
                $data['code'] = 0;//false
            }
        $this->success($data);

    }

    //回复评论
    function reply(){
        $user_id = $this->input('user_id');//接收的人
        $author_id = $this->input('author_id');//回复的人
        $record_id = $this->input('record_id');//作品id
        $comment_id = $this->input('comment_id');//评论id
        $content = $this->input('content');//

        if(empty($comment_id)){
            $data['code'] = 0;
            $this->success($data);
        }
        //$user_IP = ($_SERVER["HTTP_VIA"]) ? $_SERVER["HTTP_X_FORWARDED_FOR"] : $_SERVER["REMOTE_ADDR"];
        //$ip_address = ($user_IP) ? $user_IP : $_SERVER["REMOTE_ADDR"];
        //查询此user_id是否为作品的作者
        $addtime = gmtime();
        $select_sql = "SELECT diy.user_id FROM ".$GLOBALS['ecs']->table('diy')." diy LEFT JOIN ".$GLOBALS['ecs']->table('diy_record')." record ON diy.diy_id = record.diy_id WHERE record.record_id = $record_id";
        $if_user = $GLOBALS['db']->getOne($select_sql);
        if($if_user == $author_id){
            //r_user_id 接收的人 c_user_id 回复的人 is_commnet 回复存评论id
            $sql = "INSERT INTO ".$GLOBALS['ecs']->table('diy_comment')." (`c_user_id`,`record_id`,`r_user_id`,`content`,`is_comment`,`add_time`) VALUES ('$author_id','$record_id','$user_id','$content','$comment_id','$addtime')";
            if((string)$GLOBALS['db']->query($sql)){
                $data['comment_id'] = $GLOBALS['db']->insert_id();
                $data['code'] = 1;//true
                //$user_sql = "SELECT user_id FROM ".$GLOBALS['ecs']->table('users')." WHERE user_id = $user_id " ;
                //$data['user_data'] = $GLOBALS['db']->getOne($user_sql);
                //$data['user_data']['addtime'] = $addtime;
            }else{
                $data['code'] = 0;//false
            }
        }else{
            $data['code'] = 2;//非作者
        }
        $this->success($data);
    }

    /**
     * 评论点赞
     * @param $user_id 用户
     * @param $comment_id 评论id
     * @param $source 来源 1：创意擂台参赛作品
     * @return array
     */
    public function comment_zan(){
        $user_id = $this->input('user_id');//接收的人
        $comment_id = $this->input('comment_id');//评论id

        $source = 1;  //预留
        //$arr=array();
        if($user_id && $comment_id){
            $sql = "SELECT comment_zan_id from ".$GLOBALS['ecs']->table('comment_zan')." where comment_id = '$comment_id' AND user_id ='$user_id' AND source = '$source'";
            if($GLOBALS['db']->getOne($sql)){
                $del_sql = "DELETE from ".$GLOBALS['ecs']->table('comment_zan')." where comment_id = '$comment_id' AND user_id ='$user_id' AND source = '$source'";
                if((string)$GLOBALS['db']->query($del_sql)){
                    $result['status'] = 200;
                    $result['is_add'] = 1;
                    $result['message'] = '取消操作成功';
                }else{
                    $result['status'] = 500;
                    $result['is_add'] = 0;
                    $result['message'] = '操作失败';
                }
            }else{
                $insert_sql = "INSERT INTO " .$GLOBALS['ecs']->table('comment_zan')."( user_id,comment_id,source,add_time ) VALUES ( $user_id,$comment_id,$source,".gmtime()." )";
                if((string)$GLOBALS['db']->query($insert_sql)){
                    $result['status'] = 200;
                    $result['is_add'] = 2;
                    $result['message'] = '添加操作成功';
                }else{
                    $result['status'] = 500;
                    $result['is_add'] = 0;
                    $result['message'] = '操作失败';
                }
            }
        }else{
            $result['status'] = 500;
            $result['is_add'] = 0;
            $result['message'] = '操作失败';
        }
        $this->success($result);
    }

    //设计分类
    public function category(){
        $cat_id = $this->input('cat_id');//顶级类型
        $cat_arr = $this->diy->category($cat_id);
        $this->success($cat_arr);
    }


    /*---------弹窗详情模块END-----*/


    // /* 款式 */
    // public function goodsType(){
    //     $require_fields = array('type_id');
    //     foreach($require_fields as $v)
    //     {
    //         if(!isset($this->data[$v])) // || empty($this->data[$v])
    //         {
    //             $this->error("缺失必选参数 ({$v})，请参考API文档", '-1');
    //         }else{
    //             if(strpos($v,'_id')){
    //                 $$v = intval(trim($this->data[$v]));
    //             }else{
    //                 $$v = stripslashes(trim($this->data[$v]));
    //             }
    //         }
    //     }

    //     $type_id = $this->input('type_id', 0);
    //     $goods_type = $this->diy->goods_type($type_id);
    //     $this->success($goods_type);
    // }

    // /* 款式 */
    // public function style(){
    //     $require_fields = array('type_id');
    //     foreach($require_fields as $v)
    //     {
    //         if(!isset($this->data[$v])) // || empty($this->data[$v])
    //         {
    //             $this->error("缺失必选参数 ({$v})，请参考API文档", '-1');
    //         }else{
    //             if(strpos($v,'_id')){
    //                 $$v = intval(trim($this->data[$v]));
    //             }else{
    //                 $$v = stripslashes(trim($this->data[$v]));
    //             }
    //         }
    //     }

    //     $type_id = $this->input('type_id', 0);
    //     $style = $this->diy->style($type_id);
    //     $this->success($style);
    // }

    // /* 颜色 */
    // public function color(){
    //     $require_fields = array('type_id');
    //     foreach($require_fields as $v)
    //     {
    //         if(!isset($this->data[$v])) // || empty($this->data[$v])
    //         {
    //             $this->error("缺失必选参数 ({$v})，请参考API文档", '-1');
    //         }else{
    //             if(strpos($v,'_id')){
    //                 $$v = intval(trim($this->data[$v]));
    //             }else{
    //                 $$v = stripslashes(trim($this->data[$v]));
    //             }
    //         }
    //     }

    //     $type_id = $this->input('type_id', 0);
    //     $color = $this->diy->color($type_id);
    //     $this->success($color);
    // }

    // /* 属性组合图片 */
    // public function attrGroupImg(){
    //     $require_fields = array('type_id');
    //     foreach($require_fields as $v)
    //     {
    //         if(!isset($this->data[$v])) // || empty($this->data[$v])
    //         {
    //             $this->error("缺失必选参数 ({$v})，请参考API文档", '-1');
    //         }else{
    //             if(strpos($v,'_id')){
    //                 $$v = intval(trim($this->data[$v]));
    //             }else{
    //                 $$v = stripslashes(trim($this->data[$v]));
    //             }
    //         }
    //     }

    //     $type_id = $this->input('type_id', 0);
    //     $attr_group_img = $this->diy->attr_group_img($type_id);
    //     $this->success($attr_group_img);
    // }

    // /* 获取字体 */
    // public function fontList(){
    //     $font_list = $this->diy->font_list();
    //     $this->success($font_list);
    // }

    // /* 获取图形 */
    // public function graphList(){
    //     $graph_list = $this->diy->graph_list();
    //     $this->success($graph_list);
    // }

    // /* 获取蒙版 */
    // public function maskList(){
    //     $mask_list = $this->diy->mask_list();
    //     $this->success($mask_list);
    // }

    // /**
    //  * 导入图片
    //  */
    // public function uploadFile(){
    //     if (empty($this->data['file'])) {
    //         $this->error('请选择图片');
    //     }
    //     $result = $this->diy->upload_File($this->user_id, $this->data['file'], $this->data['design_session']);
    //     if($result){
    //         $this->success($result);
    //     }else{
    //         $this->error('导入图片失败');
    //     }
    // }

    // /**
    //  * 删除图片
    //  */
    // public function deleteFile(){
    //     $result = $this->diy->delete_File($this->data['file_id']);
    //     if($result){
    //         $this->success('删除成功');
    //     }else{
    //         $this->error('删除失败');
    //     }
    // }

    // /**
    //  * 创建设计商品
    //  */
    // public function createGoods(){
    //     $type_id = $this->input('type_id', 0);
    //     $goods_name = $this->data['goods_name'];
    //     $goods_img = $this->data['goods_img'];
    //     $design_img = $this->data['design_img'];
    //     $attr_img = $this->data['attr_img'];
    //     $design_session = $this->data['design_session'];
    //     if (empty($goods_name)) {
    //         $this->error('商品名称不能为空');
    //     }
    //     if (empty($attr_img)) {
    //         $this->error('属性图片不能为空');
    //     }
    //     if (empty($goods_img)) {
    //         $this->error('商品图不能为空');
    //     }
    //     if (empty($design_img)) {
    //         $this->error('设计图不能为空');
    //     }
    //     $result = $this->diy->create_Goods($type_id, $goods_name, $attr_img, $goods_img, $design_img, $design_session);
    //     if($result){
    //         $this->success($result);
    //     }else{
    //         $this->error('网络错误，请重试');
    //     }
    // }
}
