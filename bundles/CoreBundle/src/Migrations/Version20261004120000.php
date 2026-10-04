<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\CoreBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261004120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove the dependencies whose source or target no longer exists';
    }

    public function up(Schema $schema): void
    {
        foreach (['source', 'target'] as $side) {
            $this->addSql(sprintf(
                "DELETE d FROM dependencies d
                    LEFT JOIN objects o ON d.%1\$stype = 'object' AND o.id = d.%1\$sid
                    LEFT JOIN documents doc ON d.%1\$stype = 'document' AND doc.id = d.%1\$sid
                    LEFT JOIN assets a ON d.%1\$stype = 'asset' AND a.id = d.%1\$sid
                    WHERE o.id IS NULL AND doc.id IS NULL AND a.id IS NULL",
                $side
            ));
        }
    }

    public function down(Schema $schema): void
    {
    }
}
