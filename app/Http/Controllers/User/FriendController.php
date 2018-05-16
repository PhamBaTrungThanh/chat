<?php

namespace App\Http\Controllers\User;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\User;
class FriendController extends Controller
{
    public function __construct()
    {
        $this->middleware("auth");
    }
    public function submit(Request $request) 
    {
        $request->validate([
            "friend_id" => "required|integer",
            "action" => "required",
        ]);
        $friend = User::find($request->input('friend_id'));
        $user = auth()->user();
        switch ($request->input('action')) {
            case 'ADD':
                if ($user->hasSentFriendRequestTo($friend)) {
                    session()->flash("alert", __("friend.requestalredysent", ["username" => $friend->name]));
                    session()->flash("alert_type", "is-warning");
                } else {
                    $user->befriend($friend);
                    session()->flash("alert", __("friend.requestsent", ["username" => $friend->name]));
                }
                break;
            case 'ACCEPT':
                if ($user->isFriendWith($friend)) {
                    session()->flash("alert", __("friend.alreadyfriend", ["username" => $friend->name]));
                    session()->flash("alert_type", "is-warning");
                } else {
                    $user->acceptFriendRequest($friend);
                    session()->flash("alert", __("friend.befriend", ["username" => $friend->name]));
                }
                break;
            case 'CANCEL':
                if ($user->hasSentFriendRequestTo($friend)) {

                    $user->cancelFriendRequest($friend);
                    session()->flash("alert", __("friend.requestcanceled", ["username" => $friend->name]));
                }
                break;
            case 'DENY':
                    $user->denyFriendRequest($friend);
                    session()->flash("alert", __("friend.requestdenied", ["username" => $friend->name]));
                break;
            case 'BLOCK':
                $user->blockFriend($friend);
                session()->flash("alert", __("friend.requestblocked", ["username" => $friend->name]));
                break;  
            case 'UNBLOCK':
                $user->unblockFriend($friend);
                session()->flash("alert", __("friend.requestunblocked", ["username" => $friend->name]));
                break;                                       
            default:
                # code...
                break;
        }
        return back();

    }
    public function index(Request $request)
    {
        $list = auth()->user()->getAllFriendships();
        $userId = auth()->user()->id;
        $queries = [];
        list($pendingFriends, $friends, $rejectFriends, $blockedFriends) = array([],[],[],[]);
        foreach ($list as $friendship) {
            $queries[] = ($userId === $friendship->sender_id) ? $friendship->recipient_id : $friendship->sender_id;
        }
        $users = User::whereIn('id', $queries)->get()->keyBy('id');
        foreach ($list as $friendship) {
            $actor = ($userId === $friendship->sender_id) ? $friendship->recipient_id : $friendship->sender_id;
            $friend = $users[$actor];
            switch ($friendship->status) {
                case 0:
                    # PENDING
                    $friend->status = "PENDING";
                    $pendingFriends[] = $friend;
                    break;
                case 1:
                    # ACCEPTED 
                    $friend->status = "ACCEPTED";
                    $friends[] = $friend;
                    break;
                case 2:
                    # DENIED
                    $friend->status = "DENIED";
                    $rejectFriends[] = $friend;
                    break;
                case 3:
                    # BLOCKED
                    $friend->status = "BLOCKED";
                    $blockedFriends[] = $friend;
                    break;

                default:
                    # code...
                    break;
            }
        }
        
        return view('friend.index')->with([
            "pending" => $pendingFriends,
            "friends" => $friends,
            "rejects" => $rejectFriends,
        ]);
    }
    public function accept(Request $request, User $friend)
    {

        return back()->withInput();
    }

}