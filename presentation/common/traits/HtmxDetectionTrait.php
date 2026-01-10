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

        return $this->normalizeHeaderValue($value);
    }

    protected function getHtmxTarget(): ?string
    {
        $value = $this->getRequestObject()->getHeaders()->get('HX-Target');

        return $this->normalizeHeaderValue($value);
    }

    private function getRequestObject(): Request
    {
        return $this->request;
    }

    private function normalizeHeaderValue(mixed $value): ?string
    {
        if (is_array($value)) {
            if ($value === []) {
                return null;
            }

            $first = $value[0];

            if (is_string($first)) {
                return $first;
            }

            return is_scalar($first) ? (string)$first : null;
        }

        if ($value === null) {
            return null;
        }

        if (is_string($value)) {
            return $value;
        }

        return is_scalar($value) ? (string)$value : null;
    }
}
