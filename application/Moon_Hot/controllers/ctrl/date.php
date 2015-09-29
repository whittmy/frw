<html>
<body>
<table width="95%" id="mytab"  border="1">
<?php
		include 'connect.php';
		connect();
	
		$sql = 'select distinct year_id from r_year_vid order by year_id desc';
		$rslt = mysql_query($sql) or die(mysql_error());
		$str = '';
		while($row=mysql_fetch_array($rslt)){
			$str = $str.'<p>'.$row['year_id'].'</p>';
		}
		echo $str;
		closedb();




?>
</table>
</body>
</html>