1.本目录中的oem_mgr_cache.php为手写的，误删
2.cate_info_cache.php由函数updateCacheFile()生成

3. 本目录的头文件有全局性质，所以在include以及全局变量使用上有些特殊之处，他们要独立于 控制器代码之外，且高于他们，
   所以就放在了ci的index.php中，对本项目而言就是 erge.php,参考如下：



/**
 *	rocking global include...
 
*  global $CATA_INFO_CACHE, $OEM_MAP, $OEM_INFO

 */
 	
require($application_folder.'/controllers/erge/include/oem_mgr_cache.php');
	
require($application_folder.'/controllers/erge/include/cate_info_cache.php');
 
	



/*
 * --------------------------------------------------------------------
 
* LOAD THE BOOTSTRAP FILE
 * --------------------------------------------------------------------
 
*
 * And away we go...
 
*
 */


require_once BASEPATH.'core/CodeIgniter.php';


/* End of file index.php */

/* Location: ./index.php */


以上，在控制器的各函数中就可以不用再单独include文件，使用变量前，先 global一下。