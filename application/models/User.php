<?php

class User extends Tokened {

    public static $table = 'users';
    
    public static function online( ) {
        
        $all_users = User::all( );
        $online = array( );
        $current_time = time( );
        
        foreach( $all_users as $user ) {
            
            if ( $user->id === Auth::user()->id ) {
                continue;
            }
        
            $last_time = strtotime( $user->last_update );
            $time_diff = $current_time - $last_time;
            
            if( $time_diff < 60 ) {
                $user_info = json_decode( $user->publicJSON( ), true );
                $user_info['busy'] = ( $user->game( ) !== false ) ? true : false; 
                $online[] = $user_info;
            }
        }
           
        return $online;
    }
    
    public function chatrooms( ) {
        return $this->has_many_and_belongs_to('Chatroom');
    }
    
    public function notifications( ) {
        return $this->has_many('Notification');
    }
    
    public function addWin( $inc = 1 ) {
        $this->wins  = ( (int)$this->wins ) + (int)$inc;
        $this->games = ( (int)$this->games ) + (int)$inc;
        $this->save( );
    }
    
    public function addLoss( $inc = 1 ) {
        $this->losses = ( (int)$this->losses ) + (int)$inc;
        $this->games  = ( (int)$this->games ) + (int)$inc;
        $this->save( );
    }
    
    
    public function game( ) {
        $current_game = Game::where("challenger_id", "=", $this->id )->take(1)->first( );
        
        if( $current_game == null ) {
            $current_game = Game::where("visitor_id", "=", $this->id )->take(1)->first( );
        }
        
        if( $current_game == null ) {
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
                
    public function getChatToken( $chatroom_id ) {
        $test = DB::table('chatroom_user')
                    ->where( 'chatroom_id', '=', $chatroom_id )
                    ->where( 'user_id', '=', $this->id )->first( );
        
        return $test->token;
    }
                
    public function ping( ) {
        $date = new DateTime( );
        $this->last_update = $date;
        $this->save( );
    }
                
    public function getUpdates( ) {
        $notifications = $this->notifications()->get();
        $updates = array( );
        foreach( $notifications as $note ) {
            $updates[] = json_decode( $note->publicJSON(), true );
        }
        return $updates;
    }
    
    public function publicJSON( ) {
        $public = array( );
        
        $token = sha1( $this->id . $this->username );
        $public['username'] = $this->username;
        $public['active']   = false;
        $public['location'] = array( "lat" => $this->latitude, "lng" => $this->longitude );
        
        if( Auth::check() && $this->id === Auth::user()->id ){ 
            $public['active'] = true;
            $public['token'] = $this->encodeToken( $token );
        }
        
        $public['wins'] = $this->wins;
        $public['losses'] = $this->losses;
        
        return json_encode( $public ); 
    }
    
}

?>