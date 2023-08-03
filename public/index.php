<?php

require __DIR__ . '/../vendor/autoload.php';

use DI\Container;
use Repository\CookieRepository;
use Slim\Factory\AppFactory;
use Slim\Views\Twig;
use Slim\Views\TwigMiddleware;
use Slim\Middleware\MethodOverrideMiddleware;
use Valitron\Validator;
use Carbon\Carbon;

session_start();

// Need to replace with DBRepo
$repo = new CookieRepository('urls');

$container = new Container();

$container->set('view', function () {
    return Twig::create(__DIR__ . '/../templates');
});

$app = AppFactory::createFromContainer($container);

$app->add(TwigMiddleware::createFromContainer($app));
$app->add(MethodOverrideMiddleware::class);
$app->addErrorMiddleware(true, true, true);

$router = $app->getRouteCollector()->getRouteParser();

$app->get('/', function ($request, $response) {
    return $this->get('view')->render($response, 'index.twig');
})->setName('home');

$app->get('/urls', function ($request, $response) use ($repo) {
    $urls = $repo->all($request);

    return $this->get('view')->render($response, 'urls/index.twig', [
        'urls' => $urls
    ]);
})->setName('urls.index');

$app->post('/urls', function ($request, $response) use ($repo, $router) {
    $url = $request->getParsedBodyParam('url');

    $validator = new Validator($url);
    $validator->rule('required', 'name');
    $validator->rule('url', 'name');
    $validator->rule('lengthMax', 'name', 255);
    
    if ($validator->validate()) {
        $createdAt = Carbon::now()->toDateTimeString();

        $repo->save($url, $createdAt, $request, $response);
        // Uncomment after flash is installed and used
        //$this->get('flash')->addMessage('success', 'User added successfully!');

        return $response->withRedirect($router->urlFor('urls.index'), 302);
    }

    return $this->get('view')->render($response, 'index.twig', [
        'url' => $url,
        'errors' => $validator->errors()
    ]);
})->setName('urls.store');

$app->get('/urls/{id}', function ($request, $response, $args) use ($repo) {
    $url = $repo->find($args['id'], $request);

    if (empty($url)) {
        return $this->get('view')->render($response, '404.twig')
            ->withStatus(404);
    }

    return $this->get('view')->render($response, 'urls/show.twig', [
        'url' => $url
    ]);
})->setName('urls.show');

$app->get('/assets/{filename}', function ($request, $response, $args) {
    $filename = $args['filename'];
    $path = __DIR__ . "/../assets/{$filename}";
    $image = file_get_contents($path);
    if ($image === false) {
        $handler = $this->notFoundHandler;
        return $handler($request, $response);
    }

    $response->write($image);
    return $response->withHeader('Content-Type', 'image/svg+xml');
});

$app->run();
