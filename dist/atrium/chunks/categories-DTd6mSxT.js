import { t as e } from "./CatalogActionError-BRUgojP9.js";
import { t } from "./CatalogPager-BwJ4cMnn.js";
import { t as n } from "./CatalogState-Bkly-MlG.js";
import { C as r, D as i, O as a, S as o, f as s, k as c, o as l } from "./catalog-DCoO3A8a.js";
import { g as u } from "./structure-DbqcM-iO.js";
import { AtriumIntegrationScreen as d, AtriumPageHeader as f, AtriumState as p } from "@refactor-circus/atrium";
import { UButton as m, UCard as h, UFormField as g, UInput as _, ULink as v, UTable as y } from "@refactor-circus/atrium/ui";
import { computed as b, createElementBlock as x, createElementVNode as S, createTextVNode as C, createVNode as w, defineComponent as T, openBlock as E, reactive as D, toDisplayString as O, unref as k, withCtx as A, withModifiers as j } from "vue";
import { useI18n as M } from "vue-i18n";
import { useRoute as N } from "vue-router";
// src/pages/categories/index.vue?vue&type=script&setup=true&lang.ts
var P = {
	class: "space-y-4",
	"data-testid": "showroom-categories"
}, F = { class: "space-y-4" }, I = { class: "font-semibold" }, L = { class: "space-y-4" }, R = ["data-testid"], z = { class: "tabular-nums" }, B = /* @__PURE__ */ T({
	__name: "index",
	setup(T) {
		let { t: B, locale: V } = M(), H = N(), U = a(), W = b(() => {
			let e = o(H.query.cursor);
			return {
				roots: 1,
				...e ? { cursor: e } : {}
			};
		}), { client: G, data: K, pending: q, failed: J, status: Y, reload: X } = c(async (e) => r(await e.GET("/categories", { params: { query: i(W.value) } })), W), Z = b(() => K.value?.data ?? []), Q = D({
			code: "",
			label: ""
		}), $ = b(() => [
			{
				accessorKey: "code",
				header: B("showroom.catalog.fields.code")
			},
			{
				id: "label",
				header: B("showroom.catalog.fields.label")
			},
			{
				accessorKey: "children_count",
				header: B("showroom.structure.categories.children"),
				meta: { class: {
					th: "text-right",
					td: "text-right"
				} }
			}
		]);
		async function ee() {
			let e = {
				code: Q.code.trim(),
				...u(V.value, Q.label)
			};
			G && await U.run(async () => r(await G.POST("/categories", { body: i(e) }))) && (Q.code = "", Q.label = "", await X());
		}
		return (r, i) => {
			let a = f, o = _, c = g, u = m, b = h, T = p, D = v, M = y, N = d;
			return E(), x("div", P, [w(a, {
				title: k(B)("showroom.structure.categories.title"),
				description: k(B)("showroom.structure.categories.description")
			}, null, 8, ["title", "description"]), w(N, { "screen-key": "showroom.categories" }, {
				default: A(() => [S("div", F, [w(b, { "data-testid": "showroom-tree-new" }, {
					header: A(() => [S("h2", I, O(k(B)("showroom.structure.categories.new_tree")), 1)]),
					default: A(() => [S("form", {
						class: "flex flex-wrap items-end gap-3",
						"data-testid": "showroom-tree-create-form",
						onSubmit: j(ee, ["prevent"])
					}, [
						w(c, {
							label: k(B)("showroom.catalog.fields.code"),
							hint: k(B)("showroom.structure.code_hint"),
							error: k(U).error("code"),
							required: ""
						}, {
							default: A(() => [w(o, {
								modelValue: k(Q).code,
								"onUpdate:modelValue": i[0] ||= (e) => k(Q).code = e,
								required: "",
								class: "w-56",
								"data-testid": "showroom-tree-create-code"
							}, null, 8, ["modelValue"])]),
							_: 1
						}, 8, [
							"label",
							"hint",
							"error"
						]),
						w(c, {
							label: k(B)("showroom.catalog.fields.label_in", { locale: k(V) }),
							error: k(U).error("labels")
						}, {
							default: A(() => [w(o, {
								modelValue: k(Q).label,
								"onUpdate:modelValue": i[1] ||= (e) => k(Q).label = e,
								class: "w-56",
								"data-testid": "showroom-tree-create-label"
							}, null, 8, ["modelValue"])]),
							_: 1
						}, 8, ["label", "error"]),
						w(u, {
							type: "submit",
							icon: "i-lucide-plus",
							loading: k(U).busy.value,
							"data-testid": "showroom-tree-create-submit"
						}, {
							default: A(() => [C(O(k(B)("showroom.catalog.create")), 1)]),
							_: 1
						}, 8, ["loading"])
					], 32), w(e, {
						action: k(U),
						class: "mt-3"
					}, null, 8, ["action"])]),
					_: 1
				}), w(n, {
					ready: !k(q) && k(K) !== null,
					failed: k(J),
					status: k(Y)
				}, {
					default: A(() => [S("div", L, [w(M, {
						data: k(Z),
						columns: k($),
						"data-testid": "showroom-categories-table"
					}, {
						empty: A(() => [w(T, {
							compact: "",
							title: k(B)("showroom.structure.categories.empty"),
							"data-testid": "showroom-categories-empty"
						}, null, 8, ["title"])]),
						"code-cell": A(({ row: { original: e } }) => [S("span", { "data-testid": `showroom-category-row-${e.code}` }, [w(D, {
							to: k(l)(e.code),
							class: "font-mono font-medium hover:underline"
						}, {
							default: A(() => [C(O(e.code), 1)]),
							_: 2
						}, 1032, ["to"])], 8, R)]),
						"label-cell": A(({ row: { original: e } }) => [C(O(k(s)(e.labels, k(V), e.code)), 1)]),
						"children_count-cell": A(({ row: { original: e } }) => [S("span", z, O(e.children_count ?? k(B)("showroom.catalog.none")), 1)]),
						_: 1
					}, 8, ["data", "columns"]), w(t, {
						next: k(K)?.meta?.next_cursor ?? null,
						prev: k(K)?.meta?.prev_cursor ?? null,
						testid: "showroom-categories"
					}, null, 8, ["next", "prev"])])]),
					_: 1
				}, 8, [
					"ready",
					"failed",
					"status"
				])])]),
				_: 1
			})]);
		};
	}
});
export { B as default };
