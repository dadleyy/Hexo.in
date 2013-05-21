<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">    
    
    @include('globals.headerinfo')
    
    @yield('styles')
    
    @render('globals.tests')
    
</head>
<body>
    
    @include('globals.header')
    
    <section class="content pw whole cf">
    @yield('content')
    </section>
    
    @render('globals.footer')
    
    @if ( Auth::check() )
    @render( 'partials.pulldowns' )
    @endif    
   
    @render('globals.scripts')
    @yield('scripts')
    
</body>
</html>