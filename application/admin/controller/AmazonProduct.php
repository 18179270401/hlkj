<?php
namespace app\admin\controller;
// use app\admin\controller\CommonController;
use think\Db;
use think\Model;
use MyLib\AjaxPage;
use think\Controller;

//亚马逊订单接口类控制器
class AmazonProduct extends Controller
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

	//产品列表方法
	public function productindex(){
		$listRows = 1; //默认一页显示10条记录
		$p = input('p') ? input('p') : 1; //当前要显示第几页， 默认第一页
		$startDate = input('startDate') ? input('startDate') : date('Y-m-d 00:00:00');
		$endtDate = input('endtDate') ? input('endtDate') : date('Y-m-d 23:59:59');                   

		//查询订单列表
        $ProductsList = Db::name('amazon_product')->limit(($p-1)*$listRows . ',' . $listRows)->select();

		//根据条件当前所i需展示记录总条数
		$ProductsTotal = $count = Db::name('amazon_product')->count();

		$AjaxPage = new AjaxPage($ProductsTotal, $listRows, 'page_show'); //第三个参数是你需要调用换页的ajax函数名
		$page = $AjaxPage->show();

		$this->assign('ProductsList', $ProductsList); //产品列表
		$this->assign('page', $page); //分页列表
        $this->assign('listRows', $listRows); //分页列表

		return $this->fetch();
	}


        //分页，获取指定页的订单
    public function getPageProduct(){
        $p = input('p') ? input('p') : 1; //当前要显示第几页， 默认第一页
        $listRows = input('listRows'); //一次查询记录条数

        $ProductsList = Db::name('amazon_product')->limit(($p-1)*$listRows . ',' .$listRows)->select();

        $count = Db::name('amazon_product')->count();
        $AjaxPage = new AjaxPage($count, $listRows, 'page_show'); //第三个参数是你需要调用换页的ajax函数名
        $page = $AjaxPage->show();

        $data = array(
            'ProductsList' => $ProductsList,
            'page' => $page,
        );

        echo json_encode($data);
        exit();
    }

    //根据订单号获取订单详情
    public function getProductDetail(){
        $productno = input('ProductNo');
        $productItems = Db::name('amazon_product')->where('ProductNo', $productno)->find();

        echo json_encode($productItems);
        exit();
    }


    public function ShowProductAdd(){
        $CategoryList = Db::name("product_category")->distinct("CategoryName")->select();
        $this->assign('CategoryList', $CategoryList); //订单列表

        $TrademarkList = Db::name("product_trademark")->distinct("ChinaName")->select();
        $this->assign('TrademarkList', $TrademarkList); //订单列表

        return $this->fetch('productadd');
    }

	// //添加产品
 //    public function productadd(){
 //    	$updata = array();

 //        if(request()->isPost()){
 //            $data = input('post.');           
 //            $updata["ProductType"] = $data['producttype'];
 //            $updata["ProductNo"] = $data['productno'];
 //            $updata["ProductSKU"] = $data['productsku'];
 //            $updata["ProductName"] = $data['productname'];
 //            $updata["ProductPurchaseTax"] = $data['PurchaseTax'];
 //            $updata["ProductPurchase"] = $data['Purchase'];
 //            $updata["ProductStatus"] = $data['productsta'];
 //            $updata["ProductSellSta"] = $data['productsell'];
 //            $updata["ProductAttr"] = $data['productattr'];
 //            $updata["ProductGrade"] = $data['productgrade'];
 //            $updata["ProductLink"] = $data['productlink'];
 //            $updata["ProductQualityDate"] = $data['qualityDate'];
 //            $updata["ProductSuttle"] = $data['productsuttle'];
 //            $updata["ProductGross"] = $data['productgross'];
 //            $updata["ProductPackType"] = $data['packtype'];
 //            $updata["ProductLength"] = $data['productL'];
 //            $updata["ProductWide"] = $data['productW'];
 //            $updata["ProductHigh"] = $data['productH'];
 //            $updata["ProductPackLenght"] = $data['packL'];
 //            $updata["ProductPackWide"] = $data['packW'];
 //            $updata["ProductPackHigh"] = $data['packH'];
 //            $updata["ProductPackSuttle"] = $data['packsuttle'];
 //            $updata["ProductPackGross"] = $data['packgross'];
 //            $updata["ProductReportMoney"] = $data['reportmoney'];
 //            $updata["ProductReportWeight"] = $data['reportweight'];
 //            $updata["ProductFreightFrist"] = $data['freightfirst'];
 //            $updata["ProductHSCode"] = $data['HSCode'];
 //            $updata["ProductHSNameChina"] = $data['declarenameC'];
 //            $updata["ProductHSNameEnglish"] = $data['declarenameE'];
 //            $updata["ProductDescribe"] = $data['hlcontent'];
 //            $updata["ProductPhoto"] = "";
 //            $updata["ProductPhotoLink"] = $data['photolink'];
 //            $updata["ProductBuildTime"] = date("Y-m-d H:i:s a l");
 //            $productno = $data['productno'];

 //            if(isset($_FILES["up_img"])){
 //                if ($_FILES["up_img"]["size"] > 0){
 //                    if ($_FILES["up_img"]["error"] > 0){
 //                        $this->success('新增失败！');
 //                    }else{
 //                        if ((($_FILES["up_img"]["type"] == "image/gif")
 //                        || ($_FILES["up_img"]["type"] == "image/jpeg")
 //                        || ($_FILES["up_img"]["type"] == "image/pjpeg"))
 //                        && ($_FILES["up_img"]["size"] < 50*1024)){
 //                            $path = ROOT_PATH . 'public/static/images/product/';
 //                            if (!file_exists ($path)){
 //                                mkdir($path, 777); 
 //                            }
 //                            if (file_exists($path . $_FILES["up_img"]["name"])){
 //                                $this->error('图片重复,新增失败！');
 //                            }else{
 //                                move_uploaded_file($_FILES["up_img"]["tmp_name"],
 //                                $path . $_FILES["up_img"]["name"]);
 //                                $updata["ProductPhoto"] = '/public/static/images/product/' . $_FILES["up_img"]["name"];
 //                            }
 //                        }else{
 //                            $this->error('图片无效,新增失败！');
 //                        }
 //                    }
 //                }
 //            }
 //            //产品表
 //            $result = Db::name('amazon_product')->where('ProductNo', $productno)->distinct('id')->select();
 //            if($result){ //之前存在订单-更新
 //                $updata['id'] = $result[0]['id']; //数据中包含主键，可以直接更新
 //                Db::name('amazon_product')->update($updata);
 //                $this->success('更新成功');
 //            }else{ //不存在订单，则证明是新订单-创建
 //                Db::name('amazon_product')->insert($updata);
 //                $this->success('新增成功');
 //            }
 //        }
 //    }


    //添加产品
    public function productadd(){
        $updata = array();
        if(request()->isPost()){
            $data = input('post.');  
            $validate = validate('Product');
            $validate->scene('productadd'); 

            if (!$validate->check($data)) {
                $this->error($validate->getError());
            } else{
                $updata["ProductType"] = $data['ProductType'];
                $updata["ProductNo"] = $data['ProductNo'];
                $updata["ProductSKU"] = $data['ProductSKU'];
                $updata["ProductName"] = $data['ProductName'];
                $updata["ProductPurchaseTax"] = $data['ProductPurchaseTax'];
                $updata["ProductPurchase"] = $data['ProductPurchase'];
                $updata["ProductStatus"] = $data['ProductStatus'];
                $updata["ProductSellSta"] = $data['ProductSellSta'];
                $updata["ProductTrademark"] = $data['ProductTrademark'];
                $updata["ProductGrade"] = $data['ProductGrade'];
                $updata["ProductLink"] = $data['ProductLink'];
                $updata["ProductQualityDate"] = $data['ProductQualityDate'];
                $updata["ProductSuttle"] = $data['ProductSuttle'];
                $updata["ProductGross"] = $data['ProductGross'];
                $updata["ProductPackType"] = $data['ProductPackType'];
                $updata["ProductLength"] = $data['ProductLength'];
                $updata["ProductWide"] = $data['ProductWide'];
                $updata["ProductHigh"] = $data['ProductHigh'];
                $updata["ProductPackLenght"] = $data['ProductPackLenght'];
                $updata["ProductPackWide"] = $data['ProductPackWide'];
                $updata["ProductPackHigh"] = $data['ProductPackHigh'];
                $updata["ProductPackSuttle"] = $data['ProductPackSuttle'];
                $updata["ProductPackGross"] = $data['ProductPackGross'];
                $updata["ProductReportMoney"] = $data['ProductReportMoney'];
                $updata["ProductReportWeight"] = $data['ProductReportWeight'];
                $updata["ProductFreightFrist"] = $data['ProductFreightFrist'];
                $updata["ProductHSCode"] = $data['ProductHSCode'];
                $updata["ProductHSNameChina"] = $data['ProductHSNameChina'];
                $updata["ProductHSNameEnglish"] = $data['ProductHSNameEnglish'];
                $updata["ProductDescribe"] = $data['ProductDescribe'];
                $updata["ProductPhoto"] = "";
                $updata["ProductPhotoLink"] = $data['ProductPhotoLink'];
                $updata["ProductBuildTime"] = date("Y-m-d H:i:s a l");

                $productno = $data['ProductNo'];

                if(isset($_FILES["file"])){
                    if ($_FILES["file"]["size"] > 0){
                        if ($_FILES["file"]["error"] > 0){
                            $this->error('新增失败！');
                            exit;
                        }else{
                            $path = ROOT_PATH . 'public/static/images/product/';
                            if (!file_exists ($path)){
                                mkdir($path, 777); 
                            }
                            if (file_exists($path . $_FILES["file"]["name"])){
                                $this->error('图片重复,新增失败！');
                                exit;
                            }else{
                                move_uploaded_file($_FILES["file"]["tmp_name"],
                                $path . $_FILES["file"]["name"]);
                                $updata["ProductPhoto"] = '/public/static/images/product/' . $_FILES["file"]["name"];
                            }
                        }
                    }
                }
                
                //产品表
                $result = Db::name('amazon_product')->where('ProductNo', $productno)->distinct('id')->select();
                if($result){ //之前存在订单-更新
                    $updata['id'] = $result[0]['id']; //数据中包含主键，可以直接更新
                    Db::name('amazon_product')->update($updata);
                    $this->success('更新成功');
                }else{ //不存在订单，则证明是新订单-创建
                    Db::name('amazon_product')->insert($updata);
                    $this->success('新增成功');
                }
            }
        }
    }


    public function productedit($id)
    {
        $CategoryList = Db::name("product_category")->distinct("CategoryName")->select();
        $this->assign('CategoryList', $CategoryList); //订单列表

        $TrademarkList = Db::name("product_trademark")->distinct("ChinaName")->select();
        $this->assign('TrademarkList', $TrademarkList); //订单列表

        $data = Db::name('amazon_product')->where('id', $id)->find();
        $this->assign('data', $data); //订单列表

        return $this->fetch();
    }


    public function ProductUpdate()
    {
        $this->productadd();
    }


    //删除用户
    public function DeleteProduct($id)
    {
        // var_dump($id);die('AAAA');
        $ProductName =  Db::name('amazon_product')->where('id',$id)->value('ProductName');
        $db = Db::name('amazon_product')->where('id', $id)->delete();
        if(true == $db){
            $this->success('删除['.$ProductName.']成功');
        }else{
             $this->error('删除失败');
        }
    }



    public function categoryindex()
    {
        $listRows = 1; //默认一页显示10条记录
        $p = input('p') ? input('p') : 1; //当前要显示第几页， 默认第一页

        //查询订单列表
        $CategoryList = Db::name('product_category')->limit(($p-1)*$listRows . ',' .$listRows)->select();
        //根据条件当前所i需展示记录总条数
        $CategorysTotal = Db::name('product_category')->count();

        $AjaxPage = new AjaxPage($CategorysTotal, $listRows, 'page_show'); //第三个参数是你需要调用换页的ajax函数名
        $page = $AjaxPage->show();

        $this->assign('CategoryList', $CategoryList); //产品列表
        $this->assign('page', $page); //分页列表
        $this->assign('listRows', $listRows); //分页列表

        return $this->fetch();
    }   


    //改变每页显示订单记录条数
    public function getPageCategory(){
        $p = input('p') ? input('p') : 1; //当前要显示第几页， 默认第一页
        $listRows = input('listRows'); //一次查询记录条数

        $CategoryList = Db::name('product_category')->limit(($p-1)*$listRows . ',' .$listRows)->select();

        $count = Db::name('product_category')->count();
        $AjaxPage = new AjaxPage($count, $listRows, 'page_show'); //第三个参数是你需要调用换页的ajax函数名
        $page = $AjaxPage->show();

        $data = array(
            'CategoryList' => $CategoryList,
            'page' => $page,
        );

        echo json_encode($data);
        exit();
    }


    //根据订单号获取订单详情
    public function getCategoryDetail(){
        $CategoryNo = input('CategoryNo');

        $CategoryItems = Db::name('product_category')->where('CategoryNo', $CategoryNo)->find();

        echo json_encode($CategoryItems);
        exit();
    }


    public function ShowCategoryAdd(){
        $CategoryList = Db::name("product_category")->distinct("CategoryName")->select();
        $this->assign('CategoryList', $CategoryList); //订单列表

        return $this->fetch('categoryadd');   
    }

    //添加产品
    public function categoryadd(){
    	$updata = array();
        if(request()->isPost()){
            $data = input('post.');
            
            $validate = validate('Product');
            $validate->scene('categoryadd'); 

            $updata["CategoryNo"] = $data['categoryno'];
            $updata["CategoryName"] = $data['categoryname'];
            $updata["TransportCost"] = $data['transportcost'];
            $updata["PackCost"] = $data['packcost'];
            $updata["ReportMoney"] = $data['reportmoney'];
            $updata["HSNameEnglish"] = $data['declarenameE'];
            $updata["HSNameChina"] = $data['declarenameC'];
            $updata["HSCode"] = $data['HSCode'];
            $updata["Category"] = $data['category'];
            $updata["CategorySta"] = $data['categorysta'];

            $CategoryNo = $data['categoryno'];
           
            // print_r($updata);die('AAABBB');

            if (!$validate->check($updata)) {
                $this->error($validate->getError());
            }else{
                //产品表
                $result = Db::name('product_category')->where('CategoryNo', $CategoryNo)->distinct('id')->select();

                if($result){ //之前存在订单-更新
                    $updata['id'] = $result[0]['id']; //数据中包含主键，可以直接更新
                    Db::name('product_category')->update($updata);
                    $this->success('更新成功');
                }else{ //不存在订单，则证明是新订单-创建
                    Db::name('product_category')->insert($updata);
                    $this->success('新增成功');
                }
            }
        }
    }


    public function categoryedit($id)
    {
        $CategoryList = Db::name("product_category")->distinct("CategoryName")->select();
        $this->assign('CategoryList', $CategoryList); //订单列表

        $data = Db::name('product_category')->where('id', $id)->find();
        $this->assign('data', $data); //订单列表

        return $this->fetch();
    }


    public function categoryUpdate()
    {
        $this->categoryadd();
    }


    //删除用户
    public function DeleteCategory()
    {
        $id = $this->request->post('id');
        $CategoryName =  Db::name('product_category')->where('id',$id)->value('CategoryName');
        $db = Db::name('product_category')->where('id', $id)->delete();
        if(true == $db){
                $this->success('删除['.$CategoryName.']成功');
        }else{
             $this->error('删除失败');
        }
    }

    
    public function productskuindex()
    {
    	$listRows = 10; //默认一页显示10条记录
        $p = input('p') ? input('p') : 1; //当前要显示第几页， 默认第一页

        //查询订单列表
        $ProductSkuList = Db::name('product_sku')->limit(($p-1)*$listRows . ',' .$listRows)->select();

        // print_r($ProductSkuList);
        // exit('AAA');

        //根据条件当前所i需展示记录总条数
        $productskusTotal = Db::name('product_sku')->count();

        $AjaxPage = new AjaxPage($productskusTotal, $listRows, 'page_show'); //第三个参数是你需要调用换页的ajax函数名
        $page = $AjaxPage->show();

        $this->assign('ProductSkuList', $ProductSkuList); //产品列表
        $this->assign('page', $page); //分页列表
        $this->assign('listRows', $listRows); //分页列表

        return $this->fetch();
    }


        //改变每页显示订单记录条数
    public function getPageProductSku(){
        $p = input('p') ? input('p') : 1; //当前要显示第几页， 默认第一页
        $listRows = input('listRows'); //一次查询记录条数

        $ProductSkuList = Db::name('product_sku')->limit(($p-1)*$listRows . ',' .$listRows)->select();

        $count = Db::name('product_sku')->count();
        $AjaxPage = new AjaxPage($count, $listRows, 'page_show'); //第三个参数是你需要调用换页的ajax函数名
        $page = $AjaxPage->show();

        $data = array(
            'ProductSkuList' => $ProductSkuList,
            'page' => $page,
        );

        echo json_encode($data);
        exit();
    }


    //AJAX 异步查询SKU对应产品信息
    public function searchProductSku(){
        //接收AJAX  post传递过来的参数
        $data = input('post.');

        //查询内部SKU对应的所有外部SKU产品信息
        $p_sku = Db::name('product_sku')->where('InnerSKU', '=', $data['sku'])->select();

        //组装数组，打包成json格式数据，返还给前端
        $result = array(
            'list' => $p_sku
        );

        print_r(json_encode($result));
        exit();
    }

    //根据订单号获取订单详情
    public function getProductSkuDetail(){
        $ProductSku = input('ProductSku');

        $ProductItems = Db::name('product_sku')->where('ProductSku', $ProductSku)->find();

        echo json_encode($ProductItems);
        exit();
    }


    public function ShowProductSkuAdd(){

        return $this->fetch('productskuadd');   
    }


    //添加产品
    public function productskuadd(){
    	$updata = array();
        if(request()->isPost()){
            $data = input('post.');

            $updata["InnerSKU"] = $data['InnerSKU'];
            $updata["OuterSKU"] = $data['OuterSKU'];
            $updata["cname"] = $data['cname'];
            $updata["ename"] = $data['ename'];
            $updata["PlatformAccount"] = $data['PlatformAccount'];

            $OuterSKU = $data['OuterSKU'];
           
 			// print_r($updata);//die('AAABBB');

            //产品表
            $result = Db::name('product_sku')->where('OuterSKU', $OuterSKU)->distinct('id')->select();
			if($result){ //之前存在订单-更新
				$updata['id'] = $result[0]['id']; //数据中包含主键，可以直接更新
				Db::name('product_sku')->update($updata);
                $this->success('更新成功');
			}else{ //不存在订单，则证明是新订单-创建
				Db::name('product_sku')->insert($updata);
                $this->success('新增成功');
			}
        }
    }


    public function productskuedit($id)
    {       
        $data = Db::name('product_sku')->where('id', $id)->find();
        $this->assign('data', $data); //订单列表

        return $this->fetch();
    }


    public function productskuUpdate()
    {
        $this->productskuadd();
    }

    //删除用户
    public function DeleteProductSku()
    {
        $id = $this->request->post('id');
        $InnerSKU =  Db::name('product_sku')->where('id',$id)->value('InnerSKU');
        $db = Db::name('product_sku')->where('id', $id)->delete();
        if(true == $db){
                $this->success('删除['.$InnerSKU.']成功');
        }else{
             $this->error('删除失败');
        }
    }


    public function warehouseskuindex()
    {
    	$listRows = 1; //默认一页显示10条记录
        $p = input('p') ? input('p') : 1; //当前要显示第几页， 默认第一页

        //查询订单列表
        $WarehouseSkuList =  Db::name('warehouse_sku')->limit(($p-1)*$listRows . ',' .$listRows)->select();
        //根据条件当前所i需展示记录总条数
        $warehouseskusTotal = Db::name('warehouse_sku')->count();

        $AjaxPage = new AjaxPage($warehouseskusTotal, $listRows, 'page_show'); //第三个参数是你需要调用换页的ajax函数名
        $page = $AjaxPage->show();

        $this->assign('WarehouseSkuList', $WarehouseSkuList); //产品列表
        $this->assign('page', $page); //分页列表
        $this->assign('listRows', $listRows); //分页列表

        return $this->fetch();
    }

    //改变每页显示订单记录条数
    public function getPageWarehouseSku(){
        $p = input('p') ? input('p') : 1; //当前要显示第几页， 默认第一页
        $listRows = input('listRows'); //一次查询记录条数

        $WarehouseSkuList = Db::name('warehouse_sku')->limit(($p-1)*$listRows . ',' .$listRows)->select();

        $count = Db::name('warehouse_sku')->count();
        $AjaxPage = new AjaxPage($count, $listRows, 'page_show'); //第三个参数是你需要调用换页的ajax函数名
        $page = $AjaxPage->show();

        $data = array(
            'WarehouseSkuList' => $WarehouseSkuList,
            'page' => $page,
        );

        echo json_encode($data);
        exit();
    }

    //根据订单号获取订单详情
    public function getWarehouseSkuDetail(){
        $WarehouseSku = input('WarehouseSku');

        $WarehouseSkuItems = Db::name('product_trademark')->where('WarehouseSku', $WarehouseSku)->find();

        echo json_encode($WarehouseSkuItems);
        exit();
    }


    public function ShowWarehouseSkuAdd(){
        return $this->fetch('warehouseskuadd');   
    }

    //添加产品
    public function warehouseskuadd(){
    	$updata = array();
        if(request()->isPost()){
            $data = input('post.');

           	$updata["PlatformAccount"] = $data['PlatformAccount'];
            $updata["Warehouse"] = $data['Warehouse'];
            $updata["WarehouseSku"] = $data['WarehouseSku'];

            $WarehouseSku = $data['WarehouseSku'];
           
 			print_r($updata);//die('AAABBB');

            //产品表
            $result = Db::name('warehouse_sku')->where('WarehouseSku', $WarehouseSku)->distinct('id')->select();
			if($result){ //之前存在订单-更新
				$updata['id'] = $result[0]['id']; //数据中包含主键，可以直接更新
				Db::name('warehouse_sku')->update($updata);
                $this->success('更新成功');
			}else{ //不存在订单，则证明是新订单-创建
				Db::name('warehouse_sku')->insert($updata);
                $this->success('新增成功');
			}
        }
    }


    public function warehouseskuedit($id)
    {       
        $data = Db::name('warehouse_sku')->where('id', $id)->find();
        $this->assign('data', $data); //订单列表

        return $this->fetch();
    }


    public function warehouseskuUpdate()
    {
        $this->warehouseskuadd();
    }


    //删除用户
    public function DeleteWarehouseSku()
    {
        $id = $this->request->post('id');
        $WarehouseSku =  Db::name('warehouse_sku')->where('id',$id)->value('WarehouseSku');
        $db = Db::name('warehouse_sku')->where('id', $id)->delete();
        if(true == $db){
                $this->success('删除['.$WarehouseSku.']成功');
        }else{
             $this->error('删除失败');
        }
    }


    public function trademarkindex()
    {
    	$listRows = 1; //默认一页显示10条记录
        $p = input('p') ? input('p') : 1; //当前要显示第几页， 默认第一页

        //查询订单列表
        $TrademarksList = Db::name('product_trademark')->limit(($p-1)*$listRows . ',' .$listRows)->select();
        //根据条件当前所i需展示记录总条数
        $TrademarksTotal = Db::name('product_trademark')->count();

        $AjaxPage = new AjaxPage($TrademarksTotal, $listRows, 'page_show'); //第三个参数是你需要调用换页的ajax函数名
        $page = $AjaxPage->show();

        $this->assign('TrademarkList', $TrademarksList); //产品列表
        $this->assign('page', $page); //分页列表
        $this->assign('listRows', $listRows); //分页列表

        return $this->fetch();
    }


        //改变每页显示订单记录条数
    public function getPageTrademark(){
        $p = input('p') ? input('p') : 1; //当前要显示第几页， 默认第一页
        $listRows = input('listRows'); //一次查询记录条数

        $TrademarkList = Db::name('product_trademark')->limit(($p-1)*$listRows . ',' .$listRows)->select();

        $count = Db::name('product_trademark')->count();
        $AjaxPage = new AjaxPage($count, $listRows, 'page_show'); //第三个参数是你需要调用换页的ajax函数名
        $page = $AjaxPage->show();

        $data = array(
            'TrademarkList' => $TrademarkList,
            'page' => $page,
        );

        echo json_encode($data);
        exit();
    }


    //根据订单号获取订单详情
    public function getTrademarkDetail(){
        $TrademarkNo = input('TrademarkNo');

        $TrademarkItems = Db::name('product_trademark')->where('TrademarkNo', $TrademarkNo)->find();

        echo json_encode($TrademarkItems);
        exit();
    }


    public function ShowTrademarkAdd(){
            return $this->fetch('trademarkadd');   
        }

    //添加产品
    public function trademarkadd(){
    	$updata = array();
        if(request()->isPost()){
            $data = input('post.');

           	$updata["TrademarkNo"] = $data['TrademarkNo'];
            $updata["FatherTrademark"] = $data['FatherTrademark'];
            $updata["ChinaName"] = $data['ChinaName'];
            $updata["EnglishName"] = $data['EnglishName'];
            $updata["Link"] = $data['Link'];
            $updata["Explain"] = $data['Explain'];
            $updata["Delete"] = $data['Delete'];

            $TrademarkNo = $data['TrademarkNo'];
           
 			//print_r($updata);//die('AAABBB');

            //产品表
            $result = Db::name('product_trademark')->where('TrademarkNo', $TrademarkNo)->distinct('id')->select();
			if($result){ //之前存在订单-更新
				$updata['id'] = $result[0]['id']; //数据中包含主键，可以直接更新
				Db::name('product_trademark')->update($updata);
                $this->success('更新成功');
			}else{ //不存在订单，则证明是新订单-创建
				Db::name('product_trademark')->insert($updata);
                $this->success('新增成功');
			}
        }
    }


    public function trademarkedit($id)
    {       
        $data = Db::name('product_trademark')->where('id', $id)->find();
        $this->assign('data', $data); //订单列表

        return $this->fetch();
    }


    public function trademarkUpdate()
    {
        $this->trademarkadd();
    }

    //删除用户
    public function DeleteTrademark()
    {
        $id = $this->request->post('id');
        $ChinaName =  Db::name('product_trademark')->where('id',$id)->value('ChinaName');
        $db = Db::name('product_trademark')->where('id', $id)->delete();
        if(true == $db){
                $this->success('删除['.$ChinaName.']成功');
        }else{
             $this->error('删除失败');
        }
    }


	//导出Excel表格
	public function export_excel(){
		ini_set('memory_limit', '256M'); //升级为申请256M内存
		$startDate = input('startDate') ? input('startDate') : date('Y-m-d 00:00:00');
		$endtDate = input('endtDate') ? input('endtDate') : date('Y-m-d 23:59:59');
		$fileName = "order";
		$headArr = array();

		$sql = "desc hl_amazon_order";
		$fields = Db::query($sql);
		foreach($fields as $key => $val){
			$headArr[] = $val['Field'];
		}

		$sql = "select * from hl_amazon_order where PurchaseDate >  '$startDate' and PurchaseDate < '$endtDate' ";
		$data = Db::query($sql);

		//创建Excel文件
		if(!empty($data)){
			createExcel($fileName, $headArr, $data);
		}
	}	
}

