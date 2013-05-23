/* ******************************************* *
 * Contact.js                                  *
 * (c) 2013 Danny Hadley under the MIT license *
 * ******************************************* */
(function ( ) { 

"use strict";

var // entry point
    domEntry,
    // private vars
    _csrf = null,
    _sent = false,
    // form-functions
    _formSubmit,
    _receiver,
    // form-variables
    _$container,
    _form,
    _validator;
    
_receiver = function ( data ) {
    if( !data || !data.success ) { return false; }
    
    _$container
        .stop().animate({ "height" : "86px" }, 600)
        .find("article.success")
        .stop().animate({ "top" : "0px"}, 600);
        
    $(_form).stop().animate({ "opacity" : "0.0" }, 200);
};

_formSubmit = function ( iv ) {
    if( _sent === true ){ return false; }
    var d = { csrf_token : _csrf };
    _.each( iv, function (val,key) { d[key] = val; });
    $.post( "/contact/send", d, _receiver );
    _sent = true;
};

domEntry = function ( usr, csrf ) {
    _csrf = csrf;
    _$container = $("article.form-container");
    _form = document.getElementById( "contact-form" );
    return new IV({
        form : _form,
        callback : _formSubmit
    });
};
    
hexo.Entry( domEntry );
    
})( );