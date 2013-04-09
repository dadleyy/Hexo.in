<?php

class Game_Controller extends Base_Controller {
    
    public $layout = 'layouts.common';
    
    public function __construct(){
        $this->filter('before', 'auth');
    }
    
    public function action_index( ) {
        return Response::error("404"); 
    }
    
    public function action_play( ){
                
        $c_user  = Auth::User( );
        $rq_game = $c_user->game( );
        
        /* make sure there is a game */
        if( $rq_game == false ){
            return Redirect::to( '/home' );
        }
        
        /* make sure our user is in the game */
        if( $rq_game->visitor_id !== $c_user->id && $rq_game->challenger_id !== $c_user->id ){
            return Redirect::to( '/home' );
        }
        
        /* prep the json that gets dumped */
        $game_public = $rq_game->publicJSON( );
        $rq_chat = $rq_game->chatroom( );
                
        $view = View::make('games.main')
                    ->with("title", "play" )
                    ->with("game_js", $game_public )
                    ->with("game", $rq_game )
                    ->with("chat", $rq_chat );
                    
        return $view;
    }
    
    public function action_debug( ){
        return json_encode( User::online() );
    }
    
    public function action_quit( ){
        $current_user = Auth::user( );
        $current_game = $current_user->game( );
    
        if( $current_game !== false ) {
        
            $current_chat = $current_game->chatroom( );
            $notification = Notification::where( "item_id", "=", $current_game->id )->first( );
            if( $notification !== null ) {
                $notification->delete( );
            }
                
            File::delete( $current_game->gameFile( ) );
            File::delete( $current_chat->chatfile( ) );
            DB::table('chatroom_user')->where( 'chatroom_id', '=', $current_chat->id )->delete( );
            
            $current_game->delete( );
            $current_chat->delete( );
        }
        
        return Redirect::to( '/home' );
    }
    
    public function action_move( ) {
        
        $headers = array( 'Content-type' => 'application/json' );
        $output = array( "success" => false );
        
        $current_user = Auth::user( );
        $current_game = $current_user->game( );
    
        if( $current_game == null || Request::forged( ) ){ 
            $output['msg'] = "invalid request";
            return Response::make( json_encode($output), 200, $headers );
        }   
        
        /* get the two tokens */
        $g_token = Input::get("token");
        $r_token = $current_game->token;
      
        if( Game::decodeToken($g_token) !== $r_token ){
            $output['msg'] = "invalid token";
            return Response::make( json_encode($output), 200, $headers );
        }
    
        $t_obj = Input::get("tile");
        if( $t_obj == null || !is_array($t_obj) ){
            $output['msg'] = "invalid parameters for move call";
            return Response::make( json_encode($output), 200, $headers );
        }
        
        /* find out if this is the correct turn */ 
        if( !$current_game->checkTurn( $current_user ) ){
            $output['msg'] = "wrong turn";
            return Response::make( json_encode($output), 200, $headers );
        }
            
        
        $t_value = $t_obj['value'];
        $t_state = $t_obj['state'];
        
        $result = $current_game->moveTile( $t_value, $t_state );
        if( $result === false ){
            $output['success'] = false;
            $output['msg'] = 'unable to move turn';
            return Response::make( json_encode($output), 200, $headers );
        }
        
        $output['turn'] = $current_game->turn;
        $output['update'] = $result;
        $output['success'] = true; 
        
        $current_game->updateFlag( );
        
        return Response::make( json_encode($output), 200, $headers );
    }
    
    public function action_socket( ) {
                
        $current_user = Auth::user( );
        $current_game = $current_user->game( );
        $output = array( "success" => false );
        $headers = array( 'Content-type' => 'application/json' );
        
        if( Request::forged( ) ) {
            return Response::make( json_encode( array("success"=>false,"csrf"=>true) ), 204, $headers );
        }
                
        if( $current_game == null || !Input::get("token") ){ 
            return Response::make( json_encode( array("success"=>false,"csrf"=>true) ), 204, $headers );
        }   
        
        $game_token = $current_game->token; 
        $param_token = Input::get("token");
        $decoded_param = $current_game->decodeTokenArray( $param_token );
        
        if( $game_token !== $decoded_param ) {
            return Response::make( json_encode( array("success"=>false,"csrf"=>true) ), 204, $headers );
        }
        
        /* at this point - the game is definitely legitimate */
        $current_user->ping( );
        
        /* ************************************************** *
         * SOCKET STATE - raw update required                 *
         * The client has specifically asked for an update    *
         * ************************************************** */
        if( Input::get("raw") && Input::get("raw") == "raw" ) {  
            $package = json_decode( $current_game->publicJSON( ), true );        
            $output = array( 
                "success" => true,
                "code" => 1,
                "type" => "game",
                "new_flag" => $current_game->getFlag( ),
                "request" => Request::forged( ),
                "package" => $package
            );
            return Response::make( json_encode($output), 200, $headers );
        }
        
        
        $changed = false;
        $loops   = 0;
        $s_time  = time( );
        $c_time  = time( );
        
        $original_flag = $current_game->getFlag( );
        
        while ( !$changed ) {
            $c_time = time( );
            
            /* ************************************************** *
             * SOCKET STATE - game over                           *
             * The game is over if the game reference is null, or *
             * the file for it doesnt exist anymore               *
             * ************************************************** */
            if( $current_game == null || $current_game->isOver( ) ) {
                $output = array(
                    "success" => true,
                    "code" => 4,
                    "new_flag" => $current_game->getFlag( ),
                    "type" => "game"
                );
                return Response::make( json_encode($output), 200, $headers );
            }
            
            /* ************************************************** *
             * SOCKET STATE - socket timeout                      *
             * This socket loop has been going on for too long,   *
             * it is time to let the client know to make a new rq *
             * ************************************************** */
            if( ($c_time - $s_time) >  10 || $loops > 10000 ){     
                $output = array(
                    "success" => true,
                    "code" => 2,
                    "type" => "game",
                    "ulup" => $current_user->last_update
                );
                return Response::make( json_encode($output), 200, $headers );
            }
            
            /* ************************************************** *
             * SOCKET STATE - updated needed                      *
             * There has been a change since the last time the    *
             * socket hit this iteration, send the update to the  *
             * client.                                            *
             * ************************************************** */
            if( $current_game->getFlag( ) !== $original_flag ){
                $package = json_decode( $current_game->publicJSON( ), true );
                $output = array( 
                    "success" => true,
                    "code" => 1,
                    "type" => "game",
                    "new_flag" => $current_game->getFlag( ),
                    "package" => $package,
                    "request" => Request::forged( )
                );
                return Response::make( json_encode($output), 200, array() );
            }
            
            /* loop and sleep */
            $loops++;
            time_nanosleep( 0, 9000000 );
        }
        
        return Response::make( json_encode( array("success"=>false,"csrf"=>true) ), 404, array() );
    }
    
    public function action_start( $specific = -1 ){
        
        /* try to find the game the current user is playing (if any) */
        $current_user = Auth::user( );
        $current_game = $current_user->game( );
        if( $current_game !== false ) {
            return Redirect::to( '/game/play' );
        }
        
        /* check to see if this was an invite */
        if( $specific !== -1 ) {
            $challenged = Game::find( $specific );
            
            if( $challenged !== null && $challenged->target_id === $current_user->id ){
                
                $challenged->addUser( $current_user );
                
                $notification = Notification::where( "item_id", "=", $challenged->id )->first( );
                if( $notification !== null ) {
                    $notification->delete( );
                }
                
                return Redirect::to( '/game/play' );
            }
        }
        
        /* At this point, there is no current game.
         * The next step is to either join or make a
         * brand new one
        */
        
        /* try to find an open game */
        $open = Game::getOpen( );
        if( $open !== false ) { 
            $open->addUser( $current_user );
            return Redirect::to( '/game/play' );
        }
        
        $game = Game::open( $current_user );
        return Redirect::to( '/game/play' );
        
    }
    
    public function action_challenge( ) {
        
        $output = array( 'success' => false, 'code' => 4 );
        $headers = array( 'Content-type' => 'application/json' );
        
        if( Request::forged( ) ) {
            return Response::make( json_encode($output), 200, $headers );
        }
        
        $p_target = Input::get('target');
        $p_token  = Input::get('token');
        
        /* check the tokens */
        $current_user = Auth::user( );
        $r_token = sha1( $current_user->id . $current_user->username );
        $d_token = User::decodeToken( $p_token );
        
        if( $r_token !== $d_token ){
            $output['msg'] = 'invalid input';
            return Response::make( json_encode($output), 200, $headers );
        }
        /* get the user */
        $target = User::where( "username", "=", $p_target )->first( );
        if( $target == null ){
            $output['msg'] = 'opponent not found';
            return Response::make( json_encode($output), 200, $headers );
        }
        
        if( $current_user->game() !== false ){
            $output['msg'] = 'you are already in a game';
            return Response::make( json_encode($output), 200, $headers );
        }
            
        $game = Game::open( $current_user, $target->id );
        
        $notification = new Notification( );
        $notification->source_id = $current_user->id;
        $notification->user_id = $target->id;
        $notification->item_id = $game->id;
        $notification->type = "game";
        $notification->save( );
                
        $output['success'] = true;
        $output['code'] = 100;
        return Response::make( json_encode($output), 200, $headers );
    }
    
}