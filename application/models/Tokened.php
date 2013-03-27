<?php

class Tokened extends Eloquent {
    
    protected function shuffleTokenArray( $tokenArr ){
        if ( !is_array($tokenArr) ){ return $tokenArr; }  
        
        $keys = array_keys( $tokenArr );
        shuffle( $keys ); 
        
        $random = array( ); 
        foreach ( $keys as $key ) { 
            $random[$key] = $tokenArr[$key]; 
        }
        
        return $random; 
    }
    
    public static function decodeToken( $token ) {
        if ( !is_array($token) ){ return $token; } 
        
        $decoded_array  = array();
        $decoded_token  = "";
        $returned_array = array(); 
        
        foreach( $token as $index=>$value ){
            $based = base_convert( $index, 16, 10);
            $order = (int)substr( $based, 0, 1 );
            $decoded_array[ $order - 1 ] = $value;
        }
        for( $i = 0; $i < count($decoded_array); $i++ ) {
            $decoded_token = $decoded_token . (string)$decoded_array[$i];
        }
        $returned_array['decoded_array'] = $decoded_array;
        $returned_array['decoded_token'] = $decoded_token;
        
        
        return $decoded_token;
    }
    
    public function decodeTokenArray( $tokenArr ){
        if ( !is_array($tokenArr) ){ return $tokenArr; } 
        
        $decoded_array  = array();
        $decoded_token  = "";
        $returned_array = array(); 
        
        foreach( $tokenArr as $index=>$value ){
            $based = base_convert( $index, 16, 10);
            $order = (int)substr( $based, 0, 1 );
            $decoded_array[ $order - 1 ] = $value;
        }
        for( $i = 0; $i < count($decoded_array); $i++ ) {
            $decoded_token = $decoded_token . (string)$decoded_array[$i];
        }
        $returned_array['decoded_array'] = $decoded_array;
        $returned_array['decoded_token'] = $decoded_token;
        
        
        return $decoded_token;
    }

    
    protected function encodeToken( $token ) {
        /* the information to be sent out */
        $key_pieces = array( );
        $key_len = strlen( $token );
        $inc = $key_len / 8;
        
        /* chop up the token */
        for( $i = 1; $i < 9; $i++ ){
            $start = ( ($i - 1 ) * $inc);
            
            $random_after = time() * rand( 100000000, 400000000 );
            $first_digit = ( $i ) + "";
            
            $number = (int) ( $first_digit . "" ) . ( $random_after . "" );
            
            $based   = base_convert( $number, 10, 16 );
        
            // $unbased = base_convert( $based, 16, 10 );
            // $index = (int)substr( $unbased, 0, 1 );
            
            $key_pieces[ $based ] = substr( $token, $start, $inc );
        }
        
        return $this->shuffleTokenArray( $key_pieces );
    }
    
}

?>