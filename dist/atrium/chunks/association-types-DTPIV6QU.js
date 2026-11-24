import { t as e } from "./CatalogActionError-BRUgojP9.js";
import { t } from "./CatalogPager-BwJ4cMnn.js";
import { t as ee } from "./CatalogState-Bkly-MlG.js";
import { C as n, D as r, O as i, S as a, f as te, k as o } from "./catalog-DCoO3A8a.js";
import { g as s } from "./structure-DbqcM-iO.js";
import { AtriumIntegrationScreen as ne } from "@refactor-circus/atrium";
import { UBadge as re, UButton as ie, UCard as ae, UFormField as oe, UInput as se } from "@refactor-circus/atrium/ui";
import { Fragment as ce, computed as c, createBlock as l, createCommentVNode as u, createElementBlock as d, createElementVNode as f, createTextVNode as p, createVNode as m, defineComponent as h, openBlock as g, reactive as _, renderList as le, toDisplayString as v, unref as y, vModelCheckbox as b, withCtx as x, withDirectives as S, withModifiers as C } from "vue";
import { useI18n as w } from "vue-i18n";
import { useRoute as T } from "vue-router";
// src/pages/association-types/index.vue?vue&type=script&setup=true&lang.ts
var E = {
	class: "space-y-4",
	"data-testid": "showroom-association-types"
}, D = { class: "text-xl font-semibold" }, O = { class: "text-sm text-muted" }, k = { class: "space-y-4" }, A = { class: "font-semibold" }, j = { class: "flex h-9 items-center gap-2 text-sm" }, M = { class: "flex h-9 items-center gap-2 text-sm" }, N = { class: "space-y-4" }, P = {
	key: 0,
	class: "atrium-empty",
	"data-testid": "showroom-association-types-empty"
}, F = {
	key: 1,
	class: "w-full text-left text-sm",
	"data-testid": "showroom-association-types-table"
}, I = { class: "text-muted" }, L = { class: "py-2 pr-3 font-medium" }, R = { class: "py-2 pr-3 font-medium" }, z = { class: "py-2 pr-3 font-medium" }, B = { class: "py-2 pr-3 font-medium" }, V = { class: "divide-y divide-default" }, ue = ["data-testid"], H = { class: "py-2 pr-3 font-mono" }, U = { class: "py-2 pr-3" }, de = { class: "py-2 pr-3" }, fe = { class: "flex flex-wrap gap-1" }, pe = { class: "py-2 pr-3 tabular-nums" }, me = { class: "py-2" }, W = /* @__PURE__ */ h({
	__name: "index",
	setup(h) {
		let { t: W, locale: G } = w(), he = T(), K = i(), q = i(), J = c(() => {
			let e = a(he.query.cursor);
			return e ? { cursor: e } : {};
		}), { client: Y, data: X, pending: ge, failed: _e, status: ve, reload: Z } = o(async (e) => n(await e.GET("/association-types", { params: { query: J.value } })), J), Q = c(() => X.value?.data ?? []), $ = _({
			code: "",
			label: "",
			is_two_way: !1,
			is_quantified: !1
		});
		async function ye() {
			let e = {
				code: $.code.trim(),
				...s(G.value, $.label),
				is_two_way: $.is_two_way,
				is_quantified: $.is_quantified
			};
			Y && await K.run(async () => n(await Y.POST("/association-types", { body: r(e) }))) && ($.code = "", $.label = "", $.is_two_way = !1, $.is_quantified = !1, await Z());
		}
		async function be(e) {
			Y && window.confirm(W("showroom.structure.association_types.confirm_delete", { code: e })) && await q.run(async () => n(await Y.DELETE("/association-types/{associationType}", { params: { path: { associationType: e } } }))) && await Z();
		}
		return (n, r) => {
			let i = se, a = oe, o = ie, s = ae, c = re, h = ne;
			return g(), d("div", E, [f("div", null, [f("h1", D, v(y(W)("showroom.structure.association_types.title")), 1), f("p", O, v(y(W)("showroom.structure.association_types.description")), 1)]), m(h, { "screen-key": "showroom.association-types" }, {
				default: x(() => [f("div", k, [
					m(s, { "data-testid": "showroom-association-type-new" }, {
						header: x(() => [f("h2", A, v(y(W)("showroom.structure.association_types.new")), 1)]),
						default: x(() => [f("form", {
							class: "flex flex-wrap items-end gap-3",
							"data-testid": "showroom-association-type-create-form",
							onSubmit: C(ye, ["prevent"])
						}, [
							m(a, {
								label: y(W)("showroom.catalog.fields.code"),
								hint: y(W)("showroom.structure.code_hint"),
								error: y(K).error("code"),
								required: ""
							}, {
								default: x(() => [m(i, {
									modelValue: y($).code,
									"onUpdate:modelValue": r[0] ||= (e) => y($).code = e,
									required: "",
									class: "w-56",
									"data-testid": "showroom-association-type-create-code"
								}, null, 8, ["modelValue"])]),
								_: 1
							}, 8, [
								"label",
								"hint",
								"error"
							]),
							m(a, {
								label: y(W)("showroom.catalog.fields.label_in", { locale: y(G) }),
								error: y(K).error("labels")
							}, {
								default: x(() => [m(i, {
									modelValue: y($).label,
									"onUpdate:modelValue": r[1] ||= (e) => y($).label = e,
									class: "w-56",
									"data-testid": "showroom-association-type-create-label"
								}, null, 8, ["modelValue"])]),
								_: 1
							}, 8, ["label", "error"]),
							f("label", j, [S(f("input", {
								"onUpdate:modelValue": r[2] ||= (e) => y($).is_two_way = e,
								type: "checkbox",
								"data-testid": "showroom-association-type-create-two-way"
							}, null, 512), [[b, y($).is_two_way]]), p(" " + v(y(W)("showroom.structure.association_types.two_way")), 1)]),
							f("label", M, [S(f("input", {
								"onUpdate:modelValue": r[3] ||= (e) => y($).is_quantified = e,
								type: "checkbox",
								"data-testid": "showroom-association-type-create-quantified"
							}, null, 512), [[b, y($).is_quantified]]), p(" " + v(y(W)("showroom.structure.association_types.quantified")), 1)]),
							m(o, {
								type: "submit",
								icon: "i-lucide-plus",
								loading: y(K).busy.value,
								"data-testid": "showroom-association-type-create-submit"
							}, {
								default: x(() => [p(v(y(W)("showroom.catalog.create")), 1)]),
								_: 1
							}, 8, ["loading"])
						], 32), m(e, {
							action: y(K),
							class: "mt-3"
						}, null, 8, ["action"])]),
						_: 1
					}),
					m(e, { action: y(q) }, null, 8, ["action"]),
					m(ee, {
						ready: !y(ge) && y(X) !== null,
						failed: y(_e),
						status: y(ve)
					}, {
						default: x(() => [f("div", N, [y(Q).length === 0 ? (g(), d("p", P, v(y(W)("showroom.structure.association_types.empty")), 1)) : (g(), d("table", F, [f("thead", I, [f("tr", null, [
							f("th", L, v(y(W)("showroom.catalog.fields.code")), 1),
							f("th", R, v(y(W)("showroom.catalog.fields.label")), 1),
							f("th", z, v(y(W)("showroom.structure.attributes.switches")), 1),
							f("th", B, v(y(W)("showroom.structure.association_types.associations")), 1),
							r[4] ||= f("th", { class: "py-2 font-medium" }, null, -1)
						])]), f("tbody", V, [(g(!0), d(ce, null, le(y(Q), (e) => (g(), d("tr", {
							key: e.code,
							"data-testid": `showroom-association-type-row-${e.code}`
						}, [
							f("td", H, v(e.code), 1),
							f("td", U, v(y(te)(e.labels, y(G), e.code)), 1),
							f("td", de, [f("span", fe, [e.is_two_way ? (g(), l(c, {
								key: 0,
								color: "neutral",
								variant: "outline"
							}, {
								default: x(() => [p(v(y(W)("showroom.structure.association_types.two_way")), 1)]),
								_: 1
							})) : u("", !0), e.is_quantified ? (g(), l(c, {
								key: 1,
								color: "neutral",
								variant: "outline"
							}, {
								default: x(() => [p(v(y(W)("showroom.structure.association_types.quantified")), 1)]),
								_: 1
							})) : u("", !0)])]),
							f("td", pe, v(e.associations_count ?? y(W)("showroom.catalog.none")), 1),
							f("td", me, [m(o, {
								variant: "ghost",
								size: "sm",
								icon: "i-lucide-trash-2",
								"aria-label": y(W)("showroom.catalog.delete"),
								loading: y(q).busy.value,
								"data-testid": `showroom-association-type-delete-${e.code}`,
								onClick: (t) => be(e.code)
							}, null, 8, [
								"aria-label",
								"loading",
								"data-testid",
								"onClick"
							])])
						], 8, ue))), 128))])])), m(t, {
							next: y(X)?.meta?.next_cursor ?? null,
							prev: y(X)?.meta?.prev_cursor ?? null,
							testid: "showroom-association-types"
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
export { W as default };
