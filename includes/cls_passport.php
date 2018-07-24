<?php
/**
 * 会员验证模块
 * @2016-10-26 cyq
 */

if (!defined('IN_ECS'))
{
	die('Hacking attempt');
}


class cls_passport
{
	protected $_db                = null;
	protected $_tb_user           = null;
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
        $this->_db = $GLOBALS['db'];
		$this->_tb_user	         = $GLOBALS['ecs']->table('users');
		$this->_tb_order_info    = $GLOBALS['ecs']->table('order_info');
		$this->_now_time         = time();
		$this->_plan_time 		 = 3600*24*15;

        $this->cart              = cls_cart::getInstance();
        $this->user              = cls_user::getInstance();

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
     * 微信小程序会员登录函数
     *
     * @access  public
     * @param   string      $data     '{"nickName": "Yip", "gender": 1, "language": "zh_CN", "city": "Jiangmen", "province": "Guangdong","openid":"oWeRDuJdvF3hl_lRD2Gf0EsQ35KU"}'
     * @return  boolean
     */
    public function xcxLogin($data)
    {
        $result = array(
            'code'    => 500,
            'message' => '',
            'data'    => array()
        );
        $data['aite_id'] = isset($data['aite_id']) ? $data['aite_id'] : '';
        $count = $this->_db->getOne('SELECT COUNT(*) FROM ' . $this->_tb_user . ' WHERE aite_id="' . $data['openid'] . '"');

        if ($count == 0) {
            $sql = 'INSERT INTO ' . $this->_tb_user . '(user_name, password, aite_id, reg_time, froms) VALUES("weixin_' . rand() . '","' . MD5($data['openid']) . '","' . $data['openid'] . '","' . time() . '","xcx")';
            $try = 0;
            while (!$this->_db->query($sql) && $try < 10) {
                $try++;
            }
            $user_id = $this->_db->insert_id();
            $_SESSION['user_id'] = $user_id;
        } else if ($count == 1) {
            $user_id = $this->_db->getOne('SELECT user_id FROM ' . $this->_tb_user . ' WHERE aite_id="' . $data['openid'] . '"');
            $_SESSION['user_id'] = $user_id;
        } else {
            $result['message'] = '未知错误';
            return $result;
        }

        update_user_info();  //更新用户信息
        // 获取用户信息
        $user_rank_info = $this->user->get_user_rank($user_id);
        $this->cart->recalculate_price($_SESSION['user_id'],$user_rank_info); // 重新计算购物车中的商品价格

        $user = get_user_info();
        /*查找代付款的数据   jx*/
        $user_id = $user['user_id'];

        $user_info = $this->user->get_user_rank($user_id);
        $this->cart->recalculate_price($user_id,$user_info);

        $data = array('back_url'=>'');
        $data = array_merge($user_info,$data);

        $result['data'] = $data;
        $result['message'] = 'SUCCESS';
        $result['code'] = 200;
        return $result;

    }

    /**
     * 会员登录函数
     *
     * @access  public
     * @param   string      $username     账号
     * @param   string      $password     密码
     * @param   integer     $remember     是否记住会员
     * @return  boolean
     */
    function action_login($username, $password, $remember = null)
    {
        $field_name = 'user_name';
        if(is_mobile_phone($username))
        {
            $sql = "select user_id from " . $this->_tb_user . " where (mobile_phone = '" . $username . "' )";
//            OR username = '" . $username . "'
            $user_id = $this->_db->getOne($sql);
            $field_name = 'mobile_phone';
            if(! $user_id)
            {
                return false;
            }

        }
        else if(is_email($username))
        {
            $sql = "select user_id from " . $this->_tb_user . " where (email = '" . $username . "')";
            $user_id = $this->_db->getOne($sql);
            $field_name = 'email';
            if(! $user_id)
            {
                return false;
            }
        }
        else
        {
            $sql = "select user_id from " . $this->_tb_user . " where (user_name = '" . $username . "')";
            $user_id = $this->_db->getOne($sql);
            $field_name = 'user_name';
            if(! $user_id)
            {
                return false;
            }
        }


        if($this->check_user($username, $password, $field_name) > 0)
        {
            $this->set_session($username);
//            $this->set_cookie($username, $remember);
            return $user_id;
        }
        else
        {
            return false;
        }
    }

    /**
     * 检查指定用户是否存在及密码是否正确
     *
     * @access public
     * @param string $username 用户名
     * @param string $password 密码
     * @param string $field_name 字段名
     *
     * @return int
     */
    function check_user ($username, $password = null, $field_name = 'user_name')
    {
        $post_username = $username;
        /* 如果没有定义密码则只检查用户名 */
        if($password === null)
        {
            $sql = "SELECT user_id FROM " . $this->_tb_user . " WHERE `{$field_name}` ='" . $post_username . "'";
            return $this->_db->getOne($sql);
        }
        else
        {
			$sql = "SELECT user_id, password, salt,ec_salt " . " FROM " . $this->_tb_user . " WHERE `{$field_name}`='$post_username'";
			$row = $this->_db->getRow($sql);
			$ec_salt = $row['ec_salt'];
            $sql = "SELECT user_id FROM " . $this->_tb_user . " WHERE " . $field_name . "='" . $post_username . "' AND password ='" . $this->compile_password(array(
                    'password' => $password,
					'ec_salt' => $ec_salt)) . "'";

            return $this->_db->getOne($sql);
        }
    }

    /**
     * 设置指定用户SESSION
     *
     * @access public
     * @param string $username 用户名
     * @return void
     */
    function set_session ($username = '')
    {
        if(empty($username))
        {
            $GLOBALS['sess']->destroy_session();
        }
        else
        {
            $sql = "SELECT user_id, password, email FROM " . $this->_tb_user . " WHERE user_name='$username' LIMIT 1";
            $row = $this->_db->getRow($sql);

            if($row)
            {
                $_SESSION['user_id'] = $row['user_id'];
                $_SESSION['user_name'] = $username;
                $_SESSION['email'] = $row['email'];
            }
        }
    }

    /**
     * 设置cookie
     *
     * @access public
     * @param string $username
     * @param integer $remember
     *
     * @return void
     */
    function set_cookie ($username = '', $remember = null)
    {
        if(empty($username))
        {
            /* 摧毁cookie */
            $time = time() - 3600;
            setcookie("ECS[user_id]", '', $time, $this->cookie_path);
            setcookie("ECS[password]", '', $time, $this->cookie_path);
        }
        elseif($remember)
        {
            /* 设置cookie */
            $time = time() + 3600 * 24 * 15;

            setcookie("ECS[username]", $username, $time, $this->cookie_path, $this->cookie_domain);
            $sql = "SELECT user_id, password FROM " . $this->_tb_user . " WHERE user_name='$username' LIMIT 1";
            $row = $this->_db->getRow($sql);
            if($row)
            {
                setcookie("ECS[user_id]", $row['user_id'], $time, $this->cookie_path, $this->cookie_domain);
                setcookie("ECS[password]", $row['password'], $time, $this->cookie_path, $this->cookie_domain);
            }
        }
    }

    /**
     * 编译密码函数
     *
     * @access public
     * @param array $cfg
     *        	包含参数为 $password, $md5password, $salt, $type
     *
     * @return void
     */
    function compile_password ($cfg)
    {
        if(isset($cfg['password']))
        {
            $cfg['md5password'] = md5($cfg['password']);
        }
        if(empty($cfg['type']))
        {
            $cfg['type'] = PWD_MD5;
        }

        switch($cfg['type'])
        {
            case PWD_MD5:
                if(! empty($cfg['ec_salt']))
                {
                    return md5($cfg['md5password'] . $cfg['ec_salt']);
                }
                else
                {
                    return $cfg['md5password'];
                }

            case PWD_PRE_SALT:
                if(empty($cfg['salt']))
                {
                    $cfg['salt'] = '';
                }

                return md5($cfg['salt'] . $cfg['md5password']);

            case PWD_SUF_SALT:
                if(empty($cfg['salt']))
                {
                    $cfg['salt'] = '';
                }

                return md5($cfg['md5password'] . $cfg['salt']);

            default:
                return '';
        }
    }


    /**
     * 检查指定手机号码是否存在
     *
     * @access public
     * @param string $mobile_phone
     *        	用户手机号码
     *
     * @return boolean
     */
    function check_mobile_phone ($mobile_phone)
    {
        if(! empty($mobile_phone))
        {
            /* 检查手机号码是否重复 */
            $sql = "SELECT mobile_phone FROM " . $this->_tb_user . " WHERE mobile_phone = '$mobile_phone' ";
            if($this->_db->getOne($sql, true) > 0)
            {
                return true;
            }
            return false;
        }
    }

    /**
     * 检查指定用户名是否存在
     *
     * @access public
     * @param string $username
     *          用户手机号码
     *
     * @return boolean
     */
    function check_username ($username)
    {
        if(! empty($username))
        {
            /* 检查用户名是否重复 */
            $sql = "SELECT COUNT(*) FROM " . $this->_tb_user . " WHERE user_name = '$username' ";
            if($this->_db->getOne($sql, true) > 0)
            {
                return true;
            }
            return false;
        }
    }

    /**
     * 根据手机号生成用户名
     *
     * @param number $length
     * @return number
     */
    function generate_username_by_mobile ($mobile)
    {

        $username = 'QD'.substr($mobile, 0, 3);

        $charts = "ABCDEFGHJKLMNPQRSTUVWXYZ";
        $max = strlen($charts);

        for($i = 0; $i < 4; $i ++)
        {
            $username .= @$charts[mt_rand(0, $max)];
        }

        $username .= substr($mobile, -4);

        $sql = "select count(*) from " . $this->_tb_user . " where user_name = '$username'";
        $count = $this->_db->getOne($sql);
        if($count > 0)
        {
            return self::generate_username_by_mobile();
        }

        return $username;
    }


    public static function render($data, $code = 200, $message = 'OK') {
        header( 'Content-type: application/json; charset=UTF-8' );
        $return = array('code' => $code, 'message' => $message, 'data'=>(is_array($data) || is_object($data) ? $data : array($data)) );

        echo json_encode($return);
        exit;
    }

}
