@extends('layouts.app')

@section('page-title', 'News Portal - Latest news')

@section('page-content')

    @include('layouts.partials.header')

    <main class="main">

        <section class="section latest-news">

            <h2 class="section__title">Latest News</h2>

            @if($articles->count() != 0)

                @foreach($articles as $article)

                        <article class="article-box">
                            <div class="article-box__media">
                                <img class="article-box__img" src="{{asset('storage/articles/' . $article->hash_id . '/thumbnail_small.jpg')}}" alt="{{$article->title}}">
                            </div>
                            <div class="article-box__content">
                                <a class="link" href="{{route('article.show', $article->hash_id)}}"><h3 class="article-box__title">{{$article->title}}</h3></a>
                                <div class="article-box__info">
                                    <span class="article-box__author text">
                                        <i class="fas fa-user"></i>
                                        <a class='link' href="{{route('editor.show', $article->editor_id)}}">
                                            {{$article->author_fname . ' ' . $article->author_lname}}
                                        </a>
                                    </span>
                                    <span class="article-box__time">
                                        <i class="far fa-clock"></i>
                                        {{$article->time_posted}}
                                    </span>
                                    <span class="article-box__category">
                                        {{$article->category_name}}
                                    </span>
                                </div>
                            </div>
                        </article>

                @endforeach

            @else 

                    <div class="no-articles text">
                        <i class="fas fa-search no-articles__icon"></i>
                        No articles to show.
                    </div>

            @endif

        </section>

    </main>

    @include('layouts.partials.footer')

@endsection