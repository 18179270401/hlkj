<?php
namespace app\api\controller\amazon;
use think\Db;

//亚马逊订单接口类控制器
class Financial extends AmazonCommonController
{
	//API 对接参数密钥信息
	private $serviceUrl = "https://mws.amazonservices.com/Finances/2015-05-01"; //亚马逊 MWS 端点（美国）
	private $AWS_ACCESS_KEY_ID = 'AKIAISSY46V3P7WKG2AA';
	private $APPLICATION_NAME = 'AmazonJavascriptScratchpad'; //应用程序名称
	private $APPLICATION_VERSION = '1.0'; //应用程序版本
	private $AWS_SECRET_ACCESS_KEY = '0gS+BhoVkvSqUvz+x0oWyJyBhr+XoGHKeNGLG39x';
	private $MERCHANT_ID = 'A2S4I05OO8WH9P'; //店铺ID
	private $MARKETPLACE_ID = 'ATVPDKIKX0DER'; //商城编号
	private $NextToken = ''; //是否有下一页订单token
	private $CreatedAfterTime = '2012-06-15T00:00:00Z'; //获取订单时间节点（创建-之后）,测试默认时间自己调
	private $CreatedBeforeTime = ''; //获取订单时间节点（之前）
	private $LastUpdatedAfterTime = '2018-06-01T00:00:00Z'; //获取订单时间节点（更新-之后）,测试默认时间自己调
	private $log_type_name = 'order'; //日志文件名称
	private $log_index = ''; //日志标识，便于查找不同店铺日志
	private $shopName = 'IE'; //店铺名称
	private $AmazonOrderId = ''; //亚马逊订单号

	//测试方法
	public function test(){
		$nowdate = date("Y/m/d H:i:s");
		var_dump($nowdate);

		die('Financial test');
	}


	public function FinancialSetShopNameForQuery($obj, $startTime, $EndTime){
		$this->shopName = $obj;
		$this->CreatedAfterTime = $startTime; //. "T00:00:00Z";
		$this->CreatedBeforeTime = $EndTime; //. "T00:00:00Z";

		var_dump($this->shopName);
		var_dump($this->CreatedAfterTime);
		var_dump($this->CreatedBeforeTime);//die("SSSSSS");

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


	//获取订单列表
	public function FinancialGetServiceStatus(){
	    //引入亚马逊客户端库
	    vendor("amazon.MWSFinancesService.Client");
	    vendor("amazon.MWSFinancesService.Model.GetServiceStatusRequest");

	    $config = array (
		   'ServiceURL' => $this->serviceUrl,
		   'ProxyHost' => null,
		   'ProxyPort' => -1,
		   'ProxyUsername' => null,
		   'ProxyPassword' => null,
		   'MaxErrorRetry' => 3,
		 );

	    //实例化订单类对象
		$service = new \MWSFinancesService_Client(
	        $this->AWS_ACCESS_KEY_ID,
	        $this->AWS_SECRET_ACCESS_KEY,
	        $this->APPLICATION_NAME,
	        $this->APPLICATION_VERSION,
	        $config);

	    //实例化订单请求类对象，初始化参数列表
		$request = new \MWSFinancesService_Model_GetServiceStatusRequest();

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
			$response = $service->getServiceStatus($request);

			$SellersResponse  = $this->simplest_xml_to_array($response['ResponseBody']);

			echo $SellersResponse["GetServiceStatusResult"]["Status"];
		} 
		catch (MWSFinancesService_Exception $ex) {
			echo("Caught Exception: " . $ex->getMessage() . "\n");
			echo("Response Status Code: " . $ex->getStatusCode() . "\n");
			echo("Error Code: " . $ex->getErrorCode() . "\n");
			echo("Error Type: " . $ex->getErrorType() . "\n");
			echo("Request ID: " . $ex->getRequestId() . "\n");
			echo("XML: " . $ex->getXML() . "\n");
		}
	}

	public function FinancialListFinancialEventGroupsList(){
	//引入亚马逊客户端库
	    vendor("amazon.MWSFinancesService.Client");
	    vendor("amazon.MWSFinancesService.Model.ListFinancialEventGroupsRequest");
	    vendor("amazon.MWSFinancesService.Model.ListFinancialEventGroupsByNextTokenRequest");

	    $config = array (
		   'ServiceURL' => $this->serviceUrl,
		   'ProxyHost' => null,
		   'ProxyPort' => -1,
		   'ProxyUsername' => null,
		   'ProxyPassword' => null,
		   'MaxErrorRetry' => 3,
		 );

	    //从公共继承类获取所有店铺API对接信息
		$shop_api = $this->getApiParam();

		foreach($shop_api as $api){
			//循环遍历出每个店铺对接参数
			// $this->serviceUrl = $api['serviceUrl']; //亚马逊 MWS 端点（美国）
			$this->AWS_ACCESS_KEY_ID = $api['AWS_ACCESS_KEY_ID'];
			$this->APPLICATION_NAME = $api['APPLICATION_NAME']; //应用程序名称
			$this->APPLICATION_VERSION = $api['APPLICATION_VERSION']; //应用程序版本
			$this->AWS_SECRET_ACCESS_KEY = $api['AWS_SECRET_ACCESS_KEY'];
			$this->MERCHANT_ID = $api['MERCHANT_ID']; //店铺ID
			$this->MARKETPLACE_ID = $api['MARKETPLACE_ID']; //商城编号
			$this->shopName = $api['shopName']; //店铺名称，内部称呼

			//实例化订单类对象
			$service = new \MWSFinancesService_Client(
		        $this->AWS_ACCESS_KEY_ID,
		        $this->AWS_SECRET_ACCESS_KEY,
		        $this->APPLICATION_NAME,
		        $this->APPLICATION_VERSION,
		        $config);

		    //实例化订单请求类对象，初始化参数列表
			$request = new \MWSFinancesService_Model_ListFinancialEventGroupsRequest();

			$request->setSellerId($this->MERCHANT_ID);
			$request->setFinancialEventGroupStartedAfter($this->CreatedAfterTime);
			// $request->setFinancialEventGroupStartedBefore($this->CreatedBeforeTime);
			$request->setMaxResultsPerPage(5);

			$this->invokeListFinancialEventGroups($service, $request);
			// return $this->invokeListFinancialEventGroups($service, $request);
		}
	}


	public function invokeListFinancialEventGroups($service, $request){
		try {
			$response = $service->listFinancialEventGroups($request);	

			$FinancialResponse  = $this->simplest_xml_to_array($response['ResponseBody']);

			if(isset($FinancialResponse['ListFinancialEventGroupsResult']['NextToken'])){
	        	$this->NextToken = $FinancialResponse['ListFinancialEventGroupsResult']['NextToken'];
	        }

	        if(isset($FinancialResponse['ListFinancialEventGroupsResult']['FinancialEventGroupList']['FinancialEventGroup'])){
	        	$this->SaveFinancial($FinancialResponse['ListFinancialEventGroupsResult']['FinancialEventGroupList']['FinancialEventGroup']);
	        }else{
	        	exit('此次请求店铺没有新信息！');
	        }
	        if($this->NextToken){
	        	$this->FinancialListFinancialEventsByNextToken();
	        }    

		} catch (MWSFinancesService_Exception $ex) {
			echo("Caught Exception: " . $ex->getMessage() . "\n");
		}
	}


	public function FinancialListFinancialEventsByNextToken(){
	    $NextConfig = array (
		   'ServiceURL' => $this->serviceUrl,
		   'ProxyHost' => null,
		   'ProxyPort' => -1,
		   'ProxyUsername' => null,
		   'ProxyPassword' => null,
		   'MaxErrorRetry' => 3,
		 );

	    //实例化订单类对象
		$NextService = new \MWSFinancesService_Client(
	        $this->AWS_ACCESS_KEY_ID,
	        $this->AWS_SECRET_ACCESS_KEY,
	        $this->APPLICATION_NAME,
	        $this->APPLICATION_VERSION,
	        $NextConfig);

	    //实例化订单请求类对象，初始化参数列表
		$NextRequest = new \MWSFinancesService_Model_ListFinancialEventGroupsByNextTokenRequest();

		$NextRequest->setSellerId($this->MERCHANT_ID);
		$NextRequest->setNextToken($this->NextToken);

		$this->invokeListFinancialEventsByNextToken($NextService, $NextRequest);
	}


	function invokeListFinancialEventsByNextToken($NextService, $NextRequest)
	{
		try {
			$response = $NextService->listFinancialEventGroupsByNextToken($NextRequest); 

			$FinancialNextTokenResponse  = $this->simplest_xml_to_array($response['ResponseBody']);

			if(isset($FinancialNextTokenResponse['ListFinancialEventGroupsByNextTokenResult']['NextToken'])){
	        	$this->NextToken = $FinancialNextTokenResponse['ListFinancialEventGroupsByNextTokenResult']['NextToken'];
			}else{
	        	$this->NextToken = "";
			}

			$this->SaveFinancial($FinancialNextTokenResponse['ListFinancialEventGroupsByNextTokenResult']['FinancialEventGroupList']['FinancialEventGroup']);

			//如果有下一页订单token，获取下一页订单
			while($this->NextToken){
				$this->FinancialListFinancialEventsByNextToken();
			}

		} catch (MWSFinancesService_Exception $ex) {
			echo("Caught Exception: " . $ex->getMessage() . "\n");
		}
	}


	//将订单保存入数据库
	// public function DisplayFinancial($Financial){
	// 	if (!isset($Financial[0])) { //不是二维数组，需要变成二维数组才能遍历;
	// 	    $oneFinancial = $Financial;
	// 	    $Financial = null;
	// 	    $Financial[0] = $oneFinancial;
	// 	}

	// 	$data = array();
	// 	foreach($Financial as $key => $financial){
	// 		$data[$key] = array();
	// 		$data[$key]['ShopName'] = isset($this->shopName) ? $this->shopName : '';
	// 		$data[$key]['FinancialEventGroupEnd'] = isset($financial['FinancialEventGroupEnd']) ? str_replace(array("T","Z"),array(" ",""), $financial['FinancialEventGroupEnd']) : '';
	// 		$data[$key]['OriginalTotalCurrencyAmount'] = isset($financial['OriginalTotal']['CurrencyAmount']) ? $financial['OriginalTotal']['CurrencyAmount'] : ''; 
	// 		$data[$key]['OriginalTotalCurrencyCode'] = isset($financial['OriginalTotal']['CurrencyCode']) ? $financial['OriginalTotal']['CurrencyCode'] : ''; 
	// 		$data[$key]['TraceId'] = isset($financial['TraceId']) ? $financial['TraceId'] : '';
	// 		$data[$key]['ProcessingStatus'] = isset($financial['ProcessingStatus']) ? $financial['ProcessingStatus'] : '';
	// 		$data[$key]['BeginningBalanceCurrencyAmount'] = isset($financial['BeginningBalance']['CurrencyAmount']) ? $financial['BeginningBalance']['CurrencyAmount'] : '';
	// 		$data[$key]['BeginningBalanceCurrencyCode'] = isset($financial['BeginningBalance']['CurrencyCode']) ? $financial['BeginningBalance']['CurrencyCode'] : '';
	// 		$data[$key]['FinancialEventGroupId'] = isset($financial['FinancialEventGroupId']) ? $financial['FinancialEventGroupId'] : '';
	// 		$data[$key]['FundTransferStatus'] = isset($financial['FundTransferStatus']) ? $financial['FundTransferStatus'] : '';
	// 		$data[$key]['FinancialEventGroupStart'] = isset($financial['FinancialEventGroupStart']) ?  str_replace(array("T","Z"),array(" ",""), $financial['FinancialEventGroupStart']) : '';
	// 		$data[$key]['AccountTail'] = isset($financial['AccountTail']) ? $financial['AccountTail'] : '';
	// 		$data[$key]['FundTransferDate'] = isset($financial['FundTransferDate']) ? str_replace(array("T","Z"),array(" ",""), $financial['FundTransferDate']) : '';
	// 	}
	// 	print_r($data); die('AAA');
	// 	return $data;
	// }


	//将订单保存入数据库
	public function SaveFinancial($Financial){
		if (!isset($Financial[0])) { //不是二维数组，需要变成二维数组才能遍历;
		    $oneFinancial = $Financial;
		    $Financial = null;
		    $Financial[0] = $oneFinancial;
		}

		$data = array();
		foreach($Financial as $key => $financial){
			$data['ShopName'] = isset($this->shopName) ? $this->shopName : '';
			$data['OriginalTotalCurrencyAmount'] = isset($financial['OriginalTotal']['CurrencyAmount']) ? $financial['OriginalTotal']['CurrencyAmount'] : ''; 
			$data['OriginalTotalCurrencyCode'] = isset($financial['OriginalTotal']['CurrencyCode']) ? $financial['OriginalTotal']['CurrencyCode'] : ''; 
			$data['TraceId'] = isset($financial['TraceId']) ? $financial['TraceId'] : '';
			$data['FundTransferStatus'] = isset($financial['FundTransferStatus']) ? $financial['FundTransferStatus'] : '';
			$data['ProcessingStatus'] = isset($financial['ProcessingStatus']) ? $financial['ProcessingStatus'] : '';
			$data['BeginningBalanceCurrencyAmount'] = isset($financial['BeginningBalance']['CurrencyAmount']) ? $financial['BeginningBalance']['CurrencyAmount'] : '';
			$data['BeginningBalanceCurrencyCode'] = isset($financial['BeginningBalance']['CurrencyCode']) ? $financial['BeginningBalance']['CurrencyCode'] : '';
			$data['FinancialEventGroupId'] = isset($financial['FinancialEventGroupId']) ? $financial['FinancialEventGroupId'] : '';
			$data['FinancialEventGroupStart'] = isset($financial['FinancialEventGroupStart']) ?  str_replace(array("T","Z"),array(" ",""), $financial['FinancialEventGroupStart']) : '';
			$data['FinancialEventGroupEnd'] = isset($financial['FinancialEventGroupEnd']) ? str_replace(array("T","Z"),array(" ",""), $financial['FinancialEventGroupEnd']) : '';
			$data['AccountTail'] = isset($financial['AccountTail']) ? $financial['AccountTail'] : '';
			$data['FundTransferDate'] = isset($financial['FundTransferDate']) ? str_replace(array("T","Z"),array(" ",""), $financial['FundTransferDate']) : '';

			// var_dump($data); die('AAA');
			//产品表
	        $result = Db::name('amazon_financial')->where('FinancialEventGroupId', $data['FinancialEventGroupId'])->distinct('id')->select();
	        if($result){ //之前存在订单-更新
	            $data['id'] = $result[0]['id']; //数据中包含主键，可以直接更新
	            if('Succeeded' != $result[0]['FundTransferStatus'])
	            {
	            	// echo "updata" . $data['id'];
	            	Db::name('amazon_financial')->update($data);
	            }
	        }else{ //不存在订单，则证明是新订单-创建
	            Db::name('amazon_financial')->insert($data);
	        }
		}
	}
}


