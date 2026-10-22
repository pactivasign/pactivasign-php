<?php

declare(strict_types=1);

/**
 * Generates the models, enums and resource classes from openapi/public.json.
 *
 *     php tools/generate.php [output-dir]     (default: src)
 *
 * Generated files carry an @generated marker; files with that marker that the spec no longer
 * produces are deleted. Hand-written files (Model.php, Resource.php, ...) are never touched.
 */

const MARKER = '@generated from openapi/public.json; do not edit. Run `composer generate`.';
const SKIPPED_SCHEMAS = ['ErrorBody', 'ErrorResponse', 'OAuthErrorResponse'];
const UNKNOWN_VALUE = 'unknown';

$root = dirname(__DIR__);
$out = rtrim($argv[1] ?? $root . '/src', '/');
$spec = json_decode((string) file_get_contents($root . '/openapi/public.json'), true, 512, JSON_THROW_ON_ERROR);
$source = json_decode((string) file_get_contents($root . '/openapi/SOURCE.json'), true, 512, JSON_THROW_ON_ERROR);
if (hash_file('sha256', $root . '/openapi/public.json') !== $source['sha256']) {
    fwrite(STDERR, "openapi/public.json does not match the sha256 in openapi/SOURCE.json.\n");
    exit(1);
}

$generator = new SdkGenerator($spec, $source['tag']);
$files = $generator->files();

foreach (['Model', 'Resource'] as $dir) {
    @mkdir("$out/$dir", 0o755, true);
    foreach (glob("$out/$dir/*.php") ?: [] as $existing) {
        if (str_contains((string) file_get_contents($existing), MARKER) && !isset($files["$dir/" . basename($existing)])) {
            unlink($existing);
        }
    }
}
foreach ($files as $path => $contents) {
    if (!is_file("$out/$path") || file_get_contents("$out/$path") !== $contents) {
        file_put_contents("$out/$path", $contents);
    }
}
fwrite(STDOUT, sprintf("Generated %d files from %s.\n", count($files), $source['tag']));

final class SdkGenerator
{
    /** @var array<string, array<string, mixed>> */
    private array $schemas;

    /** @param array<string, mixed> $spec */
    public function __construct(private readonly array $spec, private readonly string $tag)
    {
        $this->schemas = $spec['components']['schemas'];
    }

    /** @return array<string, string> relative path => contents */
    public function files(): array
    {
        $files = [];
        foreach ($this->schemas as $name => $schema) {
            if (in_array($name, SKIPPED_SCHEMAS, true) || $this->isRequestSchema($name)) {
                continue;
            }
            if (isset($schema['enum'])) {
                $files["Model/$name.php"] = $this->enum($name, $schema);
            } elseif (($schema['type'] ?? null) === 'object') {
                $files["Model/$name.php"] = $this->model($name, $schema);
            }
        }

        $resources = [];
        foreach ($this->spec['paths'] as $path => $item) {
            foreach ($item as $method => $operation) {
                if (!in_array($method, ['get', 'post', 'put', 'patch', 'delete'], true)) {
                    continue;
                }
                [$group, $name] = explode('.', $operation['operationId'], 2);
                $resources[$group][] = [$path, strtoupper($method), $name, $operation];
            }
        }
        ksort($resources);
        foreach ($resources as $group => $operations) {
            $files['Resource/' . ucfirst($group) . '.php'] = $this->resource(ucfirst($group), $operations);
        }
        $files['Resource/ClientResources.php'] = $this->clientResources(array_keys($resources));
        ksort($files);

        return $files;
    }

    // ---- enums and models -------------------------------------------------------------------

    /** @param array<string, mixed> $schema */
    private function enum(string $name, array $schema): string
    {
        $cases = [];
        foreach ($schema['enum'] as $value) {
            if ($value === UNKNOWN_VALUE) {
                throw new RuntimeException("$name uses the reserved value \"unknown\".");
            }
            $case = self::caseName((string) $value);
            if (isset($cases[$case]) || $case === 'Unknown') {
                throw new RuntimeException("$name: case name $case is ambiguous.");
            }
            $cases[$case] = $value;
        }
        $body = '';
        foreach ($cases as $case => $value) {
            $body .= sprintf("    case %s = %s;\n", $case, var_export($value, true));
        }
        $body .= "\n    /** A value added to the API after this SDK version. The raw value is in the model's toArray(). */\n";
        $body .= sprintf("    case Unknown = '%s';\n", UNKNOWN_VALUE);

        return $this->php('Model', [], $this->doc($schema['description'] ?? null) . "enum $name: string\n{\n$body}\n");
    }

    /** @param array<string, mixed> $schema */
    private function model(string $name, array $schema): string
    {
        $required = $schema['required'] ?? [];
        $properties = '';
        $hydrate = '';
        foreach ($schema['properties'] ?? [] as $property => $propertySchema) {
            $type = $this->type($propertySchema, "$name.$property");
            $nullable = !in_array($property, $required, true) || $this->isNullable($propertySchema);
            $phpType = ($nullable ? '?' : '') . $type['php'];
            if ($nullable && str_contains($type['php'], '|')) {
                $phpType = $type['php'] . '|null';
            }
            $docLines = [];
            if (($description = $this->description($propertySchema)) !== null) {
                $docLines[] = $description;
            }
            if ($type['doc'] !== $type['php']) {
                $docLines[] = '@var ' . $type['doc'] . ($nullable ? '|null' : '');
            }
            if (!empty($propertySchema['deprecated'])) {
                $docLines[] = '@deprecated ' . ($description ?? '');
            }
            $properties .= $this->doc($docLines, '    ') . sprintf("    public readonly %s $%s;\n\n", $phpType, $property);
            $expression = sprintf($type['read'], var_export($property, true));
            $hydrate .= $nullable
                ? sprintf("        \$this->%s = \$this->has(%s) ? %s : null;\n", $property, var_export($property, true), $expression)
                : sprintf("        \$this->%s = %s;\n", $property, $expression);
        }

        $class = $this->doc($schema['description'] ?? null)
            . "final class $name extends Model\n{\n$properties    /**\n     * @param array<string, mixed> \$data\n     */\n    protected function __construct(array \$data)\n    {\n        parent::__construct(\$data);\n\n$hydrate    }\n}\n";

        return $this->php('Model', [], $class);
    }

    /**
     * PHP type, PHPDoc type and the Model reader expression (with %s for the quoted key).
     *
     * @param array<string, mixed> $schema
     * @return array{php: string, doc: string, read: string}
     */
    private function type(array $schema, string $where): array
    {
        if (isset($schema['allOf'])) {
            return $this->type($schema['allOf'][0], $where);
        }
        if (isset($schema['$ref'])) {
            $name = self::refName($schema['$ref']);
            $target = $this->schemas[$name];

            return isset($target['enum'])
                ? ['php' => $name, 'doc' => $name, 'read' => "\$this->enum(%s, $name::class)"]
                : ['php' => $name, 'doc' => $name, 'read' => "\$this->model(%s, $name::class)"];
        }
        if (isset($schema['oneOf'])) {
            $types = array_unique(array_map(static fn ($s) => $s['type'] ?? '', $schema['oneOf']));
            sort($types);
            if ($types === ['boolean', 'string']) {
                return ['php' => 'string|bool', 'doc' => 'string|bool', 'read' => '$this->stringOrBool(%s)'];
            }
        }

        switch ($schema['type'] ?? null) {
            case 'string':
                return ($schema['format'] ?? null) === 'date-time'
                    ? ['php' => '\DateTimeImmutable', 'doc' => '\DateTimeImmutable', 'read' => '$this->date(%s)']
                    : ['php' => 'string', 'doc' => 'string', 'read' => '$this->string(%s)'];
            case 'integer':
                return ['php' => 'int', 'doc' => 'int', 'read' => '$this->int(%s)'];
            case 'number':
                return ['php' => 'int|float', 'doc' => 'int|float', 'read' => '$this->number(%s)'];
            case 'boolean':
                return ['php' => 'bool', 'doc' => 'bool', 'read' => '$this->bool(%s)'];
            case 'object':
                $additional = $schema['additionalProperties'] ?? true;
                if (is_array($additional) && ($additional['type'] ?? null) === 'string') {
                    return ['php' => 'array', 'doc' => 'array<string, string>', 'read' => '$this->stringMap(%s)'];
                }

                return ['php' => 'array', 'doc' => 'array<string, mixed>', 'read' => '$this->object(%s)'];
            case 'array':
                $item = $this->type($schema['items'], $where . '[]');
                return match (true) {
                    str_contains($item['read'], '$this->model(') => ['php' => 'array', 'doc' => "list<{$item['doc']}>", 'read' => str_replace('$this->model(', '$this->models(', $item['read'])],
                    str_contains($item['read'], '$this->enum(') => ['php' => 'array', 'doc' => "list<{$item['doc']}>", 'read' => str_replace('$this->enum(', '$this->enums(', $item['read'])],
                    $item['php'] === 'string' => ['php' => 'array', 'doc' => 'list<string>', 'read' => '$this->strings(%s)'],
                    default => throw new RuntimeException("$where: unsupported list item type."),
                };
        }

        throw new RuntimeException("$where: unsupported schema " . json_encode($schema));
    }

    // ---- request shapes ---------------------------------------------------------------------

    /**
     * PHPStan type for a request value.
     *
     * @param array<string, mixed> $schema
     */
    private function shape(array $schema, int $indent): string
    {
        $nullable = $this->isNullable($schema) ? '|null' : '';
        if (isset($schema['allOf'])) {
            return $this->shape($schema['allOf'][0], $indent) . $nullable;
        }
        if (isset($schema['$ref'])) {
            $name = self::refName($schema['$ref']);
            $target = $this->schemas[$name];
            if (isset($target['enum'])) {
                return self::literals($target['enum']) . '|\\PactivaSign\\Model\\' . $name;
            }

            return $this->objectShape($target, $indent);
        }
        if (isset($schema['oneOf'])) {
            return 'string|bool' . $nullable;
        }
        if (isset($schema['enum'])) {
            // An inline enum with the same values as a named one also accepts that enum's cases.
            foreach ($this->schemas as $name => $candidate) {
                if (isset($candidate['enum']) && $candidate['enum'] === $schema['enum']) {
                    return self::literals($schema['enum']) . '|\\PactivaSign\\Model\\' . $name . $nullable;
                }
            }

            return self::literals($schema['enum']) . $nullable;
        }

        return match ($schema['type'] ?? null) {
            'string' => (($schema['format'] ?? null) === 'date-time' ? 'string|\DateTimeInterface' : 'string') . $nullable,
            'integer' => 'int' . $nullable,
            'number' => 'int|float' . $nullable,
            'boolean' => 'bool' . $nullable,
            'array' => 'list<' . $this->shape($schema['items'], $indent) . '>' . $nullable,
            'object' => isset($schema['properties'])
                ? $this->objectShape($schema, $indent) . $nullable
                : (is_array($schema['additionalProperties'] ?? null) && ($schema['additionalProperties']['type'] ?? null) === 'string'
                    ? 'array<string, string>'
                    : 'array<string, mixed>') . $nullable,
            default => throw new RuntimeException('Unsupported request schema ' . json_encode($schema)),
        };
    }

    /** @param array<string, mixed> $schema */
    private function objectShape(array $schema, int $indent): string
    {
        $required = $schema['required'] ?? [];
        $pad = str_repeat(' ', $indent + 4);
        $lines = [];
        foreach ($schema['properties'] ?? [] as $property => $propertySchema) {
            $optional = in_array($property, $required, true) ? '' : '?';
            $lines[] = $pad . $property . $optional . ': ' . $this->shape($propertySchema, $indent + 4) . ',';
        }

        return "array{\n" . implode("\n", $lines) . "\n" . str_repeat(' ', $indent) . '}';
    }

    // ---- resources --------------------------------------------------------------------------

    /** @param list<array{string, string, string, array<string, mixed>}> $operations */
    private function resource(string $class, array $operations): string
    {
        $imports = [];
        $methods = [];
        foreach ($operations as [$path, $httpMethod, $name, $operation]) {
            $methods[] = $this->method($class, $path, $httpMethod, $name, $operation, $imports);
        }
        ksort($imports);
        $tagDescription = null;
        foreach ($this->spec['tags'] ?? [] as $tag) {
            if (in_array($tag['name'], $operations[0][3]['tags'] ?? [], true)) {
                $tagDescription = $tag['description'] ?? null;
            }
        }

        $body = $this->doc($tagDescription === null ? ['@see \\PactivaSign\\Client'] : [$tagDescription, '', '@see \\PactivaSign\\Client']) . "final class $class extends ApiResource\n{\n"
            . implode("\n", $methods) . "}\n";

        return $this->php('Resource', array_keys($imports), $body);
    }

    /**
     * @param array<string, mixed> $operation
     * @param array<string, true> $imports
     */
    private function method(string $class, string $path, string $httpMethod, string $name, array $operation, array &$imports): string
    {
        $parameters = [];
        $docParams = [];
        $sendOptions = [];
        $prelude = [];

        // Path parameters, in path order.
        preg_match_all('/\{(\w+)\}/', $path, $matches);
        $pathArgs = [];
        foreach ($matches[1] as $parameter) {
            $parameters[] = "string \$$parameter";
            $pathArgs[] = "\$$parameter";
        }
        $pathExpression = $pathArgs === []
            ? var_export($path, true)
            : sprintf('self::path(%s, %s)', var_export(preg_replace('/\{\w+\}/', '%s', $path), true), implode(', ', $pathArgs));

        // Response.
        $successCode = array_values(array_filter(array_keys($operation['responses']), static fn ($code) => str_starts_with((string) $code, '2')))[0];
        $content = $operation['responses'][$successCode]['content'] ?? [];
        $jsonSchema = $content['application/json']['schema'] ?? null;
        $isPage = isset($jsonSchema['properties']['nextCursor']);
        $isBinary = $content !== [] && $jsonSchema === null;
        $dataSchema = $jsonSchema['properties']['data'] ?? null;

        // Query and header parameters.
        $queryShape = [];
        foreach ($operation['parameters'] ?? [] as $parameter) {
            $parameterName = $parameter['name'];
            if ($parameter['in'] === 'query') {
                if ($isPage) {
                    if ($parameterName !== 'cursor') {
                        $queryShape[] = '    ' . $parameterName . '?: ' . $this->shape($parameter['schema'], 4) . ',';
                    }
                    continue;
                }
                $type = $this->shape($parameter['schema'], 0);
                $parameters[] = '?string $' . $parameterName . ' = null';
                $docParams[] = sprintf('@param %s|null $%s %s', $type, $parameterName, $this->oneLine($parameter['description'] ?? ''));
                $sendOptions['query'] = sprintf("self::query([%s => \$%s])", var_export($parameterName, true), $parameterName);
            } elseif ($parameter['in'] === 'header' && $parameterName === 'Idempotency-Key') {
                $parameters[] = '?string $idempotencyKey = null';
                $docParams[] = '@param string|null $idempotencyKey Reuse the same key when you retry the same request; generated when omitted.';
                $sendOptions['idempotencyKey'] = '$idempotencyKey ?? Jws::uuid()';
                $imports['PactivaSign\\Auth\\Jws'] = true;
            } elseif ($parameter['in'] === 'header' && $parameterName === 'If-None-Match') {
                $parameters[] = '?string $ifNoneMatch = null';
                $docParams[] = '@param string|null $ifNoneMatch The ETag of your copy (Download::etag()); the result isNotModified() when it is current.';
                $prelude[] = "\$headers = ['Accept' => 'application/pdf'];";
                $prelude[] = "if (\$ifNoneMatch !== null) {\n            \$headers['If-None-Match'] = \$ifNoneMatch;\n        }";
                $sendOptions['headers'] = '$headers';
            }
        }

        // Request body.
        $body = $operation['requestBody']['content'] ?? [];
        if (isset($body['application/json'])) {
            $schema = $body['application/json']['schema'];
            $target = isset($schema['$ref']) ? $this->schemas[self::refName($schema['$ref'])] : $schema;
            $optional = ($target['required'] ?? []) === [];
            $bodyParameter = 'array $params' . ($optional ? ' = []' : '');
            // The body parameter goes before optional parameters.
            $firstOptional = array_key_first(array_filter($parameters, static fn ($p) => str_contains($p, '=')));
            array_splice($parameters, $firstOptional ?? count($parameters), 0, [$bodyParameter]);
            array_unshift($docParams, '@param ' . $this->shape($schema, 0) . ' $params');
            $sendOptions = ['json' => $optional ? '(object) self::normalize($params)' : 'self::normalize($params)'] + $sendOptions;
        } elseif (isset($body['multipart/form-data'])) {
            $schema = $body['multipart/form-data']['schema'];
            $multipart = '(new Multipart())';
            $fileParameters = [];
            foreach ($schema['properties'] as $field => $fieldSchema) {
                if (($fieldSchema['format'] ?? null) === 'binary') {
                    $fileParameters[] = "mixed \$$field";
                    $fileParameters[] = "string \$filename = 'document.pdf'";
                    $docParams[] = "@param string|resource|\\Psr\\Http\\Message\\StreamInterface \$$field The PDF: its contents, an open stream or a PSR-7 stream.";
                    $multipart .= "\n                ->file('$field', \$$field, \$filename)";
                } else {
                    array_unshift($fileParameters, "string \$$field");
                    $multipart .= "\n                ->field('$field', \$$field)";
                }
            }
            $parameters = array_merge($parameters, $fileParameters);
            $sendOptions = ['multipart' => $multipart] + $sendOptions;
            $imports['PactivaSign\\Http\\Multipart'] = true;
        }

        if ($isBinary && !isset($sendOptions['headers'])) {
            $sendOptions['headers'] = "['Accept' => 'application/pdf']";
        }

        // Return type and expression.
        $send = static function (array $options) use ($httpMethod, $pathExpression): string {
            if ($options === []) {
                return sprintf("\$this->transport->send('%s', %s)", $httpMethod, $pathExpression);
            }
            $lines = [];
            foreach ($options as $key => $value) {
                $lines[] = sprintf("            '%s' => %s,", $key, $value);
            }

            return sprintf("\$this->transport->send('%s', %s, [\n%s\n        ])", $httpMethod, $pathExpression, implode("\n", $lines));
        };

        if ($isPage) {
            $model = self::importModel(self::refName($dataSchema['items']['$ref']), $class, $imports);
            $imports['PactivaSign\\Pagination\\Paginator'] = true;
            $parameters[] = 'array $query = []';
            array_unshift($docParams, "@param array{\n" . implode("\n", $queryShape) . "\n} \$query");
            $returnType = 'Paginator';
            $returnDoc = "Paginator<$model>";
            $statement = "\$query = self::query(\$query);\n\n        return new Paginator(fn (?string \$cursor) => self::page(\n            \$this->transport->send('GET', $pathExpression, ['query' => \$cursor === null ? \$query : ['cursor' => \$cursor] + \$query]),\n            $model::class,\n        ));";
        } elseif ($isBinary) {
            $imports['PactivaSign\\Http\\Download'] = true;
            $returnType = 'Download';
            $returnDoc = null;
            $statement = 'return new Download(' . $send($sendOptions) . ');';
        } elseif ($dataSchema === null) {
            $returnType = 'void';
            $returnDoc = null;
            $statement = $send($sendOptions) . ';';
        } elseif (($dataSchema['type'] ?? null) === 'array') {
            $model = self::importModel(self::refName($dataSchema['items']['$ref']), $class, $imports);
            $returnType = 'array';
            $returnDoc = "list<$model>";
            $statement = 'return self::many(' . $send($sendOptions) . ", $model::class);";
        } else {
            $model = self::importModel(self::refName($dataSchema['$ref']), $class, $imports);
            $returnType = $model;
            $returnDoc = null;
            $statement = 'return self::one(' . $send($sendOptions) . ", $model::class);";
        }

        $doc = array_filter([
            $this->oneLine($operation['summary'] ?? ''),
            $this->paragraphs($operation['description'] ?? ''),
            isset($operation['x-oauth-scopes']) && $operation['x-oauth-scopes'] !== []
                ? 'Scopes: ' . implode(', ', $operation['x-oauth-scopes']) . '.'
                : null,
            sprintf('`%s %s` (%s)', $httpMethod, $path, $operation['operationId']),
        ]);
        $tags = $docParams;
        if ($returnDoc !== null) {
            $tags[] = "@return $returnDoc";
        }
        $tags[] = '@throws \\PactivaSign\\Exception\\PactivaException';
        if (!empty($operation['deprecated'])) {
            $tags[] = '@deprecated';
        }

        $prelude = $prelude === [] ? '' : implode("\n        ", $prelude) . "\n\n        ";
        $methodName = $name;

        $sections = [];
        foreach ($doc as $section) {
            array_push($sections, $section, '');
        }

        return $this->doc(array_merge($sections, $tags), '    ')
            . sprintf("    public function %s(%s): %s\n    {\n        %s%s\n    }\n", $methodName, implode(', ', $parameters), $returnType, $prelude, $statement);
    }

    /** @param list<string> $groups */
    private function clientResources(array $groups): string
    {
        $properties = '';
        $assignments = '';
        foreach ($groups as $group) {
            $class = ucfirst($group);
            $properties .= sprintf("    public readonly %s $%s;\n", $class, $group);
            $assignments .= sprintf("        \$this->%s = new %s(\$transport);\n", $group, $class);
        }

        return $this->php(
            'Resource',
            ['PactivaSign\\Http\\Transport'],
            $this->doc('The API resources, as properties of the Client: `$pactiva->envelopes->create(...)`.')
            . "trait ClientResources\n{\n$properties\n    private function initializeResources(Transport \$transport): void\n    {\n$assignments    }\n}\n",
        );
    }

    // ---- helpers ----------------------------------------------------------------------------

    /** @param list<string> $imports */
    private function php(string $namespace, array $imports, string $body): string
    {
        sort($imports);
        $uses = $imports === [] ? '' : implode('', array_map(static fn ($i) => "use $i;\n", $imports)) . "\n";

        return "<?php\n\ndeclare(strict_types=1);\n\n// " . MARKER . " Source: {$this->tag}.\n\nnamespace PactivaSign\\$namespace;\n\n$uses$body";
    }

    /** @param string|list<string>|null $lines */
    private function doc(string|array|null $lines, string $indent = ''): string
    {
        $lines = is_array($lines) ? $lines : ($lines === null || $lines === '' ? [] : [$lines]);
        if ($lines === []) {
            return '';
        }
        $out = "$indent/**\n";
        foreach ($lines as $line) {
            foreach (explode("\n", $line) as $part) {
                $part = str_replace('*/', '*\\/', $part);
                $out .= rtrim("$indent * $part") . "\n";
            }
        }

        return $out . "$indent */\n";
    }

    /** @param array<string, mixed> $schema */
    private function description(array $schema): ?string
    {
        $description = $schema['description'] ?? null;

        return is_string($description) && $description !== '' ? $this->oneLine($description) : null;
    }

    private function oneLine(string $text): string
    {
        return trim((string) preg_replace('/\s+/', ' ', $text));
    }

    private function paragraphs(string $text): string
    {
        return trim(implode("\n\n", array_map(fn ($p) => $this->oneLine($p), preg_split('/\n\s*\n/', $text) ?: [])));
    }

    /** @param array<string, mixed> $schema */
    private function isNullable(array $schema): bool
    {
        return !empty($schema['nullable']);
    }

    private function isRequestSchema(string $name): bool
    {
        static $request = null;
        if ($request === null) {
            $request = [];
            $queue = [];
            foreach ($this->spec['paths'] as $item) {
                foreach ($item as $operation) {
                    foreach ($operation['requestBody']['content'] ?? [] as $content) {
                        $queue[] = $content['schema'];
                    }
                }
            }
            while ($queue !== []) {
                $schema = array_pop($queue);
                array_walk_recursive($schema, function ($value, $key) use (&$request, &$queue): void {
                    if ($key === '$ref') {
                        $referenced = self::refName($value);
                        if (!isset($request[$referenced]) && !isset($this->schemas[$referenced]['enum'])) {
                            $request[$referenced] = true;
                            $queue[] = $this->schemas[$referenced];
                        }
                    }
                });
            }
        }

        return isset($request[$name]);
    }

    /** @param list<string|int> $values */
    private static function literals(array $values): string
    {
        return implode('|', array_map(static fn ($v) => var_export($v, true), $values));
    }

    /**
     * Imports a model into a resource class, aliased when it has the resource's own name (Me).
     *
     * @param array<string, true> $imports
     */
    private static function importModel(string $model, string $class, array &$imports): string
    {
        if ($model === $class) {
            $imports["PactivaSign\\Model\\$model as {$model}Model"] = true;

            return $model . 'Model';
        }
        $imports["PactivaSign\\Model\\$model"] = true;

        return $model;
    }

    private static function refName(string $ref): string
    {
        return substr($ref, strrpos($ref, '/') + 1);
    }

    private static function caseName(string $value): string
    {
        $words = preg_split('/[^A-Za-z0-9]+/', $value) ?: [];
        $name = implode('', array_map(static fn ($w) => ucfirst(strtolower($w)), $words));

        return preg_match('/^\d/', $name) === 1 ? 'Value' . $name : $name;
    }
}
