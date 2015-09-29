<?php
/**
json_ver: 1
	there is a url interface for upgrade, there are some return-info, whenever there is a upgrade-apk!!
	
json_ver:2
	we keep code below as before, but 
	we set upgrade-interface included by tvlist-interface, when there's a new version, tvlist will return 
		upgrade-info, else return tvlist info.
	Notice: we set the grade's status as 1, tvlist'status as 2 
*
*/

	$infoArr = array();
	$bupdate = false;	
	$info = $query->result();
	
	if(isset($info[0])){
		$row = $info[0];
		if(isset($row->ver) && !empty($row->ver)){
			$bupdate = true;
			$infoArr['status'] = 1;// we set 1 in json-version 2
			$infoArr['ver'] = $row->ver;
			$infoArr['url'] = $row->url;
		//	$infoArr['md5'] = $row->md5s;
			$infoArr['intro'] = $row->intro;
			$query->free_result();			
		}
	}
		
	
	if(!$bupdate){
		$infoArr['status'] = 0;// we set 0(because ver1 have some using about status,so we must keep it) in json-version 2, here this code would be no use in ver 2
		$infoArr['ver'] = '';
		$infoArr['url'] = '';
		//$infoArr['md5'] = '';
		$infoArr['intro'] = '';	
	}

	
	$str = json_encode($infoArr);
	echo $str;
?>
