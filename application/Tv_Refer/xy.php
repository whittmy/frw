<?php
	$url = 'http://my.eagleapp.tv/edit/list.php?random=1905195856';
	$con = file_get_contents($url);

	$obj = json_decode($con);	
/*
	$chArr = $obj->{'live'};
	
	foreach($chArr as $ch){
		echo $ch->{'name'}."\t".strtr($ch->{'urllist'}, array('#'=>',')).'<br>';
	}
*/
	//var_dump($obj);	
	print_r($obj);
?>
