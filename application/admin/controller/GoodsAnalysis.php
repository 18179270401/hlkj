<?php
namespace app\admin\controller;
use think\Controller;
use think\Db;

//商品分析控制器类
class GoodsAnalysis extends Controller
{
	//商品列表
	public function lst(){
		$page = input('page') ? input('page') : 1; //当前要获取第几页
		$page = $page - 1;
		$number = 5; //每页显示2条记录。
		$total = Db::query("select count(id) from tp_goods_analysis");
		$total = $total[0]['count(id)'];
		$start = $page*$number + 1;
		$end = (($page*$number + $number) < $total) ? ($page*$number + $number) : $total;
		$totalPages = ceil($total / $number); //向上取整
		$list = db('goods_analysis')->order('id desc')->limit($page*$number, $number)->select();
		// Db::table('t user')->order('id desc')->limit(5)->select();
		$this->assign('list', $list);
		$this->assign('page', $page+1);
		$this->assign('total', $total);
		$this->assign('start', $start);
		$this->assign('end', $end);
		$this->assign('totalPages', $totalPages);
		return $this->fetch();
	}

	//上传Excel表格
	public function upload_excel(){
		ini_set('memory_limit','30M'); 
    	// 获取表单上传文件 例如上传了001.jpg
	    $file = request()->file('goods_excel');
	    // echo ROOT_PATH;die();
	    // 移动到框架应用根目录/public/uploads/ 目录下
	    if($file){
	        $info = $file->move(ROOT_PATH . 'public/static/uploads' . DS . 'excel');
	        if($info){
	            // 成功上传后 获取上传信息
	            // 输出 jpg
	            // echo $info->getExtension();
	            // 输出 20160820/42a79759f284b767dfcb2a0197904287.jpg
	            // echo $info->getSaveName();
	            // 输出 42a79759f284b767dfcb2a0197904287.jpg
	            // echo $info->getFilename();
	            $this->read_excel(ROOT_PATH . 'public/static/uploads/excel/' . $info->getSaveName()); 
	        }else{
	            // 上传失败获取错误信息
	            echo $file->getError();
	        }
	    }
	}


	//读取Excel表格
	public function read_excel($fileName){
		//PHPEXCEL读取excel文件，并进行相应处理
		header("Content-type: text/html; charset=utf-8");

		if (!file_exists($fileName)) {
		    exit("文件".$fileName."不存在");
		}

		vendor("PHPExcel.PHPExcel.IOFactory");
		$objPHPExcel = \PHPExcel_IOFactory::load($fileName);

		//获取sheet表数目
		$sheetCount = $objPHPExcel->getSheetCount();

		//默认选中sheet0表
		$sheetSelected = 0;
		$objPHPExcel->setActiveSheetIndex($sheetSelected);

		//获取表格行数
		$rowCount = $objPHPExcel->getActiveSheet()->getHighestRow();

		//获取表格列数
		$columnCount = $objPHPExcel->getActiveSheet()->getHighestColumn();

		$dataArr = array();

		//读取表格第一行数据作为数组下标。
		for ($column = 'A'; $column <= $columnCount; $column++) {
		     $colName[] = $objPHPExcel->getActiveSheet()->getCell($column.'1')->getValue();
		}

		//上传的表格总共有多少列
		$colNum = ord($columnCount)-ord('A') + 1; 

		$i = 0; //定义循环计数器
		// 循环读取每个单元格的数据 
		//行数循环,默认从表格第2行开始读取
		for ($row = 2; $row <= $rowCount; $row++){
			$data = array();
		    //列数循环 , 列数是以A列开始
		    for ($column = 'A'; $column <= $columnCount; $column++) {
		        // $dataArr[] = $objPHPExcel->getActiveSheet()->getCell($column.$row)->getValue();
		         $data[$colName[($i++)%$colNum]] = $objPHPExcel->getActiveSheet()->getCell($column.$row)->getValue();
		         if(($i % 10) == 0){
		         	$dataArr[] = $data;
		         }
		    }
		}
		//将从表格读取的数据，插入数据库。添加多条数据
		// db('goods_analysis')->insert($dataArr[0]);
		$total = Db::query('TRUNCATE table ' . '`tp_goods_analysis`');
		$result = db('goods_analysis')->insertAll($dataArr);
		if($result){
			$this->success('导入表格成功', 'lst');
		}else{
			$this->error('导入表格失败');
		}

	}


	//添加订单
	public function add(){
		if(request()->isPost()){
			$data = array();
			$data = input('post.');
			$data['order_sn'] = time() . rand(100000, 999999);
			$data['order_date'] = date('Y-m-d H:i:s', time());
			// print_r($data);
			// die();
			$result = db('stock')->insert($data);
			if($result){
				$this->success('添加库存成功！', 'lst');
			}else{
				$this->error('添加库存失败！');
			}
			exit();
		}
		return $this->fetch('add');
	}
	//编辑订单
	public function edit(){
		$id = input('id');
		if(request()->isPost()){
			$data = input('post.');
			$result = db('stock')->where('id', $id)->update($data);
			if($result){
				$this->success('编辑库存成功！', 'lst');
			}else{
				$this->error('编辑库存失败！');
			}
		}
		$orders = db('stock')->find($id);
		$this->assign('orders', $orders);
		return $this->fetch('edit');
	}
	//删除库存订单
	public function del(){
		$id = input('id');
		$result = db('stock')->where('id', $id)->delete();
		if($result){
			$this->success('删除库存订单成功！', 'lst');
		}else{
			$this->error('删除库存订单失败！');
		}
	}
}