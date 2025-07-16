<?php

namespace Slider23\PhpLlmToolbox\Tools\Examples;

use Slider23\PhpLlmToolbox\Tools\AbstractTool;
use Slider23\PhpLlmToolbox\Tools\ToolResult;

class WeatherTool extends AbstractTool
{
    public function getName(): string
    {
        return 'get_weather';
    }

    public function getDescription(): string
    {
        return 'Get current weather information for a given location';
    }

    public function getParametersSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'location' => [
                    'type' => 'string',
                    'description' => 'The city or location to get weather for'
                ],
                'units' => [
                    'type' => 'string',
                    'enum' => ['celsius', 'fahrenheit'],
                    'description' => 'Temperature units',
                    'default' => 'celsius'
                ]
            ],
            'required' => ['location']
        ];
    }

    public function execute(array $parameters): ToolResult
    {
        try {
            $this->validateParameters($parameters);
            
            $location = $this->getParameter($parameters, 'location');
            $units = $this->getParameter($parameters, 'units', 'celsius');

            // Mock weather data - in real implementation, you'd call a weather API
            $weatherData = [
                'location' => $location,
                'temperature' => $units === 'celsius' ? 22 : 72,
                'units' => $units,
                'condition' => 'Sunny',
                'humidity' => 65,
                'wind_speed' => 10,
                'description' => 'Clear sky with light breeze'
            ];

            return ToolResult::success($weatherData);
        } catch (\Exception $e) {
            return ToolResult::error($e->getMessage());
        }
    }
}