<?php

class Chatroom extends Tokened {

    public static $table = 'chatrooms';
    
    public function users( ) {
        return $this->has_many_and_belongs_to('User');
    }
    
    public function getFlag( ) {
        if( !file_exists( $this->chatfile( ) ) ) {
            return "dead";
        }
        $info = json_decode( File::get( $this->chatfile( ) ), true ); 
        return $info['flag'];
    }
    
    public function updateFlag( ) {
        $info = json_decode( File::get( $this->chatfile() ), true );    
        $info['flag'] = base_convert( rand( 1000000, 1000000000 ), 10, 36 );
        File::put( $this->chatfile( ), json_encode( $info ) );
    }
    
    public function messages( ) {
        $info = json_decode( File::get( $this->chatfile() ), true );
        $messages = $info['messages'];
        return $messages;
    }
    
    public function mostRecentMessages( ){
        if( !file_exists( $this->chatfile() ) ){ return array("dead"=>true); }
    
        $info = json_decode( File::get( $this->chatfile() ), true );
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
        
        $chat_info = json_decode( File::get( $this->chatfile( ) ), true );
        
        $active_user = $this->users( )->where( "user_id", "=", Auth::user()->id )->first( );    
        $usr_token   = ( $active_user !== NULL ) ? $active_user->chattoken( $this->id ) : false;
        
        $public['name'] = $this->name;
        $public['messages'] = $this->mostRecentMessages( );
        $public['user_token'] = $this->encodeToken( $usr_token );
        $public['chat_token'] = $this->encodeToken( $this->token );
        
        return json_encode( $public );         
    }
    
    public function chatfile( ){
        return path('storage') . 'chats/' . $this->token . '.json';
    }
        
}

?>