<?php

/**
 * 
 * 微信支付API异常类
 * @author widyhu
 *
 */
class WxAppPayException extends \Think\Exception {
	public function errorMessage()
	{
		return $this->getMessage();
	}
}
