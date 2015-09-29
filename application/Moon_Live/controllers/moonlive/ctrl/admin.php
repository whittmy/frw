<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>直播资源管理系统</title>


<style>
<!---->
textarea.txtarea{
width:100%;            /* 相对宽度 */
}
</style>

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
	setTimeout(reloadPage, 500);	
	return false;
}

//弹框完，重新刷新下本页
function reloadPage(){
	window.location.reload(); 		
}
</script>

</head>

<body>
<?php 
echo '<div style="line-height:50%" align="center">  <h2 class="tlcls">直播管理系统'.'</h2></div>';
?>
<br>
<hr />

<!--	使用iframe，可以实现提交后不用跳转到处理信息的页面,    style='display:none'       作为submit.php 的调试输出页面用 -->
<iframe width="700" height="100" name="actionframe"  ></iframe>



<form id="form1" name="form1" method="post" target="actionframe"  
<?php 	
if(!isset($_GET['ch_id']))
$_GET['ch_id']='10_0';
$infoArr = explode('_',$_GET['ch_id']); 	

$actPath = $_SERVER["SCRIPT_NAME"];
$actPath = substr($actPath,0,strrpos($actPath,'/'));
$actPath = 'http://www.nybgjd.com/'.$actPath.'/submit.php';

$dbstr = '';
if(isset($_GET['db']) && !empty($_GET['db']))
$dbstr = 'db='.$_GET['db'].'&';

echo ' action="'.$actPath.'?'.$dbstr.'ch_id='.$infoArr[0].'">';
?> 

<table width="100%" align="left">
<tr><td width="644">
频道名: <SCRIPT language=javascript>
	<!--
function QueryString()
{
	var name,value,i;
	var str=location.href;
	var num=str.indexOf("?")
		str=str.substr(num+1);
	var arrtmp=str.split("&");
	for(i=0;i < arrtmp.length;i++){
		num=arrtmp[i].indexOf("=");
		if(num>0){
			name=arrtmp[i].substring(0,num);
			value=arrtmp[i].substr(num+1);
			this[name]=value;
		}
	}
}
function FormMenu(targ,selObj,restore){ 
	var self_url=document.URL;
	var pos = self_url.indexOf('?');
	if(pos > -1)
		self_url = self_url.substring(0,pos);
	var Request=new QueryString();			
	var db = Request['db'];

	var dbstr = '';
	if(db != null)
		dbstr = "db="+db+"&";
	//exit($dbstr);	
	eval(targ+".location='"+self_url+"?"+dbstr+"ch_id="+selObj.options[selObj.selectedIndex].value+"_"+selObj.selectedIndex+"'");
}
//-->
</SCRIPT>	
<SELECT onchange="FormMenu('self',this,0)" name=menu1 id="ch_select" > 
<?php
$s_conn = mysql_connect("localhost", "root", "lemoon8888", 1, "131072");
mysql_set_charset('utf8');
if(!$s_conn){
	exit('<option value=-1>连接数据库出错</option>');
}	
mysql_select_db(isset($_GET['db']) ? $_GET['db'] :  'moon_live');

$sql = 'select ch_id,name from ch_name order by ch_id';
$rslt = mysql_query($sql);
$i=1;
while($row=mysql_fetch_array($rslt)){
	echo '<option value='.$row['ch_id'].'>'."$i.".$row['name'].'</option>';
	$i++;
}
mysql_free_result($rslt);
mysql_close($s_conn);
?>
</SELECT>
</td></tr>
<tr><td>节目源:</td></tr>
<tr><td>
<textarea name="name_src" cols="512" rows="15" class="txtarea" id="id_srcs"></textarea>
</td> </tr>
<tr><td>

<input  type="button" name="submit1" id="id_submit" value="提交" onClick="pre_submit();" />  
<!--			type="submit" 
<input name="cont" value="提交" type="button" onClick="document.form1.cont.value='正在提交,请稍后';document.form1.cont.  
disabled=true;"> 

-->		
</td></tr>
</table>  
</form>

<script language="javascript" type="text/javascript">
<!--
<?php
$infoArr = null;
if(!isset($_GET['ch_id']))
$_GET['ch_id']='1_0';
if(isset($_GET['ch_id']) && strlen($_GET['ch_id'])>0){
	$txt_srcs = '';		
	$infoArr = explode('_',$_GET['ch_id']); 

	$s_conn = mysql_connect("localhost", "root", "lemoon8888", 1, "131072");
	mysql_set_charset('utf8');
	if(!$s_conn){
		exit('<option value=-1>连接数据库出错</option>');
	}	
	//            mysql_select_db('moon_live');	
	mysql_select_db(isset($_GET['db']) ? $_GET['db'] :  'moon_live');
	$sql = 'select url,tm_out from ch_urls where ch_id='.$infoArr[0].' order by idx';
	$rslt = mysql_query($sql) or ($txt_srcs='sss');
	while($row=mysql_fetch_array($rslt)){
		if($row['tm_out'] == 0)
			$txt_srcs= $txt_srcs.$row['url'].'\n';
		else
			$txt_srcs= $txt_srcs.$row['url'].','.$row['tm_out'].'\n';
	}
	mysql_free_result($rslt);
	mysql_close($s_conn);

	echo 'document.getElementById("ch_select").options['.$infoArr[1].'].selected = true;';
	echo 'document.getElementById("id_srcs").value=\''.$txt_srcs.'\';';
}
?>
//-->	
</script>

</body>
</html>
