<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Run extends CI_Controller {

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

   function getUrlContent($url){
	//echo 'to get content of url:'.$url.'<br>';
        $header[]='Accept-Encoding: gzip';
	$header[]='User-Agent: Dalvik/1.6.0 (Linux; U; Android 4.2.2; M6 Build/JDQ39)';
        $curl = curl_init();  // 初始化一个 cURL 对象
        curl_setopt($curl, CURLOPT_URL, $url);  // 设置你需要抓取的URL  
        curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_TIMEOUT,30);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION , true);    //重定向问题
        //curl_setopt($curl, CURLOPT_ENCODING , 'gzip, deflate');
        //curl_setopt($curl, CURLOPT_USERAGENT, "RealtekVOD");  
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);  // 设置cURL 参数，要求结果保存到字符串中还是输出到屏幕上。

        $rtdata = curl_exec($curl);  // 运行cURL，请求网页
        curl_close($curl);  // 关闭URL请求      
        return $rtdata;
    }


        function _mymail($info){
                $config['protocol']="smtp";
//              $config['smtp_host'] = 'smtp.qq.com';
//              $config['smtp_user'] = '1840223551@qq.com';
//              $config['smtp_pass'] = '07318676881';

                $config['smtp_host'] = 'smtp.163.com';
                $config['smtp_user'] = 'vod_test@163.com';
                $config['smtp_pass'] = 'abcdefg';


//              $config['crlf']="\r\n";         //这两行针对qq邮箱的，其它的好像不用
//              $config['newline']="\r\n";

                $this->load->library('email');
                $this->email->initialize($config);

                //以下设置Email内容  
                $this->email->from($config['smtp_user'], $config['smtp_user']);
                $this->email->to('rocking@lemoon.cn');
                $this->email->subject('威堡直播同步更新完成');
                $this->email->message($info);
                //    $this->email->attach('application\controllers\1.jpeg');           //相对于index.php的路径  

                $this->email->send();
                //echo $this->email->print_debugger();        //返回包含邮件内容的字符串，包括EMAIL头和EMAIL正文。用于调试。  
        }



	function weibo(){
		log_message('error', 'weibo...........................');
		$this->load->database('moon_live_test');
		//$XMLstr = file_get_contents('application\Moon_Live\controllers\moonlive\wephd_2.20_14.16.xml');
		$sqlmd5 = 'select id, url, md5s from gather_control where enable=1';
		$query = $this->db->query($sqlmd5);

		$url = null;
		$lastmd5 = null;
		$gcontrol_id = 0;
		foreach($query->result() as $row){
			$gcontrol_id = $row->id;
			$url = $row->url;
			$lastmd5 = $row->md5s;
			break;
		}
		$query->free_result();

	//	echo 'url='.$url.', md5='.$lastmd5.'<br>';
		if(empty($url))
			exit;
		//$XMLstr = file_get_contents($url);
		$XMLstr = $this->getUrlContent($url);
		while(strlen($XMLstr)<100){
			sleep(10);
			$XMLstr = $this->getUrlContent($url);
		}

		$md = md5($XMLstr);
		if($lastmd5 == $md)
			exit;

		$lastmd5 = $md;

		$filename = './application/Moon_Live/controllers/sync_prog/tmp/'.date('Y-m-d H:i:s').'.txt';
		file_put_contents($filename, $XMLstr);
		$map_only = array();
		$map_mult = array();
		$stat_shud_Handle = array();	//set ch_id(db) as key
		$stat_set_notHandle = array();		

		$stat_shud_butnotHandle = array();	// set wb_id as key
		$stat_alter_in_wb = array();		


		$sql = 'select  ch_id,name, wb_chid, sync  from gather_control_weibao';
		$query = $this->db->query($sql);
		foreach($query->result() as $row){
			$tmp = explode('-', $row->wb_chid);
			if($row->sync == 1){
				if(count($tmp) == 1){
					$map_only[ $row->wb_chid ] =  $row->ch_id;
				}
				else{
					//echo $row->wb_chid.', '.$row->ch_id.'<br>';
					foreach($tmp as $val){
						$map_mult[$val] =  $row->ch_id ;
					}
				}
				$stat_shud_Handle[$row->ch_id] = $row->name;
			}
			else{
				foreach($tmp as $val){
					$stat_set_notHandle[$val] = $row->name;
				}
			}
		}	
		$query->free_result();		

		//--------------------
		//echo $md;

		$reader = new XMLReader();
		$reader->xml($XMLstr);
		//$reader->open('application\Moon_Live\controllers\moonlive/wephd_2.20_14.16.xml');

		$chInfo_wb = array();
		$had_handArr = array();

		$this->db->trans_strict(FALSE);
		$this->db->trans_start();
		while($reader->read()){
			switch ($reader->nodeType) {
				case XMLReader::END_ELEMENT: 
					//echo 'end_element: '.$reader->name.'<br>';
					if($reader->name == 'channel'){
						//echo 'channel:'.$chInfo_wb['name'].', id:'.$chInfo_wb['id'].'<br>';
						//echo '('.$chInfo_wb['id'].', \''.$chInfo_wb['name'].'\', \''.$chInfo_wb['id'].'\'),'.'<br>';

						if(array_key_exists($chInfo_wb['id'], $map_only)){
							$chid = $map_only[$chInfo_wb['id']];
							//delete sql
							$sql = 'delete from ch_urls where ch_id='.$chid;
							//echo 'delete_sql: '.$sql.'<br>';
							$this->db->query($sql);

							//insert sql
							$sql_inst = 'insert into ch_urls (ch_id,idx,url) values ';
							$endstr = '';
							foreach($chInfo_wb['link'] as $idx=>$url){
								$endstr .= "($chid,$idx,'$url'),";
							}						
							$endstr = trim($endstr, ', ');
							if(strlen($endstr)>5){
								$sql_inst .= $endstr;
								//echo 'insert_sql: '.$sql_inst.'<br>';
								$this->db->query($sql_inst);
								$had_handArr[$chid] = ($idx+1);
							}
						}
						else if(array_key_exists($chInfo_wb['id'], $map_mult)){
							//echo 'channel:'.$chInfo_wb['name'].', id:'.$chInfo_wb['id'].'<br>';
							$chid = $map_mult[$chInfo_wb['id']];
							if(array_key_exists($chid, $had_handArr)){
								$base = $had_handArr[$chid];
								//insert sql
								$sql_inst = 'insert into ch_urls (ch_id,idx,url) values ';
								$endstr = '';
								foreach($chInfo_wb['link'] as $idx=>$url){
									$endstr .= "($chid,".($idx+$base).",'$url'),";
								}
								$endstr = trim($endstr, ', ');
								if(strlen($endstr)>5){
									$sql_inst .= $endstr;
									//echo 'insert_sql: '.$sql_inst.'<br>';
									$this->db->query($sql_inst);
									$had_handArr[$chid] = ($idx+1+$base);
								}							
							}
							else{
								// if not exist, that is first handle
								//delete sql
								$sql = 'delete from ch_urls where ch_id='.$chid;
								//echo 'delete_sql: '.$sql.'<br>';
								$this->db->query($sql);	

								//insert sql
								$sql_inst = 'insert into ch_urls (ch_id,idx,url) values ';
								$endstr = '';
								foreach($chInfo_wb['link'] as $idx=>$url){
									$endstr .= "($chid,$idx,'$url'),";
								}
								$endstr = trim($endstr, ', ');
								if(strlen($endstr)>5){
									$sql_inst .= $endstr;
									//echo 'insert_sql: '.$sql_inst.'<br>';
									$this->db->query($sql_inst);
									$had_handArr[$chid] = ($idx+1);
								}
							}
						}
						else{
							if(!array_key_exists($chInfo_wb['id'], $stat_set_notHandle)){
								$stat_alter_in_wb[] = $chInfo_wb['name'];
							}
						}
						unset($chInfo_wb);
						$chInfo_wb = array();
					}
					break;

				case XMLReader::ELEMENT:
					$tmpName = $reader->name;
					if($tmpName=='class'){
						/*
						   if($reader->hasAttributes){
						   $clsInfo = array();
						   while($reader->moveToNextAttribute()){//这里$xml->name为属性名称 
						   $clsInfo[$reader->name] = $reader->value;
						   }
						   }
						   echo '处理类别:'.$clsInfo['classname'].', cid='.$clsInfo['id'].'<br>';
						 */
					}
					else if($tmpName == 'channel'){
						if($reader->hasAttributes){
							$clsInfo = array(); 
							while($reader->moveToNextAttribute()){//这里$xml->name为属性名称 
								if($reader->name == 'id'
										|| $reader->name == 'name')
									//$clsInfo[$reader->name] = $reader->value;
									$chInfo_wb[$reader->name] = $reader->value;
							}
						}

						$chInfo_wb['link'] = array();
						//echo '处理频道：'.$clsInfo['name'].', chid='.$clsInfo['id'].'<br>';

					}
					else if($tmpName == 'tvlink'){
						if($reader->hasAttributes){
							$clsInfo = array();
							while($reader->moveToNextAttribute()){//这里$xml->name为属性名称 
								if($reader->name == 'link'){
									//$clsInfo['link'] = $reader->value;
									$chInfo_wb['link'][] = $reader->value;
								}
							}
						}

						//echo 'urls:'.$clsInfo['link'].'<br>';
					}
					break;
			}
		}

		$reader->close();	

		// get not handled channel(but set it handle before)
		foreach($stat_shud_Handle as $key=>$val){
			if(!array_key_exists($key, $had_handArr)){
				$stat_shud_butnotHandle[] = $val;
				$sql = 'delete from ch_urls where ch_id='.$key;
				$this->db->query($sql);			
			}
		}

		// 更新md5
		$sql = 'update gather_control set md5s=\''.$lastmd5.'\' where id='.$gcontrol_id;
		$this->db->query($sql);


		$this->db->trans_complete();	
		if ($this->db->trans_status() === FALSE){
			// 生成一条错误信息... 或者使用 log_message() 函数来记录你的错误信息
			$this->db->trans_rollback();
			exit("处理事务失败!!");
		}		
		//--------------------
		$this->_mymail('successfull');
		echo '处理结果如下：<br>';
		echo '<font color="red"> >>>> 设定要同步，但是没有同步的频道(对应地址已清空)<br></font>';
		foreach($stat_shud_butnotHandle as $val){
			echo $val.'<br>';
		}
		echo '<br><br>';

		echo '<font color="red"> >>>> 设定禁止同步的频道<br></font>';
		$stat_set_notHandle = array_unique($stat_set_notHandle);
		foreach($stat_set_notHandle as $val){
			echo $val.'<br>';
		}
		echo '<br><br>';

		echo '<font color="red"> >>>>> 同步目标中 未备案的频道 <br></font>';
		foreach($stat_alter_in_wb as $val){
			echo $val.'<br>';
		}
		echo '<br><br>';		
	}

       function tvlist(){
                log_message('error', 'begin to tvlist function!!');
        //        $this->output->cache(60*5);
                $this->load->database('moon_live_test');
                $cataArr = array();
                $sql = 'select cata, title from ch_cata where isShow=1 order by idx';
                $query = $this->db->query($sql);
                foreach($query->result() as $row){
                        $cataArr[] = array($row->title,$row->cata);
                }
                $query->free_result();

                $lastChid = -1;
                $chArr = array();
                $sql = 'SELECT t1.ch_id,t1.name,t1.cata,t2.idx,t2.url,t2.tm_out FROM ch_name t1, ch_urls t2 where t1.ch_id=t2.ch_id order by t1.ch_id,t2.idx';
                $query = $this->db->query($sql);
                foreach($query->result() as $row){
                        $cid = $row->ch_id;
                        if($row->ch_id != $lastChid){
                                $chArr["$cid"] = array($cid, $row->name,$row->cata, array(array($row->url,(int)$row->tm_out)));
                                $lastChid = $row->ch_id;
                        }
                        else{
                                $urlArr = $chArr["$cid"][3];
                                $urlArr[] = array($row->url,(int)$row->tm_out);
                                $chArr["$cid"][3] = $urlArr;
                        }
                }
                $query->free_result();
                $this->db->close();
                $out = array(
                        'status' =>1,
                        'cata' => $cataArr,
                        'chlist'=> array_values($chArr) //
                );
                $outstr = json_encode($out);
                $this->load->library('Simple_Encry', null, 'myEncy');
                $outstr = $this->myEncy->encode($outstr, 'lemoon_rocking');
                $data['data'] = $outstr;
                $this->load->view('tvlist', $data);
        }

}

/* End of file run.php */
/* Location: ./controllers/run.php */
