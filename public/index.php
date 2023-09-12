<?php

require __DIR__ . '/../vendor/autoload.php';

use DI\Container;
use Slim\Factory\AppFactory;
use Slim\Views\Twig;
use Slim\Views\TwigMiddleware;
use Slim\Middleware\MethodOverrideMiddleware;
use Carbon\Carbon;
use PageAnalyzer\Parser;
use Repository\DBRepository;

use function Validator\createNameValidator;
use function Validator\translateNameValidationErrors;

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
$errorMiddleware = $app->addErrorMiddleware(true, true, true);

$customErrorHandler = function ($request, $exception) use ($app) {
    $response = $app->getResponseFactory()->createResponse();
    if ($exception->getCode() == 404) {
        return $this->get('view')->render($response, 'errors/404.twig')
            ->withStatus(404);
    }

    return $response;
};

$errorMiddleware->setDefaultErrorHandler($customErrorHandler);

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
    $validator = createNameValidator($enteredUrl);

    if (!$validator->validate()) {
        $errors = $validator->errors();
        $translatedErrors = translateNameValidationErrors($errors);
        return $this->get('view')->render($response, 'index.twig', [
            'url' => $enteredUrl,
            'messages' => $this->get('flash')->getMessages(),
            'errors' => $translatedErrors
        ])->withStatus(422);
    }

    $normalizedUrl = Parser::normalizeUrl($enteredUrl);

    $existingUrl = $repoUrls->find('name', $normalizedUrl['name']);
    if (!empty($existingUrl)) {
        $this->get('flash')->addMessage('success', 'Страница уже существует');
        return $response->withRedirect($router->urlFor('urls.show', ['id' => $existingUrl['id']]), 302);
    }

    $normalizedUrl['created_at'] = Carbon::now()->toDateTimeString();
    $repoUrls->save($normalizedUrl);
    $createdUrl = $repoUrls->find('name', $normalizedUrl['name']);
    $this->get('flash')->addMessage('success', 'Страница успешно добавлена');

    return $response->withRedirect($router->urlFor('urls.show', ['id' => $createdUrl['id']]), 302);
})->setName('urls.store');

$app->get('/urls/{id:[0-9]+}', function ($request, $response, $args) use ($repoUrls, $repoChecks) {
    $url = $repoUrls->find('id', $args['id']);

    if (empty($url)) {
        return $this->get('view')->render($response, 'errors/404.twig')
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
        return $this->get('view')->render($response, 'errors/500.twig')
            ->withStatus(500);
    }

    $check = Parser::getUrlData($url);

    if (!isset($check['status_code'])) {
        $this->get('flash')
                ->addMessage('danger', 'Произошла ошибка при проверке, не удалось подключиться');
    } else {
        if ($check['status_code'] == 200) {
            $this->get('flash')
                ->addMessage('success', 'Страница успешно проверена');
        } else {
            $this->get('flash')
                ->addMessage('warning', 'Проверка была выполнена успешно, но сервер ответил с ошибкой');
        }

        if (!isset($check['url_id'])) {
            return $this->get('view')->render($response, 'errors/500.twig')
                ->withStatus(500);
        }

        $repoChecks->save($check);
    }

    return $response->withRedirect($router->urlFor('urls.show', ['id' => $url['id']]), 302);
})->setName('urls.checks.store');

$app->get('/assets/{filename}', function ($request, $response, $args) {
    $filename = $args['filename'];
    $path = __DIR__ . "/assets/{$filename}";
    $image = file_get_contents($path);
    if ($image === false) {
        $handler = $this->notFoundHandler;
        return $handler($request, $response);
    }

    $response->write($image);
    return $response->withHeader('Content-Type', 'image/svg+xml');
});

$app->run();
