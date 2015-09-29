<?php
	//本想 OEM_INFO中用 名字作为key的，但考虑可能的性能、已经修改信息需要关联查找这两个变量就麻烦
	//所以OEM_MAP在和OEM_INFO保持一致的情况下，就作为检查键值、以及某些时候获取oem名的作用吧
	$OEM_MAP = array(1=>'c500', 
					2=>'c700-lemoon'
				);
					
	$OEM_INFO = array(	1=>array('c500201507', 'i am c500', '180'), 	//三项分别为服务端和客户端约定的：skey， handlemsg， 密串有效期(秒)
						2=>array('', '','')
						
						
				);
?>