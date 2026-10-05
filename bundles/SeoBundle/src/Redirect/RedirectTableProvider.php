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
 * @copyright  Copyright (c) OpenDXP (https://www.opendxp.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0.html  GNU General Public License version 3 (GPLv3)
 */

namespace OpenDxp\Bundle\SeoBundle\Redirect;

use Doctrine\DBAL\Connection;
use OpenDxp\Bundle\SeoBundle\OpenDxpSeoBundle;
use OpenDxp\Cache;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Lock\LockFactory;
use Symfony\Contracts\Service\ResetInterface;
use Throwable;

/**
 * Hands out the redirect table of the current request.
 *
 * The table is compiled into a PHP file in the cache directory, so OPcache keeps it in shared memory and a request
 * reads it without unserializing anything. The shared cache holds the revision of the table. Saving or deleting a
 * redirect clears the tag "redirect", which removes the revision, and the next request compiles a new table. Every
 * server of a cluster compiles its own file for the revision it finds.
 *
 * @internal
 */
final class RedirectTableProvider implements ResetInterface
{
    private const string REVISION_KEY = 'seo_redirect_table_revision';

    private const array REVISION_TAGS = ['redirect'];

    /**
     * A table that could not be compiled is retried after this many seconds, for example once the migrations ran.
     */
    private const int FAILED_REVISION_LIFETIME = 60;

    private ?RedirectTable $table = null;

    public function __construct(
        private readonly Connection $db,
        private readonly LockFactory $lockFactory,
        private readonly LoggerInterface $logger,
        #[Autowire('%kernel.cache_dir%/opendxp_seo')]
        private readonly string $directory,
    ) {
    }

    public function get(): RedirectTable
    {
        return $this->table ??= $this->load();
    }

    public function reset(): void
    {
        $this->table = null;
    }

    private function load(): RedirectTable
    {
        if (!Cache::isEnabled()) {
            return $this->compile() ?? RedirectTable::notInstalled();
        }

        if (($table = $this->fromFile(Cache::load(self::REVISION_KEY))) instanceof RedirectTable) {
            return $table;
        }

        $lock = $this->lockFactory->createLock(self::class);
        $lock->acquire(true);

        try {
            $revision = Cache::load(self::REVISION_KEY);
            if (($table = $this->fromFile($revision)) instanceof RedirectTable) {
                return $table;
            }

            $table = $this->compile();
            $revision = is_string($revision) ? $revision : bin2hex(random_bytes(8));
            $this->writeFile($revision, $table ?? RedirectTable::notInstalled());
            Cache::save($revision, self::REVISION_KEY, self::REVISION_TAGS, $table instanceof RedirectTable ? null : self::FAILED_REVISION_LIFETIME, 998, true);

            return $table ?? RedirectTable::notInstalled();
        } finally {
            $lock->release();
        }
    }

    private function fromFile(mixed $revision): ?RedirectTable
    {
        if (!is_string($revision) || !is_file($file = $this->file($revision))) {
            return null;
        }

        return RedirectTable::fromArray(require $file);
    }

    /**
     * @return RedirectTable|null null when the redirects could not be read
     */
    private function compile(): ?RedirectTable
    {
        try {
            if (!OpenDxpSeoBundle::isInstalled()) {
                return RedirectTable::notInstalled();
            }

            return RedirectTable::fromRows($this->db->iterateAssociative('SELECT * FROM redirects WHERE active = 1'));
        } catch (Throwable $exception) {
            $this->logger->error('Could not compile the redirect table: {message}', ['message' => $exception->getMessage(), 'exception' => $exception]);

            return null;
        }
    }

    private function writeFile(string $revision, RedirectTable $table): void
    {
        try {
            $filesystem = new Filesystem();
            $filesystem->dumpFile($this->file($revision), '<?php return ' . var_export($table->toArray(), true) . ';');

            // OPcache skips files younger than opcache.file_update_protection. A revision never changes its file, so
            // an older modification time is safe and lets the very next request read the table from shared memory.
            touch($this->file($revision), time() - 60);

            foreach (glob($this->directory . '/redirects-*.php') ?: [] as $file) {
                if ($file !== $this->file($revision)) {
                    $filesystem->remove($file);
                }
            }
        } catch (Throwable $exception) {
            $this->logger->error('Could not write the redirect table: {message}', ['message' => $exception->getMessage(), 'exception' => $exception]);
        }
    }

    private function file(string $revision): string
    {
        return sprintf('%s/redirects-%s.php', $this->directory, preg_replace('/[^a-z0-9]/i', '', $revision));
    }
}
