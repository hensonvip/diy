<?php
/**
 * User: vincent.cao
 * Date: 14-9-10
 * Time: 下午3:07
 */
date_default_timezone_set('Asia/Chongqing');      
define('IN_ECS', true);
require(dirname(__FILE__) . '/init.php');
$auto_load_path = array(
    ROOT_PATH . 'qdapi/controllers',
    ROOT_PATH . 'qdapi/models',
    ROOT_PATH . 'qdapi/library',
);
Loader::setBasePath($auto_load_path);

try{
    $url = !empty($_GET['act'])?addslashes(trim($_GET['act']  )):'';
    unset($_GET['act']);
    $parts = explode('/', $url);
    $controller = !empty($parts[0]) ? array_shift($parts) : '';
    $action = !empty($parts[0]) ? array_shift($parts) : '';
    $params = $parts;

    $controller_class = ucfirst($controller) . 'Controller';



    if (class_exists($controller_class))
    {

        define('CONTROLLER', $controller);
        define('ACTION', $action);



        $app = new $controller_class();

        if(!method_exists($app, $action))
        {
            throw new ActivityException(1001);
        }

        call_user_func_array(array($app, $action), $params);
    } else {
        throw new ActivityException(1002);
    }
}catch(Exception $e)
{
    Response::exception($e);
}
