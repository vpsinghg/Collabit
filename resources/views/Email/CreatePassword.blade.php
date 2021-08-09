@component('mail::message')
# Hi,{{ $mailData['name'] }}

Your account has been created. Please click on this button to create new password
@component('mail::button', ['url' => 'http://localhost:3000/createpassword/'.$mailData['token']])
create Password
@endcomponent

Thanks,<br>
{{ config('app.name') }}
@endcomponent
