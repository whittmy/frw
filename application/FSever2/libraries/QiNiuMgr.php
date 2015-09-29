<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

require_once ( APPPATH . 'third_party/qiniu_sdk/io.php') ;
require_once ( APPPATH . 'third_party/qiniu_sdk/rs.php') ;
require_once ( APPPATH . 'third_party/qiniu_sdk/fop.php') ;
require_once ( APPPATH . 'third_party/qiniu_sdk/rsf.php') ;
require_once ( APPPATH . 'third_party/qiniu_sdk/resumable_io.php') ;


class QiNiuMgr
{
    var $ci;


    var $bucket = 'club3d-rock';
    var $domain = '7u2p22.com1.z0.glb.clouddn.com';
    var $accessKey = 'T-yJRYYhJYPqSyKL3Yv9wAdR_jmXK8NCJe9pAFnN';
    var $secretKey = 'Uxr4pngswu1forEbjWIB8KZx1nai79g4FzysgPJs';
    
	/**
	 * Constructor - Initializes and references CI
	 */
    function QiNiuMgr()
    {
        $this->ci =& get_instance();
        
        //$this->path = $this->ci->config->item('mp_cache_dir');
	Qiniu_setKeys($this->accessKey, $this->secretKey);

    }
    
    function getpvrUrl($key){
	$baseUrl = Qiniu_RS_MakeBaseUrl($this->domain, $key);
	$getPolicy = new Qiniu_RS_GetPolicy();
	$privateUrl = $getPolicy->MakeRequest($baseUrl, null);
	return $privateUrl;
    }
}
