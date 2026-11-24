import { t as e } from "./CatalogActionError-BRUgojP9.js";
import { t } from "./CatalogPager-BwJ4cMnn.js";
import { t as n } from "./CatalogState-Bkly-MlG.js";
import { A as ee, C as r, D as i, O as a, S as o, a as s, f as c, k as l } from "./catalog-DCoO3A8a.js";
import { g as u } from "./structure-DbqcM-iO.js";
import { t as te } from "./owners-C7wgNkGA.js";
import { AtriumIntegrationScreen as ne } from "@refactor-circus/atrium";
import { UBadge as re, UButton as ie, UCard as ae, UFormField as oe, UInput as se } from "@refactor-circus/atrium/ui";
import { Fragment as d, computed as f, createElementBlock as p, createElementVNode as m, createTextVNode as h, createVNode as g, defineComponent as ce, normalizeStyle as le, openBlock as _, reactive as v, renderList as y, resolveComponent as ue, toDisplayString as b, unref as x, vModelSelect as S, watch as de, withCtx as C, withDirectives as w, withModifiers as T } from "vue";
import { useI18n as fe } from "vue-i18n";
import { useRoute as pe, useRouter as E } from "vue-router";
// src/pages/owners/index.vue?vue&type=script&setup=true&lang.ts
var D = {
	class: "space-y-4",
	"data-testid": "showroom-owners-page"
}, O = { class: "flex flex-wrap items-start justify-between gap-3" }, k = { class: "text-xl font-semibold" }, A = { class: "text-sm text-muted" }, j = { class: "flex flex-wrap gap-2" }, M = { class: "space-y-4" }, N = { class: "font-semibold" }, P = {
	key: 0,
	class: "text-sm text-muted",
	"data-testid": "showroom-owners-no-types"
}, F = { class: "flex flex-wrap items-end gap-3" }, I = {
	value: "",
	disabled: ""
}, L = ["value"], R = { value: "" }, z = ["value"], B = { class: "space-y-4" }, me = {
	key: 0,
	class: "atrium-empty",
	"data-testid": "showroom-owners-empty"
}, he = {
	key: 1,
	class: "w-full text-left text-sm",
	"data-testid": "showroom-owners-table"
}, V = { class: "text-muted" }, ge = { class: "py-2 pr-3 font-medium" }, _e = { class: "py-2 pr-3 font-medium" }, ve = { class: "py-2 pr-3 font-medium" }, ye = { class: "py-2 pr-3 font-medium" }, be = { class: "py-2 pr-3 font-medium" }, xe = { class: "py-2 font-medium" }, Se = { class: "divide-y divide-default" }, Ce = ["data-testid"], we = { class: "py-2 pr-3" }, Te = ["title"], Ee = { class: "font-mono text-xs" }, De = { class: "py-2 pr-3 font-mono text-xs" }, Oe = { class: "py-2 pr-3 tabular-nums" }, ke = { class: "py-2" }, H = /* @__PURE__ */ ce({
	__name: "index",
	setup(ce) {
		let { t: H, locale: U } = fe(), W = pe(), G = E(), K = a(), q = f(() => {
			let e = {};
			for (let t of [
				"search",
				"type",
				"cursor"
			]) {
				let n = o(W.query[t]);
				n && (e[t] = n);
			}
			return e;
		}), { client: J, data: Y, pending: Ae, failed: je, status: Me, reload: Ne } = l(async (e) => r(await e.GET("/owners", { params: { query: i(q.value) } })), q), X = f(() => Y.value?.data ?? []), Z = ee(async (e) => r(await e.GET("/owner-types", { params: { query: { per_page: 100 } } }))), Q = v({
			search: "",
			type: ""
		});
		de(() => W.query, () => {
			Q.search = o(W.query.search) ?? "", Q.type = o(W.query.type) ?? "";
		}, { immediate: !0 });
		function Pe() {
			G.replace({ query: {
				...Q.search.trim() ? { search: Q.search.trim() } : {},
				...Q.type ? { type: Q.type } : {}
			} });
		}
		function Fe() {
			G.replace({ query: {} });
		}
		let $ = v({
			code: "",
			type: "",
			parent: "",
			label: ""
		});
		async function Ie() {
			if (!J) return;
			let e = s($.parent), t = {
				code: $.code.trim(),
				type: $.type,
				...e ? { parent: e } : {},
				...u(U.value, $.label)
			};
			await K.run(async () => r(await J.POST("/owners", { body: i(t) }))) && ($.code = "", $.parent = "", $.label = "", await Ne());
		}
		function Le(e) {
			let t = Z.value.find((t) => t.code === e);
			return t ? c(t.labels, U.value, t.code) : e ?? H("showroom.catalog.none");
		}
		return (ee, r) => {
			let i = ie, a = se, o = oe, s = ae, l = ue("RouterLink"), u = re, f = ne;
			return _(), p("div", D, [m("div", O, [m("div", null, [m("h1", k, b(x(H)("showroom.owners.title")), 1), m("p", A, b(x(H)("showroom.owners.description")), 1)]), m("div", j, [g(i, {
				to: "/showroom/owner-types",
				variant: "outline",
				icon: "i-lucide-shapes",
				"data-testid": "showroom-owners-owner-types"
			}, {
				default: C(() => [h(b(x(H)("showroom.owner_types.title")), 1)]),
				_: 1
			}), g(i, {
				to: "/showroom/flags",
				variant: "outline",
				icon: "i-lucide-flag",
				"data-testid": "showroom-owners-flags"
			}, {
				default: C(() => [h(b(x(H)("showroom.owners.flags")), 1)]),
				_: 1
			})])]), g(f, { "screen-key": "showroom.owners" }, {
				default: C(() => [m("div", M, [
					g(s, { "data-testid": "showroom-owner-new" }, {
						header: C(() => [m("h2", N, b(x(H)("showroom.owners.new")), 1)]),
						default: C(() => [x(Z).length === 0 ? (_(), p("p", P, b(x(H)("showroom.owners.no_types")), 1)) : (_(), p("form", {
							key: 1,
							class: "flex flex-col gap-3",
							"data-testid": "showroom-owner-create-form",
							onSubmit: T(Ie, ["prevent"])
						}, [g(e, { action: x(K) }, null, 8, ["action"]), m("div", F, [
							g(o, {
								label: x(H)("showroom.catalog.fields.code"),
								hint: x(H)("showroom.owners.code_hint"),
								error: x(K).error("code"),
								required: ""
							}, {
								default: C(() => [g(a, {
									modelValue: x($).code,
									"onUpdate:modelValue": r[0] ||= (e) => x($).code = e,
									required: "",
									class: "w-48",
									"data-testid": "showroom-owner-create-code"
								}, null, 8, ["modelValue"])]),
								_: 1
							}, 8, [
								"label",
								"hint",
								"error"
							]),
							g(o, {
								label: x(H)("showroom.catalog.fields.type"),
								error: x(K).error("type"),
								required: ""
							}, {
								default: C(() => [w(m("select", {
									"onUpdate:modelValue": r[1] ||= (e) => x($).type = e,
									required: "",
									class: "w-44 rounded-md border border-default bg-default px-2.5 py-1.5 text-sm",
									"data-testid": "showroom-owner-create-type"
								}, [m("option", I, b(x(H)("showroom.owners.choose_type")), 1), (_(!0), p(d, null, y(x(Z), (e) => (_(), p("option", {
									key: e.code,
									value: e.code
								}, b(x(c)(e.labels, x(U), e.code)), 9, L))), 128))], 512), [[S, x($).type]])]),
								_: 1
							}, 8, ["label", "error"]),
							g(o, {
								label: x(H)("showroom.catalog.fields.parent"),
								hint: x(H)("showroom.owners.parent_hint"),
								error: x(K).error("parent")
							}, {
								default: C(() => [g(a, {
									modelValue: x($).parent,
									"onUpdate:modelValue": r[2] ||= (e) => x($).parent = e,
									class: "w-44",
									"data-testid": "showroom-owner-create-parent"
								}, null, 8, ["modelValue"])]),
								_: 1
							}, 8, [
								"label",
								"hint",
								"error"
							]),
							g(o, {
								label: x(H)("showroom.catalog.fields.label_in", { locale: x(U) }),
								error: x(K).error("labels")
							}, {
								default: C(() => [g(a, {
									modelValue: x($).label,
									"onUpdate:modelValue": r[3] ||= (e) => x($).label = e,
									class: "w-48",
									"data-testid": "showroom-owner-create-label"
								}, null, 8, ["modelValue"])]),
								_: 1
							}, 8, ["label", "error"]),
							g(i, {
								type: "submit",
								icon: "i-lucide-plus",
								loading: x(K).busy.value,
								"data-testid": "showroom-owner-create-submit"
							}, {
								default: C(() => [h(b(x(H)("showroom.catalog.create")), 1)]),
								_: 1
							}, 8, ["loading"])
						])], 32))]),
						_: 1
					}),
					g(s, null, {
						default: C(() => [m("form", {
							class: "atrium-querybar",
							"data-testid": "showroom-owners-filters",
							onSubmit: T(Pe, ["prevent"])
						}, [
							g(o, { label: x(H)("showroom.catalog.filters.search") }, {
								default: C(() => [g(a, {
									modelValue: x(Q).search,
									"onUpdate:modelValue": r[4] ||= (e) => x(Q).search = e,
									class: "w-56",
									"data-testid": "showroom-owners-search"
								}, null, 8, ["modelValue"])]),
								_: 1
							}, 8, ["label"]),
							g(o, { label: x(H)("showroom.catalog.fields.type") }, {
								default: C(() => [w(m("select", {
									"onUpdate:modelValue": r[5] ||= (e) => x(Q).type = e,
									class: "w-44 rounded-md border border-default bg-default px-2.5 py-1.5 text-sm",
									"data-testid": "showroom-owners-type"
								}, [m("option", R, b(x(H)("showroom.owners.all_types")), 1), (_(!0), p(d, null, y(x(Z), (e) => (_(), p("option", {
									key: e.code,
									value: e.code
								}, b(x(c)(e.labels, x(U), e.code)), 9, z))), 128))], 512), [[S, x(Q).type]])]),
								_: 1
							}, 8, ["label"]),
							g(i, {
								type: "submit",
								icon: "i-lucide-filter"
							}, {
								default: C(() => [h(b(x(H)("showroom.catalog.filters.filter")), 1)]),
								_: 1
							}),
							g(i, {
								variant: "ghost",
								color: "neutral",
								icon: "i-lucide-x",
								onClick: Fe
							}, {
								default: C(() => [h(b(x(H)("showroom.catalog.filters.clear")), 1)]),
								_: 1
							})
						], 32)]),
						_: 1
					}),
					g(n, {
						ready: !x(Ae) && x(Y) !== null,
						failed: x(je),
						status: x(Me)
					}, {
						default: C(() => [m("div", B, [x(X).length === 0 ? (_(), p("p", me, b(x(H)("showroom.owners.empty")), 1)) : (_(), p("table", he, [m("thead", V, [m("tr", null, [
							m("th", ge, b(x(H)("showroom.catalog.fields.code")), 1),
							m("th", _e, b(x(H)("showroom.catalog.fields.label")), 1),
							m("th", ve, b(x(H)("showroom.catalog.fields.type")), 1),
							m("th", ye, b(x(H)("showroom.catalog.fields.parent")), 1),
							m("th", be, b(x(H)("showroom.owners.children")), 1),
							m("th", xe, b(x(H)("showroom.catalog.fields.enabled")), 1)
						])]), m("tbody", Se, [(_(!0), p(d, null, y(x(X), (e) => (_(), p("tr", {
							key: e.id,
							"data-testid": `showroom-owner-row-${e.code}`
						}, [
							m("td", {
								class: "py-2 pr-3",
								style: le({ paddingLeft: `${e.depth * .75}rem` })
							}, [g(l, {
								to: x(te)(e.id),
								class: "font-mono font-medium hover:underline"
							}, {
								default: C(() => [h(b(e.code), 1)]),
								_: 2
							}, 1032, ["to"])], 4),
							m("td", we, b(x(c)(e.labels, x(U), e.code)), 1),
							m("td", {
								class: "py-2 pr-3",
								title: Le(e.type)
							}, [m("span", Ee, b(e.type ?? x(H)("showroom.catalog.none")), 1)], 8, Te),
							m("td", De, b(e.parent ?? x(H)("showroom.catalog.none")), 1),
							m("td", Oe, b(e.children_count ?? 0), 1),
							m("td", ke, [g(u, {
								color: e.enabled ? "success" : "neutral",
								variant: "subtle"
							}, {
								default: C(() => [h(b(e.enabled ? x(H)("showroom.catalog.enabled") : x(H)("showroom.catalog.disabled")), 1)]),
								_: 2
							}, 1032, ["color"])])
						], 8, Ce))), 128))])])), g(t, {
							next: x(Y)?.meta?.next_cursor ?? null,
							prev: x(Y)?.meta?.prev_cursor ?? null,
							testid: "showroom-owners"
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
