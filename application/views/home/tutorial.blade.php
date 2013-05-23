@layout('layouts.common')

@section('styles')
<link rel="stylesheet" type="text/css" href="{{ asset("css/game.css") }}">
<link rel="stylesheet" type="text/css" href="{{ asset("css/about.css") }}">
<link rel="stylesheet" type="text/css" href="{{ asset("css/tutorial.css") }}">
@endsection

@section('scripts')
<script type="text/javascript" src="{{ asset("js/d3.js") }}"></script>
<script type="text/javascript" src="{{ asset("js/game.min.js") }}"></script>
<script type="text/javascript" src="{{ asset("js/tutorial.min.js") }}"></script>
<script type="text/javascript">hexo.Game.Tutorial({{ $game_js }})</script>
@endsection

@section('content')
    <section class="main-content spw middle">
        <h1>How to play<br><em class="righteous">Hexo</em></h1>
        
        @include('games.player-ui')->with("type","challenger")->with("player",$game->challenger( ))

        @include('games.game-ui')->with("nochat",true)
                
        @include('games.player-ui')->with("type","visitor")->with("player",$game->visitor( ))
        
    </section>
@endsection