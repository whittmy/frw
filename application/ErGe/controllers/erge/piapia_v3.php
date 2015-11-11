<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

 
define('Cache_Time_OrgPlayUrl', 480);
define('Cache_Time_RealUrl_Youku', 480);
define('Cache_Time_RealUrl_Iqiyi', 480);
define('Cache_Time_RealUrl_Funshion', 480);
define('Cache_Time_RealUrl_Letv', 480); 
define('Cache_Time_RealUrl_General', 480);

define('Cache_Time_TopInfo', 86400); //24*3600
define('Cache_Time_PL', 86400);
define('Cache_Time_ResList', 86400); //include h & gridview
define('Cache_Time_HResList', 86400);

// http://www.nybgjd.com/erge/piapia/

require_once(APPPATH.'/controllers/erge/PUBLIC_CFG/netproxy.php');
// 注意： 需要将mOemName更改为 本文件的名字，否则会影响其它的缓存
class PiaPia_V3  extends CI_Controller {
	// 测试sign为：  adseewweefewxd
	private $mChkKey;
	private $mOemName;
    
    function __construct() {
       parent::__construct();
       $this->mOemName = 'piapia_v3';
       $this->mChkKey = '@#xpia&*1452';
    }

    function usbdebug($mac=null, $orgstr=null){
        exit("abcedfg");
    }
    
    function cfgLoading(){
        echo 'getLoadingcfg';
    }
    
    function _chkSign($reqtime, $md5){
        if(strlen($reqtime)==0 || strlen($md5)==0)
            return false;
        
        $sign = md5($reqtime.$this->mChkKey);
        if(strcasecmp($sign, $md5) != 0)
            return false;
        
        //时效性30s
        //if(time()-$reqtime > 30){
        //    return false;
        //}
        return true;
    }
    
    
    //http://www.nybgjd.com/erge/piapia/playurl/36054/youku
    function playurl($id='', $src='',$idx='', $tm='', $md5=''){
        if(strlen($id)==0 || strlen($src)==0)
            return;
        if(!$this->_chkSign($tm, $md5))
            return;
        $data1 = trim($this->_parserUrl($id, $src, $idx));
        switch($src){
        case 'letv':
            $len=abs(filesize($data1)); //临时文件路径
            header('Content-Description: File Transfer');
            header('Content-Type: application/vnd.apple.mpegurl');
            header('Content-Disposition: attachment; filename='.time().'.m3u8');
            header('Content-Transfer-Encoding: binary');
            header('Expires: 0');
            header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
            header('Pragma: public');
            header('Content-Length: ' . $len);
            readfile($data1);               
            break;            
        default:
            header('Location: '.$data1);
            break;
        }
        return;
    }

    function _parserUrl($id='', $src='',$idx=''){
        $this->load->library('MP_Cache');
        $cacheName = $this->mOemName.'/api__parserUrl_orgurl/'.$id.'-'.$idx.'-'.$src;
        $url = $this->mp_cache->get($cacheName);
        if($url === false){
            $this->load->database('prj_mmh');
			$sql = "select l_downurl from mmh_vod_libs where l_pid=$id and l_idx=$idx and l_src='$src'";
            //exit($sql);
			$query = $this->db->query($sql);
			foreach($query->result() as $row){
                $url = $row->l_downurl;
                if(!empty($url))
                    $this->mp_cache->write($url, $cacheName, constant('Cache_Time_OrgPlayUrl'));
                break;
            }
            $query->free_result(); 
            $this->db->close();
        }
        
        if($src=='ergeduoduo' || $src=='link'){
            return $url;
        }
        
        $cacheName = "_parserUrl/".urlencode($url).'-'.urlencode($src);
        $data1 = $this->mp_cache->get($cacheName);
        if($data1 === false){
            $data1 = trim(NetProxy("videoParser::parser", $url, null));
            if(!empty($data1)){
                switch($src){                  
                case 'youku':
                    $this->mp_cache->write($data1, $cacheName, constant('Cache_Time_RealUrl_Youku'));
                    break;
                case 'iqiyi':
                     $this->mp_cache->write($data1, $cacheName, constant('Cache_Time_RealUrl_Iqiyi'));
                     break;
                case 'funshion':
                    $this->mp_cache->write($data1, $cacheName, constant('Cache_Time_RealUrl_Funshion'));
                    break;
                case 'letv':
                    $tmpfile = tempnam(sys_get_temp_dir(),urlencode("$id_$src_$idx"));
                    if($tmpfile != false){
                        $len = file_put_contents($tmpfile, $data1, LOCK_EX); 
                        if($len != false){
                            $this->mp_cache->write($tmpfile, $cacheName, constant('Cache_Time_RealUrl_Letv'));
                        }
                    }
                    break;
                default:
                    $this->mp_cache->write($data1, $cacheName, constant('Cache_Time_RealUrl_General'));
                    break;
                }
            }
        }
        return $data1;
    }
    
 
	// return: 
	// 1001: sign error
	// 1002: timeout
	// 1003: lost args
	// 0: ok
	function _check1($header, $flag=null){
        include(APPPATH.'/controllers/erge/PUBLIC_CFG/oem_mgr_cache.php');

		//global  $OEM_INFO;
		return 0;
		
		//防刷新
		if($flag==null || empty($flag))
			$this->load->library('AntiCollect');
		else
			$this->load->library('AntiCollect', array('prefix'=>$flag));
			
		$this->anticollect->apply();
		
		
        //header parser --
         $jobj = json_decode($header);
		//Add
		if($jobj == null){
			return 1003;
		}

		if(!isset($jobj->sign) || !isset($jobj->reqtime) ){
			return 1003;
		}

		$h_sign = $jobj->sign;
		$reqtime = $jobj->reqtime;
		
		if(empty($h_sign) || strlen($reqtime)==0)
			return 1003;
 
 
        if(!$this->_chkSign($reqtime, $h_sign)){
            return 1001;
        }
 		
		//$skey = $OEM_INFO[$clientID][0];
		//$handmsg = $OEM_INFO[$clientID][1];
		//$expire = $OEM_INFO[$clientID][2];
		//$delayed = null;//86400;
		//
		//$h_sign = strtr($h_sign, array(' '=>'+'));
		//$rslt =  $this->authcode($h_sign, "DECODE", $skey, $expire, $delayed);
		//if($rslt == '' || $rslt != $handmsg)
		//	return 1001;
 
		return 0;
	}

 

	function errorMsg($code){
		$flag = true;
		if($flag){
			if($code == 1001){
				$str = '{"body":{},"header":{"retMessage":"you sign is bad!","retStatus":300},"page":[]}';
				exit($str);
			}	
			else if($code == 1002){
				$str = '{"body":{},"header":{"retMessage":"you interface is exceed the time limit!","retStatus":300},"page":[]}';
				exit($str);
			}
			else if($code == 1003){
				$str = '{"body":{},"header":{"retMessage":"lost args","retStatus":300},"page":[]}';
				exit($str);			
			}
			else if($code == 1){
				$str = '{"body":{"resList":[],"headerList":[]},"header":{"retMessage":"ok","retStatus":200},"page":[]}';
				exit($str);
			}
		}
	}

	//
    //X-Sendfile 将允许下载非 web 目录中的文件（例如/root/），即使文件在 .htaccess 保护下禁止访问，也会被下载。
    // 缺点是你失去了对文件传输机制的控制, 比如只允许用户下载文件一次
    //Nginx 默认支持该特性，不需要加载额外的模块。只是实现有些不同，需要发送的 HTTP 头为 X-Accel-Redirect。另外，需要在配置文件中做以下设定
    /*
        location /mmh_upload/ {
          internal;
          root   /some/path;
        }
        internal 表示这个路径只能在 Nginx 内部访问，不能用浏览器直接访问防止未授权的下载。
    */
    
    // arg:  upload/vod/2015-11-11/201511111447222418.jpg
    // downurl: http://www.nybgjd.com/erge/piapia_v3/image/upload%2fvod%2f2015-11-11%2f201511111447222418.jpg
    function image($path=null){
        if(empty($path))
            exit;
        // 使用说明
        // $file = "/mmh_upload/中文名.tar.gz";  //对应 /some/path/mmh_upload/中文名.tar.gz
 
        //若想发送 /some/path/中文名.tar.gz,则配置改为如下：
        /*
        location /mmh_upload/ {
          internal;
          alias   /some/path/; # 注意最后的斜杠
        }
        */
 
        $file = "/mmh_upload/".urldecode($path);
        $filename = basename($file);
     
        header("Content-type: application/octet-stream");
     
        //处理中文文件名
        $ua = $_SERVER["HTTP_USER_AGENT"];
        $encoded_filename = rawurlencode($filename);
        if (preg_match("/MSIE/", $ua)) {
         header('Content-Disposition: attachment; filename="' . $encoded_filename . '"');
        } else if (preg_match("/Firefox/", $ua)) {
         header("Content-Disposition: attachment; filename*=\"utf8''" . $filename . '"');
        } else {
         header('Content-Disposition: attachment; filename="' . $filename . '"');
        }
     
        //让Xsendfile发送文件
        header("X-Accel-Redirect: $file");
    }

	//////////////// 获取分类  ////////////////////////
	//http://www.nybgjd.com/erge/piapia/getCata?header={"sign":"","client":1}&body={}
	//http://localhost/ci/erge.php/erge/piapia/getCata?header={"sign":"","client":1}&body={}
 
	/////////////////// 以下为 C700 Android 通用  //////////////////////////
	//$cids:类别列表，如 '2_7,3,4' , '2'
	//刚刚才知道，mp_cache竟然可以缓存数据结构
	//这里的分类可以所有的分类、包括水平的
    // raw(无需缓存)
	function _getResListData($cids, $pgId, $pgsize=15, $style=''){
        $ret = array();
		$cids = trim($cids, ',');
        if(strlen($cids) < 1)
            return $ret;
        
        $this->load->database('prj_mmh');
        $cids = strtr($cids, array('_'=>','));
        
        $sql = 'select r_did from mmh_vod_r_type_dir where r_cid in ('.$cids.') limit '.($pgId-1)*$pgsize.', '.$pgsize;	
        //exit($sql);
        $query = $this->db->query($sql);
        $cids = '';
        foreach($query->result() as $row){
            $cids = $cids.$row->r_did.',';
        }
        $cids = trim($cids, ',');
        if(strlen($cids) < 1)
            return $ret;
        //exit($cids);
        $sql = 'select d_id,d_name,d_pic,d_hasseq,d_type from mmh_vod where d_id in ('.$cids.')';//这儿没必要再限制了  order by d_id limit '.($pgId-1)*$pgsize.', '.$pgsize;	
        //exit($sql);
        $query = $this->db->query($sql);
        foreach($query->result() as $row){
            $cif = array();
            $cif['id'] = $row->d_id.'';
            $cif['name'] = $row->d_name;
            //$cif['pic'] = 'posters/'.$row->d_id.'.jpg';//$row->d_pic;
            $pic = trim($row->d_pic);
            if(empty($pic) or substr($pic, 0, 4)=='http'){
                 $cif['pic'] = $pic;
            }
            else{
                 $cif['pic'] = 'http://www.nybgjd.com/erge/'.$this->mOemName .'/image/'.urlencode($pic);
            }
           
            $cif['hasseq'] = $row->d_hasseq.'';
            $cif['type']=$row->d_type;
            $ret[] = $cif;	
        }
        $query->free_result();
        $this->db->close();		 
        
        return $ret;
	}
	
    function _getTopInfoCache(){
        $this->load->library('MP_Cache');
        $cacheName = $this->mOemName.'/TOPIC_INFO_CACHE';
        $TOPIC_INFO_CACHE = $this->mp_cache->get($cacheName);	
        if($TOPIC_INFO_CACHE === false){
            $TOPIC_INFO_CACHE = array();
            $this->load->database('prj_mmh');
            $query = $this->db->query('select * from mmh_vod_device_res_mgr order by t_id');	
            foreach($query->result() as $row){
                 $TOPIC_INFO_CACHE[$row->t_id] = array('name'=>$row->t_name, 'subcls'=>$row->t_subclses, 'allcls'=>$row->t_allclses, 'subcls_desc'=>$row->t_sub_desc);
            }
            $query->free_result();
            $this->db->close();
            
            if(count($TOPIC_INFO_CACHE) > 0){
                $this->mp_cache->write($TOPIC_INFO_CACHE, $cacheName, constant('Cache_Time_TopInfo'));
            }
        }
        return $TOPIC_INFO_CACHE;
    }
    
	//获取某一类别下面所有水平布局的分类及其元素列表
	//该函数主要被其它函数调用，向服务器获取第一页数据时才调用。
    // raw(无需缓存)
	function _getHListArr($topCata, $pgidx=1, $pgsize=20, $style=''){ //style:代表该hlist的展示形态
        $TOPIC_INFO_CACHE = $this->_getTopInfoCache();
        $hlistArr = array();
		if(isset($TOPIC_INFO_CACHE[$topCata])){
            $items =  $TOPIC_INFO_CACHE[$topCata]['subcls'];
            $items = explode(',',$items);//注意，每个item可能包含_,代表组合的意思
            
            $names =  $TOPIC_INFO_CACHE[$topCata]['subcls_desc'];
            $names = explode(',',$names);

            foreach($items as $key=>$item){
                $hlist = array();
                $hlist['title'] = $names[$key];//$CATA_INFO_CACHE[$item]['name'];
                $hlist['id'] = $item;  //可能包含 _, 是类型组合
                $hlist['style'] = $style;
                $data = (strlen($item)==0) ? null: $this->_getResListData($item, $pgidx, $pgsize);
                if($data != null){
                    $hlist['childs'] = $data;
                    $hlistArr[] = $hlist;
                }
            }
		}
		return $hlistArr;
	}

	//http://www.nybgjd.com/erge/piapia/getHResList/?header={"sign":"22"}&body={"pageindex":1,"id":"25308","pagesize":21}
	function getHResList($bgencache=0){
		$header = $this->input->get('header');
        $body = $this->input->get('body');
		
	    // check !!
		$ret = $this->_check1($header); 
		$this->errorMsg($ret);
 
		//body parser --
		$jobj = json_decode($body);
		if(!isset($jobj->id) || strlen($jobj->id)==0){
			$this->errorMsg(1003); 
		}
 
		$pgId = isset($jobj->pageindex)? $jobj->pageindex :1;
		$pgsize = isset($jobj->pagesize) ? $jobj->pagesize : 15 ;
		$fid = isset($jobj->id) ? $jobj->id: -1;
  
		$this->load->library('MP_Cache');
		$cacheName = $this->mOemName.'/api_getresList/'.$fid.'-'.$pgId.'-'.$pgsize;
		$data1 = $this->mp_cache->get($cacheName);
		if($data1 === false || $bgencache==1){
            $data1 = $this->_genHResListCache($fid, $pgId, $pgsize,$cacheName);
            if(!empty($data1))
                $this->mp_cache->write($data1, $cacheName, constant('Cache_Time_HResList'));
		}
		exit($data1);
	}
    
    function _genHResListCache($fid, $pgId, $pgsize, $cacheName){
        //exit($fid.','.$pgId.','.$pgsize.','.$cacheName);
		$body = $this->_getResListData($fid, $pgId, $pgsize);
		$ret['body']['resList'] = $body;

		//header
		$ret['header']['retMessage'] = 'ok'; 
		$ret['header']['retStatus'] = 200; 		
        
		//page
		$ret['page'] = array();
		$data1 = json_encode($ret);		
        //echo('----write '.$cacheName.'<br>');
        return $data1;
    }
    

	/*
        获取分类导航页面的数据
        该界面结构为：
            水平分类条目
                内容的类别 可以为：
                    类别列表(最初)、
                        一个类别代表一个水平分类，该类的内容为该类下面的剧集列表(mmh_vod)
                    
                    单集影片列表、
                        就是推荐的影片列表
                    软件列表、
                    专辑列表、
                    课件列表
                各内容对应各种操作。
                
            全部分类
                最初设计的应该为 剧集的列表，其它是否要兼容？？
    */
	//http://www.nybgjd.com/erge/piapia/getresList/?header={"sign":"22"}&body={"pageindex":1,"id":"7","pagesize":21}
	function getresList($bgencache=0){
		$header = $this->input->get('header');
        $bodys = $this->input->get('body');
		
	    // check !!
		$ret = $this->_check1($header); 
		$this->errorMsg($ret);

		//body parser --
		$jobj = json_decode($bodys);
		if(!isset($jobj->id) || strlen($jobj->id)==0){
			$this->errorMsg(1003); 
		}
 
		$pgId = isset($jobj->pageindex)? $jobj->pageindex :1;
		$pgsize = isset($jobj->pagesize) ? $jobj->pagesize : 15 ;
		$fid = isset($jobj->id) ? $jobj->id: -1;
        
        //+ style: 2015.9.13
        // 'role'/''/...    you can the carton character directly
        $style = '';
 
        //==========debug============
        //if($fid == 6){
        //    //数理思维，暂时用 ‘动画’代替
        //    $fid = 1;
        //}
 
		$this->load->library('MP_Cache');
		$cacheName = $this->mOemName.'/api_getresList/'.$fid.'-'.$pgId.'-'.$pgsize;
		$data1 = $this->mp_cache->get($cacheName);
		if($data1 === false || $bgencache==1){
            $data1 = $this->_genResListCache($fid, $pgId, $pgsize,$cacheName,$style);
            if(!empty($data1))
                $this->mp_cache->write($data1, $cacheName, constant('Cache_Time_ResList'));
		}
		exit($data1);
	}		

    function _genResListCache($fid, $pgId, $pgsize,$cacheName, $style){
        $TOPIC_INFO_CACHE = $this->_getTopInfoCache();
        $headerList = array();
        $body = array();
        $ret = array();
        $title = '';
        //获取All类别的数据
        if(isset($TOPIC_INFO_CACHE[$fid])){
            $ids = $TOPIC_INFO_CACHE[$fid]['allcls'];
            $title = $TOPIC_INFO_CACHE[$fid]['name'];
            //exit("$ids,$pgId, $pgsize");
            if(strlen($ids)>0){
                $body = $this->_getResListData($ids, $pgId, $pgsize); //return array()
            }
            if($pgId == 1 && $fid!=-1){
                $headerList = $this->_getHListArr($fid, 1, 20, $style);//return array()
            }
        }

        $ret['body']['resList'] = $body;
        $ret['body']['headerList'] = $headerList;
        $ret['body']['title'] = $title;

        //header
        $ret['header']['retMessage'] = 'ok'; 
        $ret['header']['retStatus'] = 200; 

        //page
        $ret['page'] = array();
        $data1 = json_encode($ret);	
        return $data1;
    }



    //果然将Url合并到 影片信息中的这种设计方式不是很符合我们的使用场景，主要是因为，某列存储了所有剧集的所有url，到这儿我们取部分的url就比较麻烦了。虽然对cms管理起来很方便。
    //这部分还是改成之前的 res_libs的组织架构吧。每次cms修改影片信息时就需要多表联合起来增删改查啦。
	
	//http://pc-20140929gboj/ci/erge.php/erge/piapia/getPL?header={"sign":"","client":1}&body={"id":1, "pageindex":1} 
    //http://www.nybgjd.com/erge/piapia/getPL/?header={"sign":"22"}&body={"pageindex":1,"id":25234,"pagesize":"30","type":"10"}
	function getPL($bgencache=0){
		$header = $this->input->get('header');
        $body = $this->input->get('body');
		
	    // check !!
		$ret = $this->_check1($header); 
		$this->errorMsg($ret);	

		//body parser --
		$jobj = json_decode($body);
		if(!isset($jobj->id) || strlen($jobj->id)==0){
			$this->errorMsg(1003); 
		}
		$id = isset($jobj->id)? $jobj->id:-1;
		$pgId = isset($jobj->pageindex)? $jobj->pageindex :1;
		$pgsize = isset($jobj->pagesize) ? $jobj->pagesize : 15 ;
		$type = isset($jobj->type) ? $jobj->type:0;

		$this->load->library('MP_Cache');
		$cacheName = $this->mOemName.'/api_getPL/'.$id.'-'.$type.'-'.$pgId.'-'.$pgsize;
		$data1 = $this->mp_cache->get($cacheName);
 		if($data1 === false || $bgencache==1){
            $data1 = $this->_genPLCache($id, $type, $pgId, $pgsize, $cacheName);
		}

		exit($data1);
	}
    
    //点进去要播放时获取数据的接口， 所以必须确保取到的数据必须是 播放地址，而非某类别，要么是某部剧集，要么是包含很多单集的某类别，要不就是某单集，除此之外没有其它的。不可能出现包含多部剧集(播放地址无法取啦)的情况
    //处理类别， type=10:代表分类列表模式，要先查询r表，其它的估计都是单集或剧集
    function _genPLCache($id, $type, $pgId, $pgsize, $cacheName){
        $id = trim($id, ' ');
        $ids = strtr($id, array('_'=>','));
        if(strlen($ids) < 1)
            return null;
 
        //$this->load->database('erge2');
        $this->load->database('prj_mmh');
        //$hasbody = false;
        $ret = array();
        
        $episode = 0;
        
        //如果是分类列表，则查询r表
        if($type == 10 || $type==14){  //可能是视频也可能是音频
            //先计算总数，因为都是单集，总不能获取所有之后再去计算数量，这样会浪费内存。
            $sql = "select count(r_did) cnt from mmh_vod_r_type_dir where r_cid in ($ids)";
            $query = $this->db->query($sql);
            foreach($query->result() as $row){
                $episode = $row->cnt;
                break;
            }
            $query->free_result();

            $sql = "select r_did from mmh_vod_r_type_dir where r_cid in ($ids) limit ".($pgId-1)*$pgsize.', '.$pgsize;
            $query = $this->db->query($sql);
            $ids = '';
            foreach($query->result() as $row){
                $ids = $ids.$row->r_did.',';
            }
            $query->free_result();
            $ids = trim($ids, ', ');
            if(strlen($ids) >= 1){
                $sql = 'select l_pid,l_idx,l_downurl,l_pic,l_name,l_src from mmh_vod_libs where l_pid in ('.$ids.') and l_src!=\'360\''; //这里未限制
                $query = $this->db->query($sql);
            }
        }
        else{
            //确保进入该分支的，只能是某一部影片或连续剧集。 这点至关重要，否则这儿根本就没法处理
            
            //计算总集数，以便客户端知道什么时候结束(其实就是 d_episode字段)
            $sql = 'SELECT sum(d_episode) episode FROM `mmh_vod` where d_id ='.$ids;
            $query = $this->db->query($sql);
            foreach($query->result() as $row){
                $episode = $row->episode;
                break;
            }
            $query->free_result();       

            $bg = ($pgId-1)*$pgsize;
            $con = "l_idx>=$bg and l_idx<".($bg+$pgsize);            
            $sql = 'select l_pid,l_idx,l_downurl,l_pic,l_name,l_src from mmh_vod_libs where l_pid='.$ids.' and l_src!=\'360\' and '.$con.' order by l_idx' ;
            //exit($sql);
            $query = $this->db->query($sql);
        }

        $cifbakarr = array();
        foreach($query->result() as $row){
            $flag = $row->l_pid.'_'.$row->l_idx;
            //echo $flag.'<br>';
            if(isset($cifbakarr[$flag])){
                //array_push($cifbakarr[$flag]['ulist'], array('s'=>$row->l_src, 'u'=>$row->l_idx));
                $cifbakarr[$flag]['ulist'][] = array('s'=>$row->l_src, 'u'=>$row->l_idx);
            }
            else{
                $cif = array();
                $cif['id'] = $row->l_pid.'';
                $cif['name'] = $row->l_name;
                $cif['pic'] = $row->l_pic;
                $cif['ulist'][] = array('s'=>$row->l_src, 'u'=>$row->l_idx);  
                $cifbakarr[$flag] = $cif;  
            }
         }
        $query->free_result();
        $this->db->close();
 
        $ret['body']['pList'] = array_values($cifbakarr);        
 
        //header
        $ret['header']['retMessage'] = 'ok'; 
        $ret['header']['retStatus'] = 200; 	
        //User-Agent:Mozilla/5.0 (iPhone; CPU iPhone OS 9_1 like Mac OS X) AppleWebKit/601.1.46 (KHTML, like Gecko) Version/9.0 Mobile/13B143 Safari/601.1   iphone no-use????
        $ret['header']['extra'] = array(array('key'=>'iqiyi','value'=>'User-Agent:Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/45.0.2454.101 Safari/537.36'), array('key'=>'ergeduoduo', 'value'=>'erge_iphone_v2 iOS/2.0.0.0 CFNetwork/672.0.8 Darwin/14.0.0'));

        //page
       // $mod = $episode % $pgsize;
        $pgcnt =  ceil($episode/$pgsize);// + (($mod!=0)? 1 : 0);
        //$cntOfcurpg = count($ret['body']['pList']);

        $ret['page'] = array();
        $ret['page']['pageindex'] = $pgId;
        $ret['page']['pagecount'] = $pgcnt;
        //$ret['page']['count'] = $cntOfcurpg;
        $ret['page']['count'] =  $episode;
        
        $data1 = json_encode($ret);		
        $this->load->library('MP_Cache');
        $this->mp_cache->write($data1, $cacheName, constant('Cache_Time_PL'));	
        return $data1;
    }

	function report($reson=null){
	}

	//---------------- 校验区-----------------------------------
	/*
	$rslt =  authcode('93fc1eHk9vpIl6jA59A', "DECODE", 'c500201507', 180, 1432023121);
	$rslt =  authcode('i am c500', "ENCODE", 'c500201507', 180, 1432023426);
	if($rslt == null)
		echo 'null';
	else
		echo $rslt;
	*/	
	
	function prt($a, $b){
		//echo $a.'='.$b.'<br>';
	}	

	function getmtime(){
		$s = microtime();
		//$s = '0.00020300 1432023426';
		return $s;
	}
	
	
	 /**
     * @param string $string 原文或者密文
     * @param string $operation 操作(ENCODE | DECODE), 默认为 DECODE
     * @param string $key 密钥
     * @param int $expiry 密文有效期, 加密时候有效， 单位 秒，0 为永久有效		！！！@rocking：已修改，加密与解密都用到， 解密时用于判断这个有效期是否与加密时用的有效期一致，避免客户端加密时私自篡改有效期
					已将 expiry的值也纳入到了加密串的一部分，方便核对
					expiry 加密与解密必须要一致
	 * @param int $de_delayed	!!! @rocking: 解密时用于延长密文的有效期，
     * @return string 处理后的 原文或者 经过 base64_encode 处理后的密文	
	  
		@example
		$a = authcode('abc', 'ENCODE', 'key', 3600);
		$b = authcode($a, 'DECODE', 'key', 3600); // 在一个小时内，$b得到'abc'，否则 $b 为空('')
	 */ 
	function authcode($string, $operation = 'DECODE', $key = '', $expiry = 3600, $de_delayed=null) {
		$ckey_length = 4;   
		// 随机密钥长度 取值 0-32;
		// 加入随机密钥，可以令密文无任何规律，即便是原文和密钥完全相同，加密结果也会每次不同，增大破解难度。
		// 取值越大，密文变动规律越大，密文变化 = 16 的 $ckey_length 次方
		// 当此值为 0 时，则不产生随机密钥
		$this->prt('decstring', $string);
		$key = md5($key ? $key : 'deflt_key'); //这里可以填写默认key值
		$this->prt('key', $key);
		$keya = md5(substr($key, 0, 16));
		$this->prt('keya', $keya);
		
		$keyb = md5(substr($key, 16, 16));
		$this->prt('keyb', $keyb);
		
		$keyc = $ckey_length ? ($operation == 'DECODE' ? substr($string, 0, $ckey_length): substr(md5(/*microtime()*/$this->getmtime()), -$ckey_length)) : '';
		$this->prt('keyc', $keyc);
		$cryptkey = $keya.md5($keya.$keyc);
		$this->prt('cryptkey', $cryptkey);
		
		$key_length = strlen($cryptkey);
		
		//rocking
		$exp = sprintf("%05d", $expiry);
		
		$string = $operation == 'DECODE' ? base64_decode(substr($string, $ckey_length)) : sprintf('%010d', $expiry ? $expiry + time() : 0).substr(md5($string.$keyb), 0, 16).$exp.$string; //rocking：此处将expire信息添加进去
		$string_length = strlen($string);
		$this->prt('string', $string);
		
		$result = '';
		$box = range(0, 255);
		
		$rndkey = array();
		for($i = 0; $i <= 255; $i++) {
			$rndkey[$i] = ord($cryptkey[$i % $key_length]);
		}
		
		for($j = $i = 0; $i < 256; $i++) {
			$j = ($j + $box[$i] + $rndkey[$i]) % 256;
			$tmp = $box[$i];
			$box[$i] = $box[$j];
			$box[$j] = $tmp;
		}
		
		for($a = $j = $i = 0; $i < $string_length; $i++) {
			$a = ($a + 1) % 256;
			$j = ($j + $box[$a]) % 256;
			$tmp = $box[$a];
			$box[$a] = $box[$j];
			$box[$j] = $tmp;
		 
		 //prt("chr$i=", (ord($string[$i]) ^ ($box[($box[$a] + $box[$j]) % 256])).'');
			$result .= chr(ord($string[$i]) ^ ($box[($box[$a] + $box[$j]) % 256]));
		}

		if($operation == 'DECODE') {
			//result 格式： tttttttttt35038f456834d134xxxxxsssssssssssssssssss
			// t代表时间戳，10字节[0,10)
			// x 代表expiry的值，5字节，不足补零 [26,31)
			// s 最终的字符串[31,
			//rocking modify, 修改目的：防止客户端私自篡改(客户端可能第三方去写)密串的有效性.
			//增加 解密延时机制，如果事先约定的时间短了，可以再服务器端进行延时处理 $de_delayed
			
			$this->prt('result', $result);
			$client_tm = intval(substr($result, 0, 10));
			$client_exp_val = intval(substr($result,26,31));
			
			
			$tm_diff_delay = $client_tm - time();
			if($de_delayed != null)
				$tm_diff_delay += $de_delayed;
			//echo $client_tm.','.$client_exp_val.', '.$tm_diff_delay.'<br>';
			//注意判断：
			// 优先判断 exiry  以及 md5 的一致性
			// 最后再判断 有效期(有三种情况：为0代表无限，非延时、延时)
			
			
			if(($client_exp_val==$expiry)
				&& substr($result, 10, 16) == substr(md5(substr($result, 31).$keyb), 0, 16)
				&& ($client_tm==0||$tm_diff_delay>0)) 	//注意这儿的判断哦，虽说可以延时，但并不代表就允许事先约定的expiry就可以随意变更。
			{
				return substr($result, 31);
			} else {
				return '';
			}
		} else {
			return $keyc.str_replace('=', '', base64_encode($result));
		}
	}

    function _seed() {
       list($msec, $sec) = explode(' ', microtime());
       return (float) $sec;
    }		
}

/* End of file piapia_v3.php */
/* Location: ./controllers/piapia_v3.php */
