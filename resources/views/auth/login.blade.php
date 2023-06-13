@extends('auth.layout')

@section('title','Login')
@section('content')
    <form method="post" action="{{ route('auth-post-login') }}" class="flex flex-col space-y-4 text-center">
        @csrf
        <h1 class="text-3xl">Login</h1>
        <input type="email" name="email" class="px-3 py-2 w-[20rem] rounded border border-slate-500 bg-transparent" placeholder="email">            
        @if ($errors->has('email'))
            <span class="text-xs text-red-300">
                <?= $errors->first('email') ?>
            </span>
        @endif
        <input type="password" name="password" class="px-3 py-2 w-[20rem] rounded border border-slate-500 bg-transparent" placeholder="password">            
        @if ($errors->has('password'))
            <span class="text-xs text-red-300">
                <?= $errors->first('password') ?>
            </span>
        @endif
        <button class="bg-slate-700 text-white w-full text-xl py-2 rounded">submit</button>
    </form>
    <a class="mt-3" href="{{ route('auth-register') }}">click to register</a>
@endsection

