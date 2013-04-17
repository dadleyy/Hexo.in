@section('scripts')

<script type="text/javascript" src="{{ asset("js/home.js") }}"></script>

<script type="text/template" data-name="online-user">
    <li class="user cf"> 
        <% if (busy) { %><span class="icon busy f"></span><% } else { %>
        <button class="t challenge slick f gr3" data-user="<%= username %>" data-type="challenge">+</button>
        <% } %>
        <h1 class="name f"><%= username %></h2>
        <h1 class="amount r"><%= losses %></h2>
        <h1 class="amount r"><%= wins %></h2>
    </li>
</script>

<script type="text/template" data-name="chatroom-list-item-template">
    <li class="obj chat-room cf">
        <button class="t quick-join slick f gr3" data-id="<%= room_id %>" data-type="chat-room">+</button>
        <h1 class="name f"><%= room_name %></h1>
        <h2 class="count r cf">
            <span class="f">Population:</span>
            <span class="f count"><%= room_count %></span>
        </h2>
    </li>
</script>

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