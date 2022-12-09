<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Rules\IdNumber;


class ClientStore extends FormRequest
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
            'main_first_name' => 'required|min:1',
            'main_last_name' =>  'required|min:1',
            // 'main_retirement_age' => 'required',
            'main_idNo' => 'required|unique:users,idNumber', //['required|unique:users', new IdNumber],
            // 'data.ip' => 'required|unique:servers,ip,'.$this>id.'|unique:servers,hostname,'.$this->id
            // 'main_phone' => ['required', 'regex:/^((\+\d{1,3}(-| )?\(?\d\)?(-| )?\d{1,5})|(\(?\d{2,6}\)?))(-| )?(\d{3,4})(-| )?(\d{4})(( x| ext)\d{1,5}){0,1}$/'],
            'email' => 'required|email|unique:clients',
            // 'spouse_first_name' => 'required|min:1',
            // 'spouse_last_name' =>  'required|min:1',
            // 'spouse_retirement_age' => 'required',
            // 'spouse_idNo' => ['required', new IdNumber],
            // 'spouse_phone' => ['required', 'regex:/^((\+\d{1,3}(-| )?\(?\d\)?(-| )?\d{1,5})|(\(?\d{2,6}\)?))(-| )?(\d{3,4})(-| )?(\d{4})(( x| ext)\d{1,5}){0,1}$/'],
            // 'spouse_email' => 'required|email|unique:clients',
        ];
    }
}
