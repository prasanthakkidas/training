<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use  App\Models\User;
use  App\Models\Task;

class TaskController extends Controller {

    /**
     * exists validation checks if users database table contains a record 
     * with a id column value matching the request's id attribute value then
     * creates task
     * 
     * @param $request
     * @return response
     */
    public function createTask(Request $request) {
        $this->validate($request, [
            'id' => 'exists:users',  
            'title' => 'required|string|max:40',
            'description' => 'required|string|max:60000',
        ]);
        
        if($request->auth->is_admin !== 'Admin') {
            return response()->json(
                ['message' => 'Forbidden'], 403
            );
        }

        $task = new Task;
        
        $id = $request->input('id');
        $user = User::find($id);
        $task->user_id = $id;
        $task->assignee = $user->name;
        $task->title = $request->input('title');
        $task->description = $request->input('description');
        $task->status = 'assigned';
        $task->due_date = $request->input('due_date');            
        $task->save();

        return response()->json(
            ['message' => 'New task created'], 201
        );
    }

    public function allTasks(Request $request) {
        $user = $request->auth;

        if($user->is_admin !== "Admin") { 
            return response()->json(
                ['message' => 'Forbidden'], 403
            );
        }
        
        $tasks = Task::all();
        return response()->json(
            ['tasks' => $tasks], 200
        );
    }

     /**
      * checks if there is task with request input id and
      * checks if user is admin then soft deletes corresponding user
      * @param $request, $id
      * @return response
      */
    public function deleteTask(Request $request) {
        $this->validate($request, [
            'id' => 'exists:tasks',  
        ]);

        $user = $request->auth;
        if($user->is_admin !== 'Admin') {
            return response()->json(
                ['message' => 'Forbidden'], 403
            );
        }

        $id = $request->get('id');
        Task::destroy($id);

        return response()->json(
            ['message' => 'Deleted successfully'], 200
        );
    }

    /**
     * 
     * 
     * 
     */
    public function updateTask(Request $request) {
        $this->validate($request, [
            'id' => 'exists:tasks',
            'title' => 'required|string|max:40',
            'description' => 'required|string|max:60000',
        ]);

        $user = $request->auth;

        if($user->is_admin !== 'Admin') {
            return response()->json(
                ['message' => 'Forbidden'], 403
            );
        }

        $task = Task::find($request->input('id'));

        $task->title = $request->input('title');
        $task->description = $request->input('description');
        $task->due_date = $request->input('due_date');

        $task->save();

        return response()->json(
            ['message' => 'Task updated'], 200
        );
    }

    public function updateTaskStatus(Request $request) {
        $this->validate($request, [
            'id' => 'exists:tasks',
            'status' => 'required|string',
        ]);

        if($request->input('status') !== "in-progress" &&  $request->input('status') !== "completed") {
            return response()->json([
                'message' => "Bad Request", 422
            ]);
        }

        $status = $request->input('status');
        $id = $request->input('id');

        $user = $request->auth;
        $task = Task::where('user_id', $user->id)->where('id', $id)->first();
        if($status === 'completed') {
            $task->completed_at = date('Y-m-d');
        }

        $task->status = $status;
        $task->save();

        return response()->json(
            ['message' => 'Status updated'], 200
        );
    }

    public function userTasks(Request $request) {
        $user = $request->auth;
        $id = $user->id;

        $tasks = Task::where('user_id', $id)->get();

        $stats = array();

        $stats['total_tasks'] = Task::where('user_id', $id)->count();

        $current_date = date('Y-m-d');  //get current date

        $stats['in_progress'] = Task::where('user_id', $id)
                                        ->where('status', 'in-progress')
                                        ->where('due_date', '>=', $current_date)
                                        ->count();

        $stats['over_due'] = Task::where('user_id', $id)
                                    ->where('status', 'in-progress')
                                    ->where('due_date', '<', $current_date)
                                    ->count();

        $stats['no_activity'] = Task::where('user_id', $id)
                                    ->where('status', 'assigned')
                                    ->count();

        $stats['on_time'] = Task::where('user_id', $id)
                                    ->where('status', 'completed')
                                    ->whereColumn('due_date', '>=', 'completed_at')
                                    ->count();   

        $stats['after_deadline'] = Task::where('user_id', $id)
                                    ->where('status', 'completed')
                                    ->whereColumn('due_date', '<', 'completed_at')
                                    ->count();

        
        return response()->json([
            'tasks' => $tasks,
            'stats' => $stats
        ], 200);  
    }

    public function todayTasks(Request $request) {
        $user = $request->auth;

        $today_tasks = Task::where('user_id', $user->id)
                        ->where('status', '!=', 'completed')
                        ->where('due_date', '<=', date('Y-m-d'))
                        ->get();  

        return response()->json([
            'today_tasks' => $today_tasks,
        ], 200);  
    }
}