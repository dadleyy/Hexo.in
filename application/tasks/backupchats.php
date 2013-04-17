<?php
define( "NL", "\r\n" );
class Backupchats_Task {
    
    public function run ( $arguments = array() ){
        echo "====================".NL;
        echo "Running chat backup".NL.NL;
        foreach( Chatroom::all( ) as $chatroom ){
            echo "moving: " . $chatroom->name.NL;
            
            $chat_location = $chatroom->chatFile( );
            $chat_contents = json_decode( File::get( $chat_location ), true );
            $messages_ref  = $chat_contents['messages'];
            /* clear it out */
            $chat_contents['messages'] = array( );
            File::put( $chat_location, json_encode( $chat_contents ) );
            
            $f_name = basename($chat_location,".json");
            $b_path = path('storage').'backups/chats/';
            $b_name = $b_path.$f_name.'-'.time().".json";
            echo "original: " . $chat_location.NL;
            echo "backup: " . $b_name.NL;
            
            $chat_contents['room_name'] = $chatroom->name;
            $chat_contents['messages'] = $messages_ref;
            File::put( $b_name, json_encode($chat_contents) );
        }
        
        $subject  = "Hexo.in Cronjob";
        $template = "Ran the backup chat cron job";
        $template = str_replace("\n", "\r\n", $template);
        
        $headers  = 'To: info@hexo.in'."\r\n";
        $headers .= 'Reply-To: Danny Hadley <info@hexo.in>' . "\r\n"; 
        $headers .= 'Return-Path: Danny Hadley <info@hexo.in>' . "\r\n"; 
        $headers .= 'From:  Danny Hadley <info@hexo.in>' . "\r\n";
        
        $returnpath = "-f" . "info@hexo.in";
        
        $success = mail( 'info@hexo.in', 'info@hexo.in', $template, $headers, $returnpath );
        
        echo NL."finished loading".NL;
        echo "====================".NL;
    }

}

?>