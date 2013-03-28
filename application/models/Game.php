<?php

class Game extends Tokened {

    public static $table = 'games';
    public static $timestamps = true;
    
    public function visitor( ){ 
        return User::find( $this->visitor_id ); 
    }
    
    public function challenger( ){ 
        return User::find( $this->challenger_id ); 
    }
    
    public function isOver( ) { 
        return !file_exists( $this->gamefile( ) ); 
    }
    
    public function gamefile( ){
        return path('storage') . 'games/' . $this->token . '.json';
    }
    
    public function getLastTime( ) {
        return strtotime( $this->updated_at );
    }
    
    public function getFlag( ) {
        
        if( !file_exists( $this->gamefile() ) ) {
            return "dead";
        }
        
        $info = json_decode( File::get( $this->gamefile() ), true ); 
        return $info['flag'];
    }
    
    public function updateFlag( ) {
        $info = json_decode( File::get( $this->gamefile() ), true );    
        $info['flag'] = base_convert( rand( 1000000, 1000000000 ), 10, 36 );
        File::put( $this->gamefile(), json_encode( $info ) );
    }
    
    public function publicJSON( ){
    
        $info  = json_decode( File::get( $this->gamefile() ), true );
        $token = $info['token'];
        $public = array( );

        /* save the information into the public array */
        $public['token'] = $this->encodeToken( $token );
    
        $public['state'] = $info['state'];
        $public['turn'] = $info['turn'];
        $public['tiles'] = $info['tiles'];
        $public['flag'] = $info['flag'];
        
        /* output the two users */
        $public['challenger'] = json_decode( $this->challenger()->publicJSON( ), true );
        $public['visitor'] = ( $this->visitor() !== null ) 
                                    ? json_decode( $this->visitor()->publicJSON( ), true )
                                    : false;
        
        if( $this->chatroom( ) !== null ){
            $public['chatroom'] = json_decode( $this->chatroom( )->publicJSON( ), true );
        }
        
        return json_encode( $public );
    }

    public function chatroom( ){
        return Chatroom::where( "game_id", "=", $this->id )->first( );
    }
    
}

?>