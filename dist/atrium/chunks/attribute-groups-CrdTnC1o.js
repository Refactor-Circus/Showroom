import { t as e } from "./CatalogActionError-BRUgojP9.js";
import { t } from "./CatalogPager-BwJ4cMnn.js";
import { t as n } from "./CatalogState-Bkly-MlG.js";
import { C as r, D as i, O as a, S as o, f as s, k as c } from "./catalog-DCoO3A8a.js";
import { b as l, g as u, i as d } from "./structure-DbqcM-iO.js";
import { AtriumIntegrationScreen as f, AtriumPageHeader as p, AtriumState as m } from "@refactor-circus/atrium";
import { UButton as h, UCard as g, UFormField as ee, UInput as _, ULink as v, UTable as y } from "@refactor-circus/atrium/ui";
import { computed as b, createElementBlock as x, createElementVNode as S, createTextVNode as C, createVNode as w, defineComponent as T, openBlock as E, reactive as D, toDisplayString as O, unref as k, withCtx as A, withModifiers as j } from "vue";
import { useI18n as M } from "vue-i18n";
import { useRoute as N } from "vue-router";
// src/pages/attribute-groups/index.vue?vue&type=script&setup=true&lang.ts
var P = {
	class: "space-y-4",
	"data-testid": "showroom-attribute-groups"
}, F = { class: "space-y-4" }, I = { class: "font-semibold" }, L = { class: "space-y-4" }, R = ["data-testid"], z = { class: "tabular-nums" }, B = { class: "tabular-nums" }, V = /* @__PURE__ */ T({
	__name: "index",
	setup(T) {
		let { t: V, locale: H } = M(), U = N(), W = a(), G = b(() => {
			let e = o(U.query.cursor);
			return e ? { cursor: e } : {};
		}), { client: K, data: q, pending: J, failed: Y, status: X, reload: Z } = c(async (e) => r(await e.GET("/attribute-groups", { params: { query: G.value } })), G), Q = b(() => q.value?.data ?? []), te = b(() => [
			{
				accessorKey: "code",
				header: V("showroom.catalog.fields.code")
			},
			{
				id: "label",
				header: V("showroom.catalog.fields.label")
			},
			{
				accessorKey: "attributes_count",
				header: V("showroom.structure.groups.attributes_count"),
				meta: { class: {
					th: "text-right",
					td: "text-right"
				} }
			},
			{
				accessorKey: "sort_order",
				header: V("showroom.catalog.fields.sort_order"),
				meta: { class: {
					th: "text-right",
					td: "text-right"
				} }
			}
		]), $ = D({
			code: "",
			label: "",
			sort_order: "0"
		});
		async function ne() {
			let e = {
				code: $.code.trim(),
				...u(H.value, $.label),
				...l($.sort_order)
			};
			K && await W.run(async () => r(await K.POST("/attribute-groups", { body: i(e) }))) && ($.code = "", $.label = "", $.sort_order = "0", await Z());
		}
		return (r, i) => {
			let a = p, o = _, c = ee, l = h, u = g, b = m, T = v, D = y, M = f;
			return E(), x("div", P, [w(a, { title: k(V)("showroom.structure.groups.title") }, null, 8, ["title"]), w(M, { "screen-key": "showroom.attribute-groups" }, {
				default: A(() => [S("div", F, [w(u, { "data-testid": "showroom-group-new" }, {
					header: A(() => [S("h2", I, O(k(V)("showroom.structure.groups.new")), 1)]),
					default: A(() => [S("form", {
						class: "flex flex-wrap items-start gap-3",
						"data-testid": "showroom-group-create-form",
						onSubmit: j(ne, ["prevent"])
					}, [
						w(c, {
							label: k(V)("showroom.catalog.fields.code"),
							hint: k(V)("showroom.structure.code_hint"),
							error: k(W).error("code"),
							required: ""
						}, {
							default: A(() => [w(o, {
								modelValue: k($).code,
								"onUpdate:modelValue": i[0] ||= (e) => k($).code = e,
								required: "",
								class: "w-56",
								"data-testid": "showroom-group-create-code"
							}, null, 8, ["modelValue"])]),
							_: 1
						}, 8, [
							"label",
							"hint",
							"error"
						]),
						w(c, {
							label: k(V)("showroom.catalog.fields.label_in", { locale: k(H) }),
							error: k(W).error("labels")
						}, {
							default: A(() => [w(o, {
								modelValue: k($).label,
								"onUpdate:modelValue": i[1] ||= (e) => k($).label = e,
								class: "w-56",
								"data-testid": "showroom-group-create-label"
							}, null, 8, ["modelValue"])]),
							_: 1
						}, 8, ["label", "error"]),
						w(c, {
							label: k(V)("showroom.catalog.fields.sort_order"),
							error: k(W).error("sort_order")
						}, {
							default: A(() => [w(o, {
								modelValue: k($).sort_order,
								"onUpdate:modelValue": i[2] ||= (e) => k($).sort_order = e,
								type: "number",
								min: "0",
								class: "w-28",
								"data-testid": "showroom-group-create-sort"
							}, null, 8, ["modelValue"])]),
							_: 1
						}, 8, ["label", "error"]),
						w(l, {
							type: "submit",
							icon: "i-lucide-plus",
							loading: k(W).busy.value,
							class: "mt-field-label",
							"data-testid": "showroom-group-create-submit"
						}, {
							default: A(() => [C(O(k(V)("showroom.structure.groups.create")), 1)]),
							_: 1
						}, 8, ["loading"])
					], 32), w(e, {
						action: k(W),
						class: "mt-3"
					}, null, 8, ["action"])]),
					_: 1
				}), w(n, {
					ready: !k(J) && k(q) !== null,
					failed: k(Y),
					status: k(X)
				}, {
					default: A(() => [S("div", L, [w(D, {
						data: k(Q),
						columns: k(te),
						"data-testid": "showroom-attribute-groups-table"
					}, {
						empty: A(() => [w(b, {
							compact: "",
							title: k(V)("showroom.structure.groups.empty"),
							"data-testid": "showroom-attribute-groups-empty"
						}, null, 8, ["title"])]),
						"code-cell": A(({ row: { original: e } }) => [S("span", { "data-testid": `showroom-group-row-${e.code}` }, [w(T, {
							to: k(d)(e.code),
							class: "font-mono font-medium hover:underline"
						}, {
							default: A(() => [C(O(e.code), 1)]),
							_: 2
						}, 1032, ["to"])], 8, R)]),
						"label-cell": A(({ row: { original: e } }) => [C(O(k(s)(e.labels, k(H), e.code)), 1)]),
						"attributes_count-cell": A(({ row: { original: e } }) => [S("span", z, O(e.attributes_count ?? k(V)("showroom.catalog.none")), 1)]),
						"sort_order-cell": A(({ row: { original: e } }) => [S("span", B, O(e.sort_order), 1)]),
						_: 1
					}, 8, ["data", "columns"]), w(t, {
						next: k(q)?.meta?.next_cursor ?? null,
						prev: k(q)?.meta?.prev_cursor ?? null,
						testid: "showroom-attribute-groups"
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
