
<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 流年 <liu21st@gmail.com>
// +----------------------------------------------------------------------

// 应用公共文件

//格式化文字长度输出 Yip 20180120
function mbSubstr($str,$length,$encoding='utf-8'){
	if(strlen($str) > $length){
		$str = mb_substr($str,0,$length-1,$encoding).'...';
	}
	return $str;
}

//获取当天星期几
function  getWeek($date){    
	$date_str=date('Y-m-d',strtotime($date));
	$arr=explode("-", $date_str);
	$year=$arr[0];
	$month=sprintf('%02d',$arr[1]);
	$day=sprintf('%02d',$arr[2]);
	$hour = $minute = $second = 0;   
	$strap = mktime($hour,$minute,$second,$month,$day,$year);
	$number_wk=date("w",$strap);
	$weekArr=array("日","一","二","三","四","五","六");
	return $weekArr[$number_wk];
}

//二维码生成
function getQrCode($content){
    vendor("phpqrcode");
    $data =$content;
    $level = 'L';
    $size =4;
    $QRcode = new \QRcode();
    ob_start();
    $QRcode->png($data,false,$level,$size,2);
    $imageString = base64_encode(ob_get_contents());
    ob_end_clean();
    return "data:image/jpg;base64,".$imageString;
}