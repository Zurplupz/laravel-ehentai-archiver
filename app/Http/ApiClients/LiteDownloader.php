<?php

namespace App\Http\ApiClients;

/**
 * 
 */
class LiteDownloader
{
	protected $options;
	
	function __construct(array $options=[])
	{
		$this->options = [
			//CURLOPT_FILE => is_resource($dest) ? $dest : fopen($dest, 'w'),
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_MAXREDIRS => 5,
			CURLOPT_FAILONERROR => false, // HTTP code > 400 will throw curl error
			CURLOPT_CONNECTTIMEOUT => 30,
			CURLOPT_TIMEOUT => 0,
			CURLOPT_HTTPHEADER => [
				'Accept: application/zip, application/octet-stream, application/x-zip-compressed'
			],
			CURLOPT_WRITEFUNCTION => function ($resource, $data) {
				return fwrite($resource, $data);
			}
		];

		if ($options) {
			$this->options = array_merge($this->options, $options);
		}
	}

	// todo: set max file size
	// todo: check file is zip
	public function download(string $url, string $dest) :bool
	{
		$resource = is_resource($dest) ? $dest : fopen($dest, 'w');

		$this->options[CURLOPT_FILE] = $resource;
		$this->options[CURLOPT_URL] = $url;

		$curl = curl_init();

		curl_setopt_array($curl, $this->options);
		
		curl_exec($curl);
		
		fclose($resource);

		if (curl_errno($curl)) { 
			$error = ' Curl error: ' . curl_error($curl);			
			\Log::error($error, ['curlopt'=>$this->options]);
			return false;
		}

		$code = curl_getinfo($curl, CURLINFO_HTTP_CODE);

		if ($code >= 400) {
			$error = "HTTP Error: " . $code;
			\Log::error($error, ['curl'=>$curl,'curlopt'=>$this->options]);
			return false;
		}

		curl_close($curl);

		return true;
	}
}