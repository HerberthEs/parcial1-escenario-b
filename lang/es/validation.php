<?php

// Mensajes de validación en español (solo las reglas que usa la API).
return [
    'required' => 'El campo :attribute es obligatorio.',
    'string' => 'El campo :attribute debe ser texto.',
    'integer' => 'El campo :attribute debe ser un número entero.',
    'array' => 'El campo :attribute debe ser una lista.',
    'in' => 'El valor de :attribute no es válido.',
    'prohibited' => 'El campo :attribute no se puede enviar.',
    'date_format' => 'El campo :attribute debe tener el formato :format.',
    'after_or_equal' => 'El campo :attribute debe ser una fecha igual o posterior a :date.',
    'distinct' => 'El campo :attribute tiene un valor duplicado.',
    'between' => [
        'numeric' => 'El campo :attribute debe estar entre :min y :max.',
        'string' => 'El campo :attribute debe tener entre :min y :max caracteres.',
        'array' => 'El campo :attribute debe tener entre :min y :max elementos.',
    ],
    'min' => [
        'numeric' => 'El campo :attribute debe ser al menos :min.',
        'string' => 'El campo :attribute debe tener al menos :min caracteres.',
        'array' => 'El campo :attribute debe tener al menos :min elementos.',
    ],
    'max' => [
        'numeric' => 'El campo :attribute no debe ser mayor que :max.',
        'string' => 'El campo :attribute no debe tener más de :max caracteres.',
        'array' => 'El campo :attribute no debe tener más de :max elementos.',
    ],
    'attributes' => [],
];
