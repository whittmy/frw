<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Proxy  extends CI_Controller {
	function index(){
		exit('index');
	}

	function img($url=null){
//		log_message('error', 'img_arg'.$url);
//		return;
		if($url	!= null){
			$url = urldecode($url);
			log_message('error', 'img:'.$url);
			$url = 'http://baidu.com/1.jpg';
			header('Location: '.$url);
		}

	}

	function url($url=null){
		if($url != null){
//			$url = urldecode($url);
			log_message('error', 'url:'.$url);
//			header('Location: '.$url);
		}
	}

}

/* End of file shandong.php */
/* Location: ./controllers/shandong.php */
