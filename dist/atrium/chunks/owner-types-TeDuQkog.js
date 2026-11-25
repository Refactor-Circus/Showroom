import { t as e } from "./CatalogActionError-BRUgojP9.js";
import { t } from "./CatalogPager-BwJ4cMnn.js";
import { t as ee } from "./CatalogState-Bkly-MlG.js";
import { C as n, D as r, O as i, S as a, f as te, k as o } from "./catalog-DCoO3A8a.js";
import { g as s } from "./structure-DbqcM-iO.js";
import { t as c } from "./OwnerTypeRules-CvmgahMf.js";
import { a as l, i as u, r as ne } from "./owners-C7wgNkGA.js";
import { AtriumIntegrationScreen as re, AtriumPageHeader as d, AtriumState as f } from "@refactor-circus/atrium";
import { UButton as p, UCard as m, UFormField as h, UInput as g, ULink as _, UTable as v } from "@refactor-circus/atrium/ui";
import { computed as y, createElementBlock as ie, createElementVNode as b, createTextVNode as x, createVNode as S, defineComponent as C, isRef as ae, openBlock as w, reactive as T, ref as E, shallowRef as D, toDisplayString as O, unref as k, withCtx as A, withModifiers as j } from "vue";
import { useI18n as M } from "vue-i18n";
import { useRoute as N } from "vue-router";
// src/pages/owner-types/index.vue?vue&type=script&setup=true&lang.ts
var P = {
	class: "space-y-4",
	"data-testid": "showroom-owner-types-page"
}, F = { class: "space-y-4" }, I = { class: "font-semibold" }, L = { class: "flex flex-wrap items-start gap-3" }, R = { class: "space-y-4" }, oe = ["data-testid"], z = { class: "font-mono text-xs" }, B = { class: "tabular-nums" }, V = /* @__PURE__ */ C({
	__name: "index",
	setup(C) {
		let { t: V, locale: H } = M(), U = N(), W = i(), G = y(() => [
			{
				accessorKey: "code",
				header: V("showroom.catalog.fields.code")
			},
			{
				id: "label",
				header: V("showroom.catalog.fields.label")
			},
			{
				accessorKey: "parent_types",
				header: V("showroom.owner_types.parent_types")
			},
			{
				accessorKey: "can_be_root",
				header: V("showroom.owner_types.can_be_root")
			},
			{
				accessorKey: "owns_products",
				header: V("showroom.owner_types.owns_products")
			},
			{
				accessorKey: "owners_count",
				header: V("showroom.owner_types.owners"),
				meta: { class: {
					th: "text-right",
					td: "text-right"
				} }
			}
		]), K = y(() => {
			let e = a(U.query.cursor);
			return e ? { cursor: e } : {};
		}), { client: q, data: J, pending: se, failed: ce, status: le, reload: ue } = o(async (e) => n(await e.GET("/owner-types", { params: { query: r(K.value) } })), K), Y = y(() => J.value?.data ?? []), X = D([]);
		async function Z() {
			if (q) try {
				X.value = n(await q.GET("/owner-types", { params: { query: { per_page: 100 } } })).data;
			} catch {
				X.value = [];
			}
		}
		Z();
		let Q = T({
			code: "",
			label: ""
		}), $ = E(u());
		async function de() {
			if (!q) return;
			let e = {
				code: Q.code.trim(),
				...s(H.value, Q.label),
				...l($.value, X.value.map((e) => e.code))
			};
			await W.run(async () => n(await q.POST("/owner-types", { body: r(e) }))) && (Q.code = "", Q.label = "", $.value = u(), await Promise.all([ue(), Z()]));
		}
		function fe(e) {
			return e.parent_types === null || e.parent_types === void 0 ? V("showroom.owner_types.any") : e.parent_types.length === 0 ? V("showroom.catalog.none") : e.parent_types.join(", ");
		}
		return (n, r) => {
			let i = p, a = d, o = g, s = h, l = m, u = f, y = _, C = v, T = re;
			return w(), ie("div", P, [S(a, {
				title: k(V)("showroom.owner_types.title"),
				description: k(V)("showroom.owner_types.description")
			}, {
				default: A(() => [S(i, {
					to: "/showroom/owners",
					variant: "outline",
					icon: "i-lucide-building-2"
				}, {
					default: A(() => [x(O(k(V)("showroom.owners.title")), 1)]),
					_: 1
				})]),
				_: 1
			}, 8, ["title", "description"]), S(T, { "screen-key": "showroom.owner-types" }, {
				default: A(() => [b("div", F, [S(l, { "data-testid": "showroom-owner-type-new" }, {
					header: A(() => [b("h2", I, O(k(V)("showroom.owner_types.new")), 1)]),
					default: A(() => [b("form", {
						class: "flex flex-col gap-4",
						"data-testid": "showroom-owner-type-create-form",
						onSubmit: j(de, ["prevent"])
					}, [
						S(e, { action: k(W) }, null, 8, ["action"]),
						b("div", L, [S(s, {
							label: k(V)("showroom.catalog.fields.code"),
							hint: k(V)("showroom.owner_types.code_hint"),
							error: k(W).error("code"),
							required: ""
						}, {
							default: A(() => [S(o, {
								modelValue: k(Q).code,
								"onUpdate:modelValue": r[0] ||= (e) => k(Q).code = e,
								required: "",
								class: "w-56",
								"data-testid": "showroom-owner-type-create-code"
							}, null, 8, ["modelValue"])]),
							_: 1
						}, 8, [
							"label",
							"hint",
							"error"
						]), S(s, {
							label: k(V)("showroom.catalog.fields.label_in", { locale: k(H) }),
							error: k(W).error("labels")
						}, {
							default: A(() => [S(o, {
								modelValue: k(Q).label,
								"onUpdate:modelValue": r[1] ||= (e) => k(Q).label = e,
								class: "w-56",
								"data-testid": "showroom-owner-type-create-label"
							}, null, 8, ["modelValue"])]),
							_: 1
						}, 8, ["label", "error"])]),
						S(c, {
							modelValue: k($),
							"onUpdate:modelValue": r[2] ||= (e) => ae($) ? $.value = e : null,
							types: k(X),
							action: k(W),
							testid: "showroom-owner-type-create"
						}, null, 8, [
							"modelValue",
							"types",
							"action"
						]),
						b("div", null, [S(i, {
							type: "submit",
							icon: "i-lucide-plus",
							loading: k(W).busy.value,
							"data-testid": "showroom-owner-type-create-submit"
						}, {
							default: A(() => [x(O(k(V)("showroom.catalog.create")), 1)]),
							_: 1
						}, 8, ["loading"])])
					], 32)]),
					_: 1
				}), S(ee, {
					ready: !k(se) && k(J) !== null,
					failed: k(ce),
					status: k(le)
				}, {
					default: A(() => [b("div", R, [S(C, {
						data: k(Y),
						columns: k(G),
						"data-testid": "showroom-owner-types-table"
					}, {
						empty: A(() => [S(u, {
							compact: "",
							title: k(V)("showroom.owner_types.empty"),
							"data-testid": "showroom-owner-types-empty"
						}, null, 8, ["title"])]),
						"code-cell": A(({ row: { original: e } }) => [b("span", { "data-testid": `showroom-owner-type-row-${e.code}` }, [S(y, {
							to: k(ne)(e.code),
							class: "font-mono font-medium hover:underline"
						}, {
							default: A(() => [x(O(e.code), 1)]),
							_: 2
						}, 1032, ["to"])], 8, oe)]),
						"label-cell": A(({ row: { original: e } }) => [x(O(k(te)(e.labels, k(H), e.code)), 1)]),
						"parent_types-cell": A(({ row: { original: e } }) => [b("span", z, O(fe(e)), 1)]),
						"can_be_root-cell": A(({ row: { original: e } }) => [x(O(e.can_be_root ? k(V)("showroom.catalog.yes") : k(V)("showroom.catalog.no")), 1)]),
						"owns_products-cell": A(({ row: { original: e } }) => [x(O(e.owns_products ? k(V)("showroom.catalog.yes") : k(V)("showroom.catalog.no")), 1)]),
						"owners_count-cell": A(({ row: { original: e } }) => [b("span", B, O(e.owners_count ?? 0), 1)]),
						_: 1
					}, 8, ["data", "columns"]), S(t, {
						next: k(J)?.meta?.next_cursor ?? null,
						prev: k(J)?.meta?.prev_cursor ?? null,
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
export { V as default };
