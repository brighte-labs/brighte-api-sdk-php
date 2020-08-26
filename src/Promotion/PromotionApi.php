<?php

namespace BrighteCapital\Api\Promotion;

use BrighteCapital\Api\AbstractApi;
use BrighteCapital\Api\Promotion\Exceptions\BadRequestException;
use BrighteCapital\Api\Promotion\Exceptions\PromotionException;
use BrighteCapital\Api\Promotion\Exceptions\RecordNotFoundException;
use BrighteCapital\Api\Promotion\Models\ApplicationPromotion;
use BrighteCapital\Api\Promotion\Models\Promotion;
use Fig\Http\Message\StatusCodeInterface;

class PromotionApi extends AbstractApi
{
    public const PATH = '/promotions';

    /**
     * Tries apply promotion to application if its applicable for vendor and product type
     * 202 status code means it not applicable
     *
     * @param \BrighteCapital\Api\Promotion\Models\ApplicationPromotion $applicationPromotion
     * @return \BrighteCapital\Api\Promotion\Models\Promotion|null
     * @throws \BrighteCapital\Api\Promotion\Exceptions\BadRequestException
     * @throws \BrighteCapital\Api\Promotion\Exceptions\PromotionException
     */
    public function applyPromotion(ApplicationPromotion $applicationPromotion): ?Promotion
    {
        $url = sprintf('%s/applications', self::PATH);
        $body = json_encode($applicationPromotion->toArray());

        $response = $this->brighteApi->post($url, $body);

        $statusCode = $response->getStatusCode();

        if ($statusCode === StatusCodeInterface::STATUS_BAD_REQUEST) {
            $errors = json_decode((string)$response->getBody());

            throw new BadRequestException($errors);
        }

        if ($statusCode === StatusCodeInterface::STATUS_NO_CONTENT) {
            return null;
        }

        if ($statusCode === StatusCodeInterface::STATUS_CREATED) {
            return json_decode((string)$response->getBody());
        }
        throw new PromotionException("Failed to apply promotion");
    }

    /**
     * @param int $id promotion id
     * @return array
     * @throws \BrighteCapital\Api\Promotion\Exceptions\RecordNotFoundException
     */
    public function getPromotion(int $id)
    {
        $response = $this->brighteApi->get(sprintf('%s/%s', self::PATH, $id));

        if ($response->getStatusCode() === StatusCodeInterface::STATUS_OK) {
            return json_decode((string)$response->getBody());
        }

        throw new RecordNotFoundException();
    }
}
