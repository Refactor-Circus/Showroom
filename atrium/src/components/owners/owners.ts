import type { CatalogAsset } from '../catalog/catalog'

/*
 * What the Showroom owner pages share (AC 54, 71): the owner and owner type
 * payloads, the chain rules form of an owner type, and the layer paths of
 * this area.
 *
 * Scramble types an owner loosely in Showroom's generated `client/src/schema.d.ts` (its
 * `chain` as `unknown[]`, `children` as an object, `external_ids` as an empty
 * record), so the shape is declared as Showroom's `OwnerResource` builds it.
 */

/** `OwnerResource`: what each endpoint loads is optional. Its chain, parent and children are owner codes. */
export interface ShowroomOwner {
  id: string
  code: string
  slug: string
  type?: string
  labels: Record<string, string | null>
  parent?: string | null
  depth: number
  enabled: boolean
  assets?: CatalogAsset[]
  /** Root first, this owner last */
  chain?: string[]
  children?: string[]
  children_count?: number
  products_count?: number
  product_models_count?: number
  external_ids?: Record<string, string>
  external_ids_synced_at?: Record<string, string | null>
  flags?: string[]
  effective_flags?: string[] | null
  created_at: string | null
  updated_at: string | null
}

/** `OwnerTypeResource`: `parent_types` is null when any type may be the parent. */
export interface ShowroomOwnerType {
  id: string
  code: string
  labels: Record<string, string | null>
  parent_types?: string[] | null
  can_be_root: boolean
  owns_products: boolean
  sort_order: number
  owners_count?: number
  created_at: string | null
  updated_at: string | null
}

/** An owner type's chain rules, as v1's `owner-types/rules` partial edited them. */
export interface OwnerTypeRules {
  any_parent: boolean
  parent_types: string[]
  can_be_root: boolean
  owns_products: boolean
}

/** The rules of `type`, or a new type's: any parent, a root that owns products. */
export function ownerTypeRules(type?: ShowroomOwnerType | null): OwnerTypeRules {
  return {
    any_parent: (type?.parent_types ?? null) === null,
    parent_types: [...(type?.parent_types ?? [])],
    can_be_root: type?.can_be_root ?? true,
    owns_products: type?.owns_products ?? true
  }
}

/**
 * What a create or save sends of `rules`: `parent_types` null for any
 * parent, else the ticked codes in the order of `all`.
 */
export function ownerTypeRulesBody(rules: OwnerTypeRules, all: readonly string[]): { parent_types: string[] | null, can_be_root: boolean, owns_products: boolean } {
  return {
    parent_types: rules.any_parent ? null : all.filter(code => rules.parent_types.includes(code)),
    can_be_root: rules.can_be_root,
    owns_products: rules.owns_products
  }
}

const segment = (value: string) => encodeURIComponent(value)

/** The layer path of an owner's page, by its id (Showroom's default lookup key). */
export function ownerPath(id: string): string {
  return `/showroom/owners/${segment(id)}`
}

/**
 * Where an owner named by its code is found: an owner's chain and children
 * name owners by code, and the owner page takes an id, so they link to the
 * owner list searched for it.
 */
export function ownerSearchPath(code: string): string {
  return `/showroom/owners?search=${segment(code)}`
}

/** The layer path of an owner type's page, by its code. */
export function ownerTypePath(code: string): string {
  return `/showroom/owner-types/${segment(code)}`
}
