<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

// http://www.nybgjd.com/3dclub/test/
class Test  extends CI_Controller {
	function index(){
		//$this->valid();
		$echoStr = $this->input->get('echostr');	//$_GET["echostr"];
        if(!$this->checkSignature()){
			log_message('error', 'checkSignature failed');
        	echo $echoStr; exit;
        }
		//暂没有使用
		$access_token = $this->_getAccessToken();
 
		
		//处理消息
		$this->parserMsg();
	}
	
	function parserMsg(){
		//妈的！！！，微信太坑爹，你这样玩有意思吗？？，我都没有见过 $GLOBALS["HTTP_RAW_POST_DATA"]的用法好不。
		//$post = $this->input->post(); 无效
		$postStr = $GLOBALS["HTTP_RAW_POST_DATA"]; 
		if(empty($postStr)){
			exit('');
		}
		
		//用SimpleXML解析POST过来的XML数据  
        $postObj = simplexml_load_string($postStr,'SimpleXMLElement',LIBXML_NOCDATA);  
        // MsgType类型为：text（文本消息）image（图片消息）audio（语音消息）video（视频消息） event location（地理位置消息）
        // event（事件消息）：subscribe（订阅） unsubscribe（取消订阅）YIXINSCAN（扫描推广二维码）CLICK（自定义菜单点击） 
        $msgType = trim($postObj->MsgType); // 消息类型；文本、菜单点击等
        // 可以直接调用 handleMessage()函数，switch一下是为了清晰明了；
        switch($msgType) {
            case 'text': // 文本消息类型；
                $this->handleMessage($postObj, $msgType);
                break;
            case 'event': // 事件消息类型 包括关注、取消关注、自定义菜单点击等；
                $this->handleMessage($postObj, $msgType);
                break;
            case 'image': // 图片消息类型；
                $this->handleMessage($postObj, $msgType);
                break;
            case 'location': // 地理位置信息（用户主动）；
                $this->handleMessage($postObj, $msgType);
                break;
            default:
                //$resultStr = "未处理事件: " . $msgType;
                //$this->log($resultStr);
                break;
        }
	}
	
	function handleMessage($postObj, $msgType){
		$ret = '';
		$fromUsername = $postObj->FromUserName; 
		$toUsername = $postObj->ToUserName; 
 
        switch($msgType) {
            case 'text': // 文本消息类型；
				//$content = trim($postObj->Content);  
				//$ret = $this->_msgResponeText($fromUsername, $toUsername, '???');
				break;
				
            case 'event': // 事件消息类型 包括关注、取消关注、自定义菜单点击等；
                break;
				
            case 'image': // 图片消息类型；

                break;
            case 'location': // 地理位置信息（用户主动）；
                break;
        }	
		
		exit($ret);
	}
	
	
	
	
	//回复文本信息给微信
	/**
		$fromUsername	: 发送方的帐号（OpenID） ，注意这是回复信息给微信， 
		$toUsername ：  获取接收方账号	
		--------- 以上两个正好与接收时的情况相反,但传入时正常传入，内部处理是调换了下顺序
		$msgType ：消息内容
		$contentStr ： 回复内容，明文
	*/
	function _msgResponeText($fromUsername,$toUsername, $contentStr){
	    //回复消息模板  
        $textTpl = "<xml>  
			<ToUserName><![CDATA[%s]]></ToUserName>  
			<FromUserName><![CDATA[%s]]></FromUserName>  
			<CreateTime>%s</CreateTime>  
			<MsgType><![CDATA[text]]></MsgType>  
			<Content><![CDATA[%s]]></Content>  
			<FuncFlag>0</FuncFlag>  
        </xml>";  

        //格式化消息模板  
        $resultStr = sprintf($textTpl,$fromUsername,$toUsername, time(), $contentStr);  
        return $resultStr; //输出结果
	}

	function _msgResponeImg($fromUsername, $toUsername, $mid){
	    //返回消息模板  
        $textTpl = "<xml>
			<ToUserName><![CDATA[%s]]></ToUserName>
			<FromUserName><![CDATA[%s]]></FromUserName>
			<CreateTime>%s</%s>
			<MsgType><![CDATA[image]]></MsgType>
			<Image>
			<MediaId><![CDATA[%s]]></MediaId>
			</Image>
		</xml>";  

        //格式化消息模板  
        $resultStr = sprintf($textTpl,$fromUsername,$toUsername, time(), $mid);  
        return $resultStr; //输出结果
	}
	
	function _msgResponeVoice($fromUsername, $toUsername, $mid){
	    //返回消息模板  
        $textTpl = "<xml>
			<ToUserName><![CDATA[%s]]></ToUserName>
			<FromUserName><![CDATA[%s]]></FromUserName>
			<CreateTime>%s</%s>
			<MsgType><![CDATA[voice]]></MsgType>
			<Voice>
			<MediaId><![CDATA[%s]]></MediaId>
			</Voice>
		</xml>";  

        //格式化消息模板  
        $resultStr = sprintf($textTpl,$fromUsername,$toUsername, time(), $mid);  
        return $resultStr; //输出结果
	}	
	
	function _msgResponeVideo($fromUsername, $toUsername, $mid, $title, $desc){
	    //返回消息模板  
        $textTpl = "<xml>
			<ToUserName><![CDATA[%s]]></ToUserName>
			<FromUserName><![CDATA[%s]]></FromUserName>
			<CreateTime>%s</%s>
			<MsgType><![CDATA[video]]></MsgType>
			<Video>
			<MediaId><![CDATA[%s]]></MediaId>
			<Title><![CDATA[%s]]></Title>
			<Description><![CDATA[%s]]></Description>
			</Video>
		</xml>";  
 	
		
        //格式化消息模板  
        $resultStr = sprintf($textTpl,$fromUsername,$toUsername, time(), $mid, $title, $desc);  
        return $resultStr; //输出结果
	}		
	
	
	
	function SimSimi($keyword) {  
		//----------- 获取COOKIE ----------//  
		$url = "http://www.simsimi.com/";  
		$ch = curl_init($url);  
		curl_setopt($ch, CURLOPT_HEADER,1);  
		curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);  
		$content = curl_exec($ch);  
		list($header, $body) = explode("\r\n\r\n", $content);  
		preg_match("/set\-cookie:([^\r\n]*);/iU", $header, $matches);  
		$cookie = $matches[1];  
		curl_close($ch);  
	  
		//----------- 抓 取 回 复 ----------//  
		$url = "http://www.simsimi.com/func/req?lc=ch&msg=$keyword";  
		$ch = curl_init($url);  
		curl_setopt($ch, CURLOPT_REFERER, "http://www.simsimi.com/talk.htm?lc=ch");  
		curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);  
		curl_setopt($ch, CURLOPT_COOKIE, $cookie);  
		$content = json_decode(curl_exec($ch),1);  
		curl_close($ch);  
	  
		if($content['result']=='100') {  
			$content['response'];  
			return $content['response'];  
		} else {  
			return '我还不会回答这个问题...';  
		}  
	}  	
	
	
	
	
	
	
	
	function _getAccessToken(){
		$this->load->library('MP_Cache');
		$token = $this->mp_cache->get('mytoken');
		//$token = false;
		if($token === false){
			$appID = 'wx3257e9ba7104bad3';
			$secret = '87db0a8c7c51b3e1cb281b3d8d026385';
			$url = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid='.$appID.'&secret='.$secret;
			$con = @file_get_contents($url);
			if($con == null){
				$con = @file_get_contents($url);
			}
			//log_message('error', 'content:'.$con);
			$jobj = json_decode($con);
			if($jobj == null)
				exit;
				
			$token = $jobj->access_token;
			if(strlen($token) < 50){
				exit;
			}
			$this->mp_cache->write($token, 'mytoken', 7100);
			unset($tmp);
		}	
		return $token;
	}

	public function valid() {
        $echoStr = $this->input->get('echostr');	//$_GET["echostr"];
        //valid signature , option
        if($this->checkSignature()){
        	echo $echoStr; exit;
        }
    }
	function checkSignature() {
		define("TOKEN", "lemoon8888");
        // you must define TOKEN by yourself
        if (!defined("TOKEN")) {
            throw new Exception('TOKEN is not defined!');
        }
        $signature = $this->input->get('signature'); //$_GET["signature"];
        $timestamp = $this->input->get('timestamp'); //$_GET["timestamp"];
        $nonce =  $this->input->get('nonce');	//$_GET["nonce"];
        		
		$token = TOKEN;
		$tmpArr = array($token, $timestamp, $nonce);
        // use SORT_STRING rule
		sort($tmpArr, SORT_STRING);
		$tmpStr = implode( $tmpArr );
		$tmpStr = sha1( $tmpStr );
		
		if( $tmpStr == $signature ){
			return true;
		}else{
			return false;
		}
	}
	
	
	
	
	
	
	
	
	
	
	
	function doc($type=null){
		if($type==null){
			$type = 'all';
		}
		
		if($type == 'all'){
			header('Location: http://mp.weixin.qq.com/s?__biz=MjM5NzUxMTg0Mg==&mid=205587535&idx=1&sn=a7c557ef7fac1c347a7175a6a4652084&scene=1&from=groupmessage&isappinstalled=0');
		}
		else if($type == 'flyscr'){
			//header('Location: http://www.sohu.com');
		}
		else if($type == 'fp_dload'){
			header('Location: http://7xiolu.com1.z0.glb.clouddn.com/3D部落-电脑飞屏工具.zip');
		}
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

	
	function version2(){
        $header = $this->input->get('header');
        $body = $this->input->get('body');		

		$this->load->library('MP_Cache');
		$cachePrefix = 'api4.version2.';
		
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
		else{
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

		$this->load->library('MP_Cache');
		$cacheName = 'api4.mv.class';
		$data1 = $this->mp_cache->get($cacheName);
		if($data1 === false){
			$this->load->database('vr');

			//3D专区(t_id=1， 为各子类的父类)
			$ret = array();
			$sql = 'select t_id,t_name from vr_vod_type where t_pid=1 and t_hide=0 order by t_sort';	
			$query = $this->db->query($sql);	
			foreach($query->result() as $row){
				$cif = array();
				$cif['id'] = $row->t_id.'';
				$cif['name'] = $row->t_name;
				$cif['logo'] = '';
				$cif['squarelogo'] = '';
				$cif['v3squarelogo'] = '';
				$cif['content'] = '';
				$cif['transverse'] = 0;
				$cif['total'] = 0;
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
			$data1 = json_encode($ret);		
			$this->mp_cache->write($data1, $cacheName, 900);
		}
		exit($data1);
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


		$this->load->library('MP_Cache');
		$cacheName = 'api4.mv.list-'."$clsId-$pgId-$pgsize";
		
		$data1 = $this->mp_cache->get($cacheName);
		//$data1 = false;
		if($data1 === false){
			require  APPPATH."include/vr_mv_cache.php";  
			$clsname = $MAC_MV_CACHE['vodtype'][$clsId]['t_name'];
			
			$host = 'http://www.nybgjd.com/vr/';
			$ret = array();
			
			$this->load->database('vr');
			$sql = 'select d_id id, d_name chinesename, d_ex_pptvmid pptvmovieid,FROM_UNIXTIME(d_addtime,"%Y-%m-%d %H:%i:%s") createtime,d_playurl web_url,d_pic images,d_picthumb crossimages,d_hits playnum,d_content recommend from vr_vod where d_type='.$clsId.' and d_lock!=1 order by d_addtime desc limit '.($pgId-1)*$pgsize.", $pgsize";	
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
				$cif['movie_definition'] = '';
				//$cif['images'] = $host.$row->images;
				//$cif['crossimages'] = $host.$row->crossimages;
				$cif['images'] = '';
				$cif['crossimages'] = '';			
				$cif['playnum'] = $row->playnum;
				$cif['recommend'] = $row->recommend;
			
				$cif['classname'] = $clsname;
				
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

			$data1 = json_encode($ret);
			$this->mp_cache->write($data1, $cacheName, 900);
		}
		exit($data1);
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

		$this->load->library('MP_Cache');
		$cacheName = 'api4.game.class';
		$data1 = $this->mp_cache->get($cacheName);
		if($data1 === false){		
			
			$this->load->database('vr');
			$ret = array();
			$sql = 'select t_id,t_name from vr_game_type where t_pid=1 and t_hide=0 order by t_sort';	
			$query = $this->db->query($sql);	
			foreach($query->result() as $row){
				$cif = array();
				$cif['id'] = $row->t_id.'';
				$cif['name'] = $row->t_name;
				$cif['logo'] = '';//'http://www.nybgjd.com/vr/'.$row->t_pic;
				$cif['content'] = '';
				$cif['total'] = 0;
				$ret['body']['gameclassList'][] = $cif;	
			}
			$query->free_result();
			$this->db->close();		

			$ret['header']['sign'] = $h_sign;
			$ret['header']['versionCode'] = $vercode;
			$ret['header']['versionName'] = $vername;
			$ret['header']['packageName'] = $packname;
			$ret['header']['reqtime'] = $rqtime.'';
			$ret['header']['retMessage'] = 'ok';
			$ret['header']['retStatus'] = 200;

			//page
			$ret['page'] = array();

			$data1 = json_encode($ret);
			$this->mp_cache->write($data1, $cacheName, 900);
		}	
		exit($data1);
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
		
		if(isset($jobj->classid)&& isset($jobj->pagesize) && isset($jobj->pageindex)){
			$clsId = $jobj->classid;
			$pgId = $jobj->pageindex;
			$pgsize = $jobj->pagesize;
		}
		else{
			$clsId = 14;
			$pgId = 1;
			$pgsize = 8;
		}

		$this->load->library('MP_Cache');
		$cacheName = 'api4.game.list-'."$clsId-$pgId-$pgsize";
		$data1 = $this->mp_cache->get($cacheName);
		if($data1 === false){
			$ret = array();
			$this->load->database('vr');
			$sql = 'SELECT d_id, d_name, FROM_UNIXTIME(d_addtime,'."'%Y-%m-%d %H:%i:%s' )".' createtime, d_version,  d_pic , d_size, d_ext, d_packname, d_downurl, d_hits  FROM vr_game where d_type='.$clsId.' and d_lock!=1 order by d_addtime desc limit '.($pgId-1)*$pgsize.", $pgsize";	

			//exit($sql);
			$host = 'http://www.nybgjd.com/vr/';
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
				$cif['images'] = 'http://www.nybgjd.com/vr/'.$row->d_pic;
				$cif['file'] = $row->d_downurl;
				$cif['packagename'] = $row->d_packname;
				$cif['downloadnum'] = $row->d_hits;
				$cif['ext'] =  $row->d_ext;
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

			$data1 = json_encode($ret);
			$this->mp_cache->write($data1, $cacheName, 900);
		}	
		exit($data1);
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

		$this->load->library('MP_Cache');
		$cacheName = 'api4.game.detail-'.$id;
		
		$data1 = $this->mp_cache->get($cacheName);
		if($data1 === false){
			$ret = array();
			$this->load->database('vr');
			$sql = 'select d_id, d_name, d_pic, d_downurl,d_content,d_version, d_size,   d_hits,d_img1,d_img2,d_img3,d_img4, d_addtime, d_packname, d_ext, d_type from vr_game where d_id='.$id;
			$query = $this->db->query($sql);

			$host = 'http://www.nybgjd.com/vr/';
			$cif = array();
			$img = array();
			foreach($query->result() as $row){
				$cif['id'] =  $row->d_id;
				$cif['title'] =  $row->d_name;
				$cif['pic'] =  $host.$row->d_pic;
				$cif['file'] =   $row->d_downurl ;
				$cif['content'] =  $row->d_content;
				$cif['version'] =  $row->d_version;
				$cif['size'] =  $row->d_size;
				$cif['recommends'] =  '';
				$cif['recommendimg'] =   '';
				$cif['downloadnum'] =  $row->d_hits;
				$cif['addtime'] =  $row->d_addtime;
				$cif['status'] =  '1';
				$cif['packagename'] =  $row->d_packname;
				$cif['classid'] =  $row->d_type;
				$cif['ext'] =  $row->d_ext;
				
				if(!empty($row->d_img1))
					$img[]['img'] = $host.$row->d_img1;
				if(!empty($row->d_img2))
					$img[]['img'] = $host.$row->d_img2;
				if(!empty($row->d_img3))
					$img[]['img'] = $host.$row->d_img3;
				if(!empty($row->d_img4))
					$img[]['img'] = $host.$row->d_img4;
					
				break;
			}
			$query->free_result();
			$this->db->close();

			if(count($cif) > 0){
				$cif['images'] = $img;
				$ret['body']['gameDetail'] = $cif;	
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
			$data1 = json_encode($ret);
			$this->mp_cache->write($data1, $cacheName, 900);
		}	
		exit($data1);
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
