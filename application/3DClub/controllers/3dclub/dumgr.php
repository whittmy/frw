<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class DuMgr extends CI_Controller {

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
		$this->load->library('DuUtil');
		$this->duutil->mkdir('whittmy', 'test222');



		exit('index');
	}
 
	//内定信息
	private $_client_id = 'Ar4jru4mrAoHI8nTudiBCAfa';
	private $_client_secret = 'S2xkwtQZe0dOG8UhrbTbO18TzRHCnZOO';
	//private $_appName = 'disk1';	//应用目录名,应该是pcs path
	private $urlbase = 'http://www.nybgjd.com/3dclub/dumgr/';
	private $_redirect = 'http://www.nybgjd.com/3dclub/dumgr/auth_callback/';
	
	//应用根目录
    private $root_dir = '/apps/disk1/';


	private $_debug = true;
	
	function logger($msg){
		if($this->_debug && $msg!=null){
			log_message('error', $msg);
		}
	}


	function _getUrlContent($url, $header, $cookiefile=null){
		$this->logger('fun__getUrlContent: url='.$url);
		
		$curl = curl_init();  // 初始化一个 cURL 对象
		curl_setopt($curl, CURLOPT_URL, $url);  // 设置你需要抓取的URL  
		if($header != null)
			curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
		curl_setopt($curl, CURLOPT_HEADER, 0);
		curl_setopt($curl, CURLOPT_TIMEOUT,30);
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION , true);    //重定向问题
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);  // 设置cURL 参数，要求结果保存到字符串中还是输出到屏幕上。
		if($cookiefile!=null && strlen($cookiefile)>0){
			curl_setopt($curl, CURLOPT_COOKIEJAR, $this->m_curDir.'shandong.cookie');
			curl_setopt($curl, CURLOPT_COOKIEFILE, $this->m_curDir.'shandong.cookie');
		}
		$rtdata = curl_exec($curl);  // 运行cURL，请求网页
		curl_close($curl);  // 关闭URL请求      
		return $rtdata;
	}	
	



	/////////////////////////////////////////////////////////////////////////
	///////	授权网盘账户操作，平时很少用，当要授权新的百度云账号时使用
	//// 注意事项： 
	//	1. 访问 http://www.nybgjd.com/fserver/api/auth/账号名  之前，请先从浏览器中退出已经登录的百度账号
	//	2. 确认退出后，再用待授权的账号登录
	//	3. 确认登录成功，然后访问 上面第一条中的地址, 如果成功会返回信息，并会将信息自动更新数据库
	//	4. 如果帐户名有中文，请先url编码
	//	5. 如果没有按照上面事先退出账号，很有可能会将账号信息搞的很混乱，切记
	/////////////////////////////////////////////////////////////////////////
	//注意：参数username主要用于标识toke用，因为token太长，而且不容易识别。
	// 		但注意：username必须要与授权的帐号人为的对应上。
	function auth($username=null){
		$ok = '{"status": 1}';
		$err = '{"status":-1}';
		
		$this->logger('fun_auth: user='.$username);
		
		if($username==null || empty($username)){
			$this->logger('must set username!');
			exit($err);
		}
		
		//$username = urldecode($username);
		
		$this->load->library('session');	
		$this->session->set_flashdata('auth_user', $username);
		
		$authurl = 'http://openapi.baidu.com/oauth/2.0/authorize?client_id='
				.$this->_client_id
				.'&user='.$username
				.'&response_type=code&scope=netdisk&redirect_uri='	// 必须加&scope=netdisk项，否则连选择‘读写百度云’的机会都没有
				.$this->_redirect/*'obb'*/;
				
		$this->logger('fun_auth: authurl='.$authurl);		
		header('Location: '.$authurl);
		//echo $this->_getUrlContent($authurl, null);
	}
		
	//该函数在调用认证接口后，自动回调该函数, 将不主动调用该函数
	function auth_callback($auth_info=''){
		$this->load->library('DuUtil');
		$this->duutil->auth_callback('');
	}
	/////////////////////////////////////////////////////////////////////////	
	//////////////////////////////////////////////////////////////////////////////////////////////////////


	/////////////////// 基础接口 //////////////////////////////////////////////////////////////////////
	// user: 某token的标识用户
	// filepath:  相对于 'disk1' 的相对路径，先base64_encode编码
	//如 disk1/ab/1/a.apk	==> filepath:  ......
	function download($user, $filePath){
		$this->load->library('DuUtil');
		$this->duutil->download($user, $filePath);
	}
	
	function mkdir($user, $dirpath){
			$this->load->library('DuUtil');
		$this->duutil->mkdir($user, $dirpath);
	}
	
	function quota_info($user){
		$this->load->library('DuUtil');
		$this->duutil->quota_info($user);
	}
	
	/*
	 * @param string $by 排序字段，缺省根据文件类型排序，time（修改时间），name（文件名），size（大小，注意目录无大小）
	 * @param string $order asc或desc，缺省采用降序排序
	 * @param string $limit 返回条目控制，参数格式为：n1-n2。返回结果集的[n1, n2)之间的条目，缺省('0-0')返回所有条目。n1从0开始。
	*/	
	function list_files($user, $dirpath=null, $is_abspath=0, $by='time', $order='asc', $limit='0-0'){
		$this->load->library('DuUtil');
		$this->duutil->list_files($user, $dirpath, $is_abspath, $by, $order, $limit);
	}

	/*
		返回null，失败
		1: 目录
		0： 文件
	*/
	function is_dir($user, $path){
		$this->load->library('DuUtil');
		return $this->duutil->is_dir($user, $path);
	}
	
	function upload($user, $localfile, $destPath){
		$this->load->library('DuUtil');
		$this->duutil->upload($user, $localfile, $destPath);
	}


	//秒传文件
	function quickCopy($user, $fullmd5, $fullcrc32, $filesize,$slicemd5, $destname){
		$this->load->library('DuUtil');
		$this->duutil->quickCopy($user, $fullmd5, $fullcrc32, $filesize,$slicemd5, $destname);
	}
	
	
	
	
	//////////////////////////////////////// 采集相关 ////////////////////////////////////
	//获取待采集的账户
	// 数组
	function getusers(){
		$this->logger("fun_getusers: into");
		$this->load->database('vr');
		$sql = 'select username from `du_pcs_auth_list`';	// where bactivate=1
		$query = $this->db->query($sql);
		
		$users = array();
		foreach($query->result() as $row){
			$users[] = $row->username;
		}
		$query->free_result();		
		$this->db->close();
		
		$this->logger("fun_getusers: users=".implode(',', $users));
		return $users;
	}
	
	// 获取dirpath目录下 所有目录，不含子目录
	// dirpath：相对路径
	function getDirs($user, $dirpath=null, $is_abspath=0){
		$this->logger("fun_getDirs: user=$user, dirpath=$dirpath, is_abspath=$is_abspath");
		//按大小，升序，即目录排在最前面
		$str = $this->list_files($user, $dirpath, $is_abspath, 'size');	
		if($str == null){
			$this->logger("fun_getDirs: list_files  is null");
			return null;
		}
		$jobj = json_decode($str);
		if($jobj == null || !isset($jobj->list)){
			$this->logger("fun_getDirs: json is invalid");
			return null;
		}
		$rt = array();
		foreach($jobj->list as $item){
			//echo $item->path."<br>";
			if($this->is_dir($user, $item->path) == 1){
				$rt[] = $item->path;
			}
			else{
				break;
			}
		}
		
		$this->logger("fun_getDirs: rslt=".implode(',', $rt));
		//print_r($rt);
		return $rt;
	} 


	/*
		cntIndir:每个目录中要获取的'文件'大概数目,要多采集些，免得有把目录的个数也算成了文件
		-1：全部
		0： 忽略
	*/
	function gather($cntIndir=-1){
		$this->logger("fun_gather: cntIndir=$cntIndir");
		$users = $this->getusers();

		foreach($users as $user){
			//先获取顶级目录下的个目录	
			echo 'url:"'.$this->urlbase.'print_file_list/'.$user.'/'.base64_encode($this->root_dir).'/'.$cntIndir.'/1'.'",'."\n";
			$dirs = $this->getDirs($user);
			foreach($dirs as $dir){
				//$cate = rtrim($dir, '/');
				//$cate = substr($cate, strrpos ($cate,'/')+1);
				echo 'url:"'.$this->urlbase.'print_file_list/'.$user.'/'.base64_encode($dir).'/'.$cntIndir.'/1'.'",'."\n";
			}
		}
	}


	//服务于采集输出
	//按名称，从高到低排序， 即新增的影片排在前面
	//cntIndir =0:忽略
	function print_file_list($user, $dirpath=null, $cntIndir=-1, $is_abspath=0 ){
		$this->logger("fun_print_file_list: user=$user, dirpath=$dirpath, cntIndir=$cntIndir, is_abspath=$is_abspath");
		
		$limit = "";
		if($cntIndir == -1){
			$limit = '0-0';
		}
		else if($cntIndir == 0){
			$this->logger("fun_print_file_list: ignore (because cntIndir==0)");
			return;
		}
		else{
			$limit = '0-'.$cntIndir;
		}
		echo $this->list_files($user, $dirpath, $is_abspath, 'name', 'desc', $limit);
	}


	
	//批量影片入库，如下格式
	//0001_小叮当羽翼之谜.MP4;CRC: C4C64CC8, 32: B89DF61929AFEF492FD1D18849A62C42,part32: 463B05E13FF300E3E2555D93406397A3,size:  1258088549                
	//0002_小海龟大冒险2.MP4;CRC: AB546241, 32: 1C43FD8CC2C69CD35AAC3391E6953937, part32: 21A4E0D7E1C6B660E9F7A05A3FE4398F,size:  1022433167  
	function bat_mv2Db($filename=null){
		$con = file_get_contents('/a/domains/other.nybgjd.com/public_html/misc/3dclub_tmp/'.$filename);
		 
		if($con == null){
			$this->logger("bat_mv2Db: $filepath, conntent is null");
			return;
		}
		 
		//$con = '0001_小叮当羽翼之谜.MP4;CRC: C4C64CC8, 32: B89DF61929AFEF492FD1D18849A62C42,part32: 463B05E13FF300E3E2555D93406397A3,size:  1258088549';
		$preg = '/\s*([^;]+);\s*CRC\:\s*([^,]+),\s*32\:\s*([^,]+),\s*part32\:\s([^,]+),\s*size\:\s*(\d+)/';

		preg_match_all($preg, $con, $tmp);
		if(!isset($tmp[1][0]) || !isset($tmp[2][0]) || !isset($tmp[3][0]) || !isset($tmp[4][0]) || !isset($tmp[5][0])){
			$this->logger("bat_mv2Db: preg error");
			return;
		}
		
		$this->load->database('vr');
		$sql = 'insert into vr_test(fid, name, fullmd5,fullcrc32,file_size,slicemd5) values ';	 
		
		$bupdate = false;
		$sz = count($tmp[1]);
		for($i=0; $i < $sz; $i++){
			$fid = 0;
			
			$pos = strrpos($tmp[1][$i], '.');
			$name =  substr($tmp[1][$i], 0,  ($pos===false)?strlen($tmp[1][$i]) : $pos); 
			$crc = $tmp[2][$i];
			$fullmd5 = $tmp[3][$i];
			$partmd5 = $tmp[4][$i];
			$size = $tmp[5][$i];
			
			$arr = explode("_",$name);
			$fid = intval($arr[0]);
			$name = $arr[1];
			
			$sql = $sql ."($fid, '$name', '$fullmd5','$crc',$size,'$partmd5'), ";
			$bupdate = true;
		}
		unset($tmp);
		
		if(!$bupdate){
			$this->logger("bat_mv2Db: 无sql数据");
			$this->db->close();
			return;
		}
		$sql = trim($sql, ', ');
		
		$this->db->query($sql);
		$this->db->close();
		
		echo 'finished';
	}
	//http://www.nybgjd.com/3dclub/dumgr/bat_game2Db/...
	function bat_game2Db($filename=null){
		$con = file_get_contents('/a/domains/other.nybgjd.com/public_html/misc/3dclub_tmp/'.$filename);
		 
		if($con == null){
			$this->logger("bat_game2Db: $filepath, conntent is null");
			return;
		}
		 
		//$con = '0001_小叮当羽翼之谜.MP4;CRC: C4C64CC8, 32: B89DF61929AFEF492FD1D18849A62C42,part32: 463B05E13FF300E3E2555D93406397A3,size:  1258088549';
		$preg = '/\s*([^;]+);\s*CRC\:\s*([^,]+),\s*32\:\s*([^,]+),\s*part32\:\s([^,]+),\s*size\:\s*(\d+)/';

		preg_match_all($preg, $con, $tmp);
		if(!isset($tmp[1][0]) || !isset($tmp[2][0]) || !isset($tmp[3][0]) || !isset($tmp[4][0]) || !isset($tmp[5][0])){
			$this->logger("bat_game2Db: preg error");
			return;
		}
		
		$this->load->database('vr');
		$sql = 'insert into vr_data_apk(fid, name, fullmd5,fullcrc32,file_size,slicemd5) values ';	 
		
		$bupdate = false;
		$sz = count($tmp[1]);
		for($i=0; $i < $sz; $i++){
			$fid = 0;
			
			$pos = strrpos($tmp[1][$i], '.');
			$name =  substr($tmp[1][$i], 0,  ($pos===false)?strlen($tmp[1][$i]) : $pos); 
			$crc = $tmp[2][$i];
			$fullmd5 = $tmp[3][$i];
			$partmd5 = $tmp[4][$i];
			$size = $tmp[5][$i];
			
			$arr = explode(".",$name);
			$fid = intval($arr[0]);
			//$name = $arr[1];
			
			$sql = $sql ."($fid, '$name', '$fullmd5','$crc',$size,'$partmd5'), ";
			$bupdate = true;
		}
		unset($tmp);
		
		if(!$bupdate){
			$this->logger("bat_game2Db: 无sql数据");
			$this->db->close();
			return;
		}
		$sql = trim($sql, ', ');
		
		$this->db->query($sql);
		$this->db->close();
		
		echo 'finished';
	}
	
}

/* End of file shandong.php */
/* Location: ./controllers/shandong.php */
