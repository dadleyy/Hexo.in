<?php

class Chat_Controller extends Base_Controller {

    public $restful = true;
    
    public function __construct(){
        $this->filter('before', 'auth');
    }
    
    public function post_message( ) {
        $output = array( "csrf" => true, "success" => false );
        $headers = array( 'Content-type' => 'application/json', 'X-Powered-By' => 'Dadleyy' );
        if( Request::forged( ) || !Auth::check( ) ){
            return Response::make( json_encode($output), 200, $headers );   
        }
    }
    
    public function post_send( ){
        $headers = array( 'Content-type' => 'application/json', 'X-Powered-By' => 'Dadleyy' );
        $output = array( "csrf" => true, "success" => false );
        
        if( Request::forged( ) || !Auth::check( ) ){
            $output['msg'] = "noauth";
            return Response::make( json_encode($output), 200, $headers );   
        }
        
        /* save all the input stuff */
        $chat_token = Input::get("chat_token");
        $user_token = Input::get("user_token");
        $message    = Input::get("msg");
        
        $p_usr = Input::get('usr');
        $p_token = $p_usr['token'];
        if( !Auth::user( )->checkToken( $p_token ) ){
            return Response::make( json_encode($output), 200, $headers );
        }
        
        if( $chat_token == null || $user_token == null || $message == null ){
            $output['msg'] = "noinput";
            return Response::make( json_encode($output), 200, $headers );
        }        
        
        /* try getting a chat room from the chat_token */
        $decoded_chat = Tokened::decodeToken( $chat_token );
        $chat_obj     = Chatroom::where( "token" , "=" , $decoded_chat )->first( );
        
        if( $chat_obj == null ){ 
            $output['msg'] = "noobj";
            return Response::make( json_encode($output), 200, $headers );   
        }
        
        /* someone is logged in - get their model */
        $active_user = Auth::user( );    
        $real_user_token    = $active_user->getChatToken( $chat_obj->id );
        $decoded_user_token = Tokened::decodeToken( $user_token );
        
        if( $real_user_token !== $decoded_user_token ) {
            $output['msg'] = "notoken";
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
    
    public function post_open( ) {
        $headers = array( 'Content-type' => 'application/json', 'X-Powered-By' => 'Dadleyy' );
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
        
        if( strlen( $p_name ) < 4 || strlen( $p_name ) > 19 ){
            $output['msg'] = "room name was too long";
            return Response::make( json_encode($output), 200, $headers );
        }  
        
        $exists = Chatroom::where( "name", "=", HTML::entities( $p_name ) )->get( );
        if( count( $exists ) !== 0 ){
            $output['cid'] = $exists[0]->cid( );
            $output['code'] = 6;
            $output["success"] = true;
            return Response::make( json_encode($output), 200, $headers );
        }
        
        //////////////////////////////
        // CHATROOM INITIALIZATION  //
        $chat = new Chatroom;
        
        $chat->game_id = 0;
        $chat->token = sha1( time() . $p_name );
        $chat->name = HTML::entities( $p_name );
        
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
        $headers = array( 'Content-type' => 'application/json', 'X-Powered-By' => 'Dadleyy' );
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
        
        $p_cid  = Chatroom::decodeCID( $p_cid );
        $c_room = Chatroom::find( $p_cid );
        if( !$c_room ){
            return Response::make( json_encode($output), 200, $headers );
        }
        
        if( !$c_room->addUser( Auth::user() ) ){
            $output["code"] = 6;
            $output["name"] = $c_room->name;
            $output["message"] = "already in the room!";
            return Response::make( json_encode($output), 200, $headers );   
        }
        
        $output["success"] = true;
        $output["code"] = 1;
        $output["package"] = json_decode( $c_room->publicJSON( ), true );
        
        return json_encode( $output );
    }

    public function post_leave( ){
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
        
        $p_cid  = Chatroom::decodeCID( $p_cid );
        $c_room = Chatroom::find( $p_cid );
        if( !$c_room ){
            return Response::make( json_encode($output), 200, $headers );
        }
        $c_room->removeUser( $a_usr );
        
        /* remove the room if it is donezoes */
        if( count( $c_room->users( )->get( ) ) === 0 ){
            File::delete( $c_room->chatFile( ) );
            $c_room->delete( );
        }
        
        $output['success'] = true;
        $output['code'] = 1;
        $output['msg'] = "left";
        $output['count'] =  count( $c_room->users( )->get( ) );
        return Response::make( json_encode($output), 200, $headers );
        
    }

}