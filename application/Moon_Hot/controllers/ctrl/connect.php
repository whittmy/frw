<?php
	$g_con = null;
	function connect(){
		$g_con = mysql_connect("localhost", "root", "xdfd8DFSFeoesdi838D", 1, "131072");
		mysql_set_charset('utf8');
		if(!$g_con){
			exit('连接数据失败');
		}	
		mysql_select_db('db_vod_news');		
	}
	
	function closedb(){
		if($g_con)
			mysql_close($g_con);

	}




?>