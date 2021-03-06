<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        // 'App\Models\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        Gate::define('access-editor-pages', function(User $user) {
            return $user->isEditor();
        });
        
        Gate::define('vote-for-article', function(User $user, $article) {
            // Only logged users, and users who are not authors can voted for the articles
            // This would allow other editors to also vote for someones article, change that? (editors can't vote for articles?)
            return $article->author_id !== $user->id;
        });

    }
}
