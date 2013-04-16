var DEBUGGING = true;
/* ******************************************* *
 * Global.js                                   *
 * Site-wide scripting definitions.            *
 * (c) 2013 Danny Hadley under the MIT license *
 * ******************************************* */
(function ( w ) { 

"use strict";

var /* global entry point */
    domReady,
    
    /* private: */
    
    /* shortcuts */
    _w   = w,           // ref: window
    _doc = _w.document, // ref: document
    _j   = _w.$,        // ref: jQuery
    
    /* variables */
    _time = new Date( ).getTime( ), // the current timestamp
    _csrf = "",                     // reference to the crsf token
    _cusr = null,                   // reference to current user
    _chatRooms = { },               // private hash of chatrooms
    
    /* functions */
    _toTop, // scrolls window to top
    _Menu,  // basic menu rigger
    _efn,   // empty function
    
    /* Public: */
    hexo = { },
    Heartbeat,
    Geo,
    U, Utils,
    Socket,
    C, Chat,
    User;

/* _efn
 * an empty function for shortcutting
*/
_efn = function( ) { };    


/* _toTop
 * scrolls window to top when the to-top button
 * is clicked
*/
_toTop = function ( ) {
    _j("html, body").stop( ).animate({scrollTop:"0px"}, 600);
};


////////////////////////
// NAMESPACE : Socket //
////////////////////////
Socket = function( conf ) { return new Socket.ns.rig(conf); };
Socket.prototype = Socket.ns = (function( ) {

    var _ns = {
        constructor : Socket,
        version : "1.0",
        ready : false,
        url : false,
        callback : false
    },
    
    /* private socket storage */
    _socketXHRs = { },
    
    /* default socket events */
    _defaultEvents = { 
        'close' : _efn,
        'update' : _efn
    },
    
    /* _data 
     * A helper that generates optional data 
     * to pass into the xhr
    */
    _data = function ( ) {
        return {
            usr : _cusr,
            csrf_token : _csrf,
            token : this.token,
            extras : this.extras,
            raw : (this.raw === true) ? "raw" : false
        };
    },
    
    /* Socket._delegate
     * takes the information from the server and
     * decides the next course of action
     * @param {object} data Server data
    */
    _delegate = function ( data ) {
        /* the socket died */
        if( data.flag && data.flag == "dead" ) {
            
            this.close( );
            
            this.ready   = false;
            this.looping = false;
            
            this.events['close']( );   
            U.l("Socket #" + this.uid + ": was terminated on the server end");
            return false;
        } 
        
        /* something traumatic happened */
        else if( !data.success ) {
            U.l("Socket #" + this.uid + ": the socket was unsuccessful", "err"); 
            this.ready = false;
            return false;
        }
            
        /* server sends back an update */
        if( parseInt( data.code, 10 ) == 1 ){
            U.l("Socket #" + this.uid + ": package received");
            this.events['update']( data['package'] || { } );
        } 
        
        /* server sends back a timeout */
        else {
            U.l("Socket #"+this.uid+": no package received -> restarting");
        }
        
    },
    
    /* Socket._loop
     * Recieves data if there, opens up 
     * a new ajax call if it is ready
     * @param {{data}} data Return data
    */
    _loop = function ( data ) {
        
        /* check for data */
        if( data !== undefined && U.type(data) === "object" ) { 
            _delegate.call( this, data );
        }
        /* make the xhr call */
        if( this.looping === true && this.ready === true ) {
            _socketXHRs[this.uid] = _j.post( this.url, _data.call( this ), _.bind( _loop, this ), "json" );
        }
    };
    
    /* Socket.rig
     * Uses an object parameter to set up the socket info
     * @param {{object}} conf The configuration settings
    */
    _ns.rig = function( conf ) {
        if( !conf || conf === undefined || U.type(conf) !== "object" ) { return false; }

        this.url = conf.url || false;
        
        /* apply the events from the configuration to this socket */
        this.events = _j.extend( { }, _defaultEvents, conf.events );
        
        /* set all of the other goodies */
        this.token = conf.token;
        this.extras = conf.extras || { };
        this.uid = U.uid( );
        this.raw = false;
        
        /* every socket needs a url */
        if( this.url === false )
            return false;
        
        this.ready = true;
    };

    _ns.force = function ( ) {
        var cb = (function( self ) {
            return (function(data){ _delegate.call( self, data ); });
        })( this );
        U.l("forcing socket #" + this.uid); 
        this.raw = true;
        _j.post( this.url, _data.call( this ), cb, "json" );
        this.raw = false;
    };

    /* Socket.open
     * Flip open the socket, and begin the looping
    */
    _ns.open = function ( ) {
        if( !this.ready ) { return false; }
        U.l("Socket #" + this.uid + ": opening up");
        this.looping = true;
        _loop.call( this );
    };
    
    /* Socket.close
     * Closes the socket
    */
    _ns.close = function ( ) {
        this.looping = false; 
        this.ready = false;
        if( _socketXHRs[this.uid] )
            _socketXHRs[this.uid].abort( );    
    };
    
    return _ns;
    
})( );
Socket.ns.rig.prototype = Socket.ns;

    
//////////////////////
// NAMESPACE : User //
//////////////////////
User = function( conf ) { return new User.ns.rig(conf); };
User.ns = User.prototype = (function( ) {

    var _ns = {
        version : "1.0",
        constructor : User    
    };

    _ns.rig = function( conf ) {
    
        if( !conf || conf == undefined ){ return false; }
        
        this.username = conf.username || "N/A";
        this.wins = ( U.pint( conf.wins ) ) ? U.pint( conf.wins ) : 0;
        this.losses = ( U.pint( conf.losses ) ) ? U.pint( conf.losses ) : 0;
        
        this.active = conf.active || false;
        
        if( this.active !== false )
            _cusr = this
            
        this.token  = conf.token || false; 
    
    };

    return _ns;
    
})( );
User.ns.rig.prototype = User.ns;

//////////////////////
// NAMESPACE : Chat //
//////////////////////
C = Chat = function( conf, isgame ){ return new Chat.ns.rig( conf, isgame ); };
Chat.ns = Chat.prototype = (function ( ) {
    
    var _ns = {
            constructor : Chat,
            version : "1.0"
        },
        
        _defaultEvents = {
            'update' : _efn
        },
        
        _data = function ( input ) {
            var d = {
                msg : input.message || false,
                chat_token : this.chat_token,
                user_token : this.user_token,
                csrf_token : _csrf
            };     
            return d;
        };    

    /* Chat.receive
     * the callback for the 'update' event of
     * the chatroom's socket 
     * @param {{object}} data The data sent back from the server
    */
    _ns.receive = function( data ) { 
        if( !data || !data['messages'] || !data['messages'].length ) { return false; }
        this.messages = data['messages'];
        this.events['update']( this.messages );
    };
        
    /* Chat.start
     * opens up the socket
    */
    _ns.start = function ( ) {
        if( !this.ready ){ U.l("chat room not ready to make calls","err"); return false; }     
        this.events['update']( this.messages );
        /* open up the socket */
        this.socket.open( );  
    };
    
    _ns.sendCheck = function ( data ) {
        if( !data.success ){ 
            U.l("something when wrong in chatroom: " + this.uid , "err"); 
            this.socket.close( );
            this.ready = false;
        } else {
            this.input.$inputs.val('');
        }
    };
    
    _ns.send = function ( stuff ) {
        if( !this.ready ){ U.l("chat room not ready to send messages","err"); return false; }
        _j.post("/chat/send", _data.call( this, stuff ), _.bind( this.sendCheck, this ) );
    };
        
    _ns.registerForm = function( opts ) {
        var f;
        
        if( U.type(opts) == "string" ) {
            f = new IV({
                form : _doc.getElementById( opts ),
                callback : _.bind( this.send, this )
            });
        } else if( U.type(opts) == "object" ) {
            f = new IV({
                form : opts.form || undefined,
                callback : _.bind( this.send, this )
            });       
        } else {  
            f = new IV({
                callback : _efn 
            });
        }
        
        this.input = f;
        return f;
    };
        
    _ns.rig = function( conf, isgame ) {
        if( !conf || conf == undefined ) { return false; }
        
        this.ready = false;

        /* basic identifying properties */
        this.name = conf.name || "N/A";
        this.uid = U.uid( );
    
        /* set the events property up */
        this.events = _j.extend( { }, _defaultEvents, conf.events );
        
        /* important information */
        this.chat_token = conf.chat_token || false;
        this.user_token = conf.user_token || false;
        this.messages = conf.messages || [ ];
        this.count = conf.count;
        
        /* if there weren't tokens, stop immediately */
        if( this.chat_token === false || this.user_token === false )
            return false;
        
    
        /* open up the socket */
        this.socket = Socket({ 
            url : "/chat/socket", 
            events : { 'update' : _.bind( this.receive, this ) },
            token : this.chat_token,
            extras : { chat_token : this.chat_token, user_token : this.user_token }
        });
          
        /* save this chatroom in the private hash */
        if( !isgame )
            _chatRooms[this.uid] = this;
                
        this.ready = true;
    };
    
    return _ns;
    
})( );
Chat.ns.rig.prototype = Chat.ns;

/* Chat.renderAll
 * Loops through all the chatrooms and renders them
 * into the pulldown
 * @param {selection} the render zone
*/
Chat.renderAll = (function ( ) {
    var _finished = false,
        $r_context, 
        $l_context,
        
        /* event functions: */
        _openChat,
        _closeChat,
        _updateRoom;
    
    _closeChat = function ( evt ) {
        if( evt.keyCode && evt.keyCode !== 27 )
            return;
            
        var $btn = $(this),
            uid  = $btn.data("uid");
        
        Chat.closeRoom( uid );
        
        $(document).off("keydown", _.bind( _closeChat, this ) );
    };
    
    _openChat = function ( ) {
        var $btn = $(this),
            uid  = $btn.data("uid");
            
        Chat.openRoom( uid );
        
        $(document).on("keydown", _.bind( _closeChat, this ) );
    };
    
    _updateRoom = function ( messages ) {
        var ele  = $r_context.find('section.chatroom[data-uid="'+this.uid+'"]'),
            target = ele.find("section.chat-window");
        
        target.html('');
        _.each( messages, function ( msg ) { 
            var author = msg.user || "",
                text = msg.message || "",
                html = U.template( "chat-message-template",{ user : author, text : text } );
            
            target.get()[0].innerHTML += html;
        
        });
        
        target.scrollTop( target.height( ) + 1000 );
        
    };
    
    return function ( $render_context, $list_context ) {
        
        if( _finished ) 
            return false;
        
        
        $r_context = $render_context;
        $l_context = $list_context;
        
        var c_html = "",
            l_html = "";
            
        _.each( _chatRooms, function( room ) {
            var ln = room.name.replace(/\s+/g, '_').toLowerCase(),
                e  = { room_id : ln + "-" + room.uid };
            
            room.events['update'] = _.bind( _updateRoom, room );
            
            c_html += U.template( "chatroom-tempate", $.extend( {} , e, room ) );
            l_html += U.template( "charoom-listitem-template", $.extend( {} , e, room ) );

        });
        
        $r_context.get()[0].innerHTML = c_html;
        $l_context.get()[0].innerHTML = l_html;
            
        _.each( _chatRooms, function( room ) {
            
            var ln = room.name.replace(/\s+/g, '_').toLowerCase(),
                e  = { room_id : ln + "-" + room.uid };
         
            room.registerForm( e.room_id + "-form" );
            room.start( );
            
        });
        
        $r_context.children().each(function( indx ) {
            $(this).css("left", (indx*400) + "px" );
        });
        
        $r_context.on( "click", "button.closer", _closeChat );
        $l_context.on("click", "button.opener", _openChat );
        
        _finished = true;
    };
    
})( );

Chat.closeRoom = function ( uid ) {
    var ele  = _j("#chatroom-pullout").find('section.chatroom[data-uid="'+uid+'"]');
    
    ele.stop().animate({
        "bottom" : (ele.height()*-2)+"px"
    }, 600, function ( ) {
        _j(this).css("display","none");
        _j("#chatroom-pullout").css("display","none");
    });
};

Chat.makeRoom = (function ( ) {
    
    var _made = 0,
        _busy = false;
    
    function _receive( data ) {
    
        if( !data['success'] ){ return false; }
        _made ++;
        setTimeout( function( ) { _busy = false; }, 3000 );
    };
    
    return (function ( name ) {
    
        if( _csrf === "" || _cusr === null || _busy )
            return false;  
    
        _busy = true;
        $.post("/chat/open", { name : name, usr : _cusr, csrf_token : _csrf }, _receive, "json" );
        
    });
    
})( );

Chat.joinRoom = (function ( ) {

    var _busy = false;

    function _receive( data ) {
        _busy = false;
    };
    
    return (function ( cid ) {
        
        if( _csrf === "" || _cusr === null || _busy )
            return false;  
    
        _busy = true;
        $.post("/chat/join", { cid : cid, usr : _cusr, csrf_token : _csrf }, _receive );
        
    });
    
})( );

Chat.openRoom = function ( uid ) {
    var ele  = _j("#chatroom-pullout").find('section.chatroom[data-uid="'+uid+'"]');
    
    _j("#chatroom-pullout").css("display","block");
    ele.stop().css("display","block").animate({
        "bottom" : "0px",
        "opacity" : "1.0"
    }, U.anTime, U.anEase );
    
};

///////////////////////////
// NAMESPACE : Utilities //
///////////////////////////
Utils = U = {

    template : (function ( ) {
        
        var /* public: */
            template,
            
            /* private: */
            _loadAll,
            _load,
            _templates = { };
        
        
        _load = function ( ) {
            
            if( $(this).length < 1 )
                return false;
                
            var $temp = $(this),
                name = $temp.data("name") || U.uid( ),
                html = $temp.html( );
                
            _templates[name] = _.template( html );
        };
        
        _loadAll = function ( ) {
            _j('script[type="text/template"]').each( _load );
        };
        
        /* U.template
         * creates the underscore html template
         * for a given object from the name param
         * @param {string} name The name of the template
         * @param {object} context The object to be rendered
         * @returns {string} html for the new item
        */
        template = function ( name, context ) {
            
            if( !_templates.hasOwnProperty( name ) )
                return false;
                
            return _templates[name](context);
        };
        
        /* give access to load function */
        template.load = _load;
        
        _j(_doc).ready( _loadAll );
        return template;
        
    })( ),

    /* U.uid
     * returns a unique identifier for uses 
     * inside the client
     */ 
    uid : (function ( ) {
        var _id = 0,
            _rn = function() { return Math.floor( Math.random() * 10e4 ).toString(36).substring(0,5); };
            
        return function ( ) { return _rn( ) + (++_id); };
    })( ),
        
    /* U.tsm
     * Translation string maker 
     * @param {{int}} xpos The x position of translation
     * @param {{int}} ypos The y position of translation
    */
    tsm : function( xpos, ypos ) {
        return "translate(" + xpos + "," + ypos + ")";  
    },
    
    /* U.pint
     * Shortcut for parseInt
    */
    pint : function( num ) {
        return parseInt( num, 10 );  
    },
    
    /* U.type
     * returns a more specific object type string
     * than the default "typeof"
     * @param {object} obj The object to be typed
    */
    type : (function () {
        var _ts    = Object.prototype.toString,
            _types = {
                "[object Array]"    : "array",
                "[object Function]" : "function",
                "[object Object]"   : "object",
                "[object Number]"   : "number",
                "[object String]"   : "string",
                "[object Boolean]"   : "boolean"
            },
            _type = function (obj) {
                var str = _ts.call(obj);
                return _types[str] || str;
            };
        
        return _type;
    })( ),
    
    /* U.Entry
     * calls document.ready callbacks 
     * from other scripts
    */
    Entry : (function ( ) {
        
        var /* public: */
            entry,
            
            /* private: */
            _callbacks = [ ],  // the array of callbacks
            _finished = false, // state management
            _onready;          // the function that calls all of these
        
        /* U._onready
         * private callback to trigger all onready functions
        */
        _onready = function ( ) {
            if( _finished ) { return false; }
                
            for( var i = 0; i < _callbacks.length; i++ ){
                // send these callbacks the current user
                _callbacks[i]( _cusr, _csrf );
            }
            _finished = true;
        };
        
        /* U.entry
         * pushes a callback into the stack
         * @param {function} fn The callback to push   
        */
        entry = function ( fn ) {
            if( U.type(fn) !== "function" )
                return false;
            
            // push callback into private array
            _callbacks.push( fn );
            
            // if the document was faster than the call
            if( _finished )
                fn( _cusr );
                    
            return true;
        };
        
        _j(_doc).ready( _onready );
               
        return entry;
        
    })( ),
    
    /* U.l
     * console logging helper.
     * @param {{string}} message What to log
     * @param {{string}} type The style of logging to use
    */
    l : (function ( hasConsole ) {
        if ( hasConsole == false || !DEBUGGING ) { return _efn; }
        
        var _log = function( msg ) { return _w.console.log("[" + msg + "]"); },
            _dir = function( msg ) { return _w.console.dir(msg); },
            _err = function( msg ) { return _w.console.error("!! " + msg); };
        
        return function ( msg, type ) {
            if( !type ) { return _log( msg ); }
            switch( type ) {
                case "log":
                    _log( msg );
                    break;
                case "dir":
                    _dir( msg );
                    break;
                case "err":
                    _err( msg );
                    break;
                default:
                    _log( msg );
                    break;
            }  
            return;
        };
    })( !!_w.console ),
    
    
    /* Global default properties */
    anTime : 500,
    anEase : "easeOutCubic",
    
};

Geo = (function( able ) {

    if( !able )
        return { init : _efn };
    
    var _ns = { },
        _prepped = false,
        
        _g = navigator.geolocation, // shortcut for geo obj
        _position = null,           // the saved position
        _map = null,                // constructed map object
        _marker = null,             // the active person's marker
        _otherMarkers = [ ],        // an array of markers for the other people
        _container = null,          // the div container
        
        /* default settings for the map */
        _defs = { 
            zoom : 3,
            zoomControl : false,
            mapTypeId : google.maps.MapTypeId.ROADMAP,
            disableDefaultUI : true,
            styles : [{"elementType":"labels","stylers":[{"visibility":"off"}]},{"featureType":"landscape","stylers":[{"color":"#4f4c44"}]},{"featureType":"water","stylers":[{"color":"#000000"}]},{"featureType":"road","stylers":[{"visibility":"off"}]},{"featureType":"administrative","stylers":[{"visibility":"off"}]},{"featureType":"poi","stylers":[{"visibility":"off"}]},{"featureType":"transit","stylers":[{"visibility":"off"}]},{"featureType":"administrative.province","stylers":[{"visibility":"on"},{"color":"#ffffff"},{"weight":0.7}]},{"featureType":"administrative.province","elementType":"labels","stylers":[{"visibility":"off"}]},{"featureType":"administrative.country","stylers":[{"visibility":"on"},{"weight":1.4},{"color":"#ffffff"}]},{"featureType":"administrative.country","elementType":"labels","stylers":[{"visibility":"off"}]}]
            },
        
        /* event blocking */
        _block = function ( e ) { 
            e.cancelBubble = true; 
            if ( e.stopPropagation ) 
                e.stopPropagation( ); 
        },
    
        /* Geo._renderMarkers
         * used to set the map for the markers that 
         * were added from the online list
        */
        _renderMarkers = function ( ) {
            _.each( _otherMarkers, function( marker ) { 
                marker.setMap( _map ); 
            });
        },
        
        /* Geo._markerHover
         * called with the marker on mouse over 
        */
        _markerHover = function ( ) {
            $("#geo-tooltip")
                .text( this.title )
                .stop().animate({
                    "bottom" : "-30px"
                }, U.anTime, U.anEase );
                
        },
        
        /* Geo._markerUnHover
         * called with the marker on mouse out 
        */
        _markerUnHover = function ( ) {
            $("#geo-tooltip")
                .stop().animate({
                    "bottom" : "0px"
                }, U.anTime, U.anEase );
        },
        
        _resize = function ( ) {
            google.maps.event.trigger( _map, 'resize' );   
        },
        
        _mapHover = function ( ) {
            $(this).stop().animate({
                "height" : "200px"
            }, U.anTime, U.anEase, _resize );
        },
        
        _mapUnHover = function ( ) {
            $(this).stop().animate({
                "height" : "65px"
            }, U.anTime, U.anEase, _resize );
        };
    
    /* Geo.addMarker
     * adds a marker to the private array
     * @param {object} the lat/lng val
    */
    _ns.addMarker = function( location, name ) {
        var lat = parseFloat( location.lat ),
            lng = parseFloat( location.lng );
        if( lat === 0 || lng === 0 ){
            return false;
        }
        var latlng  = new google.maps.LatLng(lat,lng),
            omarker = new google.maps.Marker({ 
                map : _map, 
                position : latlng, 
                icon : "http://hexo.in/img/other-marker.png",
                title : name
            });
        
        google.maps.event.addListener( omarker, "mouseover", _markerHover ); 
        google.maps.event.addListener( omarker, "mouseout", _markerUnHover); 
        
        _otherMarkers.push( omarker );
    };
    
    /* Geo.clearMarkers
     * removes the markers from the map and
     * resets the marker array
    */
    _ns.clearMarkers = function ( ) {
        _.each( _otherMarkers, function( marker ) { 
            U.l("clearing");
            marker.setMap(null); 
        });
        _otherMarkers = [ ];
    };
    
    /* Geo.setPosition
     * receives the position from the html5 api
     * function and initializes the map/marker
    */
    _ns.setPosition = function ( position ) { 
        
        $("#geo-container").css("display","block");
        _container = $("#geo-zone").css("display","block").hover( _mapHover, _mapUnHover ).get()[0];
        
        
        var options = { },
            lat = position.coords.latitude,
            lng = position.coords.longitude,
            latlng = new google.maps.LatLng(lat,lng); 
        
        $.post( "/home/heartbeat", { lat : lat, lng : lng, csrf_token : _csrf }, _efn );
        
        options.center = latlng;
        
        _map = new google.maps.Map( _container, $.extend( {}, _defs, options ) );
        _marker = new google.maps.Marker({ 
            map : _map, 
            position : latlng, 
            icon : "http://hexo.in/img/marker.png",
        });  
        
        _renderMarkers( );
            
        google.maps.event.addDomListener( _container, 'mousedown', _block ); 
        google.maps.event.addDomListener( _container, 'click', _block ); 
        google.maps.event.addDomListener( _container, 'dblclick', _block ); 
        google.maps.event.addDomListener( _container, 'contextmenu', _block ); 
    };
    
    /* Geo.init 
     * prepares the geo namespace for stuff
    */
    _ns.init = function ( ) {
        if( !_prepped )
            _g.getCurrentPosition( _ns.setPosition );
        
        _prepped = true;
    };
    
    return _ns;
    
})( (!!window.navigator) && (!!window.navigator.geolocation) );

///////////////////////////
// NAMESPACE : Heartbeat //
///////////////////////////
Heartbeat = (function( ) {
    
    var _ns = { },
        _defaults = { 
            "menu_id" : "#heartbeat-menu",
            "socket_url" : "/home/heartbeat",
            "container" : "#notification-list"
        },
        /* vars: */
        _socket,
        _count = 0,
        _container,
        _bubble,
        
        /* fn: */
        _updateBubble;
    
    _updateBubble = function ( ) {
        if( _count > 0 )
            _bubble.text( _count ).css("display","block");
        else 
            _bubble.text( 0 ).css("display","none");
    };
    
    _ns.update = function ( data ) {
        if( U.type(data) !== "array" ) 
            return false;
    
        /* clear out old html */
        _container.html('');
        _count = data.length;
        
        var newhtml = "";
        for( var i = 0; i < data.length; i++ ){
            if( data[i].type == "game" )
                newhtml += U.template( 'game-notification', data[i] );  
            else 
                newhtml += U.template( 'friend-notification', data[i] );
        }
        _container.html( newhtml );
        
        _updateBubble( );
    };
    
    /* Heartbeat.init
     * initializes the heartbeat zocket and menu
    */
    _ns.init = function ( ) {
        U.l("setting up the heartbeat view");
        
        _Menu.init( _defaults['menu_id'] );
        
        _container = $( _defaults['container'] );
        _bubble = $( _defaults['menu_id'] ).find("span.bubble");
        _count = _container.children("li").length;
            
        /* create the socket that updates information */
        _socket =  Socket({ 
            url : _defaults['socket_url'], 
            token : _cusr.token,
            events : { 'update' : _.bind( _ns.update, _ns ) }
        });
        _socket.open( );
        
        _updateBubble( );
    };
    
    return _ns;
    
})( );

//////////////////////
// DOMHELPER : Menu //
//////////////////////
_Menu = (function ( ) {
    
    var /* functions */   
        _init,
        _toggle,
        _open,
        _close,
        _checkEsc,
        
        /* variables */
        _menuDiv,
        _active = null,
        _cb,
        
        _ready = false,
        _menu;
      
    _checkEsc = function ( evt ) {  
        if( evt.keyCode !== 27 ) { return; }
        _close.call( this );
        return evt.preventDefault && evt.preventDefault( );
    };
    
    _open = function ( ) {
        if( !_ready ) { return; }
        
        if( _active !== null )
            _close.call( _active );
            
        _active = this;
        
        this.addClass("open");
        _j(_doc).on("keydown", _.bind( _checkEsc, this ) );
    };
    
    _close = function ( ) {
        if( !_ready ) { return; }
        
        _active = null;
        
        this.removeClass("open");
        _j(_doc).off("keydown", _.bind( _checkEsc, this ) );
    };
    
    _toggle = function ( ) {
        if( !_ready ) { return; }  
        return this.hasClass("open") ? _close.call( this ) : _open.call( this );
    };
    
    
    _init = function ( id ) {
        _menuDiv = _j( id );
        if( _menuDiv.length < 1 ) { return false; } 
        _cb = (function( _m ){ return function( ){ _toggle.call(_m); };  })( _menuDiv );
        _menuDiv.on("click", "button.toggle", _cb );
        _ready = true; 
    };
    
    _menu = { 
        open : _open,
        close : _close,
        init : _init,
        toggle : _toggle
    };
        
    return _menu;
    
})( );


/* entry point */
domReady = function ( ) {
    
    if( _doc.getElementById( String(_time ).substring( 0, 5 ) ) !== null ) 
        _csrf = _j("#"+String(_time ).substring( 0, 5 )).find('input[type="hidden"]').val( );

    if( _doc.getElementById("settings-menu") !== null )
        _Menu.init("#settings-menu");
    
    if( _doc.getElementById("chatist-menu") !== null )
        _Menu.init("#chatist-menu");
    
    if( _doc.getElementById("to-top") !== null ) 
        _j("#to-top").click( _toTop ); 
    
    if( _doc.getElementById("heartbeat-menu") !== null )
        Heartbeat.init( );
    
    if( _doc.getElementById("geo-zone") !== null )
        Geo.init( );
    
    if( _doc.getElementById("chatroom-pullout") !== null )
        Chat.renderAll( _j("#chatroom-pullout"), _j("#chatlist-menu-list") );
    
};


/* open up some variables to the window */
hexo.Utils = U; 
hexo.Chat = Chat; 
hexo.Socket = Socket; 
hexo.User = User; 
hexo.Entry = U.Entry;
hexo.Geo = Geo;

_w.hexo = hexo;

/* set the dom ready function */
hexo.Entry( domReady );
    
})( window );