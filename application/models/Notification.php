<?php

class Notification extends Tokened {

    public static $table = 'notifications';

    public function publicJSON( ) {
        
        $public = array();
        $source = User::find( $this->source_id );
        $item = array();
        
        if( $this->type === "game" ) {
            $game = Game::find( $this->item_id );
            if( $game !== null ) {
                $item = $game->id;
            }
        }
        
        $public['type'] = $this->type;
        $public['source_name'] = $source->username;
        $public['item_id'] = $item;
        
        return json_encode( $public );
        
    }

}

?>