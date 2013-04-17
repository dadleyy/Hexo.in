<?php

class Chatroom extends Tokened {

    public static $table = 'chatrooms';
    
    public static function publicRooms( ) {
        $rooms = Chatroom::where("game_id","=","0")->get( ); 
        $output = array( );
        foreach( $rooms as $room ) {
            $output[] = json_decode( $room->publicJSON( ), true );
        }
        return $output;
    }
    
    public static function decodeCID( $cid ) { 
        $reg = base_convert( $cid, 32, 10 );
        return (string)$reg[0];
    }
    
    public function users( ) { return $this->has_many_and_belongs_to('User'); }
    
    public function isClosed( ) { return !file_exists( $this->chatFile( ) ); }

    public function cid( ) { 
        $dec_id = $this->id . rand(10000000,4000000);
        return base_convert( $dec_id, 10, 32 ); 
    }

    public function addUser( $user ) {
        
        $date = new DateTime( );
        
        foreach( $this->users()->get() as $e_user ) {
            if( (int)$user->id === (int)$e_user->id ){ 
                return false;
            }
        }
                
        /* update the table in the db to show this user in chat */
        DB::table('chatroom_user')->insert( array(
            'chatroom_id' => $this->id,
            'user_id'     => $user->id,
            'token'       => sha1( $this->id . $user->id ),
            'created_at'  => $date,
            'updated_at'  => $date
        ));    
        
        return true;
    } 
    
    public function removeUser( $user ) {
        DB::table('chatroom_user')
                ->where( "user_id", "=", $user->id )
                ->where( "chatroom_id", "=", $this->id )
                ->delete();
    } 
        
    public function getFlag( ) {
        if( $this->isClosed( ) ) {
            return "dead";
        }
        $info = json_decode( File::get( $this->chatFile( ) ), true ); 
        return $info['flag'];
    }
    
    public function updateFlag( ) {
        $info = json_decode( File::get( $this->chatFile() ), true );    
        $info['flag'] = base_convert( rand( 1000000, 1000000000 ), 10, 36 );
        File::put( $this->chatFile( ), json_encode( $info ) );
    }
    
    public function messages( ) {
        $info = json_decode( File::get( $this->chatFile() ), true );
        $messages = $info['messages'];
        return $messages;
    }
    
    public function mostRecentMessages( ){
        if( !file_exists( $this->chatFile() ) ){ return array("dead"=>true); }
    
        $info = json_decode( File::get( $this->chatFile() ), true );
        $messages = $info['messages'];
        $recent = array( );
        $index = 0;
        foreach( $messages as $message ){
            $recent[$index] = $message;
            $index++;
        }
        return $recent;
    }
    
    /* chatroom->publicJSON
     * Returns a json encoded array of information that is 
     * usable on the client side for communication
     * @returns {string} json ecoded array
    */
    public function publicJSON( ) {
        $public = array( );
        
        $chat_info = json_decode( File::get( $this->chatFile( ) ), true );
        
        $active_user = $this->users( )->where( "user_id", "=", Auth::user()->id )->first( );    
        $usr_token   = ( $active_user !== NULL ) 
                            ? $active_user->getChatToken( $this->id ) 
                            : false;
        
        $public['name'] = $this->name;
        $public['messages'] = $this->mostRecentMessages( );
        $public['count'] = count( $this->users()->get() );
        $public['id'] = $this->cid( );
        $public['user_token'] = $this->encodeToken( $usr_token );
        $public['chat_token'] = $this->encodeToken( $this->token );
        
        return json_encode( $public );         
    }
    
    /* chatroom->createJSON
     * Sets up the json file for future use
    */
    public function createJSON( ) {
        
        $chat_location = $this->chatfile( );
        $chat_contents = array(
            "messages" => array( ),
            "token" => $this->token,
            "id"    => $this->id,
            "flag"  => rand( 0, 100000 )
        );
        File::put( $chat_location, json_encode( $chat_contents ) );
        
    }
    
    public function chatFile( ){
        return path('storage') . 'chats/' . $this->token . '.json';
    }
        
}

?>