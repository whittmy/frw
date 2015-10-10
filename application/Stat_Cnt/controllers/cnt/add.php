<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Add extends CI_Controller {

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
	
	
	function mac($mac=null){
		if($mac==null || strlen($mac)<1)
			exit('1');
		
		$arr = str_split($mac,6);
		$mac1 = base_convert($arr[0], 16,10);
		$mac2 = base_convert($arr[1], 16,10);

		$this->load->database();
		$this->load->library('MP_Cache');
		$sql = 'select enabled from launcher1_mac_cnt where lm1='.$mac1.' and lm2='.$mac2.' limit 1';
		$query = $this->db->query($sql);
		
		$enabled = null;
		foreach($query->result() as $row){
			$enabled = $row->enabled;
			break;
		}	
		if($enabled == null){
			$sql = 'insert into launcher1_mac_cnt (lm1,lm2) values ('.$mac1.','.$mac2.')';
			$this->db->query($sql);
			$enabled = 1;
		}
		
		$this->db->close();
		exit($enabled.",15");	// ",xx", is the try cnt
	}
}

/* End of file shandong.php */
/* Location: ./controllers/shandong.php */