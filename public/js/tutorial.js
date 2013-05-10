/* ******************************************* *
 * Tutorial.js                                 *
 * (c) 2013 Danny Hadley under the MIT license *
 * ******************************************* */
(function( hexo ) {

"use strict";

var /* entry point */
    domEntry,
    
    // private stuff
    _U = hexo.Utils,
    _neighborhash = { },
    _checkNeighbors,
    
    Tutorial;

_neighborhash[12] = [ 32, 17, 26 ];
_neighborhash[32] = [ 12, 17, 31, 23 ];
_neighborhash[23] = [ 25, 5,  31, 32 ];
_neighborhash[25] = [ 15, 5, 23 ];

_neighborhash[26] = [ 12, 13, 20, 17 ];
_neighborhash[17] = [ 26, 12, 32, 31, 18, 20 ];
_neighborhash[31] = [ 17, 32, 23, 5,  22, 18 ];
_neighborhash[5]  = [ 15, 19, 22, 31, 23, 25 ];
_neighborhash[15] = [ 25, 5,  19, 28 ];

_neighborhash[13] = [ 8,  24, 20, 26 ];
_neighborhash[20] = [ 13, 26, 17, 18, 1, 24 ];
_neighborhash[18] = [ 1,  20, 17, 31, 22 ];
_neighborhash[22] = [ 18, 31, 5,  19, 16 ];
_neighborhash[19] = [ 22, 5,  15, 28, 6, 16 ];
_neighborhash[28] = [ 15, 19, 6,  11 ];

_neighborhash[8]  = [ 13, 24, 35 ];
_neighborhash[24] = [ 8,  13, 20, 1,  4,  35 ];
_neighborhash[1]  = [ 24, 20, 18, 7,  4 ];
_neighborhash[16] = [ 22, 19, 6,  21, 3 ];
_neighborhash[6]  = [ 16, 19, 28, 11, 27, 21 ];
_neighborhash[11] = [ 28, 6,  27 ];

_neighborhash[35] = [ 8,  24, 4,  9 ];
_neighborhash[4]  = [ 35, 24, 1,  7,  10, 9 ];
_neighborhash[7]  = [ 1,  4,  10, 14, 3 ];
_neighborhash[3]  = [ 7,  14, 36, 21, 16 ];
_neighborhash[21] = [ 3,  36, 29, 27, 6, 16 ];
_neighborhash[27] = [ 21, 29, 11, 6 ];

_neighborhash[9]  = [ 35, 4,  10, 30 ];
_neighborhash[10] = [ 9,  4,  7,  14, 33, 30 ];
_neighborhash[14] = [ 10, 7,  3,  36, 34, 33 ];
_neighborhash[36] = [ 14, 3,  21, 29, 2,  34 ];
_neighborhash[29] = [ 36, 21, 27, 2 ];

_neighborhash[30] = [ 9,  10, 33 ];
_neighborhash[33] = [ 30, 10, 14, 34 ];
_neighborhash[34] = [ 33, 14, 36, 2  ];
_neighborhash[2]  = [ 34, 36, 29 ];

_checkNeighbors = function( tile, flag, others ) {
    var neighbors = _neighborhash[tile.value],
        ovals = [ ],
        ostate = flag ? 2 : 1,
        mstate = flag ? 1 : 2,
        game = this;
        
     _.each( neighbors, function ( other ) {
        var o = game.getTile( other );
        if( o.state === 0 || o.state === ostate )
            return;
        
        var count = 0;
        _.each( _neighborhash[o.value], function( nother ) {
            var n = game.getTile( nother );    
            if( n.state === 0 || n.state !== tile.state )
                return;
                
            count++;
        });
        
        if( count > 3 )
            game.move( o, flag );
    
    });
    
    return 0;
};

// extend the hexo game
Tutorial = function( conf ){ return new Tutorial.ns.rig(conf); };
Tutorial.ns = Tutorial.prototype = (function( ) {
    
    var _ss = hexo.Game.ns,
        _ns = { };
    
    _ns.AI = function( ) {
        var found = null,
            hval = 0;
            
        _.each( this.tiles, function ( tile ) {
            if( tile.state == 1 || tile.state == 2 )
                return;
        
            if( tile.value > hval ){
                found = tile;
                hval = tile.value;
            }
            
            return;
        });  
        
        var game = this;
        return (function ( ) { game.move( found, true ); });
    };
    
    _ns.move = function( tile, aiflag ) {
        tile.setState( aiflag ? 2 : 1 );
        
        var scores = { challenger : 0, visitor : 0 },
            tiles = this.tiles;
    
        _.each( tiles, function ( tile ) {
                    
            if( tile.state == 1 )
                scores["challenger"] += tile.value;
            else if( tile.state == 2 )
                scores["visitor"] += tile.value;
            
        });
        _checkNeighbors.call( this, tile, aiflag, tiles );
            
        this.turn = aiflag ? 1 : 2;
        this.score = scores;
        this.draw( );
        return aiflag ? false : setTimeout( this.AI( ), 3000 );
    };
    
    _ns.getTile = function ( val ) {
        var found = false;
        _.each( this.tiles, function( tile ) {
            if( tile.value == val )
                found = tile;
        });
        return found;
    };
    
    return $.extend({ }, _ss, _ns);
    
})( );
Tutorial.ns.rig.prototype = Tutorial.ns;

/* domEntry
 * Called once the page is ready to render
*/  
domEntry = function( ) {
    
};

hexo.Game.Tutorial = Tutorial;
hexo.Entry( domEntry );

})( window.hexo );