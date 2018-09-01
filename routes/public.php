<?php

Route::get('/.well-known/webfinger', '\App\Http\Controllers\WebfingerController@webfinger');

Route::get('/{username}.json', '\App\Http\Controllers\UserController@get_json');
Route::get('/{username}', '\App\Http\Controllers\UserController@get');
Route::post('/{username}/inbox', '\App\Http\Controllers\ActivityPubController@postInbox');
Route::get('/{username}/outbox', '\App\Http\Controllers\ActivityPubController@getOutbox');

Route::get('/{username}/{post_id}', '\App\Http\Controllers\PostController@post_permalink');

Route::post('/{username}/micropub', '\App\Http\Controllers\MicropubController@post');
Route::get('/{username}/micropub', '\App\Http\Controllers\MicropubController@get');
