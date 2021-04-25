<?php

namespace App\Services;
use App\Services\EhCrawler;


/**
 * 
 */
class DownloadPage extends EhCrawler
{
	function __construct(array $params, string $mode='resampled')
	{
		parent::__construct();

		$r = $this->exhentai->requestArchive($params, $mode);

		if (empty($r)) {
			throw new \Exception(
				$r->lastError() ?: __METHOD_ . ': unknown request error', $r->status ?? 1
			);			
		}
		
		$this->crawl($r); 
	}

	public function getFileUrl() :string
	{
        $script = $this->dom->filter('script[type]')->text('');
        
        if (empty($script)) {
            return '';
        }

        $find = preg_match('/"(?<url>https:\/\/[^"]+)"?/iu', $script, $match);

        if (empty($match)) {
        	return '';
        }

        return $match['url'] . '?start=1';
	}
}