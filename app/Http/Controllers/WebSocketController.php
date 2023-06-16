<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

use App\Models\User;

class WebSocketController extends Controller implements MessageComponentInterface
{
    protected $connections;

    public function __construct(){
        $this->connections = new \SplObjectStorage;
    }

    // called when connection is opened
    public function onOpen(ConnectionInterface $conn){

        // parse the request parameters
        $requestString = $conn->httpRequest->getUri()->getQuery();
        parse_str($requestString,$requestParam);

        // get the id from the params and fetch the user
        $user = User::find($requestParam['id']);

        // if the user exist; recognize the connection
        if($user){
            $user->update(['connectionID'=>$conn->resourceId]);
            $conn->user = $user;
            $this->connections->attach($conn);
            echo "New connection from ({$user->name}) !!\n";
        }else{
            echo "Invalid connection attempt with ID ".$requestParam['id']."\n";
        }
    }

    // called when connection is closed
    public function onClose(ConnectionInterface $conn){
        // detaching $conn from the list reduces the 
        // refcount to zero thus freeing the allocated memory
        // HOPEFULLY
        $this->connections->detach($conn);
        echo "Connection ({$conn->user->name}) has disconnected\n";
        // $conn falls out of scope here but to be sure
        // unset($conn->user);
    }

    // called when a client sends data through the socket
    public function onMessage(ConnectionInterface $from, $msg){

        // get the number of connections
        $numRecv = count($this->connections);
        
        $data = json_decode($msg,true);
        $type = $data['type'];


        switch($type){

            // check if its a broadcast and send accordingly
            case 'broadcast':
            $this->sendBroadcast($from,$data);
                return;
        
            case 'get-users':
                $this->getConnectedUsers($from);
                return;
            
            default:
                $this->handleRelay($from,$data,$msg,$type);
                break;
            }
        }
        
        public function handleRelay($from,$data,$msg,$type){
            // get the target user
            $targetUser = User::find($data['target']);

            // basically relaying the msg from the sender to
            // the receiver but after editing some fields

            if($targetUser){

                $relay['from']  = $from->user->id;
                $relay['to']    = $data['target'];
                $relay['type']  = $type;
                $relay['data']  = $data['data'];


                $msg = json_encode($relay);
                
                // loop through all the connections
                foreach($this->connections as $client){
                    // make sure the message is not directed at the sender
                    if($from !== $client){
                        // find the target and send the message
                        if($targetUser->id == $client->user->id){
                            $client->send($msg);
                            echo $from->user->name." Sent message of type '".$type."' to " .$targetUser->name."\n";
                        }
                    }
                }            
            }

    }

    public function sendBroadcast(ConnectionInterface $conn,$msg){
        $msg['data'] = ['user'=>$conn->user];
        foreach($this->connections as $client){
            if($conn->resourceId != $client->resourceId){
                $client->send(json_encode($msg));
            }
        }
    }

    public function getConnectedUsers($conn){
        $users = [];
        foreach($this->connections as $con){
            if(isset($con->user)){
                $users[] = [ 'id'=>$con->user->id,'name'=>$con->user->name ];
            }
        }
        $msg = [
            'target'=>$conn->user->id,
            'type'=>'users-data',
            'data'=> ['users'=>$users]
        ];
        $conn->send(json_encode($msg));
    }



    // called when there's an error
    public function onError(ConnectionInterface $conn,\Exception $e){
        echo "An error has occured: {$e->getLine()}:{$e->getMessage()}";
    }
}