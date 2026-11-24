import type { components } from '../../../client/src/schema'
import type { ShowroomAttribute, ShowroomAttributeOption, ShowroomFlag, ShowroomLocale } from '../../src/components/catalog/catalog'
import type {
  ShowroomAttributeGroup,
  ShowroomCategory,
  StructureAssociationType,
  StructureChannel
} from '../../src/components/structure/structure'

/*
 * The payloads of the Showroom structure suites (AC 54, 71): attributes and
 * their options, attribute groups, association types, categories, channels
 * with their locales, and flags. The page mounter, the call filter, the
 * confirmation stub and `ok()`/`invalid()` are the catalog suites' (`../catalog/support`).
 *
 * Where Showroom's generated `client/src/schema.d.ts` declares a resource as Showroom's
 * `toArray()` builds it (flags, locales, association types, options), each
 * payload satisfies both that schema and the shape the pages read. Where
 * Scramble types it loosely (an attribute's `options` and a group's
 * `attributes` as `string[]`, a channel's `locales` as an object, a
 * category's `children` as `unknown`), it satisfies the page's shape only.
 */

type Schemas = components['schemas']

const STAMPS = { created_at: '2026-11-01T10:00:00+00:00', updated_at: '2026-11-02T10:00:00+00:00' }

export const red = { id: '01JOPTRED', code: 'red', labels: { en: 'Red' }, sort_order: 0, ...STAMPS } satisfies ShowroomAttributeOption & Schemas['AttributeOptionResource']
export const blue = { id: '01JOPTBLUE', code: 'blue', labels: { en: 'Blue' }, sort_order: 1, ...STAMPS } satisfies ShowroomAttributeOption & Schemas['AttributeOptionResource']

export const color = {
  id: '01JATTRCOLOR',
  code: 'color',
  type: 'select',
  group: 'looks',
  labels: { en: 'Color', fr: 'Couleur' },
  is_unique: false,
  is_localizable: false,
  is_scopable: true,
  settings: {},
  sort_order: 1,
  options: [red, blue]
} satisfies ShowroomAttribute

export const name = {
  id: '01JATTRNAME',
  code: 'name',
  type: 'text',
  group: null,
  labels: { en: 'Name' },
  is_unique: true,
  is_localizable: true,
  is_scopable: false,
  settings: { max_length: 100 },
  sort_order: 0
} satisfies ShowroomAttribute

export const looks = {
  id: '01JGROUPLOOKS',
  code: 'looks',
  labels: { en: 'Looks', fr: 'Apparence' },
  sort_order: 2,
  attributes_count: 1,
  attributes: [color],
  ...STAMPS
} satisfies ShowroomAttributeGroup

export const specs = {
  id: '01JGROUPSPECS',
  code: 'specs',
  labels: { en: 'Specs' },
  sort_order: 3,
  attributes_count: 0,
  ...STAMPS
} satisfies ShowroomAttributeGroup

export const related = {
  id: '01JASSOCRELATED',
  code: 'related',
  labels: { en: 'Related' },
  is_two_way: true,
  is_quantified: false,
  associations_count: 4,
  ...STAMPS
} satisfies StructureAssociationType & Schemas['AssociationTypeResource']

export const bundle = {
  id: '01JASSOCBUNDLE',
  code: 'bundle',
  labels: { en: 'Bundle' },
  is_two_way: false,
  is_quantified: true,
  associations_count: 0,
  ...STAMPS
} satisfies StructureAssociationType & Schemas['AssociationTypeResource']

/** A tree root: `categories.index?roots=1` counts its children. */
export const master = {
  id: '01JCATMASTER',
  code: 'master',
  labels: { en: 'Master' },
  parent: null,
  sort_order: 0,
  depth: 0,
  children_count: 2,
  ...STAMPS
} satisfies ShowroomCategory

export const web = {
  ...master,
  id: '01JCATWEB',
  code: 'web_tree',
  labels: { en: 'Web tree' },
  children_count: 0
} satisfies ShowroomCategory

/** `categories.show` of a category one level down: its parent, children and counts. */
export const tops = {
  id: '01JCATTOPS',
  code: 'tops',
  labels: { en: 'Tops', fr: 'Hauts' },
  parent: 'master',
  sort_order: 1,
  depth: 1,
  children: ['shirts'],
  products_count: 3,
  product_models_count: 1,
  ...STAMPS
} satisfies ShowroomCategory

/** The branch beneath `tops` (`categories.index?under=tops`): each names its parent. */
export const shirts = { id: '01JCATSHIRTS', code: 'shirts', labels: { en: 'Shirts' }, parent: 'tops', sort_order: 0, depth: 2, children_count: 1, ...STAMPS } satisfies ShowroomCategory
export const oxfords = { id: '01JCATOXFORDS', code: 'oxfords', labels: { en: 'Oxfords' }, parent: 'shirts', sort_order: 0, depth: 3, children_count: 0, ...STAMPS } satisfies ShowroomCategory

export const english = { id: '01JLOCALEEN', code: 'en', labels: { en: 'English' }, ...STAMPS } satisfies ShowroomLocale & Schemas['LocaleResource']
export const french = { id: '01JLOCALEFR', code: 'fr', labels: { en: 'French' }, ...STAMPS } satisfies ShowroomLocale & Schemas['LocaleResource']
export const german = { id: '01JLOCALEDE', code: 'de', labels: {}, ...STAMPS } satisfies ShowroomLocale & Schemas['LocaleResource']

export const webshop = {
  id: '01JCHANNELWEB',
  code: 'webshop',
  labels: { en: 'Webshop', fr: 'Boutique' },
  locales: ['en', 'fr'],
  currencies: ['USD', 'EUR'],
  category_tree: 'master',
  ...STAMPS
} satisfies StructureChannel

export const print = {
  ...webshop,
  id: '01JCHANNELPRINT',
  code: 'print',
  labels: { en: 'Print' },
  locales: ['en'],
  currencies: [],
  category_tree: null
} satisfies StructureChannel

export const clearance = {
  id: '01JFLAGCLEARANCE',
  code: 'clearance',
  labels: { en: 'Clearance', fr: 'Soldes' },
  applies_to: ['product', 'owner'],
  sort_order: 2,
  ...STAMPS
} satisfies ShowroomFlag & Schemas['FlagResource']

export const restricted = {
  ...clearance,
  id: '01JFLAGRESTRICTED',
  code: 'restricted',
  labels: { en: 'Restricted' },
  applies_to: ['product'],
  sort_order: 3
} satisfies ShowroomFlag & Schemas['FlagResource']
