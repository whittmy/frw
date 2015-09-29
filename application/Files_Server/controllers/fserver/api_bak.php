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
 
	//内定信息
	private $_client_id = 'Ar4jru4mrAoHI8nTudiBCAfa';
	private $_client_secret = 'S2xkwtQZe0dOG8UhrbTbO18TzRHCnZOO';
	//private $_appName = 'disk1';	//应用目录名,应该是pcs path
		
	//应用根目录
    private $root_dir = '/apps/disk1/';



	private $urlbase = 'http://www.nybgjd.com/fserver/api/';
		
	private $_redirect = 'http://www.nybgjd.com/fserver/api/auth_callback/';

	function _getUrlContent($url, $header, $cookiefile=null){
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
		if($username==null || empty($username))
			exit('must set username!');
		
		$this->load->library('session');	
		$this->session->set_flashdata('auth_user', $username);
		
		$authurl = 'http://openapi.baidu.com/oauth/2.0/authorize?client_id='
				.$this->_client_id
				.'&user='.$username
				.'&response_type=code&scope=netdisk&redirect_uri='	// 必须加&scope=netdisk项，否则连选择‘读写百度云’的机会都没有
				.$this->_redirect/*'obb'*/;
		header('Location: '.$authurl);
		//echo $this->_getUrlContent($authurl, null);
	}
		
	//该函数在调用认证接口后，自动回调该函数, 将不主动调用该函数
	function auth_callback($auth_info=''){
		//	exit('xxxxxxxxx:'.$_SERVER['QUERY_STRING']);
		parse_str($_SERVER['QUERY_STRING'], $_GET);

		if(!isset($_GET['code']) || strlen($_GET['code'])<5) 
			exit('auth code invalid');
			
		$this->load->library('session');	
		$user = $this->session->flashdata('auth_user');
		
		if(!isset($user) || empty($user))
			exit('have no user info');
		
		$auth_code = $_GET['code'];
		
		$expr = null;
		$refresh_token = null;
		$access_token = null;
		
		$this->load->database();
		
		//有效性处理
		$this->_refreshToken($user, $auth_code);

		$sql = 'select access_token ,refresh_token, TIMESTAMPDIFF(DAY, last_ts, now()) as expr from pcs_auth where username=\''.$user. '\'';
		$query = $this->db->query($sql);
		foreach($query->result() as $row){
			$refresh_token = $row->refresh_token;
			$access_token = $row->access_token;
			$expr = $row->expr;
			break;
		}
		$query->free_result();	

		$this->db->close();
		exit('auth successful! username='.$user.',access_token='.$access_token.',refresh_token='.$refresh_token.',expr='.$expr);
	}
	/////////////////////////////////////////////////////////////////////////	
	//////////////////////////////////////////////////////////////////////////////////////////////////////




	
	/**
	* 注意： 一个refresh_token只能使用一次的！！！，所以使用了之后必须更新为新的
		该函数会依据登录日期是否要通过刷新，重新获取access_token，
		每个操作前，需要调用该函数
		返回成功的 access_token,否则返回null
	*/
	function _refreshToken($user, $auth_code=null){
		$expr = null;
		$refresh_token = null;
		$access_token = null;
		$this->load->database();
		$flag = 0;
		
		$sql = 'select access_token ,refresh_token, TIMESTAMPDIFF(DAY, last_ts, now()) as expr from pcs_auth where username=\''.$user. '\'';
		//echo $sql.'<br>';
		$query = $this->db->query($sql);
		foreach($query->result() as $row){
			$refresh_token = $row->refresh_token;
			$access_token = $row->access_token;
			$expr = $row->expr;
			break;
		}
		$query->free_result();		
	
		if(($expr != null) && ($refresh_token != null) && ($expr>=15)){
			//if had authed days >= 15days
			//we refresh it by refresh_token, then the org refresh_token will be invalid
			// we update it with newer tokens
			$url = 'https://openapi.baidu.com/oauth/2.0/token';
			$refresh_url = $url.'grant_type=refresh_token'
							.'&refresh_token='.$refresh_token
							.'&client_id='.$this->_client_id
							.'&client_secret='.$this->_client_secret
							.'&scope=netdisk';		// scope: 为netdisk时，才有读写权限
			//echo 'refreshurl: '.$refresh_url.'<br>';
			$con = $this->_getUrlContent($refresh_url, array());
			$jobj = json_decode($con);
			if($jobj == null){
				//exit('refreshToken error!!') ;
				// mybe the refresh token had been used many times!!
				$sql = 'delete from pcs_auth where username=\''.$user.'\'';
				$this->db->query($sql);
				$flag =  -1;
			}	
			else if(isset($jobj->error_description)){
				//echo $jobj->error_description.'<br>';
				$sql = 'delete from pcs_auth where username=\''.$user.'\'';
				$this->db->query($sql);
				$flag = -1;
			}
			else{
				$flag = 1;
			}
		}
		else if($refresh_token == null || $access_token == null){
			// need to create newer
			$flag = -1;
		}
		else{
			// ok
			$flag = 0;
		}
		
		/*	*flag: 1: 已进行刷新操作
			  0: 已经存在，且非常有效
			  -1：不存在，需要创建
		*/
		if($flag == 1){
			$sql = 'update pcs_auth set access_token=\''.$access_token.'\', refresh_token=\''.$refr_token.'\' where username=\''.$user.'\'';
			$this->db->query($sql);
			//	return 1;		
		}
		else if($flag == -1){
			//have no authed, get token by usual
			$url = 'https://openapi.baidu.com/oauth/2.0/token?grant_type=authorization_code&code='.$auth_code.'&client_id='.$this->_client_id.'&client_secret='.$this->_client_secret.'&redirect_uri='.$this->_redirect;
			$con = $this->_getUrlContent($url, array());
			//exit($con);
			$jobj = json_decode($con);
			if($jobj == null){
				$this->db->close();
				//exit('get access_token error!!');
				return null;
			}
			if(isset($jobj->error_description)){
				$this->db->close();
				//exit($jobj->error_description);
				return null;
			}	
			
			$refresh_token = $jobj->refresh_token;
			$access_token = $jobj->access_token;			

			$sql = 'insert into pcs_auth (username, access_token, refresh_token) values (\''.$user.'\', \''.$access_token.'\', \''.$refresh_token.'\')';
			$this->db->query($sql);				
		}

		$access_token = null;
		$sql = 'select access_token from pcs_auth where username=\''.$user. '\'';
		$query = $this->db->query($sql);
		foreach($query->result() as $row){
			$access_token = $row->access_token;
			break;
		}
		$query->free_result();	
		$this->db->close();
		return $access_token;
	}
	
	
	
	// user: 某token的标识用户
	// filepath:  相对于 'disk1' 的相对路径，先base64_encode编码
	//如 disk1/ab/1/a.apk	==> filepath:  ......
	function download($user, $filePath){
		$access_token = $this->_refreshToken($user);
		if($access_token == null){
			log_message('error', "download:$user - $filePath");
			return;
		}
			
		//文件路径
		$path = $this->root_dir . base64_decode($filePath);
		$fileName = '';

		//header('Content-Disposition:attachment;filename="' . $fileName . '"');
		//header('Content-Type:application/octet-stream');
		
		$this->load->library('BaiduPCS');
		$this->baidupcs->setAccessToken($access_token);
		$result = $this->baidupcs->downloadStream($path);
		
		header('Content-Disposition:attachment;filename="' . $fileName . '"');
		header('Content-Type:application/octet-stream');
		echo $result;	
	}
	
	function mkdir($user, $dirpath){
		$this->load->database();
		$access_token = $this->_refreshToken($user);
		if($access_token == null){
			log_message('error', "mkdir: $user - $dirpath");
			return;
		}

		//要创建的目录路径
		$path = $this->root_dir . urldecode($dirpath);

		$this->load->library('BaiduPCS');
		$this->baidupcs->setAccessToken($access_token);
		$this->baidupcs->makeDirectory($path);	
	}
	
	function quota_info($user){
		$this->load->database();
		$access_token = $this->_refreshToken($user);
		if($access_token == null){
				log_message('error', "quota_info: $user");
				return;
		}


		$this->load->library('BaiduPCS');
		$this->baidupcs->setAccessToken($access_token);
		//$this->baidupcs->set_ssl(true); //设置HTTPS访问方式
		echo $this->baidupcs->getQuota();
	}




	/*
	 * @param string $by 排序字段，缺省根据文件类型排序，time（修改时间），name（文件名），size（大小，注意目录无大小）
	 * @param string $order asc或desc，缺省采用降序排序
	 * @param string $limit 返回条目控制，参数格式为：n1-n2。返回结果集的[n1, n2)之间的条目，缺省('0-0')返回所有条目。n1从0开始。
	*/	
	function list_files($user, $dirpath=null, $is_abspath=0, $by='time', $order='asc', $limit='0-0'){
		log_message('error', 'into list_files');
		$access_token = $this->_refreshToken($user);
		if($access_token == null){
				log_message('error', "access_token is null! list_files: $user - $dirpath - $is_abspath");
				return;
		}	
		
		$this->load->library('BaiduPCS');
		$this->baidupcs->setAccessToken($access_token);	

		//exit($dirpath);
		//目录路径
		if($dirpath != null){
			if($is_abspath == 1){
				//exit($dirpath);
				$path = base64_decode($dirpath);
			}
			else{
				$path = $this->root_dir . base64_decode($dirpath);
			}
		}
		else
			$path = $this->root_dir;
		//exit($path);
		//$path = '/apps/disk1/新建文件夹(1)';
		$result = $this->baidupcs->listFiles($path, $by, $order, $limit);
		return $result;
	}

	/*
		返回null，失败
		1: 目录
		0： 文件
	*/
	function is_dir($user, $path){
		$access_token = $this->_refreshToken($user);
		if($access_token == null){
			log_message('error', "test: $user");
			return;
		}
	
		$this->load->library('BaiduPCS');
		$this->baidupcs->setAccessToken($access_token);	
		$result = $this->baidupcs->getMeta($path);
		$jobj = json_decode($result);
		if($jobj == null || !isset($jobj->list)){
			return null;
		}
		//exit("ass:".$jobj->list[0]->isdir);
		return $jobj->list[0]->isdir;
	}

	// 获取dirpath目录下 所有目录，不含子目录
	// dirpath：相对路径
	function getDirs($user, $dirpath=null, $is_abspath=0){
		//按大小，升序，即目录排在最前面
		$str = $this->list_files($user, $dirpath, $is_abspath, 'size');	
		if($str == null){
			log_message('error', "getDirs: $user, $dirpath");
			return null;
		}
		$jobj = json_decode($str);
		if($jobj == null || !isset($jobj->list)){
			log_message('error', "getDirs: $user, $dirpath, jobj=null");
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
		//print_r($rt);
		return $rt;
	} 


	/*
		cntIndir:每个目录中要获取的'文件'大概数目,要多采集些，免得有把目录的个数也算成了文件
		-1：全部
		0： 忽略
	*/
	function gather($cntIndir=-1){
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
		//exit($dirpath);
		$limit = "";
		if($cntIndir == -1){
			$limit = '0-0';
		}
		else if($cntIndir == 0){
			return;
		}
		else{
			$limit = '0-'.$cntIndir;
		}
		echo $this->list_files($user, $dirpath, $is_abspath, 'name', 'desc', $limit);
	}

	//获取待采集的账户
	// 数组
	function getusers(){
		$this->load->database();
		$sql = 'select username from `pcs_auth` where bactivate=1';
		$query = $this->db->query($sql);
		
		$users = array();
		foreach($query->result() as $row){
			$users[] = $row->username;
		}
		$query->free_result();		
		$this->db->close();
		
		return $users;
	}
	
	
	function upload($user, $localfile, $destPath){
		$this->load->database();
		$this->_refreshToken($user);

		$access_token = $this->_refreshToken($user);
		if($access_token == null){
				log_message('error', "upload: $user - $localfile - $destPath");
				return;
		}				


		$this->load->library('BaiduPCS');
		$this->baidupcs->setAccessToken($access_token);	
		
		//应用根目录
		$root_dir = '/apps' . '/' . $this->_appName . '/';

		//目录路径
		$targetPath = $root_dir . urldecode($destPath);
		//文件名称
		$fileName = basename($targetPath);
		$newFileName = '';
		
		if (!file_exists($localfile)) {
			exit('文件不存在，请检查路径是否正确');
		} else {
			$fileSize = filesize($localfile);
			$handle = fopen($localfile, 'rb');
			$fileContent = fread($handle, $fileSize);

			$result = $this->baidupcs->upload($fileContent, $targetPath, $fileName, $newFileName);
			fclose($handle);
			
			echo $result;
		}	
	}


	//秒传文件
	function quickCopy($user, $fullmd5, $fullcrc32, $filesize,$slicemd5, $destname){
		$this->load->database();
		$this->_refreshToken($user);

		$access_token = $this->_refreshToken($user);
		if($access_token == null){
				log_message('error', "upload: $user - $localfile - $destPath");
				return;
		}				


		$this->load->library('BaiduPCS');
		$this->baidupcs->setAccessToken($access_token);	

		$destPath =  urldecode('/apps/disk1/'.$destname);
		$result = $this->baidupcs->cloudMatch($destPath,$filesize,$fullmd5,$slicemd5,$fullcrc32);		
		echo $result;
	}


	
}

/* End of file shandong.php */
/* Location: ./controllers/shandong.php */
