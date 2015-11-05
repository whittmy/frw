<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');


/*  
    config/autoload.php  curl
    library/Curl.php

*/

// http://www.nybgjd.com/3dclub/test/
class Test  extends CI_Controller {
    
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

    //微信端配置开发接口时用到的校验函数，配置完成后改函数就没有用了。
    function _auth(){
        $echoStr = $this->input->get('echostr');	//如：配置微信服务器该接口时，其值类似：8837890928803343259
        if(!empty($echoStr)){
            log_message('error', 'func: _auth; echostr:'.$echoStr);
            if(!$this->checkSignature()){
                log_message('error', 'checkSignature failed');
                echo $echoStr; exit;
            }
            echo $echoStr; exit;                    
        }
    }
    
	function index(){
        log_message('error', 'func: index;');
        $this->_auth();

		//处理消息
		$this->parserMsg();
	}
	
    /*
        1. 本服务器收到微信通知，需要回复微信服务器
        2. 回复可以是空串，也可以用规定的方式回应(文本图片等等)
        3. 若不回复，则微信服务器会进行3次为期5秒的等待。
    */  
	function parserMsg(){
		//$post = $this->input->post(); 无效
		//$postStr = $GLOBALS["HTTP_RAW_POST_DATA"]; //默认被CI屏蔽了(Input.php)， 微信指定用它
        $postStr = file_get_contents("php://input"); //可以用该方法替代
        log_message('error', 'reciver data from wx: '.$postStr);
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
                log_message('error', 'msgType: '.$msgType);
                $this->handleMessage($postObj, $msgType);
                break;
            case 'event': // 事件消息类型 包括关注、取消关注、自定义菜单点击等；
                log_message('error', 'msgType: '.$msgType);
                $this->handleMessage($postObj, $msgType);
                break;
            case 'image': // 图片消息类型；
                log_message('error', 'msgType: '.$msgType);
                $this->handleMessage($postObj, $msgType);
                break;
            case 'location': // 地理位置信息（用户主动）；
                log_message('error', 'msgType: '.$msgType);
                $this->handleMessage($postObj, $msgType);
                break;
            default:
                log_message('error', 'other msgType: '.$msgType);
                //$resultStr = "未处理事件: " . $msgType;
                //$this->log($resultStr);
                break;
        }
	}
	
	function handleMessage($postObj, $msgType){
		$ret = ' ';
		$fromUsername = $postObj->FromUserName; 
		$toUsername = $postObj->ToUserName; 
 
        switch($msgType) {
            case 'text': // 文本消息类型；
				$content = trim($postObj->Content);  
				$ret = $this->_msgResponeText($fromUsername, $toUsername, '???');
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
	
	
	
	
	////////// 基础支持 /// >>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>
	//API1.  获取access_token接口, url: /token, GET
	function _getAccessToken(){
		$this->load->library('MP_Cache');
		$token = $this->mp_cache->get('mytoken');
		//$token = false;
		if($token === false){
			$appID = 'wx93e990a765f18039';
			$secret = '25a66c9abe27970c377021dbe193c3b2';
			$url = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid='.$appID.'&secret='.$secret;
			$con = @file_get_contents($url);
			if($con == null){
				$con = @file_get_contents($url);
			}
			//log_message('error', 'content:'.$con);
			$jobj = json_decode($con);
			if($jobj == null)
				return null;
				
			$token = $jobj->access_token;
			if(strlen($token) < 50){
				return null;
			}
			$this->mp_cache->write($token, 'mytoken', ($jobj->expires_in-1000));
			unset($tmp);
		}	
		return $token;
	}

    //API2. 多媒体文件(临时)上传接口， url: /media/upload, POST, 
    /*
        1、对于临时素材，每个素材（media_id）会在开发者上传或粉丝发送到微信服务器3天后自动删除（所以用户发送给开发者的素材，若开发者需要，应尽快下载到本地），以节省服务器资源。
        2、media_id是可复用的。
        3、素材的格式大小等要求与公众平台官网一致。具体是，图片大小不超过2M，支持bmp/png/jpeg/jpg/gif格式，语音大小不超过5M，长度不超过60秒，支持mp3/wma/wav/amr格式
        4、需使用https调用本接口。
        5、媒体文件在后台保存时间为3天，即3天后media_id失效
    */

    
    //$type: 图片（image）、语音（voice）、视频（video）和缩略图（thumb）
    //该接口仅仅是生成 微信的上传接口而已，上传在客户端完成，不在服务器端进行(现阶段减轻压力)
    //上传示例： (以表单数据方式上传) curl -F media=@test.jpg "https://api.weixin.qq.com/cgi-bin/media/upload?access_token=ACCESS_TOKEN&type=TYPE"
    //{"errcode":40004,"errmsg":"invalid media type"}
    //{"type":"image","media_id":"Xos_U2rjVdQmeW58T-aEmTruMtlmqzf4sOl8wuS9dFxU1w7BqAh5RcRbuZVdABoI","created_at":123456789}
    function upload_api($type){
        $access_token = $this->_getAccessToken();
        if(empty($access_token)){
            log_message('error', 'fun:upload; access_token invalid!!!');
            exit;
        }
        //在线测试接口 http://file.api.weixin.qq.com/cgi-bin/media/upload?access_token=xx&type=image
        $url = 'https://api.weixin.qq.com/cgi-bin/media/upload?access_token='.$access_token.'&type='.$type;
        header('Location: '.$url);
    }
    //配合上面上传使用，注册已上传成功的meida_id的相关相关信息, 保存在我们数据库中，闲余时间去将微信服务器上的这些临时图片备份下来
    function upload_reg($type, $mediaid, $create_tm){
        
    }
    
    
    //API3. 多媒体文件下载接口， url:/media/get, GET
    //服务器端调用，闲余下载媒体，并记录每天的相关信息以及其它关联信息
    /*
        Cache-Control: no-cache, must-revalidate
        Connection: close
        Date: Tue, 03 Nov 2015 02:37:31 GMT
        Content-Type: image/jpeg
        Content-Length: 165724
        Content-disposition: attachment; filename="Xos_U2rjVdQmeW58T-aEmTruMtlmqzf4sOl8wuS9dFxU1w7BqAh5RcRbuZVdABoI.jpg"
    */
    function dlFromWx($mediaid){
        $access_token = $this->_getAccessToken();
        if(empty($access_token)){
            log_message('error', 'fun:upload; access_token invalid!!!');
            exit;
        }
        $url = 'https://api.weixin.qq.com/cgi-bin/media/get?access_token='.$access_token.'&media_id='.$mediaid;
    }
    //<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<

	





       function _seed() {
	       list($msec, $sec) = explode(' ', microtime());
	       return (float) $sec;

       }		



}

/* End of file shandong.php */
/* Location: ./controllers/shandong.php */
