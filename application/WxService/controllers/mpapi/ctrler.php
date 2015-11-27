<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');


/*  
    + Curl Library
        - config/autoload.php  curl
        - library/Curl.php
*/

  
// http://www.nybgjd.com/3dclub/ctrler
class Ctrler  extends CI_Controller {
 
	function index(){
        $cacheName = 'Controller';
        $this->load->library('MP_Cache');
        $data = $this->mp_cache->get($cacheName);
        //exit($data);
        //$data = false;
        if($data === false){
            $data = time();
        }
        $data = 1447757156;  //debug
        
        $didarr = array();
        $tmnew = 0;
        
        $this->load->database('vr');
        $sql = "select p_did, p_tm from (select p_did, p_tm from mp_push_list where p_tm>$data group by p_did,p_tm order by p_tm desc) t1 group by p_did";
        echo $sql.'<br>';
        $query = $this->db->query($sql);
        foreach($query->result() as $row){
            if($tmnew == 0){
                $tmnew = $row->p_tm;
                $data = $tmnew;
                echo $data.'<br>';
            }
            $didarr[] = $row->p_did;
        }
        $query->free_result();

        if(count($didarr) > 0){
            // 推送
        }
        
        //删除 时间$data之前的(包含自己)的条目
        //$sql = 'delete from mp_push_list where p_tm<='.$data;
        //$this->db->query($sql);
        
        $this->db->close();

        $this->mp_cache->write($data, $cacheName, 36000);
        print_r($didarr);
	}


}

/* End of file shandong.php */
/* Location: ./controllers/shandong.php */
