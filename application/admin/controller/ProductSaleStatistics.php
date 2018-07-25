<?php
namespace app\admin\controller;
use think\Controller;
use think\Db;
use MyLib\AjaxPage;

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


	//显示产品销售情况列表
	public function index(){
		$listRows = 10; //默认一页显示10条记录
		$p = input('p') ? input('p') : 1; //当前要显示第几页， 默认第一页

		//默认显示每个产品当月每天销量和额度
		$startTime = date('Y-m-01');
		$endTime = date('Y-m-d');

		$sql = "SELECT OuterSKU AS sku FROM " . $this->DB_PREFIX . "product_sku limit 0, $listRows";
		$p_sku = Db::query($sql);

		$sale_volume = array();
		foreach ($p_sku as $key => $value) {
			$sku = $value['sku'];
			$sale_volume[$sku] = Db::query("SELECT * FROM " . $this->DB_PREFIX . "amazon_product_sale_statistics where p_date >= '$startTime' and p_date <= '$endTime' and sku = '$sku' order by p_date DESC "); 
		}

		$count = Db::name('product_sku')->count();
		$every_day = $this->getThEveryday($startTime, $endTime);

		$list = array();
		foreach ($p_sku as $key => $value) {
			$list[$key] = array();
			$list[$key]['sku'] = '';
			$list[$key]['title'] = '';
			$list[$key]['total_count'] = '';
			$list[$key]['total_amount'] = '';
			$list[$key]['every_day'] = array();
			foreach ($sale_volume[$value['sku']] as $k => $v) {
				$list[$key]['sku'] = $v['sku'];
				$list[$key]['title'] = $v['ename'];
				$list[$key]['total_count'] += $v['total_count'];
				$list[$key]['total_amount'] += $v['total_amount'];
				$list[$key]['every_day'][] = $v;
			}
		}

		$AjaxPage = new AjaxPage($count, $listRows, 'page_show'); //第三个参数是你需要调用换页的ajax函数名
		$page = $AjaxPage->show();

		$this->assign('every_day', $every_day);
		$this->assign('list', $list);
		$this->assign('page', $page);

		return $this->fetch();
	}


	//根据开始时间，结束时间获取期间的每一天日期
	public function getThEveryday($startTime, $endTime){
		$day_list = array();
		$ym = substr($startTime, 0, -2);
		$start = (int)substr($startTime, -2);
		$end = (int)substr($endTime, -2);

		while($end >= $start){
			$date = array();
			$date['date'] = $ym . $end;
			$day_list[] = $date;
			$end--;
		}
		
		return $day_list;
	}


	//AJAX 异步查询
	public function search_product(){
		$listRows = 10; //默认一页显示10条记录
		$p = input('p') ? input('p') : 1; //当前要显示第几页， 默认第一页
		$data = input('post.');

		$startTime = $data['startTime'] ? $data['startTime'] : date('Y-m-01');
		$endTime = $data['endTime'] ? $data['endTime'] : date('Y-m-d');

		//拼装SQL查询语句
		// $sql = "SELECT OuterSKU AS p_sku FROM " . $this->DB_PREFIX . "product_sku limit 0, $listRows";
		$sql = "SELECT OuterSKU AS p_sku FROM " . $this->DB_PREFIX . "product_sku ";
		
		
		//sku条件查询
		if(!empty($data['sku'])){
			$sku = $data['sku'];
			$sql .= " where OuterSKU = '$sku' ";
		}

		//添加分页limit条件
		$sql .= " limit " . ($p-1)*$listRows . ", $listRows ";

		//查询出所有sku
		$p_sku = Db::query($sql);

		//查询每个产品每天销量和金额
		$sale_volume = array();
		foreach ($p_sku as $key => $value) {
			$sku = $value['p_sku'];
			$arr = Db::query("SELECT * FROM " . $this->DB_PREFIX . "amazon_product_sale_statistics WHERE p_date >= '$startTime' and p_date <= '$endTime' and sku = '$sku' order by p_date DESC");

			$sale_volume[$sku] = $arr;
			// $sale_volume = array_merge($sale_volume, $arr);
		}
		
		$count = Db::name('product_sku')->count();
		$every_day = $this->getThEveryday($startTime, $endTime);

		$list = array();
		foreach ($p_sku as $key => $value) {
			$list[$key] = array();
			$list[$key]['sku'] = '';
			$list[$key]['title'] = '';
			$list[$key]['total_count'] = '';
			$list[$key]['total_amount'] = '';
			$list[$key]['every_day'] = array();
			foreach ($sale_volume[$value['p_sku']] as $k => $v) {
				$list[$key]['sku'] = $v['sku'];
				$list[$key]['title'] = $v['ename'];
				$list[$key]['total_count'] += $v['total_count'];
				$list[$key]['total_amount'] += $v['total_amount'];
				$list[$key]['every_day'][] = $v;
			}
		}

		$AjaxPage = new AjaxPage($count, $listRows, 'page_show'); //第三个参数是你需要调用换页的ajax函数名
		$page = $AjaxPage->show();

		$result = array(
			'every_day' => $every_day,
			'list' => $list,
			'page' => $page,
		);

		echo json_encode($result);
		exit();
	}



	//导出Excel表格
	public function export_excel(){
		ini_set('memory_limit', '512M'); //升级为申请256M内存
		$data = input('param.');
		$fileName = "order";
		$headArr = array('amazon-order-id', 'merchant-order-id', 'purchase-date', 'last-updated-date', 'order-status', 'fulfillment-channel', 'sales-channel', 'order-channel', 'url', 'ship-service-level', 'product-name', 'sku', 'asin', 'item-status', 'quantity', 'currency', 'item-price', 'item-tax', 'shipping-price', 'shipping-tax', 'gift-wrap-price', 'gift-wrap-tax', 'item-promotion-discount', 'ship-promotion-discount', 'ship-city', 'ship-state', 'ship-postal-code', 'ship-country', 'promotion-ids', 'is-business-order', 'purchase-order-number', 'price-designation');

		$amazon_orderno = input('amazon_orderno'); //亚马逊订单号
		$shop_name = input('shop_name'); //亚马逊店铺名称


		$where = '1 '; //组装查询条件
		if(!empty($data['orderno'])){
			// exit('orderno----');
			$orderno = $data['orderno'];
			$where .= " and amazon_order_id = '$orderno' ";
		}

		//高级搜索时，数据
		if(!empty($data['high_grade_param'])){
			$data = json_decode($data['high_grade_param'], true);
			// print_r($data);exit('AA');
			//按照关键字搜索
			if((!empty($data['field_type'])) && (!empty($data['keyword']))){
				$key = $data['field_type'];
				$value = $data['keyword'];
				$where .= " and $key = '$value' ";
			}

			//按照日期时间搜索
			if((!empty($data['startTime'])) || (!empty($data['endTime']))){
				$startTime = $data['startTime'] ? $data['startTime'] : date('2000-01-00 00:00:00');
				$endTime = $data['endTime'] ? $data['endTime'] : date('Y-m-d 23:59:59');
				$where .= " and purchase_date > '$startTime' and purchase_date < '$endTime' ";
			}
			
			//按照订单状态条件搜索
			if(!empty($data['order_status'])){
				$order_status = $data['order_status'];
				$where .= " and order_status = '$order_status' ";
			}
		}

		//查询订单
		$orders = Db::name('amazon_order')->where($where)->select();

		$data = array();
		foreach($orders as $key => $order){
			$data[$key] = array();
			$item = Db::name('amazon_order_item')->where('amazon_order_id', $order['amazon_order_id'])->find();

			$data[$key][] = $order['amazon_order_id'];
			$data[$key][] = $order['merchant_order_id'];
			$data[$key][] = $order['purchase_date'];
			$data[$key][] = $order['last_updated_date'];
			$data[$key][] = $order['order_status'];
			$data[$key][] = $order['fulfillment_channel'];
			$data[$key][] = $order['sales_channel'];
			$data[$key][] = $order['order_channel'];
			$data[$key][] = $order['url'];
			$data[$key][] = $order['ship_service_level'];
			$data[$key][] = empty($item) ? '' : $item['product_name'];
			$data[$key][] = empty($item) ? '' : $item['sku'];
			$data[$key][] = empty($item) ? '' : $item['asin'];
			$data[$key][] = empty($item) ? '' : $item['item_status'];
			$data[$key][] = empty($item) ? '' : $item['quantity'];
			$data[$key][] = empty($item) ? '' : $item['currency'];
			$data[$key][] = empty($item) ? '' : $item['item_price'];
			$data[$key][] = empty($item) ? '' : $item['item_tax'];
			$data[$key][] = empty($item) ? '' : $item['shipping_price'];
			$data[$key][] = empty($item) ? '' : $item['shipping_tax'];
			$data[$key][] = empty($item) ? '' : $item['gift_wrap_price'];
			$data[$key][] = empty($item) ? '' : $item['gift_wrap_tax'];
			$data[$key][] = empty($item) ? '' : $item['item_promotion_discount'];
			$data[$key][] = empty($item) ? '' : $item['ship_promotion_discount'];
			$data[$key][] = $order['ship_city'];
			$data[$key][] = $order['ship_state'];
			$data[$key][] = $order['ship_postal_code'];
			$data[$key][] = $order['ship_country'];
			$data[$key][] = empty($item) ? '' : $item['promotion_ids'];
			$data[$key][] = $order['is_business_order'];
			$data[$key][] = $order['purchase_order_number'];
			$data[$key][] = $order['price_designation'];
		}
		
		//创建Excel文件
		if(!empty($data)){
			createExcel($fileName, $headArr, $data);
		}
		
	}


	
}
