<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script>
        let serverHost = location.hostname;
        let serverPort = 8090;
        let connection = new WebSocket(`wss://${serverHost}/signaling?id={{ $user->id }}`);
    </script>
    @vite('resources/css/app.css')
    <title>Video chat</title>
</head>
<body class="bg-slate-900">
    <div id="call-request" class="fixed hidden h-screen w-full backdrop-blur inset-0" id="overlay">
        <div class="h-[14rem] flex justify-center text-center items-center space-y-10 flex-col rounded-md border border-slate-700 p-4 mt-20 w-[90%] sm:md:w-[40%] z-[50] bg-[#64748b11] mx-auto px-10">
            <h1 class="text-md text-slate-300"><span class="font-bold text-lg">Incomming call from</span> <br> example@techr.com</h1>
            <div class="z-[40] w-full mx-auto flex justify-around">
                <button type="" id="answer" class="w-14 rounded-full h-14 hover:bg-green-500 mt-auto bg-green-700 text-white">ans</button>
                <button type="" id="deny" class="w-14 rounded-full h-14 hover:bg-red-500 mt-auto bg-red-700 text-white">end</button>
            </div>
        </div>
    </div>
    <div id="call-screen" class="fixed hidden h-screen w-full backdrop-blur inset-0" id="overlay">
        <div class="h-full w-full sm:md:w-[40%] mx-auto px-0 sm:md:px-10">
            <video class="bg-black border w-[10rem] z-[20] absolute h-[10rem] border-orange-500" autoplay id="localVideo"></video>
            <video class="bg-black h-full  w-full" autoplay id="remoteVideo"></video>
            <div class="z-[40] mt-[-5rem] w-fit mx-auto">
                <button type="" id="endcall" class="w-14 rounded-full h-14 hover:bg-red-500 mt-auto bg-red-700 text-white">end</button>
            </div>
        </div>
    </div>
    <div class="w-[90%] sm:md:w-[70%] mx-auto h-screen px-3 sm:md:px-20 py-20 text-slate-300 border-slate-700 sm:md:border-r sm:md:border-l">
        <div class="flex justify-between">
            <h1 class="text-slate-300 text-2xl font-semibold">List of online users</h1>        
            <a href="{{ route('auth-logout') }}" class="px-3 py-1 text-white cursor-pointer rounded bg-slate-500">Logout</a>
        </div>
        <ul class="mt-5 space-y-4">
            @foreach($users as $target)
            <li class="rounded py-4 flex justify-between px-3 border border-slate-600">
                <div class="inline-block">
                    {{ $target->name }}
                </div>
                <button id="call" data-id="{{ $target->id }}" class="px-3 text-white cursor-pointer rounded bg-slate-500">call</button>
            </li>
            @endforeach
        </ul>
    </div>
    <script src="assets/js/script.js"></script>
</body>
</html>