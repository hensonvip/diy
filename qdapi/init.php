<?php
/**
 * 对内API 公共函数
 *
 * User: vincent.cao
 * Date: 14-9-10
 * Time: 下午2:37
*/

if (!defined('IN_ECS'))
{
    die('Hacking attempt');
}
header('Content-type: text/html;charset=UTF-8');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('X-Content-Type-Options: nosniff');
header('X-XSS-Protection: 1; mode=block');
header('Pragma: no-cache');

define('DEBUG_MODE', isset($_GET['debug']) && $_GET['debug']=='10cf700d1217048ae6e296f3129e1d98');//MD5('gzsc')
define('MAMA_PRICE_DECIMAL', 2);//价格小数点位数
// 禁止错误信息

//	error_reporting(0);
//	ini_set('display_errors', 0);

error_reporting(E_ALL);
ini_set('display_errors', 1);


if (__FILE__ == '')
{
    die('Fatal error code: 0');
}

/* 取得当前ecshop所在的根目录 */
define('ROOT_PATH', str_replace('qdapi/init.php', '', str_replace('\\', '/', __FILE__)));
define('LOG_PATH', ROOT_PATH.'data/logs/');
define('API_PATH', ROOT_PATH . 'api/');

/* 初始化设置 */

if (DIRECTORY_SEPARATOR == '\\')
{
    @ini_set('include_path',      '.;' . ROOT_PATH);
}
else
{
    @ini_set('include_path',      '.:' . ROOT_PATH);
}

if (file_exists(ROOT_PATH . 'data/config.php'))
{
    include(ROOT_PATH . 'data/config.php');
}
else
{
    include(ROOT_PATH . 'includes/config.php');
}

//if (defined('DEBUG_MODE') == false)
//{
//    define('DEBUG_MODE', 8);
//}

if (PHP_VERSION >= '5.1' && !empty($timezone))
{
    date_default_timezone_set($timezone);
}

$php_self = isset($_SERVER['PHP_SELF']) ? $_SERVER['PHP_SELF'] : $_SERVER['SCRIPT_NAME'];
if ('/' == substr($php_self, -1))
{
    $php_self .= 'index.php';
}
define('PHP_SELF', $php_self);

require(ROOT_PATH . 'includes/cls_ecshop.php');
require(ROOT_PATH . 'includes/lib_goods.php');
require(ROOT_PATH . 'includes/lib_base.php');
require(ROOT_PATH . 'includes/lib_common.php');
require(ROOT_PATH . 'includes/lib_time.php');
require(ROOT_PATH . 'includes/lib_main.php');

require(ROOT_PATH . 'includes/inc_constant.php');
require(ROOT_PATH . 'includes/cls_error.php');
require(ROOT_PATH . 'includes/cls_log.php');
require(ROOT_PATH . 'data/config_api.php');


/* 对用户传入的变量进行转义操作。*/
if (!get_magic_quotes_gpc())
{
    if (!empty($_GET))
    {
        $_GET  = addslashes_deep($_GET);
    }
    if (!empty($_POST))
    {
        $_POST = addslashes_deep($_POST);
    }

    $_COOKIE   = addslashes_deep($_COOKIE);
    $_REQUEST  = addslashes_deep($_REQUEST);
}

/* 创建 ECSHOP 对象 */
$ecs = new ECS($db_name, $prefix);
define('DATA_DIR', $ecs->data_dir());
define('IMAGE_DIR', $ecs->image_dir());

/* 初始化数据库类 */
require(ROOT_PATH . 'includes/cls_mysql.php');
$db = new cls_mysql($db_host, $db_user, $db_pass, $db_name);
$db_host = $db_user = $db_pass = $db_name = NULL;

/* 创建错误处理对象 */
$err = new ecs_error('message.html');


/* 载入系统参数 */
$_CFG = load_config();

/* 载入语言文件 */
require(ROOT_PATH . 'languages/' . $_CFG['lang'] . '/common.php');

require(ROOT_PATH . 'qdapi/library/Loader.php');
require(ROOT_PATH . 'qdapi/library/Response.php');
require(ROOT_PATH . 'qdapi/library/ActivityException.php');
require(ROOT_PATH . 'languages/zh_cn/admin/order.php');



/* 判断是否支持gzip模式 */
if (gzip_enabled())
{
    ob_start('ob_gzhandler');
}

/**
 * 对输出编码
 *
 * @access  public
 * @param   string   $str
 * @return  string
 */
function encode_output($str)
{
    return htmlspecialchars($str);
}

?>
