import { t as e } from "./CatalogPager-BwJ4cMnn.js";
import { t } from "./CatalogState-Bkly-MlG.js";
import { A as ee, C as n, D as r, S as i, f as a, k as o, r as te } from "./catalog-DCoO3A8a.js";
import { n as ne, t as re } from "./structure-DbqcM-iO.js";
import { AtriumIntegrationScreen as ie } from "@refactor-circus/atrium";
import { UBadge as ae, UButton as s, UCard as c, UFormField as oe, UInput as l } from "@refactor-circus/atrium/ui";
import { Fragment as u, computed as d, createBlock as se, createCommentVNode as ce, createElementBlock as f, createElementVNode as p, createTextVNode as m, createVNode as h, defineComponent as g, openBlock as _, reactive as v, renderList as y, resolveComponent as le, toDisplayString as b, unref as x, vModelSelect as S, watch as C, withCtx as w, withDirectives as T, withModifiers as E } from "vue";
import { useI18n as D } from "vue-i18n";
import { useRoute as O, useRouter as k } from "vue-router";
// src/pages/attributes/index.vue?vue&type=script&setup=true&lang.ts
var A = {
	class: "space-y-4",
	"data-testid": "showroom-attributes"
}, j = { class: "flex flex-wrap items-center justify-between gap-3" }, M = { class: "text-xl font-semibold" }, N = { class: "space-y-4" }, P = { value: "" }, F = ["value"], I = { value: "" }, L = ["value"], R = { class: "space-y-4" }, z = {
	key: 0,
	class: "atrium-empty",
	"data-testid": "showroom-attributes-empty"
}, B = {
	key: 1,
	class: "w-full text-left text-sm",
	"data-testid": "showroom-attributes-table"
}, V = { class: "text-muted" }, H = { class: "py-2 pr-3 font-medium" }, U = { class: "py-2 pr-3 font-medium" }, ue = { class: "py-2 pr-3 font-medium" }, de = { class: "py-2 pr-3 font-medium" }, fe = { class: "py-2 font-medium" }, pe = { class: "divide-y divide-default" }, me = ["data-testid"], he = { class: "py-2 pr-3" }, ge = { class: "py-2 pr-3" }, _e = { class: "py-2 pr-3" }, ve = { class: "py-2 pr-3" }, ye = { class: "py-2" }, be = { class: "flex flex-wrap gap-1" }, W = /* @__PURE__ */ g({
	__name: "index",
	setup(g) {
		let W = [
			"search",
			"type",
			"group"
		], { t: G, locale: K } = D(), q = O(), J = k(), Y = d(() => {
			let e = [...W, "cursor"].map((e) => [e, i(q.query[e])]).filter((e) => e[1] !== void 0);
			return Object.fromEntries(e);
		}), { data: X, pending: xe, failed: Se, status: Ce } = o(async (e) => n(await e.GET("/attributes", { params: { query: r(Y.value) } })), Y), Z = ee(async (e) => n(await e.GET("/attribute-groups", { params: { query: { per_page: 100 } } }))), Q = d(() => X.value?.data ?? []), $ = v({
			search: "",
			type: "",
			group: ""
		});
		C(() => q.query, () => {
			for (let e of W) $[e] = i(q.query[e]) ?? "";
		}, { immediate: !0 });
		function we(e) {
			if (!e) return G("showroom.catalog.none");
			let t = Z.value.find((t) => t.code === e);
			return t ? a(t.labels, K.value, e) : e;
		}
		function Te() {
			J.replace({ query: Object.fromEntries(W.map((e) => [e, $[e] || void 0])) });
		}
		function Ee() {
			J.replace({ query: {} });
		}
		return (ee, n) => {
			let r = s, i = l, o = oe, d = c, g = le("RouterLink"), v = ae, C = ie;
			return _(), f("div", A, [p("div", j, [p("h1", M, b(x(G)("showroom.structure.attributes.title")), 1), h(r, {
				to: "/showroom/attributes/create",
				icon: "i-lucide-plus",
				"data-testid": "showroom-attribute-new"
			}, {
				default: w(() => [m(b(x(G)("showroom.structure.attributes.new")), 1)]),
				_: 1
			})]), h(C, { "screen-key": "showroom.attributes" }, {
				default: w(() => [p("div", N, [h(d, null, {
					default: w(() => [p("form", {
						class: "atrium-querybar",
						"data-testid": "showroom-attributes-filters",
						onSubmit: E(Te, ["prevent"])
					}, [
						h(o, { label: x(G)("showroom.catalog.filters.search") }, {
							default: w(() => [h(i, {
								modelValue: x($).search,
								"onUpdate:modelValue": n[0] ||= (e) => x($).search = e,
								class: "w-56",
								"data-testid": "showroom-attributes-search"
							}, null, 8, ["modelValue"])]),
							_: 1
						}, 8, ["label"]),
						h(o, { label: x(G)("showroom.catalog.fields.type") }, {
							default: w(() => [T(p("select", {
								"onUpdate:modelValue": n[1] ||= (e) => x($).type = e,
								class: "w-44 rounded-md border border-default bg-default px-2.5 py-1.5 text-sm",
								"data-testid": "showroom-attributes-type"
							}, [p("option", P, b(x(G)("showroom.structure.attributes.all_types")), 1), (_(!0), f(u, null, y(x(ne), (e) => (_(), f("option", {
								key: e,
								value: e
							}, b(e), 9, F))), 128))], 512), [[S, x($).type]])]),
							_: 1
						}, 8, ["label"]),
						h(o, { label: x(G)("showroom.catalog.fields.group") }, {
							default: w(() => [T(p("select", {
								"onUpdate:modelValue": n[2] ||= (e) => x($).group = e,
								class: "w-48 rounded-md border border-default bg-default px-2.5 py-1.5 text-sm",
								"data-testid": "showroom-attributes-group"
							}, [p("option", I, b(x(G)("showroom.structure.attributes.all_groups")), 1), (_(!0), f(u, null, y(x(Z), (e) => (_(), f("option", {
								key: e.code,
								value: e.code
							}, b(x(a)(e.labels, x(K), e.code)), 9, L))), 128))], 512), [[S, x($).group]])]),
							_: 1
						}, 8, ["label"]),
						h(r, {
							type: "submit",
							icon: "i-lucide-filter",
							"data-testid": "showroom-attributes-filter"
						}, {
							default: w(() => [m(b(x(G)("showroom.catalog.filters.filter")), 1)]),
							_: 1
						}),
						h(r, {
							variant: "ghost",
							icon: "i-lucide-x",
							"data-testid": "showroom-attributes-clear",
							onClick: Ee
						}, {
							default: w(() => [m(b(x(G)("showroom.catalog.filters.clear")), 1)]),
							_: 1
						})
					], 32)]),
					_: 1
				}), h(t, {
					ready: !x(xe) && x(X) !== null,
					failed: x(Se),
					status: x(Ce)
				}, {
					default: w(() => [p("div", R, [x(Q).length === 0 ? (_(), f("p", z, b(x(G)("showroom.structure.attributes.empty")), 1)) : (_(), f("table", B, [p("thead", V, [p("tr", null, [
						p("th", H, b(x(G)("showroom.catalog.fields.code")), 1),
						p("th", U, b(x(G)("showroom.catalog.fields.label")), 1),
						p("th", ue, b(x(G)("showroom.catalog.fields.type")), 1),
						p("th", de, b(x(G)("showroom.catalog.fields.group")), 1),
						p("th", fe, b(x(G)("showroom.structure.attributes.switches")), 1)
					])]), p("tbody", pe, [(_(!0), f(u, null, y(x(Q), (e) => (_(), f("tr", {
						key: e.code,
						"data-testid": `showroom-attribute-row-${e.code}`
					}, [
						p("td", he, [h(g, {
							to: x(te)(e.code),
							class: "font-mono font-medium hover:underline"
						}, {
							default: w(() => [m(b(e.code), 1)]),
							_: 2
						}, 1032, ["to"])]),
						p("td", ge, b(x(a)(e.labels, x(K), e.code)), 1),
						p("td", _e, [h(v, {
							color: "neutral",
							variant: "subtle"
						}, {
							default: w(() => [m(b(e.type), 1)]),
							_: 2
						}, 1024)]),
						p("td", ve, b(we(e.group)), 1),
						p("td", ye, [p("span", be, [(_(!0), f(u, null, y(x(re), (t) => (_(), f(u, { key: t }, [e[t] ? (_(), se(v, {
							key: 0,
							color: "neutral",
							variant: "outline"
						}, {
							default: w(() => [m(b(x(G)(`showroom.structure.attributes.${t}`)), 1)]),
							_: 2
						}, 1024)) : ce("", !0)], 64))), 128))])])
					], 8, me))), 128))])])), h(e, {
						next: x(X)?.meta?.next_cursor ?? null,
						prev: x(X)?.meta?.prev_cursor ?? null,
						testid: "showroom-attributes"
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
