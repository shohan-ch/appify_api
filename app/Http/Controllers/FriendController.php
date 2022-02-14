<?php

namespace App\Http\Controllers;

use App\Models\Friend;
use App\Models\User;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;

class FriendController extends Controller
{

    public function getFriendList(Request $request)
    {

        $authUser =  JWTAuth::user()->id;
        $friends  = Friend::with("friendList:id,name")
            ->where(["user_id" => $authUser, "status" => "friend"])->get();
        return response()->json(["user" => $friends], 200);

    }


    public function searchFriend(Request $request)
    {
        $authUser =  JWTAuth::user()->id;
        $user     = User::where(function ($query) use ($request) {
            $query->where("email", $request->search)
                ->orWhere("name", "like", "%" . $request->search . "%");
               
        })->where("id", "!=", $authUser)->where("verified", 1)->get();

        if (empty($request->search)) {
            return response()->json(["message" => "Please write somethings to search"], 400);
        }
        if ($user->isEmpty()) {
            return response()->json(["message" => "No user found"], 400);
        }
        return response()->json(["user" => $user], 200);
    }




    public function getFriendRequestList()
    {

        $authUser =  JWTAuth::user()->id;
        $friendRequestList = Friend::with("user:id,name,email")
            ->where(["friend_id" => $authUser, "status" => "pending"])
            ->get();
        if ($friendRequestList->isEmpty()) {
            return response()->json(["message" => "You have no friend request!"]);
        }
        return response()->json(["result" => $friendRequestList], 200);
    }




    public function acceptRequest(Request $request, $friendId)
    {

        $authUser =  JWTAuth::user()->id;

        //Check Friend
        $checkFrnd =  Friend::where([
            "user_id" => $authUser,
            "friend_id" => $friendId,
            "status" => "friend"
        ])->first();
        if (isset($checkFrnd)) {
            return response()->json(["message" => "You are already friend"], 400);
        }

        // Insert record as status is friend if another user/ friend accept request
        $friend = Friend::create([
            "user_id"   => $authUser,
            "friend_id" => $friendId,
            "status"    => "friend"
        ]);

        $statusChangeAnotherUser = $this->statusChangeForUser($authUser, $friendId);

        if ($statusChangeAnotherUser) {

            return response()->json(["message" => "You are now friend with " . $friend->friendList->name], 200);
        } else {
            return response()->json(["error" => "Something went wrong!"], 400);
        }
    }



    public function statusChangeForUser($userId, $friendId)
    {

        $friend = Friend::where(["user_id" => $friendId, "friend_id" => $userId, "status" => "pending"])->first();
        $friend->status = "friend";
        return $friend->save();
    }

    public function sendRequest(Request $request, $id = null)
    {

        $authUser = JWTAuth::user()->id;
        $checkFrnd = Friend::where(["user_id" => $authUser, "friend_id" => $id])->where(function ($query) {

            $query->where("status", "friend")->orWhere("status", "pending");
        })->first();


        if (!empty($checkFrnd) && !empty($checkFrnd->status == "friend")) {
            return response()->json(["message" => "You are already friend"], 400);
        }

        if (!empty($checkFrnd) && !empty($checkFrnd->status == "pending")) {
            return response()->json(["message" => "You have sent friend request already. Please Wait for resonse!"], 400);
        }
        if (empty($checkFrnd)) {
            $friend =  Friend::create([
                "user_id" => $authUser,
                "friend_id" => $id,
                "status"  => "pending"
            ]);

            return response()->json(["message" => "Friend request sent!"]);
        }
    }
}