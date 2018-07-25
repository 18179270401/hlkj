<?php

//PHPEXCEL生成excel文件
//@author:firmy
//@desc 支持任意行列数据生成excel文件，暂未添加单元格样式和对齐


/* require_once './PHPExcel.php';
require_once './PHPExcel/Writer/Excel2007.php';
//require_once './PHPExcel/Writer/Excel5.php';
include_once './PHPExcel/IOFactory.php';
  
$fileName = "test_excel";
$headArr = array("第一列", "第二列", "第三列");
$data = array(array(1,2), array(1,3), array(5,7));

createExcel($fileName,$headArr,$data);
  
  
function createExcel($fileName, $headArr, $data){
    if(empty($data) || !is_array($data)){
        die("data must be a array");
    }
    if(empty($fileName)){
        exit;
    }
    $date = date("Y_m_d",time());
    $fileName .= "_{$date}.xlsx";

  
    //创建新的PHPExcel对象
    $objPHPExcel = new PHPExcel();
    $objProps = $objPHPExcel->getProperties();
      
    //设置表头
    $key = ord("A");
    foreach($headArr as $v){
        $colum = chr($key);
        $objPHPExcel->setActiveSheetIndex(0) ->setCellValue($colum.'1', $v);
        $key += 1;
    }
      
    $column = 2;
    $objActSheet = $objPHPExcel->getActiveSheet();
    foreach($data as $key => $rows){ //行写入
        $span = ord("A");
        foreach($rows as $keyName=>$value){// 列写入
            $j = chr($span);
            $objActSheet->setCellValue($j.$column, $value);
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
    $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
	$objWriter->save('php://output'); //文件通过浏览器下载
    exit;
} */







//PHPEXCEL读取excel文件，并进行相应处理
header("Content-type: text/html; charset=utf-8");
$fileName = "./test_excel_2018_01_03.xlsx";

if (!file_exists($fileName)) {
    exit("文件".$fileName."不存在");
}

$startTime = time();
//方法一：
include './PHPExcel/Reader/Excel2007.php';
$objReader = new PHPExcel_Reader_Excel2007();
$objPHPExcel = $objReader->load($fileName);
//方法二：
require_once './PHPExcel/IOFactory.php';
$objPHPExcel = PHPExcel_IOFactory::load($fileName);

//获取sheet表数目
$sheetCount = $objPHPExcel->getSheetCount();

//默认选中sheet0表
$sheetSelected = 0;
$objPHPExcel->setActiveSheetIndex($sheetSelected);

//获取表格行数
$rowCount = $objPHPExcel->getActiveSheet()->getHighestRow();

//获取表格列数
$columnCount = $objPHPExcel->getActiveSheet()->getHighestColumn();

echo "<div>Sheet Count : ".$sheetCount."　　行数： ".$rowCount."　　列数：".$columnCount."</div>"; 


$dataArr = array();


// 循环读取每个单元格的数据 
//行数循环
for ($row = 1; $row <= $rowCount; $row++){
    //列数循环 , 列数是以A列开始
    for ($column = 'A'; $column <= $columnCount; $column++) {
        $dataArr[] = $objPHPExcel->getActiveSheet()->getCell($column.$row)->getValue();
        echo $column.$row.":".$objPHPExcel->getActiveSheet()->getCell($column.$row)->getValue()."<br />";
    }
}

echo "<br/>消耗的内存为：".(memory_get_peak_usage(true) / 1024 / 1024)."M";

$endTime = time();
echo "<div>解析完后，当前的时间为：".date("Y-m-d H:i:s")."　　　总共消耗的时间为：".(($endTime - $startTime))."秒</div>";
var_dump($dataArr);
$dataArr = NULL; 














