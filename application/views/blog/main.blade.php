@section('styles')
<link rel="stylesheet" type="text/css" href="/css/blog.css">
@endsection

@section('scripts')

@endsection

@section('content')
<section class="main-content spw middle">
     
     <section class="posts cf">
     <?php $posts = Blogpost::all( ); ?>
     @foreach( $posts as $post )
        <article class="post">
            <div class="inner">
                <div class="cf">
                    <h3 class="date f">{{ $post->posted( ) }}</h3>
                    <h3 class="author r">{{ $post->author( )->username }}</h3>
                </div>
                <h1>{{ $post->title }}</h1>
                <p>{{ substr( $post->content, 0, 140 ) }}...</p>
                <a href="/blog/post/{{ $post->id }}" class="t" title="">read more</a>
            </div>
        </article>
     @endforeach
     </section>
     
</section>
@endsection