@component('mail::message')
# Hi,{{ $mailData['name'] }}

<!-- $tasks  =  $mailData['tasksdata'] -->
<h2>Following Tasks are assigned to You and these are due Today </h2>
@foreach ($mailData['tasksdata'] as $task)
    <li><a style="text-decoration: none;" href='http://localhost:3000/profile/tasks'>Task titled "{{ $task->title }}"  is having deadline  {{$task->dueDate }}  and current status is {{$task->status}}</a></li>
@endforeach

Thanks,<br>
{{ config('app.name') }}
@endcomponent
