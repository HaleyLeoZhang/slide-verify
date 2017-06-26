<?php
// RSA相关 抽象工厂
namespace Crypt;
interface Basic{
	/**
	* 加密
	* @param String : data 需要加密的数据
	* @return String: 密文
	*/
	public static function encrypt();

	/**
	* 解密
	* @param String : data 需要解密的数据
	* @return String: 明文
	*/
	public static function decrypt();
}