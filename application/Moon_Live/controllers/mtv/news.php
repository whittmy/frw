<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class News extends CI_Controller {
	function index(){
		exit("xxxxxxxxxxx");
	}

	function loc(){
		$out = array(
			'api' => 'http://int.dpool.sina.com.cn/iplookup/iplookup.php?format=js',
			'regx' => '"province"\:"([^"]+)"',
		);
		exit(json_encode($out));
	}

	function get($region=null){
		$rgsql = null;

		//exit('xssss');

		if($region!=null && strlen($region)>0){
			$area =	urldecode($region); 
			$rgsql = '  or region=\''.$area.'\'';			
		}

		$this->load->database('moon_live_test');	
	
		$sql = 'SELECT msg  FROM  notice where region=\'all\''. $rgsql .' order by sort';
		log_message('error', $sql);
		$query = $this->db->query($sql);

		$news = array();
		foreach($query->result() as $row){
			$news[] = $row->msg;	
		}
		$query->free_result();
		$this->db->close();		

	
		$out = array(
			'news' => $news
		);
		$outstr = json_encode($out);
		exit($outstr);
	}

}

/* End of file news.php */
/* Location: ./controllers/news.php */
