<?php
	if(isset($_POST['name_src'])){
		include 'connect.php';
		connect();
		
		$str = trim($_POST['name_src'], "\n, ");
		$bclear = false;
		
		$lines = explode("\n",$str);
		//exit("行数：".count($lines));
		foreach($lines as $line){
			$colsArr = explode(',', $line);
			if(count($colsArr) != 3){
				closedb();
				exit('每行的列数不全为3');
			}	
			$vid = trim($colsArr[0]);
			$title = trim($colsArr[1]);
			$url = trim($colsArr[2]);
			
			if(!is_numeric($vid) || strlen($title)<1 || strlen($url)<10){
				closedb();
				exit('请检查每行的3列是否合法');
			}
			if(!$bclear){
				$sql = 'delete from tbl_playurl where vid='.$vid;
				mysql_query($sql) or die(mysql_error());
				$bclear = true;
			}
			
			$sql = 'insert into tbl_playurl(vid,title,url) values('.$vid.',\''.mysql_escape_string($title).'\',\''.mysql_escape_string($url).'\')';
			mysql_query($sql) or die(mysql_error());
			
			$sql = 'update tbl_info set haveurl=1 where id='.$vid;
			mysql_query($sql) or die(mysql_error());			
		}	
		closedb();		
		exit('true');
	}


?>