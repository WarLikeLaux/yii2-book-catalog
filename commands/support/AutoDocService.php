<?php

declare(strict_types=1);

namespace app\commands\support;

use stdClass;
use Symfony\Component\Yaml\Yaml;
use Throwable;
use Yii;
use yii\db\TableSchema;
use yii\helpers\FileHelper;
use yii\helpers\Inflector;

final readonly class AutoDocService
{
    private const string DOCS_PATH = '@app/docs/auto';

    public function generateDbSchema(): void
    {
        $schema = Yii::$app->db->schema;
        $tables = [];

        foreach ($schema->getTableSchemas() as $tableSchema) {
            $tables[$tableSchema->name] = $this->mapTable($tableSchema);
        }

        ksort($tables);

        $this->saveYaml('db.yaml', [
            'meta' => [
                'title' => 'Database Schema',
                'updated_at' => gmdate('c'),
            ],
            'tables' => $tables,
        ]);
    }

    public function generateRoutes(): void
    {
        $controllerDir = Yii::getAlias('@app/presentation/controllers');
        $files = FileHelper::findFiles((string)$controllerDir, ['only' => ['*Controller.php']]);
        $routes = [];

        foreach ($files as $file) {
            foreach ($this->parseControllerRoutes($file) as $route) {
                $routes[] = $route;
            }
        }

        usort($routes, static fn (array $a, array $b): int => strcmp($a['path'], $b['path']));

        $this->saveYaml('routes.yaml', [
            'meta' => [
                'title' => 'HTTP Routes',
                'updated_at' => gmdate('c'),
            ],
            'routes' => $routes,
        ]);
    }

    public function generateModelsDoc(): void
    {
        $modelDir = Yii::getAlias('@app/infrastructure/persistence');
        $files = FileHelper::findFiles((string)$modelDir, ['only' => ['*.php']]);
        $models = [];

        foreach ($files as $file) {
            $content = file_get_contents($file);

            if ($content === false) {
                continue;
            }

            $model = $this->parseModelFile($content);

            if ($model === null) {
                continue;
            }

            $models[$model['name']] = $model;
        }

        ksort($models);

        $this->saveYaml('models.yaml', [
            'meta' => [
                'title' => 'ActiveRecord Models',
                'updated_at' => gmdate('c'),
            ],
            'models' => $models,
        ]);
    }

    public function generateUseCasesDoc(): void
    {
        $appDir = Yii::getAlias('@app/application');
        $files = FileHelper::findFiles((string)$appDir, ['only' => ['*UseCase.php']]);
        $useCases = [];

        foreach ($files as $file) {
            $content = (string)file_get_contents($file);

            if (!preg_match('/class\s+(\w+)\b/', $content, $matches)) {
                continue;
            }

            $name = $matches[1];
            $command = 'void';

            if (preg_match('/@param\s+([A-Za-z0-9_\\\]+)\s+\$command/', $content, $docM)) {
                $parts = explode('\\', $docM[1]);
                $command = end($parts);
            } elseif (preg_match('/execute\(\s*(\w+)\s+\$command\)/', $content, $sigM)) {
                $command = $sigM[1];
            }

            $useCases[$name] = [
                'command' => $command,
                'module' => basename(dirname($file, 2)),
            ];
        }

        ksort($useCases);

        $this->saveYaml('usecases.yaml', [
            'meta' => [
                'title' => 'Application Use Cases',
                'updated_at' => gmdate('c'),
            ],
            'usecases' => $useCases,
        ]);
    }

    public function generateEventsDoc(): void
    {
        $eventDir = Yii::getAlias('@app/domain/events');
        $files = FileHelper::findFiles((string)$eventDir, ['only' => ['*.php']]);
        $events = [];

        foreach ($files as $file) {
            $content = (string)file_get_contents($file);

            if (!preg_match('/class\s+(\w+)\b/', $content, $matches)) {
                continue;
            }

            $name = $matches[1];
            $type = preg_match("/public\s+const\s+string\s+EVENT_TYPE\s*=\s*'([^']+)'/", $content, $m) ? $m[1] : 'internal';
            $events[$name] = [
                'type' => $type,
                'payload' => $this->extractPublicProps($content),
            ];
        }

        ksort($events);

        $this->saveYaml('events.yaml', [
            'meta' => [
                'title' => 'Domain Events',
                'updated_at' => gmdate('c'),
            ],
            'events' => $events,
        ]);
    }

    private function mapTable(TableSchema $tableSchema): array
    {
        return [
            'columns' => $this->mapColumns($tableSchema),
            'primary_key' => array_values($tableSchema->primaryKey),
            'foreign_keys' => $this->mapForeignKeys($tableSchema) ?: new stdClass(),
            'indexes' => $this->mapIndexes($tableSchema) ?: new stdClass(),
        ];
    }

    private function mapColumns(TableSchema $tableSchema): array
    {
        $columns = [];

        foreach ($tableSchema->columns as $column) {
            $columns[] = [
                'name' => $column->name,
                'type' => $column->dbType,
                'nullable' => $column->allowNull,
                'default' => $column->defaultValue,
            ];
        }

        return $columns;
    }

    private function mapForeignKeys(TableSchema $tableSchema): array
    {
        $foreignKeys = [];

        foreach ($tableSchema->foreignKeys as $foreignKey) {
            $referenceTable = $foreignKey[0] ?? null;

            if ($referenceTable === null) {
                continue;
            }

            foreach ($foreignKey as $localColumn => $refColumn) {
                if (is_int($localColumn)) {
                    continue;
                }

                $foreignKeys[] = [
                    'column' => $localColumn,
                    'references' => $referenceTable . '.' . $refColumn,
                ];
            }
        }

        return $foreignKeys;
    }

    private function mapIndexes(TableSchema $tableSchema): array
    {
        $schema = Yii::$app->db->schema;
        $indexes = [];

        try {
            $dbIndexes = $schema->findUniqueIndexes($tableSchema);
            $uniqueNames = array_keys($dbIndexes);

            foreach ($dbIndexes as $indexName => $columns) {
                $indexes[] = [
                    'name' => $indexName,
                    'columns' => is_array($columns) ? $columns : [$columns],
                    'unique' => true,
                ];
            }

            $allIndexes = $schema->getTableIndexes($tableSchema->name);

            foreach ($allIndexes as $indexConstraint) {
                if (in_array($indexConstraint->name, $uniqueNames, true) || $indexConstraint->name === null) {
                    continue;
                }

                $indexes[] = [
                    'name' => $indexConstraint->name,
                    'columns' => $indexConstraint->columnNames,
                    'unique' => false,
                ];
            }
        } catch (Throwable $exception) {
            Yii::error([
                'message' => 'Failed to read table indexes',
                'table' => $tableSchema->name,
                'error' => $exception->getMessage(),
                'exception_class' => $exception::class,
            ], 'application');
            return [];
        }

        return $indexes;
    }

    private function parseControllerRoutes(string $file): array
    {
        $content = (string)file_get_contents($file);

        if (!preg_match('/class\s+(\w+)Controller\b/', $content, $matches)) {
            return [];
        }

        $controllerName = $matches[1];
        $verbMap = $this->extractVerbMap($content);
        $actionBodies = $this->extractActionBodies($content);
        $behaviors = $this->extractBehaviorsFromCode($content);

        $routes = [];

        $controllerId = $this->resolveControllerId($file, $controllerName);

        foreach ($actionBodies as $actionName => $body) {
            $entry = $this->buildRouteEntry($controllerId, $controllerName, $actionName, $verbMap, $body);

            if ($behaviors !== []) {
                $entry['guards'] = $behaviors;
            }

            $routes[] = $entry;
        }

        return $routes;
    }

    private function extractVerbMap(string $content): array
    {
        $actions = [];

        if (!preg_match("/'actions'\s*=>\s*\[(.*?)]/s", $content, $matches)) {
            return $actions;
        }

        $block = $matches[1];
        preg_match_all("/'([a-zA-Z0-9_-]+)'\s*=>\s*\[(.*?)]/s", $block, $actionMatches, PREG_SET_ORDER);

        foreach ($actionMatches as $match) {
            $action = $match[1];
            preg_match_all("/'([a-zA-Z]+)'/", $match[2], $methodMatches);
            $methods = array_map('strtoupper', $methodMatches[1] ?? []);

            if ($methods === []) {
                continue;
            }

            $actions[$action] = $methods;
        }

        return $actions;
    }

    private function extractActionBodies(string $content): array
    {
        preg_match_all('/function\s+action([A-Z]\w*)\s*\(/', $content, $matches, PREG_OFFSET_CAPTURE);
        $positions = $matches[1] ?? [];

        if ($positions === []) {
            return [];
        }

        $result = [];
        $count = count($positions);

        for ($i = 0; $i < $count; $i++) {
            $actionName = $positions[$i][0];
            $start = $positions[$i][1];
            $end = $i + 1 < $count ? $positions[$i + 1][1] : strlen($content);
            $result[$actionName] = substr($content, $start, $end - $start);
        }

        return $result;
    }

    private function buildRouteEntry(
        string $controllerId,
        string $controllerName,
        string $actionName,
        array $verbMap,
        string $body,
    ): array {
        $actionId = Inflector::camel2id($actionName);
        $path = $controllerId === 'site' && $actionId === 'index' ? '/' : "/$controllerId/$actionId";

        $methods = $verbMap[$actionId] ?? (str_contains($body, 'isPost') ? ['GET', 'POST'] : ['GET']);

        $entry = [
            'path' => $path,
            'action' => "$controllerId/$actionId",
            'controller' => "{$controllerName}Controller",
            'methods' => $methods,
        ];

        if (preg_match("/render\('\s*([a-z0-9_-]+)\s*'\)/i", $body, $m)) {
            $viewFile = 'presentation/views/' . $controllerId . '/' . $m[1] . '.php';

            if (file_exists(Yii::getAlias('@app/' . $viewFile))) {
                $entry['view'] = $viewFile;
            }
        }

        return $entry;
    }

    private function resolveControllerId(string $file, string $controllerName): string
    {
        $controllerDir = Yii::getAlias('@app/presentation/controllers');
        $dir = dirname($file);
        $relativeDir = trim(str_replace((string)$controllerDir, '', $dir), DIRECTORY_SEPARATOR);
        $controllerId = Inflector::camel2id($controllerName);

        if ($relativeDir === '') {
            return $controllerId;
        }

        $prefix = str_replace(DIRECTORY_SEPARATOR, '/', $relativeDir);

        return trim($prefix . '/' . $controllerId, '/');
    }

    private function extractBehaviorsFromCode(string $content): array
    {
        $found = [];

        if (str_contains($content, 'IdempotencyFilter::class')) {
            $found[] = 'Idempotency';
        }

        if (str_contains($content, 'AccessControl::class')) {
            $found[] = 'Auth';
        }

        if (str_contains($content, 'HttpCache::class')) {
            $found[] = 'Cache';
        }

        return $found;
    }

    private function parseModelFile(string $content): array|null
    {
        if (!preg_match('/class\s+(\w+)\s+extends\s+ActiveRecord\b/', $content, $matches)) {
            return null;
        }

        $name = $matches[1];
        $ns = $this->extractNamespace($content);
        $table = preg_match("/tableName\(\)\s*:\s*string\s*\{\s*return\s*'([^']+)'/s", $content, $m) ? $m[1] : null;

        return [
            'name' => $name,
            'class' => $ns . chr(92) . $name,
            'table' => $table,
            'behaviors' => str_contains($content, 'TimestampBehavior::class') ? ['TimestampBehavior'] : new stdClass(),
            'relations' => $this->extractRelations($content) ?: new stdClass(),
            'validation' => $this->extractRulesSummary($content),
        ];
    }

    private function extractRulesSummary(string $content): array
    {
        if (!preg_match('/function\s+rules\(\)\s*:\s*array\s*\{(.*?)\}/s', $content, $match)) {
            return [];
        }

        $rulesBlock = $match[1];
        $summary = [];

        preg_match_all("/\[\s*\[\s*(.*?)\s*\]\s*,\s*'(\w+)'/", $rulesBlock, $ruleMatches, PREG_SET_ORDER);

        foreach ($ruleMatches as $m) {
            $fields = str_replace(["'", ' ', '"', '[', ']'], '', $m[1]);
            $summary[] = sprintf('%s (%s)', $fields, $m[2]);
        }

        preg_match_all("/\[\s*'(\w+)'\s*,\s*'(\w+)'\s*\]/", $rulesBlock, $simpleMatches, PREG_SET_ORDER);

        foreach ($simpleMatches as $m) {
            $entry = sprintf('%s (%s)', $m[1], $m[2]);

            if (in_array($entry, $summary, true)) {
                continue;
            }

            $summary[] = $entry;
        }

        return $summary;
    }

    private function extractRelations(string $content): array
    {
        $relations = [];
        preg_match_all('/function\s+(get\w+)\s*\(\)\s*:\s*ActiveQuery\s*\{(.*?)\}/s', $content, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $method = $match[1];
            $body = $match[2];

            if (!preg_match('/has(One|Many)\s*\(\s*(\w+)::class/', $body, $m)) {
                continue;
            }

            $type = 'has' . $m[1];
            $target = $m[2];
            $via = preg_match("/viaTable\(\s*'([^']+)'/", $body, $mv) ? $mv[1] : null;
            $relations[] = [
                'method' => $method . '()',
                'type' => $type,
                'target' => $target,
                'via' => $via,
            ];
        }

        return $relations;
    }

    private function extractPublicProps(string $content): array
    {
        preg_match_all('/public\s+readonly\s+[\w|]+\s+\$(\w+)/', $content, $matches);
        return $matches[1] ?? [];
    }

    private function extractNamespace(string $content): string
    {
        return preg_match('/namespace\s+([^;]+);/', $content, $m) ? trim($m[1]) : 'app';
    }

    private function saveYaml(string $filename, array $data): void
    {
        $path = Yii::getAlias(self::DOCS_PATH . '/' . $filename);

        if (!is_string($path)) {
            return;
        }

        FileHelper::createDirectory(dirname($path));
        $yaml = Yaml::dump($data, 10, 2);
        $yaml = str_replace('{  }', '{}', $yaml);
        $yaml = preg_replace(
            '/(foreign_keys|indexes|behaviors|relations|validation):\s+null/',
            '$1: {}',
            $yaml,
        );
        file_put_contents($path, $yaml);
    }
}
