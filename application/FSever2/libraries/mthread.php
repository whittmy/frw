<?php if (!defined('BASEPATH')) exit('No direct script access allowed'); 

/**
 * @title:		PHP���߳���(Thread)
 * @version:	1.0
 * @author:		phper.org.cn < web@phper.org.cn >
 * @published:	2010-11-2
 * 
 * PHP���߳�Ӧ��ʾ��������ͬ������
 *  
 *  $thread = new MThread($sflag);
 *  $thread->('actioaddthreadn_log','a');
 *  $thread->addthread('action_log','b');
 *  $thread->addthread('action_log','c');
 *  $thread->runthread();
 *  
 *  function action_log($info) {
 *  	$log = 'log/' . microtime() . '.log';
 *  	$txt = $info . "\r\n\r\n" . 'Set in ' . Date('h:i:s', time()) . (double)microtime() . "\r\n";
 *  	$fp = fopen($log, 'w');
 *  	fwrite($fp, $txt);
 *  	fclose($fp);
 *  }
 
 
 *  �ø���ĺ�����Ҫ����һ��Ĭ�ϲ���('-1')������ʼ��MThread����ʱ�Ĳ�����
 */
class MThread {
    var $hooks = array();
    var $args = array();
    
    public function MThread($sflag) {
		$flag = intval($sflag[0]);
		
		if($flag > -1){
			//˵�����̵߳Ĵ���״��
			ignore_user_abort(true);
			call_user_func_array($this->hooks[$flag], $this->args[$flag]);
			log_message('error', "thread[$flag] exe finished,exit!!");
			exit;
		}	
    }

    public function addthread($func)
    {
    	$args = array_slice(func_get_args(), 1);
    	$this->hooks[] = $func;
		$this->args[] = $args;
		return true;
    }
    
    public function runthread()
    {
		echo "runthread   <br>";
		for($i = 0, $size = count($this->hooks); $i < $size; $i++)
		{
			$fp=fsockopen($_SERVER['HTTP_HOST'],$_SERVER['SERVER_PORT']);
			if($fp)
			{
				$out = "GET {$_SERVER['PHP_SELF']}?flag=$i HTTP/1.1\r\n";
				$out .= "Host: {$_SERVER['HTTP_HOST']}\r\n";
				$out .= "Connection: Close\r\n\r\n";
				
				echo $out.'<br><br>';
				fputs($fp,$out);
				fclose($fp);
			}
		}
    }
}

/* End of file MThread.php */