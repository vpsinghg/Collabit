<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Models\Task;
use App\Models\User;
use DateTime;
//use DB
use Illuminate\Support\Facades\DB;
// New Task Mail Job
use App\Jobs\NewTaskMailJob;
use Pusher;
use App\Events\TaskAssignedNotificationEvent;
use App\Models\TaskNotificationModel;
use App\Jobs\DailyTaskreminderMailJob;

class TaskController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    //

    public function createTask(Request $request){
        $loggedInUser   =   Auth::user();
        $this->validate($request, [
            'title' => 'required',
            'description' => 'required',   
            'assignee'  =>   'required',
            'dueDate'    =>  'required',
        ]);
        $task   =   new Task;
        $task->title    =   $request['title'];
        $task->description  =   $request['description'];
        $task->assignee =(int)$request['assignee'];
        $date  =  new DateTime($request['dueDate']);
        $task->dueDate  =   $date->format('Y-m-d H:i:s');
        $creator    =   $loggedInUser;
        $creator->tasks()->save($task);


        // Notification 
        $notification   =   new TaskNotificationModel;
        $notification->message  =   'A  Task titled '.$task->title.' status has been assigned to you';
        $notification->id = $task->assignee;
        $notification->channel = 'taskassignedchannel';
        $notification->event = 'taskassignedevent';
        event(new TaskAssignedNotificationEvent($notification));

        // mail job dispatch
        $mailData['email']  =   User::find($request['assignee'])->email;
        $mailData['name']   =   User::find($request['assignee'])->name;
        $mailData['task']   =   $task;
        $job =new NewTaskMailJob($mailData);
        dispatch($job);


        return  response(['message'=>"Task is created successfully",'data'=>$mailData['task']],201);    
    }

    public function deleteTask(Request $request,$task_id){
        $task_id =(int)($task_id);
        $loggedInUser   =   Auth::user();
        $toDeleteTask = Task::find($task_id);
        if(! $toDeleteTask){
            $res['message'] =   "Task is unavaible for deletion , Its already deleted";
            return response()->json($res,404);
        }
        if($toDeleteTask->user_id ===   $loggedInUser->id){
            $toDeleteTask->status ='deleted';
            $toDeleteTask->save();
            $toDeleteTask->delete();
            $res['message'] =   "Task is deleted successfully";
            return response()->json($res,200);
        }
        else{
            $res['message']   =   "You are not authorized to delete this task";
            return response()->json($res,403);
        }
    }

    public function updateTask(Request $request){
        $loggedInUser   =   Auth::user();
        $this->validate($request, [
            'task_id'   =>  'required|exists:tasks,id',
            'title' => 'required',
            'description' => 'required',   
            'dueDate'    =>  'required',
        ]);

        $task_id    =   $request['task_id'];
        $task = Task::find($task_id);
        if(! $task){
            $res['message'] =   "Task is unavailable for updates";
            return response()->json($res,404);
        }
        elseif($task->user_id   === $loggedInUser->id){
            $task->title    =   $request['title'];
            $task->description  =   $request['description'];
            $date  =  new DateTime($request['dueDate']);
            $task->dueDate  =   $date->format('Y-m-d H:i:s');
            $task->update();
            $res['message'] =   "Task is updated successfully";
            return response()->json($res,200);

        }
        else{
            $res['message']   =   "You are not authorized to update this task";
            return response()->json($res,403);
        }

    }

    public function taskStatusUpdate(Request $request){
        $this->validate($request,   [
            'task_id'   =>  'required|exists:tasks,id',
            'status'    =>   'required',
        ]);
        $task_id    =   $request['task_id'];
        $task   =   Task::find($task_id);
        $loggedInUser   =   Auth::user();

        if(! $task){
            $res['message'] =   "Task is unavailable for updates";
            return response()->json($res,404);
        }
        elseif($loggedInUser->id    === (int)$task->assignee){
            $current_status =   $task->status;
            $status =   $request['status'];
            $task->status   =   $status;
            $task->update();
            $res['message'] =   "Task  status is updated successfully";
            $notification   =   new TaskNotificationModel;
            $notification->message  =   'A  Task titled '.$task->title.' status has been changed from '.$current_status.' to '.$task->status;
            $notification->id = $task->user_id;
            $notification->channel = 'taskstatusupdatechannel';
            $notification->event = 'taskstatusupdate';
            event(new TaskAssignedNotificationEvent($notification));
            return response()->json($res,200);
        }
        else{
            $res['message']   =   "You are not authorized to update this task status";
            return response()->json($res,403);
        }
    }

    public function taskFilter(Request $request ,   $type,  $id){
        $loggedInUser   =   Auth::user();
        $keyword   =   $request['keyword'];
        $assignee   =   $request['assignee'];
        $assignor  =   $request['assignor'];
        $startTime  =  $request['startTime'];
        $endTime  =   $request['endTime'];
        $tasks =    DB::table('tasks')->where('tasks.deleted_at', NULL);
        if($type ==='todo'){
            $tasks = $tasks->where('assignee',$id);
        }
        elseif($type ==='assgined'){
            $tasks   =   User::find($id)->tasks()->orderBy('dueDate');
        }
        elseif($type   ==='all' && Auth::user()->role ==='admin'){
            $tasks   =   Task::orderBy('dueDate');
        }
        
        if ($request->has('keyword')) {
            $tasks->where(function ($q) use ($request) {
                $q->where('title', 'LIKE', '%' . $request->get('keyword') . '%')
                    ->orWhere('description', 'LIKE', '%' . $request->get('keyword') . '%');
            });
        }
        $tasks=$tasks->when($assignee, function($query,    $assignee){
                if($assignee ==="all"){
                    return $query;
                }
                return $query->where('assignee', $assignee);
            })->when($assignor, function($query,    $assignor){
                if($assignor ==="all"){
                    return $query;
                }
                return $query->where('user_id', $assignor);
            })->when($startTime, function($query,   $startTime){
                $startDatetime   =   new Datetime($startTime);
                $startDatetime  =   $startDatetime->format('Y-m-d H:i:s');
                return $query->where('dueDate','>', $startDatetime);
            })->when($endTime,  function($query,    $endTime){
                $endDatetime   =   new Datetime($endTime);
                $endDatetime  =   $endDatetime->format('Y-m-d H:i:s');
                return $query->where('dueDate','<=', $endDatetime);
            })
            ->join('users as u1','tasks.user_id','=','u1.id')
            ->join('users as u2','tasks.assignee','=','u2.id')
            ->orderBy('dueDate')
            ->select(array('tasks.id','tasks.title','tasks.status','tasks.description','tasks.assignee','tasks.user_id','dueDate','tasks.created_at', 'u1.name as creatorname', 'u2.name as assigneename'));

        
        $res['tasks']    =   $tasks->get();
        return response()->json($res,200);

    }
    
    public function getAssginedTasksToday(){
        $today = Carbon::today('Asia/Kolkata');
        $tomorrow = Carbon::tomorrow('Asia/Kolkata');
        $loggedInUser   =   Auth::user();
        $tasks  =   Task::where('assignee',$loggedInUser->id)
            ->whereBetween('dueDate',[$today,$tomorrow])
            ->join('users as u1','tasks.user_id','=','u1.id')
            ->join('users as u2','tasks.assignee','=','u2.id')
            ->where('tasks.deleted_at',NULL)
            ->orderBy('dueDate')
            ->select(array('tasks.id','tasks.title','tasks.status','tasks.description','tasks.assignee','tasks.user_id','dueDate','tasks.created_at', 'u1.name as creatorname', 'u2.name as assigneename'));
        $res['tasks']    =  $tasks->get();
        return  response()->json($res,200);
    }

    public function getTaskDataBarGraph(Request $request,$tasktype){
        $id =   $request['id'];
        $timeInterval   =   $request['timeInterval'];
        if($tasktype    === "assignedtome"){
            $tasks  =   Task::where('assignee',$id)
                ->when($timeInterval,function($query,$timeInterval){
                    $query=$query->whereDate( 'created_at', '>', Carbon::now('Asia/Kolkata')->subDays(30*$timeInterval));
                })
                ->get(["status",'dueDate','updated_at',\DB::raw("DATE_FORMAT(created_at, '%d-%m-%Y') new_date")])
                ->groupby('new_date');
        }
        elseif($tasktype    ==="createddata"){
            $assignee   =   $request['assignee'];
            $tasks  =   User::find($id)->tasks()
                ->when($assignee,function($query,$assignee){
                    if($assignee==="all"){
                        return $query;
                    }
                    else{
                        $query= $query->where('assignee',$assignee);
                        return $query;
                    }
                })
                ->get(["status",'dueDate','updated_at',\DB::raw("DATE_FORMAT(created_at, '%d-%m-%Y') new_date")])
                ->groupby('new_date');
    
        }

        $data=$this->BarGraphDataFormat($tasks);
        $res['data']   =   $data;
        return  response()->json($res,200);
    }

    public function BarGraphDataFormat($tasks){
        $field_name =array('Date','Completed on Time','Completed after Deadline','OverDue','In Progress','No Activity');
        $data=[];
        array_push($data,$field_name);
        $graphdatalist=$tasks->map(function($taskdata, $key){
            $stack = array($key);
            $assigned_count =   0;
            $inprogressed_count =   0;
            $completedontime_count =   0;
            $completedAfterDeadlineCount =0;
            $overdue_count =   0;
            $currenttime =   Carbon::now('Asia/Kolkata');
            foreach($taskdata as $value){
                if($value['status']==='assigned'){
                    $assigned_count +=   1;
                }
                elseif($value['status']==='in-progress'){
                    $inprogressed_count +=  1;
                }
                elseif($value['status']==='completed'){
                    if($value['dueDate'] >= $value['updated_at']){
                        $completedontime_count    +=  1;
                    }
                    else{
                        $completedAfterDeadlineCount +=1;
                    }
                }
                if($value['dueDate']<$currenttime && $value['status']!=='completed'){
                    $overdue_count  +=  1;
                }
            }
            array_push($stack,$completedontime_count,$completedAfterDeadlineCount,$overdue_count,$inprogressed_count,$assigned_count);
            return $stack;
        });
        foreach ($graphdatalist as $itemKey => $itemValue) {
            array_push($data,$itemValue);
        }
        return $data;
    }

    public function getAssginedTasks(){
        $loggedInUser   =   Auth::user();
        $tasks  =   Task::where('assignee',$loggedInUser->id)
        ->join('users as u1','tasks.user_id','=','u1.id')
        ->join('users as u2','tasks.assignee','=','u2.id')
        ->where('tasks.deleted_at',NULL)
        ->orderBy('dueDate')
        ->select(array('tasks.id','tasks.title','tasks.status','tasks.description','tasks.assignee','tasks.user_id','dueDate','tasks.created_at', 'u1.name as creatorname', 'u2.name as assigneename'));
        $res['tasks']    =  $tasks->get();
        return  response()->json($res,200);
    }
    public function getCreatedTasks(){
        $loggedInUser   =   Auth::user();
        $tasks  =   User::find($loggedInUser->id)->tasks()
        ->join('users as u1','tasks.user_id','=','u1.id')
        ->join('users as u2','tasks.assignee','=','u2.id')
        ->where('tasks.deleted_at',NULL)
        ->orderBy('dueDate')
        ->select(array('tasks.id','tasks.title','tasks.status','tasks.description','tasks.assignee','tasks.user_id','dueDate','tasks.created_at', 'u1.name as creatorname', 'u2.name as assigneename'));
        $res['tasks']    =   $tasks->get();
        return response()->json($res,200);
    }

    public function getAllTasks(){
        $loggedInUser   =   Auth::user();
        $user   =   User::find($loggedInUser->id);
        if($user->role ==='admin'){
            $tasks  =   Task::join('users as u1','tasks.user_id','=','u1.id')
            ->join('users as u2','tasks.assignee','=','u2.id')
            ->where('tasks.deleted_at',NULL)
            ->orderBy('dueDate')
            ->select(array('tasks.id','tasks.title','tasks.status','tasks.description','tasks.assignee','tasks.user_id','dueDate','tasks.created_at', 'u1.name as creatorname', 'u2.name as assigneename'));
            $res['tasks']    =   $tasks->get();
            return response()->json($res,200);
        }
        else{
            return response()->json(['message'  =>  "You are not authorized for this action"],401);
        }
    }


    public function taskPerformanceData(Request $request,$tasktype){
        $id =  $request['profile_id'];
        $currenttime =   Carbon::now('Asia/Kolkata');
        $data   =   [];
        $task_count =   0;
        if($tasktype    === "mytaskdata"){
            $task_count =   Task::where('assignee'  ,$id)->count();
            $data   =   [
                'Completed on Time' =>  Task::where('assignee'  ,   $id)->where('status',   'completed')->whereRaw('updated_at <= dueDate')->count(),
                'Completed after Deadline' =>  Task::where('assignee'  ,   $id)->where('status',   'completed')->whereRaw('updated_at > dueDate')->count(),
                'OverDue'   =>  Task::where('assignee'  ,   $id)->where('dueDate',  '<' ,   $currenttime)->where('status','!=','completed')->count(),
                'In Progress'    =>  Task::where('assignee'  ,   $id)->where('status','in-progress')->count(),
                'No Activity'  =>  Task::where('assignee'  ,   $id)->where('status','assigned')->count(),
            ];    
        }
        elseif($tasktype    === "createddata"){
            $task_count =   User::find($id)->tasks()->count();
            $data   =   [
                'Completed on Time' =>  User::find($id)->tasks()->where('status',   'completed')->whereRaw('updated_at <= dueDate')->count(),
                'Completed after Deadline' =>  User::find($id)->tasks()->where('status',   'completed')->whereRaw('updated_at > dueDate')->count(),
                'OverDue'   =>  User::find($id)->tasks()->where('dueDate',  '<' ,   $currenttime)->where('status','!=','completed')->count(),
                'In Progress'    =>  User::find($id)->tasks()->where('status','in-progress')->count(),
                'No Activity'  =>  User::find($id)->tasks()->where('status','assigned')->count(),
            ];    
        }
        elseif($tasktype    === "alltaskdata"){
            if(Auth::user()->role ==='admin'){
                $task_count =   Task::count();
                $data    =   [
                    'Completed on Time' =>  Task::where('status',   'completed')->whereRaw('updated_at <= dueDate')->count(),
                    'Completed after Deadline' =>  Task::where('status',   'completed')->whereRaw('updated_at > dueDate')->count(),
                    'OverDue'   =>  Task::where('dueDate',  '<' ,   $currenttime)->where('status','!=','completed')->count(),
                    'In Progress'    =>  Task::where('status','in-progress')->count(),
                    'No Activity'  =>  Task::where('status','assigned')->count(),
                ];
            }
            else{
                return response()->json(['message' =>   "You are not authorized to access this"],403);
            }
            
        }
        return response()->json(['task_count'=> $task_count,'data' =>   $data],200);
    }


    // get assigneeslist for a given id 
    public function assigneeList(Request $request){
        $id =   $request['id'];
        $data   =   User::find($id)->tasks()        
            ->join('users as assignees','tasks.assignee','=','assignees.id')
            ->where('tasks.deleted_at',NULL)
            ->where('assignees.deleted_at',NULL)
            ->select(['assignees.id','assignees.name','assignees.email'])
            ->distinct()
            ->get();
        
        
        return response()->json(['data'=>$data],200);
    }


    public function dailyTasksMail(){
        $today = Carbon::today('Asia/Kolkata');
        $tomorrow = Carbon::tomorrow('Asia/Kolkata');    
        $users=DB::table('users')->select('id')->get();
        $users=$users->map(function($taskdata, $key){
            foreach($taskdata as $value){
                return $value;
            };
        });
        $res =[];
        foreach($users as $id  => $user_id){
            $data   =   DB::table('tasks')->where('assignee',$user_id)
            ->whereBetween('dueDate',[$today,$tomorrow])
            ->select(array('tasks.id','tasks.title','tasks.status','dueDate'))
            ->get();
            $user =DB::table('users')->find($user_id);
            $usermaildata['email']   =  $user->email;
            $usermaildata['name']   =   $user->name;
            $usermaildata['tasksdata']  =   $data;
            $res[$user_id]  =$usermaildata;

            if($data->count() >0   ){
                dispatch(New DailyTaskreminderMailJob($usermaildata));
            }
        }

        return response()->json($res);

    }
}

