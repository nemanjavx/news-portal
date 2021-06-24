<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Login;
use App\Models\Comment;
use Illuminate\Support\Facades\Session;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class CollectUserVotes
{
    private $comment;

    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        $this->comment = new Comment();
    }

    /**
     * Handle the event.
     * On every user login, retrieve all the comments/items user has voted (upvoted/downvoted) for, and store it in the session for later use
     * 
     * @param  Login  $event
     * @return void
     */
    public function handle(Login $event)
    {
        $user = $event->user;

        $votedComments = $this->comment->getUserVotedComments($user->id);

        if ($votedComments->isNotEmpty()) {

            $votedComments = $votedComments->toArray();

            $votedItems = [
                'comments' => []
            ];

            foreach ($votedComments as $votedComment) {

                $votedItems['comments'][$votedComment->comment_id] = $votedComment;
            }

            Session::put('voted_items', $votedItems);
        }
    }
}
