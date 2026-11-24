import { t as e } from "./CatalogActionError-BRUgojP9.js";
import { t } from "./CatalogPager-BwJ4cMnn.js";
import { t as n } from "./CatalogState-Bkly-MlG.js";
import { C as r, D as i, O as a, S as o, a as s, f as c, k as l, l as u } from "./catalog-DCoO3A8a.js";
import { AtriumIntegrationScreen as ee, AtriumPageHeader as d, AtriumState as f } from "@refactor-circus/atrium";
import { UButton as p, UCard as m, UFormField as h, UInput as g, ULink as _, UTable as te } from "@refactor-circus/atrium/ui";
import { computed as v, createElementBlock as y, createElementVNode as b, createTextVNode as x, createVNode as S, defineComponent as C, isRef as w, openBlock as T, reactive as E, ref as D, toDisplayString as O, unref as k, watch as A, withCtx as j, withModifiers as M } from "vue";
import { useI18n as N } from "vue-i18n";
import { useRoute as P, useRouter as F } from "vue-router";
// src/pages/families/index.vue?vue&type=script&setup=true&lang.ts
var I = {
	class: "space-y-4",
	"data-testid": "showroom-families"
}, L = { class: "space-y-4" }, R = { class: "font-semibold" }, z = { class: "space-y-4" }, B = ["data-testid"], V = { class: "font-mono text-xs" }, H = /* @__PURE__ */ C({
	__name: "index",
	setup(C) {
		let { t: H, locale: U } = N(), W = P(), G = F(), K = a(), q = v(() => {
			let e = o(W.query.search), t = o(W.query.cursor);
			return {
				...e ? { search: e } : {},
				...t ? { cursor: t } : {}
			};
		}), { client: J, data: Y, pending: X, failed: Z, status: ne, reload: re } = l(async (e) => r(await e.GET("/families", { params: { query: q.value } })), q), ie = v(() => Y.value?.data ?? []), ae = v(() => [
			{
				accessorKey: "code",
				header: H("showroom.catalog.fields.code")
			},
			{
				id: "label",
				header: H("showroom.catalog.fields.label")
			},
			{
				accessorKey: "attributes_count",
				header: H("showroom.catalog.families.attributes_count"),
				meta: { class: {
					th: "text-right",
					td: "text-right tabular-nums"
				} }
			},
			{
				accessorKey: "label_attribute",
				header: H("showroom.catalog.families.label_attribute")
			}
		]), Q = D(""), $ = E({
			code: "",
			label: "",
			sort_order: "0"
		});
		A(() => W.query.search, () => {
			Q.value = o(W.query.search) ?? "";
		}, { immediate: !0 });
		function oe() {
			G.replace({ query: {
				...W.query,
				search: Q.value || void 0,
				cursor: void 0
			} });
		}
		function se() {
			G.replace({ query: {} });
		}
		async function ce() {
			let e = s($.label);
			J && await K.run(async () => r(await J.POST("/families", { body: i({
				code: $.code.trim(),
				...e ? { labels: { [U.value]: e } } : {},
				sort_order: Number($.sort_order) || 0
			}) }))) && ($.code = "", $.label = "", $.sort_order = "0", await re());
		}
		return (r, i) => {
			let a = d, o = g, s = h, l = p, v = m, C = f, E = _, D = te, A = ee;
			return T(), y("div", I, [S(a, { title: k(H)("showroom.catalog.families.title") }, null, 8, ["title"]), S(A, { "screen-key": "showroom.families" }, {
				default: j(() => [b("div", L, [
					S(v, { "data-testid": "showroom-family-new" }, {
						header: j(() => [b("h2", R, O(k(H)("showroom.catalog.families.new")), 1)]),
						default: j(() => [b("form", {
							class: "flex flex-wrap items-start gap-3",
							"data-testid": "showroom-family-create-form",
							onSubmit: M(ce, ["prevent"])
						}, [
							S(s, {
								label: k(H)("showroom.catalog.fields.code"),
								hint: k(H)("showroom.catalog.families.code_hint"),
								error: k(K).error("code"),
								required: ""
							}, {
								default: j(() => [S(o, {
									modelValue: k($).code,
									"onUpdate:modelValue": i[0] ||= (e) => k($).code = e,
									required: "",
									class: "w-56",
									"data-testid": "showroom-family-create-code"
								}, null, 8, ["modelValue"])]),
								_: 1
							}, 8, [
								"label",
								"hint",
								"error"
							]),
							S(s, {
								label: k(H)("showroom.catalog.fields.label_in", { locale: k(U) }),
								error: k(K).error("labels")
							}, {
								default: j(() => [S(o, {
									modelValue: k($).label,
									"onUpdate:modelValue": i[1] ||= (e) => k($).label = e,
									class: "w-56",
									"data-testid": "showroom-family-create-label"
								}, null, 8, ["modelValue"])]),
								_: 1
							}, 8, ["label", "error"]),
							S(s, {
								label: k(H)("showroom.catalog.fields.sort_order"),
								error: k(K).error("sort_order")
							}, {
								default: j(() => [S(o, {
									modelValue: k($).sort_order,
									"onUpdate:modelValue": i[2] ||= (e) => k($).sort_order = e,
									type: "number",
									min: "0",
									class: "w-28",
									"data-testid": "showroom-family-create-sort"
								}, null, 8, ["modelValue"])]),
								_: 1
							}, 8, ["label", "error"]),
							S(l, {
								type: "submit",
								icon: "i-lucide-plus",
								loading: k(K).busy.value,
								class: "mt-field-label",
								"data-testid": "showroom-family-create-submit"
							}, {
								default: j(() => [x(O(k(H)("showroom.catalog.families.create")), 1)]),
								_: 1
							}, 8, ["loading"])
						], 32), S(e, {
							action: k(K),
							class: "mt-3"
						}, null, 8, ["action"])]),
						_: 1
					}),
					S(v, null, {
						default: j(() => [b("form", {
							class: "flex flex-wrap items-start gap-3",
							"data-testid": "showroom-families-filters",
							onSubmit: M(oe, ["prevent"])
						}, [
							S(s, { label: k(H)("showroom.catalog.filters.search") }, {
								default: j(() => [S(o, {
									modelValue: k(Q),
									"onUpdate:modelValue": i[3] ||= (e) => w(Q) ? Q.value = e : null,
									class: "w-56",
									"data-testid": "showroom-families-search"
								}, null, 8, ["modelValue"])]),
								_: 1
							}, 8, ["label"]),
							S(l, {
								type: "submit",
								icon: "i-lucide-filter",
								class: "mt-field-label",
								"data-testid": "showroom-families-filter"
							}, {
								default: j(() => [x(O(k(H)("showroom.catalog.filters.filter")), 1)]),
								_: 1
							}),
							S(l, {
								variant: "ghost",
								icon: "i-lucide-x",
								class: "mt-field-label",
								"data-testid": "showroom-families-clear",
								onClick: se
							}, {
								default: j(() => [x(O(k(H)("showroom.catalog.filters.clear")), 1)]),
								_: 1
							})
						], 32)]),
						_: 1
					}),
					S(n, {
						ready: !k(X) && k(Y) !== null,
						failed: k(Z),
						status: k(ne)
					}, {
						default: j(() => [b("div", z, [S(D, {
							data: k(ie),
							columns: k(ae),
							"data-testid": "showroom-families-table"
						}, {
							empty: j(() => [S(C, {
								compact: "",
								title: k(H)("showroom.catalog.families.empty"),
								"data-testid": "showroom-families-empty"
							}, null, 8, ["title"])]),
							"code-cell": j(({ row: { original: e } }) => [b("span", { "data-testid": `showroom-family-row-${e.code}` }, [S(E, {
								to: k(u)(e.code),
								class: "font-mono font-medium hover:underline"
							}, {
								default: j(() => [x(O(e.code), 1)]),
								_: 2
							}, 1032, ["to"])], 8, B)]),
							"label-cell": j(({ row: { original: e } }) => [x(O(k(c)(e.labels, k(U), e.code)), 1)]),
							"attributes_count-cell": j(({ row: { original: e } }) => [x(O(e.attributes_count ?? k(H)("showroom.catalog.none")), 1)]),
							"label_attribute-cell": j(({ row: { original: e } }) => [b("span", V, O(e.label_attribute ?? k(H)("showroom.catalog.none")), 1)]),
							_: 1
						}, 8, ["data", "columns"]), S(t, {
							next: k(Y)?.meta?.next_cursor ?? null,
							prev: k(Y)?.meta?.prev_cursor ?? null,
							testid: "showroom-families"
						}, null, 8, ["next", "prev"])])]),
						_: 1
					}, 8, [
						"ready",
						"failed",
						"status"
					])
				])]),
				_: 1
			})]);
		};
	}
});
export { H as default };
