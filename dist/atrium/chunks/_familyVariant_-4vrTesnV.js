import { t as e } from "./CatalogActionError-BRUgojP9.js";
import { t } from "./CatalogState-Bkly-MlG.js";
import { C as n, O as ee, f as r, k as i, l as a, v as o } from "./catalog-DCoO3A8a.js";
import { AtriumIntegrationScreen as te, navigateTo as s } from "@refactor-circus/atrium";
import { UButton as ne, UCard as re } from "@refactor-circus/atrium/ui";
import { Fragment as c, computed as l, createBlock as ie, createCommentVNode as u, createElementBlock as d, createElementVNode as f, createTextVNode as p, createVNode as m, defineComponent as h, openBlock as g, renderList as _, resolveComponent as ae, toDisplayString as v, unref as y, withCtx as b } from "vue";
import { useI18n as x } from "vue-i18n";
import { useRoute as S } from "vue-router";
// src/pages/family-variants/[familyVariant].vue?vue&type=script&setup=true&lang.ts
var C = {
	class: "space-y-4",
	"data-testid": "showroom-family-variant"
}, w = {
	key: 0,
	class: "space-y-5"
}, T = { class: "flex flex-wrap items-start justify-between gap-3" }, E = {
	class: "text-xl font-semibold",
	"data-testid": "showroom-family-variant-title"
}, D = { class: "font-mono text-sm text-muted" }, O = { class: "flex flex-wrap items-center gap-2" }, k = { class: "font-semibold" }, A = { class: "w-full text-left text-sm" }, j = { class: "text-muted" }, M = { class: "py-1 pr-3 font-medium" }, N = { class: "py-1 pr-3 font-medium" }, P = { class: "py-1 font-medium" }, F = { class: "divide-y divide-default" }, I = { "data-testid": "showroom-level-common" }, L = { class: "py-1 pr-3" }, R = { class: "py-1 pr-3" }, z = { class: "py-1 font-mono text-xs" }, oe = ["data-testid"], B = { class: "py-1 pr-3" }, V = { class: "py-1 pr-3 font-mono text-xs" }, H = { class: "py-1 font-mono text-xs" }, U = { class: "font-semibold" }, W = {
	key: 0,
	class: "text-sm text-muted"
}, G = {
	key: 1,
	class: "flex flex-wrap gap-2"
}, K = /* @__PURE__ */ h({
	__name: "[familyVariant]",
	setup(h) {
		let { t: K, locale: q } = x(), se = S(), J = ee(), Y = l(() => String(se.params.familyVariant)), { client: X, data: Z, pending: ce, failed: le, status: ue } = i(async (e) => ({
			variant: n(await e.GET("/family-variants/{familyVariant}", { params: { path: { familyVariant: Y.value } } })).data,
			models: n(await e.GET("/product-models", { params: { query: {
				family_variant: Y.value,
				per_page: 100
			} } })).data
		}), Y), Q = l(() => Z.value?.variant ?? null);
		function $(e) {
			return e && e.length > 0 ? e.join(", ") : K("showroom.catalog.none");
		}
		async function de() {
			if (!X || !window.confirm(K("showroom.catalog.family_variants.confirm_delete"))) return;
			let e = Q.value?.family;
			await J.run(async () => n(await X.DELETE("/family-variants/{familyVariant}", { params: { path: { familyVariant: Y.value } } }))) && await s(e ? a(e) : "/showroom/families");
		}
		return (n, ee) => {
			let i = ne, s = re, l = ae("RouterLink"), h = te;
			return g(), d("div", C, [m(h, {
				"screen-key": "showroom.family-variant",
				record: y(Y)
			}, {
				default: b(() => [m(t, {
					ready: !y(ce) && y(Q) !== null,
					failed: y(le),
					status: y(ue)
				}, {
					default: b(() => [y(Q) ? (g(), d("div", w, [
						f("div", T, [f("div", null, [f("h1", E, v(y(r)(y(Q).labels, y(q), y(Q).code)), 1), f("p", D, [p(v(y(Q).code), 1), y(Q).family ? (g(), d(c, { key: 0 }, [p(" · " + v(y(Q).family), 1)], 64)) : u("", !0)])]), f("div", O, [y(Q).family ? (g(), ie(i, {
							key: 0,
							to: y(a)(y(Q).family),
							variant: "outline",
							icon: "i-lucide-arrow-left",
							"data-testid": "showroom-family-variant-family"
						}, {
							default: b(() => [p(v(y(Q).family), 1)]),
							_: 1
						}, 8, ["to"])) : u("", !0), m(i, {
							color: "error",
							variant: "outline",
							icon: "i-lucide-trash-2",
							loading: y(J).busy.value,
							"data-testid": "showroom-family-variant-delete",
							onClick: de
						}, {
							default: b(() => [p(v(y(K)("showroom.catalog.delete")), 1)]),
							_: 1
						}, 8, ["loading"])])]),
						m(e, { action: y(J) }, null, 8, ["action"]),
						m(s, { "data-testid": "showroom-family-variant-levels" }, {
							header: b(() => [f("h2", k, v(y(K)("showroom.catalog.family_variants.levels")), 1)]),
							default: b(() => [f("table", A, [f("thead", j, [f("tr", null, [
								f("th", M, v(y(K)("showroom.catalog.family_variants.level")), 1),
								f("th", N, v(y(K)("showroom.catalog.families.axes")), 1),
								f("th", P, v(y(K)("showroom.catalog.families.attributes")), 1)
							])]), f("tbody", F, [f("tr", I, [
								f("td", L, v(y(K)("showroom.catalog.family_variants.common")), 1),
								f("td", R, v(y(K)("showroom.catalog.none")), 1),
								f("td", z, v($(y(Q).common_attributes)), 1)
							]), (g(!0), d(c, null, _(y(Q).levels ?? [], (e) => (g(), d("tr", {
								key: e.level,
								"data-testid": `showroom-level-${e.level}`
							}, [
								f("td", B, v(e.level), 1),
								f("td", V, v($(e.axes)), 1),
								f("td", H, v($(e.attributes)), 1)
							], 8, oe))), 128))])])]),
							_: 1
						}),
						m(s, { "data-testid": "showroom-family-variant-models" }, {
							header: b(() => [f("h2", U, v(y(K)("showroom.catalog.product_models.title")), 1)]),
							default: b(() => [(y(Z)?.models ?? []).length === 0 ? (g(), d("p", W, v(y(K)("showroom.catalog.product_models.empty")), 1)) : (g(), d("ul", G, [(g(!0), d(c, null, _(y(Z)?.models ?? [], (e) => (g(), d("li", { key: e.code }, [m(l, {
								to: y(o)(e.code),
								class: "font-mono text-sm hover:underline",
								"data-testid": `showroom-family-variant-model-${e.code}`
							}, {
								default: b(() => [p(v(e.code), 1)]),
								_: 2
							}, 1032, ["to", "data-testid"])]))), 128))]))]),
							_: 1
						})
					])) : u("", !0)]),
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
export { K as default };
