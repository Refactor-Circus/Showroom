import { t as e } from "./CatalogActionError-BRUgojP9.js";
import { t } from "./CatalogPager-BwJ4cMnn.js";
import { t as n } from "./CatalogState-Bkly-MlG.js";
import { A as r, C as i, O as a, S as o, d as s, f as c, k as l, v as u } from "./catalog-DCoO3A8a.js";
import { AtriumIntegrationScreen as d, AtriumPageHeader as f, AtriumSelect as p, AtriumState as m } from "@refactor-circus/atrium";
import { UButton as ee, UCard as h, UFormField as g, UInput as _, ULink as v, USelect as te, UTable as ne } from "@refactor-circus/atrium/ui";
import { computed as y, createElementBlock as b, createElementVNode as x, createTextVNode as S, createVNode as C, defineComponent as w, openBlock as T, reactive as E, toDisplayString as D, unref as O, watch as k, withCtx as A, withModifiers as j } from "vue";
import { useI18n as M } from "vue-i18n";
import { useRoute as N, useRouter as P } from "vue-router";
// src/pages/product-models/index.vue?vue&type=script&setup=true&lang.ts
var F = {
	class: "space-y-4",
	"data-testid": "showroom-product-models"
}, I = { class: "space-y-4" }, L = { class: "font-semibold" }, R = { class: "space-y-4" }, z = ["data-testid"], B = { class: "font-mono text-xs" }, V = /* @__PURE__ */ w({
	__name: "index",
	setup(w) {
		let { t: V, locale: H } = M(), U = N(), W = P(), G = a(), K = y(() => {
			let e = o(U.query.search), t = o(U.query.family_variant), n = o(U.query.cursor);
			return {
				...e ? { search: e } : {},
				...t ? { family_variant: t } : {},
				...n ? { cursor: n } : {}
			};
		}), q = r(async (e) => i(await e.GET("/family-variants", { params: { query: { per_page: 100 } } }))), { client: J, data: Y, pending: X, failed: re, status: ie, reload: ae } = l(async (e) => i(await e.GET("/product-models", { params: { query: K.value } })), K), oe = y(() => Y.value?.data ?? []), Z = E({
			search: "",
			family_variant: ""
		}), Q = E({
			code: "",
			family_variant: ""
		}), $ = y(() => q.value.map((e) => ({
			label: c(e.labels, H.value, e.code),
			value: e.code
		}))), se = y(() => [
			{
				accessorKey: "code",
				header: V("showroom.catalog.fields.code")
			},
			{
				accessorKey: "family_variant",
				header: V("showroom.catalog.fields.family_variant")
			},
			{
				accessorKey: "updated_at",
				header: V("showroom.catalog.fields.updated")
			}
		]);
		k(() => [U.query.search, U.query.family_variant], () => {
			Z.search = o(U.query.search) ?? "", Z.family_variant = o(U.query.family_variant) ?? "";
		}, { immediate: !0 });
		function ce() {
			W.replace({ query: {
				...U.query,
				search: Z.search || void 0,
				family_variant: Z.family_variant || void 0,
				cursor: void 0
			} });
		}
		function le() {
			W.replace({ query: {} });
		}
		async function ue() {
			J && await G.run(async () => i(await J.POST("/product-models", { body: {
				code: Q.code.trim(),
				family_variant: Q.family_variant
			} }))) && (Q.code = "", Q.family_variant = "", await ae());
		}
		return (r, i) => {
			let a = f, o = _, c = g, l = te, y = ee, w = h, E = p, k = m, M = v, N = ne, P = d;
			return T(), b("div", F, [C(a, { title: O(V)("showroom.catalog.product_models.title") }, null, 8, ["title"]), C(P, { "screen-key": "showroom.product-models" }, {
				default: A(() => [x("div", I, [
					C(w, { "data-testid": "showroom-product-model-new" }, {
						header: A(() => [x("h2", L, D(O(V)("showroom.catalog.product_models.new")), 1)]),
						default: A(() => [x("form", {
							class: "flex flex-wrap items-start gap-3",
							"data-testid": "showroom-product-model-create-form",
							onSubmit: j(ue, ["prevent"])
						}, [
							C(c, {
								label: O(V)("showroom.catalog.fields.code"),
								error: O(G).error("code"),
								required: ""
							}, {
								default: A(() => [C(o, {
									modelValue: O(Q).code,
									"onUpdate:modelValue": i[0] ||= (e) => O(Q).code = e,
									required: "",
									class: "w-56",
									"data-testid": "showroom-product-model-create-code"
								}, null, 8, ["modelValue"])]),
								_: 1
							}, 8, ["label", "error"]),
							C(c, {
								label: O(V)("showroom.catalog.fields.family_variant"),
								error: O(G).error("family_variant"),
								required: ""
							}, {
								default: A(() => [C(l, {
									modelValue: O(Q).family_variant,
									"onUpdate:modelValue": i[1] ||= (e) => O(Q).family_variant = e,
									items: O($),
									placeholder: O(V)("showroom.catalog.product_models.choose_family_variant"),
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
							C(y, {
								type: "submit",
								icon: "i-lucide-plus",
								loading: O(G).busy.value,
								class: "mt-field-label",
								"data-testid": "showroom-product-model-create-submit"
							}, {
								default: A(() => [S(D(O(V)("showroom.catalog.create")), 1)]),
								_: 1
							}, 8, ["loading"])
						], 32), C(e, {
							action: O(G),
							class: "mt-3"
						}, null, 8, ["action"])]),
						_: 1
					}),
					C(w, null, {
						default: A(() => [x("form", {
							class: "flex flex-wrap items-start gap-3",
							"data-testid": "showroom-product-models-filters",
							onSubmit: j(ce, ["prevent"])
						}, [
							C(c, { label: O(V)("showroom.catalog.filters.search") }, {
								default: A(() => [C(o, {
									modelValue: O(Z).search,
									"onUpdate:modelValue": i[2] ||= (e) => O(Z).search = e,
									class: "w-56",
									"data-testid": "showroom-product-models-search"
								}, null, 8, ["modelValue"])]),
								_: 1
							}, 8, ["label"]),
							C(c, { label: O(V)("showroom.catalog.fields.family_variant") }, {
								default: A(() => [C(E, {
									modelValue: O(Z).family_variant,
									"onUpdate:modelValue": i[3] ||= (e) => O(Z).family_variant = e,
									items: O($),
									blank: O(V)("showroom.catalog.filters.all_family_variants"),
									"blank-value": null,
									class: "w-64",
									"data-testid": "showroom-product-models-variant"
								}, null, 8, [
									"modelValue",
									"items",
									"blank"
								])]),
								_: 1
							}, 8, ["label"]),
							C(y, {
								type: "submit",
								icon: "i-lucide-filter",
								class: "mt-field-label",
								"data-testid": "showroom-product-models-filter"
							}, {
								default: A(() => [S(D(O(V)("showroom.catalog.filters.filter")), 1)]),
								_: 1
							}),
							C(y, {
								variant: "ghost",
								icon: "i-lucide-x",
								class: "mt-field-label",
								"data-testid": "showroom-product-models-clear",
								onClick: le
							}, {
								default: A(() => [S(D(O(V)("showroom.catalog.filters.clear")), 1)]),
								_: 1
							})
						], 32)]),
						_: 1
					}),
					C(n, {
						ready: !O(X) && O(Y) !== null,
						failed: O(re),
						status: O(ie)
					}, {
						default: A(() => [x("div", R, [C(N, {
							data: O(oe),
							columns: O(se),
							"data-testid": "showroom-product-models-table"
						}, {
							empty: A(() => [C(k, {
								compact: "",
								title: O(V)("showroom.catalog.product_models.empty"),
								"data-testid": "showroom-product-models-empty"
							}, null, 8, ["title"])]),
							"code-cell": A(({ row: { original: e } }) => [x("span", { "data-testid": `showroom-product-model-row-${e.code}` }, [C(M, {
								to: O(u)(e.code),
								class: "font-mono font-medium hover:underline"
							}, {
								default: A(() => [S(D(e.code), 1)]),
								_: 2
							}, 1032, ["to"])], 8, z)]),
							"family_variant-cell": A(({ row: { original: e } }) => [x("span", B, D(e.family_variant ?? O(V)("showroom.catalog.none")), 1)]),
							"updated_at-cell": A(({ row: { original: e } }) => [S(D(O(s)(e.updated_at) ?? O(V)("showroom.catalog.none")), 1)]),
							_: 1
						}, 8, ["data", "columns"]), C(t, {
							next: O(Y)?.meta?.next_cursor ?? null,
							prev: O(Y)?.meta?.prev_cursor ?? null,
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
export { V as default };
