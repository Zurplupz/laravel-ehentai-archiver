<?php

namespace App\Services;

use App\credit_log;
use App\Http\ApiClients\ExhentaiClient;
use Symfony\Component\DomCrawler\Crawler;
use App\Exceptions\InsufficientCreditsException;

/**
 * 
 */
class CreditLogging
{
	protected $amount;
	
	function __construct()
	{
		$l = credit_log::latest()->first();

        $this->amount = !empty($l->amount) ? $l->amount : 0;
        
        $exhentai = new ExhentaiClient;
            
        $page = $exhentai->exchangePage();        
        
        if (!empty($page)) {
			$crawler = new Crawler($page);

	        $str = $crawler->filterXpath('//html/body/div[2]/div[2]/div[1]/div[2]')->text('');

	        if (!empty($str)) {
	            $amount = (int) preg_replace('/[^\d]+/', '', $x);

	            $e = 'Log update';

	            switch (true) {
	            	case $amount && $amount > $this->amount:
	            		$this->logIncome($amount - $this->amount, $e);
	            		break;

	            	case $amount && $this->amount > $amount:
	            		$this->logExpense($this->amount - $amount, $e);
	            		break;
	            	
	            	default: break;
	            }
	        }
        }
	}

	public function getCurrentBalance()
	{
		return $this->amount;
	}

	protected function logExpense(int $cost, string $event='')
	{
		$log = new credit_log;

		$this->amount -= $cost;

		$log->amount = $this->amount;
		$log->difference = $cost * -1;
		$log->event = $event;

		$log->save();
	}

	protected function logIncome(int $income, string $event='')
	{
		$log = new credit_log;

		$this->amount += $income;

		$log->amount = $this->amount;
		$log->difference = $income;
		$log->event = $event;

		$log->save();
	}

	public function validateTransacion(int $cost) :bool
	{
        if ($cost > $this->amount) {
        	throw new InsufficientCreditsException($this->amount, $cost, 1);
        }

        if ( $cost >= ($this->amount / 100 * 80) ) {
        	throw new InsufficientCreditsException($this->amount, $cost, 2);        	
        }

        if ($this->amount <= 200) {
        	throw new InsufficientCreditsException($this->amount, $cost, 3);        	
        }

        return true;
	}

	public function galleryDownload(int $cost) :bool
	{
		$this->validateDownload($cost);

		$this->logExpense($cost);

		return true;
	}
}