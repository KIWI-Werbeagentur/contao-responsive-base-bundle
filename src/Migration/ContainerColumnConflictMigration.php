<?php

declare(strict_types=1);

namespace Kiwi\Contao\ResponsiveBaseBundle\Migration;

use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Kiwi\Contao\ResponsiveBaseBundle\Service\ResponsiveFrontendService;

/**
 * Resolves records that carry BOTH a container size and column settings.
 *
 * THE CONFLICT
 * ------------
 * Since 1.1 an element in container mode no longer emits its column classes: see
 * {@see \Kiwi\Contao\ResponsiveBaseBundle\Service\ResponsiveFrontendService::suppressesColumns()}.
 * Up to 1.0.17 both class sets were emitted together, so records holding both silently lose
 * their column classes on upgrade, which changes the rendered layout.
 *
 * WHY IT LIVES IN THIS BUNDLE
 * ---------------------------
 * The rule that strands these records is this bundle's, and so is everything the predicate
 * reads: the responsiveContainer field (tl_content and tl_form_field), the container-capable
 * palette list, and the fieldset template. contao-bootstrap only contributes one *extra*
 * suppression condition (responsiveOverwriteRowCols). Shipping the migration there would have
 * left every project that pairs responsive-base with a different framework bundle - bootstrap
 * is a "suggest", not a "require" - with the regression and no fix.
 *
 * responsiveOverwriteRowCols is therefore consulted defensively, via columnExists():
 *   - with contao-bootstrap installed, its BootstrapFrontendService::getColClasses() only ever
 *     applied columns when that flag was set, so records without it were already rendering
 *     without columns before 1.1 and must not be touched;
 *   - without it, no such gate exists, every container+columns record really did render both,
 *     and the condition correctly drops out of the predicate.
 *
 * WHAT IT DOES
 * ------------
 * Switches the affected records to "Spalten-Element" (responsiveContainer = '0'), which makes
 * the stored column settings render again AND become visible in the backend, so the intent is
 * editable rather than latent. responsiveCols/responsiveOffsets are left untouched.
 *
 * This is NOT layout-neutral: those records lose their container class. That is deliberate -
 * the container was being used to fake margins/centering on top of a column, and only one of
 * the two can win. Review the affected pages afterwards.
 *
 * SELF-TERMINATION (no marker table, no date cutoff)
 * --------------------------------------------------
 * The predicate tests responsiveContainer, which is exactly the field the migration rewrites,
 * so after a successful run nothing matches and shouldRun() is false. No ledger is needed and
 * no release date has to be guessed - which matters because installs upgrade at different
 * times, so any fixed cutoff would misclassify late upgraders.
 *
 * If a record acquires the combination again later (configuring columns as "Spalten-Element"
 * and switching to a container afterwards, or duplicating such a record), this migration will
 * legitimately offer itself again: under 1.1 such a record is always in a state where stored
 * settings do not render.
 */
final class ContainerColumnConflictMigration extends AbstractMigration
{
    /**
     * table => [container field, column fields, extra conditions]
     *
     * No type list: which types are container-capable is not restated here at all. The SQL only
     * narrows to "has a container value and some column value", and every candidate is then put
     * to ResponsiveFrontendService::suppressesColumns() - the very rule that strands the records.
     * An earlier version derived the types from
     * $GLOBALS['responsive'][$table]['includePalettes']['container'], which agreed with the rule
     * only while LoadDataContainerListener kept that config and the responsiveContainer palette
     * membership aligned; a type gaining the field by any other route would have made the
     * migration convert records the frontend never stranded, or miss ones it did.
     */
    private const TARGETS = [
        'tl_content' => [
            'container' => 'responsiveContainer',
            'columns' => ['responsiveCols', 'responsiveOffsets'],
            'requireFlag' => 'responsiveOverwriteRowCols',
        ],
        'tl_form_field' => [
            'container' => 'responsiveContainer',
            'columns' => ['responsiveCols', 'responsiveOffsets'],
            'requireFlag' => null,
        ],
    ];

    private const COLUMN_ELEMENT = '0';

    public function __construct(
        private readonly Connection $connection,
        private readonly ResponsiveFrontendService $responsiveFrontendService,
    ) {
    }

    public function getName(): string
    {
        return 'Resolve element groups that carry both a container size and column settings';
    }

    public function shouldRun(): bool
    {
        foreach (array_keys(self::TARGETS) as $table) {
            if ($this->findAffectedIds($table)) {
                return true;
            }
        }

        return false;
    }

    public function run(): MigrationResult
    {
        $messages = [];
        $total = 0;

        foreach (array_keys(self::TARGETS) as $table) {
            $ids = $this->findAffectedIds($table);

            if (!$ids) {
                continue;
            }

            $this->connection->executeStatement(
                \sprintf(
                    'UPDATE %s SET %s = :columnElement WHERE id IN (:ids)',
                    $this->connection->quoteIdentifier($table),
                    $this->connection->quoteIdentifier(self::TARGETS[$table]['container']),
                ),
                ['columnElement' => self::COLUMN_ELEMENT, 'ids' => $ids],
                ['ids' => ArrayParameterType::INTEGER],
            );

            $total += \count($ids);
            $messages[] = \sprintf('%s: %d (%s)', $table, \count($ids), implode(', ', $ids));
        }

        return $this->createResult(
            true,
            \sprintf(
                'Switched %d record(s) to "Spalten-Element" so their stored column settings render '
                .'and become editable again - %s. These records no longer emit their container '
                .'class; review the affected pages.',
                $total,
                implode(' | ', $messages),
            ),
        );
    }

    /**
     * @return list<int>
     */
    private function findAffectedIds(string $table): array
    {
        $config = self::TARGETS[$table];

        if (!$this->connection->createSchemaManager()->tablesExist([$table])) {
            return [];
        }

        $columns = array_values(array_filter(
            $config['columns'],
            fn (string $c): bool => $this->columnExists($table, $c),
        ));

        if (!$columns || !$this->columnExists($table, $config['container'])) {
            return [];
        }

        $quote = $this->connection->quoteIdentifier(...);

        $where = [
            \sprintf("COALESCE(%s, '') NOT IN ('', '0')", $quote($config['container'])),
            '('.implode(' OR ', array_map(
                static fn (string $c): string => \sprintf("COALESCE(%s, '') NOT IN ('', 'a:0:{}')", $quote($c)),
                $columns,
            )).')',
        ];

        // Absent when contao-bootstrap is not installed - and then correctly omitted, because
        // without it nothing gated column output on this flag in the first place.
        if ($config['requireFlag'] && $this->columnExists($table, $config['requireFlag'])) {
            $where[] = \sprintf("COALESCE(%s, '') NOT IN ('', '0')", $quote($config['requireFlag']));
        }

        $rows = $this->connection->fetchAllAssociative(
            \sprintf(
                'SELECT id, %s, %s, %s FROM %s WHERE %s',
                $quote('type'),
                $quote($config['container']),
                implode(', ', array_map($quote, $columns)),
                $quote($table),
                implode(' AND ', $where),
            ),
        );

        $ids = [];

        foreach ($rows as $row) {
            // The SQL above only narrows; the decision is the frontend's own rule, asked directly
            // so the two can never disagree. skipPaletteCheck stays false - the palette gate is
            // exactly what determines container-capability here.
            if (!$this->responsiveFrontendService->suppressesColumns($row, $table)) {
                continue;
            }

            foreach ($columns as $column) {
                if ($this->holdsSelection($row[$column] ?? null)) {
                    $ids[] = (int) $row['id'];
                    break;
                }
            }
        }

        return $ids;
    }

    /**
     * The column fields hold serialized per-breakpoint arrays, so "not the empty string" is not
     * sufficient: a:1:{s:2:"xs";s:0:"";} is a stored-but-empty selection and must not count as
     * configured, whereas "none" is a deliberate choice and must.
     */
    private function holdsSelection(mixed $value): bool
    {
        if (!\is_string($value) || '' === $value) {
            return false;
        }

        $decoded = @unserialize($value, ['allowed_classes' => false]);

        if (false === $decoded && 'b:0;' !== $value) {
            return '' !== trim($value);
        }

        if (!\is_array($decoded)) {
            return \is_scalar($decoded) && '' !== trim((string) $decoded);
        }

        foreach ($decoded as $entry) {
            if (\is_array($entry)) {
                if ($this->holdsSelection(serialize($entry))) {
                    return true;
                }

                continue;
            }

            if (null !== $entry && '' !== trim((string) $entry)) {
                return true;
            }
        }

        return false;
    }

    private function columnExists(string $table, string $column): bool
    {
        static $cache = [];

        $cache[$table] ??= array_map(
            static fn ($c) => $c->getName(),
            $this->connection->createSchemaManager()->listTableColumns($table),
        );

        return \in_array($column, $cache[$table], true);
    }
}
