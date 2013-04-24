<?php

class Socket_Controller extends Base_Controller {

    public $restful = true;
    
    public function __construct(){
        $this->filter('before', 'auth');
    }
    
    public function post_chat( ) {
    
        $headers = array( 'Content-type' => 'application/json', 'X-Powered-By' => 'Dadleyy' );
        $output = array( "code" => 4, "success" => false, "type" => "chat-socket" );
            
        if( Request::forged( ) || !Auth::check( ) ){
            $output['msg'] = "nouser";
            return Response::make( json_encode($output), 200, $headers );
        }
        
        if( !Input::get('usr') ){
            $output['msg'] = "nouser";
            return Response::make( json_encode($output), 200, $headers );
        }
        
        $p_usr = Input::get('usr');
        $p_token = $p_usr['token'];
        if( !Auth::user( )->checkToken( $p_token ) ){
            return Response::make( json_encode($output), 200, $headers );
        }
        
        /* save all the input stuff */
        $flag       = Input::get("flag");
        $extras     = ( is_array( Input::get("extras") ) ) ? Input::get("extras") : array( );
        $chat_token = ( isset( $extras['chat_token'] ) ) ? $extras['chat_token']  : "";
        $user_token = ( isset( $extras['user_token'] ) ) ? $extras['user_token']  : "";
                
        if( $chat_token == null || $user_token == null || $flag == null ){
            $output['msg'] = "notoken";
            return Response::make( json_encode($output), 200, $headers );
        }
    
        /* try getting a chat room from the chat_token */
        $decoded_chat = Tokened::decodeToken( $chat_token );
        $chat_obj     = Chatroom::where( "token" , "=" , $decoded_chat )->first( );
        
        if( $chat_obj == null ){ 
            $output['msg'] = "nochat";
            return Response::make( json_encode($output), 200, $headers );   
        }
    
        /* someone is logged in - get their model */
        $active_user        = Auth::user( );    
        $real_user_token    = $active_user->getChatToken( $chat_obj->id );
        $decoded_user_token = Tokened::decodeToken( $user_token );
        if( $real_user_token == false ) {
            $output['success'] = false;
            $output['code'] = 4;
            $output['flag'] = 'dead';
            return Response::make( json_encode($output), 200, $headers ); 
        }
        
        if( $real_user_token !== $decoded_user_token ) {
            $output['success'] = false;
            $output['code'] = 4;
            $output['msg'] = "badtoken";
            return Response::make( json_encode($output), 200, $headers ); 
        }
        
        // * ************************************************** *
        // * SOCKET STATE - raw update required                 *
        // * The client has specifically asked for an update    *
        // * ************************************************** //
        if( Input::get("raw") && Input::get("raw") == "raw" ) {  
            $package = json_decode( $chat_obj->publicJSON( ), true );
            $output["success"]  = true;
            $output["code"]     = 1;
            $output["flag"]     = $chat_obj->getFlag( );
            $output["package"]  = $package;
            return Response::make( json_encode($output), 200, $headers );
        }
        
        //* ************************************************** *
        // * SOCKET STATE - client is behind the server        *
        // * The client needs to be updated                    *
        // * ************************************************* //
        if( $flag != $chat_obj->getFlag( ) ){
            $package = json_decode( $chat_obj->publicJSON( ), true );
            $output["success"]  = true;
            $output["code"]     = 1;
            $output["flag"]     = $chat_obj->getFlag( );
            $output["package"]  = $package;
            return Response::make( json_encode($output), 200, $headers );
        } 
        
        // * ************************************************* *
        // * SOCKET STATE - nothing to update                  *
        // * Send back a minimum amount of data                *
        // * ************************************************* //
        else {
            $output["success"] = true;
            $output["code"]    = 2;
            return Response::make( json_encode($output), 200, $headers );
        }
    
    }
    
    public function post_heartbeat( ) {
        $headers = array( 'Content-type' => 'application/json', 'X-Powered-By' => 'Dadleyy' );
        $output = array( "success" => false, "code" => 4, "type"=>"heartbeat" );
        
        if( Request::forged( ) ) {
            return Response::make( json_encode($output), 200, $headers );
        }
        
        /* check for lat/long updates */
        if( Input::get("lat") !== null && Input::get("lng") !== null ){
            $lat = floatval( Input::get("lat") );
            $lon = floatval( Input::get("lng") );
            
            $a_user = Auth::user( );
            $a_user->latitude = $lat;
            $a_user->longitude = $lon;
            $a_user->save( );
            
            $output['success'] = true;
            $output['code'] = 2;
            return Response::make( json_encode($output), 200, $headers );
        }
        
        $p_usr = Input::get('usr');
        $flag  = Input::get("flag");
        $p_token = $p_usr['token'];
        if( !Auth::user( )->checkToken( $p_token ) || $flag == null ){
            $output['msg'] = 'bad_token';
            $output['success'] = false;
            $output['code'] = 4;
            return Response::make( json_encode($output), 200, $headers );
        }
        
        /* ************************************************** *
         * SOCKET STATE - raw update required                 *
         * The client has specifically asked for an update    *
         * ************************************************** */
        if( Input::get("raw") && Input::get("raw") == "raw" ) {  
            $output['success'] = true;
            $output['code'] = 1;
            $output['package'] = Auth::user( )->getUpdates( );
            return Response::make( json_encode($output), 200, $headers );
        }
        
        $current_user = Auth::user( );    
        $s_updates = count( $current_user->getUpdates( ) );
        
        //* ************************************************** *
        // * SOCKET STATE - client is behind the server        *
        // * The client needs to be updated                    *
        // * ************************************************* //
        if( $flag != sha1( $s_updates ) ){
            $output['success'] = true;
            $output['code'] = 1;
            $output['flag'] = sha1( $s_updates );
            $output['package'] = $current_user->getUpdates( );
            return Response::make( json_encode($output), 200, $headers );
        } 
        
        // * ************************************************* *
        // * SOCKET STATE - nothing to update                  *
        // * Send back a minimum amount of data                *
        // * ************************************************* //
        else {
            $output["success"] = true;
            $output["code"]    = 2;
            return Response::make( json_encode($output), 200, $headers );
        }
        
    }
    
    public function post_online( ) {
    
        $headers = array( 'Content-type' => 'application/json', 'X-Powered-By' => 'Dadleyy' );
        $output = array( "success" => false, "code" => 4, "type" => "online" );
        
        if( Request::forged( ) ) {
            return Response::make( json_encode($output), 200, $headers );
        }
        
        $flag  = Input::get("flag");
        $p_usr = Input::get('usr');
        $p_token = $p_usr['token'];
        
        if( !Auth::user( )->checkToken( $p_token ) ){
            return Response::make( json_encode($output), 200, $headers );
        }
            
        $s_online = count( User::online( ) );
        $r_open   = count( Chatroom::publicRooms( ) );
        Auth::user( )->ping( );
    
        //* ************************************************** *
        // * SOCKET STATE - raw update required                *
        // * The client has specifically asked for an update   *
        // * ************************************************* //
        if( Input::get("raw") && Input::get("raw") == "raw" ) {  
            $output['success'] = true;
            $output['code'] = 1;
            $output['package'] = array("users" => User::online( ),"rooms" => Chatroom::publicRooms( )); 
            return Response::make( json_encode($output), 200, $headers );
        }
        
        //* ************************************************** *
        // * SOCKET STATE - client is behind the server        *
        // * The client needs to be updated                    *
        // * ************************************************* //
        if( $flag != sha1( $s_online . $r_open ) ){
            $output['success'] = true;
            $output['code'] = 1;
            $output['package'] = array("users" => User::online( ),"rooms" => Chatroom::publicRooms( ) ); 
            $output['flag'] = sha1( $s_online . $r_open );
            return Response::make( json_encode($output), 200, $headers );
        } 
        
        // * ************************************************* *
        // * SOCKET STATE - nothing to update                  *
        // * Send back a minimum amount of data                *
        // * ************************************************* //
        else {
            $output['flag'] = $flag;
            $output['u_onine'] = $s_online;
            $output['r_open'] = $r_open;
            $output['success'] = true;
            $output['code'] = 2;
            return Response::make( json_encode($output), 200, $headers );
        }
    
    }
    
    public function post_game( ) {
        $current_user = Auth::user( );
        $current_game = $current_user->game( );
        $headers = array( 'Content-type' => 'application/json', 'X-Powered-By' => 'Dadleyy' );
        $output = array( "success" => false, "code" => 4, "type"=>"game" );
        
        if( Request::forged( ) ) {
            $output['success'] = false;
            $output['code'] = 4;
            return Response::make( json_encode( $output ), 200, $headers );
        }
                
        if( $current_game == null || !Input::get("token") ){ 
            $output['success'] = false;
            $output['code'] = 4;
            return Response::make( json_encode( $output ), 200, $headers );
        }   
        
        $game_token  = $current_game->token; 
        $param_token = Input::get("token");
        $flag        = Input::get("flag");
        $decoded_param = $current_game->decodeTokenArray( $param_token );
        
        if( $game_token !== $decoded_param || $flag === null ) {
            $output['success'] = false;
            $output['code'] = 4;
            return Response::make( json_encode( $output ), 200, $headers );
        }
         
        /* ************************************************** *
         * SOCKET STATE - raw update required                 *
         * The client has specifically asked for an update    *
         * ************************************************** */
        if( Input::get("raw") && Input::get("raw") == "raw" ) {  
            $package = json_decode( $current_game->publicJSON( ), true );        
            $output["success"] = true;
            $output["code"] = 1;
            $output["flag"] = $current_game->getFlag( );
            $output["package"] = $package;
            return Response::make( json_encode($output), 200, $headers );
        }
        
        //* ************************************************** *
        // * SOCKET STATE - client is behind the server        *
        // * The client needs to be updated                    *
        // * ************************************************* //
        if( $flag != $current_game->getFlag( ) ){
            $package = json_decode( $current_game->publicJSON( ), true );        
            $output["success"] = true;
            $output["code"] = 1;
            $output["flag"] = $current_game->getFlag( );
            $output["package"] = $package;
            return Response::make( json_encode($output), 200, $headers );
        } 
        
        // * ************************************************* *
        // * SOCKET STATE - nothing to update                  *
        // * Send back a minimum amount of data                *
        // * ************************************************* //
        else {
            $output["success"] = true;
            $output["code"]    = 2;
            return Response::make( json_encode($output), 200, $headers );
        }
         
    }
    
    public function get_debug( ) {
        $headers = array( 'Content-type' => 'application/json', 'X-Powered-By' => 'Dadleyy' );
        $output = array( "success" => false, "code" => 4, "type" => "debug" );
        if( Auth::user( )->privileges != 1 )
            return Response::make( json_encode($output), 200, $headers );
            
        $users = User::all( );
        $times = array( );
        $current_time = time( );
        
        foreach( $users as $user ) {
            $last_time = strtotime( $user->last_update );
            $time_diff = $current_time - $last_time;
            $times[] = array( 
                "name" => $user->username,
                "lup" => $user->last_update,
                "diff" => $time_diff
            );    
        }
        
        $output['package'] = $times;
        $output['success'] = true;
        $output['code']    = 1;
        return Response::make( json_encode($output), 200, $headers );
        
    }
    
}

?>