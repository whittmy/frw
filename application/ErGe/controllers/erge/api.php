<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

// http://www.nybgjd.com/erge/api/
require(APPPATH.'/controllers/erge/include/oem_mgr_cache.php');
require(APPPATH.'/controllers/erge/include/cate_info_cache.php');
class Api  extends CI_Controller {

	function index(){
		//exit('index');
		//require  APPPATH."include/vr_mv_cache.php";  
		//$clsId = '5';
		//echo $MAC_MV_CACHE['vodtype'][$clsId]['t_name'];
		return;
		
		$this->load->library('AntiCollect', array('prefix'=>'tx'));
		$this->anticollect->apply();
	}
	
	
	
	function updateCacheFile(){
		$incpath = dirname(__FILE__).'/include/';
		if(!file_exists($incpath)) {
			mkdir($incpath, 0777);   
		}
		
		$this->_genCataCache($incpath . 'cate_info_cache.php');
	
	}
	
	function _compress_html($s){
		$s = str_replace(array("\r\n","\n","\t"), array('','','') , $s);
		$pattern = array (
						"/> *([^ ]*) *</",
						"/[\s]+/",
						"/<!--[\\w\\W\r\\n]*?-->/",
					   // "/\" /",
						"/ \"/",
						"'/\*[^*]*\*/'"
						);
		$replace = array (
						">\\1<",
						" ",
						"",
						//"\"",
						"\"",
						""
						);
		return preg_replace($pattern, $replace, $s);
	}

	function _genCataCache($fpath){
		$clsinfo = array();
		$this->load->database('erge');
		$sql = 'select c_id,c_name,c_type,c_hasseq,c_pid from res_class order by c_id';	
		$query = $this->db->query($sql);	
		foreach($query->result() as $row){
			$cif = array();
			$cif['name'] = $row->c_name;
			$cif['pid'] = $row->c_pid;
			$cif['type'] = $row->c_type;
			$cif['hasseq'] = $row->c_hasseq;
			
			$clsinfo[$row->c_id]  = $cif;
		}
		$query->free_result();

		foreach($clsinfo as $k=>$v){
			$cnt = 0;
			 
			//$sql = 'select count(d_id) cnt from res_dir where d_pid='.$k;	
			$sql = 'select count(id) cnt from r_cls_dir where r_cid='.$k;	
			$query = $this->db->query($sql);	
			foreach($query->result() as $row){
				$cnt = $row->cnt;
				break;
			}
			 
			
			$query->free_result();
			
			$clsinfo[$k]['cnt'] = $cnt; 

		}
		$this->db->close();
		
		
		$cacheValue = '<?php'.chr(10).'$CATA_INFO_CACHE = '.$this->_compress_html(var_export($clsinfo, true)).';'.chr(10).'?>';
		fwrite(fopen($fpath,'wb'),$cacheValue);
		echo 'finished';
	}
	
	
	
	
	
	
	
	function gettime(){
		echo time();
	}
	
 
	// return: 
	// 1001: sign error
	// 1002: timeout
	// 1003: lost args
	// 0: ok
	function _check1($header, $flag=null){
		global $OEM_MAP, $OEM_INFO;
		//return 0;
		
		//防刷新
		if($flag==null || empty($flag))
			$this->load->library('AntiCollect');
		else
			$this->load->library('AntiCollect', array('prefix'=>$flag));
			
		$this->anticollect->apply();
		
		
        //header parser --
         $jobj = json_decode($header);
		//Add
		if($jobj == null){
			return 1003;
		}

		if(!isset($jobj->sign) || !isset($jobj->client) ){
			return 1003;
		}

		$h_sign = $jobj->sign;
		$clientID = $jobj->client;
		
		if(empty($h_sign) || strlen($clientID)==0)
			return 1003;
		
		// client info error
		if(!isset($OEM_MAP[$clientID]))
			return 1003;
		
		/////////// test ///////
		if($h_sign == '22')
			return 0;
		////////////////////////
		
		$skey = $OEM_INFO[$clientID][0];
		$handmsg = $OEM_INFO[$clientID][1];
		$expire = $OEM_INFO[$clientID][2];
		$delayed = null;//86400;
		
		$h_sign = strtr($h_sign, array(' '=>'+'));
		$rslt =  $this->authcode($h_sign, "DECODE", $skey, $expire, $delayed);
		if($rslt == '' || $rslt != $handmsg)
			return 1001;
 
		return 0;
	}

 

	function errorMsg($code){
		$flag = true;
		
		if($flag){
			if($code == 1001){
				$str = '{"body":{},"header":{"retMessage":"you sign is bad!","retStatus":300},"page":[]}';
				exit($str);
			}	
			else if($code == 1002){
				$str = '{"body":{},"header":{"retMessage":"you interface is exceed the time limit!","retStatus":300},"page":[]}';
				exit($str);
			}
			else if($code == 1003){
				$str = '{"body":{},"header":{"retMessage":"lost args","retStatus":300},"page":[]}';
				exit($str);			
			}
		}
		
	}


	//////////////// 获取分类  ////////////////////////
	//http://www.nybgjd.com/erge/api/getCata?header={"sign":"","client":1}&body={}
	//http://localhost/ci/erge.php/erge/api/getCata?header={"sign":"","client":1}&body={}
	function getCata(){
		global $CATA_INFO_CACHE;
		
		$header = $this->input->get('header');
        $body = $this->input->get('body');
		
	    // check !!
		$ret = $this->_check1($header); 
		$this->errorMsg($ret);

		//body parser --
		$jobj = json_decode($body);
		$cid = isset($jobj->id)?$jobj->id: 0;
		
		$this->load->library('MP_Cache');
		$cacheName = "api.catas-".$cid;
		$data1 = $this->mp_cache->get($cacheName);
		if($data1 === false){
			$this->load->database('erge');
			$hasbody = false;
			$ret = array();
			$sql = 'select c_id,c_name,c_type,c_hasseq from res_class where c_pid='.$cid.' order by c_id';	
			$query = $this->db->query($sql);	
			foreach($query->result() as $row){
				$cif = array();
				$cif['id'] = $row->c_id.'';
				$cif['name'] = $row->c_name;
				$cif['type'] = $row->c_type;
				$cif['hasseq'] = $row->c_hasseq;
				//print_r($CATA_INFO_CACHE);
				//exit;
				$cif['cnt'] = $CATA_INFO_CACHE[$row->c_id]['cnt'];

				$hasbody = true;
				$ret['body']['catas'][] = $cif;	
			}
			$query->free_result();
			$this->db->close();

			if(!$hasbody){
				$ret['body']['catas'] = array();
			}
			
			//header
			$ret['header']['retMessage'] = 'ok'; 
			$ret['header']['retStatus'] = 200; 		

			//page
			$ret['page'] = array();
			$data1 = json_encode($ret);		
			$this->mp_cache->write($data1, $cacheName, 900);
		}
		exit($data1);
	}		
	

	
	///////////////////  通用  //////////////////////////
	//某类别下的 节目列表
	//$pgId 默认1
	//http://localhost/ci/erge.php/erge/api/getresList?header={"sign":"","client":1}&body={"id":3333}  //"pageindex":1,"pagesize":12
	function getresList(){
		$header = $this->input->get('header');
        $body = $this->input->get('body');
		
	    // check !!
		$ret = $this->_check1($header); 
		$this->errorMsg($ret);


		//body parser --
		$jobj = json_decode($body);
		if(!isset($jobj->id) || strlen($jobj->id)==0){
			$this->errorMsg(1003); 
		}
 
		$pgId = isset($jobj->pageindex)? $jobj->pageindex :1;
		$pgsize = isset($jobj->pagesize) ? $jobj->pagesize : 15 ;
		$fid = $jobj->id;
		
		
		$this->load->library('MP_Cache');
		$cacheName = 'api.reslist-'.$fid.'-'.$pgId.'-'.$pgsize;
		$data1 = $this->mp_cache->get($cacheName);
		if($data1 === false){
			$this->load->database('erge');
			$hasbody = false;
			$ret = array();
			$sql = 'select d_id,d_name,d_pic,d_hasseq from res_dir where d_pid='.$fid.' order by id limit '.($pgId-1)*$pgsize.', '.$pgsize;	
			//exit($sql);
			$query = $this->db->query($sql);	
			foreach($query->result() as $row){
				$cif = array();
				$cif['id'] = $row->d_id.'';
				$cif['name'] = $row->d_name;
				$cif['pic'] = $row->d_pic;
				$cif['hasseq'] = $row->d_hasseq.'';

				$hasbody = true;
				$ret['body']['resList'][] = $cif;	
			}
			$query->free_result();
			$this->db->close();

			if(!$hasbody){
				$ret['body']['resList'] = array();
			}
			
			//header
			$ret['header']['retMessage'] = 'ok'; 
			$ret['header']['retStatus'] = 200; 		

			//page
			$ret['page'] = array();
			$data1 = json_encode($ret);		
			$this->mp_cache->write($data1, $cacheName, 900);
		}
		exit($data1);
	}		


	function getresList2(){
		$header = $this->input->get('header');
        $body = $this->input->get('body');
		
	    // check !!
		$ret = $this->_check1($header); 
		$this->errorMsg($ret);


		//body parser --
		$jobj = json_decode($body);
		if(!isset($jobj->id) || strlen($jobj->id)==0){
			$this->errorMsg(1003); 
		}
 
		$pgId = isset($jobj->pageindex)? $jobj->pageindex :1;
		$pgsize = isset($jobj->pagesize) ? $jobj->pagesize : 15 ;
		$fid = $jobj->id;
		
		
		$this->load->library('MP_Cache');
		$cacheName = 'api.reslist2-'.$fid.'-'.$pgId.'-'.$pgsize;
		$data1 = $this->mp_cache->get($cacheName);
		if($data1 === false){
			$this->load->database('erge');
			$hasbody = false;
			$ret = array();
			$sql = 'select d_id,d_name,d_pic,d_hasseq from res_dir where d_pid='.$fid.' order by id limit '.($pgId-1)*$pgsize.', '.$pgsize;	
			//exit($sql);
			$query = $this->db->query($sql);	
			$hid = 0;
			$hidcnt = 1;
			$hlist = array();
			foreach($query->result() as $row){
				$cif = array();
				$cif['id'] = $row->d_id.'';
				$cif['name'] = $row->d_name;
				$cif['pic'] = $row->d_pic;
				$cif['hasseq'] = $row->d_hasseq.'';

				$hasbody = true;
				$ret['body']['resList'][] = $cif;	
				
				if($hidcnt < 3 && $pgId==1){
					if($hid < 8){
						if($hid == 0){
							$hlist['title'] = 'test_'.$hidcnt;
						}
						$hlist['childs'][] = $cif;
						$hid ++;
					}
					else{
						$ret['body']['headerList'][] = $hlist;
						$hlist = array();
						$hidcnt ++;

						$hid = 0;
					}					
				}

				
				

				
				
			}
			$query->free_result();
			$this->db->close();

			if(!$hasbody){
				$ret['body']['resList'] = array();
				$ret['body']['headerList'] = array();
			}
			
			//header
			$ret['header']['retMessage'] = 'ok'; 
			$ret['header']['retStatus'] = 200; 		

			//page
			$ret['page'] = array();
			$data1 = json_encode($ret);		
			$this->mp_cache->write($data1, $cacheName, 900);
		}
		exit($data1);
	}		





	
	//http://pc-20140929gboj/ci/erge.php/erge/api/getPL?header={"sign":"","client":1}&body={"id":1, "pageindex":1} 
	function getPL($ptype='v'){
		$header = $this->input->get('header');
        $body = $this->input->get('body');
		
	    // check !!
		//$ret = $this->_check1($header); 
		//$this->errorMsg($ret);	

		//body parser --
		$jobj = json_decode($body);
		//if(!isset($jobj->id) || strlen($jobj->id)==0){
		//	$this->errorMsg(1003); 
		//}
		$id = isset($jobj->id)? $jobj->id:-1;
		$pgId = isset($jobj->pageindex)? $jobj->pageindex :1;
		$pgsize = isset($jobj->pagesize) ? $jobj->pagesize : 15 ;
		//$fid = $jobj->id;

		$this->load->library('MP_Cache');
		$cacheName = 'api.pl-'.$ptype.'-'.$id.'-'.$pgId.'-'.$pgsize;
		$data1 = $this->mp_cache->get($cacheName);
		if($data1 === false){
			$this->load->database('erge');
			$hasbody = false;
			
			$qstr = '';
			if($id >= 0){
				$qstr = ' where l_pid='.$id. ' ';
			}
			$ret = array();
			if($ptype == 'v')
				$sql = 'select l_id,l_filesize,l_downurl,l_pic,l_playcnt,l_name,l_artist from res_libs'.$qstr.' limit '.($pgId-1)*$pgsize.', '.$pgsize;
			else
				$sql = 'select l_id,l_filesize,l_downurl,l_pic,l_playcnt,l_name,l_artist from res_libs where id>1306 limit '.($pgId-1)*$pgsize.', '.$pgsize;
			//exit($sql);
			$query = $this->db->query($sql);	
			foreach($query->result() as $row){
				$cif = array();
				$cif['id'] = $row->l_id.'';
				$cif['filesize'] = $row->l_filesize;
				//$cif['downurl'] = $row->l_downurl;
				$cif['downurl'] = 'http://www.nybgjd.com/erge/api/play/'.$cif['id'];
				$cif['pic'] = $row->l_pic;
				$cif['playcnt'] = $row->l_playcnt;
				$cif['name'] = $row->l_name;
				$cif['artist'] = $row->l_artist;
				
				$hasbody = true;
				$ret['body']['pList'][] = $cif;	
			}
			$query->free_result();
			$this->db->close();

			if(!$hasbody){
				$ret['body']['pList'] = array();
			}
			
			//header
			$ret['header']['retMessage'] = 'ok'; 
			$ret['header']['retStatus'] = 200; 		

			//page
			$ret['page'] = array();
			$data1 = json_encode($ret);		
			$this->mp_cache->write($data1, $cacheName, 900);		
		
		
		}

		exit($data1);
	}
	
	
	//获取播放列表
	//http://localhost/ci/erge.php/erge/api/getPlayList?header={"sign":"","client":1}&body={"id":10000010}  //"pageindex":1,"pagesize":3,
	function getPlayList(){
		$header = $this->input->get('header');
        $body = $this->input->get('body');
		
	    // check !!
		$ret = $this->_check1($header); 
		$this->errorMsg($ret);


		//body parser --
		$jobj = json_decode($body);
		if(!isset($jobj->id) || strlen($jobj->id)==0){
			$this->errorMsg(1003); 
		}
		
		$pgId = isset($jobj->pageindex)? $jobj->pageindex :1;
		$pgsize = isset($jobj->pagesize) ? $jobj->pagesize : 15 ;
		$fid = $jobj->id;
		
		$this->load->library('MP_Cache');
		$cacheName = 'api.plist-'.$fid.'-'.$pgId.'-'.$pgsize;
		$data1 = $this->mp_cache->get($cacheName);
		if($data1 === false){
			$this->load->database('erge');
			$hasbody = false;
			$ret = array();
			$sql = 'select l_id,l_filesize,l_downurl,l_pic,l_playcnt,l_name,l_artist from res_libs where l_pid='.$fid.' order by l_id limit '.($pgId-1)*$pgsize.', '.$pgsize;
			$query = $this->db->query($sql);	
			foreach($query->result() as $row){
				$cif = array();
				$cif['id'] = $row->l_id.'';
				$cif['filesize'] = $row->l_filesize;
				//$cif['downurl'] = $row->l_downurl;
				$cif['downurl'] = 'http://www.nybgjd.com/erge/api/play/'.$cif['id'];
				$cif['pic'] = $row->l_pic;
				$cif['playcnt'] = $row->l_playcnt;
				$cif['name'] = $row->l_name;
				$cif['artist'] = $row->l_artist;
				
				$hasbody = true;
				$ret['body']['pList'][] = $cif;	
			}
			$query->free_result();
			$this->db->close();

			if(!$hasbody){
				$ret['body']['pList'] = array();
			}
			
			//header
			$ret['header']['retMessage'] = 'ok'; 
			$ret['header']['retStatus'] = 200; 		

			//page
			$ret['page'] = array();
			$data1 = json_encode($ret);		
			$this->mp_cache->write($data1, $cacheName, 900);
		}
		exit($data1);
	}
 
 
	function play($lid = null){
		if($lid == null)
			return;
		$this->load->library('MP_Cache');
		$cacheName = 'play-'.$lid;
		$data1 = $this->mp_cache->get($cacheName);
		if($data1 === false){
			$this->load->database('erge');
			$sql = 'select l_downurl from res_libs where l_id='.$lid;
			$query = $this->db->query($sql);	
			foreach($query->result() as $row){
				$data1 = $row->l_downurl;
				break;
			}
			$query->free_result();
			$this->db->close();
			
			if(!empty($data1))
				$this->mp_cache->write($data1, $cacheName, 900);
		}
		
		header('Location: '.$data1);
	}
	
	//获取播放地址
	// default: getDuMvUrl/you/fid
	// optype  1: play	2: mvdown
		
	function getDuMvUrl($usr=null, $fileId=null,$optype=null){
		log_message('error', 'getDuMvUrl:'.$usr.','.$fileId);
		if($usr==null || (strlen($usr)==0) || $fileId==null || (strlen($fileId)==0)){
			exit( '');
		}
		$this->load->database('vr');
		$sql = 'select fullmd5, fullcrc32, file_size,slicemd5 from vr_test where fid='.$fileId;	
		$query = $this->db->query($sql);

		$fullmd5 = null;
		$fullcrc32 = null;
		$filesize = 0;
		$slicemd5 = null;
		foreach ($query->result() as $row) {
			$fullmd5 = $row->fullmd5 ;
			$fullcrc32 = $row->fullcrc32 ;
			$filesize = $row->file_size ;
			$slicemd5 = $row->slicemd5 ;
			break;
		}
		
		if($fullmd5 == null){
			log_message('error', 'getDuMvUrl: md5 is null');
			echo 'E_NMD5';
			return;
		}

		$destname = 'pmv/a.mp4';
		if($optype == 2)
			$destname = 'dmv/'.$fileId.'.zip';	// '.mp4' 下载的缓存，统一以 .zip结尾，不影响下载到手机端的文件格式，同时方便对缓存的维护
		
		$this->load->library('DuUtil');

		if($optype != 2){
			log_message('error', 'getDuMvUrl:begin to deleteSingle');
			$this->duutil->deleteSingle($usr, base64_encode($destname));
		}
		$rslt = $this->duutil->quickCopy($usr, $fullmd5, $fullcrc32, $filesize,$slicemd5, $destname);
		$jobj = json_decode($rslt);

		if($jobj == null){
			echo '';
			exit;
		}
		else if(isset($jobj->error_code)){
			if($jobj->error_code==110){
				log_message('error', "user=$usr, error: ".$jobj->error_msg.', begin to remove user-accesstoken');
				$sql = "delete from du_pcs_auth_list where username='$usr'";
				$this->db->query($sql);
				echo 'E110';
			}
			else if($jobj->error_code==31061){
				//msg = file already exists
				$url = $this->duutil->getDuUrl($usr, base64_encode('/apps/disk1/'.$destname));
				echo $url;
			}
			exit;
		}
	
		$url = $this->duutil->getDuUrl($usr, base64_encode($jobj->path));
		
		//$url = $this->duutil->getDuUrl($usr, base64_encode('/apps/disk1/a.mp4'));
		echo $url;
		//sleep(2);
		
		//echo $this->duutil->getStreamUrl($usr, base64_encode('/apps/disk1/a.mp4'));
	}
	
	// game download url
	//http://www.nybgjd.com/3dclub/api4/getDuGmUrl/you/
	function getDuGmUrl($usr=null, $fileId=null){
		log_message('error', 'getDuGmUrl:'.$usr.','.$fileId);
		//exit($usr);
		if($usr==null || (strlen($usr)==0) || $fileId==null || (strlen($fileId)==0)){
			exit( '');
		}
		$this->load->database('vr');
		$sql = 'select fullmd5, fullcrc32, file_size,slicemd5, ext from vr_data_apk where fid='.$fileId;	
		$query = $this->db->query($sql);

		$fullmd5 = null;
		$fullcrc32 = null;
		$filesize = 0;
		$slicemd5 = null;
		$ext = null;
		foreach ($query->result() as $row) {
			$fullmd5 = $row->fullmd5 ;
			$fullcrc32 = $row->fullcrc32 ;
			$filesize = $row->file_size ;
			$slicemd5 = $row->slicemd5 ;
			$ext = $row->ext;
			break;
		}
		
		if($fullmd5 == null){
			log_message('error', 'getDuGmUrl: md5 is null');
			echo 'E_NMD5';
			return;
		}
 
		$destname = 'dapk/'.$fileId.'.'.'zip';	//$ext 本想按原格式进行拷贝的，但是这样就不方便对网盘缓存的维护，因为名字、扩展名组合的乱七八糟，所以全部已zip进行结尾
		
		$this->load->library('DuUtil');

 
		//	log_message('error', 'getDuGmUrl:begin to deleteSingle');
		//	$this->duutil->deleteSingle($usr, base64_encode($destname));
	 
		$rslt = $this->duutil->quickCopy($usr, $fullmd5, $fullcrc32, $filesize,$slicemd5, $destname);
		$jobj = json_decode($rslt);

		if($jobj == null){
			echo '';
			exit; 
		}
		else if(isset($jobj->error_code)){
			if($jobj->error_code==110){
				log_message('error', "user=$usr, error: ".$jobj->error_msg.', begin to remove user-accesstoken');
				$sql = "delete from du_pcs_auth_list where username='$usr'";
				$this->db->query($sql);
				echo 'E110';
			}
			else if($jobj->error_code==31061){
				//msg = file already exists
				$url = $this->duutil->getDuUrl($usr, base64_encode('/apps/disk1/'.$destname));
				echo $url;
			}
			exit;
		}
	
		$url = $this->duutil->getDuUrl($usr, base64_encode($jobj->path));
		
		//$url = $this->duutil->getDuUrl($usr, base64_encode('/apps/disk1/a.mp4'));
		echo $url;
		//sleep(2);
		
		//echo $this->duutil->getStreamUrl($usr, base64_encode('/apps/disk1/a.mp4'));
	}	
	
	//改来改去最终还是觉得用base64，url编码感觉怪怪的，尤其在为该链接做编码系统时，js与php之间的url、base64函数转换时一堆问题实在容忍不了
	// 最终编码系统依托于cookie，如此方便 js与php共享数据，容易操作，另外就是cookie中特殊字符的处理，escape和unescape对于+的处理，让人无语，最后都变空格了。
	//最终得益于 js的encodeURIComponent函数，感觉完美
	//工具：  http://www.nybgjd.com/misc/tools/encode.php
	//rpath 为相对于/apps/disk1/的 相对路径的base64的编码
	// 首先请求的文件必须真实存在在指定用户下
	// user: urlencode(utf8)处理过的
	//请求hls流 http://www.nybgjd.com/3dclub/api4/getStreamUrl/ xxx
	function getStreamUrl($rpath=null, $user=null){
		log_message('error', "getStreamUrl: $rpath,  $user");
		if($rpath==null || strlen($rpath)==0)
			exit('null');

		$this->load->library('MP_Cache');
		$data1 = $this->mp_cache->get($rpath);
		//$data1 = false;
		if($data1 === false){
			$this->load->library('DuUtil');
			$path = urlencode('/apps/disk1/').urlencode(base64_decode($rpath));
			
			if($user == null || strlen($user)==0){
				$user = 'whittmy';
			}
			$data1 = $this->duutil->getStreamUrl($user, $path);	

			//避免请求
			$cnt = 0;
			while($cnt < 3 && strstr($data1, 'error')){
				log_message('error', 'getStreamUrl retry :'.$cnt);
				usleep(800000);// 300ms
				$data1 = $this->duutil->getStreamUrl($user, $path);
				$cnt ++;
			}
			
			if($data1==null || empty($data1) || strstr($data1, 'error')){
				exit('null');
			}
			
			$this->mp_cache->write($data1, $rpath, 3600*2);
		}
		
		$tmpfile = tempnam(sys_get_temp_dir(),$rpath);
		if($tmpfile === false){
			exit('null');
		}
		$len = file_put_contents($tmpfile, $data1, LOCK_EX); 
		if(false === $len){
			exit('null');
		}
		
		log_message('error', 'getStreamUrl successful:'.$rpath);
		header('Content-Description: File Transfer');
		header('Content-Type: application/vnd.apple.mpegurl');
		header('Content-Disposition: attachment; filename='.time().'.m3u8');
		header('Content-Transfer-Encoding: binary');
		header('Expires: 0');
		header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		header('Pragma: public');
		header('Content-Length: ' . $len);
		readfile($tmpfile);
		
		//exit($data1);
	}
	
	//管理云盘上的缓存文件, 
	//注：根据前面的约定，所有的缓存文件均以 .zip结尾
	//http://www.nybgjd.com/3dclub/api4/delsrvcache/you/
	function delsrvcache($usr, $files=null, $isSingle=null){
		log_message('error', 'delsrvcache:user='.$usr.',file='.$files);
		if($files == null)
			return;
			
		$this->load->library('DuUtil');
		if($isSingle != null){
			$rlst = $this->duutil->deleteSingle($usr,base64_encode($files),1);
		}
		else{
			$rlst= $this->duutil->deleteBatch($usr,base64_encode($files));
		}
		echo $rlst;
	}
	
	function report($reson=null){
		
	
	}

	//---------------- 校验区-----------------------------------
	/*
	$rslt =  authcode('93fc1eHk9vpIl6jA59A', "DECODE", 'c500201507', 180, 1432023121);
	$rslt =  authcode('i am c500', "ENCODE", 'c500201507', 180, 1432023426);
	if($rslt == null)
		echo 'null';
	else
		echo $rslt;
	*/	
	
	function prt($a, $b){
		//echo $a.'='.$b.'<br>';
	}	

	function getmtime(){
		$s = microtime();
		//$s = '0.00020300 1432023426';
		return $s;
	}
	
	
	 /**
     * @param string $string 原文或者密文
     * @param string $operation 操作(ENCODE | DECODE), 默认为 DECODE
     * @param string $key 密钥
     * @param int $expiry 密文有效期, 加密时候有效， 单位 秒，0 为永久有效		！！！@rocking：已修改，加密与解密都用到， 解密时用于判断这个有效期是否与加密时用的有效期一致，避免客户端加密时私自篡改有效期
					已将 expiry的值也纳入到了加密串的一部分，方便核对
					expiry 加密与解密必须要一致
	 * @param int $de_delayed	!!! @rocking: 解密时用于延长密文的有效期，
     * @return string 处理后的 原文或者 经过 base64_encode 处理后的密文	
	  
		@example
		$a = authcode('abc', 'ENCODE', 'key', 3600);
		$b = authcode($a, 'DECODE', 'key', 3600); // 在一个小时内，$b得到'abc'，否则 $b 为空('')
	 */ 
	function authcode($string, $operation = 'DECODE', $key = '', $expiry = 3600, $de_delayed=null) {
		$ckey_length = 4;   
		// 随机密钥长度 取值 0-32;
		// 加入随机密钥，可以令密文无任何规律，即便是原文和密钥完全相同，加密结果也会每次不同，增大破解难度。
		// 取值越大，密文变动规律越大，密文变化 = 16 的 $ckey_length 次方
		// 当此值为 0 时，则不产生随机密钥
		$this->prt('decstring', $string);
		$key = md5($key ? $key : 'deflt_key'); //这里可以填写默认key值
		$this->prt('key', $key);
		$keya = md5(substr($key, 0, 16));
		$this->prt('keya', $keya);
		
		$keyb = md5(substr($key, 16, 16));
		$this->prt('keyb', $keyb);
		
		$keyc = $ckey_length ? ($operation == 'DECODE' ? substr($string, 0, $ckey_length): substr(md5(/*microtime()*/$this->getmtime()), -$ckey_length)) : '';
		$this->prt('keyc', $keyc);
		$cryptkey = $keya.md5($keya.$keyc);
		$this->prt('cryptkey', $cryptkey);
		
		$key_length = strlen($cryptkey);
		
		//rocking
		$exp = sprintf("%05d", $expiry);
		
		$string = $operation == 'DECODE' ? base64_decode(substr($string, $ckey_length)) : sprintf('%010d', $expiry ? $expiry + time() : 0).substr(md5($string.$keyb), 0, 16).$exp.$string; //rocking：此处将expire信息添加进去
		$string_length = strlen($string);
		$this->prt('string', $string);
		
		$result = '';
		$box = range(0, 255);
		
		$rndkey = array();
		for($i = 0; $i <= 255; $i++) {
			$rndkey[$i] = ord($cryptkey[$i % $key_length]);
		}
		
		for($j = $i = 0; $i < 256; $i++) {
			$j = ($j + $box[$i] + $rndkey[$i]) % 256;
			$tmp = $box[$i];
			$box[$i] = $box[$j];
			$box[$j] = $tmp;
		}
		
		for($a = $j = $i = 0; $i < $string_length; $i++) {
			$a = ($a + 1) % 256;
			$j = ($j + $box[$a]) % 256;
			$tmp = $box[$a];
			$box[$a] = $box[$j];
			$box[$j] = $tmp;
		 
		 //prt("chr$i=", (ord($string[$i]) ^ ($box[($box[$a] + $box[$j]) % 256])).'');
			$result .= chr(ord($string[$i]) ^ ($box[($box[$a] + $box[$j]) % 256]));
		}

		if($operation == 'DECODE') {
			//result 格式： tttttttttt35038f456834d134xxxxxsssssssssssssssssss
			// t代表时间戳，10字节[0,10)
			// x 代表expiry的值，5字节，不足补零 [26,31)
			// s 最终的字符串[31,
			//rocking modify, 修改目的：防止客户端私自篡改(客户端可能第三方去写)密串的有效性.
			//增加 解密延时机制，如果事先约定的时间短了，可以再服务器端进行延时处理 $de_delayed
			
			$this->prt('result', $result);
			$client_tm = intval(substr($result, 0, 10));
			$client_exp_val = intval(substr($result,26,31));
			
			
			$tm_diff_delay = $client_tm - time();
			if($de_delayed != null)
				$tm_diff_delay += $de_delayed;
			//echo $client_tm.','.$client_exp_val.', '.$tm_diff_delay.'<br>';
			//注意判断：
			// 优先判断 exiry  以及 md5 的一致性
			// 最后再判断 有效期(有三种情况：为0代表无限，非延时、延时)
			
			
			if(($client_exp_val==$expiry)
				&& substr($result, 10, 16) == substr(md5(substr($result, 31).$keyb), 0, 16)
				&& ($client_tm==0||$tm_diff_delay>0)) 	//注意这儿的判断哦，虽说可以延时，但并不代表就允许事先约定的expiry就可以随意变更。
			{
				return substr($result, 31);
			} else {
				return '';
			}
		} else {
			return $keyc.str_replace('=', '', base64_encode($result));
		}
	}

       function _seed() {
	       list($msec, $sec) = explode(' ', microtime());
	       return (float) $sec;
       }		



}

/* End of file shandong.php */
/* Location: ./controllers/shandong.php */
