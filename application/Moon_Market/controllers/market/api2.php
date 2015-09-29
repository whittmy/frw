<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Api2 extends CI_Controller {

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
		$query = $this->db->query("select ver, url,intro from market_cfg_lv where vercode>'$curVer' order by ver desc limit 1");
		$data['query'] = $query;
		$this->load->view('upgrade_view', $data);
	}

	function applist_top($mac,$cata,$pgsize,$pageno){
		$this->output->cache(60*5);
		$this->load->database();
		
		$sql = "select title,package,bupgrade,size,ver,icon,dl_url,intro from basic_info_lv where bshow=1"; //还有其它参数待处理
		
		$query = $this->db->query($sql);
		$data['query'] = $query;
		$this->load->view('applist_view', $data);
	}

	function update_check_ali($mac, $curVer){
		$this->output->cache(60*1);
		$this->load->database();
		$query = $this->db->query("select ver, url,intro from market_cfg_ali where vercode>'$curVer' order by ver desc limit 1");
		$data['query'] = $query;
		$this->load->view('upgrade_view', $data);
	}

	function applist_ali($pgsize,$pageno){
		//$this->output->cache(60*5);
		//---------------
		$datas =$this->input->post('sysInfo');
		log_message('error','get post data:'.$datas);
		$obj = json_decode($datas);
		if($obj == null){
			log_message('error', 'post data error!!');
			//return some data
			exit;
		}
		$mac = isset($obj->{'mac'}) ? $obj->{'mac'} : null;
		log_message('error', 'mac='.$mac.',pgsize='.$pgsize.',pageno='.$pageno);
		//-----------
		
		$this->load->database();
		
		$sql = "select title,package,bupgrade,size,ver,icon,dl_url,intro from basic_info_ali where bshow=1 order by isort"; //还有其它参数待处理
		
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

	function applist($pgsize,$pageno){
		//$this->output->cache(60*5);
		//---------------
		$datas =$this->input->post('sysInfo');
		log_message('error','get post data:'.$datas);
		$obj = json_decode($datas);
		if($obj == null){
			log_message('error', 'post data error!!');
			//return some data
			exit;
		}
		$mac = isset($obj->{'mac'}) ? $obj->{'mac'} : null;
		log_message('error', 'mac='.$mac.',pgsize='.$pgsize.',pageno='.$pageno);
		//-----------
		
		$this->load->database();
		
		$sql = "select title,package,bupgrade,size,ver,icon,dl_url,intro from basic_info where bshow=1 order by isort"; //还有其它参数待处理
		
		$query = $this->db->query($sql);
		$data['query'] = $query;
		$this->load->view('applist_view', $data);
	}

}

/* End of file api.php */
/* Location: ./controllers/api.php */
