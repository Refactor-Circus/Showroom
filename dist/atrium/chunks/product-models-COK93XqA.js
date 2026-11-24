import { t as e } from "./CatalogActionError-BRUgojP9.js";
import { t } from "./CatalogPager-BwJ4cMnn.js";
import { t as n } from "./CatalogState-Bkly-MlG.js";
import { A as ee, C as r, O as i, S as a, d as o, f as s, k as c, v as te } from "./catalog-DCoO3A8a.js";
import { AtriumIntegrationScreen as ne } from "@refactor-circus/atrium";
import { UButton as re, UCard as ie, UFormField as ae, UInput as oe } from "@refactor-circus/atrium/ui";
import { Fragment as l, computed as u, createElementBlock as d, createElementVNode as f, createTextVNode as p, createVNode as m, defineComponent as h, openBlock as g, reactive as _, renderList as v, resolveComponent as y, toDisplayString as b, unref as x, vModelSelect as S, watch as se, withCtx as C, withDirectives as w, withModifiers as T } from "vue";
import { useI18n as ce } from "vue-i18n";
import { useRoute as le, useRouter as ue } from "vue-router";
// src/pages/product-models/index.vue?vue&type=script&setup=true&lang.ts
var de = {
	class: "space-y-4",
	"data-testid": "showroom-product-models"
}, E = { class: "text-xl font-semibold" }, D = { class: "space-y-4" }, O = { class: "font-semibold" }, k = {
	value: "",
	disabled: ""
}, A = ["value"], j = { value: "" }, M = ["value"], N = { class: "space-y-4" }, P = {
	key: 0,
	class: "atrium-empty",
	"data-testid": "showroom-product-models-empty"
}, F = {
	key: 1,
	class: "w-full text-left text-sm",
	"data-testid": "showroom-product-models-table"
}, I = { class: "text-muted" }, L = { class: "py-2 pr-3 font-medium" }, R = { class: "py-2 pr-3 font-medium" }, z = { class: "py-2 font-medium" }, B = { class: "divide-y divide-default" }, fe = ["data-testid"], V = { class: "py-2 pr-3" }, pe = { class: "py-2 pr-3 font-mono text-xs" }, me = { class: "py-2" }, H = /* @__PURE__ */ h({
	__name: "index",
	setup(h) {
		let { t: H, locale: U } = ce(), W = le(), G = ue(), K = i(), q = u(() => {
			let e = a(W.query.search), t = a(W.query.family_variant), n = a(W.query.cursor);
			return {
				...e ? { search: e } : {},
				...t ? { family_variant: t } : {},
				...n ? { cursor: n } : {}
			};
		}), J = ee(async (e) => r(await e.GET("/family-variants", { params: { query: { per_page: 100 } } }))), { client: Y, data: X, pending: he, failed: ge, status: _e, reload: ve } = c(async (e) => r(await e.GET("/product-models", { params: { query: q.value } })), q), Z = u(() => X.value?.data ?? []), Q = _({
			search: "",
			family_variant: ""
		}), $ = _({
			code: "",
			family_variant: ""
		});
		se(() => [W.query.search, W.query.family_variant], () => {
			Q.search = a(W.query.search) ?? "", Q.family_variant = a(W.query.family_variant) ?? "";
		}, { immediate: !0 });
		function ye() {
			G.replace({ query: {
				...W.query,
				search: Q.search || void 0,
				family_variant: Q.family_variant || void 0,
				cursor: void 0
			} });
		}
		function be() {
			G.replace({ query: {} });
		}
		async function xe() {
			Y && await K.run(async () => r(await Y.POST("/product-models", { body: {
				code: $.code.trim(),
				family_variant: $.family_variant
			} }))) && ($.code = "", $.family_variant = "", await ve());
		}
		return (ee, r) => {
			let i = oe, a = ae, c = re, u = ie, h = y("RouterLink"), _ = ne;
			return g(), d("div", de, [f("h1", E, b(x(H)("showroom.catalog.product_models.title")), 1), m(_, { "screen-key": "showroom.product-models" }, {
				default: C(() => [f("div", D, [
					m(u, { "data-testid": "showroom-product-model-new" }, {
						header: C(() => [f("h2", O, b(x(H)("showroom.catalog.product_models.new")), 1)]),
						default: C(() => [f("form", {
							class: "flex flex-wrap items-end gap-3",
							"data-testid": "showroom-product-model-create-form",
							onSubmit: T(xe, ["prevent"])
						}, [
							m(a, {
								label: x(H)("showroom.catalog.fields.code"),
								error: x(K).error("code"),
								required: ""
							}, {
								default: C(() => [m(i, {
									modelValue: x($).code,
									"onUpdate:modelValue": r[0] ||= (e) => x($).code = e,
									required: "",
									class: "w-56",
									"data-testid": "showroom-product-model-create-code"
								}, null, 8, ["modelValue"])]),
								_: 1
							}, 8, ["label", "error"]),
							m(a, {
								label: x(H)("showroom.catalog.fields.family_variant"),
								error: x(K).error("family_variant"),
								required: ""
							}, {
								default: C(() => [w(f("select", {
									"onUpdate:modelValue": r[1] ||= (e) => x($).family_variant = e,
									required: "",
									class: "w-64 rounded-md border border-default bg-default px-2.5 py-1.5 text-sm",
									"data-testid": "showroom-product-model-create-variant"
								}, [f("option", k, b(x(H)("showroom.catalog.product_models.choose_family_variant")), 1), (g(!0), d(l, null, v(x(J), (e) => (g(), d("option", {
									key: e.code,
									value: e.code
								}, b(x(s)(e.labels, x(U), e.code)), 9, A))), 128))], 512), [[S, x($).family_variant]])]),
								_: 1
							}, 8, ["label", "error"]),
							m(c, {
								type: "submit",
								icon: "i-lucide-plus",
								loading: x(K).busy.value,
								"data-testid": "showroom-product-model-create-submit"
							}, {
								default: C(() => [p(b(x(H)("showroom.catalog.create")), 1)]),
								_: 1
							}, 8, ["loading"])
						], 32), m(e, {
							action: x(K),
							class: "mt-3"
						}, null, 8, ["action"])]),
						_: 1
					}),
					m(u, null, {
						default: C(() => [f("form", {
							class: "atrium-querybar",
							"data-testid": "showroom-product-models-filters",
							onSubmit: T(ye, ["prevent"])
						}, [
							m(a, { label: x(H)("showroom.catalog.filters.search") }, {
								default: C(() => [m(i, {
									modelValue: x(Q).search,
									"onUpdate:modelValue": r[2] ||= (e) => x(Q).search = e,
									class: "w-56",
									"data-testid": "showroom-product-models-search"
								}, null, 8, ["modelValue"])]),
								_: 1
							}, 8, ["label"]),
							m(a, { label: x(H)("showroom.catalog.fields.family_variant") }, {
								default: C(() => [w(f("select", {
									"onUpdate:modelValue": r[3] ||= (e) => x(Q).family_variant = e,
									class: "w-64 rounded-md border border-default bg-default px-2.5 py-1.5 text-sm",
									"data-testid": "showroom-product-models-variant"
								}, [f("option", j, b(x(H)("showroom.catalog.filters.all_family_variants")), 1), (g(!0), d(l, null, v(x(J), (e) => (g(), d("option", {
									key: e.code,
									value: e.code
								}, b(x(s)(e.labels, x(U), e.code)), 9, M))), 128))], 512), [[S, x(Q).family_variant]])]),
								_: 1
							}, 8, ["label"]),
							m(c, {
								type: "submit",
								icon: "i-lucide-filter",
								"data-testid": "showroom-product-models-filter"
							}, {
								default: C(() => [p(b(x(H)("showroom.catalog.filters.filter")), 1)]),
								_: 1
							}),
							m(c, {
								variant: "ghost",
								icon: "i-lucide-x",
								"data-testid": "showroom-product-models-clear",
								onClick: be
							}, {
								default: C(() => [p(b(x(H)("showroom.catalog.filters.clear")), 1)]),
								_: 1
							})
						], 32)]),
						_: 1
					}),
					m(n, {
						ready: !x(he) && x(X) !== null,
						failed: x(ge),
						status: x(_e)
					}, {
						default: C(() => [f("div", N, [x(Z).length === 0 ? (g(), d("p", P, b(x(H)("showroom.catalog.product_models.empty")), 1)) : (g(), d("table", F, [f("thead", I, [f("tr", null, [
							f("th", L, b(x(H)("showroom.catalog.fields.code")), 1),
							f("th", R, b(x(H)("showroom.catalog.fields.family_variant")), 1),
							f("th", z, b(x(H)("showroom.catalog.fields.updated")), 1)
						])]), f("tbody", B, [(g(!0), d(l, null, v(x(Z), (e) => (g(), d("tr", {
							key: e.code,
							"data-testid": `showroom-product-model-row-${e.code}`
						}, [
							f("td", V, [m(h, {
								to: x(te)(e.code),
								class: "font-mono font-medium hover:underline"
							}, {
								default: C(() => [p(b(e.code), 1)]),
								_: 2
							}, 1032, ["to"])]),
							f("td", pe, b(e.family_variant ?? x(H)("showroom.catalog.none")), 1),
							f("td", me, b(x(o)(e.updated_at) ?? x(H)("showroom.catalog.none")), 1)
						], 8, fe))), 128))])])), m(t, {
							next: x(X)?.meta?.next_cursor ?? null,
							prev: x(X)?.meta?.prev_cursor ?? null,
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
export { H as default };
