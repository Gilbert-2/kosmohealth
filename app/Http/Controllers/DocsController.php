<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\File;

class DocsController extends Controller
{
    /**
     * Serve Swagger documentation
     * GET /docs/api
     */
    public function swagger()
    {
        $swaggerYamlPath = base_path('docs/api-swagger.yaml');

        $html = '
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KosmoHealth API Documentation</title>
    <link rel="stylesheet" type="text/css" href="https://unpkg.com/swagger-ui-dist@5.10.5/swagger-ui.css" />
    <style>
        html {
            box-sizing: border-box;
            overflow: -moz-scrollbars-vertical;
            overflow-y: scroll;
        }
        *, *:before, *:after {
            box-sizing: inherit;
        }
        body {
            margin:0;
            background: #fafafa;
        }
    </style>
</head>
<body>
    <div id="swagger-ui"></div>
    <script src="https://unpkg.com/swagger-ui-dist@5.10.5/swagger-ui-bundle.js"></script>
    <script src="https://unpkg.com/swagger-ui-dist@5.10.5/swagger-ui-standalone-preset.js"></script>
    <script>
        window.onload = function() {
            const ui = SwaggerUIBundle({
                url: "' . url('/api/docs/api.json') . '",
                dom_id: "#swagger-ui",
                deepLinking: true,
                presets: [
                    SwaggerUIBundle.presets.apis,
                    SwaggerUIStandalonePreset
                ],
                plugins: [
                    SwaggerUIBundle.plugins.DownloadUrl
                ],
                layout: "StandaloneLayout"
            });
        };
    </script>
</body>
</html>';

        return response($html)->header('Content-Type', 'text/html');
    }

    /**
     * Serve raw Swagger YAML
     * GET /docs/api.yaml
     */
    public function swaggerYaml()
    {
        $swaggerYaml = File::get(base_path('docs/api-swagger.yaml'));
        
        return response($swaggerYaml)
            ->header('Content-Type', 'application/x-yaml')
            ->header('Content-Disposition', 'inline; filename="kosmohealth-api.yaml"');
    }

    /**
     * Serve raw Swagger JSON
     * GET /docs/api.json
     */
    public function swaggerJson()
    {
        try {
            // If a curated YAML exists and YAML parser is available, use it
            if (class_exists('\\Symfony\\Component\\Yaml\\Yaml') && \Illuminate\Support\Facades\File::exists(base_path('docs/api-swagger.yaml'))) {
                $swaggerYaml = \Illuminate\Support\Facades\File::get(base_path('docs/api-swagger.yaml'));
                $swaggerArray = \Symfony\Component\Yaml\Yaml::parse($swaggerYaml);
                return response()->json($swaggerArray);
            }

            // Otherwise, dynamically generate a richer OpenAPI spec from registered routes
            $routes = app('router')->getRoutes();

            $paths = [];
            foreach ($routes as $route) {
                $uri = $route->uri();
                // Only include API routes
                if (strpos($uri, 'api/') !== 0) {
                    continue;
                }

                $pathKey = '/' . ltrim($uri, '/');
                // Normalize Laravel route parameter syntax {param}
                $pathKey = preg_replace('/\{([^}]+)\}/', '{$1}', $pathKey);

                $methods = array_diff($route->methods(), ['HEAD']);
                foreach ($methods as $method) {
                    $lower = strtolower($method);
                    if (!in_array($lower, ['get', 'post', 'put', 'patch', 'delete', 'options'])) {
                        continue;
                    }

                    if (!isset($paths[$pathKey])) {
                        $paths[$pathKey] = [];
                    }

                    // Derive controller, method, and docblock for summary/description
                    $actionName = $route->getActionName();
                    $summary = $route->getName() ?: $actionName ?: 'Endpoint';
                    $description = '';
                    $tags = [];
                    $operationId = null;

                    if (is_string($actionName) && strpos($actionName, '@') !== false) {
                        [$className, $methodName] = explode('@', $actionName);
                        $operationId = class_basename($className) . '@' . $methodName;
                        $tags[] = class_basename($className);
                        try {
                            if (class_exists($className) && method_exists($className, $methodName)) {
                                $ref = new \ReflectionMethod($className, $methodName);
                                $doc = $ref->getDocComment();
                                if ($doc) {
                                    $clean = preg_replace('/^\s*\/\*\*|\*\/\s*$/', '', $doc);
                                    $lines = array_values(array_filter(array_map(function ($line) {
                                        $line = preg_replace('/^\s*\*\s?/', '', $line);
                                        return rtrim($line);
                                    }, explode("\n", $clean))));
                                    // Extract summary (first non-empty) and description (rest until @ tag)
                                    $foundSummary = false;
                                    $descLines = [];
                                    foreach ($lines as $line) {
                                        if (preg_match('/^@/',$line)) { break; }
                                        if (!$foundSummary && trim($line) !== '') {
                                            $summary = trim($line);
                                            $foundSummary = true;
                                        } elseif ($foundSummary) {
                                            $descLines[] = $line;
                                        }
                                    }
                                    $description = trim(implode("\n", $descLines));
                                }
                            }
                        } catch (\Throwable $t) {
                            // ignore reflection/doc errors
                        }
                    }

                    // Path parameters
                    preg_match_all('/\{([^}]+)\}/', $pathKey, $paramMatches);
                    $parameters = [];
                    if (!empty($paramMatches[1])) {
                        foreach ($paramMatches[1] as $paramName) {
                            $parameters[] = [
                                'name' => $paramName,
                                'in' => 'path',
                                'required' => true,
                                'schema' => ['type' => 'string']
                            ];
                        }
                    }

                    // Security if route requires Sanctum auth
                    $security = [];
                    $allMiddleware = method_exists($route, 'gatherMiddleware') ? $route->gatherMiddleware() : ($route->middleware() ?? []);
                    foreach ($allMiddleware as $mw) {
                        if (is_string($mw) && (stripos($mw, 'sanctum') !== false || stripos($mw, 'Authenticate:sanctum') !== false)) {
                            $security = [['sanctum' => []]];
                            break;
                        }
                    }

                    $operation = [
                        'summary' => $summary,
                        'description' => $description,
                        'operationId' => $operationId ?? md5($pathKey . ':' . $lower),
                        'responses' => [
                            '200' => [ 'description' => 'Success' ]
                        ]
                    ];
                    if (!empty($tags)) { $operation['tags'] = $tags; }
                    if (!empty($parameters)) { $operation['parameters'] = $parameters; }
                    if (!empty($security)) { $operation['security'] = $security; }

                    $paths[$pathKey][$lower] = $operation;
                }
            }

            $spec = [
                'openapi' => '3.0.3',
                'info' => [
                    'title' => 'KosmoHealth API (Auto-generated)',
                    'version' => '1.0.0',
                    'description' => 'This spec is auto-generated from Laravel routes. For rich docs, add schemas and details to docs/api-swagger.yaml.'
                ],
                'servers' => [
                    ['url' => config('app.url') ?? url('/')]
                ],
                'paths' => $paths,
                'components' => [
                    'securitySchemes' => [
                        'sanctum' => [
                            'type' => 'apiKey',
                            'name' => 'Authorization',
                            'in' => 'header',
                            'description' => 'Send Bearer <token>'
                        ]
                    ]
                ]
            ];

            return response()->json($spec);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to generate OpenAPI spec',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
