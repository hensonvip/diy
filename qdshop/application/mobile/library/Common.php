<?php
/**
 * 函数库
 * @author qinglin <[qinglin@hunuo.com]>
 * Date:2017.09.27
 */

namespace app\Mobile\library;

class Common{

    /**
     * 验证eamil
     * @param string $value
     * @param int $length
     * @return boolean
     */
    public static function isEmail($value,$match='/^[\w\d]+[\w\d-.]*@[\w\d-.]+\.[\w\d]{2,10}$/i'){
        $v = trim($value);
        if(empty($v)){return 0;}
        return preg_match($match,$v);
    }

    /**
     * Post方式
     * @param string $url
     * @param array $data
     * @param array $file = array(array('name'=>'','path'=>''),array('name'=>'','path'=>''))
     */
    public static function curlPost($url,$data='',$file=array()){
        //初始化
        $ch = curl_init();
        //设置选项，包括URL
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        //Yip 2017-10-28 文件上传
        if(!empty($file)){
    
            $arr_out =array();  
            foreach($file as $v)  
            {  
                $arr_out[ $v['name']][] = $v;
            }  

            foreach($arr_out as $vv){
                if(count($vv)>1){
                    foreach($vv as $k=>$v){
                        if(isset($v['path']) && isset($v['name'])){
                            
                            $image = \think\Image::open(realpath($v['path']));
                            $mime = $image->mime(); 
                            
                            if (version_compare(phpversion(),'5.5.0') >= 0 && class_exists('CURLFile')) {               
                                $field =  new \CURLFile(realpath($v['path']),$mime);
                            } else {
                                $field = '@'.realpath($v['path']).';type='.$mime;   //'pic' =>'@'.realpath('./mypic.png').';type=image/png'
                            }
                            $data[$v['name'].'['.$k.']'] = $field;
                        }           
                    }
                }else{
                    $v = current($vv);
                    if(isset($v['path']) && isset($v['name'])){                     
                            $image = \think\Image::open(realpath($v['path']));
                            $mime = $image->mime(); 
                            
                            if (version_compare(phpversion(),'5.5.0') >= 0 && class_exists('CURLFile')) {               
                                $field =  new \CURLFile(realpath($v['path']),$mime);
                            } else {
                                $field = '@'.realpath($v['path']).';type='.$mime;   //'pic' =>'@'.realpath('./mypic.png').';type=image/png'
                            }
                            $data[$v['name']] = $field;
                        }   
                }
            }
        }
        //print_r($data);die;
        // POST数据
        curl_setopt($ch, CURLOPT_POST, 1);
        // 把post的变量加上
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        $output = curl_exec($ch);
        //释放curl句柄
        curl_close($ch);
        return $output;
    }

    /**
     * Get方式
     * @param string $url
     */
    public static function curlGet($url){
        //初始化
        $ch = curl_init();
        //设置选项，包括URL
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        //执行并获取HTML文档内容
        $output = curl_exec($ch);
        //释放curl句柄
        curl_close($ch);
        return $output;
    }

}
?>