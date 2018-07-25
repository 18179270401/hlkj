<?php
namespace app\api\controller\amazon;
use think\Db;

//亚马逊订单接口类控制器
class Product extends AmazonCommonController
{
	//API 对接参数密钥信息
	private $serviceUrl = "https://mws.amazonservices.com/Products/2011-10-01"; //亚马逊 MWS 端点（美国）
	private $AWS_ACCESS_KEY_ID = 'AKIAISSY46V3P7WKG2AA';
	private $APPLICATION_NAME = 'AmazonJavascriptScratchpad'; //应用程序名称
	private $APPLICATION_VERSION = '1.0'; //应用程序版本
	private $AWS_SECRET_ACCESS_KEY = '0gS+BhoVkvSqUvz+x0oWyJyBhr+XoGHKeNGLG39x';
	private $MERCHANT_ID = 'A2S4I05OO8WH9p'; //店铺ID
	private $MARKETPLACE_ID = 'ATVPDKIKX0DER'; //商城编号
	private $NextToken = ''; //是否有下一页订单token
	private $CreatedAfterTime = '2018-05-26T00:00:00Z'; //获取订单时间节点（创建-之后）,测试默认时间自己调
	private $CreatedBeforeTime = ''; //获取订单时间节点（之前）
	private $LastUpdatedAfterTime = '2018-05-26T00:00:00Z'; //获取订单时间节点（更新-之后）,测试默认时间自己调

	//测试方法
	public function test(){
		$nowdate = date("Y/m/d H:i:s");
		var_dump($nowdate);

		die('Product test');
		
	}


	//获取订单列表
	public function ProductGetServiceStatus(){

	    //引入亚马逊客户端库
	    vendor("amazon.MarketplaceWebServiceProducts.Client");
	    vendor("amazon.MarketplaceWebServiceProducts.Model.GetServiceStatusRequest");

	    $config = array (
		   'ServiceURL' => $this->serviceUrl,
		   'ProxyHost' => null,
		   'ProxyPort' => -1,
		   'ProxyUsername' => null,
		   'ProxyPassword' => null,
		   'MaxErrorRetry' => 3,
		 );

	    //实例化订单类对象
		$service = new \MarketplaceWebServiceProducts_Client(
	        $this->AWS_ACCESS_KEY_ID,
	        $this->AWS_SECRET_ACCESS_KEY,
	        $this->APPLICATION_NAME,
	        $this->APPLICATION_VERSION,
	        $config);

	    //实例化订单请求类对象，初始化参数列表
		$request = new \MarketplaceWebServiceProducts_Model_GetServiceStatusRequest();

		$request->setSellerId($this->MERCHANT_ID);

		$this->invokeGetServiceStatus($service, $request);
	}


	/* XML转成数组(注意值的格式 @attributes) */
	public function simplest_xml_to_array($xmlstring) {
		return json_decode(json_encode((array) simplexml_load_string($xmlstring)), true);
	}



	// //获取订单列表
	// public function listOrder(){
	// 	//引入亚马逊客户端库
	//     vendor("amazon.MarketplaceWebServiceProducts.Client");
	//     vendor("amazon.MarketplaceWebServiceProducts.Model.ListOrdersRequest");
	//     vendor("amazon.MarketplaceWebServiceProducts.Model.ListOrdersByNextTokenRequest");

	//     //从公共继承类获取所有店铺API对接信息
	// 	$shop_api = $this->getApiParam();

	// 	foreach($shop_api as $api){
	// 		//循环遍历出每个店铺对接参数
	// 		$this->serviceUrl = $api['serviceUrl']; //亚马逊 MWS 端点（美国）
	// 		$this->AWS_ACCESS_KEY_ID = $api['AWS_ACCESS_KEY_ID'];
	// 		$this->APPLICATION_NAME = $api['APPLICATION_NAME']; //应用程序名称
	// 		$this->APPLICATION_VERSION = $api['APPLICATION_VERSION']; //应用程序版本
	// 		$this->AWS_SECRET_ACCESS_KEY = $api['AWS_SECRET_ACCESS_KEY'];
	// 		$this->MERCHANT_ID = $api['MERCHANT_ID']; //店铺ID
	// 		$this->MARKETPLACE_ID = $api['MARKETPLACE_ID']; //商城编号

			

	// 		//如果有下一页订单token，获取下一页订单
	// 		$this->NextToken = session_AmazonApiParam('Order_' . $this->MERCHANT_ID . '.NextToken');
	// 		if($this->NextToken){
	// 			$this->ListOrdersByNextToken();
	// 			exit('下一页订单为获取完。');
	// 		}
			
	// 		//上一次获取订单时间区间，最后的时间
	// 		if(session_AmazonApiParam('Order_' . $this->MERCHANT_ID . '.LastUpdatedBeforeTime')){
	// 			$this->LastUpdatedAfterTime = session_AmazonApiParam('Order_' . $this->MERCHANT_ID . '.LastUpdatedBeforeTime'); //上次最后时间作为本次获取订单开始时间
	// 		}

	// 	    $config = array ('ServiceURL' => $this->serviceUrl,
	// 		   'ProxyHost' => null,
	// 		   'ProxyPort' => -1,
	// 		   'ProxyUsername' => null,
	// 		   'ProxyPassword' => null,
	// 		   'MaxErrorRetry' => 3,
	// 		 );

	// 	    //实例化订单类对象
	// 		$service = new \MarketplaceWebServiceOrders_Client(
	// 		    $this->AWS_ACCESS_KEY_ID,
	// 		    $this->AWS_SECRET_ACCESS_KEY,
	// 		    $this->APPLICATION_NAME,
	// 		    $this->APPLICATION_VERSION,
	// 		    $config);

	// 		//实例化订单请求类对象，初始化参数列表
	// 		$request = new \MarketplaceWebServiceOrders_Model_ListOrdersRequest();
	// 		$request->setSellerId($this->MERCHANT_ID);
	// 		$request->setMarketplaceId($this->MARKETPLACE_ID);
	// 		// $request->setCreatedAfter($this->CreatedAfterTime);
	// 		$request->setLastUpdatedAfter($this->LastUpdatedAfterTime);

	// 		$this->invokeListOrders($service, $request);
	// 	}

	// }


	/**
	* Get Get Service Status Action Sample
	* Gets competitive pricing and related information for a product identified by
	* the MarketplaceId and ASIN.
	*
	* @param MarketplaceWebServiceProducts_Interface $service instance of MarketplaceWebServiceProducts_Interface
	* @param mixed $request MarketplaceWebServiceProducts_Model_GetServiceStatus or array of parameters
	*/
	public function invokeGetServiceStatus($service, $request){
		try {
			$response = $service->GetServiceStatus($request);

			$ProductResponse  = $this->simplest_xml_to_array($response['ResponseBody']);
			
			echo $ProductResponse["GetServiceStatusResult"]["Status"];

		} 
		catch (MarketplaceWebServiceProducts_Exception $ex) {
			echo("Caught Exception: " . $ex->getMessage() . "\n");
			echo("Response Status Code: " . $ex->getStatusCode() . "\n");
			echo("Error Code: " . $ex->getErrorCode() . "\n");
			echo("Error Type: " . $ex->getErrorType() . "\n");
			echo("Request ID: " . $ex->getRequestId() . "\n");
			echo("XML: " . $ex->getXML() . "\n");
			echo("ResponseHeaderMetadata: " . $ex->getResponseHeaderMetadata() . "\n");
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
/*	  public function invokeListOrders($service, $request){
	        $response = $service->ListOrders($request);
	        write_debug_log($response); //记录日志
	        // print_r($response);
	        // die('AAA');
	        $ListOrdersResponse = $this->simplest_xml_to_array($response['ResponseBody']);
	        $LastUpdatedBeforeTime = $ListOrdersResponse['ListOrdersResult']['LastUpdatedBefore'];

	        //返回订单有下一页就把NextToken 保存进session
	        if(isset($ListOrdersResponse['ListOrdersResult']['NextToken'])){
	        	session_AmazonApiParam('Order.NextToken', $ListOrdersResponse['ListOrdersResult']['NextToken']);
	        }
	        session_AmazonApiParam('Order.LastUpdatedBeforeTime', $LastUpdatedBeforeTime); //将此订单最后时间保存进session，作为下一次查询开始时间。

	        if(isset($ListOrdersResponse['ListOrdersResult']['Orders']['Order'])){
	        	$this->saveOrder($ListOrdersResponse['ListOrdersResult']['Orders']['Order']);
	        }else{
	        	exit('此次请求店铺没有新的订单！');
	        }  			  	
	 }
*/

	//获取订单列表下一页
/*	public function ListOrdersByNextToken(){
		//引入亚马逊客户端库
	    vendor("amazon.MarketplaceWebServiceOrders.Client");
	    vendor("amazon.MarketplaceWebServiceOrders.Model.ListOrdersByNextTokenRequest");

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
*/
	
	//根据token获取下一页订单
/*	public function invokeListOrdersByNextToken($service, $request){
		$response = $service->ListOrdersByNextToken($request);
		write_debug_log($response); //记录日志
		$ListOrdersByNextTokenResponse  = $this->simplest_xml_to_array($response['ResponseBody']);

		if(isset($ListOrdersByNextTokenResponse['ListOrdersByNextTokenResult']['NextToken'])){
			session_AmazonApiParam('Order.NextToken', $ListOrdersByNextTokenResponse['ListOrdersByNextTokenResult']['NextToken']); //保存下一页的token
		}else{
			session_AmazonApiParam('Order.NextToken', null); //删除amazonApi.NextToken元素
		}

		$this->saveOrder($ListOrdersByNextTokenResponse['ListOrdersByNextTokenResult']['Orders']['Order']);

	}
*/

	//将订单保存入数据库
/*	public function saveOrder($orders){
		if (!isset($orders[0])) { //不是二维数组，需要变成二维数组才能遍历;
		    $oneOrder = $orders;
		    $orders = null;
		    $orders[0] = $oneOrder;
		}

		$data = array();
		$updateData = array();
		$insertData = array();
		foreach($orders as $key => $order){
			$data['LatestShipDate'] = isset($order['LatestShipDate']) ? $order['LatestShipDate'] : ''; //承诺的订单发货时间范围的最后一天
			$data['OrderType'] = isset($order['OrderType']) ? $order['OrderType'] : ''; //订单类型: StandardOrder包含当前有库存商品的订单,Preorder所含预售商品
			$data['PurchaseDate'] = str_replace(array("T","Z"),array(" ",""), $order['PurchaseDate']); //创建订单的日期
			$data['AmazonOrderId'] = $order['AmazonOrderId']; //亚马逊所定义的订单编码，格式为 3-7-7
			$data['BuyerEmail'] = isset($order['BuyerEmail']) ? $order['BuyerEmail'] : ''; //买家的匿名电子邮件地址
			$data['IsReplacementOrder'] = isset($order['IsReplacementOrder']) ? $order['IsReplacementOrder'] : ''; //是否为替换订单： false否， true是
			$data['LastUpdateDate'] = isset($order['LastUpdateDate']) ? $order['LastUpdateDate'] : ''; //订单的最后更新日期
			$data['NumberOfItemsShipped'] = isset($order['NumberOfItemsShipped']) ? $order['NumberOfItemsShipped'] : ''; //已配送的商品数量
			$data['ShipServiceLevel'] = isset($order['ShipServiceLevel']) ? $order['ShipServiceLevel'] : ''; //货件服务水平
			$data['OrderStatus'] = isset($order['OrderStatus']) ? $order['OrderStatus'] : ''; //当前的订单状态
			$data['SalesChannel'] = isset($order['SalesChannel']) ? $order['SalesChannel'] : ''; //订单中第一件商品的销售渠道
			$data['IsBusinessOrder'] = isset($order['IsBusinessOrder']) ? $order['IsBusinessOrder'] : ''; //是否为商业订单： false否， true是
			$data['NumberOfItemsUnshipped'] = isset($order['NumberOfItemsUnshipped']) ? $order['NumberOfItemsUnshipped'] : ''; //未配送的商品数量
			$data['PaymentMethodDetail'] = isset($order['PaymentMethodDetails']['PaymentMethodDetail']) ? $order['PaymentMethodDetails']['PaymentMethodDetail'] : ''; //支付方式
			$data['BuyerName'] = isset($order['BuyerName']) ? $order['BuyerName'] : ''; //买家姓名
			$data['CurrencyCode'] = isset($order['OrderTotal']['CurrencyCode']) ? $order['OrderTotal']['CurrencyCode'] : ''; //三位数的货币代码
			$data['Amount'] = isset($order['OrderTotal']['Amount']) ? $order['OrderTotal']['Amount'] : '0.0'; //货币金额
			$data['IsPremiumOrder'] = isset($order['IsPremiumOrder']) ? $order['IsPremiumOrder'] : ''; //订单有无保险； 0不保险， 1有保险
			$data['EarliestShipDate'] = isset($order['EarliestShipDate']) ? $order['EarliestShipDate'] : ''; //您承诺的订单发货时间范围的第一天
			$data['MarketplaceId'] = isset($order['MarketplaceId']) ? $order['MarketplaceId'] : ''; //订单生成所在商城的匿名编码
			$data['FulfillmentChannel'] = isset($order['FulfillmentChannel']) ? $order['FulfillmentChannel'] : ''; //订单配送方式：亚马逊配送 (AFN) 或卖家自行配送 (MFN)
			$data['PaymentMethod'] = isset($order['PaymentMethod']) ? $order['PaymentMethod'] : ''; //订单的主要付款方式

			//订单的配送地址
			$data['Name'] = isset($order['ShippingAddress']['Name']) ? $order['ShippingAddress']['Name'] : '';
			$data['AddressLine1'] = isset($order['ShippingAddress']['AddressLine1']) ? $order['ShippingAddress']['AddressLine1'] : '';
			$data['AddressLine2'] = isset($order['ShippingAddress']['AddressLine2']) ? $order['ShippingAddress']['AddressLine2'] : '';
			$data['AddressLine3'] = isset($order['ShippingAddress']['AddressLine3']) ? $order['ShippingAddress']['AddressLine3'] : '';
			$data['City'] = isset($order['ShippingAddress']['City']) ? $order['ShippingAddress']['City'] : '';
			$data['County'] = isset($order['ShippingAddress']['County']) ? $order['ShippingAddress']['County'] : '';
			$data['District'] = isset($order['ShippingAddress']['District']) ? $order['ShippingAddress']['District'] : '';
			$data['StateOrRegion'] = isset($order['ShippingAddress']['StateOrRegion']) ? $order['ShippingAddress']['StateOrRegion'] : '';
			$data['PostalCode'] = isset($order['ShippingAddress']['PostalCode']) ? $order['ShippingAddress']['PostalCode'] : '';
			$data['CountryCode'] = isset($order['ShippingAddress']['CountryCode']) ? $order['ShippingAddress']['CountryCode'] : '';
			$data['Phone'] = isset($order['ShippingAddress']['Phone']) ? $order['ShippingAddress']['Phone'] : '';

			$data['IsPrime'] = isset($order['IsPrime']) ? $order['IsPrime'] : '';
			$data['ShipmentServiceLevelCategory'] = isset($order['ShipmentServiceLevelCategory']) ? $order['ShipmentServiceLevelCategory'] : ''; //订单的配送服务级别分类
			$data['SellerOrderId'] = isset($order['SellerOrderId']) ? $order['SellerOrderId'] : ''; //卖家所定义的订单编码

			$AmazonOrderId = $data['AmazonOrderId'];

			$result = Db::query("select id from tp_amazon_order where AmazonOrderId='$AmazonOrderId' ");

			if($result){ //之前存在订单-更新
				$data['id'] = $result[0]['id']; //数据中包含主键，可以直接更新
				db('amazon_order')->update($data);
			}else{ //不存在订单，则证明是新订单-创建
				db('amazon_order')->insert($data);
			}
		}

	}
*/

}