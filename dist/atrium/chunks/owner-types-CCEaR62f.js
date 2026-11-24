import { t as e } from "./CatalogActionError-BRUgojP9.js";
import { t } from "./CatalogPager-BwJ4cMnn.js";
import { t as ee } from "./CatalogState-Bkly-MlG.js";
import { C as n, D as r, O as i, S as a, f as te, k as o } from "./catalog-DCoO3A8a.js";
import { g as s } from "./structure-DbqcM-iO.js";
import { t as ne } from "./OwnerTypeRules-DC0UsmJV.js";
import { a as c, i as l, r as u } from "./owners-C7wgNkGA.js";
import { AtriumIntegrationScreen as d } from "@refactor-circus/atrium";
import { UButton as f, UCard as re, UFormField as ie, UInput as ae } from "@refactor-circus/atrium/ui";
import { Fragment as oe, computed as p, createElementBlock as m, createElementVNode as h, createTextVNode as g, createVNode as _, defineComponent as v, isRef as se, openBlock as y, reactive as ce, ref as le, renderList as ue, resolveComponent as b, shallowRef as x, toDisplayString as S, unref as C, withCtx as w, withModifiers as T } from "vue";
import { useI18n as E } from "vue-i18n";
import { useRoute as D } from "vue-router";
// src/pages/owner-types/index.vue?vue&type=script&setup=true&lang.ts
var O = {
	class: "space-y-4",
	"data-testid": "showroom-owner-types-page"
}, k = { class: "flex flex-wrap items-start justify-between gap-3" }, A = { class: "text-xl font-semibold" }, j = { class: "text-sm text-muted" }, M = { class: "space-y-4" }, N = { class: "font-semibold" }, P = { class: "flex flex-wrap items-end gap-3" }, F = { class: "space-y-4" }, I = {
	key: 0,
	class: "atrium-empty",
	"data-testid": "showroom-owner-types-empty"
}, L = {
	key: 1,
	class: "w-full text-left text-sm",
	"data-testid": "showroom-owner-types-table"
}, R = { class: "text-muted" }, z = { class: "py-2 pr-3 font-medium" }, B = { class: "py-2 pr-3 font-medium" }, V = { class: "py-2 pr-3 font-medium" }, H = { class: "py-2 pr-3 font-medium" }, de = { class: "py-2 pr-3 font-medium" }, fe = { class: "py-2 font-medium" }, pe = { class: "divide-y divide-default" }, me = ["data-testid"], he = { class: "py-2 pr-3" }, ge = { class: "py-2 pr-3" }, _e = { class: "py-2 pr-3 font-mono text-xs" }, ve = { class: "py-2 pr-3" }, ye = { class: "py-2 pr-3" }, be = { class: "py-2 tabular-nums" }, U = /* @__PURE__ */ v({
	__name: "index",
	setup(v) {
		let { t: U, locale: W } = E(), xe = D(), G = i(), K = p(() => {
			let e = a(xe.query.cursor);
			return e ? { cursor: e } : {};
		}), { client: q, data: J, pending: Se, failed: Ce, status: we, reload: Te } = o(async (e) => n(await e.GET("/owner-types", { params: { query: r(K.value) } })), K), Y = p(() => J.value?.data ?? []), X = x([]);
		async function Z() {
			if (q) try {
				X.value = n(await q.GET("/owner-types", { params: { query: { per_page: 100 } } })).data;
			} catch {
				X.value = [];
			}
		}
		Z();
		let Q = ce({
			code: "",
			label: ""
		}), $ = le(l());
		async function Ee() {
			if (!q) return;
			let e = {
				code: Q.code.trim(),
				...s(W.value, Q.label),
				...c($.value, X.value.map((e) => e.code))
			};
			await G.run(async () => n(await q.POST("/owner-types", { body: r(e) }))) && (Q.code = "", Q.label = "", $.value = l(), await Promise.all([Te(), Z()]));
		}
		function De(e) {
			return e.parent_types === null || e.parent_types === void 0 ? U("showroom.owner_types.any") : e.parent_types.length === 0 ? U("showroom.catalog.none") : e.parent_types.join(", ");
		}
		return (n, r) => {
			let i = f, a = ae, o = ie, s = re, c = b("RouterLink"), l = d;
			return y(), m("div", O, [h("div", k, [h("div", null, [h("h1", A, S(C(U)("showroom.owner_types.title")), 1), h("p", j, S(C(U)("showroom.owner_types.description")), 1)]), _(i, {
				to: "/showroom/owners",
				variant: "outline",
				icon: "i-lucide-building-2"
			}, {
				default: w(() => [g(S(C(U)("showroom.owners.title")), 1)]),
				_: 1
			})]), _(l, { "screen-key": "showroom.owner-types" }, {
				default: w(() => [h("div", M, [_(s, { "data-testid": "showroom-owner-type-new" }, {
					header: w(() => [h("h2", N, S(C(U)("showroom.owner_types.new")), 1)]),
					default: w(() => [h("form", {
						class: "flex flex-col gap-4",
						"data-testid": "showroom-owner-type-create-form",
						onSubmit: T(Ee, ["prevent"])
					}, [
						_(e, { action: C(G) }, null, 8, ["action"]),
						h("div", P, [_(o, {
							label: C(U)("showroom.catalog.fields.code"),
							hint: C(U)("showroom.owner_types.code_hint"),
							error: C(G).error("code"),
							required: ""
						}, {
							default: w(() => [_(a, {
								modelValue: C(Q).code,
								"onUpdate:modelValue": r[0] ||= (e) => C(Q).code = e,
								required: "",
								class: "w-56",
								"data-testid": "showroom-owner-type-create-code"
							}, null, 8, ["modelValue"])]),
							_: 1
						}, 8, [
							"label",
							"hint",
							"error"
						]), _(o, {
							label: C(U)("showroom.catalog.fields.label_in", { locale: C(W) }),
							error: C(G).error("labels")
						}, {
							default: w(() => [_(a, {
								modelValue: C(Q).label,
								"onUpdate:modelValue": r[1] ||= (e) => C(Q).label = e,
								class: "w-56",
								"data-testid": "showroom-owner-type-create-label"
							}, null, 8, ["modelValue"])]),
							_: 1
						}, 8, ["label", "error"])]),
						_(ne, {
							modelValue: C($),
							"onUpdate:modelValue": r[2] ||= (e) => se($) ? $.value = e : null,
							types: C(X),
							action: C(G),
							testid: "showroom-owner-type-create"
						}, null, 8, [
							"modelValue",
							"types",
							"action"
						]),
						h("div", null, [_(i, {
							type: "submit",
							icon: "i-lucide-plus",
							loading: C(G).busy.value,
							"data-testid": "showroom-owner-type-create-submit"
						}, {
							default: w(() => [g(S(C(U)("showroom.catalog.create")), 1)]),
							_: 1
						}, 8, ["loading"])])
					], 32)]),
					_: 1
				}), _(ee, {
					ready: !C(Se) && C(J) !== null,
					failed: C(Ce),
					status: C(we)
				}, {
					default: w(() => [h("div", F, [C(Y).length === 0 ? (y(), m("p", I, S(C(U)("showroom.owner_types.empty")), 1)) : (y(), m("table", L, [h("thead", R, [h("tr", null, [
						h("th", z, S(C(U)("showroom.catalog.fields.code")), 1),
						h("th", B, S(C(U)("showroom.catalog.fields.label")), 1),
						h("th", V, S(C(U)("showroom.owner_types.parent_types")), 1),
						h("th", H, S(C(U)("showroom.owner_types.can_be_root")), 1),
						h("th", de, S(C(U)("showroom.owner_types.owns_products")), 1),
						h("th", fe, S(C(U)("showroom.owner_types.owners")), 1)
					])]), h("tbody", pe, [(y(!0), m(oe, null, ue(C(Y), (e) => (y(), m("tr", {
						key: e.code,
						"data-testid": `showroom-owner-type-row-${e.code}`
					}, [
						h("td", he, [_(c, {
							to: C(u)(e.code),
							class: "font-mono font-medium hover:underline"
						}, {
							default: w(() => [g(S(e.code), 1)]),
							_: 2
						}, 1032, ["to"])]),
						h("td", ge, S(C(te)(e.labels, C(W), e.code)), 1),
						h("td", _e, S(De(e)), 1),
						h("td", ve, S(e.can_be_root ? C(U)("showroom.catalog.yes") : C(U)("showroom.catalog.no")), 1),
						h("td", ye, S(e.owns_products ? C(U)("showroom.catalog.yes") : C(U)("showroom.catalog.no")), 1),
						h("td", be, S(e.owners_count ?? 0), 1)
					], 8, me))), 128))])])), _(t, {
						next: C(J)?.meta?.next_cursor ?? null,
						prev: C(J)?.meta?.prev_cursor ?? null,
						testid: "showroom-owner-types"
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
