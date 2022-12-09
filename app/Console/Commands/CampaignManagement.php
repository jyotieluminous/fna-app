<?php

namespace App\Console\Commands;

use DateTime;
use App\Invoice;
use App\Panaceaapi;
use Dompdf\Dompdf;
use App\InvoiceReminder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;


class CampaignManagement extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'campaign:notice';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Respectively send notices to clients for invoices not paid';

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
        $api = new PanaceaApi();
        $api->setUsername("ecolls");
        $api->setPassword("3nkX3!!7"); 
        $result = $api->message_send('27733504163','it works!!!!!!!', "27111236846");
        
        // $days = 0;

        // $invoice_reminders = InvoiceReminder::where('payment_status','=', 'Not Paid')->get();

        // $filterd_invoice_reminders = $invoice_reminders->map(function ($invoice_reminder) {


        //     if($invoice_reminder->payment_status == 'Not Paid')
        //     {   
        //         $reminder['invoice_id'] = $invoice_reminder->invoice_id;
        //         $reminder['reminder_status'] = $invoice_reminder->reminder_status;
        //         $reminder['is_first_template_sent'] = $invoice_reminder->is_first_template_sent;
        //         $reminder['is_first_mailreminder_enabled'] = $invoice_reminder->is_first_mailreminder_enabled;
        //         $reminder['is_second_template_sent'] = $invoice_reminder->is_second_template_sent;
        //         $reminder['is_second_mailreminder_enabled'] = $invoice_reminder->is_second_mailreminder_enabled;
        //         $reminder['is_handover_template_sent'] = $invoice_reminder->is_handover_template_sent;
        //         $reminder['is_mailhandover_enabled'] = $invoice_reminder->is_mailhandover_enabled;
        //         $reminder['is_letter_of_demand_sent'] = $invoice_reminder->is_letter_of_demand_sent;
        //         $reminder['is_letter_of_demand_enabled'] = $invoice_reminder->is_letter_of_demand_enabled;
        //         $reminder['is_final_notice_sent'] = $invoice_reminder->is_final_notice_sent;
        //         $reminder['is_final_notice_enabled'] = $invoice_reminder->is_final_notice_enabled;
        //         $reminder['due_date'] = $invoice_reminder->due_date;
        //         $reminder['days_past'] = $this->date_diff($invoice_reminder->due_date);
        //         $reminder['is_first_smsreminder_enabled'] = $invoice_reminder->is_first_smsreminder_enabled;
        //         $reminder['is_second_smsreminder_enabled'] = $invoice_reminder->is_second_smsreminder_enabled;
        //         $reminder['is_smshandover_enabled'] = $invoice_reminder->is_smshandover_enabled;
        //         $reminder['is_SMS_letter_of_demand_enabled'] = $invoice_reminder->is_SMS_letter_of_demand_enabled;
        //         $reminder['is_first_SMS_template_sent'] = $invoice_reminder->is_first_SMS_template_sent;
        //         $reminder['is_second_SMS_template_sent'] = $invoice_reminder->is_second_SMS_template_sent;
        //         $reminder['is_handover_SMS_template_sent'] = $invoice_reminder->is_handover_SMS_template_sent;

          

        //         return $reminder;
        //     }
        // });

        

        // if(count($filterd_invoice_reminders) > 0)
        // {
            
        //     $filterd_invoice_reminders->each(function($filterd_invoice_reminder) use($days) {
        //         $days = $filterd_invoice_reminder['days_past'];
              
        //         if($days > 7 && $days < 14){
                   
        //             $mail_config = $this->getEmailConfiguration($filterd_invoice_reminder['invoice_id']);
        //             $sms_config = $this->getSMSConfiguration($filterd_invoice_reminder['invoice_id']);
                    
        //                 if(($filterd_invoice_reminder['is_first_template_sent'] == null) || ($filterd_invoice_reminder['is_first_template_sent'] == 0))
        //                 {
                            
        //                     if(!$filterd_invoice_reminder['is_first_mailreminder_enabled'])
        //                     {
        //                         return false;
        //                     }

        //                     if($mail_config)
        //                     {
                               
        //                         if($mail_config->is_1st_reminder_enabled)
        //                         {
        //                              $invoice_reminder = InvoiceReminder::where('invoice_id', '=' ,$filterd_invoice_reminder['invoice_id'])->first();
                            
        //                             $invoice_reminder->reminder_status = '1st_reminder';
        //                             $invoice_reminder->is_first_template_sent = 1;
        //                             $invoice_reminder->save();
                                    

        //                             $invoice = Invoice::find($invoice_reminder->invoice_id);

        //                             $identifier = "qCuW58gfWB4pSmax";

        //                             $this->sendInvoice($invoice, $identifier);
        //                         }
        //                     }
                           
        //                 }

        //                 if(($filterd_invoice_reminder['is_first_SMS_template_sent'] == null) || ($filterd_invoice_reminder['is_first_SMS_template_sent'] == 0))
        //                 {
        //                     if(!$filterd_invoice_reminder['is_first_smsreminder_enabled'])
        //                     {
        //                         return false;
        //                     }

        //                     if($sms_config)
        //                     {
        //                         if($sms_config->is_1st_reminder_enabled)
        //                         {
        //                             $invoice_reminder = InvoiceReminder::where('invoice_id', '=' ,$filterd_invoice_reminder['invoice_id'])->first();
                        
        //                             $invoice_reminder->is_first_SMS_template_sent = 1;
        //                             $invoice_reminder->save();
                                    
            
        //                             $invoice = Invoice::find($invoice_reminder->invoice_id);
            
        //                             $message =  "Dear ".$invoice->client->title. " ".$invoice->client->last_name. ", This is a friendly reminder that R ". $invoice->calculateBalanceDue(). " will be deducted from your bank account for your " .$invoice->user->company->company_name. " account. Invoice Ref: " .$invoice->getInvoiceId();
            
        //                             $this->sendSMS($invoice, $message);
        //                         }
        //                     }
        //                 }
        //         }

        //         if($days >= 14 && $days < 21)
        //         {
                    
        //             $mail_config = $this->getEmailConfiguration($filterd_invoice_reminder['invoice_id']);
        //             $sms_config = $this->getSMSConfiguration($filterd_invoice_reminder['invoice_id']);
                    
                   
        //             if(($filterd_invoice_reminder['is_second_template_sent'] == null) || ($filterd_invoice_reminder['is_second_template_sent'] == 0))
        //             {
                       
        //                     if(!$filterd_invoice_reminder['is_second_mailreminder_enabled'])
        //                     {
                               
        //                         return false;
        //                     }

        //                     if($mail_config)
        //                     {
                                
        //                         if($mail_config->is_2nd_reminder_enabled)
        //                         {
                                    
        //                             $invoice_reminder = InvoiceReminder::where('invoice_id', '=' ,$filterd_invoice_reminder['invoice_id'])->first();
                                    
        //                             $invoice_reminder->reminder_status = '2nd_reminder';
        //                             $invoice_reminder->is_second_template_sent = 1;
        //                             $invoice_reminder->save();
                                    

        //                             $invoice = Invoice::find($invoice_reminder->invoice_id);

        //                             $identifier = "IMkOL9m6GQ7X2k5S";

        //                             $this->sendInvoice($invoice, $identifier);
        //                         }
        //                     }

        //             }

                    
        //             if(($filterd_invoice_reminder['is_second_SMS_template_sent'] == null) || ($filterd_invoice_reminder['is_second_SMS_template_sent'] == 0))
        //             {
                       
        //                 if(!$filterd_invoice_reminder['is_second_smsreminder_enabled'])
        //                 {
        //                     return false;
        //                 }

        //                 if($sms_config)
        //                 {
                            
        //                     $invoice_reminder = InvoiceReminder::where('invoice_id', '=' ,$filterd_invoice_reminder['invoice_id'])->first();
                                    
        //                     $invoice_reminder->is_second_SMS_template_sent = 1;
        //                     $invoice_reminder->save();
                            

        //                     $invoice = Invoice::find($invoice_reminder->invoice_id);

        //                     $message = "Dear ". $invoice->client->title ." ". $invoice->client->last_name. " Please call us on ". $invoice->user->company->company_telephone. " to arrange for settlement of the outstanding R ".$invoice->calculateBalanceDue(). " on your ".$invoice->user->company->company_name. " Ref: ".$invoice->getInvoiceId();

        //                     $this->sendSMS($invoice, $message);
        //                 }
        //             }
                    
        //         }

        //         if($days >= 28 && $days < 35) 
        //         {
        //             $mail_config = $this->getEmailConfiguration($filterd_invoice_reminder['invoice_id']);
        //             $sms_config = $this->getSMSConfiguration($filterd_invoice_reminder['invoice_id']);


        //             if(($filterd_invoice_reminder['is_handover_template_sent'] == null) || ($filterd_invoice_reminder['is_handover_template_sent'] == 0))
        //             {
                        
        //                 if(!$filterd_invoice_reminder['is_mailhandover_enabled'])
        //                 {
        //                     return false;
        //                 }

        //                 if($mail_config)
        //                 {
        //                     if($mail_config->is_handover_enabled)
        //                     {
        //                          $invoice_reminder = InvoiceReminder::where('invoice_id', '=' ,$filterd_invoice_reminder['invoice_id'])->first();
                        
        //                         $invoice_reminder->reminder_status = 'handover';
        //                         $invoice_reminder->is_handover_template_sent = 1;
        //                         $invoice_reminder->save();
                                

        //                         $invoice = Invoice::find($invoice_reminder->invoice_id);

        //                         $identifier = "prD6EAoxC9TVnBIo";

        //                         $this->sendInvoice($invoice, $identifier);
        //                     }
        //                 }
                       
        //             }

        //             if(($filterd_invoice_reminder['is_handover_SMS_template_sent'] == null) || ($filterd_invoice_reminder['is_handover_SMS_template_sent'] == 0))
        //             {
        //                 if(!$filterd_invoice_reminder['is_smshandover_enabled'])
        //                 {
        //                     return false;
        //                 }

        //                 if($sms_config)
        //                 {
        //                     $invoice_reminder = InvoiceReminder::where('invoice_id', '=' ,$filterd_invoice_reminder['invoice_id'])->first();
                                    
        //                     $invoice_reminder->is_handover_SMS_template_sent = 1;
        //                     $invoice_reminder->save();
                            

        //                     $invoice = Invoice::find($invoice_reminder->invoice_id);

        //                     $message = "Dear ". $invoice->client->title. " ".$invoice->client->last_name. " Please call us on ".$invoice->user->company->company_telephone. " re your New Instruction account to avoid further action. ". $invoice->user->company->company_name. " Ref: ".$invoice->getInvoiceId();

        //                     $this->sendSMS($invoice, $message);
        //                 }
        //             }
                    
        //         }

        //         if($days >= 35 && $days < 42)
        //         {
                  
        //             $mail_config = $this->getEmailConfiguration($filterd_invoice_reminder['invoice_id']);
        //             $sms_config = $this->getSMSConfiguration($filterd_invoice_reminder['invoice_id']);


        //             if(($filterd_invoice_reminder['is_handover_template_sent'] == null) || ($filterd_invoice_reminder['is_handover_template_sent'] == 0))
        //             {
                        
        //                 if(!$filterd_invoice_reminder['is_mailhandover_enabled'])
        //                 {
        //                     return false;
        //                 }

        //                 if($mail_config)
        //                 {
        //                     if($mail_config->is_handover_enabled)
        //                     {
        //                          $invoice_reminder = InvoiceReminder::where('invoice_id', '=' ,$filterd_invoice_reminder['invoice_id'])->first();
                        
        //                         $invoice_reminder->reminder_status = 'handover';
        //                         $invoice_reminder->is_handover_template_sent = 1;
        //                         $invoice_reminder->save();
                                

        //                         $invoice = Invoice::find($invoice_reminder->invoice_id);

        //                         $identifier = "prD6EAoxC9TVnBIo";

        //                         $this->sendInvoice($invoice, $identifier);
        //                     }
        //                 }
                       
        //             }

        //             if(($filterd_invoice_reminder['is_handover_SMS_template_sent'] == null) || ($filterd_invoice_reminder['is_handover_SMS_template_sent'] == 0))
        //             {
        //                 if(!$filterd_invoice_reminder['is_smshandover_enabled'])
        //                 {
        //                     return false;
        //                 }

        //                 if($sms_config)
        //                 {
        //                     $invoice_reminder = InvoiceReminder::where('invoice_id', '=' ,$filterd_invoice_reminder['invoice_id'])->first();
                                    
        //                     $invoice_reminder->is_handover_SMS_template_sent = 1;
        //                     $invoice_reminder->save();
                            

        //                     $invoice = Invoice::find($invoice_reminder->invoice_id);

        //                     $message = "Dear ". $invoice->client->title. " ".$invoice->client->last_name. " Please call us on ".$invoice->user->company->company_telephone. " re your New Instruction account to avoid further action. ". $invoice->user->company->company_name. " Ref: ".$invoice->getInvoiceId();

        //                     $this->sendSMS($invoice, $message);
        //                 }
        //             }
        //         }

        //         if($days >= 42 && $days < 49)
        //         {
                   
        //             $mail_config = $this->getEmailConfiguration($filterd_invoice_reminder['invoice_id']);
        //             $sms_config = $this->getSMSConfiguration($filterd_invoice_reminder['invoice_id']);


        //             if(($filterd_invoice_reminder['is_letter_of_demand_sent'] == null) || ($filterd_invoice_reminder['is_letter_of_demand_sent'] == 0))
        //             {
                        
        //                 if(!$filterd_invoice_reminder['is_final_notice_enabled'])
        //                 {
        //                     return false;
        //                 }

        //                 if($mail_config)
        //                 {
        //                     if($mail_config->is_letter_of_demand_enabled)
        //                     {
        //                          $invoice_reminder = InvoiceReminder::where('invoice_id', '=' ,$filterd_invoice_reminder['invoice_id'])->first();
                        
        //                         $invoice_reminder->reminder_status = 'letter of demand';
        //                         $invoice_reminder->is_letter_of_demand_sent = 1;
        //                         $invoice_reminder->save();
                                

        //                         $invoice = Invoice::find($invoice_reminder->invoice_id);

        //                         $identifier = "9Ts3ar26KB9DVa4n";

        //                         $this->sendInvoice($invoice, $identifier);
        //                     }
        //                 }
                       
        //             }

        //         }

        //         if($days >= 49 && $days < 57)
        //         {
                   
        //             $mail_config = $this->getEmailConfiguration($filterd_invoice_reminder['invoice_id']);
        //             $sms_config = $this->getSMSConfiguration($filterd_invoice_reminder['invoice_id']);


        //             if(($filterd_invoice_reminder['is_final_notice_sent'] == null) || ($filterd_invoice_reminder['is_final_notice_sent'] == 0))
        //             {
                        
        //                 if(!$filterd_invoice_reminder['is_final_notice_enabled'])
        //                 {
        //                     return false;
        //                 }

        //                 if($mail_config)
        //                 {
        //                     if($mail_config->is_final_notice_enabled)
        //                     {
        //                          $invoice_reminder = InvoiceReminder::where('invoice_id', '=' ,$filterd_invoice_reminder['invoice_id'])->first();
                        
        //                         $invoice_reminder->reminder_status = 'Final Notice';
        //                         $invoice_reminder->is_final_notice_sent = 1;
        //                         $invoice_reminder->save();
                                

        //                         $invoice = Invoice::find($invoice_reminder->invoice_id);

        //                         $identifier = "J0nbtfL30ast6X3C";

        //                         $this->sendInvoice($invoice, $identifier);
        //                     }
        //                 }
                       
        //             }

        //         }
        //     });
        // }
    }

public function getEmailConfiguration($invoice_id)
    {
        $invoice = Invoice::find($invoice_id);

        $user = $invoice->user;
        $company = $user->company;
        $email_configuration = $company->emailCampaignConfiguration;

        return $email_configuration;
    }

    public function getSMSConfiguration($invoice_id)
    {
        $invoice = Invoice::find($invoice_id);

        $user = $invoice->user;
        $company = $user->company;
        $sms_configuration = $company->smsCampaignConfiguration;

        return $sms_configuration;
    }

    public function date_diff($date)
    {
        $current_day = new DateTime(date('Y-m-d H:i:s'));

        $due = new DateTime($date);

        $interval = $current_day->diff($due);

        return $interval->invert != 0 ? $interval->format('%a') : 0;
    }

    public function sendInvoice($invoice, $identifier)
    {   
        $dompdf = new Dompdf(array('enable_remote' => true));
        $dompdf->loadHtml(view('pdf.invoice', ['invoice' => $invoice]));

        $dompdf->setPaper('A4');

        $dompdf->render();

        file_put_contents(public_path().'/invoice/invoice#'.$invoice->getInvoiceId().'.pdf',$dompdf->output());

        $pdf = public_path().'/invoice/invoice#'.$invoice->getInvoiceId().'.pdf';
    
        $pdfString = file_get_contents($pdf);
        $data = ';base64,' . base64_encode($pdfString);
        $pdf64 = explode(",", $data);

      

        // $curl = curl_init();
        
        // curl_setopt_array($curl, array(
        // CURLOPT_URL => 'https://ecolls.rocketseed.net/api/2.0/trans_mails/template',
        // CURLOPT_RETURNTRANSFER => true,
        // CURLOPT_ENCODING => '',
        // CURLOPT_MAXREDIRS => 10,
        // CURLOPT_TIMEOUT => 0,
        // CURLOPT_FOLLOWLOCATION => true,
        // CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        // CURLOPT_CUSTOMREQUEST => 'POST',
        // CURLOPT_POSTFIELDS =>'{
        // "identifier": "'.$identifier.'",
        // "headers": {
        // "from": {
        // "info@ecolls.co.za": "EColls"
        // },
        // "trans-group-name": "multiple sends Group "
        // },
        // "emails": {
        // "'.$invoice->client->email_address.'": "'.$invoice->client->first_name .' '.$invoice->client->last_name .'"
        // }
        // ,
        // "settings": [],
        // "unique_tags": {
        // "'.$invoice->client->email_address.'": {
        // "business_name": "'.$invoice->user->company->company_name.'",
        // "due_date": "'.$invoice->due_date.'",
        // "person_name": "'.$invoice->client->first_name.' '.$invoice->client->last_name .'"
        // }
        // },
        // "attachments": [{
        // "filename": "invoice#'.$invoice->getInvoiceId().'.pdf",
        // "data": "'.$pdf64[1].'"
        // }]
        // }',
        // CURLOPT_HTTPHEADER => array(
        // 'Authorization: Basic dG9ueUBhcHBsb3JkLmNvLnphOjJ5OEJxM1VhV1ZMR3ZrVDNHSm1lVTVNa3UyZHFZYjdxXzE0',
        // 'Content-Type: application/json'
        // ),
        // ));
        
        // $response = curl_exec($curl);
        // curl_close($curl);
        
        
    }

    public function sendSMS($invoice, $message)
    {

        $phone = ltrim($invoice->client->contact_number, "0");
        $phone = "27".$phone;  

        $api = new PanaceaApi();
        $api->setUsername("ecolls");
        $api->setPassword("3nkX3!!7"); 
        $result = $api->message_send($phone,$message, "27111236846");
    }
}
