<?php

namespace App\Http\ApiClients;

use App\Http\ApiClients\Client;
use GuzzleHttp\Cookie\CookieJar;

/**
 * 
 */
class ExhentaiClient extends Client
{
	protected $api_url = 'https://exhentai.org/api.php';
	protected $archives_url = 'https://exhentai.org/archiver.php';
	
	function __construct()
	{
		$this->result_type = 'json';

		$this->cookies = CookieJar::fromArray([
		    'ipb_member_id' => env('EXHENTAI_MEMBER_ID'),
		    'ipb_pass_hash' => env('EXHENTAI_PASS_HASH'),
		], '.exhentai.org');

		$this->defineGuzzle([]);
	}

	public function getGalleriesMetadata(array $gid_token_pairs)
	{
		$json = [
			'method' => 'gdata',
			'gidlist' => $gid_token_pairs,
			'namespace' => 1
		];

		return $this->request($this->api_url, compact('json'), 'POST');
	}

	public function requestArchive(array $query)
	{
		$this->result_type = 'text';

		$response = $this->request($this->archives_url, compact('query'));
		
		$this->result_type = 'json';

		return $response;
	}
}