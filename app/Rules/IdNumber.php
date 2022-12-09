<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class IdNumber implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        $validated = false;

        if (is_numeric($value) && strlen($value) === 13) {

            $num_array = str_split($value);

            // Validate the day and month

            $id_month = $num_array[2] . $num_array[3];

            $id_day = $num_array[4] . $num_array[5];


            if ( $id_month < 1 || $id_month > 12) {
                return $validated = false;
            }

            if ( $id_day < 1 || $id_day > 31) {
                return $validated = false;
            }

           
            if($num_array[10] > 1)
            {
                return $validated = false;
            }
           
            if($num_array[11] < 8)
            {
                return $validated = false;
            }
            return $validated = true;
        }else{
            
            return $validated = false;
        }

        
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'The invalid :attribute';
    }
}
