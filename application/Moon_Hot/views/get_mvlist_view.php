<?php
	$mlist = array();
	foreach($query->result() as $row){
		$mlist[] = array($row->id, $row->title, $row->img, $row->haveurl);
	}
	$query->free_result();
	
	$rslt = array();	
	$rslt['cdate'] = $cdate;
	$rslt['pdate'] = $pdate;
	$rslt['ndate'] = $ndate;
	$rslt['mlist'] = $mlist;
	$out = json_encode($rslt);
	echo $out;
?>