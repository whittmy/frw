<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class ShanDong extends CI_Controller {

	/**
	 * Index Page for this controller.
	 *
	 * Maps to the following URL
	 * 		http://example.com/index.php/api
	 *	- or -  
	 * 		http://example.com/index.php/api/index
	 *	- or -
	 * Since this controller is set as the default controller in 
	 * config/routes.php, it's displayed at http://example.com/
	 *
	 * So any other public methods not prefixed with an underscore will
	 * map to /index.php/set/<method_name>
	 * @see http://codeigniter.com/user_guide/general/urls.html
	 */
	function index(){
		exit('index');
	}
	
	function _getMillisecond() {
		/*
		list($s1, $s2) = explode(' ', microtime());
		echo 's1:'.((floatval($s1) + floatval($s2))* 1000).'<br>';
		return (float)sprintf('%.0f', (floatval($s1) + floatval($s2)) * 1000);
		*/
		return time().'000';
	}
   
	function _getUrlContent($url, $header, $cookiefile=null){
		global $g_curDir;
		$curl = curl_init();  // 初始化一个 cURL 对象
		curl_setopt($curl, CURLOPT_URL, $url);  // 设置你需要抓取的URL  
		curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
		curl_setopt($curl, CURLOPT_HEADER, 0);
		curl_setopt($curl, CURLOPT_TIMEOUT,30);
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION , true);    //重定向问题
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);  // 设置cURL 参数，要求结果保存到字符串中还是输出到屏幕上。
		if($cookiefile!=null && strlen($cookiefile)>0){
				curl_setopt($curl, CURLOPT_COOKIEJAR, $cookiefile);
				curl_setopt($curl, CURLOPT_COOKIEFILE, $cookiefile);
		}
		$rtdata = curl_exec($curl);  // 运行cURL，请求网页
		curl_close($curl);  // 关闭URL请求      
		return $rtdata;
	}
	 
	function _regKey($rfurl='http://www.wolidou.com/tvp/360/play360_0_0.html'){
		$header = array();
		$header[] = 'Accept:application/json, text/javascript, */*; q=0.01';
		$header[] = 'Accept-Language: zh-CN,zh;q=0.8';
		$header[] = 'Connection: keep-alive';
		$header[] = 'Host: www.wolidou.com';
		$header[] = 'Referer: '.$rfurl;
		$header[] = 'User-Agent: Mozilla/5.0 (X11; Linux i686) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/30.0.1599.66 Safari/537.36';
		$header[] = 'X-Requested-With: XMLHttpRequest';
		$url = 'http://www.wolidou.com/s/key.php?f=k&t='.$this->_getMillisecond();
		$this->_getUrlContent($url, $header, $this->mCookiePath);	
	}
	
	function _jy(){
		$header = array();
		$header[] = 'Host: www.wolidou.com';
		$header[] = 'Accept:application/json, text/javascript, */*; q=0.01';
		$header[] = 'Accept-Language: zh-CN,zh;q=0.8';
		$header[] = 'Referer: http://www.wolidou.com/tvp/298/play298_2_0.html';
		$header[] = 'User-Agent: Mozilla/5.0 (X11; Linux i686) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/30.0.1599.66 Safari/537.36';
		$header[] = 'Connection: keep-alive';
		$header[] = 'X-Requested-With: XMLHttpRequest';
		$url = 'http://www.wolidou.com/s/dxcctv.php?u=sdetv&m3u8&json_wolidou&ts='.$this->_getMillisecond();
		return $this->_getUrlContent($url, $header, $this->mCookiePath);	
		
	}
	
	function _usual(){
		//echo 'cookie path:'.$this->mCookiePath.'<br><br>';
		$header = array();
		$header[] = 'Host: www.wolidou.com';
		$header[] = 'Accept:application/json, text/javascript, *\/*; q=0.01';
		$header[] = 'Accept-Language: zh-CN,zh;q=0.8';
		$header[] = 'Referer: http://www.wolidou.com/tvp/360/play360_0_0.html';
		$header[] = 'User-Agent: Mozilla/5.0 (X11; Linux i686) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/30.0.1599.66 Safari/537.36';
		$header[] = 'Connection: keep-alive';
		$header[] = 'X-Requested-With: XMLHttpRequest';
		$url = 'http://www.wolidou.com/s/sdsj.php?u=sdql/playlist.m3u8&ts='.$this->_getMillisecond();
		return $this->_getUrlContent($url, $header, $this->mCookiePath);	
	}
	
	private $mCookiePath; 
	
	//sdql,sdys,sdzy,sdsh,sdnk,sdgg,sdse,sdgj
	function channel($ch){
		if($ch==null || empty($ch)){
			exit;
		}
			
		$this->config->load('config');
		$this->load->library('MP_Cache');
		//$this->mCookiePath = $this->config->item('mp_cache_dir').'cookies/shandong.cookie';
		$this->mCookiePath = 'D:/WorkSpace/www_root/ci/application/DL_Proxy/mp_cache/cookies/shandong.cookie';
		$key = null;
		if($ch == 'sdjy'){
			$key= $this->mp_cache->get('key_jy'); 
			if ($key === false) {
				$this->_regKey();
				$str = $this->_jy();
				preg_match_all('/\"(http[^\"]+)/', $str, $urlTmp);
				if(isset($urlTmp[1][0]) && strlen($urlTmp[1][0])>4){
					$key = $urlTmp[1][0];
					$this->mp_cache->write($key, 'key_jy', 5); 
				}
			}
		}
		else{
			$key= $this->mp_cache->get('key'); 
			if ($key === false) {
				$this->_regKey();
				
				$str = $this->_usual();
				preg_match_all('/\"(http[^\"]+)/', $str, $urlTmp);
				if(isset($urlTmp[1][0]) && strlen($urlTmp[1][0])>4){
					$key = $urlTmp[1][0];
					$this->mp_cache->write($key, 'key', 5); 
				}
			}		
		}
		

		
		$url = preg_replace('/live\/[^\/]+\/play/', 'live/'.$ch.'/play', $key);
		if(!empty($url)){
			header('Location: '. $url);
			//echo $url;
		}
	}
}

/* End of file shandong.php */
/* Location: ./controllers/shandong.php */