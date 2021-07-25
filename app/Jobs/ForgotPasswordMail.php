<?php

namespace App\Jobs;

use App\Jobs\Job;
use Illuminate\Support\Facades\Mail;


use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

// import ForgetMail
use App\Mail\ForgetPasswordMail;


class ForgotPasswordMail extends Job implements ShouldQueue
{
    use SerializesModels;

    protected $mailData;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($mailData)
    {
        //
        $this->mailData    =   $mailData;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        //
        Mail::to($this->mailData['email'])->send(new ForgetPasswordMail($this->mailData));

    }
}
