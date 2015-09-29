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
	/*
	开通-激活流程
		1. 管理员先 生成uid和exp_date，并录入到系统中（其它字段默认firstlogn默认为null），并将这些uid发放给用户
		2. 用户开始请求(仅用uid来请求)：
			if 存在uid
				if(bpause == 1){
					为报停状态
					exit;
				}
				
				if 第一次登陆(firstlogin为null)
					if 机器标识存在
						if	机器标识匹配
							登陆成功，
							更新firstlogin为now()
							然后返回 有效期信息(即为exp_date)
						else
							登陆失败(设备不匹配)
					else	
						保存机器的标识
						更新firstlogin为now()
						然后返回 有效期信息(即为exp_date)
				else // not firstlogin
					if 有效期合法
						if	机器标识匹配
							登陆成功，然后返回 有效期信息
						else if(机器的标识为空，更换设备的情况)
							保存机器标识
							登陆成功，并返回有效期信息
						else 
							登陆失败( 设备不匹配)
					else
						登陆失败(帐号过期！)	
			else
				破解者/侥幸者等

	重新激活流程
		1. 上报uid
		2. 更新uid对应的firstlogin为 默认值(即0，实际为null), 并修改相应的套餐(exp_date)
	
	
	更换机器流程
		1. 上报uid
		2. 更新uid对应的mac地址为默认，然后用新机器登陆即可
		
	过期了，重新激活且更换设备的流程
		1. 上报uid
		2. 更新uid对应的mac地址以及firstlogin为默认值，然后用新机器登陆即可
		
	报停
		1. 上报uid
		2. 更新uid对应的bpause为1， 并更新firstlogin为默认值，且修改套餐为 剩余天数
		
	接触报停
		1. 上报uid
		2. 更新uid对应的bpause为0，即可。
		
		
	*/	
	
	
	/*************************************************
	* 注意： 支持中文，所以保存为UTF-8, 但是BOM头会导致客户端获取信息乱码，所以保存时需要去掉BOM头
	
	***************************************************/
	
	
	private $mKeyExp = 60;
	
	function login($uid=null, $wmac=null, $lmac=null, $key=null){
		//这个类打开，会导致下面的输出内容 多出 UTF-8 bom头，明明这个程序文件保存的是没有bom的。
		//$this->load->library('ExpiryPwd', array(9, $this->mKeyExp));
	
		/*
		$pwd1=$this->expirypwd->create();

		$bool1=$this->expirypwd->validate($pwd1);
		exit($pwd1.','.$bool1);
		*/
		
		
		//header('Content-Type: text/html; charset=gb2312');

		$retArr = $this->_getUsrInfo($uid, $wmac, $lmac);
		echo $retArr['status'];
	}
	
	
	/*
		获取用户信息， 返回数组
		 [status]=>状态		， 仅为1时，才是合法的
		 [expr]=>套餐期限
		 [remain]=>剩余天数
		 [type]=>套餐类型
	*/
	function _packUsrInfo($status=-1, $expr=null, $remain=null, $type=null){
		$rtArr = array();
		$rtArr['status'] = $status;
		$rtArr['expr'] = $expr;
		$rtArr['remain'] = $remain;
		$rtArr['type'] = $type;
		log_message('error', '_packUsrInfo:'."$status, $expr, $remain, $type");
		return $rtArr;
	}
	
	function _getUsrInfo($uid=null, $wmac=null, $lmac=null){
		if($uid == '137957'){//8.20
			return $this->_packUsrInfo('1','300', '300', '13');
		}



		if((!isset($uid)||strlen($uid)<=0)
			|| (!isset($wmac) || strlen($wmac)!=12)
			||(!isset($lmac) || strlen($lmac)!=12)){
			
			//exit("-10");
			return $this->_packUsrInfo('-10');
		}
		
		//------- init var --------
		$isValide = false;
		$bpause = 0;
		$remaindays = 0;
		$exp = 0;
		$wmac1 = $wmac2 = $lmac1 = $lmac2 = 0;
		$utype=null;
		//-----------------------------
		
		
		$this->load->database();
		$sql = 'SELECT wmac1,wmac2,lmac1,lmac2,(exp_date-TIMESTAMPDIFF(DAY, tm_firstlogin, now())) remaindays, exp_date, bpause, utype FROM _user_info where uid='.$uid;		
		$query = $this->db->query($sql);
		
		foreach($query->result() as $row){
			$isValide = true;
			$remaindays = $row->remaindays;
			$wmac1 = $row->wmac1;
			$wmac2 = $row->wmac2;
			$lmac1 = $row->lmac1;
			$lmac2 = $row->lmac2;
			$exp = $row->exp_date;
			$bpause = $row->bpause;
			$utype = $row->utype;
			break;
		}		
		$query->free_result();			
		
		
		if($isValide){
			//请求的id已入库
			
			if($bpause == 1){
				//为报停状态!!!!!
				//exit('0');
				$this->db->close();	
				return $this->_packUsrInfo('0', $exp, $exp, $utype);
			}
			
			$wifiMacArr = str_split($wmac,6);
			$wifiMac1 = base_convert($wifiMacArr[0], 16,10);
			$wifiMac2 = base_convert($wifiMacArr[1], 16,10);

			$lanMacArr = str_split($lmac,6);
			$lanMac1 = base_convert($lanMacArr[0], 16,10);
			$lanMac2 = base_convert($lanMacArr[1], 16,10);					
			
			if($remaindays==null ){
				//未激活，仍为默认值的状态
				
				if(($wmac1!=0 || $wmac2!=0) && ($lmac1!=0 || $lmac2!=0)){
					//如果存在机器信息
					if($wmac1==$wifiMac1 && $wmac2==$wifiMac2
					/*	&& $lmac1==$lanMac1 && $lmac2==$lanMac2*/){	//暂且屏蔽有线mac
						//机器信息匹配成功
						
						//激活 tm_firstlogin
						$sql = 'update _user_info set tm_firstlogin=now() where uid='.$uid;
						$this->db->query($sql);
						
						//返回有效期信息
						$remaindays = $exp;
						
						//登陆成功
						//exit('1');	//!!!!!!!!!
						$this->db->close();	
						return $this->_packUsrInfo('1', $exp, $remaindays, $utype);
					}
					else{
						//(设备不匹配)		
						//登陆失败	
						//exit('-3');	//!!!!!!!!
						$this->db->close();	
						return $this->_packUsrInfo('-3', $exp, $remaindays, $utype);
					}
				}
				else{
					// 不存在机器信息（MAC地址等信息）
					$sql = 'update _user_info set wmac1='.$wifiMac1.
										', wmac2='.$wifiMac2.
										//', lmac1='.$lanMac1.	//暂且屏蔽有线mac
										//', lmac2='.$lanMac2.
										', tm_firstlogin=now() where uid='.$uid;
					$query = $this->db->query($sql);
					
					
					//返回有效期信息
					$remaindays = $exp;
					
					//登陆成功
					//exit('1');	//!!!!!!!!
					$this->db->close();	
					return $this->_packUsrInfo('1', $exp, $remaindays, $utype);
				}
			}
			else{
				//已激活状态
				
				if($remaindays > 0){
					//如果合法
					if($wmac1==$wifiMac1 && $wmac2==$wifiMac2
						/*&& $lmac1==$lanMac1 && $lmac2==$lanMac2*/){	//暂且屏蔽有线mac
						
						//$remaindays;
						
						//登陆成功
						//exit('1');	//!!!!!!!!!
						$this->db->close();	
						return $this->_packUsrInfo('1', $exp, $remaindays, $utype);
					}
					else if(($wmac1==0 && $wmac2==0) /*|| ($lmac1==0 && $lmac2==0)*/){
						//机器的标识为空，属更换设备的情况
						$sql = 'update _user_info set wmac1='.$wifiMac1.
											', wmac2='.$wifiMac2.
										//	', lmac1='.$lanMac1.	//暂且屏蔽有线mac
										//	', lmac2='.$lanMac2.
											' where uid='.$uid;
						$query = $this->db->query($sql);			

						//$remaindays;
						//登陆成功
						//exit('1');
						$this->db->close();	
						return $this->_packUsrInfo('1', $exp, $remaindays, $utype);
					}
					else{
						//设备不匹配
						//登陆失败
						//exit('-3');
						$this->db->close();	
						return $this->_packUsrInfo('-3', $exp, $remaindays, $utype);
					}
				}
				else{
					//(帐号过期！)	
					//登陆失败
					//exit('-2');
					$this->db->close();	
					return $this->_packUsrInfo('-2', $exp, $remaindays, $utype);
				}
			}	
		}
		else{
			//非法请求(破解、懵帐号的人)
			//exit('-10');
			$this->db->close();	
			return $this->_packUsrInfo('-10');
		}

		//$this->db->close();	
	}
	
	
	function getdata($uid=null, $wmac=null, $lmac=null){
		$loginInfo = $this->_getUsrInfo($uid, $wmac, $lmac);
		if($loginInfo['status'] != 1)
			exit;
			
		//获取分类集合	
		$utype = $loginInfo['type'];
		$cataArr = str_split($utype);
		if(count($cataArr) == 0){
			exit;
		}
		
		$this->load->library('MP_Cache');
		$xmlCon = $this->mp_cache->get($uid); 
		$xmlCon = false;
		if ($xmlCon === false) {
			$this->load->database();
			//  创建一个XML文档并设置XML版本和编码。。
			$dom=new DomDocument('1.0', 'utf-8');

			//  创建根节点
			$root = $this->create_root($dom, $loginInfo['remain']);
			foreach($cataArr as $cate){
				$sql = 'select name from __feelive_category where id='.$cate;
				$query = $this->db->query($sql);
				$cataname = null;
				foreach($query->result() as $row){
					$cataname = $row->name;
					break;
				}
				$query->free_result();
				if($cataname == null){
					continue;
				}
				
				//类别
				$category = $this->create_category($dom, $root, $cataname, 0);
				
				$sql = 'select t1.chid,t3.idx, t2.name,t3.url from __feelive_r_cateid_chid t1,
						__feelive_channels t2,__feelive_urls t3	
						where t1.cateid ='.$cate.'  
							and t1.chid = t2.cid 
							and t1.chid=t3.chid order by t1.chid';
				$query = $this->db->query($sql);
				
				$lastchid = -1;
				$tmpdata = array();
				$channel = null;
				foreach($query->result() as $row){
					if($lastchid != $row->chid){
						//开始新频道
						$channel = $this->create_channel($dom, $category, $row->name, 0);	
						$tmpdata = array();
						$lastchid = $row->chid;
					}

					$tmpdata['Url'] = $row->chid.'_'.$row->idx;
					$tmpdata['LineName'] = '';
					$tmpdata['Type'] = 0;
					if($channel != null)
						$this->create_item($dom, $channel, $tmpdata);	
				}
			} //end foreach
			$this->db->close();		
			
			//write cache
			$xmlCon = $dom->saveXML();
			$this->mp_cache->write($xmlCon, $uid, 3600); 
		}
		// else{
			// log_message('error', 'from cache!!');
		// }
		// output
		header('Content-Type: text/xml');	
		echo $xmlCon;	
	}
	
	function upgrade($vercode){
		$desc = '';
		$url = '';
		echo '{desc:\''.$desc.'\', url:\''.$url.'\'}';
		//echo '{desc:\'New 38D APK Welcome!\\n1.this is a test!\\n2.this is a test 2!\', url:\'http://bcs.duapp.com/upgrade/hdpzhib\'}';
	}
	
	
	function geturl($chid, $idx){
		$this->load->database();
		$sql = 'SELECT url FROM `__feelive_urls` where chid='.$chid.' and idx='.$idx;
		$query = $this->db->query($sql);
		
		$url = null;
		foreach($query->result() as $row){
			$url = $row->url;
			break;
		}
		$query->free_result();
		$this->db->close();
		
		if($url != null){
			$this->load->library('Simple_Encry', null, 'myEncy');
			$url = $this->myEncy->encode($url, 'ttmozb_lkck3ns');
		}
		exit($url);
	}
	
	
	//---------------生成uid--------------
	function _seed(){
		list($msec, $sec) = explode(' ', microtime());
		return (float) ($sec+$msec*1000000);
	}
	function _gen_uid($id, $cnt, $maxlen=null){
		$uidArr = array();
		
		$i = 0;
		while($i < $cnt){
			$gid = base_convert($id, 10,8);
			$gid = $gid.'9';
			$len = strlen($gid);
			
			$j = $len;
			if($maxlen==null || strlen($maxlen)==0)
				$maxlen = 9;

			while($j < $maxlen){
				usleep(1000);
				srand($this->_seed());	//播下随机数发生器种子，用srand函数调用seed函数的返回结果
				
				$gid .= rand(0,9);
				$j++;
			}	
			
			$uidArr[] = $gid;
			$id++;
			$i++;
		}

		return $uidArr;
	}



	function gen_try_guid($cnt, $type='1' , $bsave=0){

		//try days: 7 days
		$expr = 7;

		$this->load->database();
		$sql = 'SELECT count(id) as cnt FROM `_user_info`';
		$query = $this->db->query($sql);
		
		$baseId = 0;
		foreach($query->result() as $row){
			$baseId = $row->cnt + 1;
			break;
		}
		$query->free_result();
		$ids = $this->_gen_uid($baseId, $cnt, 6);
		
		if($bsave == 0){
			$this->db->close();
			foreach($ids as $v)
				echo $v.';<br>';
			exit;
		}
	
		$sql_pre = 'insert into _user_info(uid, exp_date, utype) values ';
		$sql_f = null;
		foreach($ids as $id){
			$sql_f = '('.$id.', '.$expr.', \''.$type.'\')' . ',' . $sql_f;
		}
		
		if($sql_f != null){
			$sql = $sql_pre. $sql_f;
			$sql = trim($sql, ', ');
			//exit($sql);
			$this->db->query($sql);
			$this->db->close();
			
			$sql_f = strtr($sql_f, array('),'=>'),<br>'));
			exit($sql_f);
		}
		else{
			echo '没有生成任何id';
		}
	}



	//花了好大力气，修改config，把url中特殊字符处理为允许使用
	function version(){
		$header = $this->input->get('header');
		$body = $this->input->get('body');
//		log_message('error', $header);
//		log_message('error', $body);	

		$hadnew = 0;
		$apkurl = "";
		$md5 = "";
		$vercode = 0;
		$version = '';
		if($body!=null && strlen($body)>1){
			$obj = json_decode($body);
			if($obj != null){
				$vercode = $obj->versioncode;
				//if($vercode < 25){
				//	$hadnew = 1;
				//}
				if($vercode < 38){
					$hadnew = 1;
					$apkurl = 'http://cos.myqcloud.com/1000970/partner/apk/cabadd833396884372ef06cff0a497c6/WHT_20150202131831.apk';
					$md5='71823A47895764CF8FAD712A845F42D7';
					$vercode = 38;
					$version = '3.0.8.00';
				}
			}
		}

	
	log_message('error', 'hadnew='.$hadnew);


		$str = '{"body":{"havenewversion":"'.$hadnew.'","versioncode":"'.$vercode.'","versionname":"'.$version.'","apkurl":"'.$apkurl.'","md5url":"'.$md5.'","feature":""},"header":{"funcId":"","osVersion":"","appId":"","accessToken":"","devType":"2","appVersion":"","retStatus":200,"userId":"","devId":"","retMessage":"ok","userType":"0"},"page":[]}';

		exit($str);
	}

	function gen_guid($cnt, $type='1', $expr=1, $bsave=0){
		$this->load->database();
		$sql = 'SELECT count(id) as cnt FROM `_user_info`';
		$query = $this->db->query($sql);
		
		$baseId = 0;
		foreach($query->result() as $row){
			$baseId = $row->cnt + 1;
			break;
		}
		$query->free_result();
		$ids = $this->_gen_uid($baseId, $cnt);
		
		if($bsave == 0){
			$this->db->close();
			foreach($ids as $v)
				echo $v.';<br>';
			exit;
		}
	
		$sql_pre = 'insert into _user_info(uid, exp_date, utype) values ';
		$sql_f = null;
		foreach($ids as $id){
			$sql_f = '('.$id.', '.$expr.', \''.$type.'\')' . ',' . $sql_f;
		}
		
		if($sql_f != null){
			$sql = $sql_pre. $sql_f;
			$sql = trim($sql, ', ');
			//exit($sql);
			$this->db->query($sql);
			$this->db->close();
			
			$sql_f = strtr($sql_f, array('),'=>'),<br>'));
			exit($sql_f);
		}
		else{
			echo '没有生成任何id';
		}
	}
	//---------------------------------------------------
	
	
	
	
	function create_root($dom, $remain){
		//  创建根节点
		$root = $dom->createElement('Root');
		$dom->appendchild($root);

		//属性
		$RemainingDays = $dom->createAttribute("RemainingDays");
		$root->appendChild($RemainingDays);
		$val = $dom->createTextNode($remain);
		$RemainingDays->appendChild($val);
		
		return $root;
	}

	function create_channel($dom, $cate, $nm, $oderval){
		$channel = $dom->createElement('Channel');
		$cate->appendChild($channel);
		
		$name = $dom->createAttribute("Name");
		$channel->appendChild($name);		
		$val = $dom->createTextNode($nm);
		$name->appendChild($val);		
		
		$order = $dom->createAttribute("Order");
		$channel->appendChild($order);		
		$val = $dom->createTextNode($oderval);
		$order->appendChild($val);		
		
		return $channel;
	}

	function create_category($dom, $root, $nm, $pwval){
		$category = $dom->createElement('Category');
		$root->appendChild($category);
		
		$name = $dom->createAttribute("Name");
		$category->appendChild($name);		
		$val = $dom->createTextNode($nm);
		$name->appendChild($val);		
		
		$pwdmode = $dom->createAttribute("PwdMode");
		$category->appendChild($pwdmode);		
		$val = $dom->createTextNode($pwval);
		$pwdmode->appendChild($val);	

		return $category;
	}

	function create_item($dom, $channel, $itemDataArr){
		$item = $dom->createElement('Item');
		$channel->appendChild($item);
		
		if(is_array($itemDataArr)){
			foreach($itemDataArr as $key=>$val){
				$keyElem = $dom->createElement($key);
				$item->appendChild($keyElem);
					$value = $dom->createTextNode($val);
					$keyElem->appendChild($value);
			}
		}
	}	
		
	
	// $idx base >=1
	/*
	function geturl($chid=null, $idx=null){
		if($chid==null || strlen($chid)<1
			|| $idx==null || strlen($idx)<1){
			exit;
		}

		$key = $chid.'-'.$idx;
		
		$this->load->library('MP_Cache');
		$url= $this->mp_cache->get($key); 
		if ($url === false) {
			$this->load->database();
			
			$sql = 'select url from p2plive_38c_urls where ch_id='.$chid.' and idx='.$idx.' limit 1';
			$query = $this->db->query($sql);
			foreach($query->result() as $row){
				$url = $row->url;
				break;
			}
			$query->free_result();	
			$this->db->close();
			
			$this->mp_cache->write($url, $key, 360); 
		}
		exit($url);
	}
	*/

}

/* End of file shandong.php */
/* Location: ./controllers/shandong.php */
