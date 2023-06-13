<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Auth;
use Hash;
use Session;

class UserAuthController extends Controller
{

    public function register(Request $request){
        return view('auth.register');
    }

    public function login(Request $request){
        return view('auth.login');
    }

    public function registerUser(Request $request){
        $validated = $request->validate([
            'name'=> 'string|required|max:255',
            'email'=> 'email|required|unique:users,email|max:255',
            'password'=> 'string|required|confirmed',
        ]);

        $user = User::create([
            'name'=> $validated['name'],
            'email'=> $validated['email'],
            'password'=> Hash::make($validated['password']),
        ]);            


        Auth::login($user);

        return redirect('/');
    }

    public function loginUser(Request $request){
        $validated = $request->validate([
            'email'=> 'email|required|max:255',
            'password'=> 'string|required'
        ]);

        if(Auth::attempt($validated)){
            $request->session()->regenerate();

            return redirect()->intended('/');
        }

        return back()->withErrors(['password'=>'Invalid login credentials'])
            ->onlyInput('password');
    }

    public function logoutUser(Request $request){
        Session::flush();

        Auth::logout();

        return redirect()->route('auth-login');
    }
}
