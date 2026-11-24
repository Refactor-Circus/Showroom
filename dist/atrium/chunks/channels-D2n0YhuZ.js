import { t as e } from "./CatalogActionError-BRUgojP9.js";
import { t } from "./CatalogPager-BwJ4cMnn.js";
import { t as n } from "./CatalogState-Bkly-MlG.js";
import { A as ee, C as r, D as i, O as a, S as o, f as s, k as c } from "./catalog-DCoO3A8a.js";
import { t as te } from "./ChannelFields-cGE2z5za.js";
import { c as l, g as u, h as ne, l as re, s as ie } from "./structure-DbqcM-iO.js";
import { AtriumIntegrationScreen as ae } from "@refactor-circus/atrium";
import { UButton as oe, UCard as se, UFormField as ce, UInput as le } from "@refactor-circus/atrium/ui";
import { Fragment as d, computed as f, createElementBlock as p, createElementVNode as m, createTextVNode as h, createVNode as g, defineComponent as ue, isRef as de, openBlock as _, reactive as v, ref as fe, renderList as y, resolveComponent as pe, toDisplayString as b, unref as x, withCtx as S, withModifiers as C } from "vue";
import { useI18n as me } from "vue-i18n";
import { useRoute as w } from "vue-router";
// src/pages/channels/index.vue?vue&type=script&setup=true&lang.ts
var T = {
	class: "space-y-4",
	"data-testid": "showroom-channels"
}, E = { class: "text-xl font-semibold" }, D = { class: "text-sm text-muted" }, O = { class: "space-y-4" }, k = { class: "font-semibold" }, A = { class: "space-y-4" }, j = {
	key: 0,
	class: "atrium-empty",
	"data-testid": "showroom-locales-empty"
}, M = {
	key: 1,
	class: "flex flex-wrap gap-3"
}, N = ["data-testid"], P = { class: "font-mono" }, F = { class: "text-muted" }, I = { class: "font-semibold" }, L = { class: "flex flex-wrap items-end gap-3" }, R = { class: "space-y-4" }, z = {
	key: 0,
	class: "atrium-empty",
	"data-testid": "showroom-channels-empty"
}, he = {
	key: 1,
	class: "w-full text-left text-sm",
	"data-testid": "showroom-channels-table"
}, ge = { class: "text-muted" }, _e = { class: "py-2 pr-3 font-medium" }, ve = { class: "py-2 pr-3 font-medium" }, ye = { class: "py-2 pr-3 font-medium" }, B = { class: "py-2 pr-3 font-medium" }, be = { class: "py-2 font-medium" }, xe = { class: "divide-y divide-default" }, Se = ["data-testid"], Ce = { class: "py-2 pr-3" }, we = { class: "py-2 pr-3" }, Te = { class: "py-2 pr-3 font-mono text-xs" }, Ee = { class: "py-2 pr-3 font-mono text-xs" }, De = { class: "py-2 font-mono text-xs" }, V = /* @__PURE__ */ ue({
	__name: "index",
	setup(ue) {
		let { t: V, locale: H } = me(), Oe = w(), U = a(), W = a(), G = f(() => {
			let e = o(Oe.query.cursor);
			return e ? { cursor: e } : {};
		}), { client: K, data: q, pending: ke, failed: Ae, status: je, reload: Me } = c(async (e) => r(await e.GET("/channels", { params: { query: G.value } })), G), J = c(async (e) => r(await e.GET("/locales", { params: { query: { per_page: 100 } } }))), Ne = ee(ne), Y = f(() => q.value?.data ?? []), X = f(() => J.data.value?.data ?? []), Z = v({
			code: "",
			label: ""
		}), Q = fe(l()), $ = v({
			code: "",
			label: ""
		});
		async function Pe() {
			let e = {
				code: Z.code.trim(),
				...u(H.value, Z.label),
				...ie(Q.value)
			};
			K && await U.run(async () => r(await K.POST("/channels", { body: i(e) }))) && (Z.code = "", Z.label = "", Q.value = l(), await Me());
		}
		async function Fe() {
			let e = {
				code: $.code.trim(),
				...u(H.value, $.label)
			};
			K && await W.run(async () => r(await K.POST("/locales", { body: i(e) }))) && ($.code = "", $.label = "", await J.reload());
		}
		async function Ie(e) {
			K && window.confirm(V("showroom.structure.channels.confirm_delete_locale", { code: e })) && await W.run(async () => r(await K.DELETE("/locales/{locale}", { params: { path: { locale: e } } }))) && await J.reload();
		}
		function Le(e) {
			let t = s(e.labels, H.value, e.code);
			return t === e.code ? "" : t;
		}
		return (ee, r) => {
			let i = oe, a = le, o = ce, c = se, l = pe("RouterLink"), u = ae;
			return _(), p("div", T, [m("div", null, [m("h1", E, b(x(V)("showroom.structure.channels.title")), 1), m("p", D, b(x(V)("showroom.structure.channels.description")), 1)]), g(u, { "screen-key": "showroom.channels" }, {
				default: S(() => [m("div", O, [
					g(c, { "data-testid": "showroom-locales" }, {
						header: S(() => [m("h2", k, b(x(V)("showroom.structure.channels.locales")), 1)]),
						default: S(() => [g(n, {
							ready: !x(J).pending.value && x(J).data.value !== null,
							failed: x(J).failed.value,
							status: x(J).status.value,
							compact: ""
						}, {
							default: S(() => [m("div", A, [x(X).length === 0 ? (_(), p("p", j, b(x(V)("showroom.structure.channels.no_locales")), 1)) : (_(), p("ul", M, [(_(!0), p(d, null, y(x(X), (e) => (_(), p("li", {
								key: e.code,
								class: "flex items-center gap-2 rounded-md border border-default px-2 py-1 text-sm",
								"data-testid": `showroom-locale-${e.code}`
							}, [
								m("span", P, b(e.code), 1),
								m("span", F, b(Le(e)), 1),
								g(i, {
									variant: "ghost",
									size: "xs",
									icon: "i-lucide-trash-2",
									"aria-label": x(V)("showroom.catalog.delete"),
									loading: x(W).busy.value,
									"data-testid": `showroom-locale-delete-${e.code}`,
									onClick: (t) => Ie(e.code)
								}, null, 8, [
									"aria-label",
									"loading",
									"data-testid",
									"onClick"
								])
							], 8, N))), 128))])), m("form", {
								class: "flex flex-wrap items-end gap-3",
								"data-testid": "showroom-locale-create-form",
								onSubmit: C(Fe, ["prevent"])
							}, [
								g(o, {
									label: x(V)("showroom.catalog.fields.code"),
									hint: x(V)("showroom.structure.channels.locale_code_hint"),
									error: x(W).error("code"),
									required: ""
								}, {
									default: S(() => [g(a, {
										modelValue: x($).code,
										"onUpdate:modelValue": r[0] ||= (e) => x($).code = e,
										required: "",
										class: "w-44",
										"data-testid": "showroom-locale-create-code"
									}, null, 8, ["modelValue"])]),
									_: 1
								}, 8, [
									"label",
									"hint",
									"error"
								]),
								g(o, {
									label: x(V)("showroom.catalog.fields.label_in", { locale: x(H) }),
									error: x(W).error("labels")
								}, {
									default: S(() => [g(a, {
										modelValue: x($).label,
										"onUpdate:modelValue": r[1] ||= (e) => x($).label = e,
										class: "w-56",
										"data-testid": "showroom-locale-create-label"
									}, null, 8, ["modelValue"])]),
									_: 1
								}, 8, ["label", "error"]),
								g(i, {
									type: "submit",
									icon: "i-lucide-plus",
									loading: x(W).busy.value,
									"data-testid": "showroom-locale-create-submit"
								}, {
									default: S(() => [h(b(x(V)("showroom.structure.channels.add_locale")), 1)]),
									_: 1
								}, 8, ["loading"])
							], 32)])]),
							_: 1
						}, 8, [
							"ready",
							"failed",
							"status"
						]), g(e, {
							action: x(W),
							class: "mt-3"
						}, null, 8, ["action"])]),
						_: 1
					}),
					g(c, { "data-testid": "showroom-channel-new" }, {
						header: S(() => [m("h2", I, b(x(V)("showroom.structure.channels.new")), 1)]),
						default: S(() => [m("form", {
							class: "flex flex-col gap-4",
							"data-testid": "showroom-channel-create-form",
							onSubmit: C(Pe, ["prevent"])
						}, [
							m("div", L, [g(o, {
								label: x(V)("showroom.catalog.fields.code"),
								hint: x(V)("showroom.structure.code_hint"),
								error: x(U).error("code"),
								required: ""
							}, {
								default: S(() => [g(a, {
									modelValue: x(Z).code,
									"onUpdate:modelValue": r[2] ||= (e) => x(Z).code = e,
									required: "",
									class: "w-56",
									"data-testid": "showroom-channel-create-code"
								}, null, 8, ["modelValue"])]),
								_: 1
							}, 8, [
								"label",
								"hint",
								"error"
							]), g(o, {
								label: x(V)("showroom.catalog.fields.label_in", { locale: x(H) }),
								error: x(U).error("labels")
							}, {
								default: S(() => [g(a, {
									modelValue: x(Z).label,
									"onUpdate:modelValue": r[3] ||= (e) => x(Z).label = e,
									class: "w-56",
									"data-testid": "showroom-channel-create-label"
								}, null, 8, ["modelValue"])]),
								_: 1
							}, 8, ["label", "error"])]),
							g(te, {
								modelValue: x(Q),
								"onUpdate:modelValue": r[4] ||= (e) => de(Q) ? Q.value = e : null,
								locales: x(X),
								trees: x(Ne),
								action: x(U),
								testid: "showroom-channel-create"
							}, null, 8, [
								"modelValue",
								"locales",
								"trees",
								"action"
							]),
							m("div", null, [g(i, {
								type: "submit",
								icon: "i-lucide-plus",
								loading: x(U).busy.value,
								"data-testid": "showroom-channel-create-submit"
							}, {
								default: S(() => [h(b(x(V)("showroom.catalog.create")), 1)]),
								_: 1
							}, 8, ["loading"])])
						], 32), g(e, {
							action: x(U),
							class: "mt-3"
						}, null, 8, ["action"])]),
						_: 1
					}),
					g(n, {
						ready: !x(ke) && x(q) !== null,
						failed: x(Ae),
						status: x(je)
					}, {
						default: S(() => [m("div", R, [x(Y).length === 0 ? (_(), p("p", z, b(x(V)("showroom.structure.channels.empty")), 1)) : (_(), p("table", he, [m("thead", ge, [m("tr", null, [
							m("th", _e, b(x(V)("showroom.catalog.fields.code")), 1),
							m("th", ve, b(x(V)("showroom.catalog.fields.label")), 1),
							m("th", ye, b(x(V)("showroom.structure.channels.locales")), 1),
							m("th", B, b(x(V)("showroom.structure.channels.currencies")), 1),
							m("th", be, b(x(V)("showroom.structure.channels.category_tree")), 1)
						])]), m("tbody", xe, [(_(!0), p(d, null, y(x(Y), (e) => (_(), p("tr", {
							key: e.code,
							"data-testid": `showroom-channel-row-${e.code}`
						}, [
							m("td", Ce, [g(l, {
								to: x(re)(e.code),
								class: "font-mono font-medium hover:underline"
							}, {
								default: S(() => [h(b(e.code), 1)]),
								_: 2
							}, 1032, ["to"])]),
							m("td", we, b(x(s)(e.labels, x(H), e.code)), 1),
							m("td", Te, b((e.locales ?? []).join(", ")), 1),
							m("td", Ee, b(e.currencies.join(", ")), 1),
							m("td", De, b(e.category_tree ?? x(V)("showroom.catalog.none")), 1)
						], 8, Se))), 128))])])), g(t, {
							next: x(q)?.meta?.next_cursor ?? null,
							prev: x(q)?.meta?.prev_cursor ?? null,
							testid: "showroom-channels"
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
