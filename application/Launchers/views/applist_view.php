<?php
	$infoArr = array();
	$applist = array();


	
	$info = $query->result();	
	foreach($info as $row){	
		$appinfo = array();
		$appinfo['title'] = $row->title;
		$appinfo['package'] = $row->package;
		$appinfo['size'] = intval($row->size);
		$appinfo['upgrade'] = intval($row->bupgrade);
		$appinfo['ver'] = $row->ver;
		$appinfo['icon'] = $row->icon;
		$appinfo['dlurl'] = $row->dl_url;
		$appinfo['intro'] = $row->intro;
		
		//print_r($appinfo);
		$applist[] = $appinfo;
	}
	$query->free_result();	
	
	$infoArr['applist'] = $applist;
	$str = json_encode($infoArr);
	echo $str;
?>
