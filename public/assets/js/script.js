'use strict'

let localStream = null;
let peerConnection;
let localVideo = document.querySelector('#localVideo');
let remoteVideo = document.querySelector('#remoteVideo');


let globalMsg = {};
let currentTarget = 0;

// object to control logging

class Logger{
    constructor(value = true){
        this.show(value)
    }
    show(value = true){
        this.showLogs = value
    }
    log(...args){
        if(this.showLogs)  
            logger.log(...args);
    }
}

const logger = new Logger()

const servers = {
    iceServers: [
        
        {
            urls: ['stun:sandbox1.techr.me:3478']
        },
        {
            urls: 'turn:sandbox1.techr.me:3478',
            username: 'techroom',
            credential: 'techroompassword'
        },

    ],
    // iceCandidatePoolSize: 10,
}



const constraints = {
    video: true,
    audio: true,
}

const createPeerConnection = () => {
    try{
        peerConnection = new RTCPeerConnection(servers);
        peerConnection.onicecandidate = e => {
            if(e.candidate){
                wsend(globalMsg.from,'client-candidate',e.candidate)
                logger.log('sent ice candidate')
            }
        }

        peerConnection.oniceconnectionstatechange = e => {
            logger.log("connection state changed")
        }

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
        logger.log('sent offer');

    }catch(error){
        alert("an error occured while trying to create and send offers")
    }
}

const wsend = (target,type,data = null) => {
    const message = JSON.stringify({
        target: target,type: type,data: data
    });
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
        // await peerConnection.setRemoteDescription(description)
        await peerConnection.setRemoteDescription(new RTCSessionDescription(description))
        const answer = await peerConnection.createAnswer();
        await peerConnection.setLocalDescription(answer);
        wsend(target,'client-answer',answer);
        logger.log('sent answer');
    }catch(error){
        alert(error)
    }
}

const makeRequest = async () => {
    await createPeerConnection();
    await sendOffer(currentTarget);
}

const handleAnswer = async (answer) => {
    logger.log("received answer");
    if(peerConnection.localDescription)
        await peerConnection.setRemoteDescription(answer);  
    else
        console.error("no peer connection");            
}


const handleCandidate = (candidate) => {
    if(peerConnection.localDescription){
        peerConnection.addIceCandidate(candidate);
    }
}


connection.onmessage = async (event) => {
    const msg = JSON.parse(event.data);
    globalMsg = msg;

    switch(msg.type){

        case 'client-offer':
            logger.log("received offer")
            sendAnswer(msg.from,msg.data) 
            break;

        case 'client-answer':
            handleAnswer(msg.data)
            break;

        case 'client-candidate':
            logger.log("received candidate");
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
            logger.log(msg.type);
            await makeRequest();
            break;
        
        case 'broadcast':
            logger.log("received broadcast... getting users");
            wsend(null,'get-users',{});
            // updateUsers(msg);
            break;

        case 'users-data':
            logger.log("listing users after broadcast",msg.data.users);
            updateUsers(msg);
            break;
        default:
            break;
    }
}

const initCam = async () => {
    return new Promise((resolve,reject) => {
        navigator.mediaDevices.getUserMedia(constraints)
            .then(stream => {
                localStream = stream;
                localVideo.srcObject = localStream;
                resolve()
            })
            .catch(error => {
                logger.log(error)
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
    logger.log(e.target);
    currentTarget = target;
    toggleCallScreen();
    initCam().then(() => {
        // ask if the target client is ready to receive a call
        logger.log("sending message to target",target)
        wsend(target,'is-client-ready',{})
    })

}

const answerCall = async (e) => {
    toggleRequestScreen();
    toggleCallScreen();
    await initCam();
    logger.log('client is ready');
    wsend(globalMsg.from,'client-ready',null);
}

const denyCall = (target) => {
    toggleRequestScreen();
    wsend(globalMsg.from,'client-denied-call',null);
}

const endcall = () => {
    logger.log("ending call");
    if(localStream){
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
    const userList = document.querySelectorAll('#call');

    msg.data.users.forEach(user => {
        let currentUser = document.querySelector(`[data-id='${user.id}']`);
        if(currentUser){
            currentUser.querySelector('.status').classList.add('bg-green-500');
            logger.log(currentUser);
            currentUser.querySelector('.status').classList.remove('bg-gray-500');
        }
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