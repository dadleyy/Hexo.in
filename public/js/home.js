/* ******************************************* *
 * Home.js                                     *
 * (c) 2013 Danny Hadley under the MIT license *
 * ******************************************* */
(function( ) {
   
"use strict";

var /* entry point */
    domEntry,
    
    /* private: */
    _user, // the current user
    _csrf, // the CSRF token 
    
    OnlineList; // handles the online people


////////////////////////////
// NAMESPACE : OnlineList //
////////////////////////////
OnlineList = (function ( ) {
    
    var _ns = { },
        
        /* some nifty defaults */
        _defaults = {
            "container" : "#online-list",
            "socket_url" : "/chat/online",
            "challenge_url" : "/game/challenge",
            "template" : "online-user"
        },
        
        /* _makeChallengeData
         * returns the data needed for ajax calls 
         * to a challege user call
         * @param {string} usr The username of the user being challenged
         * @returns an obj 
        */
        _makeData = function ( usr ){
            return {
                csrf_token : _csrf,
                target : usr,
                token : _user.token
            };
        },
        
        _socket,         // the update socket
        _container,      // the container for rendering
        _challengeUser,  // event handler for button clicks
        _checkChallenge; // data receiver for /game/challenge calls
        
    _checkChallenge = function ( data ) {
        console.log( data );  
    };
        
    _challengeUser = function ( ) {
        var $btn = $(this),
            usr = $btn.data("user");
        $.post( _defaults['challenge_url'], _makeChallengeData( usr ), _checkChallenge );  
    };
        
    _ns.update = function ( data ) {
        if( U.type(data) !== "array" ) 
            return false;
        
        /* clear out old html */
        _container.html('');
        var newhtml = "";
        for( var i = 0; i < data.length; i++ ){
            newhtml += U.template( _defaults['template'], data[i] );  
        }
        _container.html( newhtml );
    };
            
    _ns.init = function ( opts ) {
        U.l("setting up online user list");
        
        $.extend( _defaults, opts );
        
        /* get the dom container */
        _container = $(_defaults['container']);
        /* attatch events to the container */
        _container.on("click", "button.challenge", _challengeUser );
        
        
        /* create the socket that updates information */
        _socket =  Socket({ 
            url : _defaults['socket_url'], 
            token : _user.token,
            events : { 'update' : _ns.update.bind( _ns ) }
        });
        
        _socket.open( );
    };
    
    return _ns;
    
})( );

domEntry = function ( user, csrf ) {
    U.l("setting up homepage");
    
    _user = user;
    _csrf = csrf;
        
    OnlineList.init( );
};

Entry( domEntry ); 
    
})( );