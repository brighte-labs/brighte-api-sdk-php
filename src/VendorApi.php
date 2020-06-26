<?php

declare(strict_types = 1);

namespace BrighteCapital\Api;

use BrighteCapital\Api\Models\Category;
use BrighteCapital\Api\Models\Vendor;
use Fig\Http\Message\StatusCodeInterface;

class VendorApi extends \BrighteCapital\Api\AbstractApi
{

    public const PATH = '/vendors';

    /**
     * @return \BrighteCapital\Api\Models\Vendor[]
     */
    public function getVendors(): array
    {
        $response = $this->brighteApi->get(self::PATH);
        
        if ($response->getStatusCode() !== StatusCodeInterface::STATUS_OK) {
            $this->logResponse(__FUNCTION__, $response);

            return [];
        }
        
        $results = json_decode((string) $response->getBody());
        $vendors = [];

        foreach ($results as $result) {
            $vendor = new Vendor;
            $vendor->id = $result->id ?? null;
            $vendor->tradingName = $result->tradingName ?? null;
            $vendor->salesforceAccountId = $result->salesforceAccountId ?? null;
            $vendor->accountsEmail = $result->accountsEmail ?? null;
            $vendor->slug = $result->slug ?? null;
            $vendors[$result->id] = $vendor;
        }

        return $vendors;
    }

    /**
     * @param int $vendorId
     * @return int[]
     */
    public function getVendorAgentIDs(int $vendorId): array
    {
        $response = $this->brighteApi->get(sprintf('%s/%d/agents', self::PATH, $vendorId));
        
        if ($response->getStatusCode() !== StatusCodeInterface::STATUS_OK) {
            $this->logResponse(__FUNCTION__, $response);

            return [];
        }

        $agents = json_decode((string) $response->getBody(), true);

        return array_column($agents, 'userId');
    }

    /**
     * @param int $vendorId
     * @return \BrighteCapital\Api\Models\Category[]
     */
    public function getVendorCategories(int $vendorId): array
    {
        $response = $this->brighteApi->get(sprintf('%s/%d/categories', self::PATH, $vendorId));
        
        if ($response->getStatusCode() !== StatusCodeInterface::STATUS_OK) {
            $this->logResponse(__FUNCTION__, $response);

            return [];
        }

        $results = json_decode((string) $response->getBody());
        $categories = [];

        foreach ($results as $result) {
            $category = new Category;
            $category->id = $result->id;
            $category->name = $result->name;
            $category->slug = $result->slug;
            $categories[] = $category;
        }

        return $categories;
    }

}
