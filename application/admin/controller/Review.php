<?php
namespace app\admin\controller;
use think\Controller;
use think\Db;
use MyLib\AjaxPage;

//亚马逊订单接口类控制器
class Review extends Controller
{
	private $sku = '';
	private $asin = '';
	private $textPath = ROOT_PATH.'../hl-data/review/review.txt'; //review评论文件保存路径


	public function test(){
		//页面执行过久导致提示php Maximum execution time of 30 seconds exceeded错误
		set_time_limit(0);


		// $url = 'https://www.amazon.com/Iextreme-Essential-Aromatherapy-Adjustable-Waterless/product-reviews/B076LZVSFD/ref=cm_cr_dp_d_hist_1?ie=UTF8&filterByStar=one_star&reviewerType=all_reviews#reviews-filter-bar';
		// $header = array(
		// 	// "Content-type: application/x-www-form-urlencoded";
		// 	"user-agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/67.0.3396.99 Safari/537.36"
		// );

		// $curl = curl_init();
		// curl_setopt($curl, CURLOPT_URL, $url);
		// curl_setopt($curl, CURLOPT_HTTPHEADER,$header);
		// curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		// curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
		// curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1 );
		// curl_setopt($curl, CURLOPT_POST, false);
		// $respon = curl_exec($curl);
		// curl_close($curl);

		// print_r($respon);
		// file_put_contents('1.txt', $respon);
		// exit('AA');
	}


	//review差评列表
	public function index(){
		$listRows = 15; //默认一页显示10条记录
		$p = input('p') ? input('p') : 1; //当前要显示第几页， 默认第一页

		$reviewList = Db::name('amazon_review')->limit(($p-1)*$listRows . ',' .$listRows)->select();

		
		$count = Db::name('amazon_review')->count();
		$AjaxPage = new AjaxPage($count, $listRows, 'page_show'); //第三个参数是你需要调用换页的ajax函数名
		$page = $AjaxPage->show();

		$this->assign('reviewList', $reviewList);
		$this->assign('page', $page);


		return $this->fetch();
	}






}