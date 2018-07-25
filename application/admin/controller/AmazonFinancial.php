<?php
namespace app\admin\controller;
use app\api\controller\amazon\AmazonCommonController;
use app\api\controller\amazon\Financial;
use think\Db;
use MyLib\AjaxPage;
// use think\Controller;

class AmazonFinancial extends AmazonCommonController
{
	private $ToType = "";
	private $Appkey = "c43af19d89f06ad2";
	// private $url = "http://api.jisuapi.com/exchange/convert";
	private $url = "http://api.jisuapi.com/exchange/single";
	private $ShopName = "";
	private $Rate = "";
	//测试方法
	public function test(){
		$nowtime = time();

		if(!session('CreatedAfterTime')){
			session('CreatedAfterTime', $nowtime);
		}

		$time = session('CreatedAfterTime');
		$t = gmdate("Y-m-d\TH:i:s\Z", $time);
		echo $t. '<br/>';

		//逻辑代码....

		session('CreatedAfterTime', $nowtime);
		echo $time;
		die('AAA');
		
	}

	public function index(){
		$ShopList = $this->getApiParam();
		$this->assign('ShopList', $ShopList);

		$RateData = '';
		$this->assign('RateData', $RateData);

		$CurrencyCode = Db::name('exchange_rate')->select();
		$this->assign('CurrencyCode',$CurrencyCode);

		$Sql = "select distinct ProcessingStatus from hl_amazon_financial";
		$FinancialStatus = Db::query($Sql);
		// $FinancialStatus = Db::name('amazon_financial')->distinct('ProcessingStatus')->select();//有问题
		$this->assign('FinancialStatus',$FinancialStatus);

		$Sql = "select distinct FundTransferStatus from hl_amazon_financial";
		$FinancialFundStatus = Db::query($Sql);
		// $FinancialFundStatus = Db::name('amazon_financial')->distinct('FundTransferStatus')->select();//有问题
		$this->assign('FinancialFundStatus',$FinancialFundStatus);

		$FinancialData = '';
		$count = 0;
	    $p = input('p') ? input('p') : 1; //当前要显示第几页， 默认第一页
	    $listRows = input('listRows') ? input('listRows') : 10; //一次查询记录条数

	    $AjaxPage = new AjaxPage($count, $listRows, 'page_show'); //第三个参数是你需要调用换页的ajax函数名
	    $page = $AjaxPage->show();

	    $this->assign('listRows', $listRows);
	    $this->assign('FinancialData',$FinancialData);
	    $this->assign('page', $page); //分页列表

	    return $this->fetch();
	}


	//CURL   GET请求
    private function https_request_get($url, $data = '') {
        $curl = curl_init();
        $submit_url = $url . '?' . $data;
        //var_dump($submit_url);
        
        curl_setopt($curl, CURLOPT_URL, $submit_url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_TIMEOUT,60);
        
        $output = curl_exec($curl);
        curl_close($curl);
        return $output;
    }


    //CURL   POST请求
	private function https_request_post($url, $data = null) {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        if (!empty($data)) {
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        }
        /*
        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Content-Length: ' . strlen($data))
        );
        */
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_TIMEOUT,60); 
        $output = curl_exec($curl);
        curl_close($curl);
        return $output;
    }



	public function UpdateExchangeRate(){
		$get_data = ["appkey" => $this->Appkey, "currency" => "CNY"];
		$result = $this->https_request_get($this->url, http_build_query($get_data));
		
		// header('Content-Type:text/html; charset=UTF-8');
		$jsonarr = json_decode($result, true);
		 
		if($jsonarr['status'] != 0)
		{
		    echo $jsonarr['msg'];
		    exit();
		}

		$data = array();
		$RateData = $jsonarr['result']['list'];

		foreach($RateData as $key => $vo)
		{
			$data['CurrencyCode'] = $key;
			$rate = $vo['rate'];
			$rate = 1/$rate;
			// $rate = number_format($rate, 4, '.', ''); //round($num,4);
			$data['ExchangeRate'] = $rate;
			$data['CurrencyName'] = $vo['name'];
			$data['UpdateTime'] = $vo['updatetime'];
			$result = Db::name('exchange_rate')->where('CurrencyCode', $data['CurrencyCode'])->select();

	        if($result){ //之前存在订单-更新
	            $data['id'] = $result[0]['id']; //数据中包含主键，可以直接更新
	          	Db::name('exchange_rate')->update($data);
	        }else{ //不存在订单，则证明是新订单-创建
	            Db::name('exchange_rate')->insert($data);
	        }
		}
	}


	 public function GetExchangeRate(){
	 	$RateData = '';
		$this->ToType = input('ToMoney'); //一次查询记录条数

		if('' != $this->ToType)
		{
			$RateData = Db::name('exchange_rate')->where('CurrencyCode', $this->ToType)->find();
		}

		$data = array(
            'RateData' => $RateData,
        );

	    echo json_encode($data);   

        exit();
	 }



	public function Queryinformation(){
		$ShopName = input('ShopName') ? input('ShopName') : ''; 
		$StartTime = input('StartTime') ? input('StartTime') : '2018-06-01T00:00:00Z'; 
		$EndTime = input('EndTime') ? input('EndTime') : date("Y-m-d H:i:s a l");    //date('Y-m-d\TH:i:s\Z');
		$ProcessingStatus = input('ProcessingStatus') ? input('ProcessingStatus') : ''; 
		$FundTransferStatus = input('FundTransferStatus') ? input('FundTransferStatus') : ''; 
		$BeforeTotal = '';
		$EndTotal = '';

		$p = input('p') ? input('p') : 1; //当前要显示第几页， 默认第一页
		$listRows = input('listRows') ? input('listRows') : 10; //一次查询记录条数

		if('' == $ShopName){
			$FinancialData = '';
			$count = 0;
		}else{
			//产品表
			if('All' != $ShopName){
				if('All' != $ProcessingStatus){
					if('All' != $FundTransferStatus){
						$FinancialData = Db::name('amazon_financial')->where('ShopName', $ShopName)->where('ProcessingStatus', $ProcessingStatus)->where('FundTransferStatus', $FundTransferStatus)->where('FinancialEventGroupStart', '>=', $StartTime)->where('FinancialEventGroupEnd', '<=', $EndTime)->order('FinancialEventGroupStart desc')->limit(($p-1)*$listRows . ',' .$listRows)->select();
						$count = Db::name('amazon_financial')->where('ShopName', $ShopName)->where('ProcessingStatus', $ProcessingStatus)->where('FundTransferStatus', $FundTransferStatus)->where('FinancialEventGroupStart', '>=', $StartTime)->where('FinancialEventGroupEnd', '<=', $EndTime)->count();	
					}else{
						$FinancialData = Db::name('amazon_financial')->where('ShopName', $ShopName)->where('ProcessingStatus', $ProcessingStatus)->where('FinancialEventGroupStart', '>=', $StartTime)->where('FinancialEventGroupEnd', '<=', $EndTime)->order('FinancialEventGroupStart desc')->limit(($p-1)*$listRows . ',' .$listRows)->select();
						$count = Db::name('amazon_financial')->where('ShopName', $ShopName)->where('ProcessingStatus', $ProcessingStatus)->where('FinancialEventGroupStart', '>=', $StartTime)->where('FinancialEventGroupEnd', '<=', $EndTime)->count();
					}
				}else{
					if('All' != $FundTransferStatus){
						$FinancialData = Db::name('amazon_financial')->where('ShopName', $ShopName)->where('FundTransferStatus', $FundTransferStatus)->where('FinancialEventGroupStart', '>=', $StartTime)->where('FinancialEventGroupEnd', '<=', $EndTime)->order('FinancialEventGroupStart desc')->limit(($p-1)*$listRows . ',' .$listRows)->select();
						$count = Db::name('amazon_financial')->where('ShopName', $ShopName)->where('FundTransferStatus', $FundTransferStatus)->where('FinancialEventGroupStart', '>=', $StartTime)->where('FinancialEventGroupEnd', '<=', $EndTime)->count();	
					}else{
						$FinancialData = Db::name('amazon_financial')->where('ShopName', $ShopName)->where('FinancialEventGroupStart', '>=', $StartTime)->where('FinancialEventGroupEnd', '<=', $EndTime)->order('FinancialEventGroupStart desc')->limit(($p-1)*$listRows . ',' .$listRows)->select();
						$count = Db::name('amazon_financial')->where('ShopName', $ShopName)->where('FinancialEventGroupStart', '>=', $StartTime)->where('FinancialEventGroupEnd', '<=', $EndTime)->count();
					}
				}

			}else{
				if('All' != $ProcessingStatus){
					if('All' != $FundTransferStatus){
						$FinancialData = Db::name('amazon_financial')->where('ProcessingStatus', $ProcessingStatus)->where('FundTransferStatus', $FundTransferStatus)->where('FinancialEventGroupStart', '>=', $StartTime)->where('FinancialEventGroupEnd', '<=', $EndTime)->order('FinancialEventGroupStart desc')->limit(($p-1)*$listRows . ',' .$listRows)->select();
						$count = Db::name('amazon_financial')->where('ProcessingStatus', $ProcessingStatus)->where('FundTransferStatus', $FundTransferStatus)->where('FinancialEventGroupStart', '>=', $StartTime)->where('FinancialEventGroupEnd', '<=', $EndTime)->count();	
					}else{
						$FinancialData = Db::name('amazon_financial')->where('ProcessingStatus', $ProcessingStatus)->where('FinancialEventGroupStart', '>=', $StartTime)->where('FinancialEventGroupEnd', '<=', $EndTime)->order('FinancialEventGroupStart desc')->limit(($p-1)*$listRows . ',' .$listRows)->select();
						$count = Db::name('amazon_financial')->where('ProcessingStatus', $ProcessingStatus)->where('FinancialEventGroupStart', '>=', $StartTime)->where('FinancialEventGroupEnd', '<=', $EndTime)->count();
					}
				}else{
					if('All' != $FundTransferStatus){
						$FinancialData = Db::name('amazon_financial')->where('FundTransferStatus', $FundTransferStatus)->where('FinancialEventGroupStart', '>=', $StartTime)->where('FinancialEventGroupEnd', '<=', $EndTime)->order('FinancialEventGroupStart desc')->limit(($p-1)*$listRows . ',' .$listRows)->select();
						$count = Db::name('amazon_financial')->where('FundTransferStatus', $FundTransferStatus)->where('FinancialEventGroupStart', '>=', $StartTime)->where('FinancialEventGroupEnd', '<=', $EndTime)->count();	
					}else{
						$FinancialData = Db::name('amazon_financial')->where('FinancialEventGroupStart', '>=', $StartTime)->where('FinancialEventGroupEnd', '<=', $EndTime)->order('FinancialEventGroupStart desc')->limit(($p-1)*$listRows . ',' .$listRows)->select();
						$count = Db::name('amazon_financial')->where('FinancialEventGroupStart', '>=', $StartTime)->where('FinancialEventGroupEnd', '<=', $EndTime)->count();
					}
				}
			}
			
			$ShopList = $this->getApiParam();

			foreach($FinancialData as &$Data)
			{		
				$this->Rate = Db::name('exchange_rate')->where('CurrencyCode', $Data['BeginningBalanceCurrencyCode'] )->find();
				$Data['BeforeCNY'] = $Data['BeginningBalanceCurrencyAmount'] * $this->Rate['ExchangeRate'];
				$Data['BeforeCNY'] = number_format($Data['BeforeCNY'], 2, '.', ''); //round($num,4);

				$BeforeTotal += $Data['BeforeCNY'];

				$this->Rate = Db::name('exchange_rate')->where('CurrencyCode', $Data['OriginalTotalCurrencyCode'] )->find();				
				$Data['EndCNY'] = $Data['OriginalTotalCurrencyAmount'] * $this->Rate['ExchangeRate'];
				$Data['EndCNY'] = number_format($Data['EndCNY'], 2, '.', '');

				$EndTotal += $Data['EndCNY'];
				
				if('' == $Data['FundTransferStatus'])
				{
					$Data['FundTransferDate'] = '';
				}
				if('Closed' != $Data['ProcessingStatus'])
				{
					$d=strtotime("+14 Days", strtotime($Data['FinancialEventGroupStart']));
					$Data['FinancialEventGroupEnd'] = date("Y-m-d H:i:s", $d);
				}

				foreach($ShopList as $api){
					//循环遍历出每个店铺对接参数
					if($Data['ShopName'] == $api['shopName']); //商城编号
					{
						$MarketplaceId = $this->MarketplaceId_and_amazon_mall();
						$Data['MarketplaceID'] = $MarketplaceId[$api['MARKETPLACE_ID']];
					}
				}
			}
		}  

	    $AjaxPage = new AjaxPage($count, $listRows, 'page_show'); //第三个参数是你需要调用换页的ajax函数名
	    $page = $AjaxPage->show();

	    $Total = array('BeforeTotal' => $BeforeTotal, 'EndTotal' => $EndTotal,);

	    $data = array(
            'FinancialData' => $FinancialData,
            'page' => $page,
            'Total' => $Total,
        );

	    echo json_encode($data);   

        exit();
	}


	//改变每页显示订单记录条数
	public function getDisplayPage(){
		$ShopName = input('ShopName') ? input('ShopName') : ''; 
		$StartTime = input('StartTime') ? input('StartTime') : '2018-06-01T00:00:00Z'; 
		$EndTime = input('EndTime') ? input('EndTime') : date("Y-m-d H:i:s a l");    //date('Y-m-d\TH:i:s\Z');
		$ProcessingStatus = input('ProcessingStatus') ? input('ProcessingStatus') : ''; 
		$FundTransferStatus = input('FundTransferStatus') ? input('FundTransferStatus') : ''; 
		$BeforeTotal = '';
		$EndTotal = '';

		$p = input('p') ? input('p') : 1; //当前要显示第几页， 默认第一页
		$listRows = input('listRows') ? input('listRows') : 10; //一次查询记录条数

		if('' == $ShopName){
			$FinancialData = '';
			$count = 0;
		}else{
			//产品表
			if('All' != $ShopName){
				if('All' != $ProcessingStatus){
					if('All' != $FundTransferStatus){
						$FinancialData = Db::name('amazon_financial')->where('ShopName', $ShopName)->where('ProcessingStatus', $ProcessingStatus)->where('FundTransferStatus', $FundTransferStatus)->where('FinancialEventGroupStart', '>=', $StartTime)->where('FinancialEventGroupEnd', '<=', $EndTime)->order('FinancialEventGroupStart desc')->limit(($p-1)*$listRows . ',' .$listRows)->select();
						$count = Db::name('amazon_financial')->where('ShopName', $ShopName)->where('ProcessingStatus', $ProcessingStatus)->where('FundTransferStatus', $FundTransferStatus)->where('FinancialEventGroupStart', '>=', $StartTime)->where('FinancialEventGroupEnd', '<=', $EndTime)->count();	
					}else{
						$FinancialData = Db::name('amazon_financial')->where('ShopName', $ShopName)->where('ProcessingStatus', $ProcessingStatus)->where('FinancialEventGroupStart', '>=', $StartTime)->where('FinancialEventGroupEnd', '<=', $EndTime)->order('FinancialEventGroupStart desc')->limit(($p-1)*$listRows . ',' .$listRows)->select();
						$count = Db::name('amazon_financial')->where('ShopName', $ShopName)->where('ProcessingStatus', $ProcessingStatus)->where('FinancialEventGroupStart', '>=', $StartTime)->where('FinancialEventGroupEnd', '<=', $EndTime)->count();
					}
				}else{
					if('All' != $FundTransferStatus){
						$FinancialData = Db::name('amazon_financial')->where('ShopName', $ShopName)->where('FundTransferStatus', $FundTransferStatus)->where('FinancialEventGroupStart', '>=', $StartTime)->where('FinancialEventGroupEnd', '<=', $EndTime)->order('FinancialEventGroupStart desc')->limit(($p-1)*$listRows . ',' .$listRows)->select();
						$count = Db::name('amazon_financial')->where('ShopName', $ShopName)->where('FundTransferStatus', $FundTransferStatus)->where('FinancialEventGroupStart', '>=', $StartTime)->where('FinancialEventGroupEnd', '<=', $EndTime)->count();	
					}else{
						$FinancialData = Db::name('amazon_financial')->where('ShopName', $ShopName)->where('FinancialEventGroupStart', '>=', $StartTime)->where('FinancialEventGroupEnd', '<=', $EndTime)->order('FinancialEventGroupStart desc')->limit(($p-1)*$listRows . ',' .$listRows)->select();
						$count = Db::name('amazon_financial')->where('ShopName', $ShopName)->where('FinancialEventGroupStart', '>=', $StartTime)->where('FinancialEventGroupEnd', '<=', $EndTime)->count();
					}
				}

			}else{
				if('All' != $ProcessingStatus){
					if('All' != $FundTransferStatus){
						$FinancialData = Db::name('amazon_financial')->where('ProcessingStatus', $ProcessingStatus)->where('FundTransferStatus', $FundTransferStatus)->where('FinancialEventGroupStart', '>=', $StartTime)->where('FinancialEventGroupEnd', '<=', $EndTime)->order('FinancialEventGroupStart desc')->limit(($p-1)*$listRows . ',' .$listRows)->select();
						$count = Db::name('amazon_financial')->where('ProcessingStatus', $ProcessingStatus)->where('FundTransferStatus', $FundTransferStatus)->where('FinancialEventGroupStart', '>=', $StartTime)->where('FinancialEventGroupEnd', '<=', $EndTime)->count();	
					}else{
						$FinancialData = Db::name('amazon_financial')->where('ProcessingStatus', $ProcessingStatus)->where('FinancialEventGroupStart', '>=', $StartTime)->where('FinancialEventGroupEnd', '<=', $EndTime)->order('FinancialEventGroupStart desc')->limit(($p-1)*$listRows . ',' .$listRows)->select();
						$count = Db::name('amazon_financial')->where('ProcessingStatus', $ProcessingStatus)->where('FinancialEventGroupStart', '>=', $StartTime)->where('FinancialEventGroupEnd', '<=', $EndTime)->count();
					}
				}else{
					if('All' != $FundTransferStatus){
						$FinancialData = Db::name('amazon_financial')->where('FundTransferStatus', $FundTransferStatus)->where('FinancialEventGroupStart', '>=', $StartTime)->where('FinancialEventGroupEnd', '<=', $EndTime)->order('FinancialEventGroupStart desc')->limit(($p-1)*$listRows . ',' .$listRows)->select();
						$count = Db::name('amazon_financial')->where('FundTransferStatus', $FundTransferStatus)->where('FinancialEventGroupStart', '>=', $StartTime)->where('FinancialEventGroupEnd', '<=', $EndTime)->count();	
					}else{
						$FinancialData = Db::name('amazon_financial')->where('FinancialEventGroupStart', '>=', $StartTime)->where('FinancialEventGroupEnd', '<=', $EndTime)->order('FinancialEventGroupStart desc')->limit(($p-1)*$listRows . ',' .$listRows)->select();
						$count = Db::name('amazon_financial')->where('FinancialEventGroupStart', '>=', $StartTime)->where('FinancialEventGroupEnd', '<=', $EndTime)->count();
					}
				}
			}

			$ShopList = $this->getApiParam();

			foreach($FinancialData as &$Data)
			{
				$this->Rate = Db::name('exchange_rate')->where('CurrencyCode', $Data['BeginningBalanceCurrencyCode'] )->find();
				$Data['BeforeCNY'] = $Data['BeginningBalanceCurrencyAmount'] * $this->Rate['ExchangeRate'];
				$Data['BeforeCNY'] = number_format($Data['BeforeCNY'], 2, '.', ''); //round($num,4);

				$BeforeTotal += $Data['BeforeCNY'];

				$this->Rate = Db::name('exchange_rate')->where('CurrencyCode', $Data['OriginalTotalCurrencyCode'] )->find();				
				$Data['EndCNY'] = $Data['OriginalTotalCurrencyAmount'] * $this->Rate['ExchangeRate'];
				$Data['EndCNY'] = number_format($Data['EndCNY'], 2, '.', '');

				$EndTotal += $Data['EndCNY'];
				
				if('' == $Data['FundTransferStatus'])
				{
					$Data['FundTransferDate'] = '';
				}
				if('Closed' != $Data['ProcessingStatus'])
				{
					$d=strtotime("+14 Days", strtotime($Data['FinancialEventGroupStart']));
					$Data['FinancialEventGroupEnd'] = date("Y-m-d H:i:s", $d);
				}

				foreach($ShopList as $api){
					//循环遍历出每个店铺对接参数
					if($Data['ShopName'] == $api['shopName']); //商城编号
					{
						$MarketplaceId = $this->MarketplaceId_and_amazon_mall();
						$Data['MarketplaceID'] = $MarketplaceId[$api['MARKETPLACE_ID']];
					}
				}
			}
		}  

		$AjaxPage = new AjaxPage($count, $listRows, 'page_show'); //第三个参数是你需要调用换页的ajax函数名
		$page = $AjaxPage->show();

		$Total = array('BeforeTotal' => $BeforeTotal, 'EndTotal' => $EndTotal,);

	    $data = array(
            'FinancialData' => $FinancialData,
            'page' => $page,
            'Total' => $Total,
        );

		echo json_encode($data);
		exit();
	}
 }
