<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Api extends CI_Controller {

	/**
	 * Index Page for this controller.
	 *
	 * Maps to the following URL
	 * 		http://example.com/index.php/geturl
	 *	- or -  
	 * 		http://example.com/index.php/geturl/index
	 *	- or -
	 * Since this controller is set as the default controller in 
	 * config/routes.php, it's displayed at http://example.com/
	 *
	 * So any other public methods not prefixed with an underscore will
	 * map to /index.php//<method_name>
	 * @see http://codeigniter.com/user_guide/general/urls.html
	 */
	function download($n){
		//echo 'come in <br>';
		if($n==null || (strlen($n)<=0))
			exit;
		//echo 'come in 2<br>';
/*
		if(($n == 'vst') || ($n == 'net.myvst.v2')){ 
			header('Location: http://cos.myqcloud.com/1000970/partner/apk/cabadd833396884372ef06cff0a497c6/WHT_20140717181341.apk');
		}
		else if(($n == 'hdp') || ($n == 'hdpfans.com')){
			header('Location: http://cos.myqcloud.com/1000970/partner/apk/cabadd833396884372ef06cff0a497c6/WHT_20140721171045.apk');
		}
		else if($n == 'mmarket'){
			header('Location: http://bcs.duapp.com/upgrade/MoonMarket_v4.2_final.apk');
		}
		else if($n == 'com.fyzb.tv'){
			header('Location: http://cos.myqcloud.com/1000970/partner/apk/cabadd833396884372ef06cff0a497c6/WHT_20140717181038.apk');
		}
		else if($n == 'com.qiyi.video'){
			header('Location: http://cos.myqcloud.com/1000970/partner/apk/5129a1a158e914951addb1892b7c429a/WHT_20140802113335.apk');
		}
		else if($n == 'mtv'){
			header('Location: http://cos.myqcloud.com/1000970/partner/apk/cabadd833396884372ef06cff0a497c6/WHT_20140906164654.apk');
		}
		else if($n == 'com.rocking.together256top'){
			header('Location: http://cos.myqcloud.com/1000970/partner/apk/cabadd833396884372ef06cff0a497c6/WHT_20140721193334.apk');
		}	
		else if($n == 'com.youku.tv'){
			header('Location: http://cos.myqcloud.com/1000970/partner/apk/cabadd833396884372ef06cff0a497c6/WHT_20140722140528.apk');
		}

*/

		if(($n == 'vst') || ($n == 'net.myvst.v2')){ 
			header('Location: http://app.znds.com/down/20150518/vst-2.6.7.1-dangbei.apk');
		}
		else if(($n == 'hdp') || ($n == 'hdpfans.com')){
			header('Location: http://app.znds.com/down/20150529/HDPzb-1.9.1-dangbei.apk');
		}
		else if($n == 'mmarket'){
			header('Location: http://bcs.duapp.com/upgrade/MoonMarket_v4.2_final.apk');
		}
		else if($n == 'com.fyzb.tv'){
			header('Location: http://cos.myqcloud.com/1000970/partner/apk/cabadd833396884372ef06cff0a497c6/WHT_20140717181038.apk');
		}
		else if($n == 'com.qiyi.video'){
			header('Location: http://cos.myqcloud.com/1000970/partner/apk/5129a1a158e914951addb1892b7c429a/WHT_20140802113335.apk');
		}
		else if($n == 'mtv' || $n == 'com.rocking.moon_living'){
			header('Location: http://download.007looper.com/apks/MTV_5.1.0.apk');
		}
		else if($n == 'com.rocking.together256top'){
			header('Location: http://cos.myqcloud.com/1000970/partner/apk/cabadd833396884372ef06cff0a497c6/WHT_20140721193334.apk');
		}	
		else if($n == 'com.youku.tv'){
			header('Location: http://download.007looper.com/apks/com.youku.tv-1.apk');
		}
		else if($n == 'com.kandian.vodapp4tv'){
			header('Location: http://app.znds.com/down/20150424/kskp-4.6.50-dangbei.apk');
		}
		else if($n == 'com.kanke.video'){
			header('Location: http://app.znds.com/down/20150601/kkyshd-7.3-dangbei.apk');
		}
		else if($n == 'com.yinyuetai.yytv.tvbox'){
			header('Location: http://app.znds.com/down/20150401/kyyt-1.4.0-dangbei.apk');
		}
		else if($n == 'com.vst.c2dx.health'){
			header('Location: http://cos.myqcloud.com/1000970/partner/apk/cabadd833396884372ef06cff0a497c6/WHT_20141008182110.apk');
		}
		else if($n == 'cn.box.cloudbox.app'){
			header('Location: http://cos.myqcloud.com/1000970/partner/apk/cabadd833396884372ef06cff0a497c6/WHT_20140722115631.apk');
		}
		else if($n == 'com.elinkway.tvlive2'){
			header('Location: http://app.znds.com/down/20150109/dsj-2.4.3-dangbei.apk');
		}
		else if($n == 'com.moretv.android'){
			header('Location: http://app.znds.com/down/20150505/dsmsp-2.5.5-dangbei.apk');
		}	





	}

	function upinfo($vercode = null){
		$status = 0;
		if($vercode==null || !is_numeric($vercode)){
			$status = 0;
		}
		
		$str = '{"status":"'.$status.'", 
				"ver":"v2.3", 
				"vercode":"23", 
				"url":"http://bcs.duapp.com/upgrade/Win8_Launcher_2.3_final.apk", 
				"md5":"67ae52ff8322c3e5950075af7c9164f6", 
				"info":""}';
				
		exit($str);	
	}
	function getcfg(){
/*		$str = '{"live":["hdpfans.com","http://cos.myqcloud.com/1000970/partner/apk/1dbc98fa9fe520f671e5234797257472/WHT_20140823145852.apk"],
			"vod":["cn.gc","http://file.reco.cn/download/3bf3802ca03f0cb363753466d7cea096/GiTV%E5%BD%B1%E8%A7%86%E7%82%B9%E6%92%ADv1.6.4.apk"],
			"market":["com.rocking.together256top","http://cos.myqcloud.com/1000970/partner/apk/cabadd833396884372ef06cff0a497c6/WHT_20140721193334.apk"]}';
*/

		$str = '{"live":["com.weibao.live","http://app.znds.com/down/20141105/weibao.live-2.3.6_znds.apk"],
			"vod":["cn.gc","http://file.reco.cn/download/3bf3802ca03f0cb363753466d7cea096/GiTV%E5%BD%B1%E8%A7%86%E7%82%B9%E6%92%ADv1.6.4.apk"],
			"market":["com.rocking.together256top","http://cos.myqcloud.com/1000970/partner/apk/cabadd833396884372ef06cff0a497c6/WHT_20140721193334.apk"]}';

/*

		$str = '{"live":["com.rocking.moon_living","http://download.007looper.com/apks/MTV_5.1.0.apk"],
			"vod":["","http://download.007looper.com/apks/com.youku.tv-1.apk"],
			"market":["com.rocking.together256top","http://cos.myqcloud.com/1000970/partner/apk/cabadd833396884372ef06cff0a497c6/WHT_20140721193334.apk"]}';

*/

		echo $str;
	}
}

/* End of file api.php */
/* Location: ./controllers/api.php */
