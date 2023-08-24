<?php

require __DIR__ . '/../vendor/autoload.php';

use DI\Container;
use Slim\Factory\AppFactory;
use Slim\Views\Twig;
use Slim\Views\TwigMiddleware;
use Slim\Middleware\MethodOverrideMiddleware;
use Valitron\Validator;
use Carbon\Carbon;
use PageAnalyzer\Parser;
use Repository\DBRepository;

session_start();

$repoUrls = new DBRepository('urls');
$repoChecks = new DBRepository('url_checks');

$container = new Container();

$container->set('view', function () {
    return Twig::create(__DIR__ . '/../templates');
});

$container->set('flash', function () {
    return new \Slim\Flash\Messages();
});

$app = AppFactory::createFromContainer($container);

$app->add(TwigMiddleware::createFromContainer($app));
$app->add(MethodOverrideMiddleware::class);
$app->addErrorMiddleware(true, true, true);

$router = $app->getRouteCollector()->getRouteParser();

$app->get('/', function ($request, $response) {
    return $this->get('view')->render($response, 'index.twig', [
        'messages' => $this->get('flash')->getMessages()
    ]);
})->setName('home');

$app->get('/urls', function ($request, $response) use ($repoUrls, $repoChecks) {
    $urls = $repoUrls->all();
    $urlsEnriched = array_map(function ($url) use ($repoChecks) {
        $check = $repoChecks->findLast('url_id', $url['id']);
        if (!empty($check)) {
            $url['lastCheckAt'] = $check['created_at'];
            $url['lastCheckStatus'] = $check['status_code'];
        }

        return $url;
    }, $urls);

    return $this->get('view')->render($response, 'urls/index.twig', [
        'urls' => $urlsEnriched,
        'messages' => $this->get('flash')->getMessages()
    ]);
})->setName('urls.index');

$app->post('/urls', function ($request, $response) use ($repoUrls, $router) {
    $enteredUrl = $request->getParsedBodyParam('url');
    $normalizedUrl = Parser::normalizeUrl($enteredUrl);

    $validator = new Validator($normalizedUrl);
    $validator->rule('required', 'name');
    $validator->rule('url', 'name');
    $validator->rule('lengthMax', 'name', 255);

    if (!$validator->validate()) {
        return $this->get('view')->render($response, 'index.twig', [
            'url' => $enteredUrl,
            'messages' => $this->get('flash')->getMessages(),
            'errors' => $validator->errors()
        ])->withStatus(422);
    }

    $existing = $repoUrls->find('name', $normalizedUrl['name']);
    if ($existing != []) {
        $this->get('flash')->addMessage('success', 'Страница уже существует');
        return $response->withRedirect($router->urlFor('urls.show', ['id' => $existing['id']]), 302);
    }

    $normalizedUrl['created_at'] = Carbon::now()->toDateTimeString();
    $repoUrls->save($normalizedUrl);
    $createdUrl = $repoUrls->find('name', $normalizedUrl['name']);
    $this->get('flash')->addMessage('success', 'Страница успешно добавлена');

    return $response->withRedirect($router->urlFor('urls.show', ['id' => $createdUrl['id']]), 302);
})->setName('urls.store');

$app->get('/urls/{id}', function ($request, $response, $args) use ($repoUrls, $repoChecks) {
    $url = $repoUrls->find('id', $args['id']);

    if (empty($url)) {
        return $this->get('view')->render($response, '404.twig')
            ->withStatus(404);
    }

    $checks = $repoChecks->all();

    return $this->get('view')->render($response, 'urls/show.twig', [
        'url' => $url,
        'checks' => $checks,
        'messages' => $this->get('flash')->getMessages()
    ]);
})->setName('urls.show');

$app->post('/urls/{url_id}/checks', function ($request, $response, $args) use ($repoUrls, $repoChecks, $router) {
    $url = $repoUrls->find('id', $args['url_id']);

    if (empty($url)) {
        return $this->get('view')->render($response, 'oops.twig')
            ->withStatus(500);
    }

    $check = Parser::getUrlData($url);

    $pageLoadedSuccessfully = isset($check['status_code']) && $check['status_code'] == 200;

    if ($pageLoadedSuccessfully) {
        $repoChecks->save($check);
        $this->get('flash')->addMessage('success', 'Страница успешно проверена');
        return $response->withRedirect($router->urlFor('urls.show', ['id' => $url['id']]), 302);
    }

    $internalServerError = isset($check['status_code']) && $check['status_code'] == 500;

    if ($internalServerError) {
        $this->get('flash')->addMessage(
            'warning',
            'Проверка была выполнена успешно, но сервер ответил с ошибкой'
        );
        return $this->get('view')->render($response, 'oops.twig')
            ->withStatus(500);
    }

    $this->get('flash')
        ->addMessage('danger', 'Произошла ошибка при проверке, не удалось подключиться');
    return $response->withRedirect($router->urlFor('urls.show', ['id' => $url['id']]), 302);
})->setName('checks.store');

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
