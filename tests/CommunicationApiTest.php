<?php

declare(strict_types=1);

namespace BrighteCapital\Tests\Api;

use BrighteCapital\Api\BrighteApi;
use BrighteCapital\Api\CommunicationApi;
use BrighteCapital\Api\Models\Notification;
use GuzzleHttp\Psr7\Response;
use Psr\Log\LoggerInterface;

/**
 * @coversDefaultClass \BrighteCapital\Api\CommunicationApi
 */
class CommunicationApiTest extends \PHPUnit\Framework\TestCase
{

    /** @var \Psr\Log\LoggerInterface */
    protected $logger;

    /** @var \BrighteCapital\Api\BrighteApi */
    protected $brighteApi;

    /** @var \BrighteCapital\Api\CommunicationApi */
    protected $communicationApi;

    protected function setUp(): void
    {
        parent::setUp();
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->brighteApi = $this->createMock(BrighteApi::class);
        $this->communicationApi = new CommunicationApi($this->logger, $this->brighteApi);
    }

    /**
     * @covers ::__construct
     * @covers ::createNotification
     */
    public function testCreateNotification(): void
    {
        $expectedBody = [
            'to' => '0412312412',
            'templateKey' => 'sms_authcode_tc_bp',
            'payload' => [
                'id' => 123,
            ],
        ];
        $notification = new Notification();
        $notification->recipient = '0412312412';
        $notification->templateKey = 'sms_authcode_tc_bp';
        $notification->data = ['id' => 123];
        $response = new Response(200, [], json_encode(['id' => '56e659ac-d541-11ea-87d0-0242ac130003']));
        $this->brighteApi->expects(self::once())->method('post')
            ->with('/communications/notifications', json_encode($expectedBody))
            ->willReturn($response);
        $this->communicationApi->createNotification($notification);
        $this->assertEquals('56e659ac-d541-11ea-87d0-0242ac130003', $notification->id);
    }

    /**
     * @covers ::__construct
     * @covers ::createNotification
     * @covers ::logResponse
     */
    public function testCreateNotificationFail(): void
    {
        $response = new Response(404, [], json_encode(['message' => 'Template not found']));
        $this->logger->expects(self::once())->method('warning')->with(
            'BrighteCapital\Api\AbstractApi->createNotification: 404: Template not found'
        );
        $this->brighteApi->expects(self::once())->method('post')
            ->with('/communications/notifications')->willReturn($response);
        $notification = $this->communicationApi->createNotification(new Notification());
        self::assertNull($notification);
    }
}
