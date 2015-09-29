<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Test extends CI_Controller {

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
		$tmp = $this->getVerName($reqtime, $vercode);
		if($sign != $tmp){
			return 1;
		}

		$now = time();
		if(($now-$rqtime)> $this->g_timeout){
			return 2;
		}
		return 0;
	}

	function errorMsg($code){
		//debug
		return;
		if($code == 1){
			$str = '{"body":[],"retMessage":"you sign is bad!","retStatus":300,"header":[],"page":[]}';
			exit($str);
		}	
		else if($code == 2){
			$str = '{"body":[],"retMessage":"you interface is exceed the time limit!","retStatus":300,"header":[],"page":[]}';
			exit($str);
		}
		
	}


	function version(){
                $header = $this->input->get('header');
                $body = $this->input->get('body');		
	
		$info = array();
		$info['havenewversion'] = '0';
		$info['versioncode'] = '';
		$info['versionname'] = '';
		$info['apkurl'] = '';
		$info['md5url'] = '';
		$info['feature'] = '';	
	
		$jobj = json_decode($body);
		if($jobj != null && isset($jobj->versioncode) && isset($jobj->versionname)){
			$vercode = $jobj->versioncode;
			$version = $jobj->versionname;

			$this->load->database('vr');
			$sql = 'SELECT * FROM  vr_version  where vercode > '.$vercode;
			$query = $this->db->query($sql);	

			foreach($query->result() as $row){
				$info['havenewversion'] = '1';
				$info['versioncode'] = $row->vercode;
				$info['versionname'] = $row->version;
				$info['apkurl'] = $row->apkurl;
				$info['md5url'] = $row->md5url;
				$info['feature'] = $row->feature;
				break;
			}
		}

		$this->db->close();


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

		echo json_encode($ret);
	}



	function getClass(){
		$header = $this->input->get('header');
                $body = $this->input->get('body');
		
        	//header parser --
                $jobj = json_decode($header);
		//Add
		if($jobj == null){
			$this->errorMsg(1);
		}

		if(!isset($jobj->sign) || !isset($jobj->reqtime) || !isset($jobj->versionCode)
				|| !isset($jobj->versionName) || !isset($jobj->packageName)){
			$this->errorMsg(1);
		}

                $h_sign = $jobj->sign;
                $rqtime = $jobj->reqtime;
		$vercode = $jobj->versionCode;
		$vername = $jobj->versionName;
		$packname = $jobj->packageName;
	
		$ret = $this->_check1($h_sign,$vername, $rqtime); 
		$this->errorMsg($ret);


		//body parser --
		$jobj = json_decode($body);
		if(isset($jobj->reqtime) && isset($jobj->version)){
			$ret = $this->_check2($jobj->version, $vercode, $jobj->reqtime);
			$this->errorMsg($ret);
		}
		else{
			$this->errorMsg(1);		
		}



		$ret = array();

		$this->load->database('vr');
		$sql = 'SELECT r_a id, count(r_id) cnt  FROM vr_vod_relation group by r_a';
		$query = $this->db->query($sql);

		$cntArr = array();
		foreach ($query->result() as $row)
		{
			$cntArr[$row->id] = $row->cnt;
		}
		$query->free_result();

		$pre = 'http://www.nybgjd.com/srv2/get/img/vrres/';
		$sql = 'select t_id,t_name,t_pic,t_content,t_ext_squarelogo,t_ext_v3squarelogo,t_ext_transverse from vr_vod_topic';	
		$query = $this->db->query($sql);	
		foreach($query->result() as $row){
			$cif = array();
			$cif['id'] = $row->t_id.'';
			$cif['name'] = $row->t_name;
			$cif['logo'] = $row->t_pic;
			$cif['squarelogo'] = empty($row->t_ext_squarelogo)?'': $pre.urlencode($row->t_ext_squarelogo);
			$cif['v3squarelogo'] = empty($row->t_ext_v3squarelogo)?'':$pre.urlencode($row->t_ext_v3squarelogo);
			$cif['content'] = $row->t_content;
			$cif['transverse'] = intval($row->t_ext_transverse);
			$cif['total'] = intval($cntArr[$row->t_id]);
			
			$ret['body']['classList'][] = $cif;	
		}
		$query->free_result();
		$this->db->close();

		//header
                $ret['header']['sign'] = $h_sign;
                $ret['header']['versionCode'] = $vercode;
                $ret['header']['versionName'] = $vername;
                $ret['header']['packageName'] = $packname;
                $ret['header']['reqtime'] = $rqtime.'';
		$ret['header']['retMessage'] = 'ok'; 
		$ret['header']['retStatus'] = 200; 		

		//page
		$ret['page'] = array();

		$str = json_encode($ret);
		exit($str);
	}	



	function getMovieList(){
		$header = $this->input->get('header');
                $body = $this->input->get('body');
		
        	//header parser --
                $jobj = json_decode($header);
		//Add
		if($jobj == null){
			$this->errorMsg(1);
		}

		if(!isset($jobj->sign) || !isset($jobj->reqtime) || !isset($jobj->versionCode)
				|| !isset($jobj->versionName) || !isset($jobj->packageName)){
			$this->errorMsg(1);
		}


                $h_sign = $jobj->sign;
                $rqtime = $jobj->reqtime;
		$vercode = $jobj->versionCode;
		$vername = $jobj->versionName;
		$packname = $jobj->packageName;
	
		$ret = $this->_check1($h_sign,$vername, $rqtime); 
		$this->errorMsg($ret);


		//body parser --
		$jobj = json_decode($body);
		$clsId = $jobj->classid;
		$pgId = $jobj->pageindex;
		$pgsize = $jobj->pagesize;


		$ret = array();

		$this->load->database('vr');
		$sql = 'select r_a, r_b from vr_vod_relation where r_a='.$clsId.' limit '.($pgId-1)*$pgsize.", $pgsize";
		$query = $this->db->query($sql);

		$midArr = array();
		foreach ($query->result() as $row)
		{
			$midArr[] = $row->r_b;
		}
		$query->free_result();
		//print_r($midArr);
		//exit;

                if(count($midArr) == 0){
                         $sql = 'SELECT 0 FROM vr_vod where 0';
                }
                else{	
			$concase = implode(',', $midArr);
			$sql = 'select d_id id, d_name chinesename, d_ex_pptvmid pptvmovieid,FROM_UNIXTIME(d_addtime,"%Y-%m-%d %H:%i:%s") createtime,d_playurl web_url,d_pic images,d_picthumb crossimages,d_hits playnum,d_content recommend from vr_vod where d_id in ('.$concase.')';	
		}
		//exit($sql);
		$query = $this->db->query($sql);	
		$realcnt = 0;
		foreach($query->result() as $row){
			$cif = array();
			$cif['id'] = $row->id.'';
			$cif['chinesename'] = $row->chinesename;
			$cif['pptvmovieid'] = $row->pptvmovieid;
			$cif['createtime'] = $row->createtime;
			$cif['datatype'] = 'movie';
			$cif['web_url'] = $row->web_url;
			$cif['type'] = '7';
			$cif['movie_definition'] = '超清';
			$cif['images'] = $row->images;
			$cif['crossimages'] = $row->crossimages;
			$cif['playnum'] = $row->playnum;
			$cif['recommend'] = $row->recommend;
		
			$ret['body']['movieList'][] = $cif;	
			$realcnt ++;
		}
		$query->free_result();
		$this->db->close();

                  if($realcnt == 0){
                        $ret['body']['movieList'] = null;
                  }
		//header
		$ret['header']['sign'] = $h_sign;
		$ret['header']['versionCode'] = $vercode; 
		$ret['header']['versionName'] = $vername; 
		$ret['header']['packageName'] = $packname; 
		$ret['header']['reqtime'] = $rqtime.''; 
		$ret['header']['retMessage'] = 'ok'; 
		$ret['header']['retStatus'] = 200; 		

		//page
		$pgArr = array();
		$pgArr['count'] = 121;
		$pgArr['pageindex'] = intval($pgId);
		$pgArr['pagecount'] = intval($realcnt);
		$ret['page'] = $pgArr;

		$str = json_encode($ret);
		exit($str);
	}	



	function getMovieTop(){

		$header = $this->input->get('header');

		//header parser --
		$jobj = json_decode($header);
		//Add
		if($jobj == null){
			$this->errorMsg(1);
		}

		if(!isset($jobj->sign) || !isset($jobj->reqtime) || !isset($jobj->versionCode)
				|| !isset($jobj->versionName) || !isset($jobj->packageName)){
			$this->errorMsg(1);
		}


		$h_sign = $jobj->sign;
		$rqtime = $jobj->reqtime;
		$vercode = $jobj->versionCode;
		$vername = $jobj->versionName;
		$packname = $jobj->packageName;

		$ret = $this->_check1($h_sign,$vername, $rqtime);
		$this->errorMsg($ret);


		$this->load->database('vr');
		$sql = 'select count(d_id) cnt from  vr_vod where d_ex_typeid=2 || d_ex_typeid=1';
		$query = $this->db->query($sql);

		$count = 0;
		foreach ($query->result() as $row)
		{
			$count = $row->cnt;
			break;
		}
		$query->free_result();


		//产生变化
		srand($this->_seed());
		$based = rand(0,($count-11));

		$sql = 'select d_id id, d_name chinesename, d_ex_pptvmid pptvmovieid,FROM_UNIXTIME(d_addtime,"%Y-%m-%d %H:%i:%s") createtime,d_playurl web_url,d_pic images,d_picthumb crossimages,d_hits playnum,d_content recommend from vr_vod  where d_ex_typeid=2 || d_ex_typeid=1 limit '.$based.', 10';

		//exit($sql);
		$query = $this->db->query($sql);




		$ret = array();
		$realcnt = 0;
		foreach($query->result() as $row){
			$cif = array();
			$cif['id'] = $row->id.'';
			$cif['chinesename'] = $row->chinesename;
			$cif['pptvmovieid'] = $row->pptvmovieid;
			$cif['createtime'] = $row->createtime;
			$cif['datatype'] = 'movie';
			$cif['web_url'] = $row->web_url;
			$cif['type'] = '7';
			$cif['movie_definition'] = '超清';
			$cif['images'] = $row->images;
			$cif['crossimages'] = $row->crossimages;
			$cif['playnum'] = $row->playnum;
			$cif['recommend'] = $row->recommend;

			$ret['body']['movieList'][] = $cif;
			$realcnt ++;
		}
		$query->free_result();
		$this->db->close();

		if($realcnt == 0){
			$ret['body']['movieList'] = null;
		}
		//header
		$ret['header']['sign'] = $h_sign;
		$ret['header']['versionCode'] = $vercode;
		$ret['header']['versionName'] = $vername;
		$ret['header']['packageName'] = $packname;
		$ret['header']['reqtime'] = $rqtime.'';
		$ret['header']['retMessage'] = 'ok';
		$ret['header']['retStatus'] = 200;

		//page
		$pgArr = array();
		$ret['page'] = $pgArr;

		$str = json_encode($ret);
		exit($str);
	}       


	function getGameClass(){
                $header = $this->input->get('header');
                $body = $this->input->get('body');

                //header parser --
                $jobj = json_decode($header);

		//Add
		if($jobj == null){
			$this->errorMsg(1);
		}
		
		if(!isset($jobj->sign) || !isset($jobj->reqtime) || !isset($jobj->versionCode)
			|| !isset($jobj->versionName) || !isset($jobj->packageName)){
			$this->errorMsg(1);
		}

                $h_sign = $jobj->sign;
                $rqtime = $jobj->reqtime;
                $vercode = $jobj->versionCode;
                $vername = $jobj->versionName;
                $packname = $jobj->packageName;

                $ret = $this->_check1($h_sign,$vername, $rqtime);
                $this->errorMsg($ret);


                //body parser --
                $jobj = json_decode($body);
		
                if(isset($jobj->reqtime) && isset($jobj->version)){
                        $ret = $this->_check2($jobj->version, $vercode, $jobj->reqtime);
                        $this->errorMsg($ret);
                }
                else{
                        $this->errorMsg(1);
                }



		$ret = array();

		$this->load->database('vr');
		$sql = 'SELECT r_a id, count(r_id) cnt  FROM vr_game_relation group by r_a';
		$query = $this->db->query($sql);

		$cntArr = array();
		foreach ($query->result() as $row)
		{
			$cntArr[$row->id] = $row->cnt;
		}
		$query->free_result();


		//print_r($cntArr);
		//exit;

		$pre = 'http://www.nybgjd.com/srv2/get/img/vrres/';
		$sql = 'select t_id,t_name,t_pic,t_content from vr_game_topic';	
		$query = $this->db->query($sql);	
		foreach($query->result() as $row){
			$cif = array();
		//	exit($row->t_id.'');
			$cif['id'] = $row->t_id.'';
			$cif['name'] = $row->t_name;
			$cif['logo'] = $row->t_pic;
			$cif['content'] = $row->t_content;
			$cif['total'] = intval($cntArr[$row->t_id]);


			$ret['body']['gameclassList'][] = $cif;	
		}
		$query->free_result();
		$this->db->close();

		//header
                $ret['header']['sign'] = $h_sign;
                $ret['header']['versionCode'] = $vercode;
                $ret['header']['versionName'] = $vername;
                $ret['header']['packageName'] = $packname;
                $ret['header']['reqtime'] = $rqtime.'';
                $ret['header']['retMessage'] = 'ok';
                $ret['header']['retStatus'] = 200;


		//page
		$ret['page'] = array();


		$str = json_encode($ret);
		exit($str);
	}	



	function getGameList(){
		$header = $this->input->get('header');
                $body = $this->input->get('body');
		
        	//header parser --
                $jobj = json_decode($header);
		//Add
		if($jobj == null){
			$this->errorMsg(1);
		}

		if(!isset($jobj->sign) || !isset($jobj->reqtime) || !isset($jobj->versionCode)
				|| !isset($jobj->versionName) || !isset($jobj->packageName)){
			$this->errorMsg(1);
		}

                $h_sign = $jobj->sign;
                $rqtime = $jobj->reqtime;
		$vercode = $jobj->versionCode;
		$vername = $jobj->versionName;
		$packname = $jobj->packageName;
	
		$ret = $this->_check1($h_sign,$vername, $rqtime); 
		$this->errorMsg($ret);


		//body parser --
		$jobj = json_decode($body);
		$clsId = $jobj->classid;
		$pgId = $jobj->pageindex;
		$pgsize = $jobj->pagesize;


		$ret = array();

		$this->load->database('vr');
		$sql = 'select r_a, r_b from vr_game_relation where r_a='.$clsId.' limit '.($pgId-1)*$pgsize.", $pgsize";
		$query = $this->db->query($sql);

		$midArr = array();
		foreach ($query->result() as $row)
		{
			$midArr[] = $row->r_b;
		}
		$query->free_result();
		//print_r($midArr);
		//exit;


		
		if(count($midArr) == 0){
			$sql = 'SELECT 0 FROM vr_game where 0'; 
		}
		else{	
			$concase = implode(',', $midArr);
		//$pre = 'http://www.nybgjd.com/srv2/get/img/vrres/';
		$sql = 'SELECT d_id, d_name,   FROM_UNIXTIME(d_addtime,'."'%Y-%m-%d %H:%i:%s' )".' createtime, d_version,  d_pic , d_size,  d_packname, d_downurl, d_hits  FROM vr_game where d_id in ('.$concase.')';	
		}
		
		//exit($sql);
		$query = $this->db->query($sql);	
		$realcnt = 0;
		foreach($query->result() as $row){
			$cif = array();
			$cif['id'] = $row->d_id.'';
			$cif['title'] = $row->d_name;
			$cif['createtime'] = $row->createtime;
			$cif['datatype'] = 'game';
			$cif['version'] = $row->d_version;
			$cif['size'] = $row->d_size;
			$cif['images'] = $row->d_pic;
			$cif['file'] = $row->d_downurl;
			$cif['packagename'] = $row->d_packname;
			$cif['downloadnum'] = $row->d_hits;

			$ret['body']['gameList'][] = $cif;	
			$realcnt ++;
		}
		$query->free_result();
		$this->db->close();

		if($realcnt == 0){
			$ret['body']['gameList'] = null;
		}
		//header
		$ret['header']['sign'] = $h_sign;
		$ret['header']['versionCode'] = $vercode; 
		$ret['header']['versionName'] = $vername; 
		$ret['header']['packageName'] = $packname; 
		$ret['header']['reqtime'] = $rqtime.''; 
		$ret['header']['retMessage'] = 'ok'; 
		$ret['header']['retStatus'] = 200; 		

		//page
		$pgArr = array();
		$pgArr['count'] = 121;
		$pgArr['pageindex'] = intval($pgId);
		$pgArr['pagecount'] = intval($realcnt);
		$ret['page'] = $pgArr;

		$str = json_encode($ret);
		exit($str);
	}	




	function getGameDetail(){
                $header = $this->input->get('header');
                $body = $this->input->get('body');

                //header parser --
                $jobj = json_decode($header);

		//Add
		if($jobj == null){
			$this->errorMsg(1);
		}
		
		if(!isset($jobj->sign) || !isset($jobj->reqtime) || !isset($jobj->versionCode)
			|| !isset($jobj->versionName) || !isset($jobj->packageName)){
			$this->errorMsg(1);
		}

                $h_sign = $jobj->sign;
                $rqtime = $jobj->reqtime;
                $vercode = $jobj->versionCode;
                $vername = $jobj->versionName;
                $packname = $jobj->packageName;

                $ret = $this->_check1($h_sign,$vername, $rqtime);
                $this->errorMsg($ret);


                //body parser --
                $jobj = json_decode($body);
		if($jobj == null || !isset($jobj->id)){
			$this->errorMsg(1);
		}	
		
		$id = $jobj->id;


		$ret = array();

		$this->load->database('vr');
		$sql = 'select d_id, d_title, d_pic, d_file,d_content,d_version, d_size, d_recommend, d_hits,d_img1,d_img2,d_img3,d_img4, d_addtime, d_packname, d_ext_typeid from vr_game_detail where d_id='.$id;
		$query = $this->db->query($sql);

		$cif = array();
		$img = array();
		foreach($query->result() as $row){
			$cif['id'] =  $row->d_id;
			$cif['title'] =  $row->d_title;
			$cif['pic'] =  $row->d_pic;
			$cif['file'] =  $row->d_file;
			$cif['content'] =  $row->d_content;
			$cif['version'] =  $row->d_version;
			$cif['size'] =  $row->d_size;
			$cif['recommends'] =  $row->d_recommend;
			$cif['recommendimg'] =   '';
			$cif['downloadnum'] =  $row->d_hits;
			$cif['addtime'] =  $row->d_addtime;
			$cif['status'] =  '1';
			$cif['packagename'] =  $row->d_packname;
			$cif['classid'] =  $row->d_ext_typeid;
			
			if(!empty($row->d_img1))
				$img[]['img'] = $row->d_img1;
			if(!empty($row->d_img2))
				$img[]['img'] = $row->d_img2;
			if(!empty($row->d_img3))
				$img[]['img'] = $row->d_img3;
			if(!empty($row->d_img4))
				$img[]['img'] = $row->d_img4;
				
			break;
		}
		$query->free_result();
		$this->db->close();

		if(count($cif) > 0){
			$cif['images'] = $img;
			$ret['body']['gameDetail'][] = $cif;	
		}
		else{
			$ret['body']['gameDetail'] = null;
		}

		//header
                $ret['header']['sign'] = $h_sign;
                $ret['header']['versionCode'] = $vercode;
                $ret['header']['versionName'] = $vername;
                $ret['header']['packageName'] = $packname;
                $ret['header']['reqtime'] = $rqtime.'';
                $ret['header']['retMessage'] = 'ok';
                $ret['header']['retStatus'] = 200;


		//page
		$ret['page'] = array();


		$str = json_encode($ret);
		exit($str);
	}	



	function getGameTop(){
		$header = $this->input->get('header');

		//header parser --
		$jobj = json_decode($header);
		//Add
		if($jobj == null){
			$this->errorMsg(1);
		}

		if(!isset($jobj->sign) || !isset($jobj->reqtime) || !isset($jobj->versionCode)
				|| !isset($jobj->versionName) || !isset($jobj->packageName)){
			$this->errorMsg(1);
		}

		$h_sign = $jobj->sign;
		$rqtime = $jobj->reqtime;
		$vercode = $jobj->versionCode;
		$vername = $jobj->versionName;
		$packname = $jobj->packageName;

		$ret = $this->_check1($h_sign,$vername, $rqtime);
		$this->errorMsg($ret);


		$ret = array();

		$this->load->database('vr');
		$sql = 'select count(d_id) cnt from  vr_game';
		$query = $this->db->query($sql);

		$count = 0;
		foreach ($query->result() as $row)
		{
			$count = $row->cnt;
			break;
		}
		$query->free_result();

		//产生变化
		srand($this->_seed());
		$based = rand(0,($count-11));

		$sql = 'SELECT d_id, d_name,   FROM_UNIXTIME(d_addtime,'."'%Y-%m-%d %H:%i:%s' )".' createtime, d_version,  d_pic , d_size,  d_packname, d_downurl, d_hits  FROM vr_game limit '.$based.', 10';
		$query = $this->db->query($sql);
		$realcnt = 0;
		foreach($query->result() as $row){
			$cif = array();
			$cif['id'] = $row->d_id.'';
			$cif['title'] = $row->d_name;
			$cif['createtime'] = $row->createtime;
			$cif['datatype'] = 'game';
			$cif['version'] = $row->d_version;
			$cif['size'] = $row->d_size;
			$cif['images'] = $row->d_pic;
			$cif['file'] = $row->d_downurl;
			$cif['packagename'] = $row->d_packname;
			$cif['downloadnum'] = $row->d_hits;

			$ret['body']['gameList'][] = $cif;
			$realcnt ++;
		}
		$query->free_result();
		$this->db->close();

		if($realcnt == 0){
			$ret['body']['gameList'] = null;
		}
		//header
		$ret['header']['sign'] = $h_sign;
		$ret['header']['versionCode'] = $vercode;
		$ret['header']['versionName'] = $vername;
		$ret['header']['packageName'] = $packname;
		$ret['header']['reqtime'] = $rqtime.'';
		$ret['header']['retMessage'] = 'ok';
		$ret['header']['retStatus'] = 200;


		//page
		$pgArr = array();
		$ret['page'] = $pgArr;

		$str = json_encode($ret);
		exit($str);
	}





	//update game hits
	function statdownload(){
		/*
		   header={"funcId":"","osVersion":"","appId":"","accessToken":"","devType":"2","appVersion":"","retStatus":"","userId":"","devId":"","retMessage":"","userType":"0"}&body={"gameid":101}
		 */
		$body = $this->input->get('body');
		$jobj = json_decode($body);
		$gameid = $jobj->gameid;

		$this->load->database('vr');
		$sql = 'update vr_game set d_hits=(d_hits+1) where d_id='.$gameid;
		$this->db->query($sql);
		$this->db->close();
	}

	//collect info
	function collectLoginInfo(){
		/*
		   header={"funcId":"","osVersion":"","appId":"","accessToken":"","devType":"2","appVersion":"","retStatus":"","userId":"","devId":"","retMessage":"","userType":"0"}&body={"versioncode":"19","buildmodel":"HUAWEI+P7-L00","logintime":"14:07:22","versionname":"2.0.10.10","buildversion":"4.4.2","imei":"357458042052075"}
		 */
		$ret = array();

		$body = array();
		$body["status"] =  "OK";
		$ret['body'] = $body;

		$header = array();
		$header['funcId'] =  '';
		$header['osVersion'] =  '';
		$header['appId'] =  '';
		$header['accessToken'] =  '';
		$header['devType'] =  "2";
		$header['appVersion'] =  '';
		$header['retStatus'] =  200;
		$header['userId'] =  '';
		$header['devId'] =  '';
		$header['retMessage'] =  "ok";
		$header['userType'] =  "0";		
		$ret['header'] = $header;

		$ret['page'] = array();
		echo json_encode($ret);
	}


	//play 统计
	function statplay(){
		/*
		   body={"movieid":"7197"} 
		 */
		$body = $this->input->get('body');
		$jobj = json_decode($body);
		$movieid = $jobj->movieid;

		$this->load->database('vr');
		$sql = 'update vr_vod set d_hits=(d_hits+1) where d_id='.$movieid;
		$this->db->query($sql);
		$this->db->close();
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
