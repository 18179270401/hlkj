<?php
namespace app\api\controller\amazon;
use think\Db;

//亚马逊订单接口类控制器
class Sellers extends AmazonCommonController
{
	//API 对接参数密钥信息
	private $serviceUrl = "https://mws.amazonservices.com/Sellers/2011-07-01"; //亚马逊 MWS 端点（美国）
	private $AWS_ACCESS_KEY_ID = 'AKIAISSY46V3P7WKG2AA';
	private $APPLICATION_NAME = 'AmazonJavascriptScratchpad'; //应用程序名称
	private $APPLICATION_VERSION = '1.0'; //应用程序版本
	private $AWS_SECRET_ACCESS_KEY = '0gS+BhoVkvSqUvz+x0oWyJyBhr+XoGHKeNGLG39x';
	private $MERCHANT_ID = 'A2S4I05OO8WH9P'; //店铺ID
	private $MARKETPLACE_ID = 'ATVPDKIKX0DER'; //商城编号
	private $NextToken = ''; //是否有下一页订单token

	//测试方法
	public function test(){
		$nowdate = date("Y/m/d H:i:s");
		var_dump($nowdate);

		die('Sellers test');
	}


	//获取订单列表
	public function SellersGetServiceStatus(){

	    //引入亚马逊客户端库
	    vendor("amazon.MarketplaceWebServiceSellers.Client");
	    vendor("amazon.MarketplaceWebServiceSellers.Model.GetServiceStatusRequest");

	    $config = array (
		   'ServiceURL' => $this->serviceUrl,
		   'ProxyHost' => null,
		   'ProxyPort' => -1,
		   'ProxyUsername' => null,
		   'ProxyPassword' => null,
		   'MaxErrorRetry' => 3,
		 );

	    //实例化订单类对象
		$service = new \MarketplaceWebServiceSellers_Client(
	        $this->AWS_ACCESS_KEY_ID,
	        $this->AWS_SECRET_ACCESS_KEY,
	        $this->APPLICATION_NAME,
	        $this->APPLICATION_VERSION,
	        $config);

	    //实例化订单请求类对象，初始化参数列表
		$request = new \MarketplaceWebServiceSellers_Model_GetServiceStatusRequest();

		$request->setSellerId($this->MERCHANT_ID);

		$this->invokeGetServiceStatus($service, $request);
	}


	/* XML转成数组(注意值的格式 @attributes) */
	public function simplest_xml_to_array($xmlstring) {
		return json_decode(json_encode((array) simplexml_load_string($xmlstring)), true);
	}


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

			$SellersResponse  = $this->simplest_xml_to_array($response['ResponseBody']);

			return $SellersResponse["GetServiceStatusResult"]["Status"];
		} 
		catch (MarketplaceWebServiceSellers_Exception $ex) {
			echo("Caught Exception: " . $ex->getMessage() . "\n");
			echo("Response Status Code: " . $ex->getStatusCode() . "\n");
			echo("Error Code: " . $ex->getErrorCode() . "\n");
			echo("Error Type: " . $ex->getErrorType() . "\n");
			echo("Request ID: " . $ex->getRequestId() . "\n");
			echo("XML: " . $ex->getXML() . "\n");
			echo("ResponseHeaderMetadata: " . $ex->getResponseHeaderMetadata() . "\n");
		}
	}


	public function SellersSetShopNameForQuery($obj){
		$this->shopName = $obj;

		//从公共继承类获取所有店铺API对接信息
		$shop_api = $this->getApiParam();

		foreach($shop_api as $api){
			if($this->shopName == $api['shopName'])
			{
				$this->AWS_ACCESS_KEY_ID =  $api['AWS_ACCESS_KEY_ID'];
				$this->AWS_SECRET_ACCESS_KEY =  $api['AWS_SECRET_ACCESS_KEY'];
				$this->MERCHANT_ID =  $api['MERCHANT_ID'];
				$this->MARKETPLACE_ID =  $api['MARKETPLACE_ID'];
			}
		}
	}


	public function SellersMarketplaceParticipationsList(){
	//引入亚马逊客户端库
	    vendor("amazon.MarketplaceWebServiceSellers.Client");
	    vendor("amazon.MarketplaceWebServiceSellers.Model.ListMarketplaceParticipationsRequest");
	    vendor("amazon.MarketplaceWebServiceSellers.Model.ListMarketplaceParticipationsByNextTokenRequest");

		//如果有下一页订单token，获取下一页订单
		$this->NextToken = session_AmazonApiParam('Seller_' . $this->MERCHANT_ID . '.NextToken');
		if($this->NextToken){
			return $this->SellersListMarketplaceParticipationsByNextToken();
			exit('下一页订单为获取完。');
		}

	    $config = array (
		   'ServiceURL' => $this->serviceUrl,
		   'ProxyHost' => null,
		   'ProxyPort' => -1,
		   'ProxyUsername' => null,
		   'ProxyPassword' => null,
		   'MaxErrorRetry' => 3,
		 );

	    //实例化订单类对象
		$service = new \MarketplaceWebServiceSellers_Client(
	        $this->AWS_ACCESS_KEY_ID,
	        $this->AWS_SECRET_ACCESS_KEY,
	        $this->APPLICATION_NAME,
	        $this->APPLICATION_VERSION,
	        $config);

	    //实例化订单请求类对象，初始化参数列表
		$request = new \MarketplaceWebServiceSellers_Model_ListMarketplaceParticipationsRequest();

		$request->setSellerId($this->MERCHANT_ID);

		return $this->invokeListMarketplaceParticipations($service, $request);
	}


	public function invokeListMarketplaceParticipations($service, $request){
		try {
			$response = $service->ListMarketplaceParticipations($request);	

			$SellersResponse  = $this->simplest_xml_to_array($response['ResponseBody']);

			// var_dump($SellersResponse);

			//返回订单有下一页就把NextToken 保存进session
	        if(isset($SellersResponse['ListMarketplaceParticipationsResult']['NextToken'])){
	        	session_AmazonApiParam('Seller_' . $this->MERCHANT_ID . '.NextToken', $SellersResponse['ListMarketplaceParticipationsResult']['NextToken']);
	        }

	        if(isset($SellersResponse['ListMarketplaceParticipationsResult'])){
	        	return $this->DisplaySellers($SellersResponse['ListMarketplaceParticipationsResult']);
	        }else{
	        	exit('此次请求店铺没有新信息！');
	        } 

		} catch (MarketplaceWebServiceSellers_Exception $ex) {
			echo("Caught Exception: " . $ex->getMessage() . "\n");
		}
	}


	public function SellersListMarketplaceParticipationsByNextToken(){
	    $config = array (
		   'ServiceURL' => $this->serviceUrl,
		   'ProxyHost' => null,
		   'ProxyPort' => -1,
		   'ProxyUsername' => null,
		   'ProxyPassword' => null,
		   'MaxErrorRetry' => 3,
		 );

	    //实例化订单类对象
		$service = new \MarketplaceWebServiceSellers_Client(
	        $this->AWS_ACCESS_KEY_ID,
	        $this->AWS_SECRET_ACCESS_KEY,
	        $this->APPLICATION_NAME,
	        $this->APPLICATION_VERSION,
	        $config);

	    //实例化订单请求类对象，初始化参数列表
		$request = new \MarketplaceWebServiceSellers_Model_ListMarketplaceParticipationsByNextTokenRequest();

		$request->setSellerId($this->MERCHANT_ID);

		$request->setNextToken($this->NextToken);

		$this->invokeListMarketplaceParticipationsByNextToken($service, $request);
	}

	function invokeListMarketplaceParticipationsByNextToken($service, $request)
	{
		try {
			$response = $service->ListMarketplaceParticipationsByNextToken($request);

			$SellersNextTokenResponse  = $this->simplest_xml_to_array($response['ResponseBody']);

			// var_dump($SellersNextTokenResponse);

			if(isset($SellersNextTokenResponse['ListMarketplaceParticipationsByNextTokenResult']['NextToken'])){
			session_AmazonApiParam('Seller_' . $this->MERCHANT_ID . '.NextToken', $SellersNextTokenResponse['ListMarketplaceParticipationsByNextTokenResult']['NextToken']); //保存下一页的token
			}else{
				session_AmazonApiParam('Seller_' . $this->MERCHANT_ID . '.NextToken', null); //删除amazonApi.NextToken元素
			}

			$this->DisplaySellers($SellersNextTokenResponse['ListMarketplaceParticipationsByNextTokenResult']);

		} catch (MarketplaceWebServiceSellers_Exception $ex) {
			echo("Caught Exception: " . $ex->getMessage() . "\n");
		}
	}


	//将订单保存入数据库
	public function DisplaySellers($Sellers){
		if (!isset($Sellers[0])) { //不是二维数组，需要变成二维数组才能遍历;
		    $oneSeller = $Sellers;
		    $Sellers = null;
		    $Sellers[0] = $oneSeller;
		}

		$Sellers1 = $Sellers[0]['ListParticipations']['Participation'];
		if (!isset($Sellers1[0])) { //不是二维数组，需要变成二维数组才能遍历;
		    $oneSeller = $Sellers1;
		    $Sellers1 = null;
		    $Sellers1[0] = $oneSeller;
		}

		$Sellers2 = $Sellers[0]['ListMarketplaces']['Marketplace'];
		if (!isset($Sellers2[0])) { //不是二维数组，需要变成二维数组才能遍历;
		    $oneSeller = $Sellers2;
		    $Sellers2 = null;
		    $Sellers2[0] = $oneSeller;
		}

		$data = array();
		foreach($Sellers as $key => $seller){
			foreach($Sellers1 as $key1 => $seller1){
				$data[$key1] = array();
				$data[$key1]['ShopName'] = $this->shopName; 
				$data[$key1]['MarketplaceId'] = isset($seller1['MarketplaceId']) ? $seller1['MarketplaceId'] : ''; 
				$data[$key1]['SellerId'] = isset($seller1['SellerId']) ? $seller1['SellerId'] : ''; 
				$data[$key1]['HasSellerSuspendedListings'] = isset($seller1['HasSellerSuspendedListings']) ? $seller1['HasSellerSuspendedListings'] : '';

				foreach($Sellers2 as $key2 => $seller2){
					if($data[$key1]['MarketplaceId'] === (isset($seller2['MarketplaceId']) ? $seller2['MarketplaceId'] : ''))
					{
						$data[$key1]['DefaultCountryCode'] = isset($seller2['DefaultCountryCode']) ? $seller2['DefaultCountryCode'] : ''; 
						$data[$key1]['DomainName'] = isset($seller2['DomainName']) ? $seller2['DomainName'] : ''; 
						$data[$key1]['Name'] = isset($seller2['Name']) ? $seller2['Name'] : ''; 
						$data[$key1]['DefaultCurrencyCode'] = isset($seller2['DefaultCurrencyCode']) ? $seller2['DefaultCurrencyCode'] : ''; 
						$data[$key1]['DefaultLanguageCode'] = isset($seller2['DefaultLanguageCode']) ? $seller2['DefaultLanguageCode'] : ''; 
					}
				}
			}
		}
		// print_r($data); die('AAA');
		return $data;
	}
}
