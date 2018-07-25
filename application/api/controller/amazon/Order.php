<?php
namespace app\api\controller\amazon;
use think\Db;

//亚马逊订单接口类控制器
class Order extends AmazonCommonController
{
	//API 对接参数密钥信息
	private $serviceUrl = ""; //亚马逊 MWS 端点（美国）
	private $AWS_ACCESS_KEY_ID = '';
	private $APPLICATION_NAME = ''; //应用程序名称
	private $APPLICATION_VERSION = '1.0'; //应用程序版本
	private $AWS_SECRET_ACCESS_KEY = '';
	private $MERCHANT_ID = ''; //店铺ID
	private $MARKETPLACE_ID = ''; //商城编号
	private $NextToken = ''; //是否有下一页订单token
	private $CreatedAfterTime = '2018-06-00T00:00:00Z'; //获取订单时间节点（创建-之后）,测试默认时间自己调
	private $CreatedBeforeTime = ''; //获取订单时间节点（之前）
	private $LastUpdatedAfterTime = '2018-06-00T00:00:00Z'; //获取订单时间节点（更新-之后）,测试默认时间自己调
	private $log_type_name = 'order'; //日志文件名称
	private $log_index = ''; //日志标识，便于查找不同店铺日志
	private $shopName = ''; //店铺名称
	private $AmazonOrderId = ''; //亚马逊订单号



	//测试方法
	public function test(){
		$nowtime = time();
		// session('api.date', $nowtime);
		$api = session('api');
		var_dump($api['time']);

		die('AS test');
		
	}


	//获取订单列表
	public function ListOrder(){
		//引入亚马逊客户端库
	    vendor("amazon.MarketplaceWebServiceOrders.Client");
	    vendor("amazon.MarketplaceWebServiceOrders.Model.ListOrdersRequest");
	    vendor("amazon.MarketplaceWebServiceOrders.Model.ListOrdersByNextTokenRequest");

	    //从公共继承类获取所有店铺API对接信息
		$shop_api = $this->getApiParam();

		foreach($shop_api as $api){
			//循环遍历出每个店铺对接参数
			$this->serviceUrl = $api['serviceUrl']; //亚马逊 MWS 端点（美国）
			$this->AWS_ACCESS_KEY_ID = $api['AWS_ACCESS_KEY_ID'];
			$this->APPLICATION_NAME = $api['APPLICATION_NAME']; //应用程序名称
			$this->APPLICATION_VERSION = $api['APPLICATION_VERSION']; //应用程序版本
			$this->AWS_SECRET_ACCESS_KEY = $api['AWS_SECRET_ACCESS_KEY'];
			$this->MERCHANT_ID = $api['MERCHANT_ID']; //店铺ID
			$this->MARKETPLACE_ID = $api['MARKETPLACE_ID']; //商城编号
			$this->shopName = $api['shopName']; //店铺名称，内部称呼

			//如果有下一页订单token，获取下一页订单
			$this->NextToken = session_AmazonApiParam('Order_' . $this->MERCHANT_ID . '.NextToken');
			if($this->NextToken){
				$this->ListOrdersByNextToken();
				echo('下一页订单为获取完。');
			}
			
			//上一次获取订单时间区间，最后的时间
			if(session_AmazonApiParam('Order_' . $this->MERCHANT_ID . '.LastUpdatedBeforeTime')){
				$this->LastUpdatedAfterTime = session_AmazonApiParam('Order_' . $this->MERCHANT_ID . '.LastUpdatedBeforeTime'); //上次最后时间作为本次获取订单开始时间
			}

		    $config = array ('ServiceURL' => $this->serviceUrl,
			   'ProxyHost' => null,
			   'ProxyPort' => -1,
			   'ProxyUsername' => null,
			   'ProxyPassword' => null,
			   'MaxErrorRetry' => 3,
			 );

		    //实例化订单类对象
			$service = new \MarketplaceWebServiceOrders_Client(
			    $this->AWS_ACCESS_KEY_ID,
			    $this->AWS_SECRET_ACCESS_KEY,
			    $this->APPLICATION_NAME,
			    $this->APPLICATION_VERSION,
			    $config);

			//实例化订单请求类对象，初始化参数列表
			$request = new \MarketplaceWebServiceOrders_Model_ListOrdersRequest();
			$request->setSellerId($this->MERCHANT_ID);
			$request->setMarketplaceId($this->MARKETPLACE_ID);
			// $request->setCreatedAfter($this->CreatedAfterTime);
			$request->setLastUpdatedAfter($this->LastUpdatedAfterTime);

			$this->invokeListOrders($service, $request);
		}

	}


	/**
	  * Get List Orders Action Sample
	  * Gets competitive pricing and related information for a product identified by
	  * the MarketplaceId and ASIN.
	  *
	  * @param MarketplaceWebServiceOrders_Interface $service instance of MarketplaceWebServiceOrders_Interface
	  * @param mixed $request MarketplaceWebServiceOrders_Model_ListOrders or array of parameters
	  */
	  public function invokeListOrders($service, $request){
	        $response = $service->ListOrders($request);
	        $this->log_index = $this->shopName . '-' . $this->MERCHANT_ID;
	        write_debug_log(array($this->log_index, $response), 'ListOrder'); //记录日志, 类名作为日志文件名

	        $ListOrdersResponse = $this->simplest_xml_to_array($response['ResponseBody']);
	        $LastUpdatedBeforeTime = $ListOrdersResponse['ListOrdersResult']['LastUpdatedBefore'];

	        //返回订单有下一页就把NextToken 保存进session
	        if(isset($ListOrdersResponse['ListOrdersResult']['NextToken'])){
	        	session_AmazonApiParam('Order_' . $this->MERCHANT_ID . '.NextToken', $ListOrdersResponse['ListOrdersResult']['NextToken']);
	        }
	        session_AmazonApiParam('Order_' . $this->MERCHANT_ID . '.LastUpdatedBeforeTime', $LastUpdatedBeforeTime); //将此订单最后时间保存进session，作为下一次查询开始时间。

	        if(isset($ListOrdersResponse['ListOrdersResult']['Orders']['Order'])){
	        	$this->saveOrder($ListOrdersResponse['ListOrdersResult']['Orders']['Order']);
	        }else{
	        	echo('此次请求店铺没有新的订单！');
	        }  			  	
	 }


	//获取订单列表下一页
	public function ListOrdersByNextToken(){
	    $config = array ('ServiceURL' => $this->serviceUrl,
		   'ProxyHost' => null,
		   'ProxyPort' => -1,
		   'ProxyUsername' => null,
		   'ProxyPassword' => null,
		   'MaxErrorRetry' => 3,
		 );

	    //实例化订单类对象
		$service = new \MarketplaceWebServiceOrders_Client(
		    $this->AWS_ACCESS_KEY_ID,
		    $this->AWS_SECRET_ACCESS_KEY,
		    $this->APPLICATION_NAME,
		    $this->APPLICATION_VERSION,
		    $config);

		//实例化订单请求类对象，初始化参数列表
		$request = new \MarketplaceWebServiceOrders_Model_ListOrdersByNextTokenRequest();
		$request->setSellerId($this->MERCHANT_ID);
		$request->setNextToken($this->NextToken);

		$this->invokeListOrdersByNextToken($service, $request);

	}

	
	//根据token获取下一页订单
	public function invokeListOrdersByNextToken($service, $request){
		$response = $service->ListOrdersByNextToken($request);
		$this->log_index = $this->shopName . '-' . $this->MERCHANT_ID;
	    write_debug_log(array($this->log_index, $response), 'ListOrder'); //记录日志, 类名作为日志文件名
		$ListOrdersByNextTokenResponse  = $this->simplest_xml_to_array($response['ResponseBody']);

		if(isset($ListOrdersByNextTokenResponse['ListOrdersByNextTokenResult']['NextToken'])){
			session_AmazonApiParam('Order_' . $this->MERCHANT_ID . '.NextToken', $ListOrdersByNextTokenResponse['ListOrdersByNextTokenResult']['NextToken']); //保存下一页的token
		}else{
			session_AmazonApiParam('Order_' . $this->MERCHANT_ID . '.NextToken', null); //删除amazonApi.NextToken元素
		}

		$this->saveOrder($ListOrdersByNextTokenResponse['ListOrdersByNextTokenResult']['Orders']['Order']);

	}


	 /** XML转成数组(注意值的格式 @attributes) */
	public function simplest_xml_to_array($xmlstring) {
		return json_decode(json_encode((array) simplexml_load_string($xmlstring)), true);
	}


	//将订单保存入数据库
	public function saveOrder($orders){
		if (!isset($orders[0])) { //不是二维数组，需要变成二维数组才能遍历;
		    $oneOrder = $orders;
		    $orders = null;
		    $orders[0] = $oneOrder;
		}

		//获取 亚马逊商城		MarketplaceId  数组
		$amazon_mall_arr = $this->MarketplaceId_and_amazon_mall();

		$data = array();
		foreach($orders as $key => $order){
			if($order['PurchaseDate'] <= $this->CreatedAfterTime){ //只获取指定日期之后的订单
				continue;
			}

			$data['amazon_order_id'] = $order['AmazonOrderId']; //亚马逊所定义的订单编码，格式为 3-7-7
			$data['merchant_order_id'] = $order['SellerOrderId']; //卖家所定义的订单编码
			$data['purchase_date'] = str_replace(array("T","Z"),array(" ",""), $order['PurchaseDate']); //创建订单的日期
			$data['last_updated_date'] = isset($order['LastUpdateDate']) ? str_replace(array("T","Z"),array(" ",""), $order['LastUpdateDate']) : ''; //订单的最后更新日期
			$data['order_type'] = $order['OrderType']; //订单类型
			$data['order_status'] = isset($order['OrderStatus']) ? $order['OrderStatus'] : ''; //当前的订单状态
			$data['amount'] = isset($order['OrderTotal']['Amount']) ? $order['OrderTotal']['Amount'] : '0.0'; //订单金额
			$data['currency'] = isset($order['OrderTotal']['CurrencyCode']) ? $order['OrderTotal']['CurrencyCode'] : ''; //三位数的货币代码
			$data['buyer_name'] = isset($order['BuyerName']) ? $order['BuyerName'] : ''; //买家姓名
			$data['buyer_email'] = isset($order['BuyerEmail']) ? $order['BuyerEmail'] : ''; //买家的匿名电子邮件地址
			$data['fulfillment_channel'] =  $order['FulfillmentChannel']; //订单配送方式：亚马逊配送 (AFN) 或卖家自行配送 (MFN)
			$data['sales_channel'] = $order['SalesChannel']; //销售渠道
			$data['order_channel'] = isset($order['order_channel']) ? $order['order_channel'] : '';
			$data['url'] = isset($order['url']) ? $order['url'] : '';
			$data['ship_service_level'] = isset($order['ship_service_level']) ? $order['ship_service_level'] : ''; //货件服务水平
			$data['ship_city'] = isset($order['ShippingAddress']['City']) ? $order['ShippingAddress']['City'] : '';
			$data['ship_state'] = isset($order['ShippingAddress']['StateOrRegion']) ? $order['ShippingAddress']['StateOrRegion'] : '';
			$data['ship_postal_code'] = isset($order['ShippingAddress']['PostalCode']) ? $order['ShippingAddress']['PostalCode'] : '';
			$data['ship_country'] = isset($order['ShippingAddress']['County']) ? $order['ShippingAddress']['County'] : '';
			$data['is_business_order'] = isset($order['is_business_order']) ? $order['is_business_order'] : ''; //商业订单
			$data['purchase_order_number'] = isset($order['purchase_order_number']) ? $order['purchase_order_number'] : ''; //购买订单号
			$data['price_designation'] = isset($order['price_designation']) ? $order['price_designation'] : ''; //ORICE设计
			$data['amazon_mall'] = $amazon_mall_arr[$order['MarketplaceId']]; //亚马逊商城，2个字母简称
			$data['shop_name'] = $this->shopName; //店铺名称

			//亚马逊订单号
			$AmazonOrderId = $order['AmazonOrderId'];

			//订单表
			$orderID = Db::name('amazon_order')->where('amazon_order_id', $AmazonOrderId)->value('id');
			if($orderID){ //之前存在订单-更新
				$data['id'] = $orderID; //数据中包含主键，可以直接更新
				db('amazon_order')->update($data);
			}else{ //不存在订单，则证明是新订单-创建
				db('amazon_order')->insert($data);
			}

			//临时订单表
			$orderID = Db::name('amazon_order_temp')->where('amazon_order_id', $AmazonOrderId)->value('id');
			if($orderID){ //之前存在订单-更新
				$data['id'] = $orderID; //数据中包含主键，可以直接更新
				db('amazon_order_temp')->update($data);
			}else{ //不存在订单，则证明是新订单-创建
				db('amazon_order_temp')->insert($data);
			}
		}

	}


	//获取订单内的商品信息
	public function ListOrderItems(){
		//引入亚马逊客户端库
	    vendor("amazon.MarketplaceWebServiceOrders.Client");
	    vendor("amazon.MarketplaceWebServiceOrders.Model.ListOrderItemsRequest");

	    //从公共继承类获取所有店铺API对接信息
		$shop_api = $this->getApiParam();

		foreach($shop_api as $api){
			//循环遍历出每个店铺对接参数
			$this->serviceUrl = $api['serviceUrl']; //亚马逊 MWS 端点（美国）
			$this->AWS_ACCESS_KEY_ID = $api['AWS_ACCESS_KEY_ID'];
			$this->APPLICATION_NAME = $api['APPLICATION_NAME']; //应用程序名称
			$this->APPLICATION_VERSION = $api['APPLICATION_VERSION']; //应用程序版本
			$this->AWS_SECRET_ACCESS_KEY = $api['AWS_SECRET_ACCESS_KEY'];
			$this->MERCHANT_ID = $api['MERCHANT_ID']; //店铺ID
			$this->MARKETPLACE_ID = $api['MARKETPLACE_ID']; //商城编号
			$this->shopName = $api['shopName']; //店铺名称，内部称呼

			//获取当前店铺最早订单号-临时订单表
			$order = Db::name('amazon_order_temp')->where('shop_name', $this->shopName)->where("order_status='Unshipped' or order_status='PartiallyShipped' or order_status='Shipped'")->order('purchase_date desc')->limit(1)->select();

			//删除订单临时表中，订单状态是取消的订单记录
			Db::name('amazon_order_temp')->where('order_status', '=', 'Canceled')->delete();
			if(empty($order)){
				continue;
			}

			$this->AmazonOrderId = $order[0]['amazon_order_id'];
			
		    $config = array ('ServiceURL' => $this->serviceUrl,
			   'ProxyHost' => null,
			   'ProxyPort' => -1,
			   'ProxyUsername' => null,
			   'ProxyPassword' => null,
			   'MaxErrorRetry' => 3,
			 );

		    //实例化订单类对象
			$service = new \MarketplaceWebServiceOrders_Client(
			    $this->AWS_ACCESS_KEY_ID,
			    $this->AWS_SECRET_ACCESS_KEY,
			    $this->APPLICATION_NAME,
			    $this->APPLICATION_VERSION,
			    $config);

			//实例化订单请求类对象，初始化参数列表
			$request = new \MarketplaceWebServiceOrders_Model_ListOrderItemsRequest();
			$request->setSellerId($this->MERCHANT_ID);
			$request->setAmazonOrderId($this->AmazonOrderId);

			$this->invokeListOrderItems($service, $request);
		}
	}


	//获取订单内商品信息
	public function invokeListOrderItems($service, $request){
		$response = $service->ListOrderItems($request);
	    write_debug_log(array($this->shopName . '-' . $this->MERCHANT_ID, $response), 'ListOrderItems'); //记录日志, 类名作为日志文件名
	    $ListOrderItemsResponse  = $this->simplest_xml_to_array($response['ResponseBody']);


	    $this->saveOrderItems($ListOrderItemsResponse['ListOrderItemsResult']);

	}


	//将订单中的商品信息保存入数据库
	public function saveOrderItems($ListOrderItems){
		$order_items = $ListOrderItems['OrderItems']['OrderItem'];
		$AmazonOrderId = $ListOrderItems['AmazonOrderId']; 

		if (!isset($order_items[0])) { //不是二维数组，需要变成二维数组才能遍历;
		    $oneOrder = $order_items;
		    $order_items = null;
		    $order_items[0] = $oneOrder;
		}
		
		$data = array();
		foreach($order_items as $item){
			$data['amazon_order_id'] = $AmazonOrderId; //亚马逊订单号
			$data['purchase_date'] = Db::name('amazon_order')->where('amazon_order_id', '=', $AmazonOrderId)->value('purchase_date'); //订单创建时间
			$data['product_name'] = $item['Title']; //商品名称
			$data['sku'] = $item['SellerSKU']; //商品的卖家 SKU
			$data['asin'] = $item['ASIN']; //商品的亚马逊标准识别号
			$data['order_item_id'] = $item['OrderItemId']; //商品ID
			$data['item_status'] = isset($item['item_status']) ? $item['item_status'] : ''; //商品状态
			$data['quantity'] = isset($item['QuantityOrdered']) ? $item['QuantityOrdered'] : 0; //购买商品数量
			$data['currency'] = isset($item['ItemPrice']) ? $item['ItemPrice']['CurrencyCode'] : 0; //订单商品的售价货币代码
			$data['item_price'] = isset($item['ItemPrice']) ? $item['ItemPrice']['Amount'] : 0; //订单商品的售价
			$data['item_tax'] = isset($item['ItemTax']) ? $item['ItemTax']['Amount'] : 0; //商品价格的税费
			$data['shipping_price'] = isset($item['ShippingPrice']) ? $item['ShippingPrice']['Amount'] : 0; //运费
			$data['shipping_tax'] = isset($item['ShippingTax']) ? $item['ShippingTax']['CurrencyCode'] : 0; //运费的税费
			$data['gift_wrap_price'] = isset($item['GiftWrapPrice']) ? $item['GiftWrapPrice']['Amount'] : 0; //商品的礼品包装金额
			$data['gift_wrap_tax'] = isset($item['GiftWrapTax']) ? $item['GiftWrapTax']['Amount'] : 0; //礼品包装金额的税费
			$data['item_promotion_discount'] = isset($item['PromotionDiscount']) ? $item['PromotionDiscount']['Amount'] : 0; //报价中的全部促销折扣总计
			$data['ship_promotion_discount'] = isset($item['PromotionDiscount']) ? $item['PromotionDiscount']['Amount'] : 0; //报价中的全部促销折扣总计
			$data['promotion_ids'] = isset($item['PromotionIds']) ? $item['PromotionIds']['PromotionId'] : 0;
		}

		//将商品插入订单商品表中
		$result = db('amazon_order_item')->insert($data);
		if($result){ //临时订单表中的，商品查出插入数据库后，就将其删除
			db('amazon_order_temp')->where('amazon_order_id', $this->AmazonOrderId)->delete();
		}

	}

}