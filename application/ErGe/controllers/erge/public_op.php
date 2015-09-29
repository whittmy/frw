<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

 
// http://www.nybgjd.com/erge/public_op/

class Public_op  extends CI_Controller {
	// 测试sign为：  adseewweefewxd
	
	private $mOemName;
    
    function __construct() {
       parent::__construct();
       $this->mOemName = 'public_op';
    }
  
	
	//http://www.nybgjd.com/erge/public_op/updateCacheFile
	function updateCacheFile(){
 		$incpath = dirname(__FILE__).'/PUBLIC_CFG/';
		if(!file_exists($incpath)) {
			mkdir($incpath, 0777);   
		}
		
		$this->_genCataCache($incpath . 'cate_info_cache.php');
	
	}
	
	function _compress_html($s){
		$s = str_replace(array("\r\n","\n","\t"), array('','','') , $s);
		$pattern = array (
						"/> *([^ ]*) *</",
						"/[\s]+/",
						"/<!--[\\w\\W\r\\n]*?-->/",
					   // "/\" /",
						"/ \"/",
						"'/\*[^*]*\*/'"
						);
		$replace = array (
						">\\1<",
						" ",
						"",
						//"\"",
						"\"",
						""
						);
		return preg_replace($pattern, $replace, $s);
	}

	
	//生成所有类别的缓存
	//生成顶层类别与其子类别的信息缓存
	function _genCataCache($fpath){
		$parent_child_info = array();
		$every_cls_info = array();
        $topic_info = array();
        
		$this->load->database('erge2');
		$sql = 'select c_id,c_name,c_type,c_hasseq,c_pid from res_class order by c_id';	
		$query = $this->db->query($sql);	
		foreach($query->result() as $row){
			$cif = array();
			$cif['name'] = $row->c_name;
			$cif['pid'] = $row->c_pid;
			$cif['type'] = $row->c_type;
			$cif['hasseq'] = $row->c_hasseq;
			
			$every_cls_info[$row->c_id]  = $cif;
			
			//保存子类到其所属父类
			if($cif['pid'] != 0){
				$pid = $cif['pid'];
				$cif['cid'] = $row->c_id;
				$parent_child_info[$pid][] = $cif;
			}
		}
		$query->free_result();

		foreach($every_cls_info as $k=>$v){
			$cnt = 0;
			//$sql = 'select count(d_id) cnt from res_dir where d_pid='.$k;	
			$sql = 'select count(id) cnt from r_cls_dir where r_cid='.$k;	
			$query = $this->db->query($sql);	
			foreach($query->result() as $row){
				$cnt = $row->cnt;
				break;
			}
			$query->free_result();
			$every_cls_info[$k]['cnt'] = $cnt; 
		}
         //get topic info
        $sql = 'select * from res_topic order by id';
        $query = $this->db->query($sql);	
		foreach($query->result() as $row){
             $topic_info[$row->id] = array('name'=>$row->t_name, 'subcls'=>$row->t_subclses, 'allcls'=>$row->t_allclses, 'subcls_desc'=>$row->t_sub_desc);
        }
        $query->free_result();




        $this->db->close();
		$fp = fopen($fpath,'wb');
		fwrite($fp,'<?php'.chr(10));
		
		
		$cacheValue = '$CATA_INFO_CACHE = '.$this->_compress_html(var_export($every_cls_info, true)).';'.chr(10);
		fwrite($fp,$cacheValue);
		
 		$cacheValue = '$TOPCATA_INFO_CACHE = '.$this->_compress_html(var_export($parent_child_info, true)).';'.chr(10);
		fwrite($fp,$cacheValue);
        
        $cacheValue = '$TOPIC_INFO_CACHE = '.$this->_compress_html(var_export($topic_info, true)).';'.chr(10);
        fwrite($fp, $cacheValue);
		
		fwrite($fp, chr(10).'?>');
		echo 'finished';
	}

    //http://www.nybgjd.com/erge/public_op/all_class2dir
    function all_class2dir(){
        $this->load->database('erge2');
		$sql = 'SELECT * FROM res_class';	
		$query = $this->db->query($sql);	
        $rlst = $query->result();
        $query->free_result();
        
        $this->db->trans_strict(TRUE);
        $this->db->trans_start();//TRUE:测试模式
		foreach($rlst as $row){
			$org_id = $row->c_id;
            $name = $row->c_name;
            $hasseq = $row->c_hasseq;
            $type = 10;
            $sql = "insert into res_dir (d_name, d_pid, d_hasseq, d_type,d_src) values ('$name', 0, $hasseq, $type, 'cls')";
            $this->db->query($sql);
            $newId =  $this->db->insert_id();
 
            $sql2 = "update res_class set c_id=$newId where c_id=$org_id";
            $this->db->query($sql2); 
            $sql21 = "update res_class set c_pid=$newId where c_pid=$org_id";
            $this->db->query($sql21);
            
            $sql22 = "update res_dir set d_pid=$newId where d_pid=$org_id";
            $this->db->query($sql22);
             
            $sql33 = "update res_libs set l_cateid=$newId where l_cateid=$org_id";
            $this->db->query($sql33);
 
            $sql3 = "update r_cls_dir set r_cid=$newId where r_cid=$org_id";
            $this->db->query($sql3);
            echo "-=-= had update the orgid:$org_id to newId:$newId -==-=<br>";
        }
        $this->db->trans_complete();
        $this->db->close();
        echo "####### finished  #########3";
    }
    
    
    
    
    
    
    
    
}


/* End of file shandong.php */
/* Location: ./controllers/shandong.php */
