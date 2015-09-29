<?php
	if(!isset($_GET['date'])){
		exit('no args "date"!');
	}
	include 'connect.php';
	connect();

	$sql = "select t1.id,t1.title, t1.showdate,t1.actor,t1.haveurl from tbl_info t1, r_year_vid t2 where t2.year_id=".$_GET['date']." and t1.id=t2.vid";
	$rslt = mysql_query($sql) or die(mysql_error());	

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=gb2312" />
<title>QVOD管理系统</title>
<style type="text/css">
<!--
body,table{
	font-size:12px;
}
table{
	table-layout:fixed;
	empty-cells:show; 
	border-collapse: collapse;
	margin:0 auto;
}
td{
	height:20px;
}
h1,h2,h3{
	font-size:12px;
	margin:0;
	padding:0;
}

.title { background: #FFF; border: 1px solid #9DB3C5; padding: 1px; width:90%;margin:20px auto; }
	.title h1 { line-height: 31px; text-align:center;  background: #2F589C url(th_bg2.gif); background-repeat: repeat-x; background-position: 0 0; color: #FFF; }
		.title th, .title td { border: 1px solid #CAD9EA; padding: 5px; }


/*这个是借鉴一个论坛的样式*/
table.t1{
	border:1px solid #cad9ea;
	color:#666;
}
table.t1 th {
	background-image: url(th_bg1.gif);
	background-repeat::repeat-x;
	height:30px;
}
table.t1 td,table.t1 th{
	border:1px solid #cad9ea;
	padding:0 1em 0;
}
table.t1 tr.a1{
	background-color:#f5fafe;
}



table.t2{
	border:1px solid #9db3c5;
	color:#666;
}
table.t2 th {
	background-image: url(th_bg2.gif);
	background-repeat::repeat-x;
	height:30px;
	color:#fff;
}
table.t2 td{
	border:1px dotted #cad9ea;
	padding:0 2px 0;
}
table.t2 th{
	border:1px solid #a7d1fd;
	padding:0 2px 0;
}
table.t2 tr.a1{
	background-color:#e8f3fd;
}



table.t3{
	border:1px solid #fc58a6;
	color:#720337;
}
table.t3 th {
	background-image: url(th_bg3.gif);
	background-repeat::repeat-x;
	height:30px;
	color:#35031b;
}
table.t3 td{
	border:1px dashed #feb8d9;
	padding:0 1.5em 0;
}
table.t3 th{
	border:1px solid #b9f9dc;
	padding:0 2px 0;
}
table.t3 tr.a1{
	background-color:#fbd8e8;
}

-->
</style>
<script type="text/javascript">
	function ApplyStyle(s){
		document.getElementById("mytab").className=s.innerText;
	}
</script>

<script language="javascript">
	//get url 参数,暂未用
	function GetUrlParms()    
	{
		var args=new Object();   
		var query=location.search.substring(1);//获取查询串   
		var pairs=query.split("&");//在逗号处断开   
		for(var   i=0;i<pairs.length;i++)   
		{   
			var pos=pairs[i].indexOf('=');//查找name=value   
				if(pos==-1)   continue;//如果没有找到就跳过   
				var argname=pairs[i].substring(0,pos);//提取name   
				var value=pairs[i].substring(pos+1);//提取value   
				args[argname]=unescape(value);//存为属性   
		}
		return args;
	}

	//提交内容预处理
	function pre_submit(){
		document.form1.submit1.value="正在提交,请稍后";
		document.form1.submit1.disabled=true;
		
		// var outText = "";
		// var inText = document.getElementById("id_srcs").value;
		// outText = + inText;
		// document.getElementById("id_srcs").value = outText;
		
		document.form1.submit();	
		
		alert("提交成功！");	
		setTimeout(reloadPage, 5000);	
		

		return false;
	}
	
	//弹框完，重新刷新下本页
	function reloadPage(){
		window.location.reload(); 		
	}
	

</script>
</head>

<body>
<!--
<div class="title">
	<h1></h1>
	<table><tr><td>
		点击链接切换样式：<a href="javascript:;" onclick="ApplyStyle(this)">t1</a>
		<a href="javascript:;" onclick="ApplyStyle(this)">t2</a>
		<a href="javascript:;" onclick="ApplyStyle(this)">t3</a>
	</td></tr></table>
</div>
-->
<table width="95%" id="mytab"  border="1" class="t1">
  <thead>
    <th width="4%">编号</th>
	<th width="5%">已上映</th>
    <th width="10%">标题</th>
    <th width="50%">演员</th>
    <th width="20%">上映日期</th>
  </thead>
	<?php
		$addstyle = 1;
		$txt = null;
		while($row=mysql_fetch_array($rslt)){
			if($addstyle == 1){
				// align="center"
				$txt .='<tr class="a1"  onClick="EventClick('.$row['id'].')">'."\n";
				$addstyle = 0;
			}
			else{
				//align="center" 
				$txt .='<tr  onClick="EventClick('.$row['id'].')">'."\n";
				$addstyle = 1;
			}
			
			$txt = $txt.'<td>'.$row['id'].'</td>'."\n";
			$txt = $txt.'<td>'.$row['haveurl'].'</td>'."\n";
			$txt = $txt.'<td>'.$row['title'].'</td>'."\n";
			$txt = $txt.'<td>'.$row['actor'].'</td>'."\n";
			$txt = $txt.'<td>'.$row['showdate'].'</td>'."\n";
			//$txt = $txt.'<td>'.'go'.'</td>'."\n";		
			$txt .= '</tr>'."\n";
		}
		echo $txt;
	?>  
</table>

<iframe width="700" height="100" name="actionframe"  id="actionframe" style='display:none'></iframe>

<form id="form1" name="form1" method="post"  target="actionframe"  action="mvsubmit.php" >
	<textarea name="name_src" cols="512" rows="15" class="txtarea" id="id_srcs"></textarea>
	<input  type="submit" name="submit" id="id_submit" value="提交" />  
</form>

<script  type="text/javascript">
	
	function complate(){
		//回调方法
		var rtTxt = document.getElementById("actionframe").contentWindow.document.body.innerHTML;
		if(rtTxt == 'true')
			alert('提交成功');
		else{
			rtTxt = rtTxt.replace(/<[^>].*?>/g,"");
			alert('提交失败\n'+rtTxt);
		}	
		window.location.reload(); 	
	}
		
	//判断iframe是否读取完成
	function iframeLoaded(iframeEl,callback) {
		if(iframeEl.attachEvent) {
			iframeEl.attachEvent("onload", function() {
				if(callback && typeof(callback) == "function") {
					callback();
				}
			});
		} else {
			iframeEl.onload = function() {
				if(callback && typeof(callback) == "function") {
					callback();
				}
			}
		}
	}
	

   iframeLoaded(document.getElementById("actionframe"),complate) ;	
</script>

<?php
	if(isset($_GET['vid'])){
		$sql = 'select vid,title,url from tbl_playurl where vid='.$_GET['vid'];
		$rslt = mysql_query($sql) or die(mysql_error());
		$txt = '';
		while($row=mysql_fetch_array($rslt)){
			$txt = $txt.$row['vid'].','.$row['title'].','.$row['url'].'\n';
			
		}
		$txt = trim($txt, "\n,");
		
		if(empty($txt))
			$txt = '无';
		echo '<script type="text/javascript"> document.getElementById("id_srcs").value="'.$txt .'";</script>';
	}
?>
	
<script type="text/javascript">
	function EventClick(vid){
		var self_url=document.URL;
		var pos = self_url.indexOf('&');
		if(pos > -1)
			self_url = self_url.substring(0,pos);
		self.location.href=	self_url+"&vid="+vid;
	}
</script>
</body>
</html>
<?php
	closedb();
?>



