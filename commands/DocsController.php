<?php

declare(strict_types=1);

namespace app\commands;

use app\commands\support\AutoDocService;
use app\commands\support\ProjectMapPrinter;
use yii\console\Controller;
use yii\console\ExitCode;

final class DocsController extends Controller
{
    public function __construct(
        $id,
        $module,
        private readonly AutoDocService $autoDocService,
        private readonly ProjectMapPrinter $mapPrinter,
        array $config = []
    ) {
        parent::__construct($id, $module, $config);
    }

    public function actionAll(): int
    {
        $this->actionDb();
        $this->actionRoutes();
        $this->actionModels();
        $this->actionUseCases();
        $this->actionEvents();

        return ExitCode::OK;
    }

    public function actionDb(): int
    {
        $this->autoDocService->generateDbSchema();
        $this->stdout("✅ Схема БД обновлена (docs/auto/db.yaml)\n");
        return ExitCode::OK;
    }

    public function actionRoutes(): int
    {
        $this->autoDocService->generateRoutes();
        $this->stdout("✅ Маршруты обновлены (docs/auto/routes.yaml)\n");
        return ExitCode::OK;
    }

    public function actionModels(): int
    {
        $this->autoDocService->generateModelsDoc();
        $this->stdout("✅ Документация моделей обновлена (docs/auto/models.yaml)\n");
        return ExitCode::OK;
    }

    public function actionUseCases(): int
    {
        $this->autoDocService->generateUseCasesDoc();
        $this->stdout("✅ Реестр UseCases обновлен (docs/auto/usecases.yaml)\n");
        return ExitCode::OK;
    }

    public function actionEvents(): int
    {
        $this->autoDocService->generateEventsDoc();
        $this->stdout("✅ Карта событий обновлена (docs/auto/events.yaml)\n");
        return ExitCode::OK;
    }

    public function actionTree(): int
    {
        $this->mapPrinter->print($this);
        return ExitCode::OK;
    }
}
