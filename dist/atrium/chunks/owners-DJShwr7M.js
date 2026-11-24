import { t as e } from "./CatalogActionError-BRUgojP9.js";
import { t } from "./CatalogPager-BwJ4cMnn.js";
import { t as n } from "./CatalogState-Bkly-MlG.js";
import { A as ee, C as r, D as i, O as a, S as o, a as s, f as c, k as l } from "./catalog-DCoO3A8a.js";
import { g as u } from "./structure-DbqcM-iO.js";
import { t as d } from "./owners-C7wgNkGA.js";
import { AtriumIntegrationScreen as te, AtriumPageHeader as ne, AtriumSelect as f, AtriumState as re } from "@refactor-circus/atrium";
import { UBadge as ie, UButton as ae, UCard as oe, UFormField as p, UInput as m, ULink as h, USelect as se, UTable as ce } from "@refactor-circus/atrium/ui";
import { computed as g, createElementBlock as _, createElementVNode as v, createTextVNode as y, createVNode as b, defineComponent as x, normalizeStyle as le, openBlock as S, reactive as C, toDisplayString as w, unref as T, watch as E, withCtx as D, withModifiers as O } from "vue";
import { useI18n as k } from "vue-i18n";
import { useRoute as A, useRouter as j } from "vue-router";
// src/pages/owners/index.vue?vue&type=script&setup=true&lang.ts
var M = {
	class: "space-y-4",
	"data-testid": "showroom-owners-page"
}, N = { class: "space-y-4" }, P = { class: "font-semibold" }, F = {
	key: 0,
	class: "text-sm text-muted",
	"data-testid": "showroom-owners-no-types"
}, I = { class: "flex flex-wrap items-start gap-3" }, L = { class: "space-y-4" }, R = ["data-testid"], z = ["title"], B = { class: "font-mono text-xs" }, V = { class: "tabular-nums" }, H = /* @__PURE__ */ x({
	__name: "index",
	setup(x) {
		let { t: H, locale: U } = k(), W = A(), G = j(), K = a(), q = g(() => {
			let e = {};
			for (let t of [
				"search",
				"type",
				"cursor"
			]) {
				let n = o(W.query[t]);
				n && (e[t] = n);
			}
			return e;
		}), { client: J, data: Y, pending: ue, failed: de, status: fe, reload: pe } = l(async (e) => r(await e.GET("/owners", { params: { query: i(q.value) } })), q), me = g(() => Y.value?.data ?? []), X = ee(async (e) => r(await e.GET("/owner-types", { params: { query: { per_page: 100 } } }))), Z = C({
			search: "",
			type: ""
		});
		E(() => W.query, () => {
			Z.search = o(W.query.search) ?? "", Z.type = o(W.query.type) ?? "";
		}, { immediate: !0 });
		function he() {
			G.replace({ query: {
				...Z.search.trim() ? { search: Z.search.trim() } : {},
				...Z.type ? { type: Z.type } : {}
			} });
		}
		function ge() {
			G.replace({ query: {} });
		}
		let Q = g(() => X.value.map((e) => ({
			label: c(e.labels, U.value, e.code),
			value: e.code
		}))), _e = g(() => [
			{
				accessorKey: "code",
				header: H("showroom.catalog.fields.code")
			},
			{
				id: "label",
				header: H("showroom.catalog.fields.label")
			},
			{
				accessorKey: "type",
				header: H("showroom.catalog.fields.type")
			},
			{
				accessorKey: "parent",
				header: H("showroom.catalog.fields.parent")
			},
			{
				accessorKey: "children_count",
				header: H("showroom.owners.children"),
				meta: { class: {
					th: "text-right",
					td: "text-right"
				} }
			},
			{
				accessorKey: "enabled",
				header: H("showroom.catalog.fields.enabled")
			}
		]), $ = C({
			code: "",
			type: "",
			parent: "",
			label: ""
		});
		async function ve() {
			if (!J) return;
			let e = s($.parent), t = {
				code: $.code.trim(),
				type: $.type,
				...e ? { parent: e } : {},
				...u(U.value, $.label)
			};
			await K.run(async () => r(await J.POST("/owners", { body: i(t) }))) && ($.code = "", $.parent = "", $.label = "", await pe());
		}
		function ye(e) {
			let t = X.value.find((t) => t.code === e);
			return t ? c(t.labels, U.value, t.code) : e ?? H("showroom.catalog.none");
		}
		return (ee, r) => {
			let i = ae, a = ne, o = m, s = p, l = se, u = oe, g = f, x = re, C = h, E = ie, k = ce, A = te;
			return S(), _("div", M, [b(a, {
				title: T(H)("showroom.owners.title"),
				description: T(H)("showroom.owners.description")
			}, {
				default: D(() => [b(i, {
					to: "/showroom/owner-types",
					variant: "outline",
					icon: "i-lucide-shapes",
					"data-testid": "showroom-owners-owner-types"
				}, {
					default: D(() => [y(w(T(H)("showroom.owner_types.title")), 1)]),
					_: 1
				}), b(i, {
					to: "/showroom/flags",
					variant: "outline",
					icon: "i-lucide-flag",
					"data-testid": "showroom-owners-flags"
				}, {
					default: D(() => [y(w(T(H)("showroom.owners.flags")), 1)]),
					_: 1
				})]),
				_: 1
			}, 8, ["title", "description"]), b(A, { "screen-key": "showroom.owners" }, {
				default: D(() => [v("div", N, [
					b(u, { "data-testid": "showroom-owner-new" }, {
						header: D(() => [v("h2", P, w(T(H)("showroom.owners.new")), 1)]),
						default: D(() => [T(X).length === 0 ? (S(), _("p", F, w(T(H)("showroom.owners.no_types")), 1)) : (S(), _("form", {
							key: 1,
							class: "flex flex-col gap-3",
							"data-testid": "showroom-owner-create-form",
							onSubmit: O(ve, ["prevent"])
						}, [b(e, { action: T(K) }, null, 8, ["action"]), v("div", I, [
							b(s, {
								label: T(H)("showroom.catalog.fields.code"),
								hint: T(H)("showroom.owners.code_hint"),
								error: T(K).error("code"),
								required: ""
							}, {
								default: D(() => [b(o, {
									modelValue: T($).code,
									"onUpdate:modelValue": r[0] ||= (e) => T($).code = e,
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
							b(s, {
								label: T(H)("showroom.catalog.fields.type"),
								error: T(K).error("type"),
								required: ""
							}, {
								default: D(() => [b(l, {
									modelValue: T($).type,
									"onUpdate:modelValue": r[1] ||= (e) => T($).type = e,
									items: T(Q),
									placeholder: T(H)("showroom.owners.choose_type"),
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
							b(s, {
								label: T(H)("showroom.catalog.fields.parent"),
								hint: T(H)("showroom.owners.parent_hint"),
								error: T(K).error("parent")
							}, {
								default: D(() => [b(o, {
									modelValue: T($).parent,
									"onUpdate:modelValue": r[2] ||= (e) => T($).parent = e,
									class: "w-44",
									"data-testid": "showroom-owner-create-parent"
								}, null, 8, ["modelValue"])]),
								_: 1
							}, 8, [
								"label",
								"hint",
								"error"
							]),
							b(s, {
								label: T(H)("showroom.catalog.fields.label_in", { locale: T(U) }),
								error: T(K).error("labels")
							}, {
								default: D(() => [b(o, {
									modelValue: T($).label,
									"onUpdate:modelValue": r[3] ||= (e) => T($).label = e,
									class: "w-48",
									"data-testid": "showroom-owner-create-label"
								}, null, 8, ["modelValue"])]),
								_: 1
							}, 8, ["label", "error"]),
							b(i, {
								type: "submit",
								icon: "i-lucide-plus",
								loading: T(K).busy.value,
								class: "mt-field-label",
								"data-testid": "showroom-owner-create-submit"
							}, {
								default: D(() => [y(w(T(H)("showroom.catalog.create")), 1)]),
								_: 1
							}, 8, ["loading"])
						])], 32))]),
						_: 1
					}),
					b(u, null, {
						default: D(() => [v("form", {
							class: "flex flex-wrap items-start gap-3",
							"data-testid": "showroom-owners-filters",
							onSubmit: O(he, ["prevent"])
						}, [
							b(s, { label: T(H)("showroom.catalog.filters.search") }, {
								default: D(() => [b(o, {
									modelValue: T(Z).search,
									"onUpdate:modelValue": r[4] ||= (e) => T(Z).search = e,
									class: "w-56",
									"data-testid": "showroom-owners-search"
								}, null, 8, ["modelValue"])]),
								_: 1
							}, 8, ["label"]),
							b(s, { label: T(H)("showroom.catalog.fields.type") }, {
								default: D(() => [b(g, {
									modelValue: T(Z).type,
									"onUpdate:modelValue": r[5] ||= (e) => T(Z).type = e,
									items: T(Q),
									blank: T(H)("showroom.owners.all_types"),
									"blank-value": null,
									class: "w-44",
									"data-testid": "showroom-owners-type"
								}, null, 8, [
									"modelValue",
									"items",
									"blank"
								])]),
								_: 1
							}, 8, ["label"]),
							b(i, {
								type: "submit",
								icon: "i-lucide-filter",
								class: "mt-field-label"
							}, {
								default: D(() => [y(w(T(H)("showroom.catalog.filters.filter")), 1)]),
								_: 1
							}),
							b(i, {
								variant: "ghost",
								color: "neutral",
								icon: "i-lucide-x",
								class: "mt-field-label",
								onClick: ge
							}, {
								default: D(() => [y(w(T(H)("showroom.catalog.filters.clear")), 1)]),
								_: 1
							})
						], 32)]),
						_: 1
					}),
					b(n, {
						ready: !T(ue) && T(Y) !== null,
						failed: T(de),
						status: T(fe)
					}, {
						default: D(() => [v("div", L, [b(k, {
							data: T(me),
							columns: T(_e),
							"data-testid": "showroom-owners-table"
						}, {
							empty: D(() => [b(x, {
								compact: "",
								title: T(H)("showroom.owners.empty"),
								"data-testid": "showroom-owners-empty"
							}, null, 8, ["title"])]),
							"code-cell": D(({ row: { original: e } }) => [v("span", {
								style: le({ paddingLeft: `${e.depth * .75}rem` }),
								"data-testid": `showroom-owner-row-${e.code}`
							}, [b(C, {
								to: T(d)(e.id),
								class: "font-mono font-medium hover:underline"
							}, {
								default: D(() => [y(w(e.code), 1)]),
								_: 2
							}, 1032, ["to"])], 12, R)]),
							"label-cell": D(({ row: { original: e } }) => [y(w(T(c)(e.labels, T(U), e.code)), 1)]),
							"type-cell": D(({ row: { original: e } }) => [v("span", {
								class: "font-mono text-xs",
								title: ye(e.type)
							}, w(e.type ?? T(H)("showroom.catalog.none")), 9, z)]),
							"parent-cell": D(({ row: { original: e } }) => [v("span", B, w(e.parent ?? T(H)("showroom.catalog.none")), 1)]),
							"children_count-cell": D(({ row: { original: e } }) => [v("span", V, w(e.children_count ?? 0), 1)]),
							"enabled-cell": D(({ row: { original: e } }) => [b(E, {
								color: e.enabled ? "success" : "neutral",
								variant: "subtle"
							}, {
								default: D(() => [y(w(e.enabled ? T(H)("showroom.catalog.enabled") : T(H)("showroom.catalog.disabled")), 1)]),
								_: 2
							}, 1032, ["color"])]),
							_: 1
						}, 8, ["data", "columns"]), b(t, {
							next: T(Y)?.meta?.next_cursor ?? null,
							prev: T(Y)?.meta?.prev_cursor ?? null,
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
export { H as default };
