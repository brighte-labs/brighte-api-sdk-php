<?php

namespace BrighteCapital\Api\Promotion;

use BrighteCapital\Api\AbstractApi;
use BrighteCapital\Api\Promotion\Exceptions\BadRequestException;
use BrighteCapital\Api\Promotion\Exceptions\PromotionException;
use BrighteCapital\Api\Promotion\Exceptions\RecordNotFoundException;
use BrighteCapital\Api\Promotion\Models\ApplicationPromotion;
use Fig\Http\Message\StatusCodeInterface;

class PromotionApi extends AbstractApi
{
    public const PATH = '/promotions';

    public function applyPromotion(ApplicationPromotion $applicationPromotion)
    {
        $url = sprintf('%s/applications', self::PATH);
        $response = $this->brighteApi->post($url, json_encode($applicationPromotion->toArray()));

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

        if ($response->getStatusCode() == StatusCodeInterface::STATUS_OK) {
            return json_decode((string)$response->getBody());
        }

        throw new RecordNotFoundException();
    }
}
