<?php
	$con = file_get_contents('http://121.199.22.39/blumedia/tv_jsonv2.php?province=%E5%B9%BF%E4%B8%9C&rss=1');

	$obj = json_decode($con);
	if($obj == null)
		exit;
	
	//print_r($obj->live);
	foreach($obj->live as $cate){
		//echo $cate->name.',';
		foreach($cate->channel as $ch){
			echo $ch->name.':<br>';
			$url = $ch->urls;
			
			$prefix = substr($url, 0, 5);
			$endfix = substr($url, 5);
			
			
			$prefix = str_split($prefix,1);
			
			//split endfix
			$endfix = substr($endfix, 0, 6).$prefix[3].substr($endfix, 6);
			$endfix = substr($endfix, 0, 2).$prefix[0].substr($endfix, 2);
			$endfix = substr($endfix, 0, 1).$prefix[1].substr($endfix, 1);
			
			
			$len = strlen($endfix);
			$endfix1 = substr($endfix, 0, $len-5);
			$endfix2 = substr($endfix, $len-5,1);
			$endfix3 = substr($endfix,  $len-4);
			
			
			$rurl = $endfix1.$prefix[2].$endfix2.$prefix[4].$endfix3;
			$urls = base64_decode($rurl);
			
			$urlArr = explode(';', $urls);
			foreach($urlArr as $url){
				echo $url.'<br>';
			}
			echo '<br><br>';
			
			//echo base64_decode($rurl).'<br><br>';
			
			//break;
		}
		//break;
	}





?>