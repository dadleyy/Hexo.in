/* ******************************************* *
 * Game.js                                     *
 * (c) 2013 Danny Hadley under the MIT license *
 * ******************************************* */
(function( w ) {
    
"use strict";

var /* entry point */
    domEntry,

    /* public: */
    Game,
    Tile,
    GameMessageBox,
    
    /* private: */
    U = hexo.Utils,
    _games = { },      // hash of game instances
    
    /* dom info */
    _renderZone,       // the render zone
    _$indicator,       // the turn indicator
    _$cscore,
    _$vscore,
    _chatInput,        // chat input box
    _chatZone,         // the chat zone
    
    _defaults, _d,     // default settings       
    _layerInits = { }, // layer prep functions
    _userTurn = 1,     // the turn associated with the current user
    _csrf = false,
    
    /* _prepDom
     * Gets some DOM references, creates the needed
     * SVG gradients and stuff
    */
    _prepDom = function ( ) {
        
        _renderZone = document.getElementById('render-zone');
        _chatZone = document.getElementById('chat-zone');
        _$indicator = $("#turn-indicator");
        _$cscore = $("#challenger-score");
        _$vscore = $("#visitor-score");
        
        /* add the helper svg element */
        var _helper = d3.select(document.body).append("svg"),
            _defs   = _helper.append("defs"),
            _hexo   = _defs.append("polygon");
        
        _hexo.attr( _d['hexo'] );
        _helper.attr( _d['helpersvg'] );
    },
    
    /* _set
     * Sets up all the game properties (avoid "this")
     * @param {{object}} conf The configuration
     * @param {{game}} 
     */
    _set = function ( conf, game ){
        var errors = [ ];
        
        game.token = conf.token || (errors.push("no key") && false);  
        game.uid = U.uid( );
    
        /* save this game above */
        _games[game.uid] = game;
            
        game.score = conf.score || { visitor : 0, challenger : 0 };
        
        /* set the rest of the stuff */
        game.state = conf.state;
        game.tiles = conf.tiles;
        game.turn  = conf.turn || 0;
        
        if( conf.no_live === true ){
            game.no_live = true;
            return true;
        }
        
        /* create the chatroom */
        var cevts = { events : {'update' : _.bind( game.updateChat, game ) } },
            cconf = $.extend( {}, cevts, conf.chatroom );
        
        /* set the two users */
        game.challenger = hexo.User(conf.challenger);
        game.visitor = ( conf.visitor ) ? hexo.User(conf.visitor) : false;
            
        if( game.visitor && game.visitor.active && !game.challenger.active )
            _userTurn = 2;
            
        else if ( !game.visitor || ( !game.visitor.active && game.challenger.active) )
            _userTurn = 1;
        
        game.chatroom = hexo.Chat( cconf, true );
        
        /* make a new socket */
        game.socket = hexo.Socket({ 
            url : _d['gameserver'].socket, 
            flag : conf.flag,
            token : game.token,
            events : { 
                'update' : _.bind( game.update, game ),
                'close' : _.bind( game.end, game )
            }
        });
                
        if( errors.length > 0 ){ U.l("was unable to initialize the game","err"); game.errored = true; }
        
        return errors.length > 0;
    },
    
    
    /* _render
     * Renders out the game into the 
     * render zone.
     * @param {{object}} game The game to be rendered
    */
    _render = function( game ) {
        U.l("Document is ready, rendering game");
        
        var dom = game.dom,
            svg = d3.select( _renderZone ).append("svg"),
            layers = { },
            layerName, i;
        
        for( i = 0; i < _d.layers.length; i++ ){
            
            layerName = _d.layers[i];
            layers[ layerName ] = svg.append("g");
            
            /* set some basic props */
            layers[ layerName ]
                .attr({
                    "data-name" : layerName,
                    "transform" : U.tsm(0,0) 
                });
            
            if( _layerInits[ layerName ] ){
                _layerInits[ layerName ].call( game, layers[ layerName ] );
            }
             
        }
                    
        game.msgbox = GameMessageBox({"selector":"#game-message-box"});
                    
        svg.attr( _d.dimensions );
        
        dom.svg = svg;
        dom.layers = layers;
        
        if( game.state == 3 )
            game.resolve( );
        
        $("a.reset-game").click( _.bind( game.reset, game ) );
        
        game.draw( );
        
        if( game.no_live === true )
            return true;
        
        /* hook the chatroom form up */
        _chatInput = game.chatroom.registerForm({
            form : document.getElementById( "chat-input" ),
        });
        
        /* open up the sockets */
        game.socket.open( );
        game.chatroom.start( );
        
    };

/* Unique layer initialization functions */
_layerInits = {

    /* _layerInits.game
     * sets up the game layer in the svg
     * @param {{object}} layer The game layer
    */ 
    game : function( layer ) {
        U.l("rendering game layer");
        
        var x = 100, y = 130, row = 0, order = [3,4,5,6,5,4,3],
            set = 0, inc = -1, nx, ny, sum = 0, val = 0, 
            _tiles = { }, info;
            
        for( var i in this.tiles ) {
            /* prevent prototyping of tiles array */
            if( !this.tiles.hasOwnProperty(i) ) { continue; } 
            
            // save the info and parse the index
            info = this.tiles[i];        
            i    = U.pint(i);   
            
            /* make sure we have a hole in the middle */
            if( i == 19 ) {
                set++;    
            }
            /* move on to the next row order if necessary */
            if( set > order[row] ) {
                row++;
                set = 0;
                y += inc * 30;
            }
            /* if halfway done with the hexagon, start moving down */
            if( row >= Math.floor(order.length * 0.5) ){
                inc = 1;
            }
            
            /* get positions */
            nx = x + ( row * 54 ); 
            ny = y + ( set * 60 );
            set ++;
            
            info.indx = i;
            /* make the tile, render and push */    
            var t = Tile( info , this );
            _tiles[i] = t.render( layer, nx, ny );
        }
        
        this.tiles = _tiles;
    },
    
    /* _layerInits.ui
     * sets up the ui layer in the svg
     * @param {{object}} layer The ui layer
    */ 
    ui : function ( layer ) {
        
    }

};
    
/* Default setting definitions */
_defaults = _d = {
    chattemplate : "chat-message-template",
    dimensions : {
        width : "540px",
        height : "440px"
    },
    gameserver : { socket : "/socket/game", moves : "/game/move" },
    helpersvg : {
        width : "0px",
        height : "0px",
        id : "svghelper"
    },
    layers : [ "game", "ui" ],
    hexo : {
        "points" : "16.326,28.101 -16.174,28.188 -32.5,0.086 -16.326,-28.101 16.174,-28.188 32.5,-0.086",
        "id" : "hexo"
    },
    gamehexo : { 
        "xlink:href" : "#hexo", 
        "fill" : "#333",
        "style" : "cursor:pointer" 
    },
    visitorhexo : { "fill" : "#ce334c" },
    challengerhexo : { "fill" : "#00ccff" },
    hexotext : {
        "text-anchor" : "middle",
        "fill" : "#eee",
        "font-size" : "12px",
        "x" : 0,
        "y" : 5,
        "style" : "font-family:'Open Sans',sans-serif;font-weight:400;cursor:pointer;"
    }
};


GameMessageBox = function( opts ){ return new GameMessageBox.ns.rig(opts); };
GameMessageBox.ns = GameMessageBox.prototype = (function( ) {
    
    var _ns = {
            version : "1.0",
            constructor : GameMessageBox,
        },
        /* default configuration */
        _conf = {
            "selector" : "#game-message-box",
            "container" : "body",
            "style" : { 
                "width" : "400px",
                "height" : "150px",
                "left" : "50%",
                "margin-left" : "-200px",
                "position" : "absolute",
                "top" : "-200px"
            },
            "anTime" : 600,
            "anEase" : "easeOutBounce"
        },
        /* interaction functions */
        _open,
        _close;
    
    _open = function ( ) {
        this.container.stop().animate({
            "top" : "100px"
        }, _conf['anTime'], _conf['anEase'] );
        $(document).on("keyup", _.bind( _close, this ) );
    };
    _close = function ( evt ) {
        /* if this was a mouse event, but not esc */
        if( evt.keyCode && evt.keyCode !== 27 ) { return; }
        $(document).off("keyup");
        this.container.stop().animate({
            "top" : "-200px"
        }, _conf['anTime'], _conf['anEase'] );
    };
    
    _ns.rig = function( opts ) {
        var conf = $.extend( {}, _conf, opts );
        this.container = $( _conf['container'] ).find( _conf['selector'] ).css( _conf['style'] );
        
        this.container
            .on("click", "button.closer", _.bind( _close, this ) );
    };
    
    _ns.notify = function( message, tohome ) { 
        var homebtn = ( tohome === true ) ? true : false,
            html = U.template( "game-messagebox", { message: message, homebtn : homebtn } );
            
        this.container.html( html );
        _open.call( this );
    };
    
    return _ns;
    
})( );
GameMessageBox.ns.rig.prototype = GameMessageBox.ns;

////////////////////////
// NAMESPACE : Tile   //
////////////////////////
Tile = function( game, opts ){ return new Tile.ns.rig(game, opts); };
Tile.ns = Tile.prototype = (function ( ){
    
    var _ns = {
            version : "1.0",
            constructor : Tile,
            dom : { },
            errored : false        
        },
        
        /* interaction functions */
        _onHover,
        _onClick,
        _offHover;
        
    _onClick = function ( ) {
        /* check to make sure it's the right turn */
        if( this.game.turn !== _userTurn )
            return this.game.notify("its not your turn!");
    
        else if( this.state !== 0 )
            return this.game.notify("this tile is already flipped");
        
        this.game.move( this );
    };
        
    _onHover = function( ) {
        this.dom.tile
            .transition()	
            .duration(620)
			.ease("elastic")
			.attr("transform","scale(1.08)");    
    };
    
    _offHover = function( ) {
        this.dom.tile
            .transition()	
            .duration(620)
			.ease("elastic")
			.attr("transform","scale(1.0)");
    };

    _ns.rig = function ( opts, game ) {
        if( !game ) { this.errored = true; return false; }
        this.game = game;    
        
        this.uid = U.uid( );
        this.indx = opts.indx;
        
        this.state = opts.state || 0;
        this.value = opts.value || 0;
        
    };

    _ns.render = function( layer, xpos, ypos ){
        var group = layer.append("g").attr("data-uid", this.uid ),
            tile  = group.append("use"),
            text  = group.append("text");
        
        text.attr( _d['hexotext'] ).text( this.value );
        group.attr("transform", U.tsm(xpos,ypos) );
        
        group
            .on("mouseover", _.bind( _onHover, this ) )
            .on("mouseout", _.bind( _offHover, this ) )
            .on("click", _.bind( _onClick, this ) );
            
        this.dom = { group : group, tile : tile };
            
        return this.draw( ); 
    };
    
    _ns.setState = function ( state ) {
        this.state = U.pint( state );  
    };
    
    _ns.draw = function ( ) {
        this.dom.tile.attr( _d['gamehexo'] );
        
        if( U.pint( this.state ) == 1 )
            this.dom.tile.attr( _d['challengerhexo'] )
            
        else if( U.pint( this.state ) == 2 )
            this.dom.tile.attr( _d['visitorhexo'] )
        
        return this;  
    };
        
    return _ns;
    
})( );
Tile.ns.rig.prototype = Tile.prototype;

    
    
////////////////////////
// NAMESPACE : Game   //
////////////////////////
Game = function( conf ){ return new Game.ns.rig(conf); };
Game.ns = Game.prototype =  (function ( ) {
    
    var _ns = {    
            version : "1.0",
            constructor : Game,
            errored : false,
            dom : { }
        },
        
        /* _moveData
         * generates the data needed for move calls
         * @param {Tile} tile The tile that was moved
        */
        _moveData = function( tile ) {
            return { 
                csrf_token : _csrf,
                token : this.token,
                tile : tile
            };
        },
        
        _isBusy = false;

    /* Game.rig 
     * @param {object} conf Intial information about the game
    */
    _ns.rig = function( conf ) {
        if( conf === undefined || !conf ){ this.errored = true; return false; }  
        U.l("Preparing game for document.ready");
        return _set( conf, this );
    };
    
    /* Game.end
    */
    _ns.end = function ( ) {
        if( this.state !== 3 )
            this.notify("the other player has quit the game");
    
        this.socket.close( );
    };
    
    _ns.resolve = function ( ) {
        var winner = ( this.score.visitor > this.score.challenger ) ? this.visitor : this.challenger;
        this.end( );
        return this.notify( winner.username + " has won the game!", true );
    };
    
    /* Game.postMove 
     *
    */
    _ns.postMove = function ( data ) {
        
        if( !data.success )
            return this.notify( data );
    
        this.tiles[data.update.key].setState( data.update.state );
        
        this.draw( );
        setTimeout( function ( ) {
            _isBusy = false;
        }, 1000 );
    };
    
    /* Game.update
     * The callback to handle data from the socket.
     * @param {{object}} data Data object returned from server
    */
    _ns.update = function( data ) {
        if( !data || data.state === null ){ this.end( ); }

        /* someone joined the game */
        if( data.state === 1 && this.state === 0 ){
            this.state = 1;
            this.socket.force( );
        } 
        
        if( this.visitor === false && data.visitor != false ){
            U.l("The visitor has joined");
            var visitor = hexo.User( data.visitor ),
                $domInfo = $("#visitor-info");
                
            $domInfo.find("h1.name").text( visitor.username );
            $domInfo.find("dl.wins dd").text( visitor.wins );
            $domInfo.find("dl.losses dd").text( visitor.losses );
            
            this.visitor = visitor;
        }
        _.each( data.tiles, function ( tile, indx ) { 
            if( U.pint( this.tiles[indx].value ) ===  U.pint( tile.value ) ) {
                this.tiles[indx].state = U.pint( tile.state );
            }
        }, this );
        
        this.score = data.score;
        this.turn = data.turn;    
        
        if( data.state === 3 && data.flag === "complete" ){
            this.state = 3;
            this.resolve( );
        }
            
        this.draw( );
    };
    
    /* Game.draw
     * Called after a game update. Checks every tile for 
     * new states
    */
    _ns.draw = function ( ) {
        U.l("redrawing the game");  
        
        var indicatorDest = 20;
        switch( this.turn ) {
            case 1:
                indicatorDest = 20;
                break;
            case 2:
                indicatorDest = 540;
                break;
            default:
                break;     
        }
        _$indicator.stop().animate({
            "left" : indicatorDest + "px"
        }, U.anTime, U.anEase );
    
        _$cscore.text( this.score['challenger'] );
        _$vscore.text( this.score['visitor'] );
        
        _.each( this.tiles, function( tile ){ 
            tile.draw( );
        });
    };
    
    /* Game.move
     * Toggles the tile and sends to server
     * @param {Tile} the tile that was flipped
    */
    _ns.move = function ( tile ) {
    
        if( _isBusy )
            return false;
            
        _isBusy = true;
        var data = _moveData.apply( this, [tile] );
    
        $.post( _d['gameserver'].moves, {
            "csrf_token" : _csrf,   
            "token" : this.token,
            "tile" : { "value" : tile.value, "state" : _userTurn }
        }, _.bind( this.postMove, this ),  "json" );
        
    };
    
    /* Game.notify
     * Takes a message and displays it to the user 
     * @param {{string}} message The message to display
    */
    _ns.notify = function ( message, homebutton ) { 
        this.msgbox.notify( message, homebutton );
    };
    
    /* Game.updateChat
     * Updates the chat window after the game's chat socket has needed an update
     * @param {{array}} messages The array of messages from the socket
    */
    _ns.updateChat = function ( messages ) { 
        U.l("updating game chat");
        $(_chatZone).html('');
        
        _.each( messages, function ( msg ) {
        
            var author = msg.user || "",
                text = msg.message || "",
                html = U.template( _d["chattemplate"], { user : author, text : text } );
            
            _chatZone.innerHTML += html;
        
        });
        
        $( _chatZone ).scrollTop( $( _chatZone ).height( ) + 1000 );
    };
    
    _ns.resetCheck = function ( data ) { };
    
    _ns.reset = function ( evt ) {
        $.post( "/game/reset", { csrf_token : _csrf, token : this.token }, _.bind( this.resetCheck, this ) );
        return evt.preventDefault && evt.preventDefault( );  
    };

    return _ns;

})( );
Game.ns.rig.prototype = Game.prototype

/* domEntry
 * Called once the page is ready to render
*/
domEntry = function( cusr, csrf ) {
    
    /* prepare the needed dom stufff */
    _prepDom( );
    _csrf = csrf;
    /* render the game(s) */
    for( var uid in _games ){
        _render( _games[uid] );
    }
    
};

/* expose the game to the window */
hexo.Game = Game;

hexo.Entry( domEntry );
    
})( window );