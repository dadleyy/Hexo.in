/* ******************************************* *
 * Contact.js                                  *
 * (c) 2013 Danny Hadley under the MIT license *
 * ******************************************* */
(function ( ) { 

    "use strict";
    
    var /* private entry point */
        domReady,
        
        /* form-handlers */
        errored,
        success,
        acallback,
        
        /* form-variables */
        _container,
        _form,
        _validator;
    
    errored = function ( ) { };
    
    acallback = function ( data ) {
        if( !data ) { return false; }   
        data = JSON.parse( data );
        if( !data.success ){ return false; }

        _container
            .stop().animate({ "height" : "66px" }, 600)
            .find("article.success")
            .stop().animate({ "top" : "0px"}, 600);
            
        $(_form).stop().animate({ "opacity" : "0.0" }, 200);
    };
    
    success = function ( hashed ) {
        $.get( "/contact/send", hashed, acallback );
    };
    
    domReady = function( ) {
        _container = $("article.form-container");
        _form = document.getElementById( "#contact-form" );
        _validator = new IV({
            form : _form,
            ecallback : errored,
            callback : success 
        });
    };
        
    $(document).ready( domReady );
    
})( );