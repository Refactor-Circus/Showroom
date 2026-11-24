import { t as e } from "./CatalogActionError-BRUgojP9.js";
import { t } from "./CatalogPager-BwJ4cMnn.js";
import { t as n } from "./CatalogState-Bkly-MlG.js";
import { A as r, C as i, O as a, S as o, d as s, f as c, k as l, v as u } from "./catalog-DCoO3A8a.js";
import { AtriumIntegrationScreen as d, AtriumPageHeader as f, AtriumState as ee } from "@refactor-circus/atrium";
import { UButton as te, UCard as ne, UFormField as re, UInput as p, ULink as m, USelect as h, UTable as g } from "@refactor-circus/atrium/ui";
import { computed as _, createElementBlock as v, createElementVNode as y, createTextVNode as b, createVNode as x, defineComponent as S, openBlock as C, reactive as w, toDisplayString as T, unref as E, watch as D, withCtx as O, withModifiers as k } from "vue";
import { useI18n as A } from "vue-i18n";
import { useRoute as j, useRouter as M } from "vue-router";
// src/pages/product-models/index.vue?vue&type=script&setup=true&lang.ts
var N = {
	class: "space-y-4",
	"data-testid": "showroom-product-models"
}, P = { class: "space-y-4" }, F = { class: "font-semibold" }, I = { class: "space-y-4" }, L = ["data-testid"], R = { class: "font-mono text-xs" }, z = /* @__PURE__ */ S({
	__name: "index",
	setup(S) {
		let { t: z, locale: B } = A(), V = j(), H = M(), U = a(), W = _(() => {
			let e = o(V.query.search), t = o(V.query.family_variant), n = o(V.query.cursor);
			return {
				...e ? { search: e } : {},
				...t ? { family_variant: t } : {},
				...n ? { cursor: n } : {}
			};
		}), G = r(async (e) => i(await e.GET("/family-variants", { params: { query: { per_page: 100 } } }))), { client: K, data: q, pending: J, failed: Y, status: ie, reload: ae } = l(async (e) => i(await e.GET("/product-models", { params: { query: W.value } })), W), oe = _(() => q.value?.data ?? []), X = w({
			search: "",
			family_variant: ""
		}), Z = w({
			code: "",
			family_variant: ""
		}), Q = _(() => G.value.map((e) => ({
			label: c(e.labels, B.value, e.code),
			value: e.code
		}))), $ = _(() => [
			{
				accessorKey: "code",
				header: z("showroom.catalog.fields.code")
			},
			{
				accessorKey: "family_variant",
				header: z("showroom.catalog.fields.family_variant")
			},
			{
				accessorKey: "updated_at",
				header: z("showroom.catalog.fields.updated")
			}
		]);
		D(() => [V.query.search, V.query.family_variant], () => {
			X.search = o(V.query.search) ?? "", X.family_variant = o(V.query.family_variant) ?? "";
		}, { immediate: !0 });
		function se() {
			H.replace({ query: {
				...V.query,
				search: X.search || void 0,
				family_variant: X.family_variant || void 0,
				cursor: void 0
			} });
		}
		function ce() {
			H.replace({ query: {} });
		}
		async function le() {
			K && await U.run(async () => i(await K.POST("/product-models", { body: {
				code: Z.code.trim(),
				family_variant: Z.family_variant
			} }))) && (Z.code = "", Z.family_variant = "", await ae());
		}
		return (r, i) => {
			let a = f, o = p, c = re, l = h, _ = te, S = ne, w = ee, D = m, A = g, j = d;
			return C(), v("div", N, [x(a, { title: E(z)("showroom.catalog.product_models.title") }, null, 8, ["title"]), x(j, { "screen-key": "showroom.product-models" }, {
				default: O(() => [y("div", P, [
					x(S, { "data-testid": "showroom-product-model-new" }, {
						header: O(() => [y("h2", F, T(E(z)("showroom.catalog.product_models.new")), 1)]),
						default: O(() => [y("form", {
							class: "flex flex-wrap items-end gap-3",
							"data-testid": "showroom-product-model-create-form",
							onSubmit: k(le, ["prevent"])
						}, [
							x(c, {
								label: E(z)("showroom.catalog.fields.code"),
								error: E(U).error("code"),
								required: ""
							}, {
								default: O(() => [x(o, {
									modelValue: E(Z).code,
									"onUpdate:modelValue": i[0] ||= (e) => E(Z).code = e,
									required: "",
									class: "w-56",
									"data-testid": "showroom-product-model-create-code"
								}, null, 8, ["modelValue"])]),
								_: 1
							}, 8, ["label", "error"]),
							x(c, {
								label: E(z)("showroom.catalog.fields.family_variant"),
								error: E(U).error("family_variant"),
								required: ""
							}, {
								default: O(() => [x(l, {
									modelValue: E(Z).family_variant,
									"onUpdate:modelValue": i[1] ||= (e) => E(Z).family_variant = e,
									items: E(Q),
									placeholder: E(z)("showroom.catalog.product_models.choose_family_variant"),
									required: "",
									class: "w-64",
									"data-testid": "showroom-product-model-create-variant"
								}, null, 8, [
									"modelValue",
									"items",
									"placeholder"
								])]),
								_: 1
							}, 8, ["label", "error"]),
							x(_, {
								type: "submit",
								icon: "i-lucide-plus",
								loading: E(U).busy.value,
								"data-testid": "showroom-product-model-create-submit"
							}, {
								default: O(() => [b(T(E(z)("showroom.catalog.create")), 1)]),
								_: 1
							}, 8, ["loading"])
						], 32), x(e, {
							action: E(U),
							class: "mt-3"
						}, null, 8, ["action"])]),
						_: 1
					}),
					x(S, null, {
						default: O(() => [y("form", {
							class: "flex flex-wrap items-end gap-3",
							"data-testid": "showroom-product-models-filters",
							onSubmit: k(se, ["prevent"])
						}, [
							x(c, { label: E(z)("showroom.catalog.filters.search") }, {
								default: O(() => [x(o, {
									modelValue: E(X).search,
									"onUpdate:modelValue": i[2] ||= (e) => E(X).search = e,
									class: "w-56",
									"data-testid": "showroom-product-models-search"
								}, null, 8, ["modelValue"])]),
								_: 1
							}, 8, ["label"]),
							x(c, { label: E(z)("showroom.catalog.fields.family_variant") }, {
								default: O(() => [x(l, {
									modelValue: E(X).family_variant,
									"onUpdate:modelValue": i[3] ||= (e) => E(X).family_variant = e,
									items: [{
										label: E(z)("showroom.catalog.filters.all_family_variants"),
										value: null
									}, ...E(Q)],
									placeholder: E(z)("showroom.catalog.filters.all_family_variants"),
									class: "w-64",
									"data-testid": "showroom-product-models-variant"
								}, null, 8, [
									"modelValue",
									"items",
									"placeholder"
								])]),
								_: 1
							}, 8, ["label"]),
							x(_, {
								type: "submit",
								icon: "i-lucide-filter",
								"data-testid": "showroom-product-models-filter"
							}, {
								default: O(() => [b(T(E(z)("showroom.catalog.filters.filter")), 1)]),
								_: 1
							}),
							x(_, {
								variant: "ghost",
								icon: "i-lucide-x",
								"data-testid": "showroom-product-models-clear",
								onClick: ce
							}, {
								default: O(() => [b(T(E(z)("showroom.catalog.filters.clear")), 1)]),
								_: 1
							})
						], 32)]),
						_: 1
					}),
					x(n, {
						ready: !E(J) && E(q) !== null,
						failed: E(Y),
						status: E(ie)
					}, {
						default: O(() => [y("div", I, [x(A, {
							data: E(oe),
							columns: E($),
							"data-testid": "showroom-product-models-table"
						}, {
							empty: O(() => [x(w, {
								compact: "",
								title: E(z)("showroom.catalog.product_models.empty"),
								"data-testid": "showroom-product-models-empty"
							}, null, 8, ["title"])]),
							"code-cell": O(({ row: { original: e } }) => [y("span", { "data-testid": `showroom-product-model-row-${e.code}` }, [x(D, {
								to: E(u)(e.code),
								class: "font-mono font-medium hover:underline"
							}, {
								default: O(() => [b(T(e.code), 1)]),
								_: 2
							}, 1032, ["to"])], 8, L)]),
							"family_variant-cell": O(({ row: { original: e } }) => [y("span", R, T(e.family_variant ?? E(z)("showroom.catalog.none")), 1)]),
							"updated_at-cell": O(({ row: { original: e } }) => [b(T(E(s)(e.updated_at) ?? E(z)("showroom.catalog.none")), 1)]),
							_: 1
						}, 8, ["data", "columns"]), x(t, {
							next: E(q)?.meta?.next_cursor ?? null,
							prev: E(q)?.meta?.prev_cursor ?? null,
							testid: "showroom-product-models"
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
export { z as default };
