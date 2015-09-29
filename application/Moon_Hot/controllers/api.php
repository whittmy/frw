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
	 
	function get_mvurl($vid=null){
		if($vid==null || !is_numeric($vid)){
			log_message('error', 'invalid request!!');
			exit('{}');
		}
		$this->load->database('video_news');
		$sql = 'select title, url from tbl_playurl where vid='.$vid;
		//exit($sql);
		$query = $this->db->query($sql);
		$data['info'] = array($vid,$query);
		$this->db->close();
		
		$this->load->view('get_mvurl_view', $data);
	}	
	 
	function get_mvlist($_date=0, $mac=null){
		//$this->output->cache(1);
		
		if(!is_numeric($_date) /*|| empty($mac)*/){
			log_message('error', 'invalid request!!');
			exit('{}');		
		}
		
		// $macArr = str_split($mac,3);
		// if(count($macArr) != 6){
			// log_message('error', 'mac format invalid !!');
			// exit('{}');			
		// }
		
		if(empty($_date)){
			//取当前月
			$_date = date('Y').sprintf("%02d", date('m'));;
		}
		
		//限制201307
		// if($_date == "201307")
			// $_date = "201306";
		
		
		$year = substr($_date, 0, 4);
		$month = substr($_date,4,2);

		$pdate = $ndate = null;
		if($month == 1){
			$pdate = ($year-1).'12';
			$ndate =  $year.'02';				
		}
		else if($month == 12){
			$pdate = $year.'11';
			$ndate = ($year+1).'01';			
		}
		else{
			$pdate = $year.sprintf("%02d", ($month-1));
			$ndate = $year.sprintf("%02d", ($month+1));
		}
		
		//限制  201304之前
		if($pdate == "201303")
			$pdate = "000000";
		// if($ndate == "201307")
			// $ndate = "000000";
		
		
		$this->load->database('video_news');
		$sql = 'select id from r_year_vid where year_id='.$pdate.' limit 1';
		$query = $this->db->query($sql);
		if($query->num_rows() == 0){
			$pdate = 0;
		}
		$query->free_result();
		
		$sql = 'select id from r_year_vid where year_id='.$ndate.' limit 1';
		$query = $this->db->query($sql);
		if($query->num_rows() == 0){
			$ndate = 0;
		}
		$query->free_result();
		
		$sql = 'select t1.id,t1.title,t1.img,t1.haveurl from tbl_info t1, r_year_vid t2 where t2.year_id='.$_date.' and t1.id=t2.vid order by t1.vsort';
		$query = $this->db->query($sql);
		$data['query']  = $query;

		$this->db->close();

		$data['pdate'] = $pdate;
		$data['ndate'] = $ndate;
		$data['cdate'] = $_date;
		$this->load->view('get_mvlist_view', $data);
	}
	function _is_num($var)
	{
		for ($i=0;$i<strlen($var);$i++)
		{
			$ascii_code=ord($var[$i]);
			
			if ($ascii_code >=49 && $ascii_code <=57)
				continue;
			else 
				return false;
		}
		
			return true;
	}	
	function get_mvInfo($vid=null){
		if($vid==null || !is_numeric($vid)){
			log_message('error', 'invalid request!!');
			exit('{}');
		}
		//$this->output->cache(1);
		
		$this->load->database('video_news');	
		$sql = 'select id,title,img,showdate,director,actor,type,area,intro from tbl_info where id='.$vid;
		$query = $this->db->query($sql);
		$data['query'] = $query;
		$this->db->close();
		
		$this->load->view('get_mvinfo_view', $data);
	}

}

/* End of file api.php */
/* Location: ./controllers/api.php */