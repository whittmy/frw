<?php
	$url = 'http://so.52itv.cn/vst_cn/live.php';
	$con = file_get_contents($url);

	$obj = json_decode($con);	
	$chArr = $obj->{'live'};
	
	foreach($chArr as $ch){
		echo $ch->{'name'}."\t".strtr($ch->{'urllist'}, array('#'=>',')).'<br>';
	}
	
?>