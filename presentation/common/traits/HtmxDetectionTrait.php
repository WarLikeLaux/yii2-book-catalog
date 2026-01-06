<?php

declare(strict_types=1);

namespace app\presentation\common\traits;

use yii\web\Request;

trait HtmxDetectionTrait
{
    protected function isHtmxRequest(): bool
    {
        return $this->getRequestObject()->getHeaders()->has('HX-Request');
    }

    protected function getHtmxTrigger(): ?string
    {
        $value = $this->getRequestObject()->getHeaders()->get('HX-Trigger');
        return is_array($value) ? ($value[0] ?? null) : $value;
    }

    protected function getHtmxTarget(): ?string
    {
        $value = $this->getRequestObject()->getHeaders()->get('HX-Target');
        return is_array($value) ? ($value[0] ?? null) : $value;
    }

    private function getRequestObject(): Request
    {
        /** @var Request $request */
        $request = $this->request;
        return $request;
    }
}
