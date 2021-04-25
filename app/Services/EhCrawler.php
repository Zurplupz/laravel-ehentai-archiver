<?php

namespace App\Services;

use App\Http\ApiClients\ExhentaiClient;
use Symfony\Component\DomCrawler\Crawler;


/**
 * 
 */
abstract class EhCrawler
{
	protected $exhentai;
	protected $dom;

	function __construct()
	{
		$this->exhentai = new ExhentaiClient;
	}

	protected function crawl(string $dom)
	{
		$this->dom = new Crawler($dom);
	}
}