<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

// http://www.nybgjd.com/3dclub/vermgr/

class VerMgr  extends CI_Controller {

	function index(){
		exit('index');
	}


	/////// Global ///////////////////////
	var $g_skey = 'wd!%s1';
	var $g_timeout = 300;


	// return: 
	// 1: sign error
	// 2: timeout
	// 0: ok
	function _check1($sign,$vername, $rqtime){
       	
		$signTmp = $this->_mySign($vername, $this->g_skey, $rqtime); 	
		if($signTmp != $sign){
			return 1;
		}

		$now = time();	
		if(($now-$rqtime)> $this->g_timeout){
			return 2;
		}
		return 0;
	}

	
	//return 
	// 0: ok
	// 1: error
	// 2: timeout
	function _check2($sign, $vercode, $reqtime){
		$tmp = $this->_getVerName($reqtime, $vercode);
		if($sign != $tmp){
			return 1;
		}

		$now = time();
		if(($now-$reqtime)> $this->g_timeout){
			return 2;
		}
		return 0;
	}

	function errorMsg($code){
		//debug
//		return;
		if($code == 1){
			$str = '{"body":[],"retMessage":"you sign is bad!","retStatus":300,"header":[],"page":[]}';
			exit($str);
		}	
		else if($code == 2){
			$str = '{"body":[],"retMessage":"you interface is exceed the time limit!","retStatus":300,"header":[],"page":[]}';
			exit($str);
		}
		
	}

	function sysupgrade(){
        $header = $this->input->get('header');
        $body = $this->input->get('body');		

		$this->load->library('MP_Cache');
		$cachePrefix = 'vermgr.sysupgrade.';
		
		$vercode = -1;
		$info = array();
		$jobj = json_decode($body);
		if($jobj != null && isset($jobj->versioncode) && isset($jobj->versionname)){
			$vercode = $jobj->versioncode;
			$version = $jobj->versionname;

			//debug $vercode = 19;
			
			$data1 = $this->mp_cache->get($cachePrefix.$vercode);
			if(!($data1 === false)){
				exit($data1);
			}
			
			$this->load->database('vr');
			$sql = 'SELECT * FROM  vr_version  where vercode >= '.$vercode;
			$query = $this->db->query($sql);	

			foreach($query->result() as $row){
				if($vercode == $row->vercode)
					$info['havenewversion'] = '0';
				else	
					$info['havenewversion'] = '1';
				$info['versioncode'] = $row->vercode;
				$info['versionname'] = $row->version;
				$info['apkurl'] = $row->apkurl;
				$info['md5url'] = $row->md5url;
				$info['feature'] = $row->feature;
				$info['notice'] = $row->notice;		// @@分割
				break;
			}
			$this->db->close();
		}
		
		if(!isset($info['versioncode'])){
			$info['havenewversion'] = '0';
			$info['versioncode'] = '';
			$info['versionname'] = '';
			$info['apkurl'] = '';
			$info['md5url'] = '';
			$info['feature'] = '';	
			$info['notice'] = '';	
		}
 

		$head = array();	
		$head['funcId'] =  '';
		$head['osVersion'] =  '';
		$head['appId'] =  '';
		$head['accessToken'] =  '';
		$head['devType'] =  '2';
		$head['appVersion'] =  '';
		$head['retStatus'] =  200;
		$head['userId'] =  '';
		$head['devId'] =  '';
		$head['retMessage'] =  'ok';
		$head['userType'] =  '0';


		$ret = array();
		$ret['body'] = $info;
		$ret['header'] = $head;
		$ret['page'] = array();

		$data1 = json_encode($ret);
		if($vercode != -1)
			$this->mp_cache->write($data1, $cachePrefix.$vercode, 900);
		echo $data1;
	}	
	

	
	function vtsoupgrade($type=null){
		if($type==null || $type==-1){
			exit('null');
		}
		/*
		$md5s = null;
		switch($_GET['type']){
		case 40:
			$md5s = 'E605442838EA0304820FC31F7F0A09E0';
			break;
		case 50:
			$md5s = '2C3FE0140D83C4F9A815225529931F01';
			break;
		case 60:
			$md5s = '72AD096656E84A068910857B60F067A7';
			break;
		case 61:
			$md5s = '0DCDAAF4FFD4E21A0ECF677D4007072E';
			break;
		case 70:
			$md5s = '1A63C092BEA6FCA5B62E826DBEA9F139';
			break;
		case 71:
			$md5s = '57D3B6DDAEBEDE59276CF108634878FE';
			break;
		}
		
		if($md5s == null){
			exit('null');
		}	
		*/
		
		$url = 'http://7xiolu.com1.z0.glb.clouddn.com/vt_'.$type.'.so';
 
		header("Location: ".$url);
	}
	
	
	function report($reson=null){
		
	
	}

	//---------------------------------------------------
	
	function _mySign($version, $s1, $stamp){
		$parm1 = "d!e@#";	//v9
		$parm2 = "q!q@#";	//v12
		$parm3 = "af!@#";	//v13
		$parm4 = "ds!@#";	//v14
		$parm5 = "q!a@#";	//v15
		$parm6 = "a!46";	//v16
		$parm7 = "b!68";	//v17
		$parm8 = "c!01";	//v18
		$parm9 = "d!23" ;//v19
		$parm10 = "e!45" ;//v10
		$parm11 =  "f!67"  ;//v11
		
		
		$str1 = $parm1;	//v20	 
		$str2 = $parm6;	//v21	 
		
	
		$str1 = $str1 . $s1;	//v20
		$parm1=  $parm1.$parm2.$parm5;	//v9
	
		$parm1 = $parm2;	//v9 <- v12
		$parm2 = $str1;		// v12 <- v20
		
		$str1 = $str1.$parm4;	//v20
		
		$str1 = $s1;
		$parm1 = $parm1 . $str1;
		
		$parm10 = $version;
		$str2 =  $str2 . $parm7;
		$parm7 = $parm7.$parm1;
	  
		$parm7 = $parm9;
		$parm11 = $stamp;
		
		
		$parm8 = $parm8.$parm1.$parm11 ;
		$parm10 = $parm10.$parm11;
		$str2 = $parm1.$parm7;
		
		$parm10 = $parm10 . $str2;
		
		$str2 = $parm10;
		$parm10 = $parm10 . $str2 . $parm1;
	
		//exit($str2);
		$strs = md5($str2);
		//exit($strs);
		
		//goto1
		return strtolower($strs);
  	}


	function _getVerName($stamp, $vercode) {
		$parm1 = "#%*q?"; // v7
		$parm2 = "%#&w5"; // v14
		$parm3 = "!#?t?"; // v15
		$parm4 = "@#!s*"; // v16
		$parm5 = "?#%*q"; // v17
		$parm6 = "?kl8j"; // v18
		$parm7 = "^kl9k"; // v19
		$parm8 = "@kl0h"; // v20
		$parm9 = "*kl1m"; // v21
		$parm10 = "!klnk"; // v8

		$i = 0; // v5

		// goto_0
		while ($i < 5) {
			// cond_0
			$tmp = $parm1.$parm4;
			if ($tmp[0] == '!') {
				$parm4 = $parm4.$vercode;
			}
			// cond1
			$i++;
			// go goto0
		}

		$parm11 = "s!@)("; // v9
		$parm12 = "t#@)("; // v10
		$parm13 = "w$@)("; // v11
		$parm14 = "r*@))"; // v12
		$parm15 = "q(@))"; // v13

		switch ($vercode) {
		case 0x1c: // sw0
			// :pswitch_0
			$parm1 = $parm1.$parm8;
			$parm6 = $parm1.$vercode;
			break;
		case 0x1d:
			// :pswitch_1
			$parm4 = $parm4.$parm12;
			$parm12 = $parm12.$vercode;
			break;
		case 0x1e:
			// :pswitch_2
			$parm14 = $parm14.$parm9;
			$parm14 = $parm14.$vercode;
			break;
		}

		// goto1
		$str1 = $parm6; // v22
		$str2 = $parm7; // v23
		$str3 = $parm8; // v24

		$parm2 = $parm2.$parm6;

		$parm6 = $parm6.$str1;
		$str3 = $parm3.$str3.$parm12;

		$str2 = $parm4.$str2.$parm11;

		$begin = 3; // v2
		$begin++;
		$end = 5; // v4

		$str1 = $parm5.$str1.$parm10;

		$parm13 = $parm13.$str2;
		$parm14 = $parm14.$parm9;

		$parm15 = $parm15.$parm8;

		$len = $end-$begin;
		
		 // echo('parm13= '.$parm13.'<br>');
		 // echo('parm14= '.$parm14.'<br>');
		 // echo('parm15= '.$parm15.'<br>');
		 // exit;
		 
		 $ret = '';
		if (substr($parm13, $begin, $len) == '!') {
			// goto2 ret v26
			$ret = md5($stamp.$str1);
		}
		else if (substr($parm14, $begin, $len) ==  ')') {
			$ret = md5($stamp.$str2);
		}
		else if (substr($parm15, $begin, $len) == '&') {
			$ret =  md5($stamp.$str3);
		}
		else{
			$ret =  md5($str3);
		}
		//exit($ret);
		return strtolower($ret);
	}


       function _seed() {
	       list($msec, $sec) = explode(' ', microtime());
	       return (float) $sec;

       }		



}

/* End of file shandong.php */
/* Location: ./controllers/shandong.php */
