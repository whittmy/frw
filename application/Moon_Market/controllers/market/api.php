<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Api extends CI_Controller {

	/**
	 * Index Page for this controller.
	 *
	 * Maps to the following URL
	 * 		http://example.com/index.php/geturl
	 *	- or -  
	 * 		http://example.com/index.php/geturl/index
	 *	- or -
	 * Since this controller is set as the default controller in 
	 * config/routes.php, it's displayed at http://example.com/
	 *
	 * So any other public methods not prefixed with an underscore will
	 * map to /index.php//<method_name>
	 * @see http://codeigniter.com/user_guide/general/urls.html
	 */
	function update_check_lv($mac, $curVer){
		$this->output->cache(60*1);
		$this->load->database();
		$query = $this->db->query("select ver, url,intro from market_cfg_lv where vercode>'$curVer' order by ver desc limit 1");
		$data['query'] = $query;
		$this->load->view('upgrade_view', $data);
	}

	function applist_lv($mac,$cata,$pgsize,$pageno){
		$this->output->cache(60*5);
		$this->load->database();
		
		$sql = "select title,package,bupgrade,size,ver,icon,dl_url,intro from basic_info_lv where bshow=1"; //还有其它参数待处理
		
		$query = $this->db->query($sql);
		$data['query'] = $query;
		$this->load->view('applist_view', $data);
	}

	function update_check_top($mac, $curVer){
		$this->output->cache(60*1);
		$this->load->database();
		$query = $this->db->query("select ver, url,intro from market_cfg_top where vercode>'$curVer' order by ver desc limit 1");
		$data['query'] = $query;
		$this->load->view('upgrade_view', $data);
	}

	function applist_top($mac,$cata,$pgsize,$pageno){
		$this->output->cache(60*5);
		$this->load->database();
		
		$sql = "select title,package,bupgrade,size,ver,icon,dl_url,intro from basic_info_top where bshow=1 order by isort"; //还有其它参数待处理
		
		$query = $this->db->query($sql);
		$data['query'] = $query;
		$this->load->view('applist_view', $data);
	}



	function update_check($mac, $curVer){
		$this->output->cache(60*1);
		$this->load->database();
		$query = $this->db->query("select ver, url,intro from market_cfg where vercode>'$curVer' order by ver desc limit 1");
		$data['query'] = $query;
		$this->load->view('upgrade_view', $data);
	}

	function applist($mac,$cata,$pgsize,$pageno){
		$this->output->cache(60*5);
		$this->load->database();
		
		$sql = "select title,package,bupgrade,size,ver,icon,dl_url,intro from basic_info where bshow=1 order by isort"; //还有其它参数待处理
		
		$query = $this->db->query($sql);
		$data['query'] = $query;
		$this->load->view('applist_view', $data);
	}

	function _getUrlContent($url){
		//$header[]='Accept-Encoding: gzip, deflate';
		$curl = curl_init();  // 初始化一个 cURL 对象
		curl_setopt($curl, CURLOPT_URL, $url);  // 设置你需要抓取的URL  
		//curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
		curl_setopt($curl, CURLOPT_HEADER, 0);
		curl_setopt($curl, CURLOPT_TIMEOUT,30);
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION , true);    //重定向问题
		//curl_setopt($curl, CURLOPT_ENCODING , 'gzip, deflate');
		curl_setopt($curl, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 5.1; Trident/4.0; .NET4.0C; .NET4.0E; .NET CLR 2.0.50727; .NET CLR 3.0.4506.2152; .NET CLR 3.5.30729)");  
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);  // 设置cURL 参数，要求结果保存到字符串中还是输出到屏幕上。

		$rtdata = curl_exec($curl);  // 运行cURL，请求网页
		curl_close($curl);  // 关闭URL请求      
		return $rtdata;
	}	



	function dupanfile($enurl){
		$url = urldecode($enurl);
		$bcache = true;

		$this->load->library('MP_Cache');
		$enurl =  urlencode($url); 
		//#exit($url);
		$data1 = $this->mp_cache->get($enurl);	
		//exit($data1);
		if($data1 && strlen($data1)<20){
			$this->mp_cache->delete($enurl);
			$data1 = false;
		}
		if ($data1 === false){
			$con = $this->_getUrlContent($url);
			if(empty($con))
				$bcache = false;
			//exit($con);	
			preg_match_all('/_dlink=\"(http:\/\/[^\"]+sh\=1[^\"]+)\"/iU', $con, $dlTmp);	
			if(!isset($dlTmp[1][0]) || empty($dlTmp[1][0]))
				$bcache = false;
			//print_r($dlTmp);
			//exit;
			$data1 = stripslashes(stripslashes($dlTmp[1][0]));			
			$this->mp_cache->write($data1, $enurl, 25200);	//$this->mp_cache->write($data, 'example', 7200);  
			
			if(!$bcache)
				$this->mp_cache->delete($enurl);
		}
		$data['url'] = $data1;
		$this->load->view('dload_view', $data);	
	}	
}

/* End of file api.php */
/* Location: ./controllers/api.php */
