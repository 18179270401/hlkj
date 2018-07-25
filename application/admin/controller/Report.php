<?php
namespace app\admin\controller;
use think\Controller;
use think\Db;
use MyLib\AjaxPage;

//亚马逊订单接口类控制器
class ReviewProduct extends Controller
{
	//review差评列表
	public function index(){

		$reviewProduct = Db::name('amazon_review_product')->select();

		$this->assign('reviewProduct', $reviewProduct);

		return $this->fetch();
	}


	//添加review产品
	public function add(){
		if(request()->isPost()){
			$data = input('post.');

			Db::name('amazon_review_product')->insert($data);

			$result = ['msg' => 'success'];

			exit(json_encode($result));

		}

		return $this->fetch();
	}


	//编辑review产品
	public function edit(){
		$id = input('id');
		if(request()->isPost()){
			$data = input('post.');

			Db::name('amazon_review_product')->update($data);

			$result = ['msg' => 'success'];

			exit(json_encode($result));

		}


		$reviewProduct = Db::name('amazon_review_product')->find($id);
		$this->assign('reviewProduct', $reviewProduct);

		return $this->fetch();
	}


	//删除review产品
	public function del(){
		$id = input('id');

		$result = Db::name('amazon_review_product')->delete($id);
		if($result){
			$this->success('删除成功！');
		}
	}


	//获取产品SKU
	public function getProductSKU(){

	}

}