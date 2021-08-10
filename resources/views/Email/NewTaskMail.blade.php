@component('mail::message')
# Hi,{{ $mailData['name'] }}

@if ($mailData['type']==='taskupdated')
    A  task {{ $mailData['task']['title']}} details has been updated.
    Title : {{ $mailData['task']['title']}}
    Description : {{$mailData['task']['description']}}
    Due Date : {{   $mailData['task']['dueDate']}}

@else
    A new task titled {{ $mailData['task']['title']}} is assigned to you. 
    Please click on this button to accept the task assignment request;
@endif

@component('mail::button', ['url' => 'http://localhost:3000/profile/tasks/'])
    Task  Page
@endcomponent


Thanks,<br>
{{ config('app.name') }}
@endcomponent
