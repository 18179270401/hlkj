<?php
namespace app\admin\controller;
use app\api\controller\amazon\AmazonCommonController;
use app\api\controller\amazon\Sellers;
use think\Db;
use MyLib\AjaxPage;
use think\Controller;

class AmazonSellers extends AmazonCommonController
{
	//测试方法
	public function test(){
		$nowtime = time();

		if(!session('CreatedAfterTime')){
			session('CreatedAfterTime', $nowtime);
		}

		$time = session('CreatedAfterTime');
		$t = gmdate("Y-m-d\TH:i:s\Z", $time);
		echo $t, '<br/>';

		//逻辑代码....

		session('CreatedAfterTime', $nowtime);
		echo $time;
		die('AAA');
		
	}


	public function index(){
	    $ShopList = $this->getApiParam();
		$this->assign('ShopList', $ShopList);

		$SellersData = '';
		$count = 0;
		$listRows = 30; //默认一页显示10条记录
	    $p = input('p') ? input('p') : 1; //当前要显示第几页， 默认第一页

	    $AjaxPage = new AjaxPage($count, $listRows, 'page_show'); //第三个参数是你需要调用换页的ajax函数名
	    $page = $AjaxPage->show();

	    $this->assign('listRows', $listRows);
	    $this->assign('SellersData',$SellersData);
	    $this->assign('page', $page); //分页列表

	    return $this->fetch();
	}


	public function SearchSellerMessage(){
		$ShopName = input('ShopName') ? input('ShopName') : 'IE'; 
		$p = input('p') ? input('p') : 1; //当前要显示第几页， 默认第一页
		// $listRows = input('listRows'); //一次查询记录条数
		$listRows = 30;

		$Sellsers = new Sellers();
		$Sellsers->SellersSetShopNameForQuery($ShopName);
		$SellersData = $Sellsers->SellersMarketplaceParticipationsList();
		$count = count($SellersData);

	    $AjaxPage = new AjaxPage($count, $listRows, 'page_show'); //第三个参数是你需要调用换页的ajax函数名
	    $page = $AjaxPage->show();

	    $data = array(
            'SellersData' => $SellersData,
            'page' => $page,
        );

	    echo json_encode($data);   

	    // date_format();	DATE_ISO8601	getCurrentTimestamp();   date('Y-m-d\TH:i:s\Z', time() - date('Z'));
        exit();
	}


	//改变每页显示订单记录条数
	public function getDisplayPage(){
		$p = input('p') ? input('p') : 1; //当前要显示第几页， 默认第一页
		$ShopName = input('ShopName') ? input('ShopName') : 'IE'; 
		// $listRows = input('listRows'); //一次查询记录条数
		$listRows = 30;

		$SellersData = $Sellsers->SellersMarketplaceParticipationsList();
		$count = count($SellersData);

		$AjaxPage = new AjaxPage($count, $listRows, 'page_show'); //第三个参数是你需要调用换页的ajax函数名
		$page = $AjaxPage->show();

		$data = array(
			'SellersData' => $SellersData,
			'page' => $page,
		);

		echo json_encode($data);
		exit();
	}

}
