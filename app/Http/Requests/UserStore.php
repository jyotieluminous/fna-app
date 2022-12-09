<?php

namespace App\Http\Requests;

use App\Rules\IdNumber;
use Illuminate\Foundation\Http\FormRequest;

class UserStore extends FormRequest
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
            'id_number' => ['required', new IdNumber],
            'name' => 'required',
            'surname' => 'required',
            'email' => ['required', 'email'],
            'dob' => ['required'],
            'gender' => ['required'],
            'phone' => ['required'],
        ];
    }
}
