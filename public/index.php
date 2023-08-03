<?php

require __DIR__ . '/../vendor/autoload.php';

use DI\Container;
use Repository\CookieRepository;
use Slim\Factory\AppFactory;
use Slim\Views\Twig;
use Slim\Views\TwigMiddleware;
use Slim\Middleware\MethodOverrideMiddleware;

session_start();

$repo = new CookieRepository('urls');

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

$app->get('/urls', function ($request, $response) use ($repo) {
    $urls = $repo->all($request);

    return $this->get('view')->render($response, 'urls/index.twig', [
        'urls' => $urls
    ]);
})->setName('urls.index');

$app->post('/urls', function ($request, $response) use ($repo) {
    $url = $request->getParsedBodyParam('url');

    //$validator = new Validators\UserValidator();
    $errors = []; //$validator->validate($user);

    if (count($errors) === 0) {
        $repo->save($url, $request, $response);
        //$this->get('flash')->addMessage('success', 'User added successfully!');

        return $response->withRedirect('/urls', 302);
        //return $response->withRedirect($router->urlFor('users.index'), 302);
    }

    return $this->get('view')->render($response, 'index.twig', [
        'url' => $url,
        'errors' => $errors
    ]);
})->setName('urls.store');

$app->get('/urls/{id}', function ($request, $response, $args) {
    return $this->get('view')->render($response, 'urls/show.twig', [
        'id' => $args['id']
    ]);
})->setName('urls.show');


$app->run();