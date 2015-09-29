<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Api extends CI_Controller {

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
		exit('index11'.date("Y-m-d h:m:s"));
	}

	function _mymail($info){		
		$config['protocol']="smtp";
//		$config['smtp_host'] = 'smtp.qq.com';
//		$config['smtp_user'] = '1840223551@qq.com';
//		$config['smtp_pass'] = '07318676881';

		$config['smtp_host'] = 'smtp.163.com';
		$config['smtp_user'] = 'vod_test@163.com';
		$config['smtp_pass'] = 'abcdefg';


//		$config['crlf']="\r\n";   	//这两行针对qq邮箱的，其它的好像不用
//		$config['newline']="\r\n";

		$this->load->library('email');
		$this->email->initialize($config);       

		//以下设置Email内容  
		$this->email->from($config['smtp_user'], $config['smtp_user']);  
		$this->email->to('whittmy@163.com');  
		$this->email->subject('山东源采集报告');  
		$this->email->message($info);  
		//    $this->email->attach('application\controllers\1.jpeg');           //相对于index.php的路径  

		$this->email->send();    
		//echo $this->email->print_debugger();        //返回包含邮件内容的字符串，包括EMAIL头和EMAIL正文。用于调试。  
	}


	function _getMillisecond() {
		list($s1, $s2) = explode(' ', microtime());		
		return (float)sprintf('%.0f', (floatval($s1) + floatval($s2)) * 1000);	
		//return '1385971779270';
	}



	function _getUrlContent($url, $header, $cookiefile=null){
		
		$proxy = 'http://61.55.141.11';
		$port = '81';

		$curl = curl_init();  // 初始化一个 cURL 对象
		curl_setopt($curl, CURLOPT_URL, $url);  // 设置你需要抓取的URL  
		curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
		curl_setopt($curl, CURLOPT_HEADER, 0);
		curl_setopt($curl, CURLOPT_PROXY, $proxy);
		curl_setopt($curl, CURLOPT_PROXYPORT,$port);
		curl_setopt($curl, CURLOPT_TIMEOUT,30);
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION , true);    //重定向问题
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);  // 设置cURL 参数，要求结果保存到字符串中还是输出到屏幕上。
		if($cookiefile!=null && strlen($cookiefile)>0){
			curl_setopt($curl, CURLOPT_COOKIEJAR, $this->m_curDir.'shandong.cookie');
			curl_setopt($curl, CURLOPT_COOKIEFILE, $this->m_curDir.'shandong.cookie');
		}
		$rtdata = curl_exec($curl);  // 运行cURL，请求网页
		curl_close($curl);  // 关闭URL请求      
		return $rtdata;
	}

	//######################
	private	$m_curDir = '/home/wwwroot/default/frw/application/Moon_Live/cache/';
	//############################33
	function shandong_auto($arg=null){
		$header = array();
		$header[] = 'Accept:*/*';
		//	$header[] = 'Accept-Encoding: gzip,deflate,sdch';
		$header[] = 'Accept-Language: zh-CN,zh;q=0.8';
		$header[] = 'Connection: keep-alive';
		$header[] = 'Host: www.wolidou.com';
		$header[] = 'Referer: http://www.wolidou.com/tvp/360/play360_0_0.html';
		$header[] = 'User-Agent: Mozilla/5.0 (X11; Linux i686) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/30.0.1599.66 Safari/537.36';
		$header[] = 'X-Requested-With: XMLHttpRequest';
		$url = 'http://www.wolidou.com/s/key.php?f=k&t='.$this->_getMillisecond();
		//echo $url.'<br>';
		$this->_getUrlContent($url, $header,'shandong.cookie');


		$header1 = array();
		$header1[] = 'Host: www.wolidou.com';
		$header1[] = 'Accept:application/json, text/javascript, */*; q=0.01';
		//	$header1[] = 'Accept-Encoding: gzip,deflate,sdch';
		$header1[] = 'Accept-Language: zh-CN,zh;q=0.8';
		$header1[] = 'Referer: http://www.wolidou.com/tvp/360/play360_0_0.html';
		$header1[] = 'User-Agent: Mozilla/5.0 (X11; Linux i686) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/30.0.1599.66 Safari/537.36';
		$header1[] = 'Connection: keep-alive';
		$header1[] = 'X-Requested-With: XMLHttpRequest';
		$url = 'http://www.wolidou.com/s/sdsj.php?u=sdql/playlist.m3u8&ts='.$this->_getMillisecond();
		//echo $url.'<br>';
		$res = $this->_getUrlContent($url, $header1,'shandong.cookie');
		//exit($res);
		preg_match_all('/\?([^"]+)/', $res, $idTmp);
		$id = null;
		if(isset($idTmp[1][0]))
			$id = $idTmp[1][0];
	//	exit('id:'.$id);
		$code = null;
		$strexe = null;

	//	exit($id);

		if($arg !=null && strlen($arg)==strlen('deeddff1fd6f9903eac2e5873a02a990')){
			$id = $arg;
		}
		if($id==null || strlen($id)!= strlen('deeddff1fd6f9903eac2e5873a02a990')){
			$code = null;
		}
		else{
			echo 'id_:'.$id;
			$strexe = 'sed -i "s/m3u8?.*/m3u8?'.$id.'\';/g" /home/wwwroot/default/vpser/liveParser_tmp/shandong.php';
			system($strexe, $code);
		}
		
		$msg = '';
		if($code == 0 ){
			$msg = 'successfull';	
		}
		else{
			$msg = 'failed';
		}
		$this->_mymail($msg.' '.$strexe);

		//echo $msg;
		//$this->_mymail($msg.' '.$strexe);

	}


	function korean($ch=null){
		if(!isset($ch) || empty($ch))
			exit('http://www.baidu.com/404.flv');

		$tm = 10;
		$this->load->library('MP_Cache');
		$this->mp_cache->delete($ch);	
		$playurl = $this->mp_cache->get($ch); 
		

		if($playurl != null && strcmp(substr($playurl,0,strlen('rtmp://')),'rtmp://')==0 ){
			exit($playurl);
		}	

		$playurl = 'http://www.baidu.com/a.flv';



		if($ch == 'kbs1'){
			$header1 = array();
			$header1[] = 'Host: wolidou.gotoip3.com';
			$header1[] = 'Accept:text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8';
			//	$header1[] = 'Accept-Encoding: gzip,deflate,sdch';
			$header1[] = 'Accept-Language: zh-CN,zh;q=0.8';
			$header1[] = 'Referer: http://www.wolidou.com/tvp/1181/play1181_0_0.html';
			$header1[] = 'User-Agent: Mozilla/5.0 (X11; Linux i686) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/30.0.1599.66 Safari/537.36';
			//$header1[] = 'Connection: keep-alive';
			$url = 'http://wolidou.gotoip3.com/kbs.php?json_wolidou&u=kbs1';
			//echo $url.'<br>';
			$res = $this->_getUrlContent($url, $header1,null);
			//echo $res;
			//echo '<br><br><br>';
			preg_match_all('/\:\'(rtmp[^\']+)/i', $res, $urlTmp);
			//print_r($urlTmp);

			$url = null;
			if(isset($urlTmp[1][0])){
				$url = urldecode($urlTmp[1][0]);
				if(strcmp(substr($url,0,strlen('rtmp://')),'rtmp://')==0 && strlen($url)>8){
					$this->mp_cache->write($url, $ch, $tm);	
					$playurl = $url;
				}
			}
		}
		else if($ch == 'kbs2'){
			$header1 = array();
			$header1[] = 'Host: wolidou.gotoip3.com';
			$header1[] = 'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8';
			//	$header1[] = 'Accept-Encoding: gzip,deflate,sdch';
			$header1[] = 'Accept-Language: zh-CN,zh;q=0.8';
			$header1[] = 'Referer: http://www.wolidou.com/tvp/1181/play1181_0_1.html';
			$header1[] = 'User-Agent: Mozilla/5.0 (X11; Linux i686) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/30.0.1599.66 Safari/537.36';
			//$header1[] = 'Connection: keep-alive';
			$url = 'http://wolidou.gotoip3.com/kbs.php?json_wolidou&u=kbs2';
			//echo $url.'<br>';
			$res = $this->_getUrlContent($url, $header1, null);
			//exit($res);
			preg_match_all('/\:\'(rtmp[^\']+)/i', $res, $urlTmp);

			$url = null;
			if(isset($urlTmp[1][0])){
				$url = urldecode($urlTmp[1][0]);
				if(strcmp(substr($url,0,strlen('rtmp://')),'rtmp://')==0 && strlen($url)>8){
					$this->mp_cache->write($url, $ch, $tm);	
					$playurl = $url;
				}
			}
		}
		else if($ch == 'sbs'){
			$header1 = array();
			$header1[] = 'Host: wolidou.gotoip3.com';
			$header1[] = 'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8';
			//	$header1[] = 'Accept-Encoding: gzip,deflate,sdch';
			$header1[] = 'Accept-Language: zh-CN,zh;q=0.8';
			$header1[] = 'Referer: http://www.wolidou.com/tvp/1177/play1177_0_0.html';
			$header1[] = 'User-Agent: Mozilla/5.0 (X11; Linux i686) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/30.0.1599.66 Safari/537.36';
			//$header1[] = 'Connection: keep-alive';
			$url = 'http://wolidou.gotoip3.com/sbs.php?u=sbshd';
			$res = $this->_getUrlContent($url, $header1, null);
			preg_match_all('/file\=([^\&]+)\&[^\=]+\=([^\&]+)/i', $res, $urlTmp);
			//print_r($urlTmp[1]);
			$url = 'http://www.baidu.com/a.flv';
			//echo '<br>'.$urlTmp[1][0].'<br>';
			//echo  '<br>'.$urlTmp[2][0].'<br>';
			if(isset($urlTmp[1][0]) && isset($urlTmp[2][0])){
				$url = urldecode(trim($urlTmp[2][0]).'/'.trim($urlTmp[1][0]));
				if(strcmp(substr($url,0,strlen('rtmp://')),'rtmp://')==0 && strlen($url)>8){
					$this->mp_cache->write($url, $ch, $tm);	
					$playurl = $url;
				}
			}
		}

		exit($playurl);
	}

}

/* End of file api.php */
/* Location: ./controllers/self_src/api.php */
