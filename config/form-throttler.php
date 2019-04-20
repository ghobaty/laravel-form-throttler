<?php

return [

    /**
     * List of exceptions to throttle against.
     * This should contain all exceptions which might be thrown due to the user input
     */
    'exceptions'      => [
        \Illuminate\Validation\ValidationException::class,
    ],

    /**
     * The HTTP response code to send when the request is throttled.
     */
    'response-status' => \Symfony\Component\HttpFoundation\Response::HTTP_LOCKED,
];
