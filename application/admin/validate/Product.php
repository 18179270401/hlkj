<?php
namespace app\admin\validate;

use think\Validate;

class Product extends Validate
{
    protected $rule = [
        'ProductNo'       => 'require|min:5|max:20|unique:amazon_product',
        'ProductSKU'      => 'require|min:5|max:20|unique:amazon_product',
        'ProductName'     => 'require|min:2|max:20|unique:amazon_product',


        "CategoryNo"      => 'require|min:5|max:20|unique:product_category',
        "CategoryName"    => 'require|min:5|max:20|unique:product_category',
        "HSNameEnglish"   => 'min:5|max:40|unique:product_category',
        "HSNameChina"     => 'min:5|max:40|unique:product_category',

        "TrademarkNo"      => 'require|min:5|max:20|unique:product_trademark',
        "ChinaName"        => 'require|min:5|max:40|unique:product_trademark',
        "EnglishName"      => 'require|min:5|max:40|unique:product_trademark',

        "InnerSKU"         => 'require|min:5|max:20|unique:product_sku',
        "OuterSKU"         => 'require|min:5|max:20|unique:product_sku',

        "Warehouse"        => 'require|min:5|max:40|unique:warehouse_sku',
        "WarehouseSku"     => 'require|min:5|max:20|unique:warehouse_sku',
    ];

    protected $message = [
        'ProductNo.require'       => '产品编号必须',
        'ProductNo.unique'        => '产品编号重复',
        'ProductNo.min'           => '产品编号最短5位',
        'ProductNo.max'           => '产品编号最长20位',
        'ProductSKU.require'      => '产品SKU必须',
        'ProductSKU.unique'       => '产品SKU重复',
        'ProductSKU.min'          => '产品SKU最短5位',
        'ProductSKU.max'          => '产品SKU最长20位',
        'ProductName.require'     => '产品名必须',
        'ProductName.unique'      => '产品名重复',
        'ProductName.min'         => '产品名最短5位',
        'ProductName.max'         => '产品名最长20位',

        'CategoryNo.require'     => '分类编号必须',
        'CategoryNo.unique'      => '分类编号重复',
        'CategoryNo.min'         => '分类编号最短5位',
        'CategoryNo.max'         => '分类编号最长20位',
        'CategoryName.require'     => '分类名必须',
        'CategoryName.unique'      => '分类名重复',
        'CategoryName.min'         => '分类名最短5位',
        'CategoryName.max'         => '分类名最长20位',
        'HSNameEnglish.min'         => '海关英文名最短5位',
        'HSNameEnglish.max'         => '海关英文名最长40位',
        'HSNameEnglish.unique'      => '海关英文名重复',
        'HSNameChina.min'         => '海关中文名最短5位',
        'HSNameChina.max'         => '海关中文名最长40位',
        'HSNameChina.unique'      => '海关中文名重复',

        'TrademarkNo.require'     => '品牌编号必须',
        'TrademarkNo.unique'      => '品牌编号重复',
        'TrademarkNo.min'         => '品牌编号最短5位',
        'TrademarkNo.max'         => '品牌编号最长20位',
        'ChinaName.require'     => '品牌中文名必须',
        'ChinaName.unique'      => '品牌中文名重复',
        'ChinaName.min'         => '品牌中文名最短5位',
        'ChinaName.max'         => '品牌中文名最长40位',
        'EnglishName.require'     => '品牌英文名必须',
        'EnglishName.unique'      => '品牌英文名重复',
        'EnglishName.min'         => '品牌英文名最短5位',
        'EnglishName.max'         => '品牌英文名最长40位',

        'InnerSKU.require'     => '产品内部SKU必须',
        'InnerSKU.unique'      => '产品内部SKU重复',
        'InnerSKU.min'         => '产品内部SKU最短5位',
        'InnerSKU.max'         => '产品内部SKU最长20位',
        'OuterSKU.require'     => '产品外部SKU必须',
        'OuterSKU.unique'      => '产品外部SKU重复',
        'OuterSKU.min'         => '产品外部SKU最短5位',
        'OuterSKU.max'         => '产品外部SKU最长20位',

        'Warehouse.require'     => '仓库名必须',
        'Warehouse.unique'      => '仓库名重复',
        'Warehouse.min'         => '仓库名最短5位',
        'Warehouse.max'         => '仓库名最长40位',
        'WarehouseSku.require'     => '仓库SKU必须',
        'WarehouseSku.unique'      => '仓库SKU重复',
        'WarehouseSku.min'         => '仓库SKU最短5位',
        'WarehouseSku.max'         => '仓库SKU最长20位',
    ];

    protected $scene = [
        'productadd' => ['ProductNo', 'ProductSKU', 'ProductName'],
        'productedit'  => ['ProductNo', 'ProductSKU', 'ProductName'],

        'categoryadd' => ['CategoryNo', 'CategoryName', 'HSNameEnglish', 'HSNameChnia'],
        'categoryedit'  => ['CategoryNo', 'CategoryName', 'HSNameEnglish', 'HSNameChnia'],


    ];
}
