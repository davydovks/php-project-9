<?php

require __DIR__ . '/../vendor/autoload.php';

use DI\Container;
use Slim\Factory\AppFactory;
use Slim\Views\Twig;
use Slim\Views\TwigMiddleware;
use Slim\Middleware\MethodOverrideMiddleware;

$container = new Container();

$container->set('view', function () {
    return Twig::create(__DIR__ . '/../templates');
});

$app = AppFactory::createFromContainer($container);

$app->add(TwigMiddleware::createFromContainer($app));
$app->add(MethodOverrideMiddleware::class);
$app->addErrorMiddleware(true, true, true);

$app->get('/', function ($request, $response) {
    return $this->get('view')->render($response, 'index.twig');
})->setName('home');

$app->run();