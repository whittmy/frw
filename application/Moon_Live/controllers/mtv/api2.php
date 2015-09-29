<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
	this is only for korea_version_early
	Version info

ver1:
	there are upgrade-interfaces, tvlist-infaces, they are no relatived
ver2:
	tvlist-interface will include the upgrade-interfaces, but the upgrade-interface before will be reserve!!
	for some reason, we made 'status' of interface effect!!!, 2: tvlist info; 1: upgrade info;

*/

class Api2 extends CI_Controller {

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

	// ver 1
      	function update_check($mac, $curVer){
                //$this->output->cache(60*5);
                $this->load->database('moon_live_test');
                $query = $this->db->query("select ver, url,intro from live_cfg where vercode>'$curVer' order by ver desc limit 1");
                $data['query'] = $query;
                $this->load->view('upgrade_view', $data);
        }
	
	//ver 1
      	function update_check_m20($mac, $curVer){
                //$this->output->cache(60*5);
                $this->load->database('moon_live_test');
                $query = $this->db->query("select ver, url,intro from live_cfg_m20 where vercode>'$curVer' order by ver desc limit 1");
                $data['query'] = $query;
                $this->load->view('upgrade_view', $data);
        }

	//ver 2
	function _needUpgrade($mac, $curVer, $flag1){
		//$this->load->database();
		$query = null;
		if($flag1 == 'korea'){
			$query = $this->db->query("select ver, url,intro from live_cfg_korea where vercode>'$curVer' order by ver desc limit 1");	
		}
		else if($flag1 == 'm20'){
			$query = $this->db->query("select ver, url,intro from live_cfg_m20 where vercode>'$curVer' order by ver desc limit 1");	
		}
		else if($flag1 == 'russia'){
			return null;
		}
		else{
			$query = $this->db->query("select ver, url,intro from live_cfg where vercode>'$curVer' order by ver desc limit 1");
		}	

		$info = $query->result();
		if(isset($info[0]) && isset($info[0]->ver) && !empty($info[0])){
			return $query;
		}
		else{
			return null;
		}
	}

	// ver1, 2
	function tvlist(){
		log_message('error', 'begin to tvlist function!!');
		
		$datas =$this->input->post('sysInfo');
		log_message('error','get post data:'.$datas);
		$obj = json_decode($datas);
		if($obj == null){
			log_message('error', 'post data error!!');
			//return some data
			exit;
		}
		$mac = isset($obj->{'mac'}) ? $obj->{'mac'} : null;
		$json_ver = isset($obj->{'json_ver'}) ? $obj->{'json_ver'} : null;
		$postcataArr = explode(",", $obj->{'cata'});	
		$app_ver = isset($obj->{'app_ver'}) ? $obj->{'app_ver'} : null;	
		$flag1 = isset($obj->{'flag1'}) ? $obj->{'flag1'} : null;
		
		log_message('error', 'mac='.$mac.' json_ver='.$json_ver.' app_ver='.$app_ver);

		$this->load->library('Simple_Encry', null, 'myEncy');
		$this->load->database('moon_live_test');


		$qq = $this->_needUpgrade($mac, $app_ver, $flag1);
		if($qq != null){
	                $data['query'] = $qq;
        	        $info = $this->load->view('upgrade_view', $data, true);
			$info = $this->myEncy->encode($info, 'lemoon_rocking');

			log_message('error', 'need upgrad');
			exit($info);		
		}

				
		$cataArr = array();
		if($flag1 == 'korea'){
			$sql = 'select cata, title from ch_cata where cata=\'0\' union select cata, title from ch_cata where cata= \'Z\'';
		}
		else if($flag1 == 'russia'){
			$sql = 'select cata, title from ch_cata where binary cata=\'a\' union select cata, title from ch_cata where binary cata= \'b\'';
		}
		else{
			$sql = 'select cata, title from ch_cata where isShow=1 order by idx';
		}
		$query = $this->db->query($sql);
		foreach($query->result() as $row){
			$cataArr[] = array($row->title,$row->cata);
		}
		$query->free_result();
		
		$lastChid = -1;
		$chArr = array();		
		$sql = 'SELECT t1.ch_id,t1.name,t1.cata,t2.idx,t2.url,t2.tm_out FROM ch_name t1,ch_urls t2 where t1.ch_id=t2.ch_id order by t1.ch_id,t2.idx';
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
			'status' =>2, // tvlist'status is 2
			'cata' => $cataArr,
			'chlist'=> array_values($chArr)	//
		);
		$outstr = json_encode($out);
		
		$outstr = $this->myEncy->encode($outstr, 'lemoon_rocking');
		exit($outstr);
		//var_dump($out);		
		
	}
}

/* End of file api.php */
/* Location: ./controllers/api.php */
