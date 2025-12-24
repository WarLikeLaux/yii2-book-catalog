<?php

declare(strict_types=1);

namespace app\infrastructure\adapters;

use app\application\ports\TranslatorInterface;
use Yii;

final class YiiTranslatorAdapter implements TranslatorInterface
{
    public function translate(string $category, string $message, array $params = []): string
    {
        return Yii::t($category, $message, $params);
    }
}
