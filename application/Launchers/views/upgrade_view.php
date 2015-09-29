<?php
	$infoArr = array();
	$bupdate = false;	
	$info = $query->result();
	
	if(isset($info[0])){
		$row = $info[0];
		if(isset($row->ver) && !empty($row->ver)){
			$bupdate = true;
			$infoArr['status'] = 1;
			$infoArr['ver'] = $row->ver;
			$infoArr['url'] = $row->url;
			$infoArr['intro'] = $row->intro;
			$query->free_result();			
		}
	}
		
	
	if(!$bupdate){
		$infoArr['status'] = 0;
		$infoArr['ver'] = '';
		$infoArr['url'] = '';
		$infoArr['intro'] = '';	
	}

	
	$str = json_encode($infoArr);
	echo $str;
?>