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

/**
 * @var $router \Laravel\Lumen\Routing\Router
 */
$router->get('/admin/sync_events', "AdminController@syncEvents");
$router->get('/event', "EventController@index");
$router->get('/event/{uid}', "EventController@get");
$router->get('/event/{event_uid}/{event_node_uid}/submissions', "EventSubmissionsController@get");

$router->post('/submit/run', "SubmitRunController@post");
$router->post('/submit/revert', "SubmitRevertController@post");
