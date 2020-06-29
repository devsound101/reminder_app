<?php

namespace App\Http\Controllers;

use App\Reply;
use Illuminate\Http\Request;
use Twilio\Rest\Client;

class AlertController extends Controller
{
    //
    public function checkAlert($alert, $userRemind)
    {
        $diffInMinute = $this->diffInMinute($userRemind);
        if ($diffInMinute == $alert["minutes_shift"]) {
            if ($alert["level"] != 1) {
                $replied = $this->checkPreviousLevel($alert, $userRemind);
                if (!$replied) {
                    $this->sendAlert($alert, $userRemind);
                }
            } else {
                $this->sendAlert($alert, $userRemind);
            }
        }
    }

    public function checkPreviousLevel($alert, $userRemind)
    {
        // TODO:: $from, $to
        $from = "";
        $to = "";
        $todayWorkTime = $this->getTodayWorkingTime($userRemind);
        $time_in_24_hour_format  = date("H:i:s", strtotime($todayWorkTime));
        $prev_level_time = date('Y-m-d') . ' ' . $time_in_24_hour_format;
        $prev_level_time_tz = new \DateTime($prev_level_time);
        $prev_level_time_tz->setTimezone(new \DateTimeZone('PST'));
        $prev_level_time_tz = $prev_level_time_tz->format('Y-m-d H:i:s');
        foreach ($userRemind['content'] as $row) {
            if ($row['level'] == ($alert['level'] - 1)) {
                $from = date('Y-m-d H:i:s', strtotime('-' . $row['minutes_shift'] . ' minutes', strtotime($prev_level_time_tz)));
                $to = date('Y-m-d H:i:s', strtotime('+' . $row['wait_minutes'] . ' minutes', strtotime($from)));
                break;
            }
        }

        if ($from == '' || $to == '') {
            return false;
        }

        $from = date('Y-m-d H:i:s', strtotime('2020-05-01 08:30 AM'));
        $to = date('2018-05-02');
        $result = Reply::whereBetween('created_at', [$from, $to])->where("phone_number", $alert["phone_number"])->count();

        return $result && $result > 0;
    }

    private function sendAlert($alert, $userRemind)
    {
        if ($alert["type"] === "SMS") {
            $message = $alert["message"];
            $message = str_replace("{name}", $userRemind["name"], $message);
            $this->sendSMS($userRemind["phone_number"], $message);
        } else if ($alert["type"] === "Voice") {
            $this->sendVoiceMail($userRemind["phone_number"]);
        }
        return true;
    }

    private function sendVoiceMail($to)
    {
        // send voice mail
        $sid = getenv('TWILIO_ACCOUNT_SID');
        $token = getenv('TWILIO_AUTH_TOKEN');
        $twilio_number = getenv('TWILIO_PHONE_NUMBER');
        $client = new Client($sid, $token);
        $client->account->calls->create(
            $to,
            $twilio_number,
            array(
                "url" => "http://demo.twilio.com/docs/voice.xml"
            )
        );
    }

    function sendSMS($to, $sms_body)
    {
        // Your Account SID and Auth Token from twilio.com/console
        $sid = getenv('TWILIO_ACCOUNT_SID');
        $token = getenv('TWILIO_AUTH_TOKEN');
        $client = new Client($sid, $token);
        $client->messages->create(
            $to,
            [
                // A Twilio phone number you purchased at twilio.com/console
                'from' => getenv('TWILIO_PHONE_NUMBER'),
                // the body of the text message you'd like to send
                'body' => $sms_body
            ]
        );
    }

    private function diffInMinute($userRemind)
    {
        $today = $this->getTodayWorkingTime($userRemind);
        return now($userRemind['timezone'])->diffInMinutes($today);
    }

    private function getTodayWorkingTime($userRemind){
        $today = now($userRemind['timezone'])->format("l");

        return $userRemind["reminder_time"][$today];
    }
}
