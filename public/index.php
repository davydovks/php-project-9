<?php

require __DIR__ . '/../vendor/autoload.php';

use App\Entity\Url;
use App\Repository\UrlChecksRepository;
use App\Repository\UrlsRepository;
use App\Parser;
use DI\Container;
use Dotenv\Dotenv;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ServerException;
use Slim\Factory\AppFactory;
use Slim\Flash\Messages;
use Slim\Views\Twig;
use Slim\Views\TwigMiddleware;
use Slim\Middleware\MethodOverrideMiddleware;
use Valitron\Validator;

session_start();

$envPath = __DIR__ . '/../';
if (file_exists($envPath . '.env')) {
    $dotenv = Dotenv::createImmutable($envPath);
    $dotenv->load();
}

$container = new Container();

$container->set('urlsRepo', $container->get(UrlsRepository::class));
$container->set('checksRepo', $container->get(UrlChecksRepository::class));

$container->set('flash', function () {
    return new Messages();
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

$app->get('/urls', function ($request, $response) {
    $urls = $this->get('urlsRepo')->all();
    $urlsEnriched = array_map(function (Url $url) {
        $check = $this->get('checksRepo')->findLastByUrlId($url->getId());
        if (!empty($check)) {
            $url->setLastCheckStatus($check->getStatusCode());
            $url->setLastCheckedAt($check->getCreatedAt());
        }

        return $url;
    }, $urls);

    return $this->get('view')->render($response, 'urls/index.twig', [
        'urls' => $urlsEnriched
    ]);
})->setName('urls.index');

$app->post('/urls', function ($request, $response) use ($router) {
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

    $existingUrl = $this->get('urlsRepo')->findOneByName($normalizedUrl->getName());
    if (!empty($existingUrl)) {
        $this->get('flash')->addMessage('success', 'Страница уже существует');
        return $response->withRedirect($router->urlFor('urls.show', ['id' => $existingUrl->getId()]), 302);
    }

    $createdId = $this->get('urlsRepo')->save($normalizedUrl);
    $this->get('flash')->addMessage('success', 'Страница успешно добавлена');

    return $response->withRedirect($router->urlFor('urls.show', ['id' => $createdId]), 302);
})->setName('urls.store');

$app->get('/urls/{id:[0-9]+}', function ($request, $response, $args) {
    $url = $this->get('urlsRepo')->findOneById($args['id']);

    if (empty($url)) {
        return $this->get('view')->render($response, 'errors/404.twig')
            ->withStatus(404);
    }

    $checks = $this->get('checksRepo')->findAllByUrlId($url->getId());

    return $this->get('view')->render($response, 'urls/show.twig', [
        'url' => $url,
        'checks' => $checks
    ]);
})->setName('urls.show');

$app->post('/urls/{urlId:\d+}/checks', function ($request, $response, $args) use ($router) {
    $urlId = $args['urlId'];
    $url = $this->get('urlsRepo')->findOneById($urlId);

    if (empty($url)) {
        return $this->get('view')->render($response, 'errors/500.twig')
            ->withStatus(500);
    }

    $client = new Client();
    try {
        $urlResponse = $client->get($url->getName());
        $check = Parser::parseResponse($urlResponse);
        $check->setUrlId($urlId);
        $message = 'Страница успешно проверена';
        $this->get('flash')->addMessage('success', $message);
        $this->get('checksRepo')->save($check);
        return $response->withRedirect($router->urlFor('urls.show', ['id' => $urlId]), 302);
    } catch (ClientException $e) {
        $urlResponse = $e->getResponse();
        $check = Parser::parseResponse($urlResponse);
        $check->setUrlId($urlId);
        $message = 'Проверка была выполнена успешно, но сервер ответил с ошибкой';
        $this->get('flash')->addMessage('warning', $message);
        $this->get('checksRepo')->save($check);
        return $response->withRedirect($router->urlFor('urls.show', ['id' => $urlId]), 302);
    } catch (ConnectException | ServerException) {
        $message = 'Произошла ошибка при проверке, не удалось подключиться';
        $this->get('flash')->addMessage('danger', $message);
        return $response->withRedirect($router->urlFor('urls.show', ['id' => $urlId]), 302);
    } catch (RequestException) {
        $message = 'Проверка была выполнена успешно, но сервер ответил с ошибкой';
        $this->get('flash')->addMessage('warning', $message);
        return $this->get('view')->render($response, 'errors/500.twig')->withStatus(500);
    }
})->setName('urls.checks.store');

$app->run();
