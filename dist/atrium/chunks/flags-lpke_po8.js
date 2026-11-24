import { t as e } from "./CatalogActionError-BRUgojP9.js";
import { t } from "./CatalogPager-BwJ4cMnn.js";
import { t as ee } from "./CatalogState-Bkly-MlG.js";
import { C as n, D as r, O as i, S as a, f as o, k as s } from "./catalog-DCoO3A8a.js";
import { t as te } from "./FlagFields-CeNVNtmX.js";
import { b as c, d as ne, g as l, r as u } from "./structure-DbqcM-iO.js";
import { AtriumIntegrationScreen as re } from "@refactor-circus/atrium";
import { UBadge as ie, UButton as ae, UCard as d, UFormField as f, UInput as p } from "@refactor-circus/atrium/ui";
import { Fragment as m, computed as h, createBlock as oe, createElementBlock as g, createElementVNode as _, createTextVNode as v, createVNode as y, defineComponent as se, isRef as ce, openBlock as b, reactive as le, ref as ue, renderList as x, resolveComponent as S, toDisplayString as C, unref as w, withCtx as T, withModifiers as E } from "vue";
import { useI18n as D } from "vue-i18n";
import { useRoute as O } from "vue-router";
// src/pages/flags/index.vue?vue&type=script&setup=true&lang.ts
var k = {
	class: "space-y-4",
	"data-testid": "showroom-flags"
}, A = { class: "text-xl font-semibold" }, j = { class: "text-sm text-muted" }, M = { class: "space-y-4" }, N = { class: "font-semibold" }, P = { class: "flex flex-wrap items-end gap-3" }, F = { class: "space-y-4" }, I = {
	key: 0,
	class: "atrium-empty",
	"data-testid": "showroom-flags-empty"
}, L = {
	key: 1,
	class: "w-full text-left text-sm",
	"data-testid": "showroom-flags-table"
}, R = { class: "text-muted" }, z = { class: "py-2 pr-3 font-medium" }, de = { class: "py-2 pr-3 font-medium" }, B = { class: "py-2 pr-3 font-medium" }, V = { class: "py-2 font-medium" }, H = { class: "divide-y divide-default" }, fe = ["data-testid"], pe = { class: "py-2 pr-3" }, me = { class: "py-2 pr-3" }, he = { class: "py-2 pr-3" }, ge = { class: "flex flex-wrap gap-1" }, _e = { class: "py-2 tabular-nums" }, U = /* @__PURE__ */ se({
	__name: "index",
	setup(se) {
		let { t: U, locale: W } = D(), ve = O(), G = i(), K = h(() => {
			let e = a(ve.query.cursor);
			return e ? { cursor: e } : {};
		}), { client: q, data: J, pending: ye, failed: be, status: Y, reload: xe } = s(async (e) => n(await e.GET("/flags", { params: { query: K.value } })), K), X = h(() => J.value?.data ?? []), Z = le({
			code: "",
			label: ""
		});
		function Q() {
			return {
				applies_to: [...u],
				sort_order: ""
			};
		}
		let $ = ue(Q());
		async function Se() {
			let e = {
				code: Z.code.trim(),
				...l(W.value, Z.label),
				applies_to: [...$.value.applies_to],
				...c($.value.sort_order)
			};
			q && await G.run(async () => n(await q.POST("/flags", { body: r(e) }))) && (Z.code = "", Z.label = "", $.value = Q(), await xe());
		}
		return (n, r) => {
			let i = p, a = f, s = ae, c = d, l = S("RouterLink"), u = ie, h = re;
			return b(), g("div", k, [_("div", null, [_("h1", A, C(w(U)("showroom.structure.flags.title")), 1), _("p", j, C(w(U)("showroom.structure.flags.description")), 1)]), y(h, { "screen-key": "showroom.flags" }, {
				default: T(() => [_("div", M, [y(c, { "data-testid": "showroom-flag-new" }, {
					header: T(() => [_("h2", N, C(w(U)("showroom.structure.flags.new")), 1)]),
					default: T(() => [_("form", {
						class: "flex flex-col gap-4",
						"data-testid": "showroom-flag-create-form",
						onSubmit: E(Se, ["prevent"])
					}, [
						_("div", P, [y(a, {
							label: w(U)("showroom.catalog.fields.code"),
							hint: w(U)("showroom.structure.code_hint"),
							error: w(G).error("code"),
							required: ""
						}, {
							default: T(() => [y(i, {
								modelValue: w(Z).code,
								"onUpdate:modelValue": r[0] ||= (e) => w(Z).code = e,
								required: "",
								class: "w-56",
								"data-testid": "showroom-flag-create-code"
							}, null, 8, ["modelValue"])]),
							_: 1
						}, 8, [
							"label",
							"hint",
							"error"
						]), y(a, {
							label: w(U)("showroom.catalog.fields.label_in", { locale: w(W) }),
							error: w(G).error("labels")
						}, {
							default: T(() => [y(i, {
								modelValue: w(Z).label,
								"onUpdate:modelValue": r[1] ||= (e) => w(Z).label = e,
								class: "w-56",
								"data-testid": "showroom-flag-create-label"
							}, null, 8, ["modelValue"])]),
							_: 1
						}, 8, ["label", "error"])]),
						y(te, {
							modelValue: w($),
							"onUpdate:modelValue": r[2] ||= (e) => ce($) ? $.value = e : null,
							action: w(G),
							testid: "showroom-flag-create"
						}, null, 8, ["modelValue", "action"]),
						_("div", null, [y(s, {
							type: "submit",
							icon: "i-lucide-plus",
							loading: w(G).busy.value,
							"data-testid": "showroom-flag-create-submit"
						}, {
							default: T(() => [v(C(w(U)("showroom.catalog.create")), 1)]),
							_: 1
						}, 8, ["loading"])])
					], 32), y(e, {
						action: w(G),
						class: "mt-3"
					}, null, 8, ["action"])]),
					_: 1
				}), y(ee, {
					ready: !w(ye) && w(J) !== null,
					failed: w(be),
					status: w(Y)
				}, {
					default: T(() => [_("div", F, [w(X).length === 0 ? (b(), g("p", I, C(w(U)("showroom.structure.flags.empty")), 1)) : (b(), g("table", L, [_("thead", R, [_("tr", null, [
						_("th", z, C(w(U)("showroom.catalog.fields.code")), 1),
						_("th", de, C(w(U)("showroom.catalog.fields.label")), 1),
						_("th", B, C(w(U)("showroom.structure.flags.applies_to")), 1),
						_("th", V, C(w(U)("showroom.catalog.fields.sort_order")), 1)
					])]), _("tbody", H, [(b(!0), g(m, null, x(w(X), (e) => (b(), g("tr", {
						key: e.code,
						"data-testid": `showroom-flag-row-${e.code}`
					}, [
						_("td", pe, [y(l, {
							to: w(ne)(e.code),
							class: "font-mono font-medium hover:underline"
						}, {
							default: T(() => [v(C(e.code), 1)]),
							_: 2
						}, 1032, ["to"])]),
						_("td", me, C(w(o)(e.labels, w(W), e.code)), 1),
						_("td", he, [_("span", ge, [(b(!0), g(m, null, x(e.applies_to, (e) => (b(), oe(u, {
							key: e,
							color: "neutral",
							variant: "outline"
						}, {
							default: T(() => [v(C(w(U)(`showroom.structure.flags.subject_${e}`)), 1)]),
							_: 2
						}, 1024))), 128))])]),
						_("td", _e, C(e.sort_order), 1)
					], 8, fe))), 128))])])), y(t, {
						next: w(J)?.meta?.next_cursor ?? null,
						prev: w(J)?.meta?.prev_cursor ?? null,
						testid: "showroom-flags"
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
export { U as default };
