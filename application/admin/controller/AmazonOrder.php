<?php
namespace app\admin\controller;
use think\Controller;
use think\Db;
use MyLib\AjaxPage;

//亚马逊订单接口类控制器
class AmazonOrder extends Controller
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


	//订单列表方法
	public function index(){
		$listRows = 30; //默认一页显示10条记录
		$p = input('p') ? input('p') : 1; //当前要显示第几页， 默认第一页
		
		//查询订单列表
		$orderList = Db::name('amazon_order')
						->order('purchase_date desc')
						->limit(($p-1)*$listRows . ',' .$listRows)
            			->select();
        $orderList = $this->getOrderItem($orderList);

		//根据条件当前所需展示记录总条数-显示分页
        $count = Db::name('amazon_order')
					->order('purchase_date desc')
					->limit(($p-1)*$listRows . ',' .$listRows)
        			->count();

		$AjaxPage = new AjaxPage($count, $listRows, 'page_show'); //第三个参数是你需要调用换页的ajax函数名
		$page = $AjaxPage->show();

		$this->assign('listRows', $listRows);
		$this->assign('orderList', $orderList); //订单列表
		$this->assign('page', $page); //分页列表

		return $this->fetch();
	}


	//按照搜索条件，搜索出订单，显示出产品
	public function search_order(){
		$listRows = input('listRows') ? input('listRows') : 30; //默认一页显示30条记录
		$p = input('p') ? input('p') : 1; //当前要显示第几页， 默认第一页
		$data = input('post.');
		$startTime = $data['startTime'] ? $data['startTime'] : date('2000-01-01 00:00:00');
		$endTime = $data['endTime'] ? $data['endTime'] : date('Y-m-d 23:59:59');

		//组装sql查询语句条件
		$sql2 = "select * from $this->DB_PREFIX" . "amazon_order where 1 ";
		$sql3 = "select count(*) from $this->DB_PREFIX" . "amazon_order where 1 ";

		//按时间条件搜索
		$condition = "  ";

		//按照店铺名称条件搜索
		if($data['shop_name']){
			$shop_name = $data['shop_name'];
			$condition .= " and shop_name = '$shop_name' ";
		}

		//按照订单状态条件搜索
		if($data['order_status']){
			$order_status = $data['order_status'];
			$condition .= " and order_status = '$order_status' ";
		}
		
		//按照关键字搜索
		if(!empty($data['hl_keyword'])){
			$key = $data['mutile_condition'];
			$val = $data['hl_keyword'];

			if($key == 'amazon_order_id'){
				$condition .= " and $key = '$val' ";
			}else if($key == 'asin'){
				$condition .= " and amazon_order_id in (select amazon_order_id from " . $this->DB_PREFIX . "amazon_order_item where asin='$val' and purchase_date > '$startTime' and purchase_date < '$endTime')";
			}else if($key == 'sku'){
				$condition .= " and amazon_order_id in (select amazon_order_id from " . $this->DB_PREFIX . "amazon_order_item where sku='$val' and purchase_date > '$startTime' and purchase_date < '$endTime')";
			}
		}

		//sql语句排序，limit一次提取记录条数
		$sql2 = $sql2 . $condition . " order by purchase_date desc limit " . ($p-1)*$listRows . ", $listRows ";
		$sql3 = $sql3 . $condition;

		//订单记录条数
		$count = Db::query($sql3);
		$count = $count[0]['count(*)'];
		//获取订单列表
		$orderList = Db::query($sql2);
		$orderList = $this->getOrderItem($orderList);

		$AjaxPage = new AjaxPage($count, $listRows, 'page_show'); //第三个参数是你需要调用换页的ajax函数名
		$page = $AjaxPage->show();

		$result = array(
			'orderList' => $orderList,
			'page' => $page,
		);

		echo json_encode($result);
		exit();
	}


	//根据订单列表获取，该笔订单号内的商品
	public function getOrderItem($orderList){
		$orders = $orderList;
		foreach ($orders as $key => &$value) {
			$item = Db::name('amazon_order_item')->where('amazon_order_id', '=', $value['amazon_order_id'])->find();
			$value['asin'] = isset($item['asin']) ? $item['asin'] : '';
			$value['sku'] = isset($item['sku']) ? $item['sku'] : '';
			$value['product_name'] = isset($item['product_name']) ? $item['product_name'] : '';
			$value['quantity'] = isset($item['quantity']) ? $item['quantity'] : '';

		}

		return $orders;
	}


	//根据订单，获取订单商品信息
	public function getOrderListByOrder($order){
		$orderList = array();
		// $order = Db::name('amazon_order')->where('amazon_order_id', '=', $orderNo)->find();
		$oneOrder = $order;
		$item = Db::name('amazon_order_item')->where('amazon_order_id', '=', $oneOrder['amazon_order_id'])->find();
		$oneOrder['asin'] = isset($item['asin']) ? $item['asin'] : '';
		$oneOrder['sku'] = isset($item['sku']) ? $item['sku'] : '';
		$oneOrder['product_name'] = isset($item['product_name']) ? $item['product_name'] : '';
		$oneOrder['quantity'] = isset($item['quantity']) ? $item['quantity'] : '';

		$orderList[0] = $oneOrder;

		return $orderList;
	}

	//根据ASIN、SKU,获取订单信息
	public function getOrderListByItems($items){
		$orderList = array();
		
		foreach ($items as $key => $value) {
			$orderList[$key] = array();
			$orderList[$key] = Db::name('amazon_order')->where('amazon_order_id', '=', $value['amazon_order_id'])->find();
			$orderList[$key]['asin'] = $value['asin'];
			$orderList[$key]['sku'] = $value['sku'];
			$orderList[$key]['product_name'] = $value['product_name'];
			$orderList[$key]['quantity'] = $value['quantity'];
		}
		
		return $orderList;
	}



	//根据订单号搜索订单， 返回订单信息AJAX
	public function searchOrder(){
		$orderno = input('orderno'); //亚马逊订单号
		$listRows = 30; //默认一页显示10条记录

		$order = Db::name('amazon_order')->where('amazon_order_id', $orderno)->find();

		//根据条件当前所需展示记录总条数-显示分页
        $count = Db::name('amazon_order')->where('amazon_order_id', $orderno)->count();

		$AjaxPage = new AjaxPage($count, $listRows, 'page_show'); //第三个参数是你需要调用换页的ajax函数名
		$page = $AjaxPage->show();

		$result = array(
			'order' => $order,
			'page' => $page,
		);

		echo json_encode($result);
		exit();
	}


	//分页，获取指定页的订单
	public function hl_search(){
		$p = input('p') ? input('p') : 1; //当前要显示第几页， 默认第一页
		$listRows = input('listRows'); //一次查询记录条数
		$data = input('param.');

		//高级搜索时，分页获取每页数据
		if(!empty($data['high_grade_param'])){
			$data = json_decode($data['high_grade_param'], true);

			$where = '1 '; //组装查询条件 
			//按照关键字搜索
			if((!empty($data['field_type'])) && (!empty($data['keyword']))){
				$key = $data['field_type'];
				$value = $data['keyword'];
				//按照ASIN条件搜索
				//方法太啰嗦
				//1. 可新建一张表，显示页面展示信息（集合了订单和订单商品详情信息表）， 将amazon_order_item 和 amazon_order表都作为临时表
				//2. 或者新建一张临时表，储存页面显示信息（订单和订单商品详情信息表）。
				//这样查询快，sql语句也简单。
				if($data['field_type'] == 'asin'){
					$orders = Db::name('amazon_order_item')->where('asin', '=', $value)->select();
					if($orders){
						$where .= ' and ';
						foreach ($orders as $key => $value) {
							$v = $value['amazon_order_id'];
							$where .= " amazon_order_id='$v' or ";
						}
					}

					$where = substr($where, 0, -3);
					// echo $where;
					// exit('AAA');

				}else if($data['field_type'] == 'sku'){//按照SKU条件搜索

				}else{
					$where .= " and $key = '$value' ";
				}

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
				$where .= " and order_status = $order_status ";
			}

			//按照订单状态条件搜索
			if(!empty($data['order_status'])){
				$order_status = $data['order_status'];
				$where .= " and order_status = '$order_status' ";
			}


			$orderList = Db::name('amazon_order')
						->where($where)
						->limit(($p-1)*$listRows . ',' .$listRows)
	        			->select();

	        //根据条件当前所需展示记录总条数-显示分页
	        $count = Db::name('amazon_order')
						->where($where)
						->limit(($p-1)*$listRows . ',' .$listRows)
	        			->count();

			$AjaxPage = new AjaxPage($count, $listRows, 'page_show'); //第三个参数是你需要调用换页的ajax函数名
			$page = $AjaxPage->show();

			$return = array(
				'param' => $data,
				'orderList' => $orderList,
				'page' => $page,
				'msg' => 'success',
			);

			echo json_encode($return);
			exit();

		}
		

		$orderList = Db::name('amazon_order')->order('purchase_date desc')->limit(($p-1)*$listRows . ',' .$listRows)->select();

		$count = Db::name('amazon_order')->count();
		$AjaxPage = new AjaxPage($count, $listRows, 'page_show'); //第三个参数是你需要调用换页的ajax函数名
		$page = $AjaxPage->show();

		$data = array(
			'orderList' => $orderList,
			'page' => $page,
		);

		echo json_encode($data);
		exit();
	}
	

	//改变每页显示订单记录条数
	public function getDisplayPage(){
		$p = input('p') ? input('p') : 1; //当前要显示第几页， 默认第一页
		$listRows = input('listRows'); //一次查询记录条数

		$orderList = Db::name('amazon_order')->order('purchase_date desc')->limit(($p-1)*$listRows . ',' .$listRows)->select();

		$count = Db::name('amazon_order')->count();
		$AjaxPage = new AjaxPage($count, $listRows, 'page_show'); //第三个参数是你需要调用换页的ajax函数名
		$page = $AjaxPage->show();

		$data = array(
			'orderList' => $orderList,
			'page' => $page,
		);

		echo json_encode($data);
		exit();
	}


	//根据订单号获取订单详情
	public function getOrderDetail(){
		$orderno = input('orderno');

		$orderItems = Db::name('amazon_order_item')->where('amazon_order_id', $orderno)->find();

		echo json_encode($orderItems);
		exit();
	}



	//获取订单商品信息
	public function high_grade_search(){
		if(request()->isPost()){
			$data = input('param.');
			$listRows = 30; //默认一页显示10条记录
			$p = input('p') ? input('p') : 1; //当前要显示第几页， 默认第一页
			

			$where = '1 '; //组装查询条件 
			//按照关键字搜索
			if((!empty($data['field_type'])) && (!empty($data['keyword']))){
				$key = $data['field_type'];
				$value = $data['keyword'];
				//按照ASIN条件搜索
				//方法太啰嗦
				//1. 可新建一张表，显示页面展示信息（集合了订单和订单商品详情信息表）， 将amazon_order_item 和 amazon_order表都作为临时表
				//2. 或者新建一张临时表，储存页面显示信息（订单和订单商品详情信息表）。
				//这样查询快，sql语句也简单。
				if($data['field_type'] == 'asin'){
					$orders = Db::name('amazon_order_item')->where('asin', '=', $value)->select();
					if($orders){
						$where .= ' and ';
						foreach ($orders as $key => $value) {
							$v = $value['amazon_order_id'];
							$where .= " amazon_order_id='$v' or ";
						}
					}

					$where = substr($where, 0, -3);
					// echo $where;
					// exit('AAA');

				}else if($data['field_type'] == 'sku'){//按照SKU条件搜索

				}else{
					$where .= " and $key = '$value' ";
				}

			}

			//按照日期时间搜索
			if((!empty($data['startTime'])) || (!empty($data['endTime']))){
				$startTime = input('startTime') ? input('startTime') : date('2000-01-00 00:00:00');
				$endTime = input('endTime') ? input('endTime') : date('Y-m-d 23:59:59');
				$where .= " and purchase_date > '$startTime' and purchase_date < '$endTime' ";
			}

			//按照订单状态条件搜索
			if(!empty($data['order_status'])){
				$order_status = $data['order_status'];
				$where .= " and order_status = '$order_status' ";
			}



		// $return = array(
		// 		'param' => $data,
		// 		'orders' => $orders[0]['id'],
		// 		'msg' => 'success',
		// 	);

		// 	echo json_encode($return);
		// 	exit();	

			$orderList = Db::name('amazon_order')
						->where($where)
						->limit(($p-1)*$listRows . ',' .$listRows)
            			->select();


	        //根据条件当前所需展示记录总条数-显示分页
	        $count = Db::name('amazon_order')
						->where($where)
						->limit(($p-1)*$listRows . ',' .$listRows)
	        			->count();

			$AjaxPage = new AjaxPage($count, $listRows, 'page_show'); //第三个参数是你需要调用换页的ajax函数名
			$page = $AjaxPage->show();

			$return = array(
				'param' => $data,
				'orderList' => $orderList,
				'page' => $page,
				'msg' => 'success',
			);

			echo json_encode($return);
			exit();
		}

		return $this->fetch('high-grade-search');
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