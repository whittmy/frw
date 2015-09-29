<?php
	$url = array();
	//print_r($info);
	$vid = $info[0];
	$query = $info[1];
	foreach($query->result() as $row){
		$url[] = array($row->title,$row->url);
	}
	$query->free_result();
	
	$out = array();
	$out['vid']=$vid;
	// if(count($url) == 0)
		// $out['ulist'] = array();
	// else{
		// $out['ulist'] = $url;
	// }
	$out['ulist'] = $url;
	$out = json_encode($out);
	echo $out;
?>