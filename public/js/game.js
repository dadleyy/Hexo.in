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
    
    /* private: */
    _games = { },      // hash of game instances
    _renderZone,       // the render zone
    _chatInput,        // chat input box
    _chatZone,         // the chat zone
    _defaults, _d,     // default settings       
    _layerInits = { }, // layer prep functions
    _userTurn = 1,     // the turn associated with the current user
    
    /* _prepDom
     * Gets some DOM references, creates the needed
     * SVG gradients and stuff
    */
    _prepDom = function ( ) {
        
        _renderZone = document.getElementById('render-zone');
        _chatZone = document.getElementById('chat-zone');
        
        /* add the helper svg element */
        var _helper = d3.select(document.body).append("svg"),
            _defs   = _helper.append("defs"),
            _hexo   = _defs.append("polygon"),
            _mbgrad = _defs.append("linearGradient");
        
        _mbgrad.append("stop").attr( _d["msgboxgradstops"][0] );
        _mbgrad.append("stop").attr( _d["msgboxgradstops"][1] );
        
        _hexo.attr( _d['hexo'] );
        _helper.attr( _d['helpersvg'] );
        _mbgrad.attr( _d['msgboxgrad'] );
                
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
            
        /* set the two users */
        game.challenger = User(conf.challenger);
        game.visitor = ( conf.visitor ) ? User(conf.visitor) : false;
            
        if( game.visitor && game.visitor.active && !game.challenger.active ) {
            _userTurn = 2;
            U.l("you are the visitor of this game");
        } 
        else if ( !game.visitor || ( !game.visitor.active && game.challenger.active) ) {
            _userTurn = 1;
            U.l("you are the challenger of this game");
        }

        /* set the rest of the stuff */
        game.state = conf.state;
        game.tiles = conf.tiles;
        game.turn  = conf.turn || 0;
        
        /* create the chatroom */
        var cevts = { events : {'update' : _.bind( game.updateChat, game ) } },
            cconf = $.extend( {}, cevts, conf.chatroom );
            
        game.chatroom = Chat( cconf );
        
        /* make a new socket */
        game.socket = Socket({ 
            url : _d['gamesocket'].url, 
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
                    
        /* hook the chatroom form up */
        _chatInput = game.chatroom.registerForm({
            form : document.getElementById( "chat-input" ),
        });
        
        /* open up the sockets */
        game.socket.open( );
        game.chatroom.start( );
                    
        svg.attr( _d.dimensions );
        dom.svg = svg;
        dom.layers = layers;
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
            set = 0, inc = -1, nx, ny, sum = 0, val = 0, _tiles = [ ],
            info;
            
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
            
            /* make the tile, render and push */    
            var t = Tile( info , this );
            _tiles.push( t.render( layer, nx, ny ) );
        }
        
        
        this.tiles = _tiles;
    },
    
    /* _layerInits.ui
     * sets up the ui layer in the svg
     * @param {{object}} layer The ui layer
    */ 
    ui : function ( layer ) {
        var msgbox = layer.append("g").attr( _d['msgbox'] ),
            msginn = msgbox.append("g").attr("transform","translate(0,0)"),
            msgrect = msginn.append("rect").attr( _d['msgrect'] ),
            msgtxt = msginn.append("text").attr( _d['hexotext'] );
            
        this.dom.msgbox = {
            container : msginn,
            rect : msgrect,
            text : msgtxt   
        };
    }

};
    
/* Default setting definitions */
_defaults = _d = {
    chattemplate : ' \
        <dl class="cf message"> \
            <dt class="f">{{ user }}</dt> \
            <dd class="f">{{ text }}</dd> \
        </dl> \
    ',
    dimensions : {
        width : "540px",
        height : "440px"
    },
    msgbox : {
        transform : "translate(270,-100)",
        "data-name" : "msgbox"
    },
    msgrect : {
        width : 320,
        height : 120,
        fill : "url(#msgboxgrad)",
        ry : 4,
        rx : 4,
        x : -160,
        y : -60
    },
    msgtxt : { },
    msgboxgrad : {
        id : "msgboxgrad",
        gradientUnit : "userSpaceOnUse",
        x1 : "100%",
        y1 : "100%",
        x2 : "100%",
        y2 : "0%"
    },
    msgboxgradstops : [{
        "stop-color" : "#807a69",
        "offset"  : "1" 
    },{
        "stop-color" : "#726D5D",
        "offset"  : "0" 
    }],
    gamesocket : { url : "/game/socket" },
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
    hexotext : {
        "text-anchor" : "middle",
        "fill" : "#eee",
        "font-size" : "12px",
        "x" : 0,
        "y" : 5,
        "style" : "font-family:'Open Sans',sans-serif;font-weight:400;"
    }
};

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
        if( this.game.turn !== _userTurn ) {
            this.game.notify("its not your turn!");
            return false;
        }
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
        
        this.state = opts.state || 0;
        this.value = opts.value || 0;
        
    };

    _ns.render = function( layer, xpos, ypos ){
        var group = layer.append("g").attr("data-uid", this.uid ),
            tile  = group.append("use"),
            text  = group.append("text");
        
        text.attr( _d['hexotext'] ).text( this.value );
        group.attr("transform", U.tsm(xpos,ypos) );
        tile.attr( _d['gamehexo'] );
        
        group
            .on("mouseover", _.bind( _onHover, this ) )
            .on("mouseout", _.bind( _offHover, this ) )
            .on("click", _.bind( _onClick, this ) );
            
        this.dom = { group : group, tile : tile };
            
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
        
    };  

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
    _ns.end = function( ) {
        U.l("game over");
        this.socket.close( );
    };
    
    /* Game.update
     * The callback from the socket. deals with the data
     * send back from the long poll
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
            var visitor = User( data.visitor ),
                $domInfo = $("#visitor-info");
                
            $domInfo.find("h1.name").text( visitor.username );
            $domInfo.find("dl.wins dd").text( visitor.wins );
            $domInfo.find("dl.losses dd").text( visitor.losses );
            
            this.visitor = visitor;
        }
        
        this.draw( );
    };
    
    /* Game.draw
     * Called after a game update. Checks every tile for 
     * new states
    */
    _ns.draw = function ( ) {
        
    };
    
    /* Game.notify
     * Takes a message and displays it to the user 
     * @param {{string}} message The message to display
    */
    _ns.notify = function ( message ) { 
        this.dom.msgbox
            .container.transition().duration(800).attr("transform", U.tsm(0,300) );
    };
    
    /* Game.updateChat
     * Updates the chat window after the game's chat socket has needed an update
     * @param {{array}} messages The array of messages from the socket
    */
    _ns.updateChat = function ( messages ) { 
        U.l("updating game chat");
        $(_chatZone).html('');
        for( var i = 0; i < messages.length; i++ ){
            var msg = messages[i],
                author = msg.user || "",
                text = msg.message || "",
                html = _d.chattemplate.replace(/{{ user }}/g, author )
                                      .replace(/{{ text }}/g, text );
            
            _chatZone.innerHTML += html;
        }
        $( _chatZone ).scrollTop( $( _chatZone ).height( ) + 1000 );
    };

    return _ns;

})( );
Game.ns.rig.prototype = Game.prototype

/* domEntry
 * Called once the page is ready to render
*/
domEntry = function( ) {
    
    /* prepare the needed dom stufff */
    _prepDom( );
    
    /* render the game(s) */
    for( var uid in _games ){
        _render( _games[uid] );
    }
};

/* expose the game to the window */
w.Game = Game;

Entry( domEntry );
    
})( window );