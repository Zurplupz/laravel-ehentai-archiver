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
	public $status;
	protected $result_type;

	protected function defineGuzzle(array $params=[]) :void
	{
		$this->guzzle = new \GuzzleHttp\Client($params);
		$this->result_type = 'json';
	}

	public function lastError()
	{
		return $this->last_error ?? false;
	}

	private function setLastError($error)
	{
		if (is_string($error)) {
			$error = json_decode($error, true) ?? $error;
		}

		$this->last_error = $error;
	}

	protected function request(string $path, array $data=[], string $method='GET', $debug=false) 
	{
		try {
			if (empty($this->guzzle)) {
				throw new \Exception(__METHOD__." Guzzle Client not defined", 1);
			}

			if (!empty($this->auth)) {
				$data['auth'] = $this->auth;
			}

			if (!empty($debug)) {
				$data['debug'] = $debug;
			}

			if (!empty($this->cookies)) {
				$data['cookies'] = $this->cookies;
			}

			$res = $this->guzzle->request($method, $path, $data);

			$this->status = $res->getStatusCode();

			switch ($this->result_type) {
				case 'json': 
					return json_decode($res->getBody(), true);

				case 'text':
					return $res->getBody()->getContents();
				
				default: return true;
			}

		} 

		catch (\GuzzleHttp\Exception\ConnectException $e) {
			$response = $e->getMessage();
			//$request = $e->getRequest();

			$this->setLastError($response);

			if (!is_string($response)) {
				$response = str_replace('\n', ' ', json_encode($response));
			}

			\Log::error('Failed connection', compact('response'));

			return false;
		}

		catch (\GuzzleHttp\Exception\RequestException $e) {
			//$request = $e->getRequest();
			$this->status = $e->getResponse()->getStatusCode();

			if ($e->hasResponse()) {
				$response = $e->getResponse()->getBody()->getContents();
			} else {
				$response = $e->getMessage();
			}				

			$this->setLastError($response);

			if (!is_string($response)) {
				$response = str_replace('\n', ' ', json_encode($response));
			} else {
				$response = json_decode($response, true) ?? $response;
			}

			$f = '%s request error, status: %s"';

			\Log::error(sprintf($f, $method, $this->status), compact('response'));

			return false;
		}

		catch (Throwable $e) {
			$error = $e->getMessage();

			$this->setLastError($error);

			if (!is_string($error)) {
				$error = str_replace('\n', '', json_encode($error));
			}

			\Log::error(__METHOD__ . ': Throwable caught', compact('error'));

			return false;
		}
	}
}