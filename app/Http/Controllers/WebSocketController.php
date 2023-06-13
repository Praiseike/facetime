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
        $this->connections->detach($conn);

        echo "Connection ({$conn->user->name}) has disconnected\n";

    }

    // called when a client sends data through the socket
    public function onMessage(ConnectionInterface $from, $msg){
        $numRecv = count($this->connections);
        
        $data = json_decode($msg,true);
        $type = $data['type'];

        $targetUser = User::find($data['target']);

        if($targetUser){

            $relay['from']  = $from->user->id;
            $relay['to'] = $data['target'];
            $relay['type'] = $type;
            $relay['data'] = $data['data'];


            $msg = json_encode($relay);
            
            foreach($this->connections as $client){
                if($from !== $client){
                    if($targetUser->id == $client->user->id){
                        $client->send($msg);
                        echo $from->user->name." Sent message of ".$type." to " .$targetUser->name."\n";
                    }
                }
            }            
        }
    }


    // called when there's an error
    public function onError(ConnectionInterface $conn,\Exception $e){
        echo "An error has occured: {$e->getMessage()}";

    }
}

