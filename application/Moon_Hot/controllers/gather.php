<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Gather extends CI_Controller {

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
	 * map to /index.php/set/<method_name>
	 * @see http://codeigniter.com/user_guide/general/urls.html
	 */
	function index($incre='update'){
		set_time_limit(0);
		ignore_user_abort(false);
		if($incre == 'update'){
			//增量采集
			log_message('error', '增量采集开始！');  
			$year = date('Y');
			$month = date('m');
			log_message('error', "Today is $year-$month");
			$this->_gather($year, $month);
			
		}
		else if($incre == 'all'){
			//全量采集
			log_message('error', '全量采集开始！');    
			$this->_gatherAll();
		}
		else{
			exit('^V^, 你懂的！');
		}
	}
	
	function _gatherAll(){
		//清除表中所有内容
		//....
		
		
		//-----------
		$endDate = date('Y').sprintf("%02d", date('m'));
		
		$yr = 2011;	$mn = 5;
		$pg = $yr.sprintf("%02d", $mn);
		while($pg <= $endDate){
			//采集
			$this->_gather($yr, $mn);
			
			$mn++;
			if($mn > 12){
				$yr++;
				$mn = 1;
			}
			$pg = $yr.sprintf("%02d", $mn);
		}		
	}

	function _mkdirs($dir){
		if(file_exists($dir))
			return true;
		if(!is_dir($dir)){
			if(!$this->_mkdirs(dirname($dir))){
				return false;
			}
			if(!mkdir($dir,0777)){
				return false;
			}
		}
		return true;
	}
	
	function _rmdirs($dir){
		if(!file_exists($dir))
			return;
		$d = dir($dir);
		while (false !== ($child = $d->read())){
			if($child != '.' && $child != '..'){
				if(is_dir($dir.'/'.$child))
					$this->_rmdirs($dir.'/'.$child);
				else 
					unlink($dir.'/'.$child);
			}
		}
		$d->close();
		rmdir($dir);
	}
	
	/*	 !! must !!!
	 *	$year:  xxxx
	 *  $mon:   x / xx
	 */
	function _gather($year, $mon){
		if(strlen($year)==0 || strlen($mon)==0)
			exit("emtpy args(year=$year, mon=$mon)");
		$mon = sprintf("%02d", $mon);	// ==> xx
		
		//数据库连接
		$this->load->database('video_news');  
		$this->db->trans_strict(TRUE);	//开启严格事务
		
		//先检查是否已经采集。		
		$sql = 'select id from r_year_vid where year_id='.$year.$mon;
		$query = $this->db->query($sql);
		if($query->num_rows() > 0){
			$query->free_result();
			$this->db->close();
			log_message('error', "the task $year-$mon had exist!!!");
			echo ("the task $year-$mon had exist, ignore !!!<br>");
			return;
		}
		
		//配置处理	
		$this->config->load('myCfg');	
		$cacheDir_detail = $this->config->item('cacheDir_detail');
		if($cacheDir_detail == FALSE || empty($cacheDir_detail))
			exit('myCfg.php hava invalid config!!');
		
		$cacheDir_detail = strtr($cacheDir_detail,array('\\'=>'/'));
		log_message('error', 'cacheDir_detail='.$cacheDir_detail);
		
		//检测目录状态，没有则创建,及设置权限
		$cacheDir_detail = $cacheDir_detail.'/'.$year.$mon;
		$this->_rmdirs($cacheDir_detail);
		if($this->_mkdirs($cacheDir_detail) == false)
			exit("create dir $cacheDir_detail failed!!");
		
		//相关库
		$this->load->helper('download');
		$this->load->library('MTask', NULL, 'myDL1');	
		$this->load->library('pinyin');

		
		//下载并解析取listurls		
		$prefix = 'http://www.verycd.com/theater';
		$url = $prefix.'/'.$year.'/'.$mon;
		log_message('error', 'page url:'.$url);

		$con = myDLCurl($url);
		preg_match_all('/<a title=\"[^\"]+\" target=\"_blank\" href=\"([^\"]+)\"/', $con, $vlistTmp);
		if(!isset($vlistTmp[1][0]) or empty($vlistTmp[1][0])){
			exit('获取影片列表url失败！');
		}
		$urlList = $vlistTmp[1];
		unset($vlistTmp);
		
		$detail_prefix = '';
		foreach($urlList as $id=>$value){
			$dest = $cacheDir_detail.'/'.$detail_prefix.sprintf("%03d", $id);
			$src = 'http://www.verycd.com'.$value.'details/';
			
			log_message('error', 'Add task '.$id.':'.$src.' to '.$dest);
			$this->myDL1->add(array($src, $dest),
							  array(array($this, '_hdler_detail_ok'), array($id, $dest, $year.$mon)),
							  array(array($this, '_hdler_detail_err'))
							  );
		
		}
		unset($urlList);
		
		$this->benchmark->mark('tsk_b');
		
		//////////// go  //////////////
		//阻塞,只有等所有handler函数执行完毕后，才可执行该语句之后的代码
		$this->myDL1->go();	
		/////////////////////////////
		//exit;
		$this->benchmark->mark('tsk_e');
		$spttime = $this->benchmark->elapsed_time('tsk_b', 'tsk_e');
		log_message('error', 'all download task ok, spent sum:'.$spttime);
		
		//入库及清空
		$this->db->trans_begin();
		
		//tbl_title.txt
		$sqlfile = $cacheDir_detail.'/tbl_title.txt';
		if(file_exists($sqlfile)){
			$sql = 'load data infile \''.$sqlfile.'\' replace into table tbl_title character set utf8 fields terminated by \',\' enclosed by \'"\' lines terminated by \'\n\' (`id`,`title`,`vsort`)';		
			$this->db->query($sql);
			log_message('error', 'Load Finished: tbl_title.txt');
		}
		else{
			log_message('error', "N_EXIST: $sqlfile");
		}
		
		//tbl_initial.txt
		$sqlfile = $cacheDir_detail.'/tbl_initial.txt';
		if(file_exists($sqlfile)){
			$sql = 'load data infile \''.$sqlfile.'\' replace into table tbl_initial character set utf8 fields terminated by \',\' enclosed by \'"\' lines terminated by \'\n\' (`id`,`initial`)';		
			$this->db->query($sql);
			log_message('error', 'Load Finished: tbl_initial.txt');		
		}
		else{
			log_message('error', "N_EXIST: $sqlfile");
		}
		
		//tbl_info.txt
		$sqlfile = $cacheDir_detail.'/tbl_info.txt';
		if(file_exists($sqlfile)){
			$sql = 'load data infile \''.$sqlfile.'\' replace into table tbl_info character set utf8 fields terminated by \',\' enclosed by \'"\' lines terminated by \'\n\' (`id`,`title`,`img`,`showdate`,`director`,`actor`,`type`,`area`,`mark`,`intro`,`vsort`)';		
			$this->db->query($sql);
			log_message('error', 'Load Finished: tbl_info.txt');			
		}
		else{
			log_message('error', "N_EXIST: $sqlfile");
		}		
		
		//r_type_vid.txt
		$sqlfile = $cacheDir_detail.'/r_type_vid.txt';
		if(file_exists($sqlfile)){
			$sql = 'load data infile \''.$sqlfile.'\' replace into table r_type_vid character set utf8 fields terminated by \',\' enclosed by \'"\' lines terminated by \'\n\' (`type_id`,`vid`)';		
			$this->db->query($sql);
			log_message('error', 'Load Finished: r_type_vid.txt');			
		}
		else{
			log_message('error', "N_EXIST: $sqlfile");
		}	

		//r_director_vid.txt
		$sqlfile = $cacheDir_detail.'/r_director_vid.txt';
		if(file_exists($sqlfile)){
			$sql = 'load data infile \''.$sqlfile.'\' replace into table r_director_vid character set utf8 fields terminated by \',\' enclosed by \'"\' lines terminated by \'\n\' (`director_id`,`vid`)';		
			$this->db->query($sql);
			log_message('error', 'Load Finished: r_director_vid.txt');			
		}
		else{
			log_message('error', "N_EXIST: $sqlfile");
		}	

		//r_area_vid.txt
		$sqlfile = $cacheDir_detail.'/r_area_vid.txt';
		if(file_exists($sqlfile)){
			$sql = 'load data infile \''.$sqlfile.'\' replace into table r_area_vid character set utf8 fields terminated by \',\' enclosed by \'"\' lines terminated by \'\n\' (`area_id`,`vid`)';		
			$this->db->query($sql);
			log_message('error', 'Load Finished: r_area_vid.txt');			
		}
		else{
			log_message('error', "N_EXIST: $sqlfile");
		}	

		//r_year_vid.txt
		$sqlfile = $cacheDir_detail.'/r_year_vid.txt';
		if(file_exists($sqlfile)){
			$sql = 'load data infile \''.$sqlfile.'\' replace into table r_year_vid character set utf8 fields terminated by \',\' enclosed by \'"\' lines terminated by \'\n\' (`year_id`,`vid`)';		
			$this->db->query($sql);
			log_message('error', 'Load Finished: r_year_vid.txt');			
		}
		else{
			log_message('error', "N_EXIST: $sqlfile");
		}			
		
		//r_actor_vid.txt
		$sqlfile = $cacheDir_detail.'/r_actor_vid.txt';
		if(file_exists($sqlfile)){
			$sql = 'load data infile \''.$sqlfile.'\' replace into table r_actor_vid character set utf8 fields terminated by \',\' enclosed by \'"\' lines terminated by \'\n\' (`actor_id`,`vid`)';		
			$this->db->query($sql);
			log_message('error', 'Load Finished: r_actor_vid.txt');			
		}
		else{
			log_message('error', "N_EXIST: $sqlfile");
		}	
		
		if ($this->db->trans_status() === FALSE){
			$this->db->trans_rollback();
			log_message('error', 'TRANS ROLLBACK!!!');
		}
		else{
			$this->db->trans_commit();
			log_message('error', 'TRANS COMMIT!!!');
		}	
		$this->db->close();
		
		log_message('error', "Task Finished: $year-$mon\n\n");
		echo("Task Finished: $year-$mon<br>");
	}
	
	function _hdler_detail_ok($info, $id, $dest, $year){
		$this->benchmark->mark('code_start');
		
		$con = file_get_contents($dest);
				
		// img, title
		$title = $img = '';
		preg_match_all('/<img class=\"cover_img\" width=\"\d+\" height=\"\d+\" src=\"([^\"]+)\" alt=\"([^\"]+)\"/', $con, $imgTitleTmp);
		if(isset($imgTitleTmp[1][0]) && !empty($imgTitleTmp[1][0])){
			$title = $imgTitleTmp[2][0];
			$img = $imgTitleTmp[1][0];
			unset($imgTitleTmp);
		}
		if(empty($title) or empty($img)){
			log_message('error', "Error: title:$title, img:$img");
			exit;
		}
		$title = strtr($title, array('"'=>"'"));
		
		// 类型
		$typeArr = array();
		preg_match_all('/<font>类型：<\/font><em>.+?<\/em>/', $con, $typeBlockTmp);
		if(isset($typeBlockTmp[0][0]) && !empty($typeBlockTmp[0][0])){
			preg_match_all('/\">(.+?)<\/a>/', $typeBlockTmp[0][0], $typeArrTmp);
			if(isset($typeArrTmp[1][0]) && !empty($typeArrTmp[1][0])){
				$typeArr = $typeArrTmp[1];
				unset($typeArrTmp);
			}
			unset($typeBlockTmp);
		}
		if(count($typeArr) == 0){
			$typeArr[] = '其它';
			log_message('error', "Warnning: we set type 其它 !!");
		}

		// 上映日期
		$showDate = '';
		preg_match_all('/<font>上映日期：(.*?)<\/li>/', $con, $showBlockTmp);
		if(isset($showBlockTmp[0][0]) && !empty($showBlockTmp[0][0])){
			preg_match_all('/<em>(.*?)<\/em>/', $showBlockTmp[0][0], $showArrTmp);
			if(isset($showArrTmp[1][0]) && !empty($showArrTmp[1][0])){
				$showArr = $showArrTmp[1];
				unset($showArrTmp);
				
				if(isset($showArr[0])){
					$showDate = implode('，',$showArr);
				}
			}
			unset($showBlockTmp);
		}		
		//echo $showDate."<br>";
		if(empty($showDate)){
			$showDate = '不详';
			log_message('error', 'Warnning: showDate is set 不详!!');
		}
		$showDate = strtr($showDate, array('"'=>"'"));
		
		//导演
		$drctArr = array();
		preg_match_all('/<font>导演：<\/font><em>.*?<\/em>/', $con, $drctBlockTmp);
		if(isset($drctBlockTmp[0][0]) && !empty($drctBlockTmp[0][0])){
			preg_match_all('/\">(.+?)<\/a>/', $drctBlockTmp[0][0], $drctArrTmp);
			if(isset($drctArrTmp[1][0]) && !empty($drctArrTmp[1][0])){
				$drctArr = $drctArrTmp[1];
				unset($drctArrTmp);
			}
			unset($drctBlockTmp);
		}
		if(count($drctArr) ==0){
			$drctArr[] = '不详';
			log_message('error', 'Warnning: director we set it 不详');
		}
		
		//演员
		$actorArr = array();
		preg_match_all('/<font>演员：<\/font><em>.*?<\/em>/', $con, $actortArrBlockTmp);
		if(isset($actortArrBlockTmp[0][0]) && !empty($actortArrBlockTmp[0][0])){
			preg_match_all('/\">(.+?)<\/a>/', $actortArrBlockTmp[0][0], $actortArrTmp);
			if(isset($actortArrTmp[1][0]) && !empty($actortArrTmp[1][0])){
				$actorArr = $actortArrTmp[1];
				unset($actortArrTmp);
			}
			unset($actortArrBlockTmp);
		}
		if(count($actorArr) ==0){
			$actorArr[] = '不详';
			log_message('error', 'Warnning: actor we set it 不详');
		}		
		
		
		//地区
		$area = '';
		preg_match_all('/<font>地区：<\/font><em>.*?<\/em>/', $con, $areaBlockTmp);
		if(isset($areaBlockTmp[0]) && !empty($areaBlockTmp[0])){
			preg_match_all('/\">(.+?)<\/a>/', $areaBlockTmp[0][0], $areaTmp);
			if(isset($areaTmp[1][0]) && !empty($areaTmp[1][0])){
				$area = $areaTmp[1][0];
				unset($areaTmp);
			}
			unset($areaBlockTmp);
		}
		if(empty($area)){
			$area = '不详';
			log_message('error', 'Warnning: area we set it 不详');
		}				
		$area = strtr($area, array('"'=>"'"));
		
		if(empty($year)){
			$year = 0;
			log_message('error', 'year is null, we set it 0');
		}
		
		//简介
		$intro = '';
		preg_match_all('/<p>(.*?)<\/p>/', $con, $introTmp);
		if(isset($introTmp[1][0]))
			$intro = $introTmp[1][0];
		unset($introTmp);
		
		if(empty($intro)){
			$intro = '暂无';
			log_message('error', 'intro is null, we set it 暂无');
		}
		$intro = strtr($intro, array('"'=>"'"));
		
		
		//由于需要很方便的获得其id，所以设计area/type/..字段为唯一字段。
		//tbl_area,
		$areaStr = $area;
		$area_id = $this->_db_insert_gps('tbl_area', 'area', $area);
		if($area_id == 0){
			//log_message('error', '');
			exit('_db_insert_gps for tbl_area error!!');
		}
		
		//tbl_type
		$typeStr = '';		
		$type_idArr = array();
		foreach($typeArr as $key=>$value){
			$value = strtr($value, array('"'=>"'"));
			$type_id = $this->_db_insert_gps('tbl_type', 'type', $value);
			if($type_id == 0){
				//log_message('error', '');
				exit('_db_insert_gps for tbl_type error!!');
			}
			$type_idArr[] = $type_id;
			$typeStr = $typeStr.' / '.$value;
		}	
		$typeStr = trim($typeStr, ' /');
		
		//tbl_director
		$directorStr = '';
		$director_idArr = array();
		foreach($drctArr as $key=>$value){
			$value = strtr($value, array('"'=>"'"));
			$director_id = $this->_db_insert_gps('tbl_director', 'director', $value);
			if($director_id == 0){
				//log_message('error', '');
				exit('_db_insert_gps for tbl_director error!!');
			}
			$director_idArr[] = $director_id;
			$directorStr = $directorStr.' / '.$value;
		}
		$directorStr = trim($directorStr, ' /');
		
		//tbl_actor
		$actorStr = '';
		$actor_idArr = array();
		foreach($actorArr as $key=>$value){
			$value = strtr($value, array('"'=>"'"));
			$actor_id = $this->_db_insert_gps('tbl_actor', 'actor', $value);
			if($actor_id == 0){
				//log_message('error', '');
				exit('_db_insert_gps for tbl_actor error!!');
			}
			$actor_idArr[] = $actor_id;
			$actorStr = $actorStr.' / '.$value;
		}
		$actorStr = trim($actorStr, ' /');
		
		
		$dirpath = dirname($dest);		

		//我们需要vid，但又想保证影片基本信息的一致性，所以我们创建了一个和tbl_title一样的表tbl_title_check，通过对它的预插入获得vid，该表无实际应用
		//   ****** 但是每次使用前，需要初始化和 tbl_title一致。*****
		//为方便获得vid我们将 tbl_title的title列设为唯一
		$sql = "insert into tbl_title_check (title) values ('". mysql_escape_string($title) ."') ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id)";
		log_message('error', "SQL: $sql");
		$this->db->query($sql);
		
		$query = $this->db->query('select LAST_INSERT_ID() as vid');
		foreach($query->result() as $row){
			$vid = $row->vid;
			unset($row);
			$query->free_result();
			break;
		}
		if(!isset($vid) || $vid == 0){

			$this->db->close();
			exit("tbl_title_check get vid error!!");
		}
		
		//tbl_title.txt
		$tbl_title = $dirpath.'/'.'tbl_title.txt';		
		$this->_appendwrite($tbl_title, '"'.$vid.'","'.$title.'","'.$id.'"'."\n");
		
		//tbl_initial.txt
		$tbl_initial = $dirpath.'/'.'tbl_initial.txt';	
		$initialStr = $this->pinyin->initial(iconv( 'utf-8', 'gbk', $title));
		if(empty($initialStr)){
			log_message('error', "Initial Get error for $title");
			$initialStr = 'abc';
		}
		$this->_appendwrite($tbl_initial, '"'.$vid.'","'.$initialStr.'"'."\n");		
		
		//tbl_info.txt
		$tbl_info = $dirpath.'/'.'tbl_info.txt';
		$this->_appendwrite($tbl_info, '"'.$vid.'","'.$title.'","'.$img.'","'.$showDate.'","'.$directorStr.'","'.$actorStr.'","'.$typeStr.'","'.$areaStr.'","0.0","'.$intro.'","'.$id.'"'."\n");
		
		//r_year_vid.txt
		$r_year_vid = $dirpath.'/'.'r_year_vid.txt';	
		$this->_appendwrite($r_year_vid, '"'.$year.'","'.$vid.'"'."\n");	
		
		
		//r_director_vid.txt
		$r_director_vid = $dirpath.'/'.'r_director_vid.txt';	
		foreach($director_idArr as $value){
			$this->_appendwrite($r_director_vid, '"'.$value.'","'.$vid.'"'."\n");	
		}
		
		//r_area_vid.txt
		$r_area_vid = $dirpath.'/'.'r_area_vid.txt';	
		$this->_appendwrite($r_area_vid, '"'.$area_id.'","'.$vid.'"'."\n");	
		
		//r_actor_vid.txt
		$r_actor_vid = $dirpath.'/'.'r_actor_vid.txt';	
		foreach($actor_idArr as $value){
			$this->_appendwrite($r_actor_vid, '"'.$value.'","'.$vid.'"'."\n");	
		}
		
		//r_type_vid.txt
		$r_type_vid = $dirpath.'/'.'r_type_vid.txt';	
		foreach($type_idArr as $value){
			$this->_appendwrite($r_type_vid, '"'.$value.'","'.$vid.'"'."\n");	
		}		
		unset($con);
		
		$this->benchmark->mark('code_end');	
		$spttime = $this->benchmark->elapsed_time('code_start', 'code_end');
		log_message('error', "spent time ($spttime): dl [$id] to $dest ok!\n\n");

	}
	
	
	
	
	
	//注意 last_insert_id与 duplicate key update 的联合使用
	//适应于 tbl_area, tbl_type, tbl_director, tbl_actor
	function _db_insert_gps($tblName, $colName, $value){
		$cid = 0;
		$sql = "insert into $tblName ($colName) values ('".mysql_escape_string($value)."') ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id)";
		log_message('error', "SQL: $sql");
		$this->db->query($sql);
		
		$sql = 'select LAST_INSERT_ID() as id';
		$query = $this->db->query($sql);
		foreach($query->result() as $row){		//切忌：不可以用 if($row = $query->result()) !!!!!
			$cid = $row->id;
			unset($row);
			$query->free_result();
			break;
		}
		return $cid;
	}		
		
	function _hdler_detail_err($info){
		log_message('error', "download [$id] to $dest error!");
	}

	function _appendWrite($file, $data){
         if(!$fso=fopen($file,'a+')){
             return false;
         }
         if(!flock($fso,LOCK_EX)){//LOCK_NB,排它型锁定
             return false;
         }
         if(!fwrite($fso,$data)){//写入字节流,serialize写入其他格式
             return false;
         }
		 log_message('error', basename($file).':'.$data);
         flock($fso,LOCK_UN);//释放锁定
         fclose($fso);
         return true;
    }	
	
}

/* End of file geturl.php */
/* Location: ./controllers/set/geturl.php */
