<?php

namespace App\Mail;

use App\Invoice as InvoiceObject;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class Invoice extends Mailable
{
    use Queueable, SerializesModels;

    public $invoice;
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(InvoiceObject $invoice)
    {
        $this->invoice = $invoice;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
         $subject = 'eColls Client Invoice';
        return $this->subject($subject)
        ->attach(storage_path('app/public/invoice/invoice#'.$this->invoice->getInvoiceId().'.pdf'))
        ->view('mail.invoice');
    }
}
