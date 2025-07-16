<?php

namespace Slider23\PhpLlmToolbox\Tools\Examples;

use Slider23\PhpLlmToolbox\Tools\AbstractTool;
use Slider23\PhpLlmToolbox\Tools\ToolResult;

class TimeTool extends AbstractTool
{
    public function getName(): string
    {
        return 'get_current_time';
    }

    public function getDescription(): string
    {
        return 'Get current time and date information';
    }

    public function getParametersSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'timezone' => [
                    'type' => 'string',
                    'description' => 'Timezone identifier (e.g., UTC, America/New_York)',
                    'default' => 'UTC'
                ],
                'format' => [
                    'type' => 'string',
                    'description' => 'Date format (Y-m-d H:i:s by default)',
                    'default' => 'Y-m-d H:i:s'
                ]
            ],
            'required' => []
        ];
    }

    public function execute(array $parameters): ToolResult
    {
        try {
            $timezone = $this->getParameter($parameters, 'timezone', 'UTC');
            $format = $this->getParameter($parameters, 'format', 'Y-m-d H:i:s');

            $dateTime = new \DateTime('now', new \DateTimeZone($timezone));
            
            return ToolResult::success([
                'current_time' => $dateTime->format($format),
                'timezone' => $timezone,
                'timestamp' => $dateTime->getTimestamp(),
                'iso_format' => $dateTime->format(\DateTime::ISO8601)
            ]);
        } catch (\Exception $e) {
            return ToolResult::error($e->getMessage());
        }
    }
}