<?php

declare(strict_types=1);

use yii\mail\BaseMessage;
use yii\web\View;

/**
 * @var View $this view component instance
 * @var BaseMessage $message the message being composed
 * @var string $content main view render result
 */
$this->beginPage();
$this->beginBody();
echo $content;
$this->endBody();
$this->endPage();
