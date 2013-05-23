<?php

class Deletetutorialgames_Task {

    public function run ( $arguments = array() ){
        
        $tutorials = Game::where("is_tutorial","=",true)->get( );
        foreach( $tutorials as $tutorial ){
            echo "deleting: ".$tutorial->id."\n";
            $chat = $tutorial->chatroom( );
            
            $chat_file = $chat->chatFile( );
            $game_file = $tutorial->gameFile( );
            
            File::delete( $chat_file );
            File::delete( $game_file );
            
            $chat->delete( );
            $tutorial->delete( );
        }
        
    }

}

?>