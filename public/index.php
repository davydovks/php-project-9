<?php

require __DIR__ . '/../vendor/autoload.php';

use DI\Container;
use Slim\Factory\AppFactory;
use Slim\Views\Twig;
use Slim\Views\TwigMiddleware;
use Slim\Middleware\MethodOverrideMiddleware;
use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ServerException;
use PageAnalyzer\Parser;
use Repository\DBRepository;
use Valitron\Validator;

session_start();

$repoUrls = new DBRepository('urls');
$repoChecks = new DBRepository('url_checks');

$container = new Container();

$container->set('flash', function () {
    return new \Slim\Flash\Messages();
});

$container->set('view', function () use ($container) {
    $view = Twig::create(__DIR__ . '/../templates');
    $view->getEnvironment()->addGlobal('messages', $container->get('flash')->getMessages());
    return $view;
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
    return $this->get('view')->render($response, 'index.twig');
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
        'urls' => $urlsEnriched
    ]);
})->setName('urls.index');

$app->post('/urls', function ($request, $response) use ($repoUrls, $router) {
    $enteredUrl = $request->getParsedBodyParam('url');

    $validator = new Validator($enteredUrl);
    $validator->rule('required', 'name')->message('URL не должен быть пустым');
    $validator->rule('url', 'name')->message('Некорректный URL');
    $validator->rule('lengthMax', 'name', 255)->message('Некорректный URL');

    if (!$validator->validate()) {
        return $this->get('view')->render($response, 'index.twig', [
            'url' => $enteredUrl,
            'errors' => $validator->errors()
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
        'checks' => $checks
    ]);
})->setName('urls.show');

$app->post('/urls/{url_id:[0-9]+}/checks', function ($request, $response, $args) use ($repoUrls, $repoChecks, $router) {
    $url = $repoUrls->find('id', $args['url_id']);

    if (empty($url)) {
        return $this->get('view')->render($response, 'errors/500.twig')
            ->withStatus(500);
    }

    $client = new Client();
    try {
        $urlResponse = $client->get($url['name']);
        $check = Parser::parseResponse($urlResponse);
        $check['url_id'] = $url['id'];
        $message = 'Страница успешно проверена';
        $this->get('flash')->addMessage('success', $message);
        $repoChecks->save($check);
        return $response->withRedirect($router->urlFor('urls.show', ['id' => $url['id']]), 302);
    } catch (ClientException $e) {
        $urlResponse = $e->getResponse();
        $check = Parser::parseResponse($urlResponse);
        $check['url_id'] = $url['id'];
        $message = 'Проверка была выполнена успешно, но сервер ответил с ошибкой';
        $this->get('flash')->addMessage('warning', $message);
        $repoChecks->save($check);
        return $response->withRedirect($router->urlFor('urls.show', ['id' => $url['id']]), 302);
    } catch (ConnectException | ServerException) {
        $message = 'Произошла ошибка при проверке, не удалось подключиться';
        $this->get('flash')->addMessage('danger', $message);
        return $response->withRedirect($router->urlFor('urls.show', ['id' => $url['id']]), 302);
    } catch (RequestException) {
        $message = 'Проверка была выполнена успешно, но сервер ответил с ошибкой';
        $this->get('flash')->addMessage('warning', $message);
        return $this->get('view')->render($response, 'errors/500.twig')->withStatus(500);
    }
})->setName('urls.checks.store');

$app->run();
