<?php

class Contact_Controller extends Base_Controller {
   
    public function action_index( ) {
        $view = View::make('layouts.common')
                    ->nest("content", "home.contact")
                    ->with("title", "contact");
        return $view; 
    }
    
    public function action_send( ) {
        
        $email = Input::get("email");
        $message = Input::get("message");
        $name = Input::get("name");
        
        if( !$email || !$message || !$name || !filter_var( $email, FILTER_VALIDATE_EMAIL ) ){
            return json_encode( array( "message" => "invalid parameters", "success" => false ) );
        }
        
        $subject  = "Hexo.in Feeback";
        $template = $message;
        $template = str_replace("\n", "\r\n", $template);
        
        $headers  = 'To: info@hexo.in'."\r\n";
        $headers .= 'Reply-To: ' . $name . '<'. $email .'>' . "\r\n"; 
        $headers .= 'Return-Path: ' . $name . '<'. $email .'>' . "\r\n"; 
        $headers .= 'From: ' . $name . '<'. $email .'>' . "\r\n";
        
        $returnpath = "-f" . $email;
        
        $success = mail( 'info@hexo.in', $subject, $template, $headers, $returnpath );
        
        if( $success ) {
            return json_encode( array( "message" => "okay", "success" => true ) );
        } else {
            return json_encode( array( "message" => "invalid parameters", "success" => false ) );
        }
    }

}