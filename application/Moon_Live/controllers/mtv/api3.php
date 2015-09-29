<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
	Version info

ver1:
	there are upgrade-interfaces, tvlist-infaces, they are no relatived
ver2:
	tvlist-interface will include the upgrade-interfaces, but the upgrade-interface before will be reserve!!
	for some reason, we made 'status' of interface effect!!!, 2: tvlist info; 1: upgrade info;

*/

class Api3 extends CI_Controller {

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


	function _needUpgrade($mac, $curVer, $flag1){
		//$this->load->database();
		log_message('error', '_needUpgrade mac='.$mac.' curVer='.$curVer.' flag1='.$flag1);
		$query = null;
		if($flag1 == 'korea'){
			$query = $this->db->query("select ver,status,url,md5s,intro from live_cfg_korea where vercode>'$curVer' order by ver desc limit 1");	
		}
		else if($flag1 == 'm20'){
			//$curVer = 20;
			$query = $this->db->query("select ver,status,url,md5s,intro from live_cfg_m20 where vercode>'$curVer' order by ver desc limit 1");	
		}
		else if($flag1 == 'russia'){
			return null;
		}
		else{
			$query = $this->db->query("select ver,status,url,md5s,intro from live_cfg where vercode>'$curVer' order by ver desc limit 1");
		}	

		$info = $query->result();
		if(isset($info[0]) && isset($info[0]->ver) && !empty($info[0])){
			$rt = array();
			$rt[] = $info[0]->status;
			$rt[] = $query;
			return $rt;
		}
		else{
			return null;
		}
	}

	//status( $status_g): 0:tvlist; 1:upgrade_background; 2:upgrade_force
	function tvlist(){
		//log_message('error', 'begin to tvlist function!!');
		
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
		
		$model = isset($obj->{'model'}) ? $obj->{'model'} : null;
		$sysver= isset($obj->{'sysver'}) ? $obj->{'sysver'} : null;
		//log_message('error', 'mac='.$mac.' model='.$model.' app_ver='.$app_ver.' sysver='.$sysver);
	

		$this->load->library('Simple_Encry', null, 'myEncy');
		$this->load->database('moon_live_test');

		$status_g = 0;
		$qq = $this->_needUpgrade($mac, $app_ver, $flag1);
		if($qq != null){
			$status_g = $qq[0];
			if($status_g == 2){
		                $data['query'] = $qq[1];

       		 	        $info = $this->load->view('upgrade_view3', $data, true);
				$info = $this->myEncy->encode($info, 'lemoon_rocking');

				log_message('error', 'need upgrad');
				exit($info);		
			}
		}
		
		//++++++++++++++++++++++++++++++++++++++++++++++
		$isvalid = true;
		if(strstr($model, 'M5')!= null){
			//M5, M5(JC), M5II-JC
		}
		else if(strstr($model,'emoon')!=null){
			//lemoon_N10S,Lemoon_N10,lemoon_N10S_0910,Alemoon R4,LemoonN10S
		}
		else if(strstr($model, 'N8') != null){
			// N8, N8_0910
		}
		else if($model=='M6'){
			//ok
		}
		else if(strstr($model, 'M20')!=null){
			//M20, M20_0910, M20_win8
		}
		else if($model=='R4-4CPU-8GPU' || $model=='T2-四核版' || $model=='T2-双核版'){
			//ok
		}
		else if($model=='rk30sdk' || $model=='Full AOSP on godbox'){
			//m02 & n2
		}
		else if($model=='XMATE_A20'){
			//t2-old
		}
		else{
			if(strstr($sysver, '20140213') != null){
				//m8 4003 ok
			}
			else if(strstr($sysver, '20140329') != null){
				//m8 4005 ok
			}
			else if(strstr($sysver, '20140611') != null){
				//Q7 ok
			}
			else if(strstr($sysver, '20140603')!= null){
				//Q7 ok
			}
			else if(strstr($sysver, '20140718')!=null){
				//hua yu fei yang ok
			}
			else if(strstr($sysver, '20140810')!== null){

			}
			else{
				$isvalid = false;
			}
		}
		if(!$isvalid){
			//exit('{}');
			log_message('error', 'invalid model:<<<< '.$model." >>>>\n\n");
			exit('{}');
		}
		//++++++++++++++++++++++++++++++++++++++++++++++++++++
		
				
		$cataArr = array();
		if($flag1 == 'korea'){
			$sql = 'select cata, title from ch_cata where cata=\'0\' union select cata, title from ch_cata where cata= \'Z\'';
		}
		else if($flag1 == 'russia'){
			$sql = 'select cata, title from ch_cata where cata=binary(\'a\') union select cata,title from ch_cata where cata=binary(\'b\')';
		}
		else if($flag1 == 'hk'){
			$sql = 'select cata, title from ch_cata where isshow=1 union select cata,title from ch_cata where cata=binary(\'h\')';
		}
		else{
			//$sql = 'select cata, title from ch_cata where isShow=1 and cata!=binary(\'a\') and cata!=binary(\'b\') order by idx';
			$sql = 'select cata, title from ch_cata where isShow=1  order by idx';
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
		

		// !!!!!!!!!!! notice !!!!!!!!!!!!!!!!!!!!!!
		$notice = '';
		///////////////////////////////



		$gcolor = '13,7,251';

		$out = array(
			'status' => $status_g, // tvlist'status is 2
			'gcolor' => $gcolor,
			'notice' => $notice,
			'cata' => $cataArr,
			'chlist'=> array_values($chArr)	//
		);
		$outstr = json_encode($out);
		
		$outstr = $this->myEncy->encode($outstr, 'lemoon_rocking');
		exit($outstr);
		//var_dump($out);		
		
	}



	/*
	<root>
		<version pn="" tip=""/>
		<url/>
		<loadpic push="0"/>
	</root>
	*/
	function auth($pacname=null, $oem=null, $ver=null, $mac=null){
/*
		$dom=new DomDocument('1.0', 'utf-8');
		$root = $dom->createElement('root');
		$dom->appendchild($root);

		//version
		$elem_ver = $dom->createElement('version');
		$root->appendchild($elem_ver);

		$attr_pn = $dom->createAttribute("pn");
		$elem_ver->appendChild($attr_pn);

		$val_pn = $dom->createTextNode('');
		$attr_pn->appendChild($val_pn);


		$attr_tip = $dom->createAttribute("tip");
		$elem_ver->appendChild($attr_tip);

		$val_tip = $dom->createTextNode('');
		$attr_tip->appendChild($val_tip);

		$val_ver = $dom->createTextNode('');
		$elem_ver->appendChild($val_ver);	

		//url
		$elem_url = $dom->createElement('url');
		$root->appendchild($elem_url);	

		$val_url = $dom->createTextNode('');
		$elem_url->appendChild($val_url);


		//loadpic
		$elem_loadpic = $dom->createElement('loadpic');
		$root->appendchild($elem_loadpic);	

		$attr_push = $dom->createAttribute("push");
		$elem_loadpic->appendChild($attr_push);

		$val_push = $dom->createTextNode('0');
		$attr_push->appendChild($val_push);	

		$val_loadpic = $dom->createTextNode('http://pic1.win4000.com/wallpaper/6/53981f503d1af.jpg');
		$elem_loadpic->appendChild($val_loadpic);
		header('Content-Type: text/xml');	
		echo $dom->saveXML();

*/
/*
liveTV_BG.png<

*/
		$str = '<?xml version="1.0" encoding="utf-8"?>
<root><version pn="" tip=""></version><url></url><loadpic push="14">http://download.007looper.com/images/Magic_mirror.jpg</loadpic></root>';
		header('Content-Type: text/xml');
		echo $str;

	}
}

/* End of file ap3.php */
/* Location: ./controllers/api3.php */
