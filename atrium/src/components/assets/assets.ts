import { productModelPath, productSearchPath } from '../catalog/catalog'

/*
 * What the Showroom asset pages share (AC 54, 71): the asset payload, the
 * link types, the file-type filters v1 offered, and how a file is shown
 * (thumbnail, kind, size) and where a link's target lives in the layer.
 */

/** What an asset may be linked to (`AttachAssetRequest` `type`). */
export type AssetLinkType = 'product' | 'product_model' | 'owner'

/** Every link type, in Showroom's order. */
export const ASSET_LINK_TYPES: readonly AssetLinkType[] = ['product', 'product_model', 'owner']

/** One record an asset is linked to: a product by identifier, a product model or owner by code. */
export interface AssetLink {
  type: AssetLinkType
  target: string
  role: string | null
  sort_order: number
}

/** `AssetResource`: `links` when loaded (`assets.show`). */
export interface ShowroomAsset {
  id: string
  code: string
  labels: Record<string, string | null>
  filename: string
  mime_type: string | null
  size: number
  checksum: string | null
  url: string
  links?: AssetLink[]
  created_at: string | null
  updated_at: string | null
}

/** The file-type filters of v1's asset list (`assets.index?type=`), each a MIME pattern. */
export const ASSET_TYPE_FILTERS = [
  { value: 'image/*', key: 'images' },
  { value: 'application/pdf', key: 'pdf' },
  { value: 'video/*', key: 'videos' }
] as const

/** How the asset list shows: tiles, or a table. */
export type AssetView = 'grid' | 'list'

/** Whether the asset's file is an image the browser can show. */
export function isImage(asset: Pick<ShowroomAsset, 'mime_type'>): boolean {
  return (asset.mime_type ?? '').startsWith('image/')
}

/** What a tile shows for a file that is not an image: its extension, else its MIME type. */
export function fileKind(asset: Pick<ShowroomAsset, 'filename' | 'mime_type'>): string {
  const dot = asset.filename.lastIndexOf('.')
  const extension = dot > 0 ? asset.filename.slice(dot + 1) : ''

  return extension || asset.mime_type || ''
}

/** A file size in kilobytes with one decimal, as v1 showed it. */
export function formatSize(bytes: number): string {
  return `${(bytes / 1024).toFixed(1)} KB`
}

/** The layer path of an asset's page, by its code. */
export function assetPath(code: string): string {
  return `/showroom/assets/${encodeURIComponent(code)}`
}

/**
 * Where a link's target is found: a product model's page by its code; a
 * product (named by identifier) and an owner (named by code) through their
 * list searched for it, as their pages take an id.
 */
export function linkTargetPath(link: Pick<AssetLink, 'type' | 'target'>): string {
  switch (link.type) {
    case 'product_model':
      return productModelPath(link.target)
    case 'owner':
      return `/showroom/owners?search=${encodeURIComponent(link.target)}`
    default:
      return productSearchPath(link.target)
  }
}

/** The `labels[locale]` fields of a multipart form, for each label that is set. */
export function appendLabels(form: FormData, labels: Record<string, string | null>): void {
  for (const [locale, label] of Object.entries(labels)) {
    if (label !== null && label !== '') {
      form.append(`labels[${locale}]`, label)
    }
  }
}
