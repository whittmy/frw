<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Api2 extends CI_Controller {

    function handler(){
		$apk_vercode =$this->input->post('apk_vercode');
		$brand =$this->input->post('brand');
		$board =$this->input->post('board');
		$mac =$this->input->post('mac');
		$android =$this->input->post('android');
 		$time =$this->input->post('time');

		$firmware =$this->input->post('firmware');     //firmware version  
        $device =$this->input->post('device');          //machine model

        $this->load->library('MP_Cache');
        $cacheName = $device.'/'.$firmware;
		$data1 = $this->mp_cache->get($cacheName);
        $data1 = false;
		if($data1 === false){
            //$status = 'success/other';
            //$url = 'http://xxxxxx';
            //$md_5 = 'sdsd';
            //$coutry = "ELSE / 系统读取的country";
            //$intro = 'upd.......';
            
            $status =  'other';
            $url = 'http://www.nybgjd.com/update.ipa';
            $md_5 = '090E013C02FA170CFA23EE78390DC312';
            $coutry = 'ELSE';
            $intro = '该版本为测试，不求多么伟大，但求没有问题';
            
            $data1 = '<root><status>'.$status.'</status><url>'.$url.'</url><md5>'.$md_5.'</md5><description country="'.$coutry.'">'.$intro.'</description></root>';
            $this->mp_cache->write($data1, $cacheName, 24*3600);
        }

        header('Content-Type: text/xml');	
        exit($data1);
    }
	// notice: :set nobomb,  delete bom of utf8

	function t2_wht_alter(){
		$this->load->library('MP_Cache');
		$data1 = $this->mp_cache->get('t2_wht_alter');
		if($data1 === false){
			$url = 'http://7xiolu.com1.z0.glb.clouddn.com/update_t2-2_4core-2.zip';
			$md5 = '2F290362820898A8B00171DADCE391EE';
			$intr = '更新说明: \n1.更换UI系统 \n2.升级完成后请手动恢复一下出厂设置'; 
			log_message('error', $intr);
			$dom = new DomDocument('1.0', 'utf-8');
			$root = $this->create_root($dom);
			$cmd = $this->create_cmd($dom, $root, 'update_with_inc_ota', '');
			$this->create_items($dom, $cmd, $url, $md5, $intr);
			header('Content-Type: text/xml');
			$data1 =  $dom->saveXML();
			$this->mp_cache->write($data1, 't2_wht_alter', 24*3600);
		}
		exit($data1);	
	}

	function m5ii_top_auth_bug(){
		$this->load->library('MP_Cache');
		$data1 = $this->mp_cache->get('m5ii_top_auth_bug');
		if($data1 === false){
			$url = 'http://106.187.95.4/upgrades/update_M5II_top_auth_bug_1225.zip';
			$md5 = '9E9869929966A22FCB24A1B429AE217B';
			$intr = '更新说明: \n1.修正关机指示灯不变红的问题';

			$dom = new DomDocument('1.0', 'utf-8');
			$root = $this->create_root($dom);
			$cmd = $this->create_cmd($dom, $root, 'update_with_inc_ota', '');
			$this->create_items($dom, $cmd, $url, $md5, $intr);
			header('Content-Type: text/xml');
			$data1 =  $dom->saveXML();
			$this->mp_cache->write($data1, 'm5ii_top_auth_bug', 24*3600);
		}	
		exit($data1);	
	}

	function m5ii_jc_auth_bug(){
		$this->load->library('MP_Cache');
		$data1 = $this->mp_cache->get('m5ii_jc_auth_bug');
		if($data1 === false){
		    $url = 'http://106.187.95.4/upgrades/update_M5II_JC_auth_bug_1207.zip';
			$md5 = '8FD81732A32D2DF84C2786FFED64C20F';
			$intr = '紧急修复: \n1.彻底解决频繁提示认证失败提示框的问题';

			$dom = new DomDocument('1.0', 'utf-8');
			$root = $this->create_root($dom);
			$cmd = $this->create_cmd($dom, $root, 'update_with_inc_ota', '');
			$this->create_items($dom, $cmd, $url, $md5, $intr);
			header('Content-Type: text/xml');
			$data1 =  $dom->saveXML();
			$this->mp_cache->write($data1, 'm5ii_jc_auth_bug', 24*3600);
		}
		exit($data1);	
	}


	function m5jc_zero_bug(){
		//update-> 20141120
		$this->load->library('MP_Cache');
		$data1 = $this->mp_cache->get('m5jc_zero_bug');
		if($data1 === false){
			$url = 'http://106.187.95.4/upgrades/update_M5jc_zero_1202.zip';
			$md5 = '45B3227090252999863ADC3C185AF9BA';
			$intr = '更新说明: \n1.修正数字0键无法使用情况\n2.修正信息键无法调出鼠标模式\n3.彻底解决频繁提示认证失败的问题';

			$dom = new DomDocument('1.0', 'utf-8');
			$root = $this->create_root($dom);
			$cmd = $this->create_cmd($dom, $root, 'update_with_inc_ota', '');
			$this->create_items($dom, $cmd, $url, $md5, $intr);
			header('Content-Type: text/xml');
			$data1 =  $dom->saveXML();
			$this->mp_cache->write($data1, 'm5jc_zero_bug', 24*3600);
		}
		exit($data1);	
	}	

	function r4_ir_zero_bug(){
		$this->load->library('MP_Cache');
		$data1 = $this->mp_cache->get('r4_ir_zero_bug');
		if($data1 === false){
			$url = 'http://106.187.95.4/upgrades/update_R4_4core_1126_zero_bug.zip';
			$md5 = '1DC36766C8AECA1DF3083E22DADADC89';
			$intr = '更新说明: \n1.修正数字0键无法使用情况\n2.修正信息键无法调出鼠标模式';

			$dom = new DomDocument('1.0', 'utf-8');
			$root = $this->create_root($dom);
			$cmd = $this->create_cmd($dom, $root, 'update_with_inc_ota', '');
			$this->create_items($dom, $cmd, $url, $md5, $intr);
			header('Content-Type: text/xml');
			$data1 =  $dom->saveXML();
			$this->mp_cache->write($data1, 'r4_ir_zero_bug', 24*3600);
		}
		exit($data1);
	}


    function ota_info($model=null, $ver=null){
		log_message('error', 'ota_info ver:'.$ver);

		if($ver < 20141119){
			$curMaxVer = 3;
			if(($ver < $curMaxVer) || ($ver==20141114)){
				log_message('error', 'ota_info ver < '.$curMaxVer);
				$url = 'http://106.187.95.4/upgrades/update_signed_ok_3.zip';
				$md5 = '1505BDEB24E08414CED9B797A20EC696';
				$intr = '更新说明: \n1.更换系统主界面风格\n2.更新内置直播应用--电视家\n3.新增在线升级功能\n4.删除不能播放的应用，如爱奇艺、优酷、搜狐、PPTV等';

				$dom = new DomDocument('1.0', 'utf-8');
				$root = $this->create_root($dom);
				$cmd = $this->create_cmd($dom, $root, 'update_with_inc_ota', '');
				$this->create_items($dom, $cmd, $url, $md5, $intr);
				header('Content-Type: text/xml');	
				echo $dom->saveXML();
			}
		}
    }



    function create_root($dom){
         //  创建根节点
	$elem_root = $dom->createElement('root');
	$dom->appendChild($elem_root);
        return $elem_root;
    }
    
    function create_cmd($dom, $root, $name, $force){
        $elme_cmd = $dom->createElement('command');
        $root->appendChild($elme_cmd);
        
        $attr_name = $dom->createAttribute('name');
        $elme_cmd->appendChild($attr_name);
        $val = $dom->createTextNode($name);
        $attr_name->appendChild($val);
    
    
        $attr_force = $dom->createAttribute('force');
        $elme_cmd->appendChild($attr_force);
        $val = $dom->createTextNode($force);
        $attr_force->appendChild($val);
    
        return $elme_cmd;
    }
    
    function create_items($dom, $cmd, $url, $md5, $intr){
        $elem_url = $dom->createElement('url');
        $cmd->appendChild($elem_url);
        
        $val_url = $dom->createTextNode($url);
        $elem_url->appendChild($val_url);
        
        $elem_md5 = $dom->createElement('md5');
        $cmd->appendChild($elem_md5);
        
        $val_md5 = $dom->createTextNode($md5);
        $elem_md5->appendChild($val_md5);
        
        $elem_intr = $dom->createElement('description');
        $cmd->appendChild($elem_intr);
        
        $attr_c = $dom->createAttribute('country');
        $elem_intr->appendChild($attr_c);
        $val_c = $dom->createTextNode('ELSE');
        $attr_c->appendChild($val_c);
        $val_c = $dom->createTextNode($intr);
        $elem_intr->appendChild($val_c);
    }
}

/* End of file api.php */
/* Location: ./controllers/api.php */
