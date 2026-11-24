import { t as e } from "./CatalogActionError-BRUgojP9.js";
import { t } from "./CatalogPager-BwJ4cMnn.js";
import { t as n } from "./CatalogState-Bkly-MlG.js";
import { C as r, D as i, O as a, S as o, a as s, f as c, k as l, l as ee } from "./catalog-DCoO3A8a.js";
import { AtriumIntegrationScreen as te } from "@refactor-circus/atrium";
import { UButton as ne, UCard as re, UFormField as ie, UInput as ae } from "@refactor-circus/atrium/ui";
import { Fragment as oe, computed as u, createElementBlock as d, createElementVNode as f, createTextVNode as p, createVNode as m, defineComponent as h, isRef as se, openBlock as g, reactive as ce, ref as le, renderList as ue, resolveComponent as de, toDisplayString as _, unref as v, watch as y, withCtx as b, withModifiers as x } from "vue";
import { useI18n as S } from "vue-i18n";
import { useRoute as C, useRouter as w } from "vue-router";
// src/pages/families/index.vue?vue&type=script&setup=true&lang.ts
var T = {
	class: "space-y-4",
	"data-testid": "showroom-families"
}, E = { class: "text-xl font-semibold" }, D = { class: "space-y-4" }, O = { class: "font-semibold" }, k = { class: "space-y-4" }, A = {
	key: 0,
	class: "atrium-empty",
	"data-testid": "showroom-families-empty"
}, j = {
	key: 1,
	class: "w-full text-left text-sm",
	"data-testid": "showroom-families-table"
}, M = { class: "text-muted" }, N = { class: "py-2 pr-3 font-medium" }, P = { class: "py-2 pr-3 font-medium" }, F = { class: "py-2 pr-3 font-medium" }, I = { class: "py-2 font-medium" }, L = { class: "divide-y divide-default" }, R = ["data-testid"], fe = { class: "py-2 pr-3" }, z = { class: "py-2 pr-3" }, B = { class: "py-2 pr-3 tabular-nums" }, V = { class: "py-2 font-mono text-xs" }, H = /* @__PURE__ */ h({
	__name: "index",
	setup(h) {
		let { t: H, locale: U } = S(), W = C(), G = w(), K = a(), q = u(() => {
			let e = o(W.query.search), t = o(W.query.cursor);
			return {
				...e ? { search: e } : {},
				...t ? { cursor: t } : {}
			};
		}), { client: J, data: Y, pending: pe, failed: me, status: he, reload: ge } = l(async (e) => r(await e.GET("/families", { params: { query: q.value } })), q), X = u(() => Y.value?.data ?? []), Z = le(""), Q = ce({
			code: "",
			label: "",
			sort_order: "0"
		});
		y(() => W.query.search, () => {
			Z.value = o(W.query.search) ?? "";
		}, { immediate: !0 });
		function $() {
			G.replace({ query: {
				...W.query,
				search: Z.value || void 0,
				cursor: void 0
			} });
		}
		function _e() {
			G.replace({ query: {} });
		}
		async function ve() {
			let e = s(Q.label);
			J && await K.run(async () => r(await J.POST("/families", { body: i({
				code: Q.code.trim(),
				...e ? { labels: { [U.value]: e } } : {},
				sort_order: Number(Q.sort_order) || 0
			}) }))) && (Q.code = "", Q.label = "", Q.sort_order = "0", await ge());
		}
		return (r, i) => {
			let a = ae, o = ie, s = ne, l = re, u = de("RouterLink"), h = te;
			return g(), d("div", T, [f("h1", E, _(v(H)("showroom.catalog.families.title")), 1), m(h, { "screen-key": "showroom.families" }, {
				default: b(() => [f("div", D, [
					m(l, { "data-testid": "showroom-family-new" }, {
						header: b(() => [f("h2", O, _(v(H)("showroom.catalog.families.new")), 1)]),
						default: b(() => [f("form", {
							class: "flex flex-wrap items-end gap-3",
							"data-testid": "showroom-family-create-form",
							onSubmit: x(ve, ["prevent"])
						}, [
							m(o, {
								label: v(H)("showroom.catalog.fields.code"),
								hint: v(H)("showroom.catalog.families.code_hint"),
								error: v(K).error("code"),
								required: ""
							}, {
								default: b(() => [m(a, {
									modelValue: v(Q).code,
									"onUpdate:modelValue": i[0] ||= (e) => v(Q).code = e,
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
							m(o, {
								label: v(H)("showroom.catalog.fields.label_in", { locale: v(U) }),
								error: v(K).error("labels")
							}, {
								default: b(() => [m(a, {
									modelValue: v(Q).label,
									"onUpdate:modelValue": i[1] ||= (e) => v(Q).label = e,
									class: "w-56",
									"data-testid": "showroom-family-create-label"
								}, null, 8, ["modelValue"])]),
								_: 1
							}, 8, ["label", "error"]),
							m(o, {
								label: v(H)("showroom.catalog.fields.sort_order"),
								error: v(K).error("sort_order")
							}, {
								default: b(() => [m(a, {
									modelValue: v(Q).sort_order,
									"onUpdate:modelValue": i[2] ||= (e) => v(Q).sort_order = e,
									type: "number",
									min: "0",
									class: "w-28",
									"data-testid": "showroom-family-create-sort"
								}, null, 8, ["modelValue"])]),
								_: 1
							}, 8, ["label", "error"]),
							m(s, {
								type: "submit",
								icon: "i-lucide-plus",
								loading: v(K).busy.value,
								"data-testid": "showroom-family-create-submit"
							}, {
								default: b(() => [p(_(v(H)("showroom.catalog.families.create")), 1)]),
								_: 1
							}, 8, ["loading"])
						], 32), m(e, {
							action: v(K),
							class: "mt-3"
						}, null, 8, ["action"])]),
						_: 1
					}),
					m(l, null, {
						default: b(() => [f("form", {
							class: "atrium-querybar",
							"data-testid": "showroom-families-filters",
							onSubmit: x($, ["prevent"])
						}, [
							m(o, { label: v(H)("showroom.catalog.filters.search") }, {
								default: b(() => [m(a, {
									modelValue: v(Z),
									"onUpdate:modelValue": i[3] ||= (e) => se(Z) ? Z.value = e : null,
									class: "w-56",
									"data-testid": "showroom-families-search"
								}, null, 8, ["modelValue"])]),
								_: 1
							}, 8, ["label"]),
							m(s, {
								type: "submit",
								icon: "i-lucide-filter",
								"data-testid": "showroom-families-filter"
							}, {
								default: b(() => [p(_(v(H)("showroom.catalog.filters.filter")), 1)]),
								_: 1
							}),
							m(s, {
								variant: "ghost",
								icon: "i-lucide-x",
								"data-testid": "showroom-families-clear",
								onClick: _e
							}, {
								default: b(() => [p(_(v(H)("showroom.catalog.filters.clear")), 1)]),
								_: 1
							})
						], 32)]),
						_: 1
					}),
					m(n, {
						ready: !v(pe) && v(Y) !== null,
						failed: v(me),
						status: v(he)
					}, {
						default: b(() => [f("div", k, [v(X).length === 0 ? (g(), d("p", A, _(v(H)("showroom.catalog.families.empty")), 1)) : (g(), d("table", j, [f("thead", M, [f("tr", null, [
							f("th", N, _(v(H)("showroom.catalog.fields.code")), 1),
							f("th", P, _(v(H)("showroom.catalog.fields.label")), 1),
							f("th", F, _(v(H)("showroom.catalog.families.attributes_count")), 1),
							f("th", I, _(v(H)("showroom.catalog.families.label_attribute")), 1)
						])]), f("tbody", L, [(g(!0), d(oe, null, ue(v(X), (e) => (g(), d("tr", {
							key: e.code,
							"data-testid": `showroom-family-row-${e.code}`
						}, [
							f("td", fe, [m(u, {
								to: v(ee)(e.code),
								class: "font-mono font-medium hover:underline"
							}, {
								default: b(() => [p(_(e.code), 1)]),
								_: 2
							}, 1032, ["to"])]),
							f("td", z, _(v(c)(e.labels, v(U), e.code)), 1),
							f("td", B, _(e.attributes_count ?? v(H)("showroom.catalog.none")), 1),
							f("td", V, _(e.label_attribute ?? v(H)("showroom.catalog.none")), 1)
						], 8, R))), 128))])])), m(t, {
							next: v(Y)?.meta?.next_cursor ?? null,
							prev: v(Y)?.meta?.prev_cursor ?? null,
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
