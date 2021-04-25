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
	protected $exchange_url = 'https://e-hentai.org/exchange.php?t=gp';
	
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

	/* direct access to download link */
	public function requestArchive(array $query, string $resolution='resampled')
	{
		$this->result_type = 'text';

		// todo: allow to choose default resolution
		$valid = [
			'resampled' => 'res',
			'original' => 'org'
		];

		$data = [
			'query' => $query,
			'form_params' => [
				'dltype' => 'org',
				'dlcheck' => $valid[$resolution] ?? 'res',
			]
		];

		$response = $this->request($this->archives_url, $data, 'POST');
		
		$this->result_type = 'json';

		return $response;
	}

	public function requestArchiveForm(array $query)
	{
		$this->result_type = 'text';

		$response = $this->request($this->archives_url, compact('query'), 'POST');
		
		$this->result_type = 'json';

		return $response;
	}

	/* deprecated */
	public function downloadGallery(string $url, string $save_path)
	{
		$this->result_type = 'text';

		return $this->request($url, ['sink' => $save_path]);
	}

	public function exchangePage()
	{
		$this->result_type = 'text';		

		$this->cookies = CookieJar::fromArray([
		    'ipb_member_id' => env('EXHENTAI_MEMBER_ID'),
		    'ipb_pass_hash' => env('EXHENTAI_PASS_HASH'),
		], '.e-hentai.org');

		return $this->request($this->exchange_url);
	}
}