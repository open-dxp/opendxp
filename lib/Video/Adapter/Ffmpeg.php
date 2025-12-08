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

namespace OpenDxp\Video\Adapter;

use Exception;
use OpenDxp\Logger;
use OpenDxp\Tool\Console;
use OpenDxp\Video\Adapter;
use Symfony\Component\Process\Process;

/**
 * @internal
 */
class Ffmpeg extends Adapter
{
    public string $file;

    protected string $processId;

    protected array $arguments = [];

    protected array $videoFilter = [];

    protected ?float $inputSeeking = null;

    public function isAvailable(): bool
    {
        try {
            $ffmpeg = self::getFfmpegCli();
            $phpCli = Console::getPhpCli();
            if ($ffmpeg && $phpCli) {
                return true;
            }
        } catch (Exception $e) {
            Logger::warning((string) $e);
        }

        return false;
    }

    /**
     *
     * @throws Exception
     */
    public static function getFfmpegCli(): false|string
    {
        return \OpenDxp\Tool\Console::getExecutable('ffmpeg', true);
    }

    public function load(string $file, array $options = []): static
    {
        $this->file = $file;
        $this->setProcessId(uniqid());

        return $this;
    }

    /**
     *
     * @throws Exception
     */
    public function save(): bool
    {
        $success = false;

        if ($this->getDestinationFile()) {
            if (is_file($this->getConversionLogFile())) {
                $this->deleteConversionLogFile();
            }
            if (is_file($this->getDestinationFile())) {
                @unlink($this->getDestinationFile());
            }

            if (count($this->videoFilter) > 0) {
                $this->addArgument('-vf', implode(',', $this->videoFilter));
            }

            $command = $this->arguments;
            // add format specific arguments
            if ($this->getFormat() === 'mp4') {
                $command[] = '-strict';
                $command[] = 'experimental';
                $command[] = '-f';
                $command[] = 'mp4';
                $command[] = '-vcodec';
                $command[] = 'libx264';
                $command[] = '-acodec';
                $command[] = 'aac';
                $command[] = '-g';
                $command[] = '100';
                $command[] = '-pix_fmt';
                $command[] = 'yuv420p';
                $command[] = '-movflags';
                $command[] = 'faststart';
            } elseif ($this->getFormat() === 'webm') {
                // check for vp9 support
                $webmCodec = 'libvpx';
                $process = new Process([self::getFfmpegCli(), '-codecs']);
                $process->run();
                $codecs = $process->getOutput();
                if (stripos($codecs, 'vp9')) {
                    //$webmCodec = "libvpx-vp9"; // disabled until better support in ffmpeg and browsers
                }
                $command[] = '-strict';
                $command[] = 'experimental';
                $command[] = '-f';
                $command[] = 'webm';
                $command[] = '-vcodec';
                $command[] = $webmCodec;
                $command[] = '-acodec';
                $command[] = 'libvorbis';
                $command[] = '-ar';
                $command[] = '44000';
                $command[] = '-g';
                $command[] = '100';
            } elseif ($this->getFormat() === 'mpd') {
                $medias = $this->getMedias();
                $mediaKeys = array_keys($medias);
                $command = [];

                foreach ($mediaKeys as $mediaKey) {
                    $command[] = '-map';
                    $command[] = 'v:0';
                }
                $command[] = '-c:a';
                $command[] = 'libfdk_aac';
                $command[] = '-vcodec';
                $command[] = 'libx264';
                $counter = count($mediaKeys);

                for ($i = 0; $i < $counter; $i++) {
                    $bitrate = $mediaKeys[$i];
                    $command[] = '-b:v:' . $i;
                    $command[] = $bitrate;
                    $command[] = '-c:v:' . $i;
                    $command[] = 'libx264';
                    $command[] = '-c:v:' . $i;
                    $command[] = 'libx264';

                    if ($medias[$bitrate]['converter'] instanceof self) {
                        foreach ($medias[$bitrate]['converter']->arguments as $aKey => $argument) {
                            $argument = ($aKey % 2 === 0 ? $argument . ':' . $i : $argument);
                            $command[] = $argument;
                        }
                    }
                }
                $command[] = '-use_timeline';
                $command[] = '1';
                $command[] = '-use_template';
                $command[] = '1';
                $command[] = '-window_size';
                $command[] = '5';
                $command[] = '-adaptation_sets';
                $command[] = 'id=0,streams=v id=1,streams=a';
                $command[] = '-single_file';
                $command[] = '1';
                $command[] = '-f';
                $command[] = 'dash';
            } elseif ($this->getFormat() === 'mpg') {
                $command[] = '-c:v';
                $command[] = 'mpeg2video';
                $command[] = '-c:a';
                $command[] = 'mp2';
                $command[] = '-f';
                $command[] = 'vob';
            } else {
                throw new Exception('Unsupported video output format: ' . $this->getFormat());
            }
            // add some global arguments
            $command[] = '-threads';
            $command[] = '0';
            $command[] = str_replace('/', DIRECTORY_SEPARATOR, $this->getDestinationFile());
            array_unshift($command, '-i', realpath($this->file));
            // prepend seeking before input file to use input seeking method
            if ($this->inputSeeking !== null) {
                $sourceDuration = $this->getDuration() * 100;
                if ($this->inputSeeking >= $sourceDuration) {
                    $this->inputSeeking = 0;
                }
                array_unshift($command, '-ss', $this->inputSeeking);
            }
            array_unshift($command, self::getFfmpegCli());

            Console::addLowProcessPriority($command);
            $process = new Process($command);

            Logger::debug('Executing FFMPEG Command: ' . $process->getCommandLine());

            //symfony has a default timeout which is 60 sec. This is not enough for converting big video-files.
            $process->setTimeout(null);
            $process->start();

            $logHandle = fopen($this->getConversionLogFile(), 'a');
            fwrite($logHandle, 'Command: ' . $process->getCommandLine() . "\n\n\n");

            $process->wait(function ($type, $buffer) use ($logHandle): void {
                fwrite($logHandle, $buffer);
            });
            fclose($logHandle);

            if ($process->isSuccessful()) {
                // cleanup & status update
                $this->deleteConversionLogFile();
                $success = true;
            } elseif (file_exists($this->getConversionLogFile()) && filesize($this->getConversionLogFile())) {
                // create an error log file
                copy(
                    $this->getConversionLogFile(),
                    str_replace('.log', '.error.log', $this->getConversionLogFile())
                );
            }
        } else {
            throw new Exception('There is no destination file for video converter');
        }

        return $success;
    }

    public function saveImage(string $file, ?int $timeOffset = null): bool
    {
        $timeOffset = (string) ($timeOffset ?? 5);

        try {
            $cmd = [
                self::getFfmpegCli(),
                '-ss', $timeOffset, '-i', realpath($this->file),
                '-vcodec', 'png', '-vframes', '1', '-vf', 'scale=iw*sar:ih',
                str_replace('/', DIRECTORY_SEPARATOR, $file),
            ];
            Console::addLowProcessPriority($cmd);
            $process = new Process($cmd);
            $process->mustRun();

            return true;
        } catch (Exception $e) {
            Logger::error((string) $e);

            return false;
        }
    }

    /**
     *
     * @throws Exception
     */
    protected function getVideoInfo(): string
    {
        $tmpFile = OPENDXP_SYSTEM_TEMP_DIRECTORY . '/video-info-' . uniqid() . '.out';

        $cmd = [self::getFfmpegCli(), '-i', realpath($this->file)];
        Console::addLowProcessPriority($cmd);
        $process = new Process($cmd);
        $process->start();

        $tmpHandle = fopen($tmpFile, 'a');
        $process->wait(function ($type, $buffer) use ($tmpHandle): void {
            fwrite($tmpHandle, $buffer);
        });
        fclose($tmpHandle);

        $contents = file_get_contents($tmpFile);
        unlink($tmpFile);

        return $contents;
    }

    public function getDuration(): ?float
    {
        try {
            $output = $this->getVideoInfo();

            // get total video duration
            $result = preg_match('/Duration: (\d\d):(\d\d):(\d\d\.\d+),/', $output, $matches);

            if ($result) {
                // calculate duration in seconds
                $duration = ((int)$matches[1] * 3600) + ((int)$matches[2] * 60) + (float)$matches[3];

                return $duration;
            }

            throw new Exception(
                'Could not read duration with FFMPEG Adapter. File: ' . $this->file . '. Output: ' . $output
            );
        } catch (Exception $e) {
            Logger::error($e->getMessage());
        }

        return null;
    }

    public function getDimensions(): ?array
    {
        try {
            $output = $this->getVideoInfo();

            if (preg_match('/ (\d+x\d+)[, ]/', $output, $matches)) {
                $dimensionRaw = $matches[1];
                [$width, $height] = explode('x', $dimensionRaw);

                return ['width' => $width, 'height' => $height];
            }

            throw new Exception(
                'Could not read dimensions with FFMPEG Adapter. File: ' . $this->file . '. Output: ' . $output
            );
        } catch (Exception $e) {
            Logger::error($e->getMessage());
        }

        return null;
    }

    public function destroy(): void
    {
        if (file_exists($this->getConversionLogFile())) {
            Logger::debug("FFMPEG finished, last message was:\n" . file_get_contents($this->getConversionLogFile()));
            $this->deleteConversionLogFile();
        }
    }

    private function deleteConversionLogFile(): void
    {
        @unlink($this->getConversionLogFile());
    }

    public function setProcessId(string $processId): static
    {
        $this->processId = $processId;

        return $this;
    }

    public function getProcessId(): string
    {
        return $this->processId;
    }

    protected function getConversionLogFile(): string
    {
        return OPENDXP_LOG_DIRECTORY . '/ffmpeg-' . $this->getProcessId() . '-' . $this->getFormat() . '.log';
    }

    public function addArgument(string $key, string $value): void
    {
        $this->arguments[] = $key;
        $this->arguments[] = $value;
    }

    public function addFlag(string $flag): void
    {
        $this->arguments[] = $flag;
    }

    public function getArguments(): array
    {
        return $this->arguments;
    }

    #[\Override]
    public function setVideoBitrate(int $videoBitrate): static
    {
        $videoBitrate = (int) ceil($videoBitrate / 2) * 2;

        parent::setVideoBitrate($videoBitrate);

        if ($videoBitrate) {
            $this->addArgument('-vb', $videoBitrate . 'k');
        }

        return $this;
    }

    #[\Override]
    public function setAudioBitrate(int $audioBitrate): static
    {
        $audioBitrate = (int) ceil($audioBitrate / 2) * 2;

        parent::setAudioBitrate($audioBitrate);

        if ($audioBitrate) {
            $this->addArgument('-ab', $audioBitrate . 'k');
        }

        return $this;
    }

    public function resize(int $width, int $height): void
    {
        // ensure $width & $height are even (mp4 requires this)
        $width = ceil($width / 2) * 2;
        $height = ceil($height / 2) * 2;
        $this->addArgument('-s', $width.'x'.$height);
    }

    public function scaleByWidth(int $width): void
    {
        // ensure $width is even (mp4 requires this)
        $width = ceil($width / 2) * 2;
        $this->videoFilter[] = 'scale='.$width.':trunc(ow/a/2)*2';
    }

    public function scaleByHeight(int $height): void
    {
        // ensure $height is even (mp4 requires this)
        $height = ceil($height / 2) * 2;
        $this->videoFilter[] = 'scale=trunc(oh/(ih/iw)/2)*2:'.$height;
    }

    public function cut(?string $inputSeeking = null, ?string $targetDuration = null): void
    {
        if (!empty($inputSeeking)) {
            $result = preg_match("/^(\d\d):(\d\d):(\d\d\.?\d*)$/", $inputSeeking, $matches);

            if ($result) {
                $this->inputSeeking = ((int)$matches[1] * 3600) + ((int)$matches[2] * 60) + (float)$matches[3];
            }
        }
        if (!empty($targetDuration)) {
            $this->addArgument('-t', $targetDuration);
        }
    }

    public function setFramerate(int $fps): void
    {
        $this->videoFilter[] = 'fps='.$fps;
    }

    public function mute(): void
    {
        $this->addFlag('-an');
    }

    public function colorChannelMixer(?string $effect = null): void
    {
        if (!empty($effect)) {
            $this->videoFilter[] = 'colorchannelmixer='.$effect;
        }
    }
}
