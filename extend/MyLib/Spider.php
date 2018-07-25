<?php

// namespace Think;
namespace MyLib;

//爬虫类-获取网页信息
class Spider {
 
 	//将网页上面的图片，下载到本地
	public function downloadImage($url, $path='images/'){
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
		$file = curl_exec($ch);
		curl_close($ch);

		$this->saveAsImage($url, $file, $path);
	}

	private function saveAsImage($url, $file, $path){
		echo $url, '<hr/>';
		echo $file, '<hr/>';
		echo $path;
		// die('AA');
		$filename = pathinfo($url, PATHINFO_BASENAME);
		$resource = fopen($path . $filename, 'a');
		fwrite($resource, $file);
		fclose($resource);
	}
}


$images = [
	// 'https://images-cn.ssl-images-amazon.com/images/I/41mYcAHFVSL._AA200_.jpg',
	'images-cn.ssl-images-amazon.com/images/I/413qSnW2FXL._AA200_.jpg',
	'www.baidu.com/img/bd_logo1.png'
];

// $spider = new Spider();
 
// foreach ( $images as $url ) {
//   $spider->downloadImage($url);
// }