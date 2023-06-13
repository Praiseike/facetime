'use strict'

let localStream = null;
let remoteStream = null;
let peerConnection;
let localVideo = document.querySelector('#localVideo');
let remoteVideo = document.querySelector('#remoteVideo');


let globalMsg = {};
let currentTarget = 0;

const servers = {
    iceServers: [
    {
        urls: 'turn:sandbox1.techr.com:3478',
        username: 'techroom',
        credential: 'techroompassword'
    }],
    // iceCandidatePoolSize: 10,
}

const constraints = {
    video: true,
    audio: true,
}


// let peerConnection = new RTCPeerConnection(servers);

const createPeerConnection = () => {
    try{

        peerConnection = new RTCPeerConnection(servers);

        try{
            peerConnection.onicecandidate = e => {
                if(e.candidate){
                    wsend(globalMsg.from,'client-candidate',e.candidate)
                    console.log('sent ice candidate')
                }
            }            
        }catch(error){
            alert("unable to send ice candidate");
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
    
    try{
        connection.send(message);
    }catch(error){
        alert("an error occured while trying to send messages");
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

        case 'client-denied-call':
            alert("the call was denied");
            toggleScreen();
            break;

        case 'client-ready':
            console.log(msg.type);
            await makeRequest();
            break;
            
        default:
            break;
    }
}

const initCam = async () => {
    // get user cam 
    localStream = await navigator.mediaDevices.getUserMedia(constraints)
    localVideo.srcObject = localStream;
    // create new MediaSteam object for the remote stream;
    remoteStream = new MediaStream();        
}

const toggleScreen = () => {
    document.querySelector('#call-screen').classList.toggle('hidden');
}

const toggleRequestScreen = () => {
    document.querySelector('#call-request').classList.toggle('hidden');
}

const call = async (e) => {
    const target = e.target.getAttribute('data-id');
    currentTarget = target;
    toggleScreen();
    initCam().then(() => {
        wsend(target,'is-client-ready',{})
    })
}

const answerCall = async (e) => {
    toggleRequestScreen();
    toggleScreen();
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
    if(localStream){
        // stop all tracks
        localStream.getTracks().forEach(track => track.stop());
        // leave the rest to gc
        localStream = null;
        localVideo.srcObject = null;
    }
}


// window.addEventListener('beforeunload', (event) => {
//     event.preventDefault();
//     return
// });

// document.querySelector('#init').addEventListener('click',init);
document.querySelector('#call').addEventListener('click',call);
document.querySelector('#endcall').addEventListener('click',endcall);
document.querySelector('#deny').addEventListener('click',denyCall);
document.querySelector('#answer').addEventListener('click',answerCall);