<?php
  
namespace App\Mail;
  
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
  
class SendUserRegisterMail extends Mailable
{
    use Queueable, SerializesModels;
  
    public $mailData;
  
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($mailData)
    {
        $this->mailData = $mailData;
    }
  
    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $subject = $this->mailData['title'];
        return $this->subject($subject)->view('clients.userRegisterMail',[
            'name'=>$this->mailData['name'],
            'link'=>$this->mailData['link'],
            'username'=>$this->mailData['username'],
            'password'=>$this->mailData['password']
            ]);
    }
}