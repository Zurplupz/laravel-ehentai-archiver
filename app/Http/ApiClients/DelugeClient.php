<?php

namespace App\Http\ApiClients;

use App\Http\ApiClients\Client;
use GuzzleHttp\Cookie\CookieJar;

/**
 * 
 */
class DelugeClient extends Client
{	
	const METHOD_AUTH = 'auth.login';
    const METHOD_ADD_FILE = 'core.add_torrent_file';
    const METHOD_GET_STATUS = 'core.get_torrent_status';

	protected $url;
	protected $cookies;
	protected $id;

	function __construct(string $pass, string $host, int $port)
	{
		$this->result_type = 'json';

		$this->url = $this->defineUrl($host, $port);

		$this->defineGuzzle(['cookies' => true]);

		$this->id = rand(10000,99999);

		$success = $this->login($pass);

		if (!$success) {
			throw new \Exception("Deluge Web Client Auth Error");
		}
	}

	protected function defineUrl(string $host, int $port)
	{
		$host = preg_replace('/\/$/', '', $host);

		return $host . ':' . $port . '/json';
	}

	protected function login(string $pass) :bool
	{
		$json = [
			'method' => self::METHOD_AUTH,
			'params' => [ $pass ],
			'id' => $this->id
		];

		$r = $this->request($this->url, compact('json'), 'POST');

		// todo: create torrent client servicee
		if (empty($r) || !empty($r['error'])) {
			\Log::error('Deluge Request Error', $r ?: ['status' => $this->status]);
			return false;
		}

		$this->cookies = $this->guzzle->getConfig('cookies');

		return true;
	}

	public function addFile(string $path, string $data, bool $add_paused=false)
	{
		$json = [
			'method' => self::METHOD_ADD_FILE,
			'params' => [ $path, $data, compact('add_paused') ],
			'id' => $this->id
		];

		return $this->request($this->url, compact('json'), 'POST');
	}

	public function getTorrentStatus(string $torrent_id, array $data)
	{
		$json = [
			'method' => self::METHOD_GET_STATUS,
			'params' => [ $torrent_id, $data ],
			'id' => $this->id
		];

		return $this->request($this->url, compact('json'), 'POST');
	}
}