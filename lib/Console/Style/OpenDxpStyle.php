<?php

declare(strict_types=1);

/**
 * OpenDXP
 *
 * This source file is licensed under the GNU General Public License version 3 (GPLv3).
 *
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (https://pimcore.com)
 * @copyright  Modification Copyright (c) OpenDXP (https://www.opendxp.ch)
 * @license    https://www.gnu.org/licenses/gpl-3.0.html  GNU General Public License version 3 (GPLv3)
 */

namespace OpenDxp\Console\Style;

use Symfony\Component\Console\Helper\Helper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * @internal
 */
final class OpenDxpStyle extends SymfonyStyle
{
    public function __construct(private readonly InputInterface $input, private readonly OutputInterface $output)
    {
        parent::__construct($this->input, $this->output);
    }

    public function getInput(): InputInterface
    {
        return $this->input;
    }

    public function getOutput(): OutputInterface
    {
        return $this->output;
    }

    /**
     * Prints an underlined title without prepending block and/or formatting output
     *
     */
    public function simpleSection(string $message, string $underlineChar = '-', ?string $style = null): void
    {
        $underline = str_repeat($underlineChar, Helper::width(Helper::removeDecoration($this->getFormatter(), $message)));

        if (null !== $style) {
            $format = '<%s>%s</>';
            $message = sprintf($format, $style, $message);
            $underline = sprintf($format, $style, $underline);
        }

        $this->writeln([
            '',
            $message,
            $underline,
            '',
        ]);
    }
}
