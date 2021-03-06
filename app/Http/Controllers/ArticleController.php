<?php

namespace App\Http\Controllers;


use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Auth;
use Intervention\Image\Facades\Image;
use Illuminate\Http\Request;
use App\Http\Requests\Article\StoreArticleRequest;
use App\Models\ArticleCategory;
use App\Models\Article;
use App\Models\Comment;

class ArticleController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth')->except('show');
        $this->middleware(['ajax', 'throttle:4'])->only('rate');
    }

    /**
     * Show the form for creating a new article
     *
     * @return \Illuminate\Http\Response
     */
    public function create(ArticleCategory $articleCat)
    {
        // Only editors can access this page
        if (! Gate::allows('access-editor-pages')) {
            abort(403);
        }

        // Retrieve all article categories so we can create a dropdown select option
        $categories = $articleCat->getAll()->toArray();

        $categorySelect = [];

        foreach ($categories as $category) {

            $categorySelect[$category->id] = $category->name;
        }

        return view('article.create', ['categories' => $categorySelect]);
    }

    /**
     * Store a new article in database
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Article $article, StoreArticleRequest $request)
    {
        $data = $request->validated();
        // The unique string that will be used as article id in urls ( news-portal.com/article/ab94njnz9j3njna <--)
        $articleHashId = bin2hex(random_bytes(20));

        // Check if the image has been provided, and if so store the image
        if ($request->has('image')) {

            $storedImgPath = $request->file('image')->storeAs(
                'articles/' . $articleHashId,
                'thumbnail_main.' . $request->file('image')->extension(),
                'public'
            );

            // Now make a resized copy of the image for smaller thumbnails
            $secondImg = Image::make(storage_path("app/public/" . $storedImgPath));

            $secondImg->resize(250, null, function($constraint) {
                $constraint->aspectRatio();
            });

            $secondImg->save(storage_path("app/public/articles/" . $articleHashId) . '/thumbnail_small.' . $secondImg->extension);
        }

        $data['author_id'] = Auth::id();
        $data['hash_id'] = $articleHashId;
        $data['image'] = $storedImgPath ?? null;

        $status = $article->create($data);
        
        if ($status) {

            return redirect()->route('home.index');

        } else {

            // We failed to create the article
            return redirect()->route('article.create_form')->withErrors(['store_error' => 'Something went wrong.']);
        }
    }

    /**
     * Display the specified article using the hash id
     *
     * @return \Illuminate\Http\Response
     */
    public function show($hashId, Article $article, Comment $comment)
    {
        $articleToShow = $article->getByHashId($hashId);

        if ($articleToShow->isEmpty()) {

            // If not article was found, return 404 not found error
            abort(404);
        }

        $articleToShow = $articleToShow->first();

        // Retrieve the comments for this article
        $commentsPaginator = $comment->getArticleCommentsPaginated($articleToShow->id);

        // Calculate the average rating for the article
        $rating = $article->calculateAvgRating($articleToShow->id);

        return view('article.show', ['article' => $articleToShow, 'commentsPaginator' => $commentsPaginator, 'rating' => $rating]);
    }

     /**
     * Process user rating of article
     *
     */
    public function rate($hashId, Request $request, Article $article) 
    {
        $articleByHash = $article->getByHashId($hashId);

        if ($articleByHash->isEmpty()) {

            abort(404);
        }

        $articleByHash = $articleByHash->first();

        // This should prevent editors from rating their own articles
        // Temp, try to find a better way to prevent editors, maybe even somehow stopping them from sending requests to this route
        if (! Gate::allows('vote-for-article', $articleByHash)) {
            abort(401);
        }

        if ( $request->has('rating') && in_array($request->get('rating'), $article->allowedRatings()) ) {

            $ratingData = [
                'user_id' => Auth::user()->id,
                'article_id' => $articleByHash->id,
                'rating' => $request->get('rating'),
            ];

            // This would be false if nothing was updated/inserted
            $status = $article->rate($ratingData);

            // Calculate the average rating for the article
            $rating = $article->calculateAvgRating($articleByHash->id);

            return response(['status' => (bool)$status, 'avg' => $rating->avg, 'total' => $rating->total]);
            
        } 

        return response(['status' => false]);
        
    }
}
