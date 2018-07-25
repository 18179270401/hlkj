<?php
namespace app\api\controller\amazon;
use think\Controller;


//亚马逊订单接口类控制器
class AmazonCommonController extends Controller
{

	//亚马逊商城		MarketplaceId
	public function MarketplaceId_and_amazon_mall(){
		return [
			//北美地区
			'A2EUQ1WTGCTBG2'  => 'CA', //https://mws.amazonservices.ca
			'ATVPDKIKX0DER'   => 'US', //https://mws.amazonservices.com

			//欧洲地区
			'A1PA6795UKMFR9'  => 'DE', //https://mws-eu.amazonservices.com
			'A1RKKUPIHCS9HS'  => 'ES', //https://mws-eu.amazonservices.com
			'A13V1IB3VIYZZH'  => 'FR', //https://mws-eu.amazonservices.com
			'A21TJRUUN4KGV'   => 'IN', //https://mws.amazonservices.in
			'APJ6JRA9NG5V4'   => 'IT', //https://mws-eu.amazonservices.com
			'A1F83G8C2ARO7P'  => 'UK', //https://mws-eu.amazonservices.com

			//远东地区
			'A1VC38T7YXB528'  => 'JP', //https://mws.amazonservices.jp

			//中国地区
			'AAHKV2X7AFYLW'   => 'CN', //https://mws.amazonservices.com.cn
		];
	}


	//API 对接参数密钥信息
	public function getApiParam(){
		return [
			[
				//亚马逊店铺账户： iextremedirect@163.com
				'serviceUrl' => "https://mws.amazonservices.com/Orders/2013-09-01", //亚马逊 MWS 端点（美国）
				'AWS_ACCESS_KEY_ID' => 'AKIAISSY46V3P7WKG2AA',
				'APPLICATION_NAME' => 'AmazonJavascriptScratchpad',//应用程序名称
				'APPLICATION_VERSION' => '1.0',//应用程序版本
				'AWS_SECRET_ACCESS_KEY' => '0gS+BhoVkvSqUvz+x0oWyJyBhr+XoGHKeNGLG39x',
				'MERCHANT_ID' => 'A2S4I05OO8WH9P', //店铺ID
				'MARKETPLACE_ID' => 'ATVPDKIKX0DER', //商城编号
				'shopName' => 'IE'  //店铺名称，内部称呼
			],
			[
				//亚马逊美国店铺  MK
				'serviceUrl' => "https://mws.amazonservices.com/Orders/2013-09-01", //亚马逊 MWS 端点（美国）
				'AWS_ACCESS_KEY_ID' => 'AKIAIARZVRJAMTBGFFYQ',
				'APPLICATION_NAME' => 'AmazonJavascriptScratchpad',//应用程序名称
				'APPLICATION_VERSION' => '1.0',//应用程序版本
				'AWS_SECRET_ACCESS_KEY' => 'WBmVuup/fPyyswP5dyf/XU5evM/mUXSXVRzDzo0F',
				'MERCHANT_ID' => 'A2XRGR2B57H8XC', //店铺ID
				'MARKETPLACE_ID' => 'ATVPDKIKX0DER', //商城编号
				'shopName' => 'MK'  //店铺名称，内部称呼
			],
			//后续其他店铺
		];

	}


}