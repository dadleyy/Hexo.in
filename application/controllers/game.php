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
        
        if( $current_game === false ) {
            return Redirect::to( '/home' );
        }
    
        /* if there was an invite associated with this game - delete it */
        $notification = Notification::where( "item_id", "=", $current_game->id )->first( );
        if( $notification !== null ) {
            $notification->delete( );
        }
        
        if( $current_game->visitor() !== null ) {
            $current_user->addLoss( );
            /* if the quitter was the visitor - give the win to the challenger */
            if( $current_game->visitor()->id === $current_user->id ){
                $current_game->challenger()->addWin( );
            }
            /* if not - give the win to the visitor */
            else {
                $current_game->visitor()->addWin( );
            }
        
        }
        
        /* flag this game as over */
        $current_game->updateFlag( "dead", 3 );
        $current_game->complete = true;
        $current_game->save( );
    
        return Redirect::to( '/home' );
    }
    
    public function action_move( ) {
        
        $headers = array( 'Content-type' => 'application/json', 'X-Powered-By' => 'Dadleyy' );
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
    
    public function action_start( $specific = -1 ){
        
        /* try to find the game the current user is playing (if any) */
        $current_user = Auth::user( );
        $current_game = $current_user->game( );

        // Chatroom::find(1)->removeUser( $current_user );
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
    
    public function action_reset( ) {
        $output = array( 'success' => false, 'code' => 4 );
        $headers = array( 'Content-type' => 'application/json', 'X-Powered-By' => 'Dadleyy' );
        
        if( Request::forged( ) ) {
            return Response::make( json_encode($output), 200, $headers );
        }
        
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
        
        $current_game->restart( );
        
        $output['success'] = true;
        $output['code'] = 1;        
        return Response::make( json_encode($output), 200, $headers );
    }
    
    public function action_challenge( ) {
        $output = array( 'success' => false, 'code' => 4 );
        $headers = array( 'Content-type' => 'application/json', 'X-Powered-By' => 'Dadleyy' );
        
        if( Request::forged( ) ) {
            return Response::make( json_encode($output), 200, $headers );
        }
        
        $p_target = Input::get('target');
        $p_token  = Input::get('token');
        
        /* check the tokens */
        $current_user = Auth::user( );       
        if( !$current_user->checkToken( $p_token ) ){
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