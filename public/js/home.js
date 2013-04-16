/* ******************************************* *
 * Home.js                                     *
 * (c) 2013 Danny Hadley under the MIT license *
 * ******************************************* */
(function( ) {
   
"use strict";

var /* entry point */
    domEntry,
    
    /* private: */
    U = hexo.Utils,
    _user, // the current user
    _csrf, // the CSRF token     
    
    _roomValidator,
    _openRoomFactory,
    _closeRoomFactory,
    _joinRoom,
    _makeRoom,
    _addRoom,
    
    OnlineList; // handles the online people
    

_addRoom = function( room ) {
    var ctx = { room_id : room.id, room_count : room.count, room_name : room.name },
        html = U.template( "chatroom-list-item", ctx );
    document.getElementById("open-chat-listing").innerHTML += html;
};

_makeRoom = function ( inputs ) {
    var roomname = inputs['room_name'];
    _closeRoomFactory( );
    return hexo.Chat.makeRoom( roomname, _addRoom );
}; 

_closeRoomFactory = function ( evt ) {
    if( evt && evt.keyCode && evt.keyCode !== 27 )
        return;
    
    $("#new-room-factory").stop().animate({
        "bottom" : "-400px"
    }, U.anTime, U.anEase, function(){ });  
    
    $(document).off( "keydown", _closeRoomFactory );
};

_openRoomFactory = function ( ) {
    $("#new-room-factory").stop().animate({
        "bottom" : "0px"
    }, U.anTime, U.anEase, function(){ });  
    
    $(document).on( "keydown", _closeRoomFactory );
};

_joinRoom = function ( ) {
    var $btn = $(this),
        cid = $btn.data("id");
    hexo.Chat.joinRoom( cid );
};

////////////////////////////
// NAMESPACE : OnlineList //
////////////////////////////
OnlineList = (function ( ) {
    
    var _ns = { },
        
        /* some nifty defaults */
        _defaults = {
            "container" : "#online-list",
            "socket_url" : "/chat/state",
            "challenge_url" : "/game/challenge",
            "template" : "online-user"
        },
        
        /* _makeChallengeData
         * returns the data needed for ajax calls 
         * to a challege user call
         * @param {string} usr The username of the user being challenged
         * @returns an obj 
        */
        _makeChallengeData = function ( usr ){
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
        if( !data || !data.success ) {                
            return false;
        }
        
        document.location = "/game/play";
    };
        
    _challengeUser = function ( ) {
        var $btn = $(this),
            usr = $btn.data("user");
        $.post( _defaults['challenge_url'], _makeChallengeData( usr ), _checkChallenge );  
    };
        
    _ns.update = function ( data ) {
        if( U.type(data) !== "object" ) 
            return false;
                
        /* clear out old html */
        _container.html('');
        hexo.Geo.clearMarkers( );
        var newhtml = "";
        _.each( data.users, function( user ) {
            newhtml += U.template( _defaults['template'], user );  
            hexo.Geo.addMarker( user.location, user.username );
        });
        _container.html( newhtml );
        _.each( data.rooms, function ( room ) {
             
        });
    };
            
    _ns.init = function ( opts ) {
        U.l("setting up online user list");
        
        $.extend( _defaults, opts );
        
        /* get the dom container */
        _container = $(_defaults['container']);
        /* attatch events to the container */
        _container.on("click", "button.challenge", _challengeUser );
        
        
        /* create the socket that updates information */
        _socket =  hexo.Socket({ 
            url : _defaults['socket_url'], 
            token : _user.token,
            events : { 'update' : _.bind( _ns.update, _ns ) }
        });
        
        _socket.open( );
        
        _socket.force( );
    };
    
    return _ns;
    
})( );

domEntry = function ( user, csrf ) {
    U.l("setting up homepage");
    
    _user = user;
    _csrf = csrf;
        
    OnlineList.init( );
    _roomValidator = new IV({
        form : document.getElementById('new-room-form'),
        callback : _makeRoom
    });
    
    $("article.chat-rooms").on( "click", "button.add-new", _openRoomFactory );
    $("article.chat-rooms").on( "click", "button.quick-join", _joinRoom );
};

hexo.Entry( domEntry ); 
    
})( );