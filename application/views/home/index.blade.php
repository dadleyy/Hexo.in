@section('scripts')
    
@endsection

@section('content')
    <section class="main-content spw middle">
        
        <section class="cf general basic two">
            
            @render('layouts.leaderboard-stats')
            
            
            @render('layouts.quickmenu')
        
        </section>
        
        <section class="cf listings basic two">
            
            @render('layouts.gamerooms')
            
            @render('layouts.chatrooms')
    
        </section>
        
    </section>
@endsection