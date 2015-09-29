<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
/**
* 生成时效性密码（数字）
* 参考资料：http://www.idontplaydarts.com/2011/07/google-totp-two-factor-authentication-for-php/
* @author 云淡然 http://qianduanblog.com
* @version 1.0
* 2013年10月21日12:22:09

生成密码：
// 参数1：密码长度
// 参数2：密码有效期（秒）
$ExpiryPwd1=new ExpiryPwd(9,120);
$pwd1=$ExpiryPwd1->create();
echo $pwd1;


验证密码：
// 参数1：密码长度
// 参数2：密码有效期（秒）
$ExpiryPwd1=new ExpiryPwd(9,120);
$bool1=$ExpiryPwd1->validate($pwd1);
var_dump($bool1);
注意：验证密码的参数必须和生成密码的参数一致。


*/
class ExpiryPwd
{
	// 密钥
	public $secret="PEHMPSDNLXIOG65U";
 
	// 有效期秒数 [1,+∞)
	public $seconds=1200;
 
	// 密码长度 [1,9]
	public $length=6;
 
	// 构造函数
	function __construct($params){
		$this->length = $params[0];
		$this->seconds = $params[1];
	}
 
	// 编码数组
	private static $rules = array(
		"A" => 0,	"B" => 1,
		"C" => 2,	"D" => 3,
		"E" => 4,	"F" => 5,
		"G" => 6,	"H" => 7,
		"I" => 8,	"J" => 9,
		"K" => 10,	"L" => 11,
		"M" => 12,	"N" => 13,
		"O" => 14,	"P" => 15,
		"Q" => 16,	"R" => 17,
		"S" => 18,	"T" => 19,
		"U" => 20,	"V" => 21,
		"W" => 22,	"X" => 23,
		"Y" => 24,	"Z" => 25,
		"2" => 26,	"3" => 27,
		"4" => 28,	"5" => 29,
		"6" => 30,	"7" => 31
	);
 
	/**
	 * 暂时无用
	 * 生成base32格式的16位密钥
	 * @return string
	 **/
	private function secret_key() {
		$b32 	= "234567QWERTYUIOPASDFGHJKLZXCVBNM";
		$s 	= "";
 
		for ($i = 0; $i < 16; $i++){
			$s .= $b32[rand(0,31)];
		}
 
		return $s;
	}
 
	/**
	 * 根据有效期生成时间戳
	 * @return integer
	 **/
	private function timestamp() {
		return floor(microtime(true)/$this->seconds);
	}
 
	/**
	 * 生成32位字符串为二进制
	 * @return string
	 **/
	private function binary() {
 
		$b32 	= strtoupper($this->secret);
 
		if (!preg_match('/^[ABCDEFGHIJKLMNOPQRSTUVWXYZ234567]+$/', $b32, $match)){
			throw new Exception('Invalid characters in the base32 string.');
		}
 
		$l 	= strlen($b32);
		$n	= 0;
		$j	= 0;
		$binary = "";
 
		for ($i = 0; $i < $l; $i++) {
 
			$n = $n << 5; 				// Move buffer left by 5 to make room
			$n = $n + self::$rules[$b32[$i]]; 	// Add value into buffer
			$j = $j + 5;				// Keep track of number of bits in buffer
 
			if ($j >= 8) {
				$j = $j - 8;
				$binary .= chr(($n & (0xFF << $j)) >> $j);
			}
		}
 
		return $binary;
	}
 
	/**
	 * 根据二进制和有效期生成密码
	 * @return string
	 **/
	public function oath_hotp(){
		$binary=$this->binary();
		$counter=$this->timestamp();
	    if (strlen($binary) < 8){
			throw new Exception('Secret binary is too short. Must be at least 16 base 32 characters');
	    }
 
	    $bin_counter = pack('N*', 0) . pack('N*', $counter);		// Counter must be 64-bit int
	    $hash 	 = hash_hmac ('sha1', $bin_counter, $binary, true);
 
	    return str_pad($this->oath_truncate($hash), $this->length, '0', STR_PAD_LEFT);
	}
 
	/**
	 * Extracts the OTP from the SHA1 hash.
	 * @param binary $hash
	 * @return integer
	 **/
	private function oath_truncate($hash){
	    $offset = ord($hash[19]) & 0xf;
 
	    return (
	        ((ord($hash[$offset+0]) & 0x7f) << 24 ) |
	        ((ord($hash[$offset+1]) & 0xff) << 16 ) |
	        ((ord($hash[$offset+2]) & 0xff) << 8 ) |
	        (ord($hash[$offset+3]) & 0xff)
	    ) % pow(10, $this->length);
	}
 
	/**
	 * 验证密码是否正确
	 * @param string $key - User specified key
	 * @return boolean
	 **/
	public function validate($key) {
		$window = 4;
		$useTimeStamp = true;
		$timeStamp = $this->timestamp();
 
		if ($useTimeStamp !== true) $timeStamp = (int)$useTimeStamp;
 
		$binarySeed = $this->binary();
 
		for ($ts = $timeStamp - $window; $ts <= $timeStamp + $window; $ts++){
			if ($this->oath_hotp($binarySeed, $ts) == $key){
				return true;
			}
		}
 
		return false;
 
	}
 
	public function create(){
		$binary=$this->binary();
		$counter=$this->timestamp();
		return $this->oath_hotp($binary,$counter);
	}
}


/* End of file expirypwd.php */