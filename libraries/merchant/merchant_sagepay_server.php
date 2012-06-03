<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
 * CI-Merchant Library
 *
 * Copyright (c) 2011-2012 Crescendo Multimedia Ltd
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

require_once(MERCHANT_DRIVER_PATH.'/merchant_sagepay_base.php');

/**
 * Merchant SagePay Direct Class
 *
 * Payment processing using SagePay Direct
 */

class Merchant_sagepay_server extends Merchant_sagepay_base
{
	public function authorize()
	{
		$request = $this->_build_authorize_or_purchase('DEFERRED');
		return $this->_submit_request($request);
	}

	public function authorize_return()
	{
		return $this->purchase_return();
	}

	public function purchase()
	{
		$request = $this->_build_authorize_or_purchase('PAYMENT');
		return $this->_submit_request($request);
	}

	public function purchase_return($redirect_url)
	{
		file_put_contents(MERCHANT_DRIVER_PATH.'/sagepay.log', print_r($_POST, true));

		switch ($this->CI->input->post('TxType'))
		{
			case 'PAYMENT':
				$success_status = Merchant_response::COMPLETE;
				break;
			case 'DEFERRED':
				$success_status = Merchant_response::AUTHORIZED;
				break;
			default:
				echo "Status=INVALID";
				exit;
		}

		// TODO: check VPSSignature

		// return response (script must call confirm_return() after processing)
		return new Merchant_sagepay_response($_POST, $success_status);
	}

	/**
	 * Because Sage Pay does things backwards compared to every other gateway
	 * (the confirm url is called by their server, not the customer),
	 * after calling purchase_return() and recording the success/failure, the calling
	 * script must end with a call to this method, to let Sage Pay know that the message
	 * was received successfully, and where to send the customer.
	 */
	public function confirm_return($redirect_url)
	{
		echo "Status=OK\r\n";
		echo "RedirectUrl=".$redirect_url;
		exit;
	}

	protected function _build_authorize_or_purchase($method)
	{
		$this->require_params('return_url');

		$request = parent::_build_authorize_or_purchase($method);
		$request['NotificationURL'] = $this->param('return_url');

		return $request;
	}

	protected function _process_url($service)
	{
		$service = strtolower($service);
		if ($service == 'payment' OR $service == 'deferred')
		{
			$service = 'vspserver-register';
		}

		return parent::_process_url($service);
	}
}

/* End of file ./libraries/merchant/drivers/merchant_sagepay_direct.php */