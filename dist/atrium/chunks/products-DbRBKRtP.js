import { t as e } from "./CatalogPager-BwJ4cMnn.js";
import { t } from "./CatalogState-Bkly-MlG.js";
import { A as n, C as r, D as i, E as ee, S as a, d as te, f as o, k as s, p as c, t as l, v as u, x as d, y as ne } from "./catalog-DCoO3A8a.js";
import { AtriumIntegrationScreen as re, AtriumPageHeader as ie, AtriumSelect as ae, AtriumState as oe } from "@refactor-circus/atrium";
import { UBadge as se, UButton as f, UCard as p, UFormField as m, UInput as h, ULink as g, UTable as _ } from "@refactor-circus/atrium/ui";
import { computed as v, createBlock as y, createCommentVNode as b, createElementBlock as x, createElementVNode as S, createTextVNode as C, createVNode as w, defineComponent as T, openBlock as E, reactive as D, toDisplayString as O, unref as k, watch as A, withCtx as j, withModifiers as M } from "vue";
import { useI18n as N } from "vue-i18n";
import { useRoute as P, useRouter as F } from "vue-router";
// src/pages/products/index.vue?vue&type=script&setup=true&lang.ts
var I = {
	class: "space-y-4",
	"data-testid": "showroom-products"
}, L = { class: "space-y-4" }, R = { class: "space-y-4" }, z = ["data-testid"], B = { key: 1 }, V = { class: "flex items-center gap-2" }, H = /* @__PURE__ */ T({
	__name: "index",
	setup(T) {
		let { t: H, locale: U } = N(), W = P(), G = F(), K = [
			"search",
			"family",
			"category",
			"owner",
			"status",
			"enabled"
		], q = v(() => {
			let e = {};
			for (let t of K) {
				let n = a(W.query[t]);
				n !== void 0 && (e[t] = t === "enabled" ? +(n === "1") : n);
			}
			let t = d(W.query.page);
			return t > 1 ? {
				...e,
				page: t
			} : e;
		}), J = n(async (e) => r(await e.GET("/families", { params: { query: { per_page: 100 } } }))), { data: Y, pending: ce, failed: le, status: ue } = s(async (e) => r(await e.GET("/products", { params: { query: i(q.value) } })), q), de = v(() => Y.value?.data ?? []), X = v(() => ({
			current: Y.value?.meta?.current_page ?? 1,
			last: Y.value?.meta?.last_page ?? 1
		})), Z = D({
			search: "",
			family: "",
			category: "",
			owner: "",
			status: "",
			enabled: ""
		});
		A(() => K.map((e) => W.query[e]), () => {
			for (let e of K) Z[e] = a(W.query[e]) ?? "";
		}, { immediate: !0 });
		function Q(e) {
			return e ? J.value.find((t) => t.code === e) : void 0;
		}
		function fe(e) {
			return c(e.values, Q(e.family)?.label_attribute, U.value) ?? H("showroom.catalog.none");
		}
		function pe(e) {
			let t = Q(e);
			return t ? o(t.labels, U.value, t.code) : e || H("showroom.catalog.none");
		}
		let me = v(() => J.value.map((e) => ({
			label: o(e.labels, U.value, e.code),
			value: e.code
		}))), he = v(() => l.map((e) => ({
			label: H(`showroom.statuses.${e}`),
			value: e
		}))), ge = v(() => [{
			label: H("showroom.catalog.enabled"),
			value: "1"
		}, {
			label: H("showroom.catalog.disabled"),
			value: "0"
		}]), $ = v(() => [
			{
				accessorKey: "identifier",
				header: H("showroom.catalog.fields.identifier")
			},
			{
				id: "label",
				header: H("showroom.catalog.fields.label")
			},
			{
				accessorKey: "family",
				header: H("showroom.catalog.fields.family")
			},
			{
				accessorKey: "parent",
				header: H("showroom.catalog.fields.parent")
			},
			{
				accessorKey: "enabled",
				header: H("showroom.catalog.fields.enabled")
			},
			{
				accessorKey: "status",
				header: H("showroom.catalog.fields.workflow")
			},
			{
				accessorKey: "updated_at",
				header: H("showroom.catalog.fields.updated")
			}
		]);
		function _e() {
			let e = {};
			for (let t of K) e[t] = Z[t] || void 0;
			G.replace({ query: {
				...W.query,
				...e,
				page: void 0
			} });
		}
		function ve() {
			G.replace({ query: {} });
		}
		return (n, r) => {
			let i = f, a = ie, o = h, s = m, c = ae, l = p, d = oe, v = g, T = se, D = _, A = re;
			return E(), x("div", I, [w(a, { title: k(H)("showroom.catalog.products.title") }, {
				default: j(() => [w(i, {
					to: "/showroom/flags",
					variant: "outline",
					icon: "i-lucide-flag",
					"data-testid": "showroom-flag-definitions"
				}, {
					default: j(() => [C(O(k(H)("showroom.catalog.products.flag_definitions")), 1)]),
					_: 1
				}), w(i, {
					to: "/showroom/products/create",
					icon: "i-lucide-plus",
					"data-testid": "showroom-products-new"
				}, {
					default: j(() => [C(O(k(H)("showroom.catalog.products.new")), 1)]),
					_: 1
				})]),
				_: 1
			}, 8, ["title"]), w(A, { "screen-key": "showroom.products" }, {
				default: j(() => [S("div", L, [w(l, null, {
					default: j(() => [S("form", {
						class: "flex flex-wrap items-start gap-3",
						"data-testid": "showroom-products-filters",
						onSubmit: M(_e, ["prevent"])
					}, [
						w(s, { label: k(H)("showroom.catalog.filters.search") }, {
							default: j(() => [w(o, {
								modelValue: k(Z).search,
								"onUpdate:modelValue": r[0] ||= (e) => k(Z).search = e,
								name: "search",
								class: "w-64",
								"data-testid": "showroom-products-search"
							}, null, 8, ["modelValue"])]),
							_: 1
						}, 8, ["label"]),
						w(s, { label: k(H)("showroom.catalog.fields.family") }, {
							default: j(() => [w(c, {
								modelValue: k(Z).family,
								"onUpdate:modelValue": r[1] ||= (e) => k(Z).family = e,
								items: k(me),
								blank: k(H)("showroom.catalog.filters.all_families"),
								"blank-value": null,
								class: "w-48",
								"data-testid": "showroom-products-family"
							}, null, 8, [
								"modelValue",
								"items",
								"blank"
							])]),
							_: 1
						}, 8, ["label"]),
						w(s, { label: k(H)("showroom.catalog.fields.category") }, {
							default: j(() => [w(o, {
								modelValue: k(Z).category,
								"onUpdate:modelValue": r[2] ||= (e) => k(Z).category = e,
								name: "category",
								class: "w-44",
								"data-testid": "showroom-products-category"
							}, null, 8, ["modelValue"])]),
							_: 1
						}, 8, ["label"]),
						w(s, { label: k(H)("showroom.catalog.fields.owner") }, {
							default: j(() => [w(o, {
								modelValue: k(Z).owner,
								"onUpdate:modelValue": r[3] ||= (e) => k(Z).owner = e,
								name: "owner",
								class: "w-44",
								"data-testid": "showroom-products-owner"
							}, null, 8, ["modelValue"])]),
							_: 1
						}, 8, ["label"]),
						w(s, { label: k(H)("showroom.catalog.fields.workflow") }, {
							default: j(() => [w(c, {
								modelValue: k(Z).status,
								"onUpdate:modelValue": r[4] ||= (e) => k(Z).status = e,
								items: k(he),
								blank: k(H)("showroom.catalog.filters.all_statuses"),
								"blank-value": null,
								class: "w-40",
								"data-testid": "showroom-products-status"
							}, null, 8, [
								"modelValue",
								"items",
								"blank"
							])]),
							_: 1
						}, 8, ["label"]),
						w(s, { label: k(H)("showroom.catalog.fields.enabled") }, {
							default: j(() => [w(c, {
								modelValue: k(Z).enabled,
								"onUpdate:modelValue": r[5] ||= (e) => k(Z).enabled = e,
								items: k(ge),
								blank: k(H)("showroom.catalog.filters.all_statuses"),
								"blank-value": null,
								class: "w-40",
								"data-testid": "showroom-products-enabled"
							}, null, 8, [
								"modelValue",
								"items",
								"blank"
							])]),
							_: 1
						}, 8, ["label"]),
						w(i, {
							type: "submit",
							icon: "i-lucide-filter",
							class: "mt-field-label",
							"data-testid": "showroom-products-filter"
						}, {
							default: j(() => [C(O(k(H)("showroom.catalog.filters.filter")), 1)]),
							_: 1
						}),
						w(i, {
							variant: "ghost",
							icon: "i-lucide-x",
							class: "mt-field-label",
							"data-testid": "showroom-products-clear",
							onClick: ve
						}, {
							default: j(() => [C(O(k(H)("showroom.catalog.filters.clear")), 1)]),
							_: 1
						})
					], 32)]),
					_: 1
				}), w(t, {
					ready: !k(ce) && k(Y) !== null,
					failed: k(le),
					status: k(ue)
				}, {
					default: j(() => [S("div", R, [w(D, {
						data: k(de),
						columns: k($),
						"data-testid": "showroom-products-table"
					}, {
						empty: j(() => [w(d, {
							compact: "",
							title: k(H)("showroom.catalog.products.empty"),
							"data-testid": "showroom-products-empty"
						}, null, 8, ["title"])]),
						"identifier-cell": j(({ row: { original: e } }) => [S("span", { "data-testid": `showroom-product-row-${e.id}` }, [w(v, {
							to: k(ne)(e.id),
							class: "font-mono font-medium hover:underline"
						}, {
							default: j(() => [C(O(e.identifier), 1)]),
							_: 2
						}, 1032, ["to"])], 8, z)]),
						"label-cell": j(({ row: { original: e } }) => [C(O(fe(e)), 1)]),
						"family-cell": j(({ row: { original: e } }) => [C(O(pe(e.family)), 1)]),
						"parent-cell": j(({ row: { original: e } }) => [e.parent ? (E(), y(v, {
							key: 0,
							to: k(u)(e.parent),
							class: "font-mono text-xs hover:underline"
						}, {
							default: j(() => [C(O(e.parent), 1)]),
							_: 2
						}, 1032, ["to"])) : (E(), x("span", B, O(k(H)("showroom.catalog.none")), 1))]),
						"enabled-cell": j(({ row: { original: e } }) => [w(T, {
							color: e.enabled ? "success" : "neutral",
							variant: "subtle",
							"data-status": e.enabled ? "enabled" : "disabled"
						}, {
							default: j(() => [C(O(e.enabled ? k(H)("showroom.catalog.enabled") : k(H)("showroom.catalog.disabled")), 1)]),
							_: 2
						}, 1032, ["color", "data-status"])]),
						"status-cell": j(({ row: { original: e } }) => [S("span", V, [w(T, {
							color: k(ee)(e.status),
							variant: "subtle",
							"data-status": e.status
						}, {
							default: j(() => [C(O(k(H)(`showroom.statuses.${e.status}`)), 1)]),
							_: 2
						}, 1032, ["color", "data-status"]), e.published_version ? (E(), y(T, {
							key: 0,
							color: "neutral",
							variant: "outline"
						}, {
							default: j(() => [C("v" + O(e.published_version), 1)]),
							_: 2
						}, 1024)) : b("", !0)])]),
						"updated_at-cell": j(({ row: { original: e } }) => [C(O(k(te)(e.updated_at) ?? k(H)("showroom.catalog.none")), 1)]),
						_: 1
					}, 8, ["data", "columns"]), w(e, {
						current: k(X).current,
						last: k(X).last,
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
