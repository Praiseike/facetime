<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script>
        let connection = new WebSocket(`wss://${location.hostname}/signaling?id={{ $user->id }}`);
        let currentUserID = {{ $user->id }}
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    @vite('resources/css/app.css')
    <title>Facetime | Video chat</title>
</head>
<body class="bg-slate-900">
    <div id="call-request" class="fixed hidden h-screen w-full backdrop-blur inset-0" id="overlay">
        <div class="h-[14rem] flex justify-center text-center items-center space-y-10 flex-col rounded-md border border-slate-700 p-4 mt-20 w-[90%] sm:md:w-[40%] z-[50] bg-[#64748b11] mx-auto px-10">
            <h1 class="text-md text-slate-300"><span class="font-bold text-lg">Incomming call from</span> <br> example@techr.com</h1>
            <div class="z-[40] w-full mx-auto flex justify-around">
                <button type="" id="answer" class="w-14 rounded-full text-2xl h-14 hover:bg-green-500 mt-auto bg-green-700 text-white">
                    <i class="fa-solid fa-phone fa-rotate-270"></i>
                </button>
                <button type="" id="deny" class="w-14 rounded-full text-2xl h-14 hover:bg-red-500 mt-auto bg-red-700 text-white">
                    <i class="fa-solid fa-phone fa-rotate-90"></i>
                </button>
            </div>
        </div>
    </div>
    <div id="call-screen" class="fixed h-screen z-[1000] w-full backdrop-blur inset-0" id="overlay">
        <div class="h-full w-full sm:md:w-min-[40%] mx-auto px-0 sm:md:px-10">
            <div>            
                <video class="bg-neutral-900 border w-[12rem] absolute h-[14rem] border-orange-500" autoplay muted id="localVideo"></video>
                <video class="bg-neutral-900 h-screen w-full" autoplay id="remoteVideo"></video>
            </div>
            <div class="absolute bottom-[30px] left-[50%] ml-[-2rem] w-fit mx-auto">
                <button type="" id="endcall" class="w-[4rem] rounded-full h-[4rem] hover:bg-red-500 mt-auto bg-red-700 text-white">end</button>
            </div>
        </div>
    </div>
    <div class="w-[90%] sm:md:w-[70%] mx-auto h-screen px-3 sm:md:px-20 py-20 text-slate-300 border-slate-700 sm:md:border-r sm:md:border-l">
        <div class="flex justify-between">
            <h1 class="text-slate-300 text-2xl font-semibold">Users</h1>        
            <a href="{{ route('auth-logout') }}" class="px-3 py-1 text-white cursor-pointer rounded bg-slate-500">Logout</a>
        </div>
        <div class="mt-5 flex flex-row flex-wrap gap" id="user-list" >
            <div class="rounded-lg p-4 w-full mb-3  flex space-x-4 cursor-pointer bg-[#64748b99] items-center border border-slate-600">
                <div class="bg-teal-400 rounded-full w-12 h-12 flex items-center justify-center text-white font-bold text-2xl">
                    {{ $user->name[0] }}
                </div>
                <span class="font-bold text-xl">{{ $user->name }} (You)</span>
            </div>
            @foreach($users as $target)
            <div data-id="{{ $target->id }}" id="call" class="rounded-lg p-4 w-full mb-3 flex space-x-4 cursor-pointer bg-[#64748b99] items-center border border-slate-600">
                <div class="bg-teal-400 rounded-full w-12 h-12 flex items-center justify-center text-white font-bold text-2xl">
                    {{ $target->name[0] }}
                </div>
                <div class="font-bold text-xl flex flex-row items-center">
                    <div class="h-2 w-2 bg-gray-500 rounded-full mx-2 status"></div>
                    <span> 
                        {{ $target->name }}
                    </span>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    <script src="assets/js/script.js"></script>
</body>
</html>