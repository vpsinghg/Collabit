@component('mail::message')
# Hi,{{ $mailData['name'] }}

Welcome to {{config('app.name')}}. Please click on following button and verify your email
@component('mail::button', ['url' => 'http://localhost:3000/auth/email_verify/'.$mailData['email_verification_token']])
Verify Your email
@endcomponent

Thanks,<br>
{{ config('app.name') }}
@endcomponent