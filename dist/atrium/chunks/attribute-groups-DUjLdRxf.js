import { t as e } from "./CatalogActionError-BRUgojP9.js";
import { t } from "./CatalogPager-BwJ4cMnn.js";
import { t as n } from "./CatalogState-Bkly-MlG.js";
import { C as r, D as i, O as a, S as o, f as ee, k as s } from "./catalog-DCoO3A8a.js";
import { b as c, g as l, i as u } from "./structure-DbqcM-iO.js";
import { AtriumIntegrationScreen as d } from "@refactor-circus/atrium";
import { UButton as f, UCard as p, UFormField as te, UInput as ne } from "@refactor-circus/atrium/ui";
import { Fragment as re, computed as m, createElementBlock as h, createElementVNode as g, createTextVNode as _, createVNode as v, defineComponent as y, openBlock as b, reactive as ie, renderList as ae, resolveComponent as x, toDisplayString as S, unref as C, withCtx as w, withModifiers as T } from "vue";
import { useI18n as E } from "vue-i18n";
import { useRoute as D } from "vue-router";
// src/pages/attribute-groups/index.vue?vue&type=script&setup=true&lang.ts
var O = {
	class: "space-y-4",
	"data-testid": "showroom-attribute-groups"
}, k = { class: "text-xl font-semibold" }, A = { class: "space-y-4" }, j = { class: "font-semibold" }, M = { class: "space-y-4" }, N = {
	key: 0,
	class: "atrium-empty",
	"data-testid": "showroom-attribute-groups-empty"
}, P = {
	key: 1,
	class: "w-full text-left text-sm",
	"data-testid": "showroom-attribute-groups-table"
}, F = { class: "text-muted" }, I = { class: "py-2 pr-3 font-medium" }, L = { class: "py-2 pr-3 font-medium" }, R = { class: "py-2 pr-3 font-medium" }, z = { class: "py-2 font-medium" }, oe = { class: "divide-y divide-default" }, B = ["data-testid"], V = { class: "py-2 pr-3" }, H = { class: "py-2 pr-3" }, U = { class: "py-2 pr-3 tabular-nums" }, W = { class: "py-2 tabular-nums" }, G = /* @__PURE__ */ y({
	__name: "index",
	setup(y) {
		let { t: G, locale: K } = E(), q = D(), J = a(), Y = m(() => {
			let e = o(q.query.cursor);
			return e ? { cursor: e } : {};
		}), { client: X, data: Z, pending: se, failed: ce, status: le, reload: ue } = s(async (e) => r(await e.GET("/attribute-groups", { params: { query: Y.value } })), Y), Q = m(() => Z.value?.data ?? []), $ = ie({
			code: "",
			label: "",
			sort_order: "0"
		});
		async function de() {
			let e = {
				code: $.code.trim(),
				...l(K.value, $.label),
				...c($.sort_order)
			};
			X && await J.run(async () => r(await X.POST("/attribute-groups", { body: i(e) }))) && ($.code = "", $.label = "", $.sort_order = "0", await ue());
		}
		return (r, i) => {
			let a = ne, o = te, s = f, c = p, l = x("RouterLink"), m = d;
			return b(), h("div", O, [g("h1", k, S(C(G)("showroom.structure.groups.title")), 1), v(m, { "screen-key": "showroom.attribute-groups" }, {
				default: w(() => [g("div", A, [v(c, { "data-testid": "showroom-group-new" }, {
					header: w(() => [g("h2", j, S(C(G)("showroom.structure.groups.new")), 1)]),
					default: w(() => [g("form", {
						class: "flex flex-wrap items-end gap-3",
						"data-testid": "showroom-group-create-form",
						onSubmit: T(de, ["prevent"])
					}, [
						v(o, {
							label: C(G)("showroom.catalog.fields.code"),
							hint: C(G)("showroom.structure.code_hint"),
							error: C(J).error("code"),
							required: ""
						}, {
							default: w(() => [v(a, {
								modelValue: C($).code,
								"onUpdate:modelValue": i[0] ||= (e) => C($).code = e,
								required: "",
								class: "w-56",
								"data-testid": "showroom-group-create-code"
							}, null, 8, ["modelValue"])]),
							_: 1
						}, 8, [
							"label",
							"hint",
							"error"
						]),
						v(o, {
							label: C(G)("showroom.catalog.fields.label_in", { locale: C(K) }),
							error: C(J).error("labels")
						}, {
							default: w(() => [v(a, {
								modelValue: C($).label,
								"onUpdate:modelValue": i[1] ||= (e) => C($).label = e,
								class: "w-56",
								"data-testid": "showroom-group-create-label"
							}, null, 8, ["modelValue"])]),
							_: 1
						}, 8, ["label", "error"]),
						v(o, {
							label: C(G)("showroom.catalog.fields.sort_order"),
							error: C(J).error("sort_order")
						}, {
							default: w(() => [v(a, {
								modelValue: C($).sort_order,
								"onUpdate:modelValue": i[2] ||= (e) => C($).sort_order = e,
								type: "number",
								min: "0",
								class: "w-28",
								"data-testid": "showroom-group-create-sort"
							}, null, 8, ["modelValue"])]),
							_: 1
						}, 8, ["label", "error"]),
						v(s, {
							type: "submit",
							icon: "i-lucide-plus",
							loading: C(J).busy.value,
							"data-testid": "showroom-group-create-submit"
						}, {
							default: w(() => [_(S(C(G)("showroom.structure.groups.create")), 1)]),
							_: 1
						}, 8, ["loading"])
					], 32), v(e, {
						action: C(J),
						class: "mt-3"
					}, null, 8, ["action"])]),
					_: 1
				}), v(n, {
					ready: !C(se) && C(Z) !== null,
					failed: C(ce),
					status: C(le)
				}, {
					default: w(() => [g("div", M, [C(Q).length === 0 ? (b(), h("p", N, S(C(G)("showroom.structure.groups.empty")), 1)) : (b(), h("table", P, [g("thead", F, [g("tr", null, [
						g("th", I, S(C(G)("showroom.catalog.fields.code")), 1),
						g("th", L, S(C(G)("showroom.catalog.fields.label")), 1),
						g("th", R, S(C(G)("showroom.structure.groups.attributes_count")), 1),
						g("th", z, S(C(G)("showroom.catalog.fields.sort_order")), 1)
					])]), g("tbody", oe, [(b(!0), h(re, null, ae(C(Q), (e) => (b(), h("tr", {
						key: e.code,
						"data-testid": `showroom-group-row-${e.code}`
					}, [
						g("td", V, [v(l, {
							to: C(u)(e.code),
							class: "font-mono font-medium hover:underline"
						}, {
							default: w(() => [_(S(e.code), 1)]),
							_: 2
						}, 1032, ["to"])]),
						g("td", H, S(C(ee)(e.labels, C(K), e.code)), 1),
						g("td", U, S(e.attributes_count ?? C(G)("showroom.catalog.none")), 1),
						g("td", W, S(e.sort_order), 1)
					], 8, B))), 128))])])), v(t, {
						next: C(Z)?.meta?.next_cursor ?? null,
						prev: C(Z)?.meta?.prev_cursor ?? null,
						testid: "showroom-attribute-groups"
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
export { G as default };
