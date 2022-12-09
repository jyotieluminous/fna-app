<?php

namespace App\Http\Requests;

use App\Rules\IdNumber;
use Illuminate\Foundation\Http\FormRequest;

class StoreClient extends FormRequest
{
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
            'title' => 'required',
            'first_name' => 'required|min:1',
            'last_name' =>  'required|min:1',
            'street' => 'required',
            'city_town' => 'required',
            'code' => 'required',
            'id_number' => ['required', new IdNumber],
            'contact_number' => ['required', 'regex:/^((\+\d{1,3}(-| )?\(?\d\)?(-| )?\d{1,5})|(\(?\d{2,6}\)?))(-| )?(\d{3,4})(-| )?(\d{4})(( x| ext)\d{1,5}){0,1}$/'],
            'email_address' => 'required|email'
        ];
    }
}
