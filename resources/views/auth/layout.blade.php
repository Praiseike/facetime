<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>@yield('title')</title>
    @vite('resources/css/app.css')
</head>
<body class="bg-slate-900">
    <div class="w-full sm:md:w-[70%] mx-auto h-screen flex flex-col justify-center items-center text-slate-300 ">
        @yield('content')
    </div>
</body>
</html>