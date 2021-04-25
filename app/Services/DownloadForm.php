<?php

namespace App\Services;
use App\Services\EhCrawler;


/**
 * 
 */
class DownloadForm extends EhCrawler
{
	function __construct(array $params)
	{
		parent::__construct();

		$r = $this->exhentai->requestArchiveForm($params);

		if (empty($r)) {
			throw new \Exception(
				$r->lastError() ?: __METHOD_ . ': unknown request error', $r->status ?? 1
			);			
		}

		$this->crawl($r); 
	}

	public function isResampledButtonDisabled()
	{
        return $this->dom
            ->filterXpath('//html/body/div/div[1]/div[2]/form/div/input')
            ->attr('disabled') ?? false;
	}

	public function isOriginalButtonDisabled()
	{
        return $this->dom
            ->filterXpath('//html/body/div/div[1]/div[1]/form/div/input')
            ->attr('disabled') ?? false;
	}

	public function resampledArchiveCost() :int
	{
        $str = $this->dom
            ->filterXpath('//html/body/div/div[1]/div[2]/div/strong')
            ->text('');
	
        return $str ? (int) preg_replace('/[^\d]/', '', $str) : 0;
	}

	public function originalArchiveCost() :int
	{
        $str = $this->dom
            ->filterXpath('//html/body/div/div[1]/div[1]/div/strong')
            ->text('');
	
        return $str ? (int) preg_replace('/[^\d]/', '', $str) : 0;
	}
}