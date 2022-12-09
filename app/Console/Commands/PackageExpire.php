<?php

namespace App\Console\Commands;

use Mail;
use DateTime;
use App\Mail\SendUserMail;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Session;


class PackageExpire extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'package:expire';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check which user package is expire ';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $userData = DB::table('orders')->select('orders.user_id','clients.first_name','clients.last_name','clients.email','orders.activation_date_time','orders.expiry_date_time')
                        ->join('clients', 'orders.user_id', '=', 'clients.client_reference_id')
                        ->where('price_sum', 'paid')
                        ->where('clients.client_type', 'Main Client')
                        ->get();
        
            if(count($userData)>0){
                foreach($userData as $data){
                    $client_reference_id   = $data->user_id;
                    $email                 = $data->email;
                    $user                  = $data->first_name.' '.$data->last_name;
                    $activation_date_time  = $data->activation_date_time;
                    $expiry_date_time      = date('Y-m-d', strtotime($data->expiry_date_time));
                    
                    $startDate = date("Y-m-d");
                    $endDate   = date('Y-m-d', strtotime($startDate. ' + 3 days'));
                    if (($expiry_date_time >= $startDate) && ($expiry_date_time <= $endDate)){
                        $link_ref = 'https://fna2.phpapplord.co.za/public/clientslink/';
                        $mailData = [
                                    'title' => 'Your package will expire very soon.Please renew it.',
                                    'name' => $user,
                                    'link' =>$link_ref
                                ];
                        $emailSent = Mail::to($email)->send(new SendUserMail($mailData));
                    }
                }
            }



    }
}
