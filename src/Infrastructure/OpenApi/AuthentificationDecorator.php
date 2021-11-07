<?php

namespace App\Infrastructure\OpenApi;

use ApiPlatform\Core\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\Core\OpenApi\Model;
use ApiPlatform\Core\OpenApi\OpenApi;

class AuthentificationDecorator implements OpenApiFactoryInterface
{
    private OpenApiFactoryInterface $decorated;

    public function __construct(
        OpenApiFactoryInterface $decorated
    ) {
        $this->decorated = $decorated;
    }

    /**
     * @param array<array-key, mixed> $context
     */
    public function __invoke(array $context = []): OpenApi
    {
        $openApi = ($this->decorated)($context);

        $this->appendAuthenticationSchemas($openApi);
        $this->appendUserAuthentication($openApi);

        return $openApi;
    }

    private function appendAuthenticationSchemas(OpenApi $openApi): void
    {
        $schemas = $openApi->getComponents()->getSchemas();
        assert($schemas instanceof \ArrayObject);

        $schemas['AuthenticationToken'] = new \ArrayObject([
            'type' => 'object',
            'properties' => [
                'access_token' => [
                    'type' => 'string',
                    'readOnly' => true,
                    'example' => 'eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJpYXQiOjE2Mjg',
                ],
                'expires_in' => [
                    'type' => 'integer',
                    'readOnly' => true,
                    'example' => 3600,
                ],
                'token_type' => [
                    'type' => 'string',
                    'readOnly' => true,
                    'example' => 'Bearer',
                ],
            ],
        ]);

        $schemas['AuthenticationError'] = new \ArrayObject([
            'type' => 'object',
            'properties' => [
                'error' => [
                    'type' => 'string',
                    'readOnly' => true,
                    'example' => 'invalid_credentials',
                ],
                'error_description' => [
                    'type' => 'string',
                    'readOnly' => true,
                    'example' => 'The user credentials were incorrect',
                ],
            ],
        ]);
    }

    private function appendUserAuthentication(OpenApi $openApi): void
    {
        $pathItem = new Model\PathItem(
            ref: 'User Token',
            post: new Model\Operation(
                operationId: 'postUserCredentialsItem',
                tags: ['Authentication'],
                responses: [
                    '200' => [
                        'description' => 'Token created',
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    '$ref' => '#/components/schemas/AuthenticationToken',
                                ],
                            ],
                        ],
                    ],
                    '401' => [
                        'description' => 'Authentication error',
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    '$ref' => '#/components/schemas/AuthenticationError',
                                ],
                            ],
                        ],
                    ],
                ],
                summary: 'Creates token for User authentication.',
                requestBody: new Model\RequestBody(
                    content: new \ArrayObject(
                        [
                            'application/json' => [
                                'schema' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'email' => [
                                            'type' => 'string',
                                            'example' => 'johndoe@example.com',
                                        ],
                                        'password' => [
                                            'type' => 'string',
                                            'example' => 'apassword',
                                        ],
                                    ],
                                ],
                            ],
                        ]
                    ),
                ),
                security: [],
            ),
        );

        $openApi->getPaths()->addPath('/login', $pathItem);
    }
}
