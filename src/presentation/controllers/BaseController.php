<?php

declare(strict_types=1);

namespace app\presentation\controllers;

use app\application\common\exceptions\ApplicationException;
use app\presentation\common\ViewModelRenderer;
use LogicException;
use Override;
use Yii;
use yii\base\Model;
use yii\web\Application;
use yii\web\Controller;

abstract class BaseController extends Controller
{
    public function __construct(
        $id,
        $module,
        protected ViewModelRenderer $renderer,
        $config = [],
    ) {
        $this->renderer->setController($this);
        parent::__construct($id, $module, $config);
    }

    #[Override]
    public function render($_view, $_params = []): string
    {
        if ($_view === 'error') {
            return parent::render($_view, $_params);
        }

        throw new LogicException(
            'Use $this->renderer->render() for rendering views. Example: $this->renderer->render("view", $viewModel)',
        );
    }

    protected function addFormError(Model $form, ApplicationException $exception): void
    {
        $field = $exception->getField();

        if ($field) {
            $form->addError($field, Yii::t('app', $exception->errorCode));
            return;
        }

        $form->addError('', Yii::t('app', $exception->errorCode));
    }

    protected function flash(string $type, string $message): void
    {
        $app = Yii::$app;
        assert($app instanceof Application);
        $app->session->setFlash($type, $message);
    }
}
