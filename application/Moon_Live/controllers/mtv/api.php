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
		exit('index');
	}


      	function update_check($mac, $curVer){
                //$this->output->cache(60*5);
                $this->load->database('moon_live_test');
                $query = $this->db->query("select ver, url,intro from live_cfg where vercode>'$curVer' order by ver desc limit 1");
                $data['query'] = $query;
                $this->load->view('upgrade_view', $data);
        }

      	function update_check_m20($mac, $curVer){
                //$this->output->cache(60*5);
                $this->load->database('moon_live_test');
                $query = $this->db->query("select ver, url,intro from live_cfg_m20 where vercode>'$curVer' order by ver desc limit 1");
                $data['query'] = $query;
                $this->load->view('upgrade_view', $data);
        }



	function tvlist(){
		log_message('error', 'begin to tvlist function!!');
		$this->output->cache(60*5);
		/*
		$datas =$this->input->post('sysInfo');
		log_message('error','get post data:'.$datas);
		$obj = json_decode($datas);
		if($obj == null){
			log_message('error', 'post data error!!');
			//return some data
			exit;
		}
		$mac = $obj->{'mac'};
		$json_ver = $obj->{'json_ver'};
		$postcataArr = explode(",", $obj->{'cata'});	
		*/
		$this->load->database('moon_live_test');
				
		$cataArr = array();
		$sql = 'select cata, title from ch_cata where isShow=1 order by idx';
		$query = $this->db->query($sql);
		foreach($query->result() as $row){
			$cataArr[] = array($row->title,$row->cata);
		}
		$query->free_result();
		
		$lastChid = -1;
		$chArr = array();		
		$sql = 'SELECT t1.ch_id,t1.name,t1.cata,t2.idx,t2.url,t2.tm_out FROM ch_name t1, ch_urls t2 where t1.ch_id=t2.ch_id order by t1.ch_id,t2.idx';
		$query = $this->db->query($sql);
		foreach($query->result() as $row){
			$cid = $row->ch_id;
			if($row->ch_id != $lastChid){
				$chArr["$cid"] = array($cid, $row->name,$row->cata, array(array($row->url,(int)$row->tm_out)));
				$lastChid = $row->ch_id;
			}
			else{
				$urlArr = $chArr["$cid"][3];
				$urlArr[] = array($row->url,(int)$row->tm_out);
				$chArr["$cid"][3] = $urlArr;
			}
		}
		$query->free_result();
		$this->db->close();		
		
		$out = array(
			'status' =>1,
			'cata' => $cataArr,
			'chlist'=> array_values($chArr)	//
		);
		$outstr = json_encode($out);
		
		$this->load->library('Simple_Encry', null, 'myEncy');
		$outstr = $this->myEncy->encode($outstr, 'lemoon_rocking');
		
		$data['data'] = $outstr;
                $this->load->view('tvlist', $data);
	//	exit($outstr);
		//var_dump($out);		
		
	}
}

/* End of file api.php */
/* Location: ./controllers/api.php */
