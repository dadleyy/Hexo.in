<?php

class Chat_Controller extends Base_Controller {

    public $restful = true;
    
    public function __construct(){
        $this->filter('before', 'auth');
    }
    
    public function post_socket( ) {
    
        $output = array( "code" => 4, "success" => false, "type" => "chat" );
        $headers = array( 'Content-type' => 'application/json' );
            
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
        $extras     = ( is_array( Input::get("extras") ) ) ? Input::get("extras") : array( );
        $chat_token = ( isset( $extras['chat_token'] ) ) ? $extras['chat_token']  : "";
        $user_token = ( isset( $extras['user_token'] ) ) ? $extras['user_token']  : "";
                
        if( $chat_token == null || $user_token == null ){
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
        
        if( $real_user_token !== $decoded_user_token ) {
            $output['msg'] = "badtoken";
            $output['decoded'] = $decoded_user_token;
            $output['original'] = $real_user_token;
            return Response::make( json_encode($output), 200, $headers ); 
        }
        
        /* get the flag as it is now */
        $original_flag = $chat_obj->getFlag( );
    
        $changed = false;
        $loops = 0;
        
        /* socket timeout variables */
        $s_time = time( );
        $c_time = time( );
        
        /* ************************************************** *
         * SOCKET STATE - raw update required                 *
         * The client has specifically asked for an update    *
         * ************************************************** */
        if( Input::get("raw") && Input::get("raw") == "raw" ) {  
            $package = json_decode( $chat_obj->publicJSON( ), true );
            $output["success"]  = true;
            $output["code"]     = 1;
            $output["flag"]     = $chat_obj->getFlag( );
            $output["package"]  = $package;
            return Response::make( json_encode($output), 200, $headers );
        }
        
        while ( !$changed ) { 
                    
            $c_time = time( );
            
            /* ************************************************** *
             * SOCKET STATE - chat closed                         *
             * ************************************************** */
            if( $chat_obj == null || $chat_obj->isClosed( ) ) {
                $output["success"]  = false;
                $output["flag"]     = "dead";
                $output["code"]     = 4;
                return Response::make( json_encode($output), 200, $headers );
            }
            
            /* ************************************************** *
             * SOCKET STATE - socket timeout                      *
             * This socket loop has been going on for too long,   *
             * it is time to let the client know to make a new rq *
             * ************************************************** */
            if( ($c_time - $s_time) > 10 || $loops > 10000 ) {
                $output["success"] = true;
                $output["flag"]    = "to";
                $output["code"]    = 2;
                return Response::make( json_encode($output), 200, $headers );
            }
            
            /* ************************************************** *
             * SOCKET STATE - updated needed                      *
             * There has been a change since the last time the    *
             * socket hit this iteration, send the update to the  *
             * client.                                            *
             * ************************************************** */
            if( $chat_obj->getFlag( ) !== $original_flag ) {
                $package = json_decode( $chat_obj->publicJSON( ), true );
                $output["success"]  = true;
                $output["code"]     = 1;
                $output["flag"]     = $chat_obj->getFlag( );
                $output["package"]  = $package;
                return Response::make( json_encode($output), 200, $headers );
            }
              
            $loops++;
            time_nanosleep( 0, 9000000 );
        
        }
        
        $output = array(
            "success" => true,
            "code" => 2
        );
        return Response::make( json_encode($output), 200, array() );
        
    }
    
    public function post_message( ) {
        $output = array( "csrf" => true, "success" => false );
        $headers = array( 'Content-type' => 'application/json' );
        if( Request::forged( ) || !Auth::check( ) ){
            return Response::make( json_encode($output), 200, $headers );   
        }
    }
    
    public function post_send( ){
        $output = array( "csrf" => true, "success" => false );
        $headers = array( 'Content-type' => 'application/json' );
        
        if( Request::forged( ) || !Auth::check( ) ){
            return Response::make( json_encode($output), 200, $headers );   
        }
        
        /* save all the input stuff */
        $chat_token = Input::get("chat_token");
        $user_token = Input::get("user_token");
        $message    = Input::get("msg");
        
        if( $chat_token == null || $user_token == null || $message == null ){
            return Response::make( json_encode($output), 200, $headers );
        }        
        
        /* try getting a chat room from the chat_token */
        $decoded_chat = Tokened::decodeToken( $chat_token );
        $chat_obj     = Chatroom::where( "token" , "=" , $decoded_chat )->first( );
        
        if( $chat_obj == null ){ 
            return Response::make( json_encode($output), 200, $headers );   
        }
        
        /* someone is logged in - get their model */
        $active_user = Auth::user( );    
        $real_user_token    = $active_user->getChatToken( $chat_obj->id );
        $decoded_user_token = Tokened::decodeToken( $user_token );
        
        if( $real_user_token !== $decoded_user_token ) {
            return Response::make( json_encode($output), 200, $headers ); 
        }
        
        $chat_contents = File::get( $chat_obj->chatFile( ) );
        $chat_info = json_decode( $chat_contents , true );
        
        $chat_info['messages'][] = array( 
            "user" => $active_user->username,
            "message" => HTML::entities( $message ) 
        );
        
        File::put( $chat_obj->chatFile( ), json_encode($chat_info) );
        $chat_obj->updateFlag( );
        
        $output = array(
            "success" => true,
            "code" => 1
        );
        
        return Response::make( json_encode($output), 200, $headers );
    }

    public function post_state( ) {
        $headers = array( 'Content-type' => 'application/json' );
        $output = array( "success" => false, "code" => 4, "type" => "online" );
        
        if( Request::forged( ) ) {
            return Response::make( json_encode($output), 200, $headers );
        }
        
        $p_usr = Input::get('usr');
        $p_token = $p_usr['token'];
        if( !Auth::user( )->checkToken( $p_token ) ){
            return Response::make( json_encode($output), 200, $headers );
        }
        
        $output['success'] = true;
        $output['code'] = 2;
        
        $s_online = count( User::online( ) );
        $r_open   = count( Chatroom::publicRooms( ) );
        Auth::user( )->ping( );
    
        /* ************************************************** *
         * SOCKET STATE - raw update required                 *
         * The client has specifically asked for an update    *
         * ************************************************** */
        if( Input::get("raw") && Input::get("raw") == "raw" ) {  
            $output['code'] = 1;
            
            $output['package'] = array(
                "users" => User::online( ),
                "rooms" => Chatroom::publicRooms( ) 
            );
            
            return Response::make( json_encode($output), 200, $headers );
        }
                
        $s_time = time( );
        $c_time = time( );
        
        $loops   = 0;
        $changed = false;
        
        while( !$changed ) {
            
            $c_time = time( );
            
             /* ************************************************** *
             * SOCKET STATE - socket timeout                      *
             * This socket loop has been going on for too long,   *
             * it is time to let the client know to make a new rq *
             * ************************************************** */
            if( $c_time - $s_time > 10 || $loops > 1000 ) {
                return Response::make( json_encode($output), 200, $headers );
            }
            
            /* ************************************************** *
             * SOCKET STATE - updated needed                      *
             * There has been a change since the last time the    *
             * socket hit this iteration, send the update to the  *
             * client.                                            *
             * ************************************************** */
             if( (count( User::online( ) ) !== $s_online) || (count( Chatroom::publicRooms( ) ) !== $r_open ) ){
                $output['code'] = 1;
                $output['package'] = array(
                    "users" => User::online( ),
                    "rooms" => Chatroom::publicRooms( ) 
                );
                return Response::make( json_encode($output), 200, $headers );
             }
                    
            $loops++;
            time_nanosleep( 0, 9000000 );
        }

        
        return Response::make( json_encode($output), 200, $headers );
    }
    
    public function post_open( ) {
        $headers = array( 'Content-type' => 'application/json' );
        $output = array( "success" => "false", "code" => 4 );
        
        if( Request::forged( ) || !Auth::check( ) ){
            return Response::make( json_encode($output), 200, $headers );
        }
        
        $a_usr = Auth::user();
        $p_usr = Input::get("usr");
        $p_name = Input::get("name");
        
        if( !is_array( $p_usr ) ){
            return Response::make( json_encode($output), 200, $headers );
        }
        
        $p_token = $p_usr['token'];
        if( !$a_usr->checkToken( $p_token ) ){
            return Response::make( json_encode($output), 200, $headers );
        }
        
        //////////////////////////////
        // CHATROOM INITIALIZATION  //
        $chat = new Chatroom;
        
        $chat->game_id = 0;
        $chat->token = sha1( time() . $p_name );
        $chat->name = $p_name;
        
        $chat->createJSON( );
        $chat->save( );
        
        /* add the current user to that chatroom */
        $date = new DateTime( );
        DB::table('chatroom_user')->insert( array(
            'chatroom_id' => $chat->id,
            'user_id'     => Auth::user( )->id,
            'token'       => sha1( $chat->id . Auth::user( )->id ),
            'created_at'  => $date,
            'updated_at'  => $date
        ));
        
        
        $output["success"] = true;
        $output["code"] = 1;
        $output["package"] = json_decode( $chat->publicJSON( ), true );
        return json_encode( $output );
            
    }
    
    public function post_join( ){
        $headers = array( 'Content-type' => 'application/json' );
        $output = array( "success" => "false", "code" => 4 );
        
        if( Request::forged( ) || !Auth::check( ) ){
            return Response::make( json_encode($output), 200, $headers );
        }
        
        $a_usr = Auth::user();
        $p_usr = Input::get("usr");
        $p_cid = Input::get("cid");
        
        if( !is_array( $p_usr ) || !$p_cid ){
            return Response::make( json_encode($output), 200, $headers );
        }
        
        $p_token = $p_usr['token'];
        if( !$a_usr->checkToken( $p_token ) ){
            return Response::make( json_encode($output), 200, $headers );
        }
        
        $c_room = Chatroom::find( $p_cid );
        if( !$c_room ){
            return Response::make( json_encode($output), 200, $headers );
        }
        
        $c_room->addUser( Auth::user() );
        
        $output["success"] = true;
        $output["code"] = 1;
        return json_encode( $output );
    }

}