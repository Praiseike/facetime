<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <!-- <meta name="viewport" content="width=device-width, initial-scale=1.0"> -->
    <title>Webrtc testing</title>
</head>
<style>
    div{
        width: fit-content;
        margin: auto;
    }
    .buttons-container{
        margin-top: 20px;
    }
    video{
        width: 400px;
        height: 300px;
        background-color: black;
    }

</style>
<body>
    <div>
        <video autoplay id="localVideo"></video>
        <video autoplay id="remoteVideo"></video>
    </div>
    <div class="buttons-container">
        <button id="call">start call</button>
        <button id="endcall">end call</button>
    </div>

    <script>
        'use strict'

        let localStream = null;
        let remoteStream = null;
        let peerConnection;
        let localVideo = document.querySelector('#localVideo');
        let remoteVideo = document.querySelector('#remoteVideo');
        let serverHost = location.hostname;
        let serverPort = 8090;
        let connection = new WebSocket(`wss://${serverHost}/signaling`);
        
        const servers = {
            iceServers: [
            {
                // urls: ['stun:stun1.l.google.com:19302','stun:stun2.l.google.com:19302'],
                urls: 'turn:sandbox1.techr.com:3478',
                username: 'techroom',
                credential: 'techroompassword'
            }],
            iceCandidatePoolSize: 10,
        }

        const constraints = {
            video: true,
            audio: true,
        }


        // let peerConnection = new RTCPeerConnection(servers);
    
        const createPeerConnection = () => {
            try{

                peerConnection = new RTCPeerConnection(servers);

                peerConnection.onicecandidate = e => {
                    if(e.candidate){
                        wsend('client-candidate',e.candidate)
                    }
                }
                peerConnection.ontrack = e => remoteVideo.srcObject = e.streams[0];
                localStream.getTracks().forEach(track => {
                    peerConnection.addTrack(track,localStream);
                });
            }catch(error){
                alert("an error occured while creating the peer connection")
            }
        }

        const sendOffer = async () => {
            // create the offer
            // set the local description and send the offer
            try{
                await createPeerConnection();
                const offer = await peerConnection.createOffer();
                await peerConnection.setLocalDescription(offer);
                wsend('client-offer',offer);
                console.log('sent offer');

            }catch(error){
                alert("an error occured while trying to create and send offers")
            }
        }

        const wsend = (type,data) => {
            const messageObject = {
                type:type,
                data: data,
            }
            const message = JSON.stringify(messageObject);
            try{
                connection.send(message);
            }catch(error){
                alert("an error occured while trying to send messages");
            }

        }

        const sendAnswer = async (description) => {
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
                wsend('client-answer',answer);
                console.log('sent answer');
            }catch(error){
                alert(error)
            }
        }

        const makeRequest = async () => {
            await createPeerConnection();
            await sendOffer();
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
            switch(msg.type){
                case 'client-offer':
                    console.log("received offer")
                    sendAnswer(msg.data) 
                    break;

                case 'client-answer':
                    handleAnswer(msg.data)
                    break;

                case 'client-candidate':
                    handleCandidate(msg.data);
                    break

                case 'is-client-ready':
                    if(peerConnection){
                        console.error("already on a call");
                        return;
                    }
                    
                    await initCam();
                    console.log('client is ready');
                    wsend('client-ready',null);
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
        
        // const initCam = async () => {
        //     navigator.mediaDevices.getUserMedia(constraints)
        //         .then(stream => {
        //             localStream = stream;
        //             localVideo.srcObject = stream;
        //             remoteStream = new MediaStream();
        //         })
        // }

        const call = async (e) => {
            initCam().then(() => {
                wsend('is-client-ready')
            })
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
        
        // document.querySelector('#init').addEventListener('click',init);
        document.querySelector('#call').addEventListener('click',call);
        document.querySelector('#endcall').addEventListener('click',endcall);

    </script>
</body>
</html>