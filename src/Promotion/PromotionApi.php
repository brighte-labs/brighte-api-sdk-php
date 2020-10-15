<?php

namespace BrighteCapital\Api\Promotion;

use BrighteCapital\Api\AbstractApi;
use BrighteCapital\Api\Exceptions\BadRequestException;
use BrighteCapital\Api\Exceptions\RecordNotFoundException;
use BrighteCapital\Api\Promotion\Exceptions\PromotionException;
use BrighteCapital\Api\Promotion\Models\Application;
use BrighteCapital\Api\Promotion\Models\Promotion;
use Fig\Http\Message\StatusCodeInterface;

class PromotionApi extends AbstractApi
{
    public const PATH = '/promotions';

    /**
     * Tries apply promotion to application if its applicable for vendor and product type
     * 202 status code means it not applicable
     *
     * @param \BrighteCapital\Api\Promotion\Models\Application $applicationPromotion
     * @return \BrighteCapital\Api\Promotion\Models\Application|null
     * @throws \BrighteCapital\Api\Exceptions\BadRequestException
     * @throws \BrighteCapital\Api\Promotion\Exceptions\PromotionException
     */
    public function applyPromotion(Application $applicationPromotion): ?Application
    {
        $url = sprintf('%s/applications', self::PATH);
        $body = json_encode($applicationPromotion->toArray());
        $response = $this->brighteApi->post($url, $body);

        $statusCode = $response->getStatusCode();

        if ($statusCode === StatusCodeInterface::STATUS_BAD_REQUEST) {
            throw new BadRequestException(json_decode($response->getBody()));
        }

        if ($statusCode === StatusCodeInterface::STATUS_NO_CONTENT) {
            return null;
        }

        if ($statusCode !== StatusCodeInterface::STATUS_CREATED) {
            throw new PromotionException("Failed to apply promotion");
        }

        try {
            $response = json_decode($response->getBody(), true);

            return new Application(
                $response['id'],
                $response['vendorId'],
                $response['product'],
                $response['code']
            );
        } catch (\Exception $e) {
            throw new PromotionException(
                'Failed to parse response after promo application - ' . $e->getMessage()
            );
        }
    }

    /**
     * @param string $id promotion id
     * @return \BrighteCapital\Api\Promotion\Models\Promotion
     * @throws \BrighteCapital\Api\Exceptions\RecordNotFoundException
     * @throws \BrighteCapital\Api\Promotion\Exceptions\PromotionException
     */
    public function getPromotion(string $id): Promotion
    {
        $response = $this->brighteApi->get(sprintf('%s/%s', self::PATH, $id));

        if ($response->getStatusCode() !== StatusCodeInterface::STATUS_OK) {
            throw new RecordNotFoundException();
        }

        try {
            $this->jsonMapper->bStrictNullTypes = false;
            return $this->jsonMapper->map(json_decode($response->getBody()), new Promotion());
        } catch (\Exception $e) {
            throw new PromotionException(
                sprintf("Failed to map json response to :%s - %s", Promotion::class, $e->getMessage())
            );
        }
    }

    /**
     * Return array of Promotion objects or empty array
     *
     * @param string|null $query query string
     * @return Promotion[]
     * @throws \BrighteCapital\Api\Promotion\Exceptions\PromotionException
     */
    public function getPromotions(string $query = null): array
    {
        $response = $this->brighteApi->get(self::PATH, $query);

        if ($response->getStatusCode() !== StatusCodeInterface::STATUS_OK) {
            return [];
        }

        try {
            $this->jsonMapper->bStrictNullTypes = false;

            return $this->jsonMapper->mapArray(json_decode($response->getBody()), array(), Promotion::class);
        } catch (\Exception $e) {
            throw new PromotionException(
                sprintf("Failed to map json response to :%s - %s", Promotion::class, $e->getMessage())
            );
        }
    }
}
