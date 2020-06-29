<?php

namespace App\Http\Controllers;

use App\Reply;
use Illuminate\Http\Request;

class ReminderController extends Controller
{
    //
    private $UserController;

    public function __construct()
    {
        $this->UserController = new UserController;
    }

    public function index()
    {
        $reminders = $this->_readUsers();
        foreach ($reminders as $reminder) {
            $this->UserController->checkRemind($reminder);
        }
        return response()->json($reminders);
    }

    private function _readUsers()
    {
        return json_decode(file_get_contents(public_path('./reminder.json')), true);
    }

    private function getResponse(Request $request)
    {
        $message = $request->message;
        //TODO get timezone
        $time_val = date('Y-m-d H:i:s', strtotime(now('PST')));
        $reply = Reply::create([
            "phone_number" => $message["From"],
            "message_body" => $message["Body"],
            "date_received" => "asdfasdf",
            "created_at" => $time_val,
            "updated_at" => $time_val
        ]);
        return $reply;
    }
}
