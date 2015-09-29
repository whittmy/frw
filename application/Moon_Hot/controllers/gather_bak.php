<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Gather extends CI_Controller {

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
	 * map to /index.php/set/<method_name>
	 * @see http://codeigniter.com/user_guide/general/urls.html
	 */
	function index($incre='update'){
		set_time_limit(0);
		ignore_user_abort(false);
		if($incre == 'update'){
			//增量采集
			log_message('error', '增量采集开始！');  
			$year = date('Y');
			$month = date('m');
			log_message('error', "Today is $year-$month");
			$this->_gather($year, $month);
			
		}
		else if($incre == 'all'){
			//全量采集
			log_message('error', '全量采集开始！');    
			$this->_gatherAll();
		}
		else{
			exit('^V^, 你懂的！');
		}
	}
	 
	function _mymail($infoArr){		
		$config['protocol']="smtp";
		$config['smtp_host'] = 'smtp.qq.com';
		$config['smtp_user'] = '1840223551@qq.com';
		$config['smtp_pass'] = '07318676881';
		$config['crlf']="\r\n";   	//这两行针对qq邮箱的，其它的好像不用
		$config['newline']="\