<?php

namespace Acquia\Console\Acsf\Tests\Unit;

use Acquia\Console\Acsf\Command\AcsfInitialize;
use Acquia\Console\Acsf\Platform\ACSFPlatform;
use Acquia\Console\Cloud\Tests\Command\PlatformCommandTestHelperTrait;
use Acquia\Console\Cloud\Tests\TestFixtureHelperTrait;
use EclipseGc\CommonConsole\PlatformInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Class AcsfInitializeTest.
 *
 * @coversDefaultClass \Acquia\Console\Acsf\Command\AcsfInitialize
 *
 * @group acquia-console-acsf
 *
 * @package Acquia\Console\Acsf\Tests\Unit
 */
class AcsfInitializeTest extends TestCase {

  use PlatformCommandTestHelperTrait;
  use TestFixtureHelperTrait;

  /**
   * @covers ::parseOutput
   */
  public function testParseOutput(): void {
    $input = $this->getFixtureContents('acsf_init_output.txt');
    $expected = [
      'testclitool.liftcontent.acsitefactory.com' => 'enabled',
      'subscriber.liftcontent.acsitefactory.com' => 'enabled',
      'publisher.liftcontent.acsitefactory.com' => 'disabled',
      'test2.liftcontent.acsitefactory.com' => 'disabled',
      'test3.liftcontent.acsitefactory.com' => 'enabled',
    ];

    $output = $this->getAcsfInitInstance()->parseOutput($input);
    $this->assertEquals($expected, $output, 'Output has been parsed correctly.');
  }

  /**
   * @covers ::getFilteredAcsfSites
   */
  public function testGetFilteredAcsfSites(): void {
    $input = $this->getFixtureContents('acsf_init_output.txt');
    $sites = $this->getSitesMockData();

    $acsf_init_cmd = $this->getAcsfInitInstance($sites);
    $output = $acsf_init_cmd->parseOutput($input);
    $filtered = $acsf_init_cmd->getFilteredAcsfSites($sites, $output);

    // Expected output.
    unset($sites[0], $sites[1]);

    // Standardize output order.
    sort($sites);
    sort($filtered);

    $this->assertEquals($sites, $filtered, 'Sites have been filtered based on content hub module.');
  }

  /**
   * Returns an AcsfInitialize object.
   *
   * @param array $client_return_value
   *   The value the client should return.
   *
   * @return \Acquia\Console\Acsf\Command\AcsfInitialize
   *   The acsf:init command object.
   */
  protected function getAcsfInitInstance(array $client_return_value = []): AcsfInitialize {
    $cmd = new AcsfInitialize($this->getDispatcher());
    $cmd->addPlatform('test', $this->getPlatform($client_return_value));
    return $cmd;
  }

  /**
   * Mock data of /sites.
   *
   * @return array[]
   *   Sample response of /sites endpoint call.
   */
  protected function getSitesMockData(): array {
    return [
      0 =>
        [
          'id' => 346,
          'db_name' => 'ijnpim346',
          'site' => 'test2',
          'stack_id' => 1,
          'domain' => 'test2.liftcontent.acsitefactory.com',
          'groups' =>
            [
              0 => 326,
            ],
          'site_collection' => false,
          'is_primary' => true,
        ],
      1 =>
        [
          'id' => 351,
          'db_name' => 'ijnpim351',
          'site' => 'publisher',
          'stack_id' => 1,
          'domain' => 'publisher.liftcontent.acsitefactory.com',
          'groups' =>
            [
              0 => 326,
            ],
          'site_collection' => false,
          'is_primary' => true,
        ],
      2 =>
        [
          'id' => 356,
          'db_name' => 'ijnpim356',
          'site' => 'subscriber',
          'stack_id' => 1,
          'domain' => 'subscriber.liftcontent.acsitefactory.com',
          'groups' =>
            [
              0 => 326,
            ],
          'site_collection' => false,
          'is_primary' => true,
        ],
      3 =>
        [
          'id' => 361,
          'db_name' => 'ijnpim361',
          'site' => 'testclitool',
          'stack_id' => 1,
          'domain' => 'testclitool.liftcontent.acsitefactory.com',
          'groups' =>
            [
              0 => 326,
            ],
          'site_collection' => false,
          'is_primary' => true,
        ],
      4 =>
        [
          'id' => 367,
          'db_name' => 'ijnpim367',
          'site' => 'test3',
          'stack_id' => 1,
          'domain' => 'test3.liftcontent.acsitefactory.com',
          'groups' =>
            [
              0 => 326,
            ],
          'site_collection' => false,
          'is_primary' => true,
        ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getPlatform(array $args = []): PlatformInterface {
    $client_mock_modifier = function(MockObject $client) use ($args) {
      $client->method('listSites')->willReturn($args);
    };

    return $this->getAcsfPlatform(
      [
        ACSFPlatform::SITEFACTORY_USER => 'test_user',
        ACSFPlatform::SITEFACTORY_TOKEN => 'test_token',
        ACSFPlatform::SITEFACTORY_URL => 'test_url'
      ],
      $client_mock_modifier
    );
  }

}
