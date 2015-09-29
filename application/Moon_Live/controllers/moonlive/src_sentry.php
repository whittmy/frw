<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Src_Sentry extends CI_Controller {

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
	function _rmdirs($dir){
		if(!file_exists($dir))
				return;
		$d = dir($dir);
		while (false !== ($child = $d->read())){
			if($child != '.' && $child != '..'){
				if(is_dir($dir.'/'.$child))
					$this->_rmdirs($dir.'/'.$child);
				else
					unlink($dir.'/'.$child);
			}
		}
		$d->close();
		rmdir($dir);
	}

	function _mkdirs($dir){
		if(file_exists($dir))
			return true;
		if(!is_dir($dir)){
			if(!$this->_mkdirs(dirname($dir))){
				return false;
			}
			if(!mkdir($dir,0777)){
				return false;
			}
		}
		return true;
	}	 

	function kaiber(){
		set_time_limit(0);
		ignore_user_abort(false);

		//检测目录状态，没有则创建,及设置权限
		$cacheDir_detail = '/var/www/html/ci/application/Moon_Live/cache/mlive_cache';
		//$cacheDir_detail = 'C:\mlive_cache';
		$this->_rmdirs($cacheDir_detail);
		if($this->_mkdirs($cacheDir_detail) == false)
			exit("create dir $cacheDir_detail failed!!");		

		$this->load->helper('download');	//myDLCurl($url);
		$this->load->library('MTask', NULL, 'myDL1');

		$urlList = array(
				//		'kaib'=>'http://www.baidu.com'
						'kaib'=>'http://api.veryhd.net/?s=xml-android_tvout-userid-830-oemid-KIUI-3188'
						//	'longl'=>'http://default.007looper.com/frw/application/Tv_Refer/longlong.php'
				);
		foreach($urlList as $k=>$url){	
				$dest = $cacheDir_detail.'/'.$k.'.xml';
				log_message('error', 'Add task '.$url.' to '.$dest);
				$this->myDL1->add(array($url, $dest),
							array(array($this, '_hdler_detail_ok'), 
							array($k, $dest), 
							array(CURLOPT_USERAGENT=>'User-Agent:Mozilla/5.0 (Windows NT 5.1) AppleWebKit/537.31 (KHTML, like Gecko) Chrome/26.0.1410.64 Safari/537.31')),
							array(array($this, '_hdler_detail_err'))
							);	
		}
		$this->myDL1->go();
		//exit('index');
	}


	function _hdler_detail_ok($info, $name, $dest){
		log_message('error', 'come into hdler_detail_ok!!');
		//return;
		if($name == 'kaib'){
				$chArr = array(
					'CCTV-1综合'=>'10',
					'CCTV-2财经'=>'11',
					'CCTV-3综艺'=>'12',
					'CCTV-4国际'=>'13',
					'CCTV-5体育'=>'14',
					'CCTV-6电影'=>'15',
					'CCTV-7军事农业'=>'16',
					'CCTV-8电视剧'=>'17',
					'CCTV-9记录'=>'18',
					'CCTV-10科教'=>'19',
					'CCTV-11戏曲'=>'20',
					'CCTV-12社会'=>'21',
					'CCTV-13新闻'=>'22',
					'CCTV-14少儿'=>'23',
					'CCTV-15音乐'=>'24',
					'北京卫视高清'=>'42',
					'天津卫视高清'=>'51',
					'东方卫视高清'=>'44',
					'重庆卫视'=>'70',
					'湖南卫视高清'=>'39',
					'浙江卫视高清'=>'41',
					'江苏卫视高清'=>'38',
					'山东卫视高清'=>'50',
					'广东卫视高清'=>'40',
					'深圳卫视高清'=>'43',
					'安徽卫视'=>'49',
					'四川卫视'=>'62',
					'陕西卫视'=>'55',
					'山西卫视'=>'73',
					'湖北卫视高清'=>'57',
					'河北卫视高清'=>'59',
					'东南卫视'=>'46',
					'河南卫视'=>'54',
					'江西卫视'=>'53',
					'广西卫视'=>'58',
					'内蒙古卫视'=>'61',
					'旅游卫视'=>'63',
					'云南卫视'=>'52',
					'贵州卫视'=>'67',
					'青海卫视'=>'48',
					'宁夏卫视'=>'68',
					'甘肃卫视'=>'69',
					'黑龙江卫视高清'=>'37',
					'吉林卫视'=>'56',
					'辽宁卫视'=>'47',
					'西藏卫视'=>'60',
					'新疆卫视'=>'45',
					'海峡卫视'=>'75',
					'厦门卫视'=>'72',
					'兵团卫视'=>'74'
										);		

				$con = file_get_contents($dest);
				if(strlen($con) < 200)
						exit("down no contents!!");
				unset($con);			

				//$dest1 = 'D:\www_root\xml_test\kaib';
				$dest1 = $dest;
				$rslt = simplexml_load_file($dest1); //创建 SimpleXML对象
				$liveTypeObj = $rslt->attributes->liveType;

				$ids = array();
				$instSql_chname = 'insert into ch_name (ch_id,name,cata) values ';
				$instSql_churls = 'insert into ch_urls (ch_id, idx, url) values ';

				foreach($liveTypeObj as $liveType){
					$chobj = $liveType->channel;
					if(strstr($liveType['name'],'央视')){
						foreach($chobj as $ch){
							$chname = $ch['name'];
							if(array_key_exists((string)$chname, $chArr)){
								$ids[] = $chArr["$chname"];
								$instSql_chname = $instSql_chname.'('.$chArr["$chname"].',\''.$chname.'\',\'012\'),';

								$addressInfo = $ch->addressInfo;
								$k = 0;
								foreach($addressInfo as $address){
									$instSql_churls = $instSql_churls. '('.$chArr["$chname"].','.$k.',\''.$address['url'].'\'),';
									$k++;
								}	
							}
						}
					}
					else if(strstr($liveType['name'],'卫视')){
						foreach($chobj as $ch){
							$chname = $ch['name'];
							if(array_key_exists((string)$chname, $chArr)){
								$id = $ids[] = $chArr["$chname"];

								if(strstr($chname,'HD') || strstr($chname, '高清')){
									$chname = strtr($chname, array('高清'=>''));
									$chname = strtr($chname, array('HD'=>''));
									$cata = '0134';
								}
								else{
									$cata = '014';
								}
								$instSql_chname = $instSql_chname.'('.$id.',\''.$chname.'\',\''.$cata.'\'),';

								$addressInfo = $ch->addressInfo;
								$k = 0;
								foreach($addressInfo as $address){
									$instSql_churls = $instSql_churls. '('.$id.','.$k.',\''.$address['url'].'\'),';
									$k ++;
								}	
							}
						}
					}
				}

				if(count($ids) < count($chArr)){
						echo 'we get data from kaiboer maybe littler!!,cancel it!!';
						exit;
				}

				$strIds = '('.implode(',', $ids).')';			
				$delchname = 'delete from ch_name where ch_id in '. $strIds;
				$delchurls = 'delete from ch_urls where ch_id in '. $strIds;
				$instSql_chname = trim($instSql_chname, ',');
				$instSql_churls = trim($instSql_churls, ',');
						echo 'we get data from kaiboer maybe littler!!,cancel it!!';
				echo $delchname;
				echo '<br>';
				echo $delchurls;
				echo '<br>';			
				echo $instSql_chname;
				echo '<br><br>';
				echo $instSql_churls;


				$this->load->database();
				$this->db->trans_begin();
				$this->db->query($delchname);
				$this->db->query($delchurls);
				$this->db->query($instSql_chname);
				$this->db->query($instSql_churls);
				$this->db->trans_complete();
				if ($this->db->trans_status() === FALSE){
						// 生成一条错误信息... 或者使用 log_message() 函数来记录你的错误信息
						log_message('error', 'trans error !!!');
						$this->db->trans_rollback();
				}	
				else{
						$this->db->trans_commit();
						log_message('error', 'commit it !!!');
				}

		}
		else if($name == 'longl'){

		}
		else{
				log_message('error', 'no hanler name:'.$name);
		}
	}

	function _hdler_detail_err($info){
			log_message('error', "download error!");
	}	

	/*@function:指定位置插入字符串  
	 * @par：$str原字符串  
	 * $i:位置  
	 * $substr:需要插入的字符串  
	 * 返回：新组合的字符串  
	 * */  
	function str_insert($str, $i, $substr){  
		$startstr = '';
		$laststr = '';

		for($j=0; $j<$i; $j++){  
			$startstr .= $str[$j];  
		}
		for ($j=$i; $j<strlen($str); $j++){  
			$laststr .= $str[$j];  
		}
		$str = ($startstr . $substr . $laststr);  
		return $str;  
	}



	function parserUrls_forYidian($urls){
		if(empty($urls))
			return null;
		$part1 = substr($urls, 0, 5);
		$part2 = substr($urls, 5);
		//echo $part1.'<br>';
		//echo $part2.'<br>';
		$part1Arr = str_split($part1);

		$part2 = $this->str_insert($part2, 2, $part1Arr[0]);
		$part2 = $this->str_insert($part2, 1, $part1Arr[1]);
		$part2 = $this->str_insert($part2, 8, $part1Arr[3]);
				 
		$len = strlen($part2);
		$part2 = $this->str_insert($part2, $len-5, $part1Arr[2]);
		$len += 1;

		$part2 = $this->str_insert($part2, $len-4, $part1Arr[4]);
		//exit($part2);
		return base64_decode($part2);	
	}

	function yidian(){
		set_time_limit(0);
		ignore_user_abort(false);
		$chArr = array(
						'CCTV-1综合'=>'10',
						'CCTV-2财经'=>'11',
						'CCTV-3综艺'=>'12',
						'CCTV-4国际'=>'13',
						'CCTV-5体育'=>'14',
						'CCTV-6电影'=>'15',
						'CCTV-7军事农业'=>'16',
						'CCTV-8电视剧'=>'17',
						'CCTV-9纪录'=>'18',
						'CCTV-10科教'=>'19',
						'CCTV-11戏曲'=>'20',
						'CCTV-12社会与法'=>'21',
						'CCTV-13新闻'=>'22',
						'CCTV-14少儿'=>'23',
						'CCTV-15音乐'=>'24',
						'北京卫视高清'=>'42',
						'天津卫视高清'=>'51',
						'天津卫视'=>'51',
						'东方卫视高清'=>'44',
						'东方卫视'=>'44',
						'重庆卫视'=>'70',
						'重庆卫视高清'=>'70',
						'湖南卫视高清'=>'39',
						'浙江卫视'=>'41',
						'江苏卫视高清'=>'38',
						'江苏卫视'=>'38',
						'山东卫视高清'=>'50',
						'广东卫视高清'=>'40',
						'上东卫视'=>'40',
						'深圳卫视高清'=>'43',
						'北京卫视'=>'42',
						'东方卫视'=>'44',
						'广东卫视'=>'40',
						'深圳卫视'=>'43',
						'安徽卫视'=>'49',
						'四川卫视'=>'62',
						'山东卫视'=>'50',
						'陕西卫视'=>'55',
						'山西卫视'=>'73',
						'湖北卫视'=>'57',
						'河北卫视'=>'59',
						'东南卫视'=>'46',
						'河南卫视'=>'54',
						'江西卫视'=>'53',
						'广西卫视'=>'58',
						'内蒙古卫视'=>'61',
						'旅游卫视'=>'63',
						'云南卫视'=>'52',
						'贵州卫视'=>'67',
						'青海卫视'=>'48',
						'宁夏卫视'=>'68',
						'甘肃卫视'=>'69',
						'黑龙江卫视'=>'37',
						'吉林卫视'=>'56',
						'辽宁卫视'=>'47',
						'西藏卫视'=>'60',
						'新疆卫视'=>'45',
						'海峡卫视'=>'75',
						'厦门卫视'=>'72',
						'兵团卫视'=>'74');

		$url = 'http://121.199.22.39/blumedia/tv_jsonv2.php';
		//$url = 'http://121.199.22.39/blumedia/poster/live.json';
		$this->load->helper('download');	//myDLCurl($url);
		$con = myDLCurl($url);
		if(!empty($con)){
			$jobj = json_decode($con);
			if($jobj != null){
				$cataArr = $jobj->live;
				
				$ids = array();
				$instSql_chname = 'insert into ch_name (ch_id,name,cata) values ';
				$instSql_churls = 'insert into ch_urls (ch_id, idx, url) values ';

				foreach($cataArr as $cataObj){
					log_message('error', 'cata:'.$cataObj->name);
					if($cataObj->name == '中央频道'){
						$newchArr = $cataObj->channel;
						foreach($newchArr as $chObj){
							$chname = $chObj->name;
							if($chname == 'CCTV-4频道')
								$chname = 'CCTV-4国际';
							if(array_key_exists((string)$chname, $chArr)){
								log_message('error', 'chname:'.$chname);
								$ids[] = $chArr["$chname"];
								$instSql_chname = $instSql_chname.'('.$chArr["$chname"].',\''.$chname.'\',\'012\'),';
								$urlsStr = $this->parserUrls_forYidian($chObj->urls);
								$urlArr = explode(';', $urlsStr);

								$k = 0;
								foreach($urlArr as $url){
									$instSql_churls = $instSql_churls. '('.$chArr["$chname"].','.$k.',\''.$url.'\'),';
									$k++;
								}
							}
						}
					}
					else if($cataObj->name == '国内卫视'){
						$newchArr = $cataObj->channel;
						foreach($newchArr as $chObj){
							$chname = $chObj->name;
							if(array_key_exists((string)$chname, $chArr)){
								$cata = null;
								$bexist = false;
								log_message('error', 'chname:'.$chname);
								if(in_array($chArr["$chname"], $ids)){
									$cata = '014';	
									$bexist = true;
								}
								$id = $ids[] = $chArr["$chname"];
								if(strstr($chname,'HD') || strstr($chname, '高清')){
									$chname = strtr($chname, array('高清'=>''));
									$chname = strtr($chname, array('HD'=>''));
									if($cata == null)
										$cata = '0134';
								}
								else{
									$cata = '014';
								}
								if(!$bexist)
									$instSql_chname = $instSql_chname.'('.$id.',\''.$chname.'\',\''.$cata.'\'),';
								$urlsStr = $this->parserUrls_forYidian($chObj->urls);
								$urlArr = explode(';', $urlsStr);

								$k = 0;
								foreach($urlArr as $url){
									$instSql_churls = $instSql_churls. '('.$id.','.$k.',\''.$url.'\'),';
									$k ++;
								}	
							}
						}
					}
				}


				if(count($ids) < 50){
					echo 'we get data from yidian maybe littler!!,cancel it!!,idssize='.count($ids).',charrsize='.count($chArr) ;
					exit;
				}
				$strIds = '('.implode(',', $ids).')';			
				$delchname = 'delete from ch_name where ch_id in '. $strIds;
				$delchurls = 'delete from ch_urls where ch_id in '. $strIds;
				$instSql_chname = trim($instSql_chname, ',');
				$instSql_churls = trim($instSql_churls, ',');

				echo $delchname;
				echo '<br>';
				echo $delchurls;
				echo '<br>';			
				echo $instSql_chname;
				echo '<br><br>';
				echo $instSql_churls;


				$this->load->database();
				$this->db->trans_begin();
				$this->db->query($delchname);
				$this->db->query($delchurls);
				$this->db->query($instSql_chname);
				$this->db->query($instSql_churls);
				$this->db->trans_complete();
				if ($this->db->trans_status() === FALSE){
					// 生成一条错误信息... 或者使用 log_message() 函数来记录你的错误信息
					log_message('error', 'trans error !!!');
					$this->db->trans_rollback();
				}	
				else{
					$this->db->trans_commit();
					log_message('error', 'commit it !!!');
				}
				$this->db->close();
			}
		}
	}
}

/* End of file src_sentry.php */
/* Location: ./controllers/src_sentry.php */
