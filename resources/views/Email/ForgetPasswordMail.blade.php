@component('mail::message')
# Hi,{{ $mailData['name'] }}

We got a request for forget password. Please click on this button to create new password
@component('mail::button', ['url' => route('forgetPasswordChange',['token' => $mailData['api_token']])])
Change Password
@endcomponent

Thanks,<br>
{{ config('app.name') }}
@endcomponent
