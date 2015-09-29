<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class DuUtil{

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
	private $urlbase = 'http://www.nybgjd.com/3dclub/dumgr/';
	private $_redirect = 'http://www.nybgjd.com/3dclub/dumgr/auth_callback/';
	
	//应用根目录
    private $root_dir = '/apps/disk1/';


	private $_debug = true;
	private $CI;
	
	function __construct(){
		$this->CI = & get_instance();
	}
	
	
	
	function setDebug($stat){
		$this->_debug = $stat;
	}

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
	/* 该部分在 dumgr.php中实现
	function auth($username=null){
		$ok = '{"status": 1}';
		$err = '{"status":-1}';
		
		$this->logger('fun_auth: user='.$username);
		
		if($username==null || empty($username)){
			$this->logger('must set username!');
			exit($err);
		}
		
		$this->CI->load->library('session');	
		$this->CI->session->set_flashdata('auth_user', $username);
		
		$authurl = 'http://openapi.baidu.com/oauth/2.0/authorize?client_id='
				.$this->_client_id
				.'&user='.$username
				.'&response_type=code&scope=netdisk&redirect_uri='	// 必须加&scope=netdisk项，否则连选择‘读写百度云’的机会都没有
				.$this->_redirect;//'obb'
				
		$this->logger('fun_auth: authurl='.$authurl);		
		header('Location: '.$authurl);
		//echo $this->_getUrlContent($authurl, null);
	}
	*/	
	//该函数在调用认证接口后，自动回调该函数, 将不主动调用该函数
	function auth_callback($auth_info=''){
		$ok = '{"status":1}';
		$err = '{"status":-1}';
		
		$this->logger('fun_auth_callback: QUERY_STRING='.$_SERVER['QUERY_STRING']);
		parse_str($_SERVER['QUERY_STRING'], $_GET);

		if(!isset($_GET['code']) || strlen($_GET['code'])<5) {
			$this->logger('fun_auth_callback: auth code invalid');
			exit($err);
		}	
		$this->CI->load->library('session');	
		$user = $this->CI->session->flashdata('auth_user');
		
		if(!isset($user) || empty($user)){
			$this->logger('fun_auth_callback: have no user info');
			exit($err);
		}
		
		$auth_code = $_GET['code'];
		
		$expr = null;
		$refresh_token = null;
		$access_token = null;
		
		$this->CI->load->database('vr');
		
		//有效性处理
		$this->_refreshToken($user, $auth_code);

		$sql = 'select access_token ,refresh_token, TIMESTAMPDIFF(DAY, last_ts, now()) as expr from du_pcs_auth_list where username=\''.$user. '\'';
		$query = $this->CI->db->query($sql);
		foreach($query->result() as $row){
			$refresh_token = $row->refresh_token;
			$access_token = $row->access_token;
			$expr = $row->expr;
			break;
		}
		$query->free_result();	

		$this->CI->db->close();
		
		//exit($ok);
		if($access_token!=null && strlen($access_token)>0
			&& $refresh_token!=null && strlen($refresh_token)>0){
			$this->mkdir($user, 'pmv');
			$this->mkdir($user, 'dmv');
			$this->mkdir($user, 'dapk');
			$this->logger('fun_auth_callback: '. 'auth successful! username='.$user.',access_token='.$access_token.',refresh_token='.$refresh_token.',expr='.$expr);
			exit('<html><body>successful</body></html>');
		}
		else{
			$this->logger('fun_auth_callback: get refresh null!');
			exit('登录失败');
		}
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
		$this->logger('fun__refreshToken: user='. $user);
		
		$expr = null;
		$refresh_token = null;
		$access_token = null;
		$this->CI->load->database('vr');
		$flag = 0;
		
		if($auth_code!=null && strlen($auth_code)>0){
			//如果有authcode，须删除数据库中该用户的认证信息，避免干扰
			//也就是说每次认证，都要先清除其原有信息
			$sql = 'delete from du_pcs_auth_list where username=\''.$user. '\'';
			$this->CI->db->query($sql);	
			$flag = -1;
		}
		else{
			$sql = 'select access_token ,refresh_token, TIMESTAMPDIFF(DAY, last_ts, now()) as expr from du_pcs_auth_list where username=\''.$user. '\'';
			//echo $sql.'<br>';
			$query = $this->CI->db->query($sql);
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
					$sql = 'delete from du_pcs_auth_list where username=\''.$user.'\'';
					$this->CI->db->query($sql);
					$flag =  -1;
				}	
				else if(isset($jobj->error_description)){
					//echo $jobj->error_description.'<br>';
					$sql = 'delete from du_pcs_auth_list where username=\''.$user.'\'';
					$this->CI->db->query($sql);
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
		}	

		/*	*flag: 1: 已进行刷新操作
			  0: 已经存在，且非常有效
			  -1：不存在，需要创建
		*/
		if($flag == 1){
			$sql = 'update du_pcs_auth_list set access_token=\''.$access_token.'\', refresh_token=\''.$refr_token.'\' where username=\''.$user.'\'';
			$this->CI->db->query($sql);
			//	return 1;		
		}
		else if($flag == -1){
			//have no authed, get token by usual
			$url = 'https://openapi.baidu.com/oauth/2.0/token?grant_type=authorization_code&code='.$auth_code.'&client_id='.$this->_client_id.'&client_secret='.$this->_client_secret.'&redirect_uri='.$this->_redirect;
			$con = $this->_getUrlContent($url, array());
			//exit($con);
			$jobj = json_decode($con);
			if($jobj == null){
				$this->CI->db->close();
				$this->logger('fun__refreshToken: get access_token error!!');
				return null;
			}
			if(isset($jobj->error_description)){
				$this->CI->db->close();
				//exit($jobj->error_description);
				$this->logger('fun__refreshToken: error:'.$jobj->error_description);
				return null;
			}	
			
			$refresh_token = $jobj->refresh_token;
			$access_token = $jobj->access_token;			

			$sql = 'insert into du_pcs_auth_list (username, access_token, refresh_token) values (\''.$user.'\', \''.$access_token.'\', \''.$refresh_token.'\')';
			$this->CI->db->query($sql);				
		}

		$access_token = null;
		$sql = 'select access_token from du_pcs_auth_list where username=\''.$user. '\'';
		$query = $this->CI->db->query($sql);
		foreach($query->result() as $row){
			$access_token = $row->access_token;
			break;
		}
		$query->free_result();	
		$this->CI->db->close();
		
		$this->logger('fun__refreshToken: ret access_token:'.$access_token);
		return $access_token;
	}
	
	
	function getAccessToken($usr){
		$this->logger('getAccessToken: '. "user=$usr");
		$access_token = $this->_refreshToken($user);
		if($access_token == null){
			$this->logger("fun_download: access_token is null!");
			return '';
		}
		$this->logger('getAccessToken: token='.$access_token);
		return $access_token;
	}
	
	function deleteSingle($user, $filePath, $output=null){
		$this->logger('deleteSingle: '. "user=$user, filePath=$filePath");
		$access_token = $this->_refreshToken($user);
		if($access_token == null){
			$this->logger("deleteSingle: access_token is null!");
			return;
		}
			
		//文件路径
		$path = $this->root_dir . urldecode(base64_decode($filePath));
		$this->logger('path:'. $path);
		$fileName = '';

		//header('Content-Disposition:attachment;filename="' . $fileName . '"');
		//header('Content-Type:application/octet-stream');
		
		$this->CI->load->library('BaiduPCS');
		$this->CI->baidupcs->setAccessToken($access_token);
		$result = $this->CI->baidupcs->deleteSingle($path);	
		//echo $result;
		if($output != null)
			echo $result;
	}
	

	function deleteBatch($user, $filePath){
		$this->logger('deleteBatch: '. "user=$user");
		$access_token = $this->_refreshToken($user);
		if($access_token == null){
			$this->logger("deleteBatch: access_token is null!");
			return;
		}
 
		$tmp = urldecode(base64_decode($filePath));
		$paths = explode(',', $tmp);
		foreach($paths as $k=>$v){
			$paths[$k] = $this->root_dir .$v;
			$this->logger("deleteBatch: paths[$k]=".$paths[$k]);
		}

	
		$this->CI->load->library('BaiduPCS');
		$this->CI->baidupcs->setAccessToken($access_token);
		$result = $this->CI->baidupcs->deleteBatch($paths);	
		return $result;
	}

	
	// user: 某token的标识用户
	// filepath:  相对于 'disk1' 的相对路径，先base64_encode编码
	//如 disk1/ab/1/a.apk	==> filepath:  base64_encode('ab/1/a.apk');
	function download($user, $filePath){
		$this->logger('fun_download: '. "user=$user, filePath=$filePath");
		$access_token = $this->_refreshToken($user);
		if($access_token == null){
			$this->logger("fun_download: access_token is null!");
			return;
		}
			
		//文件路径
		$path = $this->root_dir . base64_decode($filePath);
		$fileName = '';

		//header('Content-Disposition:attachment;filename="' . $fileName . '"');
		//header('Content-Type:application/octet-stream');
		
		$this->CI->load->library('BaiduPCS');
		$this->CI->baidupcs->setAccessToken($access_token);
		$result = $this->CI->baidupcs->downloadStream($path);
		
		header('Content-Disposition:attachment;filename="' . $fileName . '"');
		header('Content-Type:application/octet-stream');
		
		$this->logger("fun_download: result=$result");
		echo $result;	
	}
	
	function mkdir($user, $dirpath){
		$this->logger("fun_mkdir: user=$user, dirpath=$dirpath");
		$this->CI->load->database('vr');
		$access_token = $this->_refreshToken($user);
		if($access_token == null){
			$this->logger("fun_mkdir: access_token is null!");
			return;
		}

		//要创建的目录路径
		$path = $this->root_dir . urldecode($dirpath);

		$this->CI->load->library('BaiduPCS');
		$this->CI->baidupcs->setAccessToken($access_token);
		$this->CI->baidupcs->makeDirectory($path);	
		$this->logger("fun_mkdir: finish");
	}
	
	function quota_info($user){
		$this->logger("fun_quota_info:user = $user");
		$this->CI->load->database('vr');
		$access_token = $this->_refreshToken($user);
		if($access_token == null){
				$this->logger("fun_quota_info: access_token is null");
				return;
		}

		$this->CI->load->library('BaiduPCS');
		$this->CI->baidupcs->setAccessToken($access_token);
		//$this->baidupcs->set_ssl(true); //设置HTTPS访问方式
		echo $this->CI->baidupcs->getQuota();
		
		$this->logger("fun_quota_info: finish");
	}




	/*
	 * @param string $by 排序字段，缺省根据文件类型排序，time（修改时间），name（文件名），size（大小，注意目录无大小）
	 * @param string $order asc或desc，缺省采用降序排序
	 * @param string $limit 返回条目控制，参数格式为：n1-n2。返回结果集的[n1, n2)之间的条目，缺省('0-0')返回所有条目。n1从0开始。
	*/	
	function list_files($user, $dirpath=null, $is_abspath=0, $by='time', $order='asc', $limit='0-0'){
		$this->logger("fun_list_files: user=$user, dirpath=$dirpath, is_abspath=$is_abspath, by=$by, order=$order, limit=$limit");
		$access_token = $this->_refreshToken($user);
		if($access_token == null){
			$this->logger("fun_list_files:access_token is null!");
			return;
		}	
		
		$this->CI->load->library('BaiduPCS');
		$this->CI->baidupcs->setAccessToken($access_token);	

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
		$this->logger("fun_list_files: fullpath=$path");
		$result = $this->CI->baidupcs->listFiles($path, $by, $order, $limit);
		
		$this->logger("fun_list_files: rslt=$result");
		return $result;
	}

	/*
		返回null，失败
		1: 目录
		0： 文件
	*/
	function is_dir($user, $path){
		$this->logger("fun_is_dir: user=$user, path=$path");
		$access_token = $this->_refreshToken($user);
		if($access_token == null){
			$this->logger("fun_is_dir: errror:access_token is null");
			return;
		}
	
		$this->CI->load->library('BaiduPCS');
		$this->CI->baidupcs->setAccessToken($access_token);	
		$result = $this->CI->baidupcs->getMeta($path);
		$jobj = json_decode($result);
		if($jobj == null || !isset($jobj->list)){
			$this->logger("fun_is_dir: errror: json is invalid");
			return null;
		}
		//exit("ass:".$jobj->list[0]->isdir);
		return $jobj->list[0]->isdir;
	}

	
	function upload($user, $localfile, $destPath){
		$this->logger("fun_upload: user=$user, localfile=$localfile, destPath=$destPath");
		$this->CI->load->database('vr');
		$this->_refreshToken($user);

		$access_token = $this->_refreshToken($user);
		if($access_token == null){
				$this->logger("fun_upload: error access_token is null");
				return;
		}				

		$this->CI->load->library('BaiduPCS');
		$this->CI->baidupcs->setAccessToken($access_token);	
		
		//应用根目录
		$root_dir = '/apps' . '/' . $this->_appName . '/';

		//目录路径
		$targetPath = $root_dir . urldecode($destPath);
		//文件名称
		$fileName = basename($targetPath);
		$newFileName = '';
		
		if (!file_exists($localfile)) {
			$this->logger("fun_upload: $localfile 文件不存在，请检查路径是否正确");
			exit('{"status":-1}');
		} else {
			$fileSize = filesize($localfile);
			$handle = fopen($localfile, 'rb');
			$fileContent = fread($handle, $fileSize);

			$result = $this->CI->baidupcs->upload($fileContent, $targetPath, $fileName, $newFileName);
			fclose($handle);
			
			$this->logger("fun_upload: echo: $result");
			echo $result;
		}	
	}


	//秒传文件
	function quickCopy($user, $fullmd5, $fullcrc32, $filesize,$slicemd5, $destname){
		$this->logger("fun_quickCopy: user=$user, fullmd5=$fullmd5, fullcrc32=$fullcrc32, filesize=$filesize, slicemd5=$slicemd5, destname=$destname");
		$this->CI->load->database('vr');
		$this->_refreshToken($user);

		$access_token = $this->_refreshToken($user);
		if($access_token == null){
			$this->logger("fun_quickCopy: error: access_token is null");
			return;
		}				


		$this->CI->load->library('BaiduPCS');
		$this->CI->baidupcs->setAccessToken($access_token);	

		$destPath =  urldecode('/apps/disk1/'.$destname);
		$result = $this->CI->baidupcs->cloudMatch($destPath,$filesize,$fullmd5,$slicemd5,$fullcrc32);	

		$this->logger("fun_quickCopy: rsl=$result");
		//echo $result;
		return $result;
	}

	//$abspath 为绝对路径的base64编码
	function getDuUrl($user, $abspath){
		$this->logger("fun_getDuUrl: user=$user, abspath=$abspath");
		$this->CI->load->database('vr');
		$this->_refreshToken($user);

		$access_token = $this->_refreshToken($user);
		if($access_token == null){
			$this->logger("fun_getDuUrl: error: access_token is null");
			return;
		}				

		$this->CI->load->library('BaiduPCS');
		$this->CI->baidupcs->setAccessToken($access_token);	

		$destPath =  urlencode(base64_decode($abspath));

		
		$url = 'https://d.pcs.baidu.com/rest/2.0/pcs/file?method=download&access_token='.$access_token.'&path='.$destPath;
		$this->logger("fun_getDuUrl: rsl=$url");
		return $url;
	}
	
	//$abspath 为urlencode(utf8)后的绝对路径
	function getStreamUrl($user, $abspath){
		$this->logger("fun_getStreamUrl: user=$user, abspath=$abspath");
		$this->CI->load->database('vr');
		$this->_refreshToken($user);

		$access_token = $this->_refreshToken($user);
		if($access_token == null){
			$this->logger("fun_getStreamUrl: error: access_token is null");
			return;
		}				

		$this->CI->load->library('BaiduPCS');
		$this->CI->baidupcs->setAccessToken($access_token);	

 
		$abspath = urldecode($abspath);
 
		$url = $this->CI->baidupcs->streaming($abspath, 'M3U8_640_480');
		//$this->logger("fun_getStreamUrl: rsl=$url");
		return $url;
	}
}

/* End of file shandong.php */
/* Location: ./controllers/shandong.php */
