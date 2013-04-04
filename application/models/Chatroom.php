<?php

class Chatroom extends Tokened {

    public static $table = 'chatrooms';
    
    public function users( ) {
        return $this->has_many_and_belongs_to('User');
    }
    
    public function isClosed( ) {
        return !file_exists( $this->chatFile( ) );   
    }
    
    public function addUser( $user ) {
        
        $date = new DateTime( );
                    
        /* update the table in the db to show this user in chat */
        DB::table('chatroom_user')->insert( array(
            'chatroom_id' => $this->id,
            'user_id'     => $user->id,
            'token'       => sha1( $this->id . $user->id ),
            'created_at'  => $date,
            'updated_at'  => $date
        ));    
        
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
    
    public function publicJSON( ) {
        $public = array( );
        
        $chat_info = json_decode( File::get( $this->chatFile( ) ), true );
        
        $active_user = $this->users( )->where( "user_id", "=", Auth::user()->id )->first( );    
        $usr_token   = ( $active_user !== NULL ) ? $active_user->getChatToken( $this->id ) : false;
        
        $public['name'] = $this->name;
        $public['messages'] = $this->mostRecentMessages( );
        $public['user_token'] = $this->encodeToken( $usr_token );
        $public['chat_token'] = $this->encodeToken( $this->token );
        
        return json_encode( $public );         
    }
    
    public function chatFile( ){
        return path('storage') . 'chats/' . $this->token . '.json';
    }
        
}

?>