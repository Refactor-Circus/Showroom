import { rawHtmlIn } from '@refactor-circus/atrium/testing'
import { expect, it } from 'vitest'

/*
 * Showroom's module composes Nuxt UI, as Atrium's own components do (Atrium
 * D-091 and D-093, D-022 here): no page or component renders a raw `<button>`,
 * `<input>`, `<select>`, `<textarea>`, `<table>`, `<details>` or `<a href>`.
 * No file is allowed one (an API link that may be external is <AtriumLink>).
 */
it('composes Nuxt UI, with no raw HTML controls (D-091)', () => {
  expect(rawHtmlIn('src')).toEqual([])
})
