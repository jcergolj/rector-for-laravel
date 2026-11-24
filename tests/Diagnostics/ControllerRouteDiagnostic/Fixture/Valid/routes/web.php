<?php

use Illuminate\Support\Facades\Route as Router;

Router::resource('posts', PostController::class);
Router::apiResource('comments', CommentController::class);
Router::post('posts/{post}/publication', PublishPostController::class);
Router::get('posts', [PostController::class, 'index']);
Router::get('posts/create', [PostController::class, 'create']);
Router::get('posts/{post:slug}/comments/{comment}/edit', [CommentController::class, 'edit']);
Router::patch('posts/{post}', [PostController::class, 'update']);
Router::get('health', fn () => 'OK');
