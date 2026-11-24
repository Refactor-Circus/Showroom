import { t as e } from "./CatalogPager-BwJ4cMnn.js";
import { t } from "./CatalogState-Bkly-MlG.js";
import { A as n, C as r, D as i, S as a, f as o, k as s, r as c } from "./catalog-DCoO3A8a.js";
import { n as l, t as u } from "./structure-DbqcM-iO.js";
import { AtriumIntegrationScreen as d, AtriumPageHeader as f, AtriumSelect as ee, AtriumState as p } from "@refactor-circus/atrium";
import { UBadge as m, UButton as h, UCard as g, UFormField as _, UInput as v, ULink as y, UTable as b } from "@refactor-circus/atrium/ui";
import { Fragment as x, computed as S, createBlock as C, createCommentVNode as w, createElementBlock as T, createElementVNode as E, createTextVNode as D, createVNode as O, defineComponent as k, openBlock as A, reactive as j, renderList as te, toDisplayString as M, unref as N, watch as P, withCtx as F, withModifiers as I } from "vue";
import { useI18n as L } from "vue-i18n";
import { useRoute as R, useRouter as z } from "vue-router";
// src/pages/attributes/index.vue?vue&type=script&setup=true&lang.ts
var B = {
	class: "space-y-4",
	"data-testid": "showroom-attributes"
}, V = { class: "space-y-4" }, H = { class: "space-y-4" }, U = ["data-testid"], W = { class: "flex flex-wrap gap-1" }, G = /* @__PURE__ */ k({
	__name: "index",
	setup(k) {
		let G = [
			"search",
			"type",
			"group"
		], { t: K, locale: q } = L(), J = R(), Y = z(), X = S(() => {
			let e = [...G, "cursor"].map((e) => [e, a(J.query[e])]).filter((e) => e[1] !== void 0);
			return Object.fromEntries(e);
		}), { data: Z, pending: ne, failed: re, status: ie } = s(async (e) => r(await e.GET("/attributes", { params: { query: i(X.value) } })), X), Q = n(async (e) => r(await e.GET("/attribute-groups", { params: { query: { per_page: 100 } } }))), ae = S(() => Z.value?.data ?? []), $ = j({
			search: "",
			type: "",
			group: ""
		});
		P(() => J.query, () => {
			for (let e of G) $[e] = a(J.query[e]) ?? "";
		}, { immediate: !0 });
		let oe = S(() => l.map((e) => ({
			label: e,
			value: e
		}))), se = S(() => Q.value.map((e) => ({
			label: o(e.labels, q.value, e.code),
			value: e.code
		}))), ce = S(() => [
			{
				accessorKey: "code",
				header: K("showroom.catalog.fields.code")
			},
			{
				id: "label",
				header: K("showroom.catalog.fields.label")
			},
			{
				accessorKey: "type",
				header: K("showroom.catalog.fields.type")
			},
			{
				accessorKey: "group",
				header: K("showroom.catalog.fields.group")
			},
			{
				id: "switches",
				header: K("showroom.structure.attributes.switches")
			}
		]);
		function le(e) {
			if (!e) return K("showroom.catalog.none");
			let t = Q.value.find((t) => t.code === e);
			return t ? o(t.labels, q.value, e) : e;
		}
		function ue() {
			Y.replace({ query: Object.fromEntries(G.map((e) => [e, $[e] || void 0])) });
		}
		function de() {
			Y.replace({ query: {} });
		}
		return (n, r) => {
			let i = h, a = f, s = v, l = _, S = ee, k = g, j = p, P = y, L = m, R = b, z = d;
			return A(), T("div", B, [O(a, { title: N(K)("showroom.structure.attributes.title") }, {
				default: F(() => [O(i, {
					to: "/showroom/attributes/create",
					icon: "i-lucide-plus",
					"data-testid": "showroom-attribute-new"
				}, {
					default: F(() => [D(M(N(K)("showroom.structure.attributes.new")), 1)]),
					_: 1
				})]),
				_: 1
			}, 8, ["title"]), O(z, { "screen-key": "showroom.attributes" }, {
				default: F(() => [E("div", V, [O(k, null, {
					default: F(() => [E("form", {
						class: "flex flex-wrap items-end gap-3",
						"data-testid": "showroom-attributes-filters",
						onSubmit: I(ue, ["prevent"])
					}, [
						O(l, { label: N(K)("showroom.catalog.filters.search") }, {
							default: F(() => [O(s, {
								modelValue: N($).search,
								"onUpdate:modelValue": r[0] ||= (e) => N($).search = e,
								class: "w-56",
								"data-testid": "showroom-attributes-search"
							}, null, 8, ["modelValue"])]),
							_: 1
						}, 8, ["label"]),
						O(l, { label: N(K)("showroom.catalog.fields.type") }, {
							default: F(() => [O(S, {
								modelValue: N($).type,
								"onUpdate:modelValue": r[1] ||= (e) => N($).type = e,
								items: N(oe),
								blank: N(K)("showroom.structure.attributes.all_types"),
								"blank-value": null,
								class: "w-44",
								"data-testid": "showroom-attributes-type"
							}, null, 8, [
								"modelValue",
								"items",
								"blank"
							])]),
							_: 1
						}, 8, ["label"]),
						O(l, { label: N(K)("showroom.catalog.fields.group") }, {
							default: F(() => [O(S, {
								modelValue: N($).group,
								"onUpdate:modelValue": r[2] ||= (e) => N($).group = e,
								items: N(se),
								blank: N(K)("showroom.structure.attributes.all_groups"),
								"blank-value": null,
								class: "w-48",
								"data-testid": "showroom-attributes-group"
							}, null, 8, [
								"modelValue",
								"items",
								"blank"
							])]),
							_: 1
						}, 8, ["label"]),
						O(i, {
							type: "submit",
							icon: "i-lucide-filter",
							"data-testid": "showroom-attributes-filter"
						}, {
							default: F(() => [D(M(N(K)("showroom.catalog.filters.filter")), 1)]),
							_: 1
						}),
						O(i, {
							variant: "ghost",
							icon: "i-lucide-x",
							"data-testid": "showroom-attributes-clear",
							onClick: de
						}, {
							default: F(() => [D(M(N(K)("showroom.catalog.filters.clear")), 1)]),
							_: 1
						})
					], 32)]),
					_: 1
				}), O(t, {
					ready: !N(ne) && N(Z) !== null,
					failed: N(re),
					status: N(ie)
				}, {
					default: F(() => [E("div", H, [O(R, {
						data: N(ae),
						columns: N(ce),
						"data-testid": "showroom-attributes-table"
					}, {
						empty: F(() => [O(j, {
							compact: "",
							title: N(K)("showroom.structure.attributes.empty"),
							"data-testid": "showroom-attributes-empty"
						}, null, 8, ["title"])]),
						"code-cell": F(({ row: { original: e } }) => [E("span", { "data-testid": `showroom-attribute-row-${e.code}` }, [O(P, {
							to: N(c)(e.code),
							class: "font-mono font-medium hover:underline"
						}, {
							default: F(() => [D(M(e.code), 1)]),
							_: 2
						}, 1032, ["to"])], 8, U)]),
						"label-cell": F(({ row: { original: e } }) => [D(M(N(o)(e.labels, N(q), e.code)), 1)]),
						"type-cell": F(({ row: { original: e } }) => [O(L, {
							color: "neutral",
							variant: "subtle"
						}, {
							default: F(() => [D(M(e.type), 1)]),
							_: 2
						}, 1024)]),
						"group-cell": F(({ row: { original: e } }) => [D(M(le(e.group)), 1)]),
						"switches-cell": F(({ row: { original: e } }) => [E("span", W, [(A(!0), T(x, null, te(N(u), (t) => (A(), T(x, { key: t }, [e[t] ? (A(), C(L, {
							key: 0,
							color: "neutral",
							variant: "outline"
						}, {
							default: F(() => [D(M(N(K)(`showroom.structure.attributes.${t}`)), 1)]),
							_: 2
						}, 1024)) : w("", !0)], 64))), 128))])]),
						_: 1
					}, 8, ["data", "columns"]), O(e, {
						next: N(Z)?.meta?.next_cursor ?? null,
						prev: N(Z)?.meta?.prev_cursor ?? null,
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
export { G as default };
