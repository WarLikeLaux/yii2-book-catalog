<?php

declare(strict_types=1);

namespace app\commands;

use Yii;
use yii\console\Controller;
use yii\console\ExitCode;
use yii\db\TableSchema;
use yii\helpers\FileHelper;
use yii\helpers\Inflector;

final class DocsController extends Controller
{
    public function actionAll(): int
    {
        $dbResult = $this->actionDb();
        if ($dbResult !== ExitCode::OK) {
            return $dbResult;
        }

        $routesResult = $this->actionRoutes();
        if ($routesResult !== ExitCode::OK) {
            return $routesResult;
        }

        return $this->actionModels();
    }

    public function actionDb(): int
    {
        $tables = $this->collectTables();
        $yaml = $this->buildDbYaml($tables);

        $this->writeDoc('db.yaml', $yaml);

        return ExitCode::OK;
    }

    public function actionRoutes(): int
    {
        $routes = $this->collectRoutes();
        $yaml = $this->buildRoutesYaml($routes);

        $this->writeDoc('routes.yaml', $yaml);

        return ExitCode::OK;
    }

    public function actionModels(): int
    {
        $models = $this->collectModels();
        $yaml = $this->buildModelsYaml($models);

        $this->writeDoc('models.yaml', $yaml);

        return ExitCode::OK;
    }

    private function collectTables(): array
    {
        $schema = Yii::$app->db->schema;
        $tables = [];

        foreach ($schema->getTableSchemas() as $tableSchema) {
            $tables[$tableSchema->name] = $this->mapTable($tableSchema);
        }

        ksort($tables);

        return $tables;
    }

    private function mapTable(TableSchema $tableSchema): array
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

        return [
            'columns' => $columns,
            'primary_key' => array_values($tableSchema->primaryKey),
            'foreign_keys' => $foreignKeys,
        ];
    }

    private function collectRoutes(): array
    {
        $controllerDir = Yii::getAlias('@app/presentation/controllers');
        $files = FileHelper::findFiles($controllerDir, ['only' => ['*Controller.php']]);
        $routes = [];

        foreach ($files as $file) {
            $controllerRoutes = $this->parseControllerRoutes($file);
            foreach ($controllerRoutes as $route) {
                $routes[] = $route;
            }
        }

        usort($routes, static fn (array $a, array $b): int => strcmp($a['path'], $b['path']));

        return $routes;
    }

    private function extractControllerName(string $content): string|null
    {
        if (!preg_match('/class\s+(\w+)Controller\b/', $content, $matches)) {
            return null;
        }

        return $matches[1];
    }

    private function parseControllerRoutes(string $file): array
    {
        $content = file_get_contents($file);
        if ($content === false) {
            return [];
        }

        $controllerName = $this->extractControllerName($content);
        if ($controllerName === null) {
            return [];
        }

        $verbMap = $this->extractVerbMap($content);
        $actionBodies = $this->extractActionBodies($content);
        $routes = [];

        foreach ($actionBodies as $actionName => $body) {
            $routes[] = $this->buildRouteEntry($controllerName, $actionName, $verbMap, $body);
        }

        return $routes;
    }

    private function extractVerbMap(string $content): array
    {
        $actions = [];
        if (!preg_match("/'actions'\\s*=>\\s*\\[(.*?)\\]/s", $content, $matches)) {
            return $actions;
        }

        $block = $matches[1];
        preg_match_all("/'([a-zA-Z0-9_-]+)'\\s*=>\\s*\\[(.*?)\\]/s", $block, $actionMatches, PREG_SET_ORDER);
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
        for ($i = 0; $i < $count; $i += 1) {
            $actionName = $positions[$i][0];
            $start = $positions[$i][1];
            $end = $i + 1 < $count ? $positions[$i + 1][1] : strlen($content);
            $result[$actionName] = substr($content, $start, $end - $start);
        }

        return $result;
    }

    private function buildRouteEntry(
        string $controllerName,
        string $actionName,
        array $verbMap,
        string $body
    ): array {
        $controllerId = Inflector::camel2id($controllerName);
        $actionId = Inflector::camel2id($actionName);
        $path = '/' . $controllerId . '/' . $actionId;
        $methods = $this->resolveMethods($actionId, $verbMap, $body);
        $view = $this->resolveView($controllerId, $body);

        if ($controllerId === 'site' && $actionId === 'index') {
            $entry = [
                'path' => '/',
                'action' => $controllerId . '/' . $actionId,
                'controller' => $controllerName . 'Controller',
                'methods' => $methods,
                'alias' => $path,
            ];
            if ($view !== null) {
                $entry['view'] = $view;
            }

            return $entry;
        }

        $entry = [
            'path' => $path,
            'action' => $controllerId . '/' . $actionId,
            'controller' => $controllerName . 'Controller',
            'methods' => $methods,
        ];
        if ($view !== null) {
            $entry['view'] = $view;
        }

        return $entry;
    }

    private function resolveMethods(string $actionId, array $verbMap, string $body): array
    {
        if (array_key_exists($actionId, $verbMap)) {
            return $verbMap[$actionId];
        }

        if (str_contains($body, 'isPost')) {
            return ['GET', 'POST'];
        }

        return ['GET'];
    }

    private function resolveView(string $controllerId, string $body): string|null
    {
        if (!preg_match("/render\\('\\s*([a-z0-9_-]+)\\s*'\\)/i", $body, $matches)) {
            return null;
        }

        $view = $matches[1];
        $path = Yii::getAlias('@app/presentation/views/' . $controllerId . '/' . $view . '.php');
        if (!is_file($path)) {
            return null;
        }

        return 'presentation/views/' . $controllerId . '/' . $view . '.php';
    }

    private function buildDbYaml(array $tables): string
    {
        $lines = [];
        $lines[] = 'meta:';
        $lines[] = '  title: "Database Schema"';
        $lines[] = '  source: "generated"';
        $lines[] = '  updated_at: ' . $this->yamlScalar(gmdate('c'));
        $lines[] = '';
        $lines[] = 'tables:';

        foreach ($tables as $tableName => $table) {
            $lines[] = '  ' . $tableName . ':';
            $lines[] = '    columns:';
            foreach ($table['columns'] as $column) {
                $lines[] = '      - name: ' . $this->yamlScalar($column['name']);
                $lines[] = '        type: ' . $this->yamlScalar($column['type']);
                $lines[] = '        nullable: ' . $this->yamlScalar($column['nullable']);
                $lines[] = '        default: ' . $this->yamlScalar($column['default']);
            }
            $lines[] = '    primary_key:';
            if ($table['primary_key'] === []) {
                $lines[] = '      []';
            } else {
                foreach ($table['primary_key'] as $pk) {
                    $lines[] = '      - ' . $this->yamlScalar($pk);
                }
            }
            $lines[] = '    foreign_keys:';
            if ($table['foreign_keys'] === []) {
                $lines[] = '      []';
            } else {
                foreach ($table['foreign_keys'] as $fk) {
                    $lines[] = '      - column: ' . $this->yamlScalar($fk['column']);
                    $lines[] = '        references: ' . $this->yamlScalar($fk['references']);
                }
            }
        }

        return implode("\n", $lines) . "\n";
    }

    private function buildRoutesYaml(array $routes): string
    {
        $lines = [];
        $lines[] = 'meta:';
        $lines[] = '  title: "HTTP Routes"';
        $lines[] = '  source: "generated"';
        $lines[] = '  updated_at: ' . $this->yamlScalar(gmdate('c'));
        $lines[] = '';
        $lines[] = 'routes:';

        foreach ($routes as $route) {
            $lines[] = '  - path: ' . $this->yamlScalar($route['path']);
            $lines[] = '    action: ' . $this->yamlScalar($route['action']);
            $lines[] = '    controller: ' . $this->yamlScalar($route['controller']);
            $lines[] = '    methods:';
            foreach ($route['methods'] as $method) {
                $lines[] = '      - ' . $this->yamlScalar($method);
            }
            if (array_key_exists('alias', $route)) {
                $lines[] = '    alias: ' . $this->yamlScalar($route['alias']);
            }
            if (!array_key_exists('view', $route)) {
                continue;
            }

            $lines[] = '    view: ' . $this->yamlScalar($route['view']);
        }

        return implode("\n", $lines) . "\n";
    }

    private function collectModels(): array
    {
        $modelDir = Yii::getAlias('@app/infrastructure/persistence');
        $files = FileHelper::findFiles($modelDir, ['only' => ['*.php']]);
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

        return $models;
    }

    private function parseModelFile(string $content): array|null
    {
        if (!preg_match('/class\\s+(\\w+)\\s+extends\\s+ActiveRecord\\b/', $content, $classMatch)) {
            return null;
        }

        $modelName = $classMatch[1];
        $table = $this->extractTableName($content);
        $relations = $this->extractRelations($content);
        $behaviors = $this->extractBehaviors($content);
        $namespace = $this->extractNamespace($content);

        return [
            'name' => $modelName,
            'class' => $namespace . '\\' . $modelName,
            'table' => $table,
            'relations' => $relations,
            'behaviors' => $behaviors,
        ];
    }

    private function extractNamespace(string $content): string
    {
        if (!preg_match('/namespace\\s+([^;]+);/', $content, $match)) {
            return 'app';
        }

        return trim($match[1]);
    }

    private function extractTableName(string $content): string|null
    {
        if (!preg_match("/tableName\\s*\\(\\)\\s*:\\s*string\\s*\\{\\s*return\\s*'([^']+)'/s", $content, $match)) {
            return null;
        }

        return $match[1];
    }

    private function extractRelations(string $content): array
    {
        $relations = [];
        preg_match_all('/function\\s+(get\\w+)\\s*\\(\\)\\s*:\\s*ActiveQuery\\s*\\{(.*?)\\}/s', $content, $matches, PREG_SET_ORDER);
        foreach ($matches as $match) {
            $method = $match[1];
            $body = $match[2];
            $relation = $this->parseRelationBody($method, $body);
            if ($relation === null) {
                continue;
            }

            $relations[] = $relation;
        }

        return $relations;
    }

    private function parseRelationBody(string $method, string $body): array|null
    {
        if (!preg_match('/has(One|Many)\\s*\\(\\s*(\\w+)::class/', $body, $match)) {
            return null;
        }

        $type = $match[1];
        $class = $match[2];
        $via = null;
        if (preg_match("/viaTable\\(\\s*'([^']+)'/", $body, $viaMatch)) {
            $via = $viaMatch[1];
        }

        return [
            'method' => $method . '()',
            'type' => 'has' . $type,
            'target' => $class,
            'via' => $via,
        ];
    }

    private function extractBehaviors(string $content): array
    {
        $behaviors = [];
        if (str_contains($content, 'TimestampBehavior::class')) {
            $behaviors[] = 'TimestampBehavior';
        }

        return $behaviors;
    }

    private function buildModelsYaml(array $models): string
    {
        $lines = [];
        $lines[] = 'meta:';
        $lines[] = '  title: "ActiveRecord Models"';
        $lines[] = '  source: "generated"';
        $lines[] = '  updated_at: ' . $this->yamlScalar(gmdate('c'));
        $lines[] = '';
        $lines[] = 'models:';

        foreach ($models as $model) {
            $lines[] = '  ' . $model['name'] . ':';
            $lines[] = '    class: ' . $this->yamlScalar($model['class']);
            $lines[] = '    table: ' . $this->yamlScalar($model['table']);
            $lines[] = '    behaviors:';
            if ($model['behaviors'] === []) {
                $lines[] = '      []';
            } else {
                foreach ($model['behaviors'] as $behavior) {
                    $lines[] = '      - ' . $this->yamlScalar($behavior);
                }
            }
            $lines[] = '    relations:';
            if ($model['relations'] === []) {
                $lines[] = '      []';
            } else {
                foreach ($model['relations'] as $relation) {
                    $lines[] = '      - method: ' . $this->yamlScalar($relation['method']);
                    $lines[] = '        type: ' . $this->yamlScalar($relation['type']);
                    $lines[] = '        target: ' . $this->yamlScalar($relation['target']);
                    $lines[] = '        via: ' . $this->yamlScalar($relation['via']);
                }
            }
        }

        return implode("\n", $lines) . "\n";
    }

    private function yamlScalar(mixed $value): string
    {
        if ($value === null) {
            return 'null';
        }
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }
        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }
        $escaped = addcslashes((string) $value, '\\"');
        return '"' . $escaped . '"';
    }

    private function writeDoc(string $filename, string $contents): void
    {
        $dir = Yii::getAlias('@app/docs/auto');
        FileHelper::createDirectory($dir);
        file_put_contents($dir . '/' . $filename, $contents);
    }
}
