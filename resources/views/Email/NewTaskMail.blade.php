@component('mail::message')
# Hi,{{ $mailData['name'] }}

A new task {{ $mailData['task']['title']}} is assigned to you. Please click on this button to accept the task assignment request;
@component('mail::button', ['url' => 'http://localhost:3000/tasks/'.$mailData['task']['id']])
Accept Invitation
@endcomponent

Thanks,<br>
{{ config('app.name') }}
@endcomponent
