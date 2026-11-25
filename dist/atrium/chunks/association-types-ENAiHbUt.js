import { t as e } from "./CatalogActionError-BRUgojP9.js";
import { t } from "./CatalogPager-BwJ4cMnn.js";
import { t as n } from "./CatalogState-Bkly-MlG.js";
import { C as r, D as i, O as a, S as o, f as s, k as c } from "./catalog-DCoO3A8a.js";
import { g as l } from "./structure-DbqcM-iO.js";
import { AtriumIntegrationScreen as u, AtriumPageHeader as d, AtriumState as ee } from "@refactor-circus/atrium";
import { UBadge as f, UButton as p, UCard as m, UCheckbox as h, UFormField as g, UInput as _, UTable as v } from "@refactor-circus/atrium/ui";
import { computed as y, createBlock as b, createCommentVNode as x, createElementBlock as te, createElementVNode as S, createTextVNode as C, createVNode as w, defineComponent as T, openBlock as E, reactive as D, toDisplayString as O, unref as k, withCtx as A, withModifiers as j } from "vue";
import { useI18n as M } from "vue-i18n";
import { useRoute as N } from "vue-router";
// src/pages/association-types/index.vue?vue&type=script&setup=true&lang.ts
var P = {
	class: "space-y-4",
	"data-testid": "showroom-association-types"
}, F = { class: "space-y-4" }, I = { class: "font-semibold" }, L = { class: "space-y-4" }, R = ["data-testid"], z = { class: "flex flex-wrap gap-1" }, B = { class: "tabular-nums" }, V = /* @__PURE__ */ T({
	__name: "index",
	setup(T) {
		let { t: V, locale: H } = M(), U = N(), W = a(), G = a(), K = y(() => {
			let e = o(U.query.cursor);
			return e ? { cursor: e } : {};
		}), { client: q, data: J, pending: Y, failed: X, status: Z, reload: Q } = c(async (e) => r(await e.GET("/association-types", { params: { query: K.value } })), K), ne = y(() => J.value?.data ?? []), $ = D({
			code: "",
			label: "",
			is_two_way: !1,
			is_quantified: !1
		}), re = y(() => [
			{
				accessorKey: "code",
				header: V("showroom.catalog.fields.code")
			},
			{
				id: "label",
				header: V("showroom.catalog.fields.label")
			},
			{
				id: "switches",
				header: V("showroom.structure.attributes.switches")
			},
			{
				accessorKey: "associations_count",
				header: V("showroom.structure.association_types.associations"),
				meta: { class: {
					th: "text-right",
					td: "text-right"
				} }
			},
			{
				id: "actions",
				header: ""
			}
		]);
		async function ie() {
			let e = {
				code: $.code.trim(),
				...l(H.value, $.label),
				is_two_way: $.is_two_way,
				is_quantified: $.is_quantified
			};
			q && await W.run(async () => r(await q.POST("/association-types", { body: i(e) }))) && ($.code = "", $.label = "", $.is_two_way = !1, $.is_quantified = !1, await Q());
		}
		async function ae(e) {
			q && window.confirm(V("showroom.structure.association_types.confirm_delete", { code: e })) && await G.run(async () => r(await q.DELETE("/association-types/{associationType}", { params: { path: { associationType: e } } }))) && await Q();
		}
		return (r, i) => {
			let a = d, o = _, c = g, l = h, y = p, T = m, D = ee, M = f, N = v, U = u;
			return E(), te("div", P, [w(a, {
				title: k(V)("showroom.structure.association_types.title"),
				description: k(V)("showroom.structure.association_types.description")
			}, null, 8, ["title", "description"]), w(U, { "screen-key": "showroom.association-types" }, {
				default: A(() => [S("div", F, [
					w(T, { "data-testid": "showroom-association-type-new" }, {
						header: A(() => [S("h2", I, O(k(V)("showroom.structure.association_types.new")), 1)]),
						default: A(() => [S("form", {
							class: "flex flex-wrap items-start gap-3",
							"data-testid": "showroom-association-type-create-form",
							onSubmit: j(ie, ["prevent"])
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
									"data-testid": "showroom-association-type-create-code"
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
									"data-testid": "showroom-association-type-create-label"
								}, null, 8, ["modelValue"])]),
								_: 1
							}, 8, ["label", "error"]),
							w(l, {
								modelValue: k($).is_two_way,
								"onUpdate:modelValue": i[2] ||= (e) => k($).is_two_way = e,
								label: k(V)("showroom.structure.association_types.two_way"),
								class: "mt-field-label h-field items-center",
								"data-testid": "showroom-association-type-create-two-way"
							}, null, 8, ["modelValue", "label"]),
							w(l, {
								modelValue: k($).is_quantified,
								"onUpdate:modelValue": i[3] ||= (e) => k($).is_quantified = e,
								label: k(V)("showroom.structure.association_types.quantified"),
								class: "mt-field-label h-field items-center",
								"data-testid": "showroom-association-type-create-quantified"
							}, null, 8, ["modelValue", "label"]),
							w(y, {
								type: "submit",
								icon: "i-lucide-plus",
								loading: k(W).busy.value,
								class: "mt-field-label",
								"data-testid": "showroom-association-type-create-submit"
							}, {
								default: A(() => [C(O(k(V)("showroom.catalog.create")), 1)]),
								_: 1
							}, 8, ["loading"])
						], 32), w(e, {
							action: k(W),
							class: "mt-3"
						}, null, 8, ["action"])]),
						_: 1
					}),
					w(e, { action: k(G) }, null, 8, ["action"]),
					w(n, {
						ready: !k(Y) && k(J) !== null,
						failed: k(X),
						status: k(Z)
					}, {
						default: A(() => [S("div", L, [w(N, {
							data: k(ne),
							columns: k(re),
							"data-testid": "showroom-association-types-table"
						}, {
							empty: A(() => [w(D, {
								compact: "",
								title: k(V)("showroom.structure.association_types.empty"),
								"data-testid": "showroom-association-types-empty"
							}, null, 8, ["title"])]),
							"code-cell": A(({ row: { original: e } }) => [S("span", {
								class: "font-mono",
								"data-testid": `showroom-association-type-row-${e.code}`
							}, O(e.code), 9, R)]),
							"label-cell": A(({ row: { original: e } }) => [C(O(k(s)(e.labels, k(H), e.code)), 1)]),
							"switches-cell": A(({ row: { original: e } }) => [S("span", z, [e.is_two_way ? (E(), b(M, {
								key: 0,
								color: "neutral",
								variant: "outline"
							}, {
								default: A(() => [C(O(k(V)("showroom.structure.association_types.two_way")), 1)]),
								_: 1
							})) : x("", !0), e.is_quantified ? (E(), b(M, {
								key: 1,
								color: "neutral",
								variant: "outline"
							}, {
								default: A(() => [C(O(k(V)("showroom.structure.association_types.quantified")), 1)]),
								_: 1
							})) : x("", !0)])]),
							"associations_count-cell": A(({ row: { original: e } }) => [S("span", B, O(e.associations_count ?? k(V)("showroom.catalog.none")), 1)]),
							"actions-cell": A(({ row: { original: e } }) => [w(y, {
								variant: "ghost",
								size: "sm",
								icon: "i-lucide-trash-2",
								"aria-label": k(V)("showroom.catalog.delete"),
								loading: k(G).busy.value,
								"data-testid": `showroom-association-type-delete-${e.code}`,
								onClick: (t) => ae(e.code)
							}, null, 8, [
								"aria-label",
								"loading",
								"data-testid",
								"onClick"
							])]),
							_: 1
						}, 8, ["data", "columns"]), w(t, {
							next: k(J)?.meta?.next_cursor ?? null,
							prev: k(J)?.meta?.prev_cursor ?? null,
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
export { V as default };
