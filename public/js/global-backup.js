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
        if( data.new_flag && data.new_flag == "dead" ) {
            
            this.close( );
            this.ready = false;
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
        
        this.active = conf.active || false;
        
        if( this.active !== false )
            _cusr = this
            
        this.token  = conf.token || false; 
    
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
        if( !data || !data.length ) { return false; }
        this.messages = data;
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
        
    _ns.rig = function( conf ) {
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
            U.l("loading all templates");
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
        this.addClass("open");
        _j(_doc).on("keydown", _.bind( _checkEsc, this ) );
    };
    
    _close = function ( ) {
        if( !_ready ) { return; }
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
    if( _doc.getElementById("authd-menu") !== null )
        _Menu.init("#authd-menu");
    
    if( _doc.getElementById("chatist-menu") !== null )
        _Menu.init("#chatist-menu");
    
    if( _doc.getElementById("to-top") !== null ) 
        _j("#to-top").click( _toTop ); 
    
    if( _doc.getElementById( String(_time ).substring( 0, 5 ) ) !== null ) 
        _csrf = _j("#"+String(_time ).substring( 0, 5 )).find('input[type="hidden"]').val( );
};


/* open up some variables to the window */
window.U = window.Utils = U;
window.C = window.Chat = C;
window.User = User;
window.Socket = Socket;
window.Entry = U.Entry;

/* set the dom ready function */
Entry( domReady );
    
})( window );