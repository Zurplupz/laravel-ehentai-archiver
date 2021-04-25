<?php

namespace App\Exceptions;

use Exception;

class InsufficientCreditsException extends Exception
{
	protected $cost;
	protected $current;

   	function __construct(
   		int $current, int $cost, int $code, \Throwable $previous = null
   	) {
   		$this->cost = $cost;
   		$this->current = $current;

   		switch ($code) {
   			case 1:
   				$message = 'Not enough credits to download gallery';
   				break;

   			case 2:
   				$message = 'Credit dangerously low';
   				break;

   			case 3:
   				$message = 'Gallery costs 80% of current balance';
   				break;
   			
   			default:
   				$message='';
   				break;
   		}
    
        // make sure everything is assigned properly
        parent::__construct($message, $code, $previous);
    }

    public function getCurrentBalance()
    {
    	return $this->current;
    }

    public function getTransactionCost()
    {
    	return $this->cost;
    }
}
