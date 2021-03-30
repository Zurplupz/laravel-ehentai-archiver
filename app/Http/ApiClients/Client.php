<?php

namespace App\Http\ApiClients;

/**
 * 
 */
abstract class Client
{
	protected $guzzle;
	protected $base_uri;
	protected $auth;
	protected $status;
	protected $result_type;

	protected function defineGuzzle(array $params) :void
	{
		$this->guzzle = new \GuzzleHttp\Client($params);
	}

	protected function request(string $path, array $data=[], string $method='GET', $debug=false) 
	{
		try {
			if (empty($this->guzzle)) {
				throw new \Exception(__METHOD__." Cliente Guzzle no definido", 1);
			}

			if (!empty($this->auth)) {
				$data['auth'] = $this->auth;
			}

			if (!empty($debug)) {
				$data['debug'] = $debug;
			}

			$res = $this->guzzle->request($method, $path, $data);

			$this->status = $res->getStatusCode();

			switch ($this->result_type) {
				case 'json': 
					return json_decode($res->getBody(), true);
				
				default: return true;
			}

		} catch (\GuzzleHttp\Exception\RequestException $e) {
			$error['error'] = $e->getMessage();
			$error['request'] = $e->getRequest();

			if ($e->hasResponse()) {
				$this->status = $e->getResponse()->getStatusCode();

				if ($this->status) {
					$error['response'] = $e->getResponse(); 
				}
			}

			\Log::error('Error occurred in get request.', ['error' => $error]);

			return false;

		} catch (\Exception $e) {
			\Log::error($e->getMessage());
			$this->status = 500;
			return false;

		}
	}
}