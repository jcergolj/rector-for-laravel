<?php

Route::post('posts/{post}/publish', [PostController::class, 'publish']);
Route::post('posts/{post}', [PostController::class, 'destroy']);
Route::get('posts/{post}', [PostController::class, 'edit']);
