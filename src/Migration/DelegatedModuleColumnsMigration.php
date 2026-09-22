<?php

declare(strict_types=1);

namespace Kiwi\Contao\ResponsiveBaseBundle\Migration;

use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Contao\StringUtil;
use Doctrine\DBAL\Connection;

/**
 * Shared resolution for migrations that repair include elements rendering a *delegated*
 * module.
 *
 * Column classes for a rendered module come from whichever record is considered its
 * outermost includer. That resolution used to honour only a content element inserting the
 * rendered module itself; a module reached through a wrapper - a root-page- or
 * language-dependent module, which delegates to a different module per root page - fell
 * back to its own tl_module row. Now the outermost includer wins in every case, which is
 * the intended behaviour: whoever places an element has the first call on how it is styled.
 *
 * For existing content that moves the authoritative record. Everything the inner module
 * said about its own width is now ignored and the including element speaks instead - an
 * element whose responsive settings could not previously have any effect on these modules,
 * so whatever they hold was never a decision anyone made or saw. Restoring the previous
 * rendering therefore means carrying the inner module's settings up to the element.
 *
 * Which module an element rendered is normally determinable: the element's page resolves to
 * a root page, and the wrapper's per-root-page map names the module for it. Where the
 * context has no single root page - an element in news, events, a static article, or any
 * other parent this cannot walk - every delegated module is read instead. If they all agree
 * there is still exactly one answer and it is used; only genuine disagreement is left alone
 * and reported, because one include element cannot express a per-root-page difference.
 *
 * This bundle writes the fields it defines - responsiveCols and responsiveOffsets. A bundle
 * layering further conditions on column output extends this class and overrides
 * {@see self::getValuesToWrite()} / {@see self::getRequiredContentColumns()} to add its own,
 * then replaces this service definition with the subclass. contao-bootstrap does exactly that
 * for responsiveOverwriteRowCols, which is the selector whose subpalette holds responsiveCols
 * and responsiveOffsets - so on that install the flag and the values are one setting and have
 * to be written together, in one statement, by one migration. Hence a service override rather
 * than a second migration: there is only ever one, and it adapts to what is installed.
 */
class DelegatedModuleColumnsMigration extends AbstractMigration
{
    private const EMPTY_SERIALIZED = ['', 'a:0:{}', 'N;'];

    /** @var list<array{id:int, values:array<string,string>}>|null */
    private ?array $arrUpdates = null;

    /** @var list<array{id:int, reason:string}> */
    private array $arrAmbiguous = [];

    public function __construct(protected readonly Connection $connection)
    {
    }

    public function getName(): string
    {
        return 'Kiwi Responsive Base: restore delegated module column settings on their include elements';
    }

    /**
     * The columns this migration writes onto an include element, given the settings of the
     * module it renders. Return an empty array to leave the row alone.
     *
     * @param array{cols: string, offsets: string} $arrResolved
     *
     * @return array<string, string>
     */
    protected function getValuesToWrite(array $arrResolved): array
    {
        return [
            'responsiveCols' => $arrResolved['cols'],
            'responsiveOffsets' => $arrResolved['offsets'],
        ];
    }

    /**
     * tl_content columns this migration needs. Missing columns disable it.
     *
     * @return list<string>
     */
    protected function getRequiredContentColumns(): array
    {
        return ['responsiveCols', 'responsiveOffsets'];
    }

    public function shouldRun(): bool
    {
        if (!$this->hasRequiredColumns()) {
            return false;
        }

        return $this->collectUpdates() !== [];
    }

    public function run(): MigrationResult
    {
        $arrUpdates = $this->collectUpdates();

        foreach ($arrUpdates as $arrUpdate) {
            $this->connection->update('tl_content', $arrUpdate['values'], ['id' => $arrUpdate['id']]);
        }

        // The scan describes the pre-run state; drop it so a second shouldRun() in the same
        // contao:migrate pass re-reads the new one.
        $this->arrUpdates = null;

        return $this->createResult(true, $this->buildMessage(\count($arrUpdates)));
    }

    protected function buildMessage(int $intUpdated): string
    {
        $strMessage = sprintf(
            'Restored the column settings of the delegated module on %d include element(s), so what '
            . 'they used to render keeps rendering.',
            $intUpdated,
        );

        if ($this->arrAmbiguous !== []) {
            $strMessage .= sprintf(
                ' %d element(s) need manual resolution because the modules they may render disagree '
                . 'about their columns, which a single include element cannot express: %s.',
                \count($this->arrAmbiguous),
                implode(', ', array_map(
                    static fn (array $arr) => sprintf('tl_content.%d (%s)', $arr['id'], $arr['reason']),
                    $this->arrAmbiguous,
                )),
            );
        }

        return $strMessage;
    }

    /**
     * Rows whose stored state differs from what the subclass says they should carry.
     *
     * @return list<array{id:int, values:array<string,string>}>
     */
    private function collectUpdates(): array
    {
        if ($this->arrUpdates !== null) {
            return $this->arrUpdates;
        }

        $this->arrAmbiguous = [];
        $arrUpdates = [];

        foreach ($this->fetchCandidates() as $arrCandidate) {
            $arrMap = StringUtil::deserialize($arrCandidate['rootPageDependentModules'], true);
            if ($arrMap === []) {
                continue;
            }

            $arrModuleIds = $this->resolveRenderedModuleIds($arrCandidate, $arrMap);
            if ($arrModuleIds === []) {
                continue;
            }

            $arrResolved = $this->collectModuleSettings($arrModuleIds);

            if ($arrResolved === null) {
                $this->arrAmbiguous[] = [
                    'id' => (int) $arrCandidate['id'],
                    'reason' => 'modules ' . implode('/', $arrModuleIds) . ' differ',
                ];
                continue;
            }

            // The module rendered no responsive classes at all - there is nothing to carry over,
            // and writing anything here would invent columns that never existed.
            if ($arrResolved === []) {
                continue;
            }

            $arrValues = $this->getValuesToWrite($arrResolved);
            if ($arrValues === []) {
                continue;
            }

            foreach ($arrValues as $strColumn => $strValue) {
                if (!$this->valuesMatch($arrCandidate[$strColumn] ?? null, $strValue)) {
                    $arrUpdates[] = ['id' => (int) $arrCandidate['id'], 'values' => $arrValues];
                    continue 2;
                }
            }
        }

        return $this->arrUpdates = $arrUpdates;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function fetchCandidates(): array
    {
        $arrColumns = array_unique(array_merge(
            ['id', 'pid', 'ptable'],
            $this->getRequiredContentColumns(),
        ));

        $strSelect = implode(', ', array_map(static fn (string $c) => 'c.' . $c, $arrColumns));

        return $this->connection->fetchAllAssociative(
            "SELECT {$strSelect}, m.rootPageDependentModules
               FROM tl_content c
               INNER JOIN tl_module m ON m.id = c.module
              WHERE c.type = 'module'
                AND m.rootPageDependentModules IS NOT NULL
                AND m.rootPageDependentModules NOT IN (?)",
            [self::EMPTY_SERIALIZED],
            [Connection::PARAM_STR_ARRAY],
        );
    }

    /**
     * A serialized responsive payload compares by value, so the same setting stored with
     * different scalar types (i:12 vs s:2:"12") counts as unchanged. Plain scalars compare
     * as strings.
     */
    private function valuesMatch(mixed $varStored, string $strTarget): bool
    {
        if (str_starts_with($strTarget, 'a:')) {
            return $this->normalize(\is_string($varStored) ? $varStored : null) === $this->normalize($strTarget);
        }

        return (string) ($varStored ?? '') === $strTarget;
    }

    /**
     * The module(s) an include element may render: the one for its own root page when that can
     * be determined, otherwise every module the wrapper delegates to.
     *
     * @param array<string, mixed> $arrCandidate
     * @param array<mixed, mixed>  $arrMap       root page id => module id
     *
     * @return list<int>
     */
    private function resolveRenderedModuleIds(array $arrCandidate, array $arrMap): array
    {
        $intRootPage = $this->resolveRootPageId($arrCandidate);

        if ($intRootPage !== null && isset($arrMap[$intRootPage]) && (int) $arrMap[$intRootPage] > 0) {
            return [(int) $arrMap[$intRootPage]];
        }

        $arrIds = [];

        foreach ($arrMap as $varModuleId) {
            if ((int) $varModuleId > 0) {
                $arrIds[] = (int) $varModuleId;
            }
        }

        return array_values(array_unique($arrIds));
    }

    /**
     * Walks the parent chain to the owning page, then up to its root page. Null when the chain
     * leaves what can be resolved here (news, events, static articles, …).
     *
     * @param array<string, mixed> $arrCandidate
     */
    private function resolveRootPageId(array $arrCandidate): ?int
    {
        $intPid = (int) $arrCandidate['pid'];
        $strPtable = (string) ($arrCandidate['ptable'] ?: 'tl_article');
        $intPageId = null;

        for ($i = 0; $i < 10; ++$i) {
            if ($strPtable === 'tl_article') {
                $varPage = $this->connection->fetchOne('SELECT pid FROM tl_article WHERE id = ?', [$intPid]);
                $intPageId = false === $varPage ? null : (int) $varPage;
                break;
            }

            if ($strPtable !== 'tl_content') {
                return null;
            }

            $arrParent = $this->connection->fetchAssociative('SELECT pid, ptable FROM tl_content WHERE id = ?', [$intPid]);
            if (!$arrParent) {
                return null;
            }

            $intPid = (int) $arrParent['pid'];
            $strPtable = (string) ($arrParent['ptable'] ?: 'tl_article');
        }

        if (!$intPageId) {
            return null;
        }

        $arrSeen = [];

        while ($intPageId > 0 && !isset($arrSeen[$intPageId])) {
            $arrSeen[$intPageId] = true;

            $arrPage = $this->connection->fetchAssociative('SELECT id, pid, type FROM tl_page WHERE id = ?', [$intPageId]);
            if (!$arrPage) {
                return null;
            }

            if ('root' === $arrPage['type']) {
                return (int) $arrPage['id'];
            }

            $intPageId = (int) $arrPage['pid'];
        }

        return null;
    }

    /**
     * The shared column settings of the given modules, [] when they render nothing at all, or
     * null when they disagree.
     *
     * @param list<int> $arrModuleIds
     *
     * @return array{cols: string, offsets: string}|array{}|null
     */
    private function collectModuleSettings(array $arrModuleIds): array|null
    {
        $arrRows = $this->connection->fetchAllAssociative(
            'SELECT id, addResponsive, responsiveCols, responsiveOffsets FROM tl_module WHERE id IN (?)',
            [$arrModuleIds],
            [Connection::PARAM_INT_ARRAY],
        );

        if ($arrRows === []) {
            return [];
        }

        $arrSeen = [];
        $arrFirst = null;

        foreach ($arrRows as $arrRow) {
            // A module with its responsive settings switched off rendered no classes; that is a
            // distinct answer, and mixing it with one that did render is a disagreement.
            $blnOn = (bool) $arrRow['addResponsive'];

            $arrSeen[json_encode([
                $blnOn,
                $blnOn ? $this->normalize($arrRow['responsiveCols']) : [],
                $blnOn ? $this->normalize($arrRow['responsiveOffsets']) : [],
            ])] = true;

            $arrFirst ??= [
                'on' => $blnOn,
                'cols' => (string) ($arrRow['responsiveCols'] ?? ''),
                'offsets' => (string) ($arrRow['responsiveOffsets'] ?? ''),
            ];
        }

        if (\count($arrSeen) > 1) {
            return null;
        }

        if (!$arrFirst['on'] || $this->normalize($arrFirst['cols']) === []) {
            return [];
        }

        return ['cols' => $arrFirst['cols'], 'offsets' => $arrFirst['offsets']];
    }

    /**
     * Serialized responsive payload as a comparable map.
     *
     * @return array<string, string>
     */
    private function normalize(?string $strValue): array
    {
        $arrValues = $strValue ? StringUtil::deserialize($strValue, true) : [];
        $arrOut = [];

        foreach ($arrValues as $strBreakpoint => $varValue) {
            if (null === $varValue || '' === $varValue) {
                continue;
            }

            $arrOut[(string) $strBreakpoint] = (string) $varValue;
        }

        ksort($arrOut);

        return $arrOut;
    }

    private function hasRequiredColumns(): bool
    {
        $schemaManager = $this->connection->createSchemaManager();
        $arrTables = $schemaManager->listTableNames();

        foreach (['tl_content', 'tl_module', 'tl_article', 'tl_page'] as $strTable) {
            if (!\in_array($strTable, $arrTables, true)) {
                return false;
            }
        }

        $arrContent = array_map(static fn ($col) => $col->getName(), $schemaManager->listTableColumns('tl_content'));
        $arrModule = array_map(static fn ($col) => $col->getName(), $schemaManager->listTableColumns('tl_module'));

        foreach ($this->getRequiredContentColumns() as $strColumn) {
            if (!\in_array($strColumn, $arrContent, true)) {
                return false;
            }
        }

        return \in_array('rootPageDependentModules', $arrModule, true)
            && \in_array('responsiveCols', $arrModule, true)
            && \in_array('responsiveOffsets', $arrModule, true);
    }
}
