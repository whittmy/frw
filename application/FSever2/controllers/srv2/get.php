<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Get extends CI_Controller {
	// http://www.nybgjd.com/srv2/get/index
	function index(){
		echo 'index';
	}

	
	function img($belong=null, $path){
		$base = '';
		if($belong == 'vrgame'){
		  $base = 'res/';
		}
		else if($belong == 'vrres'){
		  $base = 'res/images/';
		}
		else{
		  exit('');
		}

		$this->load->library('QiNiuMgr');
		$ret = 	$this->qiniumgr->getpvrUrl($base.urldecode($path));
		//echo $ret;
		header('Location: '.$ret);	
	}

}

/* End of file shandong.php */
/* Location: ./controllers/shandong.php */
