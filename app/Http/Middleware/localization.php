<?php
namespace App\Http\Middleware;
use Closure;

class localization
{
  /**
  * Handle an incoming request.
  *
  * @param \Illuminate\Http\Request $request
  * @param \Closure $next
  * @return mixed
  */
  public function handle($request, Closure $next)
  {
    //parameter
    $locale = ($request->has('lang')) ? $request->input('lang') : 'en' ;
    //Sätt språk Lumen
    app('translator')->setLocale($locale);
    return $next($request);

    //Header
    $locale = ($request->hasHeader('X-localization')) ? $request->header('X-localization') : 'en';
    //Sätt språk Lumen
    app('translator')->setLocale($locale);
    return $next($request);
  }
}