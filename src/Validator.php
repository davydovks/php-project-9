<?php

namespace Validator;

function translateNameValidationErrors(array|bool $errors)
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
