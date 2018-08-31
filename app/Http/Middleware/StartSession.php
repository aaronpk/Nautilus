<?php
namespace App\Http\Middleware;

use Closure, Request, Config, Route;
use Illuminate\Session\Middleware\StartSession as BaseStartSession;

class StartSession extends BaseStartSession {

  public function handle($request, Closure $next) {

    if(!isset($_COOKIE['laravel_session'])) {
      Config::set('session.driver', 'array');
    }

    return parent::handle($request, $next);
  }

}
