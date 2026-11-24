import { t as e } from "./CatalogActionError-BRUgojP9.js";
import { t } from "./CatalogState-Bkly-MlG.js";
import { C as n, O as r, f as i, k as a, l as o, v as s } from "./catalog-DCoO3A8a.js";
import { AtriumIntegrationScreen as c, navigateTo as l } from "@refactor-circus/atrium";
import { UButton as u, UCard as ee, ULink as d, UPageHeader as f, UTable as p } from "@refactor-circus/atrium/ui";
import { Fragment as m, computed as h, createBlock as g, createCommentVNode as _, createElementBlock as v, createElementVNode as y, createTextVNode as b, createVNode as x, defineComponent as S, openBlock as C, renderList as w, toDisplayString as T, unref as E, withCtx as D } from "vue";
import { useI18n as O } from "vue-i18n";
import { useRoute as k } from "vue-router";
// src/pages/family-variants/[familyVariant].vue?vue&type=script&setup=true&lang.ts
var A = {
	class: "space-y-4",
	"data-testid": "showroom-family-variant"
}, j = {
	key: 0,
	class: "space-y-5"
}, M = { "data-testid": "showroom-family-variant-title" }, N = { class: "font-mono" }, P = { class: "font-semibold" }, F = ["data-testid"], I = { class: "font-mono text-xs" }, L = { class: "font-mono text-xs" }, R = { class: "font-semibold" }, z = {
	key: 0,
	class: "text-sm text-muted"
}, B = {
	key: 1,
	class: "flex flex-wrap gap-2"
}, V = /* @__PURE__ */ S({
	__name: "[familyVariant]",
	setup(S) {
		let { t: V, locale: H } = O(), U = k(), W = r(), G = h(() => String(U.params.familyVariant)), { client: K, data: q, pending: J, failed: Y, status: X } = a(async (e) => ({
			variant: n(await e.GET("/family-variants/{familyVariant}", { params: { path: { familyVariant: G.value } } })).data,
			models: n(await e.GET("/product-models", { params: { query: {
				family_variant: G.value,
				per_page: 100
			} } })).data
		}), G), Z = h(() => q.value?.variant ?? null), Q = h(() => [
			{
				accessorKey: "level",
				header: V("showroom.catalog.family_variants.level")
			},
			{
				accessorKey: "axes",
				header: V("showroom.catalog.families.axes")
			},
			{
				accessorKey: "attributes",
				header: V("showroom.catalog.families.attributes")
			}
		]), te = h(() => Z.value === null ? [] : [{
			key: "common",
			level: V("showroom.catalog.family_variants.common"),
			axes: V("showroom.catalog.none"),
			attributes: $(Z.value.common_attributes)
		}, ...(Z.value.levels ?? []).map((e) => ({
			key: String(e.level),
			level: String(e.level),
			axes: $(e.axes),
			attributes: $(e.attributes)
		}))]);
		function $(e) {
			return e && e.length > 0 ? e.join(", ") : V("showroom.catalog.none");
		}
		async function ne() {
			if (!K || !window.confirm(V("showroom.catalog.family_variants.confirm_delete"))) return;
			let e = Z.value?.family;
			await W.run(async () => n(await K.DELETE("/family-variants/{familyVariant}", { params: { path: { familyVariant: G.value } } }))) && await l(e ? o(e) : "/showroom/families");
		}
		return (n, r) => {
			let a = u, l = f, h = p, S = ee, O = d, k = c;
			return C(), v("div", A, [x(k, {
				"screen-key": "showroom.family-variant",
				record: E(G)
			}, {
				default: D(() => [x(t, {
					ready: !E(J) && E(Z) !== null,
					failed: E(Y),
					status: E(X)
				}, {
					default: D(() => [E(Z) ? (C(), v("div", j, [
						x(l, { headline: E(V)("showroom.catalog.fields.family_variant") }, {
							title: D(() => [y("span", M, T(E(i)(E(Z).labels, E(H), E(Z).code)), 1)]),
							description: D(() => [y("span", N, [b(T(E(Z).code), 1), E(Z).family ? (C(), v(m, { key: 0 }, [b(" · " + T(E(Z).family), 1)], 64)) : _("", !0)])]),
							links: D(() => [E(Z).family ? (C(), g(a, {
								key: 0,
								to: E(o)(E(Z).family),
								variant: "outline",
								icon: "i-lucide-arrow-left",
								"data-testid": "showroom-family-variant-family"
							}, {
								default: D(() => [b(T(E(Z).family), 1)]),
								_: 1
							}, 8, ["to"])) : _("", !0), x(a, {
								color: "error",
								variant: "outline",
								icon: "i-lucide-trash-2",
								loading: E(W).busy.value,
								"data-testid": "showroom-family-variant-delete",
								onClick: ne
							}, {
								default: D(() => [b(T(E(V)("showroom.catalog.delete")), 1)]),
								_: 1
							}, 8, ["loading"])]),
							_: 1
						}, 8, ["headline"]),
						x(e, { action: E(W) }, null, 8, ["action"]),
						x(S, { "data-testid": "showroom-family-variant-levels" }, {
							header: D(() => [y("h2", P, T(E(V)("showroom.catalog.family_variants.levels")), 1)]),
							default: D(() => [x(h, {
								data: E(te),
								columns: E(Q)
							}, {
								"level-cell": D(({ row: { original: e } }) => [y("span", { "data-testid": `showroom-level-${e.key}` }, T(e.level), 9, F)]),
								"axes-cell": D(({ row: { original: e } }) => [y("span", I, T(e.axes), 1)]),
								"attributes-cell": D(({ row: { original: e } }) => [y("span", L, T(e.attributes), 1)]),
								_: 1
							}, 8, ["data", "columns"])]),
							_: 1
						}),
						x(S, { "data-testid": "showroom-family-variant-models" }, {
							header: D(() => [y("h2", R, T(E(V)("showroom.catalog.product_models.title")), 1)]),
							default: D(() => [(E(q)?.models ?? []).length === 0 ? (C(), v("p", z, T(E(V)("showroom.catalog.product_models.empty")), 1)) : (C(), v("ul", B, [(C(!0), v(m, null, w(E(q)?.models ?? [], (e) => (C(), v("li", { key: e.code }, [x(O, {
								to: E(s)(e.code),
								class: "font-mono text-sm hover:underline",
								"data-testid": `showroom-family-variant-model-${e.code}`
							}, {
								default: D(() => [b(T(e.code), 1)]),
								_: 2
							}, 1032, ["to", "data-testid"])]))), 128))]))]),
							_: 1
						})
					])) : _("", !0)]),
					_: 1
				}, 8, [
					"ready",
					"failed",
					"status"
				])]),
				_: 1
			}, 8, ["record"])]);
		};
	}
});
export { V as default };
