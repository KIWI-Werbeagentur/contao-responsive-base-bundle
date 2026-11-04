<?php

declare(strict_types=1);

namespace Kiwi\Contao\ResponsiveBaseBundle\Migration;

use Contao\Controller;
use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Kiwi\Contao\ResponsiveBaseBundle\Service\ResponsiveFrontendService;
use Psr\Log\LoggerInterface;

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
 * palette list, and the fieldset template. contao-bootstrap only contributes *extra* conditions
 * on column output. Shipping the migration there would have left every project that pairs
 * responsive-base with a different framework bundle - bootstrap is a "suggest", not a
 * "require" - with the regression and no fix.
 *
 * Those extra conditions are therefore not restated here: whether a record's columns rendered
 * before 1.1 is asked of the frontend service's getColClasses()/getOffsetClasses(), which a
 * framework bundle overrides. With contao-bootstrap that applies responsiveOverwriteRowCols -
 * but only where the parent table offers row-cols (tl_article, tl_content, tl_module). Under any
 * other parent, e.g. tl_static_article or tl_news, the flag is not consulted and the columns
 * always rendered, flag or not. Without contao-bootstrap the base methods render whatever is
 * stored.
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
 * UPGRADE ONLY (no marker table, no date cutoff)
 * ----------------------------------------------
 * It runs only on the upgrade from 1.0 ({@see DetectsUpgradeFrom10Trait}), which is detected
 * from the schema rather than a release date - installs upgrade at different times, so any
 * fixed cutoff would misclassify late upgraders. Within that run the predicate tests
 * responsiveContainer, exactly the field the migration rewrites, so after a successful pass
 * nothing matches any more.
 *
 * Under 1.1 the combination is a normal, deliberate state: an editor who configures columns as
 * "Spalten-Element" and then switches to a container keeps the now hidden column values in the
 * record (Contao does not clear fields of an inactive subpalette), and the container is meant
 * to win. Without the gate the next contao:migrate would switch such records back.
 */
final class ContainerColumnConflictMigration extends AbstractMigration
{
    use DetectsUpgradeFrom10Trait;

    /**
     * table => [container field, column fields, context fields]
     *
     * Context fields are selected when present so the frontend service's column methods see what
     * they decide on (bootstrap: ptable and responsiveOverwriteRowCols); they never narrow the SQL.
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
            'context' => ['ptable', 'responsiveOverwriteRowCols'],
        ],
        'tl_form_field' => [
            'container' => 'responsiveContainer',
            'columns' => ['responsiveCols', 'responsiveOffsets'],
            'context' => [],
        ],
    ];

    private const COLUMN_ELEMENT = '0';

    public function __construct(
        private readonly Connection $connection,
        private readonly ResponsiveFrontendService $responsiveFrontendService,
        private readonly ?LoggerInterface $logger = null,
    ) {
    }

    public function getName(): string
    {
        return 'Resolve element groups that carry both a container size and column settings';
    }

    public function shouldRun(): bool
    {
        if (!$this->isUpgradeFrom10($this->connection)) {
            return false;
        }

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

            // The rewritten field is the one the predicate tests, so after this run the affected
            // records can no longer be found again - persist the list for the review the result
            // message asks for. Logged as an error so it reaches the log file even when the
            // migration runs unmonitored.
            $this->logger?->error(
                'Switched {count} {table} record(s) to "Spalten-Element" so their stored column '
                .'settings render again; they no longer emit their container class. Review the '
                .'affected pages: {ids}.',
                ['count' => \count($ids), 'table' => $table, 'ids' => implode(', ', $ids)],
            );
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

        // responsiveOverwriteRowCols is absent without contao-bootstrap, ptable on tables that
        // have no polymorphic parent
        $context = array_values(array_filter(
            $config['context'],
            fn (string $c): bool => $this->columnExists($table, $c),
        ));

        $rows = $this->connection->fetchAllAssociative(
            \sprintf(
                'SELECT id, %s FROM %s WHERE %s',
                implode(', ', array_map($quote, ['type', $config['container'], ...$columns, ...$context])),
                $quote($table),
                implode(' AND ', $where),
            ),
        );

        $ids = [];

        Controller::loadDataContainer($table);

        foreach ($rows as $row) {
            // A type without a palette - left over from an uninstalled extension, or missing
            // altogether - is not rendered by the frontend at all, so its columns are not
            // stranded. Skip it here: the palette gate would reach the same answer, but log it
            // as a misconfigured template call, which this is not.
            if (!isset($GLOBALS['TL_DCA'][$table]['palettes'][(string) $row['type']])) {
                continue;
            }

            // The SQL above only narrows; the decision is the frontend's own rule, asked directly
            // so the two can never disagree. skipPaletteCheck stays false - the palette gate is
            // exactly what determines container-capability here.
            if (!$this->responsiveFrontendService->suppressesColumns($row, $table)) {
                continue;
            }

            if (\array_key_exists('ptable', $row)) {
                // Contao treats an empty ptable as tl_article; the DCA loader rejects an empty name
                $row['ptable'] = '' === (string) $row['ptable'] ? 'tl_article' : (string) $row['ptable'];

                // A parent table that no longer exists (uninstalled extension) renders nothing
                if (!$this->tableExists($row['ptable'])) {
                    continue;
                }
            }

            if ($this->rendersColumns($row)) {
                $ids[] = (int) $row['id'];
            }
        }

        return $ids;
    }

    /**
     * Whether the record's column settings produce classes when the container does not suppress
     * them - i.e. what they rendered up to 1.0.17, where both class sets were emitted together.
     * Asked of the frontend service so a framework bundle's own conditions apply (bootstrap's
     * responsiveOverwriteRowCols and its ptable exception); the base methods ignore $row.
     * Empty classes (values without a mapping) do not count: they never rendered anything.
     *
     * @param array<string, mixed> $row
     */
    private function rendersColumns(array $row): bool
    {
        $classes = [
            ...$this->responsiveFrontendService->getColClasses($row['responsiveCols'] ?? null, $row),
            ...$this->responsiveFrontendService->getOffsetClasses($row['responsiveOffsets'] ?? null, $row),
        ];

        return [] !== array_filter($classes, static fn ($c): bool => '' !== trim((string) $c));
    }

    private function tableExists(string $table): bool
    {
        static $cache = [];

        return $cache[$table] ??= $this->connection->createSchemaManager()->tablesExist([$table]);
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
