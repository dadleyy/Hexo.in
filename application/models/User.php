<?php

class User extends Tokened {

    public static $table = 'users';
    
    public function chatrooms( ) {
        return $this->has_many_and_belongs_to('Chatroom');
    }
    
    public function game( ) {
        $current_game = Game::where("challenger_id", "=", $this->id )->take(1)->first( );
        
        if( $current_game == null ){
            $current_game = Game::where("visitor_id", "=", $this->id )->take(1)->first( );
        }
        
        if( $current_game == null ){
            return false;
        }
        
        return $current_game;
    }
    
    public function friends( ) {
        return Friend::where( "friender", "=", $this->id );
    }
    
    public function joined( ) {
        return date( 'M j, Y H:i', strtotime($this->created_at) );
    }
                
    public function chattoken( $chatroom_id ) {
        $test = DB::table('chatroom_user')
                    ->where( 'chatroom_id', '=', $chatroom_id )
                    ->where( 'user_id', '=', $this->id )->first( );
        
        return $test->token;
    }
                
    public function publicJSON( ) {
        $public = array( );
        
        $token = sha1( $this->id . $this->username );

        $public['username'] = $this->username;
        $public['active']   = false;
        if( $this->id === Auth::user()->id ){ 
            $public['active'] = true;
            $public['token'] = $this->encodeToken( $token );
        }
        
        $public['wins'] = $this->wins;
        $public['losses'] = $this->losses;
        
        return json_encode( $public ); 
    }
    
}

?>