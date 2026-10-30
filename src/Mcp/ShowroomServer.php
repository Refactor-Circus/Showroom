<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Mcp;

use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;
use Laravel\Mcp\Server\ServerContext;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\ToolSearch;
use RefactorCircus\Keystone\Mcp\Server;
use RefactorCircus\Showroom\Domains\Asset\Mcp\Tools\AttachAssetTool;
use RefactorCircus\Showroom\Domains\Asset\Mcp\Tools\CreateAssetTool;
use RefactorCircus\Showroom\Domains\Asset\Mcp\Tools\DeleteAssetTool;
use RefactorCircus\Showroom\Domains\Asset\Mcp\Tools\DetachAssetTool;
use RefactorCircus\Showroom\Domains\Asset\Mcp\Tools\ListAssetsTool;
use RefactorCircus\Showroom\Domains\Asset\Mcp\Tools\ShowAssetTool;
use RefactorCircus\Showroom\Domains\Asset\Mcp\Tools\UpdateAssetTool;
use RefactorCircus\Showroom\Domains\Association\Mcp\Tools\CreateAssociationTypeTool;
use RefactorCircus\Showroom\Domains\Association\Mcp\Tools\DeleteAssociationTypeTool;
use RefactorCircus\Showroom\Domains\Association\Mcp\Tools\ListAssociationTypesTool;
use RefactorCircus\Showroom\Domains\Association\Mcp\Tools\ShowAssociationTypeTool;
use RefactorCircus\Showroom\Domains\Association\Mcp\Tools\UpdateAssociationTypeTool;
use RefactorCircus\Showroom\Domains\Attribute\Mcp\Tools\CreateAttributeGroupTool;
use RefactorCircus\Showroom\Domains\Attribute\Mcp\Tools\CreateAttributeOptionTool;
use RefactorCircus\Showroom\Domains\Attribute\Mcp\Tools\CreateAttributeTool;
use RefactorCircus\Showroom\Domains\Attribute\Mcp\Tools\DeleteAttributeGroupTool;
use RefactorCircus\Showroom\Domains\Attribute\Mcp\Tools\DeleteAttributeOptionTool;
use RefactorCircus\Showroom\Domains\Attribute\Mcp\Tools\DeleteAttributeTool;
use RefactorCircus\Showroom\Domains\Attribute\Mcp\Tools\ListAttributeGroupsTool;
use RefactorCircus\Showroom\Domains\Attribute\Mcp\Tools\ListAttributeOptionsTool;
use RefactorCircus\Showroom\Domains\Attribute\Mcp\Tools\ListAttributesTool;
use RefactorCircus\Showroom\Domains\Attribute\Mcp\Tools\ShowAttributeGroupTool;
use RefactorCircus\Showroom\Domains\Attribute\Mcp\Tools\ShowAttributeTool;
use RefactorCircus\Showroom\Domains\Attribute\Mcp\Tools\UpdateAttributeGroupTool;
use RefactorCircus\Showroom\Domains\Attribute\Mcp\Tools\UpdateAttributeOptionTool;
use RefactorCircus\Showroom\Domains\Attribute\Mcp\Tools\UpdateAttributeTool;
use RefactorCircus\Showroom\Domains\Category\Mcp\Tools\CreateCategoryTool;
use RefactorCircus\Showroom\Domains\Category\Mcp\Tools\DeleteCategoryTool;
use RefactorCircus\Showroom\Domains\Category\Mcp\Tools\ListCategoriesTool;
use RefactorCircus\Showroom\Domains\Category\Mcp\Tools\ShowCategoryTool;
use RefactorCircus\Showroom\Domains\Category\Mcp\Tools\UpdateCategoryTool;
use RefactorCircus\Showroom\Domains\Channel\Mcp\Tools\CreateChannelTool;
use RefactorCircus\Showroom\Domains\Channel\Mcp\Tools\CreateLocaleTool;
use RefactorCircus\Showroom\Domains\Channel\Mcp\Tools\DeleteChannelTool;
use RefactorCircus\Showroom\Domains\Channel\Mcp\Tools\DeleteLocaleTool;
use RefactorCircus\Showroom\Domains\Channel\Mcp\Tools\ListChannelsTool;
use RefactorCircus\Showroom\Domains\Channel\Mcp\Tools\ListLocalesTool;
use RefactorCircus\Showroom\Domains\Channel\Mcp\Tools\ShowChannelTool;
use RefactorCircus\Showroom\Domains\Channel\Mcp\Tools\ShowLocaleTool;
use RefactorCircus\Showroom\Domains\Channel\Mcp\Tools\UpdateChannelTool;
use RefactorCircus\Showroom\Domains\Channel\Mcp\Tools\UpdateLocaleTool;
use RefactorCircus\Showroom\Domains\Family\Mcp\Tools\CreateFamilyTool;
use RefactorCircus\Showroom\Domains\Family\Mcp\Tools\CreateFamilyVariantTool;
use RefactorCircus\Showroom\Domains\Family\Mcp\Tools\DeleteFamilyTool;
use RefactorCircus\Showroom\Domains\Family\Mcp\Tools\DeleteFamilyVariantTool;
use RefactorCircus\Showroom\Domains\Family\Mcp\Tools\ListFamiliesTool;
use RefactorCircus\Showroom\Domains\Family\Mcp\Tools\ListFamilyVariantsTool;
use RefactorCircus\Showroom\Domains\Family\Mcp\Tools\ShowFamilyTool;
use RefactorCircus\Showroom\Domains\Family\Mcp\Tools\ShowFamilyVariantTool;
use RefactorCircus\Showroom\Domains\Family\Mcp\Tools\UpdateFamilyTool;
use RefactorCircus\Showroom\Domains\Family\Mcp\Tools\UpdateFamilyVariantTool;
use RefactorCircus\Showroom\Domains\Owner\Mcp\Tools\CreateOwnerTool;
use RefactorCircus\Showroom\Domains\Owner\Mcp\Tools\CreateOwnerTypeTool;
use RefactorCircus\Showroom\Domains\Owner\Mcp\Tools\DeleteOwnerTool;
use RefactorCircus\Showroom\Domains\Owner\Mcp\Tools\DeleteOwnerTypeTool;
use RefactorCircus\Showroom\Domains\Owner\Mcp\Tools\ListOwnersTool;
use RefactorCircus\Showroom\Domains\Owner\Mcp\Tools\ListOwnerTypesTool;
use RefactorCircus\Showroom\Domains\Owner\Mcp\Tools\ShowOwnerTool;
use RefactorCircus\Showroom\Domains\Owner\Mcp\Tools\ShowOwnerTypeTool;
use RefactorCircus\Showroom\Domains\Owner\Mcp\Tools\UpdateOwnerTool;
use RefactorCircus\Showroom\Domains\Owner\Mcp\Tools\UpdateOwnerTypeTool;
use RefactorCircus\Showroom\Domains\Product\Mcp\Tools\CreateProductTool;
use RefactorCircus\Showroom\Domains\Product\Mcp\Tools\DeleteProductTool;
use RefactorCircus\Showroom\Domains\Product\Mcp\Tools\ListProductsTool;
use RefactorCircus\Showroom\Domains\Product\Mcp\Tools\ShowProductTool;
use RefactorCircus\Showroom\Domains\Product\Mcp\Tools\UpdateProductTool;
use RefactorCircus\Showroom\Domains\ProductModel\Mcp\Tools\CreateProductModelTool;
use RefactorCircus\Showroom\Domains\ProductModel\Mcp\Tools\DeleteProductModelTool;
use RefactorCircus\Showroom\Domains\ProductModel\Mcp\Tools\ListProductModelsTool;
use RefactorCircus\Showroom\Domains\ProductModel\Mcp\Tools\ShowProductModelTool;
use RefactorCircus\Showroom\Domains\ProductModel\Mcp\Tools\UpdateProductModelTool;
use RefactorCircus\Showroom\Domains\Transfer\Mcp\Tools\StartExportTool;
use RefactorCircus\Showroom\Domains\Transfer\Mcp\Tools\StartImportTool;
use RefactorCircus\Showroom\Domains\Workflow\Mcp\Tools\ListProductVersionsTool;
use RefactorCircus\Showroom\Domains\Workflow\Mcp\Tools\RevertProductTool;
use RefactorCircus\Showroom\Domains\Workflow\Mcp\Tools\ShowProductVersionTool;
use RefactorCircus\Showroom\Domains\Workflow\Mcp\Tools\TransitionProductTool;
use RefactorCircus\Showroom\Impex\ImpexIntegration;
use RefactorCircus\Showroom\Mcp\Tools\ListShowroomHistoryTool;

#[Name('Showroom')]
#[Version('1.0.0')]
#[Instructions(
    'Manage the Showroom product catalog (PIM). Attributes are the typed characteristics products are described '.
    'with — color, weight, description — and are defined at runtime. Every record is addressed by its code, '.
    'which never changes once created; an attribute\'s type never changes either, so choose both carefully. '.
    'Attribute groups only organise attributes into sections; an attribute belongs to at most one. Select and '.
    'multiselect attributes take a list of options; no other type does. A family (attribute set) is a kind of '.
    'product: the attributes it has and which of them are required. Products are identified by identifier '.
    '(SKU) and hold values keyed by attribute code, as lists of {locale, scope, data} slots. A family variant '.
    'says how a family\'s products vary: one or two levels of axes. A root product model holds the common '.
    'values; in two-level variants, sub-models hold level-1 values; variant products hold the last level and '.
    'inherit the rest. Owners (vendors, series, ...) form chains of runtime-defined owner types; a product or '.
    'root product model belongs to the deepest owner. Categories form independent trees (a root category is a '.
    'tree); products and product models are filed in any number of them, and variants inherit their models\' '.
    'categories. Assets (images, documents) are added from a URL and linked to products, product models or '.
    'owners under a role; variants show their models\' assets too. Localizable values need an existing locale; '.
    'scopable values an existing channel, and a value that is both must use one of that channel\'s locales. '.
    'Reads accept scope and locales to return one channel\'s values. Associations relate products and models '.
    'by type (cross_sell, accessories, ...); two-way types mirror themselves; quantified types hold bundle and '.
    'kit components with quantities. Products carry completeness per channel and locale, move draft → in_review → '.
    'approved through transition-product-tool, and publish snapshots a version for storefronts; every write is '.
    'versioned and can be reverted. With Impex installed, start-import-tool and start-export-tool run bulk '.
    'imports and exports as Impex runs; with refactor-circus/keen installed, list-showroom-history-tool lists who changed what. Labels are keyed by locale. Product search is page-numbered and returns a total; other '.
    'listings are cursor paginated: pass meta.next_cursor back as cursor for the next page.',
)]
final class ShowroomServer extends Server
{
    /**
     * Every tool the server offers, behind ToolSearch. Also registered with
     * Cortex when it is installed.
     *
     * @var array<int, class-string<Tool>>
     */
    public const array TOOLS = [
        // Attribute groups
        ListAttributeGroupsTool::class,
        ShowAttributeGroupTool::class,
        CreateAttributeGroupTool::class,
        UpdateAttributeGroupTool::class,
        DeleteAttributeGroupTool::class,

        // Attributes
        ListAttributesTool::class,
        ShowAttributeTool::class,
        CreateAttributeTool::class,
        UpdateAttributeTool::class,
        DeleteAttributeTool::class,

        // Attribute options
        ListAttributeOptionsTool::class,
        CreateAttributeOptionTool::class,
        UpdateAttributeOptionTool::class,
        DeleteAttributeOptionTool::class,

        // Families
        ListFamiliesTool::class,
        ShowFamilyTool::class,
        CreateFamilyTool::class,
        UpdateFamilyTool::class,
        DeleteFamilyTool::class,

        // Family variants
        ListFamilyVariantsTool::class,
        ShowFamilyVariantTool::class,
        CreateFamilyVariantTool::class,
        UpdateFamilyVariantTool::class,
        DeleteFamilyVariantTool::class,

        // Product models
        ListProductModelsTool::class,
        ShowProductModelTool::class,
        CreateProductModelTool::class,
        UpdateProductModelTool::class,
        DeleteProductModelTool::class,

        // Products
        ListProductsTool::class,
        ShowProductTool::class,
        CreateProductTool::class,
        UpdateProductTool::class,
        DeleteProductTool::class,
        TransitionProductTool::class,
        ListProductVersionsTool::class,
        ShowProductVersionTool::class,
        RevertProductTool::class,

        // Ownership
        ListOwnerTypesTool::class,
        ShowOwnerTypeTool::class,
        CreateOwnerTypeTool::class,
        UpdateOwnerTypeTool::class,
        DeleteOwnerTypeTool::class,
        ListOwnersTool::class,
        ShowOwnerTool::class,
        CreateOwnerTool::class,
        UpdateOwnerTool::class,
        DeleteOwnerTool::class,

        // Taxonomy
        ListCategoriesTool::class,
        ShowCategoryTool::class,
        CreateCategoryTool::class,
        UpdateCategoryTool::class,
        DeleteCategoryTool::class,

        // Media
        ListAssetsTool::class,
        ShowAssetTool::class,
        CreateAssetTool::class,
        UpdateAssetTool::class,
        DeleteAssetTool::class,
        AttachAssetTool::class,
        DetachAssetTool::class,

        // Channels and locales
        ListLocalesTool::class,
        ShowLocaleTool::class,
        CreateLocaleTool::class,
        UpdateLocaleTool::class,
        DeleteLocaleTool::class,
        ListChannelsTool::class,
        ShowChannelTool::class,
        CreateChannelTool::class,
        UpdateChannelTool::class,
        DeleteChannelTool::class,

        // Associations
        ListAssociationTypesTool::class,
        ShowAssociationTypeTool::class,
        CreateAssociationTypeTool::class,
        UpdateAssociationTypeTool::class,
        DeleteAssociationTypeTool::class,

        // Import and export (with refactor-circus/impex)
        StartImportTool::class,
        StartExportTool::class,

        // Audit history (with refactor-circus/keen)
        ListShowroomHistoryTool::class,
    ];

    /**
     * The tools that start Impex runs, offered only while Impex runs
     * Showroom's flows (see ImpexIntegration::active()).
     *
     * @var array<int, class-string<Tool>>
     */
    private const array TRANSFER_TOOLS = [
        StartImportTool::class,
        StartExportTool::class,
    ];

    /**
     * @var array<class-string<ToolSearch>, array<int, class-string<Tool>|Tool>>
     */
    protected array $tools = [
        ToolSearch::class => self::TOOLS,
    ];

    /**
     * Leave the transfer tools out unless Impex is active: they answer with
     * Impex's RunResource, which an application without Impex cannot load.
     */
    public function createContext(): ServerContext
    {
        if (! app(ImpexIntegration::class)->active()) {
            foreach ($this->tools as $search => $tools) {
                $this->tools[$search] = array_values(array_filter(
                    $tools,
                    fn (string|Tool $tool): bool => ! in_array($tool, self::TRANSFER_TOOLS, true),
                ));
            }
        }

        return parent::createContext();
    }
}
