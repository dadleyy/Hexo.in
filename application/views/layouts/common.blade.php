<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">    
    
    @include('globals.headerinfo')
    
    @yield('styles')
    
    @render('globals.tests')
    
</head>
<body>
    
    @include('layouts.header')
    
    <section class="content pw whole cf">
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