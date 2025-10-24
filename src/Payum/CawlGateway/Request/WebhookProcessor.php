<?php

/*
 * This file is part of the Flux:: shop.flux.audio sources.
 * COPYRIGHT (C), HARMAN INTERNATIONAL. ALL RIGHTS RESERVED.
 * -----------------------------------------------------------------------------
 * CONFIDENTIAL: NO PART OF THIS DOCUMENT MAY BE REPRODUCED IN ANY FORM WITHOUT THE
 * EXPRESSED WRITTEN PERMISSION OF HARMAN INTERNATIONAL.
 * DO NOT DISCLOSE ANY INFORMATION CONTAINED IN THIS DOCUMENT TO ANY THIRD-PARTY
 * WITHOUT THE PRIOR WRITTEN CONSENT OF HARMAN INTERNATIONAL.
 *
 */

declare(strict_types=1);

namespace Waaz\SyliusCawlPlugin\Payum\CawlGateway\Request;

use Payum\Core\Request\Generic;

final class WebhookProcessor extends Generic
{
    private array $payload;

    public function __construct(
        $model,
        array $payload
    ) {
        $this->payload = $payload;
        parent::__construct($model);
    }

    public function getPayload(): array
    {
        return $this->payload;
    }
}
