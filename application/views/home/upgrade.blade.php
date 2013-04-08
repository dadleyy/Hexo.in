<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">    
    @include('globals.headerinfo')
    @yield('styles')
    <link rel="stylesheet" type="text/css" href="/css/about.css">
</head>
<body>
    @include('layouts.header')
    
    <section class="content whole cf">
        <section class="main-content spw middle about cf">
            <h1>Browser<br><em>Support</em></h1>
            <article class="two">
                
                <div class="two f cf left">
                <div class="inner">
                    <p class="big">Hexo.in is designed for modern browsers.</p>
                    <p class="reg">If you were sent to this page, there is a chance that need to upgrade your browser to play the game. The good news is that there are tons of free browsers that we do support, and you will probably love the way it looks! On the right side of this page, we have provided you with a list of awesome browsers that will get the job done. Happy browsing!</p>
                </div>
                </div>
                
                <div class="two f cf right">
                <div class="inner">
                    <p class="big">Here is a list of good browsers:</p>
                    <ul>
                        <li><a href="http://google.com/chrome">Google<em>Chrome</em></a></li>
                        <li><a href="http://www.mozilla.org/firefox">Mozilla<em>Firefox</em></a></li>
                        <li><a href="http://www.apple.com/safari/">Apple<em>Safari</em></a></li>
                        <li><a href="http://www.opera.com/"><em>Opera</em></a></li>
                    </ul>
                </div>
                </div>
                
            </article>
        </section>
    </section>
    
    @render('layouts.footer')
</body>
</html>