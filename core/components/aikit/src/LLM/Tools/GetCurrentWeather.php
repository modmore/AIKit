<?php

namespace modmore\AIKit\LLM\Tools;

use MODX\Revolution\modX;
use Throwable;

class GetCurrentWeather implements ToolInterface
{
    private array $config;
    private modX $modx;

    /**
     * @param modX $modx
     * @param array $config
     * @inheritDoc
     */
    public function __construct(modX $modx, array $config)
    {
        $this->config = $config;
        $this->modx = $modx;
    }

    /**
     * @inheritDoc
     */
    public function getToolName(): string
    {
        return 'get_current_weather';
    }

    /**
     * @inheritDoc
     */
    public function getToolDescription(): string
    {
        return 'Get the current weather in the provided location. Location must be provided as latitude and longitude, but don\'t ask users for that. Instead ask users for the location and then transform that to latitude/longitude. The weather variables that are available are: temperature, apparent temperature, humidity, wind speed, wind direction, cloud cover, precipitation, rain, snowfall.';
    }

    /**
     * @inheritDoc
     */
    public function getModelParameters(): array
    {
        return [
            'latitude' => [
                'type' => 'number',
                'required' => true,
            ],
            'longitude' => [
                'type' => 'number',
                'required' => true,
            ],
        ];
    }

    /**
     * @inheritDoc
     */
    public function getToolParameters(): array
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function runTool(array $arguments): string
    {
        $url = "https://api.open-meteo.com/v1/forecast?latitude={$arguments['latitude']}&longitude={$arguments['longitude']}&current=temperature_2m,relative_humidity_2m,apparent_temperature,is_day,precipitation,rain,showers,snowfall,cloud_cover,pressure_msl,surface_pressure,wind_speed_10m,wind_direction_10m,wind_gusts_10m&timezone=auto&forecast_days=1";

        $client = $this->modx->services->get(\Psr\Http\Client\ClientInterface::class);
        $requestFactory = $this->modx->services->get(\Psr\Http\Message\RequestFactoryInterface::class);

        try {
            $request = $requestFactory->createRequest('GET', $url);
            $response = $client->sendRequest($request);

            if ($response->getStatusCode() === 200) {
                $responseBody = $response->getBody()->getContents();
                $parsedResponse = json_decode($responseBody, true, 512, JSON_THROW_ON_ERROR);

                $output = [];
                foreach ($parsedResponse['current'] as $key => $value) {
                    $output[$key] = [
                        'value' => $value,
                        'unit' => $parsedResponse['current_units'][$key] ?? '',
                    ];
                }
                return json_encode($output, JSON_THROW_ON_ERROR);
            }
        } catch (Throwable $e) {
            return "Received an error looking up the weather: {$e->getMessage()}";
        }

        return "Failed looking up weather.";
    }
}
