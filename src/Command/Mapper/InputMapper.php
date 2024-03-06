<?php declare(strict_types=1);

namespace Movary\Command\Mapper;

use RuntimeException;
use Symfony\Component\Console\Input\InputInterface;

class InputMapper
{
    public function mapOptionToIds(InputInterface $input, string $optionName) : ?array
    {
        $optionValue = $input->getOption($optionName);

        if ($optionValue === null) {
            return null;
        }

        $ids = [];

        foreach (explode(',', $optionValue) as $idValue) {
            if (ctype_digit($idValue) === false) {
                throw new RuntimeException('Option must be a comma separated string of only numbers: ' . $optionName);
            }

            $ids[] = (int)$idValue;
        }

        return $ids;
    }

    public function mapOptionToInteger(InputInterface $input, string $optionName) : ?int
    {
        $optionValue = $input->getOption($optionName);

        if ($optionValue === null) {
            return null;
        }

        if (ctype_digit($optionValue) === false) {
            throw new RuntimeException('Option must be a number: ' . $optionName);
        }

        return (int)$optionValue;
    }
}
