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


class PackageStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'package:status';

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
        $end_date_time         = date("Y-m-d H:i");//'2022-10-15 06:45';//
        $packageData = DB::table('orders')->select('orders.user_id','clients.first_name','clients.last_name','clients.email','orders.id')
                ->join('clients', 'orders.user_id', '=', 'clients.client_reference_id')
                ->where('price_sum', 'paid')
                ->where('expiry_date_time', $end_date_time)
                ->where('clients.client_type', 'Main Client')
                ->get();

        if(count($packageData)>0){
                foreach($packageData as $data){
                    $orders_id   = $data->id;
                    $updatePassword = DB::table('orders')->where('id', $orders_id)->update(['price_sum' =>'expired']);
                        $user   = $data->first_name.' '.$data->last_name;
                        $email  = $data->email;

                        $mailData = [
                                    'title' => 'Your package is expired.',
                                    'name' => $user
                                ];
                        $emailSent = Mail::to($email)->send(new SendUserMail($mailData));
                    
                }
            }

    }
}
