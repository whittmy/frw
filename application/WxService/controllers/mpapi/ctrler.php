<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');


/*  
    + Curl Library
        - config/autoload.php  curl
        - library/Curl.php
*/

  
// http://www.nybgjd.com/mpapi/ctrler/test





//require_once(dirname(__FILE__) . '/' . 'notification/android/AndroidBroadcast.php');
//require_once(dirname(__FILE__) . '/' . 'notification/android/AndroidFilecast.php');
//require_once(dirname(__FILE__) . '/' . 'notification/android/AndroidGroupcast.php');
//require_once(dirname(__FILE__) . '/' . 'notification/android/AndroidUnicast.php');
require_once(dirname(__FILE__) . '/' . 'notification/android/AndroidCustomizedcast.php');
/*
require_once(dirname(__FILE__) . '/' . 'notification/ios/IOSBroadcast.php');
require_once(dirname(__FILE__) . '/' . 'notification/ios/IOSFilecast.php');
require_once(dirname(__FILE__) . '/' . 'notification/ios/IOSGroupcast.php');
require_once(dirname(__FILE__) . '/' . 'notification/ios/IOSUnicast.php');
require_once(dirname(__FILE__) . '/' . 'notification/ios/IOSCustomizedcast.php');
*/

class Ctrler  extends CI_Controller {
    protected $appkey           = '5658fda4e0f55a74d000b15a'; 
	protected $appMasterSecret     = 'thzbp7bcbbtyp6xuipjzesbsi9cub7ey';
 	protected $validation_token = NULL; //no-use

    function test(){
        echo 'test';
        $this->sendAndroidCustomizedcast('4', 'mmh_piapia','message', null, null, '{"type":"album","action":"update"}');
    }
 
  
  /*
    $alias: 逗号分隔
  */
	function sendAndroidCustomizedcast($alias, $aliasType, $msgtype, $ticker, $title, $text) {
		try {
			$customizedcast = new AndroidCustomizedcast();
			$customizedcast->setAppMasterSecret($this->appMasterSecret);
			$customizedcast->setPredefinedKeyValue("appkey",           $this->appkey);
			$customizedcast->setPredefinedKeyValue("timestamp",        strval(time()));
            
            // Set your alias here, and use comma to split them if there are multiple alias.
			// And if you have many alias, you can also upload a file containing these alias, then 
			// use file_id to send customized notification.
			$customizedcast->setPredefinedKeyValue("alias",            $alias );
			// Set your alias_type here
			$customizedcast->setPredefinedKeyValue("alias_type",       $aliasType);
            
            
            //默认显示方式为 notification, 我们要用自定义消息，所以提供了如下的处理(即 "display_type" = "message")
            if($msgtype == 'message'){
                $customizedcast->setPredefinedKeyValue("display_type",      "message");  //注意默认是
                $customizedcast->setPredefinedKeyValue('custom', $text);
            }
            else{
                $customizedcast->setPredefinedKeyValue("ticker",           $ticker);
                $customizedcast->setPredefinedKeyValue("title",            $title);
                $customizedcast->setPredefinedKeyValue("text",             $text);
            }

			$customizedcast->setPredefinedKeyValue("after_open",       "go_app");
			print("Sending customizedcast notification, please wait...\r\n");
			$customizedcast->send();
			print("Sent SUCCESS\r\n");
		} catch (Exception $e) {
			print("Caught exception: " . $e->getMessage());
		}
	}
    
    //定时处理消息队列
	function noticetask(){
        $cacheName = 'Controller';
        $this->load->library('MP_Cache');
        $lasttm = $this->mp_cache->get($cacheName);
        //exit($lasttm);
        //$lasttm = false;
        if($lasttm === false){
            $lasttm = time();
        }
        //$lasttm = 1447757156;  //debug
        echo 'lasttm='.$lasttm.'<br>'."\n";
        
        $didarr = array();
        $this->load->database('mp');
        $sql = "select p_did,p_msgtype,max(p_tm) tm from mp_push_list where p_tm>$lasttm group by p_did,p_msgtype order by null";
        echo $sql.'<br>'."\n";
        $query = $this->db->query($sql);
        foreach($query->result() as $row){
            $lasttm = max($lasttm, $row->tm);
            
            $msgtype = $row->p_msgtype;
            $didarr[$msgtype][] = $row->p_did; 
        }
        $query->free_result();
        
        //删除 时间$data之前的(包含自己)的条目
        $sql = 'delete from mp_push_list where p_tm<='.$lasttm;
        echo $sql.'<br>'."\n";
        $this->db->query($sql);
        $this->db->close();
        
        echo 'save lasttm='.$lasttm.'<br>'."\n";
        $this->mp_cache->write($lasttm, $cacheName, 36000000);
        //print_r($didarr);
        
        //!!!!!!! 注意下面的判断 不是 互斥关系哦，现在虽然是互斥，后面一定是并列去处理哦 ！！！！
        $dids = array();
        $msg = null;
        if(isset($didarr[0])){
            //text
        }
        elseif(isset($didarr[1])){
            //image
            $dids = $didarr[1];
            $msg = '{"type":"album","action":"update"}';
        }
        elseif(isset($didarr[2])){
            //voice
            $dids = $didarr[2];
            $msg = '{"type":"voice","action":"update"}';
        }
        elseif(isset($didarr[3])){
            //video
            $dids = $didarr[3];
            $msg = '{"type":"video","action":"update"}';
        }

        if($msg == null){
            exit('have no msg to handle,exit!'."\n"."\n");
        }

        
        //推送到设备
        if(count($dids) <= 40){
            // 推送
            $devidstr = trim(implode(',', $dids), ',');
        }
        else{
            //文件的形式
            
        }
        $this->sendAndroidCustomizedcast($devidstr, 'mmh_piapia', 'message', null, null, $msg);
        echo '<br>devicelist:'.$devidstr.', msg='.$msg.'<br>'."\n";
        exit('handle complete! device count: '.count($dids)."\n\n");
	}


    
    
/*    
	function sendAndroidBroadcast() {
		try {
			$brocast = new AndroidBroadcast();
			$brocast->setAppMasterSecret($this->appMasterSecret);
			$brocast->setPredefinedKeyValue("appkey",           $this->appkey);
			$brocast->setPredefinedKeyValue("timestamp",        $this->timestamp);
			$brocast->setPredefinedKeyValue("ticker",           "Android broadcast ticker");
			$brocast->setPredefinedKeyValue("title",            "中文的title");
			$brocast->setPredefinedKeyValue("text",             "Android broadcast text");
			$brocast->setPredefinedKeyValue("after_open",       "go_app");
			// Set 'production_mode' to 'false' if it's a test device. 
			// For how to register a test device, please see the developer doc.
			$brocast->setPredefinedKeyValue("production_mode", "true");
			// [optional]Set extra fields
			$brocast->setExtraField("test", "helloworld");
			print("Sending broadcast notification, please wait...\r\n");
			$brocast->send();
			print("Sent SUCCESS\r\n");
		} catch (Exception $e) {
			print("Caught exception: " . $e->getMessage());
		}
	}

	function sendAndroidUnicast() {
		try {
			$unicast = new AndroidUnicast();
			$unicast->setAppMasterSecret($this->appMasterSecret);
			$unicast->setPredefinedKeyValue("appkey",           $this->appkey);
			$unicast->setPredefinedKeyValue("timestamp",        $this->timestamp);
			// Set your device tokens here
			$unicast->setPredefinedKeyValue("device_tokens",    "xx"); 
			$unicast->setPredefinedKeyValue("ticker",           "Android unicast ticker");
			$unicast->setPredefinedKeyValue("title",            "Android unicast title");
			$unicast->setPredefinedKeyValue("text",             "Android unicast text");
			$unicast->setPredefinedKeyValue("after_open",       "go_app");
			// Set 'production_mode' to 'false' if it's a test device. 
			// For how to register a test device, please see the developer doc.
			$unicast->setPredefinedKeyValue("production_mode", "true");
			// Set extra fields
			$unicast->setExtraField("test", "helloworld");
			print("Sending unicast notification, please wait...\r\n");
			$unicast->send();
			print("Sent SUCCESS\r\n");
		} catch (Exception $e) {
			print("Caught exception: " . $e->getMessage());
		}
	}

	function sendAndroidFilecast() {
		try {
			$filecast = new AndroidFilecast();
			$filecast->setAppMasterSecret($this->appMasterSecret);
			$filecast->setPredefinedKeyValue("appkey",           $this->appkey);
			$filecast->setPredefinedKeyValue("timestamp",        $this->timestamp);
			$filecast->setPredefinedKeyValue("ticker",           "Android filecast ticker");
			$filecast->setPredefinedKeyValue("title",            "Android filecast title");
			$filecast->setPredefinedKeyValue("text",             "Android filecast text");
			$filecast->setPredefinedKeyValue("after_open",       "go_app");  //go to app
			print("Uploading file contents, please wait...\r\n");
			// Upload your device tokens, and use '\n' to split them if there are multiple tokens
			$filecast->uploadContents("aa"."\n"."bb");
			print("Sending filecast notification, please wait...\r\n");
			$filecast->send();
			print("Sent SUCCESS\r\n");
		} catch (Exception $e) {
			print("Caught exception: " . $e->getMessage());
		}
	}

	function sendAndroidGroupcast() {
		try {
			//
		 	// Construct the filter condition:
		 	// "where": 
		 	//{
    	 	//	"and": 
    	 	//	[
      	 	//		{"tag":"test"},
      	 	//		{"tag":"Test"}
    	 	//	]
		 	//}
		 	//
			$filter = 	array(
							"where" => 	array(
								    		"and" 	=>  array(
								    						array(
							     								"tag" => "test"
															),
								     						array(
							     								"tag" => "Test"
								     						)
								     		 			)
								   		)
					  	);
					  
			$groupcast = new AndroidGroupcast();
			$groupcast->setAppMasterSecret($this->appMasterSecret);
			$groupcast->setPredefinedKeyValue("appkey",           $this->appkey);
			$groupcast->setPredefinedKeyValue("timestamp",        $this->timestamp);
			// Set the filter condition
			$groupcast->setPredefinedKeyValue("filter",           $filter);
			$groupcast->setPredefinedKeyValue("ticker",           "Android groupcast ticker");
			$groupcast->setPredefinedKeyValue("title",            "Android groupcast title");
			$groupcast->setPredefinedKeyValue("text",             "Android groupcast text");
			$groupcast->setPredefinedKeyValue("after_open",       "go_app");
			// Set 'production_mode' to 'false' if it's a test device. 
			// For how to register a test device, please see the developer doc.
			$groupcast->setPredefinedKeyValue("production_mode", "true");
			print("Sending groupcast notification, please wait...\r\n");
			$groupcast->send();
			print("Sent SUCCESS\r\n");
		} catch (Exception $e) {
			print("Caught exception: " . $e->getMessage());
		}
	}

*/      
}

/* End of file shandong.php */
/* Location: ./controllers/shandong.php */
