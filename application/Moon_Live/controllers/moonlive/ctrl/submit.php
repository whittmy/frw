<?php
if(!isset($_POST['name_src']))
exit('false');

$fp = fopen('post.txt','a+');
fwrite($fp,$_POST['name_src']."\n\n-----\n"); 

$s_conn = mysql_connect("localhost", "root", "lemoon8888", 1, "131072");
mysql_set_charset('utf8');
if(!$s_conn){
	exit('false');
}	
mysql_select_db((isset($_GET['db']) && !empty($_GET['db'])) ? $_GET['db'] : 'moon_live');	
mysql_query('begin');

$delsql = 'delete from ch_urls where ch_id='.$_GET['ch_id'];
fwrite($fp,$delsql."\n"); 
if(!mysql_query($delsql)){
	mysql_close($s_conn);	
	exit('false');
}

$rcvData = trim($_POST['name_src']);
$urlArr = explode("\n",$rcvData); 
$sql = 'insert into ch_urls(ch_id,idx,url,tm_out) values ';
$orglen = strlen($sql);
foreach($urlArr as $k=>$urlinfo){
	$infoArr = explode(',', $urlinfo);
	$url = trim($infoArr[0]);
	if(empty($url))
		continue;	
	if(isset($infoArr[1]))
		$tm = trim($infoArr[1]);
	else
		$tm = 127;
	$sql = $sql.'('.$_GET['ch_id'].','.$k.', \''.$url.'\','.$tm.'),';	
}
$sql = rtrim($sql,',');

fwrite($fp,$sql."\n"); 
if($orglen != strlen($sql)){


	if(!mysql_query($sql)){
		mysql_query('rollback');
		mysql_close($s_conn);
		exit('false');
	}
	$sql = 'update version set ver=ver+1';
	mysql_query($sql);


}

fwrite($fp,"\n ====begin to commit===\n"); 
mysql_query('commit');

mysql_query('end');
mysql_close($s_conn);


fclose($fp);

@unlink('/a/domains/other.nybgjd.com/public_html/frw/application/Moon_Live/cache/552530ccd4a9d310e8814e071a79ba41');
exit('true');
?>
