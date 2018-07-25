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

function write_error_log($msg,$name_ext='') {
    $msg = serialize($msg);
    $path = RUNTIME_PATH.'/Debuglogs/'.date('Ym').'/';
    if(!file_exists($path)) {
        @mkdir($path, 0777, true);
        @chmod($path, 0777);
    }
    $logFile = $path.date('d').'_error'.$name_ext.'.log';
    $now = date('Y-m-d H:i:s');
    $msg = "[{$now}] {$msg} \n";
    error_log($msg, 3, $logFile);
}

/**
 * 写调试日志，供查找问题
 * @param array $msg 日志数组
 */
function write_debug_log($msg, $log_type) {
    $msg = serialize($msg);
    // $path = RUNTIME_PATH.'/Debuglogs/'.date('Ym').'/';
    $path = ROOT_PATH.'../hl-data/logs/'.date('Ym').'/' . date('d') . '/';
    // echo $path;die();
    if(!file_exists($path)) {
        @mkdir($path, 0777, true);
        @chmod($path, 0777);
    }
    $logFile = $path . $log_type . '.log';
    $now = date('Y-m-d H:i:s');
    $msg = "[{$now}] {$msg} \n";
    error_log($msg, 3, $logFile);
}


/**
 * 写调试日志，供查找问题
 * @param array $msg 日志数组
 */
function write_review_log($msg, $log_name) {
    $msg = serialize($msg);
    $path = ROOT_PATH.'../hl-data/review/';

    if(!file_exists($path)) {
        @mkdir($path, 0777, true);
        @chmod($path, 0777);
    }
    $logFile = $path . $log_name . '.log';
    $now = date('Y-m-d H:i:s');
    $msg = "[{$now}] {$msg} \n";
    error_log($msg, 3, $logFile);
}


//将亚马逊参数保存入文件
function session_AmazonApiParam($key='', $value='') {
    $path = ROOT_PATH.'../hl-data/amazon/';
    if(!file_exists($path)) {
        @mkdir($path, 0777, true);
        @chmod($path, 0777);
    }
    
    $filename = ROOT_PATH.'../hl-data/amazon/amazon.log';
    if (file_exists($filename)) {
        $text = file_get_contents($filename);
        $data = json_decode($text, true);
    }
    
    if( (!empty($value)) &&  ($value !== null) ) {//设置键值对， 保存
        if(strpos($key, '.')){
            $k = explode(".", $key );
            if(!isset($data[$k[0]])){
                $data[$k[0]] = array();
            }
            $data[$k[0]][$k[1]] = $value;
        }else{
            $data[$key] = $value;
        }

        file_put_contents($filename, json_encode($data));
    } else if( (empty($value)) &&  ($value !== null) ) {//获取值
        if(strpos($key, '.')){
            $k = explode(".", $key );
            if(isset($data[$k[0]][$k[1]])){
                return $data[$k[0]][$k[1]];
            }else{
                return false;
            }
        }else{
            if(isset($data[$key])){
                return $data[$key];
            }else{
                return false;
            }
        }
    }else if( $value === null ){ //删除键值对
        if(strpos($key, '.')){
            $k = explode(".", $key );
            if(isset($data[$k[0]][$k[1]])){
                unset($data[$k[0]][$k[1]]);
            }
            if(empty($data[$k[0]])){
                unset($data[$k[0]]);
            }
        }else{
            unset($data[$key]);
        }

        file_put_contents($filename, json_encode($data));
        return false;
    }
}

//返回拼接好的URL给前台模板
function menu_url($controller_action){
    return 'http://' . $_SERVER['HTTP_HOST'] . '/index.php/admin/' . $controller_action;
}


//创建Excel表格文件
function createExcel($fileName, $headArr, $data){
    if(empty($data) || !is_array($data)){
        die("data must be a array");
    }
    if(empty($fileName)){
        exit;
    }
    $date = date("Y_m_d_his",time());
    $fileName .= "_{$date}.xlsx";

    //导入PHPExcel类库
    vendor("PHPExcel.PHPExcel");
    vendor("PHPExcel.PHPExcel.Writer.Excel2007");
    vendor("PHPExcel.PHPExcel.IOFactory");

    //创建新的PHPExcel对象
    $objPHPExcel = new \PHPExcel();
    $objProps = $objPHPExcel->getProperties();
      
    //设置表头
    $key = ord("A");//A--65  
    $key2 = ord("@");//@--64</span>  
    foreach($headArr as $v){  
        if($key>ord("Z")){  
            $key2 += 1;  
            $key = ord("A");  
            $colum = chr($key2).chr($key);//超过26个字母时才会启用  dingling 20150626  
        }else{  
            if($key2>=ord("A")){  
                $colum = chr($key2).chr($key);  
            }else{  
                $colum = chr($key);  
            }  
        }
        // $objPHPExcel->setActiveSheetIndex(0) ->setCellValue($colum.'1', L($v['COMMENT']));  
        $objPHPExcel->setActiveSheetIndex(0) ->setCellValue($colum.'1', $v);
        $key += 1;  
    }  
      
    $column = 2;  
    $objActSheet = $objPHPExcel->getActiveSheet();  
  
    foreach($data as $key => $rows){ //行写入  
        $span = ord("A");  
        $span2 = ord("@");  
        foreach($headArr as $k=>$v){  
            if($span>ord("Z")){  
                $span2 += 1;  
                $span = ord("A");  
                $j = chr($span2).chr($span);//超过26个字母时才会启用  dingling 20150626  
            }else{  
                if($span2>=ord("A")){  
                    $j = chr($span2).chr($span);  
                }else{  
                    $j = chr($span);  
                }  
            }
            //print_r($rows);print_r($v);die('AAA');
            // $j = chr($span);  
            // $objActSheet->setCellValue($j.$column, strip_tags($rows[$v['FIELD']]));
            // echo $j.$column;
            // echo '<br/>';
            // // echo strip_tags($rows[$v]);
            // var_dump($k);
            // print_r($rows);
            // die('AA');
            $objActSheet->setCellValue($j.$column, strip_tags($rows[$k]));
            // $objActSheet->setCellValue($j.$column, $v);
            $span++;  
        }  
        $column++;  
    }  
  
    $fileName = iconv("utf-8", "gb2312", $fileName);
    //重命名表
    $objPHPExcel->getActiveSheet()->setTitle('Simple');
    //设置活动单指数到第一个表,所以Excel打开这是第一个表
    $objPHPExcel->setActiveSheetIndex(0);
    //将输出重定向到一个客户端web浏览器(Excel2007)
    ob_end_clean();//清除缓冲区,避免乱码
    set_time_limit(0);
    ini_set('memory_limit', '1024M');
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header("Content-Disposition: attachment; filename=\"$fileName\"");
    header('Cache-Control: max-age=0');
    $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
    $objWriter->save('php://output'); //文件通过浏览器下载
    exit;
} 

function isMobile()
{
    if (isset($_SERVER['HTTP_X_WAP_PROFILE'])) {
        return true;
    }
    if (isset($_SERVER['HTTP_VIA'])) {
        return stristr($_SERVER['HTTP_VIA'], "wap") ? true : false;
    }
    if (isset($_SERVER['HTTP_USER_AGENT'])) {
        $clientkeywords = array('nokia', 'sony', 'ericsson', 'mot', 'samsung', 'htc', 'sgh', 'lg', 'sharp', 'sie-', 'philips', 'panasonic', 'alcatel', 'lenovo', 'iphone', 'ipod', 'blackberry', 'meizu', 'android', 'netfront', 'symbian', 'ucweb', 'windowsce', 'palm', 'operamini', 'operamobi', 'openwave', 'nexusone', 'cldc', 'midp', 'wap', 'mobile');
        if (preg_match("/(" . implode('|', $clientkeywords) . ")/i", strtolower($_SERVER['HTTP_USER_AGENT']))) {
            return true;
        }
    }
    if (isset($_SERVER['HTTP_ACCEPT'])) {
        if ((strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') !== false) && (strpos($_SERVER['HTTP_ACCEPT'], 'text/html') === false || (strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') < strpos($_SERVER['HTTP_ACCEPT'], 'text/html')))) {
            return true;
        }
    }
    return false;
}
/**
 * 子元素计数器
 * @param array $array
 * @param int   $pid
 * @return array
 */
function array_children_count($array, $pid)
{
    $counter = [];
    foreach ($array as $item) {
        $count = isset($counter[$item[$pid]]) ? $counter[$item[$pid]] : 0;
        $count++;
        $counter[$item[$pid]] = $count;
    }
    return $counter;
}
/**
 * 数组层级缩进转换
 * @param array $array 源数组
 * @param int   $pid
 * @param int   $level
 * @return array
 */
function array2level($array, $pid = 0, $level = 1)
{
    static $list = [];
    foreach ($array as $v) {
        if ($v['pid'] == $pid) {
            $v['level'] = $level;
            $list[]     = $v;
            array2level($array, $v['id'], $level + 1);
        }
    }
    return $list;
}
/**
 * 构建层级（树状）数组
 * @param array  $array          要进行处理的一维数组，经过该函数处理后，该数组自动转为树状数组
 * @param string $pid_name       父级ID的字段名
 * @param string $child_key_name 子元素键名
 * @return array|bool
 */
function array2tree(&$array, $pid_name = 'pid', $child_key_name = 'children')
{
    $counter = array_children_count($array, $pid_name);
    if (!isset($counter[0]) || $counter[0] == 0) {
        return $array;
    }
    $tree = [];
    while (isset($counter[0]) && $counter[0] > 0) {
        $temp = array_shift($array);
        if (isset($counter[$temp['id']]) && $counter[$temp['id']] > 0) {
            array_push($array, $temp);
        } else {
            if ($temp[$pid_name] == 0) {
                $tree[] = $temp;
            } else {
                $array = array_child_append($array, $temp[$pid_name], $temp, $child_key_name);
            }
        }
        $counter = array_children_count($array, $pid_name);
    }
    return $tree;
}
/**
 * 把元素插入到对应的父元素$child_key_name字段
 * @param        $parent
 * @param        $pid
 * @param        $child
 * @param string $child_key_name 子元素键名
 * @return mixed
 */
function array_child_append($parent, $pid, $child, $child_key_name)
{
    foreach ($parent as &$item) {
        if ($item['id'] == $pid) {
            if (!isset($item[$child_key_name])) {
                $item[$child_key_name] = [];
            }

            $item[$child_key_name][] = $child;
        }
    }
    return $parent;
}
/**
 * 手机号格式检查
 * @param string $mobile
 * @return bool
 */
function check_mobile_number($mobile)
{
    if (!is_numeric($mobile)) {
        return false;
    }
    $reg = '#^13[\d]{9}$|^14[5,7]{1}\d{8}$|^15[^4]{1}\d{8}$|^17[0,6,7,8]{1}\d{8}$|^18[\d]{9}$#';

    return preg_match($reg, $mobile) ? true : false;
}
//获取客户端真实IP
function getClientIP()
{
    global $ip;
    if (getenv("HTTP_CLIENT_IP")) {
        $ip = getenv("HTTP_CLIENT_IP");
    } else if (getenv("HTTP_X_FORWARDED_FOR")) {
        $ip = getenv("HTTP_X_FORWARDED_FOR");
    } else if (getenv("REMOTE_ADDR")) {
        $ip = getenv("REMOTE_ADDR");
    } else {
        $ip = "Unknow";
    }

    return $ip;
}
/**
 * 获取 IP  地理位置
 * 淘宝IP接口
 * @Return: array
 */
function getCity($ip = '')
{
    if($ip == ''){
        $url = "http://int.dpool.sina.com.cn/iplookup/iplookup.php?format=json";
        $ip=json_decode(file_get_contents($url),true);
        $data = $ip;
    }else{
        $url="http://ip.taobao.com/service/getIpInfo.php?ip=".$ip;
        $ip=json_decode(file_get_contents($url));   
        if((string)$ip->code=='1'){
           return false;
        }
        $data = (array)$ip->data;
    }
    
    return $data;   
}
