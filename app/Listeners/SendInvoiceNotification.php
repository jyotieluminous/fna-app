<?php

namespace App\Listeners;

use App\Invoice;
use App\Mail\Invoice as InvoiceMail;
use App\Events\InvoiceProcessed;
use Illuminate\Support\Facades\Mail;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendInvoiceNotification
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  InvoiceProcessed  $event
     * @return void
     */
    public function handle(InvoiceProcessed $event)
    {
        $address = 'mokonyamabmg@gmail.com';
        Mail::to($address)->queue(
            new InvoiceMail($event)
        );
    }
}
