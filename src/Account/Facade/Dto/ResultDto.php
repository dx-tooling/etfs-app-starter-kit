<?php

namespace App\Account\Facade\Dto;

final class ResultDto
{
    public function __construct(
        public bool    $isSuccess,
        public ?string $errorMessage = null,
        public ?string $userId = null
    ) {
    }
}
