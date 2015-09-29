<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class PlayUrl extends CI_Controller {

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
		$sql = 'select t3.id,t3.title from r_year_vid t1 left join tbl_playurl t2 on t1.vid=t2.vid
					left join tbl_title t3 on t1.vid=t3.id 
					where t1.year_id='.$year.$mon.' and t2.id is null';
		$query = $this->db->query($sql);
		$this->db->close();
		if($query->num_rows() == 0){
			$this->db->close();
			log_message('error', "the task $year-$mon had all complate!");
			echo ("the task $year-$mon had all complate!<br>");
			return;
		}
		
		//配置处理	
		$this->config->load('myCfg');	
		$cacheDir_url = $this->config->item('cacheDir_url');
		if($cacheDir_url == FALSE || empty($cacheDir_url))
			exit('myCfg.php hava invalid config!!');
		
		$cacheDir_url = strtr($cacheDir_url,array('\\'=>'/'));
		log_message('error', 'cacheDir_url='.$cacheDir_url);
		
		//检测目录状态，没有则创建,及设置权限
		$cacheDir_url = $cacheDir_url.'/'.$year.$mon;
		$this->_rmdirs($cacheDir_url);
		if($this->_mkdirs($cacheDir_url) == false)
			exit("create dir $cacheDir_url failed!!");
		
		//相关库
		$this->load->helper('download');
		$this->load->library('MTask', NULL, 'myDL1');	
		$this->load->helper('url');

		
		//下载并解析取listurls		
		$prefix = 'http://www.9skb.com/?k=';
		foreach($query->result() as $row){
			$vid = $row->id;
			$entitle = urlencode( $row->title);   //urlencode(mb_convert_encoding($url, 'utf-8', 'gb2312'))"
			$url = $prefix . $entitle;
			log_message('error', "\n\n".'##### Search '.$row->title.', url:'.$url);
			
			$con = myDLCurl($url);
			preg_match_all('/target=\"_blank\" title=\"(.*?)\">.*?<em>(.*?)<\/em>MB<\/div>.*?<a href=\"([^\"]+)\"/', $con, $urlInfoTmp);
			if(!isset($urlInfoTmp[1][0]) or empty($urlInfoTmp[1][0])){
				log_message('error', 'have no urls for '.$row->title.', skip!!!');
				continue;
			}	
			
			$titleArr = $urlInfoTmp[1];
			$sizeArr = $urlInfoTmp[2];
			$urlList = $urlInfoTmp[3];
			unset($urlInfoTmp);			
			
			$url_prefix = '';
			foreach($urlList as $id=>$url){
				$dest = $cacheDir_url.'/'.$url_prefix.sprintf("%03d", $id);
				$src = 'http://www.9skb.com'.$url;  // 播放页的索引页的url
				
				log_message('error', '  get all url for ['.$row->title.']==>> '.$vid.'-'.$id.':'.$src.' to '.$dest);
				$str = iconv('GB2312', 'UTF-8', $titleArr[$id]);
				$this->myDL1->add(array($src, $dest),
								  array(array($this, '_hdler_url_ok'), array($src, $dest, $vid, $str)),
								  array(array($this, '_hdler_url_err'), array($vid,$str,$src))
								  );
			}
			log_message('error', "\n\n");		  
			$this->myDL1->go();	
		}
		
		$this->benchmark->mark('tsk_b');

		log_message('error', "all movie url had Finished!!\n\n");
		echo("all movie url had Finished!! <br>");
	}
	
	function _hdler_url_ok($info, $src, $dest, $vid, $title){
		log_message('error', "dl [$vid]-$title-$src to $dest finished!!");
		$this->benchmark->mark('code_start');
		$con = file_get_contents($dest);
		
		$urlArr = array();
		preg_match_all('/urls\.push\(\"([^\"]+)\"\)/', $con, $urlsTmp);
		if(isset($urlsTmp[1][0]) && !empty($urlsTmp[1][0])){
			$urlArr = $urlsTmp[1];
			unset($urlsTmp);
		}
		if(count($urlArr) == 0){
			log_message('error', "Error: have no play page for [$title]-[$src], return !!!");
			return;
		}
		unset($con);
		
		$this->load->helper('download');
		
		//分析url的真实地址
		//2>/dev/null
		$cmd = 'D:\useful_tools\phantomjs-1.9.1-windows\phantomjs.exe D:\useful_tools\phantomjs-1.9.1-windows\geturlcon.js ';
		foreach($urlArr as $url){
			log_message('error', "\n to handler [$title] ==> $url");
			
			$retstr = system($cmd.$url);
			if($retstr && !empty($retstr)){
				$bpos = stripos($retstr, 'qvod://', 0);
				if($bpos === FALSE)
					continue;
				$epos = strripos($retstr,'|');
				if($epos === FALSE)
					continue;
				$qvodUrl = substr($retstr, $bpos, $epos+1);
				log_message('error', 'we get qvodurl:'.$qvodUrl."\n");
				
				break;
			}
			
			/*
			///////////////////////////////////////////////////////////
			$url = 'www.zaradvd.com/vod/41392/play.html?41392-0-12';
			////////////////////////////////////////////////////
			
			$con = myDLCurl($url);
			if(empty($con))
				continue;
			preg_match_all('/qvod%3A%2F%2F[^\"]+/', $con, $qBlockTmp);
			if(!isset($qBlockTmp[0][0]) or !empty($qBlockTmp[0][0])){
				log_message('error', 'rexEx(/qvod%3A%2F%2F[^\"]+/) have no matched!!');
				preg_match_all('/qvod:\/\/[^\"]+/', $con, $qBlockTmp);
				
				if(!isset($qBlockTmp[0][0]) or !empty($qBlockTmp[0][0])){
					log_message('error', 'rexEx(/qvod:\/\/[^\"]+/) have no matched!!, continue!!');
					continue;
				}
			}
			else{
				$qBlockTmp[0][0] = urldecode($qBlockTmp[0][0]);
			}
			
			pre_match_all('/qvod\:\/\/\d+\|[0-9a-zA-Z]+\|[^\|]+\|/', $qBlockTmp[0][0], $qurlArr);
			if(!isset($qurlArr[0][0]) || empty($qurlArr[0][0])){
				log_message('error', 'rexEx(/qvod\:\/\/\d+\|[0-9a-zA-Z]+\|[^\|]+\|/) have no matched!!, continue!!');
				continue;
			}
			
			$urlArr = $qurlArr[0][0];
			unset($qBlockTmp); 
			unset($qurlArr);
			
			$cnt = count($urlArr);
			$idx = 0;
			if($cnt > 1){
				// 分析 $url
				
			}
			
			$rtUrl = $urlArr["$idx"];
			*/
		}
		
		$this->benchmark->mark('code_end');	
		$spttime = $this->benchmark->elapsed_time('code_start', 'code_end');
		log_message('error', "spent time ($spttime): dl $title-[$src] ok!\n\n");

	}


	function _hdler_url_err($info, $vid, $title, $src){
		log_message('error', "download [$vid]-$title-$src error!");
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