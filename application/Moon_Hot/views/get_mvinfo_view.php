<?php
	$info = array();
	foreach($query->result() as $row){
		break;
	}
	if(isset($row->id)){
		$info['id'] = $row->id;
		$info['title'] = $row->title;
		$info['img'] = $row->img;
		$info['date']= $row->showdate;
		$info['director'] = $row->director;
		$info['actor'] = $row->actor;
		$info['type'] = $row->type;
		$info['area'] = $row->area;
		$info['intro'] = $row->intro;
	}
	
	$query->free_result();
	
	if(count($info) == 0)
		$out = '{}';
	else	
		$out = json_encode($info);
	echo $out;
?>