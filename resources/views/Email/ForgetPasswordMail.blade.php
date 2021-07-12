@component('mail::message')
# Hi,{{ $mailData['name'] }}

We got a request for forget password. Please click on this button to create new password
@component('mail::button', ['url' => 'http://localhost:8000'.'/api/auth/forget_password_update/'.$mailData['api_token']])
Change Password
@endcomponent

Thanks,<br>
{{ config('app.name') }}
@endcomponent