<?php

/**
 * Copyright (c) Antistress.Store® 2024. All rights reserved.
 * See LICENSE.md for license details.
 *
 * @author Sergey Gusev
 */

namespace AntistressStore\CdekSDK2\Exceptions;

class CdekV2RequestException extends CdekV2Exception
{
    /** @var string|null */
    private $apiErrorCode;

    public static function fromApiError(string $method, array $error, int $statusCode): self
    {
        $code = isset($error['code']) ? (string) $error['code'] : null;
        $exceptionClass = in_array($code, ['v2_entity_forbidden', 'v2_order_forbidden'], true)
            ? CdekV2ForeignEntityException::class
            : self::class;
        $message = self::getTranslation($code ?? '', $error['message'] ?? '');
        $exception = new $exceptionClass(
            'От API CDEK при вызове метода '.$method.' получена ошибка: '.$message,
            $statusCode
        );
        $exception->apiErrorCode = $code;

        return $exception;
    }

    public function getApiErrorCode(): ?string
    {
        return $this->apiErrorCode;
    }
}
