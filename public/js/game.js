/* ******************************************* *
 * Game.js                                     *
 * (c) 2013 Danny Hadley under the MIT license *
 * ******************************************* */
(function( w ) {
    
"use strict";

var /* entry point */
    domEntry,

    /* public: */
    Game = function( conf ){ return new Game.ns.rig(conf); },
    Tile = function( game, opts ){ return new Tile.ns.rig(game, opts); },
    
    /* private: */
    _games = { },      // hash of game instances
    _renderZone,       // the render zone
    _chatInput,        // chat input box
    _chatZone,         // the chat zone
    _defaults, _d,     // default settings       
    _layerInits = { }, // layer prep functions
    
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
            
        /* set the two users */
        game.challenger = User(conf.challenger);
        game.visitor = ( conf.visitor ) ? User(conf.visitor) : false;
        
        /* set the rest of the stuff */
        game.state = conf.state;
        game.tiles = conf.tiles;
        
        game.chatroom = ( conf.chatroom ) ? Chat(conf.chatroom) : false;
        
        /* make a new socket */
        game.socket = Socket({ 
            url : _d['gamesocket'].url, 
            callback : game.update.bind( game ), 
            token : game.token 
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
        game.chatroom.start( game.updateChat.bind( game ) );
        
        game.updateChat( game.chatroom.messages );
                    
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
            set = 0, inc = -1, nx, ny;
        
        for( var i = 0; i < 37; i++ ) {
            
            if( set > order[row] ) {
                row++;
                set = 0;
                y += inc * 30;
            }
            if( row >= Math.floor(order.length * 0.5) ){
                inc = 1;
            }
            
            nx = x + ( row * 54 ); 
            ny = y + ( set * 60 );
            set ++;
            
            if( i == 18 ){ continue; }
            
            var t = Tile({id:(i>18)?i-1:i}, this );
            t.render( layer, nx, ny );
            this.tiles[i] = t;
        }
        
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
    gamesocket : { url : "/game/socket" },
    helpersvg : {
        width : "0px",
        height : "0px",
        id : "svghelper"
    },
    layers : [ "ui", "game" ],
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

/* ********** *
 * Tile Class *
 * ********** */
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
        
    }
        
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
        
        this.state = opts.state || 0;
        this.id = opts.id || 0;
    };

    _ns.render = function( layer, xpos, ypos ){
        var group = layer.append("g").attr("data-id", this.id ),
            tile  = group.append("use"),
            text  = group.append("text");
        
        text.attr( _d['hexotext'] ).text( this.id + 1 );
        group.attr("transform", U.tsm(xpos,ypos) );
        tile.attr( _d['gamehexo'] );
        
        group
            .on("mouseover", _onHover.bind( this ) )
            .on("mouseout", _offHover.bind( this ) );
            
        this.dom = { group : group, tile : tile };
            
        return this; 
    };
    
    return _ns;
    
})( );

    
    
/* ********** *
 * Game Class *
 * ********** */
Game.ns = Game.prototype =  {
    
    version : "1.0",
    constructor : Game,
    errored : false,
    dom : { }
    
};  

/* Game.rig 
 * @param {object} conf Intial information about the game
*/
Game.ns.rig = function( conf ) {
    if( conf === undefined || !conf ){ this.errored = true; return false; }  
    U.l("Preparing game for document.ready");
    return _set( conf, this );
};

Game.ns.end = function( ) {
    U.l("game over");
    this.socket.close( );
};
Game.ns.update = function( data ) {
    if( !data || data.state === null ){ this.end( ); }
    if( data.state === 1 && this.state === 0 ){
        this.socket.reset( true );
        this.state = 1;
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

Game.ns.draw = function ( ) {
    
};

Game.ns.updateChat = function ( messages ) { 
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

$(document).ready( domEntry );

/* Finalize classes */
Game.ns.rig.prototype = Game.prototype;
Tile.ns.rig.prototype = Tile.prototype;

/* expose the game to the window */
w.Game = Game;
    
})( window );