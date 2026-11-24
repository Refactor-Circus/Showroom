import { t as e } from "./CatalogActionError-BRUgojP9.js";
import { t } from "./CatalogPager-BwJ4cMnn.js";
import { t as n } from "./CatalogState-Bkly-MlG.js";
import { C as r, D as i, O as a, S as o, f as s, k as c, o as l } from "./catalog-DCoO3A8a.js";
import { g as u } from "./structure-DbqcM-iO.js";
import { AtriumIntegrationScreen as d } from "@refactor-circus/atrium";
import { UButton as f, UCard as ee, UFormField as te, UInput as p } from "@refactor-circus/atrium/ui";
import { Fragment as m, computed as h, createElementBlock as g, createElementVNode as _, createTextVNode as v, createVNode as y, defineComponent as b, openBlock as x, reactive as ne, renderList as re, resolveComponent as ie, toDisplayString as S, unref as C, withCtx as w, withModifiers as T } from "vue";
import { useI18n as E } from "vue-i18n";
import { useRoute as D } from "vue-router";
// src/pages/categories/index.vue?vue&type=script&setup=true&lang.ts
var O = {
	class: "space-y-4",
	"data-testid": "showroom-categories"
}, k = { class: "text-xl font-semibold" }, A = { class: "text-sm text-muted" }, j = { class: "space-y-4" }, M = { class: "font-semibold" }, N = { class: "space-y-4" }, P = {
	key: 0,
	class: "atrium-empty",
	"data-testid": "showroom-categories-empty"
}, F = {
	key: 1,
	class: "w-full text-left text-sm",
	"data-testid": "showroom-categories-table"
}, I = { class: "text-muted" }, ae = { class: "py-2 pr-3 font-medium" }, L = { class: "py-2 pr-3 font-medium" }, R = { class: "py-2 font-medium" }, z = { class: "divide-y divide-default" }, B = ["data-testid"], V = { class: "py-2 pr-3" }, H = { class: "py-2 pr-3" }, U = { class: "py-2 tabular-nums" }, W = /* @__PURE__ */ b({
	__name: "index",
	setup(b) {
		let { t: W, locale: G } = E(), K = D(), q = a(), J = h(() => {
			let e = o(K.query.cursor);
			return {
				roots: 1,
				...e ? { cursor: e } : {}
			};
		}), { client: Y, data: X, pending: oe, failed: se, status: ce, reload: le } = c(async (e) => r(await e.GET("/categories", { params: { query: i(J.value) } })), J), Z = h(() => X.value?.data ?? []), Q = ne({
			code: "",
			label: ""
		});
		async function $() {
			let e = {
				code: Q.code.trim(),
				...u(G.value, Q.label)
			};
			Y && await q.run(async () => r(await Y.POST("/categories", { body: i(e) }))) && (Q.code = "", Q.label = "", await le());
		}
		return (r, i) => {
			let a = p, o = te, c = f, u = ee, h = ie("RouterLink"), b = d;
			return x(), g("div", O, [_("div", null, [_("h1", k, S(C(W)("showroom.structure.categories.title")), 1), _("p", A, S(C(W)("showroom.structure.categories.description")), 1)]), y(b, { "screen-key": "showroom.categories" }, {
				default: w(() => [_("div", j, [y(u, { "data-testid": "showroom-tree-new" }, {
					header: w(() => [_("h2", M, S(C(W)("showroom.structure.categories.new_tree")), 1)]),
					default: w(() => [_("form", {
						class: "flex flex-wrap items-end gap-3",
						"data-testid": "showroom-tree-create-form",
						onSubmit: T($, ["prevent"])
					}, [
						y(o, {
							label: C(W)("showroom.catalog.fields.code"),
							hint: C(W)("showroom.structure.code_hint"),
							error: C(q).error("code"),
							required: ""
						}, {
							default: w(() => [y(a, {
								modelValue: C(Q).code,
								"onUpdate:modelValue": i[0] ||= (e) => C(Q).code = e,
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
						y(o, {
							label: C(W)("showroom.catalog.fields.label_in", { locale: C(G) }),
							error: C(q).error("labels")
						}, {
							default: w(() => [y(a, {
								modelValue: C(Q).label,
								"onUpdate:modelValue": i[1] ||= (e) => C(Q).label = e,
								class: "w-56",
								"data-testid": "showroom-tree-create-label"
							}, null, 8, ["modelValue"])]),
							_: 1
						}, 8, ["label", "error"]),
						y(c, {
							type: "submit",
							icon: "i-lucide-plus",
							loading: C(q).busy.value,
							"data-testid": "showroom-tree-create-submit"
						}, {
							default: w(() => [v(S(C(W)("showroom.catalog.create")), 1)]),
							_: 1
						}, 8, ["loading"])
					], 32), y(e, {
						action: C(q),
						class: "mt-3"
					}, null, 8, ["action"])]),
					_: 1
				}), y(n, {
					ready: !C(oe) && C(X) !== null,
					failed: C(se),
					status: C(ce)
				}, {
					default: w(() => [_("div", N, [C(Z).length === 0 ? (x(), g("p", P, S(C(W)("showroom.structure.categories.empty")), 1)) : (x(), g("table", F, [_("thead", I, [_("tr", null, [
						_("th", ae, S(C(W)("showroom.catalog.fields.code")), 1),
						_("th", L, S(C(W)("showroom.catalog.fields.label")), 1),
						_("th", R, S(C(W)("showroom.structure.categories.children")), 1)
					])]), _("tbody", z, [(x(!0), g(m, null, re(C(Z), (e) => (x(), g("tr", {
						key: e.code,
						"data-testid": `showroom-category-row-${e.code}`
					}, [
						_("td", V, [y(h, {
							to: C(l)(e.code),
							class: "font-mono font-medium hover:underline"
						}, {
							default: w(() => [v(S(e.code), 1)]),
							_: 2
						}, 1032, ["to"])]),
						_("td", H, S(C(s)(e.labels, C(G), e.code)), 1),
						_("td", U, S(e.children_count ?? C(W)("showroom.catalog.none")), 1)
					], 8, B))), 128))])])), y(t, {
						next: C(X)?.meta?.next_cursor ?? null,
						prev: C(X)?.meta?.prev_cursor ?? null,
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
export { W as default };
