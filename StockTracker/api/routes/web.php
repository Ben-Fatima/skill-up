<?php

/** @var \Laravel\Lumen\Routing\Router $router */

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

$router->get('/products', 'ProductController@index');
$router->post('/products', 'ProductController@store');
$router->get('/products/{id:[1-9][0-9]*}', 'ProductController@show');
$router->patch('/products/{id:[1-9][0-9]*}', 'ProductController@update');
$router->delete('/products/{id:[1-9][0-9]*}', 'ProductController@destroy');

$router->get('/locations', 'LocationController@index');
$router->post('/locations', 'LocationController@store');
$router->get('/locations/{id:[1-9][0-9]*}', 'LocationController@show');
$router->patch('/locations/{id:[1-9][0-9]*}', 'LocationController@update');
$router->delete('/locations/{id:[1-9][0-9]*}', 'LocationController@destroy');

$router->get('/movements', 'MovementController@index');
$router->post('/movements', 'MovementController@store');
