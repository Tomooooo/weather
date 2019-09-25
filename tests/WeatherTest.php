<?php

namespace Ming\Weather\Tests;

use Exception;
use Mockery;
use GuzzleHttp\Client;
use Ming\Weather\Weather;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\ClientInterface;
use Ming\Weather\Exceptions\HttpException;
use PHPUnit\Framework\TestCase;
use Ming\Weather\Exceptions\InvalidArgumentException;
use Mockery\Matcher\AnyArgs;
use Mockery\Mock;

class WeatherTest extends TestCase
{

    public function testGetWeatherWithInvalidType()
    {
        $w = new Weather('mock-key');

        $this->expectException(InvalidArgumentException::class);

        $this->expectExceptionMessage('Invalid type value(base/all): foo');

        $w->getWeather('深圳', 'foo');

        $this->fail('Faild to assert getWeather throw exception with Invalid argument');
    }


    public function testGetWeatherWithInvalidFormat()
    {
        $w = new Weather('mock-key');

        $this->expectException(InvalidArgumentException::class);

        $this->expectExceptionMessage('Invalid response format: array');

        $w->getWeather('深圳', 'base', 'array');

        $this->fail('Faild to assert getWeather throw exception with Invalid argument');
    }

    public function testGetWeather()
    {
        // json
        $response = new Response(200, [], '{"success": true}');

        $client = \Mockery::mock(Client::class); // 创建模拟http client

        $client->allows()->get("https://restapi.amap.com/v3/weather/weatherInfo", [
            'query' => [
                'key'        => 'mock-key',
                'city'       => '深圳',
                'output'     => 'json',
                'extensions' => 'base',
            ]
        ])->andReturn($response);

        $w = Mockery::mock(Weather::class, ['mock-key'])->makePartial();
        $w->allows()->getHttpClient()->andReturn($client);

        $this->assertSame(['success' => true], $w->getWeather('深圳'));

        // xml
        $response = new Response(200, [], '<hello>content</hello>');

        $client = Mockery::mock(Client::class);

        $client->allows()->get("https://restapi.amap.com/v3/weather/weatherInfo", [
            'query' => [
                'key'        => 'mock-key',
                'city'       => '深圳',
                'output'     => 'xml',
                'extensions' => 'all',
            ]
        ])->andReturn($response);

        $w = Mockery::mock(Weather::class, ['mock-key'])->makePartial();
        $w->allows()->getHttpClient()->andReturn($client);

        $this->assertSame('<hello>content</hello>', $w->getWeather('深圳', 'all', 'xml'));
    }

    public function testGetWeatherWithGuzzleRuntimeException()
    {
        $client = Mockery::mock(Client::class);
        $client->allows()
            ->get(new AnyArgs())
            ->andThrow(new \Exception('request timeout'));

        $w = Mockery::mock(Weather::class, ['mock-key'])->makePartial();
        $w->allows()->getHttpClient()->andReturn($client);

        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('request timeout');

        $w->getWeather('深圳');
    }



    public function testGetHttpClient()
    {
        $w = new Weather('mock-key');

        $this->assertInstanceOf(ClientInterface::class, $w->getHttpClient());
    }

    public function testSetGuzzleOptions()
    {
        $w = new Weather('mock-key');

        $this->assertNull($w->getHttpClient()->getConfig('timeout'));

        $w->setGuzzleOptions(['timeout' => 5000]);

        $this->assertSame(5000, $w->getHttpClient()->getConfig('timeout'));
    }
}
