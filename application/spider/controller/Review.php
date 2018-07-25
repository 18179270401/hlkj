<?php
namespace app\spider\controller;
use think\Controller;
use think\Db;
use MyLib\AjaxPage;

//亚马逊订单接口类控制器
class Review extends Controller
{
	private $sku = '';
	private $asin = '';
	private $reviewPath = ROOT_PATH.'../hl-data/review/'; //review评论文件保存路径


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


	//获取亚马逊网页review差评HTML，保存为本地文本
	public function getReviewHTML(){
		//取出需要查询的review产品
		$reviewProduct = Db::name('amazon_review_product')->select();
		// $this->asin = $reviewProduct[0]['asin'];
		foreach ($reviewProduct as $key => $value) {
			$this->asin = $value['asin'];
			$this->reviewPath = $this->reviewPath . $this->asin . '.txt';
			//如果文件存在，就不再获取这个ASIN商品的HTML
			if(!file_exists($this->reviewPath)){
				write_review_log('准备请求review页面HTML', $this->asin);
				//如果没有对应的ASIN产品文件，就痛过http请求亚马逊，获取HTML保存问本地文本
				$reviewHTML = $this->https_request_review($this->asin);
				if($reviewHTML){
					file_put_contents($this->reviewPath, $reviewHTML);
					write_review_log($reviewHTML, $this->asin);
				}
			}else{
				write_review_log('对应的ASIN产品文件存在： ' . $this->asin . '.txt', $this->asin);
			}
		}	
	}


	//读取保存在本地的文本, review评论HTML文本内容
	public function readReviewFile(){
		//取出需要查询的review产品
		$reviewProduct = Db::name('amazon_review_product')->select();

		foreach($reviewProduct as $key => $val){
			$this->asin = $val['asin'];
			$this->reviewPath = $this->reviewPath . $this->asin . '.txt';

			//如果文件存在，就直接读取。
			if(file_exists($this->reviewPath)){
				write_review_log(['读取本地文本: ',  $this->asin.'.txt'], $this->asin);
				$reviewStr = file_get_contents($this->reviewPath);
				$this->analysis_review_html($reviewStr);
			}
		}
		
	}


	//http请求获取HTML页面
	public function https_request_review($keyword = null){
		$url = 'https://www.amazon.com/Iextreme-Essential-Aromatherapy-Adjustable-Waterless/product-reviews/'. $keyword .'/ref=cm_cr_dp_d_hist_1?ie=UTF8&filterByStar=one_star&reviewerType=all_reviews#reviews-filter-bar';

		echo $url;
		exit('AA');
		// $url = 'http://www.baidu.com';
		$header = array(
			// "Content-type: application/x-www-form-urlencoded";
			"user-agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/67.0.3396.99 Safari/537.36"
		);

		//CURL请求获取页面内容
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_HTTPHEADER,$header);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1 );
		curl_setopt($curl, CURLOPT_POST, false);
		$respon = curl_exec($curl);
		curl_close($curl);

		return $respon;
	}


	//review差评列表,分析review评论html内容提取出来
	public function analysis_review_html($review_str){
		//定义截取字符串区间
		$begin = 'reviews</a></span></div>';
		$end = '<div class="a-form-actions a-spacing-top-extra-large">';

		//用户评论字符串，review差评
		$review_str = $this->hl_cut($begin, $end, $review_str);

		//用css类样式做关键字，获取该段字符串中，评论条数。
		$count = substr_count($review_str, 'class="a-section celwidget"');
		$review_list = array();

		//按照指定子串，截取整个字符串为数组一个个元素，从后往前。
		while(count($review_list) < $count){
			//最后一条评论，在该字符串中位置
			$pos = strrpos($review_str, 'class="a-section celwidget"');

			if ($pos !== false) {
				//截取出来最后一条评论
			    $review_list[] = substr($review_str, $pos, strlen($review_str));

			    //将之前的评论（除最后一条），重新赋值给用户评论字符串
			    $review_str = substr($review_str, 0, $pos);
			}
		}

		$reviewData = array();

		//遍历处理每一位客户评论，取出需要的字段内容
		foreach ($review_list as $key => $value) {
			$reviewData = array();
			//获取评论几颗星
			$strCut = '<span class="a-icon-alt">';
			$starPos = mb_strpos($value, $strCut) + mb_strlen($strCut);
			$star = substr($value, $starPos, 1);

			//获取评论日期
			$review_date = $this->get_review_date($value);
			
			// 获取评论人名字
			$strCut = '<a data-hook="review-author"';
			$pos = mb_strpos($value, $strCut);
			$str = mb_substr($value, $pos);
			$begin = '>';
			$end = '</a>';
			$name = $this->hl_cut($begin, $end, $str);

			//获取Buyer ID 
			$strCut = 'href="/gp/profile/amzn1.account.';
			$pos = mb_strpos($value, $strCut) + mb_strlen($strCut);
			$buyer_id = mb_substr($value, $pos, 50);
			$endStrCut = '/';
			$pos = mb_strpos($buyer_id, $endStrCut);
			$buyer_id = mb_substr($buyer_id, 0, $pos);

			//获取评论内容
			$strCut = '<span data-hook="review-body" class="a-size-base review-text">';
			$pos = mb_strpos($value, $strCut) + mb_strlen($strCut);
			$comment = mb_substr($value, $pos);
			$endStrCut = '</span>';
			$pos = mb_strpos($comment, $endStrCut);
			$comment = mb_substr($comment, 0, $pos);


			//将获取到的字段，打包成数组
			$reviewData['star'] = $star;
			$reviewData['review_date'] = $review_date;
			$reviewData['name'] = $name;
			$reviewData['comment'] = $comment;
			$reviewData['buyer_id'] = $buyer_id;

			//将字段内容保存到数据库
			$this->save_review($reviewData);
			
		}

	}


	//获取review评论日期
	public function get_review_date($value){
		$strCut = '<span data-hook="review-date" class="a-size-base a-color-secondary review-date">';
		$datePos = mb_strpos($value, $strCut) + mb_strlen($strCut) + 3;
		$review_date = mb_substr($value, $datePos, 30);
		$year = date('y');
		$pos = mb_strpos($review_date, $year) + 2;
		$review_date = mb_substr($review_date, 0, $pos);
		$month = substr($review_date, 0, strpos($review_date, ' '));
		$date = substr($review_date, -8, -6);
		$year = substr($review_date, -4);

		$reviewMonth = '';
		switch($month){
			case 'January':   $reviewMonth = '01'; break;
			case 'February':  $reviewMonth = '02'; break;
			case 'March':     $reviewMonth = '03'; break;
			case 'April':     $reviewMonth = '04'; break;
			case 'May':       $reviewMonth = '05'; break;
			case 'June':      $reviewMonth = '06'; break;
			case 'July':      $reviewMonth = '07'; break;
			case 'August':    $reviewMonth = '08'; break;
			case 'September': $reviewMonth = '09'; break;
			case 'October':   $reviewMonth = '10'; break;
			case 'November':  $reviewMonth = '11'; break;
			case 'December':  $reviewMonth = '12'; break;
		}

		$date =substr($date, 0, -1); //取出后面的逗号

		return $year. '-' . $reviewMonth . '-' . $date;
	}


	//将获取的review信息保存入数据库
	public function save_review($data){
		$review = array();
		$review['shop_name'] = isset($data['shop_name']) ? $data['shop_name'] : '';
		$review['review_date'] = isset($data['review_date']) ? $data['review_date'] : '';
		$review['star'] = $data['star'];
		$review['title'] = isset($data['title']) ? $data['title'] : '';
		$review['asin'] = $this->asin;
		$review['sku'] = isset($data['sku']) ? $data['sku'] : '';
		$review['name'] = $data['name'];
		$review['comment'] = $data['comment'];
		$review['buyer_id'] = $data['buyer_id'];
		$review['amazon_order_id'] = isset($data['amazon_order_id']) ? $data['amazon_order_id'] : '';

		$order = Db::name('amazon_order')->where('buyer_name', '=', $review['name'])->select();
		if($order){
			$review['amazon_order_id'] = $order[0]['amazon_order_id'];
			
		}
		//插入数据库
		Db::name('amazon_review')->insert($review);
		
	}


	//获取产品SKU
	public function get_product_SKU(){

		return $this->fetch();
	}


	/**
	 * php截取指定两个字符串之间字符串，默认字符集为utf-8
	 * @param string $begin  开始字符串
	 * @param string $end    结束字符串
	 * @param string $str    需要截取的字符串
	 * @return string
	 */
	public function hl_cut($begin,$end,$str){
	    $b = mb_strpos($str,$begin) + mb_strlen($begin);
	    $e = mb_strpos($str,$end) - $b;

	    return mb_substr($str,$b,$e);
	}




}