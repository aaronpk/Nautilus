<?php

Route::get('/.well-known/webfinger', '\App\Http\Controllers\WebfingerController@webfinger');

Route::get('/{username}', '\App\Http\Controllers\UserController@get');
Route::post('/{username}/inbox', '\App\Http\Controllers\ActivityPubController@postInbox');
Route::get('/{username}/outbox', '\App\Http\Controllers\ActivityPubController@getOutbox');
