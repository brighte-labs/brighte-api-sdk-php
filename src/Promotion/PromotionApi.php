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
     * @return \BrighteCapital\Api\Promotion\Models\ApplicationPromotion|null
     * @throws \BrighteCapital\Api\Promotion\Exceptions\BadRequestException
     * @throws \BrighteCapital\Api\Promotion\Exceptions\PromotionException
     * @throws \ReflectionException
     */
    public function applyPromotion(ApplicationPromotion $applicationPromotion): ?Promotion
    {
        $url = sprintf('%s/applications', self::PATH);
        $body = json_encode($applicationPromotion->toArray());
        $response = $this->brighteApi->post($url, $body);

        $statusCode = $response->getStatusCode();

        if ($statusCode === StatusCodeInterface::STATUS_BAD_REQUEST) {
            $errors = json_decode($response->getBody());

            throw new BadRequestException($errors);
        }

        if ($statusCode === StatusCodeInterface::STATUS_NO_CONTENT) {
            return null;
        }

        if ($statusCode === StatusCodeInterface::STATUS_CREATED) {
            $applicationPromotionResponse = $response->getBody();

            return $this->jsonMapper::map($applicationPromotionResponse, Promotion::class);
        }
        throw new PromotionException("Failed to apply promotion");
    }

    /**
     * @param int $id promotion id
     * @return \BrighteCapital\Api\Promotion\Models\Promotion
     * @throws \BrighteCapital\Api\Promotion\Exceptions\RecordNotFoundException
     * @throws \ReflectionException
     */
    public function getPromotion(int $id): Promotion
    {
        $response = $this->brighteApi->get(sprintf('%s/%s', self::PATH, $id));

        if ($response->getStatusCode() === StatusCodeInterface::STATUS_OK) {
            $promotionResponse = $response->getBody();

            return $this->jsonMapper::map($promotionResponse, Promotion::class);
        }

        throw new RecordNotFoundException();
    }

    /**
     * Return array of Promotion objects or empty array
     *
     * @param string|null $query query string
     * @return Promotion[]
     * @throws \BrighteCapital\Api\Promotion\Exceptions\PromotionException
     * @throws \ReflectionException
     */
    public function getPromotions(string $query = null): array
    {
        $response = $this->brighteApi->get(sprintf('%s?%s', self::PATH, $query));

        if ($response->getStatusCode() === StatusCodeInterface::STATUS_OK) {
            $promotionsResponse = $response->getBody();
            $promotionList = json_decode($promotionsResponse, true);

            if (count($promotionList) > 0) {
                return $this->jsonMapper::mapArray($promotionsResponse, Promotion::class);
            }

            return [];
        }

        throw new PromotionException();
    }
}
