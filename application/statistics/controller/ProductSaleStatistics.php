<?php
namespace app\statistics\controller;
use think\Controller;
use think\Db;

//产品销售统计控制器类
class ProductSaleStatistics extends Controller
{
	private $DB_prefix = ''; //数据库表前缀

	public function __construct(){
		parent::__construct(); 
		$this->DB_PREFIX = config("database.prefix");
	}


	//测试方法
	public function test(){
		
		return $this->fetch();
	}


	//统计所有店铺sku对应的产品 每天销量和额度
	public function index(){

// $startTime = '2018-07-24 00:00:00';
// $endTime = '2018-07-25 00:00:00';
// $sku = 'IE-ZJGM-SEA';
// $sql = "SELECT SUM(quantity) AS total_count FROM " . $this->DB_PREFIX . "amazon_order_item WHERE  sku='$sku' and purchase_date >= '$startTime' and purchase_date <= '$endTime' ";
// 			$result = Db::query($sql);
			// $data['total_count'] = !empty($result[0]['total_count']) ? $result[0]['total_count'] : '';



		//获取产品表信息
		$products = Db::name('product_sku')->select();

		// print_r($products);
		// exit('AA');

		//需要查询前几天的产品统计信息， 天数保存在文件中
		$ago_day = session_AmazonApiParam('amazon_product_sale_statistics.day'); //获取上次统计的是那一天 //记录从今天开始到前面30天数
		if($ago_day === false){ //第一次统计，从当天开始
			session_AmazonApiParam('amazon_product_sale_statistics.day', 1);
			$ago_day = 0;
		}else{ //从当天开始向前推30天，就轮回再来一次
			$next_day = $ago_day + 1;
			if($next_day > 31){
				session_AmazonApiParam('amazon_product_sale_statistics.day', null);
				$ago_day = 0;
			}else{
				session_AmazonApiParam('amazon_product_sale_statistics.day', $next_day);
			}
		}

		$startTime = date("Y-m-d H:i:s",mktime(0,0,0,date('m'),date('d')-$ago_day,date('Y')));
		$date = explode(' ', $startTime);
		$endTime = date("Y-m-d H:i:s",mktime(0,0,0,date('m'),date('d')+1-$ago_day,date('Y'))-1);
		print_r($date);

		//循环查询出每个产品SKU 对应天数的销量 和销售额
		foreach ($products as $key => $value) {
			$data = array();
			$sku = $value['OuterSKU'];
			$data['sku'] = $sku;
			$data['cname'] = $value['cname'];
			$data['ename'] = $value['ename'];

			//统计每个产品日销量
			$sql = "SELECT SUM(quantity) AS total_count FROM " . $this->DB_PREFIX . "amazon_order_item WHERE  sku='$sku' and purchase_date >= '$startTime' and purchase_date <= '$endTime' ";
			$result = Db::query($sql);
			$data['total_count'] = !empty($result[0]['total_count']) ? $result[0]['total_count'] : '';


			//统计每个产品日销售额
			$sql = "SELECT SUM(item_price) AS total_amount FROM " . $this->DB_PREFIX . "amazon_order_item WHERE  sku='$sku' and purchase_date >= '$startTime' and purchase_date <= '$endTime' ";
			$result = Db::query($sql);
			$data['total_amount'] = !empty($result[0]['total_amount']) ? $result[0]['total_amount'] : '';

			$data['p_date'] = $date[0];

			//有记录就更新销量和销售额
			$product = Db::name('amazon_product_sale_statistics')->where('sku', '=', $sku)->where('p_date', '=', $data['p_date'])->find();

			if($product){
				$r = Db::name('amazon_product_sale_statistics')->where('sku', '=', $sku)->where('p_date', '=', $data['p_date'])->update($data);
			}else{// 新的一天没有记录就插入数据表
				Db::name('amazon_product_sale_statistics')->insert($data);
			}
		}

	}

	//统计内部sku对应的所有外部sku产品销量和销售金额
	public function product_in_sku_sale(){
		//需要查询前几天的产品统计信息， 天数保存在文件中
		$ago_day = session_AmazonApiParam('product_in_sku_sale.day'); //获取上次统计的是那一天 //记录从今天开始到前面30天数
		if($ago_day === false){ //第一次统计，从当天开始
			session_AmazonApiParam('product_in_sku_sale.day', 1);
			$ago_day = 0;
		}else{ //从当天开始向前推30天，就轮回再来一次
			$next_day = $ago_day + 1;
			if($next_day > 31){
				session_AmazonApiParam('product_in_sku_sale.day', null);
				$ago_day = 0;
			}else{
				session_AmazonApiParam('product_in_sku_sale.day', $next_day);
			}
		}

		//默认显示每个产品当月每天销量和额度
		$p_date = date("Y-m-d",mktime(0,0,0, date('m'),date('d')-$ago_day,date('Y')));

		//查询所有内部SKU
		$in_sku = Db::query("SELECT DISTINCT InnerSKU AS in_sku FROM " . $this->DB_PREFIX . "product_sku ");
		foreach ($in_sku as $key => &$value) {
			$psku = Db::name('product_sku')->where('InnerSKU', '=', $value['in_sku'])->find();
			$value['cname'] = $psku['cname'];
			$value['ename'] = $psku['ename'];
		}
		
		//查询所有外部SKU
		$out_sku = array();
		foreach ($in_sku as $key => $value) {
			$insku = $value['in_sku'];
			$out = Db::query("SELECT OuterSKU AS out_sku FROM " . $this->DB_PREFIX . "product_sku WHERE InnerSKU = '$insku' ");
			// $out_sku[$insku] = array();
			$out_sku[$insku] = $out;
		}

		//通过遍历所有内部SKU对应的所有外部SKU，统计出内部SKU所对应产品没天销售量和销售额
		foreach ($in_sku as $key => $value) {
			$data = array();
			$qty = ''; //产品当日销量
			$amt = ''; //产品当日销售额
			$insku = $value['in_sku'];
			foreach ($out_sku[$insku] as $k => $v) {
				$outsku = $v['out_sku'];
				$p = Db::query("SELECT total_count, total_amount FROM " . $this->DB_PREFIX . "amazon_product_sale_statistics WHERE sku = '$outsku' and p_date = '$p_date' ");
				$qty += $p[0]['total_count'];
				$amt += $p[0]['total_amount'];
			}

			//打包SKU产品每日销售情况数据，准备入库
			$data['sku'] = $value['in_sku'];
			$data['cname'] = $value['cname'];
			$data['ename'] = $value['ename'];
			$data['total_count'] = $qty;
			$data['total_amount'] = $amt;
			$data['p_date'] = $p_date;

			//将内部SKU产品当日销量情况存入数据库,如果之前已经存在记录就只需更新
			$p = Db::name('amazon_product_in_sale_statistics')->where('sku', '=', $value['in_sku'])->where('p_date', '=', $p_date)->find();
			if($p){
				Db::name('amazon_product_in_sale_statistics')->where('sku', '=', $value['in_sku'])->where('p_date', '=', $p_date)->update($data);
			}else{
				Db::name('amazon_product_in_sale_statistics')->where('sku', '=', $value['in_sku'])->where('p_date', '=', $p_date)->insert($data);
			}
		}

		// print_r($out_sku);
		// exit('AA');
	}

}
