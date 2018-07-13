<?php
/**
 * 发现
 */
//require_once(ROOT_PATH . 'includes/cls_diy.php');
require_once(ROOT_PATH . 'includes/cls_user.php');

class FindsController extends ApiController
{
    public function __construct()
    {
        parent::__construct();
        $this->data = $this->input();
        $this->cls_user = cls_user::getInstance();
    }

    /**
     * @description 发现作品列表
     * @param integer field 设计领域类型id
     */
    public function findsListAll(){
        $fieldCode = $this->input('field',0);
        $sortType = $this->input('sort_type',1);//排序 1最新 2推荐 3收藏
        $page = $this->input('page', 1);
        $status = $this->input('state', 3);//作品状态：3已通过
        $num = $this->input('num', 12);


        //查询条件，匹配数据库领域字符串
        if($fieldCode!=0){
            $where = "production_type LIKE '%$fieldCode%'";
            if ($status != 0) {
                $where .= " and state=$status";
            }
        }else{
            if ($status != 0) {
                $where = "state=$status";
            }
        }
        /*if($sortType==1){
            $where .= " order by add_time desc";
        }*/
        //print_r(1);exit;
        //$field = "*";
        //$result = $this->cls_user->getFinds($field,$where,$num,$page);
        //$GLOBALS['esc']->table();
        //SELECT * FROM hunuo_finds a LEFT JOIN hunuo_finds_type b ON b.type_id IN (a.production_type)
        $sql="SELECT * FROM ".$GLOBALS['ecs']->table('finds')." LEFT JOIN ".$GLOBALS['ecs']->table('finds_type')." ON ".$GLOBALS['ecs']->table('finds_type').".type_id=".$GLOBALS['ecs']->table('finds').".production_type"
            ." LEFT JOIN ".$GLOBALS['ecs']->table('users')."ON".$GLOBALS['ecs']->table('users').".user_id=".$GLOBALS['ecs']->table('finds').".user_id"." WHERE ".$where;
        $result=$GLOBALS['db']->getAll($sql);
        //echo $sql;
/*        if($sortType==2){

        }else($sortType==3){

        }*/

        $finds_sql = "SELECT count(find_id) FROM ". $GLOBALS['ecs']->table('finds')." WHERE $where";
        $count = $GLOBALS['db']->getOne($finds_sql);



        //分页
        $pager = array();
        $pager['page']         = $page;
        $pager['page_size']    = $num;
        $pager['record_count'] = $count;
        $pager['page_count']   = $page_count = ($count > 0) ? intval(ceil($count / $num)) : 1;

        $finds_data['list'] = $result;
        $finds_data['pager'] = $pager;


        $this->success($finds_data);
    }

    //作品列表用户信息
    function findUserData(){
        $user_id = $this->input('user_id',0);
        $find_id = $this->input('find_id',0);

        if($user_id!=0){
            $where= " user_id=$user_id";
            $author_id_where =" author_id=$user_id";
        }
        if($find_id!=0){
            $find_id_where =" find_id=$find_id";
        }

        //用户数据
        $user_sql="SELECT user_id,user_name,face_card FROM ".$GLOBALS['ecs']->table('users')." WHERE $where ";
        $result=$GLOBALS['db']->getAll($user_sql);


        //设计者被点赞总数
        $author_sql="SELECT count(admire_id) FROM ".$GLOBALS['ecs']->table('admire')." WHERE $author_id_where ";
        $author_count=$GLOBALS['db']->getOne($author_sql);


        //设计者的作品被点赞
        $find_sql="SELECT count(admire_id) FROM ".$GLOBALS['ecs']->table('admire')." WHERE $find_id_where ";
        $works_count=$GLOBALS['db']->getOne($find_sql);

        //print_r($count);exit;

        //作品前4条
        $finds_sql="SELECT * FROM ".$GLOBALS['ecs']->table('finds')." WHERE $where ORDER BY add_time DESC LIMIT 0,4 ";
        $finds_result=$GLOBALS['db']->getAll($finds_sql);
        //print_r($finds_result);exit;
        //设计领域类型
        $design_id_sql="SELECT production_type,label  FROM ".$GLOBALS['ecs']->table('finds')." WHERE $find_id_where ";
        $design_id=$GLOBALS['db']->getAll($design_id_sql);

        //设计领域类型id数据
        $type_id=$design_id[0]['production_type'];
       //设计标签id数据
        $tags_id=$design_id[0]['label'];

        //$design_id_arr=explode(',',$design_id);
        if(!empty($type_id)){
            //设计领域名
            $design_type_sql="SELECT `type_id`,`name` FROM ".$GLOBALS['ecs']->table('finds_type')." WHERE type_id IN ($type_id) ";
            $design_type=$GLOBALS['db']->getAll($design_type_sql);
        }
        if(!empty($tags_id)){
            //设计标签名
            $design_label_sql="SELECT * FROM ".$GLOBALS['ecs']->table('production_tags')." WHERE tags_id IN ($tags_id) ";
            $design_label=$GLOBALS['db']->getAll($design_label_sql);
        }

        $result['works']=$finds_result;
        $result['author_admire_num']=$author_count;
        $result['works_admire_num']=$works_count;
        $result['finds_type']=$design_type;
        $result['finds_label']=$design_label;



        //print_r($count);exit;
        $this->success($result);
    }

    //作品列表的对应领域名称
    function findsName(){
        $fieldCode = $this->input('field',0);

        if($fieldCode!=0){
            $where = " type_id=$fieldCode";
            $finds_sql = "SELECT `name` FROM ". $GLOBALS['ecs']->table('finds_type')." WHERE $where";
            $result = $GLOBALS['db']->getAll($finds_sql);
        }else{
            $result='';
        }

        //print_r($result);exit;
        $this->success($result);

    }

    /**
     * 获取领域列表
     */
    public function getFields(){
        $fields = $this->cls_user->get_finds_fields();
        $this->success($fields);
    }

    /**
     * 获取常用领域列表
     */
    public function getCommonFields(){
        $common_fields = $this->cls_user->get_finds_common_fields();
        $this->success($common_fields);
    }

	
}
