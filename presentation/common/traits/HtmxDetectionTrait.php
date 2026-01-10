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
        return $this->getRequestObject()->getHeaders()->get('HX-Trigger');
    }

    protected function getHtmxTarget(): ?string
    {
        return $this->getRequestObject()->getHeaders()->get('HX-Target');
    }

    private function getRequestObject(): Request
    {
        return $this->request;
    }
}
