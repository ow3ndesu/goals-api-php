<?php
use Slim\App;

return function (App $app) {
    // Swagger UI main page
    $app->get('/docs', function ($req, $res) {
        $html = <<<HTML
        <!DOCTYPE html>
        <html>
        <head>
        <title>Swagger UI</title>
        <link rel="stylesheet" href="https://unpkg.com/swagger-ui-dist/swagger-ui.css">
        </head>
        <body>
        <div id="swagger-ui"></div>
        <script src="https://unpkg.com/swagger-ui-dist/swagger-ui-bundle.js"></script>
        <script>
            window.onload = () => {
            SwaggerUIBundle({
                url: '/openapi.yaml',
                dom_id: '#swagger-ui'
            });
            }
        </script>
        </body>
        </html>
        HTML;

        $res->getBody()->write($html);
        return $res->withHeader('Content-Type', 'text/html');
    });

    $app->get('/openapi.yaml', function ($req, $res) {
        $yamlPath = __DIR__ . '/../../openapi.yaml'; // adjust path as needed

        if (!file_exists($yamlPath)) {
            $res->getBody()->write("openapi.yaml not found");
            return $res->withStatus(404);
        }

        $yaml = file_get_contents($yamlPath);
        $res->getBody()->write($yaml);
        return $res->withHeader('Content-Type', 'application/x-yaml');
    });

};