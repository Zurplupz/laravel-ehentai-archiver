<?php

namespace App\Http\Requests;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * 	
 */
trait JsonErrorList
{
	protected function failedValidation(Validator $validator) {
        $errors = $validator->errors()->all();

        throw new HttpResponseException(response()->json(compact('errors'), 400));
    }
}