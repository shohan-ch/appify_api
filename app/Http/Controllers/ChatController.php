<?php

namespace App\Http\Controllers;

use App\Models\Thread;
use App\Models\Message;
use Illuminate\Http\Request;
use App\Events\NotificationEvent;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Validator;

class ChatController extends Controller
{

    public function sendMessage(Request $request, $id)
    {

        $authUserId =  JWTAuth::user()->id;

        $validator =  Validator::make($request->all(), [
            "message" => "required",
        ], [
            'message.required' => 'Please write a message!',
        ]);

        if ($validator->fails()) {
            $error =  $validator->errors();
            return response()->json($error, 400);
        }

        $thread = Thread::where(['sender_id' => $authUserId, 'receiver_id' => $id])
            ->first();

        if (is_null($thread)) {
            $thread = Thread::where(['sender_id' => $id, 'receiver_id' => $authUserId])
                ->first();
        }

        if (is_null($thread)) {
            $thread =  Thread::create([
                "sender_id" => $authUserId,
                "receiver_id" => $id

            ]);
        }
        $message = Message::create(
            [
                "user_id" => $authUserId,
                "thread_id" => $thread->id,
                "message"  => $request->message
            ]
        );

        event(new NotificationEvent($message->message, $id));

        return response()->json($message, 200);
    }

    public function getMessage($id)
    {
        // return $id;


        $authUserId =  JWTAuth::user()->id;
        $thread = Thread::where(["sender_id" => $authUserId, "receiver_id" => $id])
            ->orWhere(["sender_id" => $id, "receiver_id" => $authUserId])->first();

        if (empty($thread)) {
            return response()->json(["message" => "You have no chat history!"], 400);
        }

        $messages = Message::where("thread_id", $thread->id)->get();

        return response()->json($messages, 200);
    }
}