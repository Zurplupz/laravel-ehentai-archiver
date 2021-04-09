<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GalleryRequest extends FormRequest
{
    use JsonErrorList;

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'gid' => ['numeric','min:4'],
            'token' => ['string','min:4'],
            'category' => ['string','min:3'],
            'rating' => ['string', 'min:3']
        ];
    }
}
