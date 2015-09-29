<?php 
	set_time_limit (24 * 60 * 60);

   function getUrlContent($url){
        //$header[]='Accept-Encoding: gzip, deflate';
        $curl = curl_init();  // 初始化一个 cURL 对象
        curl_setopt($curl, CURLOPT_URL, $url);  // 设置你需要抓取的URL  
        //curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_TIMEOUT,30);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION , true);    //重定向问题
        //curl_setopt($curl, CURLOPT_ENCODING , 'gzip, deflate');
        //curl_setopt($curl, CURLOPT_USERAGENT, "RealtekVOD");  
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);  // 设置cURL 参数，要求结果保存到字符串中还是输出到屏幕上。

        $rtdata = curl_exec($curl);  // 运行cURL，请求网页
        curl_close($curl);  // 关闭URL请求      
        return $rtdata;
    }
	
	function downloadDistantFile($url, $dest)
	{
//		echo 'url:'.$url.'<br>';
		$options = array(
			CURLOPT_FILE => is_resource($dest) ? $dest : fopen($dest, 'w'),
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_URL => $url,
			CURLOPT_FAILONERROR => true, // HTTP code > 400 will throw curl error
		);

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL,$url);
		curl_setopt($ch, CURLOPT_FILE, is_resource($dest) ? $dest : fopen($dest, 'w'));
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_FAILONERROR, true);
//		curl_setopt_array($ch, $options);
//curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
//curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0); 
		$return = curl_exec($ch);

		if ($return === false)
		{
			//echo 'xxxxxxxx';
			return curl_error($ch);
		}
		else
		{
			return null;
		}
	}
/*
	$con = getUrlContent('http://master.dl.sourceforge.net/project/longtv/update4.xml');
	preg_match_all('/<version>(.*)<\/version>/', $con, $verTmp);
	preg_match_all('/<dburl>(.*)<\/dburl>/', $con, $dbTmp);
	
	$dbver = $verTmp[1][0]; $dbver='';
	$dburl = $dbTmp[1][0];
	/exit($dburl);
	if(empty($dburl))
		exit('获取龙龙直播数据库失败');
*/	
	putenv('/home/wwwroot/default/frw/application/Tv_Refer');
	$dbver = null;
	$dburl = 'http://longtv.znds.com/TVInfo.db';	
	$db_name = "./TVInfo"."_".$dbver."_.db";
	$res = downloadDistantFile($dburl, $db_name);
	if($res){
		exit($res);		
	}
	
	//sqlite 3
	$dbh = new PDO('sqlite:'.$db_name);

	$chname = '-1';	
	foreach ($dbh->query('select  title,url from (select title,url,type from tvlist union select title,url,type from backuptvlist) t1 group by t1.url order by t1.title, t1.type;') as $row)
	{
		if($row['title'] != $chname){
			if($chname != '-1')
				echo '<br>';
			echo $row['title']."\t";
			$chname = $row['title'];
			echo $row['url'];
		}
		else{
			echo ','.$row['url'];
		}
	}
		
?> 
