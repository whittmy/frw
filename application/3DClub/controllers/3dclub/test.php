<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');


/*  
    + Curl Library
        - config/autoload.php  curl
        - library/Curl.php
*/

define("TOKEN", "lemoon8888");

// http://www.nybgjd.com/3dclub/test/
class Test  extends CI_Controller {
    //>>> 先参考底部的 ‘基础支持’

    /**
        微信公众号 开发接口
    */
	function index(){
        $this->logi('[index]: enter!');
        $this->_auth();

		//处理消息
		$ret = $this->parserMsg();
        
        $this->logi('[index]:leave!!!  respone to wx: '.$ret);
        exit($ret);
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
        $this->logi('[parserMsg]: recive data from wx: '.$postStr);
		if(empty($postStr)){
			exit('');
		}
		
		//用SimpleXML解析POST过来的XML数据  
        $postObj = simplexml_load_string($postStr,'SimpleXMLElement',LIBXML_NOCDATA);  
        // MsgType类型为：text（文本消息）image（图片消息）audio（语音消息）video（视频消息） event location（地理位置消息）
        // event（事件消息）：subscribe（订阅） unsubscribe（取消订阅）YIXINSCAN（扫描推广二维码）CLICK（自定义菜单点击） 
        $msgType = trim($postObj->MsgType); // 消息类型；文本、菜单点击等
        // 可以直接调用 handleMessage()函数，switch一下是为了清晰明了；
        
        $ret = $this->handleMessage($postObj, $msgType);
        return $ret;
	}
	
	function handleMessage($postObj, $msgType){
        $this->logi('[handleMessage]: msgType='.$msgType);

		$ret = ' ';
		$fromUsername = $postObj->FromUserName; 
		$toUsername = $postObj->ToUserName; 
 
        switch($msgType) {
            case 'text': // 文本消息类型；
				$content = trim($postObj->Content);  
				$ret = $this->_msgResponeText($fromUsername, $toUsername, '???');
				break;
				
            case 'event':
                $event = trim($postObj->Event);
                switch($event){
                case 'subscribe': // 关注    
                    break;
                case 'unsubscribe': //取消关注, 清除用户、以及与设备的关系
                    $this->unsubscribe($postObj->FromUserName);
                    break;
                case 'CLICK':       //自定义菜单点击等；
                    $key = trim($postObj->EventKey);
                    break;                    
                }
                break;

            case 'image': // 图片消息类型
                $this->load->database('vr');
                $uid = $this->_getUserIdx($fromUsername);
                $didarr = $this->_getDidsInRByUid($uid);
                foreach($didarr as $did){
                    if(empty($sql)){
                        $sql = 'insert into mp_push_list(p_did,p_tm) values ';
                    }
                    $sql .= '('.$did.','.time().'),';
                }
                if(!empty($sql)){
                    $sql = trim($sql, ',');
                    $this->db->query($sql);
                }
                
                $type = 1;
                $createtime = $postObj->CreateTime;
                $picurl = $postObj->PicUrl;
                $mediaid = $postObj->MediaId;
                $sql = "insert into mp_msgs (m_uid,m_msgtype,m_createtime,m_picurl,m_mediaid) values($uid,$type,$createtime,'$picurl','$mediaid')";
                $this->db->query($sql);
                $this->db->close();
            
                break;
            case 'location': // 地理位置信息（用户主动）；
                break;
                
            //==================== 设备消息 ==============    
            case 'device_text': //设备消息处理
                $data = base64_decode($postObj->Content);
                
                // 逻辑处理
                //...
                
                //推送文本消息给微信
                // ... 客服
                break;
                
                
            case 'device_event': //设备事件处理 绑定、解绑， 我们数据库中的用户都是绑定设备的用户，不应该包含仅仅只是订阅的用户。
                $this->handle_device_event($postObj);
                break;                
                
        }	
		
		return $ret;
	}
	
    
    function album_data(){
        $ret = array();
        $ret['body']['usrList'] = array();
        $ret['body']['picList'] = array();
        $retMessage = 'ok';
        $retStatus = 200;
        
        $header = $this->input->get('header');
        $body = $this->input->get('body');
        $jobj = json_decode($body);
        $pageidx = empty($jobj->pageindex)? 1 : $jobj->pageindex;
        $pagesize = 6;

        $jobj = json_decode($header);
        $mac = strtoupper($jobj->mac);
        
        if(strlen($mac) != 12){
            $retMessage = 'MAC invalid!';
            $retStatus = 201;
        }
        else{

            $this->load->database('vr');
            $didx = $this->_getDeviceIdxByMac($mac);
            $users = $this->_getUidsInR($didx);

            if(count($users) == 0){
                $retMessage = 'the device had no bind!!';
                $retStatus = 202;
            }
            else{
                $userstr = trim(implode(',', $users), ', ');
                if($pageidx == 1){
                    $sql = 'select u_id,u_nickname,u_headimgurl from mp_users where u_id in ('. $userstr .')';
                    $query = $this->db->query($sql);
                    foreach($query->result() as $row){
                        $ret['body']['usrList'][] = array('uid'=> $row->u_id,'name'=>$row->u_nickname, 'pic'=>$row->u_headimgurl);
                    }
                    $query->free_result();
                }
                
                //$sql = 'select m_uid,m_createtime,m_picurl from mp_msgs where m_uid in ('.$userstr.') and m_msgtype=1 and m_createtime<=(select m_createtime from mp_msgs order by m_createtime desc limit '.($pageidx-1)*$pagesize.', 1)  order by m_createtime desc limit '.$pagesize;
                $sql = 'select m_uid,m_createtime,m_picurl from mp_msgs where m_uid in ('.$userstr.') and m_msgtype=1 order by m_createtime desc limit '.($pageidx-1)*$pagesize.','. $pagesize;
                //exit($sql);
                $query = $this->db->query($sql);
                foreach($query->result() as $row){
                    $ret['body']['picList'][] = array('uid'=>$row->m_uid, 'pic'=>$row->m_picurl,'date'=>$row->m_createtime);
                }
                $query->free_result();
            }

            $this->db->close();            
        }

        
        $ret['header']['retMessage'] = $retMessage;
        $ret['header']['retStatus'] = $retStatus;
 
 
        $ret['page']['pageindex'] = $pageidx;
        $ret['page']['pagecount'] = 0;
        $ret['page']['count'] = count($ret['body']['picList']);

    
        $rslt = json_encode($ret);
        exit($rslt);
    }
    
	//我们数据库中的用户都是绑定设备的用户，不应该包含仅仅只是订阅的用户。
    function handle_device_event($postObj){
        $this->logi('[handle_device_event] enter!');
        $event = trim($postObj->Event);
        // 处理用户与设备的关系
        if($event == 'bind'){
            /* 我们收到来自微信的消息格式
            <xml>
                <ToUserName><![CDATA[%s]]></ToUserName>         公众号的user name，即OpenID, 即 deviceType
                <FromUserName><![CDATA[%s]]></FromUserName>   微信用户user name ， 和 OpenID 相同
                <CreateTime>%u</CreateTime>
                <MsgType><![CDATA[%s]]></MsgType>
                <Event><![CDATA[%s]]></Event>
                <DeviceType><![CDATA[%s]]></DeviceType>     同一型号设备的 devicetype值相同
                <DeviceID><![CDATA[%s]]></DeviceID>
                <Content><![CDATA[%s]]></Content>           二维码中附加的数据
                <SessionID>%u</SessionID>               忽略
                <OpenID><![CDATA[%s]]></OpenID>
            </xml>
            */
            $this->_saveBindInfo($postObj);
            
        }
        elseif($event == 'unbind'){
            // 格式同 bind
            $this->_removeBindInfo();
            
        }        
    }

    
    function unsubscribe($openid){
        $this->logi('[unsubscribe]: enter! '."($openid)");
        $this->load->database('vr');
        $this->_delUser($openid);
        $this->db->close();
    }
    
    // no transation handle
    function _delUser($openid){ //注意
        $this->logi('[_delUser]: enter!'."($openid)");
        $uidx = $this->_getUserIdx($openid);
        if($uidx == -1)
            return;
        $sql = "delete from mp_users where u_id=$uidx";
        $this->db->query($sql);
        
        $sql = "delete from mp_r_user_devs where r_uid=$uidx";
        $this->db->query($sql);
    }
    

    //活动于夜深人静时(用户自行更改其昵称或头像是自动通知我们的，所以需要主动去取)
    function updateAllUser(){
        $this->load->database('vr');
        $hadend = false;

        while(!$hadend){
            $hadend = true;
            $users = array();

            $sql = 'select u_openid  from mp_users where to_days(now())-to_days(u_tm)>1 limit 20';
            $query = $this->db->query($sql);
            foreach($query->result() as $row){
                $hadend = false;
                $users[] = $row->u_openid;
            }
            $query->free_result();
            $this->updateUserinfo($users); //更新后，时间戳会变成最新,再次查询就不会再被取到。
       }

        $this->db->close();
        echo '-=-=-= finished -=-=-=';
    }
    
    function updateUserinfo($userArr=array()){
        //$userArr = array('oIXMluGgJBLSVrGSPYYxM2yeA4MY', 'oIXMluKmmcfRao5Jlp5X3EL2V8ls');
        $data = array();
        foreach($userArr as $user){
            $data['user_list'][] = array('openid'=>$user, 'lang'=>'zh-CN');
        }
        if(count($data) == 0)
            return;
        
        $api = 'https://api.weixin.qq.com/cgi-bin/user/info/batchget?access_token=ACCESS_TOKEN';
        $con = $this->_doPost($api, json_encode($data));
        //return $con;
        //exit($con.'ss');
        $jobj = json_decode($con);
        $userlist = $jobj->user_info_list;
        foreach($userlist as $uinfo){
            $openid = $uinfo->openid;
            $nick = empty($uinfo->nickname)? '' : $uinfo->nickname;
            $head = empty($uinfo->headimgurl)? '' : $uinfo->headimgurl;
            $sql = "update mp_users set u_nickname='$nick', u_headimgurl='$head' where u_crc_openid=crc32('$openid') and u_openid='$openid'";
            echo "$nick($openid)".'<br>';
            $this->db->query($sql);
        }
    }
    
    
    ///>>>>> 维护用户与设备的对应关系 >>>>>>>>>>>>>>>>>>>>>
    /** 初步表结构
        create table mp_users(
            u_id bigint NOT NULL AUTO_INCREMENT ,
            u_openid varchar(60) NOT NULL,
            u_crc_openid bigint NOT NULL,
            PRIMARY KEY (`u_id`),
            INDEX (`u_crc_openid`)
         );
         create table mp_r_user_devs(
            r_id bigint NOT NULL AUTO_INCREMENT,
            r_uid bigint NOT NULL,
            r_did bigint NOT NULL,
            PRIMARY KEY(r_id),
            INDEX(r_uid),
            INDEX(r_did)
         );
    */
    //OpenID -> deviceid
    function _saveBindInfo($xmlObj){
        $this->logi('[_saveBindInfo]: enter!');
        $this->load->database('vr');
        
        $didx = $this->_getDeviceIdx($xmlObj->DeviceID);
        if($didx == -1){
           //error 
           $this->loge('[_getDeviceIdx]: return -1');
        }
        $uidx = $this->_getUserIdx($xmlObj->OpenID);
        if($uidx == -1){
            //插入用户
            $uidx = $this->_addUser($xmlObj->OpenID);
        }
        
        //更新R, 可能会有重复插入！！！！
        $this->_addR($uidx, $didx);
        
        $this->db->close();
    }
    
    //这里没有 清除用户， 删除用户始终通过 Event-unsubscribe消息来处理。
    function _removeBindInfo($xmlObj){
        $this->logi('[_removeBindInfo]: enter!');
        $this->load->database('vr');
        $didx = $this->_getDeviceIdx($xmlObj->DeviceID);
        $uidx = $this->_getUserIdx($xmlObj->OpenID);
        $this->_delR($uidx, $didx); 
        $this->db->close();
    }
    
    /**
        删除一个 用户-设备的对应关系
    */
    function _delR($uidx, $didx){
        $this->logi('[_delR]: enter!'."($uidx, $didx)");
        $sql = "delete from mp_r_user_devs where r_did=$didx and r_uid=$uidx";
        $this->db->query($sql);
    }
    
    /**
        新增一个 用户-设备 的对应关系
    */
    function _addR($uidx, $didx){
        $this->logi('[_addR] enter! '."($uidx, $didx)");
        $sql = "insert into mp_r_user_devs(r_did, r_uid) values ($didx, $uidx)";
        $this->db->query($sql);
    }
    
    /**
        像用户表 新增一个 openid
    */
    function _addUser($openid){
        $this->logi('[_addUser]: enter! '."($openid)");
        $sql = "insert into mp_users (u_openid,u_crc_openid) values ('$openid', CRC32('$openid'))";
        $this->db->query($sql);
        $id = $this->db->insert_id();
        $this->logi('[_addUser]: return value: '.$id);
        return $id;
    }
    
    /**
        依据 deviceid 获取其在表中对应的 id号(唯一)
    */
    function _getDeviceIdx($deviceid){
        $this->logi('[_getDeviceIdx]: enter!'."($deviceid)");
        $idx = -1;
        $sql = "select d_id from mp_devices where d_crc_deviceid=CRC32('$deviceid') and d_deviceid='$deviceid'";
        $query = $this->db->query($sql);
        foreach($query->result() as $row){
            $idx = $row->d_id;
            break;
        }
        $query->free_result();
        $this->logi('[_getDeviceIdx]: return value: '.$idx);
        return $idx;
    }
    /**
        依据 mac 获取其在表中对应的 id号(唯一)
    */
    function _getDeviceIdxByMac($mac){
        $this->logi('[_getDeviceIdxByMac]: enter!'."($mac)");
        $idx = -1;
        $sql = "select d_id from mp_devices where d_crc_mac=CRC32('$mac') and d_mac='$mac'";
        $query = $this->db->query($sql);
        foreach($query->result() as $row){
            $idx = $row->d_id;
            break;
        }
        $query->free_result();
        $this->logi('[_getDeviceIdxByMac]: return value: '.$idx);
        return $idx;
    }    
    
    /**
        依据 openid获取其在表中对应的 id号(唯一)
    */
    function _getUserIdx($openid){
        $this->logi('[_getUserIdx]: enter!'."($openid)");
        $idx = -1;
        $sql = "select u_id from mp_users where u_crc_openid=CRC32('$openid') and u_openid='$openid'";
        $query = $this->db->query($sql);
        foreach($query->result() as $row){
            $idx = $row->u_id;
            break;
        }
        $query->free_result();
        $this->logi('[_getUserIdx]:  return value: '.$idx);
        return $idx;        
    }
    
    
    /*didx数组*/
    function _getDidsInRByUid($uid){
        $this->logi('[_getDidsInRByUid]: enter!'."($uid)");
        $sql = "select r_did from mp_r_user_devs where r_uid=$uid";
        $query = $this->db->query($sql);
        $dids = array();
        foreach($query->result() as $row){
            $dids[] = $row->r_did;
        }
        $query->free_result();
        return $dids;
    }
    
    
    /*uidx数组*/
    function _getUidsInR($didx){
        $this->logi('[_getUidsInR]: enter!'."($didx)");
        $sql = "select r_uid from mp_r_user_devs where r_did=$didx";
        $query = $this->db->query($sql);
        $uids = array();
        foreach($query->result() as $row){
            $uids[] = $row->r_uid;
        }
        $query->free_result();
        return $uids;
    }
    
    
    /**
        R表中 返回 r_did对应的所有用户(openid数组)
    */
    function _getUsersInR($didx){
        $this->logi('[_getUsersInR]: enter!'."($didx)");
        $arr = $this->_getUidsInR($didx);
        
        $users = array();
        $uids = trim(implode(',', $arr), ',');
        if(!empty($uids)){
            $sql = "select u_openid from mp_users where u_id in ($uids)";
            $query = $this->db->query($sql);
            foreach($query->result() as $row){
                $users[] = $row->u_openid;
            } 
            $query->free_result();
        }
        $this->logi('[_getUsersInR]:  return value: '. implode(',', $users));
        return $users;
    }
    
    /**
        根据deviceId/mac串， 获取其绑定的用户openid集合
    */
    function _getUsersByDevinfo($devinfo){
        $this->logi('[_getUsersByDevinfo]: enter!');
        $didx = -1;
        if(isset($devinfo['deviceid'])){
            $didx = $this->_getDeviceIdx($devinfo['deviceid']);
        }
        elseif(isset($devinfo['mac'])){
            $didx = $this->_getDeviceIdxByMac($devinfo['mac']);
        }

        $arr = $this->_getUsersInR($didx);
        
        $this->logi('[_getUsersByDevinfo]: return value: '. implode(',', $arr));
        return $arr;
    }
    //<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<
	
	
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
	
 
    ///////////          微信公众后台 开发接口配置          ////>>>>>>>>>>>>>>>>>>>>>>>> 
    //微信端配置开发接口时用到的校验函数，配置完成后改函数就没有用了。
    function _auth(){
        $echoStr = $this->input->get('echostr');	//如：配置微信服务器该接口时，其值类似：8837890928803343259
        $this->logi('[_auth]: enter! echostr(from wx) = '.$echoStr);

        if(!empty($echoStr)){
            if(!$this->_checkSignature()){
                $this->loge('[_auth]: checkSignature failed');
                echo $echoStr; exit;
            }
            echo $echoStr; exit;                    
        }
    }
    
    /**
        配置微信后台接口时的 校验机制
        使用时机： 配置微信后台接口时
    */
	function _checkSignature() {
        // you must define TOKEN by yourself
        if (!defined("TOKEN")) {
            throw new Exception('TOKEN is not defined!');
        }
        $signature = $this->input->get('signature'); //$_GET["signature"];
        $timestamp = $this->input->get('timestamp'); //$_GET["timestamp"];
        $nonce =  $this->input->get('nonce');	//$_GET["nonce"];
		$tmpArr = array(TOKEN, $timestamp, $nonce);
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
    ///<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<
    
    
    
    /////// 自定义日志  //////>>>>>>>>>>>>>
    function logi($msg){
        log_message('ERROR', $msg);
    }
    function loge(){
        log_message('ERROR', $msg);
    }
    
    
    //<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<
    
    
    /////////////////   微信 - 设备   //////// >>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>
    /**
        0. 应用接口 getQrCode
         设备端通过访问该接口 实现认证设备，并返回设备对应的二维码
            获取deviceid&授权 这两个操作合为一个，对server端会要求高些，但是客户端只需要作一次请求即可(否则客户端需要进行分步处理)。
        使用时机： 客户端展示二维码
        返回值：包含用于生成二维码图片的‘生成串’
        涉及表：
            CREATE TABLE `mp_devices` (
                `d_id`  bigint NOT NULL AUTO_INCREMENT ,
                `d_model`  varchar(50) NOT NULL DEFAULT '' ,
                `d_ver` varchar(20) NOT NULL DEFAULT '',
                `d_mac` char(12) NOT NULL DEFAULT '',
                `d_deviceid` varchar(100) NOT NULL DEFAULT '',
                `d_devtype` varchar(50) NOT NULL DEFAULT '',
                `d_qrticket` varchar(100) NOT NULL DEFAULT '',
                `d_crc_deviceid` bigint NOT NULL DEFAULT 0,
                `d_crc_mac`  bigint NOT NULL DEFAULT 0 ,
                `tm`  timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`d_id`),
                INDEX (`d_crc_mac`),
                INDEX(`d_crc_deviceid`) 
                );
        
        http://www.nybgjd.com/3dclub/test/getQrCode?info={"mac":"0011AA334455","model":"PiaPia", "ver":"00.22.11"}
    **/
 
    function getQrCode(){
        $this->logi('[getQrCode]: enter!');

        $ret = array();

        $retMessage = 'ok';
        $retStatus = 200;
        $qrticket = '';
        
        $header = $this->input->get('header');
        $jobj = json_decode($header);
        $mac = strtoupper($jobj->mac);
        $model ='';
        $ver = empty($jobj->firmware)?'' : $jobj->firmware;        
        if(strlen($mac) != 12){
            $retMessage = 'MAC invalid!';
            $retStatus = 201;
        }
        else{
            $this->load->database('vr');
            $sql = "select d_qrticket from `mp_devices` where d_crc_mac=CRC32('$mac') and d_mac='$mac'";
            //exit($sql);
            $query = $this->db->query($sql);
            foreach($query->result() as $row){
                $qrticket = $row->d_qrticket;
                break;
            }
            $query->free_result();
            
            //若已经授权则直接返回
            if(!empty($qrticket)){
                $this->logi('[getQrCode]: had authed!');
            }
            else{
                //先生成一个 deviceId
                $genDevInfo = $this->_getDeviceID();
                //print_r($genDevInfo);
                //echo '<br><br>';
                if($genDevInfo['code'] != 0){
                    $this->loge('[getQrCode]: error status=> '.$genDevInfo['code'].', '.$genDevInfo['msg']);                  
                    $retMessage = $genDevInfo['msg'];
                    $retStatus = $genDevInfo['code'];
                }
                else{
                    $qrticket = $genDevInfo['qcode'];
                    $devid = $genDevInfo['devid'];
                    $AuthInfo = $this->_deviceAuthOne($devid, $mac);
                    //print_r($AuthInfo);
                    //echo '<br><br>';            
                    if($AuthInfo['code'] != 0){
                        $this->loge('[getQrCode]: error status=> '.$AuthInfo['code'].', '.$AuthInfo['msg']);
                        $retMessage = $AuthInfo['msg'];
                        $retStatus = $AuthInfo['code'];
                    }   
                    else{
                        $devtype = $AuthInfo['devtype'];
            
                        //更新数据库记录
                        $sql = "insert into mp_devices (d_model,d_mac,d_ver, d_deviceid,d_devtype,d_qrticket,d_crc_deviceid,d_crc_mac) 
                                    values ('$model','$mac','$ver','$devid','$devtype','$qrticket',CRC32('$devid'),CRC32('$mac'))";
                        //exit($sql);
                        $query = $this->db->query($sql);
                        if($query == False){
                            $this-loge('[getQrCode]: error status=> sql error! sql= '.$sql);
                            $retMessage = 'db error!';
                            $retStatus = 3306;
                        }  
                    }
                }
            }
            $this->db->close();
        }

        $ret['body']['qrcode'] = $qrticket;

        $ret['header']['retMessage'] = $retMessage;
        $ret['header']['retStatus'] = $retStatus;

        $ret['page'] = json_decode('{}');
        //print_r($ret['page']);

        $rslt = json_encode($ret);
        exit($rslt);
    }
 
    /**
       http://www.nybgjd.com/3dclub/test/getDeviceID
       1.  获取设备ID
       使用时机： 获取二维码时， 或发售前预先批量授权设备
    */
    function _getDeviceID(){
        $this->logi('[_getDeviceID]: enter!');
        $api = 'https://api.weixin.qq.com/device/getqrcode?access_token=ACCESS_TOKEN';
        $con = $this->_doGet($api);
        $jobj = json_decode($con);
        
        $mydata = '#my base64 data';
        
        $errcode = $jobj->base_resp->errcode;
        $errmsg = $jobj->base_resp->errmsg;
        $deviceid = $jobj->deviceid;
        $qrticket = $jobj->qrticket; // .$mydata
        unset($jobj);
        
        //exit($errcode.', '.$errmsg.', '.$deviceid.', '.$qrticket);
        $this->logi('[_getDeviceID]: return devid='.$deviceid.', qrcode='.$qrticket);
        return array('code'=>$errcode, 'msg'=>$errmsg, 'devid'=>$deviceid, 'qcode'=>$qrticket);
    }
    
    /**
            http://www.nybgjd.com/3dclub/test/deviceAuth
        2. 公众号对 device id授权     POST
        使用时机： 获取二维码时， 或发售前预先批量授权设备
    */
    function _deviceAuthOne($deviceid, $mac){
        $this->logi('[_deviceAuthOne]: enter!');
        $api = 'https://api.weixin.qq.com/device/authorize_device?access_token=ACCESS_TOKEN';
        
        $dev = array();
        $dev['id'] = $deviceid;
        $dev['mac'] = $mac; //0011223344AA
        $dev['connect_protocol'] = "4"; // 1: android-classic-bluetooh; 2: ios-classic-bluetooh; 3:ble; 4:wifi;  分隔符"|"
        $dev['auth_key'] = "1234567890ABCDEF1234567890ABCDEF";
        $dev['close_strategy'] = "3";  //不断开(微信尝试重连)
        $dev['conn_strategy'] = "8";
        $dev['crypt_method'] = "0";
        $dev['auth_ver'] = "0";
        $dev['manu_mac_pos'] = "-2";
        $dev['ser_mac_pos'] = "-2";
                
        $data =  array();
        $data['device_list'][] = $dev ;
        $data['device_num'] = "1";
        $data['op_type'] = "1";  //1：设备更新（更新已授权设备的各属性值）。  非必须
        
        $poststr = json_encode($data);
        unset($data); unset($dev);
        //exit($poststr);
        
        $rlst = $this->_doPost($api, $poststr);
        //exit($rlst);
        
        $jobj = json_decode($rlst);
        // data:
        //{"resp":[
        //      {"base_info":{"device_type":"gh_c0b5d772ae73",     // 原始用户ID
        //                    "device_id":"gh_c0b5d772ae73_bb17567496e82b368ebc726298e71ae1"},
        //          "errcode":0,"errmsg":"ok"}
        //    ]
        //}
        
        $code = $jobj->resp[0]->errcode;
        $msg = $jobj->resp[0]->errmsg;
        $devtype = $jobj->resp[0]->base_info->device_type;
        
        $this->logi('[_deviceAuthOne]: return values: devtype='.$devtype);
        return array('code'=>$code, 'msg'=>$msg, 'devtype'=>$devtype);
    }
    
    
    /**
        批量授权/更新设备属性
        暂缺
    */
    
    
    /**
        3. 查询设备状态 
        使用时机： 基本不用
            return: 0(未授权)； 1(已授权但为被绑定)；2(已被用户绑定)
    */
    function _getStat($deviceId){
        $this->logi('[_getStat]: enter');
        $api = "https://api.weixin.qq.com/device/get_stat?access_token=ACCESS_TOKEN&device_id=DEVICE_ID";
        $api = strtr($api, array('DEVICE_ID'=>$deviceId));
    }
    
    
    /**
        4. 验证二维码是否合法
        使用时机： 基本不用
    */
    function _verifyQrCode($ticket){
        $this->logi('[_verifyQrCode]: enter!');
        $api = "https://api.weixin.qq.com/device/verify_qrcode?access_token=ACCESS_TOKEN";
        $rslt = $this->_doPost($api, '{"ticket":"'.$ticket.'"}');
        return $rslt;
    }
    
    
    /**
        5. 根据设备类型和设备id查询绑定的openid
        使用时机：查看哪些用户共用一个设备，也可以不用，
    */
    function getOpenId($devtype, $devid){
        $this->logi('[getOpenId]: enter!');
        $api = 'https://api.weixin.qq.com/device/get_openid?access_token=ACCESS_TOKEN&device_type=DEVICE_TYPE&device_id=DEVICE_ID';
        $api = strtr($api, array('DEVICE_TYPE'=>$devtype, 'DEVICE_ID'=>$devid));
        $rslt = $this->_doGet($api);
        $this->logi('[getOpenId]: return value:'.$rslt);
        return $rslt;
    }
    
    /**
        6. 向设备推送消息
        使用时机： 首先要明白一点，不是该服务器直接发给设备的，是发送给微信的，然后由微信转给设备，
                但是，我们wifi设备与微信是没有任何通信的。所以就别想着该功能了。
                适应于蓝牙。
    */
    function tranMsg2Devs($devtype,$devid,$openID,$content){ //content must be base64_encode!!!
        $this->logi('[tranMsg2Devs]: enter!');
        $api = 'https://api.weixin.qq.com/device/transmsg?access_token=ACCESS_TOKEN';
        $data = '{"device_type":"'.$devtype.'","device_id":"'.$devid.'","open_id":"'.$openID.'","content":"'.$content.'"}';
        $rslt = $this->_doPost($api, $data);
        $this->logi('[tranMsg2Devs]: return value: '.$rslt);
        return $rslt;
    }
    //<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<
    
    
    ///////// 网络请求 支持  >>>>>>>>>
	function _doPost($url, $jsonstr){
        $ac = $this->_getAccessToken();
        $url = strtr($url, array('ACCESS_TOKEN'=>$ac));
        $_options = array(
            CURLOPT_HTTPHEADER => array('Content-Type: application/json','Content-Length: ' . strlen($jsonstr))
        );
        $this->curl->create($url);
        $this->curl->options($_options);
        $this->curl->post($jsonstr);
        $rlst = $this->curl->execute();        
        return $rlst;
    }
    
    function _doGet($url){
        $ac = $this->_getAccessToken();
        $url = strtr($url, array('ACCESS_TOKEN'=>$ac));
        $rslt = @file_get_contents($url);
        return $rslt;
    }
    //<<<<<<<<<<<<<<<<<<<<<<<<<<<<<
    

	
	//////////////////////////  微信-公众号 基础支持 /// >>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>
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
			if($jobj == null){
                $this->loge('[_getAccessToken] get api data null');
                return null;
            }    
				
			$token = $jobj->access_token;
			if(strlen($token) < 50){
                $this->loge('[_getAccessToken] token format error! => '.$token);
				return null;
			}
            $this->logi('[_getAccessToken] return value: '.$token);

			$this->mp_cache->write($token, 'mytoken', ($jobj->expires_in-1000));
			unset($tmp);
		}	
        //$this->logi('[_getAccessToken] return value: '.$token);
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
