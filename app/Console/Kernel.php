<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Laravel\Lumen\Console\Kernel as ConsoleKernel;
use App\Jobs\DailyTaskreminderMailJob;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Http\Request;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        //
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->call(function(){
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
        })->daily()
        ->between('8:00', '9:30')
        ->timezone('Asia/Kolkata');

    }
}
