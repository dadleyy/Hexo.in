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
    _w   = w,           // ref: window
    _doc = _w.document, // ref: document
    _j   = _w.$,        // ref: jQuery
    
    _time = new Date( ).getTime( ), // the current timestamp
    _csrf = "", // reference to the crsf token
    
    _Menu, // Basic menu rigger
    _efn = function ( ) { }, // empty function
    
    /* Public: */
    U, Utils,
    Socket,
    C, Chat,
    User;
    
////////////////////////
// NAMESPACE : Socket //
////////////////////////
Socket = function( conf ) { return new Socket.prototype.rig(conf); };
Socket.prototype = (function( ) {

    var _ns = {
        constructor : Socket,
        version : "1.0",
        ready : false,
        url : false,
        callback : false
    },
    
    /* private socket storage */
    _socketXHRs = { },
    
    /* _data 
     * A helper that generates optional data 
     * to pass into the xhr
    */
    _data = function ( ) {
        return {
            csrf_token : _csrf,
            token : this.token,
            extras : this.extras,
            raw : (this.raw) ? "raw" : false
        };
    },
    
    /* Socket._loop
     * Recieves data if there, opens up 
     * a new ajax call if it is ready
     * @param {{data}} data Return data
    */
    _loop = function ( data ) {
        
        /* check for data */
        if( data !== undefined && U.type(data) === "object" ) { 
            if( this.raw ){ this.raw = false; } 
            
            if( data.new_flag && data.new_flag == "dead" ) {
                this.close( );
                this.ready = false;
                U.l("Socket #" + this.uid + ": was terminated on the server end");
            } else if( !data.success ) { 
                U.l("Socket #" + this.uid + ": the socket was unsuccessful", "err"); 
                this.ready = false;
            }
                
            if( parseInt( data.code, 10 ) == 1 ){
                U.l("Socket #" + this.uid + ": package received");
                this.callback( data['package'] || { } );
            } 
            else {
                U.l("Socket #"+this.uid+": no package received ("+data.timeout+") -> restarting");
            }
        }
        
        /* make the xhr call */
        if( this.looping === true && this.ready == true ) {
            _socketXHRs[this.uid] = $.post( this.url, _data.call( this ), _loop.bind( this ), "json" );
        }
    };
    
    /* Socket.rig
     * Uses an object parameter to set up the socket info
     * @param {{object}} conf The configuration settings
    */
    _ns.rig = function( conf ) {
        if( !conf || conf === undefined || U.type(conf) !== "object" ) { return false; }

        this.url = conf.url || false;
        this.callback = conf.callback || false;
        this.token = conf.token;
        this.extras = conf.extras || { };
    
        this.uid = U.uid();
        
        this.raw = false;
        
        if( this.url === false || this.callback === false ) { return false; }
        this.ready = true;
    };
    
    /* Socket.reset
     * Clears out any existing requests and starts the looping 
     * over again
    */
    _ns.reset = function ( raw ) {
        if( !this.ready ) { return false; }
        
        if( _socketXHRs[this.uid] ){
            _socketXHRs[this.uid].abort( );
        }
        if( raw !== undefined && raw === true ){
            this.raw = true;
            U.l("forcing socket information");
        }
        
        this.open( );
        
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
        if( _socketXHRs[this.uid] ){
            _socketXHRs[this.uid].abort( );
        }
    };
    
    return _ns;
    
})( );
Socket.prototype.rig.prototype = Socket.prototype;

    
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
        
    };

    return _ns;
    
})( );
User.ns.rig.prototype = User.prototype;

//////////////////////
// NAMESPACE : Chat //
//////////////////////
C = Chat = function( conf ){ return new Chat.ns.rig(conf); };
Chat.ns = Chat.prototype = (function ( ) {
    
    var _ns = {
            constructor : Chat,
            version : "1.0"
        },
        
        _data = function ( input ) {
            var d = {
                msg : input.message || false,
                chat_token : this.chat_token,
                user_token : this.user_token,
                csrf_token : _csrf
            };     
            return d;
        },
        
        /* private list of chat rooms */
        _chatRooms = { };
    

    _ns.receive = function( data ) { 
        if( !data || !data.length ) { return false; }
        this.receiver( data );
    };
        
    _ns.start = function ( callback ) {
        if( !this.ready ){ U.l("chat room not ready to make calls","err"); return false; }        
        /* set the function that will deal with the data */
        this.receiver = callback;
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
        $.post("/chat/send", _data.call( this, stuff ), this.sendCheck.bind( this ) );
    };
        
    _ns.registerForm = function( opts ) {
        var f;

        if( U.type(opts) == "string" ) {
            
            f = new IV({
                form : _doc.getElementById( opts ),
                callback : this.send.bind(this)
            });
                  
        } else if( U.type(opts) == "object" ) {
            
            f = new IV({
                form : opts.form || undefined,
                callback : this.send.bind(this)
            });
         
        } else {
            
            f = new IV({
                callback : _efn 
            });
            
        }
        
        this.input = f;
        return f;
    };
        
    _ns.rig = function( conf ) {
        if( !conf || conf == undefined ) { return false; }
        
        this.ready = false;
        
        this.name = conf.name || "N/A";
        this.uid = U.uid( );
        this.chat_token = conf.chat_token || false;
        this.user_token = conf.user_token || false;
        this.messages = conf.messages || [ ];
        
        if( this.chat_token === false || this.user_token === false ){ return false; }
        
        this.socket = Socket({ 
            url : "/chat/socket", 
            callback : this.receive.bind( this ), 
            token : this.chat_token,
            extras : { chat_token : this.chat_token, user_token : this.user_token }
        });
        
        _chatRooms[this.uid] = this;
        this.ready = true;
    };
    
    return _ns;
    
})( );
Chat.ns.rig.prototype = Chat.prototype;

///////////////////////////
// NAMESPACE : Utilities //
///////////////////////////
Utils = U = {

    /* U.uid
     * returns a unique identifier for uses 
     * inside the client
     */ 
    uid : (function ( ) {
        var _id = 0,
            _rn = function() { return Math.floor( Math.random() * 10e2 ).toString(36).substring(0,5); };
            
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
                "[object String]"   : "string"
            },
            _type = function (obj) {
                var str = _ts.call(obj);
                return _types[str] || str;
            };
        
        return _type;
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
    })( !!_w.console )

};

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
        
        _ready = false,
        _menu;
      
    _checkEsc = function ( evt ) {  
        if( evt.keyCode !== 27 ) { return; }
        _close.call( this );
        return evt.preventDefault && evt.preventDefault( );
    };
    
    _open = function ( ) {
        if( !_ready ) { return; }
        this.addClass("open");
        _j(_doc).on("keydown", _checkEsc.bind( this ) );
    };
    
    _close = function ( ) {
        if( !_ready ) { return; }
        this.removeClass("open");
        _j(_doc).off("keydown", _checkEsc.bind( this ) );
    };
    
    _toggle = function ( ) {
        if( !_ready ) { return; }  
        return this.hasClass("open") ? _close.call( this ) : _open.call( this );
    };
    
    
    _init = function ( id ) {
        _menuDiv = _j( id );
        if( _menuDiv.length < 1 ) { return false; }
        _menuDiv.on("click", "button.toggle", _toggle.bind( _menuDiv ) );
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
    if( _doc.getElementById("authd-menu") !== null )
        _Menu.init("#authd-menu");
    
    if( _doc.getElementById("chatist-menu") !== null )
        _Menu.init("#chatist-menu");
      
    if( _doc.getElementById( String(_time ).substring( 0, 5 ) ) !== null ) 
        _csrf = $("#"+String(_time ).substring( 0, 5 )).find('input[type="hidden"]').val( );
};


/* open up some variables to the window */
window.U = window.Utils = U;
window.C = window.Chat = C;
window.User = User;
window.Socket = Socket;

/* set the dom ready function */
_j(_doc).ready( domReady );
    
Function.prototype.bind=Function.prototype.bind||function(d){var a=Array.prototype.splice.call(arguments,1),c=this;var b=function(){var e=a.concat(Array.prototype.splice.call(arguments,0));if(!(this instanceof b)){return c.apply(d,e)}c.apply(this,e)};b.prototype=c.prototype;return b};

})( window );