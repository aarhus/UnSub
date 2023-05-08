<?php

Route::group(['middleware' => 'web', 'prefix' => 'unsubscriber', 'namespace' => 'Modules\UnSub\Http\Controllers'], function()
{

	Route::get(
		'/{mailbox_id}/{conversation_id}',
		['uses' => 'UnSubController@index'])->name('unsub.index');

});
