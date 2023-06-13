<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Auth;


class RoomController extends Controller
{
    public function room(Request $request){
        $user = Auth::user();
        $users = User::all()->except($user->id);
        return view('welcome',compact('user','users'));
    }

    public function test(Request $request){
        return view('test');
    }
}
