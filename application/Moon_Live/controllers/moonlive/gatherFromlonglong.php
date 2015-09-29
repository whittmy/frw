<?php 
	set_time_limit (24 * 60 * 60);
	
	function downloadDistantFile($url, $dest)
	{
		$options = array(
			CURLOPT_FILE => is_resource($dest) ? $dest : fopen($dest, 'w'),
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_URL => $url,
			CURLOPT_FAILONERROR => true, // HTTP code > 400 will throw curl error
		);

		$ch = curl_init();
		curl_setopt_array($ch, $options);
		$return = curl_exec($ch);

		if ($return === false)
		{
			return curl_error($ch);
		}
		else
		{
			return true;
		}
	}
/*
	$con = file_get_contents('http://update.longtv.org/update4.xml');
	preg_match_all('/<dbversion>(.*)<\/dbversion>/', $con, $verTmp);
	preg_match_all('/<dburl>(.*)<\/dburl>/', $con, $dbTmp);
	
	$dbver = $verTmp[1][0]; $dbver='';
	$dburl = $dbTmp[1][0];
	//exit($dburl);
	if(empty($dburl))
		exit('获取龙龙直播数据库失败');
*/	
	putenv('D:/www_root');
	
	$db_name = "./TVInfo"."_".$dbver."_.db";
	echo "dbname=".$db_name."<br>";
//	downloadDistantFile($dburl, $db_name);

	
	//sqlite 3
	$dbh = new PDO('sqlite:'.$db_name);

	$typeArr = array('0','1','2','3','4','5','6','7','8','9',
					'A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z',
					 'a','b','c','d','e','f','g','h','i','j','k','l','m','n','o','p','q','r','s','t','u','v','w','x','y','z' );

	//分类
	$cata_sql = 'insert into ch_cata (cata,idx,title,type) values ';		
	$cata_str = '';		 
	foreach($dbh->query('select type, name from cata order by type;') as $key=>$row){
		//echo $row['type'].', '.$row['name']."<br>";
		$id = $row['type'];
		$cata_str = $cata_str.'(\''.$typeArr["$id"].'\','.($key+100).',\''.$row['name'].'\','.$row['type'].'),';
	}
	
	//数据
	$baseId = 10;
	$lastCh = '';	
	
	$sql_ch_name = 'insert into ch_name (ch_id,name,cata) values ';
	$sql_ch_urls = 'insert into ch_urls (ch_id,idx,url) values ';
	
	$name_str = '';
	$url_str='';
	$sql = 'select id, title,url,type from (select id, title,url,type from tvlist union select id, title,url,type from backuptvlist) t1  order by  t1.type,t1.id';
	foreach ($dbh->query($sql) as $k=>$row){
		if($row['title'] != $lastCh){
			if(!empty($lastCh))
				$baseId ++;	
			$lastCh = $row['title'];
			$type = $row['type'];
			$name_str = $name_str.'('.$baseId.',\''.$row['title'].'\',\''.$typeArr["$type"].'\'),';
			$url_str = $url_str.'('.$baseId.','.'0'.',\''.$row['url'].'\'),';
		}
		else{
			$url_str = $url_str.'('.$baseId.','.'0'.',\''.$row['url'].'\'),';
		}
	}
	
	$name_str = trim( $name_str, ',');
	$url_str = trim($url_str, ',');
	$cata_str = trim($cata_str,',');
	if(strlen($name_str)>0 
		&& strlen($url_str)>0
		&& strlen($cata_str)>0){
		echo "<br>============================<br>";
		
		$sql_ch_name = $sql_ch_name.$name_str;
		$sql_ch_urls = $sql_ch_urls.$url_str;	
		$cata_sql = $cata_sql.$cata_str;
		echo '<br>'.$cata_sql.'<br><br><br>';
		echo $sql_ch_name."<br><br><br>";
		echo $sql_ch_urls."<br><br><br>";
		
		$con = mysql_connect('localhost','root','root');
		if(!$con){
			die(mysql_error());
		}
		mysql_select_db('moon_live');
		mysql_query("SET NAMES 'UTF8'");
		mysql_query('truncate ch_cata')  or die(mysql_error());		
		mysql_query('truncate ch_name')  or die(mysql_error());
		mysql_query('truncate ch_urls')  or die(mysql_error());
		
		mysql_query($cata_sql) or die(mysql_error());	
		mysql_query($sql_ch_name) or die(mysql_error());	
		mysql_query($sql_ch_urls) or die(mysql_error());
		
		echo 'insert data from longlong!';
		mysql_close($con);		
	}
	

	
	
?> 