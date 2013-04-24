<?php

class User extends Tokened {

    public static $table = 'users';
    
    /* User::online
     * returns all of the users that have not been
     * inactive for the last 60 seconds
     * @returns [array] all online user objects
    */
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
    
    /* user->friends
     * Gets all of the user's friends
     * @returns [query] the query getting all the user's friends
    */
    public function friends( ) { return Friend::where( "friender", "=", $this->id ); }
    
    
    /* user->chatrooms 
     * Queries all chatrooms that belong to the user.
     * @returns [query] the chatroom objects 
    */
    public function chatrooms( ) { return $this->has_many_and_belongs_to('Chatroom'); }
    
    /* user->joined
     * Helper function to get the user's join date
     * @returns {string} A clean string for user join date
    */
    public function joined( ) { return date( 'M j, Y H:i', strtotime($this->created_at) ); }
        
    /* user->checkToken
     * Attempts to validate a token array sent in from a request
     * to see if it is in the proper format
     * @param [array] the encoded token array from the client
     * @returns {boolean} true or false
    */
    public function checkToken( $token_arr ) {
        if( !is_array($token_arr) ){
            return false;
        }
        
        $de_token = User::decodeToken( $token_arr );
        $real_token = sha1( $this->id . $this->username ) . sha1( "fluffy@bunny@2011" );
        
        return ( $de_token == $real_token );
    }
    
    /* user->publicChats
     * Gets all of the cahtrooms for the user that are open 
     * to anyone to join (not game chats)
     * @returns [array] 
    */
    public function publicChats( ) {
        $chatrooms = $this->chatrooms( )->get( );
        $output    = array( );
        foreach( $chatrooms as $chat ) {
            if( (int)$chat->game_id !== 0 ) { continue; }
            $output[] = $chat;
        }
        return $output;
    }
    
    /* user->notifications
     * Gets all of the notifications that have been added 
     * for this user
    */
    public function notifications( ) {
        return $this->has_many('Notification');
    }
    
    /* user->addWin
     * Adds a win to the user's record and saves the model
     * @param {int} increment for the amount to add
    */
    public function addWin( $inc = 1 ) {
        $this->wins  = ( (int)$this->wins ) + (int)$inc;
        $this->games = ( (int)$this->games ) + (int)$inc;
        $this->save( );
    }
    
    /* user->addWin
     * Adds a win to the user's record and saves the model
     * @param {int} increment for the amount to add
    */
    public function addLoss( $inc = 1 ) {
        $this->losses = ( (int)$this->losses ) + (int)$inc;
        $this->games  = ( (int)$this->games ) + (int)$inc;
        $this->save( );
    }
    
    /* user->game 
     * Gets the currrent game this user is involved in
     * @return {boolean|Game} False if no game, the game if there is one
    */
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
    
    /* user->getChatToken
     * Helper function to get the user's token for a given chatroom
     * @param {int} chatroom id number
     * @returns {string} the token associated with the user
    */ 
    public function getChatToken( $chatroom_id ) {
        $test = DB::table('chatroom_user')
                    ->where( 'chatroom_id', '=', $chatroom_id )
                    ->where( 'user_id', '=', $this->id )->first( );
        
        if( $test === null ){
            return false;
        }
        
        return $test->token;
    }
    
    /* user->ping
     * Helper function for the online call. Updates player activity
    */            
    public function ping( ) {
        $date = new DateTime( );
        $this->last_update = $date;
        $this->save( );
    }
    
    /* user->getUpdates 
     * Gets all notifications for the user and returns them
     * in an array
     * @returns [array] the list of updates
    */
    public function getUpdates( ) {
        $notifications = $this->notifications()->get();
        $updates = array( );
        foreach( $notifications as $note ) {
            $updates[] = json_decode( $note->publicJSON(), true );
        }
        return $updates;
    }
    
    /* user->publicJSON
     * Returns a json encoded array of information that is 
     * usable on the client side for communication
     * @returns {string} json ecoded array
    */
    public function publicJSON( ) {
        $public = array( );
        
        $token = sha1( $this->id . $this->username ) . sha1( "fluffy@bunny@2011" );
        $public['username'] = $this->username;
        $public['active']   = false;
        $public['location'] = array( "lat" => $this->latitude, "lng" => $this->longitude );
        
        if( Auth::check() && $this->id === Auth::user()->id ){ 
            $public['active'] = true;
            $public['token'] = $this->encodeToken( $token );
            $public['hb_flag'] = sha1( count( $this->getUpdates( ) ) );
        }
        
        $public['wins'] = $this->wins;
        $public['losses'] = $this->losses;
        
        return json_encode( $public ); 
    }
    
}

?>