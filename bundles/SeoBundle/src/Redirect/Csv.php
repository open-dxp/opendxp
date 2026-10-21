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
 * @copyright  Modification Copyright (c) OpenDXP (https://www.opendxp.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0.html  GNU General Public License version 3 (GPLv3)
 */

namespace OpenDxp\Bundle\SeoBundle\Redirect;

use DateTime;
use InvalidArgumentException;
use League\Csv\Bom;
use League\Csv\Reader;
use League\Csv\Statement;
use OpenDxp\Bundle\SeoBundle\Model\Redirect;
use OpenDxp\DateFormat;
use OpenDxp\Model\Document;
use OpenDxp\Tool\Admin;
use OpenDxp\Tool\ArrayNormalizer;
use OpenDxp\Tool\Text;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Throwable;

/**
 * @internal
 */
class Csv
{
    /**
     * @var string[]
     */
    private array $columns = [
        'id',
        'type',
        'source',
        'sourceSite',
        'target',
        'targetSite',
        'statusCode',
        'priority',
        'regex',
        'passThroughParameters',
        'active',
        'expiry',
    ];

    /**
     * Older exports lack these columns. An import without them takes the defaults.
     */
    private const array OPTIONAL_COLUMNS = [
        'validFrom' => null,
        'passThroughPath' => false,
        'protected' => false,
    ];

    private ?ArrayNormalizer $importNormalizer = null;

    private ?OptionsResolver $importResolver = null;

    public function __construct(private readonly RedirectValidator $validator)
    {
    }

    /**
     * @return list<string>
     */
    public function getExportColumns(): array
    {
        return [...$this->columns, ...array_keys(self::OPTIONAL_COLUMNS)];
    }

    /**
     * @return list<mixed>
     */
    public function getExportValues(Redirect $redirect): array
    {
        $target = $redirect->getTarget();

        if (is_numeric($redirect->getTarget())) {
            $document = Document::getById((int)$redirect->getTarget());

            if ($document) {
                $target = $document->getRealFullPath();
            }
        }

        $expiry = null;
        if ($redirect->getExpiry()) {
            $expiry = (new DateTime('@' . $redirect->getExpiry()))->format(DateFormat::ISO_8601);
        }

        $validFrom = null;
        if ($redirect->getValidFrom()) {
            $validFrom = (new DateTime('@' . $redirect->getValidFrom()))->format(DateFormat::ISO_8601);
        }

        return [
            $redirect->getId(),
            $redirect->getType(),
            $redirect->getSource(),
            $redirect->getSourceSite(),
            $target,
            $redirect->getTargetSite(),
            $redirect->getStatusCode(),
            $redirect->getPriority(),
            $redirect->getRegex(),
            $redirect->getPassThroughParameters(),
            $redirect->getActive(),
            $expiry,
            $validFrom,
            $redirect->getPassThroughPath(),
            $redirect->isProtected(),
        ];
    }

    /**
     * @param bool $mayManageProtected whether the importing user holds the permission redirects_protected
     *
     * @throws \League\Csv\Exception
     */
    public function import(string $filename, bool $mayManageProtected = true): array
    {
        if (!file_exists($filename) || !is_readable($filename)) {
            throw new InvalidArgumentException(sprintf('`%s`: failed to open stream: No such file or directory', $filename));
        }

        // reading the whole content and converting it to UTF-8 I didn't get the stream filter to work properly
        // TODO check if this can be done without loading the whole file into memory and re-try using a stream filter if necessary
        $content = file_get_contents($filename);
        $content = Text::convertToUTF8($content);

        $dialect = Admin::determineCsvDialect($filename);

        $reader = Reader::fromString($content);
        $reader->setOutputBOM(Bom::Utf8);
        $reader->setDelimiter($dialect->delimiter);
        $reader->setHeaderOffset(0);

        $stmt = new Statement();
        $result = $stmt->process($reader);

        $stats = [
            'total' => $result->count(),
            'imported' => 0,
            'created' => 0,
            'updated' => 0,
            'errored' => 0,
        ];

        $errors = [];
        foreach ($result as $line => $record) {
            try {
                $data = $this->preprocessImportData($record);
                $messages = $this->processImportData($data, $stats, $mayManageProtected);
            } catch (Throwable $e) {
                $messages = [$e->getMessage()];
            }

            if ($messages === []) {
                $stats['imported']++;
            } else {
                $stats['errored']++;
                $errors[$line] = $messages;
            }
        }

        if (count($errors) > 0) {
            $stats['errors'] = $errors;
        }

        return $stats;
    }

    private function preprocessImportData(array $record): array
    {
        // normalize data to types (string, int, ...) or null
        $data = $this->getImportNormalizer()->normalize($record);

        // validate data
        $data = $this->getImportResolver()->resolve($data);

        return $data;
    }

    /**
     * Returns the translation keys of what keeps the redirect from being saved.
     *
     * @return list<string>
     */
    private function processImportData(array $data, array &$stats, bool $mayManageProtected): array
    {
        $redirect = $data['id'] ? Redirect::getById($data['id']) : null;

        if (!$mayManageProtected && ($redirect?->isProtected() || $data['protected'])) {
            throw new InvalidArgumentException(
                'Only users with the permission redirects_protected import a protected redirect.',
            );
        }

        // ID is already set or will be generated
        unset($data['id']);

        $isNew = !$redirect instanceof Redirect;
        $redirect ??= new Redirect();
        $redirect->setValues($data);

        $result = $this->validator->validate($redirect, $mayManageProtected);
        if (!$result->isValid()) {
            return array_map(static fn (RedirectValidationError $error): string => $error->message, $result->errors);
        }

        $redirect->save();
        $stats[$isNew ? 'created' : 'updated']++;

        return [];
    }

    private function getImportNormalizer(): ArrayNormalizer
    {
        if ($this->importNormalizer instanceof \OpenDxp\Tool\ArrayNormalizer) {
            return $this->importNormalizer;
        }

        $normalizer = new ArrayNormalizer();

        $normalizer->addNormalizer(['id', 'sourceSite', 'targetSite', 'statusCode', 'priority'], function ($value) {
            if (empty($value)) {
                return null;
            }

            return (int)$value;
        });

        $normalizer->addNormalizer(['type', 'source'], function ($value) {
            if (empty($value)) {
                return null;
            }

            return (string)$value;
        });

        $normalizer->addNormalizer(['target'], function ($value) {
            if (empty($value)) {
                return null;
            }
            if (is_numeric($value)) {
                return (int)$value;
            }

            if (is_string($value) && $target = Document::getByPath($value)) {
                return (int)$target->getId();
            }

            return (string)$value;
        });

        $flags = ['regex', 'passThroughParameters', 'active', 'passThroughPath', 'protected'];
        $normalizer->addNormalizer($flags, function ($value) {
            if (empty($value)) {
                return false;
            }

            return (bool)$value;
        });

        $normalizer->addNormalizer(['expiry', 'validFrom'], function ($value) {
            if (empty($value)) {
                return null;
            }

            return strtotime($value);
        });

        $this->importNormalizer = $normalizer;

        return $this->importNormalizer;
    }

    private function getImportResolver(): OptionsResolver
    {
        if ($this->importResolver instanceof \Symfony\Component\OptionsResolver\OptionsResolver) {
            return $this->importResolver;
        }

        $resolver = new OptionsResolver();
        $resolver->setRequired($this->columns);

        $resolver->setAllowedTypes('id', ['int', 'null']);

        $resolver->setAllowedTypes('type', ['string']);
        $resolver->setAllowedValues('type', Redirect::TYPES);

        $resolver->setAllowedTypes('source', ['string', 'null']);
        $resolver->setAllowedTypes('sourceSite', ['int', 'null']);
        $resolver->setAllowedTypes('target', ['string', 'int', 'null']);
        $resolver->setAllowedTypes('targetSite', ['int', 'null']);

        $resolver->setAllowedTypes('statusCode', ['int']);
        $resolver->setAllowedValues('statusCode', array_map(fn ($code) => (int)$code, array_keys(Redirect::getStatusCodes())));

        $resolver->setAllowedTypes('priority', ['int']);
        $resolver->setAllowedValues('priority', [...range(1, 10), 99]);

        $resolver->setAllowedTypes('regex', ['bool']);
        $resolver->setAllowedTypes('passThroughParameters', ['bool']);
        $resolver->setAllowedTypes('active', ['bool']);
        $resolver->setAllowedTypes('expiry', ['int', 'null']);

        $resolver->setDefaults(self::OPTIONAL_COLUMNS);
        $resolver->setAllowedTypes('validFrom', ['int', 'null']);
        $resolver->setAllowedTypes('passThroughPath', ['bool']);
        $resolver->setAllowedTypes('protected', ['bool']);

        $this->importResolver = $resolver;

        return $this->importResolver;
    }
}
