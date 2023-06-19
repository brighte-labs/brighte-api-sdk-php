<?php

declare(strict_types=1);

namespace BrighteCapital\Api;

use BrighteCapital\Api\Models\Notification;
use Fig\Http\Message\StatusCodeInterface;

class CommunicationApi extends \BrighteCapital\Api\AbstractApi
{

    public const PATH = '/communications';

    public function createNotification(Notification $notification): ?Notification
    {
        $body = json_encode([
            'to' => $notification->to,
            'templateKey' => $notification->templateKey,
            'payload' => $notification->payload ?: null,
        ]);

        $response = $this->brighteApi->post(sprintf('%s/notifications', self::PATH), $body, '', [], self::PATH);

        if ($response->getStatusCode() !== StatusCodeInterface::STATUS_CREATED) {
            $this->logResponse(__FUNCTION__, $response);

            return null;
        }

        $result = json_decode((string) $response->getBody());

        $notification->id = $result->id ?? null;

        return $notification;
    }
}
