import { t as e } from "./CatalogActionError-BRUgojP9.js";
import { t } from "./CatalogPager-BwJ4cMnn.js";
import { t as n } from "./CatalogState-Bkly-MlG.js";
import { A as r, C as i, D as a, O as o, S as s, a as c, f as l, k as u } from "./catalog-DCoO3A8a.js";
import { g as d } from "./structure-DbqcM-iO.js";
import { t as f } from "./owners-C7wgNkGA.js";
import { AtriumIntegrationScreen as p, AtriumPageHeader as m, AtriumState as ee } from "@refactor-circus/atrium";
import { UBadge as te, UButton as ne, UCard as re, UFormField as ie, UInput as ae, ULink as oe, USelect as se, UTable as ce } from "@refactor-circus/atrium/ui";
import { computed as h, createElementBlock as g, createElementVNode as _, createTextVNode as v, createVNode as y, defineComponent as b, normalizeStyle as le, openBlock as x, reactive as S, toDisplayString as C, unref as w, watch as T, withCtx as E, withModifiers as D } from "vue";
import { useI18n as O } from "vue-i18n";
import { useRoute as k, useRouter as A } from "vue-router";
// src/pages/owners/index.vue?vue&type=script&setup=true&lang.ts
var j = {
	class: "space-y-4",
	"data-testid": "showroom-owners-page"
}, M = { class: "space-y-4" }, N = { class: "font-semibold" }, P = {
	key: 0,
	class: "text-sm text-muted",
	"data-testid": "showroom-owners-no-types"
}, F = { class: "flex flex-wrap items-end gap-3" }, I = { class: "space-y-4" }, L = ["data-testid"], R = ["title"], z = { class: "font-mono text-xs" }, B = { class: "tabular-nums" }, V = /* @__PURE__ */ b({
	__name: "index",
	setup(b) {
		let { t: V, locale: H } = O(), U = k(), W = A(), G = o(), K = h(() => {
			let e = {};
			for (let t of [
				"search",
				"type",
				"cursor"
			]) {
				let n = s(U.query[t]);
				n && (e[t] = n);
			}
			return e;
		}), { client: q, data: J, pending: ue, failed: de, status: fe, reload: pe } = u(async (e) => i(await e.GET("/owners", { params: { query: a(K.value) } })), K), me = h(() => J.value?.data ?? []), Y = r(async (e) => i(await e.GET("/owner-types", { params: { query: { per_page: 100 } } }))), X = S({
			search: "",
			type: ""
		});
		T(() => U.query, () => {
			X.search = s(U.query.search) ?? "", X.type = s(U.query.type) ?? "";
		}, { immediate: !0 });
		function he() {
			W.replace({ query: {
				...X.search.trim() ? { search: X.search.trim() } : {},
				...X.type ? { type: X.type } : {}
			} });
		}
		function Z() {
			W.replace({ query: {} });
		}
		let Q = h(() => Y.value.map((e) => ({
			label: l(e.labels, H.value, e.code),
			value: e.code
		}))), ge = h(() => [{
			label: V("showroom.owners.all_types"),
			value: null
		}, ...Q.value]), _e = h(() => [
			{
				accessorKey: "code",
				header: V("showroom.catalog.fields.code")
			},
			{
				id: "label",
				header: V("showroom.catalog.fields.label")
			},
			{
				accessorKey: "type",
				header: V("showroom.catalog.fields.type")
			},
			{
				accessorKey: "parent",
				header: V("showroom.catalog.fields.parent")
			},
			{
				accessorKey: "children_count",
				header: V("showroom.owners.children"),
				meta: { class: {
					th: "text-right",
					td: "text-right"
				} }
			},
			{
				accessorKey: "enabled",
				header: V("showroom.catalog.fields.enabled")
			}
		]), $ = S({
			code: "",
			type: "",
			parent: "",
			label: ""
		});
		async function ve() {
			if (!q) return;
			let e = c($.parent), t = {
				code: $.code.trim(),
				type: $.type,
				...e ? { parent: e } : {},
				...d(H.value, $.label)
			};
			await G.run(async () => i(await q.POST("/owners", { body: a(t) }))) && ($.code = "", $.parent = "", $.label = "", await pe());
		}
		function ye(e) {
			let t = Y.value.find((t) => t.code === e);
			return t ? l(t.labels, H.value, t.code) : e ?? V("showroom.catalog.none");
		}
		return (r, i) => {
			let a = ne, o = m, s = ae, c = ie, u = se, d = re, h = ee, b = oe, S = te, T = ce, O = p;
			return x(), g("div", j, [y(o, {
				title: w(V)("showroom.owners.title"),
				description: w(V)("showroom.owners.description")
			}, {
				default: E(() => [y(a, {
					to: "/showroom/owner-types",
					variant: "outline",
					icon: "i-lucide-shapes",
					"data-testid": "showroom-owners-owner-types"
				}, {
					default: E(() => [v(C(w(V)("showroom.owner_types.title")), 1)]),
					_: 1
				}), y(a, {
					to: "/showroom/flags",
					variant: "outline",
					icon: "i-lucide-flag",
					"data-testid": "showroom-owners-flags"
				}, {
					default: E(() => [v(C(w(V)("showroom.owners.flags")), 1)]),
					_: 1
				})]),
				_: 1
			}, 8, ["title", "description"]), y(O, { "screen-key": "showroom.owners" }, {
				default: E(() => [_("div", M, [
					y(d, { "data-testid": "showroom-owner-new" }, {
						header: E(() => [_("h2", N, C(w(V)("showroom.owners.new")), 1)]),
						default: E(() => [w(Y).length === 0 ? (x(), g("p", P, C(w(V)("showroom.owners.no_types")), 1)) : (x(), g("form", {
							key: 1,
							class: "flex flex-col gap-3",
							"data-testid": "showroom-owner-create-form",
							onSubmit: D(ve, ["prevent"])
						}, [y(e, { action: w(G) }, null, 8, ["action"]), _("div", F, [
							y(c, {
								label: w(V)("showroom.catalog.fields.code"),
								hint: w(V)("showroom.owners.code_hint"),
								error: w(G).error("code"),
								required: ""
							}, {
								default: E(() => [y(s, {
									modelValue: w($).code,
									"onUpdate:modelValue": i[0] ||= (e) => w($).code = e,
									required: "",
									class: "w-48",
									"data-testid": "showroom-owner-create-code"
								}, null, 8, ["modelValue"])]),
								_: 1
							}, 8, [
								"label",
								"hint",
								"error"
							]),
							y(c, {
								label: w(V)("showroom.catalog.fields.type"),
								error: w(G).error("type"),
								required: ""
							}, {
								default: E(() => [y(u, {
									modelValue: w($).type,
									"onUpdate:modelValue": i[1] ||= (e) => w($).type = e,
									items: w(Q),
									placeholder: w(V)("showroom.owners.choose_type"),
									required: "",
									class: "w-44",
									"data-testid": "showroom-owner-create-type"
								}, null, 8, [
									"modelValue",
									"items",
									"placeholder"
								])]),
								_: 1
							}, 8, ["label", "error"]),
							y(c, {
								label: w(V)("showroom.catalog.fields.parent"),
								hint: w(V)("showroom.owners.parent_hint"),
								error: w(G).error("parent")
							}, {
								default: E(() => [y(s, {
									modelValue: w($).parent,
									"onUpdate:modelValue": i[2] ||= (e) => w($).parent = e,
									class: "w-44",
									"data-testid": "showroom-owner-create-parent"
								}, null, 8, ["modelValue"])]),
								_: 1
							}, 8, [
								"label",
								"hint",
								"error"
							]),
							y(c, {
								label: w(V)("showroom.catalog.fields.label_in", { locale: w(H) }),
								error: w(G).error("labels")
							}, {
								default: E(() => [y(s, {
									modelValue: w($).label,
									"onUpdate:modelValue": i[3] ||= (e) => w($).label = e,
									class: "w-48",
									"data-testid": "showroom-owner-create-label"
								}, null, 8, ["modelValue"])]),
								_: 1
							}, 8, ["label", "error"]),
							y(a, {
								type: "submit",
								icon: "i-lucide-plus",
								loading: w(G).busy.value,
								"data-testid": "showroom-owner-create-submit"
							}, {
								default: E(() => [v(C(w(V)("showroom.catalog.create")), 1)]),
								_: 1
							}, 8, ["loading"])
						])], 32))]),
						_: 1
					}),
					y(d, null, {
						default: E(() => [_("form", {
							class: "flex flex-wrap items-end gap-3",
							"data-testid": "showroom-owners-filters",
							onSubmit: D(he, ["prevent"])
						}, [
							y(c, { label: w(V)("showroom.catalog.filters.search") }, {
								default: E(() => [y(s, {
									modelValue: w(X).search,
									"onUpdate:modelValue": i[4] ||= (e) => w(X).search = e,
									class: "w-56",
									"data-testid": "showroom-owners-search"
								}, null, 8, ["modelValue"])]),
								_: 1
							}, 8, ["label"]),
							y(c, { label: w(V)("showroom.catalog.fields.type") }, {
								default: E(() => [y(u, {
									modelValue: w(X).type,
									"onUpdate:modelValue": i[5] ||= (e) => w(X).type = e,
									items: w(ge),
									placeholder: w(V)("showroom.owners.all_types"),
									class: "w-44",
									"data-testid": "showroom-owners-type"
								}, null, 8, [
									"modelValue",
									"items",
									"placeholder"
								])]),
								_: 1
							}, 8, ["label"]),
							y(a, {
								type: "submit",
								icon: "i-lucide-filter"
							}, {
								default: E(() => [v(C(w(V)("showroom.catalog.filters.filter")), 1)]),
								_: 1
							}),
							y(a, {
								variant: "ghost",
								color: "neutral",
								icon: "i-lucide-x",
								onClick: Z
							}, {
								default: E(() => [v(C(w(V)("showroom.catalog.filters.clear")), 1)]),
								_: 1
							})
						], 32)]),
						_: 1
					}),
					y(n, {
						ready: !w(ue) && w(J) !== null,
						failed: w(de),
						status: w(fe)
					}, {
						default: E(() => [_("div", I, [y(T, {
							data: w(me),
							columns: w(_e),
							"data-testid": "showroom-owners-table"
						}, {
							empty: E(() => [y(h, {
								compact: "",
								title: w(V)("showroom.owners.empty"),
								"data-testid": "showroom-owners-empty"
							}, null, 8, ["title"])]),
							"code-cell": E(({ row: { original: e } }) => [_("span", {
								style: le({ paddingLeft: `${e.depth * .75}rem` }),
								"data-testid": `showroom-owner-row-${e.code}`
							}, [y(b, {
								to: w(f)(e.id),
								class: "font-mono font-medium hover:underline"
							}, {
								default: E(() => [v(C(e.code), 1)]),
								_: 2
							}, 1032, ["to"])], 12, L)]),
							"label-cell": E(({ row: { original: e } }) => [v(C(w(l)(e.labels, w(H), e.code)), 1)]),
							"type-cell": E(({ row: { original: e } }) => [_("span", {
								class: "font-mono text-xs",
								title: ye(e.type)
							}, C(e.type ?? w(V)("showroom.catalog.none")), 9, R)]),
							"parent-cell": E(({ row: { original: e } }) => [_("span", z, C(e.parent ?? w(V)("showroom.catalog.none")), 1)]),
							"children_count-cell": E(({ row: { original: e } }) => [_("span", B, C(e.children_count ?? 0), 1)]),
							"enabled-cell": E(({ row: { original: e } }) => [y(S, {
								color: e.enabled ? "success" : "neutral",
								variant: "subtle"
							}, {
								default: E(() => [v(C(e.enabled ? w(V)("showroom.catalog.enabled") : w(V)("showroom.catalog.disabled")), 1)]),
								_: 2
							}, 1032, ["color"])]),
							_: 1
						}, 8, ["data", "columns"]), y(t, {
							next: w(J)?.meta?.next_cursor ?? null,
							prev: w(J)?.meta?.prev_cursor ?? null,
							testid: "showroom-owners"
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
