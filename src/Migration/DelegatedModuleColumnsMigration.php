<?php

declare(strict_types=1);

namespace Kiwi\Contao\ResponsiveBaseBundle\Migration;

use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Contao\StringUtil;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Psr\Log\LoggerInterface;

/**
 * Shared resolution for migrations that repair include elements rendering a *delegated*
 * module.
 *
 * Column classes for a rendered module come from whichever record is considered its
 * outermost includer. That resolution used to honour only a content element inserting the
 * rendered module itself; a module reached through a wrapper - core's root page dependent
 * modules, or kiwi/contao-core-bundle's language dependent and dynamic (page dependent)
 * modules, each delegating to a different module per context - fell back to its own
 * tl_module row. Now the outermost includer wins in every case, which is
 * the intended behaviour: whoever places an element has the first call on how it is styled.
 *
 * For existing content that moves the authoritative record. Everything the inner module
 * said about its own width is now ignored and the including element speaks instead - an
 * element whose responsive settings could not previously have any effect on these modules,
 * so whatever they hold was never a decision anyone made or saw. Restoring the previous
 * rendering therefore means carrying the inner module's settings up to the element.
 *
 * Which module an element rendered is normally determinable: the element's page, and the
 * page tree above it, select the module the same way each wrapper does at runtime - by root
 * page, by root page language, or by the nearest configured page. Where the context has no
 * page - an element in news, events, a static article, or any other parent this cannot walk -
 * every delegated module is read instead. If they all agree there is still exactly one answer
 * and it is used; only genuine disagreement is left alone and reported, because one include
 * element cannot express a per-context difference.
 *
 * Wrappers nested in wrappers are not supported and not followed: the module a map names is
 * taken as the one that rendered.
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

    /**
     * Wrapper module type => tl_module column holding its delegation config. The kiwi types
     * come from kiwi/contao-core-bundle, which is optional - a type whose column is missing is
     * skipped.
     */
    private const WRAPPER_COLUMNS = [
        'root_page_dependent_modules' => 'rootPageDependentModules',
        'languageDependentModule' => 'languageDependentModules',
        'dynamicModule' => 'dynamicModule',
    ];

    /** @var array<string, string> wrapper types available on this install => config column */
    private array $arrWrapperColumns = [];

    /** @var list<array{id:int, values:array<string,string>}>|null */
    private ?array $arrUpdates = null;

    /** @var list<array{id:int, reason:string}> */
    private array $arrAmbiguous = [];

    private bool $blnAmbiguousReported = false;

    public function __construct(
        protected readonly Connection $connection,
        private readonly ?LoggerInterface $logger = null,
    ) {
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

        $arrUpdates = $this->collectUpdates();

        // Ambiguous elements are never written, so they cannot make this migration pending -
        // it would stay pending forever and contao:migrate would loop. Report them on their own
        // instead, on every migrate until they are resolved by hand, since the run result
        // message alone never appears when they are all there is.
        $this->reportAmbiguous();

        return $arrUpdates !== [];
    }

    private function reportAmbiguous(): void
    {
        if ($this->arrAmbiguous === [] || $this->blnAmbiguousReported) {
            return;
        }

        $this->blnAmbiguousReported = true;

        $this->logger?->error(
            '{count} include element(s) need manual resolution: the delegated modules they may '
            . 'render disagree about their columns, which a single include element cannot '
            . 'express, so they may no longer render what they used to: {elements}.',
            [
                'count' => \count($this->arrAmbiguous),
                'elements' => $this->formatAmbiguous(),
            ],
        );
    }

    private function formatAmbiguous(): string
    {
        return implode(', ', array_map(
            static fn (array $arr) => sprintf('tl_content.%d (%s)', $arr['id'], $arr['reason']),
            $this->arrAmbiguous,
        ));
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
                $this->formatAmbiguous(),
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
            $arrConfig = StringUtil::deserialize($arrCandidate['wrapperConfig'], true);
            if ($arrConfig === []) {
                continue;
            }

            $arrModuleIds = $this->resolveRenderedModuleIds($arrCandidate, $arrConfig);
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
     * Include elements placing a wrapper, one query per wrapper type installed. The wrapper's
     * type and config are aliased so all types share one shape.
     *
     * @return list<array<string, mixed>>
     */
    private function fetchCandidates(): array
    {
        $arrColumns = array_unique(array_merge(
            ['id', 'pid', 'ptable'],
            $this->getRequiredContentColumns(),
        ));

        $strSelect = implode(', ', array_map(static fn (string $c) => 'c.' . $c, $arrColumns));
        $arrCandidates = [];

        foreach ($this->arrWrapperColumns as $strType => $strColumn) {
            array_push($arrCandidates, ...$this->connection->fetchAllAssociative(
                "SELECT {$strSelect}, m.type AS wrapperType, m.{$strColumn} AS wrapperConfig
                   FROM tl_content c
                   INNER JOIN tl_module m ON m.id = c.module
                  WHERE c.type = 'module'
                    AND m.type = ?
                    AND m.{$strColumn} IS NOT NULL
                    AND m.{$strColumn} NOT IN (?)",
                [$strType, self::EMPTY_SERIALIZED],
                [ParameterType::STRING, ArrayParameterType::STRING],
            ));
        }

        return $arrCandidates;
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
     * The module(s) an include element may render: the one its wrapper selects for the
     * element's page when that can be determined (none if the wrapper selects nothing there),
     * otherwise every module the wrapper delegates to.
     *
     * @param array<string, mixed> $arrCandidate
     * @param array<mixed, mixed>  $arrConfig    the wrapper's deserialized delegation config
     *
     * @return list<int>
     */
    private function resolveRenderedModuleIds(array $arrCandidate, array $arrConfig): array
    {
        $arrChain = $this->resolvePageChain($arrCandidate);

        return match ($arrCandidate['wrapperType']) {
            'root_page_dependent_modules' => $this->resolveRootPageDependent($arrConfig, $arrChain),
            'languageDependentModule' => $this->resolveLanguageDependent($arrConfig, $arrChain),
            'dynamicModule' => $this->resolveDynamic($arrConfig, $arrChain),
            default => [],
        };
    }

    /**
     * RootPageDependentModulesController: the module mapped to the page's root page. An unmapped
     * root renders no delegated module at all (the wrapper returns an empty response).
     *
     * @param array<mixed, mixed>              $arrMap   root page id => module id
     * @param list<array<string, mixed>>|null  $arrChain
     *
     * @return list<int>
     */
    private function resolveRootPageDependent(array $arrMap, ?array $arrChain): array
    {
        $arrRoot = $this->findRootPage($arrChain);

        if ($arrRoot === null) {
            return $this->positiveIds($arrMap);
        }

        $intModule = (int) ($arrMap[$arrRoot['id']] ?? 0);

        return $intModule > 0 ? [$intModule] : [];
    }

    /**
     * LanguageDependentModule::getModuleModel(): the module for the page language (the root
     * page's), else for the root's fallback language, else the first configured module that
     * exists. The first entry per language wins.
     *
     * @param array<mixed, mixed>              $arrEntries list of ['language' => ..., 'mod' => ...]
     * @param list<array<string, mixed>>|null  $arrChain
     *
     * @return list<int>
     */
    private function resolveLanguageDependent(array $arrEntries, ?array $arrChain): array
    {
        $arrByLanguage = [];

        foreach ($arrEntries as $arrEntry) {
            if (\is_array($arrEntry) && !isset($arrByLanguage[$arrEntry['language'] ?? ''])) {
                $arrByLanguage[$arrEntry['language'] ?? ''] = (int) ($arrEntry['mod'] ?? 0);
            }
        }

        $arrRoot = $this->findRootPage($arrChain);

        if ($arrRoot === null) {
            return $this->positiveIds($arrByLanguage);
        }

        $arrExisting = $this->existingModuleIds($this->positiveIds($arrByLanguage));
        $arrLanguages = [(string) $arrRoot['language'], $this->resolveFallbackLanguage($arrRoot)];

        foreach ($arrLanguages as $strLanguage) {
            $intModule = $arrByLanguage[$strLanguage] ?? 0;

            if ($strLanguage !== '' && isset($arrExisting[$intModule])) {
                return [$intModule];
            }
        }

        foreach ($arrByLanguage as $intModule) {
            if (isset($arrExisting[$intModule])) {
                return [$intModule];
            }
        }

        return [];
    }

    /**
     * DynamicModule::getModuleModel(): walking up from the element's page, the first page with
     * an entry decides - unless that entry is marked "not inherited" and belongs to an ancestor
     * rather than the page itself. An entry without a module renders nothing. Later entries for
     * the same page win.
     *
     * @param array<mixed, mixed>              $arrEntries list of ['page' => ..., 'module' => ..., 'notInherited' => ...]
     * @param list<array<string, mixed>>|null  $arrChain
     *
     * @return list<int>
     */
    private function resolveDynamic(array $arrEntries, ?array $arrChain): array
    {
        $arrByPage = [];

        foreach ($arrEntries as $arrEntry) {
            if (\is_array($arrEntry)) {
                $arrByPage[(int) ($arrEntry['page'] ?? 0)] = [
                    'module' => (int) ($arrEntry['module'] ?? 0),
                    'notInherited' => (bool) ($arrEntry['notInherited'] ?? false),
                ];
            }
        }

        if ($arrChain === null) {
            return $this->positiveIds(array_column($arrByPage, 'module'));
        }

        foreach ($arrChain as $i => $arrPage) {
            $arrEntry = $arrByPage[(int) $arrPage['id']] ?? null;

            if ($arrEntry !== null && (!$arrEntry['notInherited'] || $i === 0)) {
                return $arrEntry['module'] > 0 ? [$arrEntry['module']] : [];
            }
        }

        return [];
    }

    /**
     * The element's page followed by its ancestors up to the top of the tree. Null when the
     * element's parent chain leaves what can be resolved here (news, events, static
     * articles, …) or the page tree is broken.
     *
     * @param array<string, mixed> $arrCandidate
     *
     * @return list<array<string, mixed>>|null
     */
    private function resolvePageChain(array $arrCandidate): ?array
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

        $arrChain = [];
        $arrSeen = [];

        while ($intPageId > 0 && !isset($arrSeen[$intPageId])) {
            $arrSeen[$intPageId] = true;

            $arrPage = $this->connection->fetchAssociative('SELECT id, pid, type, language, dns, fallback FROM tl_page WHERE id = ?', [$intPageId]);
            if (!$arrPage) {
                return null;
            }

            $arrChain[] = $arrPage;
            $intPageId = (int) $arrPage['pid'];
        }

        return $arrChain;
    }

    /**
     * @param list<array<string, mixed>>|null $arrChain
     *
     * @return array<string, mixed>|null
     */
    private function findRootPage(?array $arrChain): ?array
    {
        foreach ($arrChain ?? [] as $arrPage) {
            if ('root' === $arrPage['type']) {
                return $arrPage;
            }
        }

        return null;
    }

    /**
     * PageModel::loadDetails()' rootFallbackLanguage: the root's own language if it is the
     * fallback, else that of the published fallback root of the same domain.
     *
     * @param array<string, mixed> $arrRoot
     */
    private function resolveFallbackLanguage(array $arrRoot): string
    {
        if ($arrRoot['fallback']) {
            return (string) $arrRoot['language'];
        }

        $intTime = time();

        return (string) $this->connection->fetchOne(
            "SELECT language FROM tl_page
              WHERE type = 'root' AND dns = ? AND fallback = 1 AND published = 1
                AND (start = '' OR start <= ?) AND (stop = '' OR stop > ?)
              ORDER BY sorting LIMIT 1",
            [$arrRoot['dns'], $intTime, $intTime],
        );
    }

    /**
     * @param iterable<mixed> $varIds
     *
     * @return list<int>
     */
    private function positiveIds(iterable $varIds): array
    {
        $arrIds = [];

        foreach ($varIds as $varId) {
            if ((int) $varId > 0) {
                $arrIds[] = (int) $varId;
            }
        }

        return array_values(array_unique($arrIds));
    }

    /**
     * @param list<int> $arrIds
     *
     * @return array<int, true>
     */
    private function existingModuleIds(array $arrIds): array
    {
        if ($arrIds === []) {
            return [];
        }

        $arrExisting = $this->connection->fetchFirstColumn(
            'SELECT id FROM tl_module WHERE id IN (?)',
            [$arrIds],
            [ArrayParameterType::INTEGER],
        );

        return array_fill_keys(array_map('intval', $arrExisting), true);
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
            [ArrayParameterType::INTEGER],
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

        if (!\in_array('responsiveCols', $arrModule, true) || !\in_array('responsiveOffsets', $arrModule, true)) {
            return false;
        }

        $this->arrWrapperColumns = array_filter(
            self::WRAPPER_COLUMNS,
            static fn (string $strColumn) => \in_array($strColumn, $arrModule, true),
        );

        return $this->arrWrapperColumns !== [];
    }
}
