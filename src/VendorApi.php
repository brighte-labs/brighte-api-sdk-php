<?php

declare(strict_types=1);

namespace BrighteCapital\Api;

use BrighteCapital\Api\Models\Category;
use BrighteCapital\Api\Models\Manufacturer;
use BrighteCapital\Api\Models\PromoCode;
use BrighteCapital\Api\Models\Vendor;
use BrighteCapital\Api\Models\VendorFlag;
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
            $vendor = new Vendor();
            $vendor->id = $result->id ?? null;
            $vendor->remoteId = $result->remoteId ?? null;
            $vendor->tradingName = $result->tradingName ?? null;
            $vendor->salesforceAccountId = $result->salesforceAccountId ?? null;
            $vendor->accountsEmail = $result->accountsEmail ?? null;
            $vendor->slug = $result->slug ?? null;
            $vendors[$result->id] = $vendor;
        }

        return $vendors;
    }

    public function getVendor(int $vendorId): ?Vendor
    {
        $response = $this->brighteApi->get(self::PATH . '/' . $vendorId);
        
        if ($response->getStatusCode() !== StatusCodeInterface::STATUS_OK) {
            $this->logResponse(__FUNCTION__, $response);

            return null;
        }
        
        $result = json_decode((string) $response->getBody());

        $vendor = new Vendor();
        $vendor->id = $result->id ?? null;
        $vendor->remoteId = $result->remoteId ?? null;
        $vendor->tradingName = $result->tradingName ?? null;
        $vendor->salesforceAccountId = $result->salesforceAccountId ?? null;
        $vendor->accountsEmail = $result->accountsEmail ?? null;
        $vendor->slug = $result->slug ?? null;

        return $vendor;
    }

    /**
     * @return VendorFlag[]
     */
    public function getVendorFlags(int $vendorId): array
    {
        $path = sprintf("%s/%d/flags", self::PATH, $vendorId);
        $response = $this->brighteApi->get($path);
        if ($response->getStatusCode() !== StatusCodeInterface::STATUS_OK) {
            $this->logResponse(__FUNCTION__, $response);

            return [];
        }

        $flags = [];
        $results = json_decode((string) $response->getBody());
        foreach ($results as $result) {
            $flag = new VendorFlag();
            $flag->id = $result->id ?? null;
            $flag->flag = $result->flag ?? null;
            $flag->description = $result->description ?? null;
            $flags[] = $flag;
        }
        return $flags;
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
    public function getCategories(): array
    {
        $response = $this->brighteApi->get('/categories');
        
        if ($response->getStatusCode() !== StatusCodeInterface::STATUS_OK) {
            $this->logResponse(__FUNCTION__, $response);

            return [];
        }

        $results = json_decode((string) $response->getBody());
        $categories = [];

        foreach ($results as $result) {
            $category = new Category();
            $category->id = $result->id;
            $category->name = $result->name;
            $category->slug = $result->slug;
            $categories[$category->id] = $category;
        }

        return $categories;
    }

    public function getCategory(int $categoryId): ?Category
    {
        $response = $this->brighteApi->get('/categories/' . $categoryId);

        if ($response->getStatusCode() !== StatusCodeInterface::STATUS_OK) {
            $this->logResponse(__FUNCTION__, $response);

            return null;
        }

        $result = json_decode((string) $response->getBody());

        $category = new Category();
        $category->id = $result->id;
        $category->name = $result->name;
        $category->slug = $result->slug;
        $category->group = $result->group ?? null;

        return $category;
    }

    /**
     * @param int $manufacturerId
     * @return \BrighteCapital\Api\Models\Manufacturer
     */
    public function getManufacturerById(int $manufacturerId): Manufacturer
    {
        $response = $this->brighteApi->get("/manufacturers/" . (string) $manufacturerId);

        if ($response->getStatusCode() !== StatusCodeInterface::STATUS_OK) {
            $this->logResponse(__FUNCTION__, $response);

            return null;
        }

        $result = json_decode((string) $response->getBody());
        $manufacturer = new Manufacturer();
        $manufacturer->id = $result->id;
        $manufacturer->name = $result->name;
        $manufacturer->description = $result->description;
        $manufacturer->icon = $result->icon;
        $manufacturer->slug = $result->slug;
        $manufacturer->premium = $result->premium;

        return $manufacturer;
    }

    /**
     * @param string $categorySlug
     * @return \BrighteCapital\Api\Models\Manufacturer[]
     */
    public function getManufacturersByCategory(string $categorySlug): array
    {
        return $this->retrieveManufacturers("/manufacturers?category=" . $categorySlug);
    }
    
    /**
     * @return \BrighteCapital\Api\Models\Manufacturer[]
     */
    public function getManufacturers(): array
    {
        return $this->retrieveManufacturers("/manufacturers");
    }

    /**
     * @param string $queryPath
     * @return \BrighteCapital\Api\Models\Manufacturer[]
     */
    private function retrieveManufacturers(string $queryPath): array
    {
        $response = $this->brighteApi->get($queryPath);

        if ($response->getStatusCode() !== StatusCodeInterface::STATUS_OK) {
            $this->logResponse(debug_backtrace()[1]['function'], $response);

            return [];
        }

        $results = json_decode((string) $response->getBody());
        $manufacturers = [];

        foreach ($results as $result) {
            $manufacturer = new Manufacturer();
            $manufacturer->id = $result->id;
            $manufacturer->name = $result->name;
            $manufacturer->description = $result->description;
            $manufacturer->icon = $result->icon;
            $manufacturer->slug = $result->slug;
            $manufacturer->premium = $result->premium;
            $manufacturers[$manufacturer->id] = $manufacturer;
        }

        return $manufacturers;
    }

    /**
     * @param int $vendorId
     * @param bool $active
     * @return \BrighteCapital\Api\Models\PromoCode[]
     */
    public function getVendorPromos(int $vendorId, bool $active = false): array
    {
        $response = $this->brighteApi->get(sprintf('%s/%d/promos?active=%s', self::PATH, $vendorId, $active));

        if ($response->getStatusCode() !== StatusCodeInterface::STATUS_OK) {
            $this->logResponse(__FUNCTION__, $response);

            return [];
        }

        $results = json_decode((string) $response->getBody());
        $promoCodes = [];

        foreach ($results as $result) {
            $promoCode = new PromoCode();
            $promoCode->id = $result->id;
            $promoCode->code = $result->code;
            $promoCode->type = $result->type;
            $promoCode->start = $result->start;
            $promoCode->end = $result->end;
            $promoCodes[$result->id] = $promoCode;
        }

        return $promoCodes;
    }
}
