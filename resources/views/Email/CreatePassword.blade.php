@component('mail::message')
# Hi,{{ $mailData['name'] }}

Welcome to {{config('app.name')}}. Please click on following button and verify your email
@component('mail::button', ['url' => route('CreatePassword',['token' => $mailData['token']])])
Create Password
@endcomponent

Thanks,<br>
{{ config('app.name') }}
@endcomponent