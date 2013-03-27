<?php
global $start_time;
$start_time = false;

class Socket_Task {


    public function run($arguments) {
    
        global $start_time;
        
        $current_user = User::find( 1 );
        $current_game = $current_user->game( );
        
        $changed   = false;
        $loops     = 0;
            
        if( $start_time === false ) { 
            $start_time = strtotime( $current_game->updated_at );
        }
        $upcheck = 0;
        
        $opened_at = time( );
        $current_t = time( );
        $output    = array( );
        
        while( !$changed ) {
            
            $upcheck = strtotime( $current_game->updated_at );
            
            if( $loops % 100 == 0 || $loops == 0 ){
                echo "start: " . $start_time . "\r\n";
                echo "current: " . $upcheck . "\r\n";
                echo "\r\n";
            }
            
            $current_t = time( );
            if( $current_t - $opened_at > 5 ) { 
                $output = json_encode( array( 
                    "success"=>true,
                    "code" => "4",
                    "new_time"=>$upcheck,
                    "old_time"=>$start_time
                ) ); 
                echo $output;
                return $output;
            }
          
            if( $upcheck !== $start_time ) {
            
                $changed = true;
                $output  = json_encode( array(
                
                    "code"=>"1",
                    "success"=>true,
                    "looped"=>$loops,
                    "package"=>array("a"=>"b"),
                    "new_time"=>$upcheck,
                    "old_time"=>$start_time
                
                ) );
                echo  $output;
                return $output;
                    
            } else {
                sleep( 0.65 );
                $loops++;
            }
        }
        
        echo json_encode( array( "success" => false ) );
        return;
    }
    
}

?>