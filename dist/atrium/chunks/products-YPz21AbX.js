import { t as e } from "./CatalogPager-BwJ4cMnn.js";
import { t } from "./CatalogState-Bkly-MlG.js";
import { A as n, C as r, D as i, E as ee, S as a, d as te, f as o, k as s, p as c, t as ne, v as re, x as l, y as ie } from "./catalog-DCoO3A8a.js";
import { AtriumIntegrationScreen as ae } from "@refactor-circus/atrium";
import { UBadge as oe, UButton as se, UCard as ce, UFormField as le, UInput as u } from "@refactor-circus/atrium/ui";
import { Fragment as d, computed as f, createBlock as p, createCommentVNode as ue, createElementBlock as m, createElementVNode as h, createTextVNode as g, createVNode as _, defineComponent as v, openBlock as y, reactive as de, renderList as b, resolveComponent as fe, toDisplayString as x, unref as S, vModelSelect as C, watch as pe, withCtx as w, withDirectives as T, withModifiers as E } from "vue";
import { useI18n as D } from "vue-i18n";
import { useRoute as O, useRouter as k } from "vue-router";
// src/pages/products/index.vue?vue&type=script&setup=true&lang.ts
var A = {
	class: "space-y-4",
	"data-testid": "showroom-products"
}, j = { class: "flex flex-wrap items-center justify-between gap-3" }, M = { class: "text-xl font-semibold" }, N = { class: "flex gap-2" }, P = { class: "space-y-4" }, F = { value: "" }, I = ["value"], L = { value: "" }, R = ["value"], z = { value: "" }, B = { value: "1" }, me = { value: "0" }, he = { class: "space-y-4" }, ge = {
	key: 0,
	class: "atrium-empty",
	"data-testid": "showroom-products-empty"
}, _e = {
	key: 1,
	class: "w-full text-left text-sm",
	"data-testid": "showroom-products-table"
}, ve = { class: "text-muted" }, ye = { class: "py-2 pr-3 font-medium" }, be = { class: "py-2 pr-3 font-medium" }, xe = { class: "py-2 pr-3 font-medium" }, Se = { class: "py-2 pr-3 font-medium" }, V = { class: "py-2 pr-3 font-medium" }, Ce = { class: "py-2 pr-3 font-medium" }, we = { class: "py-2 font-medium" }, Te = { class: "divide-y divide-default" }, Ee = ["data-testid"], De = { class: "py-2 pr-3" }, Oe = { class: "py-2 pr-3" }, ke = { class: "py-2 pr-3" }, Ae = { class: "py-2 pr-3 font-mono text-xs" }, je = { key: 1 }, Me = { class: "py-2 pr-3" }, Ne = { class: "py-2 pr-3" }, Pe = { class: "flex items-center gap-2" }, Fe = { class: "py-2" }, H = /* @__PURE__ */ v({
	__name: "index",
	setup(v) {
		let { t: H, locale: U } = D(), W = O(), G = k(), K = [
			"search",
			"family",
			"category",
			"owner",
			"status",
			"enabled"
		], q = f(() => {
			let e = {};
			for (let t of K) {
				let n = a(W.query[t]);
				n !== void 0 && (e[t] = t === "enabled" ? +(n === "1") : n);
			}
			let t = l(W.query.page);
			return t > 1 ? {
				...e,
				page: t
			} : e;
		}), J = n(async (e) => r(await e.GET("/families", { params: { query: { per_page: 100 } } }))), { data: Y, pending: Ie, failed: Le, status: Re } = s(async (e) => r(await e.GET("/products", { params: { query: i(q.value) } })), q), X = f(() => Y.value?.data ?? []), Z = f(() => ({
			current: Y.value?.meta?.current_page ?? 1,
			last: Y.value?.meta?.last_page ?? 1
		})), Q = de({
			search: "",
			family: "",
			category: "",
			owner: "",
			status: "",
			enabled: ""
		});
		pe(() => K.map((e) => W.query[e]), () => {
			for (let e of K) Q[e] = a(W.query[e]) ?? "";
		}, { immediate: !0 });
		function $(e) {
			return e ? J.value.find((t) => t.code === e) : void 0;
		}
		function ze(e) {
			return c(e.values, $(e.family)?.label_attribute, U.value) ?? H("showroom.catalog.none");
		}
		function Be(e) {
			let t = $(e);
			return t ? o(t.labels, U.value, t.code) : e || H("showroom.catalog.none");
		}
		function Ve() {
			let e = {};
			for (let t of K) e[t] = Q[t] || void 0;
			G.replace({ query: {
				...W.query,
				...e,
				page: void 0
			} });
		}
		function He() {
			G.replace({ query: {} });
		}
		return (n, r) => {
			let i = se, a = u, s = le, c = ce, l = fe("RouterLink"), f = oe, v = ae;
			return y(), m("div", A, [h("div", j, [h("h1", M, x(S(H)("showroom.catalog.products.title")), 1), h("div", N, [_(i, {
				to: "/showroom/flags",
				variant: "outline",
				icon: "i-lucide-flag",
				"data-testid": "showroom-flag-definitions"
			}, {
				default: w(() => [g(x(S(H)("showroom.catalog.products.flag_definitions")), 1)]),
				_: 1
			}), _(i, {
				to: "/showroom/products/create",
				icon: "i-lucide-plus",
				"data-testid": "showroom-products-new"
			}, {
				default: w(() => [g(x(S(H)("showroom.catalog.products.new")), 1)]),
				_: 1
			})])]), _(v, { "screen-key": "showroom.products" }, {
				default: w(() => [h("div", P, [_(c, null, {
					default: w(() => [h("form", {
						class: "atrium-querybar",
						"data-testid": "showroom-products-filters",
						onSubmit: E(Ve, ["prevent"])
					}, [
						_(s, { label: S(H)("showroom.catalog.filters.search") }, {
							default: w(() => [_(a, {
								modelValue: S(Q).search,
								"onUpdate:modelValue": r[0] ||= (e) => S(Q).search = e,
								name: "search",
								class: "w-64",
								"data-testid": "showroom-products-search"
							}, null, 8, ["modelValue"])]),
							_: 1
						}, 8, ["label"]),
						_(s, { label: S(H)("showroom.catalog.fields.family") }, {
							default: w(() => [T(h("select", {
								"onUpdate:modelValue": r[1] ||= (e) => S(Q).family = e,
								class: "w-48 rounded-md border border-default bg-default px-2.5 py-1.5 text-sm",
								"data-testid": "showroom-products-family"
							}, [h("option", F, x(S(H)("showroom.catalog.filters.all_families")), 1), (y(!0), m(d, null, b(S(J), (e) => (y(), m("option", {
								key: e.code,
								value: e.code
							}, x(S(o)(e.labels, S(U), e.code)), 9, I))), 128))], 512), [[C, S(Q).family]])]),
							_: 1
						}, 8, ["label"]),
						_(s, { label: S(H)("showroom.catalog.fields.category") }, {
							default: w(() => [_(a, {
								modelValue: S(Q).category,
								"onUpdate:modelValue": r[2] ||= (e) => S(Q).category = e,
								name: "category",
								class: "w-44",
								"data-testid": "showroom-products-category"
							}, null, 8, ["modelValue"])]),
							_: 1
						}, 8, ["label"]),
						_(s, { label: S(H)("showroom.catalog.fields.owner") }, {
							default: w(() => [_(a, {
								modelValue: S(Q).owner,
								"onUpdate:modelValue": r[3] ||= (e) => S(Q).owner = e,
								name: "owner",
								class: "w-44",
								"data-testid": "showroom-products-owner"
							}, null, 8, ["modelValue"])]),
							_: 1
						}, 8, ["label"]),
						_(s, { label: S(H)("showroom.catalog.fields.workflow") }, {
							default: w(() => [T(h("select", {
								"onUpdate:modelValue": r[4] ||= (e) => S(Q).status = e,
								class: "w-40 rounded-md border border-default bg-default px-2.5 py-1.5 text-sm",
								"data-testid": "showroom-products-status"
							}, [h("option", L, x(S(H)("showroom.catalog.filters.all_statuses")), 1), (y(!0), m(d, null, b(S(ne), (e) => (y(), m("option", {
								key: e,
								value: e
							}, x(S(H)(`showroom.statuses.${e}`)), 9, R))), 128))], 512), [[C, S(Q).status]])]),
							_: 1
						}, 8, ["label"]),
						_(s, { label: S(H)("showroom.catalog.fields.enabled") }, {
							default: w(() => [T(h("select", {
								"onUpdate:modelValue": r[5] ||= (e) => S(Q).enabled = e,
								class: "w-40 rounded-md border border-default bg-default px-2.5 py-1.5 text-sm",
								"data-testid": "showroom-products-enabled"
							}, [
								h("option", z, x(S(H)("showroom.catalog.filters.all_statuses")), 1),
								h("option", B, x(S(H)("showroom.catalog.enabled")), 1),
								h("option", me, x(S(H)("showroom.catalog.disabled")), 1)
							], 512), [[C, S(Q).enabled]])]),
							_: 1
						}, 8, ["label"]),
						_(i, {
							type: "submit",
							icon: "i-lucide-filter",
							"data-testid": "showroom-products-filter"
						}, {
							default: w(() => [g(x(S(H)("showroom.catalog.filters.filter")), 1)]),
							_: 1
						}),
						_(i, {
							variant: "ghost",
							icon: "i-lucide-x",
							"data-testid": "showroom-products-clear",
							onClick: He
						}, {
							default: w(() => [g(x(S(H)("showroom.catalog.filters.clear")), 1)]),
							_: 1
						})
					], 32)]),
					_: 1
				}), _(t, {
					ready: !S(Ie) && S(Y) !== null,
					failed: S(Le),
					status: S(Re)
				}, {
					default: w(() => [h("div", he, [S(X).length === 0 ? (y(), m("p", ge, x(S(H)("showroom.catalog.products.empty")), 1)) : (y(), m("table", _e, [h("thead", ve, [h("tr", null, [
						h("th", ye, x(S(H)("showroom.catalog.fields.identifier")), 1),
						h("th", be, x(S(H)("showroom.catalog.fields.label")), 1),
						h("th", xe, x(S(H)("showroom.catalog.fields.family")), 1),
						h("th", Se, x(S(H)("showroom.catalog.fields.parent")), 1),
						h("th", V, x(S(H)("showroom.catalog.fields.enabled")), 1),
						h("th", Ce, x(S(H)("showroom.catalog.fields.workflow")), 1),
						h("th", we, x(S(H)("showroom.catalog.fields.updated")), 1)
					])]), h("tbody", Te, [(y(!0), m(d, null, b(S(X), (e) => (y(), m("tr", {
						key: e.id,
						"data-testid": `showroom-product-row-${e.id}`
					}, [
						h("td", De, [_(l, {
							to: S(ie)(e.id),
							class: "font-mono font-medium hover:underline"
						}, {
							default: w(() => [g(x(e.identifier), 1)]),
							_: 2
						}, 1032, ["to"])]),
						h("td", Oe, x(ze(e)), 1),
						h("td", ke, x(Be(e.family)), 1),
						h("td", Ae, [e.parent ? (y(), p(l, {
							key: 0,
							to: S(re)(e.parent),
							class: "hover:underline"
						}, {
							default: w(() => [g(x(e.parent), 1)]),
							_: 2
						}, 1032, ["to"])) : (y(), m("span", je, x(S(H)("showroom.catalog.none")), 1))]),
						h("td", Me, [_(f, {
							color: e.enabled ? "success" : "neutral",
							variant: "subtle",
							"data-status": e.enabled ? "enabled" : "disabled"
						}, {
							default: w(() => [g(x(e.enabled ? S(H)("showroom.catalog.enabled") : S(H)("showroom.catalog.disabled")), 1)]),
							_: 2
						}, 1032, ["color", "data-status"])]),
						h("td", Ne, [h("span", Pe, [_(f, {
							color: S(ee)(e.status),
							variant: "subtle",
							"data-status": e.status
						}, {
							default: w(() => [g(x(S(H)(`showroom.statuses.${e.status}`)), 1)]),
							_: 2
						}, 1032, ["color", "data-status"]), e.published_version ? (y(), p(f, {
							key: 0,
							color: "neutral",
							variant: "outline"
						}, {
							default: w(() => [g("v" + x(e.published_version), 1)]),
							_: 2
						}, 1024)) : ue("", !0)])]),
						h("td", Fe, x(S(te)(e.updated_at) ?? S(H)("showroom.catalog.none")), 1)
					], 8, Ee))), 128))])])), _(e, {
						current: S(Z).current,
						last: S(Z).last,
						testid: "showroom-products"
					}, null, 8, ["current", "last"])])]),
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
export { H as default };
