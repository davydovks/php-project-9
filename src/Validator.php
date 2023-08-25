<?php

namespace Validator;

use Valitron\Validator;

function createNameValidator(array $normalizedUrl): Validator
{
    $validator = new Validator($normalizedUrl);
    $validator->rule('required', 'name');
    $validator->rule('url', 'name');
    $validator->rule('lengthMax', 'name', 255);

    return $validator;
}

function translateNameValidationErrors($errors)
{
    if (!isset($errors['name'])) {
        return [];
    }

    if (in_array('Name is required', $errors['name'])) {
        $message = "URL не должен быть пустым";
    } elseif (in_array('Name is not a valid URL', $errors['name'])) {
        $message = "Некорректный URL";
    } elseif (count($errors['name']) > 0) {
        $message = $errors['name'][0];
    } else {
        $message = 'nothing';
    }
    return ['name' => $message];
}

function createIdValidator(array $args): Validator
{
    $validator = new Validator($args);
    $validator->rule('required', 'id');
    $validator->rule('integer', 'id');

    return $validator;
}
