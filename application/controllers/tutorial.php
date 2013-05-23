<?php

class Tutorial_Controller extends Base_Controller {
    
    public function action_index( ) {
        
        $game = Game::where("is_tutorial","=",true)->first( );
        
        $view = View::make('home.tutorial')
                    ->with("title","tutorial")            
                    ->with("game_js", $game->publicJSON( ) )
                    ->with("game", $game );
                    
        return $view;
        
    }
    
    public function action_debug( ) {
        
        $headers = array( 'Content-type' => 'application/json', 'X-Powered-By' => 'Dadleyy' );
        $output = array( "success" => false, "code" => 4, "type" => "debug" );
        
        if( !Auth::check( ) )
            Redirect::to("/tutorial");
        
        if( Auth::user( )->privileges != 1 )
            return Response::make( json_encode($output), 200, $headers );
        
        return Response::make( json_encode($output), 200, $headers );
    }
    
    
}

?>