<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">    
    @include('globals.headerinfo')
    @yield('styles')
    
</head>
<body>
    
    @include('layouts.header')
    
    <section class="content whole cf">
    @yield('content')
    </section>
    
    @render('layouts.footer')
    
    @if ( Auth::check() )
    @render( "layouts.pulldowns" )
    @endif    
   
    @render('globals.scripts')
    @yield('scripts')
    
</body>
</html>