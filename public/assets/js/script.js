'use strict'

let localStream = null;
let peerConnection;
let localVideo = document.querySelector('#localVideo');
let remoteVideo = document.querySelector('#remoteVideo');


let globalMsg = {};
let currentTarget = 0;

const servers = {
    iceServers: [
        
        {
            urls: 'stun:sandbox1.techr.me:3478'
        },
        {
            urls: 'turn:sandbox1.techr.me:3478',
            username: 'techroom',
            credential: 'techroompassword'
        }
    ],
}



const constraints = {
    video: true,
    audio: true,
}

const createPeerConnection = () => {
    try{
        peerConnection = new RTCPeerConnection(servers);
        peerConnection.onicecandidate = e => {
            try{
                if(e.candidate){
                wsend(globalMsg.from,'client-candidate',e.candidate)
                console.log('sent ice candidate')
            }
            }catch(error){
                alert("unable to send ice candidate");
                }            
            };
        peerConnection.ontrack = e => remoteVideo.srcObject = e.streams[0];
        localStream.getTracks().forEach(track => {
            peerConnection.addTrack(track,localStream);
        });
    }catch(error){
        alert("an error occured while creating the peer connection")
    }
}

const sendOffer = async (target) => {
    // create the offer
    // set the local description and send the offer
    try{
        await createPeerConnection();
        const offer = await peerConnection.createOffer();
        await peerConnection.setLocalDescription(offer);
        wsend(target,'client-offer',offer);
        console.log('sent offer');

    }catch(error){
        alert("an error occured while trying to create and send offers")
    }
}

const wsend = (target,type,data = null) => {
    const messageObject = {
        target: target,
        type: type,
        data: data
    }

    const message = JSON.stringify(messageObject);
    
    let tries = 5;
    try{
        let retryInterval = setInterval(() => {
            if(connection.readyState == connection.OPEN){
                connection.send(message);
                clearInterval(retryInterval);
            }
            if(tries == 0)
            {
                clearInterval(retryInterval);
                throw "failed to send message";
            }
            tries --;
        },1000);
    }catch(error){
        alert("WSEND ERROR:"+error,);
    }

}

const sendAnswer = async (target,description) => {
    // create the answer 
    // set the remote description and localdescription
    // send the answer
    if(peerConnection){
        console.error("existing peer connection");
        return;
    }
    try{

        await createPeerConnection();
        await peerConnection.setRemoteDescription(new RTCSessionDescription(description))
        const answer = await peerConnection.createAnswer();
        await peerConnection.setLocalDescription(answer);
        wsend(target,'client-answer',answer);
        console.log('sent answer');
    }catch(error){
        alert(error)
    }
}

const makeRequest = async () => {
    await createPeerConnection();
    await sendOffer(currentTarget);
}

const handleAnswer = async (answer) => {
    console.log("received answer");
    if(peerConnection.localDescription)
        await peerConnection.setRemoteDescription(answer);  
    else
        console.error("no peer connection");            
}


const handleCandidate = (candidate) => {
    if(peerConnection.localDescription){
        peerConnection.addIceCandidate(new RTCIceCandidate(candidate));
    }
}




connection.onmessage = async (event) => {
    const msg = JSON.parse(event.data);
    globalMsg = msg;

    switch(msg.type){

        case 'client-offer':
            console.log("received offer")
            sendAnswer(msg.from,msg.data) 
            break;

        case 'client-answer':
            handleAnswer(msg.data)
            break;

        case 'client-candidate':
            console.log("received candidate");
            handleCandidate(msg.data);
            break

        case 'is-client-ready':
            if(peerConnection){
                console.error("already on a call");
                return;
            }
            toggleRequestScreen();
            break;

        case 'end-call':
            alert("the client ended the call")
            endcall();
            break;

        case 'client-denied-call':
            alert("the call was denied");
            toggleCallScreen();
            break;

        case 'client-ready':
            console.log(msg.type);
            await makeRequest();
            break;
        
        case 'broadcast':
            console.log("received broadcast... getting users");
            wsend(null,'get-users',{});
            // updateUsers(msg);
            break;

        case 'users-data':
            console.log("listing users after broadcast",msg.data.users);
            updateUsers(msg);
            break;
        default:
            break;
    }
}

// const initCam = async () => {
//     // get user cam 
//     localStream = await navigator.mediaDevices.getUserMedia(constraints)
//     localVideo.srcObject = localStream;
// }

const initCam = async () => {
    return new Promise((resolve,reject) => {
        navigator.mediaDevices.getUserMedia(constraints)
            .then(stream => {
                localStream = stream;
                localVideo.srcObject = localStream;
                resolve()
            })
            .catch(error => {
                console.log(error)
                reject()
            })
    });
}

const toggleCallScreen = () => {
    // toggle the call screen containing the video elements
    document.querySelector('#call-screen').classList.toggle('hidden');
}

const toggleRequestScreen = () => {
    // toggle the call screen containing the call request elements
    document.querySelector('#call-request').classList.toggle('hidden');
}

const call = async (e) => {
    // initiate a call by first getting the target id 
    // and initialing user devices
    const target = e.target.getAttribute('data-id');
    console.log(e.target);
    currentTarget = target;
    toggleCallScreen();
    initCam().then(() => {
        // ask if the target client is ready to receive a call
        console.log("sending message to target",target)
        wsend(target,'is-client-ready',{})
    })

}

const answerCall = async (e) => {
    toggleRequestScreen();
    toggleCallScreen();
    await initCam();
    console.log('client is ready');
    wsend(globalMsg.from,'client-ready',null);
}

const denyCall = (target) => {
    toggleRequestScreen();
    wsend(globalMsg.from,'client-denied-call',null);
}

const endcall = () => {
    console.log("ending call");
    if(localStream || peerConnection){
        wsend(globalMsg.from,'end-call',null);
        localStream.getTracks().forEach(track => track.stop());
        localStream = null;
        localVideo.srcObject = null;
        remoteVideo.srcObject = null;
        peerConnection.close()
        peerConnection = null
        toggleCallScreen();
    }
}

const updateUsers = (msg) => {
    const userList = document.querySelector('#user-list');
    userList.innerHTML = '';
    const addUser = user => {
        userList.innerHTML += `
            <li id="call" data-id="${user.id}" " class="rounded-lg p-4 m-4 w-[10rem] h-[10rem] flex justify-center space-y-4 cursor-pointer bg-[#64748b99] flex-col items-center border border-slate-600">
                <div class="bg-teal-400 rounded-full w-14 h-14 flex items-center justify-center text-white font-bold text-2xl">
                    ${ user.name[0] }
                </div>
                <span class="font-bold text-xl">${(user.id == currentUserID) ? 'You':user.name}</span>
            </li>
            `
    }
    msg.data.users.forEach(user => {
        addUser(user);
    });        
    // update onclick handlers for all #call buttons
    document.querySelectorAll('#call').forEach((btn) => btn.addEventListener('click',call));

}

window.onload = () => {
    // announce your presence
    wsend(null,'broadcast',{});
    // get the list of currently connected users
    wsend(null,'get-users',{});

    document.querySelector('#endcall').addEventListener('click',endcall);
    document.querySelector('#deny').addEventListener('click',denyCall);
    document.querySelector('#answer').addEventListener('click',answerCall);
}