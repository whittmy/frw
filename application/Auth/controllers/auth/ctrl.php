<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Ctrl extends CI_Controller {

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
	
	function stat_uid($try=1){
		$this->load->database();
		if($try == 1)
			$str = ' where uid<1000000';
		else
			$str = ' where uid>1000000 order by tm_firstlogin desc';
		$sql = 'SELECT uid,tm_firstlogin,(exp_date-TIMESTAMPDIFF(DAY, tm_firstlogin, now())) remaindays, exp_date, bpause, utype FROM __feelive_user_info '.$str;
		$query = $this->db->query($sql);

		$html = '<head>
			<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />';
		$html .= '<body>';
		$html .= '<h1>'. (($try==1)?'测试帐号':'正式帐号'). '</h1>';		
		$html .= '<table border="1" align="center" width="100%">';
		$html .= '<tr  align="center"><td>编号</td><td>ID</td><td>激活时间</td><td>剩余天数</td><td>有效期</td><td>套餐</td></tr>';
		$cnt = 1;
		foreach($query->result() as $row){
			$str = '<tr align="center">';
			$str .= '<td>'.$cnt.'</td>';
			$str .= ('<td>'.$row->uid.'</td>');
			$str .= ('<td>'.$row->tm_firstlogin.'</td>');
			$str .= ('<td>'.$row->remaindays.'</td>');
			$str .= ('<td>'.$row->exp_date.'</td>');
			$str .= ('<td>'.$row->utype.'</td>');
			$str .= '</tr>';

			$html .= $str;
			$cnt ++;
		}

		$html .= '</table></body>';
		echo $html;


	}
	
	// $idx base >=1
	/*
	function geturl($chid=null, $idx=null){
		if($chid==null || strlen($chid)<1
			|| $idx==null || strlen($idx)<1){
			exit;
		}

		$key = $chid.'-'.$idx;
		
		$this->load->library('MP_Cache');
		$url= $this->mp_cache->get($key); 
		if ($url === false) {
			$this->load->database();
			
			$sql = 'select url from p2plive_38c_urls where ch_id='.$chid.' and idx='.$idx.' limit 1';
			$query = $this->db->query($sql);
			foreach($query->result() as $row){
				$url = $row->url;
				break;
			}
			$query->free_result();	
			$this->db->close();
			
			$this->mp_cache->write($url, $key, 360); 
		}
		exit($url);
	}
	*/

}

/* End of file shandong.php */
/* Location: ./controllers/shandong.php */
