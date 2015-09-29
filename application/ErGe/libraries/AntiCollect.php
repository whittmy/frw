<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

 /*
*网站防IP攻击代码(Anti-IP attack code website)2010-11-20,Ver2.0
*  Anti-refresh mechanism
*design by www. 
*/

class AntiCollect{
	private $CI;
	private $m_Warn_forbidden;
	private $m_Warn_forRefresh;

	private $m_basepath;	//工作目录路径	
		
	
	private $prefix;
	
	private $m_FIpfile;		//存放被禁止ip信息的 文件
	private $m_fileforbid;	//该文件记录 被禁止刷新的ip
	private $m_file;
	private $m_forbided_ip;
	
	private $m_fileforbid_expir;	
	private $m_forbidIP_maxCnt;		

	private $m_refresh_allowTime; 
	private $m_refresh_allowNum; 

	
	function _reset(){
		$this->m_Warn_forbidden = 'Thank you for your hard work!!';
		$this->m_Warn_forRefresh = '';
		
		
		$this->m_refresh_allowTime = 60;//防刷新时间
		$this->m_refresh_allowNum = 5;//防刷新次数
	
		$this->m_fileforbid_expir = 30; //m_fileforbid文件的有效期，以秒为单位
		$this->m_forbidIP_maxCnt = 120;//非法刷新（即禁止刷新期间即m_refresh_allowTime时间）超过该次数，便机制该ip
		
		$this->m_FIpfile = $this->m_basepath . $this->prefix.'forbid-ips';
		$this->m_fileforbid = $this->m_basepath. 'log/'.$this->prefix.'forbidchk.dat';
		$this->m_file = $this->m_basepath. 'log/'.$this->prefix.'ipdate.dat';
		$this->m_forbided_ip = $this->m_basepath. 'log/'.$this->prefix.'forbided_ip.log';
	}
	
	function __construct($param = null){
		$this->CI = & get_instance();
		
		if($param!=null && isset($param['prefix']) && strlen($param['prefix']) >0){
			$this->prefix = $param['prefix'].'_';
		}
		else{
			$this->prefix = '';
		}
 
		$tmp = dirname(dirname(__FILE__));	//尾部不含 '/' 或 '\'
		$tmp .= '/anti-ip/';

		if (!file_exists($tmp) && !is_dir($tmp)) mkdir($tmp, 0777);
		$this->m_basepath = $tmp;

		//-----
		$this->_reset();
	}

	function _getIP() { 
		if (getenv('HTTP_CLIENT_IP')) { 
			$ip = getenv('HTTP_CLIENT_IP'); 
		} 
		elseif (getenv('HTTP_X_FORWARDED_FOR')) { 
			$ip = getenv('HTTP_X_FORWARDED_FOR'); 
		} 
		elseif (getenv('HTTP_X_FORWARDED')) { 
			$ip = getenv('HTTP_X_FORWARDED'); 
		} 
		elseif (getenv('HTTP_FORWARDED_FOR')) { 
			$ip = getenv('HTTP_FORWARDED_FOR'); 
		} 
		elseif (getenv('HTTP_FORWARDED')) { 
			$ip = getenv('HTTP_FORWARDED'); 
		} 
		else { 
			$ip = $_SERVER['REMOTE_ADDR']; 
		} 
		return $ip; 
	} 
	
	function _getip_out(){ 
		$ip=false; 
		if(!empty($_SERVER["HTTP_CLIENT_IP"])){ 
			$ip = $_SERVER["HTTP_CLIENT_IP"]; 
		} 
		if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) { 
			$ips = explode (", ", $_SERVER['HTTP_X_FORWARDED_FOR']); 
			if ($ip) { 
				array_unshift($ips, $ip); 
				$ip = FALSE; 
			} 
			for ($i = 0; $i < count($ips); $i++) { 
				if (!eregi ("^(10│172.16│192.168).", $ips[$i])) { 
					$ip = $ips[$i]; 
					break; 
				} 
			} 
		} 
		return ($ip ? $ip : $_SERVER['REMOTE_ADDR']); 
	} 	

	function apply(){
		//查询禁止IP
		//$ip = $_SERVER['REMOTE_ADDR'];
		$ip = $this->_getIP();
		//$ip = $this->_getip_out();

		//$this->m_FIpfile = ".htaccess2";
		if (!file_exists($this->m_FIpfile)) file_put_contents($this->m_FIpfile, "");
		$this->m_FIpfilearr = @file($this->m_FIpfile);
		if (in_array($ip . "\r\n", $this->m_FIpfilearr)) die($this->m_Warn_forbidden);
		
		
		//加入禁止IP
		$time = time();
		//$this->m_fileforbid = "log/forbidchk.dat";
		if (file_exists($this->m_fileforbid)) {
			// 若服务器有超过30秒没有操作该文件(不依据ip)，则删除该文件
			if (($time - filemtime($this->m_fileforbid)) > $this->m_fileforbid_expir) {
				unlink($this->m_fileforbid);
			}
			else {
				$this->m_fileforbidarr = @file($this->m_fileforbid);
				//$this->m_fileforbidarr[0] :	ip
				//$this->m_fileforbidarr[1] :	timestamp
				//$this->m_fileforbidarr[2]	:	refresh-forbid cnt
				//只有当超出规定的刷新次数后，这个文件才记录重复刷新者的信息
				if ($ip == substr($this->m_fileforbidarr[0], 0, strlen($ip))) {
					if ($time - substr($this->m_fileforbidarr[1], 0, strlen($time)) > $this->m_forbidIP_maxCnt) unlink($this->m_fileforbid);
					elseif ($this->m_fileforbidarr[2] >  $this->m_forbidIP_maxCnt) {
						//禁止重复刷新的时间内(即下面的$this->m_refresh_allowTime)，的刷新次数若超过120次，则禁止该ip
						file_put_contents($this->m_FIpfile, $ip . "\r\n", FILE_APPEND);
						unlink($this->m_fileforbid);
					} else {
						//在到达120次之前，努力记录次数吧
						$this->m_fileforbidarr[2]++;
						file_put_contents($this->m_fileforbid, $this->m_fileforbidarr);
					}
				}
			}
		}
		
		//防刷新
		$str = "";
		//$this->m_file = "log/ipdate.dat";
		$pdir = $this->m_basepath.'log';
		if (!file_exists($pdir) && !is_dir($pdir)) mkdir($pdir, 0777);
		if (!file_exists($this->m_file)) file_put_contents($this->m_file, "");
		
		//$this->m_refresh_allowTime = 60; //防刷新时间
		//$this->m_refresh_allowNum = 5; //防刷新次数
		$uri = $_SERVER['REQUEST_URI'];
		$checkip = md5($ip);
		$checkuri = md5($uri);
		$yesno = true;
		$ipdate = @file($this->m_file);
		foreach ($ipdate as $k => $v) {
			$iptem = substr($v, 0, 32);
			$uritem = substr($v, 32, 32);
			$timetem = substr($v, 64, 10);
			$numtem = substr($v, 74);
			if ($time - $timetem < $this->m_refresh_allowTime) {
				if ($iptem != $checkip) $str.= $v;
				else {
					$yesno = false;
					if ($uritem != $checkuri) $str.= $iptem . $checkuri . $time . "1rn";
					elseif ($numtem < $this->m_refresh_allowNum) $str.= $iptem . $uritem . $timetem . ($numtem + 1) . "\r\n";
					else {
						if (!file_exists($this->m_fileforbid)) {
							$addforbidarr = array(
								$ip . "\r\n",
								time() . "\r\n",
								1
							);
							file_put_contents($this->m_fileforbid, $addforbidarr);
						}
						file_put_contents($this->m_forbided_ip, $ip . "--" . date("Y-m-d H:i:s", time()) . "--" . $uri . "\r\n", FILE_APPEND);
						$timepass = $timetem + $this->m_refresh_allowTime - $time;
						//die("Warning:" . "<br>" . "Pls don't refresh too frequently, and wait for " . $timepass . " seconds to continue, IF not your IP address will be forbided automatic by   Anti-refresh mechanism!<br>(  Anti-refresh mechanism is to enable users to have a good shipping services, but there maybe some inevitable network problems in your IP address, so that you can mail to us to solve.)");
						die($this->m_Warn_forRefresh);
					}
				}
			}
		}
		if ($yesno) $str.= $checkip . $checkuri . $time . "1rn";
		file_put_contents($this->m_file, $str);	
	}

}



?>
