<?php declare(strict_types=1);

namespace Movary\Command\Mapper;

use Symfony\Component\Console\Input\InputInterface;

class InputMapper
{
    public function mapOptionToIds(InputInterface $input, string $optionName) : ?array
    {
        $optionValue = $input->getOption($optionName);

        if ($optionValue === null) {
            return null;
        }

        return array_map('intval', explode(',', $optionValue));
    }

    public function mapOptionToInteger(InputInterface $input, string $optionName) : ?int
    {
        $optionValue = $input->getOption($optionName);

        if ($optionValue === null) {
            return null;
        }

        if (ctype_digit($optionValue) === false) {
            throw new \RuntimeException('Invalid option value, must be a number: ' . $optionName);
        }

        return (int)$optionValue;
    }
}
