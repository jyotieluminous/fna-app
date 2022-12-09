<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

class BankStatementImport implements ToCollection
{

    /**
    * @param Collection $collection
    */
    public function collection($collection)
    {
        

        $first_key = $collection->keys()->first();
        $collections = $collection->forget($first_key)->values();
        
        
        $data = $collections->map(function($collection) {
            return [
                'full_title' => $collection[0],
                'transaction_date' => $collection[1],
                'post_date' => $collection[2],
                'amount' => $collection[3],
                'service_fee' => $collection[4],
                'running_balance' => $collection[5],
                'transaction_type_id' => $collection[6],
                'currency' => $collection[7],
                'order' => $collection[8],
                'id' => $collection[9],
                'account_id' => $collection[10],
                'title' => $collection[11]
            ];
        });

        return $data;
    }
}
