<?php
/**
 * Google短网址(goo.gl)服务类
 *
 * Copyright (c) 2010 Jessica(董立强)
 * Licensed under the MIT license.
 *
 * @author Jessica<jessica.dlq@gmail.com>
 * @link http://www.skiyo.cn
 * @example
 *			$g = new GoogleShorter();
 *			echo $g->getURL('http://www.skiyo.cn/');
 *
 */
include_once('GoogleAuthToken.class.php');
class GoogleShorter {

	/**
	 * URL to shorten
	 *
	 * @var sttring
	 */
	protected $url;

	/**
	 * You must have the cURL functions
	 */
	public function  __construct() {
		if(!function_exists('curl_init')){
			throw new Exception('cURL functions are not available.');
		}
	}

	/**
	 * start the request
	 * 
	 * @return array
	 */
	protected function request() {
		$a = new GoogleAuthToken();
		//似乎goo.gl屏蔽了其他浏览器的请求.我用其他浏览器请求都失败了 所以得设置一个chrome的useragent
		$agent = 'Mozilla/5.0 (Windows; U; Windows NT 6.1; en-US) AppleWebKit/532.5 (KHTML, like Gecko) Chrome/4.0.249.89 Safari/532.5';
		$url = 'http://goo.gl/api/url?user=toolbar@google.com&url=' . urlencode($this->url) . '&auth_token=' . $a->getAuthToken($this->url);
		//由于snoopy开启的第一步就是fopen.但是由于不是post方式 所以google直接返回405.那我们只好用cURL了.
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_TIMEOUT, 25);
		curl_setopt($ch, CURLOPT_USERAGENT, $agent);
		return json_decode(curl_exec($ch), true);
	}

	/**
	 * get the URL
	 * 
	 * @param string $url
	 * @return string
	 */
	public function getURL($url) {
		$this->url = $url;
		$result = $this->request();
		return isset($result['short_url']) ? $result['short_url'] : null;
	}
}