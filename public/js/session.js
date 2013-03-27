(function ( ) { 
    "use strict";
    
    var /* private entry point */
        _domReady,
        
        /* private functions */
        _erroredForm,
        _pwFocus,
        _pwBlur,
        
        /* private form instance */
        _loginForm;
    
    _erroredForm = function ( errors ) {
        for( var i = 0; i < errors.length; i++ ){
            var $in = $(errors[i].input);
            $in.blur( );
            if( $in.attr("type") !== "password" ) { continue; }
            $in.parent().addClass("errored");
        }
        return false;
    };
    
    _pwFocus = function ( ) {
        if ( $(this).val() !== "" ) { return; }  
        $(this).parent().removeClass("errored").find("span.placeholder").fadeOut(100);
    };
    
    _pwBlur = function ( ) {
        if ( $(this).val() !== "" ) { return; }  
        $(this).parent().find("span.placeholder").fadeIn(100);
    };
    
    _domReady = function ( ) {
    
        _loginForm = new IV({
            form      : $("article.login-form form").get()[0],
            ecallback : _erroredForm
        });   
        
        _loginForm.$inputs.each(function ( ) {
            if( $(this).attr("type") !== "password") { return; }
            $(this)
                .focus( _pwFocus )
                .blur( _pwBlur );
        });
    
    };
    
    $(document).ready( _domReady );
        
})( );