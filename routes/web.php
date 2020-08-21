<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$router->get('/', function () use ($router) {
    return $router->app->version();
});

$router->group(['prefix' => 'api/v1/'], function ($router) {
    //Sätt alltid statiska routes(entries/search) före dynamiska(entries/{id})

    $router->get('checkJWT','JWTController@index');

    //Formulär
    $router->get('forms','FormController@index');
    $router->get('forms/{id}','FormController@getForm');

    //Request material
    $router->post('requestmaterial','RequestController@createRequest');

    //Contact Us
    $router->post('contact','ContactController@sendContactMail');

    //Consultation 
    $router->post('consultation','ConsultationController@sendConsultationMail');

    //Literature serach 
    $router->post('literaturesearch','LiteraturesearchController@sendLiteraturesearchMail');

    //Teachingactivity 
    $router->post('teachingactivity','TeachingactivityController@sendTeachingactivityMail');

    //Libraryaccount 
    $router->post('libraryaccount','LibraryaccountController@createLibraryaccount');
    $router->post('activatelibraryaccount','LibraryaccountController@activateLibraryaccount');

    //Siyss
    $router->post('siyss','SiyssController@sendSiyssMail');

});
// /orders?sort=-created_at