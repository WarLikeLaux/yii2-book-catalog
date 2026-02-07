<?php

declare(strict_types=1);

namespace app\commands\support;

use RuntimeException;
use Symfony\Component\Yaml\Yaml;
use Throwable;
use Yii;
use yii\db\TableSchema;
use yii\helpers\FileHelper;
use yii\helpers\Inflector;
use yii\web\UrlManager;
use yii\web\UrlRule;

final readonly class AutoDocService
{
    private const string IDEMPOTENCY_GUARD = 'Idempotency';
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
            ],
            'tables' => $tables,
        ]);
    }

    public function generateRoutes(): void
    {
        $controllerDir = Yii::getAlias('@app/presentation/controllers');
        $files = FileHelper::findFiles((string)$controllerDir, ['only' => ['*Controller.php']]);
        $urlRules = $this->loadUrlRules();
        $routes = [];

        foreach ($files as $file) {
            foreach ($this->parseControllerRoutes($file, $urlRules) as $route) {
                $routes[] = $route;
            }
        }

        usort($routes, static fn (array $a, array $b): int => strcmp($a['path'], $b['path']));

        $this->saveYaml('routes.yaml', [
            'meta' => [
                'title' => 'HTTP Routes',
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

            if (preg_match('/@param\s+([A-Za-z0-9_|\\\\<>,\[\] ]+)\s+\$command/', $content, $docM)) {
                $command = $this->normalizeCommandType($docM[1]);
            } elseif (preg_match('/execute\(\s*([A-Za-z0-9_\\\\]+)\s+\$command\)/', $content, $sigM)) {
                $command = $this->normalizeCommandType($sigM[1]);
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
            ],
            'events' => $events,
        ]);
    }

    private function mapTable(TableSchema $tableSchema): array
    {
        return [
            'columns' => $this->mapColumns($tableSchema),
            'primary_key' => array_values($tableSchema->primaryKey),
            'foreign_keys' => $this->mapForeignKeys($tableSchema),
            'indexes' => $this->mapIndexes($tableSchema),
        ];
    }

    private function mapColumns(TableSchema $tableSchema): array
    {
        $columns = [];

        foreach ($tableSchema->columns as $column) {
            $columnData = [
                'name' => $column->name,
                'type' => $column->dbType,
                'nullable' => $column->allowNull,
            ];

            if ($column->defaultValue !== null) {
                $columnData['default'] = $column->defaultValue;
            }

            $columns[] = $columnData;
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
            $allIndexes = $schema->getTableIndexes($tableSchema->name);

            foreach ($allIndexes as $indexConstraint) {
                if ($indexConstraint->isPrimary || $indexConstraint->name === null) {
                    continue;
                }

                $columns = array_filter(
                    (array)$indexConstraint->columnNames,
                    static fn($col): bool => $col !== null,
                );

                if ($columns === []) {
                    continue;
                }

                $indexes[] = [
                    'name' => $indexConstraint->name,
                    'columns' => array_values($columns),
                    'unique' => $indexConstraint->isUnique,
                ];
            }
        } catch (Throwable $exception) {
            Yii::error([
                'message' => 'Failed to read table indexes',
                'table' => $tableSchema->name,
                'exception' => $exception,
            ], 'application');
            return [];
        }

        return $indexes;
    }

    /**
     * @param array<string, string> $urlRules
     * @return array<int, array<string, mixed>>
     */
    private function parseControllerRoutes(string $file, array $urlRules): array
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
        $relativeDir = $this->getRelativeControllerDir($file);
        $prefix = $relativeDir !== '' ? str_replace(DIRECTORY_SEPARATOR, '/', $relativeDir) . '/' : '';

        foreach ($actionBodies as $actionName => $body) {
            $entry = $this->buildRouteEntry(
                $controllerId,
                $controllerName,
                $prefix,
                $actionName,
                $verbMap,
                $body,
                $urlRules,
            );

            $guards = $this->filterGuardsForMethods($behaviors, $entry['methods']);

            if ($guards !== []) {
                $entry['guards'] = $guards;
            }

            $routes[] = $entry;
        }

        return $routes;
    }

    private function extractVerbMap(string $content): array
    {
        $pos = strpos($content, 'VerbFilter::class');

        if ($pos === false) {
            return [];
        }

        $actionsPos = strpos($content, "'actions'", $pos);

        if ($actionsPos === false) {
            $actionsPos = strpos($content, '"actions"', $pos);
        }

        if ($actionsPos === false) {
            return [];
        }

        $openBracket = strpos($content, '[', $actionsPos);

        if ($openBracket === false) {
            return [];
        }

        $block = $this->extractBalancedBracket($content, $openBracket);

        if ($block === null) {
            return [];
        }

        $actions = $this->parseVerbActions("/'([a-zA-Z0-9_-]+)'\s*=>\s*\[(.*?)]/s", $block, false);

        $enumActions = $this->parseVerbActions('/\w+::(\w+)->value\s*=>\s*\[(.*?)]/s', $block, true);

        return array_merge($actions, $enumActions);
    }

    /**
     * @return array<string, list<string>>
     */
    private function parseVerbActions(string $pattern, string $block, bool $camelToId): array
    {
        $actions = [];
        preg_match_all($pattern, $block, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $action = $camelToId ? Inflector::camel2id($match[1]) : $match[1];
            preg_match_all("/'([a-zA-Z]+)'/", $match[2], $methodMatches);
            $methods = array_map('strtoupper', $methodMatches[1] ?? []);

            if ($methods === []) {
                continue;
            }

            $actions[$action] = $methods;
        }

        return $actions;
    }

    private function extractBalancedBracket(string $content, int $start): ?string
    {
        $len = strlen($content);
        $depth = 0;

        for ($i = $start; $i < $len; $i++) {
            $char = $content[$i];

            if ($char === '[') {
                $depth++;
            } elseif ($char === ']') {
                $depth--;
            }

            if ($depth === 0) {
                return substr($content, $start + 1, $i - $start - 1);
            }
        }

        return null;
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
            $start = (int)$positions[$i][1];
            $end = $i + 1 < $count ? (int)$positions[$i + 1][1] : strlen($content);
            $result[$actionName] = substr($content, $start, $end - $start);
        }

        return $result;
    }

    /**
     * @param array<string, string> $urlRules
     * @return array<string, mixed>
     */
    private function buildRouteEntry(
        string $controllerId,
        string $controllerName,
        string $prefix,
        string $actionName,
        array $verbMap,
        string $body,
        array $urlRules,
    ): array {
        $actionId = Inflector::camel2id($actionName);
        $action = "$controllerId/$actionId";
        $path = $this->resolvePublicPath($action, $urlRules);

        $methods = $verbMap[$actionId] ?? (str_contains($body, 'isPost') ? ['GET', 'POST'] : ['GET']);

        $entry = [
            'path' => $path,
            'action' => $action,
            'controller' => "{$prefix}{$controllerName}Controller",
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

    /**
     * @param array<string, string> $urlRules
     */
    private function resolvePublicPath(string $action, array $urlRules): string
    {
        $publicUrl = array_search($action, $urlRules, true);

        if ($publicUrl !== false) {
            return '/' . ltrim((string)$publicUrl, '/');
        }

        $actionParts = explode('/', $action);
        $lastPart = end($actionParts);

        if ($lastPart === 'index') {
            if ($actionParts[0] === 'site') {
                return '/';
            }

            return '/' . implode('/', array_slice($actionParts, 0, -1));
        }

        return '/' . $action;
    }

    /**
     * @return array<string, string>
     */
    private function loadUrlRules(): array
    {
        $rules = $this->loadUrlRulesFromApp();

        if ($rules !== []) {
            return $rules;
        }

        return $this->loadUrlRulesFromConfig();
    }

    /**
     * @return array<string, string>
     */
    private function loadUrlRulesFromApp(): array
    {
        $app = Yii::$app;

        if ($app === null || !$app->has('urlManager', true)) {
            return [];
        }

        $urlManager = $app->get('urlManager');

        if (!$urlManager instanceof UrlManager) {
            return [];
        }

        $rules = [];

        foreach ($urlManager->rules as $rule) {
            if (!($rule instanceof UrlRule)) {
                continue;
            }

            $pattern = $rule->pattern;

            if (!is_string($pattern) || $pattern === '' || !is_string($rule->route) || $rule->route === '') {
                continue;
            }

            $rules[$pattern] = $rule->route;
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    private function loadUrlRulesFromConfig(): array
    {
        $configPath = Yii::getAlias('@app/config/web.php');

        if (!is_string($configPath) || !file_exists($configPath)) {
            return [];
        }

        $config = require $configPath;

        if (!is_array($config)) {
            return [];
        }

        $configRules = $config['components']['urlManager']['rules'] ?? null;

        if (!is_array($configRules)) {
            return [];
        }

        $rules = [];

        foreach ($configRules as $pattern => $route) {
            if (!is_string($pattern) || !is_string($route)) {
                continue;
            }

            $rules[$pattern] = $route;
        }

        return $rules;
    }

    /**
     * @param array<int, string> $guards
     * @param array<int, string> $methods
     * @return array<int, string>
     */
    private function filterGuardsForMethods(array $guards, array $methods): array
    {
        if ($methods === ['GET'] && in_array(self::IDEMPOTENCY_GUARD, $guards, true)) {
            return array_values(array_filter($guards, static fn(string $guard): bool => $guard !== self::IDEMPOTENCY_GUARD));
        }

        return $guards;
    }

    private function resolveControllerId(string $file, string $controllerName): string
    {
        $controllerId = Inflector::camel2id($controllerName);
        $relativeDir = $this->getRelativeControllerDir($file);

        if ($relativeDir === '') {
            return $controllerId;
        }

        $prefix = str_replace(DIRECTORY_SEPARATOR, '/', $relativeDir);

        return trim($prefix . '/' . $controllerId, '/');
    }

    private function getRelativeControllerDir(string $file): string
    {
        $controllerDir = realpath((string)Yii::getAlias('@app/presentation/controllers'));
        $dir = realpath(dirname($file));

        if ($controllerDir === false || $dir === false) {
            return '';
        }

        if (!str_starts_with($dir, $controllerDir)) {
            return '';
        }

        return trim(substr($dir, strlen($controllerDir)), DIRECTORY_SEPARATOR);
    }

    private function extractBehaviorsFromCode(string $content): array
    {
        $found = [];

        if (str_contains($content, 'IdempotencyFilter::class')) {
            $found[] = self::IDEMPOTENCY_GUARD;
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
            'behaviors' => str_contains($content, 'TimestampBehavior::class') ? ['TimestampBehavior'] : [],
            'relations' => $this->extractRelations($content),
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

        preg_match_all("/(?<!\[)\[\s*'(\w+)'\s*,\s*'(\w+)'\s*(?:,.*?)?\]/", $rulesBlock, $simpleMatches, PREG_SET_ORDER);

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

    private function normalizeCommandType(string $type): string
    {
        $parts = explode('|', $type);
        $primaryType = trim($parts[0]);
        $baseType = (string)preg_replace('/\[\]$/', '', $primaryType);
        $cleanType = (string)preg_replace('/<.*>$/', '', $baseType);
        $segments = explode('\\', $cleanType);
        $last = end($segments);

        return is_string($last) && $last !== '' ? $last : 'mixed';
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
        $yaml = Yaml::dump($data, 10, 2, Yaml::DUMP_EMPTY_ARRAY_AS_SEQUENCE);

        if (file_put_contents($path, $yaml) === false) {
            throw new RuntimeException(sprintf('Failed to write %d bytes to file: %s', strlen($yaml), $path));
        }
    }
}
