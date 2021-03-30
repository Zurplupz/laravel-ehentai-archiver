<?php

namespace App\Http\ApiClients;

use App\Http\ApiClients\Client;

/**
 * 
 */
class ExhentaiClient extends Client
{
	
	function __construct()
	{
		$this->result_type = 'json';

		$this->defineGuzzle([
			'base_uri' => 'https://api.e-hentai.org/api.php/'
		]);
	}

	public function getGalleriesMetadata(array $gid_token_pairs)
	{
		$json = [
			'method' => 'gdata',
			'gidlist' => $gid_token_pairs,
			'namespace' => 1
		];

		return $this->request('', compact('json'), 'POST');
	}
}